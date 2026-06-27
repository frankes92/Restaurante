<?php
/**
 * ReporteExcel — genera el libro Excel "profesional" de reportes de ventas.
 *
 * Tema visual: OCÉANO HABANA
 *   - Azul marino profundo  #0B3D5C  (encabezado/portada)
 *   - Turquesa              #0E7C8B  (cabeceras de tabla)
 *   - Cian claro            #E3F2F5  (tarjetas / franjas)
 *   - Dorado               #C9972B  (acentos / totales)
 *
 * Hojas: Resumen ejecutivo · Ventas detalladas · Top productos · Métodos & Días
 */
require_once __DIR__ . "/../config/Conexion.php";
require_once __DIR__ . "/ExcelWriter.php";
require_once __DIR__ . "/Orden.php";
require_once __DIR__ . "/Producto.php";
require_once __DIR__ . "/Empresa.php";

class ReporteExcel
{
    // Paleta del tema
    const C_NAVY    = '0B3D5C';
    const C_NAVY2   = '12506F';
    const C_TEAL    = '0E7C8B';
    const C_TEAL_D  = '0A5C68';
    const C_CIAN    = 'E3F2F5';
    const C_CIAN2   = 'F4FAFB';
    const C_GOLD    = 'C9972B';
    const C_GOLD_BG = 'FBF3DE';
    const C_INK     = '1B2733';
    const C_MUTE    = '5B6B7A';
    const C_LINE    = 'D7E3E8';
    const C_WHITE   = 'FFFFFF';
    const C_GREEN   = '15803D';
    const C_GREEN_BG= 'E7F6EC';
    const C_RED     = 'B42318';

    private $xls;

    public function generar($desde, $hasta)
    {
        $orden    = new Orden();
        $producto = new Producto();

        // ---- Cargar datos ----
        $tipos = [];
        $rsTipos = $orden->ventasPorTipoComprobante($desde, $hasta);
        while ($r = $rsTipos->fetch_assoc()) { $tipos[$r['tipo_comprobante']] = $r; }
        foreach (['ticket','nota_venta','boleta','factura'] as $t) {
            if (!isset($tipos[$t])) $tipos[$t] = ['cantidad'=>0,'total'=>0,'igv'=>0,'subtotal'=>0];
        }

        $ventas = [];
        $rsDet = $orden->ventasDetalle($desde, $hasta);
        while ($r = $rsDet->fetch_assoc()) { $ventas[] = $r; }

        $top = [];
        $rsTop = $producto->topVendidos(20);
        while ($r = $rsTop->fetch_assoc()) { $top[] = $r; }

        // Detalle por producto del rango: vendidos, ingresos, cortesias y stock
        $detProd = [];
        $rsDP = $producto->reporteProductosDetallado($desde, $hasta);
        while ($r = $rsDP->fetch_assoc()) { $detProd[] = $r; }

        $emp = (new Empresa())->mostrar(1) ?: [];

        // Métricas derivadas a partir del detalle (mismo universo que las tablas)
        $totalGral  = array_sum(array_column($tipos, 'total'));
        $igvGral    = array_sum(array_column($tipos, 'igv'));
        $subGral    = array_sum(array_column($tipos, 'subtotal'));
        $totalOrden = array_sum(array_column($tipos, 'cantidad'));
        $ticketProm = $totalOrden > 0 ? $totalGral / $totalOrden : 0;

        // Métodos de pago y ventas por día (a partir del detalle, respeta el rango)
        $porMetodo = [];
        $porDia    = [];
        $items     = 0;
        foreach ($ventas as $v) {
            $m = $v['metodo_pago'] ?: 'otro';
            if (!isset($porMetodo[$m])) $porMetodo[$m] = ['cantidad'=>0,'total'=>0];
            $porMetodo[$m]['cantidad']++;
            $porMetodo[$m]['total'] += (float)$v['total'];

            $d = substr((string)$v['fecha_pago'], 0, 10);
            if ($d !== '') {
                if (!isset($porDia[$d])) $porDia[$d] = ['cantidad'=>0,'total'=>0];
                $porDia[$d]['cantidad']++;
                $porDia[$d]['total'] += (float)$v['total'];
            }
            $items += (int)($v['total_items'] ?? 0);
        }
        ksort($porDia);
        arsort($porMetodo);

        $this->xls = new ExcelWriter();

        $ctx = compact('emp','desde','hasta','tipos','ventas','top','detProd','porMetodo','porDia',
                       'totalGral','igvGral','subGral','totalOrden','ticketProm','items');

        $this->hojaResumen($ctx);
        $this->hojaMetodosDias($ctx);
        $this->hojaTopProductos($ctx);
        $this->hojaDetalleProductos($ctx);
        $this->hojaDetalle($ctx);

        $nombre = 'Reporte_Ventas_PuertoHabana_' . $desde . '_a_' . $hasta . '.xlsx';
        $this->xls->download($nombre);
    }

