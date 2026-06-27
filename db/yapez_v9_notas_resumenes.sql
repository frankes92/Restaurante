-- =====================================================================
-- YAPEZ POS v9 - Notas de Credito/Debito + Resumenes Diarios SUNAT
-- =====================================================================
USE `yapez_db`;

-- ---------------------------------------------------------------------
-- 1) COMPROBANTE_ELECTRONICO: tipo 07 (NC) y 08 (ND) ya soportados.
--    Agregar columnas para referencias a comprobante origen.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_yapez_v9_comp`;
DELIMITER $$
CREATE PROCEDURE `sp_yapez_v9_comp`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='comprobante_electronico' AND COLUMN_NAME='ref_tipo_documento'
    ) THEN
        ALTER TABLE `comprobante_electronico`
            ADD COLUMN `ref_idcomprobante` INT(10) UNSIGNED NULL
                COMMENT 'comprobante origen para notas de credito/debito' AFTER `idorden`,
            ADD COLUMN `ref_tipo_documento` VARCHAR(2) NULL
                COMMENT '01 factura, 03 boleta - referencia para NC/ND' AFTER `ref_idcomprobante`,
            ADD COLUMN `ref_serie`         VARCHAR(10) NULL AFTER `ref_tipo_documento`,
            ADD COLUMN `ref_numero`        VARCHAR(15) NULL AFTER `ref_serie`,
            ADD COLUMN `motivo_codigo`     VARCHAR(2)  NULL
                COMMENT 'catalogo 9 NC / catalogo 10 ND' AFTER `ref_numero`,
            ADD COLUMN `motivo_descripcion` VARCHAR(300) NULL AFTER `motivo_codigo`;
    END IF;
END$$
DELIMITER ;
CALL `sp_yapez_v9_comp`();
DROP PROCEDURE IF EXISTS `sp_yapez_v9_comp`;

-- ---------------------------------------------------------------------
-- 2) NUMERACION para Notas de Credito (FC01/BC01) y Debito (FD01/BD01)
--    Las series por convencion SUNAT: F* para facturas-relacionadas, B* para boletas
-- ---------------------------------------------------------------------
INSERT INTO `numeracion` (`idempresa`,`tipo_documento`,`serie`,`ultimo_numero`,`descripcion`) VALUES
    (1, '07', 'FC01', 0, 'Nota de Credito (referenciada a Factura)'),
    (1, '07', 'BC01', 0, 'Nota de Credito (referenciada a Boleta)'),
    (1, '08', 'FD01', 0, 'Nota de Debito (referenciada a Factura)'),
    (1, '08', 'BD01', 0, 'Nota de Debito (referenciada a Boleta)')
ON DUPLICATE KEY UPDATE descripcion=VALUES(descripcion);

-- ---------------------------------------------------------------------
-- 3) RESUMEN_SUNAT: tabla unica para resumenes diarios de boletas (RC)
--    y comunicaciones de baja (RA)
--    SUNAT envia sendSummary y devuelve un ticket; se consulta despues con getStatus
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `resumen_sunat` (
    `idresumen`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idempresa`         INT(10) UNSIGNED NOT NULL,
    `tipo`              ENUM('RC','RA') NOT NULL COMMENT 'RC = resumen boletas; RA = comunicacion de baja',
    `correlativo`       INT(11) NOT NULL DEFAULT 1 COMMENT 'correlativo del dia',
    `serie_doc`         VARCHAR(30) NOT NULL COMMENT 'RC-YYYYMMDD-NNN o RA-YYYYMMDD-NNN',
    `fecha_referencia`  DATE NOT NULL COMMENT 'fecha de los comprobantes resumidos',
    `fecha_generacion`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `xml_nombre`        VARCHAR(150) NULL,
    `xml_ruta`          VARCHAR(300) NULL,
    `zip_ruta`          VARCHAR(300) NULL,
    `cdr_ruta`          VARCHAR(300) NULL,
    `xml_hash`          VARCHAR(150) NULL,
    `ticket`            VARCHAR(50)  NULL COMMENT 'devuelto por sendSummary',
    `estado`            ENUM('pendiente','generado','enviado','aceptado','aceptado_observado','rechazado','error')
                        NOT NULL DEFAULT 'pendiente',
    `cdr_codigo`        VARCHAR(10)  NULL,
    `cdr_descripcion`   VARCHAR(500) NULL,
    `fecha_envio`       DATETIME NULL,
    `fecha_aceptacion`  DATETIME NULL,
    `idusuario`         INT(10) UNSIGNED NULL,
    PRIMARY KEY (`idresumen`),
    UNIQUE KEY `uk_res_serie` (`idempresa`,`tipo`,`fecha_referencia`,`correlativo`),
    KEY `idx_res_estado`  (`estado`),
    KEY `idx_res_fechaef` (`fecha_referencia`),
    CONSTRAINT `fk_res_emp` FOREIGN KEY (`idempresa`) REFERENCES `empresa` (`idempresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Detalle de comprobantes incluidos en cada resumen
CREATE TABLE IF NOT EXISTS `resumen_detalle` (
    `iddetalle`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idresumen`         INT(10) UNSIGNED NOT NULL,
    `idcomprobante`     INT(10) UNSIGNED NOT NULL,
    `linea`             INT(11) NOT NULL,
    -- Para RC (resumen boletas)
    `tipo_documento`    VARCHAR(2)  NOT NULL,
    `serie`             VARCHAR(10) NOT NULL,
    `numero`            VARCHAR(15) NOT NULL,
    `cliente_tipo_doc`  VARCHAR(1)  NULL,
    `cliente_num_doc`   VARCHAR(20) NULL,
    `total`             DECIMAL(14,2) NOT NULL DEFAULT 0,
    `total_gravado`     DECIMAL(14,2) NOT NULL DEFAULT 0,
    `total_exonerado`   DECIMAL(14,2) NOT NULL DEFAULT 0,
    `total_inafecto`    DECIMAL(14,2) NOT NULL DEFAULT 0,
    `igv`               DECIMAL(14,2) NOT NULL DEFAULT 0,
    -- Para RA (comunicacion de baja)
    `motivo_baja`       VARCHAR(200) NULL,
    `estado_item`       VARCHAR(2)   NULL COMMENT '1=adicionar, 2=modificar, 3=anular',
    PRIMARY KEY (`iddetalle`),
    KEY `idx_resd_res` (`idresumen`),
    KEY `idx_resd_comp`(`idcomprobante`),
    CONSTRAINT `fk_resd_res`  FOREIGN KEY (`idresumen`) REFERENCES `resumen_sunat` (`idresumen`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_resd_comp` FOREIGN KEY (`idcomprobante`) REFERENCES `comprobante_electronico` (`idcomprobante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4) Permisos nuevos
-- ---------------------------------------------------------------------
INSERT INTO `permiso` (`codigo`,`nombre`,`descripcion`,`grupo`,`orden`) VALUES
    ('emitir_nc',          'Emitir Nota de Credito',  'Anular comprobantes con NC',         'sunat', 28),
    ('emitir_nd',          'Emitir Nota de Debito',   'Emitir notas de debito',             'sunat', 29),
    ('resumen_boletas',    'Resumen Diario Boletas',  'Generar y enviar resumen RC',        'sunat', 30),
    ('comunicacion_baja',  'Comunicacion de Baja',    'Anular boletas por resumen RA',      'sunat', 31)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

INSERT IGNORE INTO `rol_permiso` (`idrol`,`idpermiso`)
SELECT (SELECT idrol FROM rol WHERE codigo='admin'), p.idpermiso
FROM permiso p WHERE p.codigo IN ('emitir_nc','emitir_nd','resumen_boletas','comunicacion_baja');

INSERT IGNORE INTO `rol_permiso` (`idrol`,`idpermiso`)
SELECT (SELECT idrol FROM rol WHERE codigo='cajero'), p.idpermiso
FROM permiso p WHERE p.codigo='emitir_nc';
