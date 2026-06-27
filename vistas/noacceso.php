<?php
require_once __DIR__ . "/../config/auth.php";
requireLogin();
$user = currentUser();
$pageTitle = 'PUERTO HABANA POS — Sin acceso';
require __DIR__ . '/template/head.php';
?>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>
    <main class="main">
        <div class="page-content" style="display:flex;align-items:center;justify-content:center;height:100vh;">
            <div class="empty-state" style="max-width:420px;">
                <i class="fa-solid fa-lock" style="color:var(--red);"></i>
                <h3>Sin permisos para esta sección</h3>
                <p>Tu rol <b><?php echo htmlspecialchars($user['rol_nombre']); ?></b> no tiene acceso a esta página.<br>
                   Si necesitas acceder, pide al administrador que ajuste tus permisos.</p>
                <a href="nuevaorden" class="btn btn-primary" style="margin-top:14px;">
                    <i class="fa-solid fa-house"></i> Volver al inicio
                </a>
            </div>
        </div>
        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>
</body>
</html>
