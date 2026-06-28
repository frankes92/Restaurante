<?php
// =====================================================================
// COMPROBANTE -> ESC/POS (PLAN B, sin Chrome ni GD)
// Devuelve los bytes ESC/POS de la boleta/factura/nota en base64.
// Lo consume el BRIDGE (con su token) y los envía tal cual a la térmica.
// Diseño: imita el ticket 80mm del sistema (encabezado, ítems, totales,
// SON, y QR NATIVO de la impresora para boleta/factura electrónica).
// =====================================================================
require_once __DIR__ . "/../config/auth.php";

header('Content-Type: application/json; charset=utf-8');

// ---- Autenticación SOLO por token del bridge ----
$tok = $_GET['token'] ?? ($_SERVER['HTTP_X_BRIDGE_TOKEN'] ?? '');
$ok  = (defined('BRIDGE_TOKEN') && BRIDGE_TOKEN !== '' && is_string($tok) && hash_equals(BRIDGE_TOKEN, $tok));
if (!$ok) { echo json_encode(['ok' => false, 'msg' => 'Token inválido']); exit; }

require_once __DIR__ . "/../modelos/Orden.php";
require_once __DIR__ . "/../modelos/Empresa.php";

$idorden = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idorden <= 0) { echo json_encode(['ok' => false, 'msg' => 'ID inválido']); exit; }

$cols = (int)($_GET['cols'] ?? 48);
if ($cols < 32 || $cols > 64) $cols = 48;

$ordenObj = new Orden();
$orden = $ordenObj->mostrarCompleta($idorden);
if (!$orden) { echo json_encode(['ok' => false, 'msg' => 'Orden no encontrada']); exit; }

// Comprobante fiscal (boleta/factura) si existe
$compFiscal = null;
if (in_array($orden['tipo_comprobante'] ?? '', ['boleta', 'factura'], true)) {
    $compFiscal = ejecutarConsultaSimpleFila(
        "SELECT tipo_documento, serie, numero, numero_completo,
                cliente_tipo_doc, cliente_num_doc, cliente_razon, cliente_direccion,
                total_letras, xml_hash, fecha_emision
         FROM comprobante_electronico
         WHERE idorden = '" . (int)$idorden . "'
         ORDER BY idcomprobante DESC LIMIT 1"
    );
}

$emp     = (new Empresa())->mostrar(1) ?: [];
$empNom  = $emp['nombre_comercial'] ?? $emp['razon_social'] ?? 'PUERTO HABANA';
$empRaz  = $emp['razon_social']     ?? $empNom;
$empRuc  = $emp['numero_ruc']       ?? '';
$empDir  = $emp['domicilio_fiscal'] ?? '';
$empUbi  = trim(($emp['distrito'] ?? '') . ', ' . ($emp['provincia'] ?? '') . ' - ' . ($emp['departamento'] ?? ''), ', -');
$empTel  = $emp['telefono']         ?? '';
$empWeb  = $emp['web']              ?? '';
$simbolo = $emp['simbolo_moneda']   ?? 'S/';
$tasaIgv = (float)($emp['tasa_igv'] ?? 0.18);
$pctIgv  = (int)round($tasaIgv * 100);

$tipoLabels = [
    'ticket'     => 'TICKET DE VENTA',
    'nota_venta' => 'NOTA DE VENTA',
    'boleta'     => 'BOLETA ELECTRONICA',
    'factura'    => 'FACTURA ELECTRONICA',
];
$tipoLabel = $tipoLabels[$orden['tipo_comprobante'] ?? ''] ?? 'COMPROBANTE';

$metodos = [
    'efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'yape' => 'Yape',
    'plin' => 'Plin', 'transferencia' => 'Transferencia', 'mixto' => 'Mixto',
];
$metodoLabel = $metodos[$orden['metodo_pago'] ?? ''] ?? ucwords((string)($orden['metodo_pago'] ?? ''));

