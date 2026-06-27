<?php
session_start();
if (isset($_SESSION['idusuario'])) {
    header('Location: nuevaorden');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PUERTO HABANA POS — Iniciar Sesión</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --primary: #5b3df5;
    --primary-hover: #4a2fe0;
    --orange: #f97316;
    --dark-bg: #0f1d3a;
    --dark-bg-2: #1a2748;
    --text-dark: #1f2937;
    --text-muted: #6b7280;
    --text-light: #9ca3af;
    --border: #e5e7eb;
    --bg-light: #f9fafb;
}
html, body {
    font-family: 'Mulish', -apple-system, BlinkMacSystemFont, sans-serif;
    height: 100vh;
    overflow: hidden;
    background: #ffffff;
    color: var(--text-dark);
    -webkit-font-smoothing: antialiased;
}

.login-page { display: grid; grid-template-columns: 1fr 1.1fr; height: 100vh; }
@media (max-width: 980px) { .login-page { grid-template-columns: 1fr; } .left-panel { display: none; } }

/* LEFT PANEL */
.left-panel { position: relative; background: var(--dark-bg); overflow: hidden; border-radius: 0 60px 60px 0; }
.left-bg { position: absolute; inset: 0; background-image: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&q=85'); background-size: cover; background-position: center; opacity: 0.35; }
.left-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(15, 29, 58, 0.92) 0%, rgba(26, 39, 72, 0.85) 100%); }
.orange-accent { position: absolute; top: 50%; right: 0; transform: translateY(-50%); width: 6px; height: 280px; background: var(--orange); border-radius: 6px 0 0 6px; z-index: 3; }

