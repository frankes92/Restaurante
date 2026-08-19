<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/device.php";
requirePermiso('historial');

if (!empty($__usarMovil)) {
    require __DIR__ . '/movil/historial.php';
    exit;
}

$activePage = 'historial';
$pageTitle  = 'PUERTO HABANA POS — Historial';
require __DIR__ . '/template/head.php';
?>
<style>
.filter-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-pill { padding: 8px 16px; border-radius: 20px; background: var(--bg-white); border: 1px solid var(--border); cursor: pointer; font-size: 12px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 6px; font-family: inherit; }
.filter-pill.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.pay-icon { width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; }
.filter-date { padding: 7px 10px; border-radius: 10px; border: 1px solid var(--border); background: var(--bg-white); font-family: inherit; font-size: 12px; color: var(--text-dark); }
.filter-date:focus { outline: none; border-color: var(--primary); }
.filter-label { font-size: 11px; font-weight: 700; color: var(--text-muted); letter-spacing: .5px; align-self: center; }
tr.month-group-row td { background: var(--primary-light); color: var(--primary); font-weight: 700; font-size: 12px; letter-spacing: .5px; text-transform: uppercase; padding: 10px 14px !important; border-top: 1px solid var(--border); }
tr.month-group-row .mg-meta { float: right; font-weight: 600; text-transform: none; letter-spacing: 0; color: var(--text-muted); }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Historial de comprobantes</div>
                <div class="page-subtitle">Tickets, notas de venta, boletas y facturas emitidas — vuelve a imprimir cuando lo necesites</div>
            </div>
            <button class="btn" onclick="exportCSV()"><i class="fa-solid fa-download"></i> Exportar CSV</button>
        </div>

        <div class="page-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--primary-light);color:var(--primary);"><i class="fa-solid fa-receipt"></i></div>
                    <div class="stat-label">ÓRDENES TOTALES</div>
                    <div class="stat-value" id="s-count">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--green-light);color:var(--green);"><i class="fa-solid fa-money-bill-wave"></i></div>
                    <div class="stat-label">VENTAS TOTALES</div>
                    <div class="stat-value" id="s-total">S/ 0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fef3c7;color:var(--orange);"><i class="fa-solid fa-chart-line"></i></div>
                    <div class="stat-label">TICKET PROMEDIO</div>
                    <div class="stat-value" id="s-avg">S/ 0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#dbeafe;color:var(--blue);"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="stat-label">VENTAS HOY</div>
                    <div class="stat-value" id="s-today">S/ 0</div>
                </div>
            </div>

            <div class="filter-row">
                <button class="filter-pill active" data-filter="all">Todos</button>
                <button class="filter-pill" data-filter="hoy">Hoy</button>
                <button class="filter-pill" data-filter="semana">Esta semana</button>
                <button class="filter-pill" data-filter="mes">Este mes</button>
            </div>
            <div class="filter-row">
                <span style="font-size:11px;font-weight:700;color:var(--text-muted);letter-spacing:.5px;align-self:center;">COMPROBANTE:</span>
                <button class="filter-pill active" data-comp="">Todos</button>
                <button class="filter-pill" data-comp="ticket"><i class="fa-solid fa-receipt"></i> Ticket</button>
                <button class="filter-pill" data-comp="nota_venta"><i class="fa-regular fa-file-lines"></i> Nota Venta</button>
                <button class="filter-pill" data-comp="boleta"><i class="fa-regular fa-file"></i> Boleta</button>
                <button class="filter-pill" data-comp="factura"><i class="fa-solid fa-file-invoice"></i> Factura</button>
            </div>
            <div class="filter-row">
                <span class="filter-label">RANGO DE FECHAS:</span>
                <label class="filter-label" style="font-weight:600;" for="f-desde">Desde</label>
                <input type="date" id="f-desde" class="filter-date">
                <label class="filter-label" style="font-weight:600;" for="f-hasta">Hasta</label>
                <input type="date" id="f-hasta" class="filter-date">
                <button class="filter-pill" id="btn-limpiar-fechas" type="button"><i class="fa-solid fa-eraser"></i> Limpiar</button>
            </div>

            <div class="table-container" style="padding:16px;">
                <table id="tbl-historial" class="data-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>#Orden</th>
                            <th>Fecha</th>
                            <th>Comprobante</th>
                            <th>Tipo</th>
                            <th>Mesa</th>
                            <th>Items</th>
                            <th>Método</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/historial.js?v=<?php echo filemtime(__DIR__ . '/scripts/historial.js'); ?>"></script>
</body>
</html>
