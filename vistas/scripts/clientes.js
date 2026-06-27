/* clientes.js - DataTables server-side */

let dt = null;
let editingId = null;

function initials(name) {
    return (name || '').split(' ').slice(0, 2).map(p => p[0] || '').join('').toUpperCase();
}

async function loadStats() {
    const stats = await API.clienteEstadisticas();
    document.getElementById('stat-total').textContent   = stats.total || 0;
    document.getElementById('stat-vip').textContent     = stats.vip || 0;
    document.getElementById('stat-avg').textContent     = Number(stats.promedio_ordenes || 0).toFixed(1);
    document.getElementById('stat-revenue').textContent = fmt.money(stats.ventas_total || 0);
}

function initDataTable() {
    dt = $('#tbl-clientes').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '../ajax/cliente.php?op=datatable',
            type: 'POST'
        },
        columns: [
            { data: null, orderable: false, render: function (row) {
                const ini = initials(row.nombre);
                return `
                    <div class="client-cell">
                        <div class="client-avatar">${ini || 'U'}</div>
                        <div>
                            <div class="client-name">${row.nombre || ''}</div>
                            <div class="client-doc">${row.documento || 'Sin documento'}</div>
                        </div>
                    </div>`;
            }},
            { data: 'nombre' },
            { data: 'documento', defaultContent: '—' },
            { data: 'telefono',  defaultContent: '—' },
            { data: 'email',     defaultContent: '—' },
            { data: 'total_ordenes', render: v => `<span class="badge badge-purple">${v || 0} órdenes</span>` },
            { data: 'total_gastado', render: v => `<span style="font-weight:600;">${fmt.money(v)}</span>` },
            { data: 'ultima_visita', render: v => v ? `<span style="color:var(--text-muted);">${fmt.date(v)}</span>` : '—' },
            { data: null, orderable: false, render: row => `
                <div style="text-align:right;">
                    <button class="btn btn-sm btn-icon" onclick="editClient(${row.idcliente})"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-sm btn-icon" onclick="deleteClient(${row.idcliente})"><i class="fa-solid fa-trash"></i></button>
                </div>`
            }
        ],
        columnDefs: [
            { targets: 1, visible: false }, // columna "nombre" oculta (la pintamos en col 0)
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copyHtml5',  text: '<i class="fa-solid fa-copy"></i> Copiar',  exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'excelHtml5', text: '<i class="fa-solid fa-file-excel"></i> Excel', title: 'Clientes', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'csvHtml5',   text: '<i class="fa-solid fa-file-csv"></i> CSV', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'pdfHtml5',   text: '<i class="fa-solid fa-file-pdf"></i> PDF', title: 'Clientes', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'print',      text: '<i class="fa-solid fa-print"></i> Imprimir', exportOptions: { columns: ':not(:last-child)' } }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
        }
    });
}

