-- =====================================================================
-- YAPEZ POS v7 - Seguridad, Arqueo de Caja, Logo y Licencia
-- =====================================================================
USE `yapez_db`;

-- ---------------------------------------------------------------------
-- 1) USUARIO: ampliar largo de clave para soportar password_hash (BCRYPT)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_yapez_v7_usuario`;
DELIMITER $$
CREATE PROCEDURE `sp_yapez_v7_usuario`()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuario' AND COLUMN_NAME='clave'
    ) THEN
        ALTER TABLE `usuario` MODIFY COLUMN `clave` VARCHAR(255) NOT NULL COMMENT 'password_hash o SHA256 legacy';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuario' AND COLUMN_NAME='intentos_fallidos'
    ) THEN
        ALTER TABLE `usuario`
            ADD COLUMN `intentos_fallidos` INT(11) NOT NULL DEFAULT 0 AFTER `condicion`,
            ADD COLUMN `bloqueado_hasta`   DATETIME NULL AFTER `intentos_fallidos`;
    END IF;
END$$
DELIMITER ;
CALL `sp_yapez_v7_usuario`();
DROP PROCEDURE IF EXISTS `sp_yapez_v7_usuario`;

-- ---------------------------------------------------------------------
-- 2) EMPRESA: campo logo (path al archivo) y formato de comprobante por defecto
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_yapez_v7_empresa`;
DELIMITER $$
CREATE PROCEDURE `sp_yapez_v7_empresa`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresa' AND COLUMN_NAME='logo'
    ) THEN
        ALTER TABLE `empresa`
            ADD COLUMN `logo` VARCHAR(300) NULL AFTER `web`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresa' AND COLUMN_NAME='formato_comprobante'
    ) THEN
        ALTER TABLE `empresa`
            ADD COLUMN `formato_comprobante` ENUM('ticket','a4') NOT NULL DEFAULT 'ticket' AFTER `logo`;
    END IF;
END$$
DELIMITER ;
CALL `sp_yapez_v7_empresa`();
DROP PROCEDURE IF EXISTS `sp_yapez_v7_empresa`;

-- ---------------------------------------------------------------------
-- 3) CERTIFICADO: la clave debe almacenarse cifrada (campo mas largo)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_yapez_v7_certificado`;
DELIMITER $$
CREATE PROCEDURE `sp_yapez_v7_certificado`()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='certificado' AND COLUMN_NAME='clave'
    ) THEN
        ALTER TABLE `certificado` MODIFY COLUMN `clave` VARCHAR(500) NULL COMMENT 'cifrado AES';
    END IF;
END$$
DELIMITER ;
CALL `sp_yapez_v7_certificado`();
DROP PROCEDURE IF EXISTS `sp_yapez_v7_certificado`;

-- ---------------------------------------------------------------------
-- 4) ARQUEO DE CAJA: detalle de conteo por denominacion + diferencia
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `caja_arqueo` (
    `idarqueo`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idsesion`         INT(10) UNSIGNED NOT NULL,
    `idusuario`        INT(10) UNSIGNED NULL,
    `fecha`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `monto_sistema`    DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'saldo esperado segun ventas',
    `monto_contado`    DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'monto fisico contado',
    `diferencia`       DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'contado - sistema (faltante negativo)',
    `observacion`      VARCHAR(300) NULL,
    `denominaciones`   TEXT NULL COMMENT 'JSON con cantidad por billete/moneda',
    PRIMARY KEY (`idarqueo`),
    KEY `idx_arq_sesion` (`idsesion`),
    KEY `idx_arq_fecha`  (`fecha`),
    CONSTRAINT `fk_arq_sesion` FOREIGN KEY (`idsesion`) REFERENCES `caja_sesion` (`idsesion`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5) PERMISOS NUEVOS: arqueo, logo, formato comprobante, licencia
-- ---------------------------------------------------------------------
INSERT INTO `permiso` (`codigo`,`nombre`,`descripcion`,`grupo`,`orden`) VALUES
    ('arqueo_caja',       'Arqueo de Caja',         'Realizar arqueo y cierre con conteo',      'caja',  13),
    ('config_logo',       'Configurar Logo',        'Subir/cambiar logo de empresa',            'sunat', 27),
    ('config_licencia',   'Gestionar Licencia',     'Activar/extender licencia del sistema',    'admin', 30)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- Asignar al admin
INSERT IGNORE INTO `rol_permiso` (`idrol`,`idpermiso`)
SELECT (SELECT idrol FROM rol WHERE codigo='admin'), p.idpermiso
FROM permiso p WHERE p.codigo IN ('arqueo_caja','config_logo','config_licencia');

-- Cajero puede hacer arqueo
INSERT IGNORE INTO `rol_permiso` (`idrol`,`idpermiso`)
SELECT (SELECT idrol FROM rol WHERE codigo='cajero'), p.idpermiso
FROM permiso p WHERE p.codigo='arqueo_caja';

-- ---------------------------------------------------------------------
-- 6) LICENCIA: control de vencimiento del sistema (alquiler)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `licencia` (
    `idlicencia`        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `cliente_nombre`    VARCHAR(150) NOT NULL DEFAULT 'Cliente',
    `fecha_inicio`      DATE NOT NULL,
    `fecha_vencimiento` DATE NOT NULL,
    `dias_aviso`        INT(11) NOT NULL DEFAULT 5 COMMENT 'mostrar aviso N dias antes',
    `estado`            ENUM('activa','suspendida') NOT NULL DEFAULT 'activa',
    `observacion`       VARCHAR(300) NULL,
    `creada_en`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizada_en`    DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`idlicencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de pagos / extensiones (auditoria)
CREATE TABLE IF NOT EXISTS `licencia_historial` (
    `idhistorial`       INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idlicencia`        INT(10) UNSIGNED NOT NULL,
    `fecha`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `accion`            ENUM('crear','extender','suspender','reactivar') NOT NULL,
    `vencimiento_anterior` DATE NULL,
    `vencimiento_nuevo`    DATE NULL,
    `monto_pagado`      DECIMAL(12,2) NULL,
    `observacion`       VARCHAR(300) NULL,
    `idusuario`         INT(10) UNSIGNED NULL,
    PRIMARY KEY (`idhistorial`),
    KEY `idx_lh_lic` (`idlicencia`),
    CONSTRAINT `fk_lh_lic` FOREIGN KEY (`idlicencia`) REFERENCES `licencia` (`idlicencia`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Licencia inicial: 30 dias desde hoy (demo). El proveedor cambia esto al desplegar.
INSERT INTO `licencia` (`cliente_nombre`,`fecha_inicio`,`fecha_vencimiento`,`dias_aviso`,`estado`,`observacion`)
SELECT 'Licencia Inicial', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 5, 'activa', 'Licencia demo creada en migracion v7'
WHERE NOT EXISTS (SELECT 1 FROM licencia);

-- ---------------------------------------------------------------------
-- 7) AUDITORIA: log de eventos de seguridad (logins, fallos, licencia, etc.)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seguridad_log` (
    `idlog`      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `fecha`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `evento`     VARCHAR(50) NOT NULL,
    `idusuario`  INT(10) UNSIGNED NULL,
    `login`      VARCHAR(45) NULL,
    `ip`         VARCHAR(45) NULL,
    `user_agent` VARCHAR(250) NULL,
    `mensaje`    VARCHAR(500) NULL,
    PRIMARY KEY (`idlog`),
    KEY `idx_seg_evt` (`evento`),
    KEY `idx_seg_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
