/* mesas.js - listar y CRUD de mesas + zonas via AJAX */

let editingId    = null;
let editingZona  = null;
let activeFilter = 'all';
let activeZona   = 'all';     // 'all' o idzona
let searchTerm   = '';
let mesasCache   = [];
let zonasCache   = [];
let mesasBuffer  = [];        // mesas pendientes en el modal de Nueva Zona
let ordenesEnCurso = [];

// ---- Disposicion ----
// Tanto el ORDEN de las mesas (columna mesa.orden) como el numero de COLUMNAS
// (empresa.mesas_columnas) se guardan en la BASE DE DATOS, por lo que son
// iguales en todos los dispositivos. El orden ya viene aplicado desde
// API.mesas() (ORDER BY m.orden), asi que no se reordena en el cliente.
let modoOrden = false;                                       // ¿drag&drop activo?
let columnasPref = 'auto';                                   // se carga desde la BD en load()

const STATUS_LABELS = { libre: 'Libre', ocupada: 'Ocupada', cuenta: 'En cuenta', reservada: 'Reservada', bloqueada: 'Bloqueada' };
const ZONA_COLORS = ['#5b3df5','#10b981','#3b82f6','#f59e0b','#ec4899','#ef4444','#0ea5e9','#8b5cf6','#14b8a6'];

async function load() {
    [mesasCache, ordenesEnCurso, zonasCache] = await Promise.all([
        API.mesas(),
        API.ordenes({ estado: 'en_curso' }),
        Http.get('../ajax/zona.php?op=listar')
    ]);
    if (!Array.isArray(zonasCache)) zonasCache = [];
    // Cargar la preferencia de columnas desde la BD (config global)
    try {
        const cfg = await Http.get('../ajax/mesa.php?op=getColumnas');
        if (cfg && cfg.columnas) columnasPref = cfg.columnas;
    } catch (e) { /* si falla, queda 'auto' */ }
    const selCols = document.getElementById('sel-cols');
    if (selCols) selCols.value = columnasPref;
    renderZonas();
    render();
}

function renderZonas() {
    const grid = document.getElementById('zonas-grid');
    const total = mesasCache.length;
    const chips = [`
        <div class="zona-chip zona-chip-all ${activeZona === 'all' ? 'active' : ''}" onclick="filtrarZona('all')">
            <span class="zona-name"><span class="zona-dot" style="background:#94a3b8;"></span> Todas</span>
            <span class="zona-count">${total} mesa${total!==1?'s':''}</span>
        </div>`];

    zonasCache.forEach(z => {
        const cnt = mesasCache.filter(m => Number(m.idzona) === Number(z.idzona)).length;
        chips.push(`
            <div class="zona-chip ${activeZona == z.idzona ? 'active' : ''}" draggable="true" data-idzona="${z.idzona}" onclick="filtrarZona(${z.idzona})">
                <button class="zona-edit" data-perm="zonas" onclick="event.stopPropagation();editZona(${z.idzona})" title="Editar zona"><i class="fa-solid fa-pen"></i></button>
                <span class="zona-name"><span class="zona-dot" style="background:${z.color};"></span> ${escapeHtml(z.nombre)}</span>
                <span class="zona-count">${cnt} mesa${cnt!==1?'s':''}</span>
            </div>`);
    });

    // Mesas sin zona (solo si hay alguna)
    const sinZona = mesasCache.filter(m => !m.idzona).length;
    if (sinZona > 0 && zonasCache.length > 0) {
        chips.push(`
            <div class="zona-chip ${activeZona === 'none' ? 'active' : ''}" onclick="filtrarZona('none')">
                <span class="zona-name"><span class="zona-dot" style="background:#d1d5db;"></span> Sin zona</span>
                <span class="zona-count">${sinZona} mesa${sinZona!==1?'s':''}</span>
            </div>`);
    }

    grid.innerHTML = chips.join('');
    if (typeof applyPermissionsDOM === 'function') applyPermissionsDOM(grid);  // ocultar zona-edit si no es admin
    habilitarDragDropZonas();
}

window.filtrarZona = (idzona) => {
    activeZona = idzona;
    renderZonas();
    render();
};

function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c]); }

