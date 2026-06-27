-- =====================================================================
-- PUERTO HABANA POS — Limpieza para NUBE / PRODUCCIÓN
-- =====================================================================
-- CONSERVA (NO se tocan):
--   categoria, producto, producto_precio (precios + stock),
--   inventario_movimiento (modulo inventario),
--   empresa, certificado, numeracion (series; correlativos a 0),
--   mesa, zona, impresora,
--   usuario, rol, permiso, rol_permiso, usuario_permiso,
--   licencia, licencia_historial, rutas.
--
-- LIMPIA (datos de operación):
--   ventas/ordenes, caja, comprobantes electronicos, clientes,
--   whatsapp, cola de impresion y logs.
--
-- USO (en la nube):
--   - phpMyAdmin: selecciona la BD -> pestaña SQL -> pega todo -> Continuar
--   - o terminal: mysql -u USUARIO -p NOMBRE_BD < limpiar_nube.sql
--
-- IMPORTANTE: IRREVERSIBLE. Haz un BACKUP de la BD antes de ejecutar.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --- Ventas / operación ---
TRUNCATE TABLE orden_detalle;
TRUNCATE TABLE orden;

-- --- Caja ---
TRUNCATE TABLE caja_arqueo;
TRUNCATE TABLE caja_movimiento;
TRUNCATE TABLE caja_sesion;

-- --- Comprobantes electrónicos emitidos (NO toca series ni certificado) ---
TRUNCATE TABLE comprobante_detalle;
TRUNCATE TABLE comprobante_electronico;
TRUNCATE TABLE cdr_log;
TRUNCATE TABLE resumen_detalle;
TRUNCATE TABLE resumen_sunat;

-- --- Clientes ---
TRUNCATE TABLE cliente;
TRUNCATE TABLE cliente_facturacion;

-- --- WhatsApp ---
TRUNCATE TABLE whatsapp_envio;
TRUNCATE TABLE whatsapp_plantilla;

-- --- Impresión (cola de trabajos; NO toca las impresoras configuradas) ---
TRUNCATE TABLE cola_impresion;

-- --- Logs ---
TRUNCATE TABLE seguridad_log;

-- --- Liberar todas las mesas (ya no hay ordenes activas) ---
UPDATE mesa SET estado = 'libre';

-- =====================================================================
-- SERIES SUNAT: se CONSERVAN. Se reinician los correlativos a 0.
-- !! OJO: si en esta nube YA emitiste boletas/facturas REALES a SUNAT,
--    reiniciar a 0 podria generar numeros duplicados (SUNAT los rechaza).
--    En ese caso, COMENTA la siguiente linea (ponle -- al inicio).
-- =====================================================================
UPDATE numeracion SET ultimo_numero = 0;

SET FOREIGN_KEY_CHECKS = 1;

-- --- Verificación ---
SELECT '=== CONSERVADO ===' AS info;
SELECT
  (SELECT COUNT(*) FROM producto)         AS productos,
  (SELECT COUNT(*) FROM producto_precio)  AS precios,
  (SELECT COUNT(*) FROM categoria)        AS categorias,
  (SELECT COUNT(*) FROM mesa)             AS mesas,
  (SELECT COUNT(*) FROM impresora)        AS impresoras,
  (SELECT COUNT(*) FROM numeracion)       AS series,
  (SELECT COUNT(*) FROM certificado)      AS certificados,
  (SELECT COUNT(*) FROM usuario)          AS usuarios;

SELECT '=== LIMPIADO (debe ser 0) ===' AS info;
SELECT
  (SELECT COUNT(*) FROM orden)                   AS ordenes,
  (SELECT COUNT(*) FROM caja_sesion)             AS caja_sesiones,
  (SELECT COUNT(*) FROM comprobante_electronico) AS comprobantes,
  (SELECT COUNT(*) FROM cliente)                 AS clientes,
  (SELECT COUNT(*) FROM cola_impresion)          AS cola;
