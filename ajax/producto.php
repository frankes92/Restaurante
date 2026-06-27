<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Producto.php";

requireLogin();

$producto = new Producto();

$idproducto  = isset($_POST['idproducto'])  ? limpiarCadena($_POST['idproducto'])  : '';
$codigo      = isset($_POST['codigo'])      ? limpiarCadena($_POST['codigo'])      : '';
$nombre      = isset($_POST['nombre'])      ? limpiarCadena($_POST['nombre'])      : '';
$precio      = isset($_POST['precio'])      ? limpiarCadena($_POST['precio'])      : '0';
$idcategoria = isset($_POST['idcategoria']) ? limpiarCadena($_POST['idcategoria']) : '';
$imagen      = isset($_POST['imagen'])      ? limpiarCadena($_POST['imagen'])      : '';
$popular     = isset($_POST['popular'])     ? limpiarCadena($_POST['popular'])     : '0';
$favorito    = isset($_POST['favorito'])    ? limpiarCadena($_POST['favorito'])    : '0';
$codigoAfect = isset($_POST['codigo_afectacion']) ? limpiarCadena($_POST['codigo_afectacion']) : '10';

$op = $_GET['op'] ?? '';

switch ($op) {

    case 'guardaryeditar':
        requirePermiso('productos');
        if (empty($idproducto)) {
            $id = $producto->insertar($codigo, $nombre, $precio, $idcategoria, $imagen, $popular, $favorito, $codigoAfect);
            jsonResponse(['ok' => $id > 0, 'idproducto' => $id]);
        } else {
            $r = $producto->editar($idproducto, $codigo, $nombre, $precio, $idcategoria, $imagen, $popular, $favorito, $codigoAfect);
            jsonResponse(['ok' => (bool)$r]);
        }
        break;

    case 'desactivar':
        requirePermiso('productos');
        jsonResponse(['ok' => (bool)$producto->desactivar($idproducto)]);
        break;

    case 'activar':
        requirePermiso('productos');
        jsonResponse(['ok' => (bool)$producto->activar($idproducto)]);
        break;

    case 'toggleFavorito':
        requirePermiso('productos');
        jsonResponse(['ok' => (bool)$producto->toggleFavorito($idproducto)]);
        break;

    case 'mostrar':
        jsonResponse($producto->mostrar($idproducto));
        break;

    case 'listar':
        $rs = $producto->listar();
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'listarActivos':
        $rs = $producto->listarActivos();
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'listarPorCategoria':
        $rs = $producto->listarPorCategoria($idcategoria ?: ($_GET['idcategoria'] ?? ''));
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'listarPaginado':
        $start  = (int)($_REQUEST['start']  ?? 0);
        $limit  = (int)($_REQUEST['limit']  ?? 24);
        $idcat  = limpiarCadena($_REQUEST['idcategoria'] ?? '');
        $searchV = limpiarCadena($_REQUEST['search'] ?? '');
        jsonResponse($producto->listarPaginado($start, $limit, $idcat, $searchV));
        break;

    case 'precios':
        $idp = (int)($_REQUEST['idproducto'] ?? 0);
        $rs = $producto->precios($idp);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'guardarPrecios':
        requirePermiso('productos');
        $idp = (int)($_POST['idproducto'] ?? 0);
        $precios = $_POST['precios'] ?? [];
        if (!is_array($precios)) $precios = [];
        $clean = [];
        foreach ($precios as $p) {
            $clean[] = [
                'nombre'     => limpiarCadena($p['nombre'] ?? 'Normal'),
                'precio'     => (float)($p['precio'] ?? 0),
                'es_default' => !empty($p['es_default']) ? 1 : 0,
            ];
        }
        jsonResponse(['ok' => $producto->setPrecios($idp, $clean)]);
        break;

    case 'subirImagen':
        requirePermiso('productos');
        if (empty($_FILES['archivo'])) jsonResponse(['ok' => false, 'msg' => 'Sin archivo']);
        $idp = (int)($_POST['idproducto'] ?? 0);
        jsonResponse($producto->subirImagen($_FILES['archivo'], $idp ?: null));
        break;

    case 'datatable':
        $draw     = (int)($_REQUEST['draw'] ?? 1);
        $start    = (int)($_REQUEST['start'] ?? 0);
        $length   = (int)($_REQUEST['length'] ?? 10);
        $searchV  = limpiarCadena($_REQUEST['search']['value'] ?? '');
        $orderCol = (int)($_REQUEST['order'][0]['column'] ?? 0);
        $orderDir = limpiarCadena($_REQUEST['order'][0]['dir'] ?? 'desc');
        $idcat    = limpiarCadena($_REQUEST['f_categoria'] ?? '');
        $rs = $producto->listarServerSide($start, $length, $searchV, $orderCol, $orderDir, $idcat);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $producto->contar('', $idcat, false),
            'recordsFiltered' => $producto->contar($searchV, $idcat, true),
            'data' => $data,
        ]);
        exit;

    case 'topVendidos':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $rs = $producto->topVendidos($limit);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    // ---------- IMPORTAR EXCEL ----------

    case 'plantillaImportar':
        // Descarga un Excel de ejemplo con el formato correcto
        requirePermiso('productos');
        require_once __DIR__ . "/../modelos/ExcelWriter.php";
        $xls = new ExcelWriter();
        $xls->addSheet('Productos');
        $heads = ['Categoria', 'Codigo', 'Producto', 'Precio', 'Presentacion', 'Afectacion'];
        $col = 'A';
        foreach ($heads as $h) { $xls->setCell($col . '1', $h, ['bold' => true, 'bg' => '0B3D5C', 'color' => 'FFFFFF', 'border' => true]); $col++; }
        // Filas de ejemplo (incluye un producto con 2 presentaciones: mismo codigo)
        $ejemplos = [
            ['Ceviches', 'CEV01', 'Ceviche Simple', 18.00, 'Normal', '20'],
            ['Bebidas',  'BEB01', 'Inca Kola',       5.00, '500 ml', '10'],
            ['Bebidas',  'BEB01', 'Inca Kola',       9.00, '1 Litro', '10'],
            ['Entradas', '',      'Agua Mineral',    3.00, '', ''],
        ];
        $r = 2;
        foreach ($ejemplos as $e) {
            $xls->setCell("A$r", $e[0], ['border' => true]);
            $xls->setCell("B$r", $e[1], ['border' => true]);
            $xls->setCell("C$r", $e[2], ['border' => true]);
            $xls->setCell("D$r", $e[3], ['border' => true, 'align' => 'right', 'format' => 'money']);
            $xls->setCell("E$r", $e[4], ['border' => true]);
            $xls->setCell("F$r", $e[5], ['border' => true, 'align' => 'center']);
            $r++;
        }
        foreach (['A' => 18, 'B' => 12, 'C' => 28, 'D' => 12, 'E' => 16, 'F' => 12] as $c => $w) $xls->setColWidth($c, $w);
        $xls->download('Plantilla_Productos_PuertoHabana.xlsx');
        exit;

    case 'importar':
        requirePermiso('productos');
        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['ok' => false, 'msg' => 'Selecciona un archivo Excel (.xlsx) o CSV']);
        }
        $nombre = basename($_FILES['archivo']['name']);
        if (!preg_match('/\.(xlsx|csv|txt)$/i', $nombre)) {
            jsonResponse(['ok' => false, 'msg' => 'Formato no soportado. Usa .xlsx o .csv']);
        }
        if (($_FILES['archivo']['size'] ?? 0) > 5 * 1024 * 1024) {
            jsonResponse(['ok' => false, 'msg' => 'El archivo no debe exceder 5 MB']);
        }
        require_once __DIR__ . "/../modelos/ExcelReader.php";
        try {
            $rows = ExcelReader::read($_FILES['archivo']['tmp_name']);
        } catch (\Throwable $e) {
            jsonResponse(['ok' => false, 'msg' => 'No se pudo leer el archivo: ' . $e->getMessage()]);
        }
        $resu = $producto->importarDesdeFilas($rows);
        jsonResponse($resu);
        break;

    // ---------- INVENTARIO (por presentacion / idprecio) ----------

    case 'setInventario':
        // Activa/desactiva stock para CADA presentacion del producto.
        // Recibe: idproducto + presentaciones[] = [{idprecio, controla, stock, minimo}]
        requirePermiso('inventario');
        $idu  = $_SESSION['idusuario'] ?? null;
        $pres = $_POST['presentaciones'] ?? [];
        if (!is_array($pres)) $pres = [];
        foreach ($pres as $pr) {
            $idprecio = (int)($pr['idprecio'] ?? 0);
            if ($idprecio <= 0) continue;
            $producto->setInventarioPrecio(
                $idprecio,
                !empty($pr['controla_stock']) ? 1 : 0,
                (float)($pr['stock'] ?? 0),
                (float)($pr['stock_minimo'] ?? 0),
                $idu
            );
        }
        jsonResponse(['ok' => true]);
        break;

    case 'moverStock':
        requirePermiso('inventario');
        $idprecio = (int)($_POST['idprecio'] ?? 0);
        $tipo = limpiarCadena($_POST['tipo'] ?? 'entrada');   // entrada | salida | ajuste
        $cant = (float)($_POST['cantidad'] ?? 0);
        $nota = limpiarCadena($_POST['nota'] ?? '');
        if (!in_array($tipo, ['entrada','salida','ajuste'], true)) $tipo = 'entrada';
        if ($cant <= 0) jsonResponse(['ok' => false, 'msg' => 'Cantidad inválida']);
        $ok = $producto->moverStockPrecio($idprecio, $tipo, $cant, $nota, $_SESSION['idusuario'] ?? null);
        jsonResponse(['ok' => (bool)$ok]);
        break;

    case 'inventario':
        requirePermiso('inventario');
        $searchV = limpiarCadena($_GET['search'] ?? '');
        $rs = $producto->reporteInventarioDetallado($searchV);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse(['ok' => true, 'data' => $data, 'resumen' => $producto->resumenInventario()]);
        break;

    case 'excelInventario':
        requirePermiso('inventario');
        require_once __DIR__ . "/../modelos/ReporteExcel.php";
        (new ReporteExcel())->generarInventario();
        exit;

    case 'movimientos':
        requirePermiso('inventario');
        $idprecio = (int)($_GET['idprecio'] ?? 0);
        $rs = $producto->movimientos($idprecio, 50);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    default:
        jsonResponse(['error' => 'op no válida']);
}
?>
