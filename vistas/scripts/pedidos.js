/* pedidos.js — listar pedidos activos + acciones según permisos */

let activeFilter = 'all';
let searchTerm = '';
let ordenesCache = [];
let cobroState = { tipoComprobante: 'nota_venta', metodoPago: 'efectivo', orden: null, sesion: null };

const TYPE_LABELS   = { dine_in: 'Local', para_llevar: 'Para Llevar', delivery: 'Delivery' };
const TYPE_ICONS    = { dine_in: 'fa-utensils', para_llevar: 'fa-bag-shopping', delivery: 'fa-motorcycle' };
const STATUS_LABELS = { en_curso: 'En curso', enviada: 'Enviada', pagada: 'Pagada', anulada: 'Anulada' };
const STATUS_BADGE  = { en_curso: 'badge-orange', enviada: 'badge-blue', pagada: 'badge-green', anulada: 'badge-gray' };

async function load() {
    const all = await API.ordenes();
    ordenesCache = (all || []).filter(o => o.estado === 'en_curso' || o.estado === 'enviada');
    cobroState.sesion = await API.cajaSesion();
    render();
}

function render() {
    document.getElementById('c-all').textContent      = ordenesCache.length;
    document.getElementById('c-en_curso').textContent = ordenesCache.filter(o => o.estado === 'en_curso').length;
    document.getElementById('c-enviada').textContent  = ordenesCache.filter(o => o.estado === 'enviada').length;

    let list = ordenesCache;
    if (activeFilter === 'en_curso') list = list.filter(o => o.estado === 'en_curso');
    else if (activeFilter === 'enviada') list = list.filter(o => o.estado === 'enviada');
    else if (activeFilter === 'dine_in_only') list = list.filter(o => o.tipo === 'dine_in');
    else if (activeFilter === 'para_llevar_only') list = list.filter(o => o.tipo === 'para_llevar');
    else if (activeFilter === 'delivery_only') list = list.filter(o => o.tipo === 'delivery');
    if (searchTerm) list = list.filter(o => String(o.numero).includes(searchTerm));

    const grid = document.getElementById('orders-grid');
    if (list.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1;" class="empty-state"><i class="fa-solid fa-receipt"></i><h3>Sin pedidos activos</h3><p>Cuando crees una orden aparecerá aquí</p></div>';
        return;
    }

    const puedeCobrar = can('cobrar');
    const puedeAnular = can('anular_orden');

    grid.innerHTML = list.map(o => `
        <div class="order-card">
            <div class="order-card-header">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="order-type-icon type-${o.tipo}"><i class="fa-solid ${TYPE_ICONS[o.tipo]}"></i></div>
                    <div>
                        <div class="order-card-id">Orden #${o.numero}</div>
                        <div class="order-card-meta">${TYPE_LABELS[o.tipo]}${o.mesa_numero ? ' · Mesa ' + o.mesa_numero : ''} · ${fmt.time(o.fecha)}</div>
                    </div>
                </div>
                <span class="badge ${STATUS_BADGE[o.estado]}">${STATUS_LABELS[o.estado]}</span>
            </div>
            <div class="order-card-body" id="body-${o.idorden}">
                <div style="font-size:11px;color:var(--text-muted);">Cargando ítems...</div>
            </div>
            <div class="order-card-footer">
                <span style="font-size:12px;color:var(--text-muted);font-weight:600;">TOTAL</span>
                <span class="order-card-total">${fmt.money(o.total)}</span>
            </div>
            <div class="order-card-actions">
                ${puedeCobrar
                    ? `<button class="btn btn-sm" onclick="iniciarCobro(${o.idorden})" style="flex:1;"><i class="fa-solid fa-check"></i> Cobrar</button>`
                    : `<button class="btn btn-sm" onclick="window.location.href='nuevaorden${o.idmesa ? '?mesa=' + o.idmesa : ''}'" style="flex:1;"><i class="fa-solid fa-eye"></i> Abrir</button>`
                }
                <button class="btn btn-sm btn-icon" onclick="verDetalle(${o.idorden})"><i class="fa-solid fa-eye"></i></button>
                ${puedeAnular ? `<button class="btn btn-sm btn-icon" onclick="anularOrden(${o.idorden})"><i class="fa-solid fa-xmark"></i></button>` : ''}
            </div>
        </div>
    `).join('');

    list.forEach(async o => {
        const full = await API.ordenMostrar(o.idorden);
        const target = document.getElementById('body-' + o.idorden);
        if (!target || !full || !full.items) return;
        const items = full.items.slice(0, 4);
        target.innerHTML = items.map(i => `
            <div class="order-card-item">
                <span>${Number(i.cantidad)}× ${i.nombre}</span>
                <span style="font-weight:600;">${fmt.money(i.subtotal)}</span>
            </div>
        `).join('') + (full.items.length > 4
            ? `<div style="font-size:11px;color:var(--text-muted);margin-top:6px;">+ ${full.items.length - 4} productos más</div>`
            : '');
    });
}