    // ============================================================
    // REPORTE DE INVENTARIO (descarga independiente)
    // ============================================================
    public function generarInventario()
    {
        $producto = new Producto();
        $emp = (new Empresa())->mostrar(1) ?: [];

        $inv = [];
        $rs = $producto->reporteInventarioDetallado('');
        while ($r = $rs->fetch_assoc()) { $inv[] = $r; }
        $resumen = $producto->resumenInventario() ?: [];

        $this->xls = new ExcelWriter();
        $x = $this->xls;
        $x->addSheet('Inventario');
        $x->hideGridlines(true);

        $empNom = $emp['nombre_comercial'] ?? $emp['razon_social'] ?? 'PUERTO HABANA';
        $ruc    = $emp['numero_ruc'] ?? '—';

        // ---- Portada ----
        $x->mergeCells('A1','K1');
        $x->setCell('A1', mb_strtoupper($empNom,'UTF-8'),
            ['bold'=>true,'size'=>20,'color'=>self::C_WHITE,'bg'=>self::C_NAVY,'align'=>'left']);
        $x->setRowHeight(1, 32);
        $x->mergeCells('A2','K2');
        $x->setCell('A2','Reporte de Inventario',
            ['bold'=>true,'size'=>12,'color'=>'BFE3EC','bg'=>self::C_NAVY,'align'=>'left']);
        $x->setRowHeight(2, 20);
        $x->mergeCells('A3','K3');
        $x->setCell('A3','RUC ' . $ruc . '     ·     Generado el ' . date('d/m/Y H:i'),
            ['size'=>9,'italic'=>true,'color'=>self::C_MUTE,'align'=>'left']);
        $x->setRowHeight(3, 16);

        // ---- KPIs ----
        $valorTotal = (float)($resumen['valor_total'] ?? 0);
        $kpis = [
            ['Presentaciones', (int)($resumen['total'] ?? 0), 'int',  self::C_NAVY,  'CBE0EA'],
            ['Valor inventario', $valorTotal,                 'money',self::C_TEAL,  'C9E7ED'],
            ['Stock bajo',     (int)($resumen['bajos'] ?? 0), 'int',  self::C_GOLD,  self::C_GOLD_BG],
            ['Agotados',       (int)($resumen['agotados'] ?? 0),'int', self::C_RED,   'F7DEDA'],
        ];
        $cols = [['A','B','C'],['D','E','F'],['G','H'],['I','J','K']];
        $rL = 5; $rV = 6;
        foreach ($kpis as $i=>$k) {
            $span = $cols[$i]; $c1 = $span[0]; $c2 = end($span);
            $x->mergeCells($c1.$rL, $c2.$rL);
            $x->setCell($c1.$rL, mb_strtoupper($k[0],'UTF-8'), ['bold'=>true,'size'=>9,'color'=>self::C_MUTE,'bg'=>$k[4],'align'=>'left']);
            $x->mergeCells($c1.$rV, $c2.$rV);
            $x->setCell($c1.$rV, $k[1], ['bold'=>true,'size'=>16,'color'=>$k[3],'bg'=>$k[4],'align'=>'left','format'=>$k[2]]);
        }
        $x->setRowHeight($rL,18); $x->setRowHeight($rV,28);

        // ---- Tabla ----
        $headRow = 8;
        $heads  = ['#','Producto','Presentación','Categoría','Stock','Precio (S/)','Valor stock (S/)','Vendido','Cortesía','Mínimo','Estado'];
        $aligns = ['center','left','left','left','right','right','right','center','center','center','left'];
        foreach ($heads as $i=>$h) {
            $x->setCell($this->colL($i+1).$headRow, $h, $this->hdr($aligns[$i]));
        }
        $x->setRowHeight($headRow, 22);
        $x->freezePanes($headRow, 0);

        $semLabel = ['rojo'=>'Agotado','amarillo'=>'Stock bajo','verde'=>'En nivel'];
        $row = $headRow + 1; $n = 1;
        $sumStock = 0; $sumValor = 0; $sumVend = 0; $sumCort = 0;
        foreach ($inv as $p) {
            $bg = ($n % 2 === 1) ? self::C_WHITE : self::C_CIAN2;
            $pres = (strtolower((string)$p['presentacion']) === 'normal') ? '—' : $p['presentacion'];
            $stock = (float)$p['stock'];
            $valor = (float)$p['valor_stock'];
            $vend  = (int)$p['vendido'];
            $cort  = (int)$p['cortesia'];
            $x->setCell($this->colL(1).$row,  $n,                  $this->cell('center',$bg,'int'));
            $x->setCell($this->colL(2).$row,  $p['producto_nombre'], $this->cell('left',$bg));
            $x->setCell($this->colL(3).$row,  $pres,               $this->cell('left',$bg));
            $x->setCell($this->colL(4).$row,  $p['categoria_nombre'] ?: '—', $this->cell('left',$bg));
            $x->setCell($this->colL(5).$row,  $stock,              $this->cell('right',$bg,'int'));
            $x->setCell($this->colL(6).$row,  (float)$p['precio'], $this->cell('right',$bg,'money'));
            $x->setCell($this->colL(7).$row,  $valor,              $this->cell('right',$bg,'money',true));
            $x->setCell($this->colL(8).$row,  $vend,               $this->cell('center',$bg,'int'));
            $x->setCell($this->colL(9).$row,  $cort,               $this->cell('center',$bg,'int'));
            $x->setCell($this->colL(10).$row, (float)$p['stock_minimo'], $this->cell('center',$bg,'int'));
            $x->setCell($this->colL(11).$row, $semLabel[$p['semaforo']] ?? '—', $this->cell('left',$bg));
            $x->setRowHeight($row, 17);
            $sumStock += $stock; $sumValor += $valor; $sumVend += $vend; $sumCort += $cort;
            $row++; $n++;
        }
        // Totales
        $x->mergeCells($this->colL(1).$row, $this->colL(4).$row);
        $x->setCell($this->colL(1).$row, 'TOTAL', $this->totalCell('right'));
        $x->setCell($this->colL(5).$row,  $sumStock, $this->totalCell('right','int'));
        $x->setCell($this->colL(6).$row,  '',        $this->totalCell('right'));
        $x->setCell($this->colL(7).$row,  $sumValor, $this->totalCell('right','money'));
        $x->setCell($this->colL(8).$row,  $sumVend,  $this->totalCell('center','int'));
        $x->setCell($this->colL(9).$row,  $sumCort,  $this->totalCell('center','int'));
        $x->setCell($this->colL(10).$row, '',        $this->totalCell('center'));
        $x->setCell($this->colL(11).$row, '',        $this->totalCell('left'));
        $x->setRowHeight($row, 22);

        $widths = ['A'=>5,'B'=>30,'C'=>18,'D'=>18,'E'=>10,'F'=>12,'G'=>16,'H'=>10,'I'=>10,'J'=>10,'K'=>13];
        foreach ($widths as $col=>$w) $x->setColWidth($col,$w);

        $x->download('Reporte_Inventario_PuertoHabana_' . date('Y-m-d') . '.xlsx');
    }

