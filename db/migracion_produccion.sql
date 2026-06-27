-- =====================================================================
-- MIGRACIÓN A PRODUCCIÓN — cambios de esquema de esta versión
-- =====================================================================
-- Seguro de ejecutar: usa IF NOT EXISTS / MODIFY (no borra datos).
-- Ejecutar UNA vez en la BD de producción:
--   mysql -u USUARIO -p NOMBRE_BD < db/migracion_produccion.sql
-- =====================================================================

-- 1) QR de pago Yape/Plin en empresa
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS yape_qr VARCHAR(200) NULL;
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS plin_qr VARCHAR(200) NULL;

-- 2) Columnas configurables de mesas (preferencia guardada en BD)
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS mesas_columnas VARCHAR(10) NOT NULL DEFAULT 'auto';

-- 3) Orden de mesas (drag & drop)
ALTER TABLE mesa ADD COLUMN IF NOT EXISTS orden INT(11) NOT NULL DEFAULT 0;

-- 4) Producto de cortesía en el detalle de la orden
ALTER TABLE orden_detalle ADD COLUMN IF NOT EXISTS cortesia TINYINT(1) NOT NULL DEFAULT 0;

-- 5) Métodos de pago: agregar 'plin' y 'mixto' al enum
ALTER TABLE orden
  MODIFY COLUMN metodo_pago ENUM('','efectivo','tarjeta','yape','plin','transferencia','mixto') NOT NULL DEFAULT '';

-- 6) Cola de impresión: permitir el tipo 'comprobante' (boleta/factura/nota por el bridge)
ALTER TABLE cola_impresion
  MODIFY COLUMN tipo ENUM('comanda','comanda_anular','ticket','prueba','comprobante') NOT NULL DEFAULT 'comanda';

-- --- Verificación ---
SELECT 'OK migración aplicada' AS info;