// ---- Cobro: abre modal y reusa la logica del modal_cobro.php ----
window.iniciarCobro = async (id) => {
    if (!can('cobrar')) { showToast('No tienes permiso para cobrar', 'error'); return; }
    if (!cobroState.sesion || !cobroState.sesion.idsesion) {
        showToast('No hay caja abierta', 'error');
        return;
    }
    cobroState.orden = await API.ordenMostrar(id);
    if (!cobroState.orden) return;

    document.getElementById('cobro-numero').textContent = '#' + cobroState.orden.numero;
    document.getElementById('cobro-mesa').textContent   = cobroState.orden.mesa_numero ? 'Mesa ' + cobroState.orden.mesa_numero : 'Sin mesa';
    document.getElementById('cobro-total').textContent  = fmt.money(cobroState.orden.total);

    cobroState.tipoComprobante = 'nota_venta';
    cobroState.metodoPago      = 'efectivo';
    document.getElementById('cobro-recibido').value     = Number(cobroState.orden.total).toFixed(2);
    document.getElementById('cobro-vuelto').textContent = fmt.money(0);
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
    const sel = document.getElementById('cli-tipo-doc');
    if (sel) sel.innerHTML = TIPOS_DOC_CLI.map(t => `<option value="${t.v}">${t.t}</option>`).join('');

    sincronizarTipoComp(); sincronizarMetodo();
    openModal('modal-cobro');

    // Ocultar Boleta/Factura si no tienen serie creada
    if (typeof window.aplicarSeriesDisponibles === 'function') window.aplicarSeriesDisponibles();
};

const TIPOS_DOC_CLI = [
    { v: '1', t: 'DNI'        },
    { v: '6', t: 'RUC'        },
    { v: '4', t: 'CE'         },
    { v: '7', t: 'Pasaporte'  },
    { v: '0', t: 'Otro'       },
];

function sincronizarTipoComp() {
    document.querySelectorAll('#tipo-comp-grid .pay-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.tipo === cobroState.tipoComprobante);
    });
    const esElectronico = (cobroState.tipoComprobante === 'boleta' || cobroState.tipoComprobante === 'factura');
    const cd = document.getElementById('cliente-datos');
    if (cd) cd.style.display = esElectronico ? '' : 'none';
    const ist = document.getElementById('info-sunat');
    if (ist) ist.style.display = esElectronico ? '' : 'none';
    const it = document.getElementById('info-ticket');
    if (it) it.style.display = esElectronico ? 'none' : '';
    const sel = document.getElementById('cli-tipo-doc');
    if (sel) {
        if (cobroState.tipoComprobante === 'factura') sel.value = '6';
        else if (cobroState.tipoComprobante === 'boleta') sel.value = '1';
    }
}

