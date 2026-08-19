/* ============================================================
   PUERTO HABANA POS - Nueva Orden MOVIL (wizard 4 tabs)
   Reusa core.js (API, Http, fmt, showToast, swalConfirm, etc.)
   ============================================================ */

const mState = {
    selectedMesa: null,
    activeCategory: 'all',
    activeZona: 'all',
    searchTerm: '',
    type: 'dine_in',
    paymentMethod: 'efectivo',
    orden: null,
    sesion: null,
    categorias: [],
    productos: [],
    mesas: [],
    zonas: [],
    readonly: false,
    // Paginacion
    pagStart: 0, pagLimit: 24, pagHasMore: true, pagLoading: false,
    searchTimer: null
};

// BroadcastChannel para sincronizar con otras pestanas
let mBc = null;
try { if (typeof BroadcastChannel !== 'undefined') mBc = new BroadcastChannel('yapez-orders'); } catch(e) {}
function mEmit(type, payload) {
    try { if (mBc) mBc.postMessage({ type, ...(payload || {}), ts: Date.now() }); } catch(e) {}
}

// ============== INIT ==============
async function mInit() {
    mState.sesion = await API.cajaSesion();
    if (!mState.sesion || !mState.sesion.idsesion) {
        showToast('Caja cerrada — abre una sesión de caja primero', 'error');
    }

    [mState.categorias, mState.mesas, mState.zonas] = await Promise.all([
        API.categorias(),
        API.mesas(),
        Http.get('../ajax/zona.php?op=listar')
    ]);
    if (!Array.isArray(mState.zonas)) mState.zonas = [];

    mRenderZonas();
    mRenderMesas();
    mRenderCategorias();
    await mCargarProductos(true);

    mBindEvents();
    mAplicarPermisos();

    // Carga inicial según la URL:
    //  - ?orden=X  → cargar esa orden por id (para llevar / delivery o local).
    //  - ?mesa=X   → seleccionar la mesa (flujo local de siempre).
    const params = new URLSearchParams(window.location.search);
    const idordenParam = params.get('orden');
    const idmesa = params.get('mesa');
    if (idordenParam) {
        // modo=abrir => entrar a editar (productos); por defecto => cobrar (pagar).
        const destino = params.get('modo') === 'abrir' ? 'productos' : 'pagar';
        await mCargarOrdenPorId(idordenParam, destino);
    } else if (idmesa) {
        const m = mState.mesas.find(x => Number(x.idmesa) === Number(idmesa));
        if (m) await mSeleccionarMesa(m);
    }
}

// Carga una orden existente por su id y prepara la UI. Sirve para órdenes SIN
// mesa (para llevar / delivery), que antes no podían cargarse desde el módulo
// Pedidos. Para local con mesa, mantiene la mesa seleccionada.
// destino: pestaña a la que ir ('pagar' para cobrar, 'productos' para editar).
async function mCargarOrdenPorId(idorden, destino) {
    const orden = await API.ordenMostrar(idorden);
    if (!orden || !orden.idorden) { showToast('No se pudo cargar la orden', 'error'); return; }

    mState.orden    = orden;
    mState.readonly = !!orden.readonly;
    mState.type     = orden.tipo || 'dine_in';

    if (mState.type === 'dine_in' && orden.idmesa) {
        mState.selectedMesa = mState.mesas.find(x => Number(x.idmesa) === Number(orden.idmesa)) || null;
    } else {
        mState.selectedMesa = null;
    }

    // Reflejar el tipo en la UI (botones de tipo y visibilidad del bloque de mesas).
    document.querySelectorAll('.m-tipo-btn').forEach(b => b.classList.toggle('active', b.dataset.tipo === mState.type));
    const mesasWrap = document.getElementById('m-mesas-wrap');
    if (mesasWrap) mesasWrap.style.display = (mState.type === 'dine_in') ? '' : 'none';

    mRenderMesas();
    mRenderTodo();

    // Si la orden es de solo lectura (otro mozo), no forzar la pestaña de pago.
    const tab = (destino === 'productos' || mState.readonly) ? (mState.readonly ? 'carrito' : 'productos') : 'pagar';
    mIrATab(tab);
}

// ============== TABS ==============
function mIrATab(tabName) {
    document.querySelectorAll('.m-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.m-nav-tab').forEach(b => b.classList.remove('active'));
    const tab = document.querySelector('.m-tab[data-tab="' + tabName + '"]');
    const btn = document.querySelector('.m-nav-tab[data-target="' + tabName + '"]');
    if (tab) tab.classList.add('active');
    if (btn) btn.classList.add('active');
}

function mBindEvents() {
    document.querySelectorAll('.m-nav-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.target;
            // Validacion: requiere mesa para productos/carrito/pagar
            if (!mState.selectedMesa && mState.type === 'dine_in' && target !== 'mesas') {
                showToast('Primero selecciona una mesa', 'error');
                return;
            }
            // Pagar requiere permiso
            if (target === 'pagar' && !can('cobrar')) {
                showToast('No tienes permiso para cobrar', 'error');
                return;
            }
            mIrATab(target);
        });
    });

    // Buscador
    document.getElementById('m-search-input').addEventListener('input', e => {
        mState.searchTerm = e.target.value;
        clearTimeout(mState.searchTimer);
        mState.searchTimer = setTimeout(() => mCargarProductos(true), 300);
    });

    // Metodos de pago
    document.querySelectorAll('.m-pay-method').forEach(el => {
        el.addEventListener('click', () => {
            document.querySelectorAll('.m-pay-method').forEach(x => x.classList.remove('active'));
            el.classList.add('active');
            mState.paymentMethod = el.dataset.method;
        });
    });
}

function mAplicarPermisos() {
    document.querySelectorAll('[data-perm]').forEach(el => {
        const p = el.getAttribute('data-perm');
        if (!can(p)) el.style.display = 'none';
    });
}

// ============== ZONAS ==============
function mRenderZonas() {
    const cont = document.getElementById('m-zonas-chips');
    if (!cont) return;
    let html = '<button class="m-chip ' + (mState.activeZona === 'all' ? 'active' : '') + '" data-z="all"><span class="dot" style="background:#94a3b8;"></span>Todas</button>';
    mState.zonas.forEach(z => {
        html += '<button class="m-chip ' + (mState.activeZona == z.idzona ? 'active' : '') + '" data-z="' + z.idzona + '">'
              + '<span class="dot" style="background:' + z.color + ';"></span>' + mEsc(z.nombre) + '</button>';
    });
    cont.innerHTML = html;
    cont.querySelectorAll('.m-chip').forEach(b => b.addEventListener('click', () => {
        mState.activeZona = b.dataset.z === 'all' ? 'all' : Number(b.dataset.z);
        mRenderZonas();
        mRenderMesas();
    }));
}

