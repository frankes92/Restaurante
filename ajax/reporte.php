<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Orden.php";
require_once __DIR__ . "/../modelos/Producto.php";

requireLogin();

$orden    = new Orden();
$producto = new Producto();

$op = $_GET['op'] ?? '';

// Saneo defensivo: las fechas que llegan por GET solo se aceptan en formato
// YYYY-MM-DD. Si no cumplen, se descartan. Evita cualquier inyección vía los
// parametros desde/hasta/fecha aunque luego se concatenen en consultas.
$_fechaOk = function ($v) {
    return (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) ? $v : '';
};
foreach (['desde', 'hasta', 'fecha'] as $_fk) {
    if (isset($_GET[$_fk])) $_GET[$_fk] = $_fechaOk($_GET[$_fk]);
}

switch ($op) {

    case 'ventasDelDia':
        $fecha = $_GET['fecha'] ?? '';
        jsonResponse($orden->ventasDelDia($fecha));
        break;

    case 'ventasPorMetodo':
        $fecha = $_GET['fecha'] ?? '';
        $rs = $orden->ventasPorMetodo($fecha);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'ventasUltimosDias':
        $dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 7;
        $rs = $orden->ventasUltimosDias($dias);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'topProductos':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $rs = $producto->topVendidos($limit);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'productosDetallado':
        // Reporte detallado por producto: vendidos, ingresos, cortesias y stock.
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';
        $rs = $producto->reporteProductosDetallado($desde, $hasta);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'ventasDetalle':
        // Detalle transaccional: una fila por venta, con fecha/hora y presentación.
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';
        $rs = $producto->reporteVentasDetalle($desde, $hasta);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'ventasPorZona':
        // Devuelve cantidad de ordenes y total por zona (incluye "Sin zona")
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';
        $where = "WHERE o.estado = 'pagada'";
        if ($desde) $where .= " AND DATE(o.fecha_pago) >= '" . addslashes($desde) . "'";
        if ($hasta) $where .= " AND DATE(o.fecha_pago) <= '" . addslashes($hasta) . "'";

        $rs = ejecutarConsulta(
            "SELECT
                COALESCE(z.idzona, 0) AS idzona,
                COALESCE(z.nombre, 'Sin zona') AS zona_nombre,
                COALESCE(z.color, '#94a3b8') AS zona_color,
                COUNT(o.idorden) AS cantidad,
                COALESCE(SUM(o.total), 0) AS total
             FROM orden o
             LEFT JOIN mesa m  ON m.idmesa  = o.idmesa
             LEFT JOIN zona z  ON z.idzona  = m.idzona AND z.activo = 1
             $where
             GROUP BY z.idzona, z.nombre, z.color
             ORDER BY z.orden ASC, z.nombre ASC"
        );
        $data = [];
        if ($rs) { while ($r = $rs->fetch_assoc()) { $data[] = $r; } }
        jsonResponse($data);
        break;

    case 'ventasPorTipoComprobante':
        // Devuelve cantidad y total por tipo (ticket, nota_venta, boleta, factura)
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';
        $rs = $orden->ventasPorTipoComprobante($desde, $hasta);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[$r['tipo_comprobante']] = $r; }
        // Asegurar todos los tipos (aunque cantidad 0)
        foreach (['ticket','nota_venta','boleta','factura'] as $t) {
            if (!isset($data[$t])) {
                $data[$t] = ['tipo_comprobante' => $t, 'cantidad' => 0, 'total' => 0, 'igv' => 0, 'subtotal' => 0];
            }
        }

        // Agregar conteo de comprobantes electronicos por estado SUNAT
        $sunat = ejecutarConsultaSimpleFila(
            "SELECT
                SUM(CASE WHEN tipo_documento='03' AND estado IN ('aceptado','aceptado_observado') THEN 1 ELSE 0 END) AS boletas_aceptadas,
                SUM(CASE WHEN tipo_documento='01' AND estado IN ('aceptado','aceptado_observado') THEN 1 ELSE 0 END) AS facturas_aceptadas,
                SUM(CASE WHEN tipo_documento='03' AND estado='pendiente' THEN 1 ELSE 0 END) AS boletas_pendientes,
                SUM(CASE WHEN tipo_documento='01' AND estado='pendiente' THEN 1 ELSE 0 END) AS facturas_pendientes,
                SUM(CASE WHEN tipo_documento='03' AND estado='rechazado' THEN 1 ELSE 0 END) AS boletas_rechazadas,
                SUM(CASE WHEN tipo_documento='01' AND estado='rechazado' THEN 1 ELSE 0 END) AS facturas_rechazadas,
                SUM(CASE WHEN tipo_documento='07' THEN 1 ELSE 0 END) AS notas_credito,
                SUM(CASE WHEN tipo_documento='08' THEN 1 ELSE 0 END) AS notas_debito
             FROM comprobante_electronico
             WHERE 1=1
             " . ($desde ? " AND DATE(fecha_emision) >= '" . addslashes($desde) . "'" : '') . "
             " . ($hasta ? " AND DATE(fecha_emision) <= '" . addslashes($hasta) . "'" : '')
        );
        jsonResponse([
            'por_tipo' => $data,
            'sunat'    => $sunat ?: [],
        ]);
        break;

    case 'topeRus':
        // Acumulado mensual de boletas electrónicas para el control del tope RUS.
        require_once __DIR__ . "/../modelos/TopeRus.php";
        jsonResponse(topeRusInfo(1));
        break;

    case 'excel':
        // Genera el libro Excel "OcÃ©ano Habana" (Resumen, MÃ©todos/DÃ­as, Top, Detalle)
        requirePermiso('reportes');
        require_once __DIR__ . "/../modelos/ReporteExcel.php";
        $desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-7 days'));
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        (new ReporteExcel())->generar($desde, $hasta);
        exit;

    default:
        jsonResponse(['error' => 'op no vÃ¡lida']);
}