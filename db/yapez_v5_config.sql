-- =====================================================================
-- YAPEZ POS v5 - Configuracion: simbolo y codigo de moneda
-- =====================================================================
USE `yapez_db`;

DROP PROCEDURE IF EXISTS `sp_yapez_v5`;
DELIMITER $$
CREATE PROCEDURE `sp_yapez_v5`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresa' AND COLUMN_NAME='simbolo_moneda'
    ) THEN
        ALTER TABLE `empresa`
            ADD COLUMN `simbolo_moneda` VARCHAR(5) NOT NULL DEFAULT 'S/' AFTER `tasa_igv`,
            ADD COLUMN `codigo_moneda`  VARCHAR(3) NOT NULL DEFAULT 'PEN' AFTER `simbolo_moneda`;
    END IF;
END$$
DELIMITER ;

CALL `sp_yapez_v5`();
DROP PROCEDURE IF EXISTS `sp_yapez_v5`;

UPDATE `empresa` SET `simbolo_moneda`='S/', `codigo_moneda`='PEN' WHERE `simbolo_moneda` IS NULL OR `simbolo_moneda`='';
