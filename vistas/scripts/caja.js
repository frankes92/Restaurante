/* caja.js — sesión activa + DataTable de movimientos + arqueo */

let movementType = 'ingreso';
let sesionActual = null;
let dt = null;
let dtc = null;   // DataTable del historial de cierres

// Denominaciones tipicas (Peru). Para otras monedas, configurar en empresa.
const DENOMS = [
    { v: 200,  l: 'S/ 200',  type: 'billete' },
    { v: 100,  l: 'S/ 100',  type: 'billete' },
    { v: 50,   l: 'S/ 50',   type: 'billete' },
    { v: 20,   l: 'S/ 20',   type: 'billete' },
    { v: 10,   l: 'S/ 10',   type: 'billete' },
    { v: 5,    l: 'S/ 5',    type: 'moneda'  },
    { v: 2,    l: 'S/ 2',    type: 'moneda'  },
    { v: 1,    l: 'S/ 1',    type: 'moneda'  },
    { v: 0.50, l: 'S/ 0.50', type: 'moneda'  },
    { v: 0.20, l: 'S/ 0.20', type: 'moneda'  },
    { v: 0.10, l: 'S/ 0.10', type: 'moneda'  },
];

const MOVE_STYLES = {
    apertura: { i: 'fa-lock-open',     bg: 'var(--primary-light)', c: 'var(--primary)',    sign: '' },
    venta:    { i: 'fa-cart-shopping', bg: 'var(--green-light)',   c: 'var(--green)',      sign: '+' },
    ingreso:  { i: 'fa-arrow-down',    bg: 'var(--green-light)',   c: 'var(--green)',      sign: '+' },
    egreso:   { i: 'fa-arrow-up',      bg: '#fee2e2',              c: 'var(--red)',        sign: '−' },
    cierre:   { i: 'fa-lock',          bg: '#f3f4f6',              c: 'var(--text-muted)', sign: '' }
};

async function load() {
    // El historial de cierres se muestra siempre (haya o no caja abierta).
    cargarHistorialCierres();

    sesionActual = await API.cajaSesion();
    if (!sesionActual || !sesionActual.idsesion) {
        document.getElementById('cash-subtitle').textContent = 'No hay caja abierta — abre una sesión para empezar a vender';
        // Mostrar mensaje grande con boton de apertura
        const balance = document.getElementById('cash-balance');
        if (balance) {
            balance.innerHTML = '<span style="font-size:18px;opacity:0.85;">Caja cerrada</span>';
        }
        // Inyectar boton flotante grande para abrir caja
        if (!document.getElementById('btn-abrir-caja')) {
            const headerBtns = document.querySelector('.page-header > div:last-child');
            if (headerBtns) {
                const b = document.createElement('button');
                b.id = 'btn-abrir-caja';
                b.className = 'btn btn-primary';
                b.style.cssText = 'animation: pulse 1.5s ease-in-out infinite;';
                b.innerHTML = '<i class="fa-solid fa-lock-open"></i> Abrir Caja';
                b.onclick = abrirCaja;
                headerBtns.insertBefore(b, headerBtns.firstChild);
            }
        }
        return;
    }
    // Si ya hay sesion, quitar el boton de apertura si quedo
    const btnApertura = document.getElementById('btn-abrir-caja');
    if (btnApertura) btnApertura.remove();

    document.getElementById('cash-subtitle').textContent =
        'Caja ' + sesionActual.caja_codigo + ' · ' + sesionActual.turno + ' · Abierta desde ' + fmt.time(sesionActual.fecha_apertura);

    const [resumen, ventasMet] = await Promise.all([
        API.cajaResumen(sesionActual.idsesion),
        API.cajaVentasMetodo(sesionActual.idsesion)
    ]);

    const initial = Number(resumen.monto_inicial || 0);
    const sales   = Number(resumen.total_ventas || 0);
    const income  = Number(resumen.total_ingresos || 0);
    const expense = Number(resumen.total_egresos || 0);
    const efectivoVentas = (ventasMet.find(v => v.metodo_pago === 'efectivo') || { total: 0 }).total;
    const balance = initial + Number(efectivoVentas) + income - expense;

    document.getElementById('cash-balance').textContent  = fmt.money(balance);
    document.getElementById('cash-initial').textContent  = fmt.money(initial);
    document.getElementById('cash-sales').textContent    = fmt.money(sales);
    document.getElementById('cash-income').textContent   = fmt.money(income);
    document.getElementById('cash-expense').textContent  = fmt.money(expense);

    const get = (m) => Number((ventasMet.find(v => v.metodo_pago === m) || { total: 0 }).total);
    document.getElementById('pay-efectivo').textContent      = fmt.money(get('efectivo'));
    document.getElementById('pay-tarjeta').textContent       = fmt.money(get('tarjeta'));
    document.getElementById('pay-yape').textContent          = fmt.money(get('yape'));
    const elPlin = document.getElementById('pay-plin');
    if (elPlin) elPlin.textContent = fmt.money(get('plin'));
    document.getElementById('pay-transferencia').textContent = fmt.money(get('transferencia'));

    initDataTable();
}

