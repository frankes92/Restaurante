<?php
require_once __DIR__ . "/../../modelos/Caja.php";
require_once __DIR__ . "/../../config/auth.php";

$cajaFooter = new Caja();
$sesionFooter = $cajaFooter->sesionActual();
$cajaAbierta  = $sesionFooter && !empty($sesionFooter['idsesion']);
$user = currentUser();
$licFooter = function_exists('licenciaInfo') ? licenciaInfo() : null;
?>
<?php if ($licFooter && $licFooter['avisar']): ?>
<div style="background:#fef3c7;color:#92400e;border-bottom:1px solid #f59e0b;padding:8px 16px;font-size:12px;text-align:center;font-weight:600;">
    <i class="fa-solid fa-triangle-exclamation"></i>
    La licencia del sistema vence en <?php echo max(0, (int)$licFooter['dias_restantes']); ?> día(s) (<?php echo date('d/m/Y', strtotime($licFooter['fecha_vencimiento'])); ?>). Contacte al proveedor para renovar.
</div>
<?php endif; ?>
<?php if (!$cajaAbierta && $user && in_array(basename($_SERVER['SCRIPT_NAME']), ['nuevaorden.php','pedidos.php','mesas.php'], true)): ?>
<div style="background:#fee2e2;color:#991b1b;border-bottom:1px solid #ef4444;padding:8px 16px;font-size:12px;text-align:center;font-weight:600;">
    <i class="fa-solid fa-circle-exclamation"></i>
    No hay caja abierta. <a href="caja" style="color:#991b1b;text-decoration:underline;font-weight:700;">Abre una sesión de caja</a> para poder cobrar.
</div>
<?php endif; ?>
<div class="footer">
    <?php if ($cajaAbierta): ?>
        <div class="footer-item">Caja: <?php echo htmlspecialchars($sesionFooter['caja_codigo']); ?></div>
        <div class="footer-divider"></div>
        <div class="footer-item">Apertura: <?php echo date('d/m/Y H:i', strtotime($sesionFooter['fecha_apertura'])); ?></div>
        <div class="footer-divider"></div>
        <div class="footer-item">Turno: <?php echo htmlspecialchars($sesionFooter['turno']); ?></div>
    <?php else: ?>
        <div class="footer-item" style="color:#ef4444;font-weight:700;"><i class="fa-solid fa-lock"></i> CAJA CERRADA</div>
        <div class="footer-divider"></div>
        <div class="footer-item"><a href="caja" style="color:var(--primary);text-decoration:underline;">Abrir caja</a></div>
    <?php endif; ?>
    <?php if ($user): ?>
    <div class="footer-divider"></div>
    <div class="footer-item">Usuario: <b><?php echo htmlspecialchars($user['login']); ?></b> (<?php echo htmlspecialchars($user['rol_nombre']); ?>)</div>
    <?php endif; ?>
    <div class="footer-right">
        <div class="footer-item">Conexión <span class="online"><i class="fa-solid fa-wifi"></i> En línea</span></div>
        <div class="footer-divider"></div>
        <div class="footer-item">
            <i class="fa-regular fa-clock"></i>
            <span id="footer-clock"><?php echo date('H:i'); ?></span>
            &nbsp; <span id="footer-date"><?php echo date('d/m/Y'); ?></span>
        </div>
    </div>
</div>

<!-- Créditos del sistema (visible en todas las páginas autenticadas) -->
<div class="creditos-bar" style="position:fixed!important;bottom:0!important;left:0!important;right:0!important;width:100vw!important;height:30px!important;background:#0d1b2e!important;color:rgba(255,255,255,0.6)!important;display:flex!important;align-items:center!important;justify-content:center!important;text-align:center!important;font-size:11px!important;letter-spacing:0.2px!important;border-top:1px solid rgba(255,255,255,0.05)!important;z-index:99999!important;padding:0 16px!important;visibility:visible!important;opacity:1!important;">
    © <?php echo date('Y'); ?>
    <span class="cb-ripa" style="color:#ffffff!important;font-weight:800!important;margin-left:6px;">RIPA</span><span class="cb-soft" style="color:#f97316!important;font-weight:800!important;">SOFT</span>.
    <span class="cb-ripapos" style="color:#ffffff!important;font-weight:700!important;margin:0 6px;">RIPA POS V1.2.0</span> .
    Sistema De Gestión De Restaurantes.
    Créditos:
    <span class="cb-dehaan" style="color:#ffffff!important;font-weight:800!important;margin-left:6px;">DEHAAN</span><span class="cb-dehaansoft" style="color:#3b82f6!important;font-weight:800!important;">SOFT</span>.
    Todos los derechos reservados.
</div>

<script>
// Mover la barra de créditos fuera de .app para que se ancle al pie del viewport
function moverCreditosAlBody() {
    var bar = document.querySelector('.creditos-bar');
    if (!bar) return;
    if (bar.parentElement && bar.parentElement.tagName !== 'BODY') {
        document.body.appendChild(bar);
    }
}
moverCreditosAlBody();
document.addEventListener('DOMContentLoaded', moverCreditosAlBody);
window.addEventListener('load', moverCreditosAlBody);

(function () {
    // Reloj del footer en hora peruana (America/Lima = UTC-5).
    // Usamos Intl.DateTimeFormat con timeZone explicito para que no dependa
    // del reloj/timezone del navegador del usuario.
    function tickPeru() {
        try {
            var now = new Date();
            var horaLima = new Intl.DateTimeFormat('es-PE', {
                timeZone: 'America/Lima',
                hour: '2-digit', minute: '2-digit', hour12: false
            }).format(now);
            var fechaLima = new Intl.DateTimeFormat('es-PE', {
                timeZone: 'America/Lima',
                day: '2-digit', month: '2-digit', year: 'numeric'
            }).format(now);
            var clock = document.getElementById('footer-clock');
            if (clock) clock.textContent = horaLima;
            var fecha = document.getElementById('footer-date');
            if (fecha) fecha.textContent = fechaLima;
        } catch (e) { /* navegador viejo, no hacer nada */ }
    }
    tickPeru();
    setInterval(tickPeru, 30000);
})();
</script>