    // ============================================================
    // HOJA 1 — RESUMEN EJECUTIVO
    // ============================================================
    private function hojaResumen($c)
    {
        $x = $this->xls;
        $x->addSheet('Resumen ejecutivo');
        $x->hideGridlines(true);

        $empNom = $c['emp']['nombre_comercial'] ?? $c['emp']['razon_social'] ?? 'PUERTO HABANA';
        $ruc    = $c['emp']['numero_ruc'] ?? '—';

        // ---- Banda de portada (A1:H4) ----
        $x->mergeCells('A1', 'H1');
        $x->setCell('A1', mb_strtoupper($empNom, 'UTF-8'),
            ['bold'=>true,'size'=>22,'color'=>self::C_WHITE,'bg'=>self::C_NAVY,'align'=>'left']);
        $x->setRowHeight(1, 34);

        $x->mergeCells('A2', 'H2');
        $x->setCell('A2', 'Reporte de Ventas',
            ['bold'=>true,'size'=>12,'color'=>'BFE3EC','bg'=>self::C_NAVY,'align'=>'left']);
        $x->setRowHeight(2, 20);

        $x->mergeCells('A3', 'H3');
        $x->setCell('A3', 'RUC ' . $ruc . '     ·     Periodo: ' . $this->fechaTxt($c['desde']) . '  →  ' . $this->fechaTxt($c['hasta']),
            ['size'=>10,'color'=>'DCEEF3','bg'=>self::C_NAVY2,'align'=>'left']);
        $x->setRowHeight(3, 18);

        $x->mergeCells('A4', 'H4');
        $x->setCell('A4', 'Generado el ' . date('d/m/Y H:i'),
            ['size'=>9,'italic'=>true,'color'=>self::C_MUTE,'align'=>'left']);
        $x->setRowHeight(4, 16);

        // ---- Tarjetas KPI (fila 6 etiqueta, 7 valor) ----
        $kpis = [
            ['Ventas totales', $c['totalGral'], 'money', self::C_TEAL,  'C9E7ED'],
            ['Órdenes',        $c['totalOrden'],'int',   self::C_NAVY,  'CBE0EA'],
            ['Ticket promedio',$c['ticketProm'],'money', self::C_GOLD,  self::C_GOLD_BG],
            ['Productos vend.',$c['items'],     'int',   self::C_TEAL_D,'C9E7ED'],
        ];
        // cada tarjeta ocupa 2 columnas: A-B, C-D, E-F, G-H
        $cols = [['A','B'],['C','D'],['E','F'],['G','H']];
        $rLabel = 6; $rValue = 7; $rPad = 8;
        foreach ($kpis as $i => $k) {
            [$c1, $c2] = $cols[$i];
            $x->mergeCells($c1.$rLabel, $c2.$rLabel);
            $x->setCell($c1.$rLabel, mb_strtoupper($k[0],'UTF-8'),
                ['bold'=>true,'size'=>9,'color'=>self::C_MUTE,'bg'=>$k[4],'align'=>'left']);
            $x->mergeCells($c1.$rValue, $c2.$rValue);
            $x->setCell($c1.$rValue, $k[1],
                ['bold'=>true,'size'=>18,'color'=>$k[3],'bg'=>$k[4],'align'=>'left','format'=>$k[2]]);
            $x->mergeCells($c1.$rPad, $c2.$rPad);
            $x->setCell($c1.$rPad, '', ['bg'=>$k[4]]);
        }
        $x->setRowHeight($rLabel, 18);
        $x->setRowHeight($rValue, 30);
        $x->setRowHeight($rPad, 6);

        // ---- Tabla: ventas por tipo de comprobante ----
        $row = 10;
        $x->mergeCells("A$row", "H$row");
        $x->setCell("A$row", 'VENTAS POR TIPO DE COMPROBANTE',
            ['bold'=>true,'size'=>12,'color'=>self::C_NAVY]);
        $x->setRowHeight($row, 22);
        $row++;

        $heads = ['Tipo de comprobante','Cantidad','Subtotal','IGV','Total'];
        $spans = [['A','D'],['E','E'],['F','F'],['G','G'],['H','H']];
        foreach ($heads as $i => $h) {
            [$c1,$c2] = $spans[$i];
            if ($c1 !== $c2) $x->mergeCells($c1.$row, $c2.$row);
            $x->setCell($c1.$row, $h, $this->hdr($i === 0 ? 'left' : 'right'));
        }
        $x->setRowHeight($row, 20);
        $row++;

        $labels = [
            'nota_venta' => 'Nota de venta',
            'boleta'     => 'Boleta electrónica (SUNAT)',
            'factura'    => 'Factura electrónica (SUNAT)',
            'ticket'     => 'Ticket interno (histórico)',
        ];
        $z = 0;
        foreach ($labels as $key => $label) {
            $t = $c['tipos'][$key];
            if ($key === 'ticket' && (int)$t['cantidad'] === 0) continue; // ocultar ticket si ya no se usa
            $bg = ($z % 2 === 0) ? self::C_WHITE : self::C_CIAN2;
            $x->mergeCells("A$row", "D$row");
            $x->setCell("A$row", $label, $this->cell('left', $bg));
            $x->setCell("E$row", (int)$t['cantidad'],    $this->cell('right',$bg,'int'));
            $x->setCell("F$row", (float)$t['subtotal'],  $this->cell('right',$bg,'money'));
            $x->setCell("G$row", (float)$t['igv'],       $this->cell('right',$bg,'money'));
            $x->setCell("H$row", (float)$t['total'],     $this->cell('right',$bg,'money',true));
            $x->setRowHeight($row, 18);
            $row++; $z++;
        }
        // Fila TOTAL
        $x->mergeCells("A$row", "D$row");
        $x->setCell("A$row", 'TOTAL', $this->totalCell('left'));
        $x->setCell("E$row", (int)$c['totalOrden'], $this->totalCell('right','int'));
        $x->setCell("F$row", (float)$c['subGral'],  $this->totalCell('right','money'));
        $x->setCell("G$row", (float)$c['igvGral'],  $this->totalCell('right','money'));
        $x->setCell("H$row", (float)$c['totalGral'],$this->totalCell('right','money'));
        $x->setRowHeight($row, 22);

        // Anchos
        foreach (['A'=>22,'B'=>14,'C'=>14,'D'=>10,'E'=>12,'F'=>16,'G'=>14,'H'=>16] as $col=>$w) {
            $x->setColWidth($col, $w);
        }
    }

