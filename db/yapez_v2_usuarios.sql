-- =====================================================================
-- YAPEZ POS - Modulo de usuarios, roles y permisos (v2)
-- BD: yapez_db
-- Patron de referencia: krxpilhe_db_tukifact (usuario + permiso + usuario_permiso)
-- Diferencia: agrega tabla rol y rol_permiso para gestion mas simple,
--             usuario_permiso permite override (grant/revoke) sobre el rol.
-- =====================================================================

USE `yapez_db`;

-- ---------------------------------------------------------------------
-- 1) ROL
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `usuario_permiso`;
DROP TABLE IF EXISTS `rol_permiso`;
DROP TABLE IF EXISTS `permiso`;
DROP TABLE IF EXISTS `usuario`;
DROP TABLE IF EXISTS `rol`;

CREATE TABLE `rol` (
    `idrol`        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo`       VARCHAR(40) NOT NULL,
    `nombre`       VARCHAR(60) NOT NULL,
    `descripcion`  VARCHAR(200) NULL,
    `estado`       TINYINT(4)  NOT NULL DEFAULT 1,
    PRIMARY KEY (`idrol`),
    UNIQUE KEY `uk_rol_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2) USUARIO
-- ---------------------------------------------------------------------
CREATE TABLE `usuario` (
    `idusuario`      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idrol`          INT(10) UNSIGNED NULL,
    `nombre`         VARCHAR(60)  NOT NULL,
    `apellidos`      VARCHAR(80)  NULL,
    `tipo_documento` VARCHAR(8)   NULL,
    `num_documento`  VARCHAR(20)  NULL,
    `telefono`       VARCHAR(20)  NULL,
    `email`          VARCHAR(120) NULL,
    `login`          VARCHAR(45)  NOT NULL,
    `clave`          VARCHAR(64)  NOT NULL COMMENT 'SHA256',
    `imagen`         VARCHAR(120) NULL,
    `condicion`      TINYINT(4)   NOT NULL DEFAULT 1,
    `ultimo_acceso`  DATETIME     NULL,
    PRIMARY KEY (`idusuario`),
    UNIQUE KEY `uk_usuario_login` (`login`),
    KEY `idx_usuario_rol` (`idrol`),
    CONSTRAINT `fk_usuario_rol`
        FOREIGN KEY (`idrol`) REFERENCES `rol` (`idrol`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3) PERMISO
-- ---------------------------------------------------------------------
CREATE TABLE `permiso` (
    `idpermiso`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo`      VARCHAR(40) NOT NULL,
    `nombre`      VARCHAR(80) NOT NULL,
    `descripcion` VARCHAR(200) NULL,
    `grupo`       VARCHAR(40) NOT NULL DEFAULT 'general',
    `orden`       INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`idpermiso`),
    UNIQUE KEY `uk_permiso_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4) ROL_PERMISO (permisos por defecto de cada rol)
-- ---------------------------------------------------------------------
CREATE TABLE `rol_permiso` (
    `idrol`     INT(10) UNSIGNED NOT NULL,
    `idpermiso` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`idrol`, `idpermiso`),
    KEY `idx_rp_permiso` (`idpermiso`),
    CONSTRAINT `fk_rp_rol`
        FOREIGN KEY (`idrol`) REFERENCES `rol` (`idrol`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permiso`
        FOREIGN KEY (`idpermiso`) REFERENCES `permiso` (`idpermiso`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5) USUARIO_PERMISO (override puntual: grant adiciona, revoke quita)
