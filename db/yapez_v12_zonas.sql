-- Migración v12: Zonas en Mesas
-- Permite agrupar mesas por zonas (Salón, Terraza, Patio, VIP, etc.)
-- Una zona contiene N mesas. Una mesa pertenece a UNA zona (o ninguna).

-- 1) Tabla zona
CREATE TABLE IF NOT EXISTS `zona` (
    `idzona`  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre`  VARCHAR(80)      NOT NULL,
    `color`   CHAR(7)          NOT NULL DEFAULT '#5b3df5',
    `orden`   INT(11)          NOT NULL DEFAULT 0,
    `activo`  TINYINT(4)       NOT NULL DEFAULT 1,
    PRIMARY KEY (`idzona`),
    UNIQUE KEY `uk_zona_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) FK en mesa
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mesa' AND COLUMN_NAME = 'idzona');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `mesa` ADD COLUMN `idzona` INT(10) UNSIGNED NULL AFTER `idmesa`, ADD INDEX `idx_mesa_zona` (`idzona`), ADD CONSTRAINT `fk_mesa_zona` FOREIGN KEY (`idzona`) REFERENCES `zona`(`idzona`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "idzona ya existe en mesa" AS msg');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Permiso 'zonas' para administrar zonas (solo admin)
INSERT IGNORE INTO `permiso` (`codigo`, `nombre`, `descripcion`)
VALUES ('zonas', 'Gestionar zonas', 'Crear, editar y eliminar zonas del salón');

INSERT IGNORE INTO `rol_permiso` (`idrol`, `idpermiso`)
SELECT r.idrol, p.idpermiso
FROM `rol` r CROSS JOIN `permiso` p
WHERE r.codigo = 'admin' AND p.codigo = 'zonas';
