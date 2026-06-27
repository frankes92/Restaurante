/* impresoras.js - gestion de impresoras LAN */
let editIdImp = null;

async function loadImpresoras() {
    const rs = await Http.get('../ajax/impresora.php?op=listar');
    const cont = document.getElementById('imp-list');
    if (!Array.isArray(rs) || rs.length === 0) {
        cont.innerHTML = '<div class="empty-state" style="padding:40px;text-align:center;color:var(--text-muted);"><i class="fa-solid fa-print" style="font-size:48px;opacity:0.3;"></i><h3>Sin impresoras configuradas</h3><p>Agrega tu primera impresora térmica de red</p></div>';
        return;
    }
    const icons = { cocina: 'fa-fire', bar: 'fa-martini-glass', caja: 'fa-cash-register', otro: 'fa-print' };
    cont.innerHTML = rs.map(i => `
        <div class="imp-card">
            <div class="imp-icon ${i.tipo}"><i class="fa-solid ${icons[i.tipo] || 'fa-print'}"></i></div>
            <div>
                <div class="imp-nom">${escapeHtml(i.nombre)}
                    <span class="imp-estado ${Number(i.activa)===1 ? 'activa' : 'inactiva'}">${Number(i.activa)===1 ? 'ACTIVA' : 'INACTIVA'}</span>
                </div>
                <div class="imp-ip">${escapeHtml(i.ip)}:${i.puerto} · ${i.tipo.toUpperCase()} · ${i.ancho_cols} cols</div>
            </div>
            <div class="imp-actions">
                <button class="btn btn-sm" onclick="probarImpresora(${i.idimpresora})" title="Imprimir prueba"><i class="fa-solid fa-vial"></i></button>
                <button class="btn btn-sm" onclick="editImpresora(${i.idimpresora})" title="Editar"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm" onclick="delImpresora(${i.idimpresora})" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
    `).join('');
}

window.openAddImpresora = () => {
    editIdImp = null;
    document.getElementById('modal-imp-title').textContent = 'Nueva Impresora';
    document.getElementById('i-nombre').value = '';
    document.getElementById('i-ip').value     = '';
    document.getElementById('i-puerto').value = '9100';
    document.getElementById('i-tipo').value   = 'cocina';
    document.getElementById('i-ancho').value  = '48';
    document.getElementById('i-cortar').checked = true;
    document.getElementById('i-activa').checked = true;
    openModal('modal-imp');
};

window.editImpresora = async (id) => {
    const i = await Http.post('../ajax/impresora.php?op=mostrar', { idimpresora: id });
    if (!i) return;
    editIdImp = id;
    document.getElementById('modal-imp-title').textContent = 'Editar Impresora';
    document.getElementById('i-nombre').value = i.nombre || '';
    document.getElementById('i-ip').value     = i.ip     || '';
    document.getElementById('i-puerto').value = i.puerto || '9100';
    document.getElementById('i-tipo').value   = i.tipo   || 'cocina';
    document.getElementById('i-ancho').value  = i.ancho_cols || '48';
    document.getElementById('i-cortar').checked = Number(i.cortar_papel) === 1;
    document.getElementById('i-activa').checked = Number(i.activa) === 1;
    openModal('modal-imp');
};

window.saveImpresora = async () => {
    const payload = {
        idimpresora:  editIdImp || '',
        nombre:       document.getElementById('i-nombre').value.trim(),
        ip:           document.getElementById('i-ip').value.trim(),
        puerto:       document.getElementById('i-puerto').value.trim() || '9100',
        tipo:         document.getElementById('i-tipo').value,
        ancho_cols:   document.getElementById('i-ancho').value,
        cortar_papel: document.getElementById('i-cortar').checked ? '1' : '0',
        activa:       document.getElementById('i-activa').checked ? '1' : '0'
    };
    if (!payload.nombre || !payload.ip) {
        showToast('Nombre e IP son obligatorios', 'error');
        return;
    }
    const r = await Http.post('../ajax/impresora.php?op=guardar', payload);
    if (r.ok) {
        showToast(editIdImp ? 'Impresora actualizada' : 'Impresora registrada', 'success');
        closeModal('modal-imp');
        loadImpresoras();
    } else {
        showToast(r.msg || 'Error al guardar', 'error');
    }
};

window.delImpresora = async (id) => {
    if (!(await swalConfirm('¿Eliminar esta impresora?', { title: 'Eliminar', icon: 'warning', confirmText: 'Sí, eliminar' }))) return;
    const r = await Http.post('../ajax/impresora.php?op=eliminar', { idimpresora: id });
    if (r.ok) {
        showToast('Impresora eliminada', 'success');
        loadImpresoras();
    }
};

window.probarImpresora = async (id) => {
    const r = await Http.post('../ajax/impresora.php?op=prueba', { idimpresora: id });
    if (r.ok) {
        showToast('Prueba encolada — el bridge la imprimirá en segundos', 'success');
    } else {
        showToast(r.msg || 'Error', 'error');
    }
};

function escapeHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

$(function () { loadImpresoras(); });
