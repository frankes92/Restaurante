/* reportes.js - usa Chart.js + endpoints reporte/orden */

let activeRange = 'hoy';
let customDesde = '';   // 'YYYY-MM-DD' cuando el rango es personalizado
let customHasta = '';
let charts = {};

function isInRange(iso, range) {
    const d = new Date((iso || '').replace(' ', 'T'));
    const now = new Date();
    if (range === 'hoy')    return d.toDateString() === now.toDateString();
    if (range === 'semana') return (now - d) <= 7 * 86400000;
    if (range === 'mes')    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
    if (range === 'custom') {
        // Comparar solo por fecha (sin hora) dentro de [customDesde, customHasta]
        const dia = (iso || '').slice(0, 10);
        return dia !== '' && (!customDesde || dia >= customDesde) && (!customHasta || dia <= customHasta);
    }
    return true;
}

function rangeToFechas(range) {
    const hoy = new Date();
    // Fecha LOCAL (no UTC). Con toISOString() en zonas como Perú (UTC-5) la fecha
    // se adelantaba un día por la tarde/noche; por eso "hoy" salía como mañana.
    const fmtD = d => {
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + mm + '-' + dd;
    };
    if (range === 'custom') {
        return { desde: customDesde || '2000-01-01', hasta: customHasta || fmtD(hoy) };
    }
    let desde, hasta = hoy;
    if (range === 'hoy') desde = hoy;
    else if (range === 'semana') { desde = new Date(); desde.setDate(desde.getDate() - 6); }
    else if (range === 'mes') { desde = new Date(hoy.getFullYear(), hoy.getMonth(), 1); }
    else { desde = new Date(2000, 0, 1); } // todo el tiempo
    return { desde: fmtD(desde), hasta: fmtD(hasta) };
}

// Aplica el rango personalizado escrito en los inputs de fecha
window.aplicarRangoPersonalizado = () => {
    const d = document.getElementById('f-desde').value;
    const h = document.getElementById('f-hasta').value;
    if (!d || !h) { showToast('Selecciona fecha desde y hasta', 'error'); return; }
    if (d > h) { showToast('La fecha "desde" no puede ser mayor que "hasta"', 'error'); return; }
    customDesde = d;
    customHasta = h;
    activeRange = 'custom';
    document.querySelectorAll('.filter-pill').forEach(x => x.classList.remove('active'));
    load();
};

window.descargarExcel = () => {
    const { desde, hasta } = rangeToFechas(activeRange);
    window.location.href = '../ajax/reporte.php?op=excel&desde=' + desde + '&hasta=' + hasta;
};

