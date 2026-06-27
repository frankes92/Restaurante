-- =====================================================================
-- YAPEZ POS v6 - Multiprecios y carga de imagenes locales
-- =====================================================================
USE `yapez_db`;

-- ---------------------------------------------------------------------
-- Tabla producto_precio: multiples precios/variantes por producto
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `producto_precio` (
    `idprecio`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idproducto` INT(10) UNSIGNED NOT NULL,
    `nombre`     VARCHAR(80) NOT NULL DEFAULT 'Normal' COMMENT 'Personal, Familiar, Vaso, Botella, etc.',
    `precio`     DECIMAL(12,2) NOT NULL DEFAULT 0,
    `es_default` TINYINT(4) NOT NULL DEFAULT 0,
    `orden`      INT(11) NOT NULL DEFAULT 0,
    `estado`     TINYINT(4) NOT NULL DEFAULT 1,
    PRIMARY KEY (`idprecio`),
    KEY `idx_pp_producto` (`idproducto`),
    CONSTRAINT `fk_pp_producto` FOREIGN KEY (`idproducto`) REFERENCES `producto` (`idproducto`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar precios existentes: cada producto recibe 1 precio "Normal" con su precio actual
INSERT INTO `producto_precio` (`idproducto`, `nombre`, `precio`, `es_default`, `orden`, `estado`)
SELECT idproducto, 'Normal', precio, 1, 1, 1
FROM producto
WHERE NOT EXISTS (SELECT 1 FROM producto_precio pp WHERE pp.idproducto = producto.idproducto);
