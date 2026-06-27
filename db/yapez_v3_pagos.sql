-- =====================================================================
-- YAPEZ POS v3 - Tipo de comprobante y metadata de pago
-- =====================================================================

USE `yapez_db`;

-- Procedimiento condicional para evitar error si ya existen las columnas
DROP PROCEDURE IF EXISTS `sp_yapez_v3_pagos`;
DELIMITER $$
CREATE PROCEDURE `sp_yapez_v3_pagos`()
BEGIN
    -- tipo_comprobante
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orden' AND COLUMN_NAME='tipo_comprobante'
    ) THEN
        ALTER TABLE `orden`
            ADD COLUMN `tipo_comprobante` ENUM('ticket','nota_venta','boleta','factura')
                NOT NULL DEFAULT 'ticket' AFTER `metodo_pago`;
    END IF;

    -- monto_recibido
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orden' AND COLUMN_NAME='monto_recibido'
    ) THEN
        ALTER TABLE `orden`
            ADD COLUMN `monto_recibido` DECIMAL(12,2) NULL AFTER `tipo_comprobante`;
    END IF;

    -- vuelto
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orden' AND COLUMN_NAME='vuelto'
    ) THEN
        ALTER TABLE `orden`
            ADD COLUMN `vuelto` DECIMAL(12,2) NULL AFTER `monto_recibido`;
    END IF;

    -- pago_referencia
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orden' AND COLUMN_NAME='pago_referencia'
    ) THEN
        ALTER TABLE `orden`
            ADD COLUMN `pago_referencia` VARCHAR(100) NULL AFTER `vuelto`;
    END IF;

    -- pago_metadata (JSON con detalle por metodo)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orden' AND COLUMN_NAME='pago_metadata'
    ) THEN
        ALTER TABLE `orden`
            ADD COLUMN `pago_metadata` TEXT NULL AFTER `pago_referencia`;
    END IF;

    -- Indice por tipo de comprobante (util para reportes)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orden' AND INDEX_NAME='idx_orden_comprobante'
    ) THEN
        ALTER TABLE `orden` ADD INDEX `idx_orden_comprobante` (`tipo_comprobante`);
    END IF;
END$$
DELIMITER ;

CALL `sp_yapez_v3_pagos`();
DROP PROCEDURE IF EXISTS `sp_yapez_v3_pagos`;

-- Marcar las ordenes seed existentes como ticket (estaban antes de esta migracion)
UPDATE `orden` SET `tipo_comprobante`='ticket' WHERE `tipo_comprobante` IS NULL OR `tipo_comprobante`='';
