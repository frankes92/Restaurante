/* nuevaorden.js - POS principal */

const state = {
    selectedMesa: null,
    activeCategory: 'all',
    searchTerm: '',
    type: 'dine_in',
    paymentMethod: 'efectivo',
    orden: null,
    sesion: null,
    categorias: [],
    productos: [],     // pagina actualmente cargada (NO todos)
    mesas: [],
    zonas: [],
    activeZona: 'all',
    // Solo-lectura: true si la orden actual pertenece a otro mozo
    readonly: false,
    // Paginacion / scroll infinito
    pagStart: 0,
    pagLimit: 24,
    pagHasMore: true,
    pagLoading: false,
    pagObserver: null,
    pagSearchTimer: null,
};

// BroadcastChannel para avisar a otras pestanas (Mesas) que algo cambio
let bcOrders = null;
try {
    if (typeof BroadcastChannel !== 'undefined') bcOrders = new BroadcastChannel('yapez-orders');
} catch (e) { /* nav viejo, ignorar */ }
function emitOrden(type, payload) {
    try { if (bcOrders) bcOrders.postMessage({ type, ...(payload || {}), ts: Date.now() }); } catch (e) {}
}

async function init() {
    state.sesion = await API.cajaSesion();
    if (!state.sesion || !state.sesion.idsesion) {
        showToast('Caja cerrada — abre una sesión de caja primero', 'error');
    }

    [state.categorias, state.mesas, state.zonas] = await Promise.all([
        API.categorias(),
        API.mesas(),
        Http.get('../ajax/zona.php?op=listar')
    ]);
    if (!Array.isArray(state.zonas)) state.zonas = [];

    renderCategorias();
    await cargarProductos(true);   // primera pagina
    renderZonasTabs();
    renderMesas();

    const params = new URLSearchParams(window.location.search);
    const idmesa = params.get('mesa');
    if (idmesa) {
        const m = state.mesas.find(x => Number(x.idmesa) === Number(idmesa));
        if (m) await seleccionarMesa(m);
    }

    bindEvents();
}

function renderCategorias() {
    const list = document.getElementById('categories-list');
    list.innerHTML = '<div class="categories-label">CATEGORÍAS</div>' +
        '<div class="cat-item ' + (state.activeCategory === 'all' ? 'active' : '') + '" data-cat="all"><i class="fa-solid fa-table-cells"></i> Todas</div>' +
        state.categorias.map(c => `
            <div class="cat-item ${c.codigo === state.activeCategory ? 'active' : ''}" data-cat="${c.codigo}">
                <i class="fa-solid ${c.icono}" style="color:${c.color};"></i> ${c.nombre}
            </div>
        `).join('');

    list.querySelectorAll('.cat-item').forEach(el => el.addEventListener('click', async () => {
        state.activeCategory = el.dataset.cat;
        renderCategorias();
        await cargarProductos(true);  // reset y recarga
    }));
}

/**
 * Carga una pagina de productos. Si reset=true, vacia el grid y arranca de 0.
 * En caso contrario, agrega la siguiente pagina al final.
 */
async function cargarProductos(reset = false) {
    if (state.pagLoading) return;
    state.pagLoading = true;

    if (reset) {
        state.pagStart = 0;
        state.pagHasMore = true;
        state.productos = [];
    }
    if (!state.pagHasMore) { state.pagLoading = false; return; }

    const grid = document.getElementById('products-grid');

    // Mostrar skeleton/loader
    if (reset) grid.innerHTML = renderSkeleton(8);
    else mostrarLoaderFinal();

    const r = await Http.get('../ajax/producto.php?op=listarPaginado'
        + '&start='   + state.pagStart
        + '&limit='   + state.pagLimit
        + '&idcategoria=' + encodeURIComponent(state.activeCategory || '')
        + '&search='  + encodeURIComponent(state.searchTerm || ''));

    const items = (r && r.data) || [];
    state.productos = state.productos.concat(items);
    state.pagStart += items.length;
    state.pagHasMore = !!(r && r.hasMore);

    if (reset) grid.innerHTML = '';
    quitarLoaderFinal();

    if (state.productos.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1;" class="empty-state"><i class="fa-solid fa-utensils"></i><h3>Sin productos</h3><p>No hay productos para esta categoría o búsqueda</p></div>';
        state.pagLoading = false;
        return;
    }

    // Append solo los nuevos
    items.forEach(p => grid.insertAdjacentHTML('beforeend', renderProductCard(p)));

    // Sentinel + observer para detectar fin del scroll
    montarSentinel();

    state.pagLoading = false;
}

function renderProductCard(p) {
    // Control de stock por presentación: el producto está "agotado" solo si TODAS
    // sus presentaciones que llevan stock están en 0.
    const conStock  = Number(p.num_con_stock) > 0;
    const stockTot  = Number(p.stock_total);
    const agotado   = conStock && stockTot <= 0;
    const badgeStock = conStock
        ? (agotado
            ? '<span style="font-size:9px;background:#fef2f2;color:#dc2626;padding:1px 5px;border-radius:6px;font-weight:700;">AGOTADO</span>'
            : '<span style="font-size:9px;background:#ecfdf5;color:#059669;padding:1px 5px;border-radius:6px;font-weight:700;">Stock: ' + stockTot + '</span>')
        : '';
    return `
        <div class="product-card${agotado ? ' agotado' : ''}" onclick="agregarProducto(${p.idproducto})"
             style="${agotado ? 'opacity:.55;filter:grayscale(.4);' : ''}">
            <div class="product-img" style="background-image:url('${p.imagen || ''}');"></div>
            <div class="product-info">
                <div>
                    <div class="product-name">${p.nombre}${Number(p.num_precios) > 1 ? ' <span style="font-size:9px;background:var(--primary-light);color:var(--primary);padding:1px 5px;border-radius:6px;font-weight:700;">VAR</span>' : ''}</div>
                    <div class="product-price">${fmt.money(p.precio)} ${badgeStock}</div>
                </div>
                <button class="product-add" onclick="event.stopPropagation();agregarProducto(${p.idproducto})">
                    <i class="fa-solid fa-${agotado ? 'ban' : 'plus'}"></i>
                </button>
            </div>
        </div>`;
}

function renderSkeleton(n) {
    let html = '';
    for (let i = 0; i < n; i++) {
        html += `
        <div class="product-card" style="opacity:0.5;pointer-events:none;">
            <div class="product-img" style="background:linear-gradient(90deg,#f3f4f6 25%,#e5e7eb 50%,#f3f4f6 75%);background-size:200% 100%;animation:skLoad 1.2s infinite;"></div>
            <div class="product-info">
                <div style="flex:1;">
                    <div style="height:11px;width:70%;background:#e5e7eb;border-radius:4px;margin-bottom:6px;"></div>
                    <div style="height:11px;width:40%;background:#e5e7eb;border-radius:4px;"></div>
                </div>
            </div>
        </div>`;
    }
    return html;
}

