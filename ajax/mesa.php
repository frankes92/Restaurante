<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Mesa.php";

requireLogin();

$mesa = new Mesa();

$idmesa    = isset($_POST['idmesa'])    ? limpiarCadena($_POST['idmesa'])    : '';
$numero    = isset($_POST['numero'])    ? limpiarCadena($_POST['numero'])    : '';
$capacidad = isset($_POST['capacidad']) ? limpiarCadena($_POST['capacidad']) : '4';
$estado    = isset($_POST['estado'])    ? limpiarCadena($_POST['estado'])    : 'libre';
$idzona    = isset($_POST['idzona'])    ? limpiarCadena($_POST['idzona'])    : '';

$op = $_GET['op'] ?? '';

switch ($op) {

    case 'guardaryeditar':
        requirePermiso('mesas');
        if (empty($idmesa)) {
            $id = $mesa->insertar($numero, $capacidad, $estado, $idzona);
            jsonResponse(['ok' => $id > 0, 'idmesa' => $id]);
        } else {
            $r = $mesa->editar($idmesa, $numero, $capacidad, $estado, $idzona);
            jsonResponse(['ok' => (bool)$r]);
        }
        break;

    case 'cambiarEstado':
        requirePermiso('mesas');
        jsonResponse(['ok' => (bool)$mesa->cambiarEstado($idmesa, $estado)]);
        break;

    case 'desactivar':
        requirePermiso('mesas');
        jsonResponse(['ok' => (bool)$mesa->desactivar($idmesa)]);
        break;

    case 'activar':
        requirePermiso('mesas');
        jsonResponse(['ok' => (bool)$mesa->activar($idmesa)]);
        break;

    case 'mostrar':
        jsonResponse($mesa->mostrar($idmesa));
        break;

    case 'listar':
        $rs = $mesa->listar();
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'contarPorEstado':
        $rs = $mesa->contarPorEstado();
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[$r['estado']] = (int)$r['total']; }
        jsonResponse($data);
        break;

    case 'reordenar':
        requirePermiso('mesas');
        $ids = json_decode($_POST['orden'] ?? '[]', true);
        if (!is_array($ids)) jsonResponse(['ok' => false, 'msg' => 'Formato inválido']);
        $mesa->reordenar($ids);
        jsonResponse(['ok' => true]);
        break;

    case 'resetOrden':
        requirePermiso('mesas');
        jsonResponse(['ok' => (bool)$mesa->resetOrden()]);
        break;

    case 'getColumnas':
        jsonResponse(['ok' => true, 'columnas' => $mesa->getColumnas()]);
        break;

    case 'setColumnas':
        requirePermiso('mesas');
        $val = $_POST['columnas'] ?? 'auto';
        jsonResponse(['ok' => (bool)$mesa->setColumnas($val)]);
        break;

    default:
        jsonResponse(['error' => 'op no válida']);
}
?>
