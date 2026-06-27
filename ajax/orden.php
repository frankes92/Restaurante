<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Orden.php";
require_once __DIR__ . "/../modelos/Caja.php";

requireLogin();

$orden = new Orden();

// Bloqueo global: si NO hay una caja abierta, no se permiten ventas ni envíos
// de comanda desde NINGÚN dispositivo (PC, laptop, celular). Server-side.
function _requiereCajaAbierta() {
    $ses = (new Caja())->sesionActual();
    if (!$ses || empty($ses['idsesion'])) {
        jsonResponse(['ok' => false, 'msg' => 'La caja está cerrada. Abre una sesión de caja para poder cobrar o enviar a cocina.']);
        exit;
    }
}

$idorden     = isset($_POST['idorden'])     ? limpiarCadena($_POST['idorden'])     : '';
$idmesa      = isset($_POST['idmesa'])      ? limpiarCadena($_POST['idmesa'])      : '';
$idproducto  = isset($_POST['idproducto'])  ? limpiarCadena($_POST['idproducto'])  : '';
$idcliente   = isset($_POST['idcliente'])   ? limpiarCadena($_POST['idcliente'])   : '';
$idsesion    = isset($_POST['idsesion'])    ? limpiarCadena($_POST['idsesion'])    : '';
$iddetalle   = isset($_POST['iddetalle'])   ? limpiarCadena($_POST['iddetalle'])   : '';
$tipo        = isset($_POST['tipo'])        ? limpiarCadena($_POST['tipo'])        : 'dine_in';
$mozo        = isset($_POST['mozo'])        ? limpiarCadena($_POST['mozo'])        : '';
$cantidad    = isset($_POST['cantidad'])    ? limpiarCadena($_POST['cantidad'])    : '1';
$nota        = isset($_POST['nota'])        ? limpiarCadena($_POST['nota'])        : '';
$observacion = isset($_POST['observacion']) ? limpiarCadena($_POST['observacion']) : '';
$metodoPago  = isset($_POST['metodo_pago']) ? limpiarCadena($_POST['metodo_pago']) : 'efectivo';
$estado      = isset($_POST['estado'])      ? limpiarCadena($_POST['estado'])      : '';

// Datos de cobro avanzados
$tipoComprobante = isset($_POST['tipo_comprobante']) ? limpiarCadena($_POST['tipo_comprobante']) : 'ticket';
$montoRecibido   = isset($_POST['monto_recibido'])   ? (float)$_POST['monto_recibido']           : 0;
$pagoReferencia  = isset($_POST['pago_referencia'])  ? limpiarCadena($_POST['pago_referencia'])  : '';
$pagoMetadata    = isset($_POST['pago_metadata'])    ? $_POST['pago_metadata']                   : '';

// Usuario actual (logueado). Se usa para multi-mozo: cada orden recuerda quien la creo
// y solo el mismo mozo (o admin/cajero) puede editarla.
$__u            = currentUser();
$__idusuario    = $__u ? (int)$__u['idusuario']  : 0;
$__rolCodigo    = $__u ? (string)$__u['rol_codigo'] : '';
$__esMozo       = ($__rolCodigo === 'mozo');
$__nombreFull   = $__u ? trim($__u['nombre'] . ' ' . $__u['apellidos']) : '';

// Si no se envia mozo, usar el del usuario logueado
if ($mozo === '') {
    $mozo = $__nombreFull;
}

/**
 * Verifica que el usuario logueado pueda editar la orden indicada.
 * Reglas:
 *  - Admin y cajero pueden editar CUALQUIER orden.
 *  - Mozo solo puede editar las ordenes que el mismo creo (idusuario coincide).
 *  - Si la orden no tiene idusuario (datos legacy), se permite editar (no bloquear).
 * Si no puede editar, responde JSON con error y CORTA la ejecucion.
 */
function _guardEditarOrden($idorden)
{
    global $__esMozo, $__idusuario;
    if (!$__esMozo) return true;       // admin/cajero: libre
    if ($idorden === '' || $idorden === null) return true;

    $idordenInt = (int)$idorden;
    $row = ejecutarConsultaSimpleFila(
        "SELECT o.idusuario,
                TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellidos,''))) AS nombre
         FROM orden o LEFT JOIN usuario u ON u.idusuario=o.idusuario
         WHERE o.idorden='$idordenInt'"
    );
    if (!$row) return true;                           // orden no existe, dejar pasar
    if (empty($row['idusuario'])) return true;        // legacy sin owner, permitir
    if ((int)$row['idusuario'] === (int)$__idusuario) return true;

    jsonResponse([
        'ok'  => false,
        'msg' => 'Esta orden esta siendo atendida por ' . ($row['nombre'] ?: 'otro mozo'),
        'cross_mozo' => true,
        'propietario_nombre' => $row['nombre'],
    ]);
    exit;
}