// ============== MESAS ==============
function mRenderMesas() {
    const cont = document.getElementById('m-mesas-grid');
    if (!cont) return;
    let mesas = mState.mesas;
    if (mState.activeZona !== 'all') {
        mesas = mesas.filter(m => Number(m.idzona) === Number(mState.activeZona));
    }
    if (!mesas.length) {
        cont.innerHTML = '<div class="m-empty"><i class="fa-solid fa-chair"></i><h3>Sin mesas</h3><p>No hay mesas en esta zona</p></div>';
        return;
    }
    cont.innerHTML = mesas.map(m => `
        <button class="m-mesa-tile ${m.estado} ${mState.selectedMesa && Number(mState.selectedMesa.idmesa) === Number(m.idmesa) ? 'selected' : ''}"
                onclick="mClickMesa(${m.idmesa})">
            <span class="num">${m.numero}</span>
            <span class="pers">${m.capacidad}p</span>
        </button>
    `).join('');
}

window.mClickMesa = async (idmesa) => {
    const m = mState.mesas.find(x => Number(x.idmesa) === Number(idmesa));
    if (!m) return;
    await mSeleccionarMesa(m);
};

async function mSeleccionarMesa(m) {
    mState.selectedMesa = m;
    const ordenExistente = await API.ordenPorMesa(m.idmesa);
    if (ordenExistente && ordenExistente.idorden) {
        mState.orden = await API.ordenMostrar(ordenExistente.idorden);
        mState.readonly = !!(mState.orden && mState.orden.readonly);
        if (mState.readonly) {
            const nombreMozo = mState.orden.propietario_nombre || mState.orden.mozo || 'otro mozo';
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
        mState.orden = null;
        mState.readonly = false;
    }
    mRenderMesas();
    mRenderTodo();
    // Saltar a Productos automaticamente cuando se elige mesa libre/propia
    if (!mState.readonly) mIrATab('productos');
}

// ============== CATEGORIAS ==============
function mRenderCategorias() {
    const cont = document.getElementById('m-cats-chips');
    if (!cont) return;
    let html = '<button class="m-chip ' + (mState.activeCategory === 'all' ? 'active' : '') + '" data-c="all"><i class="fa-solid fa-table-cells"></i>Todas</button>';
    mState.categorias.forEach(c => {
        html += '<button class="m-chip ' + (c.codigo === mState.activeCategory ? 'active' : '') + '" data-c="' + c.codigo + '">'
              + '<i class="fa-solid ' + c.icono + '" style="color:' + c.color + ';"></i>' + mEsc(c.nombre) + '</button>';
    });
    cont.innerHTML = html;
    cont.querySelectorAll('.m-chip').forEach(b => b.addEventListener('click', async () => {
        mState.activeCategory = b.dataset.c;
        mRenderCategorias();
        await mCargarProductos(true);
    }));
}

// ============== PRODUCTOS (con paginacion infinita) ==============
async function mCargarProductos(reset) {
    if (mState.pagLoading) return;
    mState.pagLoading = true;
    if (reset) {
        mState.pagStart = 0;
        mState.pagHasMore = true;
        mState.productos = [];
    }
    if (!mState.pagHasMore) { mState.pagLoading = false; return; }

    const grid = document.getElementById('m-prods-grid');
    if (reset) grid.innerHTML = mProdSkeleton(4);

    const r = await Http.get('../ajax/producto.php?op=listarPaginado'
        + '&start=' + mState.pagStart
        + '&limit=' + mState.pagLimit
        + '&idcategoria=' + encodeURIComponent(mState.activeCategory || '')
        + '&search=' + encodeURIComponent(mState.searchTerm || ''));

    const items = (r && r.data) || [];
    mState.productos = mState.productos.concat(items);
    mState.pagStart += items.length;
    mState.pagHasMore = !!(r && r.hasMore);

    if (reset) grid.innerHTML = '';
    if (mState.productos.length === 0) {
        grid.innerHTML = '<div class="m-empty" style="grid-column:1/-1;"><i class="fa-solid fa-utensils"></i><h3>Sin productos</h3><p>No hay productos para esta búsqueda</p></div>';
        mState.pagLoading = false;
        return;
    }
    items.forEach(p => grid.insertAdjacentHTML('beforeend', mProdCard(p)));
    mState.pagLoading = false;
}

function mProdCard(p) {
    const tieneVar = Number(p.num_precios) > 1;
    // Stock: igual que en la laptop. Solo se muestra si el producto controla inventario.
    const conStock = Number(p.num_con_stock) > 0;
    const stockTot = Number(p.stock_total);
    const agotado  = conStock && stockTot <= 0;
    let stockBadge = '';
    if (conStock) {
        stockBadge = agotado
            ? '<span style="font-size:9px;background:#fef2f2;color:#dc2626;padding:2px 7px;border-radius:6px;font-weight:700;">AGOTADO</span>'
            : '<span style="font-size:9px;background:#ecfdf5;color:#059669;padding:2px 7px;border-radius:6px;font-weight:700;"><i class="fa-solid fa-box" style="font-size:8px;"></i> Stock: ' + stockTot + '</span>';
    }
    return `
        <div class="m-product-card" onclick="mAgregarProducto(${p.idproducto})">
            <div class="m-product-img" style="background-image:url('${p.imagen || ''}');"></div>
            <div class="m-product-body">
                <div class="m-product-name">${mEsc(p.nombre)}${tieneVar ? ' <span style="font-size:9px;background:#ede9fe;color:#5b3df5;padding:1px 5px;border-radius:5px;font-weight:700;">VAR</span>' : ''}</div>
                ${stockBadge ? `<div style="margin:3px 0 2px;">${stockBadge}</div>` : ''}
                <div class="m-product-row">
                    <span class="m-product-price">${fmt.money(p.precio)}</span>
                    <button class="m-product-add" onclick="event.stopPropagation();mAgregarProducto(${p.idproducto})">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>`;
}

function mProdSkeleton(n) {
    let h = '';
    for (let i = 0; i < n; i++) {
        h += '<div class="m-product-card" style="opacity:0.5;pointer-events:none;"><div class="m-product-img" style="background:#e5e7eb;"></div><div class="m-product-body"><div style="height:13px;width:80%;background:#e5e7eb;border-radius:4px;margin-bottom:6px;"></div><div style="height:13px;width:40%;background:#e5e7eb;border-radius:4px;"></div></div></div>';
    }
    return h;
}

// ============== AGREGAR / EDITAR ITEMS ==============
window.mAgregarProducto = async (idproducto) => {
    if (mState.readonly) { showToast('Solo lectura, no puedes modificar esta orden', 'error'); return; }
    if (!mState.selectedMesa && mState.type === 'dine_in') {
        showToast('Selecciona una mesa primero', 'error');
        mIrATab('mesas');
        return;
    }
    const prod = mState.productos.find(p => Number(p.idproducto) === Number(idproducto));
    // Bloqueo por stock: si TODAS las presentaciones con control están en 0, agotado
    if (prod && Number(prod.num_con_stock) > 0 && Number(prod.stock_total) <= 0) {
        mAvisarAgotado(prod.nombre);
        return;
    }
    if (prod && Number(prod.num_precios) > 1) {
        await mAbrirVariante(prod);
        return;
    }
    await mAgregarConPrecio(idproducto, null);
};

async function mAbrirVariante(prod) {
    const precios = await Http.get('../ajax/producto.php?op=precios&idproducto=' + prod.idproducto);
    if (!precios || precios.length === 0) { await mAgregarConPrecio(prod.idproducto, null); return; }
    if (precios.length === 1) { await mAgregarConPrecio(prod.idproducto, precios[0].idprecio); return; }
    document.getElementById('modal-variante-title').textContent = 'Elige variante de ' + prod.nombre;
    document.getElementById('variante-list').innerHTML = precios.map(p => {
        const agot = Number(p.controla_stock) === 1 && Number(p.stock) <= 0;
        const badge = Number(p.controla_stock) === 1
            ? (agot ? '<span class="badge badge-red" style="font-size:10px;margin-left:6px;">AGOTADO</span>'
                    : '<span class="badge badge-green" style="font-size:10px;margin-left:6px;">Stock: ' + Number(p.stock) + '</span>')
            : '';
        const onclick = agot
            ? `mAvisarAgotado('${(prod.nombre + ' - ' + p.nombre).replace(/'/g, "\\'")}')`
            : `mElegirVariante(${prod.idproducto}, ${p.idprecio})`;
        return `
        <button type="button" class="btn"
                onclick="${onclick}"
                style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;text-align:left;width:100%;${agot ? 'opacity:.55;' : ''}">
            <span><span style="font-weight:600;">${mEsc(p.nombre)}</span>
                ${Number(p.es_default)===1 ? '<span class="badge badge-purple" style="font-size:10px;margin-left:6px;">Default</span>' : ''}
                ${badge}
            </span>
            <span style="font-weight:700;color:var(--m-primary);">${fmt.money(p.precio)}</span>
        </button>`;
    }).join('');
    openModal('modal-variante');
}
window.mElegirVariante = async (idproducto, idprecio) => {
    closeModal('modal-variante');
    await mAgregarConPrecio(idproducto, idprecio);
};

// Candado de creacion: mientras se esta creando la orden, cualquier otro toque espera
// ESA misma creacion en vez de lanzar otra. Sin esto, dos toques seguidos (habitual en
// celular por la latencia) creaban dos ordenes para la misma mesa y repartian los
// productos entre ambas, dejando una en S/ 0.00.
let mCreandoOrden = null;

async function mAsegurarOrden() {
    if (mState.orden && mState.orden.idorden) return mState.orden;
    if (mCreandoOrden) return mCreandoOrden;   // ya hay una creacion en vuelo: reutilizarla

    mCreandoOrden = (async () => {
        const r = await API.ordenCrear({
            idmesa:   mState.selectedMesa ? mState.selectedMesa.idmesa : '',
            tipo:     mState.type,
            mozo:     '',
            idsesion: mState.sesion ? mState.sesion.idsesion : ''
        });
        if (!r || !r.ok) { showToast((r && r.msg) || 'Error al crear orden', 'error'); return null; }
        const o = await API.ordenMostrar(r.idorden);
        if (!o || !o.idorden) { showToast('No se pudo cargar la orden', 'error'); return null; }
        mState.orden = o;
        mState.mesas = await API.mesas();
        mRenderMesas();
        mEmit('orden-creada', { idorden: o.idorden, idmesa: mState.selectedMesa?.idmesa });
        return o;
    })();

    try { return await mCreandoOrden; }
    finally { mCreandoOrden = null; }
}

// Bloquea la grilla de productos mientras se crea la orden (evita el toque impaciente)
function mBloquearProductos(bloquear) {
    const grid = document.getElementById('m-prods-grid');
    if (!grid) return;
    grid.style.pointerEvents = bloquear ? 'none' : '';
    grid.style.opacity       = bloquear ? '0.6' : '';
}

async function mAgregarConPrecio(idproducto, idprecio) {
    let orden = mState.orden;
    if (!orden || !orden.idorden) {
        mBloquearProductos(true);
        try { orden = await mAsegurarOrden(); }
        finally { mBloquearProductos(false); }
        if (!orden) return;
    }

    // Se usa el id capturado, no mState.orden, para que el item no pueda terminar
    // en otra orden si el estado cambia mientras viaja la peticion.
    const idordenDestino = orden.idorden;
    const payload = { idorden: idordenDestino, idproducto, cantidad: 1, nota: '' };
    if (idprecio) payload.idprecio = idprecio;
    const r = await Http.post('../ajax/orden.php?op=agregarItem', payload);
    if (r.ok) {
        mState.orden = await API.ordenMostrar(idordenDestino);
        mRenderTodo();
        mEmit('orden-actualizada', { idorden: idordenDestino, idmesa: mState.selectedMesa?.idmesa });
        showToast('Agregado', 'success');
    } else if (r.agotado) {
        mAvisarAgotado('');
    } else {
        showToast(r.msg || 'No se pudo agregar', 'error');
    }
}

window.mCambiarCantidad = async (iddetalle, delta) => {
    if (mState.readonly) { showToast('Solo lectura', 'error'); return; }
    const item = mState.orden.items.find(i => Number(i.iddetalle) === Number(iddetalle));
    if (!item) return;
    const nueva = Number(item.cantidad) + delta;
    const enviada = Number(item.cantidad_enviada || 0);

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

    if (nueva <= 0) await API.ordenEliminarItem({ iddetalle });
    else {
        const r = await API.ordenActualizarItem({ iddetalle, cantidad: nueva });
        // Rechazo por stock (no exceder inventario): avisar y no cambiar cantidad.
        if (r && r.ok === false && r.msg) showToast(r.msg, 'error');
    }

    mState.orden = await API.ordenMostrar(mState.orden.idorden);
    mRenderTodo();
    mEmit('orden-actualizada', { idorden: mState.orden.idorden, idmesa: mState.selectedMesa?.idmesa });
};

window.mEliminarItem = async (iddetalle) => {
    if (mState.readonly) { showToast('Solo lectura', 'error'); return; }
    const item = mState.orden.items.find(i => Number(i.iddetalle) === Number(iddetalle));
    const enviada = item ? Number(item.cantidad_enviada || 0) : 0;
    if (enviada > 0) {
        const ok = await Swal.fire({
            icon: 'warning',
            title: '¿Anular plato en cocina?',
            html: 'El plato <b>' + item.nombre + '</b> (' + enviada + ' unidad(es)) ya fue enviado a cocina.<br><br>' +
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
    mState.orden = await API.ordenMostrar(mState.orden.idorden);
    mRenderTodo();
    mEmit('orden-actualizada', { idorden: mState.orden.idorden, idmesa: mState.selectedMesa?.idmesa });
};

// Marca / quita CORTESIA en móvil: no se cobra pero descuenta stock.
window.mToggleCortesia = async (iddetalle, cortesia) => {
    if (mState.readonly) { showToast('Solo lectura', 'error'); return; }
    await Http.post('../ajax/orden.php?op=marcarCortesia', { iddetalle, cortesia });
    mState.orden = await API.ordenMostrar(mState.orden.idorden);
    mRenderTodo();
    showToast(Number(cortesia) === 1 ? 'Marcado como cortesía' : 'Cortesía quitada', 'success');
    mEmit('orden-actualizada', { idorden: mState.orden.idorden, idmesa: mState.selectedMesa?.idmesa });
};

// Clic en cortesía (móvil): quita si ya es cortesía / marca 1 unidad directo /
// pregunta cuántas en cortesía si la línea paga tiene varias (cortesía PARCIAL).
window.mMarcarCortesiaUI = async (iddetalle) => {
    if (mState.readonly) { showToast('Solo lectura', 'error'); return; }
    const item = mState.orden && mState.orden.items
        ? mState.orden.items.find(i => Number(i.iddetalle) === Number(iddetalle))
        : null;
    if (!item) return;

    const esCortesia = Number(item.cortesia) === 1;
    const cant = Number(item.cantidad);

    // Quitar cortesía: toggle directo (el backend la fusiona con el pagado).
    if (esCortesia) { await mToggleCortesia(iddetalle, 0); return; }

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

    const r = await API.ordenCortesiaParcial({ iddetalle, cantidad: n });
    if (r && r.ok === false) {
        showToast(r.msg || 'No se pudo aplicar la cortesía', 'error');
        return;
    }
    mState.orden = await API.ordenMostrar(mState.orden.idorden);
    mRenderTodo();
    showToast(n + ' unidad(es) en cortesía', 'success');
    mEmit('orden-actualizada', { idorden: mState.orden.idorden, idmesa: mState.selectedMesa?.idmesa });
};

// ============== RENDER (cabecera, carrito, totales) ==============
function mRenderTodo() {
    mRenderHeader();
    mRenderCarrito();
    mRenderReadonlyBanner();
}

const M_TIPO_LABEL = { dine_in: 'Local', para_llevar: 'Para llevar', delivery: 'Delivery' };

function mRenderHeader() {
    const sub = document.getElementById('m-header-sub');
    const tot = document.getElementById('m-header-total');
    if (mState.selectedMesa) {
        sub.textContent = 'Mesa ' + mState.selectedMesa.numero + (mState.orden ? ' · #' + mState.orden.numero : '');
    } else if (mState.type && mState.type !== 'dine_in') {
        sub.textContent = (M_TIPO_LABEL[mState.type] || mState.type) + (mState.orden ? ' · #' + mState.orden.numero : '');
    } else {
        sub.textContent = 'Sin mesa';
    }
    const totVal = mState.orden ? mState.orden.total : 0;
    tot.textContent = fmt.money(totVal);
    document.getElementById('m-pay-total').textContent = fmt.money(totVal);
    document.getElementById('m-pay-orden').textContent = mState.orden ? ('#' + mState.orden.numero) : '—';

    // Info banner sobre productos
    const infoEl = document.getElementById('m-mesa-info-prod');
    if (mState.selectedMesa) {
        infoEl.innerHTML = '<div class="m-mesa-banner"><i class="fa-solid fa-chair"></i> Mesa ' + mState.selectedMesa.numero + ' <span class="badge">' + mState.selectedMesa.capacidad + ' personas</span></div>';
    } else if (mState.type && mState.type !== 'dine_in') {
        const ic = mState.type === 'delivery' ? 'fa-motorcycle' : 'fa-bag-shopping';
        infoEl.innerHTML = '<div class="m-mesa-banner"><i class="fa-solid ' + ic + '"></i> ' + (M_TIPO_LABEL[mState.type] || mState.type) + '</div>';
    } else {
        infoEl.innerHTML = '';
    }
}

// Cambiar tipo de orden en móvil (Local / Para llevar / Delivery)
window.mSetTipo = (tipo) => {
    mState.type = tipo;
    // Cambiar de tipo inicia un contexto de orden nuevo
    mState.selectedMesa = null;
    mState.orden = null;
    mState.readonly = false;
    document.querySelectorAll('.m-tipo-btn').forEach(b => b.classList.toggle('active', b.dataset.tipo === tipo));
    const mesasWrap = document.getElementById('m-mesas-wrap');
    if (mesasWrap) mesasWrap.style.display = (tipo === 'dine_in') ? '' : 'none';
    mRenderHeader();
    mRenderTodo();
    if (tipo === 'dine_in') {
        mRenderMesas();
    } else {
        // Para llevar / Delivery: no requiere mesa, ir directo a productos
        mIrATab('productos');
    }
};

// Aviso de producto agotado (no se puede agregar)
function mAvisarAgotado(nombre) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'warning', title: 'Producto agotado',
            html: 'No hay stock de:<br><b>' + mEsc(nombre || '') + '</b>',
            confirmButtonColor: '#ef4444' });
    } else {
        showToast('Producto agotado', 'error');
    }
}