window.openAdd = () => {
    editingId = null;
    document.getElementById('modal-title').textContent = 'Nuevo Cliente';
    ['c-name','c-doc','c-phone','c-email'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('c-tipodoc').value = '1';
    document.getElementById('c-doc-status').textContent = '';
    document.getElementById('c-doc-status').style.color = 'var(--text-muted)';
    openModal('modal');
};

window.editClient = async (id) => {
    const c = await Http.post('../ajax/cliente.php?op=mostrar', { idcliente: id });
    if (!c) return;
    editingId = id;
    document.getElementById('modal-title').textContent = 'Editar Cliente';
    document.getElementById('c-name').value  = c.nombre || '';
    document.getElementById('c-doc').value   = c.documento || '';
    document.getElementById('c-phone').value = c.telefono || '';
    document.getElementById('c-email').value = c.email || '';
    // Detectar tipo doc por la longitud
    const doc = (c.documento || '').trim();
    document.getElementById('c-tipodoc').value = (doc.length === 11) ? '6' : '1';
    document.getElementById('c-doc-status').textContent = '';
    openModal('modal');
};

// Consulta SUNAT/RENIEC via el endpoint proxy.
// Flujo: 1) check duplicado local 2) si no esta, consultar SUNAT y rellenar
window.consultarSunat = async () => {
    const tipo  = document.getElementById('c-tipodoc').value;
    const num   = document.getElementById('c-doc').value.trim();
    const status = document.getElementById('c-doc-status');

    if (!num) { showToast('Ingresa un número de documento', 'error'); return; }
    if (tipo === '1' && num.length !== 8) { showToast('El DNI debe tener 8 dígitos', 'error'); return; }
    if (tipo === '6' && num.length !== 11) { showToast('El RUC debe tener 11 dígitos', 'error'); return; }

    status.textContent = 'Verificando...';
    status.style.color = 'var(--text-muted)';

    // 1) Verificar si ya existe localmente (anti-duplicado)
    const existente = await Http.post('../ajax/cliente.php?op=buscarPorDocumento', { documento: num });
    if (existente && existente.idcliente && (!editingId || Number(existente.idcliente) !== Number(editingId))) {
        const ok = await swalConfirm(
            'Este documento ya está registrado a nombre de:<br><b>' + (existente.nombre || '—') + '</b><br><br>¿Deseas abrirlo para editar?',
            { title: 'Cliente ya existe', icon: 'info', confirmText: 'Sí, editar' }
        );
        if (ok) { closeModal('modal'); editClient(existente.idcliente); }
        else    { status.textContent = '⚠ Ya registrado: ' + existente.nombre; status.style.color = '#dc2626'; }
        return;
    }

    // 2) Consultar SUNAT/RENIEC
    status.textContent = 'Consultando SUNAT/RENIEC...';
    const r = await Http.post('../ajax/sunat.php?op=consultar', { tipo_doc: tipo, numero: num });
    if (!r || !r.ok) {
        status.textContent = '✗ ' + (r && r.msg ? r.msg : 'No se pudo consultar');
        status.style.color = '#dc2626';
        return;
    }

    // 3) Rellenar campos
    document.getElementById('c-name').value = r.razon_social || '';
    status.textContent = '✓ Encontrado: ' + (r.razon_social || '');
    status.style.color = '#059669';
    showToast('Datos cargados desde ' + (tipo === '6' ? 'SUNAT' : 'RENIEC'), 'success');
};

window.saveClient = async () => {
    const nombre = document.getElementById('c-name').value.trim();
    if (!nombre) { showToast('El nombre es obligatorio', 'error'); return; }
    const payload = {
        nombre,
        documento: document.getElementById('c-doc').value.trim(),
        telefono:  document.getElementById('c-phone').value.trim(),
        email:     document.getElementById('c-email').value.trim()
    };
    if (editingId) payload.idcliente = editingId;

    const r = await API.clienteGuardar(payload);
    if (r.ok) {
        showToast(editingId ? 'Cliente actualizado' : 'Cliente registrado', 'success');
        closeModal('modal');
        dt.ajax.reload(null, false);
        loadStats();
    } else if (r.duplicado) {
        // Backend rechazo por documento ya registrado
        const ok = await swalConfirm(
            (r.msg || 'Este documento ya está registrado') + '<br><br>¿Deseas abrir ese cliente para editar?',
            { title: 'Documento duplicado', icon: 'warning', confirmText: 'Sí, abrir' }
        );
        if (ok && r.idcliente) {
            closeModal('modal');
            editClient(r.idcliente);
        }
    } else {
        showToast(r.msg || 'Error al guardar', 'error');
    }
};

window.deleteClient = async (id) => {
    if (!(await swalConfirm('¿Eliminar este cliente? Se desactivará y no aparecerá en la lista.', { title: 'Eliminar cliente', icon: 'warning', confirmText: 'Sí, eliminar' }))) return;
    const r = await API.clienteDesactivar(id);
    if (r.ok) {
        showToast('Cliente eliminado', 'success');
        dt.ajax.reload(null, false);
        loadStats();
    }
};

$(function () {
    loadStats();
    initDataTable();
});