$numeroDoc = $compFiscal ? $compFiscal['numero_completo'] : ('N° ' . ($orden['numero'] ?? ''));
$fechaEmision = $orden['fecha_pago'] ?: $orden['fecha'];
$horaEmision  = date('H:i:s', strtotime($fechaEmision));
$codMoneda    = $emp['codigo_moneda'] ?? 'PEN';
$empMail      = $emp['correo'] ?? '';
$cajero       = trim((string)($orden['propietario_nombre'] ?? ''));
$clienteNom   = $compFiscal['cliente_razon'] ?? trim((string)($orden['cliente_nombre'] ?? ''));
if ($clienteNom === '') $clienteNom = 'CLIENTE VARIOS';
$esEfectivo   = ($orden['metodo_pago'] ?? '') === 'efectivo';
$recibido     = (float)($orden['monto_recibido'] ?? 0);
$vuelto       = (float)($orden['vuelto'] ?? 0);
$mostrarPago  = $esEfectivo && $recibido > 0;

// Total en letras
$totalLetras = $compFiscal['total_letras'] ?? '';
if (!$totalLetras) {
    $totalLetras = strtoupper(numToLetras((float)$orden['total'])) . ' Y 00/100 SOLES';
}

// Cadena QR formato SUNAT (solo boleta/factura electrónica)
$cadenaQR = '';
if ($compFiscal) {
    $cadenaQR = implode('|', [
        $empRuc,
        $compFiscal['tipo_documento'],
        $compFiscal['serie'],
        ltrim($compFiscal['numero'], '0') ?: '0',
        number_format((float)$orden['igv'], 2, '.', ''),
        number_format((float)$orden['total'], 2, '.', ''),
        date('d/m/Y', strtotime($compFiscal['fecha_emision'])),
        $compFiscal['cliente_tipo_doc'],
        $compFiscal['cliente_num_doc'],
        $compFiscal['xml_hash'] ?? '',
    ]);
}

// =====================================================================
// Construcción ESC/POS
// =====================================================================
$bytes = construirEscpos();
echo json_encode(['ok' => true, 'b64' => base64_encode($bytes)]);
exit;


