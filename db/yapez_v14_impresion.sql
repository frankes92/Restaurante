-- Migracion v14: Impresion en red (cola + bridge)
-- Permite imprimir comandas de cocina y tickets desde sistema cloud
-- a impresoras termicas LAN via un puente PHP que corre en una PC del local.

-- ============================================================
-- 1) Tabla `impresora` — configuracion de impresoras de la red local
-- ============================================================
CREATE TABLE IF NOT EXISTS `impresora` (
    `idimpresora`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre`        VARCHAR(80)      NOT NULL,
    `ip`            VARCHAR(45)      NOT NULL,
    `puerto`        INT(5)           NOT NULL DEFAULT 9100,
    `tipo`          ENUM('cocina','bar','caja','otro') NOT NULL DEFAULT 'cocina',
    `ancho_cols`    INT(3)           NOT NULL DEFAULT 32, -- caracteres por linea (32 para 58mm, 48 para 80mm)
    `cortar_papel`  TINYINT(1)       NOT NULL DEFAULT 1,
    `activa`        TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idimpresora`),
    KEY `idx_imp_tipo` (`tipo`, `activa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2) Tabla `cola_impresion` — trabajos pendientes para el bridge
-- ============================================================
CREATE TABLE IF NOT EXISTS `cola_impresion` (
    `idcola`        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idimpresora`   INT(10) UNSIGNED NOT NULL,
    `idorden`       INT(10) UNSIGNED NULL,
    `tipo`          ENUM('comanda','comanda_anular','ticket','prueba')
                    NOT NULL DEFAULT 'comanda',
    `payload`       MEDIUMTEXT       NOT NULL, -- JSON con datos del trabajo
    `estado`        ENUM('pendiente','imprimiendo','impreso','error')
                    NOT NULL DEFAULT 'pendiente',
    `intentos`      TINYINT(2)       NOT NULL DEFAULT 0,
    `error_msg`     VARCHAR(255)     NULL,
    `fecha_creacion`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_impresion` DATETIME       NULL,
    PRIMARY KEY (`idcola`),
    KEY `idx_cola_estado` (`estado`, `fecha_creacion`),
    KEY `idx_cola_imp` (`idimpresora`),
    CONSTRAINT `fk_cola_imp` FOREIGN KEY (`idimpresora`) REFERENCES `impresora` (`idimpresora`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3) Permiso 'impresoras' (solo admin gestiona)
-- ============================================================
INSERT IGNORE INTO `permiso` (`codigo`, `nombre`, `descripcion`)
VALUES ('impresoras', 'Gestionar impresoras', 'Configurar IPs y tipos de impresoras LAN');

INSERT IGNORE INTO `rol_permiso` (`idrol`, `idpermiso`)
SELECT r.idrol, p.idpermiso
FROM `rol` r CROSS JOIN `permiso` p
WHERE r.codigo = 'admin' AND p.codigo = 'impresoras';
