<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Categoria.php";

requireLogin();

$categoria = new Categoria();

$idcategoria = isset($_POST['idcategoria']) ? limpiarCadena($_POST['idcategoria']) : '';
$codigo      = isset($_POST['codigo'])      ? limpiarCadena($_POST['codigo'])      : '';
$nombre      = isset($_POST['nombre'])      ? limpiarCadena($_POST['nombre'])      : '';
$icono       = isset($_POST['icono'])       ? limpiarCadena($_POST['icono'])       : 'fa-tag';
$color       = isset($_POST['color'])       ? limpiarCadena($_POST['color'])       : '#6b7280';
$orden       = isset($_POST['orden'])       ? limpiarCadena($_POST['orden'])       : '0';
$mostrarComanda = isset($_POST['mostrar_comanda']) ? (int)$_POST['mostrar_comanda'] : 1;

$op = $_GET['op'] ?? '';

switch ($op) {

    case 'guardaryeditar':
        requirePermiso('productos');
        if (empty($idcategoria)) {
            $id = $categoria->insertar($codigo, $nombre, $icono, $color, $orden, $mostrarComanda);
            jsonResponse(['ok' => $id > 0, 'idcategoria' => $id, 'msg' => 'Categoría registrada']);
        } else {
            $r = $categoria->editar($idcategoria, $codigo, $nombre, $icono, $color, $orden, $mostrarComanda);
            jsonResponse(['ok' => (bool)$r, 'msg' => 'Categoría actualizada']);
        }
        break;

    case 'desactivar':
        requirePermiso('productos');
        $r = $categoria->desactivar($idcategoria);
        jsonResponse(['ok' => (bool)$r]);
        break;

    case 'activar':
        requirePermiso('productos');
        $r = $categoria->activar($idcategoria);
        jsonResponse(['ok' => (bool)$r]);
        break;

    case 'mostrar':
        jsonResponse($categoria->mostrar($idcategoria));
        break;

    case 'listar':
        $rs = $categoria->listar();
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'listarActivas':
        $rs = $categoria->listarActivas();
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'datatable':
        $draw     = (int)($_REQUEST['draw'] ?? 1);
        $start    = (int)($_REQUEST['start'] ?? 0);
        $length   = (int)($_REQUEST['length'] ?? 10);
        $searchV  = limpiarCadena($_REQUEST['search']['value'] ?? '');
        $orderCol = (int)($_REQUEST['order'][0]['column'] ?? 0);
        $orderDir = limpiarCadena($_REQUEST['order'][0]['dir'] ?? 'asc');
        $rs = $categoria->listarServerSide($start, $length, $searchV, $orderCol, $orderDir);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $categoria->contar('', false),
            'recordsFiltered' => $categoria->contar($searchV, true),
            'data' => $data,
        ]);
        exit;

    default:
        jsonResponse(['error' => 'op no válida']);
}
?>
