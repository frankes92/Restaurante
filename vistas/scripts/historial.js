/* historial.js - DataTables server-side + filtros (rango y metodo) */

let dt = null;
let dateFilter = 'all';   // all, hoy, semana, mes
let payFilter  = '';      // efectivo, tarjeta, yape, transferencia
let compFilter = '';      // ticket, nota_venta, boleta, factura

// Etiquetas y estilo del tipo de comprobante
const COMP_LABELS = { ticket: 'Ticket', nota_venta: 'Nota Venta', boleta: 'Boleta', factura: 'Factura' };
const COMP_BADGE  = { ticket: 'badge-gray', nota_venta: 'badge-orange', boleta: 'badge-green', factura: 'badge-blue' };
const CE_ESTADO_LBL = { aceptado: 'Aceptado', aceptado_observado: 'Observado', rechazado: 'Rechazado', pendiente: 'Pendiente', generado: 'Generado', enviado: 'Enviado', baja: 'Baja' };

const TYPE_LABELS  = { dine_in: 'Local', para_llevar: 'Para Llevar', delivery: 'Delivery' };
const TYPE_BADGE   = { dine_in: 'badge-purple', para_llevar: 'badge-orange', delivery: 'badge-blue' };
const PAY_LABELS   = { efectivo: 'Efectivo', tarjeta: 'Tarjeta', yape: 'Yape/Plin', transferencia: 'Transferencia' };
const PAY_ICONS    = {
    efectivo:      { i: 'fa-money-bill',     c: 'var(--green)',  bg: 'var(--green-light)' },
    tarjeta:       { i: 'fa-credit-card',    c: 'var(--blue)',   bg: '#dbeafe' },
    yape:          { i: 'fa-mobile-screen',  c: 'var(--primary)', bg: 'var(--primary-light)' },
    transferencia: { i: 'fa-building-columns', c: 'var(--orange)', bg: '#fef3c7' }
};

async function loadStats() {
    // Stats globales (sobre todas las pagadas)
    const all = await API.ordenes({ estado: 'pagada' }) || [];
    const totalVentas = all.reduce((s, o) => s + Number(o.total), 0);
    document.getElementById('s-count').textContent = all.length;
    document.getElementById('s-total').textContent = fmt.money(totalVentas);
    document.getElementById('s-avg').textContent   = all.length ? fmt.money(totalVentas / all.length) : 'S/ 0.00';
    const hoyTotal = all.filter(o => {
        const d = new Date((o.fecha_pago || '').replace(' ', 'T'));
        return d.toDateString() === new Date().toDateString();
    }).reduce((s, o) => s + Number(o.total), 0);
    document.getElementById('s-today').textContent = fmt.money(hoyTotal);
}

function initDataTable() {
    dt = $('#tbl-historial').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '../ajax/orden.php?op=datatable',
            type: 'POST',
            data: function (d) {
                d.f_estado  = 'pagada';
                d.f_rango   = dateFilter === 'all' ? '' : dateFilter;
                d.f_metodo  = payFilter;
                d.f_comprobante = compFilter;
            }
        },
        columns: [
            { data: 'numero', render: v => `<b>#${v}</b>` },
            { data: 'fecha_pago', render: v => v
                ? `<div style="font-size:13px;">${fmt.date(v)}</div><div style="font-size:11px;color:var(--text-muted);">${fmt.time(v)}</div>`
                : '—' },
            { data: null, orderable: false, render: row => {
                const tc = row.tipo_comprobante || 'ticket';
                const lbl = COMP_LABELS[tc] || tc;
                const badge = COMP_BADGE[tc] || 'badge-gray';
                // Numero fiscal si es boleta/factura electronica
                let nro = '';
                if (row.ce_numero_completo) {
                    const est = row.ce_estado ? (CE_ESTADO_LBL[row.ce_estado] || row.ce_estado) : '';
                    nro = `<div style="font-size:11px;color:var(--text-muted);margin-top:2px;">${row.ce_numero_completo}${est ? ' · ' + est : ''}</div>`;
                }
                return `<span class="badge ${badge}">${lbl}</span>${nro}`;
            }},
            { data: 'tipo', render: v => `<span class="badge ${TYPE_BADGE[v] || ''}">${TYPE_LABELS[v] || v}</span>` },
            { data: 'mesa_numero', render: v => v ? `Mesa ${v}` : '—' },
            { data: 'total_items', render: v => `${v || 0} productos` },
            { data: 'metodo_pago', render: v => {
                const pay = PAY_ICONS[v] || PAY_ICONS.efectivo;
                return `<div style="display:flex;align-items:center;gap:8px;">
                    <span class="pay-icon" style="background:${pay.bg};color:${pay.c};"><i class="fa-solid ${pay.i}"></i></span>
                    <span style="font-size:13px;">${PAY_LABELS[v] || '—'}</span>
                </div>`;
            }},
            { data: 'total', render: v => `<span style="font-weight:700;color:var(--primary);">${fmt.money(v)}</span>` },
            { data: null, orderable: false, render: row => `
                <div style="text-align:right;">
                    <button class="btn btn-sm btn-icon" onclick="verDetalle(${row.idorden})"><i class="fa-solid fa-eye"></i></button>
                    <button class="btn btn-sm btn-icon" onclick="imprimir(${row.idorden})"><i class="fa-solid fa-print"></i></button>
                </div>`
            }
        ],
        order: [[1, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copyHtml5',  text: '<i class="fa-solid fa-copy"></i> Copiar', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'excelHtml5', text: '<i class="fa-solid fa-file-excel"></i> Excel', title: 'Historial', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'csvHtml5',   text: '<i class="fa-solid fa-file-csv"></i> CSV', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'pdfHtml5',   text: '<i class="fa-solid fa-file-pdf"></i> PDF', title: 'Historial', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'print',      text: '<i class="fa-solid fa-print"></i> Imprimir', exportOptions: { columns: ':not(:last-child)' } }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
    });
}

