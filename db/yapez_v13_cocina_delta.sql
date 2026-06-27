-- Migracion v13: Delta de envios a cocina
-- Permite distinguir cuanta cantidad de cada plato YA fue impresa en cocina,
-- para que al aumentar cantidad de un plato ya enviado solo se imprima la diferencia.

-- 1) Agregar columna cantidad_enviada (idempotente)
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'orden_detalle'
                      AND COLUMN_NAME = 'cantidad_enviada');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `orden_detalle` ADD COLUMN `cantidad_enviada` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `cantidad`',
    'SELECT "cantidad_enviada ya existe en orden_detalle" AS msg');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Para registros historicos: marcar como ya enviada toda la cantidad
--    cuando el item ya estaba en estado posterior a 'pendiente'.
UPDATE `orden_detalle`
SET cantidad_enviada = cantidad
WHERE estado IN ('en_preparacion','listo','servido')
  AND cantidad_enviada = 0;