$op = $_GET['op'] ?? '';

switch ($op) {

    case 'crear':
        requirePermiso('nuevaorden');
        $r = $orden->crear($idmesa, $tipo, $mozo, $idsesion, $__idusuario);
        jsonResponse(['ok' => true, 'idorden' => $r['idorden'], 'numero' => $r['numero']]);
        break;

    case 'agregarItem':
        requirePermiso('nuevaorden');
        _guardEditarOrden($idorden);
        $idprecio = isset($_POST['idprecio']) ? limpiarCadena($_POST['idprecio']) : null;
        $res = $orden->agregarItem($idorden, $idproducto, $cantidad, $nota, $idprecio ?: null);
        // Bloqueo por stock: devuelve array con ok=false (agotado o stock insuficiente)
        if (is_array($res) && empty($res['ok'])) {
            jsonResponse(['ok' => false, 'agotado' => !empty($res['agotado']), 'msg' => $res['msg'] ?? 'No se pudo agregar']);
        }
        jsonResponse(['ok' => $res !== false, 'iddetalle' => $res]);
        break;

    case 'actualizarItemCantidad':
        requirePermiso('nuevaorden');
        // Necesitamos el idorden a partir del iddetalle
        $rowDet = ejecutarConsultaSimpleFila("SELECT idorden FROM orden_detalle WHERE iddetalle='" . (int)$iddetalle . "'");
        if ($rowDet) _guardEditarOrden($rowDet['idorden']);
        $res = $orden->actualizarItemCantidad($iddetalle, $cantidad);
        // Bloqueo por stock al aumentar: devuelve array con ok=false y mensaje
        if (is_array($res)) {
            jsonResponse(['ok' => false, 'msg' => $res['msg'] ?? 'No se pudo actualizar']);
        }
        jsonResponse(['ok' => (bool)$res]);
        break;

    case 'eliminarItem':
        requirePermiso('nuevaorden');
        $rowDet = ejecutarConsultaSimpleFila("SELECT idorden FROM orden_detalle WHERE iddetalle='" . (int)$iddetalle . "'");
        if ($rowDet) _guardEditarOrden($rowDet['idorden']);
        jsonResponse(['ok' => (bool)$orden->eliminarItem($iddetalle)]);
        break;

    case 'marcarCortesia':
        requirePermiso('nuevaorden');
        $rowDet = ejecutarConsultaSimpleFila("SELECT idorden FROM orden_detalle WHERE iddetalle='" . (int)$iddetalle . "'");
        if ($rowDet) _guardEditarOrden($rowDet['idorden']);
        $cortesia = isset($_POST['cortesia']) ? (int)$_POST['cortesia'] : 0;
        jsonResponse(['ok' => (bool)$orden->marcarCortesia($iddetalle, $cortesia)]);
        break;

    case 'marcarCortesiaParcial':
        requirePermiso('nuevaorden');
        $rowDet = ejecutarConsultaSimpleFila("SELECT idorden FROM orden_detalle WHERE iddetalle='" . (int)$iddetalle . "'");
        if ($rowDet) _guardEditarOrden($rowDet['idorden']);
        $res = $orden->marcarCortesiaParcial($iddetalle, $cantidad);
        // Validacion fallida: devuelve array con mensaje
        if (is_array($res)) {
            jsonResponse(['ok' => false, 'msg' => $res['msg'] ?? 'No se pudo aplicar la cortesía']);
        }
        jsonResponse(['ok' => (bool)$res]);
        break;

    case 'actualizarCabecera':
        requirePermiso('nuevaorden');
        _guardEditarOrden($idorden);
        jsonResponse(['ok' => (bool)$orden->actualizarCabecera($idorden, $tipo, $observacion, $idcliente)]);
        break;

    case 'enviarACocina':
        requirePermiso('enviar_cocina');
        _requiereCajaAbierta();
        _guardEditarOrden($idorden);
        $res = $orden->enviarACocina($idorden);
        jsonResponse([
            'ok'          => true,
            'adiciones'   => (object)($res['adiciones']   ?? []),
            'anulaciones' => (object)($res['anulaciones'] ?? []),
            'total_adic'  => count($res['adiciones']   ?? []),
            'total_anul'  => count($res['anulaciones'] ?? []),
        ]);
        break;

    case 'cobrar':
        requirePermiso('cobrar');
        _requiereCajaAbierta();
        // Tipos permitidos: ticket, nota_venta, boleta, factura
        if (!in_array($tipoComprobante, ['ticket','nota_venta','boleta','factura'], true)) {
            jsonResponse(['ok' => false, 'msg' => 'Tipo de comprobante no válido']);
        }
        // Boleta/factura requieren permiso especifico
        if ($tipoComprobante === 'boleta'  && !hasPermiso('emitir_boleta'))  jsonResponse(['ok' => false, 'msg' => 'No tienes permiso para emitir boletas']);
        if ($tipoComprobante === 'factura' && !hasPermiso('emitir_factura')) jsonResponse(['ok' => false, 'msg' => 'No tienes permiso para emitir facturas']);
        // imprimir_comprobante=1 lo envía SOLO la app móvil (para imprimir por el
        // bridge). En laptop/PC no se envía: ahí imprime por el navegador.
        $imprimirComp = !empty($_POST['imprimir_comprobante']);
        $r = $orden->cobrar($idorden, $metodoPago, $idsesion, $tipoComprobante, $montoRecibido, $pagoReferencia, $pagoMetadata, $imprimirComp);
        jsonResponse(['ok' => (bool)$r, 'idorden' => $idorden]);
        break;

    case 'imprimirComprobante':
        // Encola la impresion del comprobante por el bridge (post-cobro, movil).
        requirePermiso('cobrar');
        jsonResponse(['ok' => (bool)$orden->imprimirComprobante($idorden)]);
        break;

    case 'anular':
        requirePermiso('anular_orden');
        jsonResponse(['ok' => (bool)$orden->anular($idorden)]);
        break;

    case 'mostrar':
        $data = $orden->mostrarCompleta($idorden);
        if ($data) {
            // mi_orden: true si el usuario logueado puede editar esta orden
            // (admin/cajero siempre pueden, mozo solo si es el propietario)
            $owner = (int)($data['idusuario'] ?? 0);
            $miOrden = (!$__esMozo) || ($owner === 0) || ($owner === $__idusuario);
            $data['mi_orden']  = (bool)$miOrden;
            $data['readonly']  = !$miOrden;
        }
        jsonResponse($data);
        break;

    case 'porMesa':
        $estadoBuscar = $estado ?: 'en_curso';
        // Devolver TODAS las ordenes activas (en_curso o enviada), no solo una.
        // Esto permite al frontend saber si la mesa esta atendida por otro mozo.
        $rs = ejecutarConsulta("SELECT o.*, TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellidos,''))) AS propietario_nombre
                                FROM orden o
                                LEFT JOIN usuario u ON u.idusuario = o.idusuario
                                WHERE o.idmesa='" . (int)$idmesa . "'
                                  AND o.estado IN ('en_curso','enviada')
                                ORDER BY o.idorden DESC LIMIT 1");
        $row = $rs ? $rs->fetch_assoc() : null;
        if ($row) {
            $owner = (int)($row['idusuario'] ?? 0);
            $row['mi_orden'] = (!$__esMozo) || ($owner === 0) || ($owner === $__idusuario);
            $row['readonly'] = !$row['mi_orden'];
        }
        jsonResponse($row);
        break;

    case 'listar':
        $tipoF  = $_GET['tipo']   ?? '';
        $estF   = $_GET['estado'] ?? '';
        $desde  = $_GET['desde']  ?? '';
        $hasta  = $_GET['hasta']  ?? '';
        $rs = $orden->listar($estF, $tipoF, $desde, $hasta);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'siguienteNumero':
        jsonResponse(['numero' => $orden->siguienteNumero()]);
        break;

    case 'datatable':
        $draw     = (int)($_REQUEST['draw']   ?? 1);
        $start    = (int)($_REQUEST['start']  ?? 0);
        $length   = (int)($_REQUEST['length'] ?? 10);
        $searchV  = limpiarCadena($_REQUEST['search']['value'] ?? '');
        $orderCol = (int)($_REQUEST['order'][0]['column'] ?? 0);
        $orderDir = limpiarCadena($_REQUEST['order'][0]['dir'] ?? 'desc');

        $filtros = [
            'estado'      => limpiarCadena($_REQUEST['f_estado']      ?? ''),
            'tipo'        => limpiarCadena($_REQUEST['f_tipo']        ?? ''),
            'metodo_pago' => limpiarCadena($_REQUEST['f_metodo']      ?? ''),
            'comprobante' => limpiarCadena($_REQUEST['f_comprobante'] ?? ''),
            'rango'       => limpiarCadena($_REQUEST['f_rango']       ?? ''),
            'desde'       => limpiarCadena($_REQUEST['f_desde']       ?? ''),
            'hasta'       => limpiarCadena($_REQUEST['f_hasta']       ?? ''),
        ];

        $rs = $orden->listarServerSide($start, $length, $searchV, $orderCol, $orderDir, $filtros);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $orden->contarServerSide('', $filtros, false),
            'recordsFiltered' => $orden->contarServerSide($searchV, $filtros, true),
            'data'            => $data,
        ]);
        exit;
        break;

    default:
        jsonResponse(['error' => 'op no válida']);
}
?>
