/* inventario.js (MÓVIL) — solo lectura: ver stock físico de productos */
let mInvCache = [];

function mEscInv(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c]); }

const M_SEM_LABEL = { verde: 'En nivel', amarillo: 'Stock bajo', rojo: 'Agotado' };

async function mCargarInventario() {
    const search = (document.getElementById('m-inv-search').value || '').trim();
    const r = await Http.get('../ajax/producto.php?op=inventario' + (search ? '&search=' + encodeURIComponent(search) : ''));
    if (!r || !r.ok) { document.getElementById('m-inv-sub').textContent = 'Error'; return; }
    mInvCache = r.data || [];
    const res = r.resumen || {};
    document.getElementById('m-inv-total').textContent = res.total || 0;
    document.getElementById('m-inv-valor').textContent = fmt.money(res.valor_total || 0);
    document.getElementById('m-inv-bajo').textContent  = res.bajos || 0;
    document.getElementById('m-inv-agot').textContent  = res.agotados || 0;
    document.getElementById('m-inv-sub').textContent   = (res.total || 0) + ' productos';
    mRenderInventario();
}

function mRenderInventario() {
    const cont = document.getElementById('m-inv-lista');
    if (!mInvCache.length) {
        cont.innerHTML = '<div class="m-empty"><i class="fa-solid fa-boxes-stacked"></i><h3>Sin productos con inventario</h3><p>Activa "Controlar inventario" en un producto desde Productos.</p></div>';
        return;
    }
    cont.innerHTML = mInvCache.map(p => {
        const sem   = p.semaforo;
        const stock = Number(p.stock);
        const min   = Number(p.stock_minimo);
        const pres  = (p.presentacion && p.presentacion.toLowerCase() !== 'normal') ? ' · ' + mEscInv(p.presentacion) : '';
        const valor = Number(p.valor_stock) || (stock * Number(p.precio));
        return `
            <div class="m-inv-card">
                <div>
                    <div class="m-inv-nom">${mEscInv(p.producto_nombre)}${pres}</div>
                    <div class="m-inv-sub">${mEscInv(p.categoria_nombre || '—')} · ${fmt.money(p.precio)} c/u · Valor: <b>${fmt.money(valor)}</b></div>
                    <div class="m-inv-min">Mínimo: ${min}</div>
                </div>
                <div class="m-inv-stockbox">
                    <div class="m-inv-stock ${sem}">${stock}</div>
                    <span class="m-inv-estado ${sem}">${M_SEM_LABEL[sem] || ''}</span>
                </div>
                <button class="m-inv-mov" onclick="mMovimiento(${p.idprecio})"><i class="fa-solid fa-right-left"></i> Registrar movimiento</button>
            </div>`;
    }).join('');
}

// Registrar movimiento de stock (entrada / salida / ajuste) desde el celular
window.mMovimiento = async (idprecio) => {
    const p = mInvCache.find(x => Number(x.idprecio) === Number(idprecio));
    if (!p) return;
    const pres = (p.presentacion && p.presentacion.toLowerCase() !== 'normal') ? ' · ' + p.presentacion : '';
    const nombre = (p.producto_nombre || '') + pres;

    const html =
        '<div style="text-align:left;font-size:13px;">' +
        '<div style="margin-bottom:10px;color:#6b7280;">Producto: <b style="color:#1f2937;">' + mEscInv(nombre) + '</b><br>Stock actual: <b style="color:#5b3df5;">' + Number(p.stock) + '</b></div>' +
        '<label style="font-size:12px;font-weight:700;color:#6b7280;display:block;margin-bottom:4px;">Operación</label>' +
        '<select id="m-mov-tipo" class="swal2-input" style="margin:0 0 10px;width:100%;">' +
            '<option value="entrada">Entrada (sumar — compra/ingreso)</option>' +
            '<option value="salida">Salida (restar — merma/consumo)</option>' +
            '<option value="ajuste">Ajuste (corrección)</option>' +
        '</select>' +
        '<label style="font-size:12px;font-weight:700;color:#6b7280;display:block;margin-bottom:4px;">Cantidad</label>' +
        '<input id="m-mov-cant" type="number" inputmode="numeric" min="0" step="1" class="swal2-input" style="margin:0 0 10px;width:100%;" placeholder="0">' +
        '<label style="font-size:12px;font-weight:700;color:#6b7280;display:block;margin-bottom:4px;">Nota (opcional)</label>' +
        '<input id="m-mov-nota" type="text" maxlength="200" class="swal2-input" style="margin:0;width:100%;" placeholder="Ej. compra proveedor, conteo físico…">' +
        '</div>';

    const res = await Swal.fire({
        title: 'Movimiento de stock',
        html: html,
        showCancelButton: true,
        confirmButtonText: 'Aplicar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#5b3df5',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusConfirm: false,
        preConfirm: () => {
            const tipo = document.getElementById('m-mov-tipo').value;
            const cant = parseFloat(document.getElementById('m-mov-cant').value) || 0;
            const nota = document.getElementById('m-mov-nota').value.trim();
            if (cant <= 0) { Swal.showValidationMessage('Ingresa una cantidad válida'); return false; }
            return { tipo, cant, nota };
        }
    });
    if (!res.isConfirmed || !res.value) return;

    const r = await Http.post('../ajax/producto.php?op=moverStock', {
        idprecio: idprecio,
        tipo: res.value.tipo,
        cantidad: res.value.cant,
        nota: res.value.nota
    });
    if (r && r.ok) {
        showToast('Stock actualizado', 'success');
        mCargarInventario();
    } else {
        showToast((r && r.msg) || 'No se pudo actualizar', 'error');
    }
};

let mInvTimer = null;
document.getElementById('m-inv-search').addEventListener('input', () => {
    clearTimeout(mInvTimer);
    mInvTimer = setTimeout(mCargarInventario, 300);
});

mCargarInventario();