// Coloca cada fila de CORTESÍA justo debajo de su producto pagado equivalente.
function mAgruparCortesias(items) {
    const key = (i) => [i.idproducto, (i.idprecio ?? ''), i.nombre, (i.nota || '')].join('|');
    const cortesias = items.filter(i => Number(i.cortesia) === 1);
    const usadas = new Set();
    const result = [];
    items.forEach(it => {
        if (Number(it.cortesia) === 1) return;
        result.push(it);
        cortesias.forEach(c => {
            if (usadas.has(c.iddetalle)) return;
            if (key(c) === key(it)) { result.push(c); usadas.add(c.iddetalle); }
        });
    });
    cortesias.forEach(c => { if (!usadas.has(c.iddetalle)) result.push(c); });
    return result;
}

function mRenderCarrito() {
    const list = document.getElementById('m-cart-list');
    const visibles = (mState.orden && mState.orden.items)
        ? mAgruparCortesias(mState.orden.items.filter(i => Number(i.cantidad) > 0))
        : [];

    if (!mState.orden || visibles.length === 0) {
        list.innerHTML = '<div class="m-empty"><i class="fa-solid fa-cart-shopping"></i><h3>Carrito vacío</h3><p>Agrega productos desde la pestaña Productos</p></div>';
        document.getElementById('m-subtotal').textContent = fmt.money(mState.orden ? mState.orden.subtotal : 0);
        document.getElementById('m-igv').textContent      = fmt.money(mState.orden ? mState.orden.igv      : 0);
        document.getElementById('m-total').textContent    = fmt.money(mState.orden ? mState.orden.total    : 0);
        document.getElementById('m-cart-badge').style.display = 'none';
        document.getElementById('m-cart-title').textContent = 'Tu pedido';
        document.getElementById('m-cart-subtitle').textContent = '—';
        return;
    }
    list.innerHTML = visibles.map(i => {
        const esCortesia = Number(i.cortesia) === 1;
        const original = Number(i.precio) * Number(i.cantidad);
        const precioHtml = esCortesia
            ? `<span style="text-decoration:line-through;color:#9ca3af;font-size:11px;">${fmt.money(original)}</span>
               <span style="display:block;color:#16a34a;font-weight:800;font-size:11px;">CORTESÍA</span>`
            : `<span class="m-cart-price">${fmt.money(i.subtotal)}</span>`;
        return `
        <div class="m-cart-item"${esCortesia ? ' style="background:#f0fdf4;"' : ''}>
            <div class="m-qty-control">
                <button class="m-qty-btn" onclick="mCambiarCantidad(${i.iddetalle}, -1)">−</button>
                <span class="m-qty-num">${Number(i.cantidad)}</span>
                <button class="m-qty-btn" onclick="mCambiarCantidad(${i.iddetalle}, 1)">+</button>
            </div>
            <div class="m-cart-info">
                <div class="m-cart-name">${mEsc(i.nombre)} ${esCortesia ? '<i class="fa-solid fa-gift" style="color:#16a34a;"></i>' : ''}</div>
                ${i.nota ? `<div class="m-cart-nota">${mEsc(i.nota)}</div>` : ''}
            </div>
            <div class="m-cart-actions">
                ${precioHtml}
                <div style="display:flex;gap:4px;">
                    <button class="m-cart-remove" title="${esCortesia ? 'Quitar cortesía' : 'Cortesía'}" onclick="mMarcarCortesiaUI(${i.iddetalle})" style="color:${esCortesia ? '#16a34a' : '#9ca3af'};"><i class="fa-solid fa-gift"></i></button>
                    <button class="m-cart-remove" onclick="mEliminarItem(${i.iddetalle})"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </div>`;
    }).join('');

    document.getElementById('m-subtotal').textContent = fmt.money(mState.orden.subtotal);
    document.getElementById('m-igv').textContent      = fmt.money(mState.orden.igv);
    document.getElementById('m-total').textContent    = fmt.money(mState.orden.total);
    document.getElementById('m-cart-title').textContent = 'Orden #' + mState.orden.numero;
    document.getElementById('m-cart-subtitle').textContent = visibles.length + ' producto(s)';

    const badge = document.getElementById('m-cart-badge');
    badge.textContent = visibles.length;
    badge.style.display = 'flex';
}