async function load() {
    const pagadas = await API.ordenes({ estado: 'pagada' }) || [];
    const enRango = pagadas.filter(o => isInRange(o.fecha_pago, activeRange));

    const ventas = enRango.reduce((s, o) => s + Number(o.total), 0);
    document.getElementById('r-sales').textContent  = fmt.money(ventas);
    document.getElementById('r-orders').textContent = enRango.length;
    document.getElementById('r-avg').textContent    = enRango.length ? fmt.money(ventas / enRango.length) : 'S/ 0.00';
    document.getElementById('r-items').textContent  = enRango.reduce((s, o) => s + Number(o.total_items || 0), 0);

    // Tarjetas por tipo de comprobante (nota_venta, boletas, facturas)
    const { desde, hasta } = rangeToFechas(activeRange);
    try {
        const r = await Http.get('../ajax/reporte.php?op=ventasPorTipoComprobante&desde=' + desde + '&hasta=' + hasta);
        const t = r.por_tipo || {};
        const sun = r.sunat || {};
        const setVal = (cnt, tot, key) => {
            const v = t[key] || { cantidad: 0, total: 0 };
            document.getElementById(cnt).textContent = v.cantidad;
            document.getElementById(tot).textContent = fmt.money(v.total);
        };
        setVal('r-cnt-nv',      'r-tot-nv',      'nota_venta');
        setVal('r-cnt-boleta',  'r-tot-boleta',  'boleta');
        setVal('r-cnt-factura', 'r-tot-factura', 'factura');

        // Detalle SUNAT
        const bAcept = Number(sun.boletas_aceptadas || 0);
        const bPend  = Number(sun.boletas_pendientes || 0);
        const bRech  = Number(sun.boletas_rechazadas || 0);
        const fAcept = Number(sun.facturas_aceptadas || 0);
        const fPend  = Number(sun.facturas_pendientes || 0);
        const fRech  = Number(sun.facturas_rechazadas || 0);
        document.getElementById('r-sunat-boleta').innerHTML  = `SUNAT: <b style="color:#10b981;">${bAcept} aceptadas</b>` + (bPend ? ` · ${bPend} pendientes` : '') + (bRech ? ` · <b style="color:#ef4444;">${bRech} rechazadas</b>` : '');
        document.getElementById('r-sunat-factura').innerHTML = `SUNAT: <b style="color:#10b981;">${fAcept} aceptadas</b>` + (fPend ? ` · ${fPend} pendientes` : '') + (fRech ? ` · <b style="color:#ef4444;">${fRech} rechazadas</b>` : '');
    } catch (e) {
        console.warn('Error cargando metricas por tipo', e);
    }

    // Ventas por día: recorre el rango real (cap 60 días). Para "todo el tiempo"
    // muestra los últimos 30 días para no saturar el gráfico.
    const MAX_DIAS = 60;
    let startD, endD = new Date(hasta + 'T00:00:00');
    if (activeRange === 'all') { startD = new Date(); startD.setDate(startD.getDate() - 29); }
    else { startD = new Date(desde + 'T00:00:00'); }
    const days = [];
    const labels = [];
    const cur = new Date(startD);
    while (cur <= endD && days.length < MAX_DIAS) {
        const key = cur.toDateString();
        days.push(enRango.filter(o => new Date((o.fecha_pago || '').replace(' ', 'T')).toDateString() === key)
            .reduce((s, o) => s + Number(o.total), 0));
        labels.push(cur.toLocaleDateString('es-PE', { day: '2-digit', month: 'short' }));
        cur.setDate(cur.getDate() + 1);
    }
    drawSalesChart(labels, days);

    // Métodos de pago
    const metodos = ['efectivo', 'tarjeta', 'yape', 'transferencia'];
    const payData = metodos.map(m => enRango.filter(o => o.metodo_pago === m).reduce((s, o) => s + Number(o.total), 0));
    drawPayChart(payData);

    // Top productos via endpoint
    const top = await API.topProductos(5);
    const tpEl = document.getElementById('top-products');
    if (!top || top.length === 0 || top.every(t => Number(t.total_vendido) === 0)) {
        tpEl.innerHTML = '<div class="empty-state" style="padding:30px;"><i class="fa-solid fa-chart-bar"></i><p>Sin datos en este periodo</p></div>';
    } else {
        tpEl.innerHTML = top.map((p, idx) => `
            <div class="top-product">
                <div class="tp-rank">${idx + 1}</div>
                <div class="tp-img" style="background-image:url('${p.imagen || ''}');background-color:#f3f4f6;"></div>
                <div class="tp-info">
                    <div class="tp-name">${p.nombre}</div>
                    <div class="tp-qty">${Number(p.total_vendido)} unidades vendidas</div>
                </div>
                <div class="tp-total">${fmt.money(p.total_ingresos)}</div>
            </div>
        `).join('');
    }

    // Tipo de orden
    const tipos = ['dine_in', 'para_llevar', 'delivery'];
    const typeData = tipos.map(t => enRango.filter(o => o.tipo === t).length);
    drawTypeChart(typeData);

    // Ventas por zona
    try {
        const zr = await Http.get('../ajax/reporte.php?op=ventasPorZona&desde=' + desde + '&hasta=' + hasta);
        renderVentasPorZona(Array.isArray(zr) ? zr : []);
    } catch (e) {
        console.warn('Error cargando ventas por zona', e);
    }

    // Detalle de ventas: agrupado por fecha -> producto (con totales) -> presentación
    try {
        const dp = await Http.get('../ajax/reporte.php?op=ventasDetalle&desde=' + desde + '&hasta=' + hasta);
        detalleVentas = Array.isArray(dp) ? dp : [];
        renderDetalleProductos();
    } catch (e) {
        console.warn('Error cargando detalle de ventas', e);
    }
}

