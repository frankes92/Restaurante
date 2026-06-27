<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('reportes');
$activePage = 'reportes';
$pageTitle  = 'PUERTO HABANA POS — Reportes';
require __DIR__ . '/template/head.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.reports-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
@media (max-width: 1100px) { .reports-grid { grid-template-columns: 1fr; } }
.chart-container { position: relative; height: 280px; }
.top-product { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
.top-product:last-child { border-bottom: 0; }
.tp-rank { width: 28px; height: 28px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.tp-img { width: 38px; height: 38px; border-radius: 8px; background-size: cover; background-position: center; flex-shrink: 0; }
.tp-info { flex: 1; min-width: 0; }
.tp-name { font-size: 13px; font-weight: 600; }
.tp-qty { font-size: 11px; color: var(--text-muted); }
.tp-total { font-size: 13px; font-weight: 700; color: var(--primary); }
.filter-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-pill { padding: 8px 16px; border-radius: 20px; background: var(--bg-white); border: 1px solid var(--border); cursor: pointer; font-size: 12px; font-weight: 600; color: var(--text-dark); font-family: inherit; }
.filter-pill.active { background: var(--primary); color: #fff; border-color: var(--primary); }
/* Detalle de ventas: encabezado fijo + filas agrupadas/desglose */
#dp-scroll { overflow:auto; max-height: 65vh; }
#tbl-detalle-prod thead th { position: sticky; top: 0; background: var(--bg-light, #f3f4f6); z-index: 3; }
#tbl-detalle-prod .dp-fecha-grupo td { background: #eef2ff; color: #4338ca; font-weight: 700; padding: 8px 12px !important; letter-spacing: .3px; }
#tbl-detalle-prod .dp-prod-row:hover { background: #f9fafb; }
#tbl-detalle-prod .dp-caret { transition: transform .15s; font-size: 11px; color: var(--text-muted); margin-right: 6px; }
#tbl-detalle-prod .dp-pres-row td { background: #fafafa; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Reportes</div>
                <div class="page-subtitle">Análisis y métricas del negocio</div>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-primary" onclick="descargarExcel()"><i class="fa-solid fa-file-excel"></i> Descargar Excel</button>
            </div>
        </div>

        <div class="page-content">
            <?php if (!empty($__topeRus) && in_array($__topeRus['estado'], ['cerca','excedido'], true)):
                $tr = $__topeRus;
                $esExc = $tr['estado'] === 'excedido';
                $bg  = $esExc ? '#fee2e2' : '#fef3c7';
                $bd  = $esExc ? '#ef4444' : '#f59e0b';
                $tx  = $esExc ? '#991b1b' : '#92400e';
            ?>
            <div style="background:<?php echo $bg; ?>;border:1px solid <?php echo $bd; ?>;border-radius:12px;padding:14px 18px;margin-bottom:18px;color:<?php echo $tx; ?>;">
                <div style="display:flex;align-items:center;gap:10px;font-weight:700;font-size:14px;">
                    <i class="fa-solid fa-<?php echo $esExc ? 'circle-exclamation' : 'triangle-exclamation'; ?>"></i>
                    <?php if ($esExc): ?>
                        Tope Nuevo RUS superado este mes (<?php echo $tr['mes']; ?>)
                    <?php else: ?>
                        Estás por llegar al tope del Nuevo RUS (<?php echo $tr['mes']; ?>)
                    <?php endif; ?>
                </div>
                <div style="font-size:13px;margin-top:6px;font-weight:500;line-height:1.5;">
                    Boletas electrónicas del mes: <b>S/ <?php echo number_format($tr['monto'], 2); ?></b>
                    de <b>S/ <?php echo number_format($tr['limite'], 0); ?></b>
                    (<?php echo $tr['porcentaje']; ?>%).
                    <?php if ($esExc): ?>
                        Superaste el tope de la Categoría 1; este mes la cuota sube de
                        <b>S/ <?php echo number_format($tr['cuota1'], 0); ?></b> a
                        <b>S/ <?php echo number_format($tr['cuota2'], 0); ?></b>
                        (Categoría 2, válida hasta S/ <?php echo number_format($tr['limite_max'], 0); ?>).
                    <?php else: ?>
                        Te faltan <b>S/ <?php echo number_format($tr['restante'], 2); ?></b> para el límite.
                        Si lo superas, la cuota de este mes sube de
                        <b>S/ <?php echo number_format($tr['cuota1'], 0); ?></b> a
                        <b>S/ <?php echo number_format($tr['cuota2'], 0); ?></b>.
                    <?php endif; ?>
                    <br><span style="opacity:.85;">Puedes seguir emitiendo comprobantes con normalidad; esto es solo un recordatorio tributario.</span>
                </div>
            </div>
            <?php endif; ?>
            <div class="filter-row">
                <button class="filter-pill active" data-range="hoy">Hoy</button>
                <button class="filter-pill" data-range="semana">Últimos 7 días</button>
                <button class="filter-pill" data-range="mes">Este mes</button>
                <button class="filter-pill" data-range="all">Todo el tiempo</button>

                <div style="display:flex;align-items:center;gap:6px;margin-left:auto;flex-wrap:wrap;background:var(--bg-white);border:1px solid var(--border);border-radius:20px;padding:4px 10px;">
                    <i class="fa-regular fa-calendar" style="color:var(--primary);font-size:13px;"></i>
                    <span style="font-size:12px;color:var(--text-muted);">Desde</span>
                    <input type="date" id="f-desde" class="input-field" style="height:32px;font-size:12px;padding:2px 8px;width:140px;">
                    <span style="font-size:12px;color:var(--text-muted);">Hasta</span>
                    <input type="date" id="f-hasta" class="input-field" style="height:32px;font-size:12px;padding:2px 8px;width:140px;">
                    <button class="btn btn-sm btn-primary" onclick="aplicarRangoPersonalizado()"><i class="fa-solid fa-filter"></i> Aplicar</button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--green-light);color:var(--green);"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                    <div class="stat-label">VENTAS TOTALES</div>
                    <div class="stat-value" id="r-sales">S/ 0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--primary-light);color:var(--primary);"><i class="fa-solid fa-receipt"></i></div>
                    <div class="stat-label">ÓRDENES</div>
                    <div class="stat-value" id="r-orders">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fef3c7;color:var(--orange);"><i class="fa-solid fa-chart-pie"></i></div>
                    <div class="stat-label">TICKET PROMEDIO</div>
                    <div class="stat-value" id="r-avg">S/ 0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#dbeafe;color:var(--blue);"><i class="fa-solid fa-utensils"></i></div>
                    <div class="stat-label">PRODUCTOS VENDIDOS</div>
                    <div class="stat-value" id="r-items">0</div>
                </div>
            </div>

            <!-- TARJETAS POR TIPO DE COMPROBANTE -->
            <div class="card-title" style="margin-top:24px;margin-bottom:10px;">Ventas por tipo de comprobante</div>
            <div class="stats-grid">
                <div class="stat-card" style="border-left:4px solid #f59e0b;">
                    <div class="stat-icon" style="background:#fef3c7;color:#f59e0b;"><i class="fa-regular fa-file-lines"></i></div>
                    <div class="stat-label">NOTA DE VENTA</div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;">
                        <div class="stat-value" id="r-cnt-nv" style="font-size:22px;">0</div>
                        <div id="r-tot-nv" style="font-size:14px;color:#f59e0b;font-weight:600;">S/ 0</div>
                    </div>
                </div>
                <div class="stat-card" style="border-left:4px solid #10b981;">
                    <div class="stat-icon" style="background:#d1fae5;color:#10b981;"><i class="fa-regular fa-file"></i></div>
                    <div class="stat-label">BOLETA ELECTRÓNICA</div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;">
                        <div class="stat-value" id="r-cnt-boleta" style="font-size:22px;">0</div>
                        <div id="r-tot-boleta" style="font-size:14px;color:#10b981;font-weight:600;">S/ 0</div>
                    </div>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:4px;" id="r-sunat-boleta">SUNAT: —</div>
                </div>
                <div class="stat-card" style="border-left:4px solid #3b82f6;">
                    <div class="stat-icon" style="background:#dbeafe;color:#3b82f6;"><i class="fa-solid fa-file-invoice"></i></div>
                    <div class="stat-label">FACTURA ELECTRÓNICA</div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;">
                        <div class="stat-value" id="r-cnt-factura" style="font-size:22px;">0</div>
                        <div id="r-tot-factura" style="font-size:14px;color:#3b82f6;font-weight:600;">S/ 0</div>
                    </div>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:4px;" id="r-sunat-factura">SUNAT: —</div>
                </div>
            </div>

            <div class="reports-grid">
                <div class="card">
                    <div class="card-title">Ventas por día</div>
                    <div class="chart-container"><canvas id="sales-chart"></canvas></div>
                </div>
                <div class="card">
                    <div class="card-title">Métodos de pago</div>
                    <div class="chart-container"><canvas id="pay-chart"></canvas></div>
                </div>
            </div>

            <div class="reports-grid">
                <div class="card">
                    <div class="card-title">Productos más vendidos</div>
                    <div id="top-products"></div>
                </div>
                <div class="card">
                    <div class="card-title">Tipo de orden</div>
                    <div class="chart-container"><canvas id="type-chart"></canvas></div>
                </div>
            </div>

            <!-- Ventas por zona -->
            <div class="reports-grid" style="margin-top:8px;">
                <div class="card">
                    <div class="card-title"><i class="fa-solid fa-map-location-dot" style="color:var(--primary);"></i> Ventas por zona</div>
                    <div id="ventas-por-zona"></div>
                </div>
                <div class="card">
                    <div class="card-title">Distribución por zona</div>
                    <div class="chart-container"><canvas id="zona-chart"></canvas></div>
                </div>
            </div>

            <!-- DETALLE POR PRODUCTO -->
            <div class="card" style="margin-top:8px;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:6px;">
                    <div class="card-title" style="margin:0;"><i class="fa-solid fa-list-check" style="color:var(--primary);"></i> Detalle de ventas</div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input id="dp-buscar" class="input-field" placeholder="Buscar producto o presentación..." style="max-width:240px;height:34px;font-size:13px;">
                        <span id="dp-resumen" style="font-size:12px;color:var(--text-muted);"></span>
                    </div>
                </div>
                <div id="dp-scroll">
                    <table class="data-table" style="width:100%;" id="tbl-detalle-prod">
                        <thead><tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th style="text-align:center;">Vendidos</th>
                            <th style="text-align:right;">Ingreso</th>
                            <th style="text-align:center;">Cortesías</th>
                            <th style="text-align:center;">Stock actual</th>
                        </tr></thead>
                        <tbody id="detalle-prod-body"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/reportes.js?v=<?php echo filemtime(__DIR__ . '/scripts/reportes.js'); ?>"></script>
</body>
</html>
