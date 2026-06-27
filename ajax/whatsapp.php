<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Whatsapp.php";
requireLogin();

$wa = new Whatsapp();
$op = $_GET['op'] ?? '';

switch ($op) {

    // ============== PLANTILLAS ==============
    case 'plantillas':
        $rs = $wa->listarPlantillas(!empty($_GET['solo_activas']));
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'plantilla':
        jsonResponse($wa->plantilla((int)($_REQUEST['idplantilla'] ?? 0)));
        break;

    case 'plantillaPorCodigo':
        jsonResponse($wa->plantillaPorCodigo($_REQUEST['codigo'] ?? ''));
        break;

    case 'guardarPlantilla':
        requirePermiso('whatsapp_plantillas');
        $idplantilla = (int)($_POST['idplantilla'] ?? 0);
        $codigo  = trim($_POST['codigo']  ?? '');
        $nombre  = trim($_POST['nombre']  ?? '');
        $mensaje = $_POST['mensaje']      ?? '';
        $tipo    = $_POST['tipo']         ?? 'generico';
        $activo  = (int)($_POST['activo'] ?? 1);

        if ($codigo === '' || $nombre === '' || trim($mensaje) === '') {
            jsonResponse(['ok' => false, 'msg' => 'Código, nombre y mensaje son obligatorios']);
        }
        if (!in_array($tipo, ['cobro','cumple','festivo','promocion','generico'], true)) {
            $tipo = 'generico';
        }
        $r = $wa->guardarPlantilla($idplantilla, $codigo, $nombre, $mensaje, $tipo, $activo);
        jsonResponse(['ok' => (bool)$r, 'idplantilla' => $idplantilla > 0 ? $idplantilla : $r]);
        break;

    case 'eliminarPlantilla':
        requirePermiso('whatsapp_plantillas');
        $r = $wa->eliminarPlantilla((int)($_POST['idplantilla'] ?? 0));
        jsonResponse(['ok' => (bool)$r]);
        break;

    // ============== PREVIEW DE MENSAJE (con datos reales) ==============
    case 'preview':
        $mensaje = $_POST['mensaje'] ?? '';
        $datos = [
            'nombre'         => $_POST['nombre']         ?? '',
            'documento'      => $_POST['documento']      ?? '',
            'tipo_doc'       => $_POST['tipo_doc']       ?? '',
            'comprobante'    => $_POST['comprobante']    ?? '',
            'tipo_documento' => $_POST['tipo_documento'] ?? '',
            'total'          => (float)($_POST['total']  ?? 0),
            'link_pdf'       => $_POST['link_pdf']       ?? '',
        ];
        $rendered = $wa->renderMensaje($mensaje, $datos);
        $numero   = $wa->normalizarNumero($_POST['numero'] ?? '');
        $url      = $numero ? $wa->construirLinkWaMe($numero, $rendered) : '';
        jsonResponse([
            'ok'      => true,
            'mensaje' => $rendered,
            'numero'  => $numero,
            'url'     => $url,
        ]);
        break;

    // ============== REGISTRAR ENVIO (despues de generar el link) ==============
    case 'registrarEnvio':
        requirePermiso('whatsapp_enviar');

        $datos = [
            'idcliente'     => $_POST['idcliente']     ?? null,
            'idclifact'     => $_POST['idclifact']     ?? null,
            'idcomprobante' => $_POST['idcomprobante'] ?? null,
            'idplantilla'   => $_POST['idplantilla']   ?? null,
            'numero'        => $_POST['numero']        ?? '',
            'nombre_cliente'=> $_POST['nombre_cliente']?? '',
            'documento'     => $_POST['documento']     ?? '',
            'mensaje'       => $_POST['mensaje']       ?? '',
            'idusuario'     => $_SESSION['idusuario']  ?? null,
            'tipo'          => $_POST['tipo']          ?? 'manual',
        ];
        $idenvio = $wa->registrarEnvio($datos);

        // Guardar numero en cliente / cliente_facturacion si pidio "guardar numero"
        if (!empty($_POST['guardar_numero'])) {
            if (!empty($datos['idcliente'])) $wa->guardarWhatsappCliente($datos['idcliente'], $datos['numero']);
            if (!empty($datos['idclifact'])) $wa->guardarWhatsappFacturacion($datos['idclifact'], $datos['numero']);
        }

        jsonResponse(['ok' => $idenvio > 0, 'idenvio' => $idenvio]);
        break;

    // ============== HISTORIAL ==============
    case 'historial':
        $filtros = [
            'tipo'   => $_GET['tipo']   ?? '',
            'desde'  => $_GET['desde']  ?? '',
            'hasta'  => $_GET['hasta']  ?? '',
            'numero' => $_GET['numero'] ?? '',
        ];
        $rs = $wa->listarHistorial($filtros);
        $data = [];
        while ($r = $rs->fetch_assoc()) {
            // Truncar mensaje para listado
            $r['mensaje_resumen'] = mb_substr($r['mensaje'], 0, 80) . (mb_strlen($r['mensaje']) > 80 ? '…' : '');
            $data[] = $r;
        }
        jsonResponse($data);
        break;

    // ============== CLIENTES PARA ENVIO MASIVO ==============
    case 'clientes':
        requirePermiso('whatsapp_masivo');
        $filtro = $_GET['filtro'] ?? 'todos';
        $param  = (int)($_GET['param'] ?? 0);
        $rs = $wa->clientesParaWhatsapp($filtro, $param);
        $data = [];
        while ($r = $rs->fetch_assoc()) {
            $r['numero_normalizado'] = $wa->normalizarNumero($r['whatsapp'] ?: $r['telefono']);
            $data[] = $r;
        }
        jsonResponse($data);
        break;

    // ============== BUSCAR WHATSAPP YA GUARDADO ==============
    case 'buscarPorDocumento':
        $doc = $_REQUEST['documento'] ?? '';
        $num = $wa->buscarWhatsappPorDocumento($doc);
        jsonResponse(['numero' => $num ?: '']);
        break;

    // ============== ESTADISTICAS ==============
    case 'estadisticas':
        jsonResponse($wa->estadisticas());
        break;

    default:
        jsonResponse(['error' => 'op no válida']);
}
