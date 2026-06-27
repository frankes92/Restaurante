/* categorias.js - DataTable + CRUD */
let dt;

$(function () {
    dt = $('#tbl-cat').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: '../ajax/categoria.php?op=datatable', type: 'POST' },
        columns: [
            { data: 'idcategoria' },
            { data: 'codigo', render: v => `<code>${v}</code>` },
            { data: null, render: r => `<span style="display:inline-flex;align-items:center;gap:8px;"><i class="fa-solid ${r.icono || 'fa-tag'}" style="color:${r.color||'#666'};font-size:14px;"></i> ${r.nombre}</span>` },
            { data: 'orden' },
            { data: 'mostrar_comanda', render: v => Number(v)===0
                ? '<span class="badge badge-red" title="No aparece en la comanda de cocina"><i class="fa-solid fa-eye-slash"></i> Oculta</span>'
                : '<span class="badge badge-green"><i class="fa-solid fa-receipt"></i> Sí</span>' },
            { data: 'estado', render: v => Number(v)===1
                ? '<span class="badge badge-green">Activa</span>'
                : '<span class="badge badge-red">Inactiva</span>' },
            { data: null, orderable:false, render: r => `
                <div style="text-align:right;">
                    <button class="btn btn-sm btn-icon" onclick="editar(${r.idcategoria})"><i class="fa-solid fa-pen"></i></button>
                    ${Number(r.estado)===1
                        ? `<button class="btn btn-sm btn-icon" onclick="toggle(${r.idcategoria}, 0)" title="Desactivar"><i class="fa-solid fa-ban"></i></button>`
                        : `<button class="btn btn-sm btn-icon" onclick="toggle(${r.idcategoria}, 1)" title="Activar"><i class="fa-solid fa-check"></i></button>`}
                </div>`
            }
        ],
        order: [[3, 'asc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fa-solid fa-file-excel"></i> Excel', title: 'Categorias' },
            { extend: 'pdfHtml5',   text: '<i class="fa-solid fa-file-pdf"></i> PDF',   title: 'Categorias' }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
    });
});

window.openAdd = () => {
    $('#modal-cat-title').text('Nueva categoría');
    ['c-id','c-codigo','c-nombre','c-icono'].forEach(i => $('#'+i).val(''));
    $('#c-color').val('#5b3df5');
    $('#c-orden').val(0);
    $('#c-mostrar-comanda').prop('checked', true);
    openModal('modal-cat');
};

window.editar = async (id) => {
    const r = await Http.post('../ajax/categoria.php?op=mostrar', { idcategoria: id });
    if (!r) return;
    $('#modal-cat-title').text('Editar categoría');
    $('#c-id').val(r.idcategoria);
    $('#c-codigo').val(r.codigo);
    $('#c-nombre').val(r.nombre);
    $('#c-icono').val(r.icono || '');
    $('#c-color').val(r.color || '#5b3df5');
    $('#c-orden').val(r.orden || 0);
    // Si la categoría es vieja y no trae el campo, por defecto SÍ se muestra.
    $('#c-mostrar-comanda').prop('checked', Number(r.mostrar_comanda) !== 0);
    openModal('modal-cat');
};

window.guardar = async () => {
    const codigo = $('#c-codigo').val().trim();
    const nombre = $('#c-nombre').val().trim();
    if (!codigo || !nombre) { showToast('Código y nombre obligatorios', 'error'); return; }
    const payload = {
        idcategoria: $('#c-id').val(),
        codigo, nombre,
        icono: $('#c-icono').val().trim() || 'fa-tag',
        color: $('#c-color').val(),
        orden: $('#c-orden').val() || 0,
        mostrar_comanda: $('#c-mostrar-comanda').is(':checked') ? 1 : 0
    };
    const r = await Http.post('../ajax/categoria.php?op=guardaryeditar', payload);
    if (r.ok) { showToast('Guardado', 'success'); closeModal('modal-cat'); dt.ajax.reload(null, false); }
    else showToast(r.msg || 'Error', 'error');
};

window.toggle = async (id, activar) => {
    const op = activar === 1 ? 'activar' : 'desactivar';
    const r = await Http.post('../ajax/categoria.php?op=' + op, { idcategoria: id });
    if (r.ok) dt.ajax.reload(null, false);
};
