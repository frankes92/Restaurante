<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/ComprobanteElectronico.php";
requireLogin();

$comp = new ComprobanteElectronico();
$op = $_GET['op'] ?? '';

switch ($op) {

    case 'crearDesdeOrden':
        // Crea boleta/factura a partir de una orden cobrada
        $tipoDoc = limpiarCadena($_POST['tipo_documento'] ?? '03'); // 01 factura, 03 boleta
        if (!in_array($tipoDoc, ['01','03'], true)) {
            jsonResponse(['ok' => false, 'msg' => 'Tipo de documento no soportado']);
        }
        $permiso = $tipoDoc === '01' ? 'emitir_factura' : 'emitir_boleta';
        requirePermiso($permiso);

        $idorden = (int)($_POST['idorden'] ?? 0);
        $cliente = [
            'tipo_doc'  => limpiarCadena($_POST['cliente_tipo_doc']  ?? '1'),
            'num_doc'   => limpiarCadena($_POST['cliente_num_doc']   ?? '00000000'),
            'razon'     => limpiarCadena($_POST['cliente_razon']     ?? 'CLIENTE VARIOS'),
            'direccion' => limpiarCadena($_POST['cliente_direccion'] ?? ''),
            'email'     => limpiarCadena($_POST['cliente_email']     ?? ''),
            'idclifact' => null,
            'idusuario' => $_SESSION['idusuario'] ?? null,
        ];

        // Guardar/buscar cliente_facturacion
        if ($cliente['num_doc'] !== '' && $cliente['num_doc'] !== '00000000') {
            require_once __DIR__ . "/../modelos/ClienteFacturacion.php";
            $cf = new ClienteFacturacion();
            $cliente['idclifact'] = $cf->buscarOInsertar(
                $cliente['tipo_doc'], $cliente['num_doc'], $cliente['razon'],
                $cliente['direccion'], $cliente['email'], ''
            );
        }

        // Defensa backend: sin serie de numeración activa no se puede emitir.
        require_once __DIR__ . "/../modelos/Numeracion.php";
        if (!(new Numeracion())->existeSerieActiva($tipoDoc, 1)) {
            jsonResponse(['ok' => false, 'sin_serie' => true,
                'msg' => 'No existe serie de numeración para ' . ($tipoDoc === '01' ? 'factura' : 'boleta') . '. Créala en Configuración → Numeración.']);
        }

        $idcomp = $comp->crearDesdeOrden($idorden, $tipoDoc, $cliente);
        if (!$idcomp) {
            jsonResponse(['ok' => false, 'msg' => 'No se pudo crear el comprobante']);
        }

        // ENVIO AUTOMATICO A SUNAT (si la empresa lo tiene activado en Config).
        // Va protegido: si el envio falla, el comprobante igual queda creado y
        // en cola para reenviar manualmente; nunca rompe el flujo de cobro.
        $envioAuto = ['intentado' => false, 'ok' => false, 'estado' => null, 'mensaje' => null];
        try {
            require_once __DIR__ . "/../modelos/Empresa.php";
            $emp = (new Empresa())->mostrar(1) ?: [];
            if (!empty($emp['envio_sunat_automatico']) && (int)$emp['envio_sunat_automatico'] === 1) {
                $envioAuto['intentado'] = true;
                $res = $comp->enviarASunat($idcomp);
                $envioAuto['ok']      = !empty($res['ok']);
                $envioAuto['estado']  = $res['estado']  ?? null;
                $envioAuto['mensaje'] = $res['mensaje'] ?? null;
            }
        } catch (\Throwable $e) {
            error_log('[SUNAT-AUTO] orden ' . $idorden . ': ' . $e->getMessage());
        }

        jsonResponse(['ok' => true, 'idcomprobante' => $idcomp, 'envio_auto' => $envioAuto]);
        break;

    case 'mostrar':
        requirePermiso('comprobantes_sunat');
        jsonResponse($comp->mostrarCompleto((int)($_REQUEST['idcomprobante'] ?? 0)));
        break;

    case 'contarPendientes':
        // Devuelve conteos de cada estado reenviable
        requirePermiso('enviar_sunat');
        $row = dbFila(
            "SELECT
                SUM(CASE WHEN estado='pendiente' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado='error'     THEN 1 ELSE 0 END) AS errores,
                SUM(CASE WHEN estado='rechazado' THEN 1 ELSE 0 END) AS rechazados
             FROM comprobante_electronico"
        );
        jsonResponse([
            'pendientes' => (int)($row['pendientes'] ?? 0),
            'errores'    => (int)($row['errores']    ?? 0),
            'rechazados' => (int)($row['rechazados'] ?? 0),
        ]);
        break;

    case 'listarPendientes':
        // Devuelve IDs de comprobantes a reenviar (pendiente, error, opcional rechazado)
        requirePermiso('enviar_sunat');
        $estados = ['pendiente','error'];
        if (!empty($_REQUEST['incluir_rechazados']) && $_REQUEST['incluir_rechazados'] !== '0') {
            $estados[] = 'rechazado';
        }
        $placeholders = str_repeat('?,', count($estados) - 1) . '?';
        $rs = dbQuery(
            "SELECT idcomprobante, numero_completo, tipo_documento, cliente_num_doc, cliente_razon, total, estado
             FROM comprobante_electronico
             WHERE estado IN ($placeholders)
             ORDER BY idcomprobante ASC",
            str_repeat('s', count($estados)),
            $estados
        );
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'enviarSunat':
        requirePermiso('enviar_sunat');
        $id = (int)($_POST['idcomprobante'] ?? 0);
        $r = $comp->enviarASunat($id);
        jsonResponse($r);
        break;

    case 'consultarSunat':
        // Consulta a SUNAT el estado real del comprobante (getStatusCdr)
        requirePermiso('enviar_sunat');
        $id = (int)($_POST['idcomprobante'] ?? 0);
        $r  = $comp->consultarEnSunat($id);
        jsonResponse($r);
        break;

    case 'datatable':
        requirePermiso('comprobantes_sunat');
        $draw     = (int)($_REQUEST['draw']   ?? 1);
        $start    = (int)($_REQUEST['start']  ?? 0);
        $length   = (int)($_REQUEST['length'] ?? 10);
        $searchV  = limpiarCadena($_REQUEST['search']['value'] ?? '');
        $orderCol = (int)($_REQUEST['order'][0]['column'] ?? 0);
        $orderDir = limpiarCadena($_REQUEST['order'][0]['dir'] ?? 'desc');
        $filtros = [
            'estado'         => limpiarCadena($_REQUEST['f_estado'] ?? ''),
            'tipo_documento' => limpiarCadena($_REQUEST['f_tipo']   ?? ''),
            'desde'          => limpiarCadena($_REQUEST['f_desde']  ?? ''),
            'hasta'          => limpiarCadena($_REQUEST['f_hasta']  ?? ''),
        ];
        $rs = $comp->listarServerSide($start, $length, $searchV, $orderCol, $orderDir, $filtros);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $comp->contar('', $filtros, false),
            'recordsFiltered' => $comp->contar($searchV, $filtros, true),
            'data'            => $data,
        ]);
        exit;

    case 'descargarPdf':
        requirePermiso('comprobantes_sunat');
        $idc = (int)($_GET['idcomprobante'] ?? 0);
        $c = $comp->mostrarCompleto($idc);
        if (!$c) { http_response_code(404); exit('Comprobante no encontrado'); }
        // Cargar moneda de la empresa
        require_once __DIR__ . "/../modelos/Empresa.php";
        $emp = (new Empresa())->mostrar($c['idempresa']);
        $c['simbolo_moneda'] = $emp['simbolo_moneda'] ?? 'S/';
        $logo = !empty($emp['logo']) ? __DIR__ . '/../' . $emp['logo'] : null;
        // Para SVG no es soportado por FPDF; solo png/jpg
        if ($logo && pathinfo($logo, PATHINFO_EXTENSION) === 'svg') $logo = null;

        require_once __DIR__ . "/../modelos/PdfComprobanteSunat.php";
        $pdf = new PdfComprobanteSunat();
        $pdf->generar($c, $logo);
        $nombreArchivo = $c['empresa_ruc'] . '-' . $c['tipo_documento'] . '-' . $c['serie'] . '-' . (ltrim($c['numero'],'0') ?: '0') . '.pdf';
        if (!empty($_GET['descargar'])) {
            $pdf->outputDownload($nombreArchivo);
        } else {
            $pdf->output($nombreArchivo);
        }
        exit;

    case 'descargarXml':
        requirePermiso('comprobantes_sunat');
        $c = $comp->mostrar((int)($_GET['idcomprobante'] ?? 0));
        if (!$c || !$c['xml_ruta'] || !file_exists($c['xml_ruta'])) {
            http_response_code(404); exit('XML no encontrado');
        }
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . basename($c['xml_ruta']) . '"');
        readfile($c['xml_ruta']);
        exit;

    case 'descargarCdr':
        requirePermiso('comprobantes_sunat');
        $c = $comp->mostrar((int)($_GET['idcomprobante'] ?? 0));
        if (!$c || !$c['cdr_ruta'] || !file_exists($c['cdr_ruta'])) {
            http_response_code(404); exit('CDR no encontrado');
        }
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="R-' . basename($c['xml_ruta']) . '"');
        readfile($c['cdr_ruta']);
        exit;

    case 'verCdr':
        // Vista HTML legible del CDR (alternativa al XML crudo)
        requirePermiso('comprobantes_sunat');
        $c = $comp->mostrar((int)($_GET['idcomprobante'] ?? 0));
        if (!$c || !$c['cdr_ruta'] || !file_exists($c['cdr_ruta'])) {
            http_response_code(404); exit('CDR no encontrado');
        }
        $doc = new DOMDocument();
        @$doc->load($c['cdr_ruta']);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

        $get = function($xp) use ($xpath) {
            $n = $xpath->query($xp);
            return $n->length > 0 ? $n->item(0)->nodeValue : '';
        };
        $respCode = $get('//cac:Response/cbc:ResponseCode');
        $respDesc = $get('//cac:Response/cbc:Description');
        $refId    = $get('//cac:Response/cbc:ReferenceID');
        $issueDate = $get('//cbc:IssueDate');
        $cdrId    = $get('/*/cbc:ID');

        // Notas/Observaciones
        $notas = [];
        $rs = $xpath->query('//cbc:Note');
        foreach ($rs as $n) $notas[] = $n->nodeValue;
        // ResponseCode 0 = aceptado, otros = error
        $aceptado = ($respCode === '0');
        $colorBg = $aceptado ? '#d1fae5' : '#fee2e2';
        $colorTxt = $aceptado ? '#065f46' : '#991b1b';
        $iconClass = $aceptado ? 'fa-circle-check' : 'fa-circle-xmark';

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>CDR ' . h($refId) . '</title>';
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">';
        echo '<style>body{font-family:system-ui,sans-serif;background:#f3f4f6;margin:0;padding:30px;color:#1a1f36;}';
        echo '.card{max-width:680px;margin:0 auto;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.08);overflow:hidden;}';
        echo '.head{padding:24px;background:' . $colorBg . ';color:' . $colorTxt . ';text-align:center;}';
        echo '.head .icon{font-size:50px;margin-bottom:10px;}';
        echo '.head h1{font-size:22px;margin:0 0 4px 0;}';
        echo '.head .lead{font-size:14px;opacity:.85;}';
        echo '.body{padding:24px;}';
        echo '.row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:13px;}';
        echo '.row:last-child{border-bottom:0;}';
        echo '.row .lbl{color:#6b7280;font-weight:600;}';
        echo '.row .val{color:#1a1f36;font-weight:600;text-align:right;max-width:60%;word-break:break-word;}';
        echo '.code{font-family:monospace;background:#f9fafb;padding:8px 12px;border-radius:6px;font-size:11px;color:#4b5563;margin-top:6px;display:block;}';
        echo '.btns{padding:18px 24px;background:#f9fafb;display:flex;gap:8px;flex-wrap:wrap;border-top:1px solid #e5e7eb;}';
        echo '.btn{padding:10px 16px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #d1d5db;color:#1a1f36;background:#fff;display:inline-flex;align-items:center;gap:6px;cursor:pointer;}';
        echo '.btn.primary{background:#5b3df5;color:#fff;border-color:#5b3df5;}';
        echo '.btn:hover{border-color:#5b3df5;color:#5b3df5;}';
        echo '.btn.primary:hover{background:#4a2fe0;color:#fff;}';
        echo '</style></head><body>';
        echo '<div class="card">';
        echo '<div class="head"><div class="icon"><i class="fa-solid ' . $iconClass . '"></i></div>';
        echo '<h1>' . h($aceptado ? 'Comprobante aceptado por SUNAT' : 'Comprobante con observación / rechazo') . '</h1>';
        echo '<div class="lead">CDR — Constancia de Recepción SUNAT</div></div>';
        echo '<div class="body">';
        echo '<div class="row"><span class="lbl">Comprobante</span><span class="val">' . h($refId) . '</span></div>';
        echo '<div class="row"><span class="lbl">Código de respuesta</span><span class="val">' . h($respCode) . '</span></div>';
        echo '<div class="row"><span class="lbl">Mensaje</span><span class="val">' . h($respDesc) . '</span></div>';
        echo '<div class="row"><span class="lbl">Fecha de emisión CDR</span><span class="val">' . h($issueDate) . '</span></div>';
        echo '<div class="row"><span class="lbl">ID del CDR</span><span class="val" style="font-family:monospace;font-size:11px;">' . h($cdrId) . '</span></div>';
        if (!empty($notas)) {
            echo '<div style="margin-top:14px;padding:10px;background:#fef3c7;border-radius:8px;font-size:12px;color:#92400e;">';
            echo '<b>Observaciones de SUNAT:</b><ul style="margin:6px 0 0 18px;">';
            foreach ($notas as $nota) echo '<li>' . h($nota) . '</li>';
            echo '</ul></div>';
        }
        echo '</div>';
        echo '<div class="btns">';
        echo '<a class="btn primary" href="?op=descargarCdr&idcomprobante=' . (int)$_GET['idcomprobante'] . '"><i class="fa-solid fa-download"></i> Descargar XML del CDR</a>';
        echo '<a class="btn" href="?op=descargarXml&idcomprobante=' . (int)$_GET['idcomprobante'] . '"><i class="fa-solid fa-file-code"></i> XML del comprobante</a>';
        echo '<a class="btn" href="?op=descargarPdf&idcomprobante=' . (int)$_GET['idcomprobante'] . '" target="_blank"><i class="fa-solid fa-file-pdf"></i> PDF</a>';
        echo '<button class="btn" onclick="window.close()"><i class="fa-solid fa-xmark"></i> Cerrar</button>';
        echo '</div></div></body></html>';
        exit;

    default:
        jsonResponse(['error' => 'op no valida']);
}
?>
