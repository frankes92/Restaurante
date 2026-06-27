<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Numeracion.php";
requireLogin();

$num = new Numeracion();
$op = $_GET['op'] ?? '';

switch ($op) {
    case 'listar':
        $idempresa = (int)($_REQUEST['idempresa'] ?? 1);
        $rs = $num->listar($idempresa);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'mostrar':
        jsonResponse($num->mostrar((int)($_POST['idnumeracion'] ?? 0)));
        break;

    case 'existeSerie':
        // Valida si hay serie activa para un tipo de documento ('01' factura, '03' boleta)
        $tipoDoc   = limpiarCadena($_REQUEST['tipo_documento'] ?? '');
        $idempresa = (int)($_REQUEST['idempresa'] ?? 1);
        jsonResponse(['ok' => true, 'existe' => $num->existeSerieActiva($tipoDoc, $idempresa)]);
        break;

    case 'tiposDisponibles':
        // Devuelve {boleta:bool, factura:bool} segun series activas
        $idempresa = (int)($_REQUEST['idempresa'] ?? 1);
        jsonResponse(['ok' => true, 'tipos' => $num->tiposDisponibles($idempresa)]);
        break;

    case 'guardaryeditar':
        requirePermiso('config_numeracion');
        $idnumeracion = (int)($_POST['idnumeracion'] ?? 0);
        $idempresa    = (int)($_POST['idempresa'] ?? 1);
        $tipoDoc      = limpiarCadena($_POST['tipo_documento'] ?? '');
        $serie        = limpiarCadena($_POST['serie'] ?? '');
        $ultimoNum    = (int)($_POST['ultimo_numero'] ?? 0);
        $descripcion  = limpiarCadena($_POST['descripcion'] ?? '');

        if ($idnumeracion === 0) {
            $id = $num->insertar($idempresa, $tipoDoc, $serie, $ultimoNum, $descripcion);
            jsonResponse(['ok' => $id > 0, 'idnumeracion' => $id]);
        } else {
            jsonResponse(['ok' => (bool)$num->editar($idnumeracion, $serie, $ultimoNum, $descripcion)]);
        }
        break;

    case 'activar':
        requirePermiso('config_numeracion');
        jsonResponse(['ok' => (bool)$num->activar((int)($_POST['idnumeracion'] ?? 0))]);
        break;

    case 'desactivar':
        requirePermiso('config_numeracion');
        jsonResponse(['ok' => (bool)$num->desactivar((int)($_POST['idnumeracion'] ?? 0))]);
        break;

    default:
        jsonResponse(['error' => 'op no valida']);
}
?>
