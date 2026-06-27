/* Pedidos movil */
// Rango de fechas: por defecto HOY–HOY, para no listar pedidos de otros días.
const mpState = { filtro: '', desde: mpHoy(), hasta: mpHoy(), ordenes: [] };

// Fecha local de hoy en formato yyyy-mm-dd
function mpHoy() {
    const d = new Date();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + mm + '-' + dd;
}

async function mpInit() {
    const inpDesde = document.getElementById('m-ped-desde');
    const inpHasta = document.getElementById('m-ped-hasta');
    if (inpDesde) {
        inpDesde.value = mpState.desde;
        inpDesde.addEventListener('change', async () => { mpState.desde = inpDesde.value || ''; await mpCargar(); });
    }
    if (inpHasta) {
        inpHasta.value = mpState.hasta;
        inpHasta.addEventListener('change', async () => { mpState.hasta = inpHasta.value || ''; await mpCargar(); });
    }
    const btnHoy = document.getElementById('m-ped-hoy');
    if (btnHoy) {
        btnHoy.addEventListener('click', async () => {
            mpState.desde = mpState.hasta = mpHoy();
            if (inpDesde) inpDesde.value = mpState.desde;
            if (inpHasta) inpHasta.value = mpState.hasta;
            await mpCargar();
        });
    }

    await mpCargar();
    document.querySelectorAll('#m-ped-filtros .m-chip').forEach(b => b.addEventListener('click', async () => {
        document.querySelectorAll('#m-ped-filtros .m-chip').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        mpState.filtro = b.dataset.est || '';
        await mpCargar();
    }));
}

async function mpCargar() {
    let url = '../ajax/orden.php?op=listar' + (mpState.filtro ? '&estado=' + mpState.filtro : '');
    // Filtro por rango: el backend filtra por DATE(o.fecha) entre desde y hasta.
    // Si el usuario invierte las fechas, las ordenamos para no devolver vacío.
    let desde = mpState.desde, hasta = mpState.hasta;
    if (desde && hasta && desde > hasta) { const t = desde; desde = hasta; hasta = t; }
    if (desde) url += '&desde=' + desde;
    if (hasta) url += '&hasta=' + hasta;
    mpState.ordenes = await Http.get(url) || [];
    mpRender();
}

function mpRender() {
    const cont = document.getElementById('m-ped-lista');
    const total = mpState.ordenes.length;
    document.getElementById('m-ped-sub').textContent = total + ' pedidos';
    if (!total) {
        cont.innerHTML = '<div class="m-empty"><i class="fa-solid fa-receipt"></i><h3>Sin pedidos</h3><p>No hay pedidos en este filtro</p></div>';
        return;
    }
    cont.innerHTML = mpState.ordenes.map(o => {
        const fechaTxt = (o.fecha || '').replace('T', ' ').slice(0, 16);
        const mesaTxt = o.mesa_numero ? ('Mesa ' + o.mesa_numero) : (o.tipo === 'para_llevar' ? 'Para llevar' : (o.tipo === 'delivery' ? 'Delivery' : 'Local'));
        const labels = { en_curso: 'En curso', enviada: 'En cocina', pagada: 'Pagada', anulada: 'Anulada' };
        return `
        <div class="m-order-card ${o.estado}">
            <div class="m-order-head">
                <div class="m-order-id">
                    #${o.numero}
                    <small>${mpEsc(mesaTxt)} · ${o.total_items || 0} items</small>
                </div>
                <span class="m-order-state ${o.estado}">${labels[o.estado] || o.estado}</span>
            </div>
            <div class="m-order-meta">
                <span><i class="fa-regular fa-clock"></i> ${fechaTxt}</span>
                ${o.mozo ? '<span><i class="fa-regular fa-user"></i> ' + mpEsc(o.mozo) + '</span>' : ''}
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:11px;color:var(--m-muted);font-weight:600;letter-spacing:0.4px;">TOTAL</span>
                <span class="m-order-total">${fmt.money(o.total)}</span>
            </div>
            <div class="m-order-actions">
                ${o.estado === 'pagada' ? `<button class="primary" onclick="mpImprimir(${o.idorden})"><i class="fa-solid fa-print"></i> Comprobante</button>` : ''}
                ${o.estado === 'enviada' ? `<button class="success" onclick="mpCobrar(${o.idorden}, ${o.idmesa || 'null'})"><i class="fa-solid fa-dollar-sign"></i> Cobrar</button>` : ''}
                ${(o.estado === 'en_curso' || o.estado === 'enviada') ? `<button onclick="mpAbrir(${o.idorden}, ${o.idmesa || 'null'})"><i class="fa-solid fa-edit"></i> Abrir</button>` : ''}
                ${o.estado !== 'pagada' && o.estado !== 'anulada' ? `<button onclick="mpAnular(${o.idorden})" style="color:var(--m-red);"><i class="fa-solid fa-ban"></i></button>` : ''}
            </div>
        </div>`;
    }).join('');
}

window.mpImprimir = (idorden) => {
    const fmtComp = (window.YAPEZ_CONFIG && window.YAPEZ_CONFIG.formato_comprobante) || 'ticket';
    window.open('comprobante?id=' + idorden + '&formato=' + fmtComp, '_blank');
};
window.mpAbrir = (idorden, idmesa) => {
    // Con mesa (local): abrir por mesa (flujo de siempre).
    // Sin mesa (para llevar / delivery): abrir por idorden, si no quedaría en
    // blanco y solo mostraría mesas. modo=abrir => entra a editar (productos).
    if (idmesa) window.location.href = 'nuevaorden?mesa=' + idmesa;
    else        window.location.href = 'nuevaorden?orden=' + idorden + '&modo=abrir';
};
window.mpCobrar = (idorden, idmesa) => {
    // Lleva a Nueva Orden para usar el flujo de cobro.
    //  - Con mesa (local): se carga por mesa (flujo de siempre).
    //  - Sin mesa (para llevar / delivery): se carga por idorden, si no quedaría
    //    en blanco y solo mostraría mesas.
    if (idmesa) window.location.href = 'nuevaorden?mesa=' + idmesa;
    else        window.location.href = 'nuevaorden?orden=' + idorden;
};
window.mpAnular = async (idorden) => {
    const ok = await Swal.fire({
        icon: 'warning',
        title: '¿Anular orden?',
        text: 'La orden será marcada como anulada y la mesa se liberará.',
        showCancelButton: true,
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        reverseButtons: true
    });
    if (!ok.isConfirmed) return;
    const r = await API.ordenAnular(idorden);
    if (r.ok) { showToast('Orden anulada', 'success'); await mpCargar(); }
    else      { showToast('Error al anular', 'error'); }
};

function mpEsc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

mpInit();

// Tiempo real: recargar la lista de pedidos cuando otro dispositivo cambie algo.
// No interrumpe si hay un diálogo (cobro/anulación) abierto.
if (window.Realtime) {
    Realtime.start(() => {
        if (typeof Swal !== 'undefined' && Swal.isVisible && Swal.isVisible()) return;
        mpCargar();
    }, 4000);
}
