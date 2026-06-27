<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('usuarios');

$activePage = 'usuarios';
$pageTitle  = 'PUERTO HABANA POS — Usuarios';
require __DIR__ . '/template/head.php';
?>
<style>
.usr-tabs { display:flex; gap:4px; margin-bottom: 18px; }
.usr-tab { padding: 10px 18px; border-radius: 10px; background: var(--bg-white); border: 1px solid var(--border); cursor:pointer; font-weight:600; font-size:13px; font-family:inherit; color:var(--text-dark); }
.usr-tab.active { background: var(--primary); color:#fff; border-color: var(--primary); }
.perm-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
.perm-item { display:flex; align-items:center; gap:8px; padding:10px 12px; background: var(--bg-light); border-radius: 8px; font-size:13px; }
.perm-group-title { font-size: 11px; font-weight:700; color: var(--text-muted); letter-spacing: 0.6px; margin: 14px 0 6px; text-transform: uppercase; }
.user-avatar-tbl { width:34px; height:34px; border-radius:50%; background: linear-gradient(135deg, var(--primary), #8b5cf6); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-weight:600; font-size:12px; flex-shrink:0; }
.user-cell { display:flex; align-items:center; gap:12px; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Usuarios y Permisos</div>
                <div class="page-subtitle">Administra usuarios, roles y permisos de PUERTO HABANA</div>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn btn-primary" onclick="openAddUser()"><i class="fa-solid fa-user-plus"></i> Nuevo Usuario</button>
            </div>
        </div>

        <div class="page-content">
            <div class="usr-tabs">
                <button class="usr-tab active" data-tab="usuarios">Usuarios</button>
                <button class="usr-tab" data-tab="roles">Roles y Permisos</button>
            </div>

            <!-- TAB USUARIOS -->
            <div class="tab-content" id="tab-usuarios">
                <div class="table-container" style="padding:16px;">
                    <table id="tbl-usuarios" class="data-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Login</th>
                                <th>Rol</th>
                                <th>Email</th>
                                <th>Último Acceso</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <!-- TAB ROLES -->
            <div class="tab-content" id="tab-roles" style="display:none;">
                <div class="card">
                    <div class="card-title">Permisos por rol</div>
                    <div style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;" id="roles-pills"></div>
                    <div id="role-permisos"></div>
                    <div style="margin-top:14px;text-align:right;">
                        <button class="btn btn-primary" onclick="guardarPermisosRol()"><i class="fa-solid fa-save"></i> Guardar permisos del rol</button>
                    </div>
                </div>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<!-- MODAL USUARIO -->
<div class="modal-overlay" id="modal-user">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <div class="modal-title" id="modal-user-title">Nuevo Usuario</div>
            <button class="modal-close" onclick="closeModal('modal-user')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="input-group"><label class="input-label">Nombre *</label><input id="u-nombre" class="input-field"></div>
                <div class="input-group"><label class="input-label">Apellidos</label><input id="u-apellidos" class="input-field"></div>
                <div class="input-group"><label class="input-label">Login *</label><input id="u-login" class="input-field"></div>
                <div class="input-group"><label class="input-label">Rol *</label><select id="u-rol" class="input-field"></select></div>
                <div class="input-group"><label class="input-label">Documento</label><input id="u-doc" class="input-field"></div>
                <div class="input-group"><label class="input-label">Teléfono</label><input id="u-tel" class="input-field"></div>
                <div class="input-group" style="grid-column:span 2;"><label class="input-label">Email</label><input type="email" id="u-email" class="input-field"></div>
                <div class="input-group" style="grid-column:span 2;">
                    <label class="input-label">Contraseña <span id="u-clave-hint" style="color:var(--text-muted);font-weight:400;">(dejar vacío para no cambiar)</span></label>
                    <input type="password" id="u-clave" class="input-field" autocomplete="new-password">
                </div>
            </div>
            <div id="u-overrides" style="margin-top:16px;display:none;">
                <div class="perm-group-title">Permisos override (sobre el rol)</div>
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">
                    Marca permisos que deben <b style="color:var(--green);">añadirse</b> o <b style="color:var(--red);">quitarse</b> respecto del rol del usuario.
                </div>
                <div id="u-overrides-list"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-user')">Cancelar</button>
            <button class="btn btn-primary" onclick="saveUser()">Guardar</button>
        </div>
    </div>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/usuarios.js?v=<?php echo filemtime(__DIR__ . '/scripts/usuarios.js'); ?>"></script>
</body>
</html>
