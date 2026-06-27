<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Cliente.php";

requireLogin();

$cliente = new Cliente();

$idcliente = isset($_POST['idcliente']) ? limpiarCadena($_POST['idcliente']) : '';
$nombre    = isset($_POST['nombre'])    ? limpiarCadena($_POST['nombre'])    : '';
$documento = isset($_POST['documento']) ? limpiarCadena($_POST['documento']) : '';
$telefono  = isset($_POST['telefono'])  ? limpiarCadena($_POST['telefono'])  : '';
$email     = isset($_POST['email'])     ? limpiarCadena($_POST['email'])     : '';
$key       = isset($_POST['key'])       ? limpiarCadena($_POST['key'])       : '';

$op = $_GET['op'] ?? '';

switch ($op) {

    case 'guardaryeditar':
        requirePermiso('clientes');
        if (empty($idcliente)) {
            // Anti-duplicado: si llega un documento no vacio, validar que no exista ya
            if ($documento !== '') {
                $existente = $cliente->buscarPorDocumento($documento);
                if ($existente && !empty($existente['idcliente'])) {
                    jsonResponse([
                        'ok'         => false,
                        'duplicado'  => true,
                        'idcliente'  => (int)$existente['idcliente'],
                        'nombre'     => $existente['nombre'],
                        'msg'        => 'Ya existe un cliente con ese documento: ' . $existente['nombre']
                    ]);
                }
            }
            $id = $cliente->insertar($nombre, $documento, $telefono, $email);
            jsonResponse(['ok' => $id > 0, 'idcliente' => $id]);
        } else {
            // Editar: si cambia el documento, verificar que no choque con otro cliente
            if ($documento !== '') {
                $existente = $cliente->buscarPorDocumento($documento);
                if ($existente && (int)$existente['idcliente'] !== (int)$idcliente) {
                    jsonResponse([
                        'ok'         => false,
                        'duplicado'  => true,
                        'idcliente'  => (int)$existente['idcliente'],
                        'nombre'     => $existente['nombre'],
                        'msg'        => 'Otro cliente ya tiene ese documento: ' . $existente['nombre']
                    ]);
                }
            }
            $r = $cliente->editar($idcliente, $nombre, $documento, $telefono, $email);
            jsonResponse(['ok' => (bool)$r]);
        }
        break;

    case 'buscarPorDocumento':
        requirePermiso('clientes');
        $r = $cliente->buscarPorDocumento($documento);
        jsonResponse($r ?: null);
        break;

    case 'desactivar':
        requirePermiso('clientes');
        jsonResponse(['ok' => (bool)$cliente->desactivar($idcliente)]);
        break;

    case 'activar':
        requirePermiso('clientes');
        jsonResponse(['ok' => (bool)$cliente->activar($idcliente)]);
        break;

    case 'mostrar':
        jsonResponse($cliente->mostrar($idcliente));
        break;

    case 'listar':
        $rs = $cliente->listar();
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'buscar':
        $rs = $cliente->buscar($key);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'estadisticas':
        jsonResponse($cliente->estadisticas());
        break;

    case 'datatable':
        // DataTables server-side params
        $draw     = (int)($_REQUEST['draw']   ?? 1);
        $start    = (int)($_REQUEST['start']  ?? 0);
        $length   = (int)($_REQUEST['length'] ?? 10);
        $searchV  = limpiarCadena($_REQUEST['search']['value'] ?? '');
        $orderCol = (int)($_REQUEST['order'][0]['column'] ?? 0);
        $orderDir = limpiarCadena($_REQUEST['order'][0]['dir'] ?? 'desc');

        $rs = $cliente->listarServerSide($start, $length, $searchV, $orderCol, $orderDir);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $cliente->contarServerSide('', false),
            'recordsFiltered' => $cliente->contarServerSide($searchV, true),
            'data'            => $data,
        ]);
        exit;
        break;

    default:
        jsonResponse(['error' => 'op no válida']);
}
?>
