<?php
require_once __DIR__ . "/../config/auth.php";
// Modo BRIDGE: si llega un token válido (el del bridge de impresión), se permite
// renderizar el comprobante SIN sesión, para que el Bridge lo "fotografíe" e
// imprima en la térmica. En ese modo: sin barra, sin auto-imprimir, forzado a 80mm.
$__bridgeTok = $_GET['token'] ?? '';
$__esBridge  = (defined('BRIDGE_TOKEN') && BRIDGE_TOKEN !== '' && is_string($__bridgeTok) && hash_equals(BRIDGE_TOKEN, $__bridgeTok));
if (!$__esBridge) requireLogin();
require_once __DIR__ . "/../modelos/Orden.php";
require_once __DIR__ . "/../modelos/Empresa.php";

$idorden = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idorden <= 0) { http_response_code(400); exit('ID inválido'); }

$ordenObj = new Orden();
$orden = $ordenObj->mostrarCompleta($idorden);
if (!$orden) { http_response_code(404); exit('Orden no encontrada'); }

// Si la orden tiene boleta/factura electronica, traer el numero fiscal y datos del cliente fiscal
$compFiscal = null;
if (in_array($orden['tipo_comprobante'], ['boleta','factura'], true)) {
    $compFiscal = ejecutarConsultaSimpleFila(
        "SELECT idcomprobante, tipo_documento, serie, numero, numero_completo,
                cliente_tipo_doc, cliente_num_doc, cliente_razon, cliente_direccion,
                cliente_email, total_letras, estado, xml_hash, fecha_emision,
                tipo_operacion, tipo_moneda
         FROM comprobante_electronico
         WHERE idorden = '" . (int)$idorden . "'
         ORDER BY idcomprobante DESC LIMIT 1"
    );
}

$emp     = (new Empresa())->mostrar(1) ?: [];
$empNom  = $emp['nombre_comercial'] ?? $emp['razon_social'] ?? 'PUERTO HABANA POS';
$empRaz  = $emp['razon_social']     ?? 'PUERTO HABANA POS';
$empRuc  = $emp['numero_ruc']       ?? '';
$empDir  = $emp['domicilio_fiscal'] ?? '';
$empUbi  = trim(($emp['distrito'] ?? '') . ', ' . ($emp['provincia'] ?? '') . ' - ' . ($emp['departamento'] ?? ''), ', -');
$empTel  = $emp['telefono']         ?? '';
$empMail = $emp['correo']           ?? '';
$empWeb  = $emp['web']              ?? '';
$empLogo = !empty($emp['logo']) ? '../' . $emp['logo'] : '';
$simbolo = $emp['simbolo_moneda']   ?? 'S/';
$codMoneda = $emp['codigo_moneda']  ?? 'PEN';
$tasaIgv = (float)($emp['tasa_igv'] ?? 0.18);
$pctIgv  = (int)round($tasaIgv * 100);
$slogan  = $emp['nombre_comercial'] && $emp['razon_social'] && $emp['nombre_comercial'] !== $emp['razon_social']
    ? $emp['razon_social']
    : 'Comprobante electrónico';

// Formato: parametro GET > config empresa > ticket
$formato = strtolower($_GET['formato'] ?? '');
if (!in_array($formato, ['ticket', 'a4'], true)) {
    $formato = $emp['formato_comprobante'] ?? 'ticket';
}
// En modo bridge siempre 80mm (térmica)
if ($__esBridge) $formato = 'ticket';
// Ancho en px para la captura del bridge (80mm≈576, 58mm≈384)
$__bw = (int)($_GET['w'] ?? 576);
if ($__bw < 240 || $__bw > 1200) $__bw = 576;

$user = currentUser();
$cajero = $user ? trim(($user['nombre'] ?? '') . ' ' . ($user['apellidos'] ?? '')) : '—';

$tipoLabels = [
    'ticket'     => 'TICKET DE VENTA',
    'nota_venta' => 'NOTA DE VENTA',
    'boleta'     => 'BOLETA ELECTRÓNICA',
    'factura'    => 'FACTURA ELECTRÓNICA',
];
$tipoLabel = $tipoLabels[$orden['tipo_comprobante']] ?? 'COMPROBANTE';

$metodos = [
    'efectivo'      => 'Efectivo',
    'tarjeta'       => 'Tarjeta',
    'yape'          => 'Yape / Plin',
    'transferencia' => 'Transferencia',
];
$metodoLabel = $metodos[$orden['metodo_pago']] ?? ucwords($orden['metodo_pago']);

$tipoServicios = ['dine_in' => 'En mesa', 'para_llevar' => 'Para llevar', 'delivery' => 'Delivery'];
$tipoServicio  = $tipoServicios[$orden['tipo']] ?? $orden['tipo'];

$metadata = json_decode($orden['pago_metadata'] ?? '', true) ?: [];

$money = function($v) use ($simbolo) { return $simbolo . ' ' . number_format((float)$v, 2); };

// Numero a mostrar: si hay comprobante fiscal, usar el numero fiscal; si no, el numero interno
$numeroDoc = $compFiscal ? $compFiscal['numero_completo'] : ('N° ' . $orden['numero']);

// Total en letras
$totalLetras = $compFiscal['total_letras'] ?? '';
if (!$totalLetras) {
    // Generar texto simple si no hay
    $totalLetras = strtoupper(numToLetras((float)$orden['total'])) . ' Y 00/100 SOLES';
}

// Fechas
$fechaEmision = $orden['fecha_pago'] ?: $orden['fecha'];
$fechaVenc    = date('d/m/Y', strtotime($fechaEmision));  // Para contado, vencimiento = emision
$tipoOperLabel = 'Venta Interna';

// Cadena para QR (formato SUNAT) — solo para boleta/factura electronica
$cadenaQR = '';
if ($compFiscal) {
    $cadenaQR = implode('|', [
        $empRuc,
        $compFiscal['tipo_documento'],
        $compFiscal['serie'],
        ltrim($compFiscal['numero'], '0') ?: '0',
        number_format((float)$orden['igv'], 2, '.', ''),
        number_format((float)$orden['total'], 2, '.', ''),
        date('d/m/Y', strtotime($compFiscal['fecha_emision'])),
        $compFiscal['cliente_tipo_doc'],
        $compFiscal['cliente_num_doc'],
        $compFiscal['xml_hash'] ?? '',
    ]);
}

// =====================================================================
// SI ES BOLETA/FACTURA EN FORMATO A4 → usar diseño profesional especial
// =====================================================================
$usaA4Pro = $compFiscal && $formato === 'a4';

