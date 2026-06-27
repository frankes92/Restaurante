-- =====================================================================
-- PUERTO HABANA POS — Limpiar SOLO el módulo de Inventario
-- =====================================================================
-- Deja TODO el stock en 0 y borra el historial de movimientos.
-- NO toca productos, precios, categorías ni nada más.
--   - producto_precio.stock  -> 0   (se conserva precio y stock_minimo)
--   - inventario_movimiento  -> vacío
--
-- USO (terminal):
--   "C:\xampp\mysql\bin\mysql.exe" -u root habana_db < db\limpiar_inventario.sql
--
-- IRREVERSIBLE: haz un backup antes (este proceso lo hace).
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Dejar todo el stock en 0 (precio y mínimo se conservan)
UPDATE producto_precio SET stock = 0;

-- Vaciar el historial de movimientos de inventario
TRUNCATE TABLE inventario_movimiento;

SET FOREIGN_KEY_CHECKS = 1;

-- --- Verificación ---
SELECT '=== INVENTARIO ===' AS info;
SELECT
  (SELECT COUNT(*) FROM producto_precio)                       AS presentaciones,
  (SELECT COALESCE(SUM(stock),0) FROM producto_precio)         AS stock_total,
  (SELECT COUNT(*) FROM producto_precio WHERE controla_stock=1) AS con_control,
  (SELECT COUNT(*) FROM inventario_movimiento)                 AS movimientos;
