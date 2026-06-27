<?php
$activePage = 'inventario';
$pageTitle  = 'PUERTO HABANA POS — Inventario';
require __DIR__ . '/../template/head.php';
?>
<link rel="stylesheet" href="../public/css/movil.css?v=<?php echo @filemtime(__DIR__ . '/../../public/css/movil.css'); ?>">
<style>
.m-inv-kpis { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin-bottom:14px; }
.m-inv-kpi { background:var(--m-white); border-radius:var(--m-radius); padding:12px 14px; box-shadow:var(--m-shadow); border-left:5px solid #94a3b8; }
.m-inv-kpi .lbl { font-size:10px; font-weight:700; color:var(--m-muted); letter-spacing:.4px; }
.m-inv-kpi .val { font-size:20px; font-weight:800; margin-top:3px; }
.m-inv-kpi.valor { border-left-color:#0e7c8b; } .m-inv-kpi.valor .val { color:#0e7c8b; }
.m-inv-kpi.bajo  { border-left-color:#f59e0b; } .m-inv-kpi.bajo  .val { color:#d97706; }
.m-inv-kpi.agot  { border-left-color:#ef4444; } .m-inv-kpi.agot  .val { color:#dc2626; }
.m-inv-kpi.total { border-left-color:#6b7280; } .m-inv-kpi.total .val { color:#374151; }

.m-inv-card { background:var(--m-white); border-radius:var(--m-radius); padding:12px 14px; margin-bottom:9px; box-shadow:var(--m-shadow); display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center; }
.m-inv-card > div:first-child { min-width:0; }
.m-inv-nom { font-size:14px; font-weight:800; line-height:1.2; overflow-wrap:anywhere; }
.m-inv-sub { font-size:11px; color:var(--m-muted); margin-top:3px; overflow-wrap:anywhere; }
.m-inv-sub b { color:#0e7c8b; }
.m-inv-stockbox { text-align:center; min-width:64px; }
.m-inv-stock { font-size:24px; font-weight:900; line-height:1; }
.m-inv-stock.verde { color:#059669; } .m-inv-stock.amarillo { color:#d97706; } .m-inv-stock.rojo { color:#dc2626; }
.m-inv-estado { font-size:9.5px; font-weight:800; padding:2px 8px; border-radius:999px; display:inline-block; margin-top:4px; }
.m-inv-estado.verde { background:#ecfdf5;color:#059669; } .m-inv-estado.amarillo { background:#fffbeb;color:#b45309; } .m-inv-estado.rojo { background:#fef2f2;color:#b91c1c; }
.m-inv-min { font-size:10px; color:var(--m-muted); margin-top:2px; }
.m-inv-mov { grid-column:1 / -1; margin-top:4px; background:var(--m-primary-light); color:var(--m-primary); border:1px solid #ddd6fe; border-radius:10px; padding:9px; font-size:12px; font-weight:800; font-family:inherit; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; }
.m-inv-mov:active { transform:scale(0.98); }
</style>
</head>
<body class="m-no-nav">

<?php require __DIR__ . '/_drawer.php'; ?>

<div class="m-app">
    <header class="m-header">
        <button class="m-burger" onclick="mAbrirDrawer()"><i class="fa-solid fa-bars"></i></button>
        <div class="m-title">Inventario <small id="m-inv-sub">Cargando...</small></div>
        <button class="m-action" onclick="mForzarDesktop()"><i class="fa-solid fa-desktop"></i></button>
    </header>

    <main class="m-main">
        <section class="m-tab active">
            <div class="m-inv-kpis">
                <div class="m-inv-kpi total"><div class="lbl">PRODUCTOS</div><div class="val" id="m-inv-total">0</div></div>
                <div class="m-inv-kpi valor"><div class="lbl">VALOR INVENTARIO</div><div class="val" id="m-inv-valor">S/ 0</div></div>
                <div class="m-inv-kpi bajo"><div class="lbl">STOCK BAJO</div><div class="val" id="m-inv-bajo">0</div></div>
                <div class="m-inv-kpi agot"><div class="lbl">AGOTADOS</div><div class="val" id="m-inv-agot">0</div></div>
            </div>

            <div class="m-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="m-inv-search" placeholder="Buscar producto...">
            </div>

            <div id="m-inv-lista"></div>
        </section>
    </main>
</div>

<script src="scripts/core.js?v=<?php echo @filemtime(__DIR__ . '/../scripts/core.js'); ?>"></script>
<script src="movil/scripts/inventario.js?v=<?php echo @filemtime(__DIR__ . '/scripts/inventario.js'); ?>"></script>

</body>
</html>
