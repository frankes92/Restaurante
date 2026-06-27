<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('comprobantes_sunat');
$activePage = 'comprobantes_electronicos';
$pageTitle  = 'PUERTO HABANA POS — Comprobantes SUNAT';
require __DIR__ . '/template/head.php';
?>
<style>
.estado-badge { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.es-pendiente          { background:#fef3c7; color:#92400e; }
.es-generado           { background:#dbeafe; color:#1e40af; }
.es-enviando           { background:#e0e7ff; color:#3730a3; }
.es-aceptado           { background:#d1fae5; color:#065f46; }
.es-aceptado_observado { background:#fef3c7; color:#92400e; }
.es-rechazado          { background:#fee2e2; color:#991b1b; }
.es-baja               { background:#f3f4f6; color:#4b5563; }
.es-error              { background:#fee2e2; color:#991b1b; }
.tipo-01 { color:var(--blue); }
.tipo-03 { color:var(--green); }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Comprobantes Electrónicos</div>
                <div class="page-subtitle">Boletas y facturas emitidas — gestión de envío a SUNAT</div>
            </div>
            <div style="display:flex;gap:10px;">
                <a class="btn" data-perm="resumen_boletas" href="resumenes_sunat">
                    <i class="fa-solid fa-list-check"></i> Resúmenes diarios
                </a>
                <button class="btn btn-primary" data-perm="enviar_sunat" onclick="enviarPendientesMasivo()">
                    <i class="fa-solid fa-paper-plane"></i> Enviar pendientes a SUNAT
                </button>
            </div>
        </div>

        <div class="page-content">
            <div class="filter-row" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
                <button class="filter-pill active" data-estado="">Todos</button>
                <button class="filter-pill" data-estado="pendiente">Pendientes</button>
                <button class="filter-pill" data-estado="generado">Generados</button>
                <button class="filter-pill" data-estado="aceptado">Aceptados</button>
                <button class="filter-pill" data-estado="aceptado_observado">Observados</button>
                <button class="filter-pill" data-estado="rechazado">Rechazados</button>
                <button class="filter-pill" data-estado="error">Con error</button>
                <button class="filter-pill" data-tipo="01">Solo Facturas</button>
                <button class="filter-pill" data-tipo="03">Solo Boletas</button>
            </div>

            <div class="card" style="padding:16px;">
                <table id="tbl-comp" class="data-table" style="width:100%;">
                    <thead><tr>
                        <th>#</th><th>Fecha</th><th>Tipo</th><th>Comprobante</th>
                        <th>Cliente</th><th>Razón Social</th><th>Total</th><th>Estado</th><th></th>
                    </tr></thead>
                </table>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<style>
.filter-pill { padding:8px 16px; border-radius:20px; background:var(--bg-white); border:1px solid var(--border); cursor:pointer; font-size:12px; font-weight:600; color:var(--text-dark); font-family:inherit; }
.filter-pill.active { background:var(--primary); color:#fff; border-color:var(--primary); }
</style>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script>
let dt;
let fEstado = '';
let fTipo = '';

const TIPO_LABELS = { '01': 'Factura', '03': 'Boleta' };
const ESTADOS = {
    pendiente: 'Pendiente', generado: 'Generado', enviando: 'Enviando',
    aceptado: 'Aceptado', aceptado_observado: 'Observado', rechazado: 'Rechazado',
    baja: 'Anulado', error: 'Error'
};

$(function () {
    dt = $('#tbl-comp').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '../ajax/comprobante_electronico.php?op=datatable',
            type: 'POST',
            data: function (d) {
                d.f_estado = fEstado;
                d.f_tipo   = fTipo;
            }
        },
        columns: [
            { data: 'idcomprobante' },
            { data: 'fecha_emision', render: v => v
                ? `<div style="font-size:12px;">${fmt.date(v)}</div><div style="font-size:11px;color:var(--text-muted);">${fmt.time(v)}</div>` : '—' },
            { data: 'tipo_documento', render: v => `<span class="tipo-${v}"><b>${TIPO_LABELS[v] || v}</b></span>` },
            { data: 'numero_completo', render: v => `<b>${v}</b>` },
            { data: 'cliente_num_doc' },
            { data: 'cliente_razon' },
            { data: 'total', render: v => `<span style="font-weight:600;">${fmt.money(v)}</span>` },
            { data: 'estado', render: v => `<span class="estado-badge es-${v}">${ESTADOS[v] || v}</span>` },
            { data: null, orderable: false, render: r => {
                let actions = '';
                // Enviar a SUNAT (solo si tiene permiso enviar_sunat)
                if (can('enviar_sunat') && (r.estado === 'pendiente' || r.estado === 'error' || r.estado === 'rechazado')) {
                    actions += `<button class="btn btn-sm" onclick="enviar(${r.idcomprobante})" title="Enviar a SUNAT"><i class="fa-solid fa-paper-plane"></i></button> `;
                }
                // Consultar estado en SUNAT (solo si ya fue enviado al menos una vez)
                if (can('enviar_sunat') &&
                    ['aceptado','aceptado_observado','rechazado','enviado','enviando'].includes(r.estado)) {
                    actions += `<button class="btn btn-sm" onclick="consultarSunat(${r.idcomprobante})" title="Consultar estado en SUNAT"><i class="fa-solid fa-cloud-arrow-down" style="color:#0284c7;"></i></button> `;
                }
                // Anular con NC (solo si ya esta aceptado y es boleta/factura, y tiene permiso emitir_nc)
                if (can('emitir_nc') &&
                    (r.estado === 'aceptado' || r.estado === 'aceptado_observado') &&
                    (r.tipo_documento === '01' || r.tipo_documento === '03')) {
                    actions += `<button class="btn btn-sm" onclick="anularConNC(${r.idcomprobante})" title="Anular con Nota de Crédito"><i class="fa-solid fa-rotate-left" style="color:var(--orange);"></i></button> `;
                }
                actions += `<button class="btn btn-sm btn-icon" onclick="ver(${r.idcomprobante})" title="Ver detalle"><i class="fa-solid fa-eye"></i></button> `;
                actions += `<a class="btn btn-sm btn-icon" target="_blank" href="../ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=${r.idcomprobante}" title="PDF A4"><i class="fa-solid fa-file-pdf" style="color:#dc2626;"></i></a> `;
                if (r.xml_ruta) actions += `<a class="btn btn-sm btn-icon" href="../ajax/comprobante_electronico.php?op=descargarXml&idcomprobante=${r.idcomprobante}" title="Descargar XML"><i class="fa-solid fa-file-code"></i></a> `;
                if (r.cdr_ruta) actions += `<a class="btn btn-sm btn-icon" target="_blank" href="../ajax/comprobante_electronico.php?op=verCdr&idcomprobante=${r.idcomprobante}" title="Ver CDR (constancia SUNAT)"><i class="fa-solid fa-file-circle-check" style="color:var(--green);"></i></a>`;
                return `<div style="text-align:right;white-space:nowrap;">${actions}</div>`;
            }}
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10,25,50,100],[10,25,50,100]],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copyHtml5',  text: '<i class="fa-solid fa-copy"></i>' },
            { extend: 'excelHtml5', text: '<i class="fa-solid fa-file-excel"></i> Excel', title: 'Comprobantes' },
            { extend: 'csvHtml5',   text: '<i class="fa-solid fa-file-csv"></i> CSV' },
            { extend: 'pdfHtml5',   text: '<i class="fa-solid fa-file-pdf"></i> PDF', title: 'Comprobantes' }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
    });

    $('.filter-pill').on('click', function () {
        if (this.dataset.estado !== undefined) {
            $('.filter-pill[data-estado]').removeClass('active');
            $(this).addClass('active');
            fEstado = this.dataset.estado;
        } else if (this.dataset.tipo !== undefined) {
            const wasActive = $(this).hasClass('active');
            $('.filter-pill[data-tipo]').removeClass('active');
            if (!wasActive) { $(this).addClass('active'); fTipo = this.dataset.tipo; }
            else fTipo = '';
        }
        dt.ajax.reload();
    });
});

window.consultarSunat = async (id) => {
    swalLoading('Consultando a SUNAT...');
    const r = await Http.post('../ajax/comprobante_electronico.php?op=consultarSunat', { idcomprobante: id });
    Swal.close();

    const obsHtml = (r.observaciones && r.observaciones.length)
        ? `<div style="margin-top:8px;text-align:left;font-size:12px;color:#92400e;background:#fef3c7;padding:8px 10px;border-radius:8px;"><b>Observaciones:</b><ul style="margin:4px 0 0 16px;padding:0;">${r.observaciones.map(o => '<li>' + o + '</li>').join('')}</ul></div>`
        : '';

    if (r.ok) {
        // Aceptado o aceptado con observaciones
        const titulo = r.estado === 'aceptado_observado' ? 'Aceptado con observaciones' : 'Aceptado por SUNAT';
        swalSuccess(`Código: <b>${r.codigo || '0'}</b><br>${r.mensaje || ''}${obsHtml}`, titulo);
    } else if (r.estado === 'rechazado') {
        swalError(`Código: <b>${r.codigo}</b><br>${r.mensaje || ''}${obsHtml}`, 'SUNAT rechazó el comprobante');
    } else {
        // No existe en SUNAT, fault, o error de red
        const titulo = r.codigo === '0156' ? 'No registrado en SUNAT' : 'No se pudo consultar';
        swalAlert(`Código: <b>${r.codigo || '-'}</b><br>${r.mensaje || r.msg || ''}`, { icon: 'warning', title: titulo });
    }
    dt.ajax.reload(null, false);
};

window.enviar = async (id) => {
    if (!(await swalConfirm(
        'Se firmará el XML con tu certificado y se enviará al servicio web de SUNAT.',
        { title: '¿Enviar a SUNAT?', icon: 'question', confirmText: 'Sí, enviar' }
    ))) return;
    swalLoading('Enviando a SUNAT...');
    const r = await Http.post('../ajax/comprobante_electronico.php?op=enviarSunat', { idcomprobante: id });
    Swal.close();
    if (r.ok) {
        swalSuccess('Código: ' + (r.codigo || '0') + '<br>' + (r.mensaje || ''), 'Aceptado por SUNAT');
    } else {
        // El backend puede usar 'mensaje' (errores de SUNAT) o 'msg' (errores de permiso/auth)
        const motivo = r.mensaje || r.msg || 'Error desconocido';
        const titulo = r.bloqueado ? 'Sistema bloqueado' :
                       (motivo.toLowerCase().includes('autorizado') || motivo.toLowerCase().includes('permiso')) ? 'Sin permisos' :
                       'SUNAT rechazó el comprobante';
        swalError(motivo, titulo);
    }
    dt.ajax.reload(null, false);
};

// =====================================================================
// ENVIO MASIVO DE PENDIENTES (uno por uno, reintentando los fallidos)
// =====================================================================
let envioMasivoCancelado = false;

window.enviarPendientesMasivo = async () => {
    // 1. Pedir conteos
    let conteos;
    try {
        conteos = await Http.get('../ajax/comprobante_electronico.php?op=contarPendientes');
    } catch (e) {
        return swalError('No se pudo obtener la lista de pendientes');
    }

    const pend = conteos.pendientes || 0;
    const err  = conteos.errores    || 0;
    const rech = conteos.rechazados || 0;
    const baseCount = pend + err;

    if (baseCount === 0 && rech === 0) {
        return swalAlert('No hay comprobantes pendientes ni con error para enviar.',
            { title: 'Todo al día', icon: 'success' });
    }

    // 2. Modal de confirmacion con opcion de incluir rechazados
    const { value: result, isConfirmed } = await Swal.fire({
        title: 'Envío masivo a SUNAT',
        html:
            `<div style="text-align:left;font-size:13px;line-height:1.6;">` +
            `<div style="margin-bottom:10px;">Se enviarán los siguientes comprobantes uno por uno:</div>` +
            `<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:14px;">` +
                `<div style="padding:10px;background:#fef3c7;border-radius:8px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#92400e;">${pend}</div>
                    <div style="font-size:10px;font-weight:600;color:#92400e;">PENDIENTES</div>
                </div>` +
                `<div style="padding:10px;background:#fee2e2;border-radius:8px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#991b1b;">${err}</div>
                    <div style="font-size:10px;font-weight:600;color:#991b1b;">CON ERROR</div>
                </div>` +
                `<div style="padding:10px;background:#f3f4f6;border-radius:8px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#4b5563;">${rech}</div>
                    <div style="font-size:10px;font-weight:600;color:#4b5563;">RECHAZADOS</div>
                </div>` +
            `</div>` +
            (rech > 0 ?
                `<label style="display:flex;align-items:flex-start;gap:8px;background:#f9fafb;padding:10px;border-radius:8px;cursor:pointer;">
                    <input type="checkbox" id="incluir-rech" style="margin-top:3px;">
                    <div>
                        <div style="font-weight:600;font-size:13px;">Incluir también los <b>${rech} rechazados</b></div>
                        <div style="font-size:11px;color:var(--text-muted);">Útil si arreglaste el problema (firma, IGV, datos del cliente, etc.)</div>
                    </div>
                </label>`
                : '') +
            `<div style="font-size:11px;color:var(--text-muted);margin-top:12px;">` +
            `Si alguno falla, continuará con los siguientes. Al final se reintentará automáticamente cada uno que haya fallado.` +
            `</div></div>`,
        width: 540,
        showCancelButton: true,
        confirmButtonText: 'Comenzar envío',
        cancelButtonText:  'Cancelar',
        confirmButtonColor: '#5b3df5',
        focusConfirm: false,
        preConfirm: () => {
            const cb = document.getElementById('incluir-rech');
            return { incluir_rech: cb ? cb.checked : false };
        }
    });

    if (!isConfirmed) return;
    const incluirRech = result.incluir_rech ? 1 : 0;

    // 3. Cargar la lista real de IDs
    let pendientes;
    try {
        pendientes = await Http.get('../ajax/comprobante_electronico.php?op=listarPendientes&incluir_rechazados=' + incluirRech);
    } catch (e) {
        return swalError('No se pudo obtener la lista');
    }
    if (!Array.isArray(pendientes) || pendientes.length === 0) {
        return swalAlert('No hay comprobantes para enviar con esos criterios.', { icon: 'info' });
    }

    envioMasivoCancelado = false;

    // 3. Mostrar modal de progreso (Swal con HTML custom)
    Swal.fire({
        title: 'Enviando a SUNAT...',
        html: progresoHtml(0, pendientes.length, 0, 0, '', []),
        allowOutsideClick: false,
        allowEscapeKey: false,
        showCancelButton: true,
        showConfirmButton: false,
        cancelButtonText: 'Detener',
        cancelButtonColor: '#ef4444',
        didRender: () => {
            const cancelBtn = Swal.getCancelButton();
            if (cancelBtn) cancelBtn.onclick = () => { envioMasivoCancelado = true; };
        }
    });

    // 4. Procesar uno por uno (primera pasada)
    const fallidos = [];
    const exitosos = [];
    const log = [];

    for (let i = 0; i < pendientes.length; i++) {
        if (envioMasivoCancelado) break;

        const c = pendientes[i];
        actualizarProgreso(i, pendientes.length, exitosos.length, fallidos.length,
            `Enviando ${c.numero_completo}...`, log);

        const r = await enviarUno(c.idcomprobante);
        const msg = `${c.numero_completo} — ${r.ok ? 'OK' : (r.mensaje || 'error')}`;
        log.unshift({ ok: r.ok, msg: msg });
        if (log.length > 30) log.pop();

        if (r.ok) exitosos.push(c);
        else      fallidos.push(c);

        actualizarProgreso(i + 1, pendientes.length, exitosos.length, fallidos.length, '', log);
    }

    // 5. Reintento de fallidos (UNA pasada extra)
    let reintentadosOk = [];
    if (!envioMasivoCancelado && fallidos.length > 0) {
        actualizarProgreso(pendientes.length, pendientes.length, exitosos.length, fallidos.length,
            `Reintentando ${fallidos.length} fallido(s)...`, log);
        await new Promise(r => setTimeout(r, 800));

        for (let i = fallidos.length - 1; i >= 0; i--) {
            if (envioMasivoCancelado) break;
            const c = fallidos[i];
            actualizarProgreso(pendientes.length, pendientes.length, exitosos.length, fallidos.length,
                `Reintento ${c.numero_completo}...`, log);

            const r = await enviarUno(c.idcomprobante);
            const msg = `[Reintento] ${c.numero_completo} — ${r.ok ? 'OK' : (r.mensaje || 'error')}`;
            log.unshift({ ok: r.ok, msg: msg });
            if (log.length > 30) log.pop();

            if (r.ok) {
                reintentadosOk.push(c);
                fallidos.splice(i, 1); // se quita de fallidos
                exitosos.push(c);
                actualizarProgreso(pendientes.length, pendientes.length, exitosos.length, fallidos.length, '', log);
            }
        }
    }

    Swal.close();
    dt.ajax.reload(null, false);

    // 6. Resumen final
    const total = pendientes.length;
    const okCount = exitosos.length;
    const failCount = fallidos.length;

    if (envioMasivoCancelado) {
        await Swal.fire({
            icon: 'info',
            title: 'Proceso detenido por el usuario',
            html: `Aceptados: <b>${okCount}</b><br>Pendientes/fallidos: <b>${total - okCount}</b>`,
            confirmButtonColor: '#5b3df5',
        });
    } else if (failCount === 0) {
        await Swal.fire({
            icon: 'success',
            title: '¡Todos enviados!',
            html: `<b>${okCount}</b> comprobante(s) aceptado(s) por SUNAT.${reintentadosOk.length ? `<br><small>${reintentadosOk.length} fueron exitosos en el reintento.</small>` : ''}`,
            confirmButtonColor: '#10b981',
        });
    } else {
        const listaFallidos = fallidos.map(f => `<li><b>${f.numero_completo}</b> — ${f.cliente_razon}</li>`).join('');
        await Swal.fire({
            icon: 'warning',
            title: 'Envío finalizado con errores',
            html: `Aceptados: <b>${okCount}</b> · Fallidos: <b>${failCount}</b><br><br>` +
                  `<div style="text-align:left;font-size:13px;max-height:240px;overflow:auto;">` +
                  `<b>No pudieron enviarse:</b><ul style="margin-top:6px;padding-left:20px;">${listaFallidos}</ul>` +
                  `<small style="color:var(--text-muted);">Revisa el motivo de cada uno con el botón "Ver detalle".</small></div>`,
            width: 560,
            confirmButtonColor: '#5b3df5',
        });
    }
};

async function enviarUno(idcomprobante) {
    try {
        return await Http.post('../ajax/comprobante_electronico.php?op=enviarSunat', { idcomprobante });
    } catch (e) {
        return { ok: false, mensaje: 'Error de red' };
    }
}

function progresoHtml(actual, total, ok, fail, accion, log) {
    const pct = total === 0 ? 0 : Math.round((actual / total) * 100);
    const logHtml = log.map(l =>
        `<li style="color:${l.ok ? '#065f46' : '#991b1b'};font-size:11px;line-height:1.5;">
            <i class="fa-solid fa-${l.ok ? 'check' : 'xmark'}"></i> ${l.msg}
        </li>`
    ).join('');
    return `
        <div style="text-align:left;font-size:13px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;margin-bottom:6px;">
                <span>${actual} / ${total}</span><span>${pct}%</span>
            </div>
            <div style="background:#e5e7eb;border-radius:6px;overflow:hidden;height:10px;margin-bottom:10px;">
                <div style="width:${pct}%;height:100%;background:linear-gradient(90deg,#5b3df5,#7c3aed);transition:width .3s;"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
                <div style="padding:8px;background:#d1fae5;border-radius:8px;text-align:center;">
                    <div style="font-size:18px;font-weight:700;color:#065f46;">${ok}</div>
                    <div style="font-size:10px;color:#065f46;font-weight:600;">ACEPTADOS</div>
                </div>
                <div style="padding:8px;background:#fee2e2;border-radius:8px;text-align:center;">
                    <div style="font-size:18px;font-weight:700;color:#991b1b;">${fail}</div>
                    <div style="font-size:10px;color:#991b1b;font-weight:600;">FALLIDOS</div>
                </div>
            </div>
            ${accion ? `<div style="font-size:12px;color:var(--primary);font-weight:600;margin-bottom:8px;">
                <i class="fa-solid fa-spinner fa-spin"></i> ${accion}
            </div>` : ''}
            <ul style="list-style:none;padding:0;margin:0;max-height:160px;overflow:auto;border:1px solid #e5e7eb;border-radius:6px;padding:6px;">
                ${logHtml || '<li style="color:#9ca3af;font-size:11px;">(esperando...)</li>'}
            </ul>
        </div>
    `;
}

function actualizarProgreso(actual, total, ok, fail, accion, log) {
    const cont = Swal.getHtmlContainer();
    if (cont) cont.innerHTML = progresoHtml(actual, total, ok, fail, accion, log);
}

// =====================================================================
// ANULAR CON NOTA DE CREDITO
// =====================================================================
window.anularConNC = async (idcomprobante) => {
    // Cargar motivos del catalogo 9
    const motivos = await Http.get('../ajax/nota.php?op=motivos&tipo=07');
    const opciones = motivos.map(m => `<option value="${m.codigo}">${m.codigo} — ${m.descripcion}</option>`).join('');

    const { value: form, isConfirmed } = await Swal.fire({
        title: 'Anular con Nota de Crédito',
        html:
            '<div style="text-align:left;font-size:13px;">' +
            '<div style="margin-bottom:10px;color:var(--text-muted);">Se generará una nota de crédito que anula el comprobante. La NC se enviará a SUNAT automáticamente.</div>' +
            '<label style="font-size:12px;font-weight:600;">Motivo de la anulación</label>' +
            `<select id="sw-motivo" class="swal2-input" style="margin:6px 0;">${opciones}</select>` +
            '<label style="font-size:12px;font-weight:600;">Observación (opcional)</label>' +
            '<input type="text" id="sw-obs" class="swal2-input" placeholder="Detalle adicional" style="margin:6px 0;">' +
            '</div>',
        showCancelButton: true,
        confirmButtonText: 'Generar y enviar NC',
        cancelButtonText:  'Cancelar',
        confirmButtonColor: '#f59e0b',
        focusConfirm: false,
        preConfirm: () => {
            const cod = document.getElementById('sw-motivo').value;
            const sel = document.getElementById('sw-motivo').options[document.getElementById('sw-motivo').selectedIndex].text.split('—')[1].trim();
            const obs = document.getElementById('sw-obs').value.trim();
            return {
                motivo_codigo: cod,
                motivo_descripcion: obs ? sel + ' - ' + obs : sel,
            };
        }
    });
    if (!isConfirmed) return;

    swalLoading('Generando nota y enviando a SUNAT...');
    const r = await Http.post('../ajax/nota.php?op=crear', {
        tipo_nota: '07',
        idcomprobante: idcomprobante,
        motivo_codigo: form.motivo_codigo,
        motivo_descripcion: form.motivo_descripcion,
        auto_enviar: 1,
    });
    Swal.close();
    if (!r.ok) return swalError(r.msg || 'Error al crear la nota');

    const env = r.envio || {};
    if (env.ok) {
        swalSuccess('Nota de crédito ' + r.serie + '-' + (r.numero || '').replace(/^0+/,'') + ' generada y aceptada por SUNAT.', 'Anulación completada');
    } else {
        swalAlert('La NC se generó pero el envío a SUNAT falló: ' + (env.mensaje || env.msg || ''), { icon: 'warning', title: 'Generada con advertencia' });
    }
    dt.ajax.reload(null, false);
};

window.ver = async (id) => {
    const c = await Http.post('../ajax/comprobante_electronico.php?op=mostrar', { idcomprobante: id });
    if (!c) return;
    const filas = (c.items || []).map(i => `
        <tr><td style="padding:4px 6px;">${Number(i.cantidad)}× ${i.descripcion}</td>
            <td style="padding:4px 6px;text-align:right;font-weight:600;">${fmt.money(i.total_item)}</td></tr>`).join('');
    const html = `
        <div style="text-align:left;font-size:13px;">
            <div><b>${TIPO_LABELS[c.tipo_documento]}</b> · ${fmt.datetime(c.fecha_emision)}</div>
            <div style="margin:6px 0;color:#6b7280;">${c.cliente_num_doc} · ${c.cliente_razon}</div>
            <table style="width:100%;border-collapse:collapse;border-top:1px dashed #d1d5db;border-bottom:1px dashed #d1d5db;margin:8px 0;">${filas}</table>
            <div style="text-align:right;">
                <div>Subtotal: <b>${fmt.money(c.subtotal)}</b></div>
                <div>IGV: <b>${fmt.money(c.igv)}</b></div>
                <div style="font-size:18px;color:#5b3df5;margin-top:6px;">Total: <b>${fmt.money(c.total)}</b></div>
            </div>
            <div style="margin-top:10px;padding:8px;background:#f9fafb;border-radius:8px;">
                <small><b>Estado:</b> ${c.estado}<br>
                <b>CDR:</b> ${c.cdr_codigo || '-'} ${c.cdr_descripcion || ''}</small>
            </div>
        </div>`;
    Swal.fire({ title: c.numero_completo, html, width: 500, confirmButtonColor: '#5b3df5', confirmButtonText: 'Cerrar' });
};
</script>
</body>
</html>
