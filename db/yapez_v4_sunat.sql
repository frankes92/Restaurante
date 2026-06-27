-- =====================================================================
-- YAPEZ POS v4 - Modulo Facturacion Electronica SUNAT
-- =====================================================================

USE `yapez_db`;

-- ---------------------------------------------------------------------
-- 1) EMPRESA emisora
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `empresa` (
    `idempresa`           INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `numero_ruc`          VARCHAR(15)  NOT NULL,
    `tipo_doc_emisor`     VARCHAR(2)   NOT NULL DEFAULT '6' COMMENT '6=RUC',
    `razon_social`        VARCHAR(150) NOT NULL,
    `nombre_comercial`    VARCHAR(150) NULL,
    `domicilio_fiscal`    VARCHAR(200) NOT NULL,
    `ubigeo`              VARCHAR(6)   NOT NULL DEFAULT '150101',
    `departamento`        VARCHAR(60)  NULL,
    `provincia`           VARCHAR(60)  NULL,
    `distrito`            VARCHAR(60)  NULL,
    `codigo_pais`         VARCHAR(2)   NOT NULL DEFAULT 'PE',
    `telefono`            VARCHAR(30)  NULL,
    `correo`              VARCHAR(120) NULL,
    `web`                 VARCHAR(120) NULL,
    `logo`                VARCHAR(200) NULL,
    -- SUNAT credenciales SOL (para WSSE)
    `usuario_sol`         VARCHAR(40)  NULL,
    `clave_sol`           VARCHAR(60)  NULL,
    `ambiente`            ENUM('beta','produccion') NOT NULL DEFAULT 'beta',
    `version_ubl`         VARCHAR(10)  NOT NULL DEFAULT '2.1',
    `version_estructura`  VARCHAR(10)  NOT NULL DEFAULT '2.0',
    `tasa_igv`            DECIMAL(5,4) NOT NULL DEFAULT 0.18,
    `estado`              TINYINT(4)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`idempresa`),
    UNIQUE KEY `uk_emp_ruc` (`numero_ruc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2) CERTIFICADO digital
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `certificado` (
    `idcertificado`  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idempresa`      INT(10) UNSIGNED NOT NULL,
    `nombre_archivo` VARCHAR(150) NOT NULL,
    `ruta`           VARCHAR(300) NOT NULL,
    `clave`          VARCHAR(120) NULL,
    `tipo`           ENUM('demo','produccion') NOT NULL DEFAULT 'demo',
    `fecha_carga`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `vencimiento`    DATE NULL,
    `activo`         TINYINT(4) NOT NULL DEFAULT 1,
    PRIMARY KEY (`idcertificado`),
    KEY `idx_cert_emp` (`idempresa`),
    CONSTRAINT `fk_cert_emp` FOREIGN KEY (`idempresa`) REFERENCES `empresa` (`idempresa`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3) RUTAS de carpetas SUNAT
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rutas` (
    `idruta`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idempresa`      INT(10) UNSIGNED NOT NULL,
    `ruta_data`      VARCHAR(200) NOT NULL DEFAULT '../sfs/data/',
    `ruta_firma`     VARCHAR(200) NOT NULL DEFAULT '../sfs/firma/',
    `ruta_envio`     VARCHAR(200) NOT NULL DEFAULT '../sfs/envio/',
    `ruta_rpta`      VARCHAR(200) NOT NULL DEFAULT '../sfs/rpta/',
    `ruta_unzip`     VARCHAR(200) NOT NULL DEFAULT '../sfs/unziprpta/',
    `ruta_baja`      VARCHAR(200) NOT NULL DEFAULT '../sfs/baja/',
    `ruta_resumen`   VARCHAR(200) NOT NULL DEFAULT '../sfs/resumen/',
    `ruta_pdf`       VARCHAR(200) NOT NULL DEFAULT '../comprobantesPDF/',
    PRIMARY KEY (`idruta`),
    UNIQUE KEY `uk_ruta_emp` (`idempresa`),
    CONSTRAINT `fk_ruta_emp` FOREIGN KEY (`idempresa`) REFERENCES `empresa` (`idempresa`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4) NUMERACION (series y correlativos)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `numeracion` (
    `idnumeracion`    INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idempresa`       INT(10) UNSIGNED NOT NULL,
    `tipo_documento`  VARCHAR(2)  NOT NULL COMMENT '01=Factura, 03=Boleta, 07=NC, 08=ND',
    `serie`           VARCHAR(10) NOT NULL,
    `ultimo_numero`   INT(11)     NOT NULL DEFAULT 0,
    `descripcion`     VARCHAR(80) NULL,
    `estado`          TINYINT(4)  NOT NULL DEFAULT 1,
    PRIMARY KEY (`idnumeracion`),
    UNIQUE KEY `uk_num_serie` (`idempresa`,`tipo_documento`,`serie`),
    KEY `idx_num_emp` (`idempresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5) CLIENTE_FACTURACION (datos extra para boleta/factura)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cliente_facturacion` (
    `idclifact`       INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idcliente`       INT(10) UNSIGNED NULL COMMENT 'puede vincularse a tabla cliente, pero no obligatorio',
    `tipo_documento`  VARCHAR(1) NOT NULL DEFAULT '1' COMMENT '0=DNI extranjero, 1=DNI, 6=RUC, 7=Pasaporte, 4=CE',
    `numero_documento` VARCHAR(20) NOT NULL,
    `razon_social`    VARCHAR(200) NOT NULL,
    `direccion`       VARCHAR(250) NULL,
    `email`           VARCHAR(150) NULL,
    `telefono`        VARCHAR(30)  NULL,
    `fecha_creacion`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idclifact`),
    KEY `idx_clifact_doc` (`numero_documento`),
    KEY `idx_clifact_cli` (`idcliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6) COMPROBANTE_ELECTRONICO (cola de envio + cabecera UBL)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comprobante_electronico` (
    `idcomprobante`     INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idempresa`         INT(10) UNSIGNED NOT NULL,
    `idorden`           INT(10) UNSIGNED NULL COMMENT 'orden del POS que origino el comprobante',
    `idclifact`         INT(10) UNSIGNED NULL,
    `idusuario`         INT(10) UNSIGNED NULL,
    `tipo_documento`    VARCHAR(2)  NOT NULL COMMENT '01=Factura, 03=Boleta',
    `serie`             VARCHAR(10) NOT NULL,
    `numero`            VARCHAR(15) NOT NULL,
    `numero_completo`   VARCHAR(30) AS (CONCAT(serie,'-',numero)) STORED,
    `fecha_emision`     DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `tipo_moneda`       VARCHAR(3)  NOT NULL DEFAULT 'PEN',
    `tipo_operacion`    VARCHAR(4)  NOT NULL DEFAULT '0101' COMMENT '0101=Venta interna',
    -- Cliente
    `cliente_tipo_doc`  VARCHAR(1)  NOT NULL DEFAULT '1',
    `cliente_num_doc`   VARCHAR(20) NOT NULL,
    `cliente_razon`     VARCHAR(200) NOT NULL,
    `cliente_direccion` VARCHAR(250) NULL,
    `cliente_email`     VARCHAR(150) NULL,
    -- Totales
    `subtotal`          DECIMAL(14,2) NOT NULL DEFAULT 0,
    `igv`               DECIMAL(14,2) NOT NULL DEFAULT 0,
    `total`             DECIMAL(14,2) NOT NULL DEFAULT 0,
    `tasa_igv`          DECIMAL(5,4)  NOT NULL DEFAULT 0.18,
    `total_letras`      VARCHAR(300)  NULL,
    `metodo_pago`       VARCHAR(20)   NULL,
    -- SUNAT
    `xml_nombre`        VARCHAR(150) NULL,
    `xml_ruta`          VARCHAR(300) NULL,
    `xml_hash`          VARCHAR(150) NULL COMMENT 'firma digital',
    `zip_ruta`          VARCHAR(300) NULL,
    `cdr_ruta`          VARCHAR(300) NULL,
    `pdf_ruta`          VARCHAR(300) NULL,
    `cdr_codigo`        VARCHAR(10)  NULL,
    `cdr_descripcion`   VARCHAR(500) NULL,
    `estado`            ENUM('pendiente','generado','enviando','aceptado','aceptado_observado','rechazado','baja','error')
                        NOT NULL DEFAULT 'pendiente',
    `intentos_envio`    INT(11)      NOT NULL DEFAULT 0,
    `fecha_generacion`  DATETIME NULL COMMENT 'cuando se genero el XML',
    `fecha_envio`       DATETIME NULL COMMENT 'cuando se envio a SUNAT',
    `fecha_baja`        DATETIME NULL,
    `motivo_baja`       VARCHAR(200) NULL,
    PRIMARY KEY (`idcomprobante`),
    UNIQUE KEY `uk_comp_doc` (`idempresa`,`tipo_documento`,`serie`,`numero`),
    KEY `idx_comp_estado` (`estado`),
    KEY `idx_comp_orden`  (`idorden`),
    KEY `idx_comp_fecha`  (`fecha_emision`),
    CONSTRAINT `fk_comp_emp`     FOREIGN KEY (`idempresa`) REFERENCES `empresa` (`idempresa`),
    CONSTRAINT `fk_comp_clifact` FOREIGN KEY (`idclifact`) REFERENCES `cliente_facturacion` (`idclifact`) ON DELETE SET NULL,
    CONSTRAINT `fk_comp_orden`   FOREIGN KEY (`idorden`)   REFERENCES `orden` (`idorden`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7) COMPROBANTE_DETALLE (items)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comprobante_detalle` (
    `iddetalle`        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idcomprobante`    INT(10) UNSIGNED NOT NULL,
    `linea`            INT(11) NOT NULL,
    `codigo`           VARCHAR(40)  NULL,
    `descripcion`      VARCHAR(250) NOT NULL,
    `unidad_medida`    VARCHAR(10)  NOT NULL DEFAULT 'NIU' COMMENT 'NIU=unidad, ZZ=servicio',
    `cantidad`         DECIMAL(12,3) NOT NULL,
    `precio_unitario`  DECIMAL(14,4) NOT NULL COMMENT 'sin IGV',
    `precio_con_igv`   DECIMAL(14,4) NOT NULL COMMENT 'con IGV',
    `valor_venta`      DECIMAL(14,2) NOT NULL,
    `igv_item`         DECIMAL(14,2) NOT NULL,
    `total_item`       DECIMAL(14,2) NOT NULL,
    `tipo_afectacion`  VARCHAR(2)   NOT NULL DEFAULT '10' COMMENT '10=Gravado',
    PRIMARY KEY (`iddetalle`),
    KEY `idx_det_comp` (`idcomprobante`),
    CONSTRAINT `fk_det_comp` FOREIGN KEY (`idcomprobante`) REFERENCES `comprobante_electronico` (`idcomprobante`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8) CDR_LOG (auditoria de respuestas SUNAT)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cdr_log` (
    `idlog`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idcomprobante` INT(10) UNSIGNED NOT NULL,
    `accion`        VARCHAR(40) NOT NULL,
    `codigo`        VARCHAR(10) NULL,
    `mensaje`       TEXT NULL,
    `request`       MEDIUMTEXT NULL,
    `response`      MEDIUMTEXT NULL,
    `fecha`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idlog`),
    KEY `idx_log_comp` (`idcomprobante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9) Permisos nuevos para SUNAT
