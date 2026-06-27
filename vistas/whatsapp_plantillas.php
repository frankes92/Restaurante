<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('whatsapp_plantillas');
$activePage = 'whatsapp_plantillas';
$pageTitle  = 'PUERTO HABANA POS — Plantillas WhatsApp';
require __DIR__ . '/template/head.php';
?>
<style>
.tipo-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:10px; font-weight:700; }
.tipo-cobro     { background:#dbeafe; color:#1e40af; }
.tipo-cumple    { background:#fce7f3; color:#9d174d; }
.tipo-festivo   { background:#fef3c7; color:#92400e; }
.tipo-promocion { background:#fee2e2; color:#991b1b; }
.tipo-generico  { background:#f3f4f6; color:#374151; }
.tpl-mensaje { font-family: 'Courier New', monospace; font-size: 11px; color: var(--text-muted); white-space: pre-wrap; line-height: 1.4; max-height: 80px; overflow: hidden; }
.var-chip { display:inline-block; background:#f5f3ff; color:#5b3df5; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:600; margin:2px; cursor:pointer; }
.var-chip:hover { background:#e9e4ff; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Plantillas de WhatsApp</div>
                <div class="page-subtitle">Mensajes pre-configurados con variables dinámicas</div>
            </div>
            <button class="btn btn-primary" onclick="nuevaPlantilla()"><i class="fa-solid fa-plus"></i> Nueva plantilla</button>
        </div>

        <div class="page-content">
            <div class="card" style="padding:14px 18px;background:#eff6ff;border:1px solid #bfdbfe;margin-bottom:16px;">
                <div style="display:flex;gap:14px;align-items:flex-start;">
                    <i class="fa-solid fa-circle-info" style="color:#1e40af;font-size:20px;margin-top:2px;"></i>
                    <div style="font-size:13px;color:#1e3a8a;line-height:1.6;">
                        <b>Variables disponibles</b> (se reemplazan automáticamente al enviar):
                        <div style="margin-top:8px;">
                            <span class="var-chip">{nombre}</span>
                            <span class="var-chip">{documento}</span>
                            <span class="var-chip">{tipo_doc}</span>
                            <span class="var-chip">{comprobante}</span>
                            <span class="var-chip">{tipo_comp}</span>
                            <span class="var-chip">{total}</span>
                            <span class="var-chip">{fecha}</span>
                            <span class="var-chip">{empresa}</span>
                            <span class="var-chip">{ruc_empresa}</span>
                            <span class="var-chip">{telefono_empresa}</span>
                            <span class="var-chip">{link_pdf}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="padding:0;">
                <table id="tbl-plantillas" class="data-table" style="width:100%;">
                    <thead><tr>
                        <th style="width:120px;">Código</th>
                        <th>Nombre</th>
                        <th style="width:120px;">Tipo</th>
                        <th>Mensaje</th>
                        <th style="width:80px;text-align:center;">Activo</th>
                        <th style="width:160px;"></th>
                    </tr></thead>
                    <tbody id="tpl-body"></tbody>
                </table>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<!-- Modal editar/crear -->
<div class="modal-overlay" id="modal-tpl">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <div class="modal-title" id="modal-tpl-title">Nueva plantilla</div>
            <button class="modal-close" onclick="closeModal('modal-tpl')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="tpl-id" value="">
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:12px;">
                <div class="input-group">
                    <label class="input-label">Código *</label>
                    <input id="tpl-codigo" class="input-field" placeholder="ej: cumple_marzo">
                </div>
                <div class="input-group">
                    <label class="input-label">Nombre *</label>
                    <input id="tpl-nombre" class="input-field" placeholder="Ej: Cumpleaños del cliente">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="input-group">
                    <label class="input-label">Tipo</label>
                    <select id="tpl-tipo" class="input-field">
                        <option value="generico">Genérico</option>
                        <option value="cobro">Cobro / Comprobante</option>
                        <option value="cumple">Cumpleaños</option>
                        <option value="festivo">Festivo</option>
                        <option value="promocion">Promoción</option>
                    </select>
                </div>
                <div class="input-group">
                    <label class="input-label">Estado</label>
                    <select id="tpl-activo" class="input-field">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="input-group">
                <label class="input-label">Mensaje *</label>
                <textarea id="tpl-mensaje" class="input-field" rows="9" style="font-family:'Segoe UI Emoji','Apple Color Emoji','Noto Color Emoji',-apple-system,sans-serif;font-size:13px;resize:vertical;" placeholder="Hola {nombre}..."></textarea>
                <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <button type="button" class="btn btn-sm" onclick="toggleEmojis('tpl-mensaje','tpl-emoji-picker')" style="background:#fef3c7;border-color:#f59e0b;color:#92400e;">
                        <i class="fa-regular fa-face-smile"></i> Emojis
                    </button>
                    <span style="font-size:11px;color:var(--text-muted);">Variables:</span>
                    <span class="var-chip" onclick="insertarVarEn('tpl-mensaje','{nombre}')">{nombre}</span>
                    <span class="var-chip" onclick="insertarVarEn('tpl-mensaje','{documento}')">{documento}</span>
                    <span class="var-chip" onclick="insertarVarEn('tpl-mensaje','{comprobante}')">{comprobante}</span>
                    <span class="var-chip" onclick="insertarVarEn('tpl-mensaje','{total}')">{total}</span>
                    <span class="var-chip" onclick="insertarVarEn('tpl-mensaje','{empresa}')">{empresa}</span>
                    <span class="var-chip" onclick="insertarVarEn('tpl-mensaje','{telefono_empresa}')">{telefono_empresa}</span>
                    <span class="var-chip" onclick="insertarVarEn('tpl-mensaje','{link_pdf}')">{link_pdf}</span>
                </div>
                <div id="tpl-emoji-picker" class="emoji-picker" style="display:none;"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-tpl')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarPlantilla()"><i class="fa-solid fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/whatsapp.js?v=<?php echo filemtime(__DIR__ . '/scripts/whatsapp.js'); ?>"></script>
<script>
const TIPOS = {
    cobro: 'Cobro', cumple: 'Cumpleaños', festivo: 'Festivo',
    promocion: 'Promoción', generico: 'Genérico'
};

async function cargar() {
    const data = await Http.get('../ajax/whatsapp.php?op=plantillas');
    const tb = document.getElementById('tpl-body');
    tb.innerHTML = '';
    if (!data || data.length === 0) {
        tb.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted);">Sin plantillas</td></tr>';
        return;
    }
    data.forEach(p => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><code style="font-size:11px;background:var(--bg-light);padding:2px 6px;border-radius:4px;">${p.codigo}</code></td>
            <td><b>${$('<div>').text(p.nombre).html()}</b></td>
            <td><span class="tipo-badge tipo-${p.tipo}">${TIPOS[p.tipo] || p.tipo}</span></td>
            <td><div class="tpl-mensaje">${$('<div>').text(p.mensaje).html()}</div></td>
            <td style="text-align:center;">${Number(p.activo) === 1 ? '<i class="fa-solid fa-circle-check" style="color:var(--green);"></i>' : '<i class="fa-solid fa-circle-xmark" style="color:#9ca3af;"></i>'}</td>
            <td style="text-align:right;white-space:nowrap;">
                <button class="btn btn-sm" onclick="editar(${p.idplantilla})"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm" onclick="eliminar(${p.idplantilla})" title="Eliminar"><i class="fa-solid fa-trash" style="color:var(--red);"></i></button>
            </td>`;
        tb.appendChild(tr);
    });
}

window.nuevaPlantilla = () => {
    document.getElementById('modal-tpl-title').textContent = 'Nueva plantilla';
    document.getElementById('tpl-id').value = '';
    document.getElementById('tpl-codigo').value = '';
    document.getElementById('tpl-nombre').value = '';
    document.getElementById('tpl-mensaje').value = '';
    document.getElementById('tpl-tipo').value = 'generico';
    document.getElementById('tpl-activo').value = '1';
    openModal('modal-tpl');
};

window.editar = async (id) => {
    const r = await Http.get('../ajax/whatsapp.php?op=plantilla&idplantilla=' + id);
    if (!r) return;
    document.getElementById('modal-tpl-title').textContent = 'Editar: ' + r.nombre;
    document.getElementById('tpl-id').value = r.idplantilla;
    document.getElementById('tpl-codigo').value = r.codigo;
    document.getElementById('tpl-nombre').value = r.nombre;
    document.getElementById('tpl-mensaje').value = r.mensaje;
    document.getElementById('tpl-tipo').value = r.tipo;
    document.getElementById('tpl-activo').value = r.activo;
    openModal('modal-tpl');
};

window.eliminar = async (id) => {
    if (!(await swalConfirm('¿Eliminar esta plantilla?', { icon: 'warning', confirmText: 'Eliminar', confirmColor: '#ef4444' }))) return;
    const r = await Http.post('../ajax/whatsapp.php?op=eliminarPlantilla', { idplantilla: id });
    if (r.ok) { showToast('Plantilla eliminada', 'success'); cargar(); }
    else swalError('No se pudo eliminar');
};

window.guardarPlantilla = async () => {
    const codigo  = document.getElementById('tpl-codigo').value.trim();
    const nombre  = document.getElementById('tpl-nombre').value.trim();
    const mensaje = document.getElementById('tpl-mensaje').value.trim();
    if (!codigo || !nombre || !mensaje) { showToast('Completa todos los campos obligatorios', 'error'); return; }

    const r = await Http.post('../ajax/whatsapp.php?op=guardarPlantilla', {
        idplantilla: document.getElementById('tpl-id').value,
        codigo, nombre, mensaje,
        tipo:   document.getElementById('tpl-tipo').value,
        activo: document.getElementById('tpl-activo').value,
    });
    if (r.ok) {
        showToast('Plantilla guardada', 'success');
        closeModal('modal-tpl');
        cargar();
    } else swalError(r.msg || 'No se pudo guardar');
};

cargar();
</script>
</body>
</html>
