<?php
require_once __DIR__ . "/../config/auth.php";
requireLogin();
require_once __DIR__ . "/../modelos/Orden.php";

$idorden = isset($_GET['id'])    ? (int)$_GET['id'] : 0;
// Formato unico: items=ID:DELTA,ID:DELTA  (cantidad a cocinar/anular)
$rawItems = isset($_GET['items']) ? trim($_GET['items']) : '';
// type: 'adicion' (default, prepara) o 'anular' (cancela en cocina)
$tipoComanda = isset($_GET['type']) ? trim($_GET['type']) : 'adicion';
if (!in_array($tipoComanda, ['adicion', 'anular'], true)) $tipoComanda = 'adicion';
// Compatibilidad legacy
$idsRaw   = isset($_GET['ids'])   ? trim($_GET['ids'])   : '';
$qRaw     = isset($_GET['q'])     ? trim($_GET['q'])     : '';

if ($idorden <= 0) { http_response_code(400); exit('ID inválido'); }

$ordenObj = new Orden();
$cab = $ordenObj->mostrarCabecera($idorden);
if (!$cab) { http_response_code(404); exit('Orden no encontrada'); }

// Parsear items (nuevo formato preferido)
$ids    = [];
$deltas = [];

if ($rawItems !== '') {
    foreach (explode(',', $rawItems) as $pair) {
        $parts = explode(':', $pair, 2);
        if (count($parts) === 2) {
            $idd = (int)$parts[0];
            $cnt = (float)$parts[1];
            if ($idd > 0 && $cnt > 0) {
                $ids[]         = $idd;
                $deltas[$idd]  = $cnt;
            }
        }
    }
}
// Compat: si vino formato viejo (ids + q)
if (empty($ids) && $idsRaw !== '') {
    foreach (explode(',', $idsRaw) as $x) {
        $n = (int)$x; if ($n > 0) $ids[] = $n;
    }
    if ($qRaw !== '') {
        foreach (explode(',', $qRaw) as $pair) {
            $parts = explode(':', $pair, 2);
            if (count($parts) === 2) {
                $idd = (int)$parts[0];
                $cnt = (float)$parts[1];
                if ($idd > 0 && $cnt > 0) $deltas[$idd] = $cnt;
            }
        }
    }
}

// Si seguimos sin ids, imprimir orden completa (caso reimpresion manual)
if (empty($ids)) {
    $rs = $ordenObj->mostrarDetalle($idorden);
    $items = [];
    while ($r = $rs->fetch_assoc()) {
        if ($r['estado'] === 'anulado') continue;
        $items[] = $r;
    }
} else {
    $items = $ordenObj->detallesPorIds($idorden, $ids, $deltas);
}

// Filtro por CATEGORÍA: omite los productos cuya categoría está configurada para
// NO mostrarse en comanda (ej. bebidas). Es solo visual: el producto se cobra y
// descuenta stock igual. NULL/ausente = se muestra (categorías sin configurar).
$items = array_values(array_filter($items, function ($it) {
    $mc = $it['mostrar_comanda'] ?? null;
    return !($mc !== null && (int)$mc === 0);
}));

// Si tras el filtro no queda NADA para cocina (ej. comanda solo de bebidas con la
// categoría oculta), no se imprime papel: la ventana se cierra sola. La orden ya
// quedó registrada y enviada con normalidad.
if (empty($items)) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sin comanda</title></head>'
       . '<body style="font-family:Arial,sans-serif;text-align:center;padding:26px;color:#6b7280;font-size:14px;">'
       . 'No hay productos para la comanda de cocina.'
       . '<script>setTimeout(function(){ window.close(); }, 400);</script>'
       . '</body></html>';
    exit;
}

// Etiqueta amigable del tipo de orden (para que la cocina sepa si es para llevar / delivery)
$tipoLabels = [
    'dine_in'     => 'LOCAL',
    'mesa'        => 'LOCAL',
    'local'       => 'LOCAL',
    'para_llevar' => 'PARA LLEVAR',
    'llevar'      => 'PARA LLEVAR',
    'takeaway'    => 'PARA LLEVAR',
    'delivery'    => 'DELIVERY',
];
$tipoKey   = strtolower(trim((string)($cab['tipo'] ?? '')));
$tipoOrden = $tipoLabels[$tipoKey] ?? strtoupper(str_replace('_', ' ', $tipoKey ?: 'ORDEN'));
$mesa     = $cab['mesa_numero'] ? 'Mesa ' . $cab['mesa_numero'] : $tipoOrden;
// Nombre del mozo que atiende la mesa (usuario dueño de la orden); si no, campo libre
$mozo     = trim((string)($cab['propietario_nombre'] ?? '')) ?: ($cab['mozo'] ?? '—');
$numero   = $cab['numero']      ?? '';
$obs      = trim((string)($cab['observacion'] ?? ''));
$now      = date('d/m/Y H:i');