// ---------------------------------------------------------------------
// Helpers ESC/POS
// ---------------------------------------------------------------------
function construirEscpos()
{
    global $cols, $orden, $emp, $compFiscal, $empNom, $empRaz, $empRuc, $empDir, $empUbi,
           $empTel, $empWeb, $empMail, $simbolo, $codMoneda, $pctIgv, $tasaIgv,
           $tipoLabel, $metodoLabel, $numeroDoc, $fechaEmision, $horaEmision,
           $totalLetras, $cadenaQR, $cajero, $clienteNom, $recibido, $vuelto, $mostrarPago;

    $ESC = "\x1B"; $GS = "\x1D";
    $money = function ($v) use ($simbolo) { return $simbolo . ' ' . number_format((float)$v, 2); };

    $out  = $ESC . "@";              // init
    $out .= $ESC . "t" . chr(19);   // CP858
    $out .= $ESC . "M" . "\x00";    // fuente A

    // ===== ENCABEZADO =====
    $out .= ep_center();
    $logo = ep_logo($cols);          // logo de la empresa como imagen térmica (si hay GD)
    if ($logo !== '') $out .= $logo;

    // Nombre: "CEVICHERIA" arriba y "PUERTO HABANA" debajo, en doble alto (sin
    // doble ancho) para que se vea mediano y elegante. Se oculta la razon social.
    $nom = strtoupper(trim($empNom));
    $lineasNom = [];
    if (stripos($nom, 'CEVICHERIA') !== false) {
        $resto = trim(preg_replace('/\s+/', ' ', str_ireplace('CEVICHERIA', '', $nom)));
        $lineasNom[] = 'CEVICHERIA';
        if ($resto !== '') $lineasNom[] = $resto;
    } else {
        $lineasNom[] = $nom;
    }
    $out .= ep_bold(1);
    // 1ra línea (CEVICHERIA): doble alto
    $out .= ep_size(0, 1) . ep_txt($lineasNom[0]) . "\n";
    // 2da línea (PUERTO HABANA): doble alto + DOBLE ANCHO (más estirada/ancha) + comillas + espaciado
    if (isset($lineasNom[1])) {
        $out .= ep_size(1, 1) . ep_charsp(3) . ep_txt('"' . $lineasNom[1] . '"') . ep_charsp(0) . "\n";
    }
    $out .= ep_size(0, 0) . ep_bold(0);

    if ($empRuc) $out .= ep_rev(1) . ep_txt(' RUC: ' . $empRuc . ' ') . ep_rev(0) . "\n";
    if ($empDir) foreach (ep_wrapArr($empDir, $cols) as $ln) $out .= ep_txt($ln) . "\n";
    if ($empUbi && trim($empUbi, ', -')) foreach (ep_wrapArr($empUbi, $cols) as $ln) $out .= ep_txt($ln) . "\n";

    // ===== TIPO + NUMERO =====
    $out .= ep_txt(ep_dash($cols)) . "\n";
    $out .= ep_bold(1) . ep_size(0, 1) . ep_txt($tipoLabel) . "\n" . ep_size(0, 0) . ep_bold(0);
    $out .= ep_rev(1) . ep_bold(1) . ep_txt(' ' . $numeroDoc . ' ') . ep_bold(0) . ep_rev(0) . "\n";
    $out .= ep_txt(ep_dash($cols)) . "\n";

    // ===== META =====
    $out .= ep_left();
    $out .= ep_txt(ep_meta('FECHA DE EMISION', date('d/m/Y', strtotime($fechaEmision)))) . "\n";
    $out .= ep_txt(ep_meta('HORA DE EMISION', $horaEmision)) . "\n";
    $monNom = $codMoneda === 'PEN' ? 'Soles' : ($codMoneda === 'USD' ? 'Dolares' : $codMoneda);
    $out .= ep_txt(ep_meta('MONEDA', $codMoneda . ' - ' . $monNom)) . "\n";
    $out .= ep_txt(ep_meta('FORMA DE PAGO', $metodoLabel)) . "\n";
    if ($cajero) $out .= ep_txt(ep_meta('CAJERO', $cajero)) . "\n";
    if (!empty($orden['mesa_numero'])) $out .= ep_txt(ep_meta('MESA', (string)$orden['mesa_numero'])) . "\n";

    // ===== DATOS DEL CLIENTE =====
    $out .= ep_txt(ep_dash($cols)) . "\n";
    $out .= ep_bold(1) . ep_txt('DATOS DEL CLIENTE') . ep_bold(0) . "\n";
    $out .= ep_txt(ep_meta('Cliente', strtoupper($clienteNom))) . "\n";
    if ($compFiscal) {
        $docLbl = ($compFiscal['cliente_tipo_doc'] === '6') ? 'RUC' : 'DNI';
        $out .= ep_txt(ep_meta($docLbl, (string)$compFiscal['cliente_num_doc'])) . "\n";
        if (!empty($compFiscal['cliente_direccion']))
            foreach (ep_wrapArr('Dir: ' . $compFiscal['cliente_direccion'], $cols) as $ln) $out .= ep_txt($ln) . "\n";
    }

    // ===== ITEMS =====
    $out .= ep_txt(ep_dash($cols)) . "\n";
    $out .= ep_bold(1) . ep_txt(ep_lr('COD   DESCRIPCION', 'IMPORTE', $cols)) . ep_bold(0) . "\n";
    $out .= ep_txt(ep_dash($cols)) . "\n";

    foreach (($orden['items'] ?? []) as $idx => $i) {
        $esCortesia = (int)($i['cortesia'] ?? 0) === 1;
        $cant   = (float)$i['cantidad'];
        $cantTx = ($cant == (int)$cant) ? (string)(int)$cant : number_format($cant, 2);
        $nombre = strtoupper((string)$i['nombre']);
        $punit  = $esCortesia ? 0 : (float)$i['precio'];
        $totLin = $esCortesia ? 0 : $cant * $punit;
        $cod    = 'P' . str_pad((string)($idx + 1), 3, '0', STR_PAD_LEFT);

        // Linea 1: COD + descripcion
        $linsNom = ep_wrapArr($cod . '  ' . $nombre, $cols);
        $out .= ep_bold(1) . ep_txt($linsNom[0]) . ep_bold(0) . "\n";
        for ($k = 1; $k < count($linsNom); $k++) $out .= ep_txt('      ' . $linsNom[$k]) . "\n";
        // Linea 2: cant x precio ........ importe
        $det = '      ' . $cantTx . ' x ' . $money($punit);
        $out .= ep_txt(ep_lr($det, $money($totLin), $cols)) . "\n";
        if ($esCortesia) $out .= ep_txt('      *** CORTESIA ***') . "\n";
        if (!empty($i['nota'])) foreach (ep_wrapArr('      > ' . $i['nota'], $cols) as $ln) $out .= ep_txt($ln) . "\n";
    }

    // ===== TOTAL (barra en negativo) =====
    $out .= ep_txt(ep_dash($cols)) . "\n";
    if ($tasaIgv > 0 && (float)$orden['igv'] > 0) {
        $out .= ep_txt(ep_lr('Op. Gravadas', $money($orden['subtotal']), $cols)) . "\n";
        $out .= ep_txt(ep_lr('IGV (' . $pctIgv . '%)', $money($orden['igv']), $cols)) . "\n";
    }
    $barra = ep_lr(' TOTAL A PAGAR', $money($orden['total']) . ' ', $cols);
    $out .= ep_rev(1) . ep_bold(1) . ep_size(0, 1) . ep_txt($barra) . ep_size(0, 0) . ep_bold(0) . ep_rev(0) . "\n";

    // Recibido / Vuelto (solo efectivo)
    if ($mostrarPago) {
        $out .= ep_txt(ep_lr('Recibido', $money($recibido), $cols)) . "\n";
        $out .= ep_txt(ep_lr('Vuelto', $money($vuelto), $cols)) . "\n";
    }

    // ===== SON =====
    $out .= ep_txt(ep_dash($cols)) . "\n";
    $out .= ep_bold(1) . ep_txt('SON:') . ep_bold(0) . "\n";
    foreach (ep_wrapArr($totalLetras, $cols) as $ln) $out .= ep_txt($ln) . "\n";

    // ===== OBSERVACIONES =====
    $out .= ep_bold(1) . ep_txt('OBSERVACIONES:') . ep_bold(0) . "\n";
    $obs = !empty($orden['observacion']) ? $orden['observacion'] : 'Gracias por su preferencia.';
    foreach (ep_wrapArr($obs, $cols) as $ln) $out .= ep_txt($ln) . "\n";

    // ===== PIE =====
    $out .= "\n" . ep_center();
    $out .= ep_bold(1) . ep_size(0, 1) . ep_txt('Gracias por confiar en nosotros') . ep_size(0, 0) . ep_bold(0) . "\n";
    if ($empMail) $out .= ep_txt($empMail) . "\n";
    if ($empTel)  $out .= ep_txt('Tel: ' . $empTel) . "\n";
    $out .= "\n";

    if ($cadenaQR !== '') {
        // Boleta/factura electrónica: QR de SUNAT
        $out .= ep_qr($cadenaQR);
        $out .= ep_txt('Representacion impresa de la') . "\n";
        $out .= ep_txt(strtolower($tipoLabel)) . "\n";
        $out .= ep_txt('Validado por SUNAT - sunat.gob.pe') . "\n";
        if (!empty($compFiscal['xml_hash'])) {
            $out .= ep_left();
            foreach (ep_wrapArr('Hash: ' . $compFiscal['xml_hash'], $cols) as $ln) $out .= ep_txt($ln) . "\n";
        }
    } else {
        // Documento interno (no fiscal): nota en recuadro.
        // Configurable: solo se imprime si la empresa lo tiene activado
        // (empresa.mostrar_glosa_interna). Por defecto 1 = se muestra.
        if ((int)($emp['mostrar_glosa_interna'] ?? 1) === 1) {
            $out .= ep_left();
            $out .= ep_box([
                'Documento interno de uso comercial.',
                'No constituye comprobante de pago',
                'electronico SUNAT.',
            ], $cols);
        }
    }

    // Avance y corte
    $out .= ep_left();
    $out .= $ESC . "d" . chr(4);    // feed 4
    $out .= $GS . "V" . "\x00";     // corte total
    return $out;
}