function mRenderReadonlyBanner() {
    const wrap = document.getElementById('m-readonly-banner-wrap');
    if (mState.readonly && mState.orden) {
        const nombre = mState.orden.propietario_nombre || mState.orden.mozo || 'otro mozo';
        wrap.innerHTML = '<div class="m-readonly-banner"><i class="fa-solid fa-lock"></i> Solo lectura · Atendida por <b>' + mEsc(nombre) + '</b></div>';
    } else {
        wrap.innerHTML = '';
    }
    // Deshabilitar botones de accion en readonly
    const cocina = document.getElementById('m-btn-cocina');
    if (cocina) cocina.disabled = !!mState.readonly;
}

// ============== ENVIAR A COCINA ==============
window.mEnviarCocina = async () => {
    if (mState.readonly) { showToast('Solo lectura, no puedes enviar', 'error'); return; }
    if (!mState.orden) { showToast('No hay orden activa', 'error'); return; }

    const r = await API.ordenEnviarCocina(mState.orden.idorden);
    if (!r || !r.ok) { showToast((r && r.msg) || 'Error', 'error'); return; }

    const adic = (r.adiciones && typeof r.adiciones === 'object') ? r.adiciones : {};
    const anul = (r.anulaciones && typeof r.anulaciones === 'object') ? r.anulaciones : {};
    const hayAdic = Object.keys(adic).length > 0;
    const hayAnul = Object.keys(anul).length > 0;

    if (!hayAdic && !hayAnul) { showToast('No hay cambios para enviar', ''); return; }

    if (hayAdic) mAbrirComanda(mState.orden.idorden, adic, 'adicion');
    if (hayAnul) setTimeout(() => mAbrirComanda(mState.orden.idorden, anul, 'anular'), hayAdic ? 400 : 0);

    const sumAdic = Object.values(adic).reduce((a, b) => a + Number(b), 0);
    const sumAnul = Object.values(anul).reduce((a, b) => a + Number(b), 0);
    let msg = [];
    if (sumAdic > 0) msg.push('+' + sumAdic + ' a preparar');
    if (sumAnul > 0) msg.push('−' + sumAnul + ' anuladas');
    showToast(msg.join(' · '), 'success');

    mState.orden = await API.ordenMostrar(mState.orden.idorden);
    mRenderTodo();
    mEmit('orden-enviada-cocina', { idorden: mState.orden.idorden, idmesa: mState.selectedMesa?.idmesa });
};