// Cuenta total de unidades a cocinar en esta comanda
$totalUnidades = 0;
foreach ($items as $it) $totalUnidades += (float)$it['cantidad'];

// Conteo COMIDA vs BEBIDA de ESTA comanda (bebida = categoría que contiene "BEBIDA").
// Los TAPERS (categoría/nombre con "TAPER") NO cuentan ni como comida ni bebida.
$estaComida = 0; $estaBebida = 0;
foreach ($items as $it) {
    $cant = (float)$it['cantidad'];
    $cat  = (string)($it['categoria_nombre'] ?? '');
    $nom  = (string)($it['nombre'] ?? '');
    if (stripos($cat, 'TAPER') !== false || stripos($nom, 'TAPER') !== false) continue; // taper no cuenta
    if (stripos($cat, 'BEBIDA') !== false) $estaBebida += $cant;
    else $estaComida += $cant;
}
// Acumulado del TURNO (incluye esta comanda; las anulaciones ya están descontadas)
// SIEMPRE se calcula internamente, aunque luego no se muestre.
$acum = $ordenObj->acumuladoCocinaTurno((int)($cab['idsesion'] ?? 0));

// ¿Mostrar el contador de BEBIDA en la comanda? Solo si la categoría de bebidas
// está configurada para salir en comanda (mostrar_comanda=1). Si está oculta, el
// conteo se sigue haciendo arriba pero NO se imprime.
$rowBeb = ejecutarConsultaSimpleFila(
    "SELECT COUNT(*) AS n FROM categoria WHERE estado=1 AND UPPER(nombre) LIKE '%BEBIDA%' AND mostrar_comanda=1");
$mostrarBebidaComanda = $rowBeb && (int)$rowBeb['n'] > 0;

