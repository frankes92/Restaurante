/*
Navicat MySQL Data Transfer

Source Server         : MYSQL-NUBE
Source Server Version : 110410
Source Host           : 50.31.174.155:3306
Source Database       : jprqwdud_puertohabana_db

Target Server Type    : MYSQL
Target Server Version : 110410
File Encoding         : 65001

Date: 2026-06-03 12:04:39
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for caja_arqueo
-- ----------------------------
DROP TABLE IF EXISTS `caja_arqueo`;
CREATE TABLE `caja_arqueo` (
  `idarqueo` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idsesion` int(10) unsigned NOT NULL,
  `idusuario` int(10) unsigned DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `monto_sistema` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'saldo esperado segun ventas',
  `monto_contado` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'monto fisico contado',
  `diferencia` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'contado - sistema (faltante negativo)',
  `observacion` varchar(300) DEFAULT NULL,
  `denominaciones` text DEFAULT NULL COMMENT 'JSON con cantidad por billete/moneda',
  PRIMARY KEY (`idarqueo`),
  KEY `idx_arq_sesion` (`idsesion`),
  KEY `idx_arq_fecha` (`fecha`),
  CONSTRAINT `fk_arq_sesion` FOREIGN KEY (`idsesion`) REFERENCES `caja_sesion` (`idsesion`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of caja_arqueo
-- ----------------------------

-- ----------------------------
-- Table structure for caja_movimiento
-- ----------------------------
DROP TABLE IF EXISTS `caja_movimiento`;
CREATE TABLE `caja_movimiento` (
  `idmovimiento` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idsesion` int(10) unsigned NOT NULL,
  `tipo` enum('apertura','ingreso','egreso','venta','cierre') NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `nota` varchar(250) DEFAULT NULL,
  `metodo_pago` varchar(20) DEFAULT NULL,
  `idorden` int(10) unsigned DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idmovimiento`),
  KEY `idx_mov_sesion` (`idsesion`),
  KEY `idx_mov_tipo` (`tipo`),
  CONSTRAINT `fk_mov_sesion` FOREIGN KEY (`idsesion`) REFERENCES `caja_sesion` (`idsesion`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of caja_movimiento
-- ----------------------------
INSERT INTO `caja_movimiento` VALUES ('1', '1', 'apertura', '20.00', 'Apertura de caja', null, null, '2026-06-02 20:08:06');

-- ----------------------------
-- Table structure for caja_sesion
-- ----------------------------
DROP TABLE IF EXISTS `caja_sesion`;
CREATE TABLE `caja_sesion` (
  `idsesion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `caja_codigo` varchar(20) NOT NULL DEFAULT 'AP-001',
  `turno` varchar(20) NOT NULL DEFAULT 'Mañana',
  `cajero` varchar(100) NOT NULL DEFAULT 'Cajero',
  `idusuario` int(10) unsigned DEFAULT NULL,
  `monto_inicial` decimal(12,2) NOT NULL DEFAULT 0.00,
  `monto_cierre` decimal(12,2) DEFAULT NULL,
  `fecha_apertura` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `abierta` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idsesion`),
  KEY `idx_sesion_abierta` (`abierta`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of caja_sesion
-- ----------------------------
INSERT INTO `caja_sesion` VALUES ('1', 'AP-001', 'Noche', 'Admin Puerto Habana', '1', '20.00', null, '2026-06-02 20:08:06', null, '1');

-- ----------------------------
-- Table structure for categoria
-- ----------------------------
DROP TABLE IF EXISTS `categoria`;
CREATE TABLE `categoria` (
  `idcategoria` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `icono` varchar(60) DEFAULT 'fa-tag',
  `color` varchar(20) DEFAULT '#6b7280',
  `orden` int(11) NOT NULL DEFAULT 0,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idcategoria`),
  UNIQUE KEY `uk_categoria_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of categoria
-- ----------------------------
INSERT INTO `categoria` VALUES ('1', 'ENTRADAS', 'ENTRADAS', 'fa-tag', '#5b3df5', '1', '1');
INSERT INTO `categoria` VALUES ('2', 'CEVICHES', 'CEVICHES', 'fa-lemon', '#e1fd0d', '2', '1');
INSERT INTO `categoria` VALUES ('3', 'ARROCES', 'ARROCES', 'fa-bowl-rice', '#dddbeb', '3', '1');
INSERT INTO `categoria` VALUES ('4', 'DUOS', 'DUOS', 'fa-fish', '#5b3df5', '4', '1');

-- ----------------------------
-- Table structure for cdr_log
-- ----------------------------
DROP TABLE IF EXISTS `cdr_log`;
CREATE TABLE `cdr_log` (
  `idlog` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idcomprobante` int(10) unsigned NOT NULL,
  `accion` varchar(40) NOT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `request` mediumtext DEFAULT NULL,
  `response` mediumtext DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idlog`),
  KEY `idx_log_comp` (`idcomprobante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of cdr_log
-- ----------------------------

-- ----------------------------
-- Table structure for certificado
-- ----------------------------
DROP TABLE IF EXISTS `certificado`;
CREATE TABLE `certificado` (
  `idcertificado` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idempresa` int(10) unsigned NOT NULL,
  `nombre_archivo` varchar(150) NOT NULL,
  `ruta` varchar(300) NOT NULL,
  `clave` varchar(500) DEFAULT NULL COMMENT 'cifrado AES',
  `tipo` enum('demo','produccion') NOT NULL DEFAULT 'demo',
  `fecha_carga` datetime NOT NULL DEFAULT current_timestamp(),
  `vencimiento` date DEFAULT NULL,
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idcertificado`),
  KEY `idx_cert_emp` (`idempresa`),
  CONSTRAINT `fk_cert_emp` FOREIGN KEY (`idempresa`) REFERENCES `empresa` (`idempresa`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of certificado
-- ----------------------------

-- ----------------------------
-- Table structure for cliente
-- ----------------------------
DROP TABLE IF EXISTS `cliente`;
CREATE TABLE `cliente` (
  `idcliente` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `documento` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `total_ordenes` int(11) NOT NULL DEFAULT 0,
  `total_gastado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `ultima_visita` date DEFAULT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idcliente`),
  KEY `idx_cliente_doc` (`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of cliente
-- ----------------------------

-- ----------------------------
-- Table structure for cliente_facturacion
-- ----------------------------
DROP TABLE IF EXISTS `cliente_facturacion`;
CREATE TABLE `cliente_facturacion` (
  `idclifact` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idcliente` int(10) unsigned DEFAULT NULL COMMENT 'puede vincularse a tabla cliente, pero no obligatorio',
  `tipo_documento` varchar(1) NOT NULL DEFAULT '1' COMMENT '0=DNI extranjero, 1=DNI, 6=RUC, 7=Pasaporte, 4=CE',
  `numero_documento` varchar(20) NOT NULL,
  `razon_social` varchar(200) NOT NULL,
  `direccion` varchar(250) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idclifact`),
  KEY `idx_clifact_doc` (`numero_documento`),
  KEY `idx_clifact_cli` (`idcliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of cliente_facturacion
-- ----------------------------

-- ----------------------------
-- Table structure for cola_impresion
-- ----------------------------
DROP TABLE IF EXISTS `cola_impresion`;
CREATE TABLE `cola_impresion` (
  `idcola` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idimpresora` int(10) unsigned NOT NULL,
  `idorden` int(10) unsigned DEFAULT NULL,
  `tipo` enum('comanda','comanda_anular','ticket','prueba') NOT NULL DEFAULT 'comanda',
  `payload` mediumtext NOT NULL,
  `estado` enum('pendiente','imprimiendo','impreso','error') NOT NULL DEFAULT 'pendiente',
  `intentos` tinyint(2) NOT NULL DEFAULT 0,
  `error_msg` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_impresion` datetime DEFAULT NULL,
  PRIMARY KEY (`idcola`),
  KEY `idx_cola_estado` (`estado`,`fecha_creacion`),
  KEY `idx_cola_imp` (`idimpresora`),
  CONSTRAINT `fk_cola_imp` FOREIGN KEY (`idimpresora`) REFERENCES `impresora` (`idimpresora`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of cola_impresion
-- ----------------------------

-- ----------------------------
-- Table structure for comprobante_detalle
-- ----------------------------
DROP TABLE IF EXISTS `comprobante_detalle`;
CREATE TABLE `comprobante_detalle` (
  `iddetalle` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idcomprobante` int(10) unsigned NOT NULL,
  `linea` int(11) NOT NULL,
  `codigo` varchar(40) DEFAULT NULL,
  `descripcion` varchar(250) NOT NULL,
  `unidad_medida` varchar(10) NOT NULL DEFAULT 'NIU' COMMENT 'NIU=unidad, ZZ=servicio',
  `cantidad` decimal(12,3) NOT NULL,
  `precio_unitario` decimal(14,4) NOT NULL COMMENT 'sin IGV',
  `precio_con_igv` decimal(14,4) NOT NULL COMMENT 'con IGV',
  `valor_venta` decimal(14,2) NOT NULL,
  `igv_item` decimal(14,2) NOT NULL,
  `total_item` decimal(14,2) NOT NULL,
  `tipo_afectacion` varchar(2) NOT NULL DEFAULT '10' COMMENT '10=Gravado',
  `codigo_afectacion` varchar(2) NOT NULL DEFAULT '10' COMMENT '10=Gravado, 20=Exonerado, 30=Inafecto (cat. 7)',
  PRIMARY KEY (`iddetalle`),
  KEY `idx_det_comp` (`idcomprobante`),
  CONSTRAINT `fk_det_comp` FOREIGN KEY (`idcomprobante`) REFERENCES `comprobante_electronico` (`idcomprobante`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of comprobante_detalle
-- ----------------------------

-- ----------------------------
-- Table structure for comprobante_electronico
-- ----------------------------
DROP TABLE IF EXISTS `comprobante_electronico`;
CREATE TABLE `comprobante_electronico` (
  `idcomprobante` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idempresa` int(10) unsigned NOT NULL,
  `idorden` int(10) unsigned DEFAULT NULL COMMENT 'orden del POS que origino el comprobante',
  `ref_idcomprobante` int(10) unsigned DEFAULT NULL COMMENT 'comprobante origen para notas de credito/debito',
  `ref_tipo_documento` varchar(2) DEFAULT NULL COMMENT '01 factura, 03 boleta - referencia para NC/ND',
  `ref_serie` varchar(10) DEFAULT NULL,
  `ref_numero` varchar(15) DEFAULT NULL,
  `motivo_codigo` varchar(2) DEFAULT NULL COMMENT 'catalogo 9 NC / catalogo 10 ND',
  `motivo_descripcion` varchar(300) DEFAULT NULL,
  `idclifact` int(10) unsigned DEFAULT NULL,
  `idusuario` int(10) unsigned DEFAULT NULL,
  `tipo_documento` varchar(2) NOT NULL COMMENT '01=Factura, 03=Boleta',
  `serie` varchar(10) NOT NULL,
  `numero` varchar(15) NOT NULL,
  `numero_completo` varchar(30) GENERATED ALWAYS AS (concat(`serie`,'-',`numero`)) STORED,
  `fecha_emision` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_moneda` varchar(3) NOT NULL DEFAULT 'PEN',
  `tipo_operacion` varchar(4) NOT NULL DEFAULT '0101' COMMENT '0101=Venta interna',
  `cliente_tipo_doc` varchar(1) NOT NULL DEFAULT '1',
  `cliente_num_doc` varchar(20) NOT NULL,
  `cliente_razon` varchar(200) NOT NULL,
  `cliente_direccion` varchar(250) DEFAULT NULL,
  `cliente_email` varchar(150) DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `subtotal_gravado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `subtotal_exonerado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `subtotal_inafecto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `subtotal_gratuito` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tasa_igv` decimal(5,4) NOT NULL DEFAULT 0.1800,
  `total_letras` varchar(300) DEFAULT NULL,
  `metodo_pago` varchar(20) DEFAULT NULL,
  `xml_nombre` varchar(150) DEFAULT NULL,
  `xml_ruta` varchar(300) DEFAULT NULL,
  `xml_hash` varchar(150) DEFAULT NULL COMMENT 'firma digital',
  `zip_ruta` varchar(300) DEFAULT NULL,
  `cdr_ruta` varchar(300) DEFAULT NULL,
  `pdf_ruta` varchar(300) DEFAULT NULL,
  `cdr_codigo` varchar(10) DEFAULT NULL,
  `cdr_descripcion` varchar(500) DEFAULT NULL,
  `estado` enum('pendiente','generado','enviando','aceptado','aceptado_observado','rechazado','baja','error') NOT NULL DEFAULT 'pendiente',
  `intentos_envio` int(11) NOT NULL DEFAULT 0,
  `fecha_generacion` datetime DEFAULT NULL COMMENT 'cuando se genero el XML',
  `fecha_envio` datetime DEFAULT NULL COMMENT 'cuando se envio a SUNAT',
  `fecha_baja` datetime DEFAULT NULL,
  `motivo_baja` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`idcomprobante`),
  UNIQUE KEY `uk_comp_doc` (`idempresa`,`tipo_documento`,`serie`,`numero`),
  KEY `idx_comp_estado` (`estado`),
  KEY `idx_comp_orden` (`idorden`),
  KEY `idx_comp_fecha` (`fecha_emision`),
  KEY `fk_comp_clifact` (`idclifact`),
  CONSTRAINT `fk_comp_clifact` FOREIGN KEY (`idclifact`) REFERENCES `cliente_facturacion` (`idclifact`) ON DELETE SET NULL,
  CONSTRAINT `fk_comp_emp` FOREIGN KEY (`idempresa`) REFERENCES `empresa` (`idempresa`),
  CONSTRAINT `fk_comp_orden` FOREIGN KEY (`idorden`) REFERENCES `orden` (`idorden`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of comprobante_electronico
-- ----------------------------

-- ----------------------------
-- Table structure for empresa
-- ----------------------------
DROP TABLE IF EXISTS `empresa`;
CREATE TABLE `empresa` (
  `idempresa` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `numero_ruc` varchar(15) NOT NULL,
  `tipo_doc_emisor` varchar(2) NOT NULL DEFAULT '6' COMMENT '6=RUC',
  `razon_social` varchar(150) NOT NULL,
  `nombre_comercial` varchar(150) DEFAULT NULL,
  `domicilio_fiscal` varchar(200) NOT NULL,
  `ubigeo` varchar(6) NOT NULL DEFAULT '150101',
  `departamento` varchar(60) DEFAULT NULL,
  `provincia` varchar(60) DEFAULT NULL,
  `distrito` varchar(60) DEFAULT NULL,
  `codigo_pais` varchar(2) NOT NULL DEFAULT 'PE',
  `telefono` varchar(30) DEFAULT NULL,
  `correo` varchar(120) DEFAULT NULL,
  `web` varchar(120) DEFAULT NULL,
  `logo` varchar(200) DEFAULT NULL,
  `formato_comprobante` enum('ticket','a4') NOT NULL DEFAULT 'ticket',
  `usuario_sol` varchar(40) DEFAULT NULL,
  `clave_sol` varchar(60) DEFAULT NULL,
  `ambiente` enum('beta','produccion') NOT NULL DEFAULT 'beta',
  `version_ubl` varchar(10) NOT NULL DEFAULT '2.1',
  `version_estructura` varchar(10) NOT NULL DEFAULT '2.0',
  `tasa_igv` decimal(5,4) NOT NULL DEFAULT 0.1800,
  `simbolo_moneda` varchar(5) NOT NULL DEFAULT 'S/',
  `codigo_moneda` varchar(3) NOT NULL DEFAULT 'PEN',
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `backup_prod_json` text DEFAULT NULL COMMENT 'config produccion respaldada al activar BETA',
  `mesas_columnas` varchar(10) NOT NULL DEFAULT 'auto' COMMENT 'columnas de la vista de mesas (auto o 2-8)',
  PRIMARY KEY (`idempresa`),
  UNIQUE KEY `uk_emp_ruc` (`numero_ruc`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of empresa
-- ----------------------------
INSERT INTO `empresa` VALUES ('1', '10429025546', '6', 'PUERTO HABANA CEVICHERIA', 'PUERTO HABANA CEVICHERIA', 'Av. Colonizacion 1115', '250101', 'ucayali', 'coronel portillo', 'calleria', 'PE', '979459608', 'poncebernedom@gmail.com', '', 'public/img/logo_1_e770e032.png', 'ticket', 'admin', 'admin123', 'beta', '2.1', '2.0', '0.0000', 'S/', 'PEN', '1', null, '3');

-- ----------------------------
-- Table structure for impresora
-- ----------------------------
DROP TABLE IF EXISTS `impresora`;
CREATE TABLE `impresora` (
  `idimpresora` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `puerto` int(5) NOT NULL DEFAULT 9100,
  `tipo` enum('cocina','bar','caja','otro') NOT NULL DEFAULT 'cocina',
  `ancho_cols` int(3) NOT NULL DEFAULT 32,
  `cortar_papel` tinyint(1) NOT NULL DEFAULT 1,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idimpresora`),
  KEY `idx_imp_tipo` (`tipo`,`activa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of impresora
-- ----------------------------

-- ----------------------------
-- Table structure for inventario_movimiento
-- ----------------------------
DROP TABLE IF EXISTS `inventario_movimiento`;
CREATE TABLE `inventario_movimiento` (
  `idmov` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idproducto` int(10) unsigned NOT NULL,
  `idprecio` int(10) unsigned DEFAULT NULL COMMENT 'presentacion afectada',
  `tipo` enum('entrada','salida','ajuste','venta') NOT NULL,
  `cantidad` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_resultante` decimal(12,2) NOT NULL DEFAULT 0.00,
  `nota` varchar(200) DEFAULT NULL,
  `idusuario` int(10) unsigned DEFAULT NULL,
  `idorden` int(10) unsigned DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idmov`),
  KEY `idx_inv_prod` (`idproducto`),
  KEY `idx_inv_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of inventario_movimiento
-- ----------------------------

-- ----------------------------
-- Table structure for licencia
-- ----------------------------
DROP TABLE IF EXISTS `licencia`;
CREATE TABLE `licencia` (
  `idlicencia` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cliente_nombre` varchar(150) NOT NULL DEFAULT 'Cliente',
  `fecha_inicio` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `dias_aviso` int(11) NOT NULL DEFAULT 5 COMMENT 'mostrar aviso N dias antes',
  `estado` enum('activa','suspendida') NOT NULL DEFAULT 'activa',
  `observacion` varchar(300) DEFAULT NULL,
  `creada_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizada_en` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`idlicencia`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of licencia
-- ----------------------------
INSERT INTO `licencia` VALUES ('1', 'Cliente', '2026-06-02', '2026-07-03', '5', 'activa', 'activación mensual', '2026-06-02 20:06:38', null);

-- ----------------------------
-- Table structure for licencia_historial
-- ----------------------------
DROP TABLE IF EXISTS `licencia_historial`;
CREATE TABLE `licencia_historial` (
  `idhistorial` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idlicencia` int(10) unsigned NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `accion` enum('crear','extender','suspender','reactivar') NOT NULL,
  `vencimiento_anterior` date DEFAULT NULL,
  `vencimiento_nuevo` date DEFAULT NULL,
  `monto_pagado` decimal(12,2) DEFAULT NULL,
  `observacion` varchar(300) DEFAULT NULL,
  `idusuario` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`idhistorial`),
  KEY `idx_lh_lic` (`idlicencia`),
  CONSTRAINT `fk_lh_lic` FOREIGN KEY (`idlicencia`) REFERENCES `licencia` (`idlicencia`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of licencia_historial
-- ----------------------------
INSERT INTO `licencia_historial` VALUES ('1', '1', '2026-06-02 20:06:38', 'crear', null, '2026-07-03', null, 'activación mensual', '1');

-- ----------------------------
-- Table structure for mesa
-- ----------------------------
DROP TABLE IF EXISTS `mesa`;
CREATE TABLE `mesa` (
  `idmesa` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idzona` int(10) unsigned DEFAULT NULL,
  `numero` int(11) NOT NULL,
  `capacidad` tinyint(4) NOT NULL DEFAULT 4,
  `orden` int(11) NOT NULL DEFAULT 0 COMMENT 'orden manual de visualizacion (0=por numero)',
  `estado` enum('libre','ocupada','cuenta','reservada','bloqueada') NOT NULL DEFAULT 'libre',
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idmesa`),
  UNIQUE KEY `uk_mesa_numero` (`numero`),
  KEY `idx_mesa_zona` (`idzona`),
  CONSTRAINT `fk_mesa_zona` FOREIGN KEY (`idzona`) REFERENCES `zona` (`idzona`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of mesa
-- ----------------------------
INSERT INTO `mesa` VALUES ('1', '1', '1', '4', '0', 'libre', '1');
INSERT INTO `mesa` VALUES ('2', '1', '2', '4', '0', 'libre', '1');
INSERT INTO `mesa` VALUES ('3', '1', '3', '4', '0', 'libre', '1');
INSERT INTO `mesa` VALUES ('4', '1', '4', '4', '0', 'libre', '1');
INSERT INTO `mesa` VALUES ('5', '1', '5', '4', '0', 'libre', '1');
INSERT INTO `mesa` VALUES ('6', '1', '6', '4', '0', 'libre', '1');
INSERT INTO `mesa` VALUES ('7', '1', '7', '4', '0', 'libre', '1');
INSERT INTO `mesa` VALUES ('8', '1', '8', '4', '0', 'libre', '1');
INSERT INTO `mesa` VALUES ('9', '1', '9', '4', '0', 'libre', '1');
INSERT INTO `mesa` VALUES ('10', '1', '10', '4', '0', 'libre', '1');
INSERT INTO `mesa` VALUES ('11', '1', '11', '4', '0', 'libre', '1');
INSERT INTO `mesa` VALUES ('12', '1', '12', '4', '0', 'libre', '1');

-- ----------------------------
-- Table structure for numeracion
-- ----------------------------
DROP TABLE IF EXISTS `numeracion`;
CREATE TABLE `numeracion` (
  `idnumeracion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idempresa` int(10) unsigned NOT NULL,
  `tipo_documento` varchar(2) NOT NULL COMMENT '01=Factura, 03=Boleta, 07=NC, 08=ND',
  `serie` varchar(10) NOT NULL,
  `ultimo_numero` int(11) NOT NULL DEFAULT 0,
  `descripcion` varchar(80) DEFAULT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idnumeracion`),
  UNIQUE KEY `uk_num_serie` (`idempresa`,`tipo_documento`,`serie`),
  KEY `idx_num_emp` (`idempresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of numeracion
-- ----------------------------

-- ----------------------------
-- Table structure for orden
-- ----------------------------
DROP TABLE IF EXISTS `orden`;
CREATE TABLE `orden` (
  `idorden` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(10) NOT NULL,
  `idmesa` int(10) unsigned DEFAULT NULL,
  `idcliente` int(10) unsigned DEFAULT NULL,
  `idsesion` int(10) unsigned DEFAULT NULL,
  `idusuario` int(10) unsigned DEFAULT NULL,
  `tipo` enum('dine_in','para_llevar','delivery') NOT NULL DEFAULT 'dine_in',
  `estado` enum('en_curso','enviada','pagada','anulada') NOT NULL DEFAULT 'en_curso',
  `mozo` varchar(100) DEFAULT NULL,
  `observacion` varchar(300) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` enum('','efectivo','tarjeta','yape','transferencia') NOT NULL DEFAULT '',
  `tipo_comprobante` enum('ticket','nota_venta','boleta','factura') NOT NULL DEFAULT 'ticket',
  `monto_recibido` decimal(12,2) DEFAULT NULL,
  `vuelto` decimal(12,2) DEFAULT NULL,
  `pago_referencia` varchar(100) DEFAULT NULL,
  `pago_metadata` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_pago` datetime DEFAULT NULL,
  PRIMARY KEY (`idorden`),
  UNIQUE KEY `uk_orden_numero` (`numero`),
  KEY `idx_orden_mesa` (`idmesa`),
  KEY `idx_orden_cliente` (`idcliente`),
  KEY `idx_orden_estado` (`estado`),
  KEY `idx_orden_fecha` (`fecha`),
  KEY `fk_orden_sesion` (`idsesion`),
  KEY `idx_orden_comprobante` (`tipo_comprobante`),
  CONSTRAINT `fk_orden_cliente` FOREIGN KEY (`idcliente`) REFERENCES `cliente` (`idcliente`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orden_mesa` FOREIGN KEY (`idmesa`) REFERENCES `mesa` (`idmesa`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orden_sesion` FOREIGN KEY (`idsesion`) REFERENCES `caja_sesion` (`idsesion`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of orden
-- ----------------------------
INSERT INTO `orden` VALUES ('1', '00001', '1', null, '1', '1', 'dine_in', 'anulada', 'Cajero', null, '0.00', '0.00', '0.00', '', 'ticket', null, null, null, null, '2026-06-02 20:15:23', null);

-- ----------------------------
-- Table structure for orden_detalle
-- ----------------------------
DROP TABLE IF EXISTS `orden_detalle`;
CREATE TABLE `orden_detalle` (
  `iddetalle` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idorden` int(10) unsigned NOT NULL,
  `idproducto` int(10) unsigned NOT NULL,
  `idprecio` int(10) unsigned DEFAULT NULL COMMENT 'variante/presentacion vendida',
  `nombre` varchar(150) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `cantidad_enviada` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `nota` varchar(200) DEFAULT NULL,
  `estado` enum('pendiente','en_preparacion','listo','servido','anulado') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`iddetalle`),
  KEY `idx_detalle_orden` (`idorden`),
  KEY `idx_detalle_producto` (`idproducto`),
  CONSTRAINT `fk_detalle_orden` FOREIGN KEY (`idorden`) REFERENCES `orden` (`idorden`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`idproducto`) REFERENCES `producto` (`idproducto`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of orden_detalle
-- ----------------------------

-- ----------------------------
-- Table structure for permiso
-- ----------------------------
DROP TABLE IF EXISTS `permiso`;
CREATE TABLE `permiso` (
  `idpermiso` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `grupo` varchar(40) NOT NULL DEFAULT 'general',
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idpermiso`),
  UNIQUE KEY `uk_permiso_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of permiso
-- ----------------------------
INSERT INTO `permiso` VALUES ('1', 'nuevaorden', 'Nueva Orden', 'Tomar nuevas ordenes (POS)', 'operacion', '1');
INSERT INTO `permiso` VALUES ('2', 'mesas', 'Gestionar Mesas', 'Ver y administrar mesas del salon', 'operacion', '2');
INSERT INTO `permiso` VALUES ('3', 'pedidos', 'Ver Pedidos', 'Listar y revisar pedidos activos', 'operacion', '3');
INSERT INTO `permiso` VALUES ('4', 'enviar_cocina', 'Enviar a Cocina', 'Enviar items en preparacion a cocina', 'operacion', '4');
INSERT INTO `permiso` VALUES ('5', 'cobrar', 'Cobrar Ordenes', 'Cobrar y cerrar ordenes', 'caja', '5');
INSERT INTO `permiso` VALUES ('6', 'anular_orden', 'Anular Ordenes', 'Anular ordenes activas', 'caja', '6');
INSERT INTO `permiso` VALUES ('7', 'clientes', 'Gestionar Clientes', 'CRUD de clientes', 'maestros', '7');
INSERT INTO `permiso` VALUES ('8', 'historial', 'Ver Historial', 'Consultar historial de ventas', 'reportes', '8');
INSERT INTO `permiso` VALUES ('9', 'caja', 'Operar Caja', 'Apertura/cierre y movimientos', 'caja', '9');
INSERT INTO `permiso` VALUES ('10', 'reportes', 'Ver Reportes', 'Acceso a metricas y graficos', 'reportes', '10');
INSERT INTO `permiso` VALUES ('11', 'productos', 'Gestionar Productos', 'CRUD productos y categorias', 'maestros', '11');
INSERT INTO `permiso` VALUES ('12', 'usuarios', 'Gestionar Usuarios', 'CRUD usuarios, roles y permisos', 'admin', '12');
INSERT INTO `permiso` VALUES ('13', 'comprobantes_sunat', 'Comprobantes SUNAT', 'Ver y gestionar boletas/facturas electronicas', 'sunat', '20');
INSERT INTO `permiso` VALUES ('14', 'emitir_boleta', 'Emitir Boleta', 'Emitir boletas electronicas al cobrar', 'sunat', '21');
INSERT INTO `permiso` VALUES ('15', 'emitir_factura', 'Emitir Factura', 'Emitir facturas electronicas al cobrar', 'sunat', '22');
INSERT INTO `permiso` VALUES ('16', 'enviar_sunat', 'Enviar a SUNAT', 'Enviar comprobantes a SUNAT', 'sunat', '23');
INSERT INTO `permiso` VALUES ('17', 'config_empresa', 'Configurar Empresa', 'Editar datos de la empresa emisora', 'sunat', '24');
INSERT INTO `permiso` VALUES ('18', 'config_certificado', 'Configurar Certificado', 'Cargar y gestionar certificado digital', 'sunat', '25');
INSERT INTO `permiso` VALUES ('19', 'config_numeracion', 'Configurar Numeracion', 'Gestionar series y correlativos', 'sunat', '26');
INSERT INTO `permiso` VALUES ('20', 'arqueo_caja', 'Arqueo de Caja', 'Realizar arqueo y cierre con conteo', 'caja', '13');
INSERT INTO `permiso` VALUES ('21', 'config_logo', 'Configurar Logo', 'Subir/cambiar logo de empresa', 'sunat', '27');
INSERT INTO `permiso` VALUES ('22', 'config_licencia', 'Gestionar Licencia', 'Activar/extender licencia del sistema', 'admin', '30');
INSERT INTO `permiso` VALUES ('23', 'emitir_nc', 'Emitir Nota de Credito', 'Anular comprobantes con NC', 'sunat', '28');
INSERT INTO `permiso` VALUES ('24', 'emitir_nd', 'Emitir Nota de Debito', 'Emitir notas de debito', 'sunat', '29');
INSERT INTO `permiso` VALUES ('25', 'resumen_boletas', 'Resumen Diario Boletas', 'Generar y enviar resumen RC', 'sunat', '30');
INSERT INTO `permiso` VALUES ('26', 'comunicacion_baja', 'Comunicacion de Baja', 'Anular boletas por resumen RA', 'sunat', '31');
INSERT INTO `permiso` VALUES ('27', 'whatsapp_enviar', 'Enviar WhatsApp', 'Enviar mensajes individuales (al cobrar)', 'whatsapp', '50');
INSERT INTO `permiso` VALUES ('28', 'whatsapp_plantillas', 'Plantillas WhatsApp', 'Gestionar plantillas de mensajes', 'whatsapp', '51');
INSERT INTO `permiso` VALUES ('29', 'whatsapp_masivo', 'Env??o masivo WhatsApp', 'Campa??as a m??ltiples clientes', 'whatsapp', '52');
INSERT INTO `permiso` VALUES ('30', 'zonas', 'Gestionar zonas', 'Crear, editar y eliminar zonas del salón', 'general', '0');
INSERT INTO `permiso` VALUES ('31', 'impresoras', 'Gestionar impresoras', 'Configurar IPs y tipos de impresoras LAN', 'general', '0');
INSERT INTO `permiso` VALUES ('32', 'inventario', 'Gestionar Inventario', 'Control de stock de productos', 'maestros', '12');

-- ----------------------------
-- Table structure for producto
-- ----------------------------
DROP TABLE IF EXISTS `producto`;
CREATE TABLE `producto` (
  `idproducto` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `precio` decimal(12,2) NOT NULL DEFAULT 0.00,
  `codigo_afectacion` varchar(2) NOT NULL DEFAULT '10' COMMENT '10=Gravado, 20=Exonerado, 30=Inafecto, 40=Exportacion (cat. 7 SUNAT)',
  `idcategoria` int(10) unsigned DEFAULT NULL,
  `imagen` varchar(500) DEFAULT NULL,
  `popular` tinyint(4) NOT NULL DEFAULT 0,
  `favorito` tinyint(4) NOT NULL DEFAULT 0,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `controla_stock` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1=lleva inventario',
  `stock` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'stock actual',
  `stock_minimo` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'umbral de alerta (semaforo)',
  PRIMARY KEY (`idproducto`),
  UNIQUE KEY `uk_producto_codigo` (`codigo`),
  KEY `idx_producto_cat` (`idcategoria`),
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`idcategoria`) REFERENCES `categoria` (`idcategoria`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of producto
-- ----------------------------
INSERT INTO `producto` VALUES ('1', 'PH001', 'PAPA A LA HUANCAINA', '18.00', '20', '1', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('2', 'CA001', 'CAUSA ACEVICHADA', '20.00', '20', '1', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('3', 'CL001', 'CAUSA DE LANGOSTINO', '20.00', '20', '1', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('4', 'CPC01', 'CEVICHE DE PESCADO CLASICO', '16.00', '20', '2', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('5', 'CC001', 'CEVICHE CON CHICHARRON', '18.00', '20', '2', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('6', 'CR001', 'CEVICHE DE ROCOTO', '18.00', '20', '2', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('7', 'CM001', 'CEVICHE MIXTO', '28.00', '20', '2', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('8', 'CME01', 'CEVICHE MIXTO ESPECIAL', '35.00', '20', '2', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('9', 'CDL01', 'CEVICHE DE LANGOSTINO', '25.00', '20', '2', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('10', 'LTC01', 'LECHE DE TIGRE + CHICHARRON', '18.00', '20', '2', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('11', 'LTP01', 'LECHE DE TIGRE POWER', '30.00', '20', '2', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('12', 'ACM01', 'ARROZ CON MARISCOS', '28.00', '20', '3', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('13', 'ACP01', 'ARROZ CHAUFA DE PESCADO', '25.00', '20', '3', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('14', 'ACDM01', 'ARROZ CHAUFA DE MARISCOS', '28.00', '20', '3', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('15', 'ACL01', 'ARROZ CHAUFA DE LANGOSTINO', '28.00', '20', '3', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('16', 'CP001', 'CHICHARRON DE PESCADO', '28.00', '20', '3', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('17', 'C+C01', 'CEVICHE + CHICHARRON', '28.00', '20', '4', '', '0', '0', '1', '0', '0.00', '0.00');
INSERT INTO `producto` VALUES ('18', 'C+CL01', 'CEVICHE + CAUSA DE LANGOSTINO', '28.00', '20', '4', '', '0', '0', '1', '0', '0.00', '0.00');

-- ----------------------------
-- Table structure for producto_precio
-- ----------------------------
DROP TABLE IF EXISTS `producto_precio`;
CREATE TABLE `producto_precio` (
  `idprecio` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idproducto` int(10) unsigned NOT NULL,
  `nombre` varchar(80) NOT NULL DEFAULT 'Normal' COMMENT 'Personal, Familiar, Vaso, Botella, etc.',
  `precio` decimal(12,2) NOT NULL DEFAULT 0.00,
  `es_default` tinyint(4) NOT NULL DEFAULT 0,
  `orden` int(11) NOT NULL DEFAULT 0,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `controla_stock` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1=lleva inventario esta presentacion',
  `stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_minimo` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`idprecio`),
  KEY `idx_pp_producto` (`idproducto`),
  CONSTRAINT `fk_pp_producto` FOREIGN KEY (`idproducto`) REFERENCES `producto` (`idproducto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of producto_precio
-- ----------------------------
INSERT INTO `producto_precio` VALUES ('1', '1', 'Normal', '18.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('2', '2', 'Normal', '20.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('3', '3', 'Normal', '20.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('4', '4', 'Normal', '16.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('5', '5', 'Normal', '18.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('6', '6', 'Normal', '18.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('7', '7', 'MIXTURA, LANGOSTINOS, CONCHAS ABANICOS', '28.00', '1', '1', '0', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('8', '8', 'mixto, langostino, conchas abanicos, cangrejo', '35.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('9', '7', 'mixtura, langostino, conchas abanicos', '28.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('10', '9', 'Normal', '25.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('11', '10', 'Normal', '18.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('12', '11', '(pescado marinado en abundante leche de tigre combinado con mariscos y conchas d', '30.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('13', '12', 'Normal', '28.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('14', '13', 'Normal', '25.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('15', '14', 'Normal', '28.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('16', '15', 'Normal', '28.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('17', '16', 'Normal', '28.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('18', '17', 'Normal', '28.00', '1', '1', '1', '0', '0.00', '0.00');
INSERT INTO `producto_precio` VALUES ('19', '18', 'Normal', '28.00', '1', '1', '1', '0', '0.00', '0.00');

-- ----------------------------
-- Table structure for resumen_detalle
-- ----------------------------
DROP TABLE IF EXISTS `resumen_detalle`;
CREATE TABLE `resumen_detalle` (
  `iddetalle` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idresumen` int(10) unsigned NOT NULL,
  `idcomprobante` int(10) unsigned NOT NULL,
  `linea` int(11) NOT NULL,
  `tipo_documento` varchar(2) NOT NULL,
  `serie` varchar(10) NOT NULL,
  `numero` varchar(15) NOT NULL,
  `cliente_tipo_doc` varchar(1) DEFAULT NULL,
  `cliente_num_doc` varchar(20) DEFAULT NULL,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_gravado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_exonerado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_inafecto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(14,2) NOT NULL DEFAULT 0.00,
  `motivo_baja` varchar(200) DEFAULT NULL,
  `estado_item` varchar(2) DEFAULT NULL COMMENT '1=adicionar, 2=modificar, 3=anular',
  PRIMARY KEY (`iddetalle`),
  KEY `idx_resd_res` (`idresumen`),
  KEY `idx_resd_comp` (`idcomprobante`),
  CONSTRAINT `fk_resd_comp` FOREIGN KEY (`idcomprobante`) REFERENCES `comprobante_electronico` (`idcomprobante`),
  CONSTRAINT `fk_resd_res` FOREIGN KEY (`idresumen`) REFERENCES `resumen_sunat` (`idresumen`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of resumen_detalle
-- ----------------------------

-- ----------------------------
-- Table structure for resumen_sunat
-- ----------------------------
DROP TABLE IF EXISTS `resumen_sunat`;
CREATE TABLE `resumen_sunat` (
  `idresumen` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idempresa` int(10) unsigned NOT NULL,
  `tipo` enum('RC','RA') NOT NULL COMMENT 'RC = resumen boletas; RA = comunicacion de baja',
  `correlativo` int(11) NOT NULL DEFAULT 1 COMMENT 'correlativo del dia',
  `serie_doc` varchar(30) NOT NULL,
  `fecha_referencia` date NOT NULL COMMENT 'fecha de los comprobantes resumidos',
  `fecha_generacion` datetime NOT NULL DEFAULT current_timestamp(),
  `xml_nombre` varchar(150) DEFAULT NULL,
  `xml_ruta` varchar(300) DEFAULT NULL,
  `zip_ruta` varchar(300) DEFAULT NULL,
  `cdr_ruta` varchar(300) DEFAULT NULL,
  `xml_hash` varchar(150) DEFAULT NULL,
  `ticket` varchar(50) DEFAULT NULL COMMENT 'devuelto por sendSummary',
  `estado` enum('pendiente','generado','enviado','aceptado','aceptado_observado','rechazado','error') NOT NULL DEFAULT 'pendiente',
  `cdr_codigo` varchar(10) DEFAULT NULL,
  `cdr_descripcion` varchar(500) DEFAULT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_aceptacion` datetime DEFAULT NULL,
  `idusuario` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`idresumen`),
  UNIQUE KEY `uk_res_serie` (`idempresa`,`tipo`,`fecha_referencia`,`correlativo`),
  KEY `idx_res_estado` (`estado`),
  KEY `idx_res_fechaef` (`fecha_referencia`),
  CONSTRAINT `fk_res_emp` FOREIGN KEY (`idempresa`) REFERENCES `empresa` (`idempresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of resumen_sunat
-- ----------------------------

-- ----------------------------
-- Table structure for rol
-- ----------------------------
DROP TABLE IF EXISTS `rol`;
CREATE TABLE `rol` (
  `idrol` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idrol`),
  UNIQUE KEY `uk_rol_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of rol
-- ----------------------------
INSERT INTO `rol` VALUES ('1', 'admin', 'Administrador', 'Acceso total al sistema', '1');
INSERT INTO `rol` VALUES ('2', 'cajero', 'Cajero', 'Toma ordenes, cobra y maneja caja', '1');
INSERT INTO `rol` VALUES ('3', 'mozo', 'Mozo', 'Toma ordenes y envia a cocina', '1');

-- ----------------------------
-- Table structure for rol_permiso
-- ----------------------------
DROP TABLE IF EXISTS `rol_permiso`;
CREATE TABLE `rol_permiso` (
  `idrol` int(10) unsigned NOT NULL,
  `idpermiso` int(10) unsigned NOT NULL,
  PRIMARY KEY (`idrol`,`idpermiso`),
  KEY `idx_rp_permiso` (`idpermiso`),
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`idpermiso`) REFERENCES `permiso` (`idpermiso`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_rol` FOREIGN KEY (`idrol`) REFERENCES `rol` (`idrol`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of rol_permiso
-- ----------------------------
INSERT INTO `rol_permiso` VALUES ('1', '1');
INSERT INTO `rol_permiso` VALUES ('2', '1');
INSERT INTO `rol_permiso` VALUES ('3', '1');
INSERT INTO `rol_permiso` VALUES ('1', '2');
INSERT INTO `rol_permiso` VALUES ('2', '2');
INSERT INTO `rol_permiso` VALUES ('3', '2');
INSERT INTO `rol_permiso` VALUES ('1', '3');
INSERT INTO `rol_permiso` VALUES ('2', '3');
INSERT INTO `rol_permiso` VALUES ('3', '3');
INSERT INTO `rol_permiso` VALUES ('1', '4');
INSERT INTO `rol_permiso` VALUES ('2', '4');
INSERT INTO `rol_permiso` VALUES ('3', '4');
INSERT INTO `rol_permiso` VALUES ('1', '5');
INSERT INTO `rol_permiso` VALUES ('2', '5');
INSERT INTO `rol_permiso` VALUES ('1', '6');
INSERT INTO `rol_permiso` VALUES ('2', '6');
INSERT INTO `rol_permiso` VALUES ('1', '7');
INSERT INTO `rol_permiso` VALUES ('2', '7');
INSERT INTO `rol_permiso` VALUES ('1', '8');
INSERT INTO `rol_permiso` VALUES ('2', '8');
INSERT INTO `rol_permiso` VALUES ('1', '9');
INSERT INTO `rol_permiso` VALUES ('2', '9');
INSERT INTO `rol_permiso` VALUES ('1', '10');
INSERT INTO `rol_permiso` VALUES ('1', '11');
INSERT INTO `rol_permiso` VALUES ('1', '12');
INSERT INTO `rol_permiso` VALUES ('1', '13');
INSERT INTO `rol_permiso` VALUES ('2', '13');
INSERT INTO `rol_permiso` VALUES ('1', '14');
INSERT INTO `rol_permiso` VALUES ('2', '14');
INSERT INTO `rol_permiso` VALUES ('1', '15');
INSERT INTO `rol_permiso` VALUES ('2', '15');
INSERT INTO `rol_permiso` VALUES ('1', '16');
INSERT INTO `rol_permiso` VALUES ('2', '16');
INSERT INTO `rol_permiso` VALUES ('1', '17');
INSERT INTO `rol_permiso` VALUES ('1', '18');
INSERT INTO `rol_permiso` VALUES ('1', '19');
INSERT INTO `rol_permiso` VALUES ('1', '20');
INSERT INTO `rol_permiso` VALUES ('2', '20');
INSERT INTO `rol_permiso` VALUES ('1', '21');
INSERT INTO `rol_permiso` VALUES ('1', '22');
INSERT INTO `rol_permiso` VALUES ('1', '23');
INSERT INTO `rol_permiso` VALUES ('2', '23');
INSERT INTO `rol_permiso` VALUES ('1', '24');
INSERT INTO `rol_permiso` VALUES ('1', '25');
INSERT INTO `rol_permiso` VALUES ('1', '26');
INSERT INTO `rol_permiso` VALUES ('1', '27');
INSERT INTO `rol_permiso` VALUES ('2', '27');
INSERT INTO `rol_permiso` VALUES ('1', '28');
INSERT INTO `rol_permiso` VALUES ('1', '29');
INSERT INTO `rol_permiso` VALUES ('1', '30');
INSERT INTO `rol_permiso` VALUES ('1', '31');
INSERT INTO `rol_permiso` VALUES ('1', '32');

-- ----------------------------
-- Table structure for rutas
-- ----------------------------
DROP TABLE IF EXISTS `rutas`;
CREATE TABLE `rutas` (
  `idruta` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idempresa` int(10) unsigned NOT NULL,
  `ruta_data` varchar(200) NOT NULL DEFAULT '../sfs/data/',
  `ruta_firma` varchar(200) NOT NULL DEFAULT '../sfs/firma/',
  `ruta_envio` varchar(200) NOT NULL DEFAULT '../sfs/envio/',
  `ruta_rpta` varchar(200) NOT NULL DEFAULT '../sfs/rpta/',
  `ruta_unzip` varchar(200) NOT NULL DEFAULT '../sfs/unziprpta/',
  `ruta_baja` varchar(200) NOT NULL DEFAULT '../sfs/baja/',
  `ruta_resumen` varchar(200) NOT NULL DEFAULT '../sfs/resumen/',
  `ruta_pdf` varchar(200) NOT NULL DEFAULT '../comprobantesPDF/',
  PRIMARY KEY (`idruta`),
  UNIQUE KEY `uk_ruta_emp` (`idempresa`),
  CONSTRAINT `fk_ruta_emp` FOREIGN KEY (`idempresa`) REFERENCES `empresa` (`idempresa`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of rutas
-- ----------------------------

-- ----------------------------
-- Table structure for seguridad_log
-- ----------------------------
DROP TABLE IF EXISTS `seguridad_log`;
CREATE TABLE `seguridad_log` (
  `idlog` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `evento` varchar(50) NOT NULL,
  `idusuario` int(10) unsigned DEFAULT NULL,
  `login` varchar(45) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(250) DEFAULT NULL,
  `mensaje` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`idlog`),
  KEY `idx_seg_evt` (`evento`),
  KEY `idx_seg_fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of seguridad_log
-- ----------------------------
INSERT INTO `seguridad_log` VALUES ('1', '2026-06-02 20:06:38', 'licencia_crear', '1', 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'vencimiento: 2026-07-03');
INSERT INTO `seguridad_log` VALUES ('2', '2026-06-02 20:07:48', 'logout', '1', 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '');
INSERT INTO `seguridad_log` VALUES ('3', '2026-06-02 20:07:50', 'login_ok', '1', 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '');
INSERT INTO `seguridad_log` VALUES ('4', '2026-06-02 20:15:37', 'logout', '1', 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '');
INSERT INTO `seguridad_log` VALUES ('5', '2026-06-03 09:14:01', 'login_ok', '1', 'admin', '190.43.149.151', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '');
INSERT INTO `seguridad_log` VALUES ('6', '2026-06-03 10:34:42', 'logout', null, null, '190.43.149.151', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '');
INSERT INTO `seguridad_log` VALUES ('7', '2026-06-03 11:47:10', 'login_ok', '1', 'admin', '190.43.149.151', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '');

-- ----------------------------
-- Table structure for usuario
-- ----------------------------
DROP TABLE IF EXISTS `usuario`;
CREATE TABLE `usuario` (
  `idusuario` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idrol` int(10) unsigned DEFAULT NULL,
  `nombre` varchar(60) NOT NULL,
  `apellidos` varchar(80) DEFAULT NULL,
  `tipo_documento` varchar(8) DEFAULT NULL,
  `num_documento` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `login` varchar(45) NOT NULL,
  `clave` varchar(255) NOT NULL COMMENT 'password_hash o SHA256 legacy',
  `imagen` varchar(120) DEFAULT NULL,
  `condicion` tinyint(4) NOT NULL DEFAULT 1,
  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  PRIMARY KEY (`idusuario`),
  UNIQUE KEY `uk_usuario_login` (`login`),
  KEY `idx_usuario_rol` (`idrol`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`idrol`) REFERENCES `rol` (`idrol`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of usuario
-- ----------------------------
INSERT INTO `usuario` VALUES ('1', '1', 'Admin', 'Puerto Habana', '', '', '', 'admin@puertohabana.local', 'admin', '$2y$10$xI7pAUfDGJTFAVs4SgDNPuGJB1DRrbx5PRjgvXxEf6L.W8pO2SEOy', null, '1', '0', null, '2026-06-03 11:47:10');

-- ----------------------------
-- Table structure for usuario_permiso
-- ----------------------------
DROP TABLE IF EXISTS `usuario_permiso`;
CREATE TABLE `usuario_permiso` (
  `idusuario` int(10) unsigned NOT NULL,
  `idpermiso` int(10) unsigned NOT NULL,
  `tipo` enum('grant','revoke') NOT NULL DEFAULT 'grant',
  PRIMARY KEY (`idusuario`,`idpermiso`),
  KEY `idx_up_permiso` (`idpermiso`),
  CONSTRAINT `fk_up_permiso` FOREIGN KEY (`idpermiso`) REFERENCES `permiso` (`idpermiso`) ON DELETE CASCADE,
  CONSTRAINT `fk_up_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of usuario_permiso
-- ----------------------------
INSERT INTO `usuario_permiso` VALUES ('1', '1', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '2', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '3', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '4', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '5', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '6', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '7', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '8', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '9', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '10', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '11', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '12', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '13', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '14', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '15', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '16', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '17', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '18', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '19', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '20', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '21', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '22', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '23', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '24', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '25', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '26', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '27', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '28', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '29', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '30', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '31', 'grant');
INSERT INTO `usuario_permiso` VALUES ('1', '32', 'grant');

-- ----------------------------
-- Table structure for whatsapp_envio
-- ----------------------------
DROP TABLE IF EXISTS `whatsapp_envio`;
CREATE TABLE `whatsapp_envio` (
  `idenvio` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idcliente` int(10) unsigned DEFAULT NULL,
  `idclifact` int(10) unsigned DEFAULT NULL,
  `idcomprobante` int(10) unsigned DEFAULT NULL,
  `idplantilla` int(10) unsigned DEFAULT NULL,
  `numero` varchar(20) NOT NULL,
  `nombre_cliente` varchar(150) DEFAULT NULL,
  `documento` varchar(20) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `enviado` datetime NOT NULL DEFAULT current_timestamp(),
  `idusuario` int(10) unsigned DEFAULT NULL,
  `tipo` enum('cobro','masivo','manual') NOT NULL DEFAULT 'manual',
  PRIMARY KEY (`idenvio`),
  KEY `idx_we_numero` (`numero`),
  KEY `idx_we_fecha` (`enviado`),
  KEY `idx_we_cliente` (`idcliente`),
  KEY `idx_we_comp` (`idcomprobante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of whatsapp_envio
-- ----------------------------

-- ----------------------------
-- Table structure for whatsapp_plantilla
-- ----------------------------
DROP TABLE IF EXISTS `whatsapp_plantilla`;
CREATE TABLE `whatsapp_plantilla` (
  `idplantilla` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `mensaje` text NOT NULL,
  `auto_cobro` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=plantilla por defecto al cobrar (boleta o factura)',
  `tipo` enum('cobro','cumple','festivo','promocion','generico') NOT NULL DEFAULT 'generico',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creada` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idplantilla`),
  UNIQUE KEY `uk_wp_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of whatsapp_plantilla
-- ----------------------------

-- ----------------------------
-- Table structure for zona
-- ----------------------------
DROP TABLE IF EXISTS `zona`;
CREATE TABLE `zona` (
  `idzona` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `color` char(7) NOT NULL DEFAULT '#5b3df5',
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idzona`),
  UNIQUE KEY `uk_zona_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of zona
-- ----------------------------
INSERT INTO `zona` VALUES ('1', 'LOCAL INTERIOR', '#f59e0b', '1', '1');
