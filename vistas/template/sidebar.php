<?php
require_once __DIR__ . "/../../config/auth.php";
$activePage = $activePage ?? '';
$user = currentUser();
$userIniciales = $user ? $user['iniciales'] : '?';
$userNombre    = $user ? trim(($user['nombre'] ?? '') . ' ' . ($user['apellidos'] ?? '')) : 'Sin sesión';
$userRol       = $user ? ($user['rol_nombre'] ?? '') : '';

// Logo y nombre de empresa (si existen) — el head ya los carga, aqui los re-leemos por si esta sidebar.php standalone
if (!isset($__empresa)) {
    require_once __DIR__ . "/../../modelos/Empresa.php";
    $__empresa = (new Empresa())->mostrar(1) ?: [];
}
$__sidebarLogo   = $__empresa['logo'] ?? '';
$__sidebarNombre = $__empresa['nombre_comercial'] ?? $__empresa['razon_social'] ?? 'PUERTO HABANA POS';

$navItems = [
    ['id' => 'nuevaorden', 'icon' => 'fa-regular fa-file-lines',         'label' => 'Nueva Orden', 'href' => 'nuevaorden', 'permiso' => 'nuevaorden'],
    ['id' => 'mesas',      'icon' => 'fa-solid fa-table-cells-large',    'label' => 'Mesas',       'href' => 'mesas',      'permiso' => 'mesas'],
    ['id' => 'pedidos',    'icon' => 'fa-solid fa-receipt',              'label' => 'Pedidos',     'href' => 'pedidos',    'permiso' => 'pedidos'],
    ['id' => 'clientes',   'icon' => 'fa-regular fa-user',               'label' => 'Clientes',    'href' => 'clientes',   'permiso' => 'clientes'],
    ['id' => 'historial',  'icon' => 'fa-regular fa-clock',              'label' => 'Historial',   'href' => 'historial',  'permiso' => 'historial'],
    ['id' => 'caja',       'icon' => 'fa-solid fa-cash-register',        'label' => 'Caja',        'href' => 'caja',       'permiso' => 'caja'],
    ['id' => 'reportes',   'icon' => 'fa-regular fa-chart-bar',          'label' => 'Reportes',    'href' => 'reportes',   'permiso' => 'reportes'],
    // Seccion Catalogo
    ['__divider' => 'CATÁLOGO'],
    ['id' => 'categorias', 'icon' => 'fa-solid fa-tags',                 'label' => 'Categorías',  'href' => 'categorias', 'permiso' => 'productos'],
    ['id' => 'productos',  'icon' => 'fa-solid fa-utensils',             'label' => 'Productos',   'href' => 'productos',  'permiso' => 'productos'],
    ['id' => 'inventario', 'icon' => 'fa-solid fa-boxes-stacked',        'label' => 'Inventario',  'href' => 'inventario', 'permiso' => 'inventario'],
    // Seccion SUNAT
    ['__divider' => 'SUNAT'],
    ['id' => 'comprobantes_electronicos', 'icon' => 'fa-solid fa-file-invoice', 'label' => 'Comprobantes',  'href' => 'comprobantes_electronicos', 'permiso' => 'comprobantes_sunat'],
    ['id' => 'resumenes_sunat',           'icon' => 'fa-solid fa-list-check',    'label' => 'Resúmenes',     'href' => 'resumenes_sunat',           'permiso' => 'resumen_boletas'],
    ['id' => 'empresa_config',            'icon' => 'fa-solid fa-building',     'label' => 'Empresa',       'href' => 'empresa_config',            'permiso' => 'config_empresa'],
    ['id' => 'certificado',               'icon' => 'fa-solid fa-shield-halved','label' => 'Certificado',   'href' => 'certificado',               'permiso' => 'config_certificado'],
    ['id' => 'numeracion',                'icon' => 'fa-solid fa-hashtag',      'label' => 'Numeración',    'href' => 'numeracion',                'permiso' => 'config_numeracion'],
    // WhatsApp
    ['__divider' => 'WHATSAPP'],
    ['id' => 'whatsapp_plantillas', 'icon' => 'fa-brands fa-whatsapp',  'label' => 'Plantillas',     'href' => 'whatsapp_plantillas', 'permiso' => 'whatsapp_plantillas'],
    ['id' => 'whatsapp_envios',     'icon' => 'fa-solid fa-bullhorn',   'label' => 'Envío masivo',   'href' => 'whatsapp_envios',     'permiso' => 'whatsapp_masivo'],
    // Admin
    ['__divider' => 'ADMIN'],
    ['id' => 'usuarios',   'icon' => 'fa-solid fa-user-shield',          'label' => 'Usuarios',    'href' => 'usuarios',   'permiso' => 'usuarios'],
    ['id' => 'impresoras', 'icon' => 'fa-solid fa-print',                'label' => 'Impresoras',  'href' => 'impresoras', 'permiso' => 'impresoras'],
    ['id' => 'licencia',   'icon' => 'fa-solid fa-key',                  'label' => 'Licencia',    'href' => 'licencia',   'permiso' => 'config_licencia'],
];
?>
<aside class="sidebar">
    <button type="button" class="sidebar-collapse-btn" onclick="toggleSidebar()" title="Ocultar menú">
        <i class="fa-solid fa-chevron-left"></i>
    </button>
    <a href="nuevaorden" class="logo">
        <?php if (!empty($__sidebarLogo)): ?>
            <div class="logo-icon" style="background:transparent;width:48px;height:48px;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px;">
                <img src="../<?php echo h($__sidebarLogo); ?>" alt="logo" style="max-width:100%;max-height:100%;">
            </div>
        <?php else: ?>
            <div class="logo-icon">🍲</div>
        <?php endif; ?>
        <div class="logo-text" style="font-size:14px;font-weight:700;letter-spacing:-0.3px;display:flex;align-items:center;justify-content:center;gap:6px;color:#fff;white-space:nowrap;width:100%;text-align:center;padding:0 8px;"><span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo h($__sidebarNombre); ?></span><span class="logo-pos">V1.2.0</span></div>
    </a>

    <nav class="nav">
        <?php foreach ($navItems as $it): ?>
            <?php if (isset($it['__divider'])): ?>
                <div style="font-size:10px;font-weight:700;color:#5b7090;letter-spacing:1px;padding:14px 14px 4px;text-transform:uppercase;"><?php echo $it['__divider']; ?></div>
            <?php elseif (hasPermiso($it['permiso'])): ?>
                <a href="<?php echo $it['href']; ?>"
                   class="nav-item <?php echo $it['id'] === $activePage ? 'active' : ''; ?>">
                    <i class="<?php echo $it['icon']; ?>"></i> <?php echo $it['label']; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?php echo htmlspecialchars($userIniciales); ?></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($userNombre); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($userRol); ?></div>
            </div>
        </div>
        <div class="branch-selector">
            <div>
                <div class="branch-name"><span class="bn-ripa">RIPA</span> <span class="bn-pos">POS</span></div>
                <div class="branch-status">En línea</div>
            </div>
            <i class="fa-solid fa-chevron-down" style="font-size:11px;color:#9ca3af;"></i>
        </div>
        <a href="javascript:void(0)" class="logout" onclick="forzarMovil()" style="display:none;" id="link-ver-movil"><i class="fa-solid fa-mobile-screen-button"></i> Ver versión móvil</a>
        <a href="javascript:void(0)" class="logout" onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
    </div>
</aside>
<script>
// Si el usuario forzo desktop (cookie yapez_view=desktop), mostrarle como volver a movil
(function() {
    if (document.cookie.includes('yapez_view=desktop')) {
        var el = document.getElementById('link-ver-movil');
        if (el) el.style.display = '';
    }
})();
function forzarMovil() {
    document.cookie = 'yapez_view=movil; path=/; max-age=' + (60*60*24*365);
    window.location.reload();
}
</script>
<button type="button" class="sidebar-toggle-floating" onclick="toggleSidebar()" title="Mostrar menú">
    <i class="fa-solid fa-bars"></i>
</button>
