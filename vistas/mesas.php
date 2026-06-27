<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/device.php";
requirePermiso('mesas');

if (!empty($__usarMovil)) {
    require __DIR__ . '/movil/mesas.php';
    exit;
}

$activePage = 'mesas';
$pageTitle  = 'PUERTO HABANA POS — Mesas';
require __DIR__ . '/template/head.php';
?>
<style>
.tables-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(155px, 1fr)); gap: 10px; }
.zona-section { margin-bottom: 16px; }
.zona-section-header { display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: var(--bg-white); border-radius: 10px; margin-bottom: 10px; border-left: 5px solid var(--primary); }
.zona-section-header .zsh-name { font-size: 14px; font-weight: 700; color: var(--text-dark); letter-spacing: 0.3px; }
.zona-section-header .zsh-count { font-size: 11px; color: var(--text-muted); font-weight: 600; padding: 2px 8px; background: var(--bg-light); border-radius: 10px; }
.zona-section-header .zsh-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.mesa-card { background: var(--bg-white); border: 2px solid var(--border); border-radius: 12px; padding: 12px 14px; cursor: pointer; transition: all 0.15s; position: relative; overflow: hidden; }
.mesa-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
.mesa-card.libre { border-color: #d1fae5; background: #f0fdf4; }
.mesa-card.ocupada { border-color: #fed7aa; background: #fffbeb; }
.mesa-card.cuenta { border-color: #bfdbfe; background: #eff6ff; }
.mesa-card.reservada { border-color: #ddd6fe; background: #f5f3ff; }
.mesa-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
.mesa-num { font-size: 24px; font-weight: 700; }
.mesa-card.libre .mesa-num { color: var(--green); }
.mesa-card.ocupada .mesa-num { color: #d97706; }
.mesa-card.cuenta .mesa-num { color: #2563eb; }
.mesa-card.reservada .mesa-num { color: var(--primary); }
.mesa-icon { font-size: 22px; opacity: 0.4; }
.mesa-info { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
.mesa-info.mesa-price { color: #dc2626; font-weight: 800; font-size: 14px; letter-spacing: 0.2px; margin-bottom: 4px; }
.mesa-info.mesa-price i { color: #dc2626; }
.mesa-status { font-size: 11px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase; margin-top: 12px; }
.mesa-zona-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 12px; color: #fff; letter-spacing: 0.4px; margin-bottom: 8px; max-width: 100%; }
.mesa-zona-badge i { font-size: 9px; }
.filter-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-pill { padding: 8px 16px; border-radius: 20px; background: var(--bg-white); border: 1px solid var(--border); cursor: pointer; font-size: 12px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 6px; font-family: inherit; }
.filter-pill.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.filter-pill .count { background: rgba(0,0,0,0.08); padding: 1px 7px; border-radius: 10px; font-size: 10px; }
.filter-pill.active .count { background: rgba(255,255,255,0.25); }

/* Zonas */
.zonas-section { background: var(--bg-white); border: 1px solid var(--border); border-radius: 14px; padding: 10px 14px; margin-bottom: 12px; }
.zonas-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.zonas-header .zonas-title { font-size: 11px; font-weight: 700; color: var(--text-muted); letter-spacing: 1px; }
.zonas-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.zona-chip { display: flex; flex-direction: column; align-items: flex-start; gap: 1px; min-width: 110px; padding: 6px 12px; background: var(--bg-light); border: 2px solid transparent; border-radius: 10px; cursor: pointer; transition: all 0.15s; position: relative; }
.zona-chip:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
.zona-chip.active { background: #fff; box-shadow: 0 4px 14px rgba(0,0,0,0.08); }
.zona-chip[draggable="true"] { cursor: grab; }
.zona-chip[draggable="true"]:active { cursor: grabbing; }
.zona-chip.dragging { opacity: 0.4; }
.zona-chip .zona-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.zona-chip .zona-name { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--text-dark); }
.zona-chip .zona-count { font-size: 11px; color: var(--text-muted); font-weight: 500; }
.zona-chip .zona-edit { position: absolute; top: 6px; right: 6px; width: 22px; height: 22px; border-radius: 6px; background: transparent; border: 0; cursor: pointer; color: var(--text-muted); display: none; align-items: center; justify-content: center; font-size: 10px; }
.zona-chip:hover .zona-edit { display: flex; }
.zona-chip .zona-edit:hover { background: var(--bg-light); color: var(--primary); }
.zona-chip-all { min-width: 110px; }

/* Modal zona */
.color-palette { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
.color-swatch { width: 30px; height: 30px; border-radius: 8px; cursor: pointer; border: 3px solid transparent; transition: transform 0.15s; }
.color-swatch:hover { transform: scale(1.1); }
.color-swatch.active { border-color: #1a1f36; transform: scale(1.1); }
.mesas-buffer { max-height: 180px; overflow-y: auto; padding: 4px; }
.mesa-buffer-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--bg-light); border-radius: 8px; margin-bottom: 6px; font-size: 13px; }
.mesa-buffer-item .remove-btn { background: transparent; border: 0; color: var(--red); cursor: pointer; font-size: 13px; padding: 4px 8px; }
.mesas-chk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 6px; max-height: 160px; overflow-y: auto; padding: 4px; }
.mesa-chk { display: flex; align-items: center; gap: 6px; padding: 6px 10px; background: var(--bg-light); border: 1.5px solid transparent; border-radius: 8px; cursor: pointer; font-size: 12px; transition: all 0.15s; user-select: none; }
.mesa-chk:hover { border-color: var(--primary); }
.mesa-chk.checked { background: #f5f3ff; border-color: var(--primary); color: var(--primary); font-weight: 700; }
.mesa-chk input { display: none; }
.mesa-chk .mesa-chk-num { font-weight: 700; }
.mesa-chk .mesa-chk-cap { font-size: 10px; color: var(--text-muted); }
.mesa-chk.checked .mesa-chk-cap { color: var(--primary); }
.mesas-empty { font-size: 12px; color: var(--text-muted); padding: 12px; text-align: center; background: var(--bg-light); border-radius: 8px; }

/* Barra de disposicion (ordenar + columnas) */
.layout-bar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
.layout-bar .filter-pill.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.layout-hint { font-size: 12px; color: var(--primary); font-weight: 600; }
.layout-cols { display: flex; align-items: center; gap: 8px; margin-left: auto; }
.layout-cols-lbl { font-size: 12px; font-weight: 600; color: var(--text-muted); white-space: nowrap; }
.layout-cols #sel-cols { width: auto; min-width: 120px; padding: 7px 12px; }

/* Modo ordenar: las cards se vuelven arrastrables */
.tables-grid.modo-orden .mesa-card { cursor: grab; border-style: dashed; }
.tables-grid.modo-orden .mesa-card:active { cursor: grabbing; }
.tables-grid.modo-orden .mesa-card.dragging { opacity: 0.4; transform: scale(0.97); }
.tables-grid.modo-orden .mesa-card .btn { pointer-events: none; opacity: 0.5; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Mesas</div>
                <div class="page-subtitle">Administra el estado de cada mesa del salón</div>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn" onclick="document.getElementById('search-mesas').focus()"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
                <button class="btn btn-primary" onclick="openAddTable()"><i class="fa-solid fa-plus"></i> Nueva Mesa</button>
            </div>
        </div>

        <div class="page-content">
            <!-- Zonas -->
            <div class="zonas-section">
                <div class="zonas-header">
                    <div class="zonas-title"><i class="fa-solid fa-map-location-dot" style="color:var(--primary);"></i> ZONAS DEL LOCAL</div>
                    <button class="btn btn-sm btn-primary" data-perm="zonas" onclick="openAddZona()"><i class="fa-solid fa-plus"></i> Nueva Zona</button>
                </div>
                <div class="zonas-grid" id="zonas-grid"></div>
            </div>

            <div class="filter-row">
                <button class="filter-pill active" data-filter="all">Todas <span class="count" id="count-all">0</span></button>
                <button class="filter-pill" data-filter="libre"><span class="dot" style="width:8px;height:8px;border-radius:50%;background:var(--green);"></span> Libres <span class="count" id="count-libre">0</span></button>
                <button class="filter-pill" data-filter="ocupada"><span class="dot" style="width:8px;height:8px;border-radius:50%;background:var(--orange);"></span> Ocupadas <span class="count" id="count-ocupada">0</span></button>
                <button class="filter-pill" data-filter="cuenta"><span class="dot" style="width:8px;height:8px;border-radius:50%;background:var(--blue);"></span> En cuenta <span class="count" id="count-cuenta">0</span></button>
                <button class="filter-pill" data-filter="reservada"><span class="dot" style="width:8px;height:8px;border-radius:50%;background:var(--primary);"></span> Reservadas <span class="count" id="count-reservada">0</span></button>
                <input id="search-mesas" class="input-field" placeholder="Buscar mesa..." style="margin-left:auto;max-width:240px;">
            </div>

            <!-- Controles de disposicion: ordenar (drag&drop) + columnas -->
            <div class="layout-bar">
                <button id="btn-ordenar" class="filter-pill btn-ordenar" onclick="toggleOrdenar()" type="button">
                    <i class="fa-solid fa-up-down-left-right"></i> Ordenar mesas
                </button>
                <span id="ordenar-hint" class="layout-hint" style="display:none;">
                    Arrastra las mesas para reordenarlas. Se guarda automáticamente.
                </span>
                <div class="layout-cols">
                    <span class="layout-cols-lbl"><i class="fa-solid fa-table-cells"></i> Columnas</span>
                    <select id="sel-cols" class="input-field" onchange="cambiarColumnas(this.value)">
                        <option value="auto">Automático</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                    </select>
                    <button id="btn-reset-orden" class="btn btn-sm" type="button" onclick="resetOrden()" title="Restablecer orden por número" style="display:none;">
                        <i class="fa-solid fa-rotate-left"></i> Restablecer orden
                    </button>
                </div>
            </div>

            <div class="tables-grid" id="tables-grid"></div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<div class="modal-overlay" id="modal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-title">Nueva Mesa</div>
            <button class="modal-close" onclick="closeModal('modal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="input-group">
                <label class="input-label">Zona</label>
                <select id="m-zona" class="input-field"><option value="">— Sin zona —</option></select>
            </div>
            <div class="input-group">
                <label class="input-label">Número de mesa</label>
                <input type="number" id="m-num" class="input-field" placeholder="Ej. 11">
            </div>
            <div class="input-group">
                <label class="input-label">Capacidad (personas)</label>
                <input type="number" id="m-cap" class="input-field" placeholder="Ej. 4">
            </div>
            <div class="input-group">
                <label class="input-label">Estado</label>
                <select id="m-status" class="input-field">
                    <option value="libre">Libre</option>
                    <option value="ocupada">Ocupada</option>
                    <option value="cuenta">En cuenta</option>
                    <option value="reservada">Reservada</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal')">Cancelar</button>
            <button class="btn btn-primary" onclick="saveTable()">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal Zona -->
<div class="modal-overlay" id="modal-zona">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-map-location-dot" style="color:var(--primary);"></i> <span id="zona-modal-title">Nueva Zona</span></div>
            <button class="modal-close" onclick="closeModal('modal-zona')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="input-group">
                <label class="input-label">Nombre de la zona *</label>
                <input id="z-nombre" class="input-field" placeholder="Ej. Salón Principal, Terraza, VIP" maxlength="80">
            </div>

            <div class="input-group">
                <label class="input-label">Color identificador</label>
                <div class="color-palette" id="z-colores"></div>
                <input type="hidden" id="z-color" value="#5b3df5">
            </div>

            <!-- Crear mesas nuevas (solo modo Nueva Zona) -->
            <div id="z-mesas-section">
                <div style="border-top:1px dashed var(--border);margin:14px 0 12px;"></div>
                <div style="font-size:11px;font-weight:700;color:var(--text-muted);letter-spacing:0.8px;margin-bottom:8px;">CREAR MESAS NUEVAS PARA ESTA ZONA (opcional)</div>

                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end;margin-bottom:10px;">
                    <div class="input-group" style="margin:0;">
                        <label class="input-label">N° de mesa</label>
                        <input type="number" id="z-mesa-num" class="input-field" placeholder="11" min="1">
                    </div>
                    <div class="input-group" style="margin:0;">
                        <label class="input-label">Capacidad</label>
                        <input type="number" id="z-mesa-cap" class="input-field" placeholder="4" min="1" value="4">
                    </div>
                    <button type="button" class="btn btn-primary" onclick="agregarMesaBuffer()"><i class="fa-solid fa-plus"></i> Agregar</button>
                </div>

                <div class="mesas-buffer" id="z-mesas-buffer"></div>
            </div>

            <!-- Asignar mesas existentes (modo Editar Zona) -->
            <div id="z-asignar-section" style="display:none;">
                <div style="border-top:1px dashed var(--border);margin:14px 0 12px;"></div>

                <div style="font-size:11px;font-weight:700;color:var(--text-muted);letter-spacing:0.8px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;">
                    <span><i class="fa-solid fa-circle-check" style="color:var(--green);"></i> MESAS EN ESTA ZONA</span>
                    <button type="button" id="z-btn-quitar-todas" class="btn btn-sm" style="font-size:11px;padding:4px 8px;" onclick="quitarMesasSeleccionadas()" disabled>Quitar seleccionadas</button>
                </div>
                <div id="z-mesas-en-zona" class="mesas-chk-grid"></div>

                <div style="font-size:11px;font-weight:700;color:var(--text-muted);letter-spacing:0.8px;margin:14px 0 8px;display:flex;justify-content:space-between;align-items:center;">
                    <span><i class="fa-solid fa-circle-question" style="color:#94a3b8;"></i> MESAS SIN ZONA (disponibles)</span>
                    <button type="button" id="z-btn-agregar-todas" class="btn btn-sm btn-primary" style="font-size:11px;padding:4px 8px;" onclick="asignarMesasSeleccionadas()" disabled>Agregar seleccionadas</button>
                </div>
                <div id="z-mesas-sin-zona" class="mesas-chk-grid"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" id="z-btn-eliminar" style="display:none;margin-right:auto;" onclick="eliminarZonaActual()"><i class="fa-solid fa-trash"></i> Eliminar zona</button>
            <button class="btn" onclick="closeModal('modal-zona')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarZona()"><i class="fa-solid fa-check"></i> <span id="z-btn-label">Crear Zona</span></button>
        </div>
    </div>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/mesas.js?v=<?php echo filemtime(__DIR__ . '/scripts/mesas.js'); ?>"></script>
</body>
</html>