// Logo de la empresa -> imagen térmica (raster ESC/POS GS v 0).
// Se genera en el SERVIDOR (que sí tiene GD). Si no hay GD o no hay logo,
// devuelve '' y el ticket sigue con el nombre en texto (sin romper nada).
function ep_logo($cols)
{
    global $emp;
    if (!function_exists('imagecreatefromstring')) return '';
    $rel = $emp['logo'] ?? '';
    if (!$rel) return '';
    $path = __DIR__ . '/../' . ltrim($rel, '/');
    if (!is_file($path)) return '';
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') return '';
    $src = @imagecreatefromstring($raw);
    if (!$src) return '';

    $sw = imagesx($src); $sh = imagesy($src);
    $maxW = ($cols >= 48) ? 240 : 180;     // ancho máx del logo (puntos)
    $scale = min($maxW / $sw, $maxW / $sh, 1);
    $w = max(8, (int)round($sw * $scale));
    $h = max(8, (int)round($sh * $scale));
    $w = (int)(floor($w / 8) * 8); if ($w < 8) $w = 8;   // ancho múltiplo de 8

    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, true);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));  // fondo blanco
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $sw, $sh);
    imagedestroy($src);

    $bytesPerRow = (int)(($w + 7) / 8);
    $GS = "\x1D";
    $out = '';
    $banda = 128;
    for ($y0 = 0; $y0 < $h; $y0 += $banda) {
        $rows = min($banda, $h - $y0);
        $data = '';
        for ($y = $y0; $y < $y0 + $rows; $y++) {
            for ($bx = 0; $bx < $bytesPerRow; $bx++) {
                $b = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = $bx * 8 + $bit;
                    if ($x < $w) {
                        $rgb = imagecolorat($dst, $x, $y);
                        $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $bl = $rgb & 0xFF;
                        if ((0.299 * $r + 0.587 * $g + 0.114 * $bl) < 140) $b |= (0x80 >> $bit);
                    }
                }
                $data .= chr($b);
            }
        }
        $xL = $bytesPerRow & 0xFF; $xH = ($bytesPerRow >> 8) & 0xFF;
        $yL = $rows & 0xFF;        $yH = ($rows >> 8) & 0xFF;
        $out .= $GS . "v0" . chr(0) . chr($xL) . chr($xH) . chr($yL) . chr($yH) . $data;
    }
    imagedestroy($dst);
    return $out . "\n";
}

