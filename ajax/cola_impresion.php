<?php
// =====================================================================
// ENDPOINT PARA EL BRIDGE LOCAL — autenticacion por token (no sesion)
// =====================================================================
// El bridge corre en una PC del local y consulta este endpoint cada 2-3s
// para obtener trabajos pendientes. Una vez impresos, los marca como hechos.
// =====================================================================

require_once __DIR__ . "/../config/global.php";
require_once __DIR__ . "/../config/Conexion.php";
require_once __DIR__ . "/../modelos/Impresora.php";

header('Content-Type: application/json; charset=utf-8');

// Token de autenticacion para el bridge (define BRIDGE_TOKEN en config/global.php)
$tokenEsperado = defined('BRIDGE_TOKEN') ? BRIDGE_TOKEN : '';
$tokenRecibido = $_SERVER['HTTP_X_BRIDGE_TOKEN'] ?? ($_REQUEST['token'] ?? '');

if ($tokenEsperado === '' || $tokenRecibido !== $tokenEsperado) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Token invalido']);
    exit;
}

$imp = new Impresora();
$op  = $_GET['op'] ?? '';

switch ($op) {

    case 'pendientes':
        // Devuelve trabajos pendientes con datos de impresora
        $rs = $imp->pendientes(20);
        $data = [];
        while ($r = $rs->fetch_assoc()) {
            $r['payload'] = json_decode($r['payload'], true);
            $data[] = $r;
        }
        echo json_encode(['ok' => true, 'trabajos' => $data]);
        break;

    case 'marcar_impreso':
        $idcola = (int)($_REQUEST['idcola'] ?? 0);
        if (!$idcola) { echo json_encode(['ok' => false]); exit; }
        $imp->marcarImpreso($idcola);
        echo json_encode(['ok' => true]);
        break;

    case 'marcar_imprimiendo':
        $idcola = (int)($_REQUEST['idcola'] ?? 0);
        if (!$idcola) { echo json_encode(['ok' => false]); exit; }
        $imp->marcarImprimiendo($idcola);
        echo json_encode(['ok' => true]);
        break;

    case 'marcar_error':
        $idcola = (int)($_REQUEST['idcola'] ?? 0);
        $msg    = $_REQUEST['msg']    ?? '';
        if (!$idcola) { echo json_encode(['ok' => false]); exit; }
        $imp->marcarError($idcola, $msg);
        echo json_encode(['ok' => true]);
        break;

    case 'ping':
        echo json_encode(['ok' => true, 'pong' => date('Y-m-d H:i:s')]);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'op no valida']);
}
?>
