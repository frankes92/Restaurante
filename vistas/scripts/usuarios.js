/* usuarios.js - DataTables server-side + roles */

let dt = null;
let editingUserId = null;
let rolesCache = [];
let permisosCache = [];
let permisosPorGrupo = {};
let activeRoleId = null;
let rolePermisosCache = [];

async function init() {
    await Promise.all([loadRoles(), loadPermisos()]);
    initDataTable();
    bindTabs();
}

async function loadRoles() {
    rolesCache = await Http.get('../ajax/rol.php?op=listar');
    renderRolePills();
    renderRolSelectInModal();
}

async function loadPermisos() {
    permisosCache    = await Http.get('../ajax/permiso.php?op=listar');
    permisosPorGrupo = await Http.get('../ajax/permiso.php?op=listarPorGrupo');
}

function bindTabs() {
    document.querySelectorAll('.usr-tab').forEach(t => t.addEventListener('click', () => {
        document.querySelectorAll('.usr-tab').forEach(x => x.classList.remove('active'));
        t.classList.add('active');
        document.getElementById('tab-usuarios').style.display = t.dataset.tab === 'usuarios' ? '' : 'none';
        document.getElementById('tab-roles').style.display    = t.dataset.tab === 'roles'    ? '' : 'none';
        if (t.dataset.tab === 'roles' && !activeRoleId && rolesCache.length) {
            seleccionarRol(rolesCache[0].idrol);
        }
    }));
}

function initDataTable() {
    dt = $('#tbl-usuarios').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '../ajax/usuario.php?op=datatable',
            type: 'POST'
        },
        columns: [
            { data: null, orderable: false, render: row => {
                const ini = ((row.nombre || '').charAt(0) + (row.apellidos || '').charAt(0)).toUpperCase();
                return `<div class="user-cell">
                    <div class="user-avatar-tbl">${ini || 'U'}</div>
                    <div>
                        <div style="font-weight:600;">${row.nombre || ''} ${row.apellidos || ''}</div>
                        <div style="font-size:11px;color:var(--text-muted);">${row.email || '—'}</div>
                    </div>
                </div>`;
            }},
            { data: 'nombre' },
            { data: 'login', render: v => `<b>${v}</b>` },
            { data: 'rol_nombre', render: v => `<span class="badge badge-purple">${v || '—'}</span>` },
            { data: 'email', defaultContent: '—' },
            { data: 'ultimo_acceso', render: v => v
                ? `<span style="font-size:12px;color:var(--text-muted);">${fmt.datetime(v)}</span>`
                : `<span style="color:var(--text-muted);">Nunca</span>` },
            { data: 'condicion', render: v => Number(v) === 1
                ? '<span class="badge badge-green">Activo</span>'
                : '<span class="badge badge-red">Inactivo</span>' },
            { data: null, orderable: false, render: row => `
                <div style="text-align:right;">
                    <button class="btn btn-sm btn-icon" onclick="editUser(${row.idusuario})"><i class="fa-solid fa-pen"></i></button>
                    ${Number(row.condicion) === 1
                        ? `<button class="btn btn-sm btn-icon" onclick="toggleUser(${row.idusuario}, 0)"><i class="fa-solid fa-ban"></i></button>`
                        : `<button class="btn btn-sm btn-icon" onclick="toggleUser(${row.idusuario}, 1)"><i class="fa-solid fa-check"></i></button>`}
                </div>`
            }
        ],
        columnDefs: [{ targets: 1, visible: false }],
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copyHtml5',  text: '<i class="fa-solid fa-copy"></i> Copiar', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'excelHtml5', text: '<i class="fa-solid fa-file-excel"></i> Excel', title: 'Usuarios', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'csvHtml5',   text: '<i class="fa-solid fa-file-csv"></i> CSV', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'pdfHtml5',   text: '<i class="fa-solid fa-file-pdf"></i> PDF', title: 'Usuarios', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'print',      text: '<i class="fa-solid fa-print"></i> Imprimir', exportOptions: { columns: ':not(:last-child)' } }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
    });
}