function initDataTable() {
    if (dt) { dt.ajax.reload(null, false); return; }

    dt = $('#tbl-movimientos').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '../ajax/caja.php?op=datatableMovimientos',
            type: 'POST',
            data: function (d) {
                d.idsesion = sesionActual.idsesion;
            }
        },
        columns: [
            { data: 'idmovimiento', render: v => `#${v}` },
            { data: 'fecha', render: v => `<div style="font-size:12px;">${fmt.date(v)}</div><div style="font-size:11px;color:var(--text-muted);">${fmt.time(v)}</div>` },
            { data: 'tipo', render: v => {
                const s = MOVE_STYLES[v] || MOVE_STYLES.ingreso;
                return `<span style="display:inline-flex;align-items:center;gap:6px;">
                    <span style="width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:${s.bg};color:${s.c};font-size:10px;"><i class="fa-solid ${s.i}"></i></span>
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;">${v}</span>
                </span>`;
            }},
            { data: 'metodo_pago', defaultContent: '—' },
            { data: 'monto', render: (v, t, row) => {
                const s = MOVE_STYLES[row.tipo] || MOVE_STYLES.ingreso;
                return `<span style="font-weight:700;color:${s.c};">${s.sign}${fmt.money(v)}</span>`;
            }},
            { data: 'nota', defaultContent: '—' }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copyHtml5',  text: '<i class="fa-solid fa-copy"></i> Copiar' },
            { extend: 'excelHtml5', text: '<i class="fa-solid fa-file-excel"></i> Excel', title: 'Movimientos' },
            { extend: 'csvHtml5',   text: '<i class="fa-solid fa-file-csv"></i> CSV' },
            { extend: 'pdfHtml5',   text: '<i class="fa-solid fa-file-pdf"></i> PDF', title: 'Movimientos' },
            { extend: 'print',      text: '<i class="fa-solid fa-print"></i> Imprimir' }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
    });
}

// =====================================================================
// ABRIR CAJA (apertura de sesion)
// =====================================================================
window.abrirCaja = async () => {
    const u = window.YAPEZ_USER || {};
    const cajeroDefault = (u.nombre || '') + ' ' + (u.apellidos || '');

    const { value: form, isConfirmed } = await Swal.fire({
        title: '<i class="fa-solid fa-lock-open" style="color:#10b981;"></i> Abrir Caja',
        html:
            '<div style="text-align:left;font-size:13px;">' +
            '<div style="margin-bottom:14px;color:var(--text-muted);">Configura los datos de apertura. El monto inicial es el efectivo físico que tienes en caja al comenzar el turno.</div>' +
            '<label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Caja</label>' +
            '<input type="text" id="sw-caja" class="swal2-input" value="AP-001" style="margin:4px 0;width:90%;">' +
            '<label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;margin-top:6px;">Turno</label>' +
            '<select id="sw-turno" class="swal2-input" style="margin:4px 0;width:90%;">' +
                '<option value="Mañana">Mañana</option>' +
                '<option value="Tarde">Tarde</option>' +
                '<option value="Noche">Noche</option>' +
                '<option value="Completo">Completo</option>' +
            '</select>' +
            '<label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;margin-top:6px;">Cajero</label>' +
            '<input type="text" id="sw-cajero" class="swal2-input" value="' + cajeroDefault.trim() + '" style="margin:4px 0;width:90%;">' +
            '<label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;margin-top:6px;">Monto inicial (efectivo en caja)</label>' +
            '<input type="number" id="sw-monto" class="swal2-input" placeholder="0.00" step="0.10" min="0" style="margin:4px 0;width:90%;">' +
            '</div>',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-lock-open"></i> Abrir Caja',
        cancelButtonText:  'Cancelar',
        confirmButtonColor: '#10b981',
        focusConfirm: false,
        preConfirm: () => {
            const caja_codigo  = document.getElementById('sw-caja').value.trim() || 'AP-001';
            const turno        = document.getElementById('sw-turno').value;
            const cajero       = document.getElementById('sw-cajero').value.trim();
            const monto        = parseFloat(document.getElementById('sw-monto').value) || 0;
            if (!cajero) { Swal.showValidationMessage('Cajero requerido'); return false; }
            if (monto < 0) { Swal.showValidationMessage('Monto inválido'); return false; }
            return { caja_codigo, turno, cajero, monto_inicial: monto };
        }
    });

    if (!isConfirmed) return;

    const r = await API.cajaAbrir(form);
    if (r.ok) {
        await swalSuccess('Caja abierta con S/ ' + Number(form.monto_inicial).toFixed(2) + '. Ya puedes empezar a cobrar órdenes.', '¡Caja abierta!');
        load();
    } else {
        await swalError(r.msg || 'No se pudo abrir la caja');
    }
};