function renderMesaCard(t) {
    const orden = ordenesEnCurso.find(o => Number(o.idmesa) === Number(t.idmesa));
    const zonaBadge = t.zona_nombre ? `<div class="mesa-zona-badge" style="background:${t.zona_color || '#5b3df5'};"><i class="fa-solid fa-location-dot"></i> ${escapeHtml(t.zona_nombre)}</div>` : '';
    return `
        <div class="mesa-card ${t.estado}" data-idmesa="${t.idmesa}" onclick="openMesa(${t.idmesa})">
            <div class="mesa-card-header">
                <div class="mesa-num">${t.numero}</div>
                <i class="fa-solid fa-utensils mesa-icon"></i>
            </div>
            ${zonaBadge}
            <div class="mesa-info"><i class="fa-solid fa-user-group" style="font-size:11px;"></i> ${t.capacidad} personas</div>
            ${orden ? `
                <div class="mesa-info"><i class="fa-solid fa-receipt" style="font-size:11px;"></i> Orden #${orden.numero}</div>
                <div class="mesa-info mesa-price"><i class="fa-solid fa-sack-dollar" style="font-size:11px;color:#dc2626;"></i> <span>${fmt.money(orden.total)}</span></div>
            ` : ''}
            <div class="mesa-status">${STATUS_LABELS[t.estado] || t.estado}</div>
            <div style="display:flex;gap:6px;margin-top:12px;">
                <button class="btn btn-sm" onclick="event.stopPropagation();editTable(${t.idmesa})" style="flex:1;"><i class="fa-solid fa-pen"></i> Editar</button>
                <button class="btn btn-sm btn-icon" onclick="event.stopPropagation();deleteTable(${t.idmesa})"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
    `;
}

function aplicarFiltros(list) {
    if (activeFilter !== 'all') list = list.filter(t => t.estado === activeFilter);
    if (searchTerm)             list = list.filter(t => String(t.numero).includes(searchTerm));
    // El orden ya viene de la BD (API.mesas → ORDER BY m.orden); no se reordena aquí.
    return list;
}

// Aplica el número de columnas elegido a un contenedor .tables-grid
function aplicarColumnas(grid) {
    if (!grid) return;
    if (columnasPref === 'auto') {
        grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(155px, 1fr))';
    } else {
        grid.style.gridTemplateColumns = 'repeat(' + parseInt(columnasPref, 10) + ', 1fr)';
    }
}

