/* Historial movil */
const mhState = { rango: '', metodo: '', ordenes: [] };

async function mhInit() {
    await mhCargar();
    document.querySelectorAll('#m-hist-rango .m-chip').forEach(b => b.addEventListener('click', async () => {
        document.querySelectorAll('#m-hist-rango .m-chip').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        mhState.rango = b.dataset.rango || '';
        await mhCargar();
    }));
    document.querySelectorAll('#m-hist-metodo .m-chip').forEach(b => b.addEventListener('click', async () => {
        document.querySelectorAll('#m-hist-metodo .m-chip').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        mhState.metodo = b.dataset.metodo || '';
        await mhCargar();
    }));
}

// Fecha LOCAL yyyy-mm-dd (no UTC). Con toISOString() en Perú (UTC-5) la fecha se
// adelantaba un día por la tarde/noche y el historial mostraba el día equivocado.
function mhFechaLocal(d) {
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + mm + '-' + dd;
}

async function mhCargar() {
    // Reusa el endpoint de listar con estado=pagada
    const params = new URLSearchParams({ estado: 'pagada' });
    const today = new Date();
    if (mhState.rango === 'hoy') {
        const d = mhFechaLocal(today);
        params.set('desde', d); params.set('hasta', d);
    } else if (mhState.rango === 'semana') {
        const desde = mhFechaLocal(new Date(today.getTime() - 6*24*60*60*1000));
        params.set('desde', desde);
    } else if (mhState.rango === 'mes') {
        const desde = mhFechaLocal(new Date(today.getFullYear(), today.getMonth(), 1));
        params.set('desde', desde);
    }
    const r = await Http.get('../ajax/orden.php?op=listar&' + params.toString()) || [];
    let lista = Array.isArray(r) ? r : [];
    if (mhState.metodo) lista = lista.filter(o => o.metodo_pago === mhState.metodo);
    mhState.ordenes = lista;
    mhRender();
}

function mhRender() {
    document.getElementById('m-hist-sub').textContent = mhState.ordenes.length + ' ventas';

    // Resumen
    const total = mhState.ordenes.reduce((s, o) => s + Number(o.total || 0), 0);
    const avg   = mhState.ordenes.length ? total / mhState.ordenes.length : 0;
    document.getElementById('m-hist-summary').innerHTML = `
        <div class="h-item">
            <div class="h-lbl">VENTAS</div>
            <div class="h-val">${fmt.money(total)}</div>
        </div>
        <div class="h-item">
            <div class="h-lbl">TICKET PROMEDIO</div>
            <div class="h-val">${fmt.money(avg)}</div>
        </div>
    `;

    const cont = document.getElementById('m-hist-lista');
    if (!mhState.ordenes.length) {
        cont.innerHTML = '<div class="m-empty"><i class="fa-regular fa-clock"></i><h3>Sin ventas</h3><p>No hay ventas en este rango</p></div>';
        return;
    }
    cont.innerHTML = mhState.ordenes.map(o => {
        const fechaTxt = (o.fecha_pago || o.fecha || '').replace('T', ' ').slice(0, 16);
        const mesaTxt = o.mesa_numero ? ('Mesa ' + o.mesa_numero) : (o.tipo === 'para_llevar' ? 'Para llevar' : (o.tipo === 'delivery' ? 'Delivery' : 'Local'));
        return `
        <div class="m-hist-card">
            <div>
                <div class="m-hist-num">#${o.numero}</div>
                <div class="m-hist-meta">
                    <i class="fa-regular fa-clock"></i>${fechaTxt}<br>
                    <i class="fa-solid fa-chair"></i>${mhEsc(mesaTxt)} · ${o.total_items || 0} items
                </div>
            </div>
            <div>
                <div class="m-hist-total">${fmt.money(o.total)}</div>
                <div style="text-align:right;"><span class="m-hist-metodo ${o.metodo_pago}">${o.metodo_pago || '—'}</span></div>
            </div>
            <button onclick="mhImprimir(${o.idorden})"><i class="fa-solid fa-print"></i> Ver comprobante</button>
        </div>`;
    }).join('');
}

window.mhImprimir = (idorden) => {
    const fmtComp = (window.YAPEZ_CONFIG && window.YAPEZ_CONFIG.formato_comprobante) || 'ticket';
    window.open('comprobante?id=' + idorden + '&formato=' + fmtComp, '_blank');
};

function mhEsc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

mhInit();
