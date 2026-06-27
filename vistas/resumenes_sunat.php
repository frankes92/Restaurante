<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('resumen_boletas');
$activePage = 'resumenes_sunat';
$pageTitle  = 'PUERTO HABANA POS — Resúmenes SUNAT';
require __DIR__ . '/template/head.php';
?>
<style>
.estado-badge { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.es-pendiente          { background:#fef3c7; color:#92400e; }
.es-generado           { background:#dbeafe; color:#1e40af; }
.es-enviado            { background:#e0e7ff; color:#3730a3; }
.es-aceptado           { background:#d1fae5; color:#065f46; }
.es-aceptado_observado { background:#fef3c7; color:#92400e; }
.es-rechazado          { background:#fee2e2; color:#991b1b; }
.es-error              { background:#fee2e2; color:#991b1b; }
.tipo-RC { background:#dbeafe; color:#1e40af; padding:3px 10px; border-radius:6px; font-size:11px; font-weight:700; }
.tipo-RA { background:#fee2e2; color:#991b1b; padding:3px 10px; border-radius:6px; font-size:11px; font-weight:700; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Resúmenes SUNAT</div>
                <div class="page-subtitle">RC (Resumen Diario de Boletas) y RA (Comunicación de Baja)</div>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn btn-primary" data-perm="resumen_boletas" onclick="generarRC()">
                    <i class="fa-solid fa-receipt"></i> Generar Resumen Diario (RC)
                </button>
            </div>
        </div>

        <div class="page-content">
            <div class="card" style="padding:18px;margin-bottom:14px;background:#eff6ff;border:1px solid #bfdbfe;">
                <div style="display:flex;gap:14px;align-items:flex-start;">
                    <i class="fa-solid fa-circle-info" style="color:#1e40af;font-size:22px;margin-top:3px;"></i>
                    <div style="font-size:13px;line-height:1.6;">
                        <b style="color:#1e40af;">¿Cuándo enviar cada resumen?</b><br>
                        <b>RC (Resumen Diario)</b>: SUNAT exige enviar TODAS las boletas del día. Se puede enviar el mismo día o hasta 7 días después.<br>
                        <b>RA (Comunicación de Baja)</b>: para anular boletas/facturas ya aceptadas por SUNAT (alternativa a la Nota de Crédito).<br>
                        SUNAT entrega un <b>ticket</b> al enviar cada resumen; debes consultar el ticket para conocer el resultado (puede tomar segundos o minutos).
                    </div>
                </div>
            </div>

            <div class="card" style="padding:16px;">
                <table id="tbl-res" class="data-table" style="width:100%;">
                    <thead><tr>
                        <th>#</th><th>Tipo</th><th>Identificador</th><th>Fecha referencia</th>
                        <th>Items</th><th>Estado</th><th>Ticket</th><th>CDR</th><th></th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script>
const ESTADOS = {
    pendiente: 'Pendiente', generado: 'Generado', enviado: 'Enviado',
    aceptado: 'Aceptado', aceptado_observado: 'Observado',
    rechazado: 'Rechazado', error: 'Error'
};

async function cargar() {
    const data = await Http.get('../ajax/resumen.php?op=listar');
    const tb = $('#tbl-res tbody');
    tb.empty();
    if (!data || data.length === 0) {
        tb.append('<tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:30px;">No hay resúmenes generados todavía.</td></tr>');
        return;
    }
    data.forEach(r => {
        let actions = '';
        if (r.estado === 'pendiente' || r.estado === 'generado' || r.estado === 'error') {
            actions += `<button class="btn btn-sm" onclick="enviar(${r.idresumen})" title="Enviar a SUNAT"><i class="fa-solid fa-paper-plane"></i></button> `;
        }
        if (r.ticket && r.estado !== 'aceptado' && r.estado !== 'rechazado') {
            actions += `<button class="btn btn-sm" onclick="consultarTicket(${r.idresumen})" title="Consultar ticket"><i class="fa-solid fa-search"></i></button> `;
        }
        actions += `<button class="btn btn-sm btn-icon" onclick="ver(${r.idresumen})" title="Ver detalle"><i class="fa-solid fa-eye"></i></button>`;

        const tr = $(`
            <tr>
                <td>#${r.idresumen}</td>
                <td><span class="tipo-${r.tipo}">${r.tipo}</span></td>
                <td><b>${r.serie_doc}</b></td>
                <td>${fmt.date(r.fecha_referencia)}</td>
                <td>${r.total_items}</td>
                <td><span class="estado-badge es-${r.estado}">${ESTADOS[r.estado] || r.estado}</span></td>
                <td>${r.ticket || '—'}</td>
                <td style="font-size:11px;color:var(--text-muted);">${r.cdr_descripcion ? $('<div>').text(r.cdr_descripcion).html().substr(0,80) : '—'}</td>
                <td style="text-align:right;">${actions}</td>
            </tr>
        `);
        tb.append(tr);
    });
    if (window.applyPermissionsDOM) applyPermissionsDOM(document.getElementById('tbl-res'));
}

async function generarRC() {
    const { value: fecha, isConfirmed } = await Swal.fire({
        title: 'Generar Resumen Diario de Boletas',
        html:
            '<div style="text-align:left;font-size:13px;">' +
            '<div style="margin-bottom:10px;color:var(--text-muted);">Se incluirán todas las boletas del día seleccionado que aún no estén en otro resumen.</div>' +
            '<label style="font-size:12px;font-weight:600;">Fecha de las boletas</label>' +
            '<input type="date" id="sw-fecha" class="swal2-input" value="' + (new Date()).toISOString().slice(0,10) + '">' +
            '</div>',
        showCancelButton: true,
        confirmButtonText: 'Generar resumen',
        confirmButtonColor: '#5b3df5',
        preConfirm: () => {
            const f = document.getElementById('sw-fecha').value;
            if (!f) { Swal.showValidationMessage('Fecha requerida'); return false; }
            return f;
        }
    });
    if (!isConfirmed) return;

    swalLoading('Generando RC...');
    const r = await Http.post('../ajax/resumen.php?op=crearRC', { fecha });
    Swal.close();
    if (!r.ok) return swalError(r.msg || 'Error');
    swalSuccess(`Resumen ${r.serie_doc} generado con ${r.total} boletas. Ahora envíalo a SUNAT.`);
    cargar();
}

async function enviar(id) {
    if (!(await swalConfirm('Se firmará y enviará a SUNAT (sendSummary). Devolverá un ticket que se consulta después.', { title: '¿Enviar resumen?', icon: 'question', confirmText: 'Enviar' }))) return;
    swalLoading('Enviando a SUNAT...');
    const r = await Http.post('../ajax/resumen.php?op=enviar', { idresumen: id });
    Swal.close();
    if (r.ok) {
        await swalSuccess('Ticket recibido: <b>' + r.ticket + '</b><br>Ahora consulta el ticket para obtener el CDR.', 'Enviado');
    } else {
        await swalError(r.mensaje || 'Error al enviar');
    }
    cargar();
}

async function consultarTicket(id) {
    swalLoading('Consultando ticket...');
    const r = await Http.post('../ajax/resumen.php?op=consultarTicket', { idresumen: id });
    Swal.close();
    if (r.ok) {
        await swalSuccess('Estado: <b>' + r.estado + '</b><br>' + (r.mensaje || ''), 'Resumen aceptado');
    } else if (r.en_proceso || r.transitorio) {
        await Swal.fire({ icon: 'info', title: 'En proceso', text: r.mensaje || 'SUNAT aún está procesando el resumen. Reintenta en unos segundos.' });
    } else {
        await swalError(r.mensaje || 'Error');
    }
    cargar();
}

async function ver(id) {
    const r = await Http.get('../ajax/resumen.php?op=mostrar&idresumen=' + id);
    if (!r) return;
    const filas = (r.items || []).map(i => `
        <tr><td style="padding:4px 8px;font-size:12px;">${i.serie}-${(i.numero||'').replace(/^0+/,'')}</td>
            <td style="padding:4px 8px;font-size:12px;">${i.cliente_num_doc || '—'}</td>
            <td style="padding:4px 8px;text-align:right;font-size:12px;">${r.tipo === 'RC' ? fmt.money(i.total) : (i.motivo_baja || '—')}</td></tr>`).join('');
    Swal.fire({
        title: r.serie_doc,
        html: `
            <div style="text-align:left;font-size:13px;">
                <div><b>Tipo:</b> ${r.tipo} · <b>Estado:</b> ${r.estado}</div>
                <div><b>Fecha referencia:</b> ${fmt.date(r.fecha_referencia)}</div>
                ${r.ticket ? '<div><b>Ticket:</b> ' + r.ticket + '</div>' : ''}
                ${r.cdr_descripcion ? '<div style="background:#f9fafb;padding:8px;border-radius:6px;margin-top:6px;font-size:11px;">' + $('<div>').text(r.cdr_descripcion).html() + '</div>' : ''}
                <table style="width:100%;border-collapse:collapse;margin-top:10px;border-top:1px solid #eee;">
                    <thead><tr style="background:#f9fafb;font-size:11px;"><th style="padding:4px 8px;text-align:left;">Comprobante</th><th style="padding:4px 8px;text-align:left;">Cliente</th><th style="padding:4px 8px;text-align:right;">${r.tipo === 'RC' ? 'Total' : 'Motivo'}</th></tr></thead>
                    <tbody>${filas}</tbody>
                </table>
            </div>`,
        width: 600,
        confirmButtonColor: '#5b3df5',
        confirmButtonText: 'Cerrar'
    });
}

cargar();
</script>
</body>
</html>