function render() {
    document.getElementById('count-all').textContent       = mesasCache.length;
    document.getElementById('count-libre').textContent     = mesasCache.filter(t => t.estado === 'libre').length;
    document.getElementById('count-ocupada').textContent   = mesasCache.filter(t => t.estado === 'ocupada').length;
    document.getElementById('count-cuenta').textContent    = mesasCache.filter(t => t.estado === 'cuenta').length;
    document.getElementById('count-reservada').textContent = mesasCache.filter(t => t.estado === 'reservada').length;

    const grid = document.getElementById('tables-grid');
    // Reset estilos del contenedor
    grid.style.display = '';
    grid.style.gridTemplateColumns = '';
    grid.style.gap = '';

    // Si filtran por zona especifica o "sin zona", mostramos solo esa seccion (sin header)
    if (activeZona === 'none') {
        const list = aplicarFiltros(mesasCache.filter(t => !t.idzona));
        grid.innerHTML = list.length === 0
            ? '<div style="grid-column:1/-1;" class="empty-state"><i class="fa-solid fa-table"></i><h3>Sin mesas</h3><p>No hay mesas que coincidan con el filtro</p></div>'
            : list.map(renderMesaCard).join('');
        aplicarColumnas(grid);
        activarDragDropMesas();
        return;
    }
    if (activeZona !== 'all') {
        const list = aplicarFiltros(mesasCache.filter(t => Number(t.idzona) === Number(activeZona)));
        grid.innerHTML = list.length === 0
            ? '<div style="grid-column:1/-1;" class="empty-state"><i class="fa-solid fa-table"></i><h3>Sin mesas</h3><p>No hay mesas en esta zona que coincidan con el filtro</p></div>'
            : list.map(renderMesaCard).join('');
        aplicarColumnas(grid);
        activarDragDropMesas();
        return;
    }

    // activeZona === 'all': agrupar por zona en secciones separadas
    const secciones = [];
    zonasCache.forEach(z => {
        const list = aplicarFiltros(mesasCache.filter(t => Number(t.idzona) === Number(z.idzona)));
        if (list.length === 0) return;
        secciones.push(`
            <div class="zona-section">
                <div class="zona-section-header" style="border-left-color:${z.color};">
                    <span class="zsh-dot" style="background:${z.color};"></span>
                    <span class="zsh-name">${escapeHtml(z.nombre)}</span>
                    <span class="zsh-count">${list.length} mesa${list.length!==1?'s':''}</span>
                </div>
                <div class="tables-grid">${list.map(renderMesaCard).join('')}</div>
            </div>
        `);
    });

    // Mesas sin zona
    const sinZonaList = aplicarFiltros(mesasCache.filter(t => !t.idzona));
    if (sinZonaList.length > 0) {
        secciones.push(`
            <div class="zona-section">
                <div class="zona-section-header" style="border-left-color:#94a3b8;">
                    <span class="zsh-dot" style="background:#94a3b8;"></span>
                    <span class="zsh-name">Sin zona</span>
                    <span class="zsh-count">${sinZonaList.length} mesa${sinZonaList.length!==1?'s':''}</span>
                </div>
                <div class="tables-grid">${sinZonaList.map(renderMesaCard).join('')}</div>
            </div>
        `);
    }

    if (secciones.length === 0) {
        grid.innerHTML = '<div class="empty-state"><i class="fa-solid fa-table"></i><h3>Sin mesas</h3><p>No hay mesas que coincidan con el filtro</p></div>';
        return;
    }

    // En modo agrupado, el contenedor debe ser block (no grid) para que las secciones se apilen
    grid.style.display = 'block';
    grid.innerHTML = secciones.join('');
    // Aplicar columnas a cada grilla interna de zona y activar drag&drop
    grid.querySelectorAll('.tables-grid').forEach(aplicarColumnas);
    activarDragDropMesas();
}

// =====================================================================
// Disposicion: columnas configurables + ordenar mesas con drag & drop.
// Ambas preferencias se guardan en la BASE DE DATOS (config global), por lo
// que son iguales en todos los dispositivos. No afecta a otros modulos:
// Nueva Orden sigue listando mesas con el mismo ORDER BY.
// =====================================================================
window.cambiarColumnas = (val) => {
    columnasPref = val;
    // Guardar en la BD (config global, igual en todos los dispositivos)
    Http.post('../ajax/mesa.php?op=setColumnas', { columnas: val });
    document.querySelectorAll('#tables-grid .tables-grid, #tables-grid').forEach(g => {
        if (g.style.display !== 'block') aplicarColumnas(g);
    });
    // Re-render simple para cubrir todos los modos
    render();
};

window.toggleOrdenar = () => {
    modoOrden = !modoOrden;
    const btn  = document.getElementById('btn-ordenar');
    const hint = document.getElementById('ordenar-hint');
    const reset = document.getElementById('btn-reset-orden');
    btn.classList.toggle('active', modoOrden);
    hint.style.display = modoOrden ? '' : 'none';
    if (reset) reset.style.display = modoOrden ? '' : 'none';
    render();
};

window.resetOrden = async () => {
    await Http.post('../ajax/mesa.php?op=resetOrden', {});
    await load();  // recargar desde la BD (vuelve al orden por número)
    if (typeof showToast === 'function') showToast('Orden restablecido por número', 'success');
};

async function guardarOrdenDesdeDOM() {
    // Recolecta el orden de TODAS las cards visibles (en todas las zonas) y lo
    // persiste en la BASE DE DATOS (columna mesa.orden). Asi el orden es igual
    // en todos los dispositivos.
    const ids = Array.from(document.querySelectorAll('#tables-grid .mesa-card[data-idmesa]'))
        .map(c => Number(c.dataset.idmesa));
    if (!ids.length) return;
    await Http.post('../ajax/mesa.php?op=reordenar', { orden: JSON.stringify(ids) });
    // Actualizar la cache local para que un re-render no revierta visualmente.
    const posMap = new Map(ids.map((id, i) => [id, i + 1]));
    mesasCache.forEach(m => { if (posMap.has(Number(m.idmesa))) m.orden = posMap.get(Number(m.idmesa)); });
}