function montarSentinel() {
    // Quitar el sentinel anterior
    const old = document.getElementById('pag-sentinel');
    if (old) old.remove();
    if (state.pagObserver) { state.pagObserver.disconnect(); state.pagObserver = null; }

    const grid = document.getElementById('products-grid');
    if (!state.pagHasMore) {
        // Mensaje "no hay mas"
        grid.insertAdjacentHTML('beforeend',
            '<div style="grid-column:1/-1;text-align:center;padding:14px;color:var(--text-muted);font-size:12px;">— Fin de la lista —</div>');
        return;
    }

    grid.insertAdjacentHTML('beforeend',
        '<div id="pag-sentinel" style="grid-column:1/-1;height:40px;"></div>');

    state.pagObserver = new IntersectionObserver(async (entries) => {
        if (entries[0].isIntersecting && !state.pagLoading && state.pagHasMore) {
            await cargarProductos(false);
        }
    }, { root: grid, rootMargin: '120px' });
    state.pagObserver.observe(document.getElementById('pag-sentinel'));
}

function mostrarLoaderFinal() {
    if (document.getElementById('pag-loader')) return;
    document.getElementById('products-grid').insertAdjacentHTML('beforeend',
        '<div id="pag-loader" style="grid-column:1/-1;text-align:center;padding:14px;color:var(--primary);font-size:13px;font-weight:600;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando más...</div>');
}
function quitarLoaderFinal() {
    const l = document.getElementById('pag-loader');
    if (l) l.remove();
}

function renderZonasTabs() {
    const tabs = document.getElementById('zonas-tabs');
    if (!tabs) return;
    if (!state.zonas || state.zonas.length === 0) {
        tabs.style.display = 'none';
        return;
    }
    tabs.style.display = 'flex';
    const items = [`<button type="button" class="ztab ${state.activeZona === 'all' ? 'active' : ''}" onclick="filtrarZonaNueva('all')"><span class="zt-dot" style="background:#94a3b8;"></span> Todas</button>`];
    state.zonas.forEach(z => {
        items.push(`<button type="button" class="ztab ${state.activeZona == z.idzona ? 'active' : ''}" onclick="filtrarZonaNueva(${z.idzona})"><span class="zt-dot" style="background:${z.color};"></span> ${escapeAttr(z.nombre)}</button>`);
    });
    tabs.innerHTML = items.join('');
}

function escapeAttr(s) { return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c]); }

window.filtrarZonaNueva = (z) => {
    state.activeZona = z;
    renderZonasTabs();
    renderMesas();
};

function renderMesas() {
    const row = document.getElementById('tables-row');
    let mesas = state.mesas;
    if (state.activeZona !== 'all') {
        mesas = mesas.filter(m => Number(m.idzona) === Number(state.activeZona));
    }
    if (mesas.length === 0) {
        row.innerHTML = '<div style="font-size:11px;color:var(--text-muted);padding:8px;">Sin mesas en esta zona</div>';
        return;
    }
    row.innerHTML = mesas.map(m => `
        <button class="table-btn ${m.estado} ${state.selectedMesa && Number(state.selectedMesa.idmesa) === Number(m.idmesa) ? 'active' : ''}"
                onclick="clickMesa(${m.idmesa})">
            <span class="num">${m.numero}</span>
            <span class="pers">${m.capacidad}p</span>
        </button>
    `).join('');
}

window.clickMesa = async (idmesa) => {
    const m = state.mesas.find(x => Number(x.idmesa) === Number(idmesa));
    if (!m) return;
    await seleccionarMesa(m);
};

async function seleccionarMesa(m) {
    state.selectedMesa = m;
    document.getElementById('table-title').textContent = 'Mesa ' + m.numero;
    document.getElementById('table-persons').textContent = m.capacidad;

    // Si la mesa tiene una orden en curso, cargarla
    const ordenExistente = await API.ordenPorMesa(m.idmesa);
    if (ordenExistente && ordenExistente.idorden) {
        state.orden = await API.ordenMostrar(ordenExistente.idorden);
        state.readonly = !!(state.orden && state.orden.readonly);
        document.getElementById('order-id').textContent = 'Orden #' + state.orden.numero;

        // Si la mesa esta siendo atendida por otro mozo, avisar
        if (state.readonly) {
            const nombreMozo = state.orden.propietario_nombre || state.orden.mozo || 'otro mozo';
            Swal.fire({
                icon: 'warning',
                title: 'Mesa ocupada por otro mozo',
                html: 'Esta mesa <b>Nro ' + m.numero + '</b> está siendo atendida por:<br><br>' +
                      '<div style="font-size:18px;font-weight:800;color:#5b3df5;">' + nombreMozo + '</div><br>' +
                      '<div style="font-size:13px;color:#6b7280;">Puedes ver la orden pero no modificarla.</div>',
                confirmButtonColor: '#5b3df5',
                confirmButtonText: 'Entendido'
            });
        }
    } else {
        state.orden = null;
        state.readonly = false;
        document.getElementById('order-id').textContent = 'Orden nueva';
    }
    renderMesas();
    renderOrden();
    aplicarModoReadonly();
}

// Aplica/Desaplica el modo solo-lectura sobre los controles del panel de orden.
function aplicarModoReadonly() {
    const ro = !!state.readonly;
    const panel = document.querySelector('.order-panel');
    if (!panel) return;
    panel.classList.toggle('readonly-mode', ro);

    // Botones del panel de acciones
    panel.querySelectorAll('.order-actions button, .charge-btn, .pay-btn, .order-tab, .observation input')
        .forEach(el => { el.disabled = ro; });

    // Banner informativo arriba de los items
    let banner = document.getElementById('readonly-banner');
    if (ro) {
        const nombre = (state.orden && (state.orden.propietario_nombre || state.orden.mozo)) || 'otro mozo';
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'readonly-banner';
            banner.style.cssText = 'margin:8px 14px 0;padding:8px 12px;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;font-size:11px;font-weight:600;color:#92400e;display:flex;align-items:center;gap:8px;';
            document.querySelector('.order-tabs')?.after(banner);
        }
        banner.innerHTML = '<i class="fa-solid fa-lock"></i> Solo lectura · Atendida por <b>' + escapeAttr(nombre) + '</b>';
    } else if (banner) {
        banner.remove();
    }
}

