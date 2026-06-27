<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('clientes');
$activePage = 'clientes';
$pageTitle  = 'PUERTO HABANA POS — Clientes';
require __DIR__ . '/template/head.php';
?>
<style>
.client-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #8b5cf6); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 13px; flex-shrink: 0; }
.client-cell { display: flex; align-items: center; gap: 12px; }
.client-name { font-weight: 600; font-size: 13px; }
.client-doc { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Clientes</div>
                <div class="page-subtitle">Gestión de la base de datos de clientes</div>
            </div>
            <button class="btn btn-primary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Nuevo Cliente</button>
        </div>

        <div class="page-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--primary-light);color:var(--primary);"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-label">TOTAL CLIENTES</div>
                    <div class="stat-value" id="stat-total">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--green-light);color:var(--green);"><i class="fa-solid fa-star"></i></div>
                    <div class="stat-label">CLIENTES VIP</div>
                    <div class="stat-value" id="stat-vip">0</div>
                    <div class="stat-trend up">+ S/ 500 gastados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fef3c7;color:var(--orange);"><i class="fa-solid fa-receipt"></i></div>
                    <div class="stat-label">PROMEDIO ÓRDENES</div>
                    <div class="stat-value" id="stat-avg">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#dbeafe;color:var(--blue);"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                    <div class="stat-label">VENTAS A CLIENTES</div>
                    <div class="stat-value" id="stat-revenue">S/ 0</div>
                </div>
            </div>

            <div class="table-container" style="padding: 16px;">
                <table id="tbl-clientes" class="data-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Órdenes</th>
                            <th>Total Gastado</th>
                            <th>Última Visita</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<div class="modal-overlay" id="modal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-title">Nuevo Cliente</div>
            <button class="modal-close" onclick="closeModal('modal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="display:grid;grid-template-columns:130px 1fr auto;gap:8px;align-items:end;">
                <div class="input-group" style="margin:0;">
                    <label class="input-label">Tipo Doc.</label>
                    <select id="c-tipodoc" class="input-field">
                        <option value="1">DNI</option>
                        <option value="6">RUC</option>
                    </select>
                </div>
                <div class="input-group" style="margin:0;">
                    <label class="input-label">Documento</label>
                    <input id="c-doc" class="input-field" placeholder="Nº DNI o RUC" maxlength="11" inputmode="numeric">
                </div>
                <button type="button" class="btn btn-primary" onclick="consultarSunat()" title="Consultar SUNAT/RENIEC" style="height:38px;">
                    <i class="fa-solid fa-search"></i>
                </button>
            </div>
            <div id="c-doc-status" style="font-size:11px;margin-top:4px;color:var(--text-muted);"></div>

            <div class="input-group" style="margin-top:10px;">
                <label class="input-label">Nombre completo / Razón social *</label>
                <input id="c-name" class="input-field" placeholder="Ej. María García">
            </div>
            <div class="input-group">
                <label class="input-label">Teléfono</label>
                <input id="c-phone" class="input-field" placeholder="987654321">
            </div>
            <div class="input-group">
                <label class="input-label">Email</label>
                <input type="email" id="c-email" class="input-field" placeholder="cliente@email.com">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal')">Cancelar</button>
            <button class="btn btn-primary" onclick="saveClient()">Guardar</button>
        </div>
    </div>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="scripts/clientes.js?v=<?php echo filemtime(__DIR__ . '/scripts/clientes.js'); ?>"></script>
</body>
</html>