function activarDragDropMesas() {
    if (!modoOrden) return;
    document.getElementById('tables-grid').classList.add('modo-orden');
    // marcar cada grilla individual también (para el modo agrupado)
    document.querySelectorAll('#tables-grid .tables-grid').forEach(g => g.classList.add('modo-orden'));

    const cards = document.querySelectorAll('#tables-grid .mesa-card[data-idmesa]');
    let dragged = null;

    cards.forEach(card => {
        card.setAttribute('draggable', 'true');
        card.addEventListener('dragstart', (e) => {
            dragged = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            dragged = null;
            guardarOrdenDesdeDOM();   // persiste el nuevo orden en la BD
        });
        card.addEventListener('dragover', (e) => {
            e.preventDefault();
            // Solo permitir reordenar dentro de la misma grilla (misma zona)
            if (!dragged || dragged === card || dragged.parentNode !== card.parentNode) return;
            const rect = card.getBoundingClientRect();
            const mid  = rect.top + rect.height / 2;
            const horizontal = e.clientY; // usamos Y por ser grilla multi-fila
            if (horizontal < mid) card.parentNode.insertBefore(dragged, card);
            else                  card.parentNode.insertBefore(dragged, card.nextSibling);
        });
        card.addEventListener('drop', (e) => { e.preventDefault(); });
    });
}

window.openMesa = (id) => {
    if (modoOrden) return;   // en modo ordenar, el click no abre la orden (se está arrastrando)
    const t = mesasCache.find(x => Number(x.idmesa) === Number(id));
    if (!t) return;
    if (t.estado === 'libre' || t.estado === 'ocupada') {
        window.location.href = 'nuevaorden?mesa=' + t.idmesa;
    }
};

function llenarSelectZonas(selectId, idSel) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    sel.innerHTML = '<option value="">— Sin zona —</option>' +
        zonasCache.map(z => `<option value="${z.idzona}" ${idSel == z.idzona ? 'selected' : ''}>${escapeHtml(z.nombre)}</option>`).join('');
}

window.openAddTable = () => {
    editingId = null;
    document.getElementById('modal-title').textContent = 'Nueva Mesa';
    document.getElementById('m-num').value = '';
    document.getElementById('m-cap').value = '';
    document.getElementById('m-status').value = 'libre';
    // Si hay una zona activa filtrada, preselecciónala
    const preZona = (activeZona !== 'all' && activeZona !== 'none') ? activeZona : '';
    llenarSelectZonas('m-zona', preZona);
    openModal('modal');
};

window.editTable = (id) => {
    const t = mesasCache.find(x => Number(x.idmesa) === Number(id));
    if (!t) return;
    editingId = id;
    document.getElementById('modal-title').textContent = 'Editar Mesa ' + t.numero;
    document.getElementById('m-num').value = t.numero;
    document.getElementById('m-cap').value = t.capacidad;
    document.getElementById('m-status').value = t.estado;
    llenarSelectZonas('m-zona', t.idzona || '');
    openModal('modal');
};

window.saveTable = async () => {
    const numero    = parseInt(document.getElementById('m-num').value);
    const capacidad = parseInt(document.getElementById('m-cap').value);
    const estado    = document.getElementById('m-status').value;
    const idzona    = document.getElementById('m-zona').value;
    if (!numero || !capacidad) { showToast('Completa los campos', 'error'); return; }

    const payload = { numero, capacidad, estado, idzona };
    if (editingId) payload.idmesa = editingId;

    const r = await API.mesaGuardar(payload);
    if (r.ok) {
        showToast(editingId ? 'Mesa actualizada' : 'Mesa creada', 'success');
        closeModal('modal');
        load();
    } else {
        showToast('Error al guardar', 'error');
    }
};

window.deleteTable = async (id) => {
    if (!(await swalConfirm('¿Eliminar esta mesa? Se desactivará en el sistema.', { title: 'Eliminar mesa', icon: 'warning', confirmText: 'Sí, eliminar' }))) return;
    const r = await Http.post(API.base + '/mesa.php?op=desactivar', { idmesa: id });
    if (r.ok) { showToast('Mesa eliminada', 'success'); load(); }
};