    // ============================================================
    // HOJA 2 — MÉTODOS DE PAGO + VENTAS POR DÍA
    // ============================================================
    private function hojaMetodosDias($c)
    {
        $x = $this->xls;
        $x->addSheet('Métodos y días');
        $x->hideGridlines(true);

        $x->mergeCells('A1','F1');
        $x->setCell('A1','MÉTODOS DE PAGO Y EVOLUCIÓN DIARIA',
            ['bold'=>true,'size'=>14,'color'=>self::C_WHITE,'bg'=>self::C_NAVY,'align'=>'left']);
        $x->setRowHeight(1, 28);

        // ---- Métodos de pago ----
        $row = 3;
        $x->mergeCells("A$row","C$row");
        $x->setCell("A$row",'POR MÉTODO DE PAGO',['bold'=>true,'size'=>12,'color'=>self::C_NAVY]);
        $row++;
        $metLabel = [
            'efectivo'=>'Efectivo','tarjeta'=>'Tarjeta','yape'=>'Yape / Plin',
            'transferencia'=>'Transferencia','mixto'=>'Mixto','otro'=>'Otro',
        ];
        foreach (['Método','Operaciones','Total'] as $i=>$h) {
            $x->setCell(chr(65+$i).$row, $h, $this->hdr($i===0?'left':'right'));
        }
        $x->setRowHeight($row,20); $row++;
        $z=0; $sumMet=0;
        foreach ($c['porMetodo'] as $m=>$d) {
            $bg = ($z%2===0)?self::C_WHITE:self::C_CIAN2;
            $x->setCell("A$row", $metLabel[$m] ?? ucfirst($m), $this->cell('left',$bg));
            $x->setCell("B$row", (int)$d['cantidad'], $this->cell('right',$bg,'int'));
            $x->setCell("C$row", (float)$d['total'],  $this->cell('right',$bg,'money',true));
            $x->setRowHeight($row,18); $row++; $z++; $sumMet += (float)$d['total'];
        }
        $x->setCell("A$row",'TOTAL',$this->totalCell('left'));
        $x->setCell("B$row",array_sum(array_column($c['porMetodo'],'cantidad')),$this->totalCell('right','int'));
        $x->setCell("C$row",$sumMet,$this->totalCell('right','money'));
        $x->setRowHeight($row,22);

        // ---- Ventas por día (en columnas E:G, alineado a la derecha) ----
        $row = 3;
        $x->mergeCells("E$row","G$row");
        $x->setCell("E$row",'POR DÍA',['bold'=>true,'size'=>12,'color'=>self::C_NAVY]);
        $row++;
        foreach (['Fecha','Órdenes','Total'] as $i=>$h) {
            $x->setCell(chr(69+$i).$row, $h, $this->hdr($i===0?'left':'right'));
        }
        $x->setRowHeight($row,20); $row++;
        $z=0; $sumDia=0; $maxDia=0;
        foreach ($c['porDia'] as $d) { if ((float)$d['total']>$maxDia) $maxDia=(float)$d['total']; }
        foreach ($c['porDia'] as $fecha=>$d) {
            $esMax = ($maxDia>0 && (float)$d['total']>=$maxDia);
            $bg = $esMax ? self::C_GOLD_BG : (($z%2===0)?self::C_WHITE:self::C_CIAN2);
            $x->setCell("E$row", $this->fechaTxt($fecha), $this->cell('left',$bg));
            $x->setCell("F$row", (int)$d['cantidad'], $this->cell('right',$bg,'int'));
            $x->setCell("G$row", (float)$d['total'],   $this->cell('right',$bg,'money',$esMax));
            $x->setRowHeight($row,18); $row++; $z++; $sumDia += (float)$d['total'];
        }
        $x->setCell("E$row",'TOTAL',$this->totalCell('left'));
        $x->setCell("F$row",array_sum(array_column($c['porDia'],'cantidad')),$this->totalCell('right','int'));
        $x->setCell("G$row",$sumDia,$this->totalCell('right','money'));
        $x->setRowHeight($row,22);

        foreach (['A'=>18,'B'=>14,'C'=>16,'D'=>3,'E'=>16,'F'=>12,'G'=>16] as $col=>$w) $x->setColWidth($col,$w);
    }