window.agregarProducto = async (idproducto) => {
    if (state.readonly) {
        showToast('Esta orden es de otro mozo, no puedes modificarla', 'error');
        return;
    }
    if (!state.selectedMesa && state.type === 'dine_in') {
        showToast('Selecciona una mesa primero', 'error');
        return;
    }

    // Bloqueo por stock (por presentación): si TODAS las presentaciones con
    // control de stock están en 0, el producto está agotado.
    const prod = state.productos.find(p => Number(p.idproducto) === Number(idproducto));
    if (prod && Number(prod.num_con_stock) > 0 && Number(prod.stock_total) <= 0) {
        avisarSinStock(prod.nombre);
        return;
    }

    // Si el producto tiene varias variantes, abrir el modal antes de agregar
    if (prod && Number(prod.num_precios) > 1) {
        await abrirModalVariante(prod);
        return;
    }

    await agregarProductoConPrecio(idproducto, null);
};

function avisarSinStock(nombre) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'warning', title: 'Sin stock',
            html: 'No tiene stock en este producto:<br><b>' + (nombre || '') + '</b>',
            confirmButtonColor: '#ef4444' });
    } else {
        showToast('No tiene stock en este producto', 'error');
    }
}

async function abrirModalVariante(prod) {
    const precios = await Http.get('../ajax/producto.php?op=precios&idproducto=' + prod.idproducto);
    if (!precios || precios.length === 0) {
        await agregarProductoConPrecio(prod.idproducto, null);
        return;
    }
    if (precios.length === 1) {
        await agregarProductoConPrecio(prod.idproducto, precios[0].idprecio);
        return;
    }
    document.getElementById('modal-variante-title').textContent = 'Elige variante de ' + prod.nombre;
    document.getElementById('variante-list').innerHTML = precios.map(p => {
        const agot = Number(p.controla_stock) === 1 && Number(p.stock) <= 0;
        const stockBadge = Number(p.controla_stock) === 1
            ? (agot
                ? '<span class="badge badge-red" style="font-size:10px;margin-left:6px;">AGOTADO</span>'
                : '<span class="badge badge-green" style="font-size:10px;margin-left:6px;">Stock: ' + Number(p.stock) + '</span>')
            : '';
        const onclick = agot
            ? `avisarSinStock('${(prod.nombre + ' - ' + p.nombre).replace(/'/g, "\\'")}')`
            : `elegirVariante(${prod.idproducto}, ${p.idprecio})`;
        return `
        <button type="button" class="btn"
                onclick="${onclick}"
                style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;text-align:left;${agot ? 'opacity:.55;' : ''}">
            <span>
                <span style="font-weight:600;">${p.nombre}</span>
                ${Number(p.es_default)===1 ? '<span class="badge badge-purple" style="font-size:10px;margin-left:6px;">Default</span>' : ''}
                ${stockBadge}
            </span>
            <span style="font-weight:700;color:var(--primary);">${fmt.money(p.precio)}</span>
        </button>`;
    }).join('');
    openModal('modal-variante');
}

window.elegirVariante = async (idproducto, idprecio) => {
    closeModal('modal-variante');
    await agregarProductoConPrecio(idproducto, idprecio);
};

async function agregarProductoConPrecio(idproducto, idprecio) {
    // Si no hay orden activa, crearla
    const esNuevaOrden = !state.orden || !state.orden.idorden;
    if (esNuevaOrden) {
        const r = await API.ordenCrear({
            idmesa:   state.selectedMesa ? state.selectedMesa.idmesa : '',
            tipo:     state.type,
            mozo:     'Cajero',
            idsesion: state.sesion ? state.sesion.idsesion : ''
        });
        if (!r.ok) { showToast('Error al crear orden', 'error'); return; }
        state.orden = await API.ordenMostrar(r.idorden);
        document.getElementById('order-id').textContent = 'Orden #' + state.orden.numero;
        state.mesas = await API.mesas();
        renderMesas();
        emitOrden('orden-creada', { idorden: r.idorden, idmesa: state.selectedMesa?.idmesa });
    }

    const payload = {
        idorden:    state.orden.idorden,
        idproducto: idproducto,
        cantidad:   1,
        nota:       ''
    };
    if (idprecio) payload.idprecio = idprecio;

    const r = await Http.post('../ajax/orden.php?op=agregarItem', payload);
    if (r.ok) {
        state.orden = await API.ordenMostrar(state.orden.idorden);
        renderOrden();
        emitOrden('orden-actualizada', { idorden: state.orden.idorden, idmesa: state.selectedMesa?.idmesa });
    } else if (r.agotado) {
        avisarSinStock('');
    } else if (r.msg) {
        showToast(r.msg, 'error');
    }
}

window.cambiarCantidad = async (iddetalle, delta) => {
    if (state.readonly) { showToast('Solo lectura, no puedes modificar esta orden', 'error'); return; }
    const item = state.orden.items.find(i => Number(i.iddetalle) === Number(iddetalle));
    if (!item) return;
    const nueva  = Number(item.cantidad) + delta;
    const enviada = Number(item.cantidad_enviada || 0);

    // Si va a reducir por debajo de lo que ya esta en cocina, pedir confirmacion.
    if (delta < 0 && enviada > 0 && nueva < enviada) {
        const cantAnular = enviada - Math.max(nueva, 0);
        const ok = await Swal.fire({
            icon: 'warning',
            title: '¿Anular en cocina?',
            html: 'Vas a anular <b>' + cantAnular + ' unidad(es)</b> de <b>' + item.nombre + '</b> que ya fueron enviadas a cocina.<br><br>' +
                  '<div style="font-size:12px;color:#6b7280;">La cocina recibirá una comanda de ANULACIÓN al próximo envío.</div>',
            showCancelButton: true,
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            reverseButtons: true
        });
        if (!ok.isConfirmed) return;
    }

    if (nueva <= 0) {
        await API.ordenEliminarItem({ iddetalle });
    } else {
        const r = await API.ordenActualizarItem({ iddetalle, cantidad: nueva });
        // Rechazo por stock (no exceder inventario): avisar y no cambiar cantidad.
        if (r && r.ok === false && r.msg) showToast(r.msg, 'error');
    }
    state.orden = await API.ordenMostrar(state.orden.idorden);
    renderOrden();
    emitOrden('orden-actualizada', { idorden: state.orden.idorden, idmesa: state.selectedMesa?.idmesa });
};

window.eliminarItem = async (iddetalle) => {
    if (state.readonly) { showToast('Solo lectura, no puedes modificar esta orden', 'error'); return; }

    const item = state.orden.items.find(i => Number(i.iddetalle) === Number(iddetalle));
    const enviada = item ? Number(item.cantidad_enviada || 0) : 0;

    // Si ya fue enviado a cocina, confirmar la anulacion
    if (enviada > 0) {
        const ok = await Swal.fire({
            icon: 'warning',
            title: '¿Anular plato en cocina?',
            html: 'Este plato <b>' + item.nombre + '</b> (' + enviada + ' unidad(es)) ya fue enviado a cocina.<br><br>' +
                  '<div style="font-size:12px;color:#6b7280;">La cocina recibirá una comanda de ANULACIÓN al próximo envío.</div>',
            showCancelButton: true,
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            reverseButtons: true
        });
        if (!ok.isConfirmed) return;
    }

    await API.ordenEliminarItem({ iddetalle });
    state.orden = await API.ordenMostrar(state.orden.idorden);
    renderOrden();
    emitOrden('orden-actualizada', { idorden: state.orden.idorden, idmesa: state.selectedMesa?.idmesa });
};

