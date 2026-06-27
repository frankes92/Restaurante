<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('impresoras');
$activePage = 'impresoras';
$pageTitle  = 'PUERTO HABANA POS — Impresoras';
require __DIR__ . '/template/head.php';
?>
<style>
.imp-card { background: var(--bg-white); border: 1px solid var(--border); border-radius: 12px; padding: 16px; display: grid; grid-template-columns: 50px 1fr auto; gap: 14px; align-items: center; }
.imp-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.imp-icon.cocina { background: #fef3c7; color: #b45309; }
.imp-icon.caja   { background: #dbeafe; color: #2563eb; }
.imp-icon.bar    { background: #f3e8ff; color: #7c3aed; }
.imp-icon.otro   { background: #f3f4f6; color: #4b5563; }
.imp-nom { font-size: 15px; font-weight: 700; }
.imp-ip { font-family: 'Courier New', monospace; font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.imp-actions { display: flex; gap: 6px; }
.imp-grid { display: grid; grid-template-columns: 1fr; gap: 10px; }
.imp-estado { font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: 700; }
.imp-estado.activa { background: #d1fae5; color: #059669; }
.imp-estado.inactiva { background: #fee2e2; color: #dc2626; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Impresoras</div>
                <div class="page-subtitle">Gestión de impresoras térmicas en red local (LAN)</div>
            </div>
            <button class="btn btn-primary" onclick="openAddImpresora()"><i class="fa-solid fa-plus"></i> Nueva Impresora</button>
        </div>

        <div class="page-content">
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#1e40af;">
                <i class="fa-solid fa-circle-info"></i> Para que las impresiones funcionen, debe estar corriendo el <b>Bridge</b> en una PC del local conectada a la misma red que las impresoras. Consulta <code>bridge/README.txt</code>.
            </div>

            <div class="imp-grid" id="imp-list"></div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modal-imp">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-imp-title">Nueva Impresora</div>
            <button class="modal-close" onclick="closeModal('modal-imp')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="input-group">
                <label class="input-label">Nombre *</label>
                <input id="i-nombre" class="input-field" placeholder="Ej. Cocina principal">
            </div>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px;">
                <div class="input-group">
                    <label class="input-label">IP *</label>
                    <input id="i-ip" class="input-field" placeholder="192.168.100.101" inputmode="numeric">
                </div>
                <div class="input-group">
                    <label class="input-label">Puerto</label>
                    <input id="i-puerto" class="input-field" value="9100" inputmode="numeric">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div class="input-group">
                    <label class="input-label">Tipo</label>
                    <select id="i-tipo" class="input-field">
                        <option value="cocina">Cocina</option>
                        <option value="bar">Bar</option>
                        <option value="caja">Caja</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="input-group">
                    <label class="input-label">Ancho papel</label>
                    <select id="i-ancho" class="input-field">
                        <option value="32">58mm (32 cols)</option>
                        <option value="48" selected>80mm (48 cols)</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:20px;margin-top:8px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" id="i-cortar" checked> Cortar papel al final
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" id="i-activa" checked> Impresora activa
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-imp')">Cancelar</button>
            <button class="btn btn-primary" onclick="saveImpresora()">Guardar</button>
        </div>
    </div>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/impresoras.js?v=<?php echo @filemtime(__DIR__ . '/scripts/impresoras.js'); ?>"></script>
</body>
</html>