window.verDetalle = async (id) => {
    const o = await API.ordenMostrar(id);
    if (!o) return;
    const filas = o.items.map(i => `
        <tr>
            <td style="padding:4px 6px;">${Number(i.cantidad)}× ${i.nombre}${i.nota ? '<br><small style="color:#5b3df5;">' + i.nota + '</small>' : ''}</td>
            <td style="padding:4px 6px;text-align:right;font-weight:600;">${fmt.money(i.subtotal)}</td>
        </tr>`).join('');
    const html = `
        <div style="text-align:left;font-size:13px;">
            <div style="margin-bottom:8px;">
                <b>${TYPE_LABELS[o.tipo]}</b>${o.mesa_numero ? ' · Mesa ' + o.mesa_numero : ''}
                <br><small style="color:#6b7280;">${fmt.datetime(o.fecha_pago)} · ${PAY_LABELS[o.metodo_pago] || '—'}</small>
                <br><small style="color:#6b7280;">Mozo: ${o.mozo || '—'}</small>
            </div>
            <table style="width:100%;border-collapse:collapse;border-top:1px dashed #d1d5db;border-bottom:1px dashed #d1d5db;margin:8px 0;">
                ${filas}
            </table>
            <div style="text-align:right;">
                <div>Subtotal: <b>${fmt.money(o.subtotal)}</b></div>
                <div>IGV: <b>${fmt.money(o.igv)}</b></div>
                <div style="font-size:18px;color:#5b3df5;margin-top:6px;">Total: <b>${fmt.money(o.total)}</b></div>
            </div>
        </div>`;
    Swal.fire({ title: 'Orden #' + o.numero, html, width: 460, confirmButtonColor: '#5b3df5', confirmButtonText: 'Cerrar' });
};

// Reimprimir: abre el comprobante en ventana nueva (mismo que se usa al cobrar)
window.imprimir = (id) => {
    const fmtComp = (window.YAPEZ_CONFIG && window.YAPEZ_CONFIG.formato_comprobante) || 'ticket';
    const w = fmtComp === 'a4' ? 880 : 420;
    const h = fmtComp === 'a4' ? 980 : 720;
    window.open('comprobante?id=' + id + '&formato=' + fmtComp, '_blank', 'width=' + w + ',height=' + h);
};

// Compatibilidad con boton "Exportar CSV" del header
window.exportCSV = () => {
    if (dt) dt.button('.buttons-csv').trigger();
};

// Filtros
document.querySelectorAll('.filter-pill').forEach(b => b.addEventListener('click', () => {
    if (b.dataset.filter) {
        document.querySelectorAll('.filter-pill[data-filter]').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        dateFilter = b.dataset.filter;
    } else if (b.dataset.pay) {
        const wasActive = b.classList.contains('active');
        document.querySelectorAll('.filter-pill[data-pay]').forEach(x => x.classList.remove('active'));
        if (!wasActive) { b.classList.add('active'); payFilter = b.dataset.pay; }
        else            { payFilter = ''; }
    } else if (b.dataset.comp !== undefined) {
        document.querySelectorAll('.filter-pill[data-comp]').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        compFilter = b.dataset.comp;
    }
    if (dt) dt.ajax.reload();
}));

$(function () {
    loadStats();
    initDataTable();
});