let detalleVentas = [];

function dpNum(n) { n = Number(n) || 0; return (n === Math.floor(n)) ? String(n) : n.toFixed(2); }

// Fecha "dd/mm/aaaa" (sin hora) a partir de 'YYYY-MM-DD...'
function dpFecha(s) {
    const dia = String(s || '').slice(0, 10);
    const p = dia.split('-');
    return p.length === 3 ? (p[2] + '/' + p[1] + '/' + p[0]) : (dia || '—');
}
function dpPresNombre(nombre) {
    return (nombre && nombre !== '—' && String(nombre).toLowerCase() !== 'normal') ? nombre : 'General';
}
function dpStockHtml(stock) {
    const s = Number(stock) || 0;
    const color = s <= 0 ? '#dc2626' : (s <= 5 ? '#d97706' : '#16a34a');
    return `<span style="font-weight:700;color:${color};">${dpNum(s)}</span>`;
}

// Mostrar/ocultar el desglose de presentaciones de un producto
window.dpToggle = (id) => {
    const rows = document.querySelectorAll('tr.dp-pres-row.' + id);
    const caret = document.getElementById(id + '-c');
    if (!rows.length) return;
    const mostrar = rows[0].style.display === 'none';
    rows.forEach(r => r.style.display = mostrar ? '' : 'none');
    if (caret) caret.style.transform = mostrar ? 'rotate(90deg)' : '';
};

