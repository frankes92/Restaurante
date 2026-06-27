<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Licencia.php";

// Si no hay sesion, solo permitimos consultar el estado publico (para la pagina de bloqueo).
$lic = new Licencia();
$op  = $_GET['op'] ?? '';

switch ($op) {

    case 'estado':
        // Endpoint publico (sin requireLogin) para que la pantalla de bloqueo
        // pueda mostrar la informacion. Devuelve datos no sensibles.
        $info = licenciaInfo();
        unset($info['observacion']);
        jsonResponse($info);
        break;

    case 'detalle':
        requireLogin();
        if (!hasPermiso('config_licencia')) jsonResponse(['ok' => false, 'msg' => 'No autorizado']);
        $row = $lic->actual();
        jsonResponse($row);
        break;

    case 'historial':
        requireLogin();
        if (!hasPermiso('config_licencia')) jsonResponse(['ok' => false, 'msg' => 'No autorizado']);
        $idlic = (int)($_REQUEST['idlicencia'] ?? 0);
        if ($idlic <= 0) {
            $r = $lic->actual();
            $idlic = $r ? (int)$r['idlicencia'] : 0;
        }
        $rs = $lic->listarHistorial($idlic);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'extender':
        // Permitir que un admin con clave maestra extienda incluso si esta bloqueado.
        requireLogin();
        if (!hasPermiso('config_licencia')) jsonResponse(['ok' => false, 'msg' => 'No autorizado']);

        $master   = $_POST['master_key']        ?? '';
        $nuevoVen = trim($_POST['fecha_vencimiento'] ?? '');
        $monto    = $_POST['monto_pagado']      ?? 0;
        $obs      = trim($_POST['observacion']  ?? '');

        if (!$lic->verificarMaster($master)) {
            seguridadLog('licencia_master_fail', $_SESSION['login'] ?? null, $_SESSION['idusuario'] ?? null, 'clave maestra incorrecta');
            jsonResponse(['ok' => false, 'msg' => 'Clave maestra incorrecta']);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $nuevoVen)) {
            jsonResponse(['ok' => false, 'msg' => 'Fecha de vencimiento inválida (YYYY-MM-DD)']);
        }

        $row = $lic->actual();
        $idl = $row ? (int)$row['idlicencia'] : 0;
        if ($idl <= 0) {
            // No hay licencia, crear una nueva
            $cliente = trim($_POST['cliente_nombre'] ?? 'Cliente');
            $aviso   = (int)($_POST['dias_aviso'] ?? 5);
            $idl = $lic->crear($cliente, date('Y-m-d'), $nuevoVen, $aviso, $obs, $_SESSION['idusuario'] ?? null);
            seguridadLog('licencia_crear', $_SESSION['login'] ?? null, $_SESSION['idusuario'] ?? null, "vencimiento: $nuevoVen");
            jsonResponse(['ok' => $idl > 0, 'msg' => 'Licencia creada y activada']);
        }

        $ok = $lic->extender($idl, $nuevoVen, $monto, $obs, $_SESSION['idusuario'] ?? null);
        if ($ok) {
            seguridadLog('licencia_extender', $_SESSION['login'] ?? null, $_SESSION['idusuario'] ?? null, "nuevo vencimiento: $nuevoVen");
        }
        jsonResponse(['ok' => $ok, 'msg' => $ok ? 'Licencia extendida' : 'No se pudo extender']);
        break;

    case 'suspender':
        requireLogin();
        if (!hasPermiso('config_licencia')) jsonResponse(['ok' => false, 'msg' => 'No autorizado']);
        $master = $_POST['master_key'] ?? '';
        $obs    = trim($_POST['observacion'] ?? '');
        if (!$lic->verificarMaster($master)) jsonResponse(['ok' => false, 'msg' => 'Clave maestra incorrecta']);
        $row = $lic->actual();
        if (!$row) jsonResponse(['ok' => false, 'msg' => 'No hay licencia']);
        $ok = $lic->suspender($row['idlicencia'], $obs, $_SESSION['idusuario'] ?? null);
        if ($ok) seguridadLog('licencia_suspender', $_SESSION['login'] ?? null, $_SESSION['idusuario'] ?? null, $obs);
        jsonResponse(['ok' => $ok]);
        break;

    case 'reactivar':
        requireLogin();
        if (!hasPermiso('config_licencia')) jsonResponse(['ok' => false, 'msg' => 'No autorizado']);
        $master = $_POST['master_key'] ?? '';
        $obs    = trim($_POST['observacion'] ?? '');
        if (!$lic->verificarMaster($master)) jsonResponse(['ok' => false, 'msg' => 'Clave maestra incorrecta']);
        $row = $lic->actual();
        if (!$row) jsonResponse(['ok' => false, 'msg' => 'No hay licencia']);
        $ok = $lic->reactivar($row['idlicencia'], $obs, $_SESSION['idusuario'] ?? null);
        if ($ok) seguridadLog('licencia_reactivar', $_SESSION['login'] ?? null, $_SESSION['idusuario'] ?? null, $obs);
        jsonResponse(['ok' => $ok]);
        break;

    default:
        jsonResponse(['error' => 'op no válida']);
}
