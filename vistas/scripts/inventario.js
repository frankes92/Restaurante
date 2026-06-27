/* inventario.js — listado de stock con semáforo + movimientos */
let invCache = [];

function escapeHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c]); }

const SEM_LABEL = { verde: 'En nivel', amarillo: 'Stock bajo', rojo: 'Agotado' };
const SEM_COLOR = { verde: '#10b981', amarillo: '#f59e0b', rojo: '#ef4444' };

async function cargarInventario() {
    const search = document.getElementById('inv-search').value.trim();
    const r = await Http.get('../ajax/producto.php?op=inventario' + (search ? '&search=' + encodeURIComponent(search) : ''));
    if (!r || !r.ok) return;
    invCache = r.data || [];
    const res = r.resumen || {};
    document.getElementById('inv-total').textContent = res.total   || 0;
    document.getElementById('inv-valor').textContent = fmt.money(res.valor_total || 0);
    document.getElementById('inv-ok').textContent    = res.ok      || 0;
    document.getElementById('inv-bajo').textContent  = res.bajos   || 0;
    document.getElementById('inv-agot').textContent  = res.agotados|| 0;
    render();
}

window.descargarInventarioExcel = () => {
    window.location.href = '../ajax/producto.php?op=excelInventario';
};

function render() {
    const tb = document.getElementById('inv-body');
    if (!invCache.length) {
        tb.innerHTML = '<tr><td colspan="10" style="text-align:center;color:var(--text-muted);padding:24px;">' +
            'No hay productos con control de inventario.<br><small>Activa el switch "Controlar inventario" en un producto desde el módulo Productos.</small></td></tr>';
        return;
    }
    tb.innerHTML = invCache.map(p => {
        const stock = Number(p.stock);
        const min   = Number(p.stock_minimo);
        const sem   = p.semaforo;
        const valor = Number(p.valor_stock) || (stock * Number(p.precio));
        const vend  = Number(p.vendido) || 0;
        const cort  = Number(p.cortesia) || 0;
        // Nombre: "Producto · Presentación" (la presentación solo si no es 'Normal')
        const pres = (p.presentacion && p.presentacion.toLowerCase() !== 'normal')
            ? ' · ' + escapeHtml(p.presentacion) : '';
        const cortHtml = cort > 0
            ? `<span style="display:inline-block;background:#dcfce7;color:#15803d;font-weight:700;padding:1px 9px;border-radius:10px;"><i class="fa-solid fa-gift" style="font-size:10px;"></i> ${cort}</span>`
            : '<span style="color:#9ca3af;">0</span>';
        return `
            <tr>
                <td><b>${escapeHtml(p.producto_nombre)}</b>${pres ? `<span style="color:var(--primary);font-weight:700;">${pres}</span>` : ''}<br><code style="font-size:11px;">${escapeHtml(p.codigo)}</code></td>
                <td>${escapeHtml(p.categoria_nombre || '—')}</td>
                <td><span class="sem ${sem}"><span class="dot"></span> ${SEM_LABEL[sem]}</span></td>
                <td style="text-align:right;"><span class="stock-num" style="color:${SEM_COLOR[sem]};">${stock}</span></td>
                <td style="text-align:right;">${fmt.money(p.precio)}</td>
                <td style="text-align:right;font-weight:700;color:#0e7c8b;">${fmt.money(valor)}</td>
                <td style="text-align:center;font-weight:700;">${vend}</td>
                <td style="text-align:center;">${cortHtml}</td>
                <td style="text-align:center;">${min}</td>
                <td style="text-align:right;white-space:nowrap;">
                    <button class="btn btn-sm" onclick="abrirMov(${p.idprecio})"><i class="fa-solid fa-right-left"></i> Movimiento</button>
                </td>
            </tr>`;
    }).join('');
}

window.abrirMov = (idprecio) => {
    const p = invCache.find(x => Number(x.idprecio) === Number(idprecio));
    if (!p) return;
    document.getElementById('mov-idprod').value = idprecio;
    const pres = (p.presentacion && p.presentacion.toLowerCase() !== 'normal') ? ' · ' + p.presentacion : '';
    document.getElementById('mov-prod-nombre').textContent = p.producto_nombre + pres;
    document.getElementById('mov-stock-actual').textContent = Number(p.stock);
    document.getElementById('mov-tipo').value = 'entrada';
    document.getElementById('mov-cant').value = '';
    document.getElementById('mov-nota').value = '';
    openModal('modal-mov');
};

window.guardarMovimiento = async () => {
    const idprecio = document.getElementById('mov-idprod').value;
    const tipo = document.getElementById('mov-tipo').value;
    const cant = parseFloat(document.getElementById('mov-cant').value);
    const nota = document.getElementById('mov-nota').value.trim();
    if (!cant || cant <= 0) { showToast('Ingresa una cantidad válida', 'error'); return; }
    const r = await Http.post('../ajax/producto.php?op=moverStock', { idprecio, tipo, cantidad: cant, nota });
    if (r && r.ok) {
        showToast('Stock actualizado', 'success');
        closeModal('modal-mov');
        cargarInventario();
    } else {
        showToast((r && r.msg) || 'No se pudo actualizar', 'error');
    }
};

let invTimer = null;
document.getElementById('inv-search').addEventListener('input', () => {
    clearTimeout(invTimer);
    invTimer = setTimeout(cargarInventario, 300);
});

cargarInventario();