    // ============================================================
    // HOJA 3 — TOP PRODUCTOS
    // ============================================================
    private function hojaTopProductos($c)
    {
        $x = $this->xls;
        $x->addSheet('Top productos');
        $x->hideGridlines(true);

        $x->mergeCells('A1','E1');
        $x->setCell('A1','TOP 20 PRODUCTOS MÁS VENDIDOS',
            ['bold'=>true,'size'=>14,'color'=>self::C_WHITE,'bg'=>self::C_NAVY,'align'=>'left']);
        $x->setRowHeight(1, 28);

        $row = 3;
        foreach (['#','Producto','Categoría','Unidades','Ingresos'] as $i=>$h) {
            $x->setCell(chr(65+$i).$row, $h, $this->hdr(in_array($i,[0,3,4],true)?($i===0?'center':'right'):'left'));
        }
        $x->setRowHeight($row, 20);
        $x->freezePanes($row, 0);
        $row++;

        $rank = 1; $sumU = 0; $sumI = 0;
        foreach ($c['top'] as $p) {
            $bg = ($rank % 2 === 1) ? self::C_WHITE : self::C_CIAN2;
            // medallas para top 3
            $medalla = $rank<=3 ? self::C_GOLD_BG : $bg;
            $x->setCell("A$row", $rank, $this->cell('center', $medalla, '', $rank<=3));
            $x->setCell("B$row", $p['nombre'], $this->cell('left', $bg));
            $x->setCell("C$row", $p['categoria_nombre'] ?? '—', $this->cell('left', $bg));
            $x->setCell("D$row", (int)$p['total_vendido'], $this->cell('right', $bg, 'int'));
            $x->setCell("E$row", (float)$p['total_ingresos'], $this->cell('right', $bg, 'money', true));
            $x->setRowHeight($row, 18);
            $sumU += (int)$p['total_vendido']; $sumI += (float)$p['total_ingresos'];
            $row++; $rank++;
        }
        $x->setCell("A$row",'',$this->totalCell('center'));
        $x->mergeCells("B$row","C$row");
        $x->setCell("B$row",'TOTAL',$this->totalCell('left'));
        $x->setCell("D$row",$sumU,$this->totalCell('right','int'));
        $x->setCell("E$row",$sumI,$this->totalCell('right','money'));
        $x->setRowHeight($row,22);

        foreach (['A'=>6,'B'=>34,'C'=>22,'D'=>12,'E'=>16] as $col=>$w) $x->setColWidth($col,$w);
    }

