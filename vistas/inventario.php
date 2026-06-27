<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/device.php";
requirePermiso('inventario');

// Router móvil: en celular/tablet servir la vista móvil del inventario.
if (!empty($__usarMovil)) {
    require __DIR__ . '/movil/inventario.php';
    exit;
}

$activePage = 'inventario';
$pageTitle  = 'PUERTO HABANA POS — Inventario';
require __DIR__ . '/template/head.php';
?>
<style>
.inv-stats { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:18px; }
@media (max-width:1100px){ .inv-stats{ grid-template-columns:repeat(3,1fr); } }
@media (max-width:680px){ .inv-stats{ grid-template-columns:repeat(2,1fr); } }
.inv-stat { background:var(--bg-white); border-radius:14px; padding:16px; border-left:5px solid #94a3b8; }
.inv-stat .lbl { font-size:11px; font-weight:700; color:var(--text-muted); letter-spacing:.5px; }
.inv-stat .val { font-size:28px; font-weight:800; margin-top:4px; }
.inv-stat.total  { border-left-color:#6b7280; } .inv-stat.total  .val { color:#374151; }
.inv-stat.valor  { border-left-color:#0e7c8b; } .inv-stat.valor  .val { color:#0e7c8b; font-size:24px; }
.inv-stat.ok     { border-left-color:#10b981; } .inv-stat.ok     .val { color:#059669; }
.inv-stat.bajo   { border-left-color:#f59e0b; } .inv-stat.bajo   .val { color:#d97706; }
.inv-stat.agot   { border-left-color:#ef4444; } .inv-stat.agot   .val { color:#dc2626; }

.sem { display:inline-flex; align-items:center; gap:7px; font-weight:700; font-size:12px; padding:4px 12px; border-radius:20px; }
.sem .dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
.sem.verde    { background:#ecfdf5; color:#059669; } .sem.verde    .dot{ background:#10b981; }
.sem.amarillo { background:#fffbeb; color:#b45309; } .sem.amarillo .dot{ background:#f59e0b; }
.sem.rojo     { background:#fef2f2; color:#b91c1c; } .sem.rojo     .dot{ background:#ef4444; animation:pulse 1.4s infinite; }
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.35;} }

.inv-bar { height:8px; background:#eef2f7; border-radius:6px; overflow:hidden; margin-top:6px; min-width:90px; }
.inv-bar span { display:block; height:100%; border-radius:6px; }
.stock-num { font-size:18px; font-weight:800; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Inventario</div>
                <div class="page-subtitle">Control de stock con semáforo, valor en soles y salidas (venta / cortesía)</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <input id="inv-search" class="input-field" placeholder="Buscar producto..." style="max-width:240px;">
                <button class="btn btn-primary" onclick="descargarInventarioExcel()"><i class="fa-solid fa-file-excel"></i> Descargar Excel</button>
            </div>
        </div>

        <div class="page-content">
            <div class="inv-stats">
                <div class="inv-stat total"><div class="lbl">PRODUCTOS CON STOCK</div><div class="val" id="inv-total">0</div></div>
                <div class="inv-stat valor"><div class="lbl">VALOR INVENTARIO</div><div class="val" id="inv-valor">S/ 0.00</div></div>
                <div class="inv-stat ok"><div class="lbl">EN BUEN NIVEL</div><div class="val" id="inv-ok">0</div></div>
                <div class="inv-stat bajo"><div class="lbl">STOCK BAJO</div><div class="val" id="inv-bajo">0</div></div>
                <div class="inv-stat agot"><div class="lbl">AGOTADOS</div><div class="val" id="inv-agot">0</div></div>
            </div>

            <div class="card" style="padding:16px;overflow-x:auto;">
                <table class="data-table" style="width:100%;">
                    <thead><tr>
                        <th>Producto</th><th>Categoría</th><th>Estado</th>
                        <th style="text-align:right;">Stock</th>
                        <th style="text-align:right;">Precio</th>
                        <th style="text-align:right;">Valor (S/)</th>
                        <th style="text-align:center;">Vendido</th>
                        <th style="text-align:center;">Cortesía</th>
                        <th style="text-align:center;">Mín.</th><th></th>
                    </tr></thead>
                    <tbody id="inv-body">
                        <tr><td colspan="10" style="text-align:center;color:var(--text-muted);padding:24px;">Cargando…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<!-- Modal movimiento de stock -->
<div class="modal-overlay" id="modal-mov">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-boxes-stacked" style="color:var(--primary);"></i> <span id="mov-titulo">Ajustar stock</span></div>
            <button class="modal-close" onclick="closeModal('modal-mov')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mov-idprod">
            <div style="font-size:13px;color:var(--text-muted);margin-bottom:10px;">
                Producto: <b id="mov-prod-nombre" style="color:var(--text-dark);"></b><br>
                Stock actual: <b id="mov-stock-actual" style="color:var(--primary);"></b>
            </div>
            <div class="input-group">
                <label class="input-label">Operación</label>
                <select id="mov-tipo" class="input-field">
                    <option value="entrada">Entrada (sumar — compra/ingreso)</option>
                    <option value="salida">Salida (restar — merma/consumo)</option>
                    <option value="ajuste">Ajuste (sumar/restar corrección)</option>
                </select>
            </div>
            <div class="input-group">
                <label class="input-label">Cantidad</label>
                <input type="number" id="mov-cant" class="input-field" step="1" min="0" placeholder="0">
            </div>
            <div class="input-group">
                <label class="input-label">Nota (opcional)</label>
                <input type="text" id="mov-nota" class="input-field" maxlength="200" placeholder="Ej. compra proveedor, conteo físico…">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-mov')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarMovimiento()">Aplicar</button>
        </div>
    </div>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/inventario.js?v=<?php echo filemtime(__DIR__ . '/scripts/inventario.js'); ?>"></script>
</body>
</html>