document.querySelectorAll('.filter-pill[data-filter]').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('.filter-pill[data-filter]').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    activeFilter = b.dataset.filter;
    render();
}));

document.getElementById('search-mesas').addEventListener('input', e => {
    searchTerm = e.target.value;
    render();
});

// =====================================================================
// ZONAS
// =====================================================================
function renderPaletaColores(colorActivo) {
    document.getElementById('z-color').value = colorActivo;
    document.getElementById('z-colores').innerHTML = ZONA_COLORS.map(c => `
        <div class="color-swatch ${c === colorActivo ? 'active' : ''}" style="background:${c};" onclick="seleccionarColor('${c}')"></div>
    `).join('');
}

window.seleccionarColor = (c) => {
    document.getElementById('z-color').value = c;
    document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.color-swatch').forEach(s => {
        if (s.style.background === c || s.style.backgroundColor.toLowerCase() === c.toLowerCase()) s.classList.add('active');
    });
    // Fallback con includes
    document.querySelectorAll('.color-swatch').forEach(s => {
        const bg = s.getAttribute('style') || '';
        if (bg.toLowerCase().includes(c.toLowerCase())) s.classList.add('active');
    });
};

function renderMesasBuffer() {
    const cont = document.getElementById('z-mesas-buffer');
    if (mesasBuffer.length === 0) {
        cont.innerHTML = '<div style="font-size:12px;color:var(--text-muted);padding:8px 4px;">No has agregado mesas. La zona se creará vacía.</div>';
        return;
    }
    cont.innerHTML = mesasBuffer.map((m, i) => `
        <div class="mesa-buffer-item">
            <span><b>Mesa #${m.numero}</b> · ${m.capacidad} personas</span>
            <button class="remove-btn" onclick="quitarMesaBuffer(${i})"><i class="fa-solid fa-xmark"></i></button>
        </div>
    `).join('');
}

window.agregarMesaBuffer = () => {
    const num = parseInt(document.getElementById('z-mesa-num').value);
    const cap = parseInt(document.getElementById('z-mesa-cap').value) || 4;
    if (!num || num <= 0) { showToast('Ingresa un número de mesa válido', 'error'); return; }
    if (mesasBuffer.some(x => x.numero === num)) { showToast('Ya agregaste la mesa ' + num + ' en esta zona', 'error'); return; }
    if (mesasCache.some(x => Number(x.numero) === num)) { showToast('El número ' + num + ' ya existe en el sistema', 'error'); return; }
    mesasBuffer.push({ numero: num, capacidad: cap });
    document.getElementById('z-mesa-num').value = '';
    document.getElementById('z-mesa-num').focus();
    renderMesasBuffer();
    actualizarBtnGuardarZona();
};

window.quitarMesaBuffer = (i) => {
    mesasBuffer.splice(i, 1);
    renderMesasBuffer();
    actualizarBtnGuardarZona();
};

function actualizarBtnGuardarZona() {
    const label = document.getElementById('z-btn-label');
    if (editingZona) {
        label.textContent = 'Guardar cambios';
    } else if (mesasBuffer.length > 0) {
        label.textContent = `Crear Zona + ${mesasBuffer.length} mesa${mesasBuffer.length>1?'s':''}`;
    } else {
        label.textContent = 'Crear Zona';
    }
}

window.openAddZona = () => {
    editingZona = null;
    mesasBuffer = [];
    document.getElementById('zona-modal-title').textContent = 'Nueva Zona';
    document.getElementById('z-nombre').value = '';
    document.getElementById('z-mesa-num').value = '';
    document.getElementById('z-mesa-cap').value = '4';
    document.getElementById('z-btn-eliminar').style.display = 'none';
    document.getElementById('z-mesas-section').style.display = '';
    document.getElementById('z-asignar-section').style.display = 'none';
    renderPaletaColores(ZONA_COLORS[0]);
    renderMesasBuffer();
    actualizarBtnGuardarZona();
    openModal('modal-zona');
};