window.addMovementModal = (type) => {
    if (!sesionActual || !sesionActual.idsesion) { showToast('No hay sesión abierta', 'error'); return; }
    movementType = type;
    document.getElementById('modal-title').textContent = type === 'ingreso' ? 'Registrar Ingreso' : 'Registrar Egreso';
    document.getElementById('m-amount').value = '';
    document.getElementById('m-note').value = '';
    openModal('modal');
};

window.saveMovement = async () => {
    const monto = parseFloat(document.getElementById('m-amount').value);
    const nota  = document.getElementById('m-note').value.trim();
    if (!monto || monto <= 0) { showToast('Monto inválido', 'error'); return; }
    if (!nota) { showToast('Agrega un concepto', 'error'); return; }

    const r = await API.cajaAgregarMov({
        idsesion: sesionActual.idsesion,
        tipo:     movementType,
        monto:    monto,
        nota:     nota
    });
    if (r.ok) {
        showToast((movementType === 'ingreso' ? 'Ingreso' : 'Egreso') + ' registrado', 'success');
        closeModal('modal');
        load();
    } else if (r.msg) {
        showToast(r.msg, 'error');
    }
};

// =====================================================================
// ARQUEO
// =====================================================================
let arqueoCerrarSesion = false;
let arqueoEsperado = null;

window.abrirArqueo = async (cerrar) => {
    if (!sesionActual || !sesionActual.idsesion) { showToast('No hay sesión abierta', 'error'); return; }
    arqueoCerrarSesion = !!cerrar;

    document.getElementById('arqueo-title').textContent = cerrar ? 'Cierre de Caja' : 'Arqueo de Caja';
    document.getElementById('btn-arqueo-label').textContent = cerrar ? 'Imprimir Arqueo y Cerrar Caja' : 'Guardar Arqueo';

    document.getElementById('arq-obs').value = '';
    document.getElementById('arq-contado-input').value = '';

    // Pedir esperado al backend
    const r = await Http.post('../ajax/caja.php?op=arqueoEsperado', { idsesion: sesionActual.idsesion });
    if (!r.ok) { showToast(r.msg || 'No se pudo calcular el esperado', 'error'); return; }
    arqueoEsperado = r;

    const info = `Caja: <b>${sesionActual.caja_codigo || '—'}</b> · Turno: <b>${sesionActual.turno || '—'}</b><br>Apertura: ${fmt.datetime(sesionActual.fecha_apertura)}`;
    document.getElementById('arq-info-sesion').innerHTML = info;
    document.getElementById('arq-inicial').textContent  = fmt.money(r.monto_inicial);
    document.getElementById('arq-vefec').textContent    = fmt.money(r.ventas_efectivo);
    document.getElementById('arq-ingr').textContent     = fmt.money(r.ingresos);
    document.getElementById('arq-egr').textContent      = '- ' + fmt.money(r.egresos);
    document.getElementById('arq-esperado').textContent = fmt.money(r.esperado);

    // Prellenar el input contado con el esperado para acelerar el flujo
    document.getElementById('arq-contado-input').value = Number(r.esperado).toFixed(2);
    recalcularArqueo();
    openModal('modal-arqueo');
};

window.recalcularArqueo = () => {
    const contado = +(parseFloat(document.getElementById('arq-contado-input').value) || 0).toFixed(2);

    if (!arqueoEsperado) return;
    const diff = +(contado - arqueoEsperado.esperado).toFixed(2);
    const diffEl = document.getElementById('arq-diff');
    if (diff === 0)      { diffEl.textContent = '✓ Cuadrado';                          diffEl.style.color = '#10b981'; }
    else if (diff > 0)   { diffEl.textContent = 'Sobrante ' + fmt.money(diff);         diffEl.style.color = '#2563eb'; }
    else                 { diffEl.textContent = 'Falta '    + fmt.money(Math.abs(diff)); diffEl.style.color = '#dc2626'; }
};

