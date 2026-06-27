<?php
$activePage = 'historial';
$pageTitle  = 'PUERTO HABANA POS — Historial';
require __DIR__ . '/../template/head.php';
?>
<link rel="stylesheet" href="../public/css/movil.css?v=<?php echo @filemtime(__DIR__ . '/../../public/css/movil.css'); ?>">
<style>
.m-hist-card {
    background: var(--m-white);
    border-radius: var(--m-radius);
    padding: 14px;
    margin-bottom: 10px;
    box-shadow: var(--m-shadow);
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 6px;
}
.m-hist-num { font-size: 16px; font-weight: 800; }
.m-hist-meta { font-size: 11px; color: var(--m-muted); margin-top: 3px; line-height: 1.5; }
.m-hist-meta i { color: var(--m-light); margin-right: 4px; }
.m-hist-total { font-size: 18px; font-weight: 800; color: var(--m-primary); text-align: right; }
.m-hist-metodo {
    font-size: 10px;
    text-transform: uppercase;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 999px;
    margin-top: 4px;
    display: inline-block;
}
.m-hist-metodo.efectivo  { background:#d1fae5;color:#059669; }
.m-hist-metodo.tarjeta   { background:#dbeafe;color:#3b82f6; }
.m-hist-metodo.yape      { background:#ede9fe;color:#5b3df5; }
.m-hist-metodo.transferencia { background:#fef3c7;color:#b45309; }
.m-hist-metodo.mixto     { background:#fce7f3;color:#be185d; }
.m-hist-card button {
    grid-column: 1 / -1;
    margin-top: 8px;
    background: var(--m-bg);
    border: 1px solid var(--m-border);
    border-radius: 10px;
    padding: 8px;
    font-size: 12px;
    font-weight: 700;
    color: var(--m-text);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.m-hist-summary {
    background: linear-gradient(135deg, #5b3df5 0%, #4a2fe0 100%);
    color: #fff;
    border-radius: var(--m-radius);
    padding: 16px;
    margin-bottom: 14px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.m-hist-summary .h-item {
    text-align: center;
}
.m-hist-summary .h-lbl { font-size: 10px; opacity: 0.8; letter-spacing: 0.4px; font-weight: 700; }
.m-hist-summary .h-val { font-size: 20px; font-weight: 800; margin-top: 4px; }
</style>
</head>
<body class="m-no-nav">

<?php require __DIR__ . '/_drawer.php'; ?>

<div class="m-app">
    <header class="m-header">
        <button class="m-burger" onclick="mAbrirDrawer()"><i class="fa-solid fa-bars"></i></button>
        <div class="m-title">Historial <small id="m-hist-sub">Cargando...</small></div>
        <button class="m-action" onclick="mForzarDesktop()"><i class="fa-solid fa-desktop"></i></button>
    </header>

    <main class="m-main">
        <section class="m-tab active">
            <div class="m-hist-summary" id="m-hist-summary"></div>

            <div class="m-chips" id="m-hist-rango">
                <button class="m-chip active" data-rango="">Todos</button>
                <button class="m-chip" data-rango="hoy">Hoy</button>
                <button class="m-chip" data-rango="semana">Semana</button>
                <button class="m-chip" data-rango="mes">Mes</button>
            </div>
            <div class="m-chips" id="m-hist-metodo">
                <button class="m-chip active" data-metodo="">Todos</button>
                <button class="m-chip" data-metodo="efectivo">Efectivo</button>
                <button class="m-chip" data-metodo="tarjeta">Tarjeta</button>
                <button class="m-chip" data-metodo="yape">Yape</button>
                <button class="m-chip" data-metodo="transferencia">Transf.</button>
            </div>

            <div id="m-hist-lista"></div>
        </section>
    </main>
</div>

<script src="scripts/core.js?v=<?php echo @filemtime(__DIR__ . '/../scripts/core.js'); ?>"></script>
<script src="movil/scripts/historial.js?v=<?php echo @filemtime(__DIR__ . '/scripts/historial.js'); ?>"></script>

</body>
</html>
