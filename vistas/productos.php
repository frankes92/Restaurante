<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('productos');
$activePage = 'productos';
$pageTitle  = 'PUERTO HABANA POS — Productos';
require __DIR__ . '/template/head.php';
?>
<style>
.prod-img-cell { width:48px; height:48px; border-radius:8px; background-size:cover; background-position:center; background-color:#f3f4f6; flex-shrink:0; }
.prod-cell { display:flex; align-items:center; gap:10px; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Productos del menú</div>
                <div class="page-subtitle">Platos, bebidas y otros items que se venden en el POS</div>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn" onclick="openImportar()"><i class="fa-solid fa-file-import"></i> Importar Excel</button>
                <button class="btn btn-primary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Nuevo producto</button>
            </div>
        </div>

        <div class="page-content">
            <div style="margin-bottom:14px;">
                <select id="filtro-cat" class="input-field" style="max-width:300px;display:inline-block;">
                    <option value="">— Todas las categorías —</option>
                </select>
            </div>
            <div class="card" style="padding:16px;">
                <table id="tbl-prod" class="data-table" style="width:100%;">
                    <thead><tr>
                        <th>#</th><th>Código</th><th>Producto</th><th>Categoría</th>
                        <th>Precio</th><th>Estado</th><th></th>
                    </tr></thead>
                </table>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<div class="modal-overlay" id="modal-prod">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <div class="modal-title" id="modal-prod-title">Nuevo producto</div>
            <button class="modal-close" onclick="closeModal('modal-prod')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="p-id">
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:10px;">
                <div class="input-group">
                    <label class="input-label">Código *</label>
                    <input id="p-codigo" class="input-field" maxlength="40" placeholder="P0001">
                </div>
                <div class="input-group">
                    <label class="input-label">Nombre *</label>
                    <input id="p-nombre" class="input-field" maxlength="150">
                </div>
            </div>
            <div class="input-group">
                <label class="input-label">Categoría *</label>
                <select id="p-categoria" class="input-field"></select>
            </div>

            <!-- IMAGEN: subir o URL -->
            <div class="input-group">
                <label class="input-label">Imagen del plato</label>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <input type="file" id="p-archivo" accept="image/jpeg,image/png,image/webp,image/gif" style="flex:1;min-width:200px;" class="input-field">
                    <button type="button" class="btn btn-primary btn-sm" onclick="subirImagen()"><i class="fa-solid fa-upload"></i> Subir</button>
                </div>
                <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block;">JPG, PNG, WEBP o GIF · max 2MB. Alternativa: URL externa abajo.</small>
            </div>
            <div class="input-group">
                <label class="input-label">URL de imagen</label>
                <input id="p-imagen" class="input-field" placeholder="https://... o ../public/img/productos/...">
            </div>
            <div id="p-preview" style="margin-bottom:10px;display:none;">
                <img id="p-preview-img" src="" style="width:140px;height:100px;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
            </div>

            <!-- PRECIOS / VARIANTES -->
            <div style="margin-top:10px;border-top:1px dashed var(--border);padding-top:14px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <label class="input-label" style="margin:0;">Precios / Variantes *</label>
                    <button type="button" class="btn btn-sm" onclick="agregarFilaPrecio()"><i class="fa-solid fa-plus"></i> Agregar</button>
                </div>
                <div id="p-precios-list" style="display:flex;flex-direction:column;gap:8px;"></div>
                <small style="color:var(--text-muted);font-size:11px;display:block;margin-top:4px;">
                    Marca cuál es el precio por defecto. Ej: Personal/Familiar, Vaso/Botella, Normal/Mayorista.
                </small>
            </div>

            <!-- AFECTACION SUNAT (catalogo 7) -->
            <div class="input-group" style="margin-top:10px;">
                <label class="input-label">Tipo de afectación SUNAT</label>
                <select id="p-afectacion" class="input-field">
                    <option value="10">Gravado · IGV 18% (lo más común)</option>
                    <option value="20">Exonerado · sin IGV (alimentos básicos, libros, etc.)</option>
                    <option value="30">Inafecto · fuera del IGV (servicios médicos, etc.)</option>
                    <option value="40">Exportación</option>
                </select>
                <small style="color:var(--text-muted);font-size:11px;display:block;margin-top:4px;">
                    Define cómo se trata este producto en boletas/facturas electrónicas SUNAT.
                </small>
            </div>

            <div style="display:flex;gap:18px;margin-top:14px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" id="p-popular"> Popular (más vendido)
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" id="p-favorito"> Favorito
                </label>
            </div>

            <!-- CONTROL DE INVENTARIO por presentacion -->
            <div style="margin-top:14px;border-top:1px dashed var(--border);padding-top:14px;">
                <label style="display:flex;align-items:center;gap:10px;font-size:13px;cursor:pointer;font-weight:600;">
                    <input type="checkbox" id="p-controla-stock" onchange="toggleStockFields()">
                    <i class="fa-solid fa-boxes-stacked" style="color:var(--primary);"></i>
                    Controlar inventario / stock de este producto
                </label>
                <small style="color:var(--text-muted);font-size:11px;display:block;margin-top:4px;">
                    Para bebidas u otros productos contables (cerveza, gaseosa…). Como este producto
                    puede tener varias presentaciones, el stock se lleva <b>por cada presentación</b>.
                </small>
                <div id="p-stock-fields" style="display:none;margin-top:10px;">
                    <div style="font-size:11px;font-weight:700;color:var(--text-muted);letter-spacing:.5px;margin-bottom:6px;">
                        STOCK POR PRESENTACIÓN
                    </div>
                    <div id="p-stock-list" style="display:flex;flex-direction:column;gap:8px;"></div>
                    <small style="color:var(--text-muted);font-size:11px;display:block;margin-top:6px;">
                        El "mínimo" enciende la alerta amarilla del semáforo. Luego puedes ajustar
                        entradas/salidas desde el módulo <b>Inventario</b>.
                    </small>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-prod')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardar()">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal Importar Excel -->
<div class="modal-overlay" id="modal-import">
    <div class="modal" style="max-width:520px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-file-import" style="color:var(--primary);"></i> Importar productos desde Excel</div>
            <button class="modal-close" onclick="closeModal('modal-import')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="font-size:13px;color:var(--text-muted);line-height:1.6;margin-bottom:12px;">
                Sube un archivo <b>.xlsx</b> o <b>.csv</b> con estas columnas en este orden:
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;font-size:12px;">
                <span class="badge badge-gray">Categoría</span>
                <span class="badge badge-gray">Código</span>
                <span class="badge badge-gray">Producto</span>
                <span class="badge badge-gray">Precio</span>
                <span class="badge badge-gray">Presentación</span>
                <span class="badge badge-gray">Afectación</span>
            </div>
            <ul style="font-size:12px;color:var(--text-muted);line-height:1.7;margin:0 0 14px 18px;">
                <li>La primera fila es el <b>encabezado</b> (se ignora).</li>
                <li>Si la <b>categoría</b> no existe, se crea automáticamente.</li>
                <li><b>Código</b> es opcional: si lo dejas vacío, se genera solo.</li>
                <li><b>Presentación</b> y <b>Afectación</b> son opcionales.</li>
                <li>Para varias presentaciones de un producto, repite el <b>mismo código</b> en varias filas (ej. 500ml / 1L).</li>
                <li><b>Afectación</b>: 10 = gravado (con IGV), 20 = exonerado, 30 = inafecto.</li>
                <li>Si el producto ya existe (mismo código), se <b>actualiza</b>.</li>
            </ul>
            <button class="btn btn-sm" type="button" onclick="descargarPlantilla()" style="margin-bottom:14px;">
                <i class="fa-solid fa-download"></i> Descargar plantilla de ejemplo
            </button>
            <div class="input-group">
                <label class="input-label">Archivo (.xlsx / .csv)</label>
                <input type="file" id="import-file" accept=".xlsx,.csv,.txt" class="input-field">
            </div>
            <div id="import-resultado" style="display:none;margin-top:12px;font-size:13px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-import')">Cancelar</button>
            <button class="btn btn-primary" id="btn-do-import" onclick="hacerImportar()"><i class="fa-solid fa-upload"></i> Importar</button>
        </div>
    </div>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/productos.js?v=<?php echo filemtime(__DIR__ . '/scripts/productos.js'); ?>"></script>
</body>
</html>
