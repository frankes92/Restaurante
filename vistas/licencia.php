<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('config_licencia');
$activePage = 'licencia';
$pageTitle  = 'PUERTO HABANA POS — Licencia';
require __DIR__ . '/template/head.php';
?>
<style>
.lic-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
@media (max-width: 980px) { .lic-grid { grid-template-columns: 1fr; } }
.lic-status { padding: 18px; border-radius: 14px; }
.lic-status.activa     { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; }
.lic-status.por_vencer { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; }
.lic-status.vencida    { background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); color: #fff; }
.lic-status.suspendida { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: #fff; }
.lic-status .label { font-size: 11px; opacity: .85; font-weight: 600; }
.lic-status .value { font-size: 22px; font-weight: 800; margin-top: 4px; }
.lic-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
.lic-row:last-child { border-bottom: 0; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Licencia del sistema</div>
                <div class="page-subtitle">Gestión de la licencia (alquiler / vencimiento)</div>
            </div>
        </div>

        <div class="page-content">
            <div class="lic-grid">
                <div class="card">
                    <div class="card-title">Estado actual</div>
                    <div id="lic-status-card" class="lic-status activa">
                        <div class="label">ESTADO</div>
                        <div class="value" id="lic-estado">—</div>
                        <div class="label" style="margin-top:12px;">DÍAS RESTANTES</div>
                        <div class="value" id="lic-dias">—</div>
                    </div>

                    <div style="margin-top:16px;">
                        <div class="lic-row"><span>Cliente</span><b id="lic-cliente">—</b></div>
                        <div class="lic-row"><span>Fecha de inicio</span><b id="lic-inicio">—</b></div>
                        <div class="lic-row"><span>Fecha de vencimiento</span><b id="lic-venc">—</b></div>
                        <div class="lic-row"><span>Días de aviso previo</span><b id="lic-aviso">—</b></div>
                        <div class="lic-row"><span>Última observación</span><b id="lic-obs">—</b></div>
                    </div>

                    <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
                        <button class="btn btn-primary" onclick="abrirExtender()"><i class="fa-solid fa-calendar-plus"></i> Extender / Renovar</button>
                        <button class="btn" onclick="abrirSuspender()"><i class="fa-solid fa-pause"></i> Suspender</button>
                        <button class="btn" onclick="abrirReactivar()"><i class="fa-solid fa-play"></i> Reactivar</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Historial</div>
                    <table id="tbl-historial" class="data-table" style="width:100%;font-size:12px;">
                        <thead>
                            <tr><th>Fecha</th><th>Acción</th><th>Vencimiento</th><th>Monto</th><th>Observación</th><th>Usuario</th></tr>
                        </thead>
                        <tbody id="tbl-hist-body"></tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="margin-top:18px;">
                <div class="card-title"><i class="fa-solid fa-circle-info" style="color:var(--primary);"></i> Información para el proveedor</div>
                <div style="font-size:13px;color:var(--text-muted);line-height:1.6;">
                    Para extender o reactivar la licencia se requiere la <b>clave maestra</b> que únicamente conoce el proveedor del sistema.
                    Esa clave se configura en <code>config/global.php</code> mediante la constante <code>LICENSE_MASTER_KEY</code> o la variable de entorno <code>YAPEZ_LICENSE_KEY</code>.
                    <br><br>
                    Cambia esta clave antes de entregar el sistema a cada cliente. Si el cliente accede al código, la cambiará el atacante; protege el archivo
                    moviendo <code>config/</code> fuera del webroot o usando variables de entorno.
                </div>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script>
function fmtFecha(d) {
    if (!d) return '—';
    // Si es una fecha pura YYYY-MM-DD (sin hora), construirla como fecha LOCAL
    // para evitar que el navegador la interprete como UTC y reste 1 dia en
    // husos negativos (Peru = UTC-5). Si trae hora, se parsea normal.
    const m = String(d).match(/^(\d{4})-(\d{2})-(\d{2})$/);
    const x = m ? new Date(+m[1], +m[2] - 1, +m[3]) : new Date(String(d).replace(' ', 'T'));
    return x.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

async function cargar() {
    const det = await Http.get('../ajax/licencia.php?op=detalle');
    const est = await Http.get('../ajax/licencia.php?op=estado');
    if (!det) return;

    document.getElementById('lic-cliente').textContent = det.cliente_nombre || '—';
    document.getElementById('lic-inicio').textContent  = fmtFecha(det.fecha_inicio);
    document.getElementById('lic-venc').textContent    = fmtFecha(det.fecha_vencimiento);
    document.getElementById('lic-aviso').textContent   = det.dias_aviso + ' días';
    document.getElementById('lic-obs').textContent     = det.observacion || '—';

    let estado = est.estado || 'sin_licencia';
    let card = document.getElementById('lic-status-card');
    let dias = est.dias_restantes ?? 0;
    let cls = estado;
    if (estado === 'activa' && est.avisar) cls = 'por_vencer';
    card.className = 'lic-status ' + cls;
    document.getElementById('lic-estado').textContent = (cls === 'por_vencer' ? 'POR VENCER' : estado).toUpperCase();
    document.getElementById('lic-dias').textContent   = dias >= 0 ? dias : ('-' + Math.abs(dias));

    const hist = await Http.get('../ajax/licencia.php?op=historial');
    const tb = document.getElementById('tbl-hist-body');
    tb.innerHTML = '';
    hist.forEach(h => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${fmtFecha(h.fecha)} ${(new Date(h.fecha.replace(' ','T'))).toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit'})}</td>
            <td><b>${(h.accion||'').toUpperCase()}</b></td>
            <td>${fmtFecha(h.vencimiento_nuevo) || '—'}</td>
            <td>${h.monto_pagado ? fmt.money(h.monto_pagado) : '—'}</td>
            <td>${h.observacion ? $('<div>').text(h.observacion).html() : '—'}</td>
            <td>${h.usuario_nombre || '—'}</td>
        `;
        tb.appendChild(tr);
    });
}

async function abrirExtender() {
    const { value: form } = await Swal.fire({
        title: 'Extender / Renovar licencia',
        html:
            '<div style="text-align:left;">' +
            '<label style="font-size:12px;font-weight:600;">Clave maestra del proveedor</label>' +
            '<input type="password" id="sw-master" class="swal2-input" placeholder="Clave maestra" style="margin:6px 0;">' +
            '<label style="font-size:12px;font-weight:600;">Nueva fecha de vencimiento</label>' +
            '<input type="date" id="sw-fecha" class="swal2-input" style="margin:6px 0;">' +
            '<label style="font-size:12px;font-weight:600;">Monto pagado (opcional)</label>' +
            '<input type="number" id="sw-monto" class="swal2-input" placeholder="0.00" step="0.01" style="margin:6px 0;">' +
            '<label style="font-size:12px;font-weight:600;">Observación</label>' +
            '<input type="text" id="sw-obs" class="swal2-input" placeholder="Renovación mensual" style="margin:6px 0;">' +
            '</div>',
        focusConfirm: false,
        confirmButtonText: 'Aplicar',
        cancelButtonText: 'Cancelar',
        showCancelButton: true,
        confirmButtonColor: '#5b3df5',
        preConfirm: () => {
            const m = document.getElementById('sw-master').value.trim();
            const f = document.getElementById('sw-fecha').value;
            if (!m) { Swal.showValidationMessage('Clave requerida'); return false; }
            if (!f) { Swal.showValidationMessage('Fecha requerida'); return false; }
            return {
                master_key: m, fecha_vencimiento: f,
                monto_pagado: document.getElementById('sw-monto').value || 0,
                observacion:  document.getElementById('sw-obs').value.trim()
            };
        }
    });
    if (!form) return;
    $.post('../ajax/licencia.php?op=extender', form, r => {
        if (r.ok) { showToast(r.msg || 'Licencia actualizada', 'success'); cargar(); }
        else      { swalError(r.msg || 'No se pudo extender'); }
    }, 'json');
}

async function abrirSuspender() {
    const { value: form } = await Swal.fire({
        title: 'Suspender licencia',
        text: 'El sistema quedará bloqueado para todos los usuarios.',
        icon: 'warning',
        html:
            '<input type="password" id="sw-master" class="swal2-input" placeholder="Clave maestra">' +
            '<input type="text" id="sw-obs" class="swal2-input" placeholder="Motivo">',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        preConfirm: () => {
            const m = document.getElementById('sw-master').value.trim();
            if (!m) { Swal.showValidationMessage('Clave requerida'); return false; }
            return { master_key: m, observacion: document.getElementById('sw-obs').value.trim() };
        }
    });
    if (!form) return;
    $.post('../ajax/licencia.php?op=suspender', form, r => {
        if (r.ok) { showToast('Licencia suspendida', 'success'); cargar(); }
        else swalError(r.msg || 'Error');
    }, 'json');
}

async function abrirReactivar() {
    const { value: form } = await Swal.fire({
        title: 'Reactivar licencia',
        html:
            '<input type="password" id="sw-master" class="swal2-input" placeholder="Clave maestra">' +
            '<input type="text" id="sw-obs" class="swal2-input" placeholder="Observación">',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        preConfirm: () => {
            const m = document.getElementById('sw-master').value.trim();
            if (!m) { Swal.showValidationMessage('Clave requerida'); return false; }
            return { master_key: m, observacion: document.getElementById('sw-obs').value.trim() };
        }
    });
    if (!form) return;
    $.post('../ajax/licencia.php?op=reactivar', form, r => {
        if (r.ok) { showToast('Licencia reactivada', 'success'); cargar(); }
        else swalError(r.msg || 'Error');
    }, 'json');
}

cargar();
</script>
</body>
</html>