window.confirmarArqueo = async () => {
    const contado = +(parseFloat(document.getElementById('arq-contado-input').value) || 0).toFixed(2);

    const op = arqueoCerrarSesion ? 'arqueoCerrarConSesion' : 'arqueoGuardar';

    if (arqueoCerrarSesion) {
        const ok = await swalConfirm(
            'Esto cerrará la caja. Asegúrate de haber contado todo. <br><br>Diferencia: <b>' + document.getElementById('arq-diff').textContent + '</b>',
            { title: '¿Cerrar caja?', icon: 'warning', confirmText: 'Sí, cerrar e imprimir', confirmColor: '#ef4444' }
        );
        if (!ok) return;
    }

    const idsesionCerrada = sesionActual.idsesion;
    const r = await Http.post('../ajax/caja.php?op=' + op, {
        idsesion:        idsesionCerrada,
        monto_contado:   contado,
        denominaciones:  '{}',
        observacion:     document.getElementById('arq-obs').value.trim()
    });
    if (r.ok) {
        showToast(arqueoCerrarSesion ? 'Caja cerrada · Imprimiendo arqueo' : 'Arqueo guardado', 'success');
        closeModal('modal-arqueo');
        // Abrir el ticket de arqueo en ventana nueva (siempre al cerrar; opcional al guardar)
        if (arqueoCerrarSesion) {
            window.open('arqueo_print.php?idsesion=' + idsesionCerrada, '_blank', 'width=420,height=720');
        }
        load();
    } else {
        showToast(r.msg || 'Error', 'error');
    }
};

window.imprimirArqueoSesion = (idsesion) => {
    window.open('arqueo_print.php?idsesion=' + idsesion, '_blank', 'width=420,height=720');
};

// Historial de cierres: tabla con todas las sesiones cerradas + botón de reimprimir.
async function cargarHistorialCierres() {
    const data = await Http.get('../ajax/caja.php?op=historialCierres') || [];
    const rows = Array.isArray(data) ? data : [];
    if (dtc) { dtc.clear().rows.add(rows).draw(); return; }

    dtc = $('#tbl-cierres').DataTable({
        data: rows,
        responsive: true,
        columns: [
            { data: 'idsesion', render: v => '#' + v },
            { data: null, render: r => `${(r.caja_codigo || '').toString()}${r.turno ? ' · ' + r.turno : ''}` },
            { data: 'fecha_apertura', render: (v, type) => {
                // Para ordenar/buscar usa el valor crudo (YYYY-MM-DD HH:MM:SS, ordena bien);
                // para mostrar, el formato dd/mm/aaaa hh:mm.
                if (type === 'sort' || type === 'type') return v || '';
                return v ? (fmt.date(v) + ' ' + fmt.time(v)) : '—';
            }},
            { data: 'fecha_cierre',   render: (v, type) => {
                if (type === 'sort' || type === 'type') return v || '';
                return v ? (fmt.date(v) + ' ' + fmt.time(v)) : '—';
            }},
            { data: null, render: r => {
                const n = (r.cajero_nombre || '').trim();
                return n !== '' ? n : (r.cajero_libre || '—');
            }},
            { data: 'ventas',       className: 'text-right', render: v => fmt.money(v) },
            { data: 'monto_cierre', className: 'text-right', render: v => (v == null ? '—' : fmt.money(v)) },
            { data: 'diferencia',   className: 'text-right', render: v => {
                if (v == null) return '<span style="color:#9ca3af;">—</span>';
                const n = Number(v);
                const color = n === 0 ? 'var(--green)' : (n > 0 ? 'var(--blue)' : 'var(--red)');
                return `<span style="font-weight:700;color:${color};">${fmt.money(n)}</span>`;
            }},
            { data: 'idsesion', orderable: false, render: v =>
                `<button class="btn btn-sm" onclick="imprimirArqueoSesion(${v})"><i class="fa-solid fa-print"></i> Imprimir</button>` }
        ],
        order: [[2, 'desc']],   // por FECHA DE APERTURA, de la más reciente a la más antigua
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fa-solid fa-file-excel"></i> Excel', title: 'Cierres de caja' },
            { extend: 'pdfHtml5',   text: '<i class="fa-solid fa-file-pdf"></i> PDF',   title: 'Cierres de caja' },
            { extend: 'print',      text: '<i class="fa-solid fa-print"></i> Imprimir' }
        ]
    });
}

window.reimprimirArqueo = () => {
    if (!sesionActual || !sesionActual.idsesion) {
        showToast('No hay sesión activa para reimprimir', 'error');
        return;
    }
    window.open('arqueo_print.php?idsesion=' + sesionActual.idsesion, '_blank', 'width=420,height=720');
};

load();
