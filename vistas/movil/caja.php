<?php
$activePage = 'caja';
$pageTitle  = 'PUERTO HABANA POS — Caja';
require __DIR__ . '/../template/head.php';
?>
<link rel="stylesheet" href="../public/css/movil.css?v=<?php echo @filemtime(__DIR__ . '/../../public/css/movil.css'); ?>">
<style>
.m-kpi-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px; }
.m-kpi {
    background: var(--m-white);
    border-radius: var(--m-radius);
    padding: 14px;
    box-shadow: var(--m-shadow);
    border-left: 4px solid var(--m-primary);
}
.m-kpi.green  { border-left-color: var(--m-green); }
.m-kpi.orange { border-left-color: var(--m-orange); }
.m-kpi.blue   { border-left-color: var(--m-blue); }
.m-kpi-lbl { font-size:10px;color:var(--m-muted);font-weight:700;letter-spacing:0.5px; }
.m-kpi-val { font-size:22px;font-weight:800;margin-top:6px;color:var(--m-text); }
.m-kpi-sub { font-size:11px;color:var(--m-muted);margin-top:2px; }
.m-mov-item {
    background: var(--m-white);
    border-radius:10px;
    padding:10px 12px;
    margin-bottom:6px;
    display:flex;align-items:center;gap:10px;
    box-shadow: var(--m-shadow);
}
.m-mov-icon {
    width:34px;height:34px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;font-size:13px;
}
.m-mov-icon.venta  { background:#d1fae5;color:#059669; }
.m-mov-icon.ingreso{ background:#dbeafe;color:#3b82f6; }
.m-mov-icon.egreso { background:#fee2e2;color:#dc2626; }
.m-mov-info { flex:1;min-width:0; }
.m-mov-nota { font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.m-mov-meta { font-size:11px;color:var(--m-muted); }
.m-mov-monto { font-size:14px;font-weight:800; }
.m-mov-monto.pos { color:var(--m-green); }
.m-mov-monto.neg { color:var(--m-red); }
</style>
</head>
<body class="m-no-nav">

<?php require __DIR__ . '/_drawer.php'; ?>

<div class="m-app">
    <header class="m-header">
        <button class="m-burger" onclick="mAbrirDrawer()"><i class="fa-solid fa-bars"></i></button>
        <div class="m-title">Caja <small id="m-caja-sub">Cargando...</small></div>
        <button class="m-action" onclick="mForzarDesktop()"><i class="fa-solid fa-desktop"></i></button>
    </header>

    <main class="m-main">
        <section class="m-tab active">

            <!-- Estado de la caja -->
            <div id="m-caja-estado"></div>

            <!-- KPIs -->
            <div class="m-kpi-grid" id="m-caja-kpis"></div>

            <!-- Acciones rapidas -->
            <div id="m-caja-acciones" style="display:flex;flex-direction:column;gap:8px;margin-bottom:18px;"></div>

            <!-- Movimientos -->
            <div style="font-size:13px;font-weight:800;color:var(--m-muted);letter-spacing:0.4px;margin-bottom:8px;">MOVIMIENTOS</div>
            <div id="m-caja-movs"></div>
        </section>
    </main>
</div>

<script src="scripts/core.js?v=<?php echo @filemtime(__DIR__ . '/../scripts/core.js'); ?>"></script>
<script src="movil/scripts/caja.js?v=<?php echo @filemtime(__DIR__ . '/scripts/caja.js'); ?>"></script>

</body>
</html>