function mAbrirComanda(idorden, mapa, tipo) {
    const itemsParam = Object.keys(mapa).map(id => id + ':' + mapa[id]).join(',');
    if (!itemsParam) return;
    const url = 'comanda?id=' + idorden
              + '&type=' + (tipo || 'adicion')
              + '&items=' + encodeURIComponent(itemsParam)
              + '&_=' + Date.now();
    window.open(url, '_blank', 'width=420,height=720');
}

// ============== OBSERVACION ==============
window.mGuardarObservacion = async () => {
    if (mState.readonly) { showToast('Solo lectura', 'error'); return; }
    if (!mState.orden) { showToast('No hay orden activa', 'error'); return; }
    const obs = document.getElementById('m-observation').value.trim();
    await API.ordenActualizarCab({ idorden: mState.orden.idorden, tipo: mState.type, observacion: obs, idcliente: '' });
    showToast('Observación guardada', 'success');
};

// Pregunta si desea imprimir y, según la CONFIGURACIÓN (permisos del usuario),
// qué comprobante emitir. Devuelve:
//   { imprimir:bool, tipo:'nota_venta'|'boleta'|'factura', clienteData:null|{...} }
// o null si el usuario cancela (en ese caso NO se cobra).
async function mDecidirImpresion() {
    // Sin Swal: mantener comportamiento previo (imprime nota de venta).
    if (typeof Swal === 'undefined') return { imprimir: true, tipo: 'nota_venta', clienteData: null };

    // 1) ¿Desea imprimir?
    const q = await Swal.fire({
        title: '¿Desea imprimir?',
        text: 'Comprobante de esta venta',
        icon: 'question',
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'Sí, imprimir',
        denyButtonText: 'No imprimir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#10b981',
        denyButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (q.isDismissed) return null;                       // canceló: no cobra
    if (q.isDenied) return { imprimir: false, tipo: 'nota_venta', clienteData: null };

    // 2) Tipo según configuración (permisos). Nota de venta siempre disponible.
    const opts = { nota_venta: 'Nota de venta' };
    if (can('emitir_boleta'))  opts.boleta  = 'Boleta (SUNAT)';
    if (can('emitir_factura')) opts.factura = 'Factura (SUNAT)';

    let tipo = 'nota_venta';
    if (Object.keys(opts).length > 1) {
        const sel = await Swal.fire({
            title: 'Tipo de comprobante',
            input: 'radio',
            inputOptions: opts,
            inputValue: 'nota_venta',
            showCancelButton: true,
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#10b981',
            inputValidator: (v) => (!v ? 'Elige un tipo' : undefined),
        });
        if (!sel.isConfirmed) return null;
        tipo = sel.value;
    }

    if (tipo === 'nota_venta') return { imprimir: true, tipo, clienteData: null };

    // 3) Boleta/Factura: validar serie y pedir datos del cliente.
    const tipoSunat = tipo === 'factura' ? '01' : '03';
    if (typeof validarSerieComprobante === 'function') {
        const okSerie = await validarSerieComprobante(tipoSunat, tipo);
        if (!okSerie) return null;
    }
    const cli = await mPedirDatosCliente(tipo);
    if (!cli) return null;
    return { imprimir: true, tipo, clienteData: cli };
}

// Formulario de datos del receptor para boleta/factura (móvil).
async function mPedirDatosCliente(tipo) {
    const esFactura = tipo === 'factura';
    const r = await Swal.fire({
        title: esFactura ? 'Datos para Factura' : 'Datos para Boleta',
        html:
            '<div style="text-align:left;font-size:13px;">'
          + '<label style="font-weight:700;color:#6b7280;font-size:12px;">' + (esFactura ? 'RUC (11 dígitos)' : 'Nº Documento (DNI/RUC)') + '</label>'
          + '<input id="m-cli-doc" class="swal2-input" style="margin:6px 0;width:100%;" maxlength="15" placeholder="' + (esFactura ? 'RUC' : 'Documento') + '">'
          + '<label style="font-weight:700;color:#6b7280;font-size:12px;">' + (esFactura ? 'Razón social' : 'Nombre / Razón social') + '</label>'
          + '<input id="m-cli-razon" class="swal2-input" style="margin:6px 0;width:100%;" placeholder="Nombre o razón social">'
          + '<label style="font-weight:700;color:#6b7280;font-size:12px;">Dirección (opcional)</label>'
          + '<input id="m-cli-dir" class="swal2-input" style="margin:6px 0;width:100%;" placeholder="Dirección">'
          + '<label style="font-weight:700;color:#6b7280;font-size:12px;">Email (opcional)</label>'
          + '<input id="m-cli-email" type="email" class="swal2-input" style="margin:6px 0;width:100%;" placeholder="correo@ejemplo.com">'
          + '</div>',
        showCancelButton: true,
        confirmButtonText: 'Continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#10b981',
        focusConfirm: false,
        preConfirm: () => {
            const doc   = (document.getElementById('m-cli-doc').value   || '').trim();
            const razon = (document.getElementById('m-cli-razon').value || '').trim();
            const dir   = (document.getElementById('m-cli-dir').value   || '').trim();
            const email = (document.getElementById('m-cli-email').value || '').trim();
            if (!doc || !razon) { Swal.showValidationMessage('Documento y nombre son obligatorios'); return false; }
            if (esFactura && doc.length !== 11) { Swal.showValidationMessage('La factura requiere RUC de 11 dígitos'); return false; }
            // 6 = RUC, 1 = DNI (mismos códigos que el desktop).
            const tipo_doc = esFactura ? '6' : (doc.length === 11 ? '6' : '1');
            return { tipo_doc, num_doc: doc, razon, direccion: dir, email };
        }
    });
    if (!r.isConfirmed) return null;
    return r.value;
}

// ============== COBRO MOVIL (simplificado: ticket + 4 metodos) ==============
window.mAbrirCobro = async () => {
    if (!can('cobrar')) { showToast('No tienes permiso para cobrar', 'error'); return; }
    if (mState.readonly) { showToast('Solo lectura', 'error'); return; }
    if (!mState.orden) { showToast('No hay orden activa', 'error'); return; }
    if (!mState.sesion) { showToast('No hay caja abierta', 'error'); return; }
    if (!mState.orden.items || mState.orden.items.filter(i => Number(i.cantidad)>0).length === 0) {
        showToast('La orden está vacía', 'error'); return;
    }

    const totalNum = Number(mState.orden.total) || 0;
    const metodo = mState.paymentMethod;
    const labels = {
        efectivo: 'Efectivo',
        tarjeta:  'Tarjeta',
        yape:     'Yape',
        plin:     'Plin',
        transferencia: 'Transferencia'
    };

    let extraHtml = '';
    if (metodo === 'efectivo') {
        extraHtml = '<div style="margin-top:14px;text-align:left;">'
                  + '<label style="font-size:12px;font-weight:700;color:#6b7280;">MONTO RECIBIDO</label>'
                  + '<input id="m-cobro-recibido" type="number" step="0.10" min="0" value="' + totalNum.toFixed(2) + '" '
                  + 'style="width:100%;padding:14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:18px;font-weight:700;margin-top:6px;">'
                  + '<div id="m-cobro-vuelto" style="margin-top:8px;font-size:13px;color:#10b981;font-weight:700;text-align:right;"></div>'
                  + '</div>';
    } else if (metodo === 'yape' || metodo === 'plin') {
        // Mostrar el QR para que el cliente lo escanee + monto pagado
        const cfg = window.YAPEZ_CONFIG || {};
        const qrRuta = (metodo === 'plin') ? (cfg.plin_qr || cfg.yape_qr) : cfg.yape_qr;
        let qrHtml;
        if (qrRuta) {
            qrHtml = '<div style="font-size:12px;color:#6b7280;font-weight:700;margin-bottom:6px;">Escanea con ' + (metodo === 'plin' ? 'Plin' : 'Yape') + '</div>'
                   + '<img src="../' + qrRuta + '" alt="QR" style="max-width:180px;max-height:180px;border:1px solid #e5e7eb;border-radius:12px;padding:6px;background:#fff;">';
        } else {
            qrHtml = '<div style="font-size:12px;color:#92400e;background:#fef3c7;border-radius:10px;padding:12px;">No has configurado el QR. Súbelo en <b>Empresa</b>.</div>';
        }
        extraHtml = '<div style="margin-top:14px;text-align:center;">' + qrHtml + '</div>'
                  + '<div style="margin-top:14px;text-align:left;">'
                  + '<label style="font-size:12px;font-weight:700;color:#6b7280;">MONTO PAGADO EN ' + (metodo === 'plin' ? 'PLIN' : 'YAPE') + '</label>'
                  + '<input id="m-cobro-recibido" type="number" step="0.10" min="0" value="' + totalNum.toFixed(2) + '" '
                  + 'style="width:100%;padding:14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:18px;font-weight:700;margin-top:6px;">'
                  + '<label style="font-size:12px;font-weight:700;color:#6b7280;margin-top:10px;display:block;">NRO. OPERACIÓN / CELULAR (opcional)</label>'
                  + '<input id="m-cobro-ref" type="text" placeholder="Opcional" '
                  + 'style="width:100%;padding:14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;margin-top:6px;">'
                  + '</div>';
    } else if (metodo === 'transferencia') {
        extraHtml = '<div style="margin-top:14px;text-align:left;">'
                  + '<label style="font-size:12px;font-weight:700;color:#6b7280;">NRO. DE OPERACIÓN</label>'
                  + '<input id="m-cobro-ref" type="text" placeholder="Opcional" '
                  + 'style="width:100%;padding:14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;margin-top:6px;">'
                  + '</div>';
    } else if (metodo === 'tarjeta') {
        extraHtml = '<div style="margin-top:14px;text-align:left;">'
                  + '<label style="font-size:12px;font-weight:700;color:#6b7280;">NRO. VOUCHER</label>'
                  + '<input id="m-cobro-ref" type="text" placeholder="Ej: 12345" '
                  + 'style="width:100%;padding:14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;margin-top:6px;">'
                  + '</div>';
    } else if (metodo === 'mixto') {
        // Dividir el total entre varios medios (efectivo/yape/plin/transferencia/tarjeta)
        const mkRow = (id, lbl) =>
            '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">'
          + '<span style="flex:1;font-size:13px;font-weight:600;color:#374151;">' + lbl + '</span>'
          + '<span style="font-size:13px;color:#6b7280;">S/</span>'
          + '<input id="' + id + '" type="number" step="0.10" min="0" value="0" '
          + 'class="m-mix-inp" style="width:110px;padding:10px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:15px;font-weight:700;text-align:right;">'
          + '</div>';
        extraHtml = '<div style="margin-top:14px;text-align:left;">'
                  + '<div style="font-size:11px;color:#6b7280;font-weight:700;margin-bottom:8px;">DIVIDE EL TOTAL ENTRE LOS MEDIOS</div>'
                  + mkRow('m-mix-efectivo', 'Efectivo')
                  + mkRow('m-mix-yape', 'Yape')
                  + mkRow('m-mix-plin', 'Plin')
                  + mkRow('m-mix-transferencia', 'Transferencia')
                  + mkRow('m-mix-tarjeta', 'Tarjeta')
                  + '<div id="m-mix-resumen" style="margin-top:6px;padding:10px;border-radius:10px;background:#f3f4f6;font-size:13px;font-weight:700;text-align:center;"></div>'
                  + '<div id="m-mix-qr" style="margin-top:10px;"></div>'
                  + '</div>';
    }

    const result = await Swal.fire({
        title: 'Confirmar cobro',
        html: '<div style="font-size:11px;color:#6b7280;letter-spacing:0.5px;">ORDEN #' + mState.orden.numero + '</div>'
            + '<div style="font-size:32px;font-weight:800;color:#5b3df5;margin-top:4px;">' + fmt.money(totalNum) + '</div>'
            + '<div style="margin-top:6px;font-size:13px;font-weight:600;color:#1f2937;">' + (labels[metodo] || metodo) + '</div>'
            + extraHtml,
        showCancelButton: true,
        confirmButtonText: 'Cobrar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        didOpen: () => {
            const rec = document.getElementById('m-cobro-recibido');
            if (rec) {
                const upd = () => {
                    const v = parseFloat(rec.value) || 0;
                    const vuelto = Math.max(0, v - totalNum);
                    document.getElementById('m-cobro-vuelto').textContent = 'Vuelto: ' + fmt.money(vuelto);
                };
                rec.addEventListener('input', upd);
                upd();
            }
            // Pago mixto: recalcular asignado/falta en vivo
            const mixInputs = document.querySelectorAll('.m-mix-inp');
            if (mixInputs.length) {
                const cfg = window.YAPEZ_CONFIG || {};
                const renderQR = () => {
                    const qrEl = document.getElementById('m-mix-qr');
                    if (!qrEl) return;
                    const yMonto = parseFloat(document.getElementById('m-mix-yape').value) || 0;
                    const pMonto = parseFloat(document.getElementById('m-mix-plin').value) || 0;
                    const box = (ruta, lbl) => ruta
                        ? '<div style="text-align:center;margin-top:8px;">'
                          + '<div style="font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;">' + lbl + '</div>'
                          + '<img src="../' + ruta + '" alt="QR" style="max-width:150px;max-height:150px;border:1px solid #e5e7eb;border-radius:10px;padding:5px;background:#fff;">'
                          + '</div>'
                        : '<div style="text-align:center;margin-top:8px;font-size:11px;color:#92400e;background:#fef3c7;border-radius:8px;padding:8px;">No hay QR de ' + lbl + ' configurado.</div>';
                    let html = '';
                    if (yMonto > 0) html += box(cfg.yape_qr, 'Escanea con Yape');
                    if (pMonto > 0) html += box(cfg.plin_qr || cfg.yape_qr, 'Escanea con Plin');
                    qrEl.innerHTML = html;
                };
                const updMix = () => {
                    let suma = 0;
                    mixInputs.forEach(i => suma += parseFloat(i.value) || 0);
                    suma = +suma.toFixed(2);
                    const falta = +(totalNum - suma).toFixed(2);
                    const el = document.getElementById('m-mix-resumen');
                    if (Math.abs(falta) < 0.005) {
                        el.style.background = '#dcfce7'; el.style.color = '#15803d';
                        el.textContent = 'Asignado S/ ' + suma.toFixed(2) + '  ✓ Cuadra';
                    } else if (falta > 0) {
                        el.style.background = '#fef3c7'; el.style.color = '#92400e';
                        el.textContent = 'Asignado S/ ' + suma.toFixed(2) + '  ·  Falta S/ ' + falta.toFixed(2);
                    } else {
                        el.style.background = '#fee2e2'; el.style.color = '#b91c1c';
                        el.textContent = 'Asignado S/ ' + suma.toFixed(2) + '  ·  Sobra S/ ' + Math.abs(falta).toFixed(2);
                    }
                    renderQR();
                };
                mixInputs.forEach(i => {
                    // Al enfocar: si está en 0, se limpia para escribir directo
                    i.addEventListener('focus', () => { if ((parseFloat(i.value) || 0) === 0) i.value = ''; });
                    // Al salir: si quedó vacío, vuelve a 0
                    i.addEventListener('blur', () => { if (i.value.trim() === '' || isNaN(parseFloat(i.value))) i.value = '0'; updMix(); });
                    i.addEventListener('input', updMix);
                });
                updMix();
            }
        },
        preConfirm: () => {
            let recibido = totalNum;
            let referencia = '';
            let metadata = {};
            let metodoEnviar = metodo;
            if (metodo === 'efectivo') {
                recibido = parseFloat(document.getElementById('m-cobro-recibido').value) || 0;
                if (recibido < totalNum) { Swal.showValidationMessage('El monto recibido es menor al total'); return false; }
            } else if (metodo === 'tarjeta') {
                const v = document.getElementById('m-cobro-ref').value.trim();
                if (!v) { Swal.showValidationMessage('Ingresa el número de voucher'); return false; }
                referencia = 'Voucher ' + v;
            } else if (metodo === 'yape' || metodo === 'plin') {
                const monto = parseFloat(document.getElementById('m-cobro-recibido').value) || 0;
                if (monto < totalNum) { Swal.showValidationMessage('El monto pagado es menor al total'); return false; }
                recibido = monto;
                const v = document.getElementById('m-cobro-ref').value.trim();
                referencia = (metodo === 'plin' ? 'Plin' : 'Yape') + ' S/ ' + monto.toFixed(2) + (v ? ' · ' + v : '');
            } else if (metodo === 'transferencia') {
                const v = document.getElementById('m-cobro-ref').value.trim();
                referencia = 'Trans. ' + (v || 'sin ref');
            } else if (metodo === 'mixto') {
                const get = id => parseFloat(document.getElementById(id).value) || 0;
                const partes = [
                    { metodo: 'efectivo',      monto: get('m-mix-efectivo') },
                    { metodo: 'yape',          monto: get('m-mix-yape') },
                    { metodo: 'plin',          monto: get('m-mix-plin') },
                    { metodo: 'transferencia', monto: get('m-mix-transferencia') },
                    { metodo: 'tarjeta',       monto: get('m-mix-tarjeta') },
                ].filter(p => p.monto > 0).map(p => ({ metodo: p.metodo, monto: +p.monto.toFixed(2) }));
                if (partes.length < 1) { Swal.showValidationMessage('Ingresa al menos un monto'); return false; }
                const suma = +partes.reduce((a, p) => a + p.monto, 0).toFixed(2);
                if (Math.abs(suma - totalNum) > 0.01) {
                    Swal.showValidationMessage('La suma (S/ ' + suma.toFixed(2) + ') debe ser igual al total (S/ ' + totalNum.toFixed(2) + ')');
                    return false;
                }
                recibido = totalNum;
                metadata = { partes };
                metodoEnviar = 'mixto';
                const lbl = { efectivo: 'Efectivo', yape: 'Yape', plin: 'Plin', transferencia: 'Transf.', tarjeta: 'Tarjeta' };
                referencia = partes.map(p => lbl[p.metodo] + ' S/ ' + p.monto.toFixed(2)).join(' + ');
            }
            return { recibido, referencia, metadata, metodoEnviar };
        }
    });

    if (!result.isConfirmed || !result.value) return;

    const { recibido, referencia, metadata, metodoEnviar } = result.value;

    // ¿Imprimir? y qué comprobante (según configuración). Si cancela: NO se cobra.
    const imp = await mDecidirImpresion();
    if (!imp) return;

    swalLoading('Procesando cobro...');

    const r = await API.ordenCobrar({
        idorden:          mState.orden.idorden,
        metodo_pago:      metodoEnviar || metodo,
        idsesion:         mState.sesion.idsesion,
        tipo_comprobante: imp.tipo,
        monto_recibido:   recibido,
        pago_referencia:  referencia,
        pago_metadata:    JSON.stringify(metadata || {}),
        // Solo la NOTA DE VENTA se imprime al cobrar (bridge térmica, como antes).
        // Boleta/factura se imprimen DESPUÉS de generar el comprobante electrónico
        // (para que salga con número fiscal). "No imprimir" => 0.
        imprimir_comprobante: (imp.imprimir && imp.tipo === 'nota_venta') ? 1 : 0
    });

    if (!r.ok) { Swal.close(); showToast(r.msg || 'Error al cobrar', 'error'); return; }

    const numMesa = mState.selectedMesa ? mState.selectedMesa.numero : null;
    let msgFinal = 'Orden cobrada';

    // Boleta/Factura: generar comprobante electrónico (cola SUNAT) y luego imprimir.
    if (imp.clienteData) {
        const tipoSunat = imp.tipo === 'factura' ? '01' : '03';
        const ce = await Http.post('../ajax/comprobante_electronico.php?op=crearDesdeOrden', {
            idorden:           mState.orden.idorden,
            tipo_documento:    tipoSunat,
            cliente_tipo_doc:  imp.clienteData.tipo_doc,
            cliente_num_doc:   imp.clienteData.num_doc,
            cliente_razon:     imp.clienteData.razon,
            cliente_direccion: imp.clienteData.direccion,
            cliente_email:     imp.clienteData.email,
        });
        if (ce && ce.ok) {
            const ea = ce.envio_auto;
            msgFinal = (ea && ea.intentado && ea.ok)
                ? ('Cobrado · Comp. #' + ce.idcomprobante + ' aceptado SUNAT')
                : ('Cobrado · Comp. #' + ce.idcomprobante + ' en cola SUNAT');
            if (imp.imprimir) {
                await API.ordenImprimirComprobante({ idorden: mState.orden.idorden });
            }
        } else {
            msgFinal = 'Cobrado, pero el comprobante electrónico falló: ' + ((ce && ce.msg) || 'revisar');
        }
    }

    Swal.close();

    mEmit('orden-cobrada', { idorden: mState.orden.idorden, idmesa: mState.selectedMesa?.idmesa });

    // En móvil NO se abre el navegador: el comprobante se imprime por el BRIDGE.

    // Reset → vuelve a la pantalla de mesas para un nuevo pedido.
    mState.orden = null;
    mState.selectedMesa = null;
    mState.readonly = false;
    document.getElementById('m-observation').value = '';
    mState.mesas = await API.mesas();
    mRenderMesas();
    mRenderTodo();

    // Refrescar productos para que el stock descontado en el cobro se vea al
    // instante (sin recargar la página), igual que en la versión de escritorio.
    await mCargarProductos(true);

    showToast(numMesa ? ('Mesa ' + numMesa + ' liberada · ' + msgFinal) : msgFinal, 'success');
    mIrATab('mesas');
};

// ============== UTIL ==============
function mEsc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
}

// ============== BOOTSTRAP ==============
mInit();

// Tiempo real: si otro dispositivo cambia mesas o agrega a la orden abierta,
// refrescar automáticamente. No interrumpe si hay un diálogo (cobro) abierto.
async function mRefrescarTiempoReal() {
    if (typeof Swal !== 'undefined' && Swal.isVisible && Swal.isVisible()) return;
    try {
        mState.mesas = await API.mesas();
        mRenderMesas();
        if (mState.orden && mState.orden.idorden) {
            const fresca = await API.ordenMostrar(mState.orden.idorden);
            if (fresca && fresca.idorden) { mState.orden = fresca; mRenderTodo(); }
        }
    } catch (e) { /* reintentar en el próximo tick */ }
}
if (window.Realtime) Realtime.start(mRefrescarTiempoReal, 4000);
