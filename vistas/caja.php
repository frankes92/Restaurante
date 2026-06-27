<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/device.php";
requirePermiso('caja');

if (!empty($__usarMovil)) {
    require __DIR__ . '/movil/caja.php';
    exit;
}

$activePage = 'caja';
$pageTitle  = 'PUERTO HABANA POS — Caja';
require __DIR__ . '/template/head.php';
?>
<style>
.caja-grid { display: grid; grid-template-columns: 1fr 360px; gap: 20px; }
@media (max-width: 1100px) { .caja-grid { grid-template-columns: 1fr; } }
.session-card { background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%); color: #fff; border-radius: 14px; padding: 22px; }
.session-card .label { font-size: 11px; opacity: 0.85; font-weight: 600; letter-spacing: 0.6px; }
.session-card .value { font-size: 26px; font-weight: 700; margin-top: 4px; }
.pay-summary { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px; }
.pay-row { display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: var(--bg-white); border: 1px solid var(--border); border-radius: 12px; }
.pay-row-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
.pay-row-label { font-size: 12px; color: var(--text-muted); font-weight: 600; }
.pay-row-value { font-size: 16px; font-weight: 700; margin-top: 2px; }
.move-row { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #f3f4f6; }
.move-row:last-child { border-bottom: 0; }
.move-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; }
.move-info { flex: 1; }
.move-note { font-size: 13px; font-weight: 600; }
.move-time { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.move-amount { font-weight: 700; font-size: 14px; }

/* Arqueo */
.arq-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.arq-grid .denom { display: grid; grid-template-columns: 90px 1fr 110px; gap: 6px; align-items: center; padding: 6px 8px; border: 1px solid var(--border); border-radius: 8px; }
.arq-grid .denom label { font-size: 12px; font-weight: 600; }
.arq-grid .denom input { width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px; outline: 0; }
.arq-grid .denom .sub { text-align: right; font-size: 12px; font-weight: 700; color: var(--primary); }
.arq-resumen { background: var(--bg-light); border-radius: 10px; padding: 12px 14px; margin-top: 12px; font-size: 13px; }
.arq-resumen .row { display: flex; justify-content: space-between; padding: 4px 0; }
.arq-resumen .total { font-size: 16px; font-weight: 700; padding-top: 6px; border-top: 1px solid var(--border); margin-top: 6px; }
.arq-resumen .diff-pos { color: var(--green); }
.arq-resumen .diff-neg { color: var(--red); }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Caja</div>
                <div class="page-subtitle" id="cash-subtitle">Sesión actual de caja</div>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn" onclick="addMovementModal('ingreso')"><i class="fa-solid fa-arrow-down" style="color:var(--green);"></i> Ingreso</button>
                <button class="btn" onclick="addMovementModal('egreso')"><i class="fa-solid fa-arrow-up" style="color:var(--red);"></i> Egreso</button>
                <button class="btn" data-perm="arqueo_caja" onclick="abrirArqueo(false)"><i class="fa-solid fa-calculator"></i> Arqueo</button>
                <button class="btn" data-perm="arqueo_caja" onclick="reimprimirArqueo()" title="Reimprimir el último arqueo"><i class="fa-solid fa-print"></i> Reimprimir</button>
                <button class="btn btn-danger" data-perm="arqueo_caja" onclick="abrirArqueo(true)"><i class="fa-solid fa-lock"></i> Cerrar Caja</button>
            </div>
        </div>

        <div class="page-content">
            <div class="caja-grid">
                <div>
                    <div class="session-card">
                        <div class="label">SALDO ACTUAL EN CAJA</div>
                        <div class="value" id="cash-balance">S/ 0.00</div>
                        <div style="display:flex;gap:24px;margin-top:14px;">
                            <div><div class="label">APERTURA</div><div style="font-size:14px;font-weight:600;margin-top:2px;" id="cash-initial">S/ 0.00</div></div>
                            <div><div class="label">VENTAS</div><div style="font-size:14px;font-weight:600;margin-top:2px;" id="cash-sales">S/ 0.00</div></div>
                            <div><div class="label">INGRESOS</div><div style="font-size:14px;font-weight:600;margin-top:2px;" id="cash-income">S/ 0.00</div></div>
                            <div><div class="label">EGRESOS</div><div style="font-size:14px;font-weight:600;margin-top:2px;" id="cash-expense">S/ 0.00</div></div>
                        </div>
                    </div>

                    <div style="margin-top:20px;">
                        <div class="card-title" style="margin-bottom:12px;">Ventas por método de pago</div>
                        <div class="pay-summary">
                            <div class="pay-row"><div class="pay-row-icon" style="background:var(--green-light);color:var(--green);"><i class="fa-solid fa-money-bill"></i></div><div><div class="pay-row-label">EFECTIVO</div><div class="pay-row-value" id="pay-efectivo">S/ 0.00</div></div></div>
                            <div class="pay-row"><div class="pay-row-icon" style="background:#dbeafe;color:var(--blue);"><i class="fa-regular fa-credit-card"></i></div><div><div class="pay-row-label">TARJETA</div><div class="pay-row-value" id="pay-tarjeta">S/ 0.00</div></div></div>
                            <div class="pay-row"><div class="pay-row-icon" style="background:#f3e8fc;color:#742384;"><i class="fa-solid fa-mobile-screen"></i></div><div><div class="pay-row-label">YAPE</div><div class="pay-row-value" id="pay-yape">S/ 0.00</div></div></div>
                            <div class="pay-row"><div class="pay-row-icon" style="background:#e0f7fa;color:#00bcd4;"><i class="fa-solid fa-mobile-screen"></i></div><div><div class="pay-row-label">PLIN</div><div class="pay-row-value" id="pay-plin">S/ 0.00</div></div></div>
                            <div class="pay-row"><div class="pay-row-icon" style="background:#fef3c7;color:var(--orange);"><i class="fa-solid fa-building-columns"></i></div><div><div class="pay-row-label">TRANSFERENCIA</div><div class="pay-row-value" id="pay-transferencia">S/ 0.00</div></div></div>
                        </div>
                    </div>
                </div>

                <div class="card" style="padding:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                        <div style="font-size:14px;font-weight:700;">Movimientos</div>
                    </div>
                    <table id="tbl-movimientos" class="data-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Método</th>
                                <th>Monto</th>
                                <th>Nota</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<!-- Modal: ingreso/egreso -->
<div class="modal-overlay" id="modal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-title">Nuevo Movimiento</div>
            <button class="modal-close" onclick="closeModal('modal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="input-group">
                <label class="input-label">Monto (S/)</label>
                <input type="number" id="m-amount" class="input-field" placeholder="0.00" step="0.01">
            </div>
            <div class="input-group">
                <label class="input-label">Concepto / Nota</label>
                <input id="m-note" class="input-field" placeholder="Ej. Compra de insumos">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal')">Cancelar</button>
            <button class="btn btn-primary" onclick="saveMovement()">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal: arqueo -->
<div class="modal-overlay" id="modal-arqueo">
    <div class="modal" style="max-width: 560px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-calculator" style="color:var(--primary);"></i> <span id="arqueo-title">Arqueo de Caja</span></div>
            <button class="modal-close" onclick="closeModal('modal-arqueo')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <!-- Resumen de la sesion -->
            <div style="background:var(--bg-light);border-radius:12px;padding:14px 16px;margin-bottom:14px;">
                <div style="font-size:10px;font-weight:700;color:var(--text-muted);letter-spacing:0.8px;margin-bottom:8px;">RESUMEN DE LA SESIÓN</div>
                <div style="font-size:12px;color:var(--text-dark);margin-bottom:8px;" id="arq-info-sesion">—</div>
                <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-muted);padding:3px 0;"><span>Monto inicial</span><span id="arq-inicial" style="color:var(--text-dark);font-weight:600;">S/ 0.00</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-muted);padding:3px 0;"><span>Ventas en efectivo</span><span id="arq-vefec" style="color:var(--text-dark);font-weight:600;">S/ 0.00</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-muted);padding:3px 0;"><span>Otros ingresos</span><span id="arq-ingr" style="color:var(--text-dark);font-weight:600;">S/ 0.00</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-muted);padding:3px 0;"><span>Egresos</span><span id="arq-egr" style="color:var(--text-dark);font-weight:600;">- S/ 0.00</span></div>
                <div style="border-top:1px dashed var(--border);margin:8px 0;"></div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;font-weight:700;">💰 Esperado en caja</span>
                    <span id="arq-esperado" style="font-size:22px;font-weight:800;color:var(--primary);">S/ 0.00</span>
                </div>
            </div>

            <!-- Monto contado (un solo input) -->
            <div style="background:#fff;border:2px solid var(--primary);border-radius:12px;padding:14px 16px;margin-bottom:14px;">
                <label class="input-label" style="font-weight:700;color:var(--primary);">MONTO REAL CONTADO (S/)</label>
                <input type="number" id="arq-contado-input" class="input-field" step="0.10" placeholder="0.00" style="font-size:24px;font-weight:700;text-align:right;padding:12px 14px;margin-top:6px;" oninput="recalcularArqueo()">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                    <span style="font-size:12px;color:var(--text-muted);font-weight:600;">Diferencia:</span>
                    <span id="arq-diff" style="font-size:15px;font-weight:700;">—</span>
                </div>
            </div>

            <div class="input-group">
                <label class="input-label">Observación (opcional)</label>
                <input id="arq-obs" class="input-field" placeholder="Ej. Faltante por error de vuelto">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-arqueo')">Cancelar</button>
            <button class="btn btn-primary" id="btn-arqueo-confirm" onclick="confirmarArqueo()"><i class="fa-solid fa-print"></i> <span id="btn-arqueo-label">Guardar Arqueo</span></button>
        </div>
    </div>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/caja.js?v=<?php echo filemtime(__DIR__ . '/scripts/caja.js'); ?>"></script>
</body>
</html>
