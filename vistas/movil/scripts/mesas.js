/* Mesas movil */
const mxState = { mesas: [], zonas: [], activeZona: 'all', stats: {} };

async function mxInit() {
    [mxState.mesas, mxState.zonas, mxState.stats] = await Promise.all([
        API.mesas(),
        Http.get('../ajax/zona.php?op=listar'),
        API.mesaContarEstados()
    ]);
    if (!Array.isArray(mxState.zonas)) mxState.zonas = [];
    mxRenderStats();
    mxRenderZonas();
    mxRenderMesas();
}

function mxRenderStats() {
    const s = mxState.stats || {};
    const total = mxState.mesas.length;
    document.getElementById('m-mesas-sub').textContent = total + ' mesas';
    const items = [
        { lbl: 'Libres',   val: s.libre   || 0, bg: '#f0fdf4', col: '#059669' },
        { lbl: 'Ocupadas', val: s.ocupada || 0, bg: '#fffbeb', col: '#b45309' },
        { lbl: 'Cuenta',   val: s.cuenta  || 0, bg: '#eff6ff', col: '#3b82f6' },
        { lbl: 'Reserv.',  val: s.reservada || 0, bg: '#f5f3ff', col: '#5b3df5' }
    ];
    document.getElementById('m-mesas-stats').innerHTML = items.map(it => `
        <div style="background:${it.bg};border-radius:10px;padding:10px 6px;text-align:center;">
            <div style="font-size:20px;font-weight:800;color:${it.col};line-height:1;">${it.val}</div>
            <div style="font-size:10px;color:${it.col};font-weight:600;margin-top:4px;letter-spacing:0.4px;">${it.lbl}</div>
        </div>
    `).join('');
}

function mxRenderZonas() {
    const cont = document.getElementById('m-zonas-chips');
    let html = '<button class="m-chip ' + (mxState.activeZona === 'all' ? 'active' : '') + '" data-z="all"><span class="dot" style="background:#94a3b8;"></span>Todas</button>';
    mxState.zonas.forEach(z => {
        html += '<button class="m-chip ' + (mxState.activeZona == z.idzona ? 'active' : '') + '" data-z="' + z.idzona + '">'
              + '<span class="dot" style="background:' + z.color + ';"></span>' + mxEsc(z.nombre) + '</button>';
    });
    cont.innerHTML = html;
    cont.querySelectorAll('.m-chip').forEach(b => b.addEventListener('click', () => {
        mxState.activeZona = b.dataset.z === 'all' ? 'all' : Number(b.dataset.z);
        mxRenderZonas(); mxRenderMesas();
    }));
}

function mxRenderMesas() {
    const cont = document.getElementById('m-mesas-grid');
    let mesas = mxState.mesas;
    if (mxState.activeZona !== 'all') {
        mesas = mesas.filter(m => Number(m.idzona) === Number(mxState.activeZona));
    }
    if (!mesas.length) {
        cont.innerHTML = '<div class="m-empty"><i class="fa-solid fa-chair"></i><h3>Sin mesas</h3><p>No hay mesas en esta zona</p></div>';
        return;
    }
    cont.innerHTML = mesas.map(m => `
        <button class="m-mesa-tile ${m.estado}" onclick="mxAbrirMesa(${m.idmesa})">
            <span class="num">${m.numero}</span>
            <span class="pers">${m.capacidad}p</span>
        </button>
    `).join('');
}

window.mxAbrirMesa = (idmesa) => {
    // Navegar a Nueva Orden con ?mesa=X
    window.location.href = 'nuevaorden?mesa=' + idmesa;
};

function mxEsc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

mxInit();

// Tiempo real: refrescar el estado de las mesas cuando otro dispositivo cambie algo
if (window.Realtime) Realtime.start(mxInit, 4000);
