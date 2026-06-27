<?php
// Drawer lateral movil — compartido por todas las vistas /movil/*.php
// Requiere $__user, $__permisos definidos arriba (los provee head.php).

$__activePage = $activePage ?? '';
$__userName  = trim(($__user['nombre'] ?? '') . ' ' . ($__user['apellidos'] ?? ''));
$__rolNombre = $__user['rol_nombre'] ?? '';

function _mHasPerm(string $cod, array $perms): bool {
    return in_array($cod, $perms, true);
}
?>
<div class="m-drawer-overlay" id="m-drawer-overlay" onclick="mCerrarDrawer()"></div>
<aside class="m-drawer" id="m-drawer">
    <div class="m-drawer-header">
        <div class="m-drawer-brand"><span class="ripa">RIPA</span><span class="pos">POS</span></div>
        <div class="m-drawer-user-name"><?php echo htmlspecialchars($__userName ?: 'Usuario'); ?></div>
        <div class="m-drawer-user-role"><?php echo htmlspecialchars($__rolNombre ?: '—'); ?></div>
    </div>
    <div class="m-drawer-links">
        <?php if (_mHasPerm('nuevaorden', $__permisos)): ?>
        <a href="nuevaorden" class="m-drawer-link <?php echo $__activePage === 'nuevaorden' ? 'active' : ''; ?>">
            <i class="fa-solid fa-utensils"></i> Nueva Orden
        </a>
        <?php endif; ?>
        <?php if (_mHasPerm('mesas', $__permisos)): ?>
        <a href="mesas" class="m-drawer-link <?php echo $__activePage === 'mesas' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chair"></i> Mesas
        </a>
        <?php endif; ?>
        <?php if (_mHasPerm('pedidos', $__permisos)): ?>
        <a href="pedidos" class="m-drawer-link <?php echo $__activePage === 'pedidos' ? 'active' : ''; ?>">
            <i class="fa-solid fa-receipt"></i> Pedidos
        </a>
        <?php endif; ?>
        <?php if (_mHasPerm('clientes', $__permisos)): ?>
        <a href="clientes" class="m-drawer-link <?php echo $__activePage === 'clientes' ? 'active' : ''; ?>">
            <i class="fa-regular fa-user"></i> Clientes
        </a>
        <?php endif; ?>
        <?php if (_mHasPerm('historial', $__permisos)): ?>
        <a href="historial" class="m-drawer-link <?php echo $__activePage === 'historial' ? 'active' : ''; ?>">
            <i class="fa-regular fa-clock"></i> Historial
        </a>
        <?php endif; ?>
        <?php if (_mHasPerm('caja', $__permisos)): ?>
        <a href="caja" class="m-drawer-link <?php echo $__activePage === 'caja' ? 'active' : ''; ?>">
            <i class="fa-solid fa-cash-register"></i> Caja
        </a>
        <?php endif; ?>
        <?php if (_mHasPerm('inventario', $__permisos)): ?>
        <a href="inventario" class="m-drawer-link <?php echo $__activePage === 'inventario' ? 'active' : ''; ?>">
            <i class="fa-solid fa-boxes-stacked"></i> Inventario
        </a>
        <?php endif; ?>
    </div>
    <div class="m-drawer-footer">
        <a href="#" onclick="mForzarDesktop();return false;" class="m-drawer-link">
            <i class="fa-solid fa-desktop"></i> Ver versión escritorio
        </a>
        <button type="button" onclick="logout()" class="m-logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
        </button>
    </div>
</aside>

<script>
function mAbrirDrawer()  {
    document.getElementById('m-drawer').classList.add('open');
    document.getElementById('m-drawer-overlay').classList.add('open');
}
function mCerrarDrawer() {
    document.getElementById('m-drawer').classList.remove('open');
    document.getElementById('m-drawer-overlay').classList.remove('open');
}
function mForzarDesktop() {
    // Cookie 1 año para forzar vista escritorio
    document.cookie = 'yapez_view=desktop; path=/; max-age=' + (60*60*24*365);
    window.location.reload();
}

// Fallback de viewport-height para navegadores que no soportan 100dvh.
// Setea --vh = 1% del innerHeight visible y lo recalcula al rotar / redimensionar.
(function () {
    function setVh() {
        document.documentElement.style.setProperty('--vh', (window.innerHeight * 0.01) + 'px');
    }
    setVh();
    window.addEventListener('resize', setVh);
    window.addEventListener('orientationchange', function () {
        // Pequeño delay para que el navegador termine de reflowear tras la rotación
        setTimeout(setVh, 120);
    });
    // En iOS, el scroll puede cambiar la altura por la URL bar
    window.addEventListener('scroll', function () {
        // Throttle simple: solo recalcular si cambió significativamente
        var nuevoVh = window.innerHeight * 0.01;
        var actual = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--vh')) || 0;
        if (Math.abs(nuevoVh - actual) > 0.5) setVh();
    }, { passive: true });
})();
</script>