function ep_center() { return "\x1B" . "a" . "\x01"; }
function ep_left()   { return "\x1B" . "a" . "\x00"; }
function ep_bold($on){ return "\x1B" . "E" . ($on ? "\x01" : "\x00"); }
function ep_size($w, $h) { return "\x1D" . "!" . chr((($w & 7) << 4) | ($h & 7)); }
function ep_rev($on) { return "\x1D" . "B" . ($on ? "\x01" : "\x00"); }   // video inverso (negativo)
function ep_charsp($n) { return "\x1B" . " " . chr(max(0, (int)$n)); }    // ESC SP n: espaciado entre caracteres (puntos)

// Línea divisoria punteada "- - - - -" del ancho de columnas
function ep_dash($cols) { return substr(str_repeat('- ', $cols), 0, $cols); }

// Fila tipo "ETIQUETA        : valor" (etiqueta alineada a 16)
function ep_meta($lbl, $val) { return str_pad((string)$lbl, 16) . ': ' . (string)$val; }

// Recuadro con borde +---+ alrededor de líneas de texto centradas
function ep_box($lineas, $cols)
{
    $inner = $cols - 4;   // "| " + texto + " |"
    $borde = '+' . str_repeat('-', $cols - 2) . '+';
    $out = ep_txt($borde) . "\n";
    foreach ($lineas as $l) {
        foreach (ep_wrapArr($l, $inner) as $w) {
            $pad = $inner - ep_len($w);
            $lft = (int)floor($pad / 2); $rgt = $pad - $lft;
            $out .= ep_txt('| ' . str_repeat(' ', $lft) . $w . str_repeat(' ', $rgt) . ' |') . "\n";
        }
    }
    $out .= ep_txt($borde) . "\n";
    return $out;
}

function ep_txt($s)
{
    if ($s === null || $s === '') return '';
    $c = @iconv('UTF-8', 'CP858//TRANSLIT//IGNORE', (string)$s);
    return $c !== false ? $c : (string)$s;
}

