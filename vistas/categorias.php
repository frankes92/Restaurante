<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('productos');
$activePage = 'categorias';
$pageTitle  = 'PUERTO HABANA POS — Categorías';
require __DIR__ . '/template/head.php';
?>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Categorías</div>
                <div class="page-subtitle">Categorías del menú: entradas, postres, bebidas, etc.</div>
            </div>
            <button class="btn btn-primary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Nueva categoría</button>
        </div>

        <div class="page-content">
            <div class="card" style="padding:16px;">
                <table id="tbl-cat" class="data-table" style="width:100%;">
                    <thead><tr>
                        <th>#</th><th>Código</th><th>Nombre</th><th>Orden</th><th>Comanda</th><th>Estado</th><th></th>
                    </tr></thead>
                </table>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<div class="modal-overlay" id="modal-cat">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title" id="modal-cat-title">Nueva categoría</div>
            <button class="modal-close" onclick="closeModal('modal-cat')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="c-id">
            <div class="input-group">
                <label class="input-label">Código *</label>
                <input id="c-codigo" class="input-field" maxlength="40" placeholder="entradas, platos, bebidas">
            </div>
            <div class="input-group">
                <label class="input-label">Nombre *</label>
                <input id="c-nombre" class="input-field" maxlength="80" placeholder="Entradas">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 100px;gap:10px;">
                <div class="input-group">
                    <label class="input-label">Icono FontAwesome</label>
                    <input id="c-icono" class="input-field" placeholder="fa-leaf">
                </div>
                <div class="input-group">
                    <label class="input-label">Color</label>
                    <input type="color" id="c-color" class="input-field" value="#5b3df5" style="height:42px;">
                </div>
                <div class="input-group">
                    <label class="input-label">Orden</label>
                    <input type="number" id="c-orden" class="input-field" min="0" value="0">
                </div>
            </div>
            <div style="background:#f3f4f6;border-radius:8px;padding:8px 12px;font-size:12px;color:var(--text-muted);">
                Ejemplos de iconos: fa-leaf, fa-utensils, fa-fire, fa-pizza-slice, fa-mug-saucer, fa-ice-cream
            </div>
            <label style="display:flex;align-items:center;gap:10px;margin-top:14px;cursor:pointer;font-size:13px;font-weight:600;color:var(--text-dark);">
                <input type="checkbox" id="c-mostrar-comanda" checked style="width:18px;height:18px;">
                <span><i class="fa-solid fa-receipt" style="color:var(--primary);"></i> Mostrar en comanda de cocina</span>
            </label>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px;padding-left:28px;">
                Desmárcalo para que los productos de esta categoría (ej. bebidas) NO salgan en la comanda. Se siguen cobrando y descontando stock normalmente.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-cat')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardar()">Guardar</button>
        </div>
    </div>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/categorias.js?v=<?php echo filemtime(__DIR__ . '/scripts/categorias.js'); ?>"></script>
</body>
</html>
