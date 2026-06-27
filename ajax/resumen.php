<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/ResumenSunat.php";
requireLogin();

$res = new ResumenSunat();
$op = $_GET['op'] ?? '';

switch ($op) {

    case 'crearRC':
        requirePermiso('resumen_boletas');
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $r = $res->crearRC($fecha, 1, $_SESSION['idusuario'] ?? null);
        jsonResponse($r);
        break;

    case 'crearRA':
        requirePermiso('comunicacion_baja');
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) $ids = explode(',', $ids);
        $ids = array_filter(array_map('intval', $ids));
        $motivo = limpiarCadena($_POST['motivo_baja'] ?? 'ERROR DE EMISION');
        $r = $res->crearRA($ids, $motivo, 1, $_SESSION['idusuario'] ?? null);
        jsonResponse($r);
        break;

    case 'enviar':
        $idresumen = (int)($_POST['idresumen'] ?? 0);
        if ($idresumen <= 0) jsonResponse(['ok' => false, 'msg' => 'idresumen requerido']);
        $row = ejecutarConsultaSimpleFila("SELECT tipo FROM resumen_sunat WHERE idresumen='$idresumen'");
        if (!$row) jsonResponse(['ok' => false, 'msg' => 'Resumen no encontrado']);
        requirePermiso($row['tipo'] === 'RC' ? 'resumen_boletas' : 'comunicacion_baja');
        jsonResponse($res->enviar($idresumen));
        break;

    case 'consultarTicket':
        $idresumen = (int)($_POST['idresumen'] ?? $_REQUEST['idresumen'] ?? 0);
        if ($idresumen <= 0) jsonResponse(['ok' => false, 'msg' => 'idresumen requerido']);
        $row = ejecutarConsultaSimpleFila("SELECT tipo FROM resumen_sunat WHERE idresumen='$idresumen'");
        if (!$row) jsonResponse(['ok' => false, 'msg' => 'Resumen no encontrado']);
        requirePermiso($row['tipo'] === 'RC' ? 'resumen_boletas' : 'comunicacion_baja');
        jsonResponse($res->consultarTicket($idresumen));
        break;

    case 'mostrar':
        requirePermiso('resumen_boletas');
        jsonResponse($res->mostrarCompleto((int)($_REQUEST['idresumen'] ?? 0)));
        break;

    case 'listar':
        requirePermiso('resumen_boletas');
        $filtros = [
            'tipo'             => limpiarCadena($_REQUEST['f_tipo']   ?? ''),
            'estado'           => limpiarCadena($_REQUEST['f_estado'] ?? ''),
            'fecha_referencia' => limpiarCadena($_REQUEST['f_fecha']  ?? ''),
        ];
        $rs = $res->listar($filtros);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    default:
        jsonResponse(['error' => 'op no valida']);
}