window.openAddUser = () => {
    editingUserId = null;
    document.getElementById('modal-user-title').textContent = 'Nuevo Usuario';
    ['u-nombre','u-apellidos','u-login','u-doc','u-tel','u-email','u-clave'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('u-clave-hint').style.display = 'none';
    document.getElementById('u-overrides').style.display = 'none';
    openModal('modal-user');
};

window.editUser = async (id) => {
    const u = await Http.post('../ajax/usuario.php?op=mostrar', { idusuario: id });
    if (!u) return;
    editingUserId = id;
    document.getElementById('modal-user-title').textContent = 'Editar Usuario';
    document.getElementById('u-nombre').value     = u.nombre || '';
    document.getElementById('u-apellidos').value  = u.apellidos || '';
    document.getElementById('u-login').value      = u.login || '';
    document.getElementById('u-doc').value        = u.num_documento || '';
    document.getElementById('u-tel').value        = u.telefono || '';
    document.getElementById('u-email').value      = u.email || '';
    document.getElementById('u-clave').value      = '';
    document.getElementById('u-rol').value        = u.idrol || '';
    document.getElementById('u-clave-hint').style.display = '';

    const ovs = await Http.post('../ajax/usuario.php?op=overrides', { idusuario: id });
    renderOverrides(u.idrol, ovs || []);
    document.getElementById('u-overrides').style.display = '';

    openModal('modal-user');
};

function renderOverrides(idrol, overrides) {
    const grants  = new Set(overrides.filter(o => o.tipo === 'grant').map(o => Number(o.idpermiso)));
    const revokes = new Set(overrides.filter(o => o.tipo === 'revoke').map(o => Number(o.idpermiso)));
    const cont = document.getElementById('u-overrides-list');
    cont.innerHTML = Object.keys(permisosPorGrupo).map(grupo => `
        <div class="perm-group-title">${grupo}</div>
        <div class="perm-grid">
            ${permisosPorGrupo[grupo].map(p => `
                <label class="perm-item" style="justify-content:space-between;">
                    <span>${p.nombre}</span>
                    <span style="display:flex;gap:6px;">
                        <label title="Conceder este permiso al usuario"><input type="checkbox" class="ov-grant"  data-id="${p.idpermiso}" ${grants.has(Number(p.idpermiso)) ? 'checked':''}> +</label>
                        <label title="Revocar este permiso al usuario"><input type="checkbox" class="ov-revoke" data-id="${p.idpermiso}" ${revokes.has(Number(p.idpermiso)) ? 'checked':''}> −</label>
                    </span>
                </label>
            `).join('')}
        </div>
    `).join('');
}

function renderRolSelectInModal() {
    const sel = document.getElementById('u-rol');
    sel.innerHTML = '<option value="">— Sin rol —</option>' +
        rolesCache.map(r => `<option value="${r.idrol}">${r.nombre}</option>`).join('');
}

window.saveUser = async () => {
    const nombre = document.getElementById('u-nombre').value.trim();
    const login  = document.getElementById('u-login').value.trim();
    const idrol  = document.getElementById('u-rol').value;
    if (!nombre || !login) { showToast('Nombre y login obligatorios', 'error'); return; }

    const payload = {
        idusuario:      editingUserId || '',
        idrol:          idrol,
        nombre:         nombre,
        apellidos:      document.getElementById('u-apellidos').value.trim(),
        login:          login,
        clave:          document.getElementById('u-clave').value,
        num_documento:  document.getElementById('u-doc').value.trim(),
        telefono:       document.getElementById('u-tel').value.trim(),
        email:          document.getElementById('u-email').value.trim()
    };
    const r = await Http.post('../ajax/usuario.php?op=guardaryeditar', payload);
    if (!r.ok) { showToast(r.msg || 'Error al guardar', 'error'); return; }

    const idusuario = editingUserId || r.idusuario;

    if (editingUserId) {
        const grants  = Array.from(document.querySelectorAll('.ov-grant:checked')).map(el => Number(el.dataset.id));
        const revokes = Array.from(document.querySelectorAll('.ov-revoke:checked')).map(el => Number(el.dataset.id));
        await Http.post('../ajax/usuario.php?op=setOverrides', { idusuario, grants, revokes });
    }

    showToast(editingUserId ? 'Usuario actualizado' : 'Usuario creado', 'success');
    closeModal('modal-user');
    dt.ajax.reload(null, false);
};

window.toggleUser = async (id, activar) => {
    const op = activar === 1 ? 'activar' : 'desactivar';
    const msg = activar === 1 ? '¿Activar este usuario?' : '¿Desactivar este usuario?';
    if (!(await swalConfirm(msg, { icon: activar === 1 ? 'question' : 'warning' }))) return;
    const r = await Http.post('../ajax/usuario.php?op=' + op, { idusuario: id });
    if (r.ok) { showToast('Estado actualizado', 'success'); dt.ajax.reload(null, false); }
};

// ---- Roles y permisos ----
function renderRolePills() {
    const cont = document.getElementById('roles-pills');
    cont.innerHTML = rolesCache.map(r => `
        <button class="btn ${activeRoleId === r.idrol ? 'btn-primary' : ''}" onclick="seleccionarRol(${r.idrol})">
            <i class="fa-solid fa-user-shield"></i> ${r.nombre}
        </button>
    `).join('');
}

window.seleccionarRol = async (idrol) => {
    activeRoleId = Number(idrol);
    renderRolePills();
    rolePermisosCache = (await Http.get('../ajax/rol.php?op=permisos&idrol=' + idrol)).map(p => Number(p.idpermiso));
    const cont = document.getElementById('role-permisos');
    cont.innerHTML = Object.keys(permisosPorGrupo).map(grupo => `
        <div class="perm-group-title">${grupo}</div>
        <div class="perm-grid">
            ${permisosPorGrupo[grupo].map(p => `
                <label class="perm-item">
                    <input type="checkbox" class="rp-chk" data-id="${p.idpermiso}" ${rolePermisosCache.includes(Number(p.idpermiso)) ? 'checked' : ''}>
                    ${p.nombre}
                </label>
            `).join('')}
        </div>
    `).join('');
};

window.guardarPermisosRol = async () => {
    if (!activeRoleId) return;
    const ids = Array.from(document.querySelectorAll('.rp-chk:checked')).map(el => Number(el.dataset.id));
    const r = await Http.post('../ajax/rol.php?op=setPermisos', { idrol: activeRoleId, permisos: ids });
    if (r.ok) showToast('Permisos del rol actualizados', 'success');
    else showToast('Error al guardar', 'error');
};

$(function () { init(); });