// Marca / quita un item como CORTESIA (invitación): no se cobra pero sí descuenta stock.
window.toggleCortesia = async (iddetalle, cortesia) => {
    if (state.readonly) { showToast('Solo lectura, no puedes modificar esta orden', 'error'); return; }
    await Http.post('../ajax/orden.php?op=marcarCortesia', { iddetalle, cortesia });
    state.orden = await API.ordenMostrar(state.orden.idorden);
    renderOrden();
    showToast(Number(cortesia) === 1 ? 'Producto marcado como cortesía' : 'Cortesía quitada', 'success');
    emitOrden('orden-actualizada', { idorden: state.orden.idorden, idmesa: state.selectedMesa?.idmesa });
};

// Maneja el clic en el botón de cortesía:
//  - Si la línea YA es cortesía  => la quita (toggle completo).
//  - Si es paga de 1 unidad      => la marca completa (toggle).
//  - Si es paga con varias       => pregunta CUÁNTAS unidades son cortesía
//    (cortesía PARCIAL: divide la línea en paga + cortesía).
window.marcarCortesiaUI = async (iddetalle) => {
    if (state.readonly) { showToast('Solo lectura, no puedes modificar esta orden', 'error'); return; }
    const item = state.orden && state.orden.items
        ? state.orden.items.find(i => Number(i.iddetalle) === Number(iddetalle))
        : null;
    if (!item) return;

    const esCortesia = Number(item.cortesia) === 1;
    const cant = Number(item.cantidad);

    // Quitar cortesía: toggle directo (el backend la fusiona con el pagado).
    if (esCortesia) { await toggleCortesia(iddetalle, 0); return; }

    // Cuántas unidades van de cortesía: 1 directo, o preguntar si hay varias.
    let n = 1;
    if (cant > 1) {
        if (typeof Swal === 'undefined') { n = cant; }
        else {
            const res = await Swal.fire({
                title: 'Marcar cortesía',
                html: '¿Cuántas unidades de <b>' + (item.nombre || '') + '</b> son cortesía?<br><small style="color:#6b7280;">Máximo ' + cant + '</small>',
                input: 'number',
                inputValue: cant,
                inputAttributes: { min: 1, max: cant, step: 1 },
                showCancelButton: true,
                confirmButtonText: 'Aplicar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#16a34a',
                inputValidator: (v) => {
                    const x = Number(v);
                    if (!Number.isFinite(x) || x < 1 || x > cant) return 'Ingresa un número entre 1 y ' + cant;
                }
            });
            if (!res.isConfirmed) return;
            n = Number(res.value);
        }
    }

    // Siempre por el endpoint parcial: fusiona en UNA sola fila de cortesía
    // (incluso si son todas o 1 unidad).
    const r = await API.ordenCortesiaParcial({ iddetalle, cantidad: n });
    if (r && r.ok === false) {
        showToast(r.msg || 'No se pudo aplicar la cortesía', 'error');
        return;
    }
    state.orden = await API.ordenMostrar(state.orden.idorden);
    renderOrden();
    showToast(n + ' unidad(es) marcada(s) como cortesía', 'success');
    emitOrden('orden-actualizada', { idorden: state.orden.idorden, idmesa: state.selectedMesa?.idmesa });
};

// Coloca cada fila de CORTESÍA justo debajo de su producto pagado equivalente
// (mismo producto/presentación/nota). Las cortesías sin pagado quedan al final.
function agruparCortesias(items) {
    const key = (i) => [i.idproducto, (i.idprecio ?? ''), i.nombre, (i.nota || '')].join('|');
    const cortesias = items.filter(i => Number(i.cortesia) === 1);
    const usadas = new Set();
    const result = [];
    items.forEach(it => {
        if (Number(it.cortesia) === 1) return; // se colocan junto a su pagado
        result.push(it);
        cortesias.forEach(c => {
            if (usadas.has(c.iddetalle)) return;
            if (key(c) === key(it)) { result.push(c); usadas.add(c.iddetalle); }
        });
    });
    // Cortesías huérfanas (sin pagado correspondiente): al final, en su orden.
    cortesias.forEach(c => { if (!usadas.has(c.iddetalle)) result.push(c); });
    return result;
}

function renderOrden() {
    const cont = document.getElementById('order-items');
    // Filtrar items con cantidad=0 (estan marcados para anulacion al proximo envio, no se muestran)
    const itemsVisibles = (state.orden && state.orden.items)
        ? agruparCortesias(state.orden.items.filter(i => Number(i.cantidad) > 0))
        : [];

    if (!state.orden || itemsVisibles.length === 0) {
        cont.innerHTML = '<div class="empty-cart"><i class="fa-solid fa-cart-shopping"></i> Sin productos en la orden</div>';
        document.getElementById('subtotal').textContent     = fmt.money(state.orden ? state.orden.subtotal : 0);
        document.getElementById('tax').textContent          = fmt.money(state.orden ? state.orden.igv      : 0);
        document.getElementById('total').textContent        = fmt.money(state.orden ? state.orden.total    : 0);
        document.getElementById('charge-amount').textContent = fmt.money(state.orden ? state.orden.total   : 0);
        return;
    }
    cont.innerHTML = itemsVisibles.map(i => {
        const esCortesia = Number(i.cortesia) === 1;
        const original = Number(i.precio) * Number(i.cantidad);
        const precioHtml = esCortesia
            ? `<span style="text-decoration:line-through;color:#9ca3af;font-size:11px;">${fmt.money(original)}</span>
               <span style="display:block;color:#16a34a;font-weight:800;font-size:11px;">CORTESÍA</span>`
            : fmt.money(i.subtotal);
        return `
        <div class="order-item"${esCortesia ? ' style="background:#f0fdf4;"' : ''}>
            <div class="qty-control">
                <button class="qty-btn" onclick="cambiarCantidad(${i.iddetalle}, -1)">−</button>
                <span class="qty-num">${Number(i.cantidad)}</span>
                <button class="qty-btn" onclick="cambiarCantidad(${i.iddetalle}, 1)">+</button>
            </div>
            <div class="order-item-info">
                <div class="order-item-name">${i.nombre} ${esCortesia ? '<i class="fa-solid fa-gift" style="color:#16a34a;" title="Cortesía"></i>' : ''}</div>
                ${i.nota ? `<div class="order-item-note">${i.nota}</div>` : ''}
            </div>
            <div class="order-item-price">${precioHtml}</div>
            <button class="order-item-remove" title="${esCortesia ? 'Quitar cortesía' : 'Marcar como cortesía'}" onclick="marcarCortesiaUI(${i.iddetalle})" style="color:${esCortesia ? '#16a34a' : '#9ca3af'};"><i class="fa-solid fa-gift"></i></button>
            <button class="order-item-remove" onclick="eliminarItem(${i.iddetalle})"><i class="fa-solid fa-xmark"></i></button>
        </div>`;
    }).join('');

    document.getElementById('subtotal').textContent      = fmt.money(state.orden.subtotal);
    document.getElementById('tax').textContent           = fmt.money(state.orden.igv);
    document.getElementById('total').textContent         = fmt.money(state.orden.total);
    document.getElementById('charge-amount').textContent = fmt.money(state.orden.total);
}

