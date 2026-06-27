-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: habana_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `habana_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `habana_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `habana_db`;

--
-- Table structure for table `caja_arqueo`
--

DROP TABLE IF EXISTS `caja_arqueo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caja_arqueo`
--

LOCK TABLES `caja_arqueo` WRITE;
/*!40000 ALTER TABLE `caja_arqueo` DISABLE KEYS */;
INSERT INTO `caja_arqueo` VALUES (1,1,1,'2026-06-10 15:22:19',20.00,20.00,0.00,'','{}');
/*!40000 ALTER TABLE `caja_arqueo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caja_movimiento`
--

DROP TABLE IF EXISTS `caja_movimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caja_movimiento`
--

LOCK TABLES `caja_movimiento` WRITE;
/*!40000 ALTER TABLE `caja_movimiento` DISABLE KEYS */;
INSERT INTO `caja_movimiento` VALUES (1,1,'apertura',20.00,'Apertura de caja',NULL,NULL,'2026-06-09 21:54:35'),(2,1,'cierre',20.00,'Cierre de caja',NULL,NULL,'2026-06-10 15:22:19'),(3,2,'apertura',20.00,'Apertura de caja',NULL,NULL,'2026-06-10 15:22:37');
/*!40000 ALTER TABLE `caja_movimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caja_sesion`
--

DROP TABLE IF EXISTS `caja_sesion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caja_sesion`
--

LOCK TABLES `caja_sesion` WRITE;
/*!40000 ALTER TABLE `caja_sesion` DISABLE KEYS */;
INSERT INTO `caja_sesion` VALUES (1,'AP-001','Noche','Admin Puerto Habana',1,20.00,20.00,'2026-06-09 21:54:35','2026-06-10 15:22:19',0),(2,'AP-001','Mañana','Admin Puerto Habana',1,20.00,NULL,'2026-06-10 15:22:37',NULL,1);
/*!40000 ALTER TABLE `caja_sesion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES (1,'ENTRADAS','ENTRADAS','fa-utensils','#5b3df5',1,1),(2,'CEVICHES','CEVICHES','fa-lemon','#f4ff5c',2,1),(3,'ARROCES','ARROCES','fa-bowl-rice','#eae8f2',3,1),(4,'DUOS','DUOS','fa-fish','#3d6bf5',4,1),(5,'TRIOS','TRIOS','fa-fish-fins','#ffb61a',5,1),(6,'BEBIDAS','BEBIDAS','fa-bottle-water','#7df604',6,1);
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cdr_log`
--

DROP TABLE IF EXISTS `cdr_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cdr_log`
--

LOCK TABLES `cdr_log` WRITE;
/*!40000 ALTER TABLE `cdr_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `cdr_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificado`
--

DROP TABLE IF EXISTS `certificado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificado`
--

LOCK TABLES `certificado` WRITE;
/*!40000 ALTER TABLE `certificado` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente_facturacion`
--

DROP TABLE IF EXISTS `cliente_facturacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente_facturacion`
--

LOCK TABLES `cliente_facturacion` WRITE;
/*!40000 ALTER TABLE `cliente_facturacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `cliente_facturacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cola_impresion`
--

DROP TABLE IF EXISTS `cola_impresion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cola_impresion`
--

LOCK TABLES `cola_impresion` WRITE;
/*!40000 ALTER TABLE `cola_impresion` DISABLE KEYS */;
/*!40000 ALTER TABLE `cola_impresion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comprobante_detalle`
--

DROP TABLE IF EXISTS `comprobante_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comprobante_detalle`
--

LOCK TABLES `comprobante_detalle` WRITE;
/*!40000 ALTER TABLE `comprobante_detalle` DISABLE KEYS */;
/*!40000 ALTER TABLE `comprobante_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comprobante_electronico`
--

DROP TABLE IF EXISTS `comprobante_electronico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comprobante_electronico`
--

LOCK TABLES `comprobante_electronico` WRITE;
/*!40000 ALTER TABLE `comprobante_electronico` DISABLE KEYS */;
/*!40000 ALTER TABLE `comprobante_electronico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empresa`
--

DROP TABLE IF EXISTS `empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `yape_qr` varchar(200) DEFAULT NULL COMMENT 'ruta imagen QR Yape',
  `plin_qr` varchar(200) DEFAULT NULL COMMENT 'ruta imagen QR Plin (vacio=usar el de Yape)',
  `formato_comprobante` enum('ticket','a4') NOT NULL DEFAULT 'ticket',
  `envio_sunat_automatico` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1=enviar a SUNAT al emitir boleta/factura',
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresa`
--

LOCK TABLES `empresa` WRITE;
/*!40000 ALTER TABLE `empresa` DISABLE KEYS */;
INSERT INTO `empresa` VALUES (1,'10429025546','6','PONCE BERNEDO MARCO ANTONIO','PUERTO HABANA CEVICHERIA','AV. COLONIZACION 1115 - FRENTE AL COLEGIO AGROPECUARIO','250101','UCAYALI','CORONEL PORTILLO','CALLERIA','PE','979459608','poncebernedom@gmail.com','','public/img/logo_1_a16944ba.png','public/img/qr_yape_1_1c633b2f.png',NULL,'ticket',0,'MODDATOS','MODDATOS','beta','2.1','2.0',0.0000,'S/','PEN',1,NULL,'3');
/*!40000 ALTER TABLE `empresa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `impresora`
--

DROP TABLE IF EXISTS `impresora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `impresora`
--

LOCK TABLES `impresora` WRITE;
/*!40000 ALTER TABLE `impresora` DISABLE KEYS */;
/*!40000 ALTER TABLE `impresora` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventario_movimiento`
--

DROP TABLE IF EXISTS `inventario_movimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventario_movimiento`
--

LOCK TABLES `inventario_movimiento` WRITE;
/*!40000 ALTER TABLE `inventario_movimiento` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventario_movimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `licencia`
--

DROP TABLE IF EXISTS `licencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licencia`
--

LOCK TABLES `licencia` WRITE;
/*!40000 ALTER TABLE `licencia` DISABLE KEYS */;
INSERT INTO `licencia` VALUES (1,'Cliente','2026-06-03','2026-07-04',5,'activa','activación mensual','2026-06-03 13:09:14',NULL);
/*!40000 ALTER TABLE `licencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `licencia_historial`
--

DROP TABLE IF EXISTS `licencia_historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licencia_historial`
--

LOCK TABLES `licencia_historial` WRITE;
/*!40000 ALTER TABLE `licencia_historial` DISABLE KEYS */;
INSERT INTO `licencia_historial` VALUES (1,1,'2026-06-03 13:09:14','crear',NULL,'2026-07-04',NULL,'activación mensual',1);
/*!40000 ALTER TABLE `licencia_historial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mesa`
--

DROP TABLE IF EXISTS `mesa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mesa`
--

LOCK TABLES `mesa` WRITE;
/*!40000 ALTER TABLE `mesa` DISABLE KEYS */;
INSERT INTO `mesa` VALUES (1,1,1,4,1,'ocupada',1),(2,1,2,4,2,'ocupada',1),(3,1,3,4,3,'libre',1),(4,1,4,4,4,'libre',1),(5,1,5,4,5,'libre',1),(6,1,6,4,6,'libre',1),(7,1,7,4,7,'libre',1),(8,1,8,4,8,'libre',1),(9,1,9,4,9,'libre',1),(10,1,10,4,10,'libre',1),(11,1,11,4,11,'libre',1),(12,1,12,4,12,'libre',1);
/*!40000 ALTER TABLE `mesa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `numeracion`
--

DROP TABLE IF EXISTS `numeracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `numeracion`
--

LOCK TABLES `numeracion` WRITE;
/*!40000 ALTER TABLE `numeracion` DISABLE KEYS */;
/*!40000 ALTER TABLE `numeracion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orden`
--

DROP TABLE IF EXISTS `orden`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `metodo_pago` enum('','efectivo','tarjeta','yape','plin','transferencia','mixto') NOT NULL DEFAULT '',
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orden`
--

LOCK TABLES `orden` WRITE;
/*!40000 ALTER TABLE `orden` DISABLE KEYS */;
INSERT INTO `orden` VALUES (1,'00001',1,NULL,1,1,'dine_in','anulada','Cajero',NULL,65.00,0.00,65.00,'','ticket',NULL,NULL,NULL,NULL,'2026-06-09 21:54:44',NULL),(2,'00002',1,NULL,1,1,'dine_in','anulada','Cajero',NULL,134.00,0.00,134.00,'','ticket',NULL,NULL,NULL,NULL,'2026-06-09 22:30:27',NULL),(3,'00003',3,NULL,1,1,'dine_in','anulada','Cajero',NULL,92.00,0.00,92.00,'','ticket',NULL,NULL,NULL,NULL,'2026-06-09 22:32:00',NULL),(4,'00004',1,NULL,2,1,'dine_in','enviada','Cajero',NULL,106.00,0.00,106.00,'','ticket',NULL,NULL,NULL,NULL,'2026-06-10 15:23:01',NULL),(5,'00005',2,NULL,2,1,'dine_in','enviada','Cajero',NULL,75.00,0.00,75.00,'','ticket',NULL,NULL,NULL,NULL,'2026-06-10 15:23:31',NULL);
/*!40000 ALTER TABLE `orden` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orden_detalle`
--

DROP TABLE IF EXISTS `orden_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `cortesia` tinyint(1) NOT NULL DEFAULT 0,
  `nota` varchar(200) DEFAULT NULL,
  `estado` enum('pendiente','en_preparacion','listo','servido','anulado') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`iddetalle`),
  KEY `idx_detalle_orden` (`idorden`),
  KEY `idx_detalle_producto` (`idproducto`),
  CONSTRAINT `fk_detalle_orden` FOREIGN KEY (`idorden`) REFERENCES `orden` (`idorden`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`idproducto`) REFERENCES `producto` (`idproducto`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orden_detalle`
--

LOCK TABLES `orden_detalle` WRITE;
/*!40000 ALTER TABLE `orden_detalle` DISABLE KEYS */;
INSERT INTO `orden_detalle` VALUES (1,1,33,34,'AGUA MINERAL 700ML',1.00,1.00,3.00,3.00,0,'','en_preparacion'),(2,1,14,14,'ARROZ CHAUFA DE MARISCOS',1.00,1.00,28.00,28.00,0,'','en_preparacion'),(3,1,27,28,'CERVEZA SAN JUAN',1.00,1.00,9.00,9.00,0,'','en_preparacion'),(4,1,13,13,'ARROZ CHAUFA DE PESCADO',1.00,1.00,25.00,25.00,0,'','en_preparacion'),(5,2,33,34,'AGUA MINERAL 700ML',1.00,1.00,3.00,3.00,0,'','en_preparacion'),(6,2,15,15,'ARROZ CHAUFA DE LANGOSTINO',2.00,2.00,28.00,56.00,0,'','en_preparacion'),(7,2,14,14,'ARROZ CHAUFA DE MARISCOS',2.00,2.00,28.00,56.00,0,'','en_preparacion'),(8,2,27,28,'CERVEZA SAN JUAN',1.00,1.00,9.00,9.00,0,'','en_preparacion'),(9,2,28,29,'CERVEZA CUSQUEÑA TRIGO',1.00,1.00,10.00,10.00,0,'','en_preparacion'),(10,3,33,34,'AGUA MINERAL 700ML',1.00,1.00,3.00,3.00,0,'','en_preparacion'),(11,3,27,28,'CERVEZA SAN JUAN',2.00,2.00,9.00,18.00,0,'','en_preparacion'),(12,3,4,4,'CEVICHE DE PESCADO CLASICO',1.00,1.00,16.00,16.00,0,'','en_preparacion'),(13,3,9,9,'CEVICHE DE LANGOSTINO',1.00,1.00,25.00,25.00,0,'','en_preparacion'),(14,3,11,11,'LECHE DE TIGRE POWER (Pescado marinado en leche de tigre con mariscos y conchas)',1.00,1.00,30.00,30.00,0,'','en_preparacion'),(15,4,33,34,'AGUA MINERAL 700ML',2.00,2.00,3.00,6.00,0,'','en_preparacion'),(16,4,15,15,'ARROZ CHAUFA DE LANGOSTINO',1.00,1.00,28.00,28.00,0,'','en_preparacion'),(17,4,14,14,'ARROZ CHAUFA DE MARISCOS',1.00,1.00,28.00,28.00,0,'','en_preparacion'),(18,4,13,13,'ARROZ CHAUFA DE PESCADO',1.00,1.00,25.00,25.00,0,'','en_preparacion'),(19,4,27,28,'CERVEZA SAN JUAN',1.00,1.00,9.00,9.00,0,'','en_preparacion'),(20,4,28,29,'CERVEZA CUSQUEÑA TRIGO',1.00,1.00,10.00,10.00,0,'','en_preparacion'),(21,5,15,15,'ARROZ CHAUFA DE LANGOSTINO',1.00,1.00,28.00,28.00,0,'','en_preparacion'),(22,5,14,14,'ARROZ CHAUFA DE MARISCOS',1.00,1.00,28.00,28.00,0,'','en_preparacion'),(23,5,27,28,'CERVEZA SAN JUAN',1.00,1.00,9.00,9.00,0,'','en_preparacion'),(24,5,28,29,'CERVEZA CUSQUEÑA TRIGO',1.00,1.00,10.00,10.00,0,'','en_preparacion');
/*!40000 ALTER TABLE `orden_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permiso`
--

DROP TABLE IF EXISTS `permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permiso`
--

LOCK TABLES `permiso` WRITE;
/*!40000 ALTER TABLE `permiso` DISABLE KEYS */;
INSERT INTO `permiso` VALUES (1,'nuevaorden','Nueva Orden','Tomar nuevas ordenes (POS)','operacion',1),(2,'mesas','Gestionar Mesas','Ver y administrar mesas del salon','operacion',2),(3,'pedidos','Ver Pedidos','Listar y revisar pedidos activos','operacion',3),(4,'enviar_cocina','Enviar a Cocina','Enviar items en preparacion a cocina','operacion',4),(5,'cobrar','Cobrar Ordenes','Cobrar y cerrar ordenes','caja',5),(6,'anular_orden','Anular Ordenes','Anular ordenes activas','caja',6),(7,'clientes','Gestionar Clientes','CRUD de clientes','maestros',7),(8,'historial','Ver Historial','Consultar historial de ventas','reportes',8),(9,'caja','Operar Caja','Apertura/cierre y movimientos','caja',9),(10,'reportes','Ver Reportes','Acceso a metricas y graficos','reportes',10),(11,'productos','Gestionar Productos','CRUD productos y categorias','maestros',11),(12,'usuarios','Gestionar Usuarios','CRUD usuarios, roles y permisos','admin',12),(13,'comprobantes_sunat','Comprobantes SUNAT','Ver y gestionar boletas/facturas electronicas','sunat',20),(14,'emitir_boleta','Emitir Boleta','Emitir boletas electronicas al cobrar','sunat',21),(15,'emitir_factura','Emitir Factura','Emitir facturas electronicas al cobrar','sunat',22),(16,'enviar_sunat','Enviar a SUNAT','Enviar comprobantes a SUNAT','sunat',23),(17,'config_empresa','Configurar Empresa','Editar datos de la empresa emisora','sunat',24),(18,'config_certificado','Configurar Certificado','Cargar y gestionar certificado digital','sunat',25),(19,'config_numeracion','Configurar Numeracion','Gestionar series y correlativos','sunat',26),(20,'arqueo_caja','Arqueo de Caja','Realizar arqueo y cierre con conteo','caja',13),(21,'config_logo','Configurar Logo','Subir/cambiar logo de empresa','sunat',27),(22,'config_licencia','Gestionar Licencia','Activar/extender licencia del sistema','admin',30),(23,'emitir_nc','Emitir Nota de Credito','Anular comprobantes con NC','sunat',28),(24,'emitir_nd','Emitir Nota de Debito','Emitir notas de debito','sunat',29),(25,'resumen_boletas','Resumen Diario Boletas','Generar y enviar resumen RC','sunat',30),(26,'comunicacion_baja','Comunicacion de Baja','Anular boletas por resumen RA','sunat',31),(27,'whatsapp_enviar','Enviar WhatsApp','Enviar mensajes individuales (al cobrar)','whatsapp',50),(28,'whatsapp_plantillas','Plantillas WhatsApp','Gestionar plantillas de mensajes','whatsapp',51),(29,'whatsapp_masivo','Env??o masivo WhatsApp','Campa??as a m??ltiples clientes','whatsapp',52),(30,'zonas','Gestionar zonas','Crear, editar y eliminar zonas del salón','general',0),(31,'impresoras','Gestionar impresoras','Configurar IPs y tipos de impresoras LAN','general',0),(32,'inventario','Gestionar Inventario','Control de stock de productos','maestros',12);
/*!40000 ALTER TABLE `permiso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
INSERT INTO `producto` VALUES (1,'PH001','PAPA A LA HUANCAINA',18.00,'20',1,'',0,0,1,0,0.00,0.00),(2,'CA001','CAUSA ACEVICHADA',20.00,'20',1,'../public/img/productos/p2_d3870a8c7537.jpg',0,0,1,0,0.00,0.00),(3,'CL001','CAUSA DE LANGOSTINO',20.00,'20',1,'',0,0,1,0,0.00,0.00),(4,'CPC01','CEVICHE DE PESCADO CLASICO',16.00,'20',2,'',0,0,1,0,0.00,0.00),(5,'CC001','CEVICHE CON CHICHARRON',18.00,'20',2,'',0,0,1,0,0.00,0.00),(6,'CR001','CEVICHE DE ROCOTO',18.00,'20',2,'',0,0,1,0,0.00,0.00),(7,'CM001','CEVICHE MIXTO',28.00,'20',2,'',0,0,1,0,0.00,0.00),(8,'CME01','CEVICHE MIXTO ESPECIAL',35.00,'20',2,'',0,0,1,0,0.00,0.00),(9,'CDL01','CEVICHE DE LANGOSTINO',25.00,'20',2,'',0,0,1,0,0.00,0.00),(10,'LTC01','LECHE DE TIGRE + CHICHARRON',18.00,'20',2,'',0,0,1,0,0.00,0.00),(11,'LTP01','LECHE DE TIGRE POWER',30.00,'20',2,'../public/img/productos/p11_e2fe3182a81c.jpg',0,0,1,0,0.00,0.00),(12,'ACM01','ARROZ CON MARISCOS',28.00,'20',3,'',0,0,1,0,0.00,0.00),(13,'ACP01','ARROZ CHAUFA DE PESCADO',25.00,'20',3,'',0,0,1,0,0.00,0.00),(14,'ACDM01','ARROZ CHAUFA DE MARISCOS',28.00,'20',3,'../public/img/productos/p14_13c5ef0ed7f4.jpg',0,0,1,0,0.00,0.00),(15,'ACL01','ARROZ CHAUFA DE LANGOSTINO',28.00,'20',3,'',0,0,1,0,0.00,0.00),(16,'CP001','CHICHARRON DE PESCADO',28.00,'20',3,'',0,0,1,0,0.00,0.00),(17,'C+C01','CEVICHE + CHICHARRON',28.00,'20',4,'../public/img/productos/p17_74ce24092b69.jpg',0,0,1,0,0.00,0.00),(18,'C+CL01','CEVICHE + CAUSA DE LANGOSTINO',28.00,'20',4,'',0,0,1,0,0.00,0.00),(19,'C+AM01','CEVICHE + ARROZ CON MARISCOS',30.00,'20',4,'',0,0,1,0,0.00,0.00),(20,'C+CP01','CEVICHE + CHAUFA DE PESCADO',25.00,'20',4,'',0,0,1,0,0.00,0.00),(21,'C+CM01','CEVICHE + CHAUFA DE MARISCOS',28.00,'20',4,'',0,0,1,0,0.00,0.00),(22,'LT+C01','LECHE DE TIGRE + CHICHARRON',28.00,'20',4,'',0,0,1,0,0.00,0.00),(23,'CC+AM01','CEVICHE + CAUSA + ARROZ CON MARISCO',35.00,'20',5,'',0,0,1,0,0.00,0.00),(24,'C+CAM01','CEVICHE + CHICHARRON + ARROZ CON MARISCOS',33.00,'20',5,'',0,0,1,0,0.00,0.00),(25,'CC+C01','CEVICHE + CHICHARRON + CAUSA',33.00,'20',5,'',0,0,1,0,0.00,0.00),(26,'CCM+C01','CEVICHE + CHAUFA DE MARISCOS + CHICHARRON',33.00,'20',5,'',0,0,1,0,0.00,0.00),(27,'CSJ01','CERVEZA SAN JUAN',9.00,'20',6,'../public/img/productos/p27_814d56f32165.jpg',0,0,1,0,0.00,0.00),(28,'CCT01','CERVEZA CUSQUEÑA TRIGO',10.00,'20',6,'../public/img/productos/p28_586f9fb6f6a7.jpg',0,0,1,0,0.00,0.00),(29,'JDC01','JARRA DE CHICHA',12.00,'20',6,'../public/img/productos/p29_6ae000696f24.jpg',0,0,1,0,0.00,0.00),(30,'VDC01','VASO DE CHICHA',3.00,'20',6,'../public/img/productos/p30_9916a7f04fb3.jpg',0,0,1,0,0.00,0.00),(31,'IK001','INKA COLA',5.00,'20',6,'../public/img/productos/p31_85a76b3ac1f4.jpg',0,0,1,0,0.00,0.00),(32,'CCN01','COCA COLA 600ML',5.00,'20',6,'',0,0,1,0,0.00,0.00),(33,'AM001','AGUA MINERAL 700ML',3.00,'20',6,'../public/img/productos/p33_6614a36379a2.jpg',0,0,1,0,0.00,0.00),(34,'EP001','SPORADE 400ML',3.00,'20',6,'../public/img/productos/f33358ca83ab.jpg',0,0,1,0,0.00,0.00);
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto_precio`
--

DROP TABLE IF EXISTS `producto_precio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_precio`
--

LOCK TABLES `producto_precio` WRITE;
/*!40000 ALTER TABLE `producto_precio` DISABLE KEYS */;
INSERT INTO `producto_precio` VALUES (1,1,'Normal',18.00,1,1,1,0,0.00,0.00),(2,2,'Normal',20.00,1,1,1,0,0.00,0.00),(3,3,'Normal',20.00,1,1,1,0,0.00,0.00),(4,4,'Normal',16.00,1,1,1,0,0.00,0.00),(5,5,'Normal',18.00,1,1,1,0,0.00,0.00),(6,6,'Normal',18.00,1,1,1,0,0.00,0.00),(7,7,'Mixtura, langostinos, conchas abanicos',28.00,1,1,1,0,0.00,0.00),(8,8,'Mixto, langostino, conchas abanicos, cangrejo',35.00,1,1,1,0,0.00,0.00),(9,9,'Normal',25.00,1,1,1,0,0.00,0.00),(10,10,'Normal',18.00,1,1,1,0,0.00,0.00),(11,11,'Pescado marinado en leche de tigre con mariscos y conchas',30.00,1,1,1,0,0.00,0.00),(12,12,'Normal',28.00,1,1,1,0,0.00,0.00),(13,13,'Normal',25.00,1,1,1,0,0.00,0.00),(14,14,'Normal',28.00,1,1,1,0,0.00,0.00),(15,15,'Normal',28.00,1,1,1,0,0.00,0.00),(16,16,'Normal',28.00,1,1,1,0,0.00,0.00),(17,17,'Normal',28.00,1,1,1,0,0.00,0.00),(18,18,'Normal',28.00,1,1,1,0,0.00,0.00),(19,19,'Normal',30.00,1,1,1,0,0.00,0.00),(20,20,'Normal',25.00,1,1,1,0,0.00,0.00),(21,21,'Normal',28.00,1,1,1,0,0.00,0.00),(22,22,'POTA',28.00,1,1,1,0,0.00,0.00),(23,22,'PESCADO',28.00,0,2,1,0,0.00,0.00),(24,23,'Normal',35.00,1,1,1,0,0.00,0.00),(25,24,'Normal',33.00,1,1,1,0,0.00,0.00),(26,25,'Normal',33.00,1,1,1,0,0.00,0.00),(27,26,'Normal',33.00,1,1,1,0,0.00,0.00),(28,27,'Normal',9.00,1,1,1,1,9.00,5.00),(29,28,'Normal',10.00,1,1,1,1,8.00,5.00),(30,29,'Normal',12.00,1,1,1,0,0.00,0.00),(31,30,'Normal',3.00,1,1,1,0,0.00,0.00),(32,31,'Normal',5.00,1,1,0,1,11.00,5.00),(33,32,'Normal',5.00,1,1,1,1,10.00,5.00),(34,33,'Normal',3.00,1,1,1,1,17.00,5.00),(35,34,'PERSONAL',2.50,0,1,0,1,0.00,0.00),(36,34,'PUBLICO',3.00,1,1,1,1,7.00,5.00),(37,31,'PERSONAL 600ML',5.00,1,1,1,1,26.00,5.00),(38,31,'GORDITA',7.00,0,2,1,1,23.00,5.00),(39,31,'VIDRIO RETORNABLE 1LT',10.00,0,3,1,1,11.00,3.00);
/*!40000 ALTER TABLE `producto_precio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resumen_detalle`
--

DROP TABLE IF EXISTS `resumen_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resumen_detalle`
--

LOCK TABLES `resumen_detalle` WRITE;
/*!40000 ALTER TABLE `resumen_detalle` DISABLE KEYS */;
/*!40000 ALTER TABLE `resumen_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resumen_sunat`
--

DROP TABLE IF EXISTS `resumen_sunat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resumen_sunat`
--

LOCK TABLES `resumen_sunat` WRITE;
/*!40000 ALTER TABLE `resumen_sunat` DISABLE KEYS */;
/*!40000 ALTER TABLE `resumen_sunat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol` (
  `idrol` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idrol`),
  UNIQUE KEY `uk_rol_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` VALUES (1,'admin','Administrador','Acceso total al sistema',1),(2,'cajero','Cajero','Toma ordenes, cobra y maneja caja',1),(3,'mozo','Mozo','Toma ordenes y envia a cocina',1);
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol_permiso`
--

DROP TABLE IF EXISTS `rol_permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol_permiso` (
  `idrol` int(10) unsigned NOT NULL,
  `idpermiso` int(10) unsigned NOT NULL,
  PRIMARY KEY (`idrol`,`idpermiso`),
  KEY `idx_rp_permiso` (`idpermiso`),
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`idpermiso`) REFERENCES `permiso` (`idpermiso`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_rol` FOREIGN KEY (`idrol`) REFERENCES `rol` (`idrol`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol_permiso`
--

LOCK TABLES `rol_permiso` WRITE;
/*!40000 ALTER TABLE `rol_permiso` DISABLE KEYS */;
INSERT INTO `rol_permiso` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),(1,22),(1,23),(1,24),(1,25),(1,26),(1,27),(1,28),(1,29),(1,30),(1,31),(1,32),(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(2,7),(2,8),(2,9),(2,13),(2,14),(2,15),(2,16),(2,20),(2,23),(2,27),(3,1),(3,2),(3,3),(3,4);
/*!40000 ALTER TABLE `rol_permiso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rutas`
--

DROP TABLE IF EXISTS `rutas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rutas`
--

LOCK TABLES `rutas` WRITE;
/*!40000 ALTER TABLE `rutas` DISABLE KEYS */;
/*!40000 ALTER TABLE `rutas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seguridad_log`
--

DROP TABLE IF EXISTS `seguridad_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seguridad_log`
--

LOCK TABLES `seguridad_log` WRITE;
/*!40000 ALTER TABLE `seguridad_log` DISABLE KEYS */;
INSERT INTO `seguridad_log` VALUES (1,'2026-06-09 21:54:14','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',''),(2,'2026-06-10 15:22:03','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','');
/*!40000 ALTER TABLE `seguridad_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,1,'Admin','Puerto Habana','','','','admin@puertohabana.local','admin','$2y$10$xI7pAUfDGJTFAVs4SgDNPuGJB1DRrbx5PRjgvXxEf6L.W8pO2SEOy',NULL,1,0,NULL,'2026-06-10 15:22:03');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_permiso`
--

DROP TABLE IF EXISTS `usuario_permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario_permiso` (
  `idusuario` int(10) unsigned NOT NULL,
  `idpermiso` int(10) unsigned NOT NULL,
  `tipo` enum('grant','revoke') NOT NULL DEFAULT 'grant',
  PRIMARY KEY (`idusuario`,`idpermiso`),
  KEY `idx_up_permiso` (`idpermiso`),
  CONSTRAINT `fk_up_permiso` FOREIGN KEY (`idpermiso`) REFERENCES `permiso` (`idpermiso`) ON DELETE CASCADE,
  CONSTRAINT `fk_up_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_permiso`
--

LOCK TABLES `usuario_permiso` WRITE;
/*!40000 ALTER TABLE `usuario_permiso` DISABLE KEYS */;
INSERT INTO `usuario_permiso` VALUES (1,1,'grant'),(1,2,'grant'),(1,3,'grant'),(1,4,'grant'),(1,5,'grant'),(1,6,'grant'),(1,7,'grant'),(1,8,'grant'),(1,9,'grant'),(1,10,'grant'),(1,11,'grant'),(1,12,'grant'),(1,13,'grant'),(1,14,'grant'),(1,15,'grant'),(1,16,'grant'),(1,17,'grant'),(1,18,'grant'),(1,19,'grant'),(1,20,'grant'),(1,21,'grant'),(1,22,'grant'),(1,23,'grant'),(1,24,'grant'),(1,25,'grant'),(1,26,'grant'),(1,27,'grant'),(1,28,'grant'),(1,29,'grant'),(1,30,'grant'),(1,31,'grant'),(1,32,'grant');
/*!40000 ALTER TABLE `usuario_permiso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_envio`
--

DROP TABLE IF EXISTS `whatsapp_envio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_envio`
--

LOCK TABLES `whatsapp_envio` WRITE;
/*!40000 ALTER TABLE `whatsapp_envio` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_envio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_plantilla`
--

DROP TABLE IF EXISTS `whatsapp_plantilla`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_plantilla`
--

LOCK TABLES `whatsapp_plantilla` WRITE;
/*!40000 ALTER TABLE `whatsapp_plantilla` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_plantilla` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zona`
--

DROP TABLE IF EXISTS `zona`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zona` (
  `idzona` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `color` char(7) NOT NULL DEFAULT '#5b3df5',
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idzona`),
  UNIQUE KEY `uk_zona_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zona`
--

LOCK TABLES `zona` WRITE;
/*!40000 ALTER TABLE `zona` DISABLE KEYS */;
INSERT INTO `zona` VALUES (1,'LOCAL INTERIOR','#f59e0b',1,1);
/*!40000 ALTER TABLE `zona` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-13  9:58:12