-- ---------------------------------------------------------------------
INSERT INTO `permiso` (`codigo`,`nombre`,`descripcion`,`grupo`,`orden`) VALUES
    ('comprobantes_sunat', 'Comprobantes SUNAT',  'Ver y gestionar boletas/facturas electronicas', 'sunat', 20),
    ('emitir_boleta',      'Emitir Boleta',       'Emitir boletas electronicas al cobrar',         'sunat', 21),
    ('emitir_factura',     'Emitir Factura',      'Emitir facturas electronicas al cobrar',        'sunat', 22),
    ('enviar_sunat',       'Enviar a SUNAT',      'Enviar comprobantes a SUNAT',                   'sunat', 23),
    ('config_empresa',     'Configurar Empresa',  'Editar datos de la empresa emisora',            'sunat', 24),
    ('config_certificado', 'Configurar Certificado','Cargar y gestionar certificado digital',      'sunat', 25),
    ('config_numeracion',  'Configurar Numeracion','Gestionar series y correlativos',              'sunat', 26)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- Asignar TODOS los permisos SUNAT al rol Administrador
INSERT IGNORE INTO `rol_permiso` (`idrol`,`idpermiso`)
SELECT (SELECT idrol FROM rol WHERE codigo='admin'), p.idpermiso
FROM permiso p WHERE p.grupo='sunat';

