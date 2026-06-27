-- =====================================================================
-- YAPEZ POS v8 - Tipos de afectacion del IGV (catalogo 7 SUNAT)
-- =====================================================================
USE `yapez_db`;

-- ---------------------------------------------------------------------
-- 1) PRODUCTO: agregar codigo_afectacion (catalogo 7 SUNAT)
--    10 = Gravado (default), 20 = Exonerado, 30 = Inafecto, 40 = Exportacion
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_yapez_v8_prod`;
DELIMITER $$
CREATE PROCEDURE `sp_yapez_v8_prod`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='producto' AND COLUMN_NAME='codigo_afectacion'
    ) THEN
        ALTER TABLE `producto`
            ADD COLUMN `codigo_afectacion` VARCHAR(2) NOT NULL DEFAULT '10'
                COMMENT '10=Gravado, 20=Exonerado, 30=Inafecto, 40=Exportacion (cat. 7 SUNAT)'
                AFTER `precio`;
    END IF;
END$$
DELIMITER ;
CALL `sp_yapez_v8_prod`();
DROP PROCEDURE IF EXISTS `sp_yapez_v8_prod`;

-- ---------------------------------------------------------------------
-- 2) COMPROBANTE_DETALLE: agregar codigo_afectacion (heredado del producto)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_yapez_v8_compdet`;
DELIMITER $$
CREATE PROCEDURE `sp_yapez_v8_compdet`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='comprobante_detalle' AND COLUMN_NAME='codigo_afectacion'
    ) THEN
        ALTER TABLE `comprobante_detalle`
            ADD COLUMN `codigo_afectacion` VARCHAR(2) NOT NULL DEFAULT '10'
                COMMENT '10=Gravado, 20=Exonerado, 30=Inafecto (cat. 7)'
                AFTER `tipo_afectacion`;
    END IF;
END$$
DELIMITER ;
CALL `sp_yapez_v8_compdet`();
DROP PROCEDURE IF EXISTS `sp_yapez_v8_compdet`;

-- ---------------------------------------------------------------------
-- 3) COMPROBANTE_ELECTRONICO: subtotales por tipo de afectacion
--    (para reportes y XML cuando hay mezcla de afectaciones)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_yapez_v8_compe`;
DELIMITER $$
CREATE PROCEDURE `sp_yapez_v8_compe`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='comprobante_electronico' AND COLUMN_NAME='subtotal_gravado'
    ) THEN
        ALTER TABLE `comprobante_electronico`
            ADD COLUMN `subtotal_gravado`   DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `subtotal`,
            ADD COLUMN `subtotal_exonerado` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `subtotal_gravado`,
            ADD COLUMN `subtotal_inafecto`  DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `subtotal_exonerado`,
            ADD COLUMN `subtotal_gratuito`  DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `subtotal_inafecto`;
    END IF;
END$$
DELIMITER ;
CALL `sp_yapez_v8_compe`();
DROP PROCEDURE IF EXISTS `sp_yapez_v8_compe`;

-- ---------------------------------------------------------------------
-- 4) Backfill: asegurar que todos los productos existentes tengan '10' (Gravado)
-- ---------------------------------------------------------------------
UPDATE `producto` SET `codigo_afectacion` = '10' WHERE `codigo_afectacion` IS NULL OR `codigo_afectacion` = '';
UPDATE `comprobante_detalle` SET `codigo_afectacion` = '10' WHERE `codigo_afectacion` IS NULL OR `codigo_afectacion` = '';
