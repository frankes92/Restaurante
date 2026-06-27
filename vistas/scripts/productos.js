/* productos.js - DataTable + CRUD + multiprecios + upload imagen */
let dt;
let categorias = [];
let fCategoria = '';
let preciosActuales = []; // cache de precios al editar

async function cargarCategorias() {
    categorias = await Http.get('../ajax/categoria.php?op=listar');
    const opts = '<option value="">— Todas las categorías —</option>' +
        categorias.filter(c => Number(c.estado)===1).map(c => `<option value="${c.idcategoria}">${c.nombre}</option>`).join('');
    $('#filtro-cat').html(opts);
    $('#p-categoria').html(categorias.filter(c => Number(c.estado)===1).map(c => `<option value="${c.idcategoria}">${c.nombre}</option>`).join(''));
}

$(async function () {
    await cargarCategorias();

    dt = $('#tbl-prod').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: {
            url: '../ajax/producto.php?op=datatable',
            type: 'POST',
            data: function (d) { d.f_categoria = fCategoria; }
        },
        columns: [
            { data: 'idproducto' },
            { data: 'codigo', render: v => `<code>${v}</code>` },
            { data: null, render: r => `<div class="prod-cell"><div class="prod-img-cell" style="background-image:url('${r.imagen||''}');"></div><div><div style="font-weight:600;">${r.nombre}</div>${r.popular==1 ? '<span class="badge badge-orange" style="font-size:10px;">Popular</span>' : ''} ${r.favorito==1 ? '<span class="badge badge-purple" style="font-size:10px;">Favorito</span>' : ''}</div></div>` },
            { data: 'categoria_nombre', defaultContent: '—' },
            { data: 'precio', render: v => `<span style="font-weight:600;color:var(--primary);">${fmt.money(v)}</span>` },
            { data: 'estado', render: v => Number(v)===1
                ? '<span class="badge badge-green">Activo</span>'
                : '<span class="badge badge-red">Inactivo</span>' },
            { data: null, orderable:false, render: r => `
                <div style="text-align:right;">
                    <button class="btn btn-sm btn-icon" onclick="editar(${r.idproducto})"><i class="fa-solid fa-pen"></i></button>
                    ${Number(r.estado)===1
                        ? `<button class="btn btn-sm btn-icon" onclick="toggle(${r.idproducto}, 0)" title="Desactivar"><i class="fa-solid fa-ban"></i></button>`
                        : `<button class="btn btn-sm btn-icon" onclick="toggle(${r.idproducto}, 1)" title="Activar"><i class="fa-solid fa-check"></i></button>`}
                </div>`
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fa-solid fa-file-excel"></i> Excel', title: 'Productos' },
            { extend: 'pdfHtml5',   text: '<i class="fa-solid fa-file-pdf"></i> PDF',   title: 'Productos' }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
    });

    $('#filtro-cat').on('change', function () {
        fCategoria = this.value;
        dt.ajax.reload();
    });

    $('#p-imagen').on('input', function () {
        const v = this.value.trim();
        if (v) { $('#p-preview-img').attr('src', v); $('#p-preview').show(); }
        else $('#p-preview').hide();
    });
});

// ---------- Precios / Variantes ----------
function renderPrecios(precios) {
    const cont = document.getElementById('p-precios-list');
    if (!precios || !precios.length) {
        // Por defecto al menos 1 fila vacia marcada como default
        precios = [{ nombre: 'Normal', precio: '', es_default: 1 }];
    }
    cont.innerHTML = precios.map((p, i) => filaPrecioHtml(p, i)).join('');
}

function filaPrecioHtml(p, i) {
    const checked = Number(p.es_default) === 1 ? 'checked' : '';
    // data-* conservan el estado de inventario de cada presentacion existente
    return `
        <div class="precio-row" style="display:flex;gap:6px;align-items:center;"
             data-idprecio="${p.idprecio || ''}"
             data-controla="${Number(p.controla_stock)||0}"
             data-stock="${p.stock != null ? p.stock : 0}"
             data-min="${p.stock_minimo != null ? p.stock_minimo : 0}">
            <input type="text"   class="input-field pp-nombre" placeholder="Nombre (Personal, Vaso, Mayorista...)" value="${(p.nombre||'').replace(/"/g,'&quot;')}" style="flex:2;" oninput="if(window.refrescarStockList)refrescarStockList()">
            <input type="number" class="input-field pp-precio" placeholder="0.00" step="0.10" min="0" value="${p.precio||''}" style="flex:1;">
            <label title="Por defecto" style="display:flex;align-items:center;gap:4px;font-size:12px;flex-shrink:0;">
                <input type="radio" name="pp-default" class="pp-default" ${checked}> Default
            </label>
            <button type="button" class="btn btn-sm btn-icon" onclick="quitarFilaPrecio(this)" title="Quitar">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>`;
}