function renderDetalleProductos() {
    const body = document.getElementById('detalle-prod-body');
    const resumen = document.getElementById('dp-resumen');
    if (!body) return;
    const filtro = (document.getElementById('dp-buscar')?.value || '').toLowerCase().trim();
    const lista = filtro
        ? detalleVentas.filter(v => (v.producto_nombre || '').toLowerCase().includes(filtro)
                                 || (v.presentacion || '').toLowerCase().includes(filtro))
        : detalleVentas;

    // Agrupar: FECHA -> PRODUCTO -> PRESENTACIÓN
    let totVend = 0, totCort = 0, totIng = 0;
    const fechas = new Map();   // 'YYYY-MM-DD' -> { fecha, productos: Map }
    lista.forEach(v => {
        const fk = String(v.fecha_pago || '').slice(0, 10);
        if (!fechas.has(fk)) fechas.set(fk, { fecha: fk, productos: new Map() });
        const g = fechas.get(fk);
        const pid = v.idproducto != null ? v.idproducto : ('n' + (v.producto_nombre || ''));
        if (!g.productos.has(pid)) g.productos.set(pid, {
            nombre: v.producto_nombre || '—', categoria: v.categoria_nombre || '—',
            vendidos: 0, ingresos: 0, cortesias: 0, presentaciones: new Map()
        });
        const prod = g.productos.get(pid);
        const cant = Number(v.cantidad) || 0;
        const sub  = Number(v.subtotal) || 0;
        const esCort = Number(v.cortesia) === 1;
        if (esCort) prod.cortesias += cant; else { prod.vendidos += cant; prod.ingresos += sub; }
        // Presentación
        const prk = v.idprecio != null ? ('p' + v.idprecio) : ('x' + (v.presentacion || ''));
        if (!prod.presentaciones.has(prk)) prod.presentaciones.set(prk, {
            nombre: v.presentacion, vendidos: 0, ingresos: 0, cortesias: 0,
            controla_stock: Number(v.controla_stock) === 1, stock_actual: Number(v.stock_actual) || 0
        });
        const pres = prod.presentaciones.get(prk);
        if (esCort) pres.cortesias += cant; else { pres.vendidos += cant; pres.ingresos += sub; }
        // Totales generales
        if (esCort) totCort += cant; else { totVend += cant; totIng += sub; }
    });

    if (resumen) {
        resumen.innerHTML = `<b>${dpNum(totVend)}</b> vendidos · <b style="color:var(--primary);">${fmt.money(totIng)}</b>`
            + (totCort > 0 ? ` · <b style="color:#15803d;">${dpNum(totCort)}</b> cortesías` : '');
    }

    if (lista.length === 0) {
        body.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">Sin ventas en este periodo</td></tr>';
        return;
    }

    const cortBadge = (n) => Number(n) > 0
        ? `<span style="display:inline-block;background:#dcfce7;color:#15803d;font-weight:700;padding:1px 8px;border-radius:10px;">${dpNum(n)}</span>`
        : '<span style="color:#9ca3af;">0</span>';

    let html = '';
    let rid = 0;
    // Fechas más recientes primero
    [...fechas.values()].sort((a, b) => a.fecha < b.fecha ? 1 : -1).forEach(g => {
        html += `<tr class="dp-fecha-grupo"><td colspan="6"><i class="fa-regular fa-calendar"></i> ${dpFecha(g.fecha)}</td></tr>`;
        // Productos: más vendidos primero
        [...g.productos.values()].sort((a, b) => b.vendidos - a.vendidos).forEach(prod => {
            rid++;
            const id = 'dpd' + rid;
            const presList = [...prod.presentaciones.values()];
            const tieneStock = presList.some(p => p.controla_stock);
            // Stock total = suma del stock actual de cada presentación (distinta)
            const stockTot = presList.filter(p => p.controla_stock).reduce((s, p) => s + p.stock_actual, 0);
            html += `<tr class="dp-prod-row" style="cursor:pointer;" onclick="dpToggle('${id}')">
                <td style="font-weight:600;"><i class="fa-solid fa-chevron-right dp-caret" id="${id}-c"></i>${prod.nombre}</td>
                <td style="color:var(--text-muted);">${prod.categoria}</td>
                <td style="text-align:center;font-weight:700;">${dpNum(prod.vendidos)}</td>
                <td style="text-align:right;font-weight:700;color:var(--primary);">${fmt.money(prod.ingresos)}</td>
                <td style="text-align:center;">${cortBadge(prod.cortesias)}</td>
                <td style="text-align:center;">${tieneStock ? dpStockHtml(stockTot) : '<span style="color:#9ca3af;">—</span>'}</td>
            </tr>`;
            // Desglose por presentación (oculto hasta hacer clic)
            presList.sort((a, b) => b.vendidos - a.vendidos).forEach(pres => {
                html += `<tr class="dp-pres-row ${id}" style="display:none;">
                    <td style="padding-left:34px;color:#374151;">↳ ${dpPresNombre(pres.nombre)}</td>
                    <td></td>
                    <td style="text-align:center;">${dpNum(pres.vendidos)}</td>
                    <td style="text-align:right;color:var(--primary);">${fmt.money(pres.ingresos)}</td>
                    <td style="text-align:center;">${cortBadge(pres.cortesias)}</td>
                    <td style="text-align:center;">${pres.controla_stock ? dpStockHtml(pres.stock_actual) : '<span style="color:#9ca3af;">—</span>'}</td>
                </tr>`;
            });
        });
    });
    body.innerHTML = html;
}

