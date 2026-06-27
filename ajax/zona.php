<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Zona.php";

requireLogin();

$zona = new Zona();
$op = $_GET['op'] ?? '';

switch ($op) {

    case 'listar':
        // Cualquier usuario autenticado puede leer zonas (las necesita Nueva Orden)
        jsonResponse($zona->listar() ?: []);
        break;

    case 'mostrar':
        $id = (int)($_REQUEST['idzona'] ?? 0);
        jsonResponse($zona->mostrar($id) ?: []);
        break;

    case 'crearConMesas':
        requirePermiso('zonas');
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $color  = (string)($_POST['color'] ?? '#5b3df5');
        $mesasJson = $_POST['mesas'] ?? '[]';
        $mesas = json_decode($mesasJson, true);
        if (!is_array($mesas)) $mesas = [];
        if ($nombre === '') jsonResponse(['ok' => false, 'msg' => 'Nombre requerido']);

        $r = $zona->crearConMesas($nombre, $color, $mesas);
        jsonResponse($r);
        break;

    case 'editar':
        requirePermiso('zonas');
        $id = (int)($_POST['idzona'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $color  = (string)($_POST['color'] ?? '#5b3df5');
        if ($id <= 0 || $nombre === '') jsonResponse(['ok' => false, 'msg' => 'Datos inválidos']);
        jsonResponse(['ok' => (bool)$zona->editar($id, $nombre, $color)]);
        break;

    case 'eliminar':
        requirePermiso('zonas');
        $id = (int)($_POST['idzona'] ?? 0);
        if ($id <= 0) jsonResponse(['ok' => false, 'msg' => 'Datos inválidos']);
        jsonResponse(['ok' => (bool)$zona->desactivar($id)]);
        break;

    case 'reordenar':
        requirePermiso('zonas');
        $ids = json_decode($_POST['orden'] ?? '[]', true);
        if (!is_array($ids)) jsonResponse(['ok' => false, 'msg' => 'Formato inválido']);
        $zona->reordenar($ids);
        jsonResponse(['ok' => true]);
        break;

    case 'mesasDeZona':
        $id = (int)($_REQUEST['idzona'] ?? 0);
        jsonResponse($zona->mesasDeZona($id) ?: []);
        break;

    case 'mesasSinZona':
        jsonResponse($zona->mesasSinZona() ?: []);
        break;

    case 'asignarMesas':
        requirePermiso('zonas');
        $idzona = $_POST['idzona'] ?? '';   // puede ser '' para quitar la zona
        $idmesas = json_decode($_POST['idmesas'] ?? '[]', true);
        if (!is_array($idmesas)) jsonResponse(['ok' => false, 'msg' => 'Formato inválido']);
        $afect = $zona->asignarMesas($idzona === '' ? null : (int)$idzona, $idmesas);
        jsonResponse(['ok' => true, 'asignadas' => $afect]);
        break;

    default:
        jsonResponse(['ok' => false, 'msg' => 'Operación no válida']);
}