window.editZona = async (id) => {
    const z = zonasCache.find(x => Number(x.idzona) === Number(id));
    if (!z) return;
    editingZona = id;
    mesasBuffer = [];
    document.getElementById('zona-modal-title').textContent = 'Editar Zona';
    document.getElementById('z-nombre').value = z.nombre;
    document.getElementById('z-btn-eliminar').style.display = '';
    document.getElementById('z-mesas-section').style.display = 'none';
    document.getElementById('z-asignar-section').style.display = '';
    renderPaletaColores(z.color || ZONA_COLORS[0]);
    actualizarBtnGuardarZona();
    openModal('modal-zona');
    await cargarMesasParaAsignar(id);
};

async function cargarMesasParaAsignar(idzona) {
    const [enZona, sinZona] = await Promise.all([
        Http.get('../ajax/zona.php?op=mesasDeZona&idzona=' + idzona),
        Http.get('../ajax/zona.php?op=mesasSinZona')
    ]);
    renderMesasChk('z-mesas-en-zona', enZona || [], 'z-btn-quitar-todas');
    renderMesasChk('z-mesas-sin-zona', sinZona || [], 'z-btn-agregar-todas');
}

function renderMesasChk(contId, mesas, btnId) {
    const cont = document.getElementById(contId);
    if (!mesas || mesas.length === 0) {
        cont.innerHTML = '<div class="mesas-empty">Ninguna mesa en este grupo</div>';
        document.getElementById(btnId).disabled = true;
        return;
    }
    cont.innerHTML = mesas.map(m => `
        <label class="mesa-chk" data-id="${m.idmesa}">
            <input type="checkbox" onchange="toggleMesaChk(this)">
            <span class="mesa-chk-num">#${m.numero}</span>
            <span class="mesa-chk-cap">· ${m.capacidad}p</span>
        </label>
    `).join('');
}

window.toggleMesaChk = (input) => {
    const label = input.closest('.mesa-chk');
    label.classList.toggle('checked', input.checked);
    // Habilitar/deshabilitar botones de accion segun haya selecciones
    const enZonaAny  = document.querySelectorAll('#z-mesas-en-zona  .mesa-chk input:checked').length > 0;
    const sinZonaAny = document.querySelectorAll('#z-mesas-sin-zona .mesa-chk input:checked').length > 0;
    document.getElementById('z-btn-quitar-todas').disabled  = !enZonaAny;
    document.getElementById('z-btn-agregar-todas').disabled = !sinZonaAny;
};

window.asignarMesasSeleccionadas = async () => {
    if (!editingZona) return;
    const ids = Array.from(document.querySelectorAll('#z-mesas-sin-zona .mesa-chk input:checked'))
        .map(i => Number(i.closest('.mesa-chk').dataset.id));
    if (ids.length === 0) return;
    const r = await Http.post('../ajax/zona.php?op=asignarMesas', {
        idzona: editingZona,
        idmesas: JSON.stringify(ids)
    });
    if (r.ok) {
        showToast(`${r.asignadas} mesa(s) agregadas a la zona`, 'success');
        await cargarMesasParaAsignar(editingZona);
        await load();          // refrescar grid principal
    } else {
        showToast(r.msg || 'Error al asignar', 'error');
    }
};

window.quitarMesasSeleccionadas = async () => {
    if (!editingZona) return;
    const ids = Array.from(document.querySelectorAll('#z-mesas-en-zona .mesa-chk input:checked'))
        .map(i => Number(i.closest('.mesa-chk').dataset.id));
    if (ids.length === 0) return;
    if (!(await swalConfirm(`¿Quitar ${ids.length} mesa(s) de esta zona? Quedarán sin zona pero NO se eliminan.`, { title: 'Quitar de zona', icon: 'question', confirmText: 'Sí, quitar' }))) return;
    const r = await Http.post('../ajax/zona.php?op=asignarMesas', {
        idzona: '',
        idmesas: JSON.stringify(ids)
    });
    if (r.ok) {
        showToast(`${r.asignadas} mesa(s) quitadas`, 'success');
        await cargarMesasParaAsignar(editingZona);
        await load();
    } else {
        showToast(r.msg || 'Error', 'error');
    }
};

