<?php
require_once __DIR__ . "/../config/auth.php";
requireLogin();
require_once __DIR__ . "/../modelos/Caja.php";
require_once __DIR__ . "/../modelos/Empresa.php";

$idsesion = (int)($_GET['idsesion'] ?? 0);
if ($idsesion <= 0) { http_response_code(400); exit('idsesion requerido'); }

$ses = dbFila(
    "SELECT s.*, u.nombre AS u_nombre, u.apellidos AS u_apellidos
     FROM caja_sesion s
     LEFT JOIN usuario u ON u.idusuario = s.idusuario
     WHERE s.idsesion = ?",
    'i', [$idsesion]
);
if (!$ses) { http_response_code(404); exit('Sesión no encontrada'); }

$caja = new Caja();
$info = $caja->arqueoEsperado($idsesion);
$desglose = $info['desglose'] ?? [];
$consolidado = $caja->consolidadoDia($idsesion);

$arq = dbFila(
    "SELECT * FROM caja_arqueo WHERE idsesion = ? ORDER BY idarqueo DESC LIMIT 1",
    'i', [$idsesion]
);
$contado    = $arq ? (float)$arq['monto_contado'] : (float)$info['esperado'];
$diferencia = $arq ? (float)$arq['diferencia']    : 0.0;
$obs        = $arq ? trim((string)$arq['observacion']) : '';

$emp = (new Empresa())->mostrar(1) ?: [];
$nombreEmp = $emp['nombre_comercial'] ?? $emp['razon_social'] ?? 'PUERTO HABANA POS';
$razonSoc  = $emp['razon_social'] ?? '';
$ruc       = $emp['numero_ruc'] ?? '';
$direccion = $emp['domicilio_fiscal'] ?? '';
$ubicacion = trim(implode(', ', array_filter([
    $emp['distrito'] ?? '',
    $emp['provincia'] ?? '',
    $emp['departamento'] ?? '',
])));
$logoPath  = !empty($emp['logo']) ? ('../' . $emp['logo']) : '';

$cajero = trim(($ses['u_nombre'] ?? '') . ' ' . ($ses['u_apellidos'] ?? '')) ?: ($ses['cajero'] ?? '—');

$metodosLabels = [
    'efectivo'      => 'Efectivo',
    'tarjeta'       => 'Tarjeta',
    'yape'          => 'Yape',
    'plin'          => 'Plin',
    'transferencia' => 'Transferencia',
];

$totalVentas = 0; $totalCantidad = 0;
foreach ($desglose as $m => $d) { $totalVentas += $d['total']; $totalCantidad += $d['cantidad']; }

$fmt = function ($n) { return 'S/ ' . number_format((float)$n, 2, '.', ','); };
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
// Formatea cantidades de unidades (entero si es exacto, si no con 2 decimales)
$und = function ($n) {
    $n = (float)$n;
    return (floor($n) == $n) ? (string)(int)$n : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
};
$totalConsumo = (float)$consolidado['comidas'] + (float)$consolidado['bebidas'];