.left-content { position: relative; z-index: 2; padding: 30px 50px; height: 100%; display: flex; flex-direction: column; justify-content: space-between; color: #fff; }

.logo-block { text-align: center; margin-top: 0; }
.logo-icon { display: inline-block; margin-bottom: 6px; }
.logo-icon svg { width: 60px; height: 60px; }
.logo-name { font-size: 42px; font-weight: 800; letter-spacing: -1px; line-height: 1; margin-bottom: 4px; }
.logo-name .resto { color: #ffffff; }
.logo-name .gest { color: var(--orange); }
.logo-tagline { font-size: 13px; color: rgba(255, 255, 255, 0.85); font-weight: 400; margin-bottom: 10px; }

.divider { width: 100%; height: 1px; background: rgba(255, 255, 255, 0.15); margin: 10px 0 18px; position: relative; }
.divider::after { content: ''; position: absolute; left: 50%; top: 0; transform: translateX(-50%); width: 60px; height: 3px; background: var(--orange); border-radius: 2px; }

.heading-text { text-align: center; font-size: 17px; font-weight: 700; line-height: 1.35; margin-bottom: 20px; color: #fff; }

.features { display: flex; flex-direction: column; gap: 12px; }
.feature { display: flex; gap: 14px; align-items: flex-start; }
.feature-icon { width: 36px; height: 36px; background: rgba(255, 255, 255, 0.08); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; flex-shrink: 0; }
.feature-text h4 { font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 2px; }
.feature-text p { font-size: 11px; color: rgba(255, 255, 255, 0.7); line-height: 1.4; }

.copyright { font-size: 11px; color: rgba(255, 255, 255, 0.5); margin-top: auto; padding-top: 16px; line-height: 1.6; }
.copyright .brand-ripa     { color: #ffffff;          font-weight: 800; }
.copyright .brand-soft     { color: var(--orange);    font-weight: 800; }
.copyright .credit-dehaan  { color: #ffffff;          font-weight: 800; }
.copyright .credit-soft    { color: #3b82f6;          font-weight: 800; }

/* RIGHT PANEL */
.right-panel { background: #ffffff; display: flex; align-items: center; justify-content: center; padding: 20px 30px; overflow-y: auto; }
.form-container { width: 100%; max-width: 420px; animation: fadeUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) both; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

.form-title { text-align: center; margin-bottom: 16px; }
.form-title h1 { font-size: 26px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px; letter-spacing: -0.5px; }
.form-title p { font-size: 13px; color: var(--text-muted); margin-bottom: 8px; }
.form-title .underline { display: inline-block; width: 60px; height: 3px; background: var(--orange); border-radius: 2px; }

.role-selector { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px; }
.role-btn { background: #fff; border: 2px solid var(--border); border-radius: 10px; padding: 8px 6px; cursor: pointer; text-align: center; font-family: inherit; transition: all 0.2s; }
.role-btn:hover { border-color: #c4b5fd; }
.role-btn.active { border-color: var(--primary); background: #f5f3ff; }
.role-btn-icon { width: 28px; height: 28px; border-radius: 50%; background: #f3f4f6; color: var(--text-muted); display: flex; align-items: center; justify-content: center; font-size: 12px; margin: 0 auto 4px; transition: all 0.2s; }
.role-btn.active .role-btn-icon { background: var(--primary); color: #fff; }
.role-btn-label { font-size: 11px; font-weight: 600; color: var(--text-dark); }
.role-btn.active .role-btn-label { color: var(--primary); }

.field { margin-bottom: 10px; }
.input-wrapper { position: relative; background: #fff; border: 1px solid var(--border); border-radius: 10px; transition: border-color 0.2s, box-shadow 0.2s; }
.input-wrapper:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(91, 61, 245, 0.08); }
.input-wrapper i.left-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 14px; }
.input-wrapper i.right-icon { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 14px; cursor: pointer; transition: color 0.2s; }
.input-wrapper i.right-icon:hover { color: var(--primary); }
.input-wrapper input { width: 100%; padding: 12px 14px 12px 40px; border: 0; border-radius: 10px; background: transparent; font-family: inherit; font-size: 13px; color: var(--text-dark); outline: 0; }
.input-wrapper input::placeholder { color: var(--text-light); font-weight: 400; }
.input-wrapper input[type="password"] { padding-right: 40px; }

.options-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px; }
.checkbox { display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; }
.checkbox input { display: none; }
.checkbox-mark { width: 18px; height: 18px; border: 2px solid var(--border); border-radius: 5px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.checkbox input:checked + .checkbox-mark { background: var(--primary); border-color: var(--primary); }
.checkbox input:checked + .checkbox-mark::after { content: ''; width: 5px; height: 9px; border-right: 2px solid #fff; border-bottom: 2px solid #fff; transform: rotate(45deg) translate(-1px, -1px); }
.checkbox-label { font-size: 12px; color: var(--text-dark); font-weight: 500; }
.forgot-link { font-size: 12px; color: var(--primary); text-decoration: none; font-weight: 500; }
.forgot-link:hover { text-decoration: underline; }

.submit-btn { width: 100%; padding: 13px; background: var(--primary); color: #fff; border: 0; border-radius: 10px; font-family: inherit; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s; margin-bottom: 12px; box-shadow: 0 6px 20px rgba(91, 61, 245, 0.25); }
.submit-btn:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(91, 61, 245, 0.35); }
.submit-btn:active { transform: translateY(0); }
.submit-btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

.signup-link { text-align: center; font-size: 12px; color: var(--text-muted); }
.signup-link a { color: var(--primary); font-weight: 700; text-decoration: none; }
.signup-link a:hover { text-decoration: underline; }

.error-banner { padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 10px; font-size: 12px; margin-bottom: 12px; display: none; align-items: center; gap: 10px; }
.error-banner.show { display: flex; animation: shake 0.4s; }
@keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

/* =================== PRELOADER PROFESIONAL =================== */
.preloader-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: radial-gradient(ellipse at top, #1a2748 0%, #0f1d3a 60%, #060d1f 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.5s ease, visibility 0.5s ease;
    overflow: hidden;
}
.preloader-overlay.show { opacity: 1; visibility: visible; }

/* Partículas de fondo */
.preloader-overlay::before,
.preloader-overlay::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.4;
    animation: floatOrb 8s ease-in-out infinite;
}
.preloader-overlay::before {
    width: 380px; height: 380px;
    background: radial-gradient(circle, #5b3df5 0%, transparent 70%);
    top: -100px; left: -100px;
}
.preloader-overlay::after {
    width: 420px; height: 420px;
    background: radial-gradient(circle, #f97316 0%, transparent 70%);
    bottom: -120px; right: -120px;
    animation-delay: -4s;
}
@keyframes floatOrb {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(40px, -30px) scale(1.1); }
}

/* Grid decorativo */
.preloader-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 50px 50px;
    mask-image: radial-gradient(ellipse at center, #000 30%, transparent 70%);
    -webkit-mask-image: radial-gradient(ellipse at center, #000 30%, transparent 70%);
}

.preloader-content {
    position: relative;
    z-index: 2;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 28px;
    padding: 30px;
    max-width: 420px;
    animation: fadeInScale 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}
@keyframes fadeInScale {
    from { opacity: 0; transform: scale(0.92); }
    to { opacity: 1; transform: scale(1); }
}

/* Logo con anillos giratorios */
.preloader-logo {
    position: relative;
    width: 140px;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.preloader-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 2px solid transparent;
    border-top-color: #f97316;
    border-right-color: rgba(249, 115, 22, 0.4);
    animation: spin 1.4s linear infinite;
}
.preloader-ring.ring-2 {
    inset: 12px;
    border-top-color: #5b3df5;
    border-right-color: rgba(91, 61, 245, 0.4);
    animation: spin 2s linear infinite reverse;
}
.preloader-ring.ring-3 {
    inset: 24px;
    border-top-color: #ffffff;
    border-right-color: rgba(255, 255, 255, 0.3);
    animation: spin 1.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.preloader-logo-inner {
    position: relative;
    width: 78px;
    height: 78px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0f1d3a 0%, #1a2748 100%);
    border: 2px solid rgba(249, 115, 22, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 40px rgba(249, 115, 22, 0.4), inset 0 0 20px rgba(91, 61, 245, 0.2);
    animation: pulseGlow 2s ease-in-out infinite;
}
@keyframes pulseGlow {
    0%, 100% { box-shadow: 0 0 40px rgba(249, 115, 22, 0.4), inset 0 0 20px rgba(91, 61, 245, 0.2); }
    50% { box-shadow: 0 0 60px rgba(249, 115, 22, 0.7), inset 0 0 30px rgba(91, 61, 245, 0.4); }
}
.preloader-logo-inner svg { width: 50px; height: 50px; }

/* Marca */
.preloader-brand {
    font-family: 'Mulish', sans-serif;
    font-size: 32px;
    font-weight: 800;
    letter-spacing: -1px;
    line-height: 1;
    margin: 0;
}
.preloader-brand .ripa { color: #ffffff; }
.preloader-brand .pos { color: #f97316; }

.preloader-tagline {
    font-family: 'Mulish', sans-serif;
    font-size: 12px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.55);
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-top: 10px;
}

/* Barra de progreso */
.preloader-progress {
    width: 260px;
    height: 4px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 999px;
    overflow: hidden;
    position: relative;
}
.preloader-progress-bar {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, #5b3df5 0%, #f97316 50%, #5b3df5 100%);
    background-size: 200% 100%;
    border-radius: 999px;
    width: 0%;
    transition: width 0.4s ease;
    animation: shimmer 1.6s linear infinite;
    box-shadow: 0 0 12px rgba(249, 115, 22, 0.5);
}
@keyframes shimmer {
    from { background-position: 0% 0; }
    to { background-position: 200% 0; }
}

/* Texto de estado */
.preloader-status {
    font-family: 'Mulish', sans-serif;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
    min-height: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.preloader-status .dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 8px #22c55e;
    animation: blink 1.2s ease-in-out infinite;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* Footer del preloader */
.preloader-footer {
    position: absolute;
    bottom: 24px;
    left: 0; right: 0;
    text-align: center;
    font-family: 'Mulish', sans-serif;
    font-size: 11px;
    color: rgba(255, 255, 255, 0.35);
    letter-spacing: 0.5px;
}
.preloader-footer .b1 { color: #ffffff; font-weight: 700; }
.preloader-footer .b2 { color: #f97316; font-weight: 700; }

/* ====== Tarjetas de contacto del administrador (modal) ====== */
.admin-contact-list { display:flex; flex-direction:column; gap:12px; margin-top:6px; text-align:left; }
.admin-contact-card {
    display:flex; align-items:center; gap:14px;
    padding:14px 16px; border-radius:16px;
    background:#ffffff; border:1.5px solid #eef0f4;
    cursor:pointer; text-decoration:none;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
    box-shadow: 0 1px 2px rgba(16,24,40,.04);
}
.admin-contact-card:hover {
    transform: translateY(-3px);
    border-color:#25D366;
    background:#f6fff9;
    box-shadow: 0 10px 24px rgba(37,211,102,.18);
}
.admin-contact-avatar {
    flex-shrink:0; width:48px; height:48px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#25D366,#128C7E);
    color:#fff; font-size:22px;
    box-shadow: 0 4px 10px rgba(18,140,126,.35);
}
.admin-contact-info { flex:1; min-width:0; display:flex; flex-direction:column; align-items:flex-start; }
.admin-contact-name { font-size:14px; font-weight:800; color:#1f2937; line-height:1.2; }
.admin-contact-num {
    font-size:15px; font-weight:700; color:#128C7E;
    font-family:'Courier New', monospace; letter-spacing:.5px; margin-top:2px;
}
.admin-contact-badge {
    display:inline-flex; align-items:center; gap:5px; margin-top:5px;
    font-size:10.5px; font-weight:700; color:#0e9f6e;
    background:#e7f9f0; padding:2px 8px; border-radius:20px;
}
.admin-contact-badge .dot { width:7px; height:7px; border-radius:50%; background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.25); }
.admin-contact-go {
    flex-shrink:0; width:34px; height:34px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:#25D366; color:#fff; font-size:14px;
    transition: transform .18s ease;
}
.admin-contact-card:hover .admin-contact-go { transform: scale(1.12); }
.admin-contact-intro { font-size:13px; color:#6b7280; margin-bottom:14px; line-height:1.5; }
.admin-contact-hint { font-size:11px; color:#9ca3af; margin-top:14px; display:flex; align-items:center; justify-content:center; gap:6px; }
.swal2-icon.swal2-no-border { border:none !important; }
.swal2-popup { border-radius:22px !important; }
</style>
</head>
<body>

<div class="login-page">

    <!-- LEFT PANEL -->
    <aside class="left-panel">
        <div class="left-bg"></div>
        <div class="left-overlay"></div>
        <div class="orange-accent"></div>

        <div class="left-content">

            <div class="logo-block">
                <div class="logo-icon">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <path d="M 42 22 Q 38 16, 42 10 Q 46 4, 42 -2" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M 50 22 Q 46 16, 50 10 Q 54 4, 50 -2" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M 58 22 Q 54 16, 58 10 Q 62 4, 58 -2" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M 18 55 Q 50 18, 82 55" fill="none" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round"/>
                        <circle cx="50" cy="32" r="3" fill="#ffffff"/>
                        <ellipse cx="50" cy="58" rx="38" ry="3" fill="none" stroke="#ffffff" stroke-width="3"/>
                        <path d="M 12 60 Q 50 75, 88 60" fill="none" stroke="#f97316" stroke-width="3.5" stroke-linecap="round"/>
                        <line x1="18" y1="55" x2="14" y2="58" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>
                        <line x1="82" y1="55" x2="86" y2="58" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="logo-name">
                    <span class="resto">RIPA</span><span class="gest">POS</span>
                </div>
                <div class="logo-tagline">Sistema de Gestión de Restaurantes</div>
            </div>

            <div class="divider"></div>

            <div class="heading-text">
                Administra tu restaurante<br>
                de forma simple y eficiente
            </div>

            <div class="features">
                <div class="feature">
                    <div class="feature-icon"><i class="fa-solid fa-store"></i></div>
                    <div class="feature-text">
                        <h4>Multi-Sucursal</h4>
                        <p>Gestiona todas tus sucursales<br>desde un solo lugar.</p>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="feature-text">
                        <h4>Multi-Usuario</h4>
                        <p>Controla los permisos y roles<br>de tu equipo.</p>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon"><i class="fa-solid fa-chart-column"></i></div>
                    <div class="feature-text">
                        <h4>Reportes en Tiempo Real</h4>
                        <p>Toma decisiones con datos<br>actualizados al instante.</p>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="feature-text">
                        <h4>Seguro y Confiable</h4>
                        <p>Tus datos y los de tu negocio<br>siempre protegidos.</p>
                    </div>
                </div>
            </div>

            <div class="copyright">
                © <?php echo date('Y'); ?> <span class="brand-ripa">RIPA</span><span class="brand-soft">SOFT</span>. RIPA POS V1.2.0 . Sistema De Gestión De Restaurantes. Creditos: <span class="credit-dehaan">DEHAAN</span><span class="credit-soft">SOFT</span>. Todos los derechos reservados.
            </div>
        </div>
    </aside>

    <!-- RIGHT PANEL -->
    <main class="right-panel">
        <div class="form-container">

            <div class="form-title">
                <h1>Iniciar Sesión</h1>
                <p>Bienvenido de vuelta</p>
                <span class="underline"></span>
            </div>

            <form id="login-form" autocomplete="off">

                <div class="error-banner" id="error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="error-msg">Credenciales incorrectas</span>
                </div>

                <div class="role-selector">
                    <button type="button" class="role-btn active" data-role="admin">
                        <div class="role-btn-icon"><i class="fa-solid fa-crown"></i></div>
                        <div class="role-btn-label">Administrador</div>
                    </button>
                    <button type="button" class="role-btn" data-role="cajero">
                        <div class="role-btn-icon"><i class="fa-solid fa-cash-register"></i></div>
                        <div class="role-btn-label">Cajero</div>
                    </button>
                    <button type="button" class="role-btn" data-role="mozo">
                        <div class="role-btn-icon"><i class="fa-solid fa-bell-concierge"></i></div>
                        <div class="role-btn-label">Mozo</div>
                    </button>
                </div>

                <div class="field">
                    <div class="input-wrapper">
                        <i class="fa-regular fa-user left-icon"></i>
                        <input id="username" type="text" placeholder="Usuario o correo electrónico" required autocomplete="username">
                    </div>
                </div>

                <div class="field">
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock left-icon"></i>
                        <input id="password" type="password" placeholder="Contraseña" required autocomplete="current-password">
                        <i id="toggle-pass" class="fa-regular fa-eye right-icon"></i>
                    </div>
                </div>

                <div class="options-row">
                    <label class="checkbox">
                        <input type="checkbox" id="remember" checked>
                        <span class="checkbox-mark"></span>
                        <span class="checkbox-label">Recordarme</span>
                    </label>
                    <a href="#" class="forgot-link" onclick="mostrarRecuperarPass();return false;">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="submit-btn" id="btn-submit">Iniciar Sesión</button>

                <div class="signup-link">
                    ¿No tienes una cuenta? <a href="#" onclick="mostrarSolicitarAcceso();return false;">Contacta al administrador</a>
                </div>

            </form>
        </div>
    </main>
</div>

<!-- =================== PRELOADER PROFESIONAL =================== -->
<div class="preloader-overlay" id="preloader">
    <div class="preloader-grid"></div>
    <div class="preloader-content">

        <div class="preloader-logo">
            <div class="preloader-ring"></div>
            <div class="preloader-ring ring-2"></div>
            <div class="preloader-ring ring-3"></div>
            <div class="preloader-logo-inner">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <path d="M 42 22 Q 38 16, 42 10 Q 46 4, 42 -2" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M 50 22 Q 46 16, 50 10 Q 54 4, 50 -2" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M 58 22 Q 54 16, 58 10 Q 62 4, 58 -2" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M 18 55 Q 50 18, 82 55" fill="none" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round"/>
                    <circle cx="50" cy="32" r="3" fill="#ffffff"/>
                    <ellipse cx="50" cy="58" rx="38" ry="3" fill="none" stroke="#ffffff" stroke-width="3"/>
                    <path d="M 12 60 Q 50 75, 88 60" fill="none" stroke="#f97316" stroke-width="3.5" stroke-linecap="round"/>
                </svg>
            </div>
        </div>

        <div>
            <div class="preloader-brand">
                <span class="ripa">RIPA</span><span class="pos">POS</span>
            </div>
            <div class="preloader-tagline">Sistema de Gestión</div>
        </div>

        <div class="preloader-progress">
            <div class="preloader-progress-bar" id="preloader-bar"></div>
        </div>

        <div class="preloader-status" id="preloader-status">
            <span class="dot"></span>
            <span id="preloader-text">Iniciando sesión segura...</span>
        </div>
    </div>
    <div class="preloader-footer">
        © <?php echo date('Y'); ?> <span class="b1">RIPA</span><span class="b2">SOFT</span> · Todos los derechos reservados
    </div>
</div>

<script>
let selectedRole = 'admin';

// ====== CONTACTO ADMINISTRADOR (WhatsApp / Llamada) ======
const ADMINS_CONTACTO = [
    { numero: '984309758', etiqueta: 'Administrador maestro' },
    { numero: '958117638', etiqueta: 'Administrador maestro' }
];
const COD_PAIS = '51'; // Perú

function esMovil() {
    return /Android|iPhone|iPad|iPod|Windows Phone|Mobile|Tablet/i.test(navigator.userAgent)
        || (('ontouchstart' in window) && (navigator.maxTouchPoints > 0) && window.innerWidth < 1024);
}

// Click en un número: en celular/tablet pregunta Llamar o WhatsApp; en PC abre WhatsApp Web.
window.contactarAdmin = function (numero) {
    const wa = 'https://wa.me/' + COD_PAIS + numero;
    const tel = 'tel:+' + COD_PAIS + numero;
    if (esMovil()) {
        Swal.fire({
            title: numero,
            text: '¿Cómo deseas contactar?',
            icon: 'question',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-brands fa-whatsapp"></i> WhatsApp',
            denyButtonText: '<i class="fa-solid fa-phone"></i> Llamar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#25D366',
            denyButtonColor: '#2563eb'
        }).then((res) => {
            if (res.isConfirmed)      window.open(wa, '_blank');
            else if (res.isDenied)    window.location.href = tel;
        });
    } else {
        // PC / laptop: directo a WhatsApp Web
        window.open(wa, '_blank');
    }
};

// Construye el HTML moderno (tarjetas estilo WhatsApp) para el modal
function htmlContactosAdmin(intro) {
    const cards = ADMINS_CONTACTO.map(a =>
        '<a href="#" class="admin-contact-card" onclick="contactarAdmin(\'' + a.numero + '\');return false;">'
        +   '<span class="admin-contact-avatar"><i class="fa-brands fa-whatsapp"></i></span>'
        +   '<span class="admin-contact-info">'
        +       '<span class="admin-contact-name">' + a.etiqueta + '</span>'
        +       '<span class="admin-contact-num">' + a.numero + '</span>'
        +       '<span class="admin-contact-badge"><span class="dot"></span> Respuesta rápida</span>'
        +   '</span>'
        +   '<span class="admin-contact-go"><i class="fa-solid fa-arrow-right"></i></span>'
        + '</a>'
    ).join('');
    return '<div class="admin-contact-intro">' + intro + '</div>'
         + '<div class="admin-contact-list">' + cards + '</div>'
         + '<div class="admin-contact-hint"><i class="fa-solid fa-circle-info"></i> Toca un contacto para abrir WhatsApp</div>';
}

const ADMIN_ICON_HTML =
    '<div style="width:62px;height:62px;border-radius:50%;margin:0 auto;'
    + 'display:flex;align-items:center;justify-content:center;'
    + 'background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;font-size:30px;'
    + 'box-shadow:0 8px 20px rgba(18,140,126,.4);"><i class="fa-brands fa-whatsapp"></i></div>';

window.mostrarSolicitarAcceso = function () {
    Swal.fire({
        title: 'Solicitar acceso',
        iconHtml: ADMIN_ICON_HTML,
        html: htmlContactosAdmin('Contacta a un administrador del sistema para solicitar tu acceso.'),
        showConfirmButton: true,
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#5b3df5',
        width: 420,
        customClass: { icon: 'swal2-no-border' }
    });
};

window.mostrarRecuperarPass = function () {
    Swal.fire({
        title: 'Recuperar contraseña',
        iconHtml: ADMIN_ICON_HTML,
        html: htmlContactosAdmin('Contacta a un administrador para restablecer tu contraseña.'),
        showConfirmButton: true,
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#5b3df5',
        width: 420,
        customClass: { icon: 'swal2-no-border' }
    });
};

// Selector de rol
document.querySelectorAll('.role-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedRole = btn.dataset.role;
    });
});

// Toggle ver/ocultar contraseña
document.getElementById('toggle-pass').addEventListener('click', () => {
    const inp = document.getElementById('password');
    const icon = document.getElementById('toggle-pass');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});

function showError(msg) {
    const e = document.getElementById('error');
    document.getElementById('error-msg').textContent = msg;
    e.classList.add('show');
    setTimeout(() => e.classList.remove('show'), 4000);
}

// SUBMIT — llama al backend real (ajax/usuario.php)
document.getElementById('login-form').addEventListener('submit', (ev) => {
    ev.preventDefault();
    const u = document.getElementById('username').value.trim();
    const p = document.getElementById('password').value;
    const remember = document.getElementById('remember').checked;
    const btn = document.getElementById('btn-submit');

    if (!u || !p) { showError('Completa usuario y contraseña'); return; }

    btn.disabled = true;
    btn.textContent = 'Validando...';

    $.ajax({
        url: '../ajax/usuario.php?op=login',
        type: 'POST',
        data: { login: u, clave: p },
        dataType: 'json',
        success: function (r) {
            if (!r.ok) {
                btn.disabled = false;
                btn.textContent = 'Iniciar Sesión';
                showError(r.msg || 'Credenciales incorrectas');
                return;
            }

            // Validacion del selector de rol contra el rol real del usuario
            const rolUsuario = (r.rol_nombre || '').toLowerCase();
            const rolMap = { 'administrador': 'admin', 'cajero': 'cajero', 'mozo': 'mozo' };
            const rolReal = rolMap[rolUsuario] || rolUsuario;

            if (rolReal && rolReal !== selectedRole) {
                btn.disabled = false;
                btn.textContent = 'Iniciar Sesión';
                showError('Este usuario tiene rol "' + r.rol_nombre + '". Selecciona el rol correcto.');
                // logout en backend porque ya seteo sesion
                $.post('../ajax/usuario.php?op=logout', {});
                return;
            }

            // Guardar username localmente si "Recordarme" esta activo
            if (remember) {
                localStorage.setItem('yapez_last_user', u);
            } else {
                localStorage.removeItem('yapez_last_user');
            }

            // Limpiar flag de "postergar apertura de caja" para que el modal salga al primer ingreso
            try { sessionStorage.removeItem('yapez_apertura_postergada'); } catch(e) {}

            // Mostrar preloader profesional
            mostrarPreloader(r.nombre || r.usuario || u);
        },
        error: function () {
            btn.disabled = false;
            btn.textContent = 'Iniciar Sesión';
            showError('Error de conexión con el servidor');
        }
    });
});

// Pre-llenar último usuario si hay
const lastUser = localStorage.getItem('yapez_last_user');
if (lastUser) {
    document.getElementById('username').value = lastUser;
    document.getElementById('password').focus();
}

// =================== CONTROL DEL PRELOADER ===================
function mostrarPreloader(nombreUsuario) {
    const overlay   = document.getElementById('preloader');
    const bar       = document.getElementById('preloader-bar');
    const textEl    = document.getElementById('preloader-text');
    const saludo    = nombreUsuario ? ('Bienvenido, ' + nombreUsuario) : 'Bienvenido de vuelta';

    const pasos = [
        { p: 15,  t: 'Validando credenciales...',         d: 350 },
        { p: 35,  t: 'Verificando permisos del usuario...', d: 450 },
        { p: 55,  t: 'Cargando configuración del sistema...', d: 500 },
        { p: 75,  t: 'Preparando módulos de gestión...',   d: 500 },
        { p: 92,  t: saludo + ' · Iniciando RIPA POS...', d: 500 },
        { p: 100, t: 'Redirigiendo al sistema...',         d: 350 }
    ];

    overlay.classList.add('show');

    let i = 0;
    function avanzar() {
        if (i >= pasos.length) {
            setTimeout(() => { window.location.href = 'nuevaorden'; }, 250);
            return;
        }
        const paso = pasos[i++];
        bar.style.width  = paso.p + '%';
        textEl.textContent = paso.t;
        setTimeout(avanzar, paso.d);
    }
    avanzar();
}
</script>

</body>
</html>
