<?php
require_once __DIR__ . "/../../config/auth.php";
$pageTitle = $pageTitle ?? 'PUERTO HABANA POS';
$__user = currentUser();
$__permisos = $__user ? $__user['permisos'] : [];

// Cargar config de empresa (moneda + IGV) para exponer al frontend
require_once __DIR__ . "/../../modelos/Empresa.php";
$__empresa = (new Empresa())->mostrar(1);
$__config = [
    'simbolo_moneda'      => $__empresa['simbolo_moneda'] ?? 'S/',
    'codigo_moneda'       => $__empresa['codigo_moneda']  ?? 'PEN',
    'tasa_igv'            => (float)($__empresa['tasa_igv'] ?? 0.18),
    'nombre_empresa'      => $__empresa['nombre_comercial'] ?? $__empresa['razon_social'] ?? 'PUERTO HABANA POS',
    'logo'                => $__empresa['logo'] ?? '',
    'formato_comprobante' => $__empresa['formato_comprobante'] ?? 'ticket',
    'yape_qr'             => $__empresa['yape_qr'] ?? '',
    'plin_qr'            => $__empresa['plin_qr'] ?? '',
];

// Aviso de licencia (si esta proxima a vencer)
$__lic = function_exists('licenciaInfo') ? licenciaInfo() : null;

// Tope RUS: acumulado de boletas del mes (para aviso al iniciar sesion y banner)
require_once __DIR__ . "/../../modelos/TopeRus.php";
$__topeRus = function_exists('topeRusInfo') ? topeRusInfo(1) : null;

// Estado de caja: si hay sesion abierta para que el frontend lo sepa
require_once __DIR__ . "/../../modelos/Caja.php";
$__cajaActual = (new Caja())->sesionActual();
$__cajaAbierta = !empty($__cajaActual) && !empty($__cajaActual['idsesion']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="../public/css/styles.css?v=<?php echo @filemtime(__DIR__ . '/../../public/css/styles.css'); ?>">

<!-- DataTables (core + Buttons + Responsive + JSZip) -->
<link rel="stylesheet" href="https://cdn.datatables.net/v/dt/jszip-3.10.1/dt-1.13.8/b-2.4.2/b-html5-2.4.2/b-print-2.4.2/r-2.5.0/datatables.min.css">

<!-- SweetAlert2 (alertas, confirmaciones, toasts) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- pdfmake (necesario para boton PDF de DataTables) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- DataTables JS bundle -->
<script src="https://cdn.datatables.net/v/dt/jszip-3.10.1/dt-1.13.8/b-2.4.2/b-html5-2.4.2/b-print-2.4.2/r-2.5.0/datatables.min.js"></script>

<style>
/* Ajuste visual de DataTables al estilo PUERTO HABANA */
.dataTables_wrapper { font-size: 13px; }
.dataTables_wrapper .dt-buttons { margin-bottom: 12px; }
.dataTables_wrapper .dt-buttons .dt-button {
    background: var(--bg-white) !important;
    border: 1px solid var(--border) !important;
    border-radius: 8px !important;
    padding: 7px 12px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    color: var(--text-dark) !important;
    margin-right: 4px !important;
    box-shadow: none !important;
}
.dataTables_wrapper .dt-buttons .dt-button:hover {
    border-color: var(--primary) !important;
    color: var(--primary) !important;
    background: var(--bg-white) !important;
}
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 13px;
    margin-left: 6px;
    outline: 0;
    font-family: inherit;
}
.dataTables_wrapper .dataTables_filter input:focus { border-color: var(--primary); }
.dataTables_wrapper .dataTables_length select {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 5px 8px;
    font-size: 13px;
    margin: 0 6px;
    font-family: inherit;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 8px !important;
    padding: 6px 12px !important;
    font-size: 12px !important;
    margin: 0 2px !important;
    border: 1px solid var(--border) !important;
    color: var(--text-dark) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: var(--primary) !important;
    color: #fff !important;
    border-color: var(--primary) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: var(--primary-light) !important;
    color: var(--primary) !important;
    border-color: var(--primary) !important;
}
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter { color: var(--text-muted); margin: 8px 0; }
table.dataTable thead th {
    background: var(--bg-light);
    text-align: left;
    padding: 12px 18px !important;
    font-size: 11px;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 0.6px;
    border-bottom: 1px solid var(--border);
    text-transform: uppercase;
}
table.dataTable tbody td { padding: 14px 18px !important; vertical-align: middle; }
table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before {
    background-color: var(--primary);
}
</style>

<!-- Permisos del usuario y config de empresa disponibles globalmente -->
<script>
window.YAPEZ_USER = <?php echo json_encode([
    'idusuario'  => $__user['idusuario']   ?? null,
    'nombre'     => $__user['nombre']      ?? '',
    'apellidos'  => $__user['apellidos']   ?? '',
    'login'      => $__user['login']       ?? '',
    'rol_codigo' => $__user['rol_codigo']  ?? '',
    'rol_nombre' => $__user['rol_nombre']  ?? '',
    'permisos'   => $__permisos,
], JSON_UNESCAPED_UNICODE); ?>;
window.YAPEZ_CONFIG = <?php echo json_encode($__config, JSON_UNESCAPED_UNICODE); ?>;
window.YAPEZ_LICENCIA = <?php echo json_encode($__lic ?: [], JSON_UNESCAPED_UNICODE); ?>;
window.YAPEZ_CAJA_ABIERTA = <?php echo $__cajaAbierta ? 'true' : 'false'; ?>;
window.HABANA_TOPE_RUS = <?php echo json_encode($__topeRus ?: [], JSON_UNESCAPED_UNICODE); ?>;
</script>
</head>
