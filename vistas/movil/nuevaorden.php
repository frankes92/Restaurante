<?php
// Vista movil de Nueva Orden — wizard de 4 tabs.
// Esta vista se incluye desde vistas/nuevaorden.php cuando $__usarMovil = true.
// Asume que ya se ejecuto require_once auth.php + requirePermiso('nuevaorden') desde el caller.

$activePage = 'nuevaorden';
$pageTitle  = 'PUERTO HABANA POS — Nueva Orden';
require __DIR__ . '/../template/head.php';
?>
<link rel="stylesheet" href="../public/css/movil.css?v=<?php echo @filemtime(__DIR__ . '/../../public/css/movil.css'); ?>">
<style>
/* Estilos especificos solo de Nueva Orden movil */
.m-cocina-fab {
    position: fixed;
    bottom: 80px;
    left: 14px; right: 14px;
    z-index: 100;
}
</style>
</head>
<body>

<?php require __DIR__ . '/_drawer.php'; ?>

<div class="m-app">

    <!-- ========== HEADER ========== -->
    <header class="m-header">
        <button class="m-burger" onclick="mAbrirDrawer()" aria-label="Menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="m-title">
            Nueva Orden
            <small id="m-header-sub">Sin mesa</small>
        </div>
        <span class="m-total-pill" id="m-header-total">S/ 0.00</span>
        <button class="m-action" onclick="mForzarDesktop()" title="Versión escritorio">
            <i class="fa-solid fa-desktop"></i>
        </button>
    </header>

    <!-- ========== MAIN ========== -->
    <main class="m-main">

        <!-- ========== TAB 1: MESAS / TIPO ========== -->
        <section class="m-tab active" data-tab="mesas">
            <div class="m-tab-header">
                <h2>Nueva orden</h2>
            </div>
            <!-- Tipo de orden: Local / Para llevar / Delivery -->
            <div class="m-tipo-orden" id="m-tipo-orden">
                <button class="m-tipo-btn active" data-tipo="dine_in" onclick="mSetTipo('dine_in')"><i class="fa-solid fa-chair"></i> Local</button>
                <button class="m-tipo-btn" data-tipo="para_llevar" onclick="mSetTipo('para_llevar')"><i class="fa-solid fa-bag-shopping"></i> Para llevar</button>
                <button class="m-tipo-btn" data-tipo="delivery" onclick="mSetTipo('delivery')"><i class="fa-solid fa-motorcycle"></i> Delivery</button>
            </div>

            <div id="m-mesas-wrap">
                <div class="m-tab-header" style="margin-top:6px;"><h2 style="font-size:15px;">Selecciona tu mesa</h2></div>
                <div class="m-chips" id="m-zonas-chips"></div>
                <div class="m-mesa-grid" id="m-mesas-grid"></div>
                <div class="m-legend">
                    <div class="m-legend-item"><span class="swatch" style="background:#bbf7d0;border-color:#059669;"></span>Libre</div>
                    <div class="m-legend-item"><span class="swatch" style="background:#fcd34d;border-color:#b45309;"></span>Ocupada</div>
                    <div class="m-legend-item"><span class="swatch" style="background:#93c5fd;border-color:#3b82f6;"></span>En cuenta</div>
                    <div class="m-legend-item"><span class="swatch" style="background:#c4b5fd;border-color:#5b3df5;"></span>Reservada</div>
                </div>
            </div>
        </section>

        <!-- ========== TAB 2: PRODUCTOS ========== -->
        <section class="m-tab" data-tab="productos">
            <div id="m-mesa-info-prod"></div>
            <div class="m-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="m-search-input" placeholder="Buscar productos...">
            </div>
            <div class="m-chips" id="m-cats-chips"></div>
            <div class="m-product-grid" id="m-prods-grid"></div>
        </section>

        <!-- ========== TAB 3: CARRITO ========== -->
        <section class="m-tab" data-tab="carrito">
            <div class="m-tab-header">
                <h2 id="m-cart-title">Tu pedido</h2>
                <span class="info" id="m-cart-subtitle">—</span>
            </div>
            <div id="m-readonly-banner-wrap"></div>
            <div class="m-cart-list" id="m-cart-list"></div>
            <div class="m-note-box">
                <i class="fa-regular fa-comment-dots"></i>
                <input id="m-observation" placeholder="Observación general...">
            </div>
            <div class="m-summary">
                <div class="m-summary-row"><span>Subtotal</span><span id="m-subtotal">S/ 0.00</span></div>
                <div class="m-summary-row"><span>IGV (18%)</span><span id="m-igv">S/ 0.00</span></div>
                <div class="m-summary-row total"><span>TOTAL</span><span id="m-total">S/ 0.00</span></div>
            </div>
            <div style="margin-top:14px;display:flex;flex-direction:column;gap:10px;">
                <button class="m-btn warning" id="m-btn-cocina" data-perm="enviar_cocina" onclick="mEnviarCocina()">
                    <i class="fa-solid fa-utensils"></i> Enviar a Cocina
                </button>
                <button class="m-btn ghost" onclick="mGuardarObservacion()">
                    <i class="fa-regular fa-bookmark"></i> Guardar observación
                </button>
            </div>
            <div style="height:80px;"><!-- espaciado para que no tape el bottom nav --></div>
        </section>

        <!-- ========== TAB 4: PAGAR ========== -->
        <section class="m-tab" data-tab="pagar">
            <div class="m-tab-header">
                <h2>Cobrar</h2>
                <span class="info" id="m-pay-orden">—</span>
            </div>

            <div class="m-summary" style="margin-top:0;">
                <div style="text-align:center;padding:10px 0;">
                    <div style="font-size:11px;color:var(--m-muted);font-weight:600;letter-spacing:0.5px;">TOTAL A COBRAR</div>
                    <div style="font-size:34px;font-weight:800;color:var(--m-primary);margin-top:4px;" id="m-pay-total">S/ 0.00</div>
                </div>
            </div>

            <div style="font-size:13px;font-weight:700;color:var(--m-muted);margin-top:18px;margin-bottom:8px;">MÉTODO DE PAGO</div>
            <div class="m-pay-methods">
                <div class="m-pay-method active" data-method="efectivo">
                    <i class="fa-solid fa-money-bill"></i>
                    <div>
                        <div class="m-pay-method-name">Efectivo</div>
                        <div class="m-pay-method-sub">Pago en caja</div>
                    </div>
                </div>
                <div class="m-pay-method" data-method="tarjeta">
                    <i class="fa-regular fa-credit-card"></i>
                    <div>
                        <div class="m-pay-method-name">Tarjeta</div>
                        <div class="m-pay-method-sub">POS / Visa Net</div>
                    </div>
                </div>
                <div class="m-pay-method" data-method="yape">
                    <i class="fa-solid fa-mobile-screen" style="color:#742384;"></i>
                    <div>
                        <div class="m-pay-method-name">Yape</div>
                        <div class="m-pay-method-sub">Escanea el QR</div>
                    </div>
                </div>
                <div class="m-pay-method" data-method="plin">
                    <i class="fa-solid fa-mobile-screen" style="color:#00bcd4;"></i>
                    <div>
                        <div class="m-pay-method-name">Plin</div>
                        <div class="m-pay-method-sub">Escanea el QR</div>
                    </div>
                </div>
                <div class="m-pay-method" data-method="transferencia">
                    <i class="fa-solid fa-building-columns"></i>
                    <div>
                        <div class="m-pay-method-name">Transferencia</div>
                        <div class="m-pay-method-sub">Bancaria</div>
                    </div>
                </div>
                <div class="m-pay-method" data-method="mixto">
                    <i class="fa-solid fa-layer-group" style="color:#5b3df5;"></i>
                    <div>
                        <div class="m-pay-method-name">Mixto</div>
                        <div class="m-pay-method-sub">Dividir entre varios medios</div>
                    </div>
                </div>
            </div>

            <button class="m-btn success" data-perm="cobrar" onclick="mAbrirCobro()">
                <i class="fa-solid fa-check"></i> Confirmar Cobro
            </button>
            <div style="height:80px;"></div>
        </section>

    </main>

    <!-- ========== BOTTOM NAV (wizard tabs) ========== -->
    <nav class="m-bottom-nav">
        <button class="m-nav-tab active" data-target="mesas">
            <i class="fa-solid fa-chair"></i>
            <span>Mesas</span>
        </button>
        <button class="m-nav-tab" data-target="productos" id="m-nav-productos">
            <i class="fa-solid fa-utensils"></i>
            <span>Productos</span>
        </button>
        <button class="m-nav-tab" data-target="carrito">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Carrito</span>
            <span class="m-tab-badge" id="m-cart-badge" style="display:none;">0</span>
        </button>
        <button class="m-nav-tab" data-target="pagar" id="m-nav-pagar" data-perm="cobrar">
            <i class="fa-solid fa-money-bill-wave"></i>
            <span>Cobrar</span>
        </button>
    </nav>
</div>

<!-- Modal de variante (productos con varios precios) -->
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

<script src="scripts/core.js?v=<?php echo @filemtime(__DIR__ . '/../scripts/core.js'); ?>"></script>
<script src="movil/scripts/nuevaorden.js?v=<?php echo @filemtime(__DIR__ . '/scripts/nuevaorden.js'); ?>"></script>

</body>
</html>
