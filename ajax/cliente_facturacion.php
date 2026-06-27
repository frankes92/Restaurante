<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/ClienteFacturacion.php";
requireLogin();

$cli = new ClienteFacturacion();
$op = $_GET['op'] ?? '';

switch ($op) {
    case 'buscarPorDoc':
        $doc = limpiarCadena($_REQUEST['numero_documento'] ?? '');
        jsonResponse($cli->buscarPorDoc($doc));
        break;

    case 'guardar':
        $tipoDoc = limpiarCadena($_POST['tipo_documento'] ?? '1');
        $numDoc  = limpiarCadena($_POST['numero_documento'] ?? '');
        $razon   = limpiarCadena($_POST['razon_social'] ?? '');
        $direccion = limpiarCadena($_POST['direccion'] ?? '');
        $email   = limpiarCadena($_POST['email'] ?? '');
        $telefono = limpiarCadena($_POST['telefono'] ?? '');
        $id = $cli->buscarOInsertar($tipoDoc, $numDoc, $razon, $direccion, $email, $telefono);
        jsonResponse(['ok' => $id > 0, 'idclifact' => $id]);
        break;

    case 'listar':
        $rs = $cli->listar();
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    default:
        jsonResponse(['error' => 'op no valida']);
}
?>