window.guardarObservacion = async () => {
    if (state.readonly) { showToast('Solo lectura, no puedes modificar esta orden', 'error'); return; }
    if (!state.orden) { showToast('No hay orden activa', 'error'); return; }
    const obs = document.getElementById('observation-input').value.trim();
    await API.ordenActualizarCab({
        idorden: state.orden.idorden,
        tipo: state.type,
        observacion: obs,
        idcliente: ''
    });
    showToast('Observación guardada', 'success');
};

window.enviarACocina = async () => {
    if (state.readonly) { showToast('Solo lectura, no puedes enviar esta orden', 'error'); return; }
    if (!state.orden) { showToast('No hay orden activa', 'error'); return; }

    const r = await API.ordenEnviarCocina(state.orden.idorden);
    if (!r || !r.ok) {
        showToast((r && r.msg) || 'Error al enviar a cocina', 'error');
        return;
    }

    // Backend devuelve dos mapas: adiciones y anulaciones (iddetalle => cantidadDelta)
    const adic = (r.adiciones   && typeof r.adiciones   === 'object') ? r.adiciones   : {};
    const anul = (r.anulaciones && typeof r.anulaciones === 'object') ? r.anulaciones : {};
    const hayAdic = Object.keys(adic).length > 0;
    const hayAnul = Object.keys(anul).length > 0;

    if (!hayAdic && !hayAnul) {
        showToast('No hay cambios para enviar a cocina', '');
        return;
    }

    // Imprimir adiciones primero (si hay)
    if (hayAdic) abrirComanda(state.orden.idorden, adic, 'adicion');
    // Despues anulaciones (si hay) - segunda hoja, con pequeño delay para evitar bloqueo de popups
    if (hayAnul) {
        setTimeout(() => abrirComanda(state.orden.idorden, anul, 'anular'), hayAdic ? 400 : 0);
    }

    // Toast resumen
    const sumAdic = Object.values(adic).reduce((a, b) => a + Number(b), 0);
    const sumAnul = Object.values(anul).reduce((a, b) => a + Number(b), 0);
    let msg = [];
    if (sumAdic > 0) msg.push('+' + sumAdic + ' a preparar');
    if (sumAnul > 0) msg.push('−' + sumAnul + ' anuladas');
    showToast(msg.join(' · '), 'success');

    state.orden = await API.ordenMostrar(state.orden.idorden);
    renderOrden();
    emitOrden('orden-enviada-cocina', { idorden: state.orden.idorden, idmesa: state.selectedMesa?.idmesa });
};

// Helper para abrir la ventana de comanda con un mapa de items
function abrirComanda(idorden, mapa, tipo) {
    const itemsParam = Object.keys(mapa).map(id => id + ':' + mapa[id]).join(',');
    if (!itemsParam) return;
    const url = 'comanda?id=' + idorden
              + '&type=' + (tipo || 'adicion')
              + '&items=' + encodeURIComponent(itemsParam)
              + '&_=' + Date.now();
    window.open(url, '_blank', 'width=420,height=720');
}

// Estado del modal de cobro
const cobroState = {
    tipoComprobante: 'nota_venta',
    metodoPago: 'efectivo'
};

// Tipos de documento de identidad (catalogo SUNAT 06)
const TIPOS_DOC_CLI = [
    { v: '1', t: 'DNI'        },
    { v: '6', t: 'RUC'        },
    { v: '4', t: 'CE'         },
    { v: '7', t: 'Pasaporte'  },
    { v: '0', t: 'Otro'       },
];

window.abrirModalCobro = () => {
    if (!can('cobrar')) { showToast('No tienes permiso para cobrar', 'error'); return; }
    if (state.readonly) { showToast('Esta orden es de otro mozo, no puedes cobrarla', 'error'); return; }
    if (!state.orden) { showToast('No hay orden activa', 'error'); return; }
    if (!state.sesion) { showToast('No hay caja abierta', 'error'); return; }
    if (!state.orden.items || state.orden.items.length === 0) { showToast('La orden está vacía', 'error'); return; }

    const totalNum = Number(state.orden.total) || 0;
    document.getElementById('cobro-numero').textContent = '#' + state.orden.numero;
    document.getElementById('cobro-mesa').textContent   = state.orden.mesa_numero ? 'Mesa ' + state.orden.mesa_numero : 'Sin mesa';
    document.getElementById('cobro-total').textContent  = fmt.money(totalNum);

    // Reset
    cobroState.tipoComprobante = 'nota_venta';
    cobroState.metodoPago      = state.paymentMethod || 'efectivo';
    document.getElementById('cobro-recibido').value      = totalNum.toFixed(2);
    document.getElementById('cobro-vuelto').textContent  = fmt.money(0);
    ['cobro-tarjeta-4','cobro-tarjeta-voucher','cobro-trans-op','cli-num-doc','cli-razon','cli-direccion','cli-email'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    // Pago combinado: resetear checkbox y campos
    const elChk = document.getElementById('cobro-combinado-chk');
    if (elChk) elChk.checked = false;
    const elBox = document.getElementById('cobro-combinado-box');
    if (elBox) elBox.style.display = 'none';
    const elComb = document.getElementById('cobro-comb-monto');
    if (elComb) elComb.value = '';
    const elCombMet = document.getElementById('cobro-comb-metodo');
    if (elCombMet) elCombMet.value = 'yape';

    // Llenar select tipo doc cliente
    const sel = document.getElementById('cli-tipo-doc');
    if (sel) sel.innerHTML = TIPOS_DOC_CLI.map(t => `<option value="${t.v}">${t.t}</option>`).join('');

    sincronizarTipoComp();
    sincronizarMetodo();
    openModal('modal-cobro');

    // Ocultar Boleta/Factura si no tienen serie creada (no bloquea, solo limpia el UI)
    if (typeof window.aplicarSeriesDisponibles === 'function') window.aplicarSeriesDisponibles();
};

function sincronizarTipoComp() {
    document.querySelectorAll('#tipo-comp-grid .pay-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.tipo === cobroState.tipoComprobante);
    });
    const esElectronico = (cobroState.tipoComprobante === 'boleta' || cobroState.tipoComprobante === 'factura');
    document.getElementById('cliente-datos').style.display = esElectronico ? '' : 'none';
    document.getElementById('info-sunat').style.display    = esElectronico ? '' : 'none';
    document.getElementById('info-ticket').style.display   = esElectronico ? 'none' : '';

    // Setear valor por defecto del tipo de doc segun comprobante
    const sel = document.getElementById('cli-tipo-doc');
    if (sel) {
        if (cobroState.tipoComprobante === 'factura') sel.value = '6';   // RUC
        else if (cobroState.tipoComprobante === 'boleta')  sel.value = '1';   // DNI
    }
}