$estado = $diferencia == 0.0 ? 'CUADRA' : ($diferencia > 0 ? 'SOBRANTE' : 'FALTANTE');
$colorEstado = $diferencia == 0.0 ? '#10b981' : ($diferencia > 0 ? '#2563eb' : '#dc2626');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Arqueo de Caja · <?php echo $h($ses['caja_codigo']); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
@page { size: 80mm auto; margin: 0; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; font-size: 11px; line-height: 1.4; color: #1f2937; background: #e5e7eb; padding: 14px 0; }
.ticket { width: 80mm; margin: 0 auto; background: #fff; padding: 12px 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
@media print {
    body { background: #fff; padding: 0; }
    .ticket { box-shadow: none; width: 80mm; padding: 6px 6px; }
    .toolbar { display: none !important; }
}

.toolbar { width: 80mm; margin: 0 auto 10px; display: flex; gap: 6px; }
.btn { flex: 1; padding: 9px; background: #0f1d3a; color: #fff; border: 0; border-radius: 6px; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 12px; }
.btn.btn-sec { background: #fff; color: #1f2937; border: 1px solid #cbd5e1; }

/* Header empresa */
.empresa { text-align: center; padding: 4px 0 8px; }
.empresa-logo { display: block; margin: 2px auto 6px; width: 150px; height: 150px; }
.empresa-logo img { width: 100%; height: 100%; object-fit: contain; display: block; image-rendering: -webkit-optimize-contrast; }
@media print {
    .empresa-logo { width: 130px; height: 130px; }
}
.empresa-nombre { font-size: 13px; font-weight: 800; color: #0f1d3a; letter-spacing: 0.3px; line-height: 1.2; }
.empresa-razon { font-size: 10px; color: #1f2937; font-weight: 500; line-height: 1.3; margin-top: 2px; }
.empresa-ruc { font-size: 11px; font-weight: 700; color: #0f1d3a; margin-top: 3px; }
.empresa-dir { font-size: 9.5px; color: #4b5563; line-height: 1.3; margin-top: 3px; }

.doble-linea { border-top: 2px solid #0f1d3a; margin: 8px 0; }
.dash-line { border-top: 1px dashed #94a3b8; margin: 6px 0; }

/* Cajas tipo "BOLETA ELECTRÓNICA" y "B001-00000019" */
.box-title { background: #0f1d3a; color: #fff; text-align: center; padding: 8px 6px; font-weight: 800; font-size: 12px; border-radius: 4px; letter-spacing: 1px; margin: 4px 0; }
.box-sub { background: #0f1d3a; color: #fff; text-align: center; padding: 6px 6px; font-weight: 700; font-size: 11px; border-radius: 4px; letter-spacing: 0.5px; margin: 6px 0; }

/* Tabla y filas */
.section-title { font-size: 11px; font-weight: 700; color: #0f1d3a; text-align: center; padding: 2px 0; letter-spacing: 0.5px; }
.row-info { display: flex; justify-content: space-between; gap: 6px; padding: 1px 0; font-size: 11px; }
.row-info .lbl { color: #4b5563; font-weight: 600; }
.row-info .val { color: #1f2937; font-weight: 600; text-align: right; }

.tbl-head { background: #0f1d3a; color: #fff; font-weight: 700; font-size: 10px; padding: 5px 6px; border-radius: 3px; display: grid; grid-template-columns: 1fr 26px 70px; gap: 4px; letter-spacing: 0.5px; }
.tbl-row { display: grid; grid-template-columns: 1fr 26px 70px; gap: 4px; padding: 3px 6px; font-size: 11px; border-bottom: 1px dotted #e5e7eb; }
.tbl-row .qty { text-align: center; font-weight: 600; }
.tbl-row .amt { text-align: right; font-weight: 600; white-space: nowrap; }
.tbl-total { display: grid; grid-template-columns: 1fr 26px 70px; gap: 4px; padding: 5px 6px; font-size: 11px; font-weight: 700; color: #0f1d3a; }
.tbl-total .qty { text-align: center; }
.tbl-total .amt { text-align: right; white-space: nowrap; }

/* Caja DIFERENCIA estilo TOTAL A PAGAR */
.box-total { background: #0f1d3a; color: #fff; padding: 10px 12px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; margin: 6px 0; font-weight: 800; font-size: 13px; letter-spacing: 0.4px; }

/* Tag estado */
.tag-wrap { text-align: center; margin: 8px 0 4px; }
.tag { display: inline-block; padding: 5px 16px; border-radius: 16px; color: #fff; font-weight: 800; font-size: 11px; letter-spacing: 1px; }

/* Firma */
.firma { font-size: 10.5px; color: #1f2937; font-weight: 600; margin-top: 14px; }
.firma-linea { border-top: 1px solid #1f2937; margin-top: 28px; }
.fecha-pie { text-align: center; font-size: 10px; color: #6b7280; margin-top: 6px; font-weight: 500; }

.obs-block { margin-top: 6px; }
.obs-block .obs-label { font-size: 10px; font-weight: 700; color: #0f1d3a; letter-spacing: 0.5px; }
.obs-block .obs-text { font-size: 10.5px; color: #1f2937; margin-top: 3px; }
</style>
</head>
<body>

<div class="toolbar">
    <button class="btn" onclick="window.print()">🖨 Imprimir</button>
    <button class="btn btn-sec" onclick="window.close()">Cerrar</button>
</div>

<div class="ticket">

    <!-- Cabecera empresa -->
    <div class="empresa">
        <?php if ($logoPath): ?>
            <div class="empresa-logo"><img src="<?php echo $h($logoPath); ?>" alt="logo"></div>
        <?php endif; ?>
        <div class="empresa-nombre"><?php echo $h(strtoupper($nombreEmp)); ?></div>
        <?php if ($razonSoc && strtoupper($razonSoc) !== strtoupper($nombreEmp)): ?>
            <div class="empresa-razon"><?php echo $h($razonSoc); ?></div>
        <?php endif; ?>
        <?php if ($ruc): ?>
            <div class="empresa-ruc">RUC: <?php echo $h($ruc); ?></div>
        <?php endif; ?>
        <?php if ($direccion): ?>
            <div class="empresa-dir"><?php echo $h($direccion); ?></div>
        <?php endif; ?>
        <?php if ($ubicacion): ?>
            <div class="empresa-dir"><?php echo $h($ubicacion); ?></div>
        <?php endif; ?>
    </div>

    <div class="doble-linea"></div>

    <!-- Titulo "ARQUEO DE CAJA" -->
    <div class="box-title">ARQUEO DE CAJA</div>

    <!-- Box AP-001 · TURNO -->
    <div class="box-sub"><?php echo $h(strtoupper($ses['caja_codigo'])); ?> &middot; TURNO: <?php echo $h(strtoupper($ses['turno'])); ?></div>

    <!-- Datos sesion -->
    <div style="margin-top:8px;">
        <div class="row-info"><span class="lbl">CAJERO</span><span class="val"><?php echo $h($cajero); ?></span></div>
        <div class="row-info"><span class="lbl">APERTURA</span><span class="val"><?php echo $h(date('d/m/Y H:i', strtotime($ses['fecha_apertura']))); ?></span></div>
        <div class="row-info"><span class="lbl">CIERRE</span><span class="val"><?php echo $h(date('d/m/Y H:i')); ?></span></div>
    </div>

    <div class="dash-line"></div>
    <div class="section-title">VENTAS POR MÉTODO DE PAGO</div>
    <div class="dash-line"></div>

    <div class="tbl-head"><span>MÉTODO</span><span style="text-align:center;">N°</span><span style="text-align:right;">TOTAL</span></div>
    <?php foreach ($metodosLabels as $m => $label):
        $d = $desglose[$m] ?? ['cantidad'=>0,'total'=>0]; ?>
        <div class="tbl-row">
            <span><?php echo $h($label); ?></span>
            <span class="qty"><?php echo (int)$d['cantidad']; ?></span>
            <span class="amt"><?php echo $fmt($d['total']); ?></span>
        </div>
    <?php endforeach; ?>

    <div class="tbl-total">
        <span>TOTAL VENTAS</span>
        <span class="qty"><?php echo $totalCantidad; ?></span>
        <span class="amt"><?php echo $fmt($totalVentas); ?></span>
    </div>

    <div class="doble-linea"></div>
    <div class="section-title">MOVIMIENTOS DE CAJA</div>
    <div class="dash-line"></div>

    <div class="row-info"><span class="lbl">Monto apertura</span><span class="val"><?php echo $fmt($info['monto_inicial']); ?></span></div>
    <div class="row-info"><span class="lbl">(+) Ventas efectivo</span><span class="val"><?php echo $fmt($info['ventas_efectivo']); ?></span></div>
    <div class="row-info"><span class="lbl">(+) Otros ingresos</span><span class="val"><?php echo $fmt($info['ingresos']); ?></span></div>
    <div class="row-info"><span class="lbl">(-) Egresos</span><span class="val"><?php echo $fmt($info['egresos']); ?></span></div>

    <div class="dash-line"></div>
    <div class="row-info" style="font-weight:700;"><span class="lbl" style="color:#0f1d3a;">ESPERADO EN CAJA</span><span class="val"><?php echo $fmt($info['esperado']); ?></span></div>
    <div class="row-info" style="font-weight:700;"><span class="lbl" style="color:#0f1d3a;">CONTADO</span><span class="val"><?php echo $fmt($contado); ?></span></div>
    <div class="dash-line"></div>

    <!-- Caja DIFERENCIA -->
    <div class="box-total">
        <span>DIFERENCIA</span>
        <span><?php echo $fmt(abs($diferencia)); ?></span>
    </div>

    <!-- Tag estado -->
    <div class="tag-wrap">
        <span class="tag" style="background: <?php echo $colorEstado; ?>;">
            <?php echo $estado === 'CUADRA' ? '✓ ' : ''; ?><?php echo $estado; ?>
        </span>
    </div>

    <!-- ===================== CONSOLIDADO DEL DÍA ===================== -->
    <div class="doble-linea"></div>
    <div class="section-title">CONSOLIDADO DEL DÍA</div>
    <div class="dash-line"></div>

    <!-- General: comidas vs bebidas (incluye cortesías) -->
    <div class="tbl-head" style="grid-template-columns:1fr 70px;">
        <span>TIPO</span><span style="text-align:right;">CANT.</span>
    </div>
    <div class="tbl-row" style="grid-template-columns:1fr 70px;">
        <span>🍽 Platos de comida</span>
        <span class="amt"><?php echo $und($consolidado['comidas']); ?> und</span>
    </div>
    <div class="tbl-row" style="grid-template-columns:1fr 70px;">
        <span>🥤 Bebidas</span>
        <span class="amt"><?php echo $und($consolidado['bebidas']); ?> und</span>
    </div>
    <div class="tbl-total" style="grid-template-columns:1fr 70px;">
        <span>TOTAL CONSUMO</span>
        <span class="amt"><?php echo $und($totalConsumo); ?> und</span>
    </div>

    <?php if (!empty($consolidado['bebidas_detalle'])): ?>
        <div class="dash-line"></div>
        <div class="section-title" style="font-size:10px;">DESGLOSE DE BEBIDAS</div>
        <div class="tbl-head" style="grid-template-columns:1fr 60px;">
            <span>PRODUCTO</span><span style="text-align:right;">UND</span>
        </div>
        <?php foreach ($consolidado['bebidas_detalle'] as $b): ?>
            <div class="tbl-row" style="grid-template-columns:1fr 60px;">
                <span><?php echo $h($b['nombre']); ?></span>
                <span class="amt"><?php echo $und($b['cantidad']); ?> und</span>
            </div>
        <?php endforeach; ?>
        <div class="tbl-total" style="grid-template-columns:1fr 60px;">
            <span>TOTAL BEBIDAS</span>
            <span class="amt"><?php echo $und($consolidado['bebidas']); ?> und</span>
        </div>
    <?php endif; ?>

    <?php if ($obs !== ''): ?>
        <div class="dash-line"></div>
        <div class="obs-block">
            <div class="obs-label">OBSERVACIÓN:</div>
            <div class="obs-text"><?php echo nl2br($h($obs)); ?></div>
        </div>
    <?php endif; ?>

    <div class="dash-line"></div>

    <div class="firma">Firma del cajero:</div>
    <div class="firma-linea"></div>
    <div class="fecha-pie"><?php echo date('d/m/Y H:i'); ?></div>

    <div class="doble-linea"></div>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 350);
});
</script>
</body>
</html>
