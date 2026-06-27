<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('whatsapp_masivo');
$activePage = 'whatsapp_envios';
$pageTitle  = 'PUERTO HABANA POS — WhatsApp Masivo';
require __DIR__ . '/template/head.php';
?>
<style>
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 18px; }
.stat-mini { background: var(--bg-white); border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px; }
.stat-mini .lbl { font-size: 11px; color: var(--text-muted); font-weight: 700; letter-spacing: .4px; }
.stat-mini .val { font-size: 22px; font-weight: 800; color: var(--text-dark); margin-top: 4px; }
.stat-mini .sub { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

.tipo-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:10px; font-weight:700; }
.tipo-cobro  { background:#dbeafe; color:#1e40af; }
.tipo-masivo { background:#fce7f3; color:#9d174d; }
.tipo-manual { background:#f3f4f6; color:#374151; }
.cliente-row { padding: 8px 12px; border-bottom: 1px solid #f3f4f6; display: grid; grid-template-columns: 32px 1fr 130px 80px; gap: 10px; align-items: center; font-size: 13px; }
.cliente-row:hover { background: var(--bg-light); }
.cliente-row .name { font-weight: 600; color: var(--text-dark); }
.cliente-row .info { font-size: 11px; color: var(--text-muted); }
.cliente-row .badge { font-size: 11px; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">WhatsApp — Envío masivo</div>
                <div class="page-subtitle">Campañas a clientes por cumpleaños, festivos y promociones</div>
            </div>
        </div>

        <div class="page-content">

            <!-- Estadisticas -->
            <div class="stats-row">
                <div class="stat-mini">
                    <div class="lbl"><i class="fa-brands fa-whatsapp" style="color:#25d366;"></i> ENVIADOS HOY</div>
                    <div class="val" id="st-hoy">0</div>
                    <div class="sub" id="st-7dias">— en últimos 7 días</div>
                </div>
                <div class="stat-mini">
                    <div class="lbl"><i class="fa-solid fa-receipt"></i> EN COBROS</div>
                    <div class="val" id="st-cobros">0</div>
                </div>
                <div class="stat-mini">
                    <div class="lbl"><i class="fa-solid fa-bullhorn"></i> EN CAMPAÑAS</div>
                    <div class="val" id="st-masivos">0</div>
                </div>
                <div class="stat-mini">
                    <div class="lbl"><i class="fa-solid fa-cake-candles"></i> CUMPLEAÑEROS DEL MES</div>
                    <div class="val" id="st-cumples">0</div>
                    <div class="sub" id="st-clientes">— clientes con WhatsApp</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:340px 1fr;gap:18px;">

                <!-- Filtros y plantilla -->
                <div class="card" style="padding:18px;">
                    <div class="card-title" style="margin-bottom:14px;">Configuración del envío</div>

                    <div class="input-group">
                        <label class="input-label">Filtrar clientes</label>
                        <select id="f-filtro" class="input-field" onchange="cargarClientes()">
                            <option value="todos">Todos los clientes con WhatsApp</option>
                            <option value="cumple_hoy">Cumpleañeros HOY</option>
                            <option value="cumple_mes">Cumpleañeros del mes</option>
                            <option value="frecuentes">Frecuentes (más de N órdenes)</option>
                            <option value="vip">VIP (más de N en gasto total)</option>
                            <option value="inactivos">Inactivos (sin visita en N días)</option>
                        </select>
                    </div>

                    <div class="input-group" id="f-param-wrap" style="display:none;">
                        <label class="input-label" id="f-param-label">N</label>
                        <input type="number" id="f-param" class="input-field" value="5" onchange="cargarClientes()">
                    </div>

                    <div class="input-group">
                        <label class="input-label">Plantilla</label>
                        <select id="f-plantilla" class="input-field" onchange="actualizarPreview()"></select>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Vista previa del mensaje</label>
                        <textarea id="f-preview" class="input-field" rows="9" style="font-family:'Courier New',monospace;font-size:12px;" readonly></textarea>
                        <div style="font-size:10px;color:var(--text-muted);margin-top:4px;">
                            Las variables se reemplazan por los datos de cada cliente.
                        </div>
                    </div>

                    <button class="btn btn-primary" style="width:100%;background:#25d366;border-color:#25d366;" onclick="iniciarEnvioMasivo()">
                        <i class="fa-brands fa-whatsapp"></i> Iniciar envío secuencial
                    </button>
                </div>

                <!-- Lista de clientes -->
                <div class="card" style="padding:0;">
                    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:var(--text-dark);">Clientes seleccionados</div>
                            <div style="font-size:11px;color:var(--text-muted);" id="cli-count">0 con WhatsApp</div>
                        </div>
                        <div>
                            <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;">
                                <input type="checkbox" id="chk-todos" onchange="toggleTodos()"> Seleccionar todos
                            </label>
                        </div>
                    </div>
                    <div id="cli-lista" style="max-height:480px;overflow-y:auto;"></div>
                </div>
            </div>

            <!-- Historial -->
            <div class="card" style="margin-top:18px;padding:14px 18px;">
                <div class="card-title" style="margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;">
                    <span>Historial de envíos</span>
                    <button class="btn btn-sm" onclick="cargarHistorial()"><i class="fa-solid fa-arrows-rotate"></i> Refrescar</button>
                </div>
                <table id="tbl-hist" class="data-table" style="width:100%;font-size:12px;">
                    <thead><tr>
                        <th>Fecha</th><th>Tipo</th><th>Cliente</th><th>Documento</th>
                        <th>Número</th><th>Plantilla</th><th>Mensaje</th><th>Usuario</th>
                    </tr></thead>
                    <tbody id="hist-body"></tbody>
                </table>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script>
let clientesCargados = [];
let plantillas = [];

async function init() {
    // Stats
    const st = await Http.get('../ajax/whatsapp.php?op=estadisticas');
    document.getElementById('st-hoy').textContent     = st.hoy;
    document.getElementById('st-7dias').textContent   = st.ultimos_7 + ' en últimos 7 días';
    document.getElementById('st-cobros').textContent  = st.cobros;
    document.getElementById('st-masivos').textContent = st.masivos;
    document.getElementById('st-cumples').textContent = st.cumples_del_mes;
    document.getElementById('st-clientes').textContent = st.clientes_con_wa + ' clientes con WhatsApp';

    // Plantillas
    plantillas = await Http.get('../ajax/whatsapp.php?op=plantillas&solo_activas=1');
    const sel = document.getElementById('f-plantilla');
    sel.innerHTML = '';
    plantillas.forEach(p => {
        const o = document.createElement('option');
        o.value = p.idplantilla;
        o.textContent = p.nombre;
        sel.appendChild(o);
    });
    // Preferir cumple si filtro es cumple_*
    actualizarPreview();
    cargarClientes();
    cargarHistorial();
}

document.getElementById('f-filtro').addEventListener('change', () => {
    const v = document.getElementById('f-filtro').value;
    const wrap = document.getElementById('f-param-wrap');
    const lbl  = document.getElementById('f-param-label');
    if (v === 'frecuentes') { wrap.style.display=''; lbl.textContent='Mínimo de órdenes'; }
    else if (v === 'vip')        { wrap.style.display=''; lbl.textContent='Mínimo gastado (S/)'; }
    else if (v === 'inactivos')  { wrap.style.display=''; lbl.textContent='Días sin visita'; }
    else wrap.style.display='none';
});

async function cargarClientes() {
    const filtro = document.getElementById('f-filtro').value;
    const param  = document.getElementById('f-param').value || 0;
    const data = await Http.get('../ajax/whatsapp.php?op=clientes&filtro=' + filtro + '&param=' + param);
    clientesCargados = (data || []).filter(c => c.numero_normalizado);
    document.getElementById('cli-count').textContent = clientesCargados.length + ' con WhatsApp';

    const cont = document.getElementById('cli-lista');
    if (clientesCargados.length === 0) {
        cont.innerHTML = '<div style="padding:30px;text-align:center;color:var(--text-muted);">Sin clientes para este filtro</div>';
        return;
    }
    cont.innerHTML = clientesCargados.map((c, i) => `
        <div class="cliente-row">
            <input type="checkbox" class="chk-cli" data-idx="${i}" checked>
            <div>
                <div class="name">${$('<div>').text(c.nombre).html()}</div>
                <div class="info">${c.documento || '—'} · ${c.fecha_nacimiento ? '🎂 ' + c.fecha_nacimiento : ''}</div>
            </div>
            <div>
                <div class="info"><i class="fa-brands fa-whatsapp" style="color:#25d366;"></i> +${c.numero_normalizado}</div>
                <div class="info">${Number(c.total_ordenes||0)} órdenes</div>
            </div>
            <div style="text-align:right;font-size:11px;color:var(--primary);font-weight:700;">${fmt.money(c.total_gastado||0)}</div>
        </div>`).join('');
}

window.toggleTodos = () => {
    const ch = document.getElementById('chk-todos').checked;
    document.querySelectorAll('.chk-cli').forEach(c => c.checked = ch);
};

async function actualizarPreview() {
    const id = document.getElementById('f-plantilla').value;
    const p = plantillas.find(x => Number(x.idplantilla) === Number(id));
    if (!p) return;
    // Preview con datos demo
    const r = await Http.post('../ajax/whatsapp.php?op=preview', {
        mensaje: p.mensaje,
        nombre: '[NOMBRE_CLIENTE]',
        documento: '[DOCUMENTO]',
        tipo_doc: '1',
        comprobante: '',
        tipo_documento: '',
        total: 0,
        link_pdf: '',
        numero: '',
    });
    document.getElementById('f-preview').value = r.mensaje || p.mensaje;
}

async function iniciarEnvioMasivo() {
    const seleccionados = [];
    document.querySelectorAll('.chk-cli').forEach(c => {
        if (c.checked) seleccionados.push(clientesCargados[+c.dataset.idx]);
    });
    if (seleccionados.length === 0) { showToast('Selecciona al menos un cliente', 'error'); return; }

    const id = document.getElementById('f-plantilla').value;
    const p = plantillas.find(x => Number(x.idplantilla) === Number(id));
    if (!p) { showToast('Selecciona una plantilla', 'error'); return; }

    const ok = await swalConfirm(
        `Se abrirán ${seleccionados.length} ventanas de WhatsApp en secuencia.<br>` +
        `Tendrás que <b>presionar Enviar manualmente</b> en cada conversación.<br><br>` +
        `<b>Plantilla:</b> ${p.nombre}<br>` +
        `<b>Filtro:</b> ${document.getElementById('f-filtro').selectedOptions[0].text}`,
        { title: '¿Iniciar envío?', icon: 'question', confirmText: 'Sí, comenzar' }
    );
    if (!ok) return;

    let enviados = 0;
    for (const c of seleccionados) {
        const tipoDoc = (c.documento && c.documento.length === 11) ? '6' : '1';
        // Reemplazar placeholders en el backend
        const r = await Http.post('../ajax/whatsapp.php?op=preview', {
            mensaje: p.mensaje,
            nombre: c.nombre,
            documento: c.documento,
            tipo_doc: tipoDoc,
            numero: c.numero_normalizado,
        });
        const mensajeRender = (r && r.mensaje) ? r.mensaje : p.mensaje;
        const numeroNorm = c.numero_normalizado || '';
        if (!numeroNorm) continue;

        // CONSTRUIR URL EN EL CLIENTE — preserva emojis 4-byte intactos
        const url = 'https://api.whatsapp.com/send?phone=' + numeroNorm
                  + '&text=' + encodeURIComponent(mensajeRender);

        // Registrar
        await Http.post('../ajax/whatsapp.php?op=registrarEnvio', {
            idcliente: c.idcliente,
            idplantilla: p.idplantilla,
            numero: numeroNorm,
            nombre_cliente: c.nombre,
            documento: c.documento,
            mensaje: mensajeRender,
            tipo: 'masivo',
        });
        window.open(url, '_blank');
        enviados++;
        // Esperar 800ms entre cada apertura para no abrumar al navegador
        await new Promise(res => setTimeout(res, 800));
    }

    swalSuccess(enviados + ' conversaciones de WhatsApp abiertas. Confirma cada envío en sus pestañas.', '¡Listo!');
    cargarHistorial();
}

async function cargarHistorial() {
    const data = await Http.get('../ajax/whatsapp.php?op=historial');
    const tb = document.getElementById('hist-body');
    if (!data || data.length === 0) {
        tb.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);">Sin envíos registrados</td></tr>';
        return;
    }
    tb.innerHTML = data.map(h => `
        <tr>
            <td style="white-space:nowrap;">${fmt.datetime(h.enviado)}</td>
            <td><span class="tipo-badge tipo-${h.tipo}">${h.tipo.toUpperCase()}</span></td>
            <td>${$('<div>').text(h.nombre_cliente || '—').html()}</td>
            <td>${h.documento || '—'}</td>
            <td><i class="fa-brands fa-whatsapp" style="color:#25d366;"></i> +${h.numero}</td>
            <td>${h.plantilla_nombre || '—'}</td>
            <td style="font-size:11px;color:var(--text-muted);max-width:240px;">${$('<div>').text(h.mensaje_resumen).html()}</td>
            <td>${h.usuario_login || '—'}</td>
        </tr>`).join('');
}

init();
</script>
</body>
</html>
