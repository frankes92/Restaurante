<?php
require_once __DIR__ . "/../config/auth.php";

// Esta pagina debe ser accesible aunque la licencia este vencida.
$user = currentUser();
$info = licenciaInfo();
$puedeReactivar = $user && in_array('config_licencia', $user['permisos'] ?? [], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistema bloqueado — PUERTO HABANA POS</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', system-ui, sans-serif; background: linear-gradient(135deg, #0f1d3a 0%, #1a2748 100%); min-height: 100vh; color: #fff; display: flex; align-items: center; justify-content: center; padding: 20px; }
.bloq-card { background: #fff; color: #1a1f36; max-width: 520px; width: 100%; border-radius: 18px; padding: 36px 32px; text-align: center; box-shadow: 0 30px 60px rgba(0,0,0,0.3); }
.icon { width: 90px; height: 90px; border-radius: 50%; background: #fef3c7; color: #f59e0b; display: inline-flex; align-items: center; justify-content: center; font-size: 38px; margin-bottom: 18px; }
.icon.danger { background: #fee2e2; color: #ef4444; }
h1 { font-size: 22px; margin-bottom: 8px; }
.lead { font-size: 14px; color: #6b7280; margin-bottom: 18px; }
.estado-card { background: #f9fafb; border-radius: 12px; padding: 16px 18px; margin-bottom: 18px; text-align: left; }
.estado-card .row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; }
.estado-card .row b { font-weight: 700; }
.btn { width: 100%; padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; border: 0; }
.btn-primary { background: #5b3df5; color: #fff; }
.btn-primary:hover { background: #4a2fe0; }
.btn-secondary { background: #fff; color: #1a1f36; border: 1px solid #d1d5db; margin-top: 8px; }
.btn-secondary:hover { border-color: #5b3df5; color: #5b3df5; }
.contact { font-size: 12px; color: #6b7280; margin-top: 18px; }
</style>
</head>
<body>
<div class="bloq-card">
    <?php if ($info['estado'] === 'vencida'): ?>
        <div class="icon danger"><i class="fa-solid fa-circle-exclamation"></i></div>
        <h1>Sistema bloqueado por vencimiento</h1>
        <p class="lead">La licencia del sistema ha vencido. Contacta al proveedor para renovar y reactivar el acceso.</p>
    <?php elseif ($info['estado'] === 'suspendida'): ?>
        <div class="icon danger"><i class="fa-solid fa-pause-circle"></i></div>
        <h1>Licencia suspendida</h1>
        <p class="lead">La licencia ha sido suspendida. Contacta al proveedor.</p>
    <?php else: ?>
        <div class="icon"><i class="fa-solid fa-key"></i></div>
        <h1>Sin licencia activa</h1>
        <p class="lead">No se ha configurado una licencia. Contacta al proveedor para activarla.</p>
    <?php endif; ?>

    <div class="estado-card">
        <div class="row"><span>Cliente:</span><b><?php echo h($info['cliente_nombre'] ?: '—'); ?></b></div>
        <div class="row"><span>Fecha de vencimiento:</span><b><?php echo $info['fecha_vencimiento'] ? date('d/m/Y', strtotime($info['fecha_vencimiento'])) : '—'; ?></b></div>
        <div class="row"><span>Estado:</span><b style="color:#ef4444;text-transform:uppercase;"><?php echo h($info['estado']); ?></b></div>
        <?php if ($info['fecha_vencimiento']): ?>
            <div class="row"><span>Días vencida:</span><b><?php echo abs($info['dias_restantes']); ?></b></div>
        <?php endif; ?>
    </div>

    <?php if ($puedeReactivar): ?>
        <button class="btn btn-primary" onclick="abrirReactivar()"><i class="fa-solid fa-key"></i> Reactivar con clave del proveedor</button>
    <?php endif; ?>
    <button class="btn btn-secondary" onclick="cerrarSesion()"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</button>

    <div class="contact">Para soporte: contacta al proveedor del sistema.</div>
</div>

<script>
async function cerrarSesion() {
    try { await $.post('../ajax/usuario.php?op=logout', {}); } catch(e) {}
    window.location.href = 'login';
}

async function abrirReactivar() {
    const { value: form } = await Swal.fire({
        title: 'Reactivar / Extender licencia',
        html:
            '<div style="text-align:left;">' +
            '<label style="font-size:12px;font-weight:600;">Clave maestra del proveedor</label>' +
            '<input type="password" id="sw-master" class="swal2-input" placeholder="Clave maestra" style="margin:6px 0;">' +
            '<label style="font-size:12px;font-weight:600;">Nueva fecha de vencimiento</label>' +
            '<input type="date" id="sw-fecha" class="swal2-input" style="margin:6px 0;">' +
            '<label style="font-size:12px;font-weight:600;">Monto pagado (opcional)</label>' +
            '<input type="number" id="sw-monto" class="swal2-input" placeholder="0.00" step="0.01" style="margin:6px 0;">' +
            '<label style="font-size:12px;font-weight:600;">Observación</label>' +
            '<input type="text" id="sw-obs" class="swal2-input" placeholder="Renovación mensual, etc." style="margin:6px 0;">' +
            '</div>',
        focusConfirm: false,
        confirmButtonText: 'Aplicar',
        cancelButtonText: 'Cancelar',
        showCancelButton: true,
        confirmButtonColor: '#5b3df5',
        preConfirm: () => {
            const master = document.getElementById('sw-master').value.trim();
            const fecha  = document.getElementById('sw-fecha').value;
            const monto  = document.getElementById('sw-monto').value || 0;
            const obs    = document.getElementById('sw-obs').value.trim();
            if (!master) { Swal.showValidationMessage('Clave requerida'); return false; }
            if (!fecha)  { Swal.showValidationMessage('Fecha requerida'); return false; }
            return { master_key: master, fecha_vencimiento: fecha, monto_pagado: monto, observacion: obs };
        }
    });
    if (!form) return;
    $.post('../ajax/licencia.php?op=extender', form, function(r){
        if (r.ok) {
            Swal.fire({ icon: 'success', title: 'Licencia activada', text: r.msg || '', timer: 1500 })
                .then(() => window.location.href = 'nuevaorden');
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: r.msg || 'No se pudo activar' });
        }
    }, 'json');
}
</script>
</body>
</html>