    // ============================================================
    // HOJA — DETALLE POR PRODUCTO (vendidos / cortesias / stock)
    // ============================================================
    private function hojaDetalleProductos($c)
    {
        $x = $this->xls;
        $x->addSheet('Detalle por producto');
        $x->hideGridlines(true);

        $x->mergeCells('A1','G1');
        $x->setCell('A1','DETALLE POR PRODUCTO',
            ['bold'=>true,'size'=>14,'color'=>self::C_WHITE,'bg'=>self::C_NAVY,'align'=>'left']);
        $x->setRowHeight(1, 28);
        $x->mergeCells('A2','G2');
        $x->setCell('A2','Unidades vendidas, ingresos, cortesías (no facturadas) y stock disponible · '
            . $this->fechaTxt($c['desde']) . '  →  ' . $this->fechaTxt($c['hasta']),
            ['size'=>10,'italic'=>true,'color'=>self::C_MUTE,'align'=>'left']);

        $headRow = 4;
        $heads  = ['#','Producto','Categoría','Vendidos','Ingresos','Cortesías','Stock actual'];
        $aligns = ['center','left','left','right','right','center','center'];
        foreach ($heads as $i=>$h) {
            $x->setCell($this->colL($i+1).$headRow, $h, $this->hdr($aligns[$i]));
        }
        $x->setRowHeight($headRow, 22);
        $x->freezePanes($headRow, 0);

        $row = $headRow + 1;
        $n = 1; $sumVend = 0; $sumIng = 0; $sumCort = 0;
        foreach ($c['detProd'] as $p) {
            $bg = ($n % 2 === 1) ? self::C_WHITE : self::C_CIAN2;
            $conStock = (int)($p['num_con_stock'] ?? 0) > 0;
            $vend = (int)$p['cantidad_vendida'];
            $ing  = (float)$p['total_ingresos'];
            $cort = (int)$p['cantidad_cortesia'];
            $x->setCell($this->colL(1).$row, $n,                       $this->cell('center',$bg,'int'));
            $x->setCell($this->colL(2).$row, $p['nombre'],             $this->cell('left',$bg));
            $x->setCell($this->colL(3).$row, $p['categoria_nombre'] ?: '—', $this->cell('left',$bg));
            $x->setCell($this->colL(4).$row, $vend,                    $this->cell('right',$bg,'int'));
            $x->setCell($this->colL(5).$row, $ing,                     $this->cell('right',$bg,'money',true));
            $x->setCell($this->colL(6).$row, $cort,                    $this->cell('center',$bg,'int'));
            $x->setCell($this->colL(7).$row, $conStock ? (float)$p['stock_actual'] : '—',
                        $conStock ? $this->cell('center',$bg,'int') : $this->cell('center',$bg));
            $x->setRowHeight($row, 17);
            $sumVend += $vend; $sumIng += $ing; $sumCort += $cort;
            $row++; $n++;
        }
        // Totales
        $x->mergeCells($this->colL(1).$row, $this->colL(3).$row);
        $x->setCell($this->colL(1).$row, 'TOTAL', $this->totalCell('right'));
        $x->setCell($this->colL(4).$row, $sumVend, $this->totalCell('right','int'));
        $x->setCell($this->colL(5).$row, $sumIng,  $this->totalCell('right','money'));
        $x->setCell($this->colL(6).$row, $sumCort, $this->totalCell('center','int'));
        $x->setCell($this->colL(7).$row, '',       $this->totalCell('center'));
        $x->setRowHeight($row, 22);

        foreach (['A'=>5,'B'=>34,'C'=>22,'D'=>12,'E'=>16,'F'=>12,'G'=>14] as $col=>$w) $x->setColWidth($col,$w);
    }