window.guardarZona = async () => {
    const nombre = document.getElementById('z-nombre').value.trim();
    const color  = document.getElementById('z-color').value;
    if (!nombre) { showToast('Ingresa el nombre de la zona', 'error'); return; }

    if (editingZona) {
        const r = await Http.post('../ajax/zona.php?op=editar', { idzona: editingZona, nombre, color });
        if (r.ok) { showToast('Zona actualizada', 'success'); closeModal('modal-zona'); load(); }
        else      { showToast(r.msg || 'Error al editar', 'error'); }
    } else {
        const r = await Http.post('../ajax/zona.php?op=crearConMesas', {
            nombre, color, mesas: JSON.stringify(mesasBuffer)
        });
        if (r.ok) {
            let msg = 'Zona creada';
            if (r.mesas_creadas > 0) msg += ` · ${r.mesas_creadas} mesa(s) agregadas`;
            if (r.duplicadas && r.duplicadas.length) msg += ` · ${r.duplicadas.length} número(s) ya existían`;
            showToast(msg, 'success');
            closeModal('modal-zona');
            load();
        } else {
            showToast(r.msg || 'Error al crear', 'error');
        }
    }
};

window.eliminarZonaActual = async () => {
    if (!editingZona) return;
    const z = zonasCache.find(x => Number(x.idzona) === Number(editingZona));
    if (!(await swalConfirm(`¿Eliminar la zona "${z?.nombre}"? Las mesas quedarán sin zona pero NO se eliminan.`, { title: 'Eliminar zona', icon: 'warning', confirmText: 'Sí, eliminar', confirmColor: '#ef4444' }))) return;
    const r = await Http.post('../ajax/zona.php?op=eliminar', { idzona: editingZona });
    if (r.ok) { showToast('Zona eliminada', 'success'); closeModal('modal-zona'); load(); }
    else       { showToast(r.msg || 'Error', 'error'); }
};

// =====================================================================
// (A) Drag-and-drop reordenar zonas
// =====================================================================
function habilitarDragDropZonas() {
    const chips = document.querySelectorAll('.zona-chip[draggable="true"]');
    let dragged = null;

    chips.forEach(c => {
        c.addEventListener('dragstart', (e) => {
            dragged = c;
            c.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        c.addEventListener('dragend', () => {
            c.classList.remove('dragging');
            dragged = null;
        });
        c.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (!dragged || dragged === c) return;
            const rect = c.getBoundingClientRect();
            const mid  = rect.left + rect.width / 2;
            if (e.clientX < mid) c.parentNode.insertBefore(dragged, c);
            else                 c.parentNode.insertBefore(dragged, c.nextSibling);
        });
        c.addEventListener('drop', async (e) => {
            e.preventDefault();
            const nuevoOrden = Array.from(document.querySelectorAll('.zona-chip[data-idzona]')).map(x => Number(x.dataset.idzona));
            await Http.post('../ajax/zona.php?op=reordenar', { orden: JSON.stringify(nuevoOrden) });
            await load();
        });
    });
}

load();

// =====================================================================
// Auto-refresh REACTIVO: combina 3 mecanismos para estado en tiempo real
//   1) BroadcastChannel: recibe eventos instantaneos desde Nueva Orden
//      (crear orden, agregar item, enviar a cocina, cobrar)
//   2) Page Visibility: al volver a la pestana, recarga al instante
//   3) Polling cada 5s como red de seguridad (otros dispositivos/navegadores)
// =====================================================================

function noHayModalAbierto() {
    const m  = document.getElementById('modal');
    const mz = document.getElementById('modal-zona');
    return !m.classList.contains('active') && !mz.classList.contains('active');
}

let bcOrders = null;
try {
    if (typeof BroadcastChannel !== 'undefined') {
        bcOrders = new BroadcastChannel('yapez-orders');
        bcOrders.onmessage = (ev) => {
            if (!ev?.data?.type) return;
            // Cualquier evento que mueva el estado de una mesa: refrescar
            if (['orden-creada','orden-actualizada','orden-cobrada','orden-enviada-cocina','mesa-cambio'].includes(ev.data.type)) {
                if (noHayModalAbierto()) load();
            }
        };
    }
} catch (e) {
    console.warn('BroadcastChannel no disponible', e);
}

document.addEventListener('visibilitychange', () => {
    if (!document.hidden && noHayModalAbierto()) load();
});

setInterval(() => {
    if (document.hidden) return;             // no consumir recursos en background
    if (noHayModalAbierto()) load();
}, 5000);
