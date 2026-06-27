<?php
$activePage = 'pedidos';
$pageTitle  = 'PUERTO HABANA POS — Pedidos';
require __DIR__ . '/../template/head.php';
?>
<link rel="stylesheet" href="../public/css/movil.css?v=<?php echo @filemtime(__DIR__ . '/../../public/css/movil.css'); ?>">
<style>
.m-order-card {
    background: var(--m-white);
    border-radius: var(--m-radius);
    box-shadow: var(--m-shadow);
    padding: 14px;
    margin-bottom: 10px;
    border-left: 4px solid var(--m-border);
}
.m-order-card.en_curso  { border-left-color: #94a3b8; }
.m-order-card.enviada   { border-left-color: var(--m-orange); }
.m-order-card.pagada    { border-left-color: var(--m-green); }
.m-order-card.anulada   { border-left-color: var(--m-red); }
.m-order-head { display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:8px; }
.m-order-id { font-size:16px;font-weight:800; }
.m-order-id small { display:block;font-size:11px;font-weight:500;color:var(--m-muted);margin-top:2px; }
.m-order-state {
    font-size: 10px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 999px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    flex-shrink: 0;
}
.m-order-state.en_curso { background:#e2e8f0;color:#475569; }
.m-order-state.enviada  { background:#fed7aa;color:#9a3412; }
.m-order-state.pagada   { background:#bbf7d0;color:#166534; }
.m-order-state.anulada  { background:#fecaca;color:#991b1b; }
.m-order-meta { display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--m-muted);margin-bottom:10px; }
.m-order-total { font-size:18px;font-weight:800;color:var(--m-primary); }
.m-order-actions { display:flex;gap:6px;margin-top:10px; }
.m-order-actions button {
    flex:1;
    background:var(--m-bg);
    border:1px solid var(--m-border);
    border-radius:10px;
    padding:9px 8px;
    font-size:12px;
    font-weight:700;
    color:var(--m-text);
    display:flex;align-items:center;justify-content:center;gap:5px;
}
.m-order-actions button.primary { background:var(--m-primary);color:#fff;border-color:var(--m-primary); }
.m-order-actions button.success { background:var(--m-green);color:#fff;border-color:var(--m-green); }
</style>
</head>
<body class="m-no-nav">

<?php require __DIR__ . '/_drawer.php'; ?>

<div class="m-app">
    <header class="m-header">
        <button class="m-burger" onclick="mAbrirDrawer()"><i class="fa-solid fa-bars"></i></button>
        <div class="m-title">Pedidos <small id="m-ped-sub">Cargando...</small></div>
        <button class="m-action" onclick="mForzarDesktop()"><i class="fa-solid fa-desktop"></i></button>
    </header>

    <main class="m-main">
        <section class="m-tab active">
            <div class="m-chips" id="m-ped-filtros">
                <button class="m-chip active" data-est="">Todos</button>
                <button class="m-chip" data-est="en_curso">En curso</button>
                <button class="m-chip" data-est="enviada">En cocina</button>
                <button class="m-chip" data-est="pagada">Pagadas</button>
            </div>
            <div style="display:flex;align-items:center;gap:6px;padding:0 12px 10px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:135px;">
                    <span style="font-size:11px;color:var(--m-muted);font-weight:700;">Desde</span>
                    <input type="date" id="m-ped-desde" style="flex:1;padding:8px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;background:#fff;">
                </div>
                <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:135px;">
                    <span style="font-size:11px;color:var(--m-muted);font-weight:700;">Hasta</span>
                    <input type="date" id="m-ped-hasta" style="flex:1;padding:8px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;background:#fff;">
                </div>
                <button class="m-chip" id="m-ped-hoy" type="button">Hoy</button>
            </div>
            <div id="m-ped-lista"></div>
        </section>
    </main>
</div>

<script src="scripts/core.js?v=<?php echo @filemtime(__DIR__ . '/../scripts/core.js'); ?>"></script>
<script src="movil/scripts/pedidos.js?v=<?php echo @filemtime(__DIR__ . '/scripts/pedidos.js'); ?>"></script>

</body>
</html>