    // ============================================================
    // HOJA 4 — DETALLE DE VENTAS
    // ============================================================
    private function hojaDetalle($c)
    {
        $x = $this->xls;
        $x->addSheet('Ventas detalladas');
        $x->hideGridlines(true);

        $x->mergeCells('A1','M1');
        $x->setCell('A1','DETALLE DE VENTAS PAGADAS',
            ['bold'=>true,'size'=>14,'color'=>self::C_WHITE,'bg'=>self::C_NAVY,'align'=>'left']);
        $x->setRowHeight(1, 28);
        $x->mergeCells('A2','M2');
        $x->setCell('A2', count($c['ventas']) . ' registros · ' . $this->fechaTxt($c['desde']) . '  →  ' . $this->fechaTxt($c['hasta']),
            ['size'=>10,'italic'=>true,'color'=>self::C_MUTE,'align'=>'left']);

        $headRow = 4;
        $heads = ['#','Fecha','N° orden','Tipo','Comprobante','N° fiscal','Cliente','Documento','Mesa','Mozo','Método','IGV','Total'];
        $aligns = ['center','left','center','left','left','left','left','left','center','left','left','right','right'];
        foreach ($heads as $i=>$h) {
            $x->setCell($this->colL($i+1).$headRow, $h, $this->hdr($aligns[$i]));
        }
        $x->setRowHeight($headRow, 22);

        $row = $headRow + 1;
        $i = 1; $sumIgv = 0; $sumTot = 0;
        $tcLabel = ['ticket'=>'Ticket','nota_venta'=>'Nota venta','boleta'=>'Boleta','factura'=>'Factura'];
        $tipoLabel = ['dine_in'=>'En mesa','para_llevar'=>'Para llevar','delivery'=>'Delivery'];
        foreach ($c['ventas'] as $v) {
            $bg = ($i % 2 === 1) ? self::C_WHITE : self::C_CIAN2;
            $x->setCell($this->colL(1).$row,  $i,                                   $this->cell('center',$bg,'int'));
            $x->setCell($this->colL(2).$row,  $v['fecha_pago'] ?: '',               $this->cell('left',$bg,'date'));
            $x->setCell($this->colL(3).$row,  $v['numero'],                         $this->cell('center',$bg));
            $x->setCell($this->colL(4).$row,  $tipoLabel[$v['tipo']] ?? $v['tipo'], $this->cell('left',$bg));
            $x->setCell($this->colL(5).$row,  $tcLabel[$v['tipo_comprobante']] ?? $v['tipo_comprobante'], $this->cell('left',$bg));
            $x->setCell($this->colL(6).$row,  $v['ce_numero_completo'] ?: '—',      $this->cell('left',$bg));
            $x->setCell($this->colL(7).$row,  $v['ce_cliente_razon'] ?: $v['cliente_nombre'] ?: '—', $this->cell('left',$bg));
            $x->setCell($this->colL(8).$row,  $v['ce_cliente_doc'] ?: $v['cliente_documento'] ?: '—', $this->cell('left',$bg));
            $x->setCell($this->colL(9).$row,  $v['mesa_numero'] ?: '—',             $this->cell('center',$bg));
            $x->setCell($this->colL(10).$row, $v['mozo'] ?: '—',                    $this->cell('left',$bg));
            $x->setCell($this->colL(11).$row, ucfirst($v['metodo_pago']),           $this->cell('left',$bg));
            $x->setCell($this->colL(12).$row, (float)$v['igv'],                     $this->cell('right',$bg,'money'));
            $x->setCell($this->colL(13).$row, (float)$v['total'],                   $this->cell('right',$bg,'money',true));
            $x->setRowHeight($row, 17);
            $sumIgv += (float)$v['igv']; $sumTot += (float)$v['total'];
            $row++; $i++;
        }
        // Totales
        $x->mergeCells($this->colL(1).$row, $this->colL(11).$row);
        $x->setCell($this->colL(1).$row, 'TOTAL  ·  ' . count($c['ventas']) . ' ventas', $this->totalCell('right'));
        $x->setCell($this->colL(12).$row, $sumIgv, $this->totalCell('right','money'));
        $x->setCell($this->colL(13).$row, $sumTot, $this->totalCell('right','money'));
        $x->setRowHeight($row, 22);

        // Panel congelado bajo el encabezado (mantiene los títulos visibles
        // al desplazarse). No usamos autoFilter porque genera incompatibilidad
        // de "reparación" en algunas versiones de Excel.
        $x->freezePanes($headRow, 0);

        $widths = ['A'=>5,'B'=>18,'C'=>10,'D'=>12,'E'=>12,'F'=>16,'G'=>28,'H'=>14,'I'=>7,'J'=>16,'K'=>13,'L'=>13,'M'=>14];
        foreach ($widths as $col=>$w) $x->setColWidth($col,$w);
    }