$undC = function ($n) { $n=(float)$n; return (floor($n)==$n)?(string)(int)$n:rtrim(rtrim(number_format($n,2,'.',''),'0'),'.'); };
$esAnular = ($tipoComanda === 'anular');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Comanda · #<?php echo htmlspecialchars($numero); ?></title>
<style>
    @page { size: 80mm auto; margin: 0; }
    * { box-sizing: border-box; }
    html, body {
        margin: 0; padding: 0;
        font-family: 'Courier New', monospace;
        color: #000;
        background: #fff;
    }
    body { width: 80mm; padding: 6mm 5mm; }

    /* ========== HEADER ========== */
    .header {
        text-align: center;
        padding-bottom: 8px;
        margin-bottom: 8px;
        border-bottom: 2px dashed #000;
    }
    .titulo {
        font-size: 22px;
        font-weight: 900;
        letter-spacing: 3px;
        line-height: 1;
        margin-bottom: 4px;
    }
    .subtitulo {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
        color: #000;
    }
    .subtitulo .ord-num {
        display: inline-block;
        padding: 2px 8px;
        background: #000;
        color: #fff;
        border-radius: 3px;
        margin-left: 4px;
    }

    /* Tag central: "POR PREPARAR" siempre (kitchen-centric) */
    .tag-cocina {
        display: inline-block;
        margin-top: 10px;
        padding: 5px 16px;
        background: #fff;
        color: #000;
        border: 3px solid #000;
        font-weight: 900;
        font-size: 12px;
        letter-spacing: 3px;
        position: relative;
    }
    .tag-cocina::before,
    .tag-cocina::after {
        content: '';
        position: absolute;
        top: 50%;
        width: 14px;
        height: 3px;
        background: #000;
        transform: translateY(-50%);
    }
    .tag-cocina::before { left: -18px; }
    .tag-cocina::after  { right: -18px; }

    /* Tipo de orden bien visible: LOCAL / PARA LLEVAR / DELIVERY */
    .tipo-orden {
        display: block;
        margin: 8px auto 0;
        padding: 6px 10px;
        background: #000;
        color: #fff;
        font-weight: 900;
        font-size: 16px;
        letter-spacing: 2px;
        text-align: center;
    }

    /* ========== INFO MESA + MOZO + HORA ========== */
    .info {
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 6px;
        padding: 4px 0;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 2px 0;
    }
    .info-row .mesa-big { font-size: 16px; font-weight: 900; }

    /* ========== DIVISORES ========== */
    .divider-strong { border-top: 3px solid #000; margin: 8px 0 6px; }

    /* ========== ITEMS ========== */
    .items { margin: 4px 0; }
    .item  {
        padding: 9px 0;
        border-bottom: 1px dashed #777;
    }
    .item:last-child { border-bottom: 0; }
    .item-line {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        font-size: 15px;
        line-height: 1.25;
    }

    /* Chip de cantidad: SIEMPRE con + para indicar "esto hay que cocinar ahora" */
    .qty-chip {
        flex-shrink: 0;
        min-width: 54px;
        background: #fff;
        color: #000;
        border: 3px solid #000;
        padding: 3px 6px;
        text-align: center;
        font-size: 17px;
        font-weight: 900;
        letter-spacing: 0.5px;
        line-height: 1;
    }
    .qty-chip .plus {
        font-size: 20px;
        font-weight: 900;
        margin-right: 1px;
        vertical-align: -1px;
    }

    .item-name {
        flex: 1;
        font-weight: 800;
        word-break: break-word;
        text-transform: uppercase;
        font-size: 14px;
        padding-top: 6px;
    }
    /* Estilo tachado para items anulados */
    .item-name.name-anular {
        text-decoration: line-through;
        text-decoration-thickness: 2px;
    }
    /* Etiqueta de cortesia (alto contraste para impresora termica B/N) */
    .cortesia-tag {
        display: inline-block;
        font-size: 11px;
        font-weight: 900;
        padding: 1px 6px;
        border: 2px solid #000;
        border-radius: 4px;
        margin-left: 4px;
        vertical-align: middle;
        letter-spacing: .5px;
    }

    /* Nota / observacion del item */
    .item-nota {
        margin-top: 6px;
        margin-left: 64px;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 8px;
        background: #f0f0f0;
        border-left: 4px solid #000;
        color: #000;
    }
    .item-nota::before { content: '> '; font-weight: 900; }

    /* ========== OBS GENERAL ========== */
    .obs-box {
        margin-top: 10px;
        padding: 6px 8px;
        border: 2px dashed #000;
        font-size: 12px;
        font-weight: 700;
    }
    .obs-box .obs-title {
        font-weight: 900;
        margin-bottom: 3px;
        letter-spacing: 1px;
    }

    /* ========== FOOTER ========== */
    .footer {
        text-align: center;
        margin-top: 12px;
        padding-top: 8px;
        border-top: 2px dashed #000;
    }
    .footer .big {
        font-weight: 900;
        font-size: 14px;
        letter-spacing: 2px;
    }
    .footer .count {
        margin-top: 4px;
        font-size: 11px;
        font-weight: 700;
    }

    /* Toolbar (oculto en print) */
    @media print {
        body { width: 80mm; }
        .no-print { display: none !important; }
    }
    .no-print-toolbar {
        position: fixed; top: 10px; right: 10px;
        display: flex; gap: 8px;
        background: #fff; padding: 8px; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-family: sans-serif;
    }
    .no-print-toolbar button {
        padding: 6px 14px; border: 0; border-radius: 6px;
        font-family: inherit; font-weight: 700; cursor: pointer; font-size: 12px;
    }
    .btn-print { background: #5b3df5; color: #fff; }
    .btn-close { background: #e5e7eb; color: #111; }

    /* Resumen de consumo (comida/bebida) bajo el mozo */
    .consumo-box { margin-top: 6px; padding: 5px 0; border-top: 1px dashed #000; border-bottom: 1px dashed #000; }
    .consumo-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; font-weight: 800; padding: 1px 2px; }
    .consumo-row .cl { letter-spacing: 0.3px; }
    .consumo-row .cv { white-space: nowrap; }
    .consumo-row.acum .cv { color: #000; }
</style>
</head>
<body>

<!-- ========== HEADER ========== -->
<div class="header">
    <div class="titulo"><?php echo $tipoComanda === 'anular' ? 'COMANDA' : 'COMANDA'; ?></div>
    <div class="subtitulo">ORDEN <span class="ord-num">#<?php echo htmlspecialchars($numero); ?></span></div>
    <div class="tag-cocina"><?php echo $tipoComanda === 'anular' ? 'ANULAR' : 'POR PREPARAR'; ?></div>
    <div class="tipo-orden"><?php echo htmlspecialchars($tipoOrden); ?></div>
</div>

<!-- ========== INFO ========== -->
<div class="info">
    <div class="info-row">
        <span class="mesa-big"><?php echo htmlspecialchars($mesa); ?></span>
        <span><?php echo htmlspecialchars($now); ?></span>
    </div>
    <div class="info-row" style="margin-top:4px;font-size:11px;">
        <span>Mozo:</span>
        <span><?php echo htmlspecialchars($mozo); ?></span>
    </div>
    <!-- Resumen de consumo: esta comanda + acumulado del turno -->
    <div class="consumo-box">
        <div class="consumo-row">
            <span class="cl"><?php echo $esAnular ? 'ESTA ANULACION' : 'ESTA COMANDA'; ?></span>
            <?php /* La BEBIDA siempre se cuenta ($estaBebida); solo se muestra si su categoría sale en comanda. */ ?>
            <span class="cv">Comida <?php echo $undC($estaComida); ?><?php if ($mostrarBebidaComanda): ?> &nbsp;·&nbsp; Bebida <?php echo $undC($estaBebida); ?><?php endif; ?></span>
        </div>
        <div class="consumo-row acum">
            <span class="cl">ACUMULADO DIA</span>
            <span class="cv">Comida <?php echo $undC($acum['comida']); ?><?php if ($mostrarBebidaComanda): ?> &nbsp;·&nbsp; Bebida <?php echo $undC($acum['bebida']); ?><?php endif; ?></span>
        </div>
    </div>
</div>

<div class="divider-strong"></div>

<!-- ========== ITEMS ========== -->
<div class="items">
<?php if (empty($items)): ?>
    <div style="text-align:center;padding:14px 0;font-size:12px;">Sin items para imprimir</div>
<?php else: foreach ($items as $it):
    $cant    = (float)$it['cantidad'];
    $cantTxt = ($cant == (int)$cant) ? (string)(int)$cant : rtrim(rtrim(number_format($cant, 2, '.', ''), '0'), '.');
?>
    <div class="item">
        <div class="item-line">
            <span class="qty-chip"><span class="plus"><?php echo $tipoComanda === 'anular' ? '&minus;' : '+'; ?></span><?php echo $cantTxt; ?></span>
            <span class="item-name<?php echo $tipoComanda === 'anular' ? ' name-anular' : ''; ?>"><?php echo htmlspecialchars($it['nombre']); ?><?php if ((int)($it['cortesia'] ?? 0) === 1): ?> <span class="cortesia-tag">CORTESÍA</span><?php endif; ?></span>
        </div>
        <?php if (!empty($it['nota'])): ?>
            <div class="item-nota"><?php echo htmlspecialchars($it['nota']); ?></div>
        <?php endif; ?>
    </div>
<?php endforeach; endif; ?>
</div>

<?php if ($obs !== ''): ?>
    <div class="obs-box">
        <div class="obs-title">>> OBSERVACION GENERAL <<</div>
        <?php echo htmlspecialchars($obs); ?>
    </div>
<?php endif; ?>

<!-- ========== FOOTER ========== -->
<div class="footer">
    <div class="big">&gt;&gt;&gt; COCINA &lt;&lt;&lt;</div>
    <div class="count">
        <?php
        $n = count($items);
        $totalTxt = ($totalUnidades == (int)$totalUnidades) ? (string)(int)$totalUnidades : number_format($totalUnidades, 2);
        if ($tipoComanda === 'anular') {
            echo $totalTxt . ' unidad(es) ANULADAS · ' . $n . ' producto(s)';
        } else {
            echo $totalTxt . ' unidad(es) en ' . $n . ' producto(s)';
        }
        ?>
    </div>
</div>

<!-- ========== TOOLBAR ========== -->
<div class="no-print-toolbar no-print">
    <button class="btn-print" onclick="window.print()">Imprimir</button>
    <button class="btn-close" onclick="window.close()">Cerrar</button>
</div>

<script>
window.addEventListener('load', () => {
    setTimeout(() => window.print(), 250);
});
window.addEventListener('afterprint', () => {
    setTimeout(() => window.close(), 300);
});
</script>
</body>
</html>