function numToLetras($n) {
    $entero  = (int)floor($n);
    $decimal = (int)round(($n - $entero) * 100);
    $unidades = ['','UNO','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE','DIEZ',
                 'ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE','DIECIOCHO','DIECINUEVE'];
    $decenas  = ['','','VEINTI','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA'];
    $centenas = ['','CIENTO','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS',
                 'SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];
    $convertir3 = function($n) use ($unidades, $decenas, $centenas) {
        if ($n === 0) return '';
        if ($n === 100) return 'CIEN';
        $c = (int)floor($n / 100);
        $resto = $n % 100;
        $res = $centenas[$c];
        if ($resto > 0) {
            if ($resto < 20) $res .= ($res ? ' ' : '') . $unidades[$resto];
            else {
                $d = (int)floor($resto / 10);
                $u = $resto % 10;
                if ($d === 2 && $u > 0) $res .= ($res ? ' ' : '') . 'VEINTI' . strtolower($unidades[$u]);
                else { $res .= ($res ? ' ' : '') . $decenas[$d]; if ($u > 0) $res .= ' Y ' . $unidades[$u]; }
            }
        }
        return $res;
    };
    if ($entero < 1000) return $convertir3($entero) ?: 'CERO';
    if ($entero < 1000000) {
        $miles = (int)floor($entero / 1000);
        $resto = $entero % 1000;
        $res = ($miles === 1 ? 'MIL' : $convertir3($miles) . ' MIL');
        if ($resto > 0) $res .= ' ' . $convertir3($resto);
        return $res;
    }
    return (string)$entero;
}
?>
<?php if ($usaA4Pro): /* ============ DISEÑO A4 PROFESIONAL PARA BOLETA/FACTURA ============ */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Comprobante <?php echo h($numeroDoc); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
    --azul-primario: #1e3a8a;
    --azul-oscuro:   #1e40af;
    --azul-claro:    #dbeafe;
    --rojo-doc:      #dc2626;
    --gris-borde:    #d1d5db;
    --gris-suave:    #e5e7eb;
    --gris-text:     #6b7280;
    --gris-claro:    #f9fafb;
    --texto:         #1f2937;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', system-ui, sans-serif;
    background: #e5e7eb;
    padding: 24px;
    color: var(--texto);
}
.toolbar { max-width: 850px; margin: 0 auto 16px; display: flex; gap: 8px; flex-wrap: wrap; }
.toolbar button, .toolbar a.btn-link {
    flex: 1; padding: 10px 16px; border: 0; border-radius: 10px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    font-family: inherit; text-align: center; text-decoration: none;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    min-width: 110px; transition: all .2s;
}
.btn-print { background: var(--azul-primario); color: #fff; }
.btn-print:hover { background: var(--azul-oscuro); transform: translateY(-1px); }
.btn-close, .btn-link { background: #fff; color: var(--azul-primario); border: 1px solid var(--gris-borde); }
.btn-close:hover, .btn-link:hover { border-color: var(--azul-primario); }

.doc {
    width: 850px;
    margin: 0 auto;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 12px 40px rgba(30, 58, 138, 0.15);
    overflow: hidden;
    color: var(--texto);
    font-size: 13px;
}
.doc-content { padding: 18px 26px; }

/* ============ HEADER ============ */
.head-grid { display: grid; grid-template-columns: 1fr 360px; gap: 16px; align-items: start; }
.brand { display: flex; gap: 12px; align-items: center; margin-bottom: 8px; }
.brand .logo {
    width: 92px; height: 92px;
    background: var(--azul-primario);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 42px; font-weight: 800;
    box-shadow: 0 6px 16px rgba(30, 58, 138, 0.28);
    overflow: hidden;
    flex-shrink: 0;
}
.brand .logo img { width: 100%; height: 100%; object-fit: cover; padding: 0; background: #fff; border-radius: 50%; display: block; }
.brand .name { line-height: 1.1; }
.brand .name h1 { font-size: 22px; font-weight: 800; color: var(--azul-primario); letter-spacing: -.5px; }
.brand .name .slogan { font-size: 12px; color: var(--gris-text); margin-top: 4px; font-weight: 500; }

.contact { font-size: 12px; line-height: 1.4; color: var(--texto); }
.contact .row { display: flex; align-items: flex-start; gap: 8px; }
.contact .row svg { width: 14px; height: 14px; color: var(--azul-primario); flex-shrink: 0; margin-top: 3px; }
.contact .row span { flex: 1; }
.contact .ruc-line { font-weight: 800; color: var(--azul-primario); font-size: 14px; margin-top: 4px; }

/* Banner derecho del documento */
.doc-banner {
    border: 2px solid var(--azul-primario);
    border-radius: 12px;
    padding: 8px 14px;
    text-align: center;
}
.doc-banner .tipo {
    font-size: 19px;
    font-weight: 800;
    color: var(--azul-primario);
    letter-spacing: -.5px;
    line-height: 1.1;
    margin-bottom: 5px;
}
.doc-banner .ruc-box {
    background: var(--azul-primario);
    color: #fff;
    padding: 4px 12px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .5px;
    margin-bottom: 5px;
}
.doc-banner .num {
    color: var(--rojo-doc);
    font-size: 20px;
    font-weight: 900;
    letter-spacing: -.5px;
    margin-bottom: 6px;
    line-height: 1.1;
}
.doc-banner .meta { text-align: left; font-size: 11.5px; line-height: 1.25; }
.doc-banner .meta .row { display: grid; grid-template-columns: 130px 14px 1fr; padding: 1px 0; }
.doc-banner .meta .lbl { font-weight: 800; color: var(--texto); text-transform: uppercase; font-size: 10px; letter-spacing: .3px; align-self: center; }
.doc-banner .meta .sep { font-weight: 800; }
.doc-banner .meta .val { color: var(--texto); font-weight: 500; }

/* ============ CARDS EMISOR / CLIENTE ============ */
.cards { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 8px; }
.party-card {
    background: var(--gris-claro);
    border-radius: 12px;
    padding: 12px 14px;
    border: 1px solid var(--gris-suave);
}
.party-card .head-card {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--azul-primario);
    color: #fff;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .4px;
    margin-bottom: 8px;
}
.party-card .head-card svg { width: 12px; height: 12px; }
.party-card h3 { font-size: 15px; font-weight: 800; color: var(--azul-primario); margin-bottom: 6px; line-height: 1.2; }
.party-card .row { display: grid; grid-template-columns: 110px 14px 1fr; padding: 1px 0; font-size: 12px; }
.party-card .row .lbl { font-weight: 700; color: var(--texto); }
.party-card .row .sep { font-weight: 700; color: var(--texto); }
.party-card .row .val { color: var(--texto); font-weight: 500; }

/* ============ TABLA ITEMS ============ */
.items-tbl { width: 100%; border-collapse: collapse; margin-top: 8px; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
.items-tbl thead th {
    background: var(--azul-primario);
    color: #fff;
    padding: 7px 10px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .4px;
    text-transform: uppercase;
    text-align: left;
}
.items-tbl thead th.r { text-align: right; }
.items-tbl thead th.c { text-align: center; }
.items-tbl tbody td {
    padding: 6px 10px;
    border-bottom: 1px solid var(--gris-suave);
    font-size: 12.5px;
    color: var(--texto);
    background: #fff;
}
.items-tbl tbody tr:nth-child(even) td { background: #fafbfc; }
.items-tbl tbody tr:last-child td { border-bottom: 0; }
.items-tbl tbody td.r { text-align: right; font-weight: 600; }
.items-tbl tbody td.c { text-align: center; }
.items-tbl tbody td .nota { font-size: 11px; color: var(--gris-text); font-style: italic; margin-top: 2px; }

/* ============ TOTALES Y SON ============ */
.son-tot-grid { display: grid; grid-template-columns: 1fr 360px; gap: 12px; margin-top: 10px; align-items: start; }
.son-block {
    background: var(--gris-claro);
    border-radius: 12px;
    padding: 9px 14px;
    border: 1px solid var(--gris-suave);
    margin-bottom: 8px;
}
.son-block .lbl { font-size: 11px; font-weight: 800; color: var(--azul-primario); text-transform: uppercase; letter-spacing: .4px; }
.son-block .val { font-size: 13px; font-weight: 700; color: var(--texto); margin-top: 3px; line-height: 1.3; }

.tot-table { width: 100%; border-collapse: collapse; }
.tot-table tr { background: var(--gris-claro); }
.tot-table td {
    padding: 7px 14px;
    border-bottom: 1px solid #fff;
    font-size: 13px;
    font-weight: 700;
}
.tot-table td.lbl { color: var(--texto); text-transform: uppercase; font-size: 11.5px; letter-spacing: .3px; }
.tot-table td.val { text-align: right; color: var(--texto); }
.tot-table .total-row td {
    background: var(--azul-primario);
    color: #fff;
    font-size: 15px;
    font-weight: 800;
    padding: 9px 14px;
}
.tot-table .total-row td.lbl { color: #fff; text-transform: uppercase; font-size: 13px; }
.tot-table .total-row td.val { color: #fff; font-size: 18px; font-weight: 900; }
.tot-table tr:first-child td { border-radius: 10px 10px 0 0; }
.tot-table tr:last-child td { border-radius: 0 0 10px 10px; }

/* ============ QR + CDR ============ */
.qr-grid { display: grid; grid-template-columns: 170px 1fr; gap: 16px; margin-top: 10px; padding: 12px 14px; border: 1px solid var(--gris-suave); border-radius: 12px; align-items: start; }
.qr-grid .qr-side { text-align: center; }
.qr-grid .qr-side canvas { width: 130px; height: 130px; border: 1px solid var(--gris-borde); border-radius: 8px; padding: 4px; background: #fff; }
.qr-grid .qr-side .qr-titulo { font-size: 13px; font-weight: 700; color: var(--azul-primario); margin-top: 5px; }
.qr-grid .qr-side .qr-help { font-size: 10.5px; color: var(--gris-text); margin-top: 2px; line-height: 1.3; }
.qr-grid .info b { font-size: 13px; font-weight: 800; color: var(--azul-primario); display: block; margin-bottom: 3px; }
.qr-grid .info .lead { font-size: 12px; color: var(--texto); line-height: 1.35; margin-bottom: 8px; }
.qr-grid .info .sub-lbl { font-size: 11px; font-weight: 800; color: var(--texto); text-transform: uppercase; letter-spacing: .3px; margin-top: 6px; margin-bottom: 3px; }
.qr-grid .info .hash {
    font-family: 'Courier New', monospace;
    font-size: 11px;
    color: var(--texto);
    background: var(--gris-claro);
    padding: 8px 10px;
    border-radius: 6px;
    word-break: break-all;
    line-height: 1.5;
    border: 1px solid var(--gris-suave);
}

/* ============ FOOTER ============ */
.gracias-line {
    text-align: center;
    padding: 10px 0;
    font-size: 16px;
    font-weight: 700;
    font-style: italic;
    color: var(--azul-primario);
}
.foot-bar {
    background: var(--azul-primario);
    color: #fff;
    padding: 9px 26px;
    display: grid;
    grid-template-columns: 1.4fr 1fr 1.2fr 1fr;
    gap: 16px;
    font-size: 12px;
}
.foot-bar .item { display: flex; gap: 8px; align-items: center; }
.foot-bar .item svg { width: 22px; height: 22px; padding: 4px; background: rgba(255,255,255,.12); border-radius: 50%; flex-shrink: 0; }
.foot-bar .item .lbl { font-size: 10px; opacity: .8; line-height: 1.3; }
.foot-bar .item .val { font-size: 11.5px; font-weight: 600; line-height: 1.3; }
.legal {
    text-align: center;
    padding: 8px 18px;
    background: var(--gris-claro);
    color: var(--gris-text);
    font-size: 11px;
    line-height: 1.5;
}
.amazonia-note {
    text-align: center;
    padding: 7px 18px;
    background: #fff;
    color: var(--azul-primario);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .3px;
    line-height: 1.3;
    text-transform: uppercase;
    border-top: 1px dashed var(--gris-borde);
    border-bottom: 1px dashed var(--gris-borde);
}

/* ============ IMPRESIÓN ============ */
@media print {
    body { background: #fff; padding: 0; font-size: 10.5px; }
    .toolbar { display: none; }
    .doc {
        width: 100%;
        box-shadow: none;
        border-radius: 0;
        page-break-inside: avoid;
    }
    /* Forzar que TODO el comprobante quepa en una sola hoja A4 */
    .doc-content { padding: 12px 18px !important; }
    .head-grid { gap: 12px !important; }
    .brand { margin-bottom: 4px !important; }
    .contact { line-height: 1.3 !important; }
    .doc-banner { padding: 6px 12px !important; }
    .doc-banner .tipo { font-size: 17px !important; margin-bottom: 4px !important; }
    .doc-banner .ruc-box { padding: 3px 10px !important; font-size: 12px !important; margin-bottom: 4px !important; }
    .doc-banner .num { font-size: 18px !important; margin-bottom: 5px !important; }
    .doc-banner .meta { line-height: 1.2 !important; font-size: 10.5px !important; }
    .doc-banner .meta .row { padding: 0 !important; }
    .cards { gap: 8px !important; margin-top: 6px !important; }
    .party-card { padding: 8px 12px !important; }
    .party-card .head-card { padding: 3px 10px !important; margin-bottom: 5px !important; }
    .party-card h3 { margin-bottom: 4px !important; font-size: 13px !important; }
    .party-card .row { padding: 0 !important; font-size: 11px !important; }
    .items-tbl { margin-top: 6px !important; }
    .items-tbl thead th { padding: 5px 8px !important; }
    .items-tbl tbody td { padding: 4px 8px !important; }
    .son-tot-grid { gap: 8px !important; margin-top: 6px !important; }
    .son-block { padding: 6px 12px !important; margin-bottom: 5px !important; }
    .tot-table td { padding: 5px 12px !important; }
    .tot-table .total-row td { padding: 6px 12px !important; }
    .qr-grid { padding: 8px 12px !important; gap: 12px !important; margin-top: 6px !important; }
    .qr-grid .qr-side canvas { width: 105px !important; height: 105px !important; }
    .qr-grid .info .lead { margin-bottom: 5px !important; line-height: 1.25 !important; }
    .qr-grid .info .sub-lbl { margin-top: 4px !important; margin-bottom: 2px !important; }
    .gracias-line { padding: 6px 0 !important; font-size: 13px !important; }
    .foot-bar { padding: 6px 18px !important; }
    .foot-bar .item .lbl { font-size: 9px !important; line-height: 1.2 !important; }
    .foot-bar .item .val { font-size: 10.5px !important; line-height: 1.2 !important; }
    .amazonia-note { padding: 5px 14px !important; font-size: 10.5px !important; }
    .legal { padding: 5px 14px !important; font-size: 10px !important; line-height: 1.3 !important; }
    @page { margin: 6mm; size: A4 portrait; }
}
</style>
</head>
<body>

<div class="toolbar">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
    <a class="btn-link" href="?id=<?php echo $idorden; ?>&formato=ticket">Cambiar a Ticket</a>
    <button class="btn-close" onclick="window.close()">✕ Cerrar</button>
</div>

<div class="doc">
    <div class="doc-content">

    <!-- HEADER -->
    <div class="head-grid">
        <div>
            <div class="brand">
                <div class="logo">
                    <?php if ($empLogo): ?>
                        <img src="<?php echo h($empLogo); ?>" alt="logo">
                    <?php else: ?>
                        <?php echo h(mb_substr($empNom, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="name">
                    <h1><?php echo h(strtoupper($empNom)); ?></h1>
                    <?php if ($empRaz !== $empNom): ?>
                        <div class="slogan"><?php echo h($empRaz); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="contact">
                <?php if ($empDir): ?>
                <div class="row">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8 2 5 5 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-4-3-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z"/></svg>
                    <span><?php echo h($empDir); ?><?php if ($empUbi && trim($empUbi,', -')) echo '<br>' . h($empUbi); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($empTel): ?>
                <div class="row">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1A17 17 0 013 4c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                    <span><?php echo h($empTel); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($empMail): ?>
                <div class="row">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    <span><?php echo h($empMail); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($empWeb): ?>
                <div class="row">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95a15.65 15.65 0 00-1.38-3.56A8.03 8.03 0 0118.92 8zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2 0 .68.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.99 7.99 0 015.08 16zm2.95-8H5.08a7.99 7.99 0 014.33-3.56A15.65 15.65 0 008.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2 0-.68.07-1.35.16-2h4.68c.09.65.16 1.32.16 2 0 .68-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 01-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2 0-.68-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>
                    <span><?php echo h($empWeb); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($empRuc): ?>
                <div class="ruc-line">RUC: <?php echo h($empRuc); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Banner del documento (derecha) -->
        <div class="doc-banner">
            <div class="tipo"><?php echo h($tipoLabel); ?></div>
            <div class="ruc-box">RUC: <?php echo h($empRuc); ?></div>
            <div class="num"><?php echo h($numeroDoc); ?></div>
            <div class="meta">
                <?php $esCredito = stripos(trim((string)$metodoLabel), 'credito') !== false || stripos(trim((string)$metodoLabel), 'crédito') !== false; ?>
                <div class="row"><span class="lbl">Fecha de emisión</span><span class="sep">:</span><span class="val"><?php echo date('d/m/Y', strtotime($fechaEmision)); ?></span></div>
                <div class="row"><span class="lbl">Hora de emisión</span><span class="sep">:</span><span class="val"><?php echo date('H:i:s', strtotime($fechaEmision)); ?></span></div>
                <?php if ($esCredito): ?>
                <div class="row"><span class="lbl">Fecha de vencimiento</span><span class="sep">:</span><span class="val"><?php echo $fechaVenc; ?></span></div>
                <?php endif; ?>
                <div class="row"><span class="lbl">Moneda</span><span class="sep">:</span><span class="val"><?php echo h($codMoneda); ?> - <?php echo $codMoneda === 'PEN' ? 'Soles' : ($codMoneda === 'USD' ? 'Dólares' : $codMoneda); ?></span></div>
                <div class="row"><span class="lbl">Tipo de operación</span><span class="sep">:</span><span class="val"><?php echo h($tipoOperLabel); ?></span></div>
                <div class="row"><span class="lbl">Forma de pago</span><span class="sep">:</span><span class="val"><?php echo h($metodoLabel); ?></span></div>
            </div>
        </div>
    </div>

    <!-- DATOS EMISOR + CLIENTE -->
    <div class="cards">
        <div class="party-card">
            <div class="head-card">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7v3h20V7L12 2zM4 12v6h2v-6H4zm5 0v6h2v-6H9zm4 0v6h2v-6h-2zm5 0v6h2v-6h-2zM2 20h20v2H2v-2z"/></svg>
                DATOS DEL EMISOR
            </div>
            <h3><?php echo h(strtoupper($empNom)); ?></h3>
            <div class="row"><span class="lbl">RUC</span><span class="sep">:</span><span class="val"><?php echo h($empRuc); ?></span></div>
            <div class="row"><span class="lbl">Dirección</span><span class="sep">:</span><span class="val"><?php echo h($empDir); ?><?php if ($empUbi && trim($empUbi,', -')) echo '<br>' . h($empUbi); ?></span></div>
            <?php if (!empty($emp['departamento'])): ?>
                <div class="row"><span class="lbl">Departamento</span><span class="sep">:</span><span class="val"><?php echo h($emp['departamento']); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($emp['provincia'])): ?>
                <div class="row"><span class="lbl">Provincia</span><span class="sep">:</span><span class="val"><?php echo h($emp['provincia']); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($emp['distrito'])): ?>
                <div class="row"><span class="lbl">Distrito</span><span class="sep">:</span><span class="val"><?php echo h($emp['distrito']); ?></span></div>
            <?php endif; ?>
        </div>

        <div class="party-card">
            <div class="head-card">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                DATOS DEL CLIENTE
            </div>
            <h3><?php echo h(strtoupper($compFiscal['cliente_razon'])); ?></h3>
            <div class="row"><span class="lbl"><?php echo $compFiscal['cliente_tipo_doc'] === '6' ? 'RUC' : 'DNI'; ?></span><span class="sep">:</span><span class="val"><?php echo h($compFiscal['cliente_num_doc']); ?></span></div>
            <?php if (!empty($compFiscal['cliente_direccion'])): ?>
                <div class="row"><span class="lbl">Dirección</span><span class="sep">:</span><span class="val"><?php echo h($compFiscal['cliente_direccion']); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($compFiscal['cliente_email'])): ?>
                <div class="row"><span class="lbl">Email</span><span class="sep">:</span><span class="val"><?php echo h($compFiscal['cliente_email']); ?></span></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TABLA ITEMS -->
    <table class="items-tbl">
        <thead>
            <tr>
                <th style="width:90px;">Código</th>
                <th>Descripción</th>
                <th class="c" style="width:70px;">Cant.</th>
                <th class="c" style="width:80px;">Unidad</th>
                <th class="r" style="width:100px;">V. Unitario</th>
                <th class="c" style="width:80px;">IGV (%)</th>
                <th class="r" style="width:100px;">V. Venta</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $tasaIgvComp = (float)($compFiscal['tasa_igv'] ?? $tasaIgv);
            $factorIgv = 1 + $tasaIgvComp;
            foreach ($orden['items'] as $idx => $i):
                $esCortesia = (int)($i['cortesia'] ?? 0) === 1;
                $cantidad = (float)$i['cantidad'];
                $precioConIgv = (float)$i['precio'];
                $precioUnitSinIgv = $esCortesia ? 0 : round($precioConIgv / $factorIgv, 2);
                $valorVenta = $esCortesia ? 0 : round($cantidad * $precioUnitSinIgv, 2);
            ?>
                <tr>
                    <td>P<?php echo str_pad($idx + 1, 3, '0', STR_PAD_LEFT); ?></td>
                    <td>
                        <?php echo h($i['nombre']); ?>
                        <?php if ($esCortesia): ?>
                            <span style="display:inline-block;background:#dcfce7;color:#15803d;font-size:10px;font-weight:700;padding:1px 7px;border-radius:6px;margin-left:6px;">CORTESÍA</span>
                        <?php endif; ?>
                        <?php if (!empty($i['nota'])): ?>
                            <div class="nota">└ <?php echo h($i['nota']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="c"><?php echo number_format($cantidad, 2); ?></td>
                    <td class="c">NIU</td>
                    <td class="r"><?php echo number_format($precioUnitSinIgv, 2); ?></td>
                    <td class="c"><?php echo $esCortesia ? '—' : $pctIgv . '%'; ?></td>
                    <td class="r"><?php echo number_format($valorVenta, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- SON + TOTALES -->
    <div class="son-tot-grid">
        <div>
            <div class="son-block">
                <div class="lbl">SON:</div>
                <div class="val"><?php echo h($totalLetras); ?></div>
            </div>
            <div class="son-block">
                <div class="lbl">OBSERVACIONES</div>
                <div class="val">
                    <?php
                    if (!empty($orden['observacion'])) echo h($orden['observacion']);
                    elseif (!empty($orden['pago_referencia'])) echo 'Ref. pago: ' . h($orden['pago_referencia']);
                    else echo 'Gracias por su preferencia.';
                    ?>
                </div>
            </div>
        </div>

        <table class="tot-table">
            <?php if ($tasaIgv > 0 && (float)$orden['subtotal'] > 0): ?>
            <tr>
                <td class="lbl">Op. Gravadas</td>
                <td class="val"><?php echo $money($orden['subtotal']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($tasaIgv > 0 && (float)$orden['igv'] > 0): ?>
            <tr>
                <td class="lbl">IGV (<?php echo $pctIgv; ?>%)</td>
                <td class="val"><?php echo $money($orden['igv']); ?></td>
            </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td class="lbl">Importe Total</td>
                <td class="val"><?php echo $money($orden['total']); ?></td>
            </tr>
        </table>
    </div>

    <!-- QR + INFORMACIÓN SUNAT -->
    <div class="qr-grid">
        <div class="qr-side">
            <canvas id="qr-canvas-pro" width="320" height="320"></canvas>
            <div class="qr-titulo">Código QR</div>
            <div class="qr-help">
                Escanee este código para<br>
                verificar la validez de la<br>
                <?php echo $orden['tipo_comprobante'] === 'factura' ? 'factura' : 'boleta'; ?> en SUNAT.
            </div>
        </div>
        <div class="info">
            <b>Documento validado por la SUNAT</b>
            <div class="lead">
                Representación impresa de la <?php echo strtolower($tipoLabel); ?>.<br>
                Puede consultar su comprobante en:<br>
                <span style="color:var(--azul-primario);font-weight:600;">https://www.sunat.gob.pe</span>
            </div>
            <?php if (!empty($compFiscal['xml_hash'])): ?>
                <div class="sub-lbl">Código Hash:</div>
                <div class="hash"><?php echo h($compFiscal['xml_hash']); ?></div>
            <?php endif; ?>
            <?php if (!empty($cadenaQR)): ?>
                <div class="sub-lbl">CUFE:</div>
                <div class="hash"><?php echo h($cadenaQR); ?></div>
            <?php endif; ?>
        </div>
    </div>

    </div><!-- /doc-content -->

    <!-- GRACIAS POR CONFIAR -->
    <div class="gracias-line">Gracias por confiar en nosotros</div>

    <!-- BARRA INFERIOR AZUL -->
    <div class="foot-bar">
        <div class="item">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/></svg>
            <div>
                <div class="lbl">Emitido a través de:</div>
                <div class="val">Sistema de Emisión Electrónica (SEE)</div>
            </div>
        </div>
        <?php if ($empTel): ?>
        <div class="item">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1A17 17 0 013 4c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
            <div><div class="val"><?php echo h($empTel); ?></div></div>
        </div>
        <?php endif; ?>
        <?php if ($empMail): ?>
        <div class="item">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            <div><div class="val"><?php echo h($empMail); ?></div></div>
        </div>
        <?php endif; ?>
        <?php if ($empWeb): ?>
        <div class="item">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2z"/></svg>
            <div><div class="val"><?php echo h($empWeb); ?></div></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="amazonia-note">
        BIENES TRANSFERIDOS EN LA AMAZONIA PARA SER CONSUMIDOS EN LA MISMA
    </div>

    <div class="legal">
        Resolución de Superintendencia N° 300-2014/SUNAT y modificatorias<br>
        Autorizado mediante Resolución de Intendencia N° 0340056789012
    </div>

</div>

<script>
// QR client-side
(function(){
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js';
    script.onload = function() {
        var canvas = document.getElementById('qr-canvas-pro');
        if (!canvas) return;
        var qr = qrcode(0, 'L');
        qr.addData(<?php echo json_encode($cadenaQR); ?>);
        qr.make();
        var ctx = canvas.getContext('2d');
        var modules = qr.getModuleCount();
        var size = 320;
        var cell = size / modules;
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, size, size);
        ctx.fillStyle = '#1e3a8a';
        for (var r = 0; r < modules; r++) {
            for (var c = 0; c < modules; c++) {
                if (qr.isDark(r, c)) ctx.fillRect(c * cell, r * cell, cell, cell);
            }
        }
    };
    document.head.appendChild(script);
})();
<?php if (!$__esBridge): ?>
window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 700); });
<?php endif; ?>
</script>
</body>
</html>
<?php exit; /* termina aqui — no renderiza el diseño anterior */ ?>
<?php endif; /* fin diseño A4 profesional */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Comprobante <?php echo h($numeroDoc); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;500;600;700&family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
<style>
:root {
    --azul-oscuro: #1a2748;
    --azul-medio:  #2c3e60;
    --gris-borde:  #cbd2db;
    --gris-text:   #5c6473;
    --gris-claro:  #f5f6f8;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Roboto Condensed', 'Arial Narrow', 'Liberation Sans Narrow', Arial, sans-serif;
    background: #e5e7eb;
    padding: 20px;
    color: #1a2748;
    letter-spacing: -0.1px;
}
.toolbar {
    max-width: <?php echo $formato === 'a4' ? '780px' : '380px'; ?>;
    margin: 0 auto 14px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.toolbar button, .toolbar a.btn-link {
    flex: 1;
    padding: 10px 14px;
    border: 0;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    min-width: 110px;
    transition: all .2s;
}
.btn-print { background: var(--azul-oscuro); color: #fff; }
.btn-print:hover { background: #2c3e60; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(26,39,72,.3); }
.btn-close, .btn-link { background: #fff; color: var(--azul-oscuro); border: 1px solid var(--gris-borde); }
.btn-close:hover, .btn-link:hover { border-color: var(--azul-oscuro); }

/* ============ DOCUMENTO BASE ============ */
.doc {
    background: #fff;
    margin: 0 auto;
    color: var(--azul-oscuro);
    font-size: 11.5px;
    line-height: 1.25;
    position: relative;
}

/* Bordes "dentados" tipo recibo (decorativos, ocultos en impresión) */
.doc::before, .doc::after {
    content: "";
    position: absolute;
    left: 0; right: 0;
    height: 12px;
    background:
        linear-gradient(45deg, transparent 33%, #fff 33%, #fff 66%, transparent 66%) 0 0/14px 14px,
        linear-gradient(-45deg, transparent 33%, #fff 33%, #fff 66%, transparent 66%) 0 0/14px 14px;
    background-color: #e5e7eb;
}
.doc::before { top: -10px; }
.doc::after { bottom: -10px; transform: scaleY(-1); }

/* ----- TICKET (~80mm térmica o tirilla) ----- */
.doc.ticket {
    width: 380px;
    padding: 16px 18px;
    box-shadow: 0 8px 30px rgba(26,39,72,.12);
    /* Tipografia: Courier Prime (web font) -> Courier New (sistema) -> monospace.
       Peso 700 + negro absoluto para impresion termica gruesa y nitida. */
    font-family: 'Courier Prime', 'Courier New', 'Courier', monospace;
    font-size: 11.5px;
    font-weight: 700;
    line-height: 1.3;
    letter-spacing: 0;
    /* Variables locales: convertir azul oscuro a negro 100% solo en el ticket */
    --azul-oscuro: #000000;
    --azul-medio:  #000000;
    --gris-text:   #000000;
    color: #000;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.doc.ticket * {
    letter-spacing: 0 !important;
    font-family: inherit;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
/* Resaltar elementos clave con peso extra para impresion termica */
.doc.ticket .tipo,
.doc.ticket .numero,
.doc.ticket .total-final .lbl,
.doc.ticket .total-final .val,
.doc.ticket .items thead th { font-weight: 800 !important; }

/* ============ OVERRIDES SOLO PARA TICKET (sin afectar A4) ============ */
/* Encabezado vertical: LOGO GRANDE y CENTRADO arriba, datos debajo */
.doc.ticket .head {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 6px;
    margin-bottom: 6px;
}
.doc.ticket .head .logo-box {
    width: 150px;
    height: 150px;
    margin: 2px auto 4px;
    background: transparent !important;
    border: 0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
}
.doc.ticket .head .logo-box img {
    width: 100% !important;
    height: 100% !important;
    border-radius: 0 !important;
    object-fit: contain !important;
    background: transparent !important;
    image-rendering: -webkit-optimize-contrast;
}
.doc.ticket .head .logo-default { font-size: 70px; }
.doc.ticket .head .emp-data { width: 100%; text-align: center; }
.doc.ticket .head .emp-data h1 {
    font-size: 17px;
    margin-bottom: 2px;
    line-height: 1.1;
    text-transform: uppercase;
}
.doc.ticket .head .emp-data .slogan { font-size: 10px; margin-bottom: 3px; }
.doc.ticket .head .emp-data .ruc {
    font-size: 12px;
    display: inline-block;
    border: 1.5px solid #000;
    border-radius: 4px;
    padding: 1px 12px;
    margin: 2px 0 3px;
}
.doc.ticket .head .emp-data .addr { font-size: 10px; line-height: 1.25; }

.doc.ticket .divider { margin: 4px 0; }
.doc.ticket .tipo-block { margin: 1px 0 3px; }
.doc.ticket .tipo-block .tipo { font-size: 15px; margin-bottom: 3px; }
.doc.ticket .tipo-block .numero { padding: 3px 18px; font-size: 14px; }

.doc.ticket .meta { font-size: 10.5px; }
.doc.ticket .meta .row { padding: 0; }
.doc.ticket .meta .row .lbl { font-size: 9.5px; }

.doc.ticket .cliente { margin-top: 1px; }
.doc.ticket .cliente h3 { font-size: 10.5px; margin-bottom: 2px; }
.doc.ticket .cliente .row { padding: 0; font-size: 10.5px; }

.doc.ticket .items { margin-top: 1px; }
.doc.ticket .items table { font-size: 10.5px; }
.doc.ticket .items thead th { padding: 3px 4px; font-size: 9.5px; }
.doc.ticket .items tbody td { padding: 2px 4px; font-size: 10.5px; }

.doc.ticket .totals { margin-top: 3px; }
.doc.ticket .totals .row { padding: 1px 0; font-size: 10.5px; }
.doc.ticket .totals .total-final { padding: 5px 10px; margin-top: 2px; }
.doc.ticket .totals .total-final .lbl,
.doc.ticket .totals .total-final .val { font-size: 12px; }

.doc.ticket .son { margin-top: 3px; font-size: 10.5px; }
.doc.ticket .son .texto { margin-top: 0; line-height: 1.15; }
.doc.ticket .obs { margin-top: 3px; font-size: 10.5px; }
.doc.ticket .obs b { font-size: 10px; }
.doc.ticket .obs .texto { margin-top: 0; line-height: 1.15; }

.doc.ticket .qr-section { gap: 10px; margin-top: 4px; }
.doc.ticket .qr-section .qr-box img,
.doc.ticket .qr-section .qr-box canvas { width: 90px; height: 90px; }
.doc.ticket .qr-section .qr-box .qr-lbl { font-size: 10px; margin-top: 3px; }
.doc.ticket .qr-section .qr-box .qr-help { font-size: 8.5px; margin-top: 1px; line-height: 1.15; }
.doc.ticket .qr-section .info { font-size: 10px; padding-left: 5px; }
.doc.ticket .qr-section .info b { font-size: 10px; margin-bottom: 1px; }
.doc.ticket .qr-section .info .lead { font-size: 9.5px; margin-bottom: 3px; line-height: 1.2; }
.doc.ticket .qr-section .info .hash { font-size: 8px; padding: 4px 6px; line-height: 1.2; margin-top: 1px; }

.doc.ticket .foot { margin-top: 5px; }
.doc.ticket .foot .gracias { font-size: 12px; }
.doc.ticket .foot .web { font-size: 10px; margin-top: 1px; }
.doc.ticket .foot .amazonia-note { font-size: 10px; margin-top: 5px; line-height: 1.2; }

@media print {
    .doc.ticket {
        padding: 6mm 4mm !important;
        font-size: 10px !important;
        line-height: 1.15 !important;
        page-break-inside: avoid;
    }
    .doc.ticket .head { flex-direction: column !important; align-items: center !important; text-align: center !important; gap: 4px !important; margin-bottom: 4px !important; }
    .doc.ticket .head .logo-box { width: 130px !important; height: 130px !important; margin: 0 auto 3px !important; background: transparent !important; border: 0 !important; border-radius: 0 !important; }
    .doc.ticket .head .logo-box img { width: 100% !important; height: 100% !important; border-radius: 0 !important; object-fit: contain !important; }
    .doc.ticket .head .emp-data { width: 100% !important; text-align: center !important; }
    .doc.ticket .head .emp-data h1 { font-size: 13px !important; }
    .doc.ticket .head .emp-data .slogan { font-size: 9px !important; margin-bottom: 1px !important; }
    .doc.ticket .head .emp-data .ruc { font-size: 11px !important; }
    .doc.ticket .head .emp-data .addr { font-size: 9px !important; line-height: 1.15 !important; }
    .doc.ticket .divider { margin: 3px 0 !important; }
    .doc.ticket .tipo-block { margin: 0 0 2px !important; }
    .doc.ticket .tipo-block .tipo { font-size: 13px !important; margin-bottom: 2px !important; }
    .doc.ticket .tipo-block .numero { padding: 2px 14px !important; font-size: 12px !important; }
    .doc.ticket .meta { font-size: 9.5px !important; }
    .doc.ticket .meta .row .lbl { font-size: 9px !important; }
    .doc.ticket .cliente h3 { font-size: 10px !important; margin-bottom: 1px !important; }
    .doc.ticket .cliente .row { font-size: 9.5px !important; }
    .doc.ticket .items thead th { padding: 2px 4px !important; font-size: 9px !important; }
    .doc.ticket .items tbody td { padding: 2px 4px !important; font-size: 9.5px !important; }
    .doc.ticket .totals .row { font-size: 9.5px !important; padding: 0 !important; }
    .doc.ticket .totals .total-final { padding: 4px 8px !important; }
    .doc.ticket .totals .total-final .lbl,
    .doc.ticket .totals .total-final .val { font-size: 11px !important; }
    .doc.ticket .son, .doc.ticket .obs { font-size: 9.5px !important; margin-top: 2px !important; }
    .doc.ticket .qr-section { gap: 8px !important; margin-top: 3px !important; }
    .doc.ticket .qr-section .qr-box img,
    .doc.ticket .qr-section .qr-box canvas { width: 75px !important; height: 75px !important; }
    .doc.ticket .qr-section .info .lead { font-size: 9px !important; margin-bottom: 2px !important; }
    .doc.ticket .qr-section .info .hash { font-size: 7.5px !important; padding: 3px 5px !important; }
    .doc.ticket .foot { margin-top: 3px !important; }
    .doc.ticket .foot .gracias { font-size: 11px !important; }
    .doc.ticket .foot .web { font-size: 9px !important; }
    .doc.ticket .foot .amazonia-note { font-size: 9px !important; margin-top: 3px !important; }
}

/* ----- A4 ----- */
.doc.a4 {
    width: 780px;
    padding: 28px 42px;
    box-shadow: 0 10px 40px rgba(26,39,72,.15);
}

/* ============ HEADER (Logo + Empresa) ============ */
.head { display: flex; gap: 14px; align-items: center; margin-bottom: 10px; }
.head .logo-box {
    width: 70px; height: 70px;
    border-radius: 50%;
    background: var(--gris-claro);
    border: 1.5px solid var(--azul-oscuro);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}
.head .logo-box img { width: 100%; height: 100%; object-fit: cover; padding: 0; border-radius: 50%; display: block; }
.head .logo-default {
    color: var(--azul-oscuro);
    font-size: 36px;
    font-weight: 800;
    line-height: 1;
}
.head .emp-data { flex: 1; min-width: 0; }
.head .emp-data h1 { font-size: 16px; font-weight: 700; color: var(--azul-oscuro); letter-spacing: -.3px; line-height: 1.1; margin-bottom: 1px; }
.head .emp-data .slogan { font-size: 10.5px; color: var(--gris-text); font-weight: 500; margin-bottom: 3px; line-height: 1.2; }
.head .emp-data .ruc { font-size: 12px; font-weight: 700; color: var(--azul-oscuro); }
.head .emp-data .addr { font-size: 10.5px; color: var(--gris-text); margin-top: 1px; line-height: 1.25; }

/* ============ DIVIDER PUNTEADO ============ */
.divider {
    border: 0;
    border-top: 1.5px dashed var(--gris-borde);
    margin: 7px 0;
}

/* ============ TIPO DE COMPROBANTE ============ */
.tipo-block { text-align: center; margin: 2px 0 6px; }
.tipo-block .tipo {
    font-size: 16px;
    font-weight: 700;
    color: var(--azul-oscuro);
    letter-spacing: -.3px;
    margin-bottom: 4px;
}
.tipo-block .numero {
    display: inline-block;
    background: var(--azul-oscuro);
    color: #fff;
    padding: 5px 22px;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 1px;
}

/* ============ DATOS / META ============ */
.meta { font-size: 11px; }
.meta .row { display: flex; padding: 1px 0; }
.meta .row .lbl { font-weight: 700; color: var(--azul-oscuro); width: 56%; text-transform: uppercase; font-size: 10px; }
.meta .row .sep { width: 12px; color: var(--azul-oscuro); font-weight: 700; }
.meta .row .val { flex: 1; color: var(--azul-oscuro); font-weight: 500; }

/* ============ DATOS CLIENTE ============ */
.cliente { margin-top: 2px; }
.cliente h3 {
    font-size: 11px;
    font-weight: 700;
    color: var(--azul-oscuro);
    text-transform: uppercase;
    margin-bottom: 3px;
    letter-spacing: .3px;
}
.cliente .row { display: flex; padding: 1px 0; font-size: 11px; }
.cliente .row .lbl { color: var(--gris-text); width: 78px; font-weight: 500; }
.cliente .row .sep { width: 12px; color: var(--gris-text); }
.cliente .row .val { flex: 1; color: var(--azul-oscuro); font-weight: 600; }

/* ============ TABLA ITEMS ============ */
.items { margin-top: 2px; }
.items table { width: 100%; border-collapse: collapse; font-size: 11px; }
.items thead th {
    background: var(--azul-oscuro);
    color: #fff;
    padding: 5px 6px;
    text-align: left;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: .4px;
}
.items thead th.r { text-align: right; }
.items thead th.c { text-align: center; }
.items thead th:first-child { border-radius: 4px 0 0 4px; }
.items thead th:last-child  { border-radius: 0 4px 4px 0; }
.items tbody td {
    padding: 4px 6px;
    border-bottom: 1px solid var(--gris-claro);
    color: var(--azul-oscuro);
    font-size: 11px;
    vertical-align: top;
}
.items tbody td.r { text-align: right; font-weight: 600; }
.items tbody td.c { text-align: center; }
.items tbody td .desc { line-height: 1.2; }
.items tbody td .nota { font-size: 10px; color: var(--gris-text); font-style: italic; margin-top: 1px; }

/* ============ TOTALES ============ */
.totals { margin-top: 6px; }
.totals .row {
    display: flex; justify-content: space-between;
    padding: 2px 0;
    font-size: 11px;
}
.totals .row .lbl { color: var(--azul-oscuro); font-weight: 700; }
.totals .row .val { color: var(--azul-oscuro); font-weight: 600; min-width: 100px; text-align: right; }
.totals .total-final {
    margin-top: 4px;
    padding: 7px 12px;
    background: var(--azul-oscuro);
    color: #fff;
    border-radius: 6px;
}
.totals .total-final .lbl, .totals .total-final .val { color: #fff; font-size: 13px; font-weight: 700; }

/* ============ TEXTO EN LETRAS ============ */
.son { margin-top: 6px; font-size: 11px; }
.son b { color: var(--azul-oscuro); font-weight: 700; }
.son .texto { color: var(--azul-oscuro); margin-top: 1px; line-height: 1.2; font-weight: 500; }

/* ============ OBSERVACIONES ============ */
.obs { margin-top: 6px; font-size: 11px; }
.obs b { color: var(--azul-oscuro); font-weight: 700; text-transform: uppercase; font-size: 10.5px; }
.obs .texto { color: var(--azul-oscuro); margin-top: 1px; line-height: 1.2; }

/* ============ QR + CDR ============ */
.qr-section { display: flex; gap: 12px; align-items: flex-start; margin-top: 6px; }
.qr-section .qr-box { flex-shrink: 0; text-align: center; }
.qr-section .qr-box img,
.qr-section .qr-box canvas {
    width: 100px; height: 100px;
    background: #fff;
    border: 1px solid var(--gris-borde);
    border-radius: 6px;
}
.qr-section .qr-box .qr-lbl { font-size: 10.5px; font-weight: 700; color: var(--azul-oscuro); margin-top: 3px; }
.qr-section .qr-box .qr-help { font-size: 9px; color: var(--gris-text); margin-top: 1px; line-height: 1.2; }
.qr-section .info { flex: 1; padding-left: 6px; border-left: 2px solid var(--gris-borde); font-size: 11px; }
.qr-section .info b { font-weight: 700; color: var(--azul-oscuro); display: block; margin-bottom: 1px; font-size: 10.5px; text-transform: uppercase; letter-spacing: .3px; }
.qr-section .info .lead { color: var(--gris-text); font-size: 10px; line-height: 1.25; margin-bottom: 4px; }
.qr-section .info .hash {
    font-family: 'Courier New', monospace;
    font-size: 9px;
    color: var(--azul-oscuro);
    background: var(--gris-claro);
    padding: 5px 7px;
    border-radius: 4px;
    word-break: break-all;
    line-height: 1.25;
    margin-top: 2px;
}

/* ============ FOOTER ============ */
.foot { text-align: center; margin-top: 8px; }
.foot .gracias { font-size: 13px; font-weight: 700; color: var(--azul-oscuro); }
.foot .web     { font-size: 10.5px; color: var(--gris-text); margin-top: 2px; font-weight: 500; }
.foot .amazonia-note { font-size: 11px; font-weight: 700; color: var(--azul-oscuro); margin-top: 8px; text-transform: uppercase; letter-spacing: .3px; line-height: 1.25; }

/* Disclaimer ticket interno */
.disclaimer-int {
    margin-top: 6px;
    padding: 7px 10px;
    background: #fef3c7;
    color: #92400e;
    border-radius: 6px;
    font-size: 10px;
    text-align: center;
    line-height: 1.25;
}

/* ============ IMPRESIÓN ============ */
@media print {
    body { background: #fff; padding: 0; }
    .toolbar { display: none; }
    .doc::before, .doc::after { display: none; }
    .doc.ticket {
        width: 78mm;
        padding: 4mm 3mm;
        box-shadow: none;
        font-size: 10px;
    }
    .doc.a4 {
        width: 100%;
        padding: 12mm 14mm;
        box-shadow: none;
    }
}
@media print {
<?php if ($formato === 'ticket'): ?>
    @page { margin: 0; size: 80mm auto; }
<?php else: ?>
    @page { margin: 0; size: A4 portrait; }
<?php endif; ?>
}
<?php if ($__esBridge): ?>
/* Modo captura para el Bridge: sin barra, fondo blanco, ancho fijo en px */
.toolbar { display: none !important; }
html, body { background: #fff !important; padding: 0 !important; margin: 0 !important; width: <?php echo $__bw; ?>px !important; }
.doc, .doc.ticket { box-shadow: none !important; margin: 0 !important; width: <?php echo $__bw; ?>px !important; max-width: <?php echo $__bw; ?>px !important; }
<?php endif; ?>
</style>
</head>
<body>

<div class="toolbar">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
    <a class="btn-link" href="?id=<?php echo $idorden; ?>&formato=<?php echo $formato === 'ticket' ? 'a4' : 'ticket'; ?>">
        Cambiar a <?php echo $formato === 'ticket' ? 'A4' : 'Ticket'; ?>
    </a>
    <button class="btn-close" onclick="window.close()">✕ Cerrar</button>
</div>

<div class="doc <?php echo $formato; ?>">

    <!-- HEADER: Logo + Empresa -->
    <div class="head">
        <div class="logo-box">
            <?php if ($empLogo): ?>
                <img src="<?php echo h($empLogo); ?>" alt="logo">
            <?php else: ?>
                <div class="logo-default"><?php echo h(mb_substr($empNom, 0, 1)); ?></div>
            <?php endif; ?>
        </div>
        <div class="emp-data">
            <h1><?php echo h(strtoupper($empNom)); ?></h1>
            <?php if ($empRaz !== $empNom): ?>
                <div class="slogan"><?php echo h($empRaz); ?></div>
            <?php endif; ?>
            <?php if ($empRuc): ?>
                <div class="ruc">RUC: <?php echo h($empRuc); ?></div>
            <?php endif; ?>
            <?php if ($empDir): ?>
                <div class="addr"><?php echo h($empDir); ?></div>
            <?php endif; ?>
            <?php if ($empUbi && trim($empUbi, ' ,-')): ?>
                <div class="addr"><?php echo h($empUbi); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <hr class="divider">

    <!-- TIPO DE COMPROBANTE -->
    <div class="tipo-block">
        <div class="tipo"><?php echo h($tipoLabel); ?></div>
        <div class="numero"><?php echo h($numeroDoc); ?></div>
    </div>

    <hr class="divider">

    <!-- META: Fecha, hora, moneda, etc -->
    <div class="meta">
        <div class="row">
            <span class="lbl">Fecha de emisión</span>
            <span class="sep">:</span>
            <span class="val"><?php echo date('d/m/Y', strtotime($fechaEmision)); ?></span>
        </div>
        <div class="row">
            <span class="lbl">Hora de emisión</span>
            <span class="sep">:</span>
            <span class="val"><?php echo date('H:i:s', strtotime($fechaEmision)); ?></span>
        </div>
        <?php $esCredito = stripos(trim((string)$metodoLabel), 'credito') !== false || stripos(trim((string)$metodoLabel), 'crédito') !== false; ?>
        <?php if ($compFiscal && $esCredito): ?>
        <div class="row">
            <span class="lbl">Fecha de vencimiento</span>
            <span class="sep">:</span>
            <span class="val"><?php echo $fechaVenc; ?></span>
        </div>
        <?php endif; ?>
        <div class="row">
            <span class="lbl">Moneda</span>
            <span class="sep">:</span>
            <span class="val"><?php echo h($codMoneda); ?> - <?php echo $codMoneda === 'PEN' ? 'Soles' : ($codMoneda === 'USD' ? 'Dólares' : $codMoneda); ?></span>
        </div>
        <?php if ($compFiscal): ?>
        <div class="row">
            <span class="lbl">Tipo de operación</span>
            <span class="sep">:</span>
            <span class="val"><?php echo h($tipoOperLabel); ?></span>
        </div>
        <?php endif; ?>
        <div class="row">
            <span class="lbl">Forma de pago</span>
            <span class="sep">:</span>
            <span class="val"><?php echo h($metodoLabel); ?></span>
        </div>
        <div class="row">
            <span class="lbl">Cajero</span>
            <span class="sep">:</span>
            <span class="val"><?php echo h($cajero); ?></span>
        </div>
    </div>

    <hr class="divider">

    <!-- DATOS CLIENTE -->
    <div class="cliente">
        <h3>Datos del cliente</h3>
        <?php if ($compFiscal): ?>
            <div class="row">
                <span class="lbl">Cliente</span>
                <span class="sep">:</span>
                <span class="val"><?php echo h($compFiscal['cliente_razon']); ?></span>
            </div>
            <div class="row">
                <span class="lbl"><?php echo $compFiscal['cliente_tipo_doc'] === '6' ? 'RUC' : 'DNI'; ?></span>
                <span class="sep">:</span>
                <span class="val"><?php echo h($compFiscal['cliente_num_doc']); ?></span>
            </div>
            <?php if (!empty($compFiscal['cliente_direccion'])): ?>
            <div class="row">
                <span class="lbl">Dirección</span>
                <span class="sep">:</span>
                <span class="val"><?php echo h($compFiscal['cliente_direccion']); ?></span>
            </div>
            <?php endif; ?>
        <?php elseif (!empty($orden['cliente_nombre'])): ?>
            <div class="row">
                <span class="lbl">Cliente</span>
                <span class="sep">:</span>
                <span class="val"><?php echo h($orden['cliente_nombre']); ?></span>
            </div>
        <?php else: ?>
            <div class="row">
                <span class="lbl">Cliente</span>
                <span class="sep">:</span>
                <span class="val">CLIENTE VARIOS</span>
            </div>
        <?php endif; ?>
    </div>

    <hr class="divider">

    <!-- ITEMS -->
    <div class="items">
        <table>
            <thead>
                <tr>
                    <th style="width:14%;">Cód.</th>
                    <th>Descripción</th>
                    <th class="c" style="width:13%;">Cant.</th>
                    <th class="r" style="width:18%;">P. Unit.</th>
                    <th class="r" style="width:18%;">Importe</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orden['items'] as $idx => $i): $esCortesia = (int)($i['cortesia'] ?? 0) === 1; ?>
                    <tr>
                        <td>P<?php echo str_pad($idx + 1, 3, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <div class="desc"><?php echo h($i['nombre']); ?><?php if ($esCortesia): ?> <b style="color:#15803d;">(CORTESÍA)</b><?php endif; ?></div>
                            <?php if (!empty($i['nota'])): ?>
                                <div class="nota">└ <?php echo h($i['nota']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="c"><?php echo number_format((float)$i['cantidad'], 2); ?></td>
                        <td class="r"><?php echo $esCortesia ? '0.00' : number_format((float)$i['precio'], 2); ?></td>
                        <td class="r"><?php echo number_format((float)$i['subtotal'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <hr class="divider">

    <!-- TOTALES -->
    <div class="totals">
        <?php if ($tasaIgv > 0 && (float)$orden['subtotal'] > 0): ?>
        <div class="row">
            <span class="lbl">Op. Gravadas</span>
            <span class="val"><?php echo $money($orden['subtotal']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($tasaIgv > 0 && (float)$orden['igv'] > 0): ?>
        <div class="row">
            <span class="lbl">IGV (<?php echo $pctIgv; ?>%)</span>
            <span class="val"><?php echo $money($orden['igv']); ?></span>
        </div>
        <?php endif; ?>
        <div class="row total-final">
            <span class="lbl">TOTAL A PAGAR</span>
            <span class="val"><?php echo $money($orden['total']); ?></span>
        </div>
        <?php if ($orden['metodo_pago'] === 'efectivo' && (float)$orden['monto_recibido'] > 0): ?>
            <div class="row" style="margin-top:8px;">
                <span class="lbl" style="font-weight:500;">Recibido</span>
                <span class="val" style="font-weight:600;"><?php echo $money($orden['monto_recibido']); ?></span>
            </div>
            <div class="row">
                <span class="lbl" style="font-weight:500;">Vuelto</span>
                <span class="val" style="font-weight:600;"><?php echo $money($orden['vuelto']); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- TOTAL EN LETRAS -->
    <div class="son">
        <b>Son:</b>
        <div class="texto"><?php echo h($totalLetras); ?></div>
    </div>

    <!-- OBSERVACIONES -->
    <?php if (!empty($orden['observacion']) || !empty($orden['pago_referencia'])): ?>
    <div class="obs">
        <b>Observaciones:</b>
        <div class="texto">
            <?php if (!empty($orden['observacion'])) echo h($orden['observacion']); ?>
            <?php if (!empty($orden['pago_referencia'])): ?>
                <?php if (!empty($orden['observacion'])) echo '<br>'; ?>
                Ref. pago: <?php echo h($orden['pago_referencia']); ?>
            <?php endif; ?>
            <?php if (!empty($metadata['banco'])): ?> · Banco: <?php echo h($metadata['banco']); ?><?php endif; ?>
            <?php if (!empty($metadata['ultimos4'])): ?> · Tarjeta: ****<?php echo h($metadata['ultimos4']); ?><?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="obs">
        <b>Observaciones:</b>
        <div class="texto">Gracias por su preferencia.</div>
    </div>
    <?php endif; ?>

    <hr class="divider">

    <!-- QR + CDR (solo si es boleta/factura electrónica) -->
    <?php if ($compFiscal): ?>
    <div class="qr-section">
        <div class="qr-box">
            <canvas id="qr-canvas" width="220" height="220"></canvas>
            <div class="qr-lbl">Código QR</div>
            <div class="qr-help">Escanee este código para<br>verificar la validez de<br>la <?php echo $orden['tipo_comprobante'] === 'factura' ? 'factura' : 'boleta'; ?> en SUNAT.</div>
        </div>
        <div class="info">
            <b>Documento validado por SUNAT</b>
            <div class="lead">
                Representación impresa de la <?php echo strtolower($tipoLabel); ?>.<br>
                Puede consultar su comprobante en:<br>
                <span style="color:var(--azul-oscuro);font-weight:600;">https://www.sunat.gob.pe</span>
            </div>
            <?php if (!empty($compFiscal['xml_hash'])): ?>
                <b>Código Hash:</b>
                <div class="hash"><?php echo h($compFiscal['xml_hash']); ?></div>
            <?php endif; ?>
            <?php if (!empty($cadenaQR)): ?>
                <b style="margin-top:8px;display:block;">CUFE:</b>
                <div class="hash"><?php echo h($cadenaQR); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <hr class="divider">
    <?php endif; ?>

    <!-- FOOTER -->
    <div class="foot">
        <div class="gracias">¡Gracias por confiar en nosotros!</div>
        <?php if ($empWeb): ?>
            <div class="web"><?php echo h($empWeb); ?></div>
        <?php elseif ($empMail): ?>
            <div class="web"><?php echo h($empMail); ?></div>
        <?php endif; ?>
        <?php if ($empTel): ?>
            <div class="web">Tel: <?php echo h($empTel); ?></div>
        <?php endif; ?>
        <?php if ($compFiscal): ?>
            <div class="amazonia-note">BIENES TRANSFERIDOS EN LA AMAZONIA PARA SER CONSUMIDOS EN LA MISMA</div>
        <?php endif; ?>
    </div>

    <!-- Disclaimer para tickets internos (configurable: empresa.mostrar_glosa_interna) -->
    <?php if (!$compFiscal && in_array($orden['tipo_comprobante'], ['ticket','nota_venta'], true)
              && (int)($emp['mostrar_glosa_interna'] ?? 1) === 1): ?>
        <div class="disclaimer-int">
            Documento interno de uso comercial.<br>
            No constituye comprobante de pago electrónico SUNAT.
        </div>
    <?php endif; ?>

</div>

<?php if ($cadenaQR): ?>
<!-- Generador de QR client-side (sin dependencias) -->
<script>
// QR Code generator (qrcode-generator) embebido minimo
(function(){
    // Usar libreria CDN
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js';
    script.onload = function() {
        var canvas = document.getElementById('qr-canvas');
        if (!canvas) return;
        var qr = qrcode(0, 'L');
        qr.addData(<?php echo json_encode($cadenaQR); ?>);
        qr.make();
        var ctx = canvas.getContext('2d');
        var modules = qr.getModuleCount();
        var size = 220;
        var cell = size / modules;
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, size, size);
        ctx.fillStyle = '#1a2748';
        for (var r = 0; r < modules; r++) {
            for (var c = 0; c < modules; c++) {
                if (qr.isDark(r, c)) {
                    ctx.fillRect(c * cell, r * cell, cell, cell);
                }
            }
        }
    };
    document.head.appendChild(script);
})();
</script>
<?php endif; ?>

<script>
<?php if (!$__esBridge): ?>
// Auto-imprimir al abrir
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 600);
});
<?php endif; ?>
</script>
</body>
</html>
