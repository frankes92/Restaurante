<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/device.php";
requirePermiso('nuevaorden');

// Router movil: si el dispositivo es movil/tablet, servir la vista movil y salir.
if (!empty($__usarMovil)) {
    require __DIR__ . '/movil/nuevaorden.php';
    exit;
}

$activePage = 'nuevaorden';
$pageTitle  = 'PUERTO HABANA POS — Nueva Orden';
require __DIR__ . '/template/head.php';
?>
<style>
.main { display: grid; grid-template-columns: 1fr 360px; grid-template-rows: auto 1fr auto; background: var(--bg-light); overflow: hidden; height: 100%; min-height: 0; }
.topbar { grid-column: 1 / 2; background: var(--bg-white); padding: 18px 24px; display: flex; align-items: center; gap: 16px; border-bottom: 1px solid var(--border); }
.order-title { display: flex; flex-direction: column; gap: 2px; }
.order-title .label { font-size: 11px; color: var(--text-muted); font-weight: 600; letter-spacing: 0.8px; }
.order-title .title { font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.persons-badge { background: #f3f4f6; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; color: #4b5563; display: flex; align-items: center; gap: 6px; }
.topbar .search-box { flex: 1; max-width: 360px; margin-left: auto; }

.content { grid-column: 1 / 2; display: flex; flex-direction: column; overflow: hidden; padding: 18px 24px 0; gap: 14px; }
.categories { display: flex; flex-direction: row; gap: 8px; overflow-x: auto; overflow-y: hidden; padding: 4px 4px 8px; flex-shrink: 0; }
.categories::-webkit-scrollbar { height: 6px; }
.categories::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
.categories-label { display: none; }
.cat-item { flex-shrink: 0; display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; background: var(--bg-white); border: 1px solid var(--border); border-radius: 999px; cursor: pointer; font-size: 13px; font-weight: 500; color: var(--text-dark); transition: all 0.15s; white-space: nowrap; }
.cat-item i { width: 16px; text-align: center; }
.cat-item:hover { border-color: var(--primary); }
.cat-item.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 12px rgba(91, 61, 245, 0.25); }
.cat-item.active i { color: #fff !important; }

.products-area { display: flex; flex-direction: column; gap: 16px; overflow: hidden; }
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(135px, 1fr)); gap: 8px; overflow-y: auto; padding-bottom: 14px; padding-right: 4px; align-content: start; align-items: stretch; grid-auto-rows: max-content; }
.product-card { background: var(--bg-white); border-radius: 8px; overflow: hidden; border: 1px solid var(--border); transition: all 0.15s; cursor: pointer; display: flex; flex-direction: column; box-shadow: 0 1px 2px rgba(0,0,0,0.03); height: fit-content; }
.product-card:hover { transform: translateY(-2px); box-shadow: 0 5px 12px rgba(0,0,0,0.08); border-color: var(--primary-light); }
.product-img { display: block; width: 100%; aspect-ratio: 1 / 1; background-size: cover; background-position: center; background-repeat: no-repeat; background-color: #f3f4f6; flex-shrink: 0; }
.product-info { padding: 6px 8px 7px; display: flex; align-items: center; justify-content: space-between; gap: 5px; flex-shrink: 0; }
.product-info > div:first-child { min-width: 0; flex: 1; }
.product-name { font-size: 11px; font-weight: 700; color: var(--text-dark); margin-bottom: 2px; line-height: 1.25; white-space: normal; overflow-wrap: anywhere; word-break: break-word; }
.product-price { font-size: 11px; font-weight: 700; color: var(--text-dark); }
.product-add { width: 22px; height: 22px; border-radius: 50%; background: var(--primary); color: #fff; border: 0; cursor: pointer; font-size: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(91, 61, 245, 0.3); flex-shrink: 0; }
.product-add:hover { background: var(--primary-hover); }

.bottom-panel { grid-column: 1 / 2; background: var(--bg-white); border-top: 1px solid var(--border); padding: 10px 24px; display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; align-items: start; transition: padding .2s; }
.bottom-panel.mesas-collapsed { padding: 6px 24px; }
.bottom-panel.mesas-collapsed #mesas-collapse { display: none; }
.btn-toggle-mesas { background: transparent; border: 0; cursor: pointer; color: var(--text-muted); padding: 2px 6px; border-radius: 4px; font-size: 11px; transition: background .15s; }
.btn-toggle-mesas:hover { background: var(--bg-light); color: var(--primary); }
.btn-toggle-mesas i { transition: transform .25s; }
.bottom-panel.mesas-collapsed .btn-toggle-mesas i { transform: rotate(-90deg); }
.panel-label { font-size: 10px; font-weight: 700; color: var(--text-muted); letter-spacing: 0.8px; margin-bottom: 6px; }
.zonas-tabs { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 6px; }
.zonas-tabs .ztab { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border: 1px solid var(--border); border-radius: 999px; background: var(--bg-white); cursor: pointer; font-family: inherit; font-size: 10px; font-weight: 600; color: var(--text-dark); transition: all 0.15s; }
.zonas-tabs .ztab:hover { border-color: var(--primary); }
.zonas-tabs .ztab.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.zonas-tabs .ztab .zt-dot { width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; }
.tables-row { display: flex; gap: 6px; flex-wrap: wrap; }
.table-btn { width: 42px; height: 42px; background: var(--bg-white); border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: 600; font-family: inherit; padding: 2px; }
.table-btn .num { font-size: 13px; line-height: 1; }
.table-btn .pers { font-size: 8px; color: var(--text-muted); margin-top: 2px; line-height: 1; }
.table-btn.libre { color: var(--green); border-color: #d1fae5; background: #f0fdf4; }
.table-btn.ocupada { color: #d97706; border-color: #fed7aa; background: #fffbeb; }
.table-btn.cuenta { color: #2563eb; border-color: #bfdbfe; background: #eff6ff; }
.table-btn.reservada { color: var(--primary); border-color: #ddd6fe; background: #f5f3ff; }
.table-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 12px rgba(91, 61, 245, 0.35); }
.table-btn.active .pers { color: rgba(255,255,255,0.85); }
.legend { display: flex; gap: 16px; margin-top: 12px; flex-wrap: wrap; }
.legend-item { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; font-weight: 500; }
.legend-item .dot { width: 8px; height: 8px; border-radius: 50%; }
.dot-libre { background: var(--green); }
.dot-ocupada { background: var(--orange); }
.dot-cuenta { background: #3b82f6; }
.dot-reservada { background: var(--primary); }

.order-panel { grid-row: 1 / 4; grid-column: 2 / 3; background: var(--bg-white); border-left: 1px solid var(--border); display: flex; flex-direction: column; overflow: hidden; min-height: 0; }
.order-header { padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); flex-shrink: 0; }
.order-header h3 { font-size: 16px; font-weight: 700; }
.order-tabs { display: flex; padding: 8px 12px; gap: 4px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
.order-tab { flex: 1; padding: 8px 6px; text-align: center; border: 0; background: transparent; cursor: pointer; font-size: 11px; font-weight: 600; letter-spacing: 0.4px; color: var(--text-muted); border-radius: 8px; font-family: inherit; }
.order-tab.active { color: var(--primary); background: var(--primary-light); border: 1px solid #ddd6fe; }

.order-items { flex: 1 1 auto; min-height: 70px; overflow-y: auto; padding: 6px 14px; }
.order-item { padding: 8px 2px; border-bottom: 1px solid #f3f4f6; display: grid; grid-template-columns: auto 1fr auto auto; gap: 8px; align-items: center; }
.qty-control { display: flex; align-items: center; background: #f9fafb; border-radius: 8px; overflow: hidden; }
.qty-btn { width: 24px; height: 24px; background: transparent; border: 0; cursor: pointer; font-size: 13px; color: var(--text-muted); }
.qty-num { font-size: 12px; font-weight: 600; padding: 0 6px; min-width: 20px; text-align: center; }
.order-item-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.order-item-name { font-size: 12px; font-weight: 600; }
.order-item-note { font-size: 10px; color: var(--primary); font-weight: 500; }
.order-item-price { font-size: 12px; font-weight: 600; white-space: nowrap; }
.order-item-remove { width: 22px; height: 22px; border: 0; background: transparent; color: #d1d5db; cursor: pointer; }
.order-item-remove:hover { color: var(--red); }

.order-summary { padding: 8px 16px; border-top: 1px solid var(--border); flex-shrink: 0; }
.summary-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; color: var(--text-muted); }
.summary-row span:last-child { color: var(--text-dark); font-weight: 600; }
.total-row { display: flex; justify-content: space-between; padding: 6px 0 2px; align-items: center; }
.total-row .label { font-size: 13px; font-weight: 700; }
.total-row .amount { font-size: 20px; font-weight: 700; color: var(--primary); }

.order-actions { padding: 0 16px 8px; display: grid; grid-template-columns: 1fr 1fr; gap: 6px; flex-shrink: 0; }
.order-actions .btn { padding: 8px 10px; font-size: 12px; }
.payment-methods { padding: 0 16px 8px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; flex-shrink: 0; }
.pay-btn { background: var(--bg-white); border: 1px solid var(--border); border-radius: 10px; padding: 8px 4px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: 10px; font-weight: 500; color: var(--text-muted); font-family: inherit; }
.pay-btn i { font-size: 14px; }
.pay-btn.active { background: #ecfdf5; border-color: var(--green); color: var(--green); }
.pay-btn:nth-child(2) i { color: #3b82f6; }
.pay-btn:nth-child(3) i { color: var(--primary); }
.pay-btn:nth-child(4) i { color: var(--orange); }

.charge-btn { margin: 0 16px 12px; background: var(--primary); color: #fff; border: 0; border-radius: 12px; padding: 14px 18px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; font-size: 15px; font-weight: 700; font-family: inherit; box-shadow: 0 8px 20px rgba(91, 61, 245, 0.35); flex-shrink: 0; }
.charge-btn:hover { background: var(--primary-hover); }
.charge-btn .left { display: flex; align-items: center; gap: 10px; }

.footer { grid-column: 1 / -1; }
.empty-cart { padding: 20px 16px; text-align: center; color: var(--text-muted); font-size: 12px; }
.empty-cart i { font-size: 28px; opacity: 0.3; margin-bottom: 6px; display:block; }
.observation { margin: 6px 16px; background: #f9fafb; border: 1px solid var(--border); border-radius: 10px; padding: 6px 10px; display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.observation input { flex: 1; border: 0; background: transparent; outline: 0; font-size: 12px; font-family: inherit; }

/* Modo solo-lectura: orden de otro mozo, deshabilita controles del panel */
.order-panel.readonly-mode .order-items .qty-control,
.order-panel.readonly-mode .order-items .order-item-remove { opacity: 0.45; pointer-events: none; }
.order-panel.readonly-mode .order-actions button,
.order-panel.readonly-mode .charge-btn,
.order-panel.readonly-mode .pay-btn,
.order-panel.readonly-mode .observation { opacity: 0.5; pointer-events: none; cursor: not-allowed; }
.order-panel.readonly-mode .order-tab { pointer-events: none; }

/* ============================================================
   TABLETS TÁCTILES en horizontal (ancho >= 769px y pantalla táctil):
   la altura útil del navegador suele ser menor, así que el layout fijo
   recortaba el cobro y las mesas. Aquí permitimos desplazamiento vertical
   para que TODO (cobro + mesas) se pueda ver completo. No afecta a
   laptop/PC (pointer: fine).
   ============================================================ */
@media (pointer: coarse) and (min-width: 769px) {
    html, body { height: auto !important; min-height: 100vh; overflow-y: auto !important; }
    .app  { height: auto !important; min-height: calc(100vh - 30px); }
    .main { height: auto !important; overflow: visible !important; min-height: calc(100vh - 30px); }
    .order-panel { overflow: visible; }
    .order-items { min-height: 120px; max-height: 38vh; }
    .products-area { overflow: visible; }
    .products-grid { overflow-y: visible; }
    .content { overflow: visible; }
}
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <div class="order-title">
                <span class="label">NUEVA ORDEN</span>
                <span class="title">
                    <span id="table-title">Sin mesa</span>
                    <span class="persons-badge"><i class="fa-solid fa-user-group"></i> <span id="table-persons">—</span> personas</span>
                </span>
            </div>
            <div class="search-box" style="flex:1;max-width:360px;margin-left:auto;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="search-input" placeholder="Buscar productos...">
            </div>
        </div>

        <div class="content">
            <div class="categories" id="categories-list"></div>

            <div class="products-area">
                <div class="products-grid" id="products-grid"></div>
            </div>
        </div>

        <div class="bottom-panel" id="bottom-panel">
            <div>
                <div class="panel-label" style="display:flex;justify-content:space-between;align-items:center;">
                    <span>MESAS</span>
                    <button type="button" id="btn-toggle-mesas" class="btn-toggle-mesas" onclick="toggleMesas()" title="Ocultar/Mostrar mesas">
                        <i class="fa-solid fa-chevron-down" id="icon-toggle-mesas"></i>
                    </button>
                </div>
                <div id="mesas-collapse">
                    <div class="zonas-tabs" id="zonas-tabs" style="display:none;"></div>
                    <div class="tables-row" id="tables-row"></div>
                    <div class="legend">
                        <div class="legend-item"><span class="dot dot-libre"></span> Libre</div>
                        <div class="legend-item"><span class="dot dot-ocupada"></span> Ocupada</div>
                        <div class="legend-item"><span class="dot dot-cuenta"></span> En cuenta</div>
                        <div class="legend-item"><span class="dot dot-reservada"></span> Reservada</div>
                    </div>
                </div>
            </div>
            <div>
                <div class="panel-label">ACCESOS RÁPIDOS</div>
                <div class="quick-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <button class="btn" onclick="document.getElementById('observation-input').focus()"><i class="fa-regular fa-note-sticky"></i> Nota</button>
                    <a class="btn" href="clientes"><i class="fa-regular fa-user"></i> Cliente</a>
                </div>
            </div>
        </div>

        <aside class="order-panel">
            <div class="order-header">
                <h3 id="order-id">Orden nueva</h3>
            </div>
            <div class="order-tabs">
                <button class="order-tab active" data-type="dine_in">LOCAL</button>
                <button class="order-tab" data-type="para_llevar">PARA LLEVAR</button>
                <button class="order-tab" data-type="delivery">DELIVERY</button>
            </div>
            <div class="order-items" id="order-items">
                <div class="empty-cart"><i class="fa-solid fa-cart-shopping"></i> Sin productos en la orden</div>
            </div>
            <div class="observation">
                <input id="observation-input" placeholder="Agregar observación...">
                <i class="fa-regular fa-comment-dots" style="color:var(--primary);"></i>
            </div>
            <div class="order-summary">
                <div class="summary-row"><span>Subtotal</span><span id="subtotal">S/ 0.00</span></div>
                <div class="summary-row"><span>IGV (18%)</span><span id="tax">S/ 0.00</span></div>
                <div class="total-row">
                    <span class="label">TOTAL</span>
                    <span class="amount" id="total">S/ 0.00</span>
                </div>
            </div>
            <div class="order-actions">
                <button class="btn" onclick="guardarObservacion()"><i class="fa-regular fa-bookmark"></i> Guardar</button>
                <button class="btn btn-primary" data-perm="enviar_cocina" onclick="enviarACocina()"><i class="fa-solid fa-utensils"></i> Enviar a Cocina</button>
            </div>
            <div class="payment-methods" data-perm="cobrar">
                <button class="pay-btn active" data-method="efectivo"><i class="fa-solid fa-money-bill"></i> Efectivo</button>
                <button class="pay-btn" data-method="tarjeta"><i class="fa-regular fa-credit-card"></i> Tarjeta</button>
                <button class="pay-btn" data-method="yape"><i class="fa-solid fa-mobile-screen"></i> Yape / Plin</button>
                <button class="pay-btn" data-method="transferencia"><i class="fa-solid fa-building-columns"></i> Transferencia</button>
            </div>
            <button class="charge-btn" data-perm="cobrar" onclick="abrirModalCobro()">
                <span class="left"><i class="fa-regular fa-calendar"></i> Cobrar</span>
                <span id="charge-amount">S/ 0.00</span>
            </button>
        </aside>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<?php require __DIR__ . '/template/modal_cobro.php'; ?>

<!-- Modal de seleccion de variante (cuando producto tiene multi-precios) -->
<div class="modal-overlay" id="modal-variante">
    <div class="modal" style="max-width: 420px;">
        <div class="modal-header">
            <div class="modal-title" id="modal-variante-title">Elige variante</div>
            <button class="modal-close" onclick="closeModal('modal-variante')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div id="variante-list" style="display:flex;flex-direction:column;gap:8px;"></div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/template/modal_whatsapp.php'; ?>

<!-- FAB: botón flotante que solo aparece en móvil (CSS lo controla) -->
<button id="fab-order" class="fab-order" style="display:none;" onclick="toggleOrderDrawer()" title="Ver orden">
    <i class="fa-solid fa-receipt"></i>
    <span id="fab-order-badge" class="badge" style="display:none;">0</span>
</button>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/whatsapp.js?v=<?php echo filemtime(__DIR__ . '/scripts/whatsapp.js'); ?>"></script>
<script src="scripts/nuevaorden.js?v=<?php echo filemtime(__DIR__ . '/scripts/nuevaorden.js'); ?>"></script>
<script>
// ============ Order drawer en móvil ============
function isMobile() { return window.matchMedia('(max-width: 900px)').matches; }

function toggleOrderDrawer() {
    const panel = document.querySelector('.order-panel');
    if (!panel) return;
    panel.classList.toggle('open');
}

function syncFabVisibility() {
    const fab = document.getElementById('fab-order');
    if (!fab) return;
    fab.style.display = isMobile() ? 'flex' : 'none';
    // Al pasar a desktop, asegurar que el drawer no se quede en estado "open" raro
    if (!isMobile()) {
        document.querySelector('.order-panel')?.classList.remove('open');
    }
}

function updateFabBadge() {
    const badge = document.getElementById('fab-order-badge');
    if (!badge) return;
    let count = 0;
    try {
        const items = document.querySelectorAll('#order-items .order-item');
        count = items.length;
    } catch(e) {}
    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

// Refrescar el badge tras cualquier cambio del DOM en el carrito
const orderItemsEl = document.getElementById('order-items');
if (orderItemsEl && 'MutationObserver' in window) {
    new MutationObserver(updateFabBadge).observe(orderItemsEl, { childList: true, subtree: true });
}

window.addEventListener('resize', syncFabVisibility);
syncFabVisibility();
updateFabBadge();

// Cerrar el drawer al hacer clic en el header del order-panel (toggle)
document.querySelector('.order-panel .order-header')?.addEventListener('click', () => {
    if (isMobile()) toggleOrderDrawer();
});

// Toggle ocultar/mostrar panel de mesas (recordar preferencia en localStorage)
function toggleMesas() {
    const panel = document.getElementById('bottom-panel');
    if (!panel) return;
    panel.classList.toggle('mesas-collapsed');
    const colapsado = panel.classList.contains('mesas-collapsed');
    try { localStorage.setItem('yapez_mesas_collapsed', colapsado ? '1' : '0'); } catch(e) {}
}
// Aplicar preferencia guardada al cargar
(function () {
    try {
        if (localStorage.getItem('yapez_mesas_collapsed') === '1') {
            document.getElementById('bottom-panel')?.classList.add('mesas-collapsed');
        }
    } catch(e) {}
})();
</script>
</body>
</html>