window.agregarFilaPrecio = () => {
    const cont = document.getElementById('p-precios-list');
    cont.insertAdjacentHTML('beforeend', filaPrecioHtml({ nombre: '', precio: '', es_default: 0 }, cont.children.length));
    if (window.refrescarStockList) refrescarStockList();
};

window.quitarFilaPrecio = (btn) => {
    const fila = btn.closest('.precio-row');
    if (fila) fila.remove();
    // Asegurar que al menos un radio quede marcado
    const radios = document.querySelectorAll('.pp-default');
    if (radios.length && !document.querySelector('.pp-default:checked')) {
        radios[0].checked = true;
    }
    if (window.refrescarStockList) refrescarStockList();
};

function recolectarPrecios() {
    const filas = document.querySelectorAll('#p-precios-list .precio-row');
    const out = [];
    filas.forEach(f => {
        const nombre = f.querySelector('.pp-nombre').value.trim();
        const precio = parseFloat(f.querySelector('.pp-precio').value);
        const def    = f.querySelector('.pp-default').checked ? 1 : 0;
        if (nombre && !isNaN(precio) && precio > 0) {
            out.push({ nombre, precio, es_default: def });
        }
    });
    return out;
}

// ---------- Stock por presentación ----------
// Pinta una fila de stock (actual + mínimo) por cada presentación con nombre.
window.refrescarStockList = () => {
    const cont = document.getElementById('p-stock-list');
    if (!cont) return;
    const filas = document.querySelectorAll('#p-precios-list .precio-row');
    let rows = '';
    filas.forEach(f => {
        const nombre = f.querySelector('.pp-nombre').value.trim();
        if (!nombre) return;
        const stock = f.dataset.stock || 0;
        const min   = f.dataset.min || 0;
        const idp   = f.dataset.idprecio || '';
        rows += `
            <div class="stock-row" data-nombre="${nombre.replace(/"/g,'&quot;')}" data-idprecio="${idp}"
                 style="display:grid;grid-template-columns:1.6fr 1fr 1fr;gap:8px;align-items:center;">
                <span style="font-size:12px;font-weight:600;">${nombre}</span>
                <input type="number" class="input-field st-stock" step="1" min="0" value="${stock}" placeholder="0" title="Cantidad actual en stock">
                <input type="number" class="input-field st-min"   step="1" min="0" value="${min}"   placeholder="0" title="Cuando llega a este número, alerta amarilla">
            </div>`;
    });
    if (!rows) {
        cont.innerHTML = '<small style="color:var(--text-muted);">Agrega presentaciones con nombre arriba para asignarles stock.</small>';
        return;
    }
    // Encabezado de columnas para que se distinga cada campo
    const head = `
        <div style="display:grid;grid-template-columns:1.6fr 1fr 1fr;gap:8px;align-items:end;margin-bottom:2px;">
            <span style="font-size:10px;font-weight:700;color:var(--text-muted);letter-spacing:.4px;">PRESENTACIÓN</span>
            <span style="font-size:10px;font-weight:700;color:var(--text-muted);letter-spacing:.4px;text-align:center;">STOCK ACTUAL</span>
            <span style="font-size:10px;font-weight:700;color:var(--text-muted);letter-spacing:.4px;text-align:center;">STOCK MÍNIMO</span>
        </div>`;
    cont.innerHTML = head + rows;
};

window.toggleStockFields = () => {
    const on = document.getElementById('p-controla-stock').checked;
    document.getElementById('p-stock-fields').style.display = on ? 'block' : 'none';
    if (on) refrescarStockList();
};

