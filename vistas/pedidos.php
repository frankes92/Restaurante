<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/device.php";
requirePermiso('pedidos');

if (!empty($__usarMovil)) {
    require __DIR__ . '/movil/pedidos.php';
    exit;
}

$activePage = 'pedidos';
$pageTitle  = 'PUERTO HABANA POS — Pedidos';
require __DIR__ . '/template/head.php';
?>
<style>
.orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
.order-card { background: var(--bg-white); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; }
.order-card-header { padding: 14px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.order-card-id { font-size: 15px; font-weight: 700; }
.order-card-meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.order-card-body { padding: 14px 16px; flex: 1; }
.order-card-item { font-size: 13px; padding: 6px 0; display: flex; justify-content: space-between; border-bottom: 1px solid #f3f4f6; }
.order-card-item:last-child { border-bottom: 0; }
.order-card-footer { padding: 12px 16px; background: var(--bg-light); display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border); }
.order-card-total { font-size: 16px; font-weight: 700; color: var(--primary); }
.order-card-actions { display: flex; gap: 6px; padding: 0 16px 14px; }
.filter-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-pill { padding: 8px 16px; border-radius: 20px; background: var(--bg-white); border: 1px solid var(--border); cursor: pointer; font-size: 12px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 6px; font-family: inherit; }
.filter-pill.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.filter-pill .count { background: rgba(0,0,0,0.08); padding: 1px 7px; border-radius: 10px; font-size: 10px; }
.filter-pill.active .count { background: rgba(255,255,255,0.25); }
.order-type-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; }
.type-dine_in { background: var(--primary-light); color: var(--primary); }
.type-para_llevar { background: #fef3c7; color: var(--orange); }
.type-delivery { background: #dbeafe; color: var(--blue); }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Pedidos</div>
                <div class="page-subtitle">Órdenes en curso, enviadas a cocina y por cobrar</div>
            </div>
            <a href="nuevaorden" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nueva Orden</a>
        </div>

        <div class="page-content">
            <div class="filter-row">
                <button class="filter-pill active" data-filter="all">Todos <span class="count" id="c-all">0</span></button>
                <button class="filter-pill" data-filter="en_curso"><span style="width:8px;height:8px;border-radius:50%;background:var(--orange);"></span> En curso <span class="count" id="c-en_curso">0</span></button>
                <button class="filter-pill" data-filter="enviada"><span style="width:8px;height:8px;border-radius:50%;background:var(--blue);"></span> Enviadas <span class="count" id="c-enviada">0</span></button>
                <button class="filter-pill" data-filter="dine_in_only">Local</button>
                <button class="filter-pill" data-filter="para_llevar_only">Para Llevar</button>
                <button class="filter-pill" data-filter="delivery_only">Delivery</button>
                <input id="search-orders" class="input-field" placeholder="Buscar por #orden..." style="margin-left:auto;max-width:240px;">
            </div>

            <div class="orders-grid" id="orders-grid"></div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<?php require __DIR__ . '/template/modal_cobro.php'; ?>
<?php require __DIR__ . '/template/modal_whatsapp.php'; ?>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/whatsapp.js?v=<?php echo filemtime(__DIR__ . '/scripts/whatsapp.js'); ?>"></script>
<script src="scripts/pedidos.js?v=<?php echo filemtime(__DIR__ . '/scripts/pedidos.js'); ?>"></script>
</body>
</html>
