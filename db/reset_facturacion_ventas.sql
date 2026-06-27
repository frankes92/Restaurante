-- =====================================================================
-- YAPEZ POS - Reset completo de Facturacion + Ventas POS
-- Vacia todas las ventas y comprobantes para arrancar limpio en produccion.
-- Preserva: empresa, certificado, productos, mesas, clientes (CRM), usuarios.
-- =====================================================================
USE `yapez_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1) Comprobantes electronicos y dependencias
TRUNCATE TABLE `comprobante_detalle`;
TRUNCATE TABLE `cdr_log`;
TRUNCATE TABLE `resumen_detalle`;
TRUNCATE TABLE `resumen_sunat`;
TRUNCATE TABLE `comprobante_electronico`;
TRUNCATE TABLE `cliente_facturacion`;

-- 2) Ventas del POS
TRUNCATE TABLE `orden_detalle`;
TRUNCATE TABLE `orden`;

-- 3) Caja: arqueos, movimientos y sesiones
TRUNCATE TABLE `caja_arqueo`;
TRUNCATE TABLE `caja_movimiento`;
TRUNCATE TABLE `caja_sesion`;

-- 4) Resetear correlativos de numeracion (no borramos las series)
UPDATE `numeracion` SET `ultimo_numero` = 0;

-- 5) Liberar todas las mesas (estaban marcadas ocupadas/cuenta por las ordenes)
UPDATE `mesa` SET `estado` = 'libre' WHERE `estado` IN ('ocupada','cuenta');

-- 6) Resetear estadisticas de clientes (totales calculados desde ventas)
UPDATE `cliente`
   SET `total_ordenes` = 0,
       `total_gastado` = 0,
       `ultima_visita` = NULL;

-- 7) Limpiar log de seguridad opcional (logins, etc.) — comentado por defecto
-- TRUNCATE TABLE `seguridad_log`;

-- 8) Cambiar ambiente a PRODUCCION
UPDATE `empresa` SET `ambiente` = 'produccion' WHERE `idempresa` = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- Verificacion
SELECT 'comprobante_electronico' AS tabla, COUNT(*) AS registros FROM comprobante_electronico
UNION ALL SELECT 'comprobante_detalle', COUNT(*) FROM comprobante_detalle
UNION ALL SELECT 'cdr_log', COUNT(*) FROM cdr_log
UNION ALL SELECT 'resumen_sunat', COUNT(*) FROM resumen_sunat
UNION ALL SELECT 'resumen_detalle', COUNT(*) FROM resumen_detalle
UNION ALL SELECT 'cliente_facturacion', COUNT(*) FROM cliente_facturacion
UNION ALL SELECT 'orden', COUNT(*) FROM orden
UNION ALL SELECT 'orden_detalle', COUNT(*) FROM orden_detalle
UNION ALL SELECT 'caja_sesion', COUNT(*) FROM caja_sesion
UNION ALL SELECT 'caja_movimiento', COUNT(*) FROM caja_movimiento;

SELECT serie, descripcion, ultimo_numero FROM numeracion ORDER BY tipo_documento, serie;

SELECT idempresa, numero_ruc, razon_social, ambiente FROM empresa;