window.buscarCliente = async () => {
    const doc = document.getElementById('cli-num-doc').value.trim();
    if (!doc) return;

    const tipo = (doc.length === 11) ? '6' : '1';   // 6 = RUC, 1 = DNI

    // 1) Buscar en BD local (cliente_facturacion)
    const r = await Http.post('../ajax/cliente_facturacion.php?op=buscarPorDoc', { numero_documento: doc });

    if (r && r.idclifact) {
        const direccionBD = (r.direccion || '').trim();

        // Si esta en BD y SI tiene direccion → usar directamente
        // Si es RUC sin direccion → enriquecer desde SUNAT (registro viejo)
        if (direccionBD !== '' || tipo === '1') {
            document.getElementById('cli-razon').value     = r.razon_social || '';
            document.getElementById('cli-direccion').value = direccionBD;
            document.getElementById('cli-email').value     = r.email || '';
            document.getElementById('cli-tipo-doc').value  = r.tipo_documento || tipo;
            showToast('Cliente encontrado en BD', 'success');
            return;
        }

        // Es RUC en BD pero sin direccion → consultar SUNAT y actualizar
        const s2 = await Http.post('../ajax/sunat.php?op=consultar', { tipo_doc: '6', numero: doc });
        const dirNueva = (s2 && s2.ok ? (s2.direccion || '') : '');
        document.getElementById('cli-tipo-doc').value  = '6';
        document.getElementById('cli-razon').value     = r.razon_social || (s2 && s2.razon_social) || '';
        document.getElementById('cli-direccion').value = dirNueva;
        document.getElementById('cli-email').value     = r.email || '';
        // Persistir la nueva direccion para no volver a consultar SUNAT
        if (dirNueva) {
            await Http.post('../ajax/cliente_facturacion.php?op=guardar', {
                tipo_documento:   '6',
                numero_documento: doc,
                razon_social:     r.razon_social || (s2 && s2.razon_social) || '',
                direccion:        dirNueva,
                email:            r.email || '',
                telefono:         r.telefono || ''
            });
            showToast('Dirección actualizada desde SUNAT', 'success');
        } else {
            showToast('Cliente en BD — SUNAT no devolvió dirección', '');
        }
        return;
    }

    // 2) No esta en BD → consultar SUNAT/RENIEC
    const s = await Http.post('../ajax/sunat.php?op=consultar', { tipo_doc: tipo, numero: doc });
    if (!s || !s.ok) {
        showToast(s && s.msg ? s.msg : 'No registrado, completa los datos manualmente', '');
        return;
    }

    // 3) Registrar en BD automaticamente
    const direccion = s.direccion || '';
    const ins = await Http.post('../ajax/cliente_facturacion.php?op=guardar', {
        tipo_documento:   tipo,
        numero_documento: doc,
        razon_social:     s.razon_social || '',
        direccion:        direccion,
        email:            '',
        telefono:         ''
    });

    // 4) Rellenar el formulario
    document.getElementById('cli-tipo-doc').value  = tipo;
    document.getElementById('cli-razon').value     = s.razon_social || '';
    document.getElementById('cli-direccion').value = direccion;
    document.getElementById('cli-email').value     = '';

    if (ins && ins.ok) {
        showToast('Registrado en BD y seleccionado', 'success');
    } else {
        showToast('Datos cargados desde ' + (tipo === '6' ? 'SUNAT' : 'RENIEC'), 'success');
    }
};

function sincronizarMetodo() {
    document.querySelectorAll('#metodo-pago-grid .pay-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.metodo === cobroState.metodoPago);
    });
    document.querySelectorAll('.metodo-fields').forEach(el => el.style.display = 'none');
    // Yape y Plin comparten el mismo campo (campo-yape) que muestra el QR
    const campoId = (cobroState.metodoPago === 'yape' || cobroState.metodoPago === 'plin')
        ? 'campo-yape' : 'campo-' + cobroState.metodoPago;
    const visible = document.getElementById(campoId);
    if (visible) visible.style.display = '';
    if (cobroState.metodoPago === 'yape' || cobroState.metodoPago === 'plin') {
        mostrarQR(cobroState.metodoPago);
    }
    actualizarVuelto();
}

// Muestra el QR configurado para Yape o Plin (Plin cae al de Yape si no tiene propio).
// prefix permite reutilizarlo en distintos bloques (campo-yape o pago combinado).
function mostrarQR(metodo, prefix) {
    prefix = prefix || 'cobro-qr';
    const cfg = window.YAPEZ_CONFIG || {};
    let ruta = (metodo === 'plin') ? (cfg.plin_qr || cfg.yape_qr) : cfg.yape_qr;
    const wrap = document.getElementById(prefix + '-wrap');
    const vacio = document.getElementById(prefix + '-vacio');
    const titulo = document.getElementById(prefix + '-titulo');
    const img = document.getElementById(prefix + '-img');
    if (!wrap || !vacio) return;
    if (ruta) {
        if (img) img.src = '../' + ruta;
        if (titulo) titulo.textContent = 'Escanea con ' + (metodo === 'plin' ? 'Plin' : 'Yape') + ' para pagar';
        wrap.style.display = ''; vacio.style.display = 'none';
    } else {
        wrap.style.display = 'none'; vacio.style.display = '';
    }
}

function actualizarVuelto() {
    if (cobroState.metodoPago !== 'efectivo' || !state.orden) return;
    const total    = Number(state.orden.total);
    const recibido = parseFloat(document.getElementById('cobro-recibido').value) || 0;
    // Si hay parte combinada en Yape/Plin, el vuelto se calcula sobre lo que falta en efectivo
    const comb = cobroCombinadoMonto();
    const efectivoNecesario = Math.max(0, +(total - comb).toFixed(2));
    const vuelto = Math.max(0, +(recibido - efectivoNecesario).toFixed(2));
    document.getElementById('cobro-vuelto').textContent = fmt.money(vuelto);
}

// Monto de la parte combinada (Yape/Plin) si el checkbox esta activo
function cobroCombinadoMonto() {
    const chk = document.getElementById('cobro-combinado-chk');
    if (!chk || !chk.checked) return 0;
    return Math.max(0, parseFloat(document.getElementById('cobro-comb-monto')?.value) || 0);
}

window.toggleCombinado = () => {
    const on = document.getElementById('cobro-combinado-chk').checked;
    document.getElementById('cobro-combinado-box').style.display = on ? '' : 'none';
    actualizarCombinado();
};

window.actualizarCombinado = () => {
    if (!state.orden) return;
    const total = Number(state.orden.total);
    const comb  = cobroCombinadoMonto();
    const efectivo = Math.max(0, +(total - comb).toFixed(2));
    const el = document.getElementById('cobro-comb-efectivo');
    if (el) el.textContent = fmt.money(efectivo);
    // Mostrar el QR de la parte digital (Yape o Plin) si el combinado esta activo
    const chk = document.getElementById('cobro-combinado-chk');
    if (chk && chk.checked) {
        const metMet = document.getElementById('cobro-comb-metodo');
        mostrarQR(metMet ? metMet.value : 'yape', 'cobro-comb-qr');
    }
    actualizarVuelto();
};

window.setMontoRecibido = (val) => {
    const total = Number(state.orden ? state.orden.total : 0);
    const monto = (val === 'exacto') ? total : Number(val);
    document.getElementById('cobro-recibido').value = monto.toFixed(2);
    actualizarVuelto();
};