window.buscarCliente = async () => {
    const doc = document.getElementById('cli-num-doc').value.trim();
    if (!doc) return;
    const r = await Http.post('../ajax/cliente_facturacion.php?op=buscarPorDoc', { numero_documento: doc });
    if (r && r.idclifact) {
        document.getElementById('cli-razon').value     = r.razon_social || '';
        document.getElementById('cli-direccion').value = r.direccion || '';
        document.getElementById('cli-email').value     = r.email || '';
        document.getElementById('cli-tipo-doc').value  = r.tipo_documento || '1';
        showToast('Cliente encontrado', 'success');
    } else {
        showToast('No registrado, completa los datos', '');
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
    if (cobroState.metodoPago !== 'efectivo' || !cobroState.orden) return;
    const recibido = parseFloat(document.getElementById('cobro-recibido').value) || 0;
    const total    = Number(cobroState.orden.total);
    // Si hay parte combinada en Yape/Plin, el vuelto se calcula sobre lo que falta en efectivo
    const comb = cobroCombinadoMonto();
    const efectivoNecesario = Math.max(0, +(total - comb).toFixed(2));
    const vuelto   = Math.max(0, +(recibido - efectivoNecesario).toFixed(2));
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
    window.actualizarCombinado();
};
window.actualizarCombinado = () => {
    if (!cobroState.orden) return;
    const total = Number(cobroState.orden.total);
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
$(function () {
    $(document).on('input', '#cobro-comb-monto', window.actualizarCombinado);
});
window.setMontoRecibido = (val) => {
    const total = Number(cobroState.orden ? cobroState.orden.total : 0);
    const monto = (val === 'exacto') ? total : Number(val);
    document.getElementById('cobro-recibido').value = monto.toFixed(2);
    actualizarVuelto();
};

window.confirmarCobro = async () => {
    const btn = document.getElementById('btn-confirmar-cobro');
    if (!cobroState.orden) return;
    const total = Number(cobroState.orden.total);
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
        const last4 = document.getElementById('cobro-tarjeta-4').value.trim();
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

    // Validar datos cliente para boleta/factura
    let clienteData = null;
    if (cobroState.tipoComprobante === 'boleta' || cobroState.tipoComprobante === 'factura') {
        // Validar que exista la serie de numeración antes de cobrar
        const tipoDocSunatChk = cobroState.tipoComprobante === 'factura' ? '01' : '03';
        if (typeof window.validarSerieComprobante === 'function') {
            const okSerie = await window.validarSerieComprobante(tipoDocSunatChk, cobroState.tipoComprobante);
            if (!okSerie) return;
        }
        const tipoDoc = document.getElementById('cli-tipo-doc').value;
        const numDoc  = document.getElementById('cli-num-doc').value.trim();
        const razon   = document.getElementById('cli-razon').value.trim();
        if (!numDoc || !razon) { showToast('Datos del cliente obligatorios para boleta/factura', 'error'); return; }
        if (cobroState.tipoComprobante === 'factura' && (tipoDoc !== '6' || numDoc.length !== 11)) {
            showToast('Para factura el cliente debe tener RUC (11 digitos)', 'error'); return;
        }
        clienteData = {
            tipo_doc: tipoDoc, num_doc: numDoc, razon,
            direccion: document.getElementById('cli-direccion').value.trim(),
            email:     document.getElementById('cli-email').value.trim()
        };
    }

    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
    const r = await API.ordenCobrar({
        idorden:          cobroState.orden.idorden,
        metodo_pago:      metodoEnviar,
        idsesion:         cobroState.sesion.idsesion,
        tipo_comprobante: cobroState.tipoComprobante,
        monto_recibido:   recibido,
        pago_referencia:  referencia,
        pago_metadata:    JSON.stringify(metadata)
    });

    if (!r.ok) {
        btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Cobro';
        showToast(r.msg || 'Error al cobrar', 'error');
        return;
    }

    // Notificar a otras pestanas (Mesas) que esta mesa se libero
    try {
        if (typeof BroadcastChannel !== 'undefined') {
            const bc = new BroadcastChannel('yapez-orders');
            bc.postMessage({ type: 'orden-cobrada', idorden: cobroState.orden.idorden, idmesa: cobroState.orden.idmesa, ts: Date.now() });
            bc.close();
        }
    } catch (e) {}

    let mensajeFinal = 'Orden cobrada';
    let r2 = null;
    if (clienteData) {
        const tipoDocSunat = cobroState.tipoComprobante === 'factura' ? '01' : '03';
        r2 = await Http.post('../ajax/comprobante_electronico.php?op=crearDesdeOrden', {
            idorden:           cobroState.orden.idorden,
            tipo_documento:    tipoDocSunat,
            cliente_tipo_doc:  clienteData.tipo_doc,
            cliente_num_doc:   clienteData.num_doc,
            cliente_razon:     clienteData.razon,
            cliente_direccion: clienteData.direccion,
            cliente_email:     clienteData.email,
        });
        if (r2.ok) {
            const ea = r2.envio_auto;
            if (ea && ea.intentado) {
                mensajeFinal = ea.ok
                    ? 'Cobrado · Comprobante #' + r2.idcomprobante + ' ACEPTADO por SUNAT'
                    : 'Cobrado · Comprobante #' + r2.idcomprobante + ' enviado pero SUNAT respondió: ' + (ea.mensaje || 'revisar');
            } else {
                mensajeFinal = 'Cobrado · Comprobante #' + r2.idcomprobante + ' en cola SUNAT';
            }
            if (cobroState.tipoComprobante === 'boleta' && typeof window.verificarTopeRusCobro === 'function') {
                window.verificarTopeRusCobro();
            }
        } else {
            mensajeFinal = 'Cobrado pero comprobante electronico fallo: ' + (r2.msg || '');
        }
    }

    btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Cobro';
    const numMesa = cobroState.orden && cobroState.orden.mesa_numero;
    closeModal('modal-cobro');
    const fmtComp = (window.YAPEZ_CONFIG && window.YAPEZ_CONFIG.formato_comprobante) || 'ticket';
    const wWin = fmtComp === 'a4' ? 880 : 420;
    const hWin = fmtComp === 'a4' ? 980 : 720;
    const abrirImpresion = () => {
        window.open('comprobante?id=' + r.idorden + '&formato=' + fmtComp, '_blank', 'width=' + wWin + ',height=' + hWin);
    };

    // Modal post-cobro solo para boleta/factura
    if (clienteData && (cobroState.tipoComprobante === 'boleta' || cobroState.tipoComprobante === 'factura')) {
        const idcomprobanteCE = (r2 && r2.idcomprobante) ? r2.idcomprobante : null;
        const tipoDocSunat    = cobroState.tipoComprobante === 'factura' ? '01' : '03';
        window.mostrarPostCobro({
            idcomprobante:  idcomprobanteCE,
            idclifact:      null,
            idcliente:      cobroState.orden && cobroState.orden.idcliente ? cobroState.orden.idcliente : null,
            nombre:         clienteData.razon,
            documento:      clienteData.num_doc,
            tipo_doc:       clienteData.tipo_doc,
            comprobante:    '',
            tipo_documento: tipoDocSunat,
            total:          cobroState.orden.total,
            link_pdf:       idcomprobanteCE ? (window.location.origin + '/puerto_habana/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=' + idcomprobanteCE) : '',
            numero_interno: r.idorden,
        }, abrirImpresion);
    } else {
        abrirImpresion();
    }

    if (numMesa) showToast('Mesa ' + numMesa + ' liberada · ' + mensajeFinal, 'success');
    else         showToast(mensajeFinal, 'success');
    load();   // recarga lista de pedidos (la cobrada desaparece de "activos")
};

window.anularOrden = async (id) => {
    if (!can('anular_orden')) { showToast('No tienes permiso para anular', 'error'); return; }
    if (!(await swalConfirm('¿Anular esta orden? La mesa quedará libre y no se podrá cobrar.', { title: 'Anular orden', icon: 'warning', confirmText: 'Sí, anular' }))) return;
    const r = await API.ordenAnular(id);
    if (r.ok) { showToast('Orden anulada', 'success'); load(); }
};

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
                <b>${TYPE_LABELS[o.tipo]}</b>
                ${o.mesa_numero ? ' · Mesa ' + o.mesa_numero : ''}
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
    Swal.fire({
        title: 'Orden #' + o.numero,
        html, width: 460,
        confirmButtonColor: '#5b3df5', confirmButtonText: 'Cerrar',
    });
};

document.querySelectorAll('.filter-pill').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('.filter-pill').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    activeFilter = b.dataset.filter;
    render();
}));

document.getElementById('search-orders').addEventListener('input', e => {
    searchTerm = e.target.value;
    render();
});

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
});

load();

// Tiempo real: si otro dispositivo (el mozo) crea/modifica una orden, recargar
// la lista automáticamente. No interrumpe si hay un cobro en curso.
if (window.Realtime) {
    Realtime.start(() => {
        const m = document.getElementById('modal-cobro');
        if (m && m.classList.contains('active')) return;   // no interrumpir el cobro
        load();
    }, 4000);
}