-- Cajero puede emitir y ver (no enviar ni configurar)
INSERT IGNORE INTO `rol_permiso` (`idrol`,`idpermiso`)
SELECT (SELECT idrol FROM rol WHERE codigo='cajero'), p.idpermiso
FROM permiso p WHERE p.codigo IN ('comprobantes_sunat','emitir_boleta','emitir_factura');


-- ---------------------------------------------------------------------
-- SEED: empresa demo + certificado demo + rutas + numeracion inicial
-- ---------------------------------------------------------------------

-- Empresa DEMO (replica los datos de SystemRivascorp para reutilizar el certificado DEMO)
INSERT INTO `empresa` (
    `numero_ruc`, `razon_social`, `nombre_comercial`, `domicilio_fiscal`,
    `ubigeo`, `departamento`, `provincia`, `distrito`,
    `usuario_sol`, `clave_sol`, `ambiente`, `correo`, `telefono`
) VALUES (
    '20607027685',
    'YAPEZ POS DEMO E.I.R.L.',
    'YAPEZ POS',
    'Av. Demostracion 123 - Lima',
    '150101', 'Lima', 'Lima', 'Lima',
    'MODDATOS', 'moddatos', 'beta',
    'demo@yapez.local', '999999999'
)
ON DUPLICATE KEY UPDATE razon_social=VALUES(razon_social);

