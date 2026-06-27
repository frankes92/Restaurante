<?php
// Vista movil de Mesas - se incluye desde vistas/mesas.php cuando $__usarMovil
$activePage = 'mesas';
$pageTitle  = 'PUERTO HABANA POS — Mesas';
require __DIR__ . '/../template/head.php';
?>
<link rel="stylesheet" href="../public/css/movil.css?v=<?php echo @filemtime(__DIR__ . '/../../public/css/movil.css'); ?>">
</head>
<body class="m-no-nav">

<?php require __DIR__ . '/_drawer.php'; ?>

<div class="m-app">
    <header class="m-header">
        <button class="m-burger" onclick="mAbrirDrawer()"><i class="fa-solid fa-bars"></i></button>
        <div class="m-title">Mesas <small id="m-mesas-sub">Cargando...</small></div>
        <button class="m-action" onclick="mForzarDesktop()" title="Versión escritorio"><i class="fa-solid fa-desktop"></i></button>
    </header>

    <main class="m-main">
        <section class="m-tab active" data-tab="mesas">

            <!-- Stats arriba -->
            <div id="m-mesas-stats" style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:14px;"></div>

            <!-- Filtro de zonas -->
            <div class="m-chips" id="m-zonas-chips"></div>

            <!-- Grid de mesas -->
            <div class="m-mesa-grid" id="m-mesas-grid"></div>

            <div class="m-legend">
                <div class="m-legend-item"><span class="swatch" style="background:#bbf7d0;border-color:#059669;"></span>Libre</div>
                <div class="m-legend-item"><span class="swatch" style="background:#fcd34d;border-color:#b45309;"></span>Ocupada</div>
                <div class="m-legend-item"><span class="swatch" style="background:#93c5fd;border-color:#3b82f6;"></span>En cuenta</div>
                <div class="m-legend-item"><span class="swatch" style="background:#c4b5fd;border-color:#5b3df5;"></span>Reservada</div>
            </div>
        </section>
    </main>
</div>

<script src="scripts/core.js?v=<?php echo @filemtime(__DIR__ . '/../scripts/core.js'); ?>"></script>
<script src="movil/scripts/mesas.js?v=<?php echo @filemtime(__DIR__ . '/scripts/mesas.js'); ?>"></script>

</body>
</html>
