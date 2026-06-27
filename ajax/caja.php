<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Caja.php";

requireLogin();

$caja = new Caja();

$idsesion     = isset($_POST['idsesion'])     ? limpiarCadena($_POST['idsesion'])     : '';
$cajaCodigo   = isset($_POST['caja_codigo'])  ? limpiarCadena($_POST['caja_codigo'])  : 'AP-001';
$turno        = isset($_POST['turno'])        ? limpiarCadena($_POST['turno'])        : 'Mañana';
$cajero       = isset($_POST['cajero'])       ? limpiarCadena($_POST['cajero'])       : 'Cajero';
$montoInicial = isset($_POST['monto_inicial'])? limpiarCadena($_POST['monto_inicial']): '0';
$montoCierre  = isset($_POST['monto_cierre']) ? limpiarCadena($_POST['monto_cierre']) : '0';
$tipo         = isset($_POST['tipo'])         ? limpiarCadena($_POST['tipo'])         : '';
$monto        = isset($_POST['monto'])        ? limpiarCadena($_POST['monto'])        : '0';
$nota         = isset($_POST['nota'])         ? limpiarCadena($_POST['nota'])         : '';
$metodoPago   = isset($_POST['metodo_pago'])  ? limpiarCadena($_POST['metodo_pago'])  : '';

$op = $_GET['op'] ?? '';

switch ($op) {

    case 'sesionActual':
        jsonResponse($caja->sesionActual());
        break;

    case 'abrirSesion':
        requirePermiso('caja');
        // Si no se envia cajero, usar el del usuario logueado
        if ($cajero === '' || $cajero === 'Cajero') {
            $u = currentUser();
            if ($u) $cajero = trim($u['nombre'] . ' ' . $u['apellidos']);
        }
        $idu = $_SESSION['idusuario'] ?? null;
        $id = $caja->abrirSesion($cajaCodigo, $turno, $cajero, $montoInicial, $idu);
        jsonResponse(['ok' => $id > 0, 'idsesion' => $id]);
        break;

    case 'cerrarSesion':
        requirePermiso('caja');
        jsonResponse(['ok' => (bool)$caja->cerrarSesion($idsesion, $montoCierre)]);
        break;

    case 'agregarMovimiento':
        requirePermiso('caja');
        // Validar tipo permitido (no permitir registrar 'venta' o 'apertura'/'cierre' manualmente)
        if (!in_array($tipo, ['ingreso', 'egreso'], true)) {
            jsonResponse(['ok' => false, 'msg' => 'Tipo de movimiento no permitido']);
        }
        $id = $caja->agregarMovimiento($idsesion, $tipo, $monto, $nota, $metodoPago);
        jsonResponse(['ok' => $id > 0, 'idmovimiento' => $id]);
        break;

    case 'listarMovimientos':
        $rs = $caja->listarMovimientos($idsesion);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'resumenSesion':
        jsonResponse($caja->resumenSesion($idsesion));
        break;

    case 'ventasPorMetodo':
        $rs = $caja->ventasPorMetodo($idsesion);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'datatableMovimientos':
        $draw     = (int)($_REQUEST['draw']   ?? 1);
        $start    = (int)($_REQUEST['start']  ?? 0);
        $length   = (int)($_REQUEST['length'] ?? 10);
        $searchV  = limpiarCadena($_REQUEST['search']['value'] ?? '');
        $orderCol = (int)($_REQUEST['order'][0]['column'] ?? 0);
        $orderDir = limpiarCadena($_REQUEST['order'][0]['dir'] ?? 'desc');
        $idses    = limpiarCadena($_REQUEST['idsesion'] ?? '');
        $tipo     = limpiarCadena($_REQUEST['f_tipo']   ?? '');

        $rs = $caja->movimientosServerSide($idses, $start, $length, $searchV, $orderCol, $orderDir, $tipo);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $caja->contarMovimientos($idses, '', $tipo),
            'recordsFiltered' => $caja->contarMovimientos($idses, $searchV, $tipo),
            'data'            => $data,
        ]);
        exit;
        break;

    // ---------- ARQUEO ----------

    case 'arqueoEsperado':
        // Calcula el monto esperado en caja para la sesion (efectivo)
        jsonResponse($caja->arqueoEsperado($idsesion));
        break;

    case 'arqueoGuardar':
        requirePermiso('arqueo_caja');
        $denom = $_POST['denominaciones'] ?? '';
        if (is_array($denom)) $denom = json_encode($denom);
        $contado = (float)($_POST['monto_contado'] ?? 0);
        $obs     = limpiarCadena($_POST['observacion'] ?? '');
        $idu     = $_SESSION['idusuario'] ?? null;
        $r = $caja->guardarArqueo($idsesion, $idu, $contado, $denom, $obs);
        jsonResponse($r);
        break;

    case 'arqueoListar':
        $rs = $caja->listarArqueos($idsesion);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'historialCierres':
        // Historial de todas las sesiones cerradas (para reimprimir arqueo/consolidado).
        $rs = $caja->historialCierres();
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'arqueoCerrarConSesion':
        // Hace arqueo + cierre de caja en una sola operacion atomica
        requirePermiso('arqueo_caja');
        $denom = $_POST['denominaciones'] ?? '';
        if (is_array($denom)) $denom = json_encode($denom);
        $contado = (float)($_POST['monto_contado'] ?? 0);
        $obs     = limpiarCadena($_POST['observacion'] ?? '');
        $idu     = $_SESSION['idusuario'] ?? null;
        $r = $caja->guardarArqueo($idsesion, $idu, $contado, $denom, $obs);
        if ($r['ok']) {
            $caja->cerrarSesion($idsesion, $contado);
            $r['cerrada'] = true;
        }
        jsonResponse($r);
        break;

    default:
        jsonResponse(['error' => 'op no válida']);
}