-- Rutas para la empresa demo (idempresa=1)
INSERT INTO `rutas` (`idempresa`) VALUES (1)
ON DUPLICATE KEY UPDATE idempresa=idempresa;

-- Certificado DEMO (apunta al .pfx que copiaremos a YAPEZ/certificado/)
INSERT INTO `certificado` (`idempresa`, `nombre_archivo`, `ruta`, `clave`, `tipo`, `activo`)
VALUES (1, 'LLAMA-PE-CERTIFICADO-DEMO-20607027685.pfx',
        '../certificado/LLAMA-PE-CERTIFICADO-DEMO-20607027685.pfx',
        '12345678', 'demo', 1)
ON DUPLICATE KEY UPDATE nombre_archivo=VALUES(nombre_archivo);

-- Numeracion inicial: F001 facturas y B001 boletas
INSERT INTO `numeracion` (`idempresa`,`tipo_documento`,`serie`,`ultimo_numero`,`descripcion`) VALUES
    (1, '01', 'F001', 0, 'Factura electronica'),
    (1, '03', 'B001', 0, 'Boleta de venta electronica'),
    (1, '07', 'FC01', 0, 'Nota de credito'),
    (1, '08', 'FD01', 0, 'Nota de debito')
ON DUPLICATE KEY UPDATE descripcion=VALUES(descripcion);