    // ===================== Estilos reutilizables =====================

    private function hdr($align = 'left')
    {
        return ['bold'=>true,'size'=>10,'color'=>self::C_WHITE,'bg'=>self::C_TEAL,'align'=>$align,'border'=>true];
    }

    private function cell($align = 'left', $bg = self::C_WHITE, $format = '', $bold = false)
    {
        $s = ['size'=>10,'color'=>self::C_INK,'align'=>$align,'bg'=>$bg,'border'=>true];
        if ($format !== '') $s['format'] = $format;
        if ($bold) { $s['bold'] = true; $s['color'] = self::C_NAVY; }
        return $s;
    }

    private function totalCell($align = 'left', $format = '')
    {
        $s = ['bold'=>true,'size'=>10,'color'=>self::C_WHITE,'bg'=>self::C_GOLD,'align'=>$align,'border'=>true];
        if ($format !== '') $s['format'] = $format;
        return $s;
    }

    private function colL($n)
    {
        $s = '';
        while ($n > 0) { $r = ($n - 1) % 26; $s = chr(65 + $r) . $s; $n = (int)(($n - 1) / 26); }
        return $s;
    }

    private function fechaTxt($iso)
    {
        $iso = (string)$iso;
        if ($iso === '') return '—';
        $d = strtotime($iso);
        return $d ? date('d/m/Y', $d) : $iso;
    }
}