window.confirmarCobro = async () => {
    const btn = document.getElementById('btn-confirmar-cobro');
    if (!state.orden) return;

    const total = Number(state.orden.total);
    let recibido = total;
    let referencia = '';
    let metadata = {};
    let metodoEnviar = cobroState.metodoPago;   // lo que se guarda en la orden

    if (cobroState.metodoPago === 'efectivo') {
        const comb = cobroCombinadoMonto();
        if (comb > 0) {
            // PAGO COMBINADO: parte efectivo + parte Yape/Plin
            const metodoDig = document.getElementById('cobro-comb-metodo').value; // yape|plin
            if (comb > total) { showToast('La parte digital no puede ser mayor al total', 'error'); return; }
            const efectivoNec = +(total - comb).toFixed(2);
            recibido = parseFloat(document.getElementById('cobro-recibido').value) || 0;
            if (recibido < efectivoNec) { showToast('El efectivo recibido (S/ ' + recibido.toFixed(2) + ') es menor a lo que falta (S/ ' + efectivoNec.toFixed(2) + ')', 'error'); return; }
            metodoEnviar = 'mixto';
            referencia = 'Efectivo S/ ' + efectivoNec.toFixed(2) + ' + ' + (metodoDig === 'plin' ? 'Plin' : 'Yape') + ' S/ ' + comb.toFixed(2);
            metadata = { partes: [
                { metodo: 'efectivo', monto: efectivoNec },
                { metodo: metodoDig,  monto: comb }
            ]};
        } else {
            recibido = parseFloat(document.getElementById('cobro-recibido').value) || 0;
            if (recibido < total) { showToast('El monto recibido es menor al total', 'error'); return; }
        }
    } else if (cobroState.metodoPago === 'tarjeta') {
        const last4   = document.getElementById('cobro-tarjeta-4').value.trim();
        const voucher = document.getElementById('cobro-tarjeta-voucher').value.trim();
        if (!voucher) { showToast('Ingresa el número de voucher', 'error'); return; }
        referencia = 'Voucher ' + voucher;
        metadata = { ultimos4: last4, voucher };
    } else if (cobroState.metodoPago === 'yape' || cobroState.metodoPago === 'plin') {
        recibido = total;
        referencia = (cobroState.metodoPago === 'plin' ? 'Plin' : 'Yape') + ' S/ ' + total.toFixed(2);
        metadata = { metodo: cobroState.metodoPago };
    } else if (cobroState.metodoPago === 'transferencia') {
        const banco = document.getElementById('cobro-trans-banco').value;
        const nro   = document.getElementById('cobro-trans-op').value.trim();
        if (!nro) { showToast('Ingresa el número de operación', 'error'); return; }
        referencia = banco + ' ' + nro;
        metadata = { banco, operacion: nro };
    }

    // Validacion datos cliente para boleta/factura
    let clienteData = null;
    if (cobroState.tipoComprobante === 'boleta' || cobroState.tipoComprobante === 'factura') {
        // Validar que exista la SERIE de numeracion para este tipo de documento.
        // Sin serie no se puede generar el comprobante => no permitir cobrar.
        const tipoDocSunatChk = cobroState.tipoComprobante === 'factura' ? '01' : '03';
        const okSerie = await validarSerieComprobante(tipoDocSunatChk, cobroState.tipoComprobante);
        if (!okSerie) return;

        const tipoDoc = document.getElementById('cli-tipo-doc').value;
        const numDoc  = document.getElementById('cli-num-doc').value.trim();
        const razon   = document.getElementById('cli-razon').value.trim();
        const direccion = document.getElementById('cli-direccion').value.trim();
        const email     = document.getElementById('cli-email').value.trim();
        if (!numDoc || !razon) { showToast('Datos del cliente obligatorios para boleta/factura', 'error'); return; }
        if (cobroState.tipoComprobante === 'factura' && (tipoDoc !== '6' || numDoc.length !== 11)) {
            showToast('Para factura el cliente debe tener RUC (11 digitos)', 'error'); return;
        }
        clienteData = { tipo_doc: tipoDoc, num_doc: numDoc, razon, direccion, email };
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

    // 1. Cobrar la orden (la marca como pagada en BD)
    const r = await API.ordenCobrar({
        idorden:          state.orden.idorden,
        metodo_pago:      metodoEnviar,
        idsesion:         state.sesion.idsesion,
        tipo_comprobante: cobroState.tipoComprobante,
        monto_recibido:   recibido,
        pago_referencia:  referencia,
        pago_metadata:    JSON.stringify(metadata)
    });

    if (!r.ok) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Cobro';
        showToast(r.msg || 'Error al cobrar', 'error');
        return;
    }

    // La mesa quedo libre tras el cobro: notificar a otras pestanas (Mesas)
    emitOrden('orden-cobrada', { idorden: state.orden.idorden, idmesa: state.selectedMesa?.idmesa });

    // 2. Si es boleta/factura, crear comprobante electronico (queda pendiente de envio)
    let mensajeFinal = 'Orden cobrada';
    let r2 = null;
    if (clienteData) {
        const tipoDocSunat = cobroState.tipoComprobante === 'factura' ? '01' : '03';
        r2 = await Http.post('../ajax/comprobante_electronico.php?op=crearDesdeOrden', {
            idorden:           state.orden.idorden,
            tipo_documento:    tipoDocSunat,
            cliente_tipo_doc:  clienteData.tipo_doc,
            cliente_num_doc:   clienteData.num_doc,
            cliente_razon:     clienteData.razon,
            cliente_direccion: clienteData.direccion,
            cliente_email:     clienteData.email,
        });
        if (r2.ok) {
            // Mensaje según si se envió automáticamente a SUNAT o quedó en cola
            const ea = r2.envio_auto;
            if (ea && ea.intentado) {
                mensajeFinal = ea.ok
                    ? 'Cobrado · Comprobante #' + r2.idcomprobante + ' ACEPTADO por SUNAT'
                    : 'Cobrado · Comprobante #' + r2.idcomprobante + ' enviado pero SUNAT respondió: ' + (ea.mensaje || 'revisar');
            } else {
                mensajeFinal = 'Cobrado · Comprobante #' + r2.idcomprobante + ' en cola SUNAT';
            }
            // Recordatorio tope RUS si fue una boleta y el mes esta cerca/superado
            if (cobroState.tipoComprobante === 'boleta' && typeof window.verificarTopeRusCobro === 'function') {
                window.verificarTopeRusCobro();
            }
        } else {
            mensajeFinal = 'Cobrado pero comprobante electronico fallo: ' + (r2.msg || '');
        }
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Cobro';

    const numMesa = state.selectedMesa ? state.selectedMesa.numero : null;
    closeModal('modal-cobro');

    // Funcion que abre el ticket impreso
    const fmtComp = (window.YAPEZ_CONFIG && window.YAPEZ_CONFIG.formato_comprobante) || 'ticket';
    const wWin = fmtComp === 'a4' ? 880 : 420;
    const hWin = fmtComp === 'a4' ? 980 : 720;
    const abrirImpresion = () => {
        window.open('comprobante?id=' + r.idorden + '&formato=' + fmtComp, '_blank', 'width=' + wWin + ',height=' + hWin);
    };

    // Si es boleta/factura mostrar modal post-cobro (Imprimir o WhatsApp).
    // Para ticket/nota_venta abrir directo el ticket.
    if (clienteData && (cobroState.tipoComprobante === 'boleta' || cobroState.tipoComprobante === 'factura')) {
        const idcomprobanteCE = (r2 && r2.idcomprobante) ? r2.idcomprobante : null;
        const tipoDocSunat   = cobroState.tipoComprobante === 'factura' ? '01' : '03';
        // El backend no devuelve numero_completo aún; lo construimos cliente-side
        const numeroFiscal   = ''; // se rellena con el modal si hace falta

        window.mostrarPostCobro({
            idcomprobante:  idcomprobanteCE,
            idclifact:      null,
            idcliente:      state.orden && state.orden.idcliente ? state.orden.idcliente : null,
            nombre:         clienteData.razon,
            documento:      clienteData.num_doc,
            tipo_doc:       clienteData.tipo_doc,
            comprobante:    numeroFiscal,
            tipo_documento: tipoDocSunat,
            total:          state.orden.total,
            link_pdf:       idcomprobanteCE ? (window.location.origin + '/puerto_habana/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=' + idcomprobanteCE) : '',
            numero_interno: r.idorden,
        }, abrirImpresion);
    } else {
        abrirImpresion();
    }

    // Resetear UI
    state.orden = null;
    state.selectedMesa = null;
    state.readonly = false;
    aplicarModoReadonly();
    document.getElementById('order-id').textContent = 'Orden nueva';
    document.getElementById('table-title').textContent = 'Sin mesa';
    document.getElementById('table-persons').textContent = '—';
    document.getElementById('observation-input').value = '';

    // Refrescar mesas SIEMPRE (la mesa cobrada queda libre en backend)
    state.mesas = await API.mesas();
    renderMesas();
    renderOrden();

    // Refrescar productos para que el stock descontado se vea al instante
    // (sin tener que recargar la página manualmente).
    await cargarProductos(true);

    // Toast con confirmacion de mesa liberada
    if (numMesa) {
        showToast('Mesa ' + numMesa + ' liberada · ' + mensajeFinal, 'success');
    } else {
        showToast(mensajeFinal, 'success');
    }
};

// Listeners del modal de cobro
$(function () {
    $('#tipo-comp-grid').on('click', '.pay-btn:not([disabled])', function () {
        cobroState.tipoComprobante = this.dataset.tipo;
        sincronizarTipoComp();
    });
    $('#metodo-pago-grid').on('click', '.pay-btn:not([disabled])', function () {
        cobroState.metodoPago = this.dataset.metodo;
        sincronizarMetodo();
    });
    $('#cobro-recibido').on('input', actualizarVuelto);
    $('#cobro-comb-monto').on('input', window.actualizarCombinado);

    // Al cambiar tipo de documento (DNI/RUC), limpiar los campos del cliente
    // para evitar que queden datos del documento anterior.
    $('#cli-tipo-doc').on('change', function () {
        ['cli-num-doc','cli-razon','cli-direccion','cli-email'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const numEl = document.getElementById('cli-num-doc');
        if (numEl) numEl.focus();
    });
});

function bindEvents() {
    document.getElementById('search-input').addEventListener('input', e => {
        state.searchTerm = e.target.value;
        // Debounce 300ms para no spammear el backend
        clearTimeout(state.pagSearchTimer);
        state.pagSearchTimer = setTimeout(() => cargarProductos(true), 300);
    });

    document.querySelectorAll('.order-tab').forEach(t => t.addEventListener('click', () => {
        document.querySelectorAll('.order-tab').forEach(x => x.classList.remove('active'));
        t.classList.add('active');
        state.type = t.dataset.type;
        if (state.orden) {
            API.ordenActualizarCab({
                idorden: state.orden.idorden,
                tipo: state.type,
                observacion: document.getElementById('observation-input').value.trim(),
                idcliente: ''
            });
        }
    }));

    document.querySelectorAll('.pay-btn').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('.pay-btn').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        state.paymentMethod = b.dataset.method;
    }));
}

init();

// Tiempo real: si el mozo (otro dispositivo) cambia el estado de las mesas o
// agrega items a la orden abierta, refrescar automáticamente. No interrumpe
// el modal de cobro.
async function refrescarTiempoReal() {
    const modal = document.getElementById('modal-cobro');
    if (modal && modal.classList.contains('active')) return;
    try {
        state.mesas = await API.mesas();
        renderMesas();
        // Si hay una orden abierta, refrescar sus items (ver lo que agrega el mozo)
        if (state.orden && state.orden.idorden) {
            const fresca = await API.ordenMostrar(state.orden.idorden);
            if (fresca && fresca.idorden) { state.orden = fresca; renderOrden(); }
        }
    } catch (e) { /* reintentar en el próximo tick */ }
}
if (window.Realtime) Realtime.start(refrescarTiempoReal, 4000);
