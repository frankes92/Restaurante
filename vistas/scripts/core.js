/* ============================================================
   PUERTO HABANA POS - core.js
   Helpers compartidos: formato, toast, wrapper de AJAX a backend
   ============================================================ */

// ---------- Formato ----------
const fmt = {
    money: (n) => {
        const sim = (window.YAPEZ_CONFIG && window.YAPEZ_CONFIG.simbolo_moneda) || 'S/';
        return sim + ' ' + Number(n || 0).toFixed(2);
    },
    number: (n) => Number(n || 0).toFixed(2),
    date: (iso) => {
        if (!iso) return '—';
        const d = new Date(iso.replace(' ', 'T'));
        return d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    },
    time: (iso) => {
        if (!iso) return '—';
        const d = new Date(iso.replace(' ', 'T'));
        return d.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
    },
    datetime: (iso) => {
        if (!iso) return '—';
        return fmt.date(iso) + ' ' + fmt.time(iso);
    }
};

// ---------- Toast (SweetAlert2) ----------
const _SwalToast = (typeof Swal !== 'undefined') ? Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
}) : null;

function showToast(message, type = '') {
    if (!_SwalToast) {
        // fallback antes que cargue SweetAlert
        console.log('[toast]', type, message);
        return;
    }
    const icon = (type === 'error') ? 'error' : (type === 'success' ? 'success' : 'info');
    _SwalToast.fire({ icon, title: message });
}

// ---------- Confirm / Alert con Swal ----------
async function swalConfirm(message, opts = {}) {
    const r = await Swal.fire({
        title: opts.title || '¿Confirmar?',
        html:  message,
        icon:  opts.icon || 'question',
        showCancelButton: true,
        confirmButtonText: opts.confirmText || 'Sí, continuar',
        cancelButtonText:  opts.cancelText  || 'Cancelar',
        confirmButtonColor: opts.confirmColor || '#5b3df5',
        cancelButtonColor:  '#6b7280',
        reverseButtons: true,
    });
    return r.isConfirmed;
}

async function swalAlert(message, opts = {}) {
    return Swal.fire({
        title: opts.title || '',
        html:  message,
        icon:  opts.icon  || 'info',
        confirmButtonColor: '#5b3df5',
        confirmButtonText:  opts.confirmText || 'Aceptar',
    });
}

async function swalError(message, title = 'Error') {
    return Swal.fire({
        title, html: message, icon: 'error',
        confirmButtonColor: '#ef4444', confirmButtonText: 'Aceptar',
    });
}

async function swalSuccess(message, title = 'Listo') {
    return Swal.fire({
        title, html: message, icon: 'success',
        confirmButtonColor: '#10b981', confirmButtonText: 'OK',
        timer: 2000, timerProgressBar: true,
    });
}

async function swalLoading(title = 'Procesando...') {
    Swal.fire({
        title, allowOutsideClick: false, allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });
}

// ---------- HTTP wrapper (jQuery $.post a ajax/*.php) ----------
const Http = {
    get(url) {
        return $.ajax({ url: url, type: 'GET', dataType: 'json' });
    },
    post(url, data) {
        return $.ajax({ url: url, type: 'POST', data: data || {}, dataType: 'json' });
    }
};