-- ---------------------------------------------------------------------
CREATE TABLE `usuario_permiso` (
    `idusuario` INT(10) UNSIGNED NOT NULL,
    `idpermiso` INT(10) UNSIGNED NOT NULL,
    `tipo`      ENUM('grant','revoke') NOT NULL DEFAULT 'grant',
    PRIMARY KEY (`idusuario`, `idpermiso`),
    KEY `idx_up_permiso` (`idpermiso`),
    CONSTRAINT `fk_up_usuario`
        FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE,
    CONSTRAINT `fk_up_permiso`
        FOREIGN KEY (`idpermiso`) REFERENCES `permiso` (`idpermiso`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6) Agregar idusuario a orden y caja_sesion (para auditoria)
-- ---------------------------------------------------------------------
ALTER TABLE `orden`
    ADD COLUMN `idusuario` INT(10) UNSIGNED NULL AFTER `idsesion`;

ALTER TABLE `caja_sesion`
    ADD COLUMN `idusuario` INT(10) UNSIGNED NULL AFTER `cajero`;


-- =====================================================================
-- SEED
-- =====================================================================

INSERT INTO `rol` (`codigo`,`nombre`,`descripcion`) VALUES
    ('admin',  'Administrador', 'Acceso total al sistema'),
    ('cajero', 'Cajero',        'Toma ordenes, cobra y maneja caja'),
    ('mozo',   'Mozo',          'Toma ordenes y envia a cocina');

INSERT INTO `permiso` (`codigo`,`nombre`,`descripcion`,`grupo`,`orden`) VALUES
    ('nuevaorden',    'Nueva Orden',         'Tomar nuevas ordenes (POS)',           'operacion', 1),
    ('mesas',         'Gestionar Mesas',     'Ver y administrar mesas del salon',    'operacion', 2),
    ('pedidos',       'Ver Pedidos',         'Listar y revisar pedidos activos',     'operacion', 3),
    ('enviar_cocina', 'Enviar a Cocina',     'Enviar items en preparacion a cocina', 'operacion', 4),
    ('cobrar',        'Cobrar Ordenes',      'Cobrar y cerrar ordenes',              'caja',      5),
    ('anular_orden',  'Anular Ordenes',      'Anular ordenes activas',               'caja',      6),
    ('clientes',      'Gestionar Clientes',  'CRUD de clientes',                     'maestros',  7),
    ('historial',     'Ver Historial',       'Consultar historial de ventas',        'reportes',  8),
    ('caja',          'Operar Caja',         'Apertura/cierre y movimientos',        'caja',      9),
    ('reportes',      'Ver Reportes',        'Acceso a metricas y graficos',         'reportes', 10),
    ('productos',     'Gestionar Productos', 'CRUD productos y categorias',          'maestros', 11),
    ('usuarios',      'Gestionar Usuarios',  'CRUD usuarios, roles y permisos',      'admin',    12);

-- Permisos del rol Administrador: TODOS
INSERT INTO `rol_permiso` (`idrol`, `idpermiso`)
SELECT r.idrol, p.idpermiso
FROM rol r CROSS JOIN permiso p
WHERE r.codigo='admin';

-- Permisos del rol Cajero
INSERT INTO `rol_permiso` (`idrol`, `idpermiso`)
SELECT r.idrol, p.idpermiso
FROM rol r CROSS JOIN permiso p
WHERE r.codigo='cajero'
  AND p.codigo IN ('nuevaorden','mesas','pedidos','enviar_cocina','cobrar','anular_orden','clientes','historial','caja');

-- Permisos del rol Mozo
INSERT INTO `rol_permiso` (`idrol`, `idpermiso`)
SELECT r.idrol, p.idpermiso
FROM rol r CROSS JOIN permiso p
WHERE r.codigo='mozo'
  AND p.codigo IN ('nuevaorden','mesas','pedidos','enviar_cocina');


-- Usuarios demo (claves SHA256)
-- admin    / admin123   -> 240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9
-- cajero   / cajero123  -> 0f2ddf1c84cee99b58fc1c9047ee4f6c1bdcb4c4c9c87e3a09dbd024dadf80fb
-- mozo     / mozo123    -> 1de6e14c39604fe44efc2c39a4604eed7dc41a9f04b2b27c3e9f5fe6f4f15a4f

INSERT INTO `usuario` (`idrol`,`nombre`,`apellidos`,`login`,`clave`,`email`,`condicion`) VALUES
    ((SELECT idrol FROM rol WHERE codigo='admin'),  'Admin',  'YAPEZ',  'admin',  SHA2('admin123',  256), 'admin@yapez.local',  1),
    ((SELECT idrol FROM rol WHERE codigo='cajero'), 'Juan',   'Perez',  'cajero', SHA2('cajero123', 256), 'cajero@yapez.local', 1),
    ((SELECT idrol FROM rol WHERE codigo='mozo'),   'Carlos', 'Rivera', 'mozo',   SHA2('mozo123',   256), 'mozo@yapez.local',   1);

-- Asignar usuario admin a la sesion de caja existente
UPDATE `caja_sesion`
SET `idusuario` = (SELECT idusuario FROM usuario WHERE login='admin' LIMIT 1)
WHERE idsesion=1;