// Izquierda + derecha rellenando con espacios hasta $cols
function ep_lr($izq, $der, $cols)
{
    $izq = (string)$izq; $der = (string)$der;
    $libre = $cols - ep_len($izq) - ep_len($der);
    if ($libre < 1) { $izq = ep_corta($izq, $cols - ep_len($der) - 1); $libre = 1; }
    return $izq . str_repeat(' ', max(1, $libre)) . $der;
}

// Largo en caracteres (UTF-8)
function ep_len($s) { return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s); }
function ep_corta($s, $n) { return function_exists('mb_substr') ? mb_substr($s, 0, max(0, $n), 'UTF-8') : substr($s, 0, max(0, $n)); }

// Envuelve un texto a varias líneas de ancho $cols (devuelve string con \n)
function ep_wrap($s, $cols) { return implode("\n", ep_wrapArr($s, $cols)); }

// Envuelve y devuelve array de líneas
function ep_wrapArr($s, $cols)
{
    $s = trim((string)$s);
    if ($s === '') return [''];
    $palabras = preg_split('/\s+/', $s);
    $lineas = []; $actual = '';
    foreach ($palabras as $p) {
        // palabra más larga que la línea: cortarla
        while (ep_len($p) > $cols) {
            if ($actual !== '') { $lineas[] = $actual; $actual = ''; }
            $lineas[] = ep_corta($p, $cols);
            $p = ep_corta(function_exists('mb_substr') ? mb_substr($p, $cols, null, 'UTF-8') : substr($p, $cols), $cols * 4);
        }
        $cand = $actual === '' ? $p : $actual . ' ' . $p;
        if (ep_len($cand) > $cols) { if ($actual !== '') $lineas[] = $actual; $actual = $p; }
        else { $actual = $cand; }
    }
    if ($actual !== '') $lineas[] = $actual;
    return $lineas ?: [''];
}

// QR nativo ESC/POS (GS ( k). Tamaño de módulo 6, corrección L.
function ep_qr($data)
{
    $GS = "\x1D";
    $store = function ($d) use ($GS) {
        $len = strlen($d) + 3;
        return $GS . "(k" . chr($len & 0xFF) . chr(($len >> 8) & 0xFF) . chr(49) . chr(80) . chr(48) . $d;
    };
    $o  = $GS . "(k" . chr(4) . chr(0) . chr(49) . chr(65) . chr(50) . chr(0);  // modelo 2
    $o .= $GS . "(k" . chr(3) . chr(0) . chr(49) . chr(67) . chr(6);            // tamaño módulo
    $o .= $GS . "(k" . chr(3) . chr(0) . chr(49) . chr(69) . chr(48);           // EC nivel L
    $o .= $store($data);                                                        // almacenar datos
    $o .= $GS . "(k" . chr(3) . chr(0) . chr(49) . chr(81) . chr(48);           // imprimir
    return $o . "\n";
}

// Número a letras (parte entera) — versión simple para SON
function numToLetras($n)
{
    $entero = (int)floor($n);
    $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ',
                 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    $decenas  = ['', '', 'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
                 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    $c3 = function ($n) use ($unidades, $decenas, $centenas) {
        if ($n === 0) return '';
        if ($n === 100) return 'CIEN';
        $c = (int)floor($n / 100); $resto = $n % 100; $res = $centenas[$c];
        if ($resto > 0) {
            if ($resto < 20) $res .= ($res ? ' ' : '') . $unidades[$resto];
            else {
                $d = (int)floor($resto / 10); $u = $resto % 10;
                if ($d === 2 && $u > 0) $res .= ($res ? ' ' : '') . 'VEINTI' . strtolower($unidades[$u]);
                else { $res .= ($res ? ' ' : '') . $decenas[$d]; if ($u > 0) $res .= ' Y ' . $unidades[$u]; }
            }
        }
        return $res;
    };
    if ($entero < 1000) return $c3($entero) ?: 'CERO';
    if ($entero < 1000000) {
        $miles = (int)floor($entero / 1000); $resto = $entero % 1000;
        $res = ($miles === 1 ? 'MIL' : $c3($miles) . ' MIL');
        if ($resto > 0) $res .= ' ' . $c3($resto);
        return $res;
    }
    return (string)$entero;
}