// ---------- Subir imagen ----------
window.subirImagen = async () => {
    const inp = document.getElementById('p-archivo');
    if (!inp.files || !inp.files[0]) { showToast('Selecciona un archivo', 'error'); return; }
    const fd = new FormData();
    fd.append('archivo', inp.files[0]);
    const idProd = $('#p-id').val();
    if (idProd) fd.append('idproducto', idProd);
    try {
        const r = await $.ajax({
            url: '../ajax/producto.php?op=subirImagen',
            type: 'POST', data: fd, contentType: false, processData: false, dataType: 'json'
        });
        if (r.ok) {
            $('#p-imagen').val(r.url).trigger('input');
            showToast('Imagen subida', 'success');
            inp.value = '';
        } else showToast(r.msg || 'Error al subir', 'error');
    } catch (e) {
        showToast('Error al subir imagen', 'error');
    }
};

// ---------- CRUD ----------
window.openAdd = () => {
    $('#modal-prod-title').text('Nuevo producto');
    ['p-id','p-codigo','p-nombre','p-imagen'].forEach(i => $('#'+i).val(''));
    $('#p-archivo').val('');
    $('#p-popular,#p-favorito').prop('checked', false);
    $('#p-afectacion').val('10');
    $('#p-preview').hide();
    $('#p-controla-stock').prop('checked', false);
    renderPrecios([{ nombre: 'Normal', precio: '', es_default: 1 }]);
    toggleStockFields();
    openModal('modal-prod');
};

window.editar = async (id) => {
    const r = await Http.post('../ajax/producto.php?op=mostrar', { idproducto: id });
    if (!r) return;
    $('#modal-prod-title').text('Editar producto');
    $('#p-id').val(r.idproducto);
    $('#p-codigo').val(r.codigo);
    $('#p-nombre').val(r.nombre);
    $('#p-categoria').val(r.idcategoria);
    $('#p-imagen').val(r.imagen || '');
    $('#p-archivo').val('');
    $('#p-popular').prop('checked', Number(r.popular)===1);
    $('#p-favorito').prop('checked', Number(r.favorito)===1);
    $('#p-afectacion').val(r.codigo_afectacion || '10');
    if (r.imagen) { $('#p-preview-img').attr('src', r.imagen); $('#p-preview').show(); }
    else $('#p-preview').hide();

    // Cargar precios/presentaciones (traen su stock por presentación)
    const precios = await Http.get('../ajax/producto.php?op=precios&idproducto=' + r.idproducto);
    renderPrecios(precios && precios.length ? precios : [{ nombre: 'Normal', precio: r.precio || '', es_default: 1 }]);

    // Activar el switch de stock si alguna presentación ya lo controla
    const algunaControla = (precios || []).some(p => Number(p.controla_stock) === 1);
    $('#p-controla-stock').prop('checked', algunaControla);
    toggleStockFields();

    openModal('modal-prod');
};

