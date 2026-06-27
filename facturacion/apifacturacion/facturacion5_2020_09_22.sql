/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50714
 Source Host           : 127.0.0.1:3306
 Source Schema         : facturacion5

 Target Server Type    : MySQL
 Target Server Version : 50714
 File Encoding         : 65001

 Date: 22/09/2020 22:03:32
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for cliente
-- ----------------------------
DROP TABLE IF EXISTS `cliente`;
CREATE TABLE `cliente`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipodoc` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `nrodoc` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `razon_social` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `direccion` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_tipodoc`(`tipodoc`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for detalle
-- ----------------------------
DROP TABLE IF EXISTS `detalle`;
CREATE TABLE `detalle`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idventa` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `item` int(11) NULL DEFAULT NULL,
  `idproducto` int(11) NULL DEFAULT NULL,
  `cantidad` decimal(11, 2) NULL DEFAULT NULL,
  `valor_unitario` decimal(11, 2) NULL DEFAULT NULL,
  `precio_unitario` decimal(11, 2) NULL DEFAULT NULL,
  `igv` decimal(11, 2) NULL DEFAULT NULL,
  `porcentaje_igv` decimal(11, 2) NULL DEFAULT NULL,
  `valor_total` decimal(11, 2) NULL DEFAULT NULL,
  `importe_total` decimal(11, 2) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_venta`(`idventa`) USING BTREE,
  INDEX `fk_producto`(`idproducto`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for emisor
-- ----------------------------
DROP TABLE IF EXISTS `emisor`;
CREATE TABLE `emisor`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipodoc` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ruc` char(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `razon_social` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `nombre_comercial` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `direccion` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `pais` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `departamento` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `provincia` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `distrito` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ubigeo` char(6) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `usuario_sol` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `clave_sol` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of emisor
-- ----------------------------
INSERT INTO `emisor` VALUES (1, '6', '20602814425', 'TAQINI TECHNOLOGY SAC', 'TAQINI TECHNOLOGY SAC', '8 DE OCTUBRE N 123 - CHICLAYO - CHICLAYO - LAMBAYEQUE', 'PE', 'LAMBAYEQUE', 'CHICLAYO', 'CHICLAYO', '140101', 'MODDATOS', 'MODDATOS');
INSERT INTO `emisor` VALUES (2, '6', '20480631286', 'ASOCIACION CENTRO DE ENTRENAMIENTO EN TECNOLOGIAS DE INFORMACION - CETI', 'ASOCIACION CENTRO DE ENTRENAMIENTO EN TECNOLOGIAS DE INFORMACION - CETI', 'Cal. Francisco Cuneo-Pataz Nro. 270(Frente al Circulo Departamental de Emple)', 'PE', 'LAMBAYEQUE', 'CHICLAYO', 'CHICLAYO', '140101', 'MODDATOS', 'MODDATOS');

-- ----------------------------
-- Table structure for moneda
-- ----------------------------
DROP TABLE IF EXISTS `moneda`;
CREATE TABLE `moneda`  (
  `codigo` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `descripcion` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`codigo`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for producto
-- ----------------------------
DROP TABLE IF EXISTS `producto`;
CREATE TABLE `producto`  (
  `codigo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `precio` decimal(11, 2) NULL DEFAULT NULL,
  `tipo_precio` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `codigoafectacion` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `unidad` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`codigo`) USING BTREE,
  INDEX `fk_codigoafectacion`(`codigoafectacion`) USING BTREE,
  INDEX `fk_unidad`(`unidad`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for serie
-- ----------------------------
DROP TABLE IF EXISTS `serie`;
CREATE TABLE `serie`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipocomp` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `serie` char(4) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `correlativo` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for tipo_afectacion
-- ----------------------------
DROP TABLE IF EXISTS `tipo_afectacion`;
CREATE TABLE `tipo_afectacion`  (
  `codigo` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `descripcion` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `codigo_afectacion` char(4) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `nombre_afectacion` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `tipo_afectacion` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`codigo`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for tipo_comprobante
-- ----------------------------
DROP TABLE IF EXISTS `tipo_comprobante`;
CREATE TABLE `tipo_comprobante`  (
  `codigo` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `descripcion` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`codigo`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for tipo_documento
-- ----------------------------
DROP TABLE IF EXISTS `tipo_documento`;
CREATE TABLE `tipo_documento`  (
  `codigo` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `descripcion` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`codigo`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for unidad
-- ----------------------------
DROP TABLE IF EXISTS `unidad`;
CREATE TABLE `unidad`  (
  `codigo` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `descripcion` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`codigo`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for venta
-- ----------------------------
DROP TABLE IF EXISTS `venta`;
CREATE TABLE `venta`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idemisor` int(11) NULL DEFAULT NULL,
  `tipocomp` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `idserie` int(11) NULL DEFAULT NULL,
  `serie` char(4) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `correlativo` int(11) NULL DEFAULT NULL,
  `fecha_emision` date NULL DEFAULT NULL,
  `codmoneda` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `op_gravadas` decimal(11, 2) NULL DEFAULT NULL,
  `op_exoneradas` decimal(11, 2) NULL DEFAULT NULL,
  `op_inafectas` decimal(11, 2) NULL DEFAULT NULL,
  `igv` decimal(11, 2) NULL DEFAULT NULL,
  `total` decimal(11, 2) NULL DEFAULT NULL,
  `codcliente` int(11) NULL DEFAULT NULL,
  `feestado` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `fecodigoerror` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `femensajesunat` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_tipcomp`(`tipocomp`) USING BTREE,
  INDEX `fk_moneda`(`codmoneda`) USING BTREE,
  INDEX `fk_cliente`(`codcliente`) USING BTREE,
  INDEX `fk_serie`(`idserie`) USING BTREE,
  INDEX `fk_emisor`(`idemisor`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
