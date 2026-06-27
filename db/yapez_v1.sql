-- =====================================================================
-- YAPEZ POS - Base de datos v1
-- Crea BD yapez_db con todas las tablas y datos demo (seed)
-- Compatible con MariaDB 10.4 (XAMPP)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `yapez_db`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `yapez_db`;

-- ---------------------------------------------------------------------
-- 1) CATEGORIA
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `categoria`;
CREATE TABLE `categoria` (
    `idcategoria` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo`      VARCHAR(40)  NOT NULL,
    `nombre`      VARCHAR(80)  NOT NULL,
    `icono`       VARCHAR(60)  NULL DEFAULT 'fa-tag',
    `color`       VARCHAR(20)  NULL DEFAULT '#6b7280',
    `orden`       INT(11)      NOT NULL DEFAULT 0,
    `estado`      TINYINT(4)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`idcategoria`),
    UNIQUE KEY `uk_categoria_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2) PRODUCTO
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `producto`;
CREATE TABLE `producto` (
    `idproducto`  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo`      VARCHAR(40)  NOT NULL,
    `nombre`      VARCHAR(150) NOT NULL,
    `precio`      DECIMAL(12,2) NOT NULL DEFAULT 0,
    `idcategoria` INT(10) UNSIGNED NULL,
    `imagen`      VARCHAR(500) NULL,
    `popular`     TINYINT(4)   NOT NULL DEFAULT 0,
    `favorito`    TINYINT(4)   NOT NULL DEFAULT 0,
    `estado`      TINYINT(4)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`idproducto`),
    UNIQUE KEY `uk_producto_codigo` (`codigo`),
    KEY `idx_producto_cat` (`idcategoria`),
    CONSTRAINT `fk_producto_categoria`
        FOREIGN KEY (`idcategoria`) REFERENCES `categoria` (`idcategoria`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3) MESA
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `mesa`;
CREATE TABLE `mesa` (
    `idmesa`     INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `numero`     INT(11)     NOT NULL,
    `capacidad`  TINYINT(4)  NOT NULL DEFAULT 4,
    `estado`     ENUM('libre','ocupada','cuenta','reservada','bloqueada')
                 NOT NULL DEFAULT 'libre',
    `activo`     TINYINT(4)  NOT NULL DEFAULT 1,
    PRIMARY KEY (`idmesa`),
    UNIQUE KEY `uk_mesa_numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4) CLIENTE
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `cliente`;
CREATE TABLE `cliente` (
    `idcliente`     INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre`        VARCHAR(150) NOT NULL,
    `documento`     VARCHAR(20)  NULL,
    `telefono`      VARCHAR(20)  NULL,
    `email`         VARCHAR(150) NULL,
    `total_ordenes` INT(11)      NOT NULL DEFAULT 0,
    `total_gastado` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `ultima_visita` DATE         NULL,
    `estado`        TINYINT(4)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`idcliente`),
    KEY `idx_cliente_doc` (`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5) CAJA_SESION
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `caja_sesion`;
CREATE TABLE `caja_sesion` (
    `idsesion`       INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `caja_codigo`    VARCHAR(20)  NOT NULL DEFAULT 'AP-001',
    `turno`          VARCHAR(20)  NOT NULL DEFAULT 'Mañana',
    `cajero`         VARCHAR(100) NOT NULL DEFAULT 'Cajero',
    `monto_inicial`  DECIMAL(12,2) NOT NULL DEFAULT 0,
    `monto_cierre`   DECIMAL(12,2) NULL,
    `fecha_apertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_cierre`   DATETIME NULL,
    `abierta`        TINYINT(4) NOT NULL DEFAULT 1,
    PRIMARY KEY (`idsesion`),
    KEY `idx_sesion_abierta` (`abierta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6) CAJA_MOVIMIENTO
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `caja_movimiento`;
CREATE TABLE `caja_movimiento` (
    `idmovimiento` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idsesion`     INT(10) UNSIGNED NOT NULL,
    `tipo`         ENUM('apertura','ingreso','egreso','venta','cierre')
                   NOT NULL,
    `monto`        DECIMAL(12,2) NOT NULL DEFAULT 0,
    `nota`         VARCHAR(250) NULL,
    `metodo_pago`  VARCHAR(20)  NULL,
    `idorden`      INT(10) UNSIGNED NULL,
    `fecha`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idmovimiento`),
    KEY `idx_mov_sesion` (`idsesion`),
    KEY `idx_mov_tipo`   (`tipo`),
    CONSTRAINT `fk_mov_sesion`
        FOREIGN KEY (`idsesion`) REFERENCES `caja_sesion` (`idsesion`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7) ORDEN (cabecera)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `orden`;
CREATE TABLE `orden` (
    `idorden`      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `numero`       VARCHAR(10)  NOT NULL,
    `idmesa`       INT(10) UNSIGNED NULL,
    `idcliente`    INT(10) UNSIGNED NULL,
    `idsesion`     INT(10) UNSIGNED NULL,
    `tipo`         ENUM('dine_in','para_llevar','delivery') NOT NULL DEFAULT 'dine_in',
    `estado`       ENUM('en_curso','enviada','pagada','anulada') NOT NULL DEFAULT 'en_curso',
    `mozo`         VARCHAR(100) NULL,
    `observacion`  VARCHAR(300) NULL,
    `subtotal`     DECIMAL(12,2) NOT NULL DEFAULT 0,
    `igv`          DECIMAL(12,2) NOT NULL DEFAULT 0,
    `total`        DECIMAL(12,2) NOT NULL DEFAULT 0,
    `metodo_pago`  ENUM('','efectivo','tarjeta','yape','transferencia') NOT NULL DEFAULT '',
    `fecha`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_pago`   DATETIME NULL,
    PRIMARY KEY (`idorden`),
    UNIQUE KEY `uk_orden_numero` (`numero`),
    KEY `idx_orden_mesa`     (`idmesa`),
    KEY `idx_orden_cliente`  (`idcliente`),
    KEY `idx_orden_estado`   (`estado`),
    KEY `idx_orden_fecha`    (`fecha`),
    CONSTRAINT `fk_orden_mesa`
        FOREIGN KEY (`idmesa`) REFERENCES `mesa` (`idmesa`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_orden_cliente`
        FOREIGN KEY (`idcliente`) REFERENCES `cliente` (`idcliente`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_orden_sesion`
        FOREIGN KEY (`idsesion`) REFERENCES `caja_sesion` (`idsesion`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8) ORDEN_DETALLE (items)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `orden_detalle`;
CREATE TABLE `orden_detalle` (
    `iddetalle`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idorden`     INT(10) UNSIGNED NOT NULL,
    `idproducto`  INT(10) UNSIGNED NOT NULL,
    `nombre`      VARCHAR(150) NOT NULL,
    `cantidad`    DECIMAL(10,2) NOT NULL DEFAULT 1,
    `precio`      DECIMAL(12,2) NOT NULL DEFAULT 0,
    `subtotal`    DECIMAL(12,2) NOT NULL DEFAULT 0,
    `nota`        VARCHAR(200) NULL,
    `estado`      ENUM('pendiente','en_preparacion','listo','servido','anulado')
                  NOT NULL DEFAULT 'pendiente',
    PRIMARY KEY (`iddetalle`),
    KEY `idx_detalle_orden`     (`idorden`),
    KEY `idx_detalle_producto`  (`idproducto`),
    CONSTRAINT `fk_detalle_orden`
        FOREIGN KEY (`idorden`) REFERENCES `orden` (`idorden`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_detalle_producto`
        FOREIGN KEY (`idproducto`) REFERENCES `producto` (`idproducto`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- SEED (datos demo, replica los del app.js original)
-- =====================================================================

INSERT INTO `categoria` (`codigo`,`nombre`,`icono`,`color`,`orden`) VALUES
    ('entradas',    'Entradas',       'fa-leaf',         '#10b981', 1),
    ('platos',      'Platos Fuertes', 'fa-utensils',     '#dc2626', 2),
    ('parrillas',   'Parrillas',      'fa-fire',         '#f59e0b', 3),
    ('pastas',      'Pastas',         'fa-bowl-food',    '#f59e0b', 4),
    ('pizzas',      'Pizzas',         'fa-pizza-slice',  '#dc2626', 5),
    ('bebidas',     'Bebidas',        'fa-mug-saucer',   '#10b981', 6),
    ('postres',     'Postres',        'fa-ice-cream',    '#f59e0b', 7),
    ('promociones', 'Promociones',    'fa-tag',          '#dc2626', 8);

INSERT INTO `producto` (`codigo`,`nombre`,`precio`,`idcategoria`,`imagen`,`popular`,`favorito`) VALUES
    ('p1',  'Lomo Saltado',           28.00, (SELECT idcategoria FROM categoria WHERE codigo='platos'),    'https://images.unsplash.com/photo-1626804475297-41608ea09aeb?w=400&q=80', 0, 0),
    ('p2',  'Arroz con Mariscos',     32.00, (SELECT idcategoria FROM categoria WHERE codigo='platos'),    'https://images.unsplash.com/photo-1633504581786-316c8002b1b9?w=400&q=80', 1, 1),
    ('p3',  'Pizza Americana',        26.00, (SELECT idcategoria FROM categoria WHERE codigo='pizzas'),    'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400&q=80', 0, 0),
    ('p4',  'Hamburguesa Clasica',    24.00, (SELECT idcategoria FROM categoria WHERE codigo='platos'),    'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80', 0, 0),
    ('p5',  'Tallarin Saltado',       25.00, (SELECT idcategoria FROM categoria WHERE codigo='pastas'),    'https://images.unsplash.com/photo-1552611052-33e04de081de?w=400&q=80', 0, 0),
    ('p6',  'Parrilla Mixta',         58.00, (SELECT idcategoria FROM categoria WHERE codigo='parrillas'), 'https://images.unsplash.com/photo-1544025162-d76694265947?w=400&q=80', 0, 0),
    ('p7',  'Ensalada Cesar',         18.00, (SELECT idcategoria FROM categoria WHERE codigo='entradas'),  'https://images.unsplash.com/photo-1546793665-c74683f339c1?w=400&q=80', 0, 0),
    ('p8',  'Ceviche Mixto',          10.00, (SELECT idcategoria FROM categoria WHERE codigo='entradas'),  'https://images.unsplash.com/photo-1626200926749-cd28f0a47b21?w=400&q=80', 0, 0),
    ('p9',  'Chicha Morada',           8.00, (SELECT idcategoria FROM categoria WHERE codigo='bebidas'),   'https://images.unsplash.com/photo-1597481499750-3e6b22637e12?w=400&q=80', 0, 0),
    ('p10', 'Limonada',                8.00, (SELECT idcategoria FROM categoria WHERE codigo='bebidas'),   'https://images.unsplash.com/photo-1621263764928-df1444c5e859?w=400&q=80', 0, 0),
    ('p11', 'Inca Kola 500ml',         6.00, (SELECT idcategoria FROM categoria WHERE codigo='bebidas'),   'https://images.unsplash.com/photo-1625772299848-391b6a87d7b3?w=400&q=80', 0, 0),
    ('p12', 'Cheesecake',             16.00, (SELECT idcategoria FROM categoria WHERE codigo='postres'),   'https://images.unsplash.com/photo-1567171466295-4afa63d45416?w=400&q=80', 0, 0);

INSERT INTO `mesa` (`numero`,`capacidad`,`estado`) VALUES
    ( 1, 2, 'libre'),
    ( 2, 4, 'ocupada'),
    ( 3, 4, 'ocupada'),
    ( 4, 6, 'ocupada'),
    ( 5, 4, 'ocupada'),
    ( 6, 6, 'libre'),
    ( 7, 2, 'libre'),
    ( 8, 4, 'ocupada'),
    ( 9, 4, 'libre'),
    (10, 6, 'libre'),
    (11, 8, 'reservada'),
    (12, 2, 'libre');

INSERT INTO `cliente` (`nombre`,`documento`,`telefono`,`email`,`total_ordenes`,`total_gastado`,`ultima_visita`) VALUES
    ('Maria Garcia',   '45678912', '987654321', 'maria@email.com', 12,  458.50, '2026-04-28'),
    ('Carlos Mendoza', '78912345', '912345678', 'carlos@email.com', 8,  312.00, '2026-05-02'),
    ('Ana Torres',     '12345678', '954321876', 'ana@email.com',   25, 1256.80, '2026-05-05'),
    ('Luis Ramirez',   '87654321', '998877665', 'luis@email.com',   3,  124.00, '2026-04-15'),
    ('Sofia Vargas',   '23456789', '923456789', 'sofia@email.com', 17,  689.30, '2026-05-06');

-- Sesion de caja abierta inicial
INSERT INTO `caja_sesion` (`caja_codigo`,`turno`,`cajero`,`monto_inicial`,`fecha_apertura`,`abierta`) VALUES
    ('AP-001','Mañana','Juan Perez', 200.00, NOW(), 1);

INSERT INTO `caja_movimiento` (`idsesion`,`tipo`,`monto`,`nota`) VALUES
    (1, 'apertura', 200.00, 'Apertura de caja');

-- Ordenes demo (1 en curso + 4 pagadas)
INSERT INTO `orden` (`numero`,`idmesa`,`idcliente`,`idsesion`,`tipo`,`estado`,`mozo`,`subtotal`,`igv`,`total`,`metodo_pago`,`fecha`,`fecha_pago`) VALUES
    ('00058', (SELECT idmesa FROM mesa WHERE numero=5), NULL, 1, 'dine_in',     'en_curso', 'Juan Perez',  94.00, 16.92, 110.92, '',          NOW(), NULL),
    ('00057', (SELECT idmesa FROM mesa WHERE numero=2), 1,    1, 'dine_in',     'pagada',   'Juan Perez',  70.00, 12.60,  82.60, 'efectivo',  DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
    ('00056', NULL,                                     3,    1, 'delivery',    'pagada',   'Juan Perez',  68.00, 12.24,  80.24, 'yape',      DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
    ('00055', (SELECT idmesa FROM mesa WHERE numero=8), 2,    1, 'dine_in',     'pagada',   'Juan Perez',  88.00, 15.84, 103.84, 'tarjeta',   DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
    ('00054', NULL,                                     NULL, 1, 'para_llevar', 'pagada',   'Juan Perez',  25.00,  4.50,  29.50, 'efectivo',  DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Detalle de las ordenes demo
INSERT INTO `orden_detalle` (`idorden`,`idproducto`,`nombre`,`cantidad`,`precio`,`subtotal`,`nota`) VALUES
    -- Orden 00058 (en curso)
    ((SELECT idorden FROM orden WHERE numero='00058'), (SELECT idproducto FROM producto WHERE codigo='p1'), 'Lomo Saltado',         1, 28.00, 28.00, 'A punto'),
    ((SELECT idorden FROM orden WHERE numero='00058'), (SELECT idproducto FROM producto WHERE codigo='p2'), 'Arroz con Mariscos',   1, 32.00, 32.00, 'Sin picante'),
    ((SELECT idorden FROM orden WHERE numero='00058'), (SELECT idproducto FROM producto WHERE codigo='p9'), 'Chicha Morada',        2,  8.00, 16.00, 'Vaso grande'),
    ((SELECT idorden FROM orden WHERE numero='00058'), (SELECT idproducto FROM producto WHERE codigo='p7'), 'Ensalada Cesar',       1, 18.00, 18.00, 'Extra pollo'),
    -- Orden 00057
    ((SELECT idorden FROM orden WHERE numero='00057'), (SELECT idproducto FROM producto WHERE codigo='p6'),  'Parrilla Mixta',      1, 58.00, 58.00, ''),
    ((SELECT idorden FROM orden WHERE numero='00057'), (SELECT idproducto FROM producto WHERE codigo='p11'), 'Inca Kola 500ml',     2,  6.00, 12.00, ''),
    -- Orden 00056
    ((SELECT idorden FROM orden WHERE numero='00056'), (SELECT idproducto FROM producto WHERE codigo='p3'),  'Pizza Americana',     2, 26.00, 52.00, ''),
    ((SELECT idorden FROM orden WHERE numero='00056'), (SELECT idproducto FROM producto WHERE codigo='p10'), 'Limonada',            2,  8.00, 16.00, ''),
    -- Orden 00055
    ((SELECT idorden FROM orden WHERE numero='00055'), (SELECT idproducto FROM producto WHERE codigo='p1'),  'Lomo Saltado',        2, 28.00, 56.00, ''),
    ((SELECT idorden FROM orden WHERE numero='00055'), (SELECT idproducto FROM producto WHERE codigo='p9'),  'Chicha Morada',       2,  8.00, 16.00, ''),
    ((SELECT idorden FROM orden WHERE numero='00055'), (SELECT idproducto FROM producto WHERE codigo='p12'), 'Cheesecake',          1, 16.00, 16.00, ''),
    -- Orden 00054
    ((SELECT idorden FROM orden WHERE numero='00054'), (SELECT idproducto FROM producto WHERE codigo='p5'),  'Tallarin Saltado',    1, 25.00, 25.00, '');
