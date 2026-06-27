-- =====================================================================
-- PUERTO HABANA POS — Limpieza dejando SOLO el catálogo y configuración base
-- =====================================================================
-- CONSERVA:
--   producto, producto_precio (precios + stock + fotos via producto.imagen),
--   categoria, empresa, licencia, licencia_historial, mesa, zona,
--   usuario, rol, permiso, rol_permiso, usuario_permiso (para poder ingresar).
--
-- BORRA (datos de operación + SUNAT + clientes + whatsapp + logs):
--   ventas, caja, comprobantes electrónicos, series, certificado,
--   inventario (movimientos), clientes, whatsapp, impresoras, logs.
--
-- USO (terminal):
--   "C:\xampp\mysql\bin\mysql.exe" -u root habana_db < db\limpiar_dejar_catalogo.sql
--
-- IMPORTANTE: IRREVERSIBLE. Haz un backup antes (este proceso lo hace).
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --- Ventas / operación ---
TRUNCATE TABLE orden_detalle;
TRUNCATE TABLE orden;

-- --- Caja ---
TRUNCATE TABLE caja_arqueo;
TRUNCATE TABLE caja_movimiento;
TRUNCATE TABLE caja_sesion;

-- --- Comprobantes electrónicos / SUNAT (incluye series y certificado) ---
TRUNCATE TABLE comprobante_detalle;
TRUNCATE TABLE comprobante_electronico;
TRUNCATE TABLE cdr_log;
TRUNCATE TABLE resumen_detalle;
TRUNCATE TABLE resumen_sunat;
TRUNCATE TABLE numeracion;
TRUNCATE TABLE certificado;

-- --- Inventario (solo el historial de movimientos; el stock vive en producto_precio) ---
TRUNCATE TABLE inventario_movimiento;

-- --- Clientes ---
TRUNCATE TABLE cliente;
TRUNCATE TABLE cliente_facturacion;

-- --- WhatsApp ---
TRUNCATE TABLE whatsapp_envio;
TRUNCATE TABLE whatsapp_plantilla;

-- --- Impresión ---
TRUNCATE TABLE impresora;
TRUNCATE TABLE cola_impresion;

-- --- Config de rutas / logs ---
TRUNCATE TABLE rutas;
TRUNCATE TABLE seguridad_log;

-- --- Liberar todas las mesas (ya no hay ordenes activas) ---
UPDATE mesa SET estado = 'libre';

-- NOTA: NO se tocan producto, producto_precio, categoria, empresa, licencia,
--       licencia_historial, mesa, zona, usuario, rol, permiso, rol_permiso,
--       usuario_permiso.

SET FOREIGN_KEY_CHECKS = 1;

-- --- Verificación ---
SELECT '=== CONSERVADO ===' AS info;
SELECT
  (SELECT COUNT(*) FROM producto)        AS productos,
  (SELECT COUNT(*) FROM producto_precio) AS precios,
  (SELECT COUNT(*) FROM categoria)       AS categorias,
  (SELECT COUNT(*) FROM mesa)            AS mesas,
  (SELECT COUNT(*) FROM zona)            AS zonas,
  (SELECT COUNT(*) FROM empresa)         AS empresa,
  (SELECT COUNT(*) FROM licencia)        AS licencia,
  (SELECT COUNT(*) FROM usuario)         AS usuarios;

SELECT '=== LIMPIADO (debe ser 0) ===' AS info;
SELECT
  (SELECT COUNT(*) FROM orden)                  AS ordenes,
  (SELECT COUNT(*) FROM caja_sesion)            AS caja_sesiones,
  (SELECT COUNT(*) FROM comprobante_electronico) AS comprobantes,
  (SELECT COUNT(*) FROM numeracion)             AS series,
  (SELECT COUNT(*) FROM certificado)            AS certificados,
  (SELECT COUNT(*) FROM cliente)                AS clientes,
  (SELECT COUNT(*) FROM inventario_movimiento)  AS inv_movs;