// ---------- API por dominio (azucar sintactico) ----------
const API = {
    base: '../ajax',

    // Categorias
    categorias()           { return Http.get(this.base + '/categoria.php?op=listarActivas'); },

    // Productos
    productos()            { return Http.get(this.base + '/producto.php?op=listarActivos'); },
    productosCategoria(id) { return Http.get(this.base + '/producto.php?op=listarPorCategoria&idcategoria=' + encodeURIComponent(id)); },
    topProductos(limit)    { return Http.get(this.base + '/reporte.php?op=topProductos&limit=' + (limit || 10)); },

    // Mesas
    mesas()                { return Http.get(this.base + '/mesa.php?op=listar'); },
    mesaContarEstados()    { return Http.get(this.base + '/mesa.php?op=contarPorEstado'); },
    mesaCambiarEstado(idmesa, estado) {
        return Http.post(this.base + '/mesa.php?op=cambiarEstado', { idmesa, estado });
    },
    mesaGuardar(payload)   { return Http.post(this.base + '/mesa.php?op=guardaryeditar', payload); },

    // Clientes
    clientes()             { return Http.get(this.base + '/cliente.php?op=listar'); },
    clienteEstadisticas()  { return Http.get(this.base + '/cliente.php?op=estadisticas'); },
    clienteGuardar(p)      { return Http.post(this.base + '/cliente.php?op=guardaryeditar', p); },
    clienteDesactivar(id)  { return Http.post(this.base + '/cliente.php?op=desactivar', { idcliente: id }); },
    clienteBuscar(key)     { return Http.post(this.base + '/cliente.php?op=buscar', { key }); },

    // Ordenes
    ordenes(filtros = {})  {
        const qs = new URLSearchParams(filtros).toString();
        return Http.get(this.base + '/orden.php?op=listar' + (qs ? '&' + qs : ''));
    },
    ordenMostrar(id)       { return Http.post(this.base + '/orden.php?op=mostrar', { idorden: id }); },
    ordenPorMesa(idmesa)   { return Http.post(this.base + '/orden.php?op=porMesa', { idmesa }); },
    ordenCrear(p)          { return Http.post(this.base + '/orden.php?op=crear', p); },
    ordenAgregarItem(p)    { return Http.post(this.base + '/orden.php?op=agregarItem', p); },
    ordenActualizarItem(p) { return Http.post(this.base + '/orden.php?op=actualizarItemCantidad', p); },
    ordenEliminarItem(p)   { return Http.post(this.base + '/orden.php?op=eliminarItem', p); },
    ordenCortesiaParcial(p){ return Http.post(this.base + '/orden.php?op=marcarCortesiaParcial', p); },
    ordenActualizarCab(p)  { return Http.post(this.base + '/orden.php?op=actualizarCabecera', p); },
    ordenEnviarCocina(id)  { return Http.post(this.base + '/orden.php?op=enviarACocina', { idorden: id }); },
    ordenCobrar(p)         { return Http.post(this.base + '/orden.php?op=cobrar', p); },
    ordenImprimirComprobante(p){ return Http.post(this.base + '/orden.php?op=imprimirComprobante', p); },
    ordenAnular(id)        { return Http.post(this.base + '/orden.php?op=anular', { idorden: id }); },

    // Caja
    cajaSesion()           { return Http.get(this.base + '/caja.php?op=sesionActual'); },
    cajaResumen(idsesion)  { return Http.post(this.base + '/caja.php?op=resumenSesion', { idsesion }); },
    cajaMovimientos(id)    { return Http.post(this.base + '/caja.php?op=listarMovimientos', { idsesion: id }); },
    cajaAgregarMov(p)      { return Http.post(this.base + '/caja.php?op=agregarMovimiento', p); },
    cajaAbrir(p)           { return Http.post(this.base + '/caja.php?op=abrirSesion', p); },
    cajaCerrar(p)          { return Http.post(this.base + '/caja.php?op=cerrarSesion', p); },
    cajaVentasMetodo(id)   { return Http.post(this.base + '/caja.php?op=ventasPorMetodo', { idsesion: id }); },

    // Reportes
    ventasDelDia(fecha)    { return Http.get(this.base + '/reporte.php?op=ventasDelDia' + (fecha ? '&fecha=' + fecha : '')); },
    ventasUltimosDias(d)   { return Http.get(this.base + '/reporte.php?op=ventasUltimosDias&dias=' + (d || 7)); },
};