function renderVentasPorZona(zonas) {
    const cont = document.getElementById('ventas-por-zona');
    if (!cont) return;
    const total = zonas.reduce((s, z) => s + Number(z.total), 0);
    if (zonas.length === 0 || total === 0) {
        cont.innerHTML = '<div class="empty-state" style="padding:24px;"><i class="fa-solid fa-map-location-dot"></i><p>Sin datos en este periodo</p></div>';
        drawZonaChart([], [], []);
        return;
    }
    cont.innerHTML = zonas.map(z => {
        const pct = total > 0 ? (Number(z.total) / total) * 100 : 0;
        return `
            <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f3f4f6;">
                <div style="width:10px;height:36px;background:${z.zona_color};border-radius:3px;"></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;">${z.zona_nombre}</div>
                    <div style="font-size:11px;color:var(--text-muted);">${z.cantidad} orden${Number(z.cantidad)!==1?'es':''} · ${pct.toFixed(1)}%</div>
                    <div style="height:4px;background:#f3f4f6;border-radius:2px;margin-top:4px;overflow:hidden;">
                        <div style="height:100%;width:${pct.toFixed(1)}%;background:${z.zona_color};"></div>
                    </div>
                </div>
                <div style="font-size:14px;font-weight:700;color:var(--primary);">${fmt.money(z.total)}</div>
            </div>`;
    }).join('');
    drawZonaChart(zonas.map(z => z.zona_nombre), zonas.map(z => Number(z.total)), zonas.map(z => z.zona_color));
}

function drawZonaChart(labels, data, colors) {
    if (charts.zona) charts.zona.destroy();
    const el = document.getElementById('zona-chart');
    if (!el) return;
    const ctx = el.getContext('2d');
    charts.zona = new Chart(ctx, {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: (colors && colors.length) ? colors : ['#5b3df5','#10b981','#3b82f6','#f59e0b','#ec4899'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12, boxWidth: 10, boxHeight: 10, color: '#6b7280' } } }, cutout: '60%' }
    });
}

function drawSalesChart(labels, data) {
    if (charts.sales) charts.sales.destroy();
    const ctx = document.getElementById('sales-chart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 280);
    grad.addColorStop(0, 'rgba(91, 61, 245, 0.3)');
    grad.addColorStop(1, 'rgba(91, 61, 245, 0)');
    charts.sales = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets: [{ label: 'Ventas', data, fill: true, backgroundColor: grad, borderColor: '#5b3df5', borderWidth: 2.5, tension: 0.35, pointRadius: 4, pointBackgroundColor: '#5b3df5', pointBorderColor: '#fff', pointBorderWidth: 2 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 }, color: '#6b7280' } }, x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#6b7280' } } } }
    });
}

function drawPayChart(data) {
    if (charts.pay) charts.pay.destroy();
    const ctx = document.getElementById('pay-chart').getContext('2d');
    charts.pay = new Chart(ctx, {
        type: 'doughnut',
        data: { labels: ['Efectivo', 'Tarjeta', 'Yape/Plin', 'Transferencia'], datasets: [{ data, backgroundColor: ['#10b981', '#3b82f6', '#5b3df5', '#f59e0b'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 14, boxWidth: 10, boxHeight: 10, color: '#6b7280' } } }, cutout: '65%' }
    });
}

function drawTypeChart(data) {
    if (charts.type) charts.type.destroy();
    const ctx = document.getElementById('type-chart').getContext('2d');
    charts.type = new Chart(ctx, {
        type: 'bar',
        data: { labels: ['Local', 'Para Llevar', 'Delivery'], datasets: [{ data, backgroundColor: ['#5b3df5', '#f59e0b', '#3b82f6'], borderRadius: 8, barThickness: 50 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 }, color: '#6b7280', stepSize: 1 } }, x: { grid: { display: false }, ticks: { font: { size: 12, weight: 600 }, color: '#1a1f36' } } } }
    });
}

document.querySelectorAll('.filter-pill').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('.filter-pill').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    activeRange = b.dataset.range;
    load();
}));

// Buscador del detalle por producto (filtra en cliente, sin recargar)
document.getElementById('dp-buscar')?.addEventListener('input', renderDetalleProductos);

load();