window.guardar = async () => {
    const codigo = $('#p-codigo').val().trim();
    const nombre = $('#p-nombre').val().trim();
    const idcat  = $('#p-categoria').val();
    if (!codigo || !nombre || !idcat) {
        showToast('Completa código, nombre y categoría', 'error'); return;
    }
    const precios = recolectarPrecios();
    if (!precios.length) { showToast('Agrega al menos un precio', 'error'); return; }

    // Asegurar al menos un default
    if (!precios.some(p => p.es_default)) precios[0].es_default = 1;

    // El "precio principal" es el default (compatibilidad con producto.precio)
    const precioDefault = precios.find(p => p.es_default).precio;

    const payload = {
        idproducto: $('#p-id').val(),
        codigo, nombre,
        idcategoria: idcat,
        precio: precioDefault,
        imagen: $('#p-imagen').val().trim(),
        popular:  $('#p-popular').is(':checked')  ? 1 : 0,
        favorito: $('#p-favorito').is(':checked') ? 1 : 0,
        codigo_afectacion: $('#p-afectacion').val() || '10'
    };
    const r = await Http.post('../ajax/producto.php?op=guardaryeditar', payload);
    if (!r.ok) { showToast(r.msg || 'Error al guardar', 'error'); return; }

    const idproducto = $('#p-id').val() || r.idproducto;

    // Guardar lista de precios PRIMERO (sincroniza/crea las presentaciones).
    // Esto preserva el stock de las presentaciones que ya existían.
    await $.ajax({
        url: '../ajax/producto.php?op=guardarPrecios',
        type: 'POST',
        data: { idproducto: idproducto, precios: precios },
        dataType: 'json'
    });

    // Guardar inventario POR PRESENTACIÓN. Releemos las presentaciones para
    // obtener su idprecio (recién creado/sincronizado) y matchear por nombre.
    try {
        const controla = $('#p-controla-stock').is(':checked') ? 1 : 0;
        const ppActuales = await Http.get('../ajax/producto.php?op=precios&idproducto=' + idproducto) || [];
        const mapaNombre = {};
        ppActuales.forEach(p => { mapaNombre[(p.nombre||'').trim().toLowerCase()] = p.idprecio; });

        const presentaciones = [];
        document.querySelectorAll('#p-stock-list .stock-row').forEach(row => {
            const nombre = (row.dataset.nombre || '').trim().toLowerCase();
            const idprecio = mapaNombre[nombre];
            if (!idprecio) return;
            presentaciones.push({
                idprecio: idprecio,
                controla_stock: controla,
                stock: parseFloat(row.querySelector('.st-stock').value) || 0,
                stock_minimo: parseFloat(row.querySelector('.st-min').value) || 0
            });
        });
        // Si se desactivó el control, igual mandamos controla=0 para todas las presentaciones
        if (!controla) {
            ppActuales.forEach(p => presentaciones.push({ idprecio: p.idprecio, controla_stock: 0,
                stock: p.stock || 0, stock_minimo: p.stock_minimo || 0 }));
        }
        if (presentaciones.length) {
            await Http.post('../ajax/producto.php?op=setInventario', { idproducto, presentaciones });
        }
    } catch (e) { /* el producto ya se guardó; el stock es secundario */ }

    showToast('Producto guardado', 'success');
    closeModal('modal-prod');
    dt.ajax.reload(null, false);
};

window.toggle = async (id, activar) => {
    const op = activar === 1 ? 'activar' : 'desactivar';
    const r = await Http.post('../ajax/producto.php?op=' + op, { idproducto: id });
    if (r.ok) dt.ajax.reload(null, false);
};

// ---------- Importar Excel ----------
window.openImportar = () => {
    document.getElementById('import-file').value = '';
    const box = document.getElementById('import-resultado');
    box.style.display = 'none'; box.innerHTML = '';
    document.getElementById('btn-do-import').disabled = false;
    openModal('modal-import');
};

window.descargarPlantilla = () => {
    window.location.href = '../ajax/producto.php?op=plantillaImportar';
};

window.hacerImportar = async () => {
    const inp = document.getElementById('import-file');
    if (!inp.files || !inp.files[0]) { showToast('Selecciona un archivo', 'error'); return; }
    const btn = document.getElementById('btn-do-import');
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Importando...';

    const fd = new FormData();
    fd.append('archivo', inp.files[0]);
    try {
        const r = await $.ajax({
            url: '../ajax/producto.php?op=importar',
            type: 'POST', data: fd, contentType: false, processData: false, dataType: 'json'
        });
        const box = document.getElementById('import-resultado');
        box.style.display = '';
        if (r.ok || (r.creados !== undefined)) {
            let html = `<div style="background:#ecfdf5;border:1px solid #10b981;border-radius:8px;padding:10px 12px;color:#065f46;">
                <b>Importación completada</b><br>
                ✓ ${r.creados} creados · ${r.actualizados} actualizados · ${r.categorias_nuevas} categorías nuevas</div>`;
            if (r.errores && r.errores.length) {
                html += `<div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:10px 12px;color:#92400e;margin-top:8px;max-height:160px;overflow:auto;">
                    <b>${r.errores.length} fila(s) con aviso:</b><ul style="margin:6px 0 0 16px;">` +
                    r.errores.map(e => `<li>${$('<div>').text(e).html()}</li>`).join('') + `</ul></div>`;
            }
            box.innerHTML = html;
            showToast('Importación: ' + r.creados + ' creados, ' + r.actualizados + ' actualizados', 'success');
            await cargarCategorias();
            dt.ajax.reload(null, false);
        } else {
            box.innerHTML = `<div style="background:#fef2f2;border:1px solid #ef4444;border-radius:8px;padding:10px 12px;color:#991b1b;">${$('<div>').text(r.msg || 'Error al importar').html()}</div>`;
        }
    } catch (e) {
        showToast('Error al importar el archivo', 'error');
    }
    btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-upload"></i> Importar';
};