// ---------- Modal helper minimal ----------
function openModal(id)  { document.getElementById(id)?.classList.add('active'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('active'); }

// ---------- Permisos ----------
function can(permiso) {
    if (!window.YAPEZ_USER || !Array.isArray(window.YAPEZ_USER.permisos)) return false;
    return window.YAPEZ_USER.permisos.indexOf(permiso) !== -1;
}

// Esconde elementos con data-perm que no coincidan con un permiso del usuario
function applyPermissionsDOM(root) {
    const scope = root || document;
    scope.querySelectorAll('[data-perm]').forEach(el => {
        const need = el.getAttribute('data-perm');
        if (!can(need)) el.style.display = 'none';
    });
}

// ---------- Sidebar toggle ----------
const SIDEBAR_KEY = 'yapez_sidebar_collapsed';
function esMobile() { return window.matchMedia('(max-width: 768px)').matches; }

function aplicarEstadoSidebar(colapsado) {
    const app = document.querySelector('.app');
    const floatBtn = document.querySelector('.sidebar-toggle-floating');
    const creditos = document.querySelector('.creditos-bar');
    if (!app) return;
    if (colapsado) {
        app.classList.add('sidebar-collapsed');
        if (floatBtn) floatBtn.classList.add('show');
        if (creditos) creditos.style.display = 'block';
    } else {
        app.classList.remove('sidebar-collapsed');
        if (floatBtn) floatBtn.classList.remove('show');
        if (creditos) creditos.style.display = 'none';
    }
}

function toggleSidebar() {
    const app = document.querySelector('.app');
    if (!app) return;
    const colapsado = !app.classList.contains('sidebar-collapsed');
    // En móvil NO persistimos el estado (siempre se abre por gesto del usuario)
    if (!esMobile()) {
        localStorage.setItem(SIDEBAR_KEY, colapsado ? '1' : '0');
    }
    aplicarEstadoSidebar(colapsado);
}

// Al redimensionar entre desktop ↔ móvil, re-aplicar el estado correcto
window.addEventListener('resize', () => {
    if (esMobile()) {
        // Asegurar sidebar cerrada en móvil
        if (!document.querySelector('.app')?.classList.contains('sidebar-collapsed')) {
            aplicarEstadoSidebar(true);
        }
    } else {
        // Volver al estado guardado en desktop
        const guardado = localStorage.getItem(SIDEBAR_KEY);
        aplicarEstadoSidebar(guardado === '1');
    }
});

// Tocar el backdrop oscuro cierra el sidebar en móvil
document.addEventListener('click', (e) => {
    if (!esMobile()) return;
    const app = document.querySelector('.app');
    if (!app || app.classList.contains('sidebar-collapsed')) return;
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = e.target.closest('.sidebar-toggle-floating, .sidebar-collapse-btn');
    if (toggleBtn) return;
    if (sidebar && !sidebar.contains(e.target)) {
        aplicarEstadoSidebar(true);
    }
});

// Al hacer click en un item del menú en móvil, cerrar la sidebar automáticamente
document.addEventListener('click', (e) => {
    if (!esMobile()) return;
    const navItem = e.target.closest('.nav-item');
    if (navItem) {
        setTimeout(() => aplicarEstadoSidebar(true), 100);
    }
});

function inicializarSidebar() {
    // En móvil: siempre cerrada al cargar la página (ignora localStorage)
    if (esMobile()) {
        aplicarEstadoSidebar(true);
        return;
    }
    let pref = localStorage.getItem(SIDEBAR_KEY);
    if (pref === null) {
        // Sin preferencia: colapsar por defecto si el rol es mozo
        const rol = (window.YAPEZ_USER && window.YAPEZ_USER.rol_codigo) || '';
        pref = (rol === 'mozo') ? '1' : '0';
        localStorage.setItem(SIDEBAR_KEY, pref);
    }
    aplicarEstadoSidebar(pref === '1');
}

// ---------- Logout ----------
async function logout() {
    if (!(await swalConfirm('¿Estás seguro de cerrar sesión?', { title: 'Cerrar sesión', icon: 'question', confirmText: 'Sí, salir' }))) return;
    try { await Http.post('../ajax/usuario.php?op=logout', {}); } catch (e) {}
    window.location.href = 'login';
}

// Exponer globalmente
window.fmt        = fmt;
window.showToast  = showToast;
window.Http       = Http;
window.API        = API;
window.openModal  = openModal;
window.closeModal = closeModal;
window.logout     = logout;
window.can        = can;
window.applyPermissionsDOM = applyPermissionsDOM;
window.toggleSidebar = toggleSidebar;
window.swalConfirm   = swalConfirm;
window.swalAlert     = swalAlert;
window.swalError     = swalError;
window.swalSuccess   = swalSuccess;
window.swalLoading   = swalLoading;

// =====================================================================
// MODAL AUTOMATICO DE APERTURA DE CAJA
// Si el usuario tiene permiso de caja y NO hay sesion abierta, mostrar
// el modal de apertura al cargar la primera pantalla.
// =====================================================================
window.abrirCajaModal = async function () {
    const u = window.YAPEZ_USER || {};
    const cajeroDefault = ((u.nombre || '') + ' ' + (u.apellidos || '')).trim();

    const { value: form, isConfirmed } = await Swal.fire({
        title: '<i class="fa-solid fa-lock-open" style="color:#10b981;"></i> Apertura de Caja',
        html:
            '<div style="text-align:left;font-size:13px;">' +
            '<div style="margin-bottom:14px;color:var(--text-muted);font-size:12px;">' +
                'Antes de empezar a vender, abre tu sesión de caja. ' +
                'El monto inicial es el efectivo físico que tienes para dar vuelto.' +
            '</div>' +
            '<label style="font-size:12px;font-weight:600;display:block;margin:6px 0 4px;">Caja</label>' +
            '<input type="text" id="sw-caja" class="swal2-input" value="AP-001" style="margin:4px 0;width:90%;">' +
            '<label style="font-size:12px;font-weight:600;display:block;margin:6px 0 4px;">Turno</label>' +
            '<select id="sw-turno" class="swal2-input" style="margin:4px 0;width:90%;">' +
                '<option value="Mañana">Mañana</option>' +
                '<option value="Tarde">Tarde</option>' +
                '<option value="Noche">Noche</option>' +
                '<option value="Completo">Completo</option>' +
            '</select>' +
            '<label style="font-size:12px;font-weight:600;display:block;margin:6px 0 4px;">Cajero</label>' +
            '<input type="text" id="sw-cajero" class="swal2-input" value="' + cajeroDefault + '" style="margin:4px 0;width:90%;">' +
            '<label style="font-size:12px;font-weight:600;display:block;margin:6px 0 4px;">Monto inicial (efectivo en caja)</label>' +
            '<input type="number" id="sw-monto" class="swal2-input" placeholder="0.00" step="0.10" min="0" style="margin:4px 0;width:90%;">' +
            '</div>',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-lock-open"></i> Abrir Caja',
        cancelButtonText:  'Más tarde',
        confirmButtonColor: '#10b981',
        cancelButtonColor:  '#6b7280',
        focusConfirm: false,
        allowOutsideClick: false,
        allowEscapeKey: true,
        // Auto-detectar turno por hora
        didOpen: () => {
            const sel = document.getElementById('sw-turno');
            if (sel) {
                const h = new Date().getHours();
                if (h < 12) sel.value = 'Mañana';
                else if (h < 18) sel.value = 'Tarde';
                else sel.value = 'Noche';
            }
        },
        preConfirm: () => {
            const caja_codigo = document.getElementById('sw-caja').value.trim() || 'AP-001';
            const turno       = document.getElementById('sw-turno').value;
            const cajero      = document.getElementById('sw-cajero').value.trim();
            const monto       = parseFloat(document.getElementById('sw-monto').value) || 0;
            if (!cajero) { Swal.showValidationMessage('Cajero requerido'); return false; }
            if (monto < 0) { Swal.showValidationMessage('Monto inválido'); return false; }
            return { caja_codigo, turno, cajero, monto_inicial: monto };
        }
    });

    if (!isConfirmed) return false;

    const r = await Http.post('../ajax/caja.php?op=abrirSesion', form);
    if (r.ok) {
        await swalSuccess(
            'Caja abierta con ' + (window.YAPEZ_CONFIG?.simbolo_moneda || 'S/') + ' ' +
            Number(form.monto_inicial).toFixed(2) + '<br><br>Ya puedes empezar a cobrar órdenes.',
            '¡Caja abierta!'
        );
        window.YAPEZ_CAJA_ABIERTA = true;
        // Recargar para que el footer y demás reflejen el nuevo estado
        setTimeout(() => location.reload(), 200);
        return true;
    } else {
        await swalError(r.msg || 'No se pudo abrir la caja');
        return false;
    }
};

/**
 * Verifica al cargar la pagina si:
 *   - No hay caja abierta
 *   - El usuario tiene permiso 'caja'
 *   - No estamos en pantallas excluidas
 * Y muestra el modal automaticamente.
 */
async function verificarAperturaCaja() {
    if (window.YAPEZ_CAJA_ABIERTA) return;
    if (!can('caja')) return;

    // Pantallas donde NO mostrar el modal automatico
    const path = window.location.pathname.toLowerCase();
    const excluir = ['login','bloqueado','noacceso','caja','licencia'];
    for (const e of excluir) {
        if (path.endsWith('/' + e) || path.endsWith('/' + e + '.php')) return;
    }

    // No mostrar si ya se mostro en esta sesion del navegador (evitar loop al cancelar)
    if (sessionStorage.getItem('yapez_apertura_postergada') === '1') return;

    // Pequeño delay para que la pantalla cargue primero
    setTimeout(async () => {
        const ok = await window.abrirCajaModal();
        if (!ok) {
            // El usuario eligio "Mas tarde" → recordar para no molestar en cada navegacion
            sessionStorage.setItem('yapez_apertura_postergada', '1');
        }
    }, 350);
}

// ---------- Aviso de tope Nuevo RUS (boletas del mes) ----------
// Muestra un recordatorio cuando las boletas electronicas del mes se acercan
// (>=90%) o superan el tope de S/ 5,000. Solo informa; nunca bloquea.
function mensajeTopeRus(tr) {
    const money = n => 'S/ ' + Number(n || 0).toFixed(2);
    if (tr.estado === 'excedido') {
        return {
            icon: 'warning',
            title: 'Tope Nuevo RUS superado',
            html: `Las boletas electrónicas de este mes (${tr.mes}) suman <b>${money(tr.monto)}</b>, ` +
                  `superando el tope de <b>${money(tr.limite)}</b>.<br><br>` +
                  `La cuota de este mes sube de <b>S/ ${Number(tr.cuota1).toFixed(0)}</b> a ` +
                  `<b>S/ ${Number(tr.cuota2).toFixed(0)}</b> (Categoría 2, válida hasta ${money(tr.limite_max)}).<br><br>` +
                  `<span style="color:#6b7280;">Puedes seguir emitiendo comprobantes con normalidad; ` +
                  `esto es solo un recordatorio tributario.</span>`
        };
    }
    return {
        icon: 'info',
        title: 'Estás por llegar al tope del Nuevo RUS',
        html: `Las boletas electrónicas de este mes (${tr.mes}) suman <b>${money(tr.monto)}</b> ` +
              `de <b>${money(tr.limite)}</b> (${tr.porcentaje}%).<br><br>` +
              `Te faltan <b>${money(tr.restante)}</b> para el límite. Si lo superas, la cuota de ` +
              `este mes sube de <b>S/ ${Number(tr.cuota1).toFixed(0)}</b> a <b>S/ ${Number(tr.cuota2).toFixed(0)}</b>.<br><br>` +
              `<span style="color:#6b7280;">Puedes seguir emitiendo comprobantes con normalidad; ` +
              `esto es solo un recordatorio tributario.</span>`
    };
}

// Recordatorio al iniciar sesion / cargar (una vez por dia por navegador)
function verificarTopeRus() {
    const tr = window.HABANA_TOPE_RUS;
    if (!tr || !tr.estado || (tr.estado !== 'cerca' && tr.estado !== 'excedido')) return;
    if (typeof Swal === 'undefined') return;

    // Mostrar como maximo una vez al dia (clave incluye estado para re-avisar si pasa a excedido)
    const hoy = new Date().toISOString().slice(0, 10);
    const clave = 'habana_rus_aviso_' + hoy + '_' + tr.estado;
    if (sessionStorage.getItem(clave) === '1') return;

    const m = mensajeTopeRus(tr);
    setTimeout(() => {
        Swal.fire({
            icon: m.icon, title: m.title, html: m.html,
            confirmButtonColor: tr.estado === 'excedido' ? '#ef4444' : '#5b3df5',
            confirmButtonText: 'Entendido',
        });
        sessionStorage.setItem(clave, '1');
    }, 600);
}

// Expuesta para llamarla tras un cobro con boleta (recalcula con el dato fresco del backend)
window.verificarTopeRusCobro = async function () {
    try {
        const tr = await Http.get('../ajax/reporte.php?op=topeRus');
        if (!tr || (tr.estado !== 'cerca' && tr.estado !== 'excedido')) return;
        window.HABANA_TOPE_RUS = tr;
        const m = mensajeTopeRus(tr);
        await Swal.fire({
            icon: m.icon, title: m.title, html: m.html,
            confirmButtonColor: tr.estado === 'excedido' ? '#ef4444' : '#5b3df5',
            confirmButtonText: 'Entendido',
        });
    } catch (e) { /* no romper el flujo de cobro por el aviso */ }
};

// Valida que exista una SERIE de numeración activa para el tipo de documento
// antes de cobrar boleta/factura. Si no existe, muestra alerta y devuelve false
// (el cobro NO debe continuar). tipoSunat: '01' factura, '03' boleta.
window.validarSerieComprobante = async function (tipoSunat, etiqueta) {
    try {
        const r = await Http.get('../ajax/numeracion.php?op=existeSerie&tipo_documento=' + encodeURIComponent(tipoSunat));
        if (r && r.ok && r.existe) return true;
    } catch (e) { /* si la consulta falla, caemos a la alerta abajo */ }

    const nombre = etiqueta === 'factura' ? 'FACTURA' : 'BOLETA';
    if (typeof Swal !== 'undefined') {
        await Swal.fire({
            icon: 'error',
            title: 'No se puede emitir ' + nombre,
            html: 'No existe una <b>serie de numeración</b> creada para ' + nombre.toLowerCase() + '.<br><br>' +
                  'Crea la serie en <b>Configuración → Numeración</b> antes de cobrar este comprobante electrónico.',
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Entendido',
        });
    } else {
        showToast('No existe serie de numeración para ' + nombre + '. Créala en Numeración.', 'error');
    }
    return false;
};

// Oculta los botones Boleta/Factura del modal de cobro si NO existe su serie
// de numeración. Así el cajero solo ve los comprobantes que sí puede emitir.
// Se llama al abrir el modal de cobro. Devuelve los tipos disponibles.
window.aplicarSeriesDisponibles = async function () {
    let tipos = { boleta: true, factura: true }; // por defecto mostrar (fail-safe)
    try {
        const r = await Http.get('../ajax/numeracion.php?op=tiposDisponibles');
        if (r && r.ok && r.tipos) tipos = r.tipos;
    } catch (e) { /* si falla, se dejan visibles y la validacion al cobrar protege */ }

    document.querySelectorAll('#tipo-comp-grid .pay-btn[data-tipo]').forEach(btn => {
        const t = btn.dataset.tipo;
        if (t === 'boleta')      btn.style.display = tipos.boleta  ? '' : 'none';
        else if (t === 'factura') btn.style.display = tipos.factura ? '' : 'none';
        // 'nota_venta' siempre visible
    });
    return tipos;
};

// =====================================================================
// REALTIME — sincronización en tiempo real entre dispositivos (polling).
// Consulta cada pocos segundos la "firma" del estado (ajax/sync.php). Si
// cambió respecto a la última, ejecuta onChange() para recargar la vista.
// Funciona entre PC, laptop y celulares (es del lado servidor), a diferencia
// de BroadcastChannel que solo sincroniza pestañas del mismo navegador.
// Uso:  Realtime.start(onChange, 5000)
// =====================================================================
window.Realtime = {
    _last: null,
    _timer: null,
    async _tick(onChange) {
        if (document.hidden) return;             // ahorra recursos en segundo plano
        try {
            const r = await Http.get('../ajax/sync.php?op=sig');
            if (!r || typeof r.sig === 'undefined') return;
            if (this._last !== null && r.sig !== this._last) {
                this._last = r.sig;
                try { onChange(); } catch (e) {}
            } else {
                this._last = r.sig;              // fija/actualiza baseline
            }
        } catch (e) { /* red intermitente: reintenta en el próximo tick */ }
    },
    start(onChange, ms) {
        ms = ms || 5000;
        if (this._timer) clearInterval(this._timer);
        this._tick(onChange);                                   // baseline inmediato
        this._timer = setInterval(() => this._tick(onChange), ms);
        // Al volver a la pestaña, comprobar al instante
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) this._tick(onChange);
        });
    }
};

// Aplicar permisos + estado del sidebar al cargar
$(function () {
    applyPermissionsDOM();
    inicializarSidebar();
    verificarAperturaCaja();
    verificarTopeRus();
});
