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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caja_arqueo`
--

LOCK TABLES `caja_arqueo` WRITE;
/*!40000 ALTER TABLE `caja_arqueo` DISABLE KEYS */;
INSERT INTO `caja_arqueo` VALUES (1,3,1,'2026-05-09 20:37:00',201.00,201.00,0.00,'','{\"1\":1,\"100\":2}'),(2,4,1,'2026-05-13 18:37:25',629.20,800.00,170.80,'','{\"200\":4}'),(3,5,1,'2026-05-13 19:05:35',42.00,42.00,0.00,'','{}'),(4,6,2,'2026-05-14 00:35:50',47.00,47.00,0.00,'','{}'),(5,7,1,'2026-05-31 17:58:38',361.90,361.90,0.00,'','{}');
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
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caja_movimiento`
--

LOCK TABLES `caja_movimiento` WRITE;
/*!40000 ALTER TABLE `caja_movimiento` DISABLE KEYS */;
INSERT INTO `caja_movimiento` VALUES (1,1,'apertura',50.00,'Apertura de caja',NULL,NULL,'2026-05-09 13:32:12'),(2,1,'venta',1.00,'Cobro orden #00002','efectivo',2,'2026-05-09 13:36:20'),(3,2,'apertura',100.00,'Apertura de caja',NULL,NULL,'2026-05-09 13:43:33'),(4,2,'venta',32.00,'Cobro orden #00003','efectivo',3,'2026-05-09 13:47:34'),(5,2,'venta',1.00,'Cobro orden #00004','efectivo',4,'2026-05-09 14:02:11'),(6,3,'apertura',200.00,'Apertura de caja',NULL,NULL,'2026-05-09 14:12:29'),(7,3,'venta',1.00,'Cobro orden #00005','efectivo',5,'2026-05-09 14:13:41'),(8,3,'cierre',201.00,'Cierre de caja',NULL,NULL,'2026-05-09 20:37:00'),(9,4,'apertura',100.00,'Apertura de caja',NULL,NULL,'2026-05-09 20:50:55'),(10,4,'venta',40.00,'Cobro orden #00008','efectivo',8,'2026-05-10 12:05:55'),(11,4,'venta',29.50,'Cobro orden #00009','efectivo',9,'2026-05-10 12:38:29'),(12,4,'venta',40.90,'Cobro orden #00010','efectivo',10,'2026-05-10 12:42:00'),(13,4,'venta',56.00,'Cobro orden #00011','efectivo',11,'2026-05-10 12:46:55'),(14,4,'venta',60.00,'Cobro orden #00012','efectivo',12,'2026-05-10 13:00:05'),(15,4,'venta',59.00,'Cobro orden #00013','efectivo',13,'2026-05-10 13:48:59'),(16,4,'venta',75.00,'Cobro orden #00014','efectivo',14,'2026-05-10 14:12:31'),(17,4,'venta',26.90,'Cobro orden #00015','efectivo',15,'2026-05-10 15:02:20'),(18,4,'venta',2.00,'Cobro orden #00016','efectivo',16,'2026-05-10 17:15:26'),(19,4,'venta',37.90,'Cobro orden #00017','efectivo',17,'2026-05-11 13:11:25'),(20,4,'venta',2.00,'Cobro orden #00018','efectivo',18,'2026-05-11 14:53:47'),(21,4,'venta',2.00,'Cobro orden #00019','efectivo',19,'2026-05-12 09:48:43'),(22,4,'venta',20.00,'Cobro orden #00020','efectivo',20,'2026-05-12 17:54:33'),(23,4,'venta',2.00,'Cobro orden #00021','efectivo',21,'2026-05-13 11:15:31'),(24,4,'venta',2.00,'Cobro orden #00022','efectivo',22,'2026-05-13 11:51:23'),(25,4,'venta',28.00,'Cobro orden #00026','efectivo',26,'2026-05-13 14:56:17'),(26,4,'venta',15.00,'Cobro orden #00027','efectivo',27,'2026-05-13 14:56:36'),(27,4,'venta',5.00,'Cobro orden #00028','efectivo',28,'2026-05-13 14:56:52'),(28,4,'venta',15.00,'Cobro orden #00029','efectivo',29,'2026-05-13 14:57:33'),(29,4,'venta',2.00,'Cobro orden #00030','efectivo',30,'2026-05-13 14:57:41'),(30,4,'venta',9.00,'Cobro orden #00031','efectivo',31,'2026-05-13 14:57:55'),(31,4,'cierre',800.00,'Cierre de caja',NULL,NULL,'2026-05-13 18:37:25'),(32,5,'apertura',20.00,'Apertura de caja',NULL,NULL,'2026-05-13 18:38:14'),(33,5,'venta',22.00,'Cobro orden #00032','efectivo',32,'2026-05-13 19:04:20'),(34,5,'cierre',42.00,'Cierre de caja',NULL,NULL,'2026-05-13 19:05:35'),(35,6,'apertura',20.00,'Apertura de caja',NULL,NULL,'2026-05-13 19:07:18'),(36,6,'venta',5.00,'Cobro orden #00035','efectivo',35,'2026-05-13 22:43:51'),(37,6,'venta',2.00,'Cobro orden #00034','efectivo',34,'2026-05-13 22:44:12'),(38,6,'venta',20.00,'Cobro orden #00036','efectivo',36,'2026-05-14 00:30:47'),(39,6,'cierre',47.00,'Cierre de caja',NULL,NULL,'2026-05-14 00:35:50'),(40,7,'apertura',20.00,'Apertura de caja',NULL,NULL,'2026-05-14 00:45:21'),(41,7,'venta',2.00,'Cobro orden #00037','efectivo',37,'2026-05-14 00:45:38'),(42,7,'venta',10.00,'Cobro orden #00038','yape',38,'2026-05-14 00:46:18'),(43,7,'venta',24.00,'Cobro orden #00039','mixto',39,'2026-05-14 00:46:56'),(44,7,'venta',2.00,'Cobro orden #00040','efectivo',40,'2026-05-14 09:46:49'),(45,7,'venta',5.00,'Cobro orden #00041','efectivo',41,'2026-05-14 09:53:36'),(46,7,'venta',3.00,'Cobro orden #00042','efectivo',42,'2026-05-14 09:57:09'),(47,7,'venta',5.00,'Cobro orden #00043','efectivo',43,'2026-05-14 14:56:29'),(48,7,'venta',5.00,'Cobro orden #00044','efectivo',44,'2026-05-15 09:57:00'),(49,7,'venta',48.00,'Cobro orden #00045','efectivo',45,'2026-05-15 14:51:06'),(50,7,'venta',2.00,'Cobro orden #00049','efectivo',49,'2026-05-17 16:02:22'),(51,7,'venta',2.00,'Cobro orden #00050','efectivo',50,'2026-05-17 16:07:18'),(52,7,'venta',63.00,'Cobro orden #00051','efectivo',51,'2026-05-18 08:55:31'),(53,7,'venta',45.00,'Cobro orden #00052','efectivo',52,'2026-05-18 09:11:23'),(54,7,'venta',2.00,'Cobro orden #00053','efectivo',53,'2026-05-18 14:22:43'),(55,7,'venta',2.00,'Cobro orden #00057','efectivo',57,'2026-05-18 19:15:24'),(56,7,'venta',30.00,'Cobro orden #00054','efectivo',54,'2026-05-18 19:16:28'),(57,7,'venta',5.00,'Cobro orden #00055','efectivo',55,'2026-05-18 19:16:43'),(58,7,'venta',18.00,'Cobro orden #00058','efectivo',58,'2026-05-21 12:13:00'),(59,7,'venta',25.00,'Cobro orden #00060','efectivo',60,'2026-05-31 13:19:50'),(60,7,'venta',20.00,'Cobro orden #00061','efectivo',61,'2026-05-31 13:30:36'),(61,7,'venta',16.00,'Cobro orden #00062','efectivo',62,'2026-05-31 13:31:26'),(62,7,'venta',7.00,'Cobro orden #00063','efectivo',63,'2026-05-31 16:00:53'),(63,7,'venta',29.90,'Cobro orden #00064','efectivo',64,'2026-05-31 16:01:36'),(64,7,'venta',5.00,'Cobro orden #00065','efectivo',65,'2026-05-31 17:02:00'),(65,7,'venta',47.00,'Cobro orden #00066','yape',66,'2026-05-31 17:57:38'),(66,7,'cierre',361.90,'Cierre de caja',NULL,NULL,'2026-05-31 17:58:38'),(67,8,'apertura',50.00,'Apertura de caja',NULL,NULL,'2026-06-02 10:57:16'),(68,8,'venta',20.00,'Cobro orden #00067','efectivo',67,'2026-06-02 11:04:11'),(69,8,'venta',56.00,'Cobro orden #00068','efectivo',68,'2026-06-02 17:50:37'),(70,8,'venta',30.00,'Cobro orden #00069','efectivo',69,'2026-06-02 19:02:17'),(71,8,'venta',22.00,'Cobro orden #00070','efectivo',70,'2026-06-02 19:56:16'),(72,8,'venta',4.00,'Cobro orden #00071','efectivo',71,'2026-06-02 20:00:23');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caja_sesion`
--

LOCK TABLES `caja_sesion` WRITE;
/*!40000 ALTER TABLE `caja_sesion` DISABLE KEYS */;
INSERT INTO `caja_sesion` VALUES (1,'CAJA001','Tarde','Juan Perez',2,50.00,NULL,'2026-05-09 13:32:12','2026-05-09 14:11:28',0),(2,'AP-001','Tarde','Admin',1,100.00,NULL,'2026-05-09 13:43:33','2026-05-09 14:11:28',0),(3,'AP-001','Tarde','Juan Perez',2,200.00,201.00,'2026-05-09 14:12:29','2026-05-09 20:37:00',0),(4,'AP-001','Mañana','Admin YAPEZ',1,100.00,800.00,'2026-05-09 20:50:55','2026-05-13 18:37:25',0),(5,'AP-001','Mañana','Admin YAPEZ',1,20.00,42.00,'2026-05-13 18:38:14','2026-05-13 19:05:35',0),(6,'AP-001','Mañana','Admin YAPEZ',1,20.00,47.00,'2026-05-13 19:07:18','2026-05-14 00:35:50',0),(7,'AP-001','Mañana','Juan Perez',2,20.00,361.90,'2026-05-14 00:45:21','2026-05-31 17:58:38',0),(8,'AP-001','Mañana','Admin Puerto Habana',1,50.00,NULL,'2026-06-02 10:57:16',NULL,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES (1,'Ceviches','Ceviches','fa-lemon','#f6fa0a',1,1),(2,'Duo Marino','Duo Marino','fa-fish','#1e81eb',2,1),(3,'Trios Marinos','Trios Marinos','fa-fish-fins','#f59e0b',3,1),(4,'Chicharrones','Chicharrones','fa-bacon','#f59e0b',5,1),(5,'Arroz','Porción De Arroz','fa-bowl-rice','#dc2626',6,1),(6,'bebidas','Bebidas','fa-bottle-water','#10b981',7,1),(7,'Rondas Marinas','Rondas Marinas','fa-shrimp','#f59e0b',4,1),(8,'promociones','Promociones','fa-tag','#dc2626',8,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cdr_log`
--

LOCK TABLES `cdr_log` WRITE;
/*!40000 ALTER TABLE `cdr_log` DISABLE KEYS */;
INSERT INTO `cdr_log` VALUES (24,21,'ENVIO_SUNAT','0','La Boleta numero B001-21, ha sido aceptada','','{\"ok\":true,\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-21, ha sido aceptada\",\"observaciones\":[\"4093 - El codigo de ubigeo del domicilio fiscal del emisor no es v\\u00e1lido - : 4093: Valor no se encuentra en el catalogo: 13 (nodo: \\\"cac:RegistrationAddress\\/cbc:ID\\\" valor: \\\"25001\\\")\",\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"2.00\\\")\"],\"cdr_zip\":\"UEsDBBQAAgAIAONNrlwAAAAAAgAAAAAAAAAGAAAAZHVtbXkvAwBQSwMEFAACAAgA402uXJkN0N8PDAAARhgAABwAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjEueG1stVlrc6LMtv6+fwWVqTp1TmVnABUVdya7mqsooFwNfkNAQLmFu\\/7602A0ZiZTe963alcqlXb16mc969JLFnn+dxtHSO3lRZgmPx7w79gD4iVO6oaJ\\/+PB0Lmn6cO\\/X\\/7xbOczkGVR6NglVFS9IkuTwkPg4aT48VDlySy1i7CYJXbsFbMi85xw\\/648q3bRrHACL7ZnbeHOhKROQ8d7Gjxcjs\\/s\\/C8ifMHkA81ry78IR6dxnCZsW3pJFwX4EUJ6SVl8gDo752+BUlDd+RLQ\\/nuAwPdzz7dL7ytQF6YiKMtshqJN03xvht\\/T3EcHGIahGIlCHbcI\\/W9X7SK1s5v+xVDxHW518v5gt0C9pPaiNPPQmxFo\\/HbMa4uo7JU7cfFkJ+5TGUJfbkaufhZVYpe\\/9TPz8ureWa3T\\/spX\\/Arc\\/s5XHH2VRK2HuupCFK\\/NviANN6rIzp\\/gbu4VXfKLh5dnWEEzgxJvBVFcy\\/yLvYvkrnYSuCpfnrXQhx5U+e2K\\/EFe4DXrjnmukOzTl38gyDNtJ2kC4xSF5z5WklcGqYuAyE\\/zsAzi34YAxzpY6Jfz5OCj5NsGancF1MXwAe2xbwz\\/GBQbXbk+xWnufcsL+6kIbAIfvEOq3t7LYffwEEMVunBBIRTruZ0U+zSPi4vgXvQfzX4K0bUY3afiyv5i+i+C\\/kmAICD6M\\/NnJvS9ovzDiH2iDgOF34AvMKYdVd5LDLLDaMnWvlQPl21QSPX5dT9\\/Y5pN8+MZvdfsQozeYgyrBf1cLvdJvZw4zHlvk0\\/nK0FPeYqLz5PTolph4+2Q2+byW7KwJpLwOBkuH6NkObAC8Sg6+mgZpFExXDSLTG2t0WP+Sp5GvC2ZKlcO03NN+qQoPA6dSmUHrhEcLAVPzFUSg9rLRLENT9l6HKHNRq\\/wUtzsN2+TQD\\/pJSvUpGSSS368MqN1Ojydsc0W81o7eAvq0ZxZy8Dh9JUrL+fnldiqKcObu6LJzK1bTtq3o1RX5tkmxxmjRxZJuhY\\/3WOVR26ho3OT92Vdb8vNKKhWG5R268e04cnNOpuzO3Fhn3RrsyryXULtpcQK54\\/eYbVll0VzJqPsmGbsTuFOtLvTkkPIxTSMeTp6NGi9fluXFmE4WqxNTtl80BR27f\\/4cYn8XaCfl96pT8PzK4GRjF3alxXt5eWl03kvkiBw+YGmqYXtg0aggC8Ie1J3JzD3mFE0jGItlulWCGpHBgorUgpo1AN7lGiBB7jBgqbZ6Xwb7DZs5fCGb2ABJR2MgcDJmLUhjtbr4iiwMiWpbMM0FmMqypIBgaSznKxohKLqrCiBY49FBRJtsEYzDxxZOijNigGEpFutrEvEppc5dzLQbCBvYCrt8gwyypdNChwl4chtxJCaK+aC0YyWMrCpr7KGr7Osbx7NuaJRK+M4rQSGxeSz0AoH4FP+8S04hjzZYBStsBt2uCi3r1mwxaizuyEClyaS3UDo\\/GsVHaAXW5K04mTTYE1NMwgD2lwphlQJrMoJLMeaBrdSTMnfaBSrahSlsvhCP0a6yS0oBYvWekgZxjGiTVOl4F8Yn6bh\\/T4+IkPJlIqZKxPyVkzT0DGcMtnIVgx1LWmjRgTverS5sTZttuW54+5ENbuh4CvGghHYiFUhJ81QKZWmWM1wFybLSQLkKplKY\\/jveWBL3TQpDtqHtsyVCn8hN0pgiQvHY3fm7\\/kj0Jhv4BAP5ofRgXiJmSNRbLvQDbalz2BxkVk6iChoSKBgLuT7XMB64wBY0UCZgm6f9pdwzQLczuxssWqkA1lSmb8E\\/kFthCg1jo9tUyvb1sEKdHSKRjGj5Q7KoevldqhQ8XI3Gu65JoT3x6iLfDzdK\\/r+JC4lfEQ9tq9NtFPQUzB8nE4K6VRFYrBax6W2MrA1O1UbbF3EvNnucRGbr8HaGfJ1FbyNtiTTEtSJZcbFo5llTJw4tVNGC1TOz9aWPpJzEl+Fia6vzW3OlLv4LVlZuT1d27qa0gLJxYnrpeQqnXiFNFmar+SeGMqsi8ay8nYGq9zXXsuAq6itPDmvCr5djLZ8bp7nWkTP0QMn+qsdG57n+VtY+CLutMvDVKl3vGgc5NKo9FolBGscNKpIt7so1O3piJJxjMUO9CQIQJJUrAC4RdgIDFAAlY4E6tDdKyttmK7OVEwHyhylgNAABuy7nM01ieUZsPEp1Q25qhwPXidoHIaEHNVr0z2l1TSPdSqh4KMS5bMcpTgwl+pO4o6NrFjCsrEoSjHmEljyy02AuXMwFk\\/kwUmUavfRS+rLmmu2rx+9xBqwlTUwTxINeJoueKAYHHUGVBCoqTtXm1U4rXcD+ezQRLHjudLSiGarA6\\/nrbIsdwamr8qn3cYcWxsXOEOzsl6VajsgQzGhIom1ul7kCkBBJQZrVo0l93wBz0RDB8AYMUAHbxLtf9iHfZML2t6+c05rcdAW1gYPOjxxuIgcnjzbr2rtJMdaorAef6EoG4lS7jAk+i5eDMWA8YXzlJXOoJXoR1+0QR6E0c1Pa7goxPjO1rtdhzfPLs9VsC+ctrwciDH0lwFuj6eMWA52iUo9rumTNCB8UBGFR\\/K5TaPGCpu8ThvG7\\/O+vuRdYYC\\/AxLAeFp74zVhN2QUlqIbAwBYK0Ch6OmjJU3idDvZLS1aezTPi2lzPrnaLt14cgWKAb0\\/BsI0UCUlx3Nis\\/WnGRPlKV1EHJqPtcPumB8qZu7wy4UuDfKQZw\\/RihnRokGah9V6ortjWtieFqpon8fDbGutGhabY43Ho+vaytKM2Z4XY\\/BqnbdtYx0X2BBt0bqx6aIgt3VcsMmAxduS38FnVVJiC3+aclgVDSs1mSonwueGk700sZYVpDMKKY2otJpyALEYcDsBXStRMVn5As2cdsMNPua9qLJ18phbRKO0YbTQMt40Hh+TmpDPsTo\\/vlXzYegMhBJdrsfSlnwdlHt\\/zAUZN17YxnkiadbjAlsmDOPsF1HQ+snRT3WiNIdaPj4T60yZj5\\/Rn7+p+69uoSgqL9e8PLSje4kMR5gX+sea\\/Sey+iGKMF3fuzUt\\/xAjO7a\\/Zx6izcGAGCNaCcchO3cRGlxs3AH0gBdwuYp3Xv5CDnB8MMTx0Wg6HBHEAB+Sl1OftO6BrtzQj2cO9PYcss7TLC3CMn2hqziLPCS7Cp7Rjz34yF6ncBbtJpl3vfwm6R42b7vPuiZeVcoigs\\/G8PMz22ZhflGQUySwEa8XuNDI3dbd4xJcfz0wob9OVr+IipdnOIh3IvPywkJgXgbfsWf0F2mvR1dFmcbvoxMU4lfVnzd67Q4LG4wH2GRAECRJTIYX5dt+F3OmK49O7QkjnvDRu8Zt50NRh0PwC0bORuPZiLxT6+W92vXNxZeQnzY\\/qd+AJzNs8ln5Hdt2Znfhfnehk2iGDPQ7p26KaX5a23l5usj6peDCrNxm9LsI4UP4MyAJ4gMI\\/f2p68al5rsD\\/eqOyWUH\\/UkT\\/R05OA+FpR3dHARlaTtB3JdQt9\\/VSp7Y0ceYdCkZVXj59lMMOtnF0BeH0P9kDP0iznIKszXCyCHyhLAR0r1F81PE9ZBqB8e4bhUhLiw8J4zCFNmHBZzse6EXh0WaI0mKeAVS\\/8+3wYD4VxS6KcSZIR3gDIFzxkWj8LoXdBUkkcPrliDwOAw33PXTGYIPkf9NUheuHjp+queHBdTrsgFct3vH8Z61B6TuAKHegIDz8MP\\/vWcjLT\\/5MsRIyEG0kaKKu1SEdudOf7RfQBZ2R8pJ87yvQii0kSgtkDDO0ryE7jiwNLswuVD4hAgyt+p86oDvmYqeb0dSmnilnZ\\/0FPrT8xHDxLtdfxCnVVJ+MMe+Y9hviePYr8QjpEfuVz\\/5EF+CXly5V3DqhqvetS7auWt3KySykZ0NU9C5l4Q72AzvfII2\\/8An3W6FxImqIqy9n10a\\/OpSV2YejGv+37mi6JcGVM\\/xIL0\\/tYnDponjGDkgJ39s8wsTTOpU3e26NrQrl9unvtm931FogoKV+zTAr13wY+NTx6RTF3bMz62yl\\/VajFc4eZj1xGC5UGnkdSUNeeQp8m7gn903W9HdR9vxstJ27Qva\\/dmrU\\/fMP\\/z51Ix+Zn4L1lf6l0iFWQjlf5qNpwk2mhKj0fivJOOTCfTrdKBf\\/xvg5f8BUEsBAgAAFAACAAgA402uXAAAAAACAAAAAAAAAAYAAAAAAAAAAAAAAAAAAAAAAGR1bW15L1BLAQIAABQAAgAIAONNrlyZDdDfDwwAAEYYAAAcAAAAAAAAAAEAAAAAACYAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjEueG1sUEsFBgAAAAACAAIAfgAAAG8MAAAAAA==\"}','2026-05-14 09:47:06'),(25,22,'ENVIO_SUNAT','0102','Usuario o contrasena incorrectos - Detalle: ','','{\"ok\":false,\"codigo\":\"0102\",\"mensaje\":\"Usuario o contrasena incorrectos - Detalle: \"}','2026-05-14 09:53:54'),(26,22,'ENVIO_SUNAT','0140','Existe un Documento igual en Proceso. Vuelva intentarlo en 15 minutos.','','{\"ok\":false,\"codigo\":\"0140\",\"mensaje\":\"Existe un Documento igual en Proceso. Vuelva intentarlo en 15 minutos.\"}','2026-05-14 09:54:56'),(27,23,'ENVIO_SUNAT','0','La Boleta numero B001-23, ha sido aceptada','','{\"ok\":true,\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-23, ha sido aceptada\",\"observaciones\":[\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"3.00\\\")\"],\"cdr_zip\":\"UEsDBBQAAgAIAC1PrlwAAAAAAgAAAAAAAAAGAAAAZHVtbXkvAwBQSwMEFAACAAgALU+uXLyIONSdCwAAehcAABwAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjMueG1stVhpc6paFv3+foWVW9XVXelcJnFIJ3l1GAUBZVT8hoCAMiiDoL++DxiNuTe3+r5X1ZWyctx7n7XXHtic48ufTRL3jn5eRFn6+oB9Rx96fupmXpQGrw+mwT2NHv58++PFyZ\\/Bfh9HrlNCQ80v9lla+D24OS1eH6o8fc6cIiqeUyfxi+di77vR5t34uVrHz4Ub+onz3BTes5Aes8j1n\\/CHy\\/ZnJ\\/+LCF8w+UDzm\\/IvwtFZkmQp25R+2mYBfoWQfloWH6Du2v1boBQ0d78EdP4eIAiC3A+c0v8K1IOlCMty\\/4wgdV1\\/r4nvWR4gOIqiCDpGoI1XRMG3q3WROfub\\/cVR8R2qWnm3sV0gfnr042zvIzcn0Pltm98UcdkZt+LiyUm9pzKCsdycXOMsqtQpfxnn3s+r+2D11vqrWLErcPOrWDFkKUt6B3W1hSh+s\\/+CNFRUsZM\\/QW3uF23xi4e3F9hBzyYl3RqiuLb5F7qL5K53Urgq3170KIARVPntEfmNusDHrN3me0K6yd7+6PVeaCfNUpinODp3uZL9Msy8HoiDLI\\/KMPllCjC0hYVxuU8u1k+\\/LaB120BtDh+QDvvG8LdB0f6V61OS5f63vHCeitAhMfwdUvM3fg6nh98zNaFNFxRCsZE7abHJ8qS4CO5F\\/9PtpxRdm9F7Kq7sL67\\/IujvJAgCIj8yf2GiwC\\/K38zYJ+owUdgN+AJjOXHlv0WjPlLyKmMQo3m\\/TxBN5bnRMIrdk\\/v6gtxbtilGbjmG3YJ8bpf7ol521InO2v4QEtV8KuBj2djkW05Eg\\/JAmnmIio\\/qQpTk\\/shkCYfG5zHPWcz8IDtb5RBTjKdU7FJj+kuBTEWEwnV7OQYIEixsOgqM4Xm5IZ06IhCR95uKLKNJdLQ5GR9G1MQTT9yk3rIBNp2h6NimzbNDJTug+3VfeTydBNmzw2mffnQHvsstkzJ2kUL2sQPIidDY2jCQBVqmIfqIjpwwzX1qvfGt2A7n8cFIsCU7QDh7uBc3zrbSF2ZWYDv9YDjnYEEyVV4YLnd+LHXHL3G6OFlY7hDNUF2Xs1hmymqqH9gZxgWDeVQJeY0SK3J7WlSBFNdGX91Ia07Tcmx5iPojmlarw8w5jR\\/5kpNoYnCkwevrJfN3iX6Z+qeuDC9LEh0zTulcVrSfl5dJ57\\/JgsDlW5qmRCcAtUCBQBA2Y8MbsscANYuaUW1xmq2E8OgqQGUlSgW1tmV3Mi3wADNZUNdrg2\\/C9YKtXN4MTDSk5K2JC5yC2gtyZy\\/FncAqlKyxNVPbjKWqUwaEssFyiqqTqmawkgx2HRYVyrTJmvUkdBV5q9YzBpCyYTeKIZOLTubeyUC9gLyBpTbTM9hTgWJRYCcLO24hwVKrlsjoZkOZ6CjQWDMwWDawdtZE1amZuRtVAsOiyllohC0IqGB3CHcRP65RilbZBUuI5Wq5D1codfYWZOjRZLrGhTa+RjUAcvElyzNOsUzW0nWTNKHPmWrKlcBqnMByrGVyM9WSg4VOsZpOURqLicYuNixOpFQ0nhsRZZq7mLYsjYL\\/YX7qmg+6\\/EgMpVAaas0syFu1LNNAMcpiY0c1tbms92sJvNvR1sJeNPsVz+3WJ6peE0KgmiIjsDGrQU66qVEaTbG66YkWy8kC5Cpbam0G73VgS8OyKA76h76smQY\\/kBslsOSF467d8\\/fiEWg0MDGIB+vDGEC65MyVKbYRDZNt6DMQLzLbADEFHQkUrIVyXwvYbxwAMxqoI9Dq6WAK1yzAnL2zF2e1vB2X1D6YgmCr1UKcmbvHpj6qq8ZFC6R\\/ivsJo+cuwiHz6YpQqWS67hMbro6Oh7l5LPLBaKMam5M0lbE+9dgs63itIqeQeBwNC\\/lUxVI4myelPjPROTvSanReJLzVbDAJnczB3CX4YxUe+qsx05DUiWUGxaO13zNJ6h7dMhYRJT\\/bK3o3noyxWZQaxtxa5Uy5Tg7pzM6d0dwxtIwWxlySen42nmVDv5CHU2s53pCEwnpIoqiHM5jlgb4sQ66iVsrwPCv4Ruyv+Nw6T\\/SYniBbTgpmazY6T\\/JDVAQS5jbT7Ug9rnnJ3CqlWRlHjRTsQVhrEt2s48hwRn1KwVAW3dLDMARpWrEC4MSoFhigAirrC9S2fa7srGbaPtNQA6gThAJCDRiwaWs20WWWZ8AioDQv4qpygC+HSBJFpBIf55Z3yqpRnhhUSsGjEhWwHKW6sJbaWuZ2taLawrS2KUo1JzKY8tNFiHoTMJBO462bqtX6Y5YcL2uuXi0\\/ZomNs5WNWyeZBjxNFzxQTY46AyoMtcybaPUsGh3XuHJ2abJY81xp62S9MoDf8dZYljsDK9CU03phDeyFB1zCquylWq3wcSSlVCyzdjuLPAGoiMyg9ay2lY4v4JmYcAHMEQMMcJDp4MM\\/nJtc2HT+3XN2lPCmsBdY2OJJhBi7\\/PjsLLWjm+6OMoV2+KKqLmRKvcOQ6bt8MRQDBhfOI1Y+g0amHwPJAXkYxbc4bUIspOTO17tfl7fOHs9VcC6cVrwSSgmMlwFeh6f2WQ5OiUrbzemTjJMBqMjCH\\/O5QyPmDB0uRzUTdHWfX+quMiBYAxmgPK0feF1YE4zKUnRtAgB7BagUPXq05WGSrYbrqU3rj9ZZHNXnk6evs4WvVKDA6c0uFEahJqs5lpOLVTDaM3Ge0UXMIflA3653+bZiJi4\\/FQ0ZzyOe3cYzpk9L5tjazuZDwxvQwuokapJzHhD7lT2rWXSC1j6PzI\\/2Ptszq7M4AEv7vGpqeyeiBNIgx9qhi2K8OiYFm+Is1pT8Gp5VxzJbBKOMQ6uYqLR0pJ7IgCOGG3loTytIpx9ROlnpR8oFpIhzawGZq3ExnAUCzZzWxAIb8H5cOcZ4l9tkrTZRLOp73jIfH9MjqZwTbbI7VBMicnGhRKbzgbwaL\\/FyEwy4cM8NRMc8D2XdfhTRacow7kaMwyZId0FmkKVF6PngTM736mTwgvz4pu5e3UJRVH6u+3nkxPcSBV5h3ujXOfvv3uxVkmC5vrdrWnmVYidxvu\\/9nj4BODno6SW8Djm516PBxccdQAd4AVeqZO3nb2Mcw3ACw\\/r9EdEnSRwjxpddn6zuga7ckI8zB3I7h8zzbJ8VUZm90VWyj\\/3e\\/ip4QT508Mh+zOBdtL3JvNvlN0l72LxpXwxdupqURQzPxvD7C9vso\\/xioGS90On5ncCDTu5Ud8cluP76woT8fLP6SVS8vcCLeCuyLj9YCMwb\\/h19QX6SdnZ0VZRZ8n51gkLsavqjorNusVB8gKNDWLvhgMCHF+Obvs0507ZHa\\/aEkk9Y\\/93ipvkwNOAl+A0dP5PDZ3R8Z9bJO7PrLxdfQn5SfjK\\/A74y\\/KR6gfV6vkv3ewitRDcVYNwFdTPM8tPcycvTRdYtBQ9W5XZHv8sQRsA\\/fEySH0DIr3ddFZeebzd0qzsmFw3ygyXyK3LwPhSVTnwLEJSl44ZJ10Ktvu2VPHXij2vSpWU04e3bDzloZRdHX2xC\\/pcz5Is8KxmsVp9Ax72nnuT0iipp6UdOz\\/N7RyfO8m4Btzu9NOu5WZ53lYNCpxdnRS9K9lle+gVUpd0F14PCp56gcLPec68D\\/meaedlz76H1LvmBE8tZ6pdOfjIyyLSLS4pS\\/\\/bIgCSr0vLh4h7uQ7+j6MO\\/3lOflZ+JY+jPxONeh9ytfogh+cc3HCf\\/U1y5V\\/CmClddaKkLB5TntKte7PTWTuF34aXRGg6Qu5igz9+IyXAaIXXjqoiO\\/o8hET+H1JbGh3nN\\/z9tjXzpQPNdH9L7XZ8YHDQYho7x8fC3fX7hgsncqu3I6xC4crl96wbEe19DFxSKYk84cZ0cH4pPU4bOPDhlPo+XTtZZMX7h5tG+Iwbbhcpiv21pyCPPeu8O\\/t2+DYrIy3qO6+9Lx3MuaPd7r0HdM\\/+I59MD\\/CPzW7K+sr9kKtpHUP671XgaYihJoMT4rxTjkwvk63IgX\\/90\\/vZfUEsBAgAAFAACAAgALU+uXAAAAAACAAAAAAAAAAYAAAAAAAAAAAAAAAAAAAAAAGR1bW15L1BLAQIAABQAAgAIAC1Prly8iDjUnQsAAHoXAAAcAAAAAAAAAAEAAAAAACYAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjMueG1sUEsFBgAAAAACAAIAfgAAAP0LAAAAAA==\"}','2026-05-14 09:57:26'),(28,23,'CONSULTA_SUNAT','','SUNAT no devolvio statusCode (HTTP 500)','','{\"ok\":false,\"mensaje\":\"SUNAT no devolvio statusCode (HTTP 500)\"}','2026-05-14 10:39:09'),(29,23,'CONSULTA_SUNAT','','SUNAT no devolvio statusCode (HTTP 500)','','{\"ok\":false,\"mensaje\":\"SUNAT no devolvio statusCode (HTTP 500)\"}','2026-05-14 10:39:18'),(30,23,'CONSULTA_SUNAT','0','La Boleta numero B001-23, ha sido aceptada','','{\"ok\":true,\"estado\":\"0004\",\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-23, ha sido aceptada\",\"observaciones\":[\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"3.00\\\")\"],\"cdr_zip\":\"UEsDBBQACAgIAC1PrlwAAAAAAAAAAAAAAAAcAAAAUi0xMDcyMTEwOTI5Ny0wMy1CMDAxLTIzLnhtbLVYaXOqWhb9\\/n6FlVvV1V3pXCZxSCd5dRgFAWVU\\/IaAgDIog6C\\/vg8Yjbk3t\\/q+V9WVsnLce5+11x7YnOPLn00S945+XkRZ+vqAfUcfen7qZl6UBq8PpsE9jR7+fPvjxcmfwX4fR65TQkPNL\\/ZZWvg9uDktXh+qPH3OnCIqnlMn8YvnYu+70ebd+Llax8+FG\\/qJ89wU3rOQHrPI9Z\\/wh8v2Zyf\\/iwhfMPlA85vyL8LRWZJkKduUftpmAX6FkH5aFh+g7tr9W6AUNHe\\/BHT+HiAIgtwPnNL\\/CtSDpQjLcv+MIHVdf6+J71keIDiKogg6RqCNV0TBt6t1kTn7m\\/3FUfEdqlp5t7FdIH569ONs7yM3J9D5bZvfFHHZGbfi4slJvacygrHcnFzjLKrUKX8Z597Pq\\/tg9db6q1ixK3Dzq1gxZClLegd1tYUofrP\\/gjRUVLGTP0Ft7hdt8YuHtxfYQc8mJd0aori2+Re6i+Sud1K4Kt9e9CiAEVT57RH5jbrAx6zd5ntCusne\\/uj1XmgnzVKYpzg6d7mS\\/TLMvB6IgyyPyjD5ZQowtIWFcblPLtZPvy2gddtAbQ4fkA77xvC3QdH+letTkuX+t7xwnorQITH8HVLzN34Op4ffMzWhTRcUQrGRO2mxyfKkuAjuRf\\/T7acUXZvReyqu7C+u\\/yLo7yQIAiI\\/Mn9hosAvyt\\/M2CfqMFHYDfgCYzlx5b9Foz5S8ipjEKN5v08QTeW50TCK3ZP7+oLcW7YpRm45ht2CfG6X+6JedtSJztr+EBLVfCrgY9nY5FtORIPyQJp5iIqP6kKU5P7IZAmHxucxz1nM\\/CA7W+UQU4ynVOxSY\\/pLgUxFhMJ1ezkGCBIsbDoKjOF5uSGdOiIQkfebiiyjSXS0ORkfRtTEE0\\/cpN6yATadoejYps2zQyU7oPt1X3k8nQTZs8Npn350B77LLZMydpFC9rEDyInQ2NowkAVapiH6iI6cMM19ar3xrdgO5\\/HBSLAlO0A4e7gXN8620hdmVmA7\\/WA452BBMlVeGC53fix1xy9xujhZWO4QzVBdl7NYZspqqh\\/YGcYFg3lUCXmNEitye1pUgRTXRl\\/dSGtO03JseYj6I5pWq8PMOY0f+ZKTaGJwpMHr6yXzd4l+mfqnrgwvSxIdM07pXFa0n5eXSee\\/yYLA5VuapkQnALVAgUAQNmPDG7LHADWLmlFtcZqthPDoKkBlJUoFtbZldzIt8AAzWVDXa4NvwvWCrVzeDEw0pOStiQucgtoLcmcvxZ3AKpSssTVT24ylqlMGhLLBcoqqk6pmsJIMdh0WFcq0yZr1JHQVeavWMwaQsmE3iiGTi07m3slAvYC8gaU20zPYU4FiUWAnCztuIcFSq5bI6GZDmego0FgzMFg2sHbWRNWpmbkbVQLDospZaIQtCKhgdwh3ET+uUYpW2QVLiOVquQ9XKHX2FmTo0WS6xoU2vkY1AHLxJcszTrFM1tJ1kzShz5lqypXAapzAcqxlcjPVkoOFTrGaTlEai4nGLjYsTqRUNJ4bEWWau5i2LI2C\\/2F+6poPuvxIDKVQGmrNLMhbtSzTQDHKYmNHNbW5rPdrCbzb0dbCXjT7Fc\\/t1ieqXhNCoJoiI7Axq0FOuqlRGk2xuumJFsvJAuQqW2ptBu91YEvDsigO+oe+rJkGP5AbJbDkheOu3fP34hFoNDAxiAfrwxhAuuTMlSm2EQ2TbegzEC8y2wAxBR0JFKyFcl8L2G8cADMaqCPQ6ulgCtcswJy9sxdntbwdl9Q+mIJgq9VCnJm7x6Y+qqvGRQukf4r7CaPnLsIh8+mKUKlkuu4TG66Ojoe5eSzywWijGpuTNJWxPvXYLOt4rSKnkHgcDQv5VMVSOJsnpT4z0Tk70mp0XiS81WwwCZ3Mwdwl+GMVHvqrMdOQ1IllBsWjtd8zSeoe3TIWESU\\/2yt6N56MsVmUGsbcWuVMuU4O6czOndHcMbSMFsZcknp+Np5lQ7+Qh1NrOd6QhMJ6SKKohzOY5YG+LEOuolbK8Dwr+Ebsr\\/jcOk\\/0mJ4gW04KZms2Ok\\/yQ1QEEuY20+1IPa55ydwqpVkZR40U7EFYaxLdrOPIcEZ9SsFQFt3SwzAEaVqxAuDEqBYYoAIq6wvUtn2u7Kxm2j7TUAOoE4QCQg0YsGlrNtFllmfAIqA0L+KqcoAvh0gSRaQSH+eWd8qqUZ4YVErBoxIVsBylurCW2lrmdrWi2sK0tilKNScymPLTRYh6EzCQTuOtm6rV+mOWHC9rrl4tP2aJjbOVjVsnmQY8TRc8UE2OOgMqDLXMm2j1LBod17hydmmyWPNcaetkvTKA3\\/HWWJY7AyvQlNN6YQ3shQdcwqrspVqt8HEkpVQss3Y7izwBqIjMoPWstpWOL+CZmHABzBEDDHCQ6eDDP5ybXNh0\\/t1zdpTwprAXWNjiSYQYu\\/z47Cy1o5vujjKFdviiqi5kSr3DkOm7fDEUAwYXziNWPoNGph8DyQF5GMW3OG1CLKTkzte7X5e3zh7PVXAunFa8EkoJjJcBXoen9lkOTolK283pk4yTAajIwh\\/zuUMj5gwdLkc1E3R1n1\\/qrjIgWAMZoDytH3hdWBOMylJ0bQIAewWoFD16tOVhkq2G66lN64\\/WWRzV55Onr7OFr1SgwOnNLhRGoSarOZaTi1Uw2jNxntFFzCH5QN+ud\\/m2YiYuPxUNGc8jnt3GM6ZPS+bY2s7mQ8Mb0MLqJGqScx4Q+5U9q1l0gtY+j8yP9j7bM6uzOABL+7xqansnogTSIMfaoYtivDomBZviLNaU\\/BqeVccyWwSjjEOrmKi0dKSeyIAjhht5aE8rSKcfUTpZ6UfKBaSIc2sBmatxMZwFAs2c1sQCG\\/B+XDnGeJfbZK02USzqe94yHx\\/TI6mcE22yO1QTInJxoUSm84G8Gi\\/xchMMuHDPDUTHPA9l3X4U0WnKMO5GjMMmSHdBZpClRej54EzO9+pk8IL8+KbuXt1CUVR+rvt55MT3EgVeYd7o1zn7797sVZJgub63a1p5lWIncb7v\\/Z4+ATg56OklvA45udejwcXHHUAHeAFXqmTt529jHMNwAsP6\\/RHRJ0kcI8aXXZ+s7oGu3JCPMwdyO4fM82yfFVGZvdFVso\\/93v4qeEE+dPDIfszgXbS9ybzb5TdJe9i8aV8MXbqalEUMz8bw+wvb7KP8YqBkvdDp+Z3Ag07uVHfHJbj++sKE\\/Hyz+klUvL3Ai3grsi4\\/WAjMG\\/4dfUF+knZ2dFWUWfJ+dYJC7Gr6o6KzbrFQfICjQ1i74YDAhxfjm77NOdO2R2v2hJJPWP\\/d4qb5MDTgJfgNHT+Tw2d0fGfWyTuz6y8XX0J+Un4yvwO+MvykeoH1er5L93sIrUQ3FWDcBXUzzPLT3MnL00XWLQUPVuV2R7\\/LEEbAP3xMkh9AyK93XRWXnm83dKs7JhcN8oMl8ity8D4UlU58CxCUpeOGSddCrb7tlTx14o9r0qVlNOHt2w85aGUXR19sQv6XM+SLPCsZrFafQMe9p57k9IoqaelHTs\\/ze0cnzvJuAbc7vTTruVmed5WDQqcXZ0UvSvZZXvoFVKXdBdeDwqeeoHCz3nOvA\\/5nmnnZc++h9S75gRPLWeqXTn4yMsi0i0uKUv\\/2yIAkq9Ly4eIe7kO\\/o+jDv95Tn5WfiWPoz8TjXofcrX6IIfnHNxwn\\/1NcuVfwpgpXXWipCweU57SrXuz01k7hd+Gl0RoOkLuYoM\\/fiMlwGiF146qIjv6PIRE\\/h9SWxod5zf8\\/bY186UDzXR\\/S+12fGBw0GIaO8fHwt31+4YLJ3KrtyOsQuHK5fesGxHtfQxcUimJPOHGdHB+KT1OGzjw4ZT6Pl07WWTF+4ebRviMG24XKYr9tacgjz3rvDv7dvg2KyMt6juvvS8dzLmj3e69B3TP\\/iOfTA\\/wj81uyvrK\\/ZCraR1D+u9V4GmIoSaDE+K8U45ML5OtyIF\\/\\/dP72X1BLBwi8iDjUnQsAAHoXAABQSwECFAAUAAgICAAtT65cvIg41J0LAAB6FwAAHAAAAAAAAAAAAAAAAAAAAAAAUi0xMDcyMTEwOTI5Ny0wMy1CMDAxLTIzLnhtbFBLBQYAAAAAAQABAEoAAADnCwAAAAA=\",\"con_observ\":false,\"rechazado\":false,\"no_existe\":false}','2026-05-14 10:43:18'),(31,23,'CONSULTA_SUNAT','0','La Boleta numero B001-23, ha sido aceptada','','{\"ok\":true,\"estado\":\"0004\",\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-23, ha sido aceptada\",\"observaciones\":[\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"3.00\\\")\"],\"cdr_zip\":\"UEsDBBQACAgIAC1PrlwAAAAAAAAAAAAAAAAcAAAAUi0xMDcyMTEwOTI5Ny0wMy1CMDAxLTIzLnhtbLVYaXOqWhb9\\/n6FlVvV1V3pXCZxSCd5dRgFAWVU\\/IaAgDIog6C\\/vg8Yjbk3t\\/q+V9WVsnLce5+11x7YnOPLn00S945+XkRZ+vqAfUcfen7qZl6UBq8PpsE9jR7+fPvjxcmfwX4fR65TQkPNL\\/ZZWvg9uDktXh+qPH3OnCIqnlMn8YvnYu+70ebd+Llax8+FG\\/qJ89wU3rOQHrPI9Z\\/wh8v2Zyf\\/iwhfMPlA85vyL8LRWZJkKduUftpmAX6FkH5aFh+g7tr9W6AUNHe\\/BHT+HiAIgtwPnNL\\/CtSDpQjLcv+MIHVdf6+J71keIDiKogg6RqCNV0TBt6t1kTn7m\\/3FUfEdqlp5t7FdIH569ONs7yM3J9D5bZvfFHHZGbfi4slJvacygrHcnFzjLKrUKX8Z597Pq\\/tg9db6q1ixK3Dzq1gxZClLegd1tYUofrP\\/gjRUVLGTP0Ft7hdt8YuHtxfYQc8mJd0aori2+Re6i+Sud1K4Kt9e9CiAEVT57RH5jbrAx6zd5ntCusne\\/uj1XmgnzVKYpzg6d7mS\\/TLMvB6IgyyPyjD5ZQowtIWFcblPLtZPvy2gddtAbQ4fkA77xvC3QdH+letTkuX+t7xwnorQITH8HVLzN34Op4ffMzWhTRcUQrGRO2mxyfKkuAjuRf\\/T7acUXZvReyqu7C+u\\/yLo7yQIAiI\\/Mn9hosAvyt\\/M2CfqMFHYDfgCYzlx5b9Foz5S8ipjEKN5v08QTeW50TCK3ZP7+oLcW7YpRm45ht2CfG6X+6JedtSJztr+EBLVfCrgY9nY5FtORIPyQJp5iIqP6kKU5P7IZAmHxucxz1nM\\/CA7W+UQU4ynVOxSY\\/pLgUxFhMJ1ezkGCBIsbDoKjOF5uSGdOiIQkfebiiyjSXS0ORkfRtTEE0\\/cpN6yATadoejYps2zQyU7oPt1X3k8nQTZs8Npn350B77LLZMydpFC9rEDyInQ2NowkAVapiH6iI6cMM19ar3xrdgO5\\/HBSLAlO0A4e7gXN8620hdmVmA7\\/WA452BBMlVeGC53fix1xy9xujhZWO4QzVBdl7NYZspqqh\\/YGcYFg3lUCXmNEitye1pUgRTXRl\\/dSGtO03JseYj6I5pWq8PMOY0f+ZKTaGJwpMHr6yXzd4l+mfqnrgwvSxIdM07pXFa0n5eXSee\\/yYLA5VuapkQnALVAgUAQNmPDG7LHADWLmlFtcZqthPDoKkBlJUoFtbZldzIt8AAzWVDXa4NvwvWCrVzeDEw0pOStiQucgtoLcmcvxZ3AKpSssTVT24ylqlMGhLLBcoqqk6pmsJIMdh0WFcq0yZr1JHQVeavWMwaQsmE3iiGTi07m3slAvYC8gaU20zPYU4FiUWAnCztuIcFSq5bI6GZDmego0FgzMFg2sHbWRNWpmbkbVQLDospZaIQtCKhgdwh3ET+uUYpW2QVLiOVquQ9XKHX2FmTo0WS6xoU2vkY1AHLxJcszTrFM1tJ1kzShz5lqypXAapzAcqxlcjPVkoOFTrGaTlEai4nGLjYsTqRUNJ4bEWWau5i2LI2C\\/2F+6poPuvxIDKVQGmrNLMhbtSzTQDHKYmNHNbW5rPdrCbzb0dbCXjT7Fc\\/t1ieqXhNCoJoiI7Axq0FOuqlRGk2xuumJFsvJAuQqW2ptBu91YEvDsigO+oe+rJkGP5AbJbDkheOu3fP34hFoNDAxiAfrwxhAuuTMlSm2EQ2TbegzEC8y2wAxBR0JFKyFcl8L2G8cADMaqCPQ6ulgCtcswJy9sxdntbwdl9Q+mIJgq9VCnJm7x6Y+qqvGRQukf4r7CaPnLsIh8+mKUKlkuu4TG66Ojoe5eSzywWijGpuTNJWxPvXYLOt4rSKnkHgcDQv5VMVSOJsnpT4z0Tk70mp0XiS81WwwCZ3Mwdwl+GMVHvqrMdOQ1IllBsWjtd8zSeoe3TIWESU\\/2yt6N56MsVmUGsbcWuVMuU4O6czOndHcMbSMFsZcknp+Np5lQ7+Qh1NrOd6QhMJ6SKKohzOY5YG+LEOuolbK8Dwr+Ebsr\\/jcOk\\/0mJ4gW04KZms2Ok\\/yQ1QEEuY20+1IPa55ydwqpVkZR40U7EFYaxLdrOPIcEZ9SsFQFt3SwzAEaVqxAuDEqBYYoAIq6wvUtn2u7Kxm2j7TUAOoE4QCQg0YsGlrNtFllmfAIqA0L+KqcoAvh0gSRaQSH+eWd8qqUZ4YVErBoxIVsBylurCW2lrmdrWi2sK0tilKNScymPLTRYh6EzCQTuOtm6rV+mOWHC9rrl4tP2aJjbOVjVsnmQY8TRc8UE2OOgMqDLXMm2j1LBod17hydmmyWPNcaetkvTKA3\\/HWWJY7AyvQlNN6YQ3shQdcwqrspVqt8HEkpVQss3Y7izwBqIjMoPWstpWOL+CZmHABzBEDDHCQ6eDDP5ybXNh0\\/t1zdpTwprAXWNjiSYQYu\\/z47Cy1o5vujjKFdviiqi5kSr3DkOm7fDEUAwYXziNWPoNGph8DyQF5GMW3OG1CLKTkzte7X5e3zh7PVXAunFa8EkoJjJcBXoen9lkOTolK283pk4yTAajIwh\\/zuUMj5gwdLkc1E3R1n1\\/qrjIgWAMZoDytH3hdWBOMylJ0bQIAewWoFD16tOVhkq2G66lN64\\/WWRzV55Onr7OFr1SgwOnNLhRGoSarOZaTi1Uw2jNxntFFzCH5QN+ud\\/m2YiYuPxUNGc8jnt3GM6ZPS+bY2s7mQ8Mb0MLqJGqScx4Q+5U9q1l0gtY+j8yP9j7bM6uzOABL+7xqansnogTSIMfaoYtivDomBZviLNaU\\/BqeVccyWwSjjEOrmKi0dKSeyIAjhht5aE8rSKcfUTpZ6UfKBaSIc2sBmatxMZwFAs2c1sQCG\\/B+XDnGeJfbZK02USzqe94yHx\\/TI6mcE22yO1QTInJxoUSm84G8Gi\\/xchMMuHDPDUTHPA9l3X4U0WnKMO5GjMMmSHdBZpClRej54EzO9+pk8IL8+KbuXt1CUVR+rvt55MT3EgVeYd7o1zn7797sVZJgub63a1p5lWIncb7v\\/Z4+ATg56OklvA45udejwcXHHUAHeAFXqmTt529jHMNwAsP6\\/RHRJ0kcI8aXXZ+s7oGu3JCPMwdyO4fM82yfFVGZvdFVso\\/93v4qeEE+dPDIfszgXbS9ybzb5TdJe9i8aV8MXbqalEUMz8bw+wvb7KP8YqBkvdDp+Z3Ag07uVHfHJbj++sKE\\/Hyz+klUvL3Ai3grsi4\\/WAjMG\\/4dfUF+knZ2dFWUWfJ+dYJC7Gr6o6KzbrFQfICjQ1i74YDAhxfjm77NOdO2R2v2hJJPWP\\/d4qb5MDTgJfgNHT+Tw2d0fGfWyTuz6y8XX0J+Un4yvwO+MvykeoH1er5L93sIrUQ3FWDcBXUzzPLT3MnL00XWLQUPVuV2R7\\/LEEbAP3xMkh9AyK93XRWXnm83dKs7JhcN8oMl8ity8D4UlU58CxCUpeOGSddCrb7tlTx14o9r0qVlNOHt2w85aGUXR19sQv6XM+SLPCsZrFafQMe9p57k9IoqaelHTs\\/ze0cnzvJuAbc7vTTruVmed5WDQqcXZ0UvSvZZXvoFVKXdBdeDwqeeoHCz3nOvA\\/5nmnnZc++h9S75gRPLWeqXTn4yMsi0i0uKUv\\/2yIAkq9Ly4eIe7kO\\/o+jDv95Tn5WfiWPoz8TjXofcrX6IIfnHNxwn\\/1NcuVfwpgpXXWipCweU57SrXuz01k7hd+Gl0RoOkLuYoM\\/fiMlwGiF146qIjv6PIRE\\/h9SWxod5zf8\\/bY186UDzXR\\/S+12fGBw0GIaO8fHwt31+4YLJ3KrtyOsQuHK5fesGxHtfQxcUimJPOHGdHB+KT1OGzjw4ZT6Pl07WWTF+4ebRviMG24XKYr9tacgjz3rvDv7dvg2KyMt6juvvS8dzLmj3e69B3TP\\/iOfTA\\/wj81uyvrK\\/ZCraR1D+u9V4GmIoSaDE+K8U45ML5OtyIF\\/\\/dP72X1BLBwi8iDjUnQsAAHoXAABQSwECFAAUAAgICAAtT65cvIg41J0LAAB6FwAAHAAAAAAAAAAAAAAAAAAAAAAAUi0xMDcyMTEwOTI5Ny0wMy1CMDAxLTIzLnhtbFBLBQYAAAAAAQABAEoAAADnCwAAAAA=\",\"con_observ\":false,\"rechazado\":false,\"no_existe\":false}','2026-05-14 10:43:24'),(32,23,'CONSULTA_SUNAT','0','La Boleta numero B001-23, ha sido aceptada','','{\"ok\":true,\"estado\":\"0004\",\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-23, ha sido aceptada\",\"observaciones\":[\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"3.00\\\")\"],\"cdr_zip\":\"UEsDBBQACAgIAC1PrlwAAAAAAAAAAAAAAAAcAAAAUi0xMDcyMTEwOTI5Ny0wMy1CMDAxLTIzLnhtbLVYaXOqWhb9\\/n6FlVvV1V3pXCZxSCd5dRgFAWVU\\/IaAgDIog6C\\/vg8Yjbk3t\\/q+V9WVsnLce5+11x7YnOPLn00S945+XkRZ+vqAfUcfen7qZl6UBq8PpsE9jR7+fPvjxcmfwX4fR65TQkPNL\\/ZZWvg9uDktXh+qPH3OnCIqnlMn8YvnYu+70ebd+Llax8+FG\\/qJ89wU3rOQHrPI9Z\\/wh8v2Zyf\\/iwhfMPlA85vyL8LRWZJkKduUftpmAX6FkH5aFh+g7tr9W6AUNHe\\/BHT+HiAIgtwPnNL\\/CtSDpQjLcv+MIHVdf6+J71keIDiKogg6RqCNV0TBt6t1kTn7m\\/3FUfEdqlp5t7FdIH569ONs7yM3J9D5bZvfFHHZGbfi4slJvacygrHcnFzjLKrUKX8Z597Pq\\/tg9db6q1ixK3Dzq1gxZClLegd1tYUofrP\\/gjRUVLGTP0Ft7hdt8YuHtxfYQc8mJd0aori2+Re6i+Sud1K4Kt9e9CiAEVT57RH5jbrAx6zd5ntCusne\\/uj1XmgnzVKYpzg6d7mS\\/TLMvB6IgyyPyjD5ZQowtIWFcblPLtZPvy2gddtAbQ4fkA77xvC3QdH+letTkuX+t7xwnorQITH8HVLzN34Op4ffMzWhTRcUQrGRO2mxyfKkuAjuRf\\/T7acUXZvReyqu7C+u\\/yLo7yQIAiI\\/Mn9hosAvyt\\/M2CfqMFHYDfgCYzlx5b9Foz5S8ipjEKN5v08QTeW50TCK3ZP7+oLcW7YpRm45ht2CfG6X+6JedtSJztr+EBLVfCrgY9nY5FtORIPyQJp5iIqP6kKU5P7IZAmHxucxz1nM\\/CA7W+UQU4ynVOxSY\\/pLgUxFhMJ1ezkGCBIsbDoKjOF5uSGdOiIQkfebiiyjSXS0ORkfRtTEE0\\/cpN6yATadoejYps2zQyU7oPt1X3k8nQTZs8Npn350B77LLZMydpFC9rEDyInQ2NowkAVapiH6iI6cMM19ar3xrdgO5\\/HBSLAlO0A4e7gXN8620hdmVmA7\\/WA452BBMlVeGC53fix1xy9xujhZWO4QzVBdl7NYZspqqh\\/YGcYFg3lUCXmNEitye1pUgRTXRl\\/dSGtO03JseYj6I5pWq8PMOY0f+ZKTaGJwpMHr6yXzd4l+mfqnrgwvSxIdM07pXFa0n5eXSee\\/yYLA5VuapkQnALVAgUAQNmPDG7LHADWLmlFtcZqthPDoKkBlJUoFtbZldzIt8AAzWVDXa4NvwvWCrVzeDEw0pOStiQucgtoLcmcvxZ3AKpSssTVT24ylqlMGhLLBcoqqk6pmsJIMdh0WFcq0yZr1JHQVeavWMwaQsmE3iiGTi07m3slAvYC8gaU20zPYU4FiUWAnCztuIcFSq5bI6GZDmego0FgzMFg2sHbWRNWpmbkbVQLDospZaIQtCKhgdwh3ET+uUYpW2QVLiOVquQ9XKHX2FmTo0WS6xoU2vkY1AHLxJcszTrFM1tJ1kzShz5lqypXAapzAcqxlcjPVkoOFTrGaTlEai4nGLjYsTqRUNJ4bEWWau5i2LI2C\\/2F+6poPuvxIDKVQGmrNLMhbtSzTQDHKYmNHNbW5rPdrCbzb0dbCXjT7Fc\\/t1ieqXhNCoJoiI7Axq0FOuqlRGk2xuumJFsvJAuQqW2ptBu91YEvDsigO+oe+rJkGP5AbJbDkheOu3fP34hFoNDAxiAfrwxhAuuTMlSm2EQ2TbegzEC8y2wAxBR0JFKyFcl8L2G8cADMaqCPQ6ulgCtcswJy9sxdntbwdl9Q+mIJgq9VCnJm7x6Y+qqvGRQukf4r7CaPnLsIh8+mKUKlkuu4TG66Ojoe5eSzywWijGpuTNJWxPvXYLOt4rSKnkHgcDQv5VMVSOJsnpT4z0Tk70mp0XiS81WwwCZ3Mwdwl+GMVHvqrMdOQ1IllBsWjtd8zSeoe3TIWESU\\/2yt6N56MsVmUGsbcWuVMuU4O6czOndHcMbSMFsZcknp+Np5lQ7+Qh1NrOd6QhMJ6SKKohzOY5YG+LEOuolbK8Dwr+Ebsr\\/jcOk\\/0mJ4gW04KZms2Ok\\/yQ1QEEuY20+1IPa55ydwqpVkZR40U7EFYaxLdrOPIcEZ9SsFQFt3SwzAEaVqxAuDEqBYYoAIq6wvUtn2u7Kxm2j7TUAOoE4QCQg0YsGlrNtFllmfAIqA0L+KqcoAvh0gSRaQSH+eWd8qqUZ4YVErBoxIVsBylurCW2lrmdrWi2sK0tilKNScymPLTRYh6EzCQTuOtm6rV+mOWHC9rrl4tP2aJjbOVjVsnmQY8TRc8UE2OOgMqDLXMm2j1LBod17hydmmyWPNcaetkvTKA3\\/HWWJY7AyvQlNN6YQ3shQdcwqrspVqt8HEkpVQss3Y7izwBqIjMoPWstpWOL+CZmHABzBEDDHCQ6eDDP5ybXNh0\\/t1zdpTwprAXWNjiSYQYu\\/z47Cy1o5vujjKFdviiqi5kSr3DkOm7fDEUAwYXziNWPoNGph8DyQF5GMW3OG1CLKTkzte7X5e3zh7PVXAunFa8EkoJjJcBXoen9lkOTolK283pk4yTAajIwh\\/zuUMj5gwdLkc1E3R1n1\\/qrjIgWAMZoDytH3hdWBOMylJ0bQIAewWoFD16tOVhkq2G66lN64\\/WWRzV55Onr7OFr1SgwOnNLhRGoSarOZaTi1Uw2jNxntFFzCH5QN+ud\\/m2YiYuPxUNGc8jnt3GM6ZPS+bY2s7mQ8Mb0MLqJGqScx4Q+5U9q1l0gtY+j8yP9j7bM6uzOABL+7xqansnogTSIMfaoYtivDomBZviLNaU\\/BqeVccyWwSjjEOrmKi0dKSeyIAjhht5aE8rSKcfUTpZ6UfKBaSIc2sBmatxMZwFAs2c1sQCG\\/B+XDnGeJfbZK02USzqe94yHx\\/TI6mcE22yO1QTInJxoUSm84G8Gi\\/xchMMuHDPDUTHPA9l3X4U0WnKMO5GjMMmSHdBZpClRej54EzO9+pk8IL8+KbuXt1CUVR+rvt55MT3EgVeYd7o1zn7797sVZJgub63a1p5lWIncb7v\\/Z4+ATg56OklvA45udejwcXHHUAHeAFXqmTt529jHMNwAsP6\\/RHRJ0kcI8aXXZ+s7oGu3JCPMwdyO4fM82yfFVGZvdFVso\\/93v4qeEE+dPDIfszgXbS9ybzb5TdJe9i8aV8MXbqalEUMz8bw+wvb7KP8YqBkvdDp+Z3Ag07uVHfHJbj++sKE\\/Hyz+klUvL3Ai3grsi4\\/WAjMG\\/4dfUF+knZ2dFWUWfJ+dYJC7Gr6o6KzbrFQfICjQ1i74YDAhxfjm77NOdO2R2v2hJJPWP\\/d4qb5MDTgJfgNHT+Tw2d0fGfWyTuz6y8XX0J+Un4yvwO+MvykeoH1er5L93sIrUQ3FWDcBXUzzPLT3MnL00XWLQUPVuV2R7\\/LEEbAP3xMkh9AyK93XRWXnm83dKs7JhcN8oMl8ity8D4UlU58CxCUpeOGSddCrb7tlTx14o9r0qVlNOHt2w85aGUXR19sQv6XM+SLPCsZrFafQMe9p57k9IoqaelHTs\\/ze0cnzvJuAbc7vTTruVmed5WDQqcXZ0UvSvZZXvoFVKXdBdeDwqeeoHCz3nOvA\\/5nmnnZc++h9S75gRPLWeqXTn4yMsi0i0uKUv\\/2yIAkq9Ly4eIe7kO\\/o+jDv95Tn5WfiWPoz8TjXofcrX6IIfnHNxwn\\/1NcuVfwpgpXXWipCweU57SrXuz01k7hd+Gl0RoOkLuYoM\\/fiMlwGiF146qIjv6PIRE\\/h9SWxod5zf8\\/bY186UDzXR\\/S+12fGBw0GIaO8fHwt31+4YLJ3KrtyOsQuHK5fesGxHtfQxcUimJPOHGdHB+KT1OGzjw4ZT6Pl07WWTF+4ebRviMG24XKYr9tacgjz3rvDv7dvg2KyMt6juvvS8dzLmj3e69B3TP\\/iOfTA\\/wj81uyvrK\\/ZCraR1D+u9V4GmIoSaDE+K8U45ML5OtyIF\\/\\/dP72X1BLBwi8iDjUnQsAAHoXAABQSwECFAAUAAgICAAtT65cvIg41J0LAAB6FwAAHAAAAAAAAAAAAAAAAAAAAAAAUi0xMDcyMTEwOTI5Ny0wMy1CMDAxLTIzLnhtbFBLBQYAAAAAAQABAEoAAADnCwAAAAA=\",\"con_observ\":false,\"rechazado\":false,\"no_existe\":false}','2026-05-14 10:44:35'),(33,22,'ENVIO_SUNAT','0','La Boleta numero B001-22, ha sido aceptada','','{\"ok\":true,\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-22, ha sido aceptada\",\"observaciones\":[\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"5.00\\\")\"],\"cdr_zip\":\"UEsDBBQAAgAIAPVzrlwAAAAAAgAAAAAAAAAGAAAAZHVtbXkvAwBQSwMEFAACAAgA9XOuXHsF\\/VyhCwAAehcAABwAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjIueG1stVhpc6rMtv5+foWVXXXr3srJZhKnk+RUMykKKKPiNwQEZAyDoL\\/+NhiN2Tu7Tt636lTKSrvW088aWXT7\\/O8mjnpHNy+CNHl5wH6iDz03sVMnSLyXB13jnkYP\\/379x7OVT0CWRYFtlRCouEWWJoXbg5uT4uWhypNJahVBMUms2C0mRebawf4dPKl20aSwfTe2Jk3hTPjkmAa2+4Q\\/XLZPrPwvMnzhyQeb25R\\/kY5O4zhN2KZ0kzYL8CukdJOy+CC1d\\/bfIqUg3P6S0Pp7hMDzctezSvcrUgeWwi\\/LbIIgdV3\\/rImfae4hOIqiCDpGIMYpAu\\/HFV2kVnbDXwwVP6GqlXcb2wXiJkc3SjMXuRmBxm\\/b3KaIyg7ciosnK3GeygDGcjNyjbOoEqv8Y5yZm1f3waot+qtYsStx86dYMWQjCmpHdcVCFrfJvnAaKqrIyp+gNneLtvjFw+sz7KCJTgm3hiiubf6F7iK5650ErsrXZzXwYARVfntEvlEX+Ji121yHT\\/bp6z96vWfaStIE5ikKzl2uRLf0U6cHIi\\/Ng9KP\\/5gCDG1pYVz2k431kx9riG4bqM3hA9Jx3zz8Ninav\\/r6FKe5+yMvrKfCt0gMf6dU3L2bw+nh9nSFb9MFhVCs5VZS7NM8Li6Ce9F\\/NPspRddmdJ6Kq\\/cX03+R9DsJgoTIr54\\/M4HnFuU3M\\/bJdZgo7EZ8oTGsqHJf3YFFlTs82ObqDI\\/nWsYpiDKXlinaf3lG7pFtipFbjmG3IJ\\/b5b6olx2iZu6HpU+yGxIr6Znhpsgw2MUJibjTef+An2tLDoaPWn6khBRdISEeWK6XrYodYi7LcL5abUNRjRQ5lljTYYeuP1Yz8RHWnBDHJFegR0YJ3sqZP58RewKEa8k9HdT6zUoydRxbWyskVgNhU9gHPNFn9kjMAtIvlo9VsE2BaBxl2XasmsaMvvF2Zs3lVsw11pEikIiEP8tA6duH067hVCYRGiHEwSOxk970GO8b++leCUnnXA3fAtqLDH3PVEfDkOhaNICflKHjslQobSXVIYmKeOyjWwkzHoe7RyW29H0mMtn29BYOJUHied+XS5Pe7GjfWutpMSXHVgiCxqvnNZIfxHXpLlXkDRgMYm7G4OXlkvm7RD8v3FNXhucNiY4Zq7QuK9rNy8ukg8XgeS4\\/0DQ1tzxQ8xTweH4\\/1pwhe\\/RQvagZ2Zwv0i3vH20JyKxAyaBWDmwo0vwUYDoL6nqnTRt\\/t2Yre6p7OupT4kHHeU5CzTUZmpt5yLMSJSpszdQmY8jyggG+qLGcJKukrGisIIKw46J8kdZZvZ75tiQe5HrJABI2SyNpIrnuZPadDNRr6Dcw5GZxBhnlSQYFQpEPubUQUDPZmDOq3lA6OvIUVvc0lvWM0JjJKrXUw1HFMywqnfmGPwCP8sI3Pwym4xqlaJlds8S83G4yf4tSZ2dN+g5NJjucb+NrZA0gF1uiuOQkQ2cNVdVJHdpcyrpY8azC8SzHGjq3lA3RW6sUq6gUpbDYXAsjzeDmlIxGKy2gdD2MaMNQKPgf5qeup16XH4GhJEpBjaUB\\/ZYNQ9dQjDLYyJJ1ZSWq\\/VoA7zjaWJvrJttOuXB3ouodwXuyPmd4NmIV6JOqK5RCU6yqO3OD5UQe+ioacq1773VgS80wKA7ah7aMpQI\\/0DeKZ8mLj2G75+\\/Fw9Oop2OQD9aH0YBwyZktUmwz13S2oc9gfpGZGogoaIinYC2k+1rAfuMAWNJAHoFWT3sLuGYBZmVWNl\\/W4mFcUpm3AN5Bqfko1cPHpj7K28ZGC6R\\/ivoxo+Y2wiGrxZaQqXix6xN7rg6Obyv9WOSD0V7W9idhIWJ96rHZ1NFORk4+8TgaFuKpigR\\/uYpLdamjK3ak1OiqiKdGs8cEdLYCK5uYHiv\\/rb8dMw1JnVhmUDwaWcbEiX20y2iOSPnZ3NLheDbGlkGiaStjmzPlLn5LlmZujVaWpqQ0P+bixHHT8TIduoU4XBib8Z4kJNZBYkl+O4Nl7qmb0ucqaisNz8ti2sz722lunGdqRM+QAyd4yx0bnGf5W1B4AmY3i8NIPu6mgn6QSr3SjgrJmwO\\/VgS62UWBZo36lIShLHqgh74PkqRiecDNg5pngAyotM9Th\\/a5MtOaaftMQTUgzxAK8DVgwL6t2UwV2SkD1h6lOAFXlQN8M0TiICCl6LgynFNajfJYoxIKHpUoj+Uo2Ya1VHYiF9aSbPKL2qQoWZ+JYDFdrH3UmYGBcBof7ESudh+z5HhZc\\/V28zFLTJytTNw4iTSY0nQxBbLOUWdA+b6SOjOlXgaj4w6XzjZNFrspV5oqWW814HZ+KyzLnYHhKdJptzYG5toBNmFU5kautvg4EBIqElmznUUOD2REZNB6WZtS5y+YMhFhA5gjBmjgTaS9D\\/twbnJ+09m3z+lRwJvCXGN+yycQ88iejs\\/WRjnaSXgUKbTjn8vyWqTkOw6RvssXQzFgcPF5xIpn0Ij0oydYIPeD6BanScwLIb6z9W7XnhpnZ8pVcC6ctlPJF2IYLwOcjk\\/usxycEpUSruiTiJMeqMjCHU9zi0b0JTrcjGrG6+q+utRdZoC3AyJAp7T6NlX5HcHILEXXOgCwV4BM0aNHUxzG6Xa4W5i0+mic56P6fHLUXbp2pQoUOL0PfX7kK6KcYzm53nqjjInylC4iDskH6mEX5oeKmdnTxVwT8TyYsodoyfRpQR8bh+VqqDkDmt+e5opgnQdEtjWXNYvO0NqdIqujmaUZsz3PB2BjnrdNbYZzlEAa5FhbdFGMt8e4YBOcxZpyuoNn1bHIFt4o5dAqIiolGckn0uOI4V4cmosKutMPKJWs1CNlA3KOczseWclRMVx6PM2cdsQaG0zdqLK0cZibZC03QTRXs6mhPz4mR1I6x8osfKtmRGDjfIksVgNxO97g5d4bcH7GDeaWfh6Kqvk4RxcJw9j7eeQ3XhJ6qUaWBqHmgzO5yuTZ4Bn59U3dvbr5oqjcXHXzwIruJRK8wrzSLyv2n73liyDAcv1s17T0IkRWbP3M3J46Azg56KklvA5ZudOjwcXGHUFHeCGXqnjn5q9jHMNwAsP6\\/RHRJ0kcI8aXXZ9Q90RX35CPMwdyO4es8jRLi6BMX+kqziK3l10Fz8iHDh7Zjym8i7Y3mXdcfpO0h82b9llThSukLCJ4Nobfn9kmC\\/ILQEp7vtVzO4EDjdyp7o5LcP31hQn5\\/Wb1m6h4fYYX8VZkXH6w4JlX\\/Cf6jPwm7XB0VZRp\\/H51gkLsCv1V0aFbLhQf4OgQH+EYrAZxAd\\/0bc6Ztj1a2BNKPmH9d8RN8wHU4CX4FR1PSGJCDO5gnbyDXX+5+JLyk\\/ITvCPA+hMCm\\/Txz+B3bsue3KX7PYRWouoS0O6CugHT\\/LSy8vJ0kXVL3oFVud3R7zKEEfAPH5PkBxHy511XxaXn2w3d6s6Tiwb5BYn8yTl4HwpKK7oFCMrSsv24a6FW3\\/ZKnljRxzXp0jIK\\/\\/rjlxy0souhLzYh\\/8kY8kWepRRWq0+g495TT7B6RRW37gdWz3F7RytK824Bt1u9JO3ZaZ53lYNCqxelRS+IszQv3QKqku6C60DhU4+XuGVv0uuI\\/zdJnXTSe2itC65nRWKauKWVn7QUetrFJQSJe3tkQJxWSflwMQ\\/3oT9R9OH\\/3lOflp8dx9DfHY96HXO3+iWG+H9+4Dj5r+LqewVvqnDVhZbYcEA5VrvqRVZvZxVuF14S7OAAuYsJ2vxGTJrV8IkdVUVwdH8Nifw9pLY0Lsxr\\/t9pa+RLA4pru9C979rE4KDBMHSMj4fftvmFCSa1q7Yjr0Pg6svtWzcg3vsamqBQFHvCb5PjQ\\/FpytCpA8fX5\\/HSyToU4xZ2HmSdY7BdqDRy25aGfuRp793AP9u3QRE4ac+y3ay0HOvCdr\\/3GtS95x\\/xfHqAf\\/X8lqyv8JdMBVkA5d+txtMQQ0kCbV+63y\\/GJxPI1+VAvv7p\\/PX\\/AVBLAQIAABQAAgAIAPVzrlwAAAAAAgAAAAAAAAAGAAAAAAAAAAAAAAAAAAAAAABkdW1teS9QSwECAAAUAAIACAD1c65cewX9XKELAAB6FwAAHAAAAAAAAAABAAAAAAAmAAAAUi0xMDcyMTEwOTI5Ny0wMy1CMDAxLTIyLnhtbFBLBQYAAAAAAgACAH4AAAABDAAAAAA=\"}','2026-05-14 14:31:41'),(34,24,'ENVIO_SUNAT','0','La Boleta numero B001-24, ha sido aceptada','','{\"ok\":true,\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-24, ha sido aceptada\",\"observaciones\":[\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"5.00\\\")\"],\"cdr_zip\":\"UEsDBBQAAgAIAAalrlwAAAAAAgAAAAAAAAAGAAAAZHVtbXkvAwBQSwMEFAACAAgABqWuXJR1BqulCwAAehcAABwAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjQueG1stVhZc6LaFn4\\/v8JKV926t3LSDIrTTXJqM4oCyqj4hoCAMskg6K+\\/G4zGpNN1uk\\/VfYjZrrX2t741sNjb57\\/qKOwc3SwPkvjlAfuOPnTc2E6cIPZeHnSNfRo+\\/PX6x7OVjUGahoFtFdBQcfM0iXO3AzfH+ctDmcXjxMqDfBxbkZuP89S1g+2b8bjchOPc9t3IGte5M+bjYxLY7hP+cNk+trLfRPiCyTuaWxe\\/CUclUZTETF24cZMF+BVCunGRv4PaG\\/sfgZLQ3P4S0PpngMDzMtezCvcrUAeWwi+KdIwgVVV9r7rfk8xDcBRFEXSEQBsnD7xvV+s8sdKb\\/cVR\\/h2qGnm7sVkgbnx0wyR1kZsT6Py2za3zsGiNG3H+ZMXOUxHAWG5OrnHmZWwVP40zdbPyPli1sf4qVuwKXP8sVgxZiYLaQl1tIYpbp1+QhooytLInqM3cvCl+\\/vD6DDtorJPCrSHya5t\\/obtI7nonhqvi9VkNPBhBmd0ekV+oC3zMmm2uw8fb5PWPTueZsuIkhnkKg3ObK9Et\\/MTpgNBLsqDwo5+mAEMbWBiX\\/WRjvfjbElo3DdTk8AFpsW8MfxkU7V25PkVJ5n7Lcusp9y0Cw98gFXfrZnB6uB1d4Zt0QSEUa5kV59ski\\/KL4F70t24\\/pOjajM5TfmV\\/cf2boL+SIAiIfGb+TAeemxe\\/mLEP1GGisBvwBcawwtJ9tSrbne\\/x0ypdiSrKdVNqZmASV5t87+UZubdsUozccgy7BfnYLvdFvezIDetIZXjPZJYp6cNP3hxyuTNNN70ln0j4Nq4n+Paw3q6ceW9mLUYr9ChZmrKiS8SRp4MJJekDtc9ZZnLe9wdYNKSiiTbdPR5A6JQhY57PFVcv15Z5iBmNLZj+ZMXOjjquh\\/ZKmS3klV0ymMRKo4O0WDCRUYysoSmu7QWjiatZbFYTsZifuMEiNCVK2W774Rrxg1F3IgyFZFU\\/xlRPNpZHDzMZ53Hp+9GQHyWFViJ013DWGbai62lgnIbafudNq2BXbNb903pfsXlkj7yDUW8OUhK4xvlxZx0ZiQ8nR2xrJwjY9U+PAtifhUTaq3HFksX50D1syHCoaGtVJgQx4c\\/BWY\\/1fMGW1JEifL\\/QZkhALuWXl0vm7xL9PHNPbRmeVwQ6oq3CuqwoNysuk859FXmezXYURU4tD1Q8CTye3440Z8AcPVTPK1o2p7NkzftHWwIyI5AyqJQdsxcpngOYzoCq2mhc7W+WTGlzuqejPinudJxnJdRcEntzNd3zjESKClPRlUkbsjyjgS9qDCvJKiErGiOIYN9ikb5I6YxeTXxbEndyNacBIWpmLWkisWxl9p0MVEvIGxhyPTuDlPQkgwR7kd+zSyEgJ7IxpVW9JnV06CmM7mkM4xl7YyKr5FzfD0ueZlDpzNf8Dniktz\\/4+4AbVShJycyS6U6L9Sr11yh5dpaE71BEvMH5Jr5a1gBy8SWKc1YydMZQVZ3Qoc+5rIslzygsz7CMobNz2RC9pUoyikqSCoNNtX2oGeyUlNFwoQWkru9DyjAUEv6H+akqzmvzI9CkRCqoMTcgb9kwdA3FSIMJLVlXFqLaqwTwZkcZS3NZp2uO3W9OZLXp8p6sT2meCRkFclJ1hVQoklF1Z2owrMhDrqIhV7r3Vgem0AyDZKF\\/6MuYK\\/APciN5hrhw3Dd7\\/lk8PIV6OgbxYH1oDQiXnNkiydRTTWdq6gymF5mpgZCEjngS1kK6rwXsNxaAOQXkIWj0lDeDawZgVmql03kl7kYFmXoz4O2Uig8Tff9YV0d5XdtojvROYS+i1cxGWGQxW3dlMpptet0tWwXHw0I\\/5ll\\/uJW17UmYiViPfKxXVbiRkZPffRwOcvFUhoI\\/X0SFOtfRBTNUKnSRR5xRbzEBnSzAwu5yx9I\\/9NYjuibIE0P380cjTekoto92EU4RKTuba2o\\/moyweRBr2sJYZ3SxiQ7x3Mys4QJOtITiR2wUO24ymicDNxcHM2M12hJdiXGQSJIPZzDPPHVV+GxJrqXBeZ5z9bS35jLjPFFDaoLsWMGbb5jgPMkOQe4JmF3PdkP5uOEEfScVeqkdFYI3+36lCFS9CQPNGvZICUMZdEcNfB\\/EccnwgJ0GFU8DGZBJjyd3zXNlJhXd9JmCakCeICTgK0CDbVOziSoyHA2WHqk4AVsWfXw1QKIgIKTwuDCcU1IOs0gjYxIelUiPYUnZhrVUNiK7ryTZ5GeVSZKyPhHBjJstfdSZgL5wGu3sWC4377PkeFmz1Xr1PktMnClN3DiJFOAoKueArLPkGZC+ryTORKnmwfC4waWzTRH5hmMLUyWqtQbclrfCMOwZGJ4inTZLo28uHWB3jdJcyeUaHwVCTIYiYzazyOGBjIg0Ws0rU2r5Ao4OuzaAOaKBBg4i5b37h3OT9evWv31OjgJe5+YS8xs8oTsNbW50tlbK0Y73R5FEW\\/ypLC9FUr7DEKm7fNEkDfoXzkNGPINapB49wQKZH4S3OM3uNBeiO19vfm3OODscW8K5cFpzki9EMF4aOC2e3GNYOCVKZb+gTiJOeKAkcnfEZRaF6HN0sBpWtNfWfXGpu0wDbwNEgHKUeuBUftOlZYakKh0A2CtAJqnhoykOomQ92MxMSn00ztNhdT456iZZulIJcpza7n1+6CuinGEZsVx7w5QOs4TKQxbJ+upus892JT2xudlUE\\/Es4JhdOKd7lKCPjN18MdCcPsWvT1NFsM79bro25xWDTtDK5ZDF0UyTlF6fp32wMs\\/rujL3U7SL1Mixsqg8H62PUc7EOIPVBbeBZ9WRyOTeMGHRMuyWSjyUT4THdgdbcWDOSkinF5AqUapH0gbEFGc3PLKQw3ww93iKPm26S6zPuWFpaaN9ZhKVXAfhVE05Q398jI+EdI6Uyf5QTrqBjfMFMlv0xfVohRdbr8\\/6KdufWvp5IKrm4xSdxTRtb6ehX3vx3ks0ojC6atY\\/E4tUnvSfkc9v6vbVzed56WaqmwVWeC+R4BXmlXpZMH925i+CAMv1vVlT0osQWpH1PXU76gTgRL+jFvA6ZGVOhwIXH3cALeAFXCqjjZu9jnAMw7sY1usNuz2CwLHu6LLrg9U90JUb8n7mQG7nkEWWpEkeFMkrVUZp6HbSq+AZedfBI\\/sxgXfR5ibzZpfdJM1h86Z91lThalLkITwbw+\\/PTJ0G2cVASjq+1XFbgQOd3Knujktw\\/fWFCfnxZvWDKH99hhfxRmRcfrDg6Vf8O\\/qM\\/CBt7agyL5Lo7eoEhdjV9LOitW6wULyPo4Mu1iUGRP\\/N+KZvck437dGYPaHEE9Z7s7hp3g01eAl+xXpjoj\\/GR3dmrbw1u\\/5y8SXkB+UH8xYAR8c9dIzhH43fsC17fJfutxAaiapLQLsL6maYZKeFlRWni6xd8g6syu2OfpchDGYHw0cE8Q6E\\/HzXVXHp+WZDu7pjctEgnyyRn5GD96GgsMJbgKAoLNuP2hZq9E2vZLEVvl+TLi2j8K\\/fPuWgkV0cfbEJ+TtnyBd5lhJYrV4XHXWeOoLVycuooR9YHcftHK0wydoF3G514qRjJ1nWVg4KrU6Y5J0gSpOscHOoitsLrgOFTx1eYuedcacF\\/necOMm489B4F1zPCsUkdgsrO2kJZNrGJQSxe3tkQJSUcfFwcQ\\/3od9R9OE\\/b6lPio\\/EMfRH4mGnRW5Xn2KI\\/vUNx4n\\/5lfuJbypwlUbWmzDAeVYzaoTWp2NlbtteHGwgQPkLibo8xdi0qyaj+2wzIOj+zkk4seQmtK4MK\\/Z\\/6etkS8dKK7tQnq\\/6hNDB3DioyN8NPhln1+4oBO7bDryOgSuXG7f2gHx1tfQBYmi2BN+GzPvig9Thkoc9xX9OF5aWWtFu7mdBWlLDLYLmYRu09KQR5Z03hz82bwN8sBJOpbtpoXlWBe0+73XoO6Zv8fz4QH+zPyWrK\\/sL5kK0gDKf7UaTwO0NyR6vf7vFOODC+TrciBf\\/3T++j9QSwECAAAUAAIACAAGpa5cAAAAAAIAAAAAAAAABgAAAAAAAAAAAAAAAAAAAAAAZHVtbXkvUEsBAgAAFAACAAgABqWuXJR1BqulCwAAehcAABwAAAAAAAAAAQAAAAAAJgAAAFItMTA3MjExMDkyOTctMDMtQjAwMS0yNC54bWxQSwUGAAAAAAIAAgB+AAAABQwAAAAA\"}','2026-05-14 20:40:11'),(35,20,'ENVIO_SUNAT','1033','El comprobante fue registrado previamente con otros datos - Detalle: xxx.xxx.xxx value=\'ticket: 202620735442762 error: El comprobante B001-19 fue informado anteriormente\'','','{\"ok\":false,\"codigo\":\"1033\",\"mensaje\":\"El comprobante fue registrado previamente con otros datos - Detalle: xxx.xxx.xxx value=\'ticket: 202620735442762 error: El comprobante B001-19 fue informado anteriormente\'\"}','2026-05-15 10:17:45'),(36,25,'ENVIO_SUNAT','0','La Boleta numero B001-25, ha sido aceptada','','{\"ok\":true,\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-25, ha sido aceptada\",\"observaciones\":[\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"5.00\\\")\"],\"cdr_zip\":\"UEsDBBQAAgAIAEVSr1wAAAAAAgAAAAAAAAAGAAAAZHVtbXkvAwBQSwMEFAACAAgARVKvXDTxrO+kCwAAehcAABwAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjUueG1stVhpk6JMtv4+v8Kojrhxb9RUsyhuU10TySoIKKviNwQElE0WQX\\/9TbC0rO7qmH7fiIkKo9JzTj7nOQuHTF\\/\\/3cRR7+TlRZgmP56w7+hTz0uc1A0T\\/8eTobMv46d\\/v\\/3j1c6nIMui0LFLaKh6RZYmhdeDm5Pix1OVJ9PULsJimtixV0yLzHPC3bvxtNpG08IJvNieNoU75ZNTGjreC\\/503T6187+I8AWTDzSvKf8iHJXGcZowTeklbRbgVwjpJWXxAepsnb8FSkJz50tA++8BAt\\/PPd8uva9AXViKoCyzKYLUdf297n9Pcx\\/BURRF0AkCbdwi9L\\/drIvUzu72V0fFd6hq5d3GdoF4ycmL0sxD7k6g8\\/s2rymisjNuxcWLnbgvZQhjuTu5xVlUiV3+Ns7My6vHYLXW+qtYsRtw87tYMWQtiVoHdbOFKF6TfUEaKqrIzl+gNveKtvjF09sr7KCpQYr3hihubf6F7ip56J0Ersq3Vy30YQRVfn9E\\/qAu8DFrt3kun+zSt3\\/0eq+UnaQJzFMUXrpcSV4ZpG4PRH6ah2UQ\\/zYFGNrCwricFwcbJN9W0LptoDaHT0iHfWf4x6Do4Mb1JU5z71te2C9FYBMY\\/g6pejsvh9PD6xkq36YLCqFYz+2k2KV5XFwFj6L\\/6PZTim7N6L4UN\\/ZX138R9E8SBAGRn5m\\/0qHvFeUfZuwTdZgo7A58hTHtqPLeZHKGYTIOPOEyFL3xbCCA8niY8PrJ+fGKPFq2KUbuOYbdgnxul8eiXncAnxbxCcNshNlom+eVraC8GsoIW0\\/Gs\\/h5PKHOooN6nDPWdGF3QfVTcsocfuiAzXBeYGm8BI7iJdlOY4ccuzsnm6EwzA6xO6\\/m6+rkF3viEAF+plr88EgARVgIzMxf2S5NRNWOmOPGotzVynEozRUKDNN9nyypzSJ5bsTJhe5L61nDOYNx5R4xYYUbyljn7f1mED5Xh1g7WmB5ro+MuDyCgcwsDilkw0WKrzQXIDDRQimOUcPPN87zOMb18HzZY2iScwd\\/a0r6hZaESvfXRKMcqOhcxku7iZEKPcphJI2VbRWOL9wzM5D7a+Wsxg1Ws\\/213LcRx7UGsX5e2CfOZMy+p1EnMXLdfpIbuS00OxT8+HHN\\/EOiX+feuSvD65pAJ7Rd2tcV5eXlddJ5bxLPs\\/meokjB9kHNk8Dn+d1Ed0fMyUeNoqYVS5inGz44OTJQGJFUQK3umYNE8RzADAbU9VbnmmC7YiqHM3wDDUhpb+A8K6PWijhYa+HAMzIpqUxN1xZtKsqcBoGkM6ysaISi6owogUOHRQYSZTBGPQscWdor9YIGhKRbjaxLxKqTOQ8yUK8gb2AqzfwCMtKXTRIcJP7ArsSQnCmmQGtGQxro2FcZw9cZxjcP5kzRyIVxGFc8zaDyhW\\/4PfBJ\\/3AMDiE3qVGSUpgV0xfKzToLNih5cVdE4FJEssX5Nr5G0QFy9SVJC1Y2DcbUNIMwoM+FYkgVz6gsz7CMabALxZT8lUYyqkaSKoMJ+iHSTVYgFTRa6iFpGIeIMk2VhP9hfuqa87v8iDQpkypqLkzIWzFNQ0cx0mQiWzHUpaQNahG821Hmylo12YZjD9szWW\\/7vK8YAs0zEaNCTpqhkipFMprhCibDSjzkKplKbfjvdWBK3TRJFvqHvsyFCj+QG8kzxJXjod3z9+LhKdQ3MIgH60PrQLzmzJFIphF0g2ko+LRcZZYOIhI64klYC\\/mxFrDfWAAWFFDGoNVT\\/hyuGYDZmZ0Ji1raT0oy8+fA36s1H6XG4bmpT8qmcdACGZyjQUxruYOwyHK+6StkPN8O+ju2Dk\\/HpXEq8uF4p+i7sziXsAH53KzraKsg56D\\/PB4V0rmKxGCxjEttYaBLZqzW6LKIObPZYSI6W4Kl0+dOVXAcbCZ0Q5Bnhh4Wz2aW0XHinJwyEhA5v1gb6jCZTbBFmOj60tzkdLmNj8nCyu3x0tbVlOInbJy4XjpZpCOvkEZzcz3ZEX2ZcZFYVo4XsMh9bV0GbEVu5NFlUXCNMNhwuXmZaRE1Q\\/as6C+2THiZ5cew8EXMaeb7sXLacqKxl0uj0k8qwVvDoFZFqtlGoW6PB6SMoQy6p0ZBAJKkYnjACmHN00ABZDrgyX37XFlpTbd9pqI6UGYICfga0GDX1mymSQxHg5VPqm7IVuUQX4+QOAwJOTotTfecVuM81smEhEcl0mdYUnFgLdWtxB5qWbH4eW2RpGLMJDDn5qsAdWdgKJ4neydRqu3HLDld12y9WX\\/MEgtnKgs3zxIFOIoqOKAYLHkBZBCoqTtT60U4Pm1x+eJQRLHl2NLSiHqjA6\\/jrTIMewGmr8rn7cocWisXOH2zstZKtcEnoZiQkcRY7SxyeaAgEo3Wi9qSO76Ao6O+A2COaKCDo0T5H\\/7h3GSDpvPvXNKTiDeFtcKCFk\\/sC5HDTS72Wj05yeEkkWiHLyjKSiKVBwyJesgXTdJgeOU8ZqQLaCTq2RdtkAdhdI\\/T6guFGD\\/4evfrcObF5dgKzoXzhpMDMYbx0sDt8JQBw8IpUamHJXWWcMIHFVF4Ey63KcRYoKP1uKb9ru7La90VGvhbIAGUo7Qjp\\/HbPq0wJFUbAMBeAQpJjZ8taRSnm9F2blHas3kRxvXl7GrbdOXJFShwancI+HGgSkqO5cRq448zOspTqohYJB9q++0h31f0zOHmgi7hecgx+2hBDyjRmJj7xXKku0OK35wFVbQvw362sRY1g87Q2uOQ5cnK0ozeXIQhWFuXTVNbBwHtIw1yqm2qKCabU1wwCc5gTclt4Vl1IjGFP05ZtIr6lZqMlTPhs\\/3RThpZ8wrSGYSkRlTaiXQAIeDslkeWSlSMFj5P0edtf4UNOS+qbH1yyC2iVpowErSMM43n5+REyJdYnR2O1awfOjhfIvPlUNpM1ni584dskLFDwTYuI0mzngV0ntC0sxOioPGTg5\\/qRGn2tXx4IZaZMhu+Ij+\\/qbtXN18UlZdrXh7a0aNEhleYN+rHkvlnb\\/FDFGG5vrdrSv4hRnZsf8+8njYDODHsaSW8Dtm526PA1ccDQAd4BZereOvlbxMcw\\/A+hg0G4\\/6AIHCsP7nu+mT1CHTjhnycOZD7OWSZp1lahGX6RlVxFnm97CZ4RT508Mh+SuFdtL3JvNvld0l72LxrX3VNvJmURQTPxvD7K9NkYX41kNNeYPe8TuBCJw+qh+MSXH99YUJ+vVn9IireXuFFvBWZ1x8sePoN\\/46+Ir9IOzuqKso0fr86QSF2M\\/1Z0Vm3WCg+xNFRnxgMhuhgcDW+69uc0217tGYvKPGCEe8Wd82HoQ4vwW\\/oZEqMpij6YNbJO7PbLxdfQn5SfjLvADB0io2nGPrZ+B3bdqYP6X4PoZVohgz0h6Duhml+Xtp5eb7KuiXvwqrc7+gPGcL68A+fEMQHEPL7XTfFtefbDd3qgclVg\\/xkifyOHLwPhaUd3QMEZWk7Qdy1UKtveyVP7OjjmnRtGZV\\/+\\/ZTDlrZ1dEXm5D\\/5Az5Is9yCqs16KOT3ktPtHtFFbf0Q7vner2THaV5t4Db7V6S9pw0z7vKQaHdi9KiF8ZZmpdeAVVJd8F1ofClx8vsojftdcD\\/m6RuOu09td5Fz7cjKU280s7PegqZdnGJYeLdHxkQp1VSPl3dw33odxR9+r\\/31KflZ+IY+ivxqNchd6ufYoj\\/5xuOE\\/8qbtwreFOFqy60xIEDyrXbVS+ye1u78LrwknALB8hDTNDnH8Sk2w2fOFFVhCfv55CIX0NqS+PBvOb\\/nbZGvnSgeo4H6f2pTwwdwYmPTvDJ6I99fuGCTp2q7cjbELhxuX\\/rBsR7X0MXJIpiL\\/h9zHwoPk0ZKnXh+Po8XjpZZ0V7hZOHWUcMtguZRl7b0pBHnvbeHfyzfRsUoZv2bMfLStu1r2iPe29BPTL\\/iOfTA\\/wz83uyvrK\\/ZirMQij\\/02q8jNDBuB38f6UYn1wgX5cD+fqn87f\\/B1BLAQIAABQAAgAIAEVSr1wAAAAAAgAAAAAAAAAGAAAAAAAAAAAAAAAAAAAAAABkdW1teS9QSwECAAAUAAIACABFUq9cNPGs76QLAAB6FwAAHAAAAAAAAAABAAAAAAAmAAAAUi0xMDcyMTEwOTI5Ny0wMy1CMDAxLTI1LnhtbFBLBQYAAAAAAgACAH4AAAAEDAAAAAA=\"}','2026-05-15 10:18:09'),(37,26,'ENVIO_SUNAT','0','La Boleta numero B001-26, ha sido aceptada','','{\"ok\":true,\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-26, ha sido aceptada\",\"observaciones\":[\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"48.00\\\")\"],\"cdr_zip\":\"UEsDBBQAAgAIAI52r1wAAAAAAgAAAAAAAAAGAAAAZHVtbXkvAwBQSwMEFAACAAgAjnavXLBsOPShCwAAexcAABwAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjYueG1stVhpb+JKFv3+fgVKS6MZZdLeMNskeSpvYGMbvLJ884Zt8BYv2PDrp2wCId1pTb8njSKU4t5b5567+LqK5z+bOOodvbwI0+TlAfuOPvS8xEndMPFfHgydexo9\\/Pn6x7OVT0CWRaFjldBQ9YosTQqvBzcnxctDlSeT1CrCYpJYsVdMisxzwt278aSyo0nhBF5sTZrCnfDJMQ0d7wl\\/uGyfWPlfRPiCyQea15R\\/EY5O4zhN2Kb0kjYL8CuE9JKy+AB1bOdvgVLQ3PkS0Pp7gMD3c8+3Su8rUBeWIijLbIIgdV1\\/r4nvae4jOIqiCDpGoI1bhP63q3WRWtnN\\/uKo+A5Vrbzb2C4QLzl6UZp5yM0JdH7b5jVFVHbGrbh4shL3qQxhLDcn1ziLKrHKX8aZeXl1H6zWWn8VK3YFbn4VK4asJVHroK62EMVrsi9IQ0UVWfkT1OZe0Ra\\/eHh9hh00MSjx1hDFtc2\\/0F0kd72TwFX5+qyFPoygym+PyG\\/UBT5m7TbP5ZNd+vpHr\\/dMW0mawDxF4bnLleSVQer2QOSneVgG8S9TgKEtLIzLeXKwfvJtBa3bBmpz+IB02DeGvw2K9q9cn+I0977lhfVUBBaJ4e+Qqrfzcjg9vJ6h8m26oBCK9dxKil2ax8VFcC\\/6n24\\/pejajO5TcWV\\/cf0XQX8nQRAQ+ZH5MxP6XlH+ZsY+UYeJwm7AFxjTiirvdYmfjwOVDSt3beAzCxFLZ71+01ZROXp5Ru4t2xQjtxzDbkE+t8t9US87HON4aqplJfil7XEu\\/UazW8r18Dox++mWmeWCTMoLbaVGszm2fdR48mj5e9rJHrWDfdJjZ78dR\\/0B\\/YbiC3Beagsk3++N0XrOsAlnRAjOI4q+FXRBzYl8uc6k1I7knWOjxAB5i2f9JGJgdOn0aAe15ETbYB2k+PpwcsIVt946qDdC2HFVn+yQOlgRWWlieloNqTcm5woTI41hnBp75\\/FIvInqkkQNm13TjkyaZMkdI3PESut+JQ0pYmCLxGl3XkL9ampSxzIRJYTYivaysZaiwiFbiXUczqenBltb4VsxykKBFyvazgbyCcNg6e2GcfWFN4zt9QqzcFdODRzyb9aLOaWws\\/5wpnprXWiy2n95uWT+LtHPc+\\/UleF5TaJjxiqty4r28vIy6bxXiee5fE\\/TlGD5oOYp4PP8bqy7Q\\/boo0ZRM8pGmKdbPjg6MlBYkVJAre7Zg0TzU4AZLKhrW582gb1iK2dq+AYaUNLewHlORjcr8rBZCweelSlJZWum3jCmoswZEEg6y8mKRiqqzooSOHRYVCDRBmvUs8CRpb1SLxhASvqmkXWJXHUy504G6hXkDUylmZ9BRvmySYGDxB+4lRhSM8UUGM1oKAMd+Spr+DrL+ubBnCkatTAOo4pnWFQ+8w2\\/Bz7lH96CQzgd1yhFK+yKJYRyu86CLUqd3RUZuDSZ2DjfxtcoOkAuviRpwcmmwZqaZpAG9LlQDKniWZXjWY41DW6hmJK\\/0ihW1ShKZTFBP0S6yQmUgkZLPaQM4xDRpqlS8D\\/MT11P\\/S4\\/IkPJlIqaCxPyVkzT0FGMMtnIUgx1KWn9WgTvdrS52qyabDvl4INB1TbB+4ohMDwbsSrkpBkqpdIUqxmuYLKcxEOukqnUhv9eB7bUTZPioH\\/oy1yo8AO5UTxLXjge2j1\\/Lx6eRn0Dg3iwPowOxEvOHIliG0E32IY+A+Ei2+ggoqAjnoK1kO9rAfuNA2BBA2UEWj3tz+GaBZiVWZmwqKX9uKQyfw78vVrzUWocHpv6qGwbBy2Q\\/inqx4yWOwiHLOdbQqHiud0ndlwdHt+WxrHIB6Odou9O4lzC+tRjs64jW0FOAfE4GhbSqYrEYLGMS21hoEt2pNbosoinZrPDRHS2BEuHmB6r4K2\\/HTMNSZ1YZlA8mlnGxIlzdMpIQOT8vNnSh\\/FsjC3CRNeX5jZnSjt+Sxab3BotLV1NaX7MxYnrpeNFOvQKaTg31+MdScisi8Sy8nYGi9zX1mXAVdRWHp4XxbQR+ttpbp5nWkTPkD0n+gubDc+z\\/C0sfBFzmvl+pBztqWjs5dKo9KNK8ptBUKsi3dhRqFujPiVjKIvu6WEQgCSpWB5wQljzDFAAlfZ5at8+V5u0Zto+U1EdKDOEAnwNGLBrazbTJHbKgJVPqW7IVeUAXw+ROAxJOTouTfeUVqM81qmEgkclymc5SnFgLVVb4g61rGz4eb2hKMWYSWA+na8C1J2BgXga751EqeyPWXK8rLl6u\\/6YJRucrTa4eZJoMKXpYgoUg6POgAoCNXVnar0IR0cbl88OTRb2lCs3GllvdeB1vFWW5c7A9FX5ZK\\/MwWblAocwq81aqbb4OBQTKpLYTTuLXB4oiMSg9aLeyB1fMGUiwgEwRwzQwZtE+x\\/+4dzkgqbz75zTo4g3xWaFBS2eSAiRMx2frbV6dJLDUaLQDl9QlJVEKXcYEn2XL4ZiwODCGb5QzqCR6EdftEAehNEtzg0hFGJ85+vdrzM1z+6Uq+BcOG2nciDGMF4GuB2e0mc5OCUq9bCkTxJO+qAiC288zS0aMRbocD2qGb+r+\\/JSd4UBvg0kgE5p7W2q8TbBKCxF1wYAsFeAQtGjx40EX4rboT3f0NqjeRZG9fnkana68uQKFDi9OwT8KFAlJcdycrX1RxkT5SldRBySD7S9fcj3FTNzpnNBl\\/A8nLL7aMH0adEYm\\/vFcqi7A5rfngRVtM4DIttuFjWLztDamyLL4yZLM2Z7FgZgvTlvm3pzEFACaZBjbdFFMd4e44JNcBZryqkNz6pjiS38UcqhVURUajJSTqTPEcOdNNzMK0inH1IafOcfKQeQAs7ZPLJUomK48HmaOdnEChtMvaiy9PEh35C10oSRoGVT03h8TI6kfI7V2eGtmhGhg\\/MlMl8OpO14jZc7f8AFGTcQLOM8lLTNo4DOE4ZxdkIUNH5y8FOdLE1Cywdncpkps8Ez8uObunt180VRebnm5aEV3UtkeIV5pV+W7L97ixdRhOX63q5p+UWMrNj6nnk9bQZwctDTSngdsnK3R4OLjzuADvACLlex7eWvYxzDcALD+v0R0SdJHCPGl12frO6BrtyQjzMHcjuHLPM0S4uwTF\\/pKs4ir5ddBc\\/Ihw4e2Y8pvIu2N5l3u\\/wmaQ+bN+2zrolXk7KI4NkYfn9mmyzMLwZy2gusntcJXOjkTnV3XILrry9MyM83q59ExeszvIi3IvPygwXPvOLf0WfkJ2lnR1dFmcbvVycoxK6mPyo66xYLxQc4OiSG4yExGI0uxjd9m3OmbY\\/W7AklnzDy3eKm+TDU4SX4FetPSGyCDu7MOnlndv3l4kvIT8pP5jdgfIKPPxu\\/Y1vO5C7d7yG0Es2QgX4X1M0wzU9LKy9PF1m35F1Yldsd\\/S5DGAH\\/8DFJfgAhv951VVx6vt3Qre6YXDTID5bIr8jB+1BYWtEtQFCWlhPEXQu1+rZX8sSKPq5Jl5ZR+ddvP+SglV0cfbEJ+V\\/OkC\\/yLKewWn0CHfeeeqLVK6q4pR9aPdfrHa0ozbsF3G71krTnpHneVQ4KrV6UFr0wztK89AqoSroLrguFTz1e5ha9Sa8D\\/meSuumk99B6Fz3fiqQ08UorP+kpZNrFJYaJd3tkQJxWSflwcQ\\/3od9R9OFf76lPy8\\/EMfRn4lGvQ+5WP8QQ\\/+MbjpP\\/Ka7cK3hThasutMSBA8q12lUvsnq2VXhdeElowwFyFxP0+Rsx6VbDJ05UFeHR+zGk\\/ujnmNraeDCx+f+nr5EvHaie40F+v+sTQ4dw5KNjfDz8bZ9fuGBSp2pb8joFrlxu37oJ8d7Y0AWFotgTPriOjg\\/FpzFDp673in6eL52ss2K8wsnDrCMG+4VKI6\\/tacgjT3vvDv7dvg6K0E17luNlpeVaF7T7vdeg7pl\\/xPPpCf6R+S1ZX9lfMhVmIZT\\/bjWe+rAW4\\/54\\/FeK8ckF8nU5kK9\\/O3\\/9L1BLAQIAABQAAgAIAI52r1wAAAAAAgAAAAAAAAAGAAAAAAAAAAAAAAAAAAAAAABkdW1teS9QSwECAAAUAAIACACOdq9csGw49KELAAB7FwAAHAAAAAAAAAABAAAAAAAmAAAAUi0xMDcyMTEwOTI5Ny0wMy1CMDAxLTI2LnhtbFBLBQYAAAAAAgACAH4AAAABDAAAAAA=\"}','2026-05-15 14:52:28'),(38,27,'ENVIO_SUNAT','0','La Boleta numero B001-27, ha sido aceptada','','{\"ok\":true,\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-27, ha sido aceptada\",\"observaciones\":[\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"2.00\\\")\"],\"cdr_zip\":\"UEsDBBQAAgAIANCAsVwAAAAAAgAAAAAAAAAGAAAAZHVtbXkvAwBQSwMEFAACAAgA0ICxXDorp9+iCwAAehcAABwAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjcueG1stVhpb+JKFv3+fgVKS6MZMWkvYJZMkqfyBgYb8Ar2N2\\/Yxmu8YMOvn7IJhHSnNf2eNIpQintvnXvu4usqnv9s4qh3dPMiSJOXB+w7+tBzEzt1gsR7eVAV9nHy8OfrH89m\\/gSyLApss4SGkltkaVK4Pbg5KV4eqjx5Ss0iKJ4SM3aLpyJz7WD\\/bvxUWdFTYftubD41hfPEJcc0sN1H\\/OGy\\/cnM\\/yLCF0w+0Nym\\/ItwVBrHacI0pZu0WYBfIaSblMUHqG3ZfwuUhOb2l4Dm3wMEnpe7nlm6X4E6sBR+WWZPCFLX9fd68D3NPQRHURRBpwi0cYrA+3a1LlIzu9lfHBXfoaqVdxvbBeImRzdKMxe5OYHOb9vcpojKzrgVF49m4jyWAYzl5uQaZ1ElZvnLODM3r+6DlVvrr2LFrsDNr2LFkJ3Ayx3U1RaiuE32BWmoqCIzf4Ta3C3a4hcPr8+wg55Ukr81RHFt8y90F8ld7yRwVb4+y4EHI6jy2yPyG3WBj1m7zXW4ZJ++\\/tHrPVNmkiYwT1Fw7nIluKWfOj0QeWkelH78yxRgaAsL47IfbWyYfNtC67aB2hw+IB32jeFvg6LDK9fHOM3db3lhPha+SWD4O6Tk7t0cTg+3p0pcmy4ohGIlN5Nin+ZxcRHci\\/6n208pujaj81hc2V9c\\/0XQ30kQBER+ZP5MB55blL+ZsU\\/UYaKwG\\/AFRjOjyn1ltuuyb\\/vEmgwcA+P54wDz1NnCtqaTl2fk3rJNMXLLMewW5HO73Bf1suNN3mUoZyMGNanW5wPuIHVAW8XcGuuGYVic6AiBiht9a4qJy378NjSS6E1nZkc54XYrhInNOprTdrDO1FAzYsvKqrdUOg4G9MhDA61PnhL16MuHo9nn9XDC0wOR15BdshyMscpyfNnIrea8MiQ+nJ2DEcNbG7BdT85uVLIBYUmR3ZTnOKH7+yL0FUeLjTTcU1odFCdHRfIyCvWAOFDlJEhkL5LGOL005+fd2M1T3XOE\\/S46j9k5fSiwYzAP3WQuIX0k4X3aQ1Jr6W\\/Y2I2KLDI8zinmZyENGcYeRTtpPF7kysYw8sXhkJCTJb3Zz6mGURo+HB73dJ9F6hExAq6tUBa\\/4fujgRtVnF9y4ySi6peXS+bvEv28dE9dGZ53BDqlzdK8rCg3Ly+Tzn0VOI7NDxRFLkwP1BwJPI7bTxVnzBw9VC1qWtQXy9Tg\\/KO9AiLDkyKopQMTChQ3A5jKgLq2lFnjW1umsmeqp6I+KRxUnGNXqL4lQn23CDlmRQoSU9O1TmuiuKSBLygMuxJlQpQUhhdA2GGRvkCpjFrPfXslHMR6TQNCUPRmpQjEtpPZdzJQbyFvoInN8gwy0ltpJAgFLmS3fEDORW1By2pDqujEkxjVUxjG00JtLsrkWg0nFUcz6OrMNdwBeKQXvvlhMJvWKEmJzJYZLEpjl\\/kGSp6dLeE7FJFYONfG14gKQC6+BGHNrjSV0WRZJVTocy2qQsUxEssxLKOp7FrUBG8rk4wkk6TEYAsljBSNXZAiGm2UgFTVMKI0TSLhf5ifup55XX54mlyREqqtNchb1DRVQTFSYyJTVKWNIA9rHrzbUdpW3zaZMWND60TW1oDzRHVBc0zESJCTrEqkRJGMrDoLjWEFDnIVNLFWvfc6MKWiaSQL\\/UNf2lqCH8iN5BjiwjFs9\\/y9eDgK9VQM4sH60ArgLzmzBZJpForKNNQZLC4yXQERCR1xJKzF6r4WsN9YANYUECeg1VPeEq4ZgJmZmS3WtXCYlmTmLYF3kGouStWw39RH0WhstECGp2gY03JuIyyyWRoDkYyX1nCwZ+vg+LZRj0U+muxFZX\\/ilwI2JPvNro4sETn5g\\/5kXAinKuL99SYu5bWKbpiJVKObIp5pzR7j0fkGbOzB7Fj5cDhN6YYgTww9KvpaltFxYh\\/tMlogq\\/ysG1Q4nU+xdZAoykYzcrq04rdkrefmZGMqUkpxUzZOHDedrtOxWwjjpbab7onBinGQeCW+ncE69+Rd6bMVaazG53UxaxZDY5Zr57kcUXPkwPLe2mKC8zx\\/CwqPx+xmeZiIR2vGq4dVqVbKUSI4feTXEk81VhQo5mRIrjCUQQ\\/U2PdBklQMB9hFUHM0EAGZDjny0D5XelrTbZ9JqALEOUICrgY02Lc1m8sCM6PB1iMlJ2CrcoTvxkgcBMQqOm4055RWkzxWyISERyXSY1hStGEtJUtgw3ol6tyy1klSVOcCWM6WWx915mDEn6YHOxEr62OWHC9rtjZ2H7NEx5lKx7WTQIEZRRUzIKoseQak70upM5fqdTA5WvjqbFNEYc3YUpeJ2lCA2\\/GWGIY9A82TVidrq430rQPsgVbpO7Ey8GnAJ2QkMHo7ixwOiIhAo\\/W61lcdXzCjo4ENYI5ooIA3gfI+\\/MO5yfpN598+p0cebwp9i\\/ktHj9YRPZsejZ30tFOwqNAoh3+QhS3AineYQjUXb5okgajC+cJI5xBI1B9jzdB7gfRLU59sCj4+M7Xu197pp2dGVvBuXAyZiufj2G8NHA6PHHIsHBKVFK4oU4CTnigIgp3OstNClHX6Hg3qWmvq\\/vmUneRBp4FBIDOKPltJnPWgBYZkqpVAGCvAJGkJn1dGMepMbaWOiX3tfNiUp9PjmylW3dVgQKn9qHPTXxJEHMsJ7aGN8noKE+pImKRfCQfrDA\\/VPTcni0XioDnwYw5RGt6SPHqVDusN2PFGVGccVpIvHkeDTJDX9cMOkdrd4ZsjnqWZrRxXozATj8bTa2HC3SANMixNqmimBrHuGASnMGacmbBs+pUYApvkrJoFQ0qKZmIJ8JjB+O9MNaXFaQzDEiZqOQjaQNigbMWh2zEqBivPY6iT9Zgi41m8LVrKtMw14labIJoIWczTe33kyOxOsfSPHyr5oPAxrkSWW5GgjHd4eXeG7F+xo4WpnoeC7LeX6DLhKbt\\/SLyGy8JvVQhSm0g56MzscnE+egZ+fFN3b26uaKo3Fx288CM7iUreIV5pV42zL976xeeh+X63q6p1QsfmbH5PXN78hzgxKgnl\\/A6ZOZOjwIXH3cAHeAFfFXFlpu\\/TnEMwwcYNhxOBkOCwLHB9LLrk9U90JUb8nHmQG7nkE2eZmkRlOkrVcVZ5Payq+AZ+dDBI\\/sxhXfR9ibzbpffJO1h86Z9VmT+alIWETwbw+\\/PTJMF+cVglfZ8s+d2Agc6uVPdHZfg+usLE\\/LzzeonUfH6DC\\/irUi7\\/GDB0a\\/4d\\/QZ+Una2VFVUabx+9UJCrGr6Y+KzrrFQvERjo6JMTrGiPHgYnzTtzmn2\\/ZozR5R4hEbv1vcNB+GCrwEv2KjJxR\\/wvE7s07emV1\\/ufgS8pPyk\\/kNePQ0wD8bv2Ob9tNdut9DaCWyugLKXVA3wzQ\\/bcy8PF1k3ZJzYFVud\\/S7DGED+IdPCeIDCPn1rqvi0vPthm51x+SiQX6wRH5FDt6HgtKMbgGCsjRtP+5aqNW3vZInZvRxTbq0jMS9fvshB63s4uiLTcj\\/coZ8kedVCqs1HKDT3mOPN3tFFbf0A7PnuL2jGaV5t4DbzV6S9uw0z7vKQaHZi9KiF8RZmpduAVVJd8F1oPCxx63Yde+p1wH\\/M0md9Kn30HrnXc+MhDRxSzM\\/KSlk2sXFB4l7e2RAnFZJ+XBxD\\/eh31H04V\\/vqU\\/Lz8Qx9GfiUa9D7lY\\/xBD\\/4xuOE\\/8prtwreFOFqy60xIYDyjHbVS8ye5ZZuF14SWDBAXIXE\\/T5GzEpZsMldlQVwdH9MST855Da0rgwr\\/n\\/p62RLx1Iru1Cer\\/rE0PHcOKjU3w6\\/m2fX7igU7tqO\\/I6BK5cbt+6AfHe19AFiaLYI34bMx+KT1OGSh33Ff08XjpZZ0W7hZ0HWUcMtguZRm7b0pBHnvbeHfy7fRsUgZP2TNvNStMxL2j3e69B3TP\\/iOfTA\\/wj81uyvrK\\/ZCrIAij\\/3Wo8jtHhhBgOR3+lGJ9cIF+XA\\/n6p\\/PX\\/wJQSwECAAAUAAIACADQgLFcAAAAAAIAAAAAAAAABgAAAAAAAAAAAAAAAAAAAAAAZHVtbXkvUEsBAgAAFAACAAgA0ICxXDorp9+iCwAAehcAABwAAAAAAAAAAQAAAAAAJgAAAFItMTA3MjExMDkyOTctMDMtQjAwMS0yNy54bWxQSwUGAAAAAAIAAgB+AAAAAgwAAAAA\"}','2026-05-17 16:06:32'),(39,28,'ENVIO_SUNAT','0','La Boleta numero B001-28, ha sido aceptada','','{\"ok\":true,\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-28, ha sido aceptada\",\"observaciones\":[\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"2.00\\\")\"],\"cdr_zip\":\"UEsDBBQAAgAIAEFxslwAAAAAAgAAAAAAAAAGAAAAZHVtbXkvAwBQSwMEFAACAAgAQXGyXBgMbWymCwAAehcAABwAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjgueG1stVhpb+JMtv4+vwKlpat7lUl7AbNNOqMqL2BjG7wS880btsEL8YINv\\/6WTSCkO63p95VGEUpxzlPPWX1cxfO\\/myTuHf28iLL0xwPxHX\\/o+ambeVEa\\/HgwdO5p\\/PDvl3882\\/kUHA5x5NolAqp+ccjSwu+hzWnx46HK02lmF1ExTe3EL6bFwXej7Tt4WjnxtHBDP7GnTeFN+fSYRa7\\/RD5ctk\\/t\\/C8yfOHJB5vflH+Rjs6SJEvZpvTTNgvoK6L007L4IHUd92+RQgR3vyS0\\/x4hCILcD+zS\\/4rUQ6UIy\\/IwxbC6rr\\/X\\/e9ZHmAkjuMYPsEQxiui4NsVXWT24Ya\\/GCq+I1Ur7za2C8xPj36cHXzsZgQZv23zmyIuO3ArLp7s1HsqIxTLzcg1zqJK7fK3cR78vLoPVmvRX8VKXImb38VKYK+SqHVUVyxi8ZvDF04jRRXb+RPS5n7RFr94eHlGHTQ1oHhriOLa5l\\/oLpK73knRqnx51qIARVDlt0fkD+qCHrN2m+\\/x6TZ7+Uev90zbaZaiPMXRucuV5Jdh5vVAHGR5VIbJb1NA4C0tist9colB+m2N0G0DtTl8wDrum4d\\/TIoPrr4+JVnuf8sL+6kIbYog3ylVf+vnaHr4PUPl23QhIRLruZ0W2yxPiovgXvQfzX5K0bUZvafi6v3F9F8k\\/ZMEIULsZ8+fmSjwi\\/IPM\\/bJdZQo4kZ8oTHtuPJfdv7xvH7TBXtr42nDkuXWokJrzHNv+x\\/P2D2yTTF2yzHqFuxzu9wX9bIjrzRXlhox950d4ciBKcPZNo6UjSrnkq5qgz1bLfl8L\\/jNYIUHCc17XD+Zk1IN9AaKsdPn2EIzJ4NzPxb9V3oc9XOH7VPhnl7OV3RcFIv1nqD99ZEZ7mj6rIcFIMfB+XWs+Kd4Ky53dolTWnHKm40h2LvKOY4eDf8sUJZY7QabtW1Clxe4vH9cEfEiS3b9M8z33uy8zbXzInOhQuwLkeWJpBjT5MBQ3yh7AbCNEPnGcrgSfNFeSFwDD8vFxhmUj+OFmwemOJCTJQGq7XxIvp7ofLMTwG5ZTTa1Qrzij5RDnLR+stKbsxva3iSBu2zJboE8OVL6CYLNQtpw+TYJxrsxI6Vvq8VbwuPWIPGiwYyH9Y8fl8zfJfp54Z+6Mjy\\/UviEsUv7sqL9vLxMOv9F4nkuR1mCgh2Amocg4PntRPdG7DHAjaJmFEtYZBs+PLoyUFgRKqBWd+xeovkZIAwW1LWjz5rQWbOVOzMCAw+htDNInpNxa03trVdhz7MylFS2ZmqLMRVlwYBQ0llOVjRKUXVWlMC+44KhRBusUc9D1B87pV4ygJJ0q5F1iVp3MvdOBuo18huYSrM4gwMMZBOCvcTvubUYwbliCoxmNNDAx4HKGoHOsoG5N+eKBpfGflzxDIvLZ77hdyCAwf4t3EezSY1DWmHXbF8oN6+HcIPDs7emQo+mUofk2\\/gaRQfYxZYkLTnZNFhT0wzKQDaXiiFVPKtyPMuxpsEtFVMK1hpkVQ1ClSUEfR\\/rJidABY9XegQNYx\\/TpqlC9B\\/lp65nQZcfkYEyVHFzaSK\\/FdM0dJyAJhvbiqGuJG1Qi+AdR5tra90cNjNu75xg7fT5QDEEhmdjVkU+aYYKVRqymuEJJstJPPJVMpXaCN7rwJa6aUIO2Ue2zKWKPsg3yLPUxcd9u+fvxcPTeGAQiA\\/Vh9GBeMmZK0G2EXSDbegzEC4ySwcxRIZ4iGoh39cC9RsHwJIGyhi0ejpYoDULCPtgH4RlLe0mJTwECxDs1JqPM2P\\/2NRHZdO4eIENTvEgYbTcxThstdj0FZgsnEF\\/y9XR8W1lHIt8ON4q+vYkLiRiAB+b1zp2FOwU9h\\/Ho0I6VbEYLldJqS0NfMWO1RpfFcnMbLaEiM9XYOX2Z8cqfBtsJkxDwRPLDItH83BgktQ9umUsYHJ+tjb0fjKfEMso1fWVucmZ0kne0qWV2+OVrasZzU+4JPX8bLLMRn4hjRbm62RL9WXWwxJZeTuDZR5or2XIVXAjj87LYtYIg80sN89zLabn2I4Tg6XDRud5\\/hYVgUi4zWI3Vo7OTDR2cmlU+lGleGsY1qpIN04c6fZ4AGUCZ\\/EdPQpDkKYVywNOiGqeAQqA2YCHu\\/a5srKaaftMxXWgzDEI+BowYNvWbK5J7IwB6wCqXsRVJZpnIyyJIkqOjyvTO2XVOE90mEJ0VIIBy0HFRbVUHYnb17Ji8YvaglAx5hJYzBbrEPfmYCieJjs3VSrnY5YcL2uu3rx+zBKLZCuLNE8SDWY0XcyAYnDwDGAYqpk3V+tlND46pHx2aapwZlxpaVS90YHf+a2yLHcGZqDKJ2dtDq21B9y+WVmvSrUhJ5GYwlhirXYWeTxQMInB62VtyZ2\\/YMbEfRegHDFAB28SHXzYR3OTC5vOvnvOjiLZFNaaCFs+sS\\/E7mxytl\\/Vo5vujxLEO35BUdYSVO44JPouXwxkwPDi85iVzqCR6MdAtEEeRvEtTqsvFGJyZ+vdrjszz96Mq9BcOG1mcigmKF4GeB2fMmA5NCUqdb+iTxJJBaCiCn8yy20aM5b46HVcM0FX99Wl7goDAgdIAJ\\/R2ttM450+o7CQrg0AUK8ABdLjR0saJdlm5CwsWns0z8K4Pp88zcnWvlyBgqS3+5Afh6qk5EROrTfB+MDEeUYXMYflQ23n7PNdxczd2ULQJTKPZuwuXjIDWjQm5m65GunekOY3J0EV7fOwf9hYy5rF53jtz7DV0TpkB2ZzFobg1TpvmtraC3gfa7BjbdNFMdkck4JNSZZoypmDzqoTiS2CccbhVdyv1HSsnKiA64+20shaVMidQQQ1qtKO0AWUQHIOj62UuBgtA55mTk5\\/TQxnflzZ+mSfW1StNFEsaIeZaTw+pkdKPifqfP9WzfuRS\\/IltlgNpc3kFR2fgiEXHrihYBvnkaRZjwK+SBnG3Qpx2ATpPsh0qjT7Wj48U6uDMh8+Yz+\\/qbtXN18UlZ9rfh7Z8b1ERleYF\\/rHiv1nb\\/lDFFG5vrdrWv4hxnZifz\\/4PW0OSGrY00p0HbJzr0eDi407go7wQi5XiePnLxOSIMg+QQwG4\\/6AokiiP7ns+oS6J7r6hn2cObDbOWSVZ4esiMrsha6SQ+z3DlfBM\\/ahQ0f2Y4buou1N5h2X3yTtYfOmfdY18QopixidjdH3Z7Y5RPkFIGe90O75ncBDRu5Ud8cltP76woT9erP6RVS8PKOLeCsyLz9Y8MwL+R1\\/xn6Rdji6Ksoseb86ISFxhf6s6NAtF04OSXw0HBDUeDIhLuCbvs0507ZHC3vCqSdi9I64aT6AOroEvxDDKT6aEuM7WCfvYNdfLj5TvmM\\/KT\\/BL8SDKYFPcfIz+J3bdqd36X4PoZVohgz0u6BuwCw\\/rey8PF1k3ZL3UFVud\\/S7DBF99EdOKOqDCPv9rqvi0vPthm5158lFg\\/2ExH7nHLoPRaUd3wIEZWm7YdK1UKtveyVP7fjjmnRpGZV\\/+fZTDlrZxdAXm7D\\/ZAz7Is9yhqo16OOT3lNPtHtFlbTuR3bP83tHO87yboG2270067lZnneVQ0K7F2dFL0oOWV76BVKl3QXXQ8KnHi9zy9601xH\\/b5p52bT30FoX\\/cCOpSz1Szs\\/6RnytItLjFL\\/9siAJKvS8uFiHu3Dv+P4w\\/+9pz4rPztO4L86Hvc65m71UwzJ\\/3wjSepfxdX3Ct1U0aoLLXXRgPLsdtWL7Z5jF34XXho5aIDcxYRs\\/kFMut3wqRtXRXT0fw6J\\/DWktjQ+ymv+32lr7EsDqu\\/6yL0\\/tUngIzTx8Qk5Gf2xzS9MMJlbtR15HQJXX27fugHx3tfIBMRx4om8jZkPxacpQ2ee\\/4J\\/Hi+drEMxfuHm0aFzDLULzGK\\/bWnkR5713g38s30bFJGX9WzXP5S2Z1\\/Y7vdeg7r3\\/COeTw\\/wz57fkvUV\\/pKp6BAh+Z9W42mED8bUYDD8K8X4ZAL7uhzY1z+dv\\/w\\/UEsBAgAAFAACAAgAQXGyXAAAAAACAAAAAAAAAAYAAAAAAAAAAAAAAAAAAAAAAGR1bW15L1BLAQIAABQAAgAIAEFxslwYDG1spgsAAHoXAAAcAAAAAAAAAAEAAAAAACYAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjgueG1sUEsFBgAAAAACAAIAfgAAAAYMAAAAAA==\"}','2026-05-18 14:10:02'),(40,29,'ENVIO_SUNAT','2024','El XML no contiene el tag InvoicedQuantity en el detalle de los Items o es cero (0) - El XML no contiene el tag InvoicedQuantity en el detalle de los Items o es cero (0) Detalle: xxx.xxx.xxx value=\'ticket: 202620764283285 error: Error en la linea:1. : 2024 (nodo: \"cac:InvoiceLine/cbc:InvoicedQuantity\" valor: \"0.00\")\'','','{\"ok\":true,\"codigo\":\"2024\",\"mensaje\":\"El XML no contiene el tag InvoicedQuantity en el detalle de los Items o es cero (0) - El XML no contiene el tag InvoicedQuantity en el detalle de los Items o es cero (0) Detalle: xxx.xxx.xxx value=\'ticket: 202620764283285 error: Error en la linea:1. : 2024 (nodo: \\\"cac:InvoiceLine\\/cbc:InvoicedQuantity\\\" valor: \\\"0.00\\\")\'\",\"observaciones\":[],\"cdr_zip\":\"UEsDBBQAAgAIABpzslwAAAAAAgAAAAAAAAAGAAAAZHVtbXkvAwBQSwMEFAACAAgAGnOyXMlmtFZgCwAABxcAABwAAABSLTEwNzIxMTA5Mjk3LTAzLUIwMDEtMjkueG1stVhZc7JIF76fX0FlLmam\\/BIWRcVKMtWsgqCyKt4hIKAIhEXQX\\/81Go3J5K15Z6omqcTmnKefs3bbzfOfzT5GDn5eRGny8oA\\/YQ+In7ipFyXBy4Np8I\\/Dhz9ff3l28hHIsjhynRICNb\\/I0qTwETg5KV4eqjwZpU4RFaPE2fvFqMh8N9q8g0fVOh4VbujvnVFTeCMxOaSR6z8SD5fpIyf\\/hwzfePLB5jflP6Rj0v0+Tbim9JM2C\\/ARUvpJWXyQumv3X5HSEO5+S+j8O0IQBLkfOKX\\/HakHSxGWZTZC0bqun+ruU5oHKIFhGIpRKMR4RRT8ekUXqZPd8BdDxRNUtfLzxHaA+snBj9PMR29GoPHbNL8p4vIMbsXFo5N4j2UEY7kZucZZVIlT\\/jDOzM+r+2D1Fv1drPiVuPlRrDi6VGT9THXFQha\\/yb5xGiqq2MkfoTb3i7b4xcPrM+ygkUnLt4Yorm3+je4iueudBI7K12c9CmAEVX5bIj9RF7jM2mm+Jyab9PUXBHlmnCRNYJ7i6HTOleKXYeohIA7SPCrD\\/Q9TgGMtLYzLfXTxXvLrAqLbBmpz+ICeuW8e\\/jQp1rv6+rhPc\\/\\/XvHAei9AhceKdUvM3fg53Dx8xNbFNFxRCsZE7SbFJ831xEdyL\\/tbspxRdm9F7LK7eX0z\\/Q9KfSRAkRL96\\/sxGgV+UP5mxT67DROE34guN5cSV\\/7qtUGtCjndSuujUcyInpIVuZwPuJIgvz+g9sk0xessx7Bb0c7vcF\\/Uyo7TXjN8bKwSvVWy6djbmWvZ7NHbwjQHTGdjdDUouKR1zPWXtx0LYWwR85+QDlaF39Zah3rD+ZDnouzPLp8Pj2+Goq5I7jsVTZBj2mg6OLjGcL1y\\/ngrzJIrFBSE0jb0JNU\\/GvE5Ud6hDSG7sI9bB12UU61apxadMcfnpeOXOSG55WDHjyphuh9DlycFbvIlSlUsKg2KMQ\\/YqnkLVbFL1462R+vmgW+7ftpQjSxh2AEk4Ditt2HXwQ3mc46az2\\/DKPC2dWjZO\\/HYexpRbVlzXTSyqxwiEMKRsayxwoK6DVTIfno6iJZH1opQ5cYPmu6jAF2I37uwH4f7EYkZZjvMgqkWlb7hDdugFJk3t2LyngJeXS+bvEv088Y\\/nMjwvSYxindK5jBg\\/Ly87nf+qiCKfbxmGlpwA1CINAlHcUIY34A4BZhY1q9rSJF2J4cGdApWTaRXU2pbbKYwoANxsHV8bQhOuF1zlCmZgYiGtbE1C5KeYvSB39lLaidyUVjSuZmubtVR1woJQMTh+quqkqhmcrIDdmYsOFcbkzHoculNlq9YzFpCKYTdTQyEXZ5l7JwP1AvoNLLWZnEBGB1OLBjtF3PELOaLHqiWxutnQJjYMNM4MDI4LrJ01VnV6Zu6Glchy2PQkNuIWBHSwewt3kUDVGM2o3ILrSuVqmYUrjD55CzL0GDJZE2IbX6MaAL3YUpQZP7VMztJ1kzShzZlqKpXIabzI8Zxl8jPVUoKFTnOaTtMah0vGLjYsXqJVLJ4bEW2au5ixLI2GnzA\\/dS0E5\\/zILD2lNcyaWdBv1bJMA8Npi4sd1dTmit6rZfCOY6yFvWiylcDv1ke6XnfFQDUlVuRiToM+6aZGawzN6aYnWRyviNBXxVJrM3ivA1calkXz0D60Zc00+Ad9o0WOvPi4a+f8u3hEBgtMHPLB+rAGkC85cxWaayTD5BrmBKSLzDZATENDIg1rMb2vBew3HoAZA9QhaPVMMIFjDuBO5mTSrFa2VElnwQQEW60W49TcdZr6oK4aFyvQ3jHu7Vk9d1EenU9WXZXeT9a97oavo8Pb3DwUeX+4UY3NUZ4oeI\\/uNMs6XqvoMex2hoNCOVaxHM7m+1KfmdicG2o1Ni\\/2gtVscBkbz8Hc7QqHKnzrrSi2Iekjx\\/aLjpVl7D5xD24ZS+g0P9krZkeNKXwWJYYxt1Y5W673b8nMzp3h3DG0lBEpfp94fkrN0oFfKIOJtaQ2ZHfKeeh+qr6dwCwP9GUZ8hW9mg5Os0JopN5KyK3TWI+ZMbrl5WC25qLTOH+LikDG3WayHaqHtSCb22lpVsZBI0W7H9aazDTrODKcYY+e4hiHbZlBGIIkqTgR8BLcU1igAjrtifS2XVd2WrNtn2mYAdQxSgOxBizYtDUb6wonsGAR0JoX8VXZJ5YDdB9F5DQ+zC3vmFbDfG\\/QCQ2PSnTA8bTqwlpqa4Xf1VPVFie1TdOqOVbARJgsQswbg758pLZuolbrj73kcBnz9Wr5sZfYBFfZhHVUGCAwTCEA1eTpE6DDUEu9sVbPouFhTUxPLkMWa4EvbZ2sVwbwz35rHMefgBVo0+N6YfXthQfcrlXZS7VaEVQkJ3SscHa7F3kiUFGFxepZbU\\/P\\/gKBjbsugDligQHeFCb4sA\\/3TT5szvbdU3qQiaawF3jY8sldKXYF6uQstYOb7A4KjZ35JVVdKLR6x6Ewd\\/liaRb0Lz4POeUEGoXpBLID8jCKb3HaXamQ93e23u26gnXyBL6C+8JxJUxDeQ\\/jZYF35lN7HA93iUrbzZmjQpABqMjCp4TcYVBzhg2Ww5oNznWfX+qusiBYAwVgAqO\\/Cbq47rIqRzO1CQDsFaDSzLBjK4N9uhqsJzajd6yTNKxPR09fpwt\\/WoGCYDa7UByGmqLmeE4uVsEwY+M8ZYqYR\\/O+vl3v8m3Fjl1hIhkKkUcCt41nbI+RTcrazuYDw+sz4uooabJz6nezlT2rOWyM1b6Azg92lmbs6iT1wdI+rZra3klYF23QQ+0wRUGtDvuCSwgOb0phDc+qlMIVwTDlsSruVloyVI9kwHcHG2VgTyroTi+idbLSD7QLSIng1yI6V+NiMAtEhj2uuwu8L\\/hx5RjULrfJWm2iWNIzwTI7neRATk97bbx7q8bdyCXEEp3M+8qKWhLlJujzYcb3Jcc8DRTd7kjYJGFZdyPFYRMkuyA1yNLq6nn\\/RM4zddx\\/Rr9+U5+\\/usWiqPxc9\\/PIie8lU3iFeWVe5tz\\/kNmLLMNyPbVjZvoix87eecp8RB8DguwjegmvQ07uIQy42LgjOBNeyKfVfu3nrxSB40QXx3u9YbdHkgTepS6zPqHuia6+oR9nDvR2DpnnaZYWUZm+MtU+i30kuwqe0Q8dPLIfUngXbW8y77j8JmkPmzfts6HLV0hZxPBsDJ+fuSaL8gtgmiKhg\\/hngQeN3Knujktw\\/P2FCf3rzeovouL1GV7EW5F1eWEhsq\\/EE\\/aM\\/kV6xjFVUab796sTFOJX6FfFGd1yYUSfwAb9HjHsEkPyAr7p25yzbXu0sEeMfMSHq3fITfWBNOAt+BXvjYjeiCSeBv0uBX+6Q6rfG\\/bJ+3ln4Hne9V3GFyPw\\/wh79\\/wT5NOkT\\/a+m\\/Jux3FHd8V4D7CV6OYUGHch34Bpfpw7eXm8yM5D0YM1u93g7\\/KHd+EvQZF3uUN\\/POuquKyIdsJ5dOfJRYN+QaI\\/cg7elqLSiW8BgrJ03HB\\/brBW33ZSnjjxxyXq0lCa+Prrlxy0souhbyahf2cM\\/Zrn9tGHx4\\/8v0kl+q0BzXf96PCzNtG\\/NfANH5u6VRvytcuuhm9P5w58TxyMgYaX70eCurbmh+JTMzOpd14Bvc8tfBafgaxfuHmUnX3jYmSpyEiSIi7cTCI\\/8RE\\/RkonQN7fNHpq5UBFeUT8pFV5Piwb3MY8H4nTAhFLf18gKeIXiOvnKfI79gfyiPwXrOwFMkKapnl6\\/0MO7QXy5bcycnd+OUK+7EGIn+dpPkK49qO1FDtIHCW+M8KfkDO6h\\/yepF46Qh7azL87J0PIpW2+OPvQ2msJH7AnDHv447dLhu\\/zea31fUE\\/yvxp4Xwt6K1Jv8NfGijKYC7Ln1wFjz\\/d+1+Z0e+bE\\/3+Pfbr\\/wFQSwECAAAUAAIACAAac7JcAAAAAAIAAAAAAAAABgAAAAAAAAAAAAAAAAAAAAAAZHVtbXkvUEsBAgAAFAACAAgAGnOyXMlmtFZgCwAABxcAABwAAAAAAAAAAQAAAAAAJgAAAFItMTA3MjExMDkyOTctMDMtQjAwMS0yOS54bWxQSwUGAAAAAAIAAgB+AAAAwAsAAAAA\"}','2026-05-18 14:24:52'),(41,31,'ENVIO_SUNAT','0','La Boleta numero B001-31, ha sido aceptada','','{\"ok\":true,\"codigo\":\"0\",\"mensaje\":\"La Boleta numero B001-31, ha sido aceptada\",\"observaciones\":[\"4309 - La sumatoria de valor de venta no corresponde a los importes consignados - INFO : 4309 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:LineExtensionAmount\\\" valor: \\\"0.00\\\")\",\"4310 - La sumatoria del Total del valor de venta m\\u00e1s los impuestos no concuerda con la base imponible - INFO : 4310 (nodo: \\\"cac:LegalMonetaryTotal\\/cbc:TaxInclusiveAmount\\\" valor: \\\"25.00\\\")\"],\"cdr_zip\":\"UEsDBBQAAgAIAJxyv1wAAAAAAgAAAAAAAAAGAAAAZHVtbXkvAwBQSwMEFAACAAgAnHK\\/XAV2A8zoBAAApQ4AABwAAABSLTIwNjA3MDI3Njg1LTAzLUIwMDEtMzEueG1stVdtU+M2EP5+v0ITZnq9a41sgxPihtwEuCspIeVCQpl+E7aSeLAlI8kh8Ou7kmPHCWYuuZkOfBC7zz77qpXpfFkmMVpQISPOThvOod1AlAU8jNjstDEZf7NOGl+6HzpE+L00jaOAKACOqEw5kxSBMZOnjUwwnxMZSZ+RhEpfpjSIpiuwnz3EvgzmNCH+UoZ+ny14FFDLbeTmPhF7MtREsmajS7Un3TlPEs6+LhVlugrwJ1BSpuSaNHgIfor0DOBBLSH5OcLebCbojChaRxpCK+ZKpT7Gz8\\/Ph89Hh1zMsGvbNrbbGDChjGYHBVpykpb43JE8BJWWG0N9wJQtaMxTiksn4Lw0o0sZKwPWYmkRFloqglxKJ0WeMmNEvZtnSkVWTfZWo+tydQri5Xu5Ovj+enBrqAossNBlWhM0KLKYCAu0gkrdfNnodmCC\\/MnZoBwIWYx5jS6XVGaHwUl1O7fRDDLIRHlFdugLXDNtRsM+m\\/LuB4Q654RxBnWKo1dTq2uq5jxEvXjGRaTmybslcGxNC3kFVuAcs4N\\/AK0HSNewgQ13GeHOpPZxEauVcEEPhCSWnBPPcVeUIzqlArYHRZNRX5cLhCAeC8LklItE5oKq6IduN0pUDGNoySL63PWepLsUCAjxduSdi2hGpdqzYlCRg2qdSp47Eme0eyLDx3Pnio2exBGb3rnu4HWRPd4\\/fb0KpsPhMX18ovRm3ibNh1c2ubq0H+8m\\/6bXf8XfX6aty8urP5\\/Sxeusfd\\/vTRffvd+uloz2e5Pn09MOrnrR\\/cFlg2DU8OasVScit\\/h8I6IF3D70SF\\/QxzOqyA1cVVhnVKiPiHGFsvRzTlOx6lzRF8PZuffs9gVRJD9pq\\/zOA\\/MQ1kCIgrVoxZ87BIYK\\/7axYetLmVFxS0VE4qpEE+9PX7E1XDnvMEseqNifbcO66qAIF68rg8tqresI5\\/qdgt8unzci2e3AW6VFd\\/mb3r\\/ouod2B7+RGtx5JhVPVtsFhE4B3VYYtAa0TmzXc5peq+U5ObTU6iQvdItc221atmcdOWPnyHfavrdiXUPWFmN4Lrq27ZvfCszIDax447e4c+yGcgNuCJxj37V9r7UJXnGTwK9UfZWLltxOhr1xJbsSyMXLDRHqJZeZYz+E5pSvWUnj2s4R\\/Lhtz1sT4fetCkU+hdrAnCqR5Bq8hcTvBQeXP1IkLhPsKUWCeWImSev1yAhG4vVOyCdn1O8ebNVAy3JHNUb4R85wTZ2HHLp1fGS3kYUGBMks0eFHBIUULUjMhTmAOYG7hQIuhOkcCAmKuUQRfBAI+OAAFTNPQQhCC\\/WH3\\/5GPjLEvzIech81tPcBfEbE1\\/AJoYh4GXOI1OQ1iBgtb04v4RlTjdw92NmHtt34tCo9V5uBO\\/bbwGNkmM1pK4fklwPX9f6QRewZrGU4mdRYAKshJPqEYoIeCHxW6\\/RY9BDTak7gc4ecxmTZZ0GcyWhBt1Nyvbc56d5QKKz4f+Ya1zoY0YBCfLv7bNot2201T3b3WePiggeZHsliCxSxlH+ZDbEabHBxBk94Zc+sFRtr5pyHsL8294uRGdQFlYGIUhMYzMsZj6meaYhDcLRy8DuawyRFIUckoKkiIcnZqrZFUtXI1\\/ls3ODtyMti1eHzSkVpBPIdu9G0jqERrt1u7tOMDRe4vh24\\/r\\/M7n9QSwECAAAUAAIACACccr9cAAAAAAIAAAAAAAAABgAAAAAAAAAAAAAAAAAAAAAAZHVtbXkvUEsBAgAAFAACAAgAnHK\\/XAV2A8zoBAAApQ4AABwAAAAAAAAAAQAAAAAAJgAAAFItMjA2MDcwMjc2ODUtMDMtQjAwMS0zMS54bWxQSwUGAAAAAAIAAgB+AAAASAUAAAAA\"}','2026-05-31 13:20:49'),(42,30,'ENVIO_SUNAT','2024','El XML no contiene el tag InvoicedQuantity en el detalle de los Items o es cero (0) - Detalle: xxx.xxx.xxx value=\'ticket: 1780251666345 error: Error en la linea:1. : 2024 (nodo: \"cac:InvoiceLine/cbc:InvoicedQuantity\" valor: \"0.00\")\'','','{\"ok\":false,\"codigo\":\"2024\",\"mensaje\":\"El XML no contiene el tag InvoicedQuantity en el detalle de los Items o es cero (0) - Detalle: xxx.xxx.xxx value=\'ticket: 1780251666345 error: Error en la linea:1. : 2024 (nodo: \\\"cac:InvoiceLine\\/cbc:InvoicedQuantity\\\" valor: \\\"0.00\\\")\'\"}','2026-05-31 13:20:57'),(43,30,'ENVIO_SUNAT','2024','El XML no contiene el tag InvoicedQuantity en el detalle de los Items o es cero (0) - Detalle: xxx.xxx.xxx value=\'ticket: 1780251667564 error: Error en la linea:1. : 2024 (nodo: \"cac:InvoiceLine/cbc:InvoicedQuantity\" valor: \"0.00\")\'','','{\"ok\":false,\"codigo\":\"2024\",\"mensaje\":\"El XML no contiene el tag InvoicedQuantity en el detalle de los Items o es cero (0) - Detalle: xxx.xxx.xxx value=\'ticket: 1780251667564 error: Error en la linea:1. : 2024 (nodo: \\\"cac:InvoiceLine\\/cbc:InvoicedQuantity\\\" valor: \\\"0.00\\\")\'\"}','2026-05-31 13:20:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificado`
--

LOCK TABLES `certificado` WRITE;
/*!40000 ALTER TABLE `certificado` DISABLE KEYS */;
INSERT INTO `certificado` VALUES (1,1,'LLAMA-PE-CERTIFICADO-DEMO-20607027685.pfx','../certificado/LLAMA-PE-CERTIFICADO-DEMO-20607027685.pfx','Leogi101715/*','demo','2026-05-07 19:25:50',NULL,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
INSERT INTO `cliente` VALUES (1,'Maria Garcia','45678912','987654321',NULL,NULL,'maria@email.com',0,0.00,NULL,1),(2,'Carlos Mendoza','78912345','912345678',NULL,NULL,'carlos@email.com',0,0.00,NULL,1),(3,'Ana Torres','12345678','954321876',NULL,NULL,'ana@email.com',0,0.00,NULL,1),(4,'Luis Ramirez','87654321','998877665',NULL,NULL,'luis@email.com',0,0.00,NULL,1),(5,'Sofia Vargas','23456789','923456789',NULL,NULL,'sofia@email.com',0,0.00,NULL,1),(6,'Juanita Sadith Panduro Villasis','70485446','958838916',NULL,NULL,'juani_1415@hotmail.com',0,0.00,NULL,1),(7,'CREACIONES HUERTA E.I.R.L.','20615763374','',NULL,NULL,'',0,0.00,NULL,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente_facturacion`
--

LOCK TABLES `cliente_facturacion` WRITE;
/*!40000 ALTER TABLE `cliente_facturacion` DISABLE KEYS */;
INSERT INTO `cliente_facturacion` VALUES (1,NULL,'1','71053039','roy frank','pachitea','royespinoza27@gmail.com','',NULL,'2026-05-09 13:36:20'),(2,NULL,'6','10468520961','PIERO JUNIOR RIOS VASQUEZ','JR 2 DE MAYO 1080','RIVASCORP.1784@GMAIL.COM','',NULL,'2026-05-09 13:47:34'),(3,NULL,'1','46852096','PIERO JUNIOR RIOS VASQUEZ','JR 2 DE MAYO 1080','RIVASCORP.1784@GMAIL.COM','',NULL,'2026-05-10 12:05:55'),(4,NULL,'1','46080180','MATHEWS JARAMILLO GUSTAVO','S/D','','',NULL,'2026-05-10 12:42:00'),(5,NULL,'1','70485446','Juanita Sadith Panduro Villasis','Jr. poma rosa Mz 12 Lt 05','','',NULL,'2026-05-10 13:00:05'),(6,NULL,'1','41099499','CARLOS EDUARDO MONTENEGRO VERASTEGUI','S/D','','',NULL,'2026-05-15 14:51:06'),(7,NULL,'1','00002902','SILVA SALAZAR ZOILA','','','',NULL,'2026-05-19 12:14:40'),(8,NULL,'6','20615763374','CREACIONES HUERTA E.I.R.L.','JR. INDEPENDENCIA NRO 666 INT. 3 URB. CERCADO DE PUCALLPA','','',NULL,'2026-05-19 12:18:40');
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cola_impresion`
--

LOCK TABLES `cola_impresion` WRITE;
/*!40000 ALTER TABLE `cola_impresion` DISABLE KEYS */;
INSERT INTO `cola_impresion` VALUES (1,1,56,'comanda_anular','{\"numero_orden\":\"00056\",\"mesa\":\"Mesa 4\",\"mozo\":\"Carlos Rivera\",\"observacion\":\"\",\"fecha\":\"20\\/05\\/2026 13:45\",\"items\":[{\"cantidad\":1,\"nombre\":\"CERVEZA CUZQUEÑA TRIGO\",\"nota\":\"\"}]}','impreso',0,NULL,'2026-05-20 13:45:37','2026-05-20 14:01:00'),(2,1,56,'comanda_anular','{\"numero_orden\":\"00056\",\"mesa\":\"Mesa 4\",\"mozo\":\"Carlos Rivera\",\"observacion\":\"\",\"fecha\":\"20\\/05\\/2026 14:01\",\"items\":[]}','impreso',0,NULL,'2026-05-20 14:01:48','2026-05-20 14:01:51'),(3,1,56,'comanda','{\"numero_orden\":\"00056\",\"mesa\":\"Mesa 4\",\"mozo\":\"Carlos Rivera\",\"observacion\":\"\",\"fecha\":\"20\\/05\\/2026 14:03\",\"items\":[{\"cantidad\":1,\"nombre\":\"ARROZ CON MARISCOS\",\"nota\":\"\"}]}','impreso',0,NULL,'2026-05-20 14:03:59','2026-05-20 14:04:00'),(4,1,56,'comanda','{\"numero_orden\":\"00056\",\"mesa\":\"Mesa 4\",\"mozo\":\"Carlos Rivera\",\"observacion\":\"\",\"fecha\":\"20\\/05\\/2026 14:04\",\"items\":[{\"cantidad\":1,\"nombre\":\"AGUA PERSONAL (PLASTICO  S\\/GAS MAIJA 500ML)\",\"nota\":\"\"},{\"cantidad\":1,\"nombre\":\"ARROZ CON MARISCOS\",\"nota\":\"\"},{\"cantidad\":1,\"nombre\":\"ARROZ BLANCO\",\"nota\":\"\"}]}','impreso',0,NULL,'2026-05-20 14:04:21','2026-05-20 14:04:21'),(5,1,58,'comanda','{\"numero_orden\":\"00058\",\"mesa\":\"Mesa 1\",\"mozo\":\"Cajero\",\"observacion\":\"\",\"fecha\":\"20\\/05\\/2026 14:08\",\"items\":[{\"cantidad\":1,\"nombre\":\"CAUSA ACEVICHADA\",\"nota\":\"\"},{\"cantidad\":2,\"nombre\":\"AGUA PERSONAL (PLASTICO  S\\/GAS MAIJA 500ML)\",\"nota\":\"\"}]}','impreso',0,NULL,'2026-05-20 14:08:35','2026-05-20 14:08:36'),(6,1,58,'comanda','{\"numero_orden\":\"00058\",\"mesa\":\"Mesa 1\",\"mozo\":\"Cajero\",\"observacion\":\"\",\"fecha\":\"20\\/05\\/2026 14:14\",\"items\":[{\"cantidad\":1,\"nombre\":\"AGUA PERSONAL (PLASTICO  S\\/GAS MAIJA 500ML)\",\"nota\":\"\"},{\"cantidad\":1,\"nombre\":\"ARROZ BLANCO\",\"nota\":\"\"}]}','impreso',0,NULL,'2026-05-20 14:14:40','2026-05-20 14:15:14'),(7,1,58,'comanda','{\"numero_orden\":\"00058\",\"mesa\":\"Mesa 1\",\"mozo\":\"Cajero\",\"observacion\":\"\",\"fecha\":\"20\\/05\\/2026 14:15\",\"items\":[{\"cantidad\":1,\"nombre\":\"ARROZ CON MARISCOS\",\"nota\":\"\"}]}','impreso',0,NULL,'2026-05-20 14:15:49','2026-05-20 14:15:51'),(8,1,58,'comanda_anular','{\"numero_orden\":\"00058\",\"mesa\":\"Mesa 1\",\"mozo\":\"Cajero\",\"observacion\":\"\",\"fecha\":\"20\\/05\\/2026 14:15\",\"items\":[{\"cantidad\":2,\"nombre\":\"AGUA PERSONAL (PLASTICO  S\\/GAS MAIJA 500ML)\",\"nota\":\"\"}]}','impreso',0,NULL,'2026-05-20 14:15:49','2026-05-20 14:15:51'),(9,1,59,'comanda','{\"numero_orden\":\"00059\",\"mesa\":\"Mesa 2\",\"mozo\":\"Cajero\",\"observacion\":\"\",\"fecha\":\"20\\/05\\/2026 14:19\",\"items\":[{\"cantidad\":1,\"nombre\":\"AGUA PERSONAL (PLASTICO  S\\/GAS MAIJA 500ML)\",\"nota\":\"\"},{\"cantidad\":1,\"nombre\":\"CAUSA ACEVICHADA\",\"nota\":\"\"},{\"cantidad\":1,\"nombre\":\"ARROZ CHAUFA\",\"nota\":\"\"}]}','impreso',0,NULL,'2026-05-20 14:19:59','2026-05-20 14:20:47'),(10,1,67,'comanda','{\"numero_orden\":\"00067\",\"mesa\":\"Mesa 1\",\"mozo\":\"Cajero\",\"observacion\":\"\",\"fecha\":\"02\\/06\\/2026 11:02\",\"items\":[{\"cantidad\":2,\"nombre\":\"ARROZ BLANCO\",\"nota\":\"\"}]}','pendiente',0,NULL,'2026-06-02 11:02:01',NULL),(11,1,67,'comanda_anular','{\"numero_orden\":\"00067\",\"mesa\":\"Mesa 1\",\"mozo\":\"Cajero\",\"observacion\":\"\",\"fecha\":\"02\\/06\\/2026 11:02\",\"items\":[{\"cantidad\":1,\"nombre\":\"ARROZ BLANCO\",\"nota\":\"\"}]}','pendiente',0,NULL,'2026-06-02 11:02:22',NULL),(12,1,67,'comanda','{\"numero_orden\":\"00067\",\"mesa\":\"Mesa 1\",\"mozo\":\"Cajero\",\"observacion\":\"\",\"fecha\":\"02\\/06\\/2026 11:02\",\"items\":[{\"cantidad\":1,\"nombre\":\"ARROZ CON MARISCOS\",\"nota\":\"\"}]}','pendiente',0,NULL,'2026-06-02 11:02:39',NULL),(13,1,69,'comanda','{\"numero_orden\":\"00069\",\"mesa\":\"Mesa 1\",\"mozo\":\"Cajero\",\"observacion\":\"\",\"fecha\":\"02\\/06\\/2026 17:52\",\"items\":[{\"cantidad\":1,\"nombre\":\"AGUA PERSONAL (PLASTICO  S\\/GAS MAIJA 500ML)\",\"nota\":\"\"},{\"cantidad\":1,\"nombre\":\"ARROZ CON MARISCOS\",\"nota\":\"\"},{\"cantidad\":1,\"nombre\":\"ARROZ CHAUFA\",\"nota\":\"\"}]}','pendiente',0,NULL,'2026-06-02 17:52:07',NULL),(14,1,70,'comanda','{\"numero_orden\":\"00070\",\"mesa\":\"Mesa 1\",\"mozo\":\"Cajero\",\"observacion\":\"\",\"fecha\":\"02\\/06\\/2026 19:04\",\"items\":[{\"cantidad\":1,\"nombre\":\"ARROZ BLANCO\",\"nota\":\"\"},{\"cantidad\":1,\"nombre\":\"ARROZ CHAUFA\",\"nota\":\"\"}]}','pendiente',0,NULL,'2026-06-02 19:04:01',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comprobante_detalle`
--

LOCK TABLES `comprobante_detalle` WRITE;
/*!40000 ALTER TABLE `comprobante_detalle` DISABLE KEYS */;
INSERT INTO `comprobante_detalle` VALUES (35,20,1,'24','ARROZ BLANCO','NIU',1.000,5.0000,5.0000,5.00,0.00,5.00,'20','20'),(36,20,2,'3','ARROZ CHAUFA','NIU',1.000,15.0000,15.0000,15.00,0.00,15.00,'20','20'),(37,21,1,'18','AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)','NIU',1.000,2.0000,2.0000,2.00,0.00,2.00,'20','20'),(38,22,1,'24','ARROZ BLANCO','NIU',1.000,5.0000,5.0000,5.00,0.00,5.00,'20','20'),(39,23,1,'9','Chicha Morada vaso','NIU',1.000,3.0000,3.0000,3.00,0.00,3.00,'20','20'),(40,24,1,'24','ARROZ BLANCO','NIU',1.000,5.0000,5.0000,5.00,0.00,5.00,'20','20'),(41,25,1,'24','ARROZ BLANCO','NIU',1.000,5.0000,5.0000,5.00,0.00,5.00,'20','20'),(42,26,1,'14','CEVICHE + ARROZ C/MARISCOS','NIU',2.000,15.0000,15.0000,30.00,0.00,30.00,'20','20'),(43,26,2,'16','CERVEZA SAN JUAN','NIU',2.000,9.0000,9.0000,18.00,0.00,18.00,'20','20'),(44,27,1,'18','AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)','NIU',1.000,2.0000,2.0000,2.00,0.00,2.00,'20','20'),(45,28,1,'18','AGUA PERSONAL (PLASTICO C/GAS CIELO 500 ML)','NIU',1.000,2.0000,2.0000,2.00,0.00,2.00,'20','20'),(46,29,1,'3','ARROZ CHAUFA','NIU',0.000,15.0000,15.0000,0.00,0.00,0.00,'20','20'),(47,29,2,'19','ARROZ CON MARISCOS','NIU',0.000,15.0000,15.0000,0.00,0.00,0.00,'20','20'),(48,29,3,'28','CAUSA DE POLLO','NIU',0.000,5.9000,5.9000,0.00,0.00,0.00,'20','20'),(49,29,4,'18','AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)','NIU',1.000,2.0000,2.0000,2.00,0.00,2.00,'20','20'),(50,30,1,'18','AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)','NIU',0.000,2.0000,2.0000,0.00,0.00,0.00,'20','20'),(51,30,2,'19','ARROZ CON MARISCOS','NIU',0.000,15.0000,15.0000,0.00,0.00,0.00,'20','20'),(52,30,3,'2','CEVICHE + CAUSA (PRESENTACIÓN 1)','NIU',1.000,15.0000,15.0000,15.00,0.00,15.00,'20','20'),(53,30,4,'11','COCA COLA (VIDRIO PERSONAL 296ML)','NIU',1.000,3.0000,3.0000,3.00,0.00,3.00,'20','20'),(54,31,1,'24','ARROZ BLANCO','NIU',2.000,5.0000,5.0000,10.00,0.00,10.00,'20','20'),(55,31,2,'3','ARROZ CHAUFA','NIU',1.000,15.0000,15.0000,15.00,0.00,15.00,'20','20'),(56,32,1,'24','ARROZ BLANCO','NIU',1.000,5.0000,5.0000,5.00,0.00,5.00,'20','20'),(57,32,2,'17','CERVEZA CUZQUEÑA TRIGO','NIU',1.000,10.0000,10.0000,10.00,0.00,10.00,'20','20'),(58,32,3,'16','CERVEZA SAN JUAN','NIU',1.000,9.0000,9.0000,9.00,0.00,9.00,'20','20'),(59,32,4,'28','CAUSA DE POLLO','NIU',1.000,5.9000,5.9000,5.90,0.00,5.90,'20','20'),(60,33,1,'24','ARROZ BLANCO','NIU',1.000,5.0000,5.0000,5.00,0.00,5.00,'20','20'),(61,34,1,'18','AGUA PERSONAL (PLASTICO C/GAS CIELO 500 ML)','NIU',1.000,2.0000,2.0000,2.00,0.00,2.00,'20','20'),(62,34,2,'3','ARROZ CHAUFA','NIU',1.000,15.0000,15.0000,15.00,0.00,15.00,'20','20'),(63,34,3,'19','ARROZ CON MARISCOS','NIU',1.000,15.0000,15.0000,15.00,0.00,15.00,'20','20'),(64,34,4,'7','CAUSA ACEVICHADA','NIU',1.000,15.0000,15.0000,15.00,0.00,15.00,'20','20');
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
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comprobante_electronico`
--

LOCK TABLES `comprobante_electronico` WRITE;
/*!40000 ALTER TABLE `comprobante_electronico` DISABLE KEYS */;
INSERT INTO `comprobante_electronico` VALUES (20,1,36,NULL,NULL,NULL,NULL,NULL,NULL,5,2,'03','B001','00000019','B001-00000019','2026-05-14 00:30:47','PEN','0101','1','70485446','Juanita Sadith Panduro Villasis','Jr. poma rosa Mz 12 Lt 05','',20.00,0.00,20.00,0.00,0.00,0.00,20.00,0.1800,'SON: VEINTI CON 00/100 SOLES','efectivo','10721109297-03-B001-19.xml','C:\\xampp\\htdocs\\YAPEZ/sfs/firma/10721109297-03-B001-19.xml','FDY2ZknePHD8xJWjYwO4v+UF3H0=','C:\\xampp\\htdocs\\YAPEZ/sfs/envio/10721109297-03-B001-19.zip',NULL,NULL,'1033','El comprobante fue registrado previamente con otros datos - Detalle: xxx.xxx.xxx value=\'ticket: 202620735442762 error: El comprobante B001-19 fue informado anteriormente\'','rechazado',1,'2026-05-15 10:17:44','2026-05-15 10:17:45',NULL,NULL),(21,1,40,NULL,NULL,NULL,NULL,NULL,NULL,5,1,'03','B001','00000021','B001-00000021','2026-05-14 09:46:49','PEN','0101','1','70485446','Juanita Sadith Panduro Villasis','Jr. poma rosa Mz 12 Lt 05','',2.00,0.00,2.00,0.00,0.00,0.00,2.00,0.1800,'SON: DOS CON 00/100 SOLES','efectivo','10721109297-03-B001-21.xml','C:\\xampp\\htdocs\\YAPEZ/sfs/firma/10721109297-03-B001-21.xml','j5F0dAK3qXanWtWQD9N266gnfpI=','C:\\xampp\\htdocs\\YAPEZ/sfs/envio/10721109297-03-B001-21.zip','C:\\xampp\\htdocs\\YAPEZ/sfs/unziprpta/R-10721109297-03-B001-21.xml',NULL,'0','La Boleta numero B001-21, ha sido aceptada','aceptado',1,'2026-05-14 09:47:05','2026-05-14 09:47:06',NULL,NULL),(22,1,41,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'03','B001','00000022','B001-00000022','2026-05-14 09:53:36','PEN','0101','1','71053039','roy frank','JR pachitea 643','royespinoza27@gmail.com',5.00,0.00,5.00,0.00,0.00,0.00,5.00,0.1800,'SON: CINCO CON 00/100 SOLES','efectivo','10721109297-03-B001-22.xml','C:\\xampp\\htdocs\\YAPEZ/sfs/firma/10721109297-03-B001-22.xml','X0Q4ZEVJ3/TEG6wlekf4ZdoxQ1s=','C:\\xampp\\htdocs\\YAPEZ/sfs/envio/10721109297-03-B001-22.zip','C:\\xampp\\htdocs\\YAPEZ/sfs/unziprpta/R-10721109297-03-B001-22.xml',NULL,'0','La Boleta numero B001-22, ha sido aceptada','aceptado',3,'2026-05-14 14:31:40','2026-05-14 14:31:41',NULL,NULL),(23,1,42,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'03','B001','00000023','B001-00000023','2026-05-14 09:57:09','PEN','0101','1','71053039','ROY FRANK ESPINOZA PINEDO','JR pachitea 643','royespinoza27@gmail.com',3.00,0.00,3.00,0.00,0.00,0.00,3.00,0.1800,'SON: TRES CON 00/100 SOLES','efectivo','10721109297-03-B001-23.xml','C:\\xampp\\htdocs\\YAPEZ/sfs/firma/10721109297-03-B001-23.xml','UIwiWPfvw74aTLNSM2q1pMBUd5U=','C:\\xampp\\htdocs\\YAPEZ/sfs/envio/10721109297-03-B001-23.zip','C:\\xampp\\htdocs\\YAPEZ/sfs/unziprpta/R-10721109297-03-B001-23.xml',NULL,'0','La Boleta numero B001-23, ha sido aceptada','aceptado',4,'2026-05-14 09:57:26','2026-05-14 10:44:35',NULL,NULL),(24,1,43,NULL,NULL,NULL,NULL,NULL,NULL,5,1,'03','B001','00000024','B001-00000024','2026-05-14 14:56:29','PEN','0101','1','70485446','Juanita Sadith Panduro Villasis','Jr. poma rosa Mz 12 Lt 05','',5.00,0.00,5.00,0.00,0.00,0.00,5.00,0.1800,'SON: CINCO CON 00/100 SOLES','efectivo','10721109297-03-B001-24.xml','C:\\xampp\\htdocs\\YAPEZ/sfs/firma/10721109297-03-B001-24.xml','V0xVm83KrnYgADDpFGguWdbq6m4=','C:\\xampp\\htdocs\\YAPEZ/sfs/envio/10721109297-03-B001-24.zip','C:\\xampp\\htdocs\\YAPEZ/sfs/unziprpta/R-10721109297-03-B001-24.xml',NULL,'0','La Boleta numero B001-24, ha sido aceptada','aceptado',1,'2026-05-14 20:40:09','2026-05-14 20:40:11',NULL,NULL),(25,1,44,NULL,NULL,NULL,NULL,NULL,NULL,5,1,'03','B001','00000025','B001-00000025','2026-05-15 09:57:00','PEN','0101','1','70485446','Juanita Sadith Panduro Villasis','Jr. poma rosa Mz 12 Lt 05','',5.00,0.00,5.00,0.00,0.00,0.00,5.00,0.1800,'SON: CINCO CON 00/100 SOLES','efectivo','10721109297-03-B001-25.xml','C:\\xampp\\htdocs\\YAPEZ/sfs/firma/10721109297-03-B001-25.xml','UQ5FSzZKF17lfTCuFhy6XT0Vwkk=','C:\\xampp\\htdocs\\YAPEZ/sfs/envio/10721109297-03-B001-25.zip','C:\\xampp\\htdocs\\YAPEZ/sfs/unziprpta/R-10721109297-03-B001-25.xml',NULL,'0','La Boleta numero B001-25, ha sido aceptada','aceptado',1,'2026-05-15 10:18:08','2026-05-15 10:18:09',NULL,NULL),(26,1,45,NULL,NULL,NULL,NULL,NULL,NULL,6,1,'03','B001','00000026','B001-00000026','2026-05-15 14:51:06','PEN','0101','1','41099499','CARLOS EDUARDO MONTENEGRO VERASTEGUI','S/D','',48.00,0.00,48.00,0.00,0.00,0.00,48.00,0.1800,'SON: CUARENTA Y OCHO CON 00/100 SOLES','efectivo','10721109297-03-B001-26.xml','C:\\xampp\\htdocs\\YAPEZ/sfs/firma/10721109297-03-B001-26.xml','FrQM/Y3jXv/jM/loSO77GaILbJY=','C:\\xampp\\htdocs\\YAPEZ/sfs/envio/10721109297-03-B001-26.zip','C:\\xampp\\htdocs\\YAPEZ/sfs/unziprpta/R-10721109297-03-B001-26.xml',NULL,'0','La Boleta numero B001-26, ha sido aceptada','aceptado',1,'2026-05-15 14:52:26','2026-05-15 14:52:28',NULL,NULL),(27,1,49,NULL,NULL,NULL,NULL,NULL,NULL,5,1,'03','B001','00000027','B001-00000027','2026-05-17 16:02:22','PEN','0101','1','70485446','Juanita Sadith Panduro Villasis','Jr. poma rosa Mz 12 Lt 05','',2.00,0.00,2.00,0.00,0.00,0.00,2.00,0.1800,'SON: DOS CON 00/100 SOLES','efectivo','10721109297-03-B001-27.xml','C:\\xampp\\htdocs\\YAPEZ/sfs/firma/10721109297-03-B001-27.xml','CebbAGBBp4Cxy625IrQ8pZxAqMY=','C:\\xampp\\htdocs\\YAPEZ/sfs/envio/10721109297-03-B001-27.zip','C:\\xampp\\htdocs\\YAPEZ/sfs/unziprpta/R-10721109297-03-B001-27.xml',NULL,'0','La Boleta numero B001-27, ha sido aceptada','aceptado',1,'2026-05-17 16:06:31','2026-05-17 16:06:32',NULL,NULL),(28,1,50,NULL,NULL,NULL,NULL,NULL,NULL,5,1,'03','B001','00000028','B001-00000028','2026-05-17 16:07:18','PEN','0101','1','70485446','Juanita Sadith Panduro Villasis','Jr. poma rosa Mz 12 Lt 05','',2.00,0.00,2.00,0.00,0.00,0.00,2.00,0.1800,'SON: DOS CON 00/100 SOLES','efectivo','10721109297-03-B001-28.xml','C:\\xampp\\htdocs\\YAPEZ/sfs/firma/10721109297-03-B001-28.xml','P7pXcPYPxxGBg2bZoDKaFel2658=','C:\\xampp\\htdocs\\YAPEZ/sfs/envio/10721109297-03-B001-28.zip','C:\\xampp\\htdocs\\YAPEZ/sfs/unziprpta/R-10721109297-03-B001-28.xml',NULL,'0','La Boleta numero B001-28, ha sido aceptada','aceptado',1,'2026-05-18 14:10:00','2026-05-18 14:10:02',NULL,NULL),(29,1,53,NULL,NULL,NULL,NULL,NULL,NULL,3,1,'03','B001','00000029','B001-00000029','2026-05-18 14:22:43','PEN','0101','1','46852096','PIERO JUNIOR RIOS VASQUEZ','','',2.00,0.00,2.00,0.00,0.00,0.00,2.00,0.1800,'SON: DOS CON 00/100 SOLES','efectivo','10721109297-03-B001-29.xml','C:\\xampp\\htdocs\\YAPEZ/sfs/firma/10721109297-03-B001-29.xml','Wj2nP+SIQXfmdCCjegQZAvY5/QY=','C:\\xampp\\htdocs\\YAPEZ/sfs/envio/10721109297-03-B001-29.zip','C:\\xampp\\htdocs\\YAPEZ/sfs/unziprpta/R-10721109297-03-B001-29.xml',NULL,'2024','El XML no contiene el tag InvoicedQuantity en el detalle de los Items o es cero (0) - El XML no contiene el tag InvoicedQuantity en el detalle de los Items o es cero (0) Detalle: xxx.xxx.xxx value=\'ticket: 202620764283285 error: Error en la linea:1. : 2024 (nodo: \"cac:InvoiceLine/cbc:InvoicedQuantity\" valor: \"0.00\")\'','aceptado',1,'2026-05-18 14:24:51','2026-05-18 14:24:52',NULL,NULL),(30,1,58,NULL,NULL,NULL,NULL,NULL,NULL,3,1,'03','B001','00000030','B001-00000030','2026-05-21 12:13:00','PEN','0101','1','46852096','PIERO JUNIOR RIOS VASQUEZ','JR 2 DE MAYO 1080','RIVASCORP.1784@GMAIL.COM',18.00,0.00,18.00,0.00,0.00,0.00,18.00,0.1800,'SON: DIECIOCHO CON 00/100 SOLES','efectivo','20607027685-03-B001-30.xml','C:\\xampp\\htdocs\\puerto_habana/sfs/firma/20607027685-03-B001-30.xml','lPnxkrSHCx2Joo14Lr+WHwpkPXE=','C:\\xampp\\htdocs\\puerto_habana/sfs/envio/20607027685-03-B001-30.zip',NULL,NULL,'2024','El XML no contiene el tag InvoicedQuantity en el detalle de los Items o es cero (0) - Detalle: xxx.xxx.xxx value=\'ticket: 1780251667564 error: Error en la linea:1. : 2024 (nodo: \"cac:InvoiceLine/cbc:InvoicedQuantity\" valor: \"0.00\")\'','rechazado',2,'2026-05-31 13:20:58','2026-05-31 13:20:58',NULL,NULL),(31,1,60,NULL,NULL,NULL,NULL,NULL,NULL,3,1,'03','B001','00000031','B001-00000031','2026-05-31 13:19:50','PEN','0101','1','46852096','PIERO JUNIOR RIOS VASQUEZ','JR 2 DE MAYO 1080','RIVASCORP.1784@GMAIL.COM',25.00,0.00,25.00,0.00,0.00,0.00,25.00,0.1800,'SON: VEINTICINCO CON 00/100 SOLES','efectivo','20607027685-03-B001-31.xml','C:\\xampp\\htdocs\\puerto_habana/sfs/firma/20607027685-03-B001-31.xml','S/K6C4oWx89YOGTS3Lije+qsNVo=','C:\\xampp\\htdocs\\puerto_habana/sfs/envio/20607027685-03-B001-31.zip','C:\\xampp\\htdocs\\puerto_habana/sfs/unziprpta/R-20607027685-03-B001-31.xml',NULL,'0','La Boleta numero B001-31, ha sido aceptada','aceptado',1,'2026-05-31 13:20:48','2026-05-31 13:20:49',NULL,NULL),(32,1,64,NULL,NULL,NULL,NULL,NULL,NULL,3,1,'03','B001','00000032','B001-00000032','2026-05-31 16:01:36','PEN','0101','1','46852096','PIERO JUNIOR RIOS VASQUEZ','JR 2 DE MAYO 1080','RIVASCORP.1784@GMAIL.COM',29.90,0.00,0.00,0.00,0.00,0.00,29.90,0.0000,'SON: VEINTINUEVE CON 90/100 SOLES','efectivo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pendiente',0,NULL,NULL,NULL,NULL),(33,1,65,NULL,NULL,NULL,NULL,NULL,NULL,3,1,'03','B001','00000033','B001-00000033','2026-05-31 17:02:00','PEN','0101','1','46852096','PIERO JUNIOR RIOS VASQUEZ','JR 2 DE MAYO 1080','RIVASCORP.1784@GMAIL.COM',5.00,0.00,0.00,0.00,0.00,0.00,5.00,0.0000,'SON: CINCO CON 00/100 SOLES','efectivo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pendiente',0,NULL,NULL,NULL,NULL),(34,1,66,NULL,NULL,NULL,NULL,NULL,NULL,5,1,'03','B001','00000034','B001-00000034','2026-05-31 17:57:38','PEN','0101','1','70485446','Juanita Sadith Panduro Villasis','Jr. poma rosa Mz 12 Lt 05','',47.00,0.00,0.00,0.00,0.00,0.00,47.00,0.0000,'SON: CUARENTA Y SIETE CON 00/100 SOLES','yape',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pendiente',0,NULL,NULL,NULL,NULL);
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresa`
--

LOCK TABLES `empresa` WRITE;
/*!40000 ALTER TABLE `empresa` DISABLE KEYS */;
INSERT INTO `empresa` VALUES (1,'10429025546','6','PONCE BERNEDO MARCO ANTONIO','PUERTO HABANA CEVICHERIA','AV. COLONIZACION 1115 - FRENTE AL COLEGIO AGROPECUARIO','250101','ucayali','coronel portillo','calleria','PE','979459608','poncebernedom@gmail.com','','public/img/logo_1_5a5bd3da.png','ticket','GEISEN17','admin123','beta','2.1','2.0',0.0000,'S/','PEN',1,'{\"numero_ruc\": \"10058676735\", \"razon_social\": \"JUANA DE JESUS VILLASIS GONZALES\", \"nombre_comercial\": \"CEVICHERIA YAPEZ\", \"domicilio_fiscal\": \"Av. Demostracion 123 - Lima\", \"usuario_sol\": \"JUANA1784\", \"clave_sol\": \"Juana1784\", \"ambiente\": \"produccion\", \"idcert_activo\": 3}','3');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `impresora`
--

LOCK TABLES `impresora` WRITE;
/*!40000 ALTER TABLE `impresora` DISABLE KEYS */;
INSERT INTO `impresora` VALUES (1,'COCINA','192.168.100.101',9100,'cocina',48,1,1,'2026-05-20 13:45:01');
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventario_movimiento`
--

LOCK TABLES `inventario_movimiento` WRITE;
/*!40000 ALTER TABLE `inventario_movimiento` DISABLE KEYS */;
INSERT INTO `inventario_movimiento` VALUES (7,18,108,'ajuste',30.00,30.00,'Ajuste manual de inventario',1,NULL,'2026-06-02 19:53:57'),(8,18,109,'ajuste',20.00,20.00,'Ajuste manual de inventario',1,NULL,'2026-06-02 19:53:57'),(9,18,108,'entrada',10.00,40.00,'',1,NULL,'2026-06-02 19:55:04'),(10,18,108,'entrada',10.00,50.00,'',1,NULL,'2026-06-02 19:55:34'),(11,18,108,'venta',-1.00,49.00,'Venta orden #70',1,70,'2026-06-02 19:56:16'),(12,18,108,'venta',-2.00,47.00,'Venta orden #71',1,71,'2026-06-02 20:00:23');
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
INSERT INTO `licencia` VALUES (1,'Licencia Inicial','2026-05-08','2026-06-30',5,'activa','renovacion mensual','2026-05-08 00:45:36','2026-05-31 18:09:34');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licencia_historial`
--

LOCK TABLES `licencia_historial` WRITE;
/*!40000 ALTER TABLE `licencia_historial` DISABLE KEYS */;
INSERT INTO `licencia_historial` VALUES (1,1,'2026-05-31 18:09:34','extender','2026-06-07','2026-06-30',50.00,'renovacion mensual',1),(2,1,'2026-05-31 18:33:07','extender','2026-06-30','2026-06-30',50.00,'renovacion mensual',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mesa`
--

LOCK TABLES `mesa` WRITE;
/*!40000 ALTER TABLE `mesa` DISABLE KEYS */;
INSERT INTO `mesa` VALUES (1,1,1,3,1,'libre',1),(2,1,2,3,2,'libre',1),(3,1,3,4,3,'libre',1),(4,1,4,6,4,'libre',1),(5,1,5,4,5,'libre',1),(6,1,6,4,6,'libre',1),(7,1,7,4,7,'libre',1),(8,1,8,4,8,'libre',1),(9,1,9,4,9,'libre',1),(10,1,10,4,10,'libre',1),(11,1,11,4,11,'libre',1),(12,1,12,4,12,'libre',1),(13,2,13,4,13,'libre',1),(14,2,14,4,14,'libre',1),(15,2,15,4,15,'libre',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `numeracion`
--

LOCK TABLES `numeracion` WRITE;
/*!40000 ALTER TABLE `numeracion` DISABLE KEYS */;
INSERT INTO `numeracion` VALUES (1,1,'01','F001',1,'Factura electronica',1),(2,1,'03','B001',34,'Boleta de venta electronica',1),(3,1,'07','FC01',0,'Nota de Credito (referenciada a Factura)',1),(4,1,'08','FD01',0,'Nota de Debito (referenciada a Factura)',1),(5,1,'07','BC01',0,'Nota de Credito (referenciada a Boleta)',1),(6,1,'08','BD01',0,'Nota de Debito (referenciada a Boleta)',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orden`
--

LOCK TABLES `orden` WRITE;
/*!40000 ALTER TABLE `orden` DISABLE KEYS */;
INSERT INTO `orden` VALUES (1,'00001',1,NULL,1,NULL,'dine_in','anulada','Cajero',NULL,23.73,4.27,28.00,'','ticket',NULL,NULL,NULL,NULL,'2026-05-09 13:33:28',NULL),(2,'00002',2,NULL,1,NULL,'dine_in','pagada','Cajero',NULL,1.00,0.00,1.00,'efectivo','boleta',1.00,0.00,'','{}','2026-05-09 13:35:50','2026-05-09 13:36:20'),(3,'00003',1,NULL,2,NULL,'dine_in','pagada','Cajero',NULL,27.12,4.88,32.00,'efectivo','factura',32.00,0.00,'','{}','2026-05-09 13:47:08','2026-05-09 13:47:34'),(4,'00004',1,NULL,2,NULL,'dine_in','pagada','Cajero',NULL,0.85,0.15,1.00,'efectivo','boleta',1.00,0.00,'','{}','2026-05-09 14:01:52','2026-05-09 14:02:11'),(5,'00005',1,NULL,3,NULL,'dine_in','pagada','Cajero',NULL,0.85,0.15,1.00,'efectivo','boleta',1.00,0.00,'','{}','2026-05-09 14:13:21','2026-05-09 14:13:41'),(6,'00006',1,NULL,3,NULL,'para_llevar','anulada','Cajero','',0.00,0.00,0.00,'','ticket',NULL,NULL,NULL,NULL,'2026-05-09 14:56:22',NULL),(7,'00007',2,NULL,3,NULL,'dine_in','anulada','Cajero','',0.00,0.00,0.00,'','ticket',NULL,NULL,NULL,NULL,'2026-05-09 18:25:41',NULL),(8,'00008',1,NULL,4,NULL,'dine_in','pagada','Cajero','',40.00,0.00,40.00,'efectivo','boleta',50.00,10.00,'','{}','2026-05-10 12:05:09','2026-05-10 12:05:55'),(9,'00009',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,29.50,0.00,29.50,'efectivo','boleta',29.50,0.00,'','{}','2026-05-10 12:07:51','2026-05-10 12:38:29'),(10,'00010',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,40.90,0.00,40.90,'efectivo','boleta',40.90,0.00,'','{}','2026-05-10 12:40:13','2026-05-10 12:42:00'),(11,'00011',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,56.00,0.00,56.00,'efectivo','boleta',60.00,4.00,'','{}','2026-05-10 12:43:45','2026-05-10 12:46:55'),(12,'00012',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,60.00,0.00,60.00,'efectivo','boleta',60.00,0.00,'','{}','2026-05-10 12:59:19','2026-05-10 13:00:05'),(13,'00013',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,59.00,0.00,59.00,'efectivo','boleta',60.00,1.00,'','{}','2026-05-10 13:47:22','2026-05-10 13:48:59'),(14,'00014',8,NULL,4,NULL,'para_llevar','pagada','Cajero',NULL,75.00,0.00,75.00,'efectivo','boleta',75.00,0.00,'','{}','2026-05-10 14:09:26','2026-05-10 14:12:31'),(15,'00015',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,26.90,0.00,26.90,'efectivo','boleta',26.90,0.00,'','{}','2026-05-10 15:01:46','2026-05-10 15:02:20'),(16,'00016',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','boleta',2.00,0.00,'','{}','2026-05-10 17:14:26','2026-05-10 17:15:26'),(17,'00017',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,37.90,0.00,37.90,'efectivo','boleta',50.00,12.10,'','{}','2026-05-11 12:15:37','2026-05-11 13:11:25'),(18,'00018',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','boleta',2.00,0.00,'','{}','2026-05-11 14:52:40','2026-05-11 14:53:47'),(19,'00019',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','boleta',2.00,0.00,'','{}','2026-05-12 09:48:20','2026-05-12 09:48:43'),(20,'00020',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,20.00,0.00,20.00,'efectivo','boleta',20.00,0.00,'','{}','2026-05-12 17:53:53','2026-05-12 17:54:33'),(21,'00021',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','boleta',2.00,0.00,'','{}','2026-05-13 11:15:14','2026-05-13 11:15:31'),(22,'00022',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','boleta',2.00,0.00,'','{}','2026-05-13 11:50:56','2026-05-13 11:51:22'),(23,'00023',8,NULL,4,NULL,'dine_in','anulada','Cajero',NULL,40.90,0.00,40.90,'','ticket',NULL,NULL,NULL,NULL,'2026-05-13 13:50:56',NULL),(24,'00024',1,NULL,4,NULL,'dine_in','anulada','Cajero',NULL,47.00,0.00,47.00,'','ticket',NULL,NULL,NULL,NULL,'2026-05-13 13:57:53',NULL),(25,'00025',2,NULL,4,NULL,'dine_in','anulada','Cajero',NULL,0.00,0.00,0.00,'','ticket',NULL,NULL,NULL,NULL,'2026-05-13 14:06:48',NULL),(26,'00026',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,28.00,0.00,28.00,'efectivo','nota_venta',28.00,0.00,'','{}','2026-05-13 14:36:46','2026-05-13 14:56:17'),(27,'00027',2,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,15.00,0.00,15.00,'efectivo','ticket',15.00,0.00,'','{}','2026-05-13 14:51:39','2026-05-13 14:56:36'),(28,'00028',3,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,5.00,0.00,5.00,'efectivo','ticket',5.00,0.00,'','{}','2026-05-13 14:55:42','2026-05-13 14:56:52'),(29,'00029',4,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,15.00,0.00,15.00,'efectivo','nota_venta',15.00,0.00,'','{}','2026-05-13 14:55:46','2026-05-13 14:57:33'),(30,'00030',5,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','ticket',2.00,0.00,'','{}','2026-05-13 14:55:50','2026-05-13 14:57:41'),(31,'00031',1,NULL,4,NULL,'dine_in','pagada','Cajero',NULL,9.00,0.00,9.00,'efectivo','ticket',9.00,0.00,'','{}','2026-05-13 14:57:48','2026-05-13 14:57:55'),(32,'00032',1,NULL,4,NULL,'dine_in','pagada','Cajero','',22.00,0.00,22.00,'efectivo','ticket',22.00,0.00,'','{}','2026-05-13 15:09:39','2026-05-13 19:04:20'),(33,'00033',15,NULL,6,NULL,'dine_in','anulada','Cajero',NULL,0.00,0.00,0.00,'','ticket',NULL,NULL,NULL,NULL,'2026-05-13 22:04:43',NULL),(34,'00034',1,NULL,6,NULL,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','ticket',2.00,0.00,'','{}','2026-05-13 22:30:01','2026-05-13 22:44:12'),(35,'00035',3,NULL,6,NULL,'dine_in','pagada','Cajero',NULL,5.00,0.00,5.00,'efectivo','ticket',5.00,0.00,'','{}','2026-05-13 22:42:56','2026-05-13 22:43:51'),(36,'00036',4,NULL,6,NULL,'dine_in','pagada','Cajero',NULL,20.00,0.00,20.00,'efectivo','boleta',20.00,0.00,'','{}','2026-05-13 23:04:52','2026-05-14 00:30:47'),(37,'00037',1,NULL,7,NULL,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','ticket',2.00,0.00,'','{}','2026-05-14 00:45:33','2026-05-14 00:45:38'),(38,'00038',5,NULL,7,NULL,'dine_in','pagada','Cajero',NULL,10.00,0.00,10.00,'yape','ticket',10.00,0.00,'Yape/Plin S/ 10.00','{\"monto\":10}','2026-05-14 00:46:08','2026-05-14 00:46:18'),(39,'00039',3,NULL,7,NULL,'dine_in','pagada','Cajero',NULL,24.00,0.00,24.00,'','ticket',24.00,0.00,'Mixto · Efectivo S/ 20.00 + Yape S/ 4.00','{\"efectivo\":20,\"yape\":4}','2026-05-14 00:46:27','2026-05-14 00:46:56'),(40,'00040',1,NULL,7,NULL,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','boleta',2.00,0.00,'','{}','2026-05-14 09:42:28','2026-05-14 09:46:49'),(41,'00041',1,NULL,7,NULL,'dine_in','pagada','Cajero',NULL,5.00,0.00,5.00,'efectivo','boleta',5.00,0.00,'','{}','2026-05-14 09:53:05','2026-05-14 09:53:36'),(42,'00042',1,NULL,7,NULL,'dine_in','pagada','Cajero',NULL,3.00,0.00,3.00,'efectivo','boleta',3.00,0.00,'','{}','2026-05-14 09:56:38','2026-05-14 09:57:09'),(43,'00043',1,NULL,7,NULL,'dine_in','pagada','Cajero',NULL,5.00,0.00,5.00,'efectivo','boleta',5.00,0.00,'','{}','2026-05-14 10:56:25','2026-05-14 14:56:29'),(44,'00044',1,NULL,7,NULL,'dine_in','pagada','Cajero',NULL,5.00,0.00,5.00,'efectivo','boleta',5.00,0.00,'','{}','2026-05-14 18:11:31','2026-05-15 09:57:00'),(45,'00045',1,NULL,7,NULL,'dine_in','pagada','Cajero',NULL,48.00,0.00,48.00,'efectivo','boleta',48.00,0.00,'','{}','2026-05-15 14:49:40','2026-05-15 14:51:06'),(46,'00046',1,NULL,7,NULL,'dine_in','anulada','Cajero',NULL,94.00,0.00,94.00,'','ticket',NULL,NULL,NULL,NULL,'2026-05-15 15:26:19',NULL),(47,'00047',1,NULL,7,NULL,'dine_in','anulada','Cajero',NULL,10.00,0.00,10.00,'','ticket',NULL,NULL,NULL,NULL,'2026-05-15 15:39:02',NULL),(48,'00048',1,NULL,7,NULL,'dine_in','anulada','Cajero',NULL,0.00,0.00,0.00,'','ticket',NULL,NULL,NULL,NULL,'2026-05-16 13:20:21',NULL),(49,'00049',1,NULL,7,1,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','boleta',2.00,0.00,'','{}','2026-05-17 14:55:50','2026-05-17 16:02:22'),(50,'00050',6,NULL,7,1,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','boleta',2.00,0.00,'','{}','2026-05-17 16:06:57','2026-05-17 16:07:18'),(51,'00051',1,NULL,7,1,'dine_in','pagada','Cajero',NULL,63.00,0.00,63.00,'efectivo','ticket',63.00,0.00,'','{}','2026-05-17 16:57:53','2026-05-18 08:55:31'),(52,'00052',1,NULL,7,1,'dine_in','pagada','Admin YAPEZ',NULL,45.00,0.00,45.00,'efectivo','ticket',45.00,0.00,'','{}','2026-05-18 08:57:50','2026-05-18 09:11:23'),(53,'00053',1,NULL,7,1,'dine_in','pagada','Admin YAPEZ',NULL,2.00,0.00,2.00,'efectivo','boleta',2.00,0.00,'','{}','2026-05-18 09:14:41','2026-05-18 14:22:43'),(54,'00054',2,NULL,7,3,'para_llevar','pagada','Carlos Rivera','',30.00,0.00,30.00,'efectivo','ticket',30.00,0.00,'','{}','2026-05-18 09:16:20','2026-05-18 19:16:28'),(55,'00055',3,NULL,7,3,'dine_in','pagada','Cajero',NULL,5.00,0.00,5.00,'efectivo','ticket',5.00,0.00,'','{}','2026-05-18 09:17:29','2026-05-18 19:16:43'),(56,'00056',4,NULL,7,3,'dine_in','anulada','Carlos Rivera','',54.00,0.00,54.00,'','ticket',NULL,NULL,NULL,NULL,'2026-05-18 09:22:26',NULL),(57,'00057',1,NULL,7,1,'dine_in','pagada','Cajero',NULL,2.00,0.00,2.00,'efectivo','ticket',2.00,0.00,'','{}','2026-05-18 19:12:32','2026-05-18 19:15:24'),(58,'00058',1,NULL,7,1,'dine_in','pagada','Cajero',NULL,18.00,0.00,18.00,'efectivo','boleta',18.00,0.00,'','{}','2026-05-19 12:08:34','2026-05-21 12:13:00'),(59,'00059',2,NULL,7,1,'dine_in','anulada','Cajero',NULL,32.00,0.00,32.00,'','ticket',NULL,NULL,NULL,NULL,'2026-05-20 14:19:55',NULL),(60,'00060',1,NULL,7,1,'dine_in','pagada','Cajero',NULL,21.19,3.81,25.00,'efectivo','boleta',25.00,0.00,'','{}','2026-05-31 13:03:41','2026-05-31 13:19:50'),(61,'00061',1,NULL,7,1,'dine_in','pagada','Cajero',NULL,16.95,3.05,20.00,'efectivo','nota_venta',20.00,0.00,'','{}','2026-05-31 13:30:30','2026-05-31 13:30:36'),(62,'00062',1,NULL,7,1,'dine_in','pagada','Cajero',NULL,16.00,0.00,16.00,'efectivo','nota_venta',16.00,0.00,'','{}','2026-05-31 13:31:19','2026-05-31 13:31:26'),(63,'00063',1,NULL,7,1,'dine_in','pagada','Cajero',NULL,7.00,0.00,7.00,'efectivo','nota_venta',7.00,0.00,'','{}','2026-05-31 16:00:43','2026-05-31 16:00:53'),(64,'00064',1,NULL,7,1,'dine_in','pagada','Cajero',NULL,29.90,0.00,29.90,'efectivo','boleta',50.00,20.10,'','{}','2026-05-31 16:01:11','2026-05-31 16:01:36'),(65,'00065',1,NULL,7,1,'dine_in','pagada','Cajero',NULL,5.00,0.00,5.00,'efectivo','boleta',5.00,0.00,'','{}','2026-05-31 17:01:40','2026-05-31 17:02:00'),(66,'00066',1,NULL,7,1,'dine_in','pagada','Cajero',NULL,47.00,0.00,47.00,'yape','boleta',47.00,0.00,'Yape/Plin S/ 47.00','{\"monto\":47}','2026-05-31 17:57:12','2026-05-31 17:57:38'),(67,'00067',1,NULL,8,1,'dine_in','pagada','Cajero',NULL,20.00,0.00,20.00,'efectivo','nota_venta',20.00,0.00,'','{}','2026-06-02 11:00:53','2026-06-02 11:04:11'),(68,'00068',1,NULL,8,1,'dine_in','pagada','Cajero',NULL,56.00,0.00,56.00,'efectivo','nota_venta',56.00,0.00,'','{}','2026-06-02 11:18:55','2026-06-02 17:50:37'),(69,'00069',1,NULL,8,1,'dine_in','pagada','Cajero',NULL,30.00,0.00,30.00,'efectivo','nota_venta',30.00,0.00,'','{}','2026-06-02 17:52:00','2026-06-02 19:02:17'),(70,'00070',1,NULL,8,1,'dine_in','pagada','Cajero',NULL,22.00,0.00,22.00,'efectivo','nota_venta',22.00,0.00,'','{}','2026-06-02 19:03:52','2026-06-02 19:56:16'),(71,'00071',1,NULL,8,1,'dine_in','pagada','Cajero',NULL,4.00,0.00,4.00,'efectivo','nota_venta',4.00,0.00,'','{}','2026-06-02 19:59:52','2026-06-02 20:00:23');
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
  `nota` varchar(200) DEFAULT NULL,
  `estado` enum('pendiente','en_preparacion','listo','servido','anulado') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`iddetalle`),
  KEY `idx_detalle_orden` (`idorden`),
  KEY `idx_detalle_producto` (`idproducto`),
  CONSTRAINT `fk_detalle_orden` FOREIGN KEY (`idorden`) REFERENCES `orden` (`idorden`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`idproducto`) REFERENCES `producto` (`idproducto`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orden_detalle`
--

LOCK TABLES `orden_detalle` WRITE;
/*!40000 ALTER TABLE `orden_detalle` DISABLE KEYS */;
INSERT INTO `orden_detalle` VALUES (4,2,15,NULL,'AGUA',1.00,0.00,1.00,1.00,'','pendiente'),(5,1,1,NULL,'Lomo Saltado',1.00,1.00,28.00,28.00,'','en_preparacion'),(6,3,2,NULL,'Arroz con Mariscos',1.00,0.00,32.00,32.00,'','pendiente'),(7,4,15,NULL,'AGUA',1.00,0.00,1.00,1.00,'','pendiente'),(8,5,15,NULL,'AGUA',1.00,0.00,1.00,1.00,'','pendiente'),(19,8,1,NULL,'CEVICHE SIMPLE (PRESENTACIÓN 2)',2.00,0.00,20.00,40.00,'','pendiente'),(20,9,28,NULL,'CAUSA DE POLLO',5.00,0.00,5.90,29.50,'','pendiente'),(21,10,1,NULL,'CEVICHE SIMPLE (PRESENTACIÓN 2)',1.00,0.00,20.00,20.00,'','pendiente'),(22,10,8,NULL,'LECHE DE TIGRE',1.00,0.00,15.00,15.00,'','pendiente'),(23,10,28,NULL,'CAUSA DE POLLO',1.00,0.00,5.90,5.90,'','pendiente'),(24,11,12,NULL,'CEVICHE + CHAUFA + CAUSA + CHICHARRON',2.00,0.00,28.00,56.00,'','pendiente'),(25,12,5,NULL,'CHICHARRON (D/PESCADO)',1.00,0.00,20.00,20.00,'','pendiente'),(26,12,22,NULL,'CEVICHE D/PESCADO',2.00,0.00,20.00,40.00,'','pendiente'),(27,13,19,NULL,'ARROZ CON MARISCOS',1.00,0.00,15.00,15.00,'','pendiente'),(28,13,16,NULL,'CERVEZA SAN JUAN',1.00,0.00,9.00,9.00,'','pendiente'),(29,13,8,NULL,'LECHE DE TIGRE',1.00,0.00,15.00,15.00,'','pendiente'),(30,13,1,NULL,'CEVICHE SIMPLE (PRESENTACIÓN 2)',1.00,0.00,20.00,20.00,'','pendiente'),(31,14,10,NULL,'FANTA (VIDRIO RETORNABLE 1 1/2 LT)',1.00,0.00,15.00,15.00,'','pendiente'),(32,14,1,NULL,'CEVICHE SIMPLE (PRESENTACIÓN 2)',1.00,0.00,20.00,20.00,'','pendiente'),(33,14,5,NULL,'CHICHARRON (D/PESCADO)',1.00,0.00,20.00,20.00,'','pendiente'),(34,14,17,NULL,'CERVEZA CUZQUEÑA TRIGO',1.00,0.00,10.00,10.00,'','pendiente'),(35,14,15,NULL,'INKA COLA (VIDRIO RETORNABLE 1LT)',1.00,0.00,10.00,10.00,'','pendiente'),(36,15,28,NULL,'CAUSA DE POLLO',1.00,0.00,5.90,5.90,'','pendiente'),(37,15,15,NULL,'INKA COLA (GORDITA 625 ML)',1.00,0.00,6.00,6.00,'','pendiente'),(38,15,3,NULL,'ARROZ CHAUFA',1.00,0.00,15.00,15.00,'','pendiente'),(39,16,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(40,17,18,NULL,'AGUA PERSONAL (PLASTICO C/GAS CIELO 500 ML)',1.00,0.00,2.00,2.00,'','pendiente'),(41,17,17,NULL,'CERVEZA CUZQUEÑA TRIGO',1.00,0.00,10.00,10.00,'','pendiente'),(42,17,28,NULL,'CAUSA DE POLLO',1.00,0.00,5.90,5.90,'','pendiente'),(43,17,1,NULL,'CEVICHE SIMPLE (PRESENTACIÓN 2)',1.00,0.00,20.00,20.00,'','pendiente'),(44,18,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(45,19,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(46,20,1,NULL,'CEVICHE SIMPLE (PRESENTACIÓN 2)',1.00,0.00,20.00,20.00,'','pendiente'),(47,21,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(48,22,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(49,23,6,NULL,'CEVICHE + ARROZ C/MARISCOS + CHICHARRON (PRESENTACIÓN 2)',1.00,1.00,20.00,20.00,'','en_preparacion'),(50,23,28,NULL,'CAUSA DE POLLO',1.00,1.00,5.90,5.90,'','en_preparacion'),(51,23,19,NULL,'ARROZ CON MARISCOS',1.00,1.00,15.00,15.00,'','en_preparacion'),(52,24,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,1.00,2.00,2.00,'','en_preparacion'),(53,24,7,NULL,'CAUSA ACEVICHADA',1.00,1.00,15.00,15.00,'','en_preparacion'),(58,24,3,NULL,'ARROZ CHAUFA',1.00,1.00,15.00,15.00,'','en_preparacion'),(59,24,19,NULL,'ARROZ CON MARISCOS',1.00,1.00,15.00,15.00,'','en_preparacion'),(63,28,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(64,29,3,NULL,'ARROZ CHAUFA',1.00,0.00,15.00,15.00,'','pendiente'),(65,30,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(66,27,14,NULL,'CEVICHE + ARROZ C/MARISCOS',1.00,0.00,15.00,15.00,'','pendiente'),(67,26,20,NULL,'CEVICHE + ARROZ C/MARISCOS + CAUSA + CHICHARRON',1.00,0.00,28.00,28.00,'','pendiente'),(68,31,16,NULL,'CERVEZA SAN JUAN',1.00,0.00,9.00,9.00,'','pendiente'),(69,32,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(70,32,1,NULL,'CEVICHE SIMPLE (PRESENTACIÓN 2)',1.00,0.00,20.00,20.00,'','pendiente'),(72,34,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(73,35,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(74,36,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(75,36,3,NULL,'ARROZ CHAUFA',1.00,0.00,15.00,15.00,'','pendiente'),(76,37,18,NULL,'AGUA PERSONAL (PLASTICO C/GAS CIELO 500 ML)',1.00,0.00,2.00,2.00,'','pendiente'),(77,38,24,NULL,'ARROZ BLANCO',2.00,0.00,5.00,10.00,'','pendiente'),(78,39,3,NULL,'ARROZ CHAUFA',1.00,0.00,15.00,15.00,'','pendiente'),(79,39,16,NULL,'CERVEZA SAN JUAN',1.00,0.00,9.00,9.00,'','pendiente'),(80,40,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(81,41,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(82,42,9,NULL,'Chicha Morada vaso',1.00,0.00,3.00,3.00,'','pendiente'),(85,43,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(88,44,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(89,45,14,NULL,'CEVICHE + ARROZ C/MARISCOS',2.00,0.00,15.00,30.00,'','pendiente'),(90,45,16,NULL,'CERVEZA SAN JUAN',2.00,0.00,9.00,18.00,'','pendiente'),(91,46,8,NULL,'LECHE DE TIGRE',1.00,1.00,15.00,15.00,'','en_preparacion'),(92,46,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,1.00,2.00,2.00,'','en_preparacion'),(93,46,3,NULL,'ARROZ CHAUFA',3.00,3.00,15.00,45.00,'','en_preparacion'),(94,46,19,NULL,'ARROZ CON MARISCOS',1.00,1.00,15.00,15.00,'','en_preparacion'),(95,46,21,NULL,'CEVICHE + ARROZ CHAUFA + CHICHARRON (PRESENTACIÓN 1)',1.00,1.00,15.00,15.00,'','en_preparacion'),(96,46,18,NULL,'AGUA PERSONAL (PLASTICO C/GAS CIELO 500 ML)',1.00,1.00,2.00,2.00,'','en_preparacion'),(97,47,24,NULL,'ARROZ BLANCO',2.00,2.00,5.00,10.00,'','en_preparacion'),(107,49,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,1.00,2.00,2.00,'','en_preparacion'),(108,50,18,NULL,'AGUA PERSONAL (PLASTICO C/GAS CIELO 500 ML)',1.00,0.00,2.00,2.00,'','pendiente'),(110,51,5,NULL,'CHICHARRON (D/POTA)',1.00,1.00,15.00,15.00,'','en_preparacion'),(111,51,20,NULL,'CEVICHE + ARROZ C/MARISCOS + CAUSA + CHICHARRON',1.00,1.00,28.00,28.00,'','en_preparacion'),(112,51,6,NULL,'CEVICHE + ARROZ C/MARISCOS + CHICHARRON (PRESENTACIÓN 2)',1.00,1.00,20.00,20.00,'','en_preparacion'),(113,52,3,NULL,'ARROZ CHAUFA',1.00,1.00,15.00,15.00,'','en_preparacion'),(114,52,19,NULL,'ARROZ CON MARISCOS',1.00,1.00,15.00,15.00,'','en_preparacion'),(115,52,7,NULL,'CAUSA ACEVICHADA',1.00,1.00,15.00,15.00,'','en_preparacion'),(116,53,3,NULL,'ARROZ CHAUFA',0.00,1.00,15.00,0.00,'','en_preparacion'),(117,53,19,NULL,'ARROZ CON MARISCOS',0.00,1.00,15.00,0.00,'','en_preparacion'),(119,53,28,NULL,'CAUSA DE POLLO',0.00,1.00,5.90,0.00,'','en_preparacion'),(122,56,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',2.00,2.00,2.00,4.00,'','en_preparacion'),(123,56,19,NULL,'ARROZ CON MARISCOS',3.00,3.00,15.00,45.00,'','en_preparacion'),(127,54,19,NULL,'ARROZ CON MARISCOS',1.00,1.00,15.00,15.00,'','en_preparacion'),(128,54,7,NULL,'CAUSA ACEVICHADA',1.00,1.00,15.00,15.00,'','en_preparacion'),(129,53,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(133,57,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(134,55,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(137,58,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',0.00,1.00,2.00,0.00,'','en_preparacion'),(138,56,24,NULL,'ARROZ BLANCO',1.00,1.00,5.00,5.00,'','en_preparacion'),(140,58,19,NULL,'ARROZ CON MARISCOS',0.00,1.00,15.00,0.00,'','en_preparacion'),(141,59,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,1.00,2.00,2.00,'','en_preparacion'),(142,59,7,NULL,'CAUSA ACEVICHADA',1.00,1.00,15.00,15.00,'','en_preparacion'),(143,59,3,NULL,'ARROZ CHAUFA',1.00,1.00,15.00,15.00,'','en_preparacion'),(144,58,2,NULL,'CEVICHE + CAUSA (PRESENTACIÓN 1)',1.00,0.00,15.00,15.00,'','pendiente'),(145,58,11,NULL,'COCA COLA (VIDRIO PERSONAL 296ML)',1.00,0.00,3.00,3.00,'','pendiente'),(146,60,24,NULL,'ARROZ BLANCO',2.00,0.00,5.00,10.00,'','pendiente'),(147,60,3,NULL,'ARROZ CHAUFA',1.00,0.00,15.00,15.00,'','pendiente'),(148,61,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(149,61,19,NULL,'ARROZ CON MARISCOS',1.00,0.00,15.00,15.00,'','pendiente'),(150,62,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(151,62,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(152,62,16,NULL,'CERVEZA SAN JUAN',1.00,0.00,9.00,9.00,'','pendiente'),(153,63,18,NULL,'AGUA PERSONAL (PLASTICO C/GAS CIELO 500 ML)',1.00,0.00,2.00,2.00,'','pendiente'),(154,63,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(155,64,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(156,64,17,NULL,'CERVEZA CUZQUEÑA TRIGO',1.00,0.00,10.00,10.00,'','pendiente'),(157,64,16,NULL,'CERVEZA SAN JUAN',1.00,0.00,9.00,9.00,'','pendiente'),(158,64,28,NULL,'CAUSA DE POLLO',1.00,0.00,5.90,5.90,'','pendiente'),(159,65,24,NULL,'ARROZ BLANCO',1.00,0.00,5.00,5.00,'','pendiente'),(160,66,18,NULL,'AGUA PERSONAL (PLASTICO C/GAS CIELO 500 ML)',1.00,0.00,2.00,2.00,'','pendiente'),(161,66,3,NULL,'ARROZ CHAUFA',1.00,0.00,15.00,15.00,'','pendiente'),(162,66,19,NULL,'ARROZ CON MARISCOS',1.00,0.00,15.00,15.00,'','pendiente'),(163,66,7,NULL,'CAUSA ACEVICHADA',1.00,0.00,15.00,15.00,'','pendiente'),(164,67,24,NULL,'ARROZ BLANCO',1.00,1.00,5.00,5.00,'','en_preparacion'),(165,67,19,NULL,'ARROZ CON MARISCOS',1.00,1.00,15.00,15.00,'','en_preparacion'),(166,68,20,NULL,'CEVICHE + ARROZ C/MARISCOS + CAUSA + CHICHARRON',1.00,0.00,28.00,28.00,'','pendiente'),(167,68,12,NULL,'CEVICHE + CHAUFA + CAUSA + CHICHARRON',1.00,0.00,28.00,28.00,'','pendiente'),(168,69,18,NULL,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',0.00,1.00,2.00,0.00,'','en_preparacion'),(169,69,19,NULL,'ARROZ CON MARISCOS',1.00,1.00,15.00,15.00,'','en_preparacion'),(170,69,3,NULL,'ARROZ CHAUFA',0.00,1.00,15.00,0.00,'','en_preparacion'),(171,69,7,NULL,'CAUSA ACEVICHADA',1.00,0.00,15.00,15.00,'','pendiente'),(172,70,24,NULL,'ARROZ BLANCO',1.00,1.00,5.00,5.00,'','en_preparacion'),(173,70,3,NULL,'ARROZ CHAUFA',1.00,1.00,15.00,15.00,'','en_preparacion'),(174,70,18,108,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',1.00,0.00,2.00,2.00,'','pendiente'),(175,71,18,108,'AGUA PERSONAL (PLASTICO  S/GAS MAIJA 500ML)',2.00,0.00,2.00,4.00,'','pendiente');
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
INSERT INTO `producto` VALUES (1,'CS02','CEVICHE SIMPLE',16.00,'20',1,'../public/img/productos/p1_68065b329b22.jpg',0,0,1,0,0.00,0.00),(2,'CC02','CEVICHE + CAUSA',15.00,'20',1,'../public/img/productos/p2_1a337df232a2.jpg',0,0,1,0,0.00,0.00),(3,'AC01','ARROZ CHAUFA',15.00,'20',5,'../public/img/productos/p3_ea2ffa4aae50.jpg',0,0,1,0,0.00,0.00),(4,'DM03','CEVICHE + CHICHARRON',20.00,'20',2,'../public/img/productos/p4_c65134a19aea.jpg',0,0,1,0,0.00,0.00),(5,'CH01','CHICHARRON',15.00,'20',4,'../public/img/productos/p5_d5512857ce44.jpg',0,0,1,0,0.00,0.00),(6,'TM01','CEVICHE + ARROZ C/MARISCOS + CHICHARRON',15.00,'20',3,'../public/img/productos/p6_542ed235ad30.jpg',0,0,1,0,0.00,0.00),(7,'CA01','CAUSA ACEVICHADA',15.00,'20',1,'../public/img/productos/p7_12da79353f8a.jpg',0,0,1,0,0.00,0.00),(8,'LT01','LECHE DE TIGRE',15.00,'20',1,'../public/img/productos/p8_fbf4f24eefe6.jpg',0,0,1,0,0.00,0.00),(9,'CM3','Chicha Morada vaso',3.00,'20',6,'../public/img/productos/p9_7300b63f087b.jpg',0,0,1,0,0.00,0.00),(10,'FP01','FANTA',3.00,'20',6,'../public/img/productos/p10_3e287f995fce.png',0,0,1,0,0.00,0.00),(11,'CC01','COCA COLA',3.00,'20',6,'../public/img/productos/p11_98ccb1c2b3e7.jpg',0,0,1,0,0.00,0.00),(12,'RM01','CEVICHE + CHAUFA + CAUSA + CHICHARRON',28.00,'20',7,'../public/img/productos/p12_5f011913e895.jpg',0,0,1,0,0.00,0.00),(13,'DM02','CEVICHE + ARROZ CHAUFA',25.00,'20',2,'../public/img/productos/p13_7c097ca35958.jpg',0,0,1,0,0.00,0.00),(14,'DM01','CEVICHE + ARROZ C/MARISCOS',15.00,'20',2,'../public/img/productos/p14_019b435adc92.jpg',0,0,1,0,0.00,0.00),(15,'IK01','INKA COLA',3.00,'20',6,'../public/img/productos/p15_462e99584316.jpg',0,0,1,0,0.00,0.00),(16,'CS01','CERVEZA SAN JUAN',9.00,'20',6,'../public/img/productos/4e456c604cb7.jpg',0,0,1,0,0.00,0.00),(17,'CT01','CERVEZA CUZQUEÑA TRIGO',10.00,'20',6,'../public/img/productos/873b23bb27cc.jpg',0,0,1,0,0.00,0.00),(18,'AP01','AGUA PERSONAL',2.00,'20',6,'../public/img/productos/2e8f880f2d99.jpg',0,0,1,0,0.00,0.00),(19,'AM01','ARROZ CON MARISCOS',15.00,'20',5,'../public/img/productos/9c5eef37170d.jpg',0,0,1,0,0.00,0.00),(20,'RM02','CEVICHE + ARROZ C/MARISCOS + CAUSA + CHICHARRON',28.00,'20',7,'../public/img/productos/28aa3a8da1d4.jpg',0,0,1,0,0.00,0.00),(21,'TM02','CEVICHE + ARROZ CHAUFA + CHICHARRON',15.00,'20',3,'../public/img/productos/1a5313920099.jpg',0,0,1,0,0.00,0.00),(22,'CP01','CEVICHE D/PESCADO',20.00,'20',1,'../public/img/productos/51d612459460.jpg',0,0,1,0,0.00,0.00),(23,'CM01','CEVICHE MIXTO',30.00,'20',1,'../public/img/productos/959474466db7.jpg',0,0,1,0,0.00,0.00),(24,'AB01','ARROZ BLANCO',5.00,'20',5,'../public/img/productos/44fa9c001e01.jpg',0,0,1,0,0.00,0.00),(28,'CDP1','CAUSA DE POLLO',5.90,'20',1,'../public/img/productos/3a74225b714b.jpg',0,0,1,0,0.00,0.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_precio`
--

LOCK TABLES `producto_precio` WRITE;
/*!40000 ALTER TABLE `producto_precio` DISABLE KEYS */;
INSERT INTO `producto_precio` VALUES (35,9,'vaso',3.00,1,1,1,0,0.00,0.00),(65,17,'VIDRIO',10.00,1,1,1,0,0.00,0.00),(68,3,'MARISCOS',15.00,1,1,1,0,0.00,0.00),(69,19,'MARISCOS',15.00,1,1,1,0,0.00,0.00),(70,5,'D/POTA',15.00,1,1,1,0,0.00,0.00),(71,5,'D/PESCADO',20.00,0,2,1,0,0.00,0.00),(73,20,'Normal',28.00,1,1,1,0,0.00,0.00),(74,12,'Normal',28.00,1,1,1,0,0.00,0.00),(97,11,'VIDRIO PERSONAL 296ML',3.00,1,1,1,0,0.00,0.00),(98,11,'PLASTICO PERSONAL 600ML',5.00,0,2,1,0,0.00,0.00),(99,11,'VIDRIO RETORNABLE 1 LT',10.00,0,3,1,0,0.00,0.00),(100,11,'VIDRIO RETORNABLE 1 1/2 LT',17.30,0,4,1,0,0.00,0.00),(101,15,'VIDRIO PERSONAL 296 ML',3.00,1,1,1,0,0.00,0.00),(102,15,'PLASTICO PERSONAL 600 ML',5.00,0,2,1,0,0.00,0.00),(103,15,'GORDITA 625 ML',6.00,0,3,1,0,0.00,0.00),(104,15,'VIDRIO RETORNABLE 1LT',10.00,0,4,1,0,0.00,0.00),(105,15,'VIDRIO RETORNABLE 1 1/2 LT',18.00,0,5,1,0,0.00,0.00),(106,10,'VIDRIO PERSONAL 296 ML',3.00,1,1,1,0,0.00,0.00),(107,10,'VIDRIO RETORNABLE 1 1/2 LT',15.00,0,2,1,0,0.00,0.00),(108,18,'PLASTICO  S/GAS MAIJA 500ML',2.00,1,1,1,1,47.00,5.00),(109,18,'PLASTICO C/GAS CIELO 500 ML',2.00,0,2,1,1,20.00,5.00),(110,16,'VIDRIO 620 ML',9.00,1,1,1,0,0.00,0.00),(112,24,'PORCION',5.00,1,1,1,0,0.00,0.00),(113,21,'PRESENTACIÓN 1',15.00,1,1,1,0,0.00,0.00),(114,21,'PRESENTACIÓN 2',20.00,0,2,1,0,0.00,0.00),(115,21,'PRESENTACIÓN 3',25.00,0,3,1,0,0.00,0.00),(116,6,'PRESENTACIÓN 1',15.00,1,1,1,0,0.00,0.00),(117,6,'PRESENTACIÓN 2',20.00,0,2,1,0,0.00,0.00),(118,6,'PRESENTACIÓN 3',24.90,0,3,1,0,0.00,0.00),(119,14,'PRESENTACIÓN 1',15.00,1,1,1,0,0.00,0.00),(120,13,'PRESENTACIÓN 1',25.00,1,1,1,0,0.00,0.00),(121,4,'PRESENTACIÓN 1',20.00,1,1,1,0,0.00,0.00),(122,23,'PRESENTACIÓN 1',30.00,1,1,1,0,0.00,0.00),(123,22,'PRESENTACIÓN 1',20.00,1,1,1,0,0.00,0.00),(124,8,'PRESENTACIÓN 1',15.00,1,1,1,0,0.00,0.00),(125,7,'PRESENTACIÓN 1',15.00,1,1,1,0,0.00,0.00),(126,2,'PRESENTACIÓN 1',15.00,1,1,1,0,0.00,0.00),(127,2,'PRESENTACIÓN 2',20.00,0,2,1,0,0.00,0.00),(128,1,'PRESENTACIÓN 1',15.00,1,1,1,0,0.00,0.00),(129,1,'PRESENTACIÓN 2',20.00,0,2,1,0,0.00,0.00),(130,28,'PRESENTACIÓN 1',5.90,1,1,1,0,0.00,0.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rutas`
--

LOCK TABLES `rutas` WRITE;
/*!40000 ALTER TABLE `rutas` DISABLE KEYS */;
INSERT INTO `rutas` VALUES (1,1,'../sfs/data/','../sfs/firma/','../sfs/envio/','../sfs/rpta/','../sfs/unziprpta/','../sfs/baja/','../sfs/resumen/','../comprobantesPDF/');
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
) ENGINE=InnoDB AUTO_INCREMENT=272 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seguridad_log`
--

LOCK TABLES `seguridad_log` WRITE;
/*!40000 ALTER TABLE `seguridad_log` DISABLE KEYS */;
INSERT INTO `seguridad_log` VALUES (1,'2026-05-08 00:46:34','login_ok',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(2,'2026-05-08 00:46:47','logout',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(3,'2026-05-08 00:46:52','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(4,'2026-05-08 00:48:14','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(5,'2026-05-08 00:55:23','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(6,'2026-05-08 01:02:20','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(7,'2026-05-08 01:10:57','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(8,'2026-05-08 01:11:44','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(9,'2026-05-08 01:19:04','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(10,'2026-05-08 01:20:09','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(11,'2026-05-08 01:20:39','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(12,'2026-05-08 01:21:22','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(13,'2026-05-08 01:22:15','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(14,'2026-05-08 09:49:51','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(15,'2026-05-08 09:51:31','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(16,'2026-05-08 10:01:48','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(17,'2026-05-08 10:03:15','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(18,'2026-05-08 10:04:19','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(19,'2026-05-08 10:08:32','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(20,'2026-05-08 10:32:54','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(21,'2026-05-08 10:34:35','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(22,'2026-05-08 10:53:27','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(23,'2026-05-08 11:11:35','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(24,'2026-05-08 11:12:14','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(25,'2026-05-08 11:12:44','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(26,'2026-05-08 11:13:49','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(27,'2026-05-08 11:15:30','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(28,'2026-05-08 11:15:49','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(29,'2026-05-08 11:17:06','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(30,'2026-05-08 11:26:23','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(31,'2026-05-08 11:26:35','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(32,'2026-05-08 11:34:43','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(33,'2026-05-08 11:38:41','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(34,'2026-05-08 11:50:48','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(35,'2026-05-09 13:12:45','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(36,'2026-05-09 13:26:52','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(37,'2026-05-09 13:26:57','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(38,'2026-05-09 13:29:53','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(39,'2026-05-09 13:31:31','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(40,'2026-05-09 13:31:36','login_ok',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(41,'2026-05-09 13:32:25','logout',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(42,'2026-05-09 13:32:30','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(43,'2026-05-09 13:32:39','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(44,'2026-05-09 13:32:48','login_ok',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(45,'2026-05-09 13:33:00','logout',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(46,'2026-05-09 13:33:09','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(47,'2026-05-09 13:40:08','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(48,'2026-05-09 13:43:33','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(49,'2026-05-09 13:45:32','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(50,'2026-05-09 13:45:40','login_ok',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(51,'2026-05-09 13:46:07','logout',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(52,'2026-05-09 13:46:13','login_ok',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(53,'2026-05-09 13:46:31','logout',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(54,'2026-05-09 13:46:37','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(55,'2026-05-09 13:59:37','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(56,'2026-05-09 14:00:44','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(57,'2026-05-09 14:07:30','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(58,'2026-05-09 14:10:58','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(59,'2026-05-09 14:11:29','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(60,'2026-05-09 14:12:04','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(61,'2026-05-09 14:12:14','login_ok',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(62,'2026-05-09 14:15:52','login_ok',2,'cajero','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(63,'2026-05-09 14:16:03','login_ok',2,'cajero','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(64,'2026-05-09 14:16:43','logout',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(65,'2026-05-09 14:16:47','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(66,'2026-05-09 14:24:33','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(67,'2026-05-09 14:24:40','login_ok',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(68,'2026-05-09 14:56:37','logout',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(69,'2026-05-09 15:21:57','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(70,'2026-05-09 15:22:34','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(71,'2026-05-09 15:23:06','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(72,'2026-05-09 17:59:35','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(73,'2026-05-09 17:59:40','login_ok',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(74,'2026-05-09 18:01:27','logout',2,'cajero','::1','Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36',''),(75,'2026-05-09 18:01:33','login_ok',2,'cajero','::1','Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36',''),(76,'2026-05-09 18:03:30','logout',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(77,'2026-05-09 18:04:20','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(78,'2026-05-09 20:38:27','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(79,'2026-05-09 20:39:31','login_ok',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(80,'2026-05-09 20:40:02','logout',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(81,'2026-05-09 20:43:18','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(82,'2026-05-10 00:18:16','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(83,'2026-05-10 09:43:09','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(84,'2026-05-10 12:04:32','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(85,'2026-05-10 12:34:38','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(86,'2026-05-10 12:35:00','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(87,'2026-05-10 12:36:14','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(88,'2026-05-10 12:36:18','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(89,'2026-05-10 13:21:02','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(90,'2026-05-10 13:41:36','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(91,'2026-05-10 13:41:50','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(92,'2026-05-10 13:43:03','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(93,'2026-05-10 13:43:07','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(94,'2026-05-10 14:24:15','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(95,'2026-05-10 15:00:08','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(96,'2026-05-11 12:15:23','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(97,'2026-05-11 14:52:27','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(98,'2026-05-11 14:57:22','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(99,'2026-05-11 17:02:47','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(100,'2026-05-12 09:47:35','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(101,'2026-05-12 17:53:14','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(102,'2026-05-12 17:56:05','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',''),(103,'2026-05-13 11:15:05','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(104,'2026-05-13 11:34:34','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(105,'2026-05-13 11:34:37','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(106,'2026-05-13 12:01:31','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(107,'2026-05-13 12:01:48','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; es-ES) WindowsPowerShell/5.1.19041.7181',''),(108,'2026-05-13 13:58:42','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(109,'2026-05-13 13:58:48','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(110,'2026-05-13 14:07:36','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(111,'2026-05-13 14:10:37','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(112,'2026-05-13 14:16:47','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(113,'2026-05-13 14:16:52','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(114,'2026-05-13 14:17:35','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(115,'2026-05-13 14:17:37','login_ok',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(116,'2026-05-13 14:19:48','logout',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(117,'2026-05-13 14:19:52','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(118,'2026-05-13 14:21:14','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(119,'2026-05-13 14:21:20','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(120,'2026-05-13 14:27:42','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(121,'2026-05-13 14:27:45','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(122,'2026-05-13 14:38:50','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(123,'2026-05-13 14:46:08','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(124,'2026-05-13 15:57:19','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(125,'2026-05-13 18:36:16','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(126,'2026-05-13 19:31:13','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(127,'2026-05-13 19:31:16','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(128,'2026-05-13 20:06:32','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(129,'2026-05-13 20:06:34','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(130,'2026-05-13 22:01:05','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(131,'2026-05-13 22:03:46','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(132,'2026-05-13 22:07:13','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(133,'2026-05-13 22:07:16','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(134,'2026-05-13 22:10:59','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(135,'2026-05-13 22:11:02','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(136,'2026-05-13 23:04:38','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(137,'2026-05-14 00:17:43','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(138,'2026-05-14 00:17:43','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(139,'2026-05-14 00:17:48','login_ok',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(140,'2026-05-14 00:21:29','logout',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(141,'2026-05-14 00:21:33','login_ok',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(142,'2026-05-14 00:44:53','logout',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(143,'2026-05-14 00:45:12','login_ok',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(144,'2026-05-14 09:27:59','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(145,'2026-05-14 11:30:09','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(146,'2026-05-14 18:07:52','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(147,'2026-05-14 18:08:03','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(148,'2026-05-14 20:39:49','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(149,'2026-05-15 09:46:56','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(150,'2026-05-15 09:58:06','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(151,'2026-05-15 10:00:43','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(152,'2026-05-15 10:03:38','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(153,'2026-05-15 10:05:13','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(154,'2026-05-15 10:50:26','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(155,'2026-05-15 11:25:25','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(156,'2026-05-15 11:34:21','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(157,'2026-05-15 11:35:02','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(158,'2026-05-15 11:37:04','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(159,'2026-05-15 11:42:06','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(160,'2026-05-15 11:48:24','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(161,'2026-05-15 11:52:27','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(162,'2026-05-15 12:16:42','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(163,'2026-05-15 12:16:47','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(164,'2026-05-15 13:07:53','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(165,'2026-05-15 13:07:58','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(166,'2026-05-15 15:19:50','login_ok',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(167,'2026-05-15 15:32:55','login_ok',1,'admin','192.168.18.28','Mozilla/5.0 (Android 15; Mobile; rv:150.0) Gecko/150.0 Firefox/150.0',''),(168,'2026-05-15 15:33:43','logout',1,'admin','192.168.18.28','Mozilla/5.0 (Android 15; Mobile; rv:150.0) Gecko/150.0 Firefox/150.0',''),(169,'2026-05-15 15:33:49','login_ok',3,'mozo','192.168.18.28','Mozilla/5.0 (Android 15; Mobile; rv:150.0) Gecko/150.0 Firefox/150.0',''),(170,'2026-05-15 15:39:32','logout',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(171,'2026-05-15 15:39:36','login_ok',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(172,'2026-05-15 15:40:41','logout',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(173,'2026-05-15 15:40:48','login_ok',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(174,'2026-05-15 15:47:11','logout',3,'mozo','192.168.18.28','Mozilla/5.0 (Android 15; Mobile; rv:150.0) Gecko/150.0 Firefox/150.0',''),(175,'2026-05-15 15:49:13','logout',2,'cajero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(176,'2026-05-15 18:24:41','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(177,'2026-05-15 18:50:00','login_ok',3,'mozo','192.168.18.28','Mozilla/5.0 (Android 15; Mobile; rv:150.0) Gecko/150.0 Firefox/150.0',''),(178,'2026-05-16 12:17:29','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(179,'2026-05-16 13:00:08','login_fallido',NULL,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','Credenciales invalidas'),(180,'2026-05-16 13:00:13','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(181,'2026-05-16 13:05:38','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(182,'2026-05-16 13:05:48','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(183,'2026-05-16 13:17:59','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(184,'2026-05-16 13:18:05','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(185,'2026-05-16 13:18:17','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(186,'2026-05-16 13:18:23','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(187,'2026-05-16 13:19:42','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(188,'2026-05-16 13:19:47','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(189,'2026-05-16 17:15:42','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(190,'2026-05-16 17:15:51','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(191,'2026-05-16 17:16:03','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(192,'2026-05-16 17:16:10','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(193,'2026-05-16 17:17:45','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(194,'2026-05-17 14:54:58','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(195,'2026-05-17 16:08:46','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(196,'2026-05-17 16:08:51','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(197,'2026-05-17 16:20:21','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(198,'2026-05-17 16:57:36','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(199,'2026-05-17 17:09:36','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(200,'2026-05-17 20:19:27','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(201,'2026-05-17 20:21:25','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(202,'2026-05-18 08:43:34','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(203,'2026-05-18 08:49:58','login_ok',1,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(204,'2026-05-18 08:57:13','logout',1,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(205,'2026-05-18 08:57:19','login_fallido',NULL,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36','Credenciales invalidas'),(206,'2026-05-18 08:57:27','login_ok',1,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(207,'2026-05-18 08:58:24','logout',1,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(208,'2026-05-18 08:58:41','login_fallido',NULL,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36','Credenciales invalidas'),(209,'2026-05-18 08:58:51','login_ok',1,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(210,'2026-05-18 09:02:27','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(211,'2026-05-18 09:02:29','logout',1,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(212,'2026-05-18 09:02:41','login_fallido',NULL,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36','Credenciales invalidas'),(213,'2026-05-18 09:02:47','login_ok',1,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(214,'2026-05-18 09:03:18','logout',1,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(215,'2026-05-18 09:03:46','login_ok',1,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(216,'2026-05-18 09:10:15','login_ok',1,'admin','10.28.1.188','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(217,'2026-05-18 09:10:36','logout',1,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(218,'2026-05-18 09:10:43','login_ok',1,'admin','10.28.1.252','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(219,'2026-05-18 09:14:16','logout',1,'admin','10.28.1.188','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(220,'2026-05-18 09:14:21','login_ok',1,'admin','10.28.1.188','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(221,'2026-05-18 09:15:47','logout',1,'admin','10.28.1.188','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(222,'2026-05-18 09:15:56','login_ok',3,'mozo','10.28.1.188','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(223,'2026-05-18 09:17:13','login_ok',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(224,'2026-05-18 09:17:39','logout',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(225,'2026-05-18 09:17:50','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(226,'2026-05-18 09:18:58','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(227,'2026-05-18 09:19:03','logout',3,'mozo','10.28.1.188','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(228,'2026-05-18 09:19:08','login_ok',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(229,'2026-05-18 09:22:17','login_ok',3,'mozo','10.28.1.188','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(230,'2026-05-18 09:27:42','logout',3,'mozo','10.28.1.188','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36',''),(231,'2026-05-18 09:29:39','logout',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(232,'2026-05-18 12:06:05','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(233,'2026-05-18 13:16:34','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(234,'2026-05-18 14:09:42','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(235,'2026-05-18 19:11:53','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(236,'2026-05-19 12:08:04','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(237,'2026-05-19 12:52:51','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(238,'2026-05-19 12:58:55','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(239,'2026-05-19 14:52:02','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(240,'2026-05-19 16:01:49','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(241,'2026-05-20 13:39:06','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(242,'2026-05-20 13:39:06','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(243,'2026-05-20 13:39:20','login_ok',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(244,'2026-05-20 13:44:32','logout',3,'mozo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(245,'2026-05-20 13:44:37','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(246,'2026-05-20 15:29:30','login_ok',1,'admin','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',''),(247,'2026-05-20 15:30:16','logout',1,'admin','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',''),(248,'2026-05-21 12:09:33','login_fallido',NULL,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','Credenciales invalidas'),(249,'2026-05-21 12:09:39','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(250,'2026-05-31 11:47:03','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(251,'2026-05-31 13:35:04','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(252,'2026-05-31 13:35:06','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(253,'2026-05-31 13:37:26','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(254,'2026-05-31 13:40:09','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(255,'2026-05-31 14:08:24','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(256,'2026-05-31 14:08:26','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(257,'2026-05-31 14:15:15','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(258,'2026-05-31 14:15:17','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(259,'2026-05-31 14:37:25','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(260,'2026-05-31 16:00:30','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(261,'2026-05-31 17:01:18','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(262,'2026-05-31 17:01:20','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(263,'2026-05-31 17:57:02','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(264,'2026-05-31 17:57:04','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(265,'2026-05-31 18:09:34','licencia_extender',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','nuevo vencimiento: 2026-06-30'),(266,'2026-05-31 18:33:07','licencia_extender',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','nuevo vencimiento: 2026-06-30'),(267,'2026-05-31 18:33:35','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(268,'2026-06-02 10:56:49','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(269,'2026-06-02 17:49:09','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(270,'2026-06-02 19:26:48','logout',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',''),(271,'2026-06-02 19:26:50','login_ok',1,'admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','');
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
INSERT INTO `usuario` VALUES (1,1,'Admin','Puerto Habana','','','','admin@puertohabana.local','admin','$2y$10$xI7pAUfDGJTFAVs4SgDNPuGJB1DRrbx5PRjgvXxEf6L.W8pO2SEOy',NULL,1,0,NULL,'2026-06-02 19:26:50'),(2,2,'Juan','Perez',NULL,NULL,NULL,'cajero@puertohabana.local','cajero','$2y$10$BdDQz6afHRgbSX3aQwWD6u43Y4wN41LZPKYwCkhiRoOSAJlYQiRIq',NULL,1,0,NULL,'2026-05-15 15:40:48'),(3,3,'Carlos','Rivera','','','','mozo@puertohabana.local','mozo','$2y$10$ba4gliCw.2ywcszodlTvXuWguCvOMSNqN.WkzGGoKgomeQwrC6v7.',NULL,1,0,NULL,'2026-05-20 13:39:20');
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
INSERT INTO `usuario_permiso` VALUES (1,1,'grant'),(1,2,'grant'),(1,3,'grant'),(1,4,'grant'),(1,5,'grant'),(1,6,'grant'),(1,7,'grant'),(1,8,'grant'),(1,9,'grant'),(1,10,'grant'),(1,11,'grant'),(1,12,'grant'),(1,13,'grant'),(1,14,'grant'),(1,15,'grant'),(1,16,'grant'),(1,17,'grant'),(1,18,'grant'),(1,19,'grant'),(1,20,'grant'),(1,21,'grant'),(1,22,'grant'),(1,23,'grant'),(1,24,'grant'),(1,25,'grant'),(1,26,'grant'),(1,27,'grant'),(1,28,'grant'),(1,29,'grant'),(1,30,'grant'),(1,31,'grant'),(1,32,'grant'),(3,1,'grant'),(3,2,'grant'),(3,3,'grant'),(3,4,'grant');
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_envio`
--

LOCK TABLES `whatsapp_envio` WRITE;
/*!40000 ALTER TABLE `whatsapp_envio` DISABLE KEYS */;
INSERT INTO `whatsapp_envio` VALUES (1,NULL,NULL,6,1,'51958838916','PIERO JUNIOR RIOS VASQUEZ','46852096','Hola PIERO JUNIOR RIOS VASQUEZ ????\n\nTe enviamos tu *Boleta Electr??nica*\nN?? \nTotal: *S/ 29.50*\nDNI: 46852096\n\nGracias por tu compra en *YAPEZ POS* ????\n\nDescarga tu comprobante:\nhttp://localhost/yapez/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=6','2026-05-10 12:38:54',1,'cobro'),(2,NULL,NULL,7,1,'51907233078','MATHEWS JARAMILLO GUSTAVO','46080180','Hola MATHEWS JARAMILLO GUSTAVO ????\n\nTe enviamos tu *Boleta Electr??nica*\nN?? \nTotal: *S/ 40.90*\nDNI: 46080180\n\nGracias por tu compra en *YAPEZ POS* ????\n\nDescarga tu comprobante:\nhttp://localhost/yapez/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=7','2026-05-10 12:42:54',1,'cobro'),(3,NULL,NULL,8,1,'51907233078','MATHEWS JARAMILLO GUSTAVO','46080180','Hola MATHEWS JARAMILLO GUSTAVO\n\nTe enviamos tu *Boleta Electr??nica*\nN° \nTotal: *S/ 56.00*\nDNI: 46080180\n\n\"¡Hola! ? Muchas gracias por visitarnos hoy en YAPEZ POS. Esperamos que hayas disfrutado de nuestro sabor marino. ??\n\nTe adjunto tu boleta electrónica por el consumo realizado. ¡Que tengas un excelente día y esperamos verte pronto! ?\"\n\nDescarga tu comprobante:\nhttp://localhost/yapez/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=8','2026-05-10 12:47:29',1,'cobro'),(4,NULL,NULL,9,1,'51958838916','Juanita Sadith Panduro Villasis','70485446','Hola Juanita Sadith Panduro Villasis\n\nTe enviamos tu *Boleta Electrónica*\nN° \nTotal: *S/ 60.00*\nDNI: 70485446\n\n\"¡Hola! ? Muchas gracias por visitarnos hoy en YAPEZ POS. Esperamos que hayas disfrutado de nuestro sabor marino. ??\n\nTe adjunto tu boleta electrónica por el consumo realizado. ¡Que tengas un excelente día y esperamos verte pronto! ?\"\n\nDescarga tu comprobante:\n\nhttp://localhost/yapez/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=9','2026-05-10 13:00:37',1,'cobro'),(5,6,NULL,NULL,6,'51958838916','Juanita Sadith Panduro Villasis','70485446','¡Feliz día, Mamá! ✨ Hoy celebramos tu fuerza, tu amor y tu preferencia. Gracias por ser nuestra cliente y la verdadera inspiración de la familia.\n\n¡No cocines hoy! Ven a Cevichería Yapez y déjanos engreírte con un ceviche espectacular y el mejor ambiente. ¡Te esperamos! ??','2026-05-10 13:11:49',1,'masivo'),(6,6,NULL,NULL,6,'51958838916','Juanita Sadith Panduro Villasis','70485446','? ¡Feliz Día de la Madre Juanita Sadith Panduro Villasis! ?\n\n? En Cevichería Yapez sabemos que no hay amor más puro que el tuyo. Gracias por existir y por permitirnos ser parte de tus momentos especiales. ❤️\n\nHoy el homenajeado es tu paladar. ¡Te esperamos para celebrar como te mereces con el mejor sabor marino! ??\n\nReserva tu mesa: ? 937754575\n\n¡Te esperamos para celebrar a las mamás como se merecen!','2026-05-10 13:30:07',1,'masivo'),(7,6,NULL,NULL,6,'51958838916','Juanita Sadith Panduro Villasis','70485446','? ¡Feliz Día de la Madre Juanita Sadith Panduro Villasis! ❤\n\n? En Cevichería Yapez sabemos que no hay amor más puro que el tuyo. Gracias por existir y por permitirnos ser parte de tus momentos especiales. ❤\n\nHoy el homenajeado es tu paladar. ¡Te esperamos para celebrar como te mereces con el mejor sabor marino! ???\n\nReserva tu mesa: ✋ 937754575\n\n¡Te esperamos para celebrar a las mamás como se merecen!','2026-05-10 13:36:00',1,'masivo'),(8,6,NULL,NULL,6,'51958838916','Juanita Sadith Panduro Villasis','70485446','? ¡Feliz Día de la Madre Juanita Sadith Panduro Villasis! ❤\n\n? En Cevichería Yapez sabemos que no hay amor más puro que el tuyo. Gracias por existir y por permitirnos ser parte de tus momentos especiales. ❤\n\nHoy el homenajeado es tu paladar. ¡Te esperamos para celebrar como te mereces con el mejor sabor marino! ???\n\nReserva tu mesa: ✋ 937754575\n\n¡Te esperamos para celebrar a las mamás como se merecen!','2026-05-10 13:42:51',1,'masivo'),(9,6,NULL,NULL,6,'51958838916','Juanita Sadith Panduro Villasis','70485446','? ¡Feliz Día de la Madre Juanita Sadith Panduro Villasis! ❤♥♥\n\n? En Cevichería Yapez sabemos que no hay amor más puro que el tuyo. Gracias por existir y por permitirnos ser parte de tus momentos especiales. ❤\n\nHoy el homenajeado es tu paladar. ¡Te esperamos para celebrar como te mereces con el mejor sabor marino! ???\n\nReserva tu mesa: ✋ 937754575\n\n¡Te esperamos para celebrar a las mamás como se merecen!','2026-05-10 13:44:15',1,'masivo'),(10,6,NULL,NULL,9,'51958838916','Juanita Sadith Panduro Villasis','70485446','? ¡Hola Juanita Sadith Panduro Villasis! ?\n\nTenemos una *promoción especial* solo para nuestros clientes:\n\n[Aquí describe tu promoción]\n\n? 937754575\n? Visítanos en *YAPEZ POS*\n\n¡No te lo pierdas! ⏰','2026-05-10 13:45:54',1,'masivo'),(11,NULL,NULL,10,1,'51941825254','roy frank','71053039','Hola roy frank ?\n\nTe enviamos tu *Boleta Electrónica*\nN° \nTotal: *S/ 59.00*\nDNI: 71053039\n\n\"? Muchas gracias por visitarnos hoy en Cevichería Yapez. Esperamos que hayas disfrutado de nuestro sabor marino. ??\n\nTe adjunto tu boleta electrónica por el consumo realizado. ¡Que tengas un excelente día y esperamos verte pronto! ?\"\n\nDescarga tu comprobante:\n\nhttp://localhost/yapez/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=10','2026-05-10 13:49:48',1,'cobro'),(12,NULL,NULL,11,1,'51941825254','roy frank','71053039','Hola roy frank ?\n\nTe enviamos tu *Boleta Electrónica*\nN° \nTotal: *S/ 75.00*\nDNI: 71053039\n\n\"? Muchas gracias por visitarnos hoy en Cevichería Yapez. Esperamos que hayas disfrutado de nuestro sabor marino. ??\n\nTe adjunto tu boleta electrónica por el consumo realizado. ¡Que tengas un excelente día y esperamos verte pronto! ?\"\n\nDescarga tu comprobante:\n\nhttp://localhost/yapez/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=11','2026-05-10 14:13:04',1,'cobro'),(13,6,NULL,NULL,6,'51958838916','Juanita Sadith Panduro Villasis','70485446','? ¡Feliz Día de la Madre Juanita Sadith Panduro Villasis! ❤♥♥\n\n? En Cevichería Yapez sabemos que no hay amor más puro que el tuyo. Gracias por existir y por permitirnos ser parte de tus momentos especiales. ❤\n\nHoy el homenajeado es tu paladar. ¡Te esperamos para celebrar como te mereces con el mejor sabor marino! ???\n\nReserva tu mesa: ✋ 937754575\n\n¡Te esperamos para celebrar a las mamás como se merecen!','2026-05-10 14:13:41',1,'masivo'),(14,NULL,NULL,12,1,'51958838916','PIERO JUNIOR RIOS VASQUEZ','46852096','Hola PIERO JUNIOR RIOS VASQUEZ ?\n\nTe enviamos tu *Boleta Electrónica*\nN° \nTotal: *S/ 26.90*\nDNI: 46852096\n\n\"? Muchas gracias por visitarnos hoy en Cevichería Yapez. Esperamos que hayas disfrutado de nuestro sabor marino. ??\n\nTe adjunto tu boleta electrónica por el consumo realizado. ¡Que tengas un excelente día y esperamos verte pronto! ?\"\n\nDescarga tu comprobante:\n\nhttp://localhost/yapez/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=12','2026-05-10 15:02:36',1,'cobro'),(15,NULL,NULL,14,1,'51979086702','PIERO JUNIOR RIOS VASQUEZ','46852096','Hola PIERO JUNIOR RIOS VASQUEZ ?\n\nTe enviamos tu *Boleta Electrónica*\nN° \nTotal: *S/ 37.90*\nDNI: 46852096\n\n\"? Muchas gracias por visitarnos hoy en Cevichería Yapez. Esperamos que hayas disfrutado de nuestro sabor marino. ??\n\nTe adjunto tu boleta electrónica por el consumo realizado. ¡Que tengas un excelente día y esperamos verte pronto! ?\"\n\nDescarga tu comprobante:\n\nhttp://localhost/yapez/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=14','2026-05-11 13:12:21',1,'cobro'),(16,NULL,NULL,15,1,'51958838916','Juanita Sadith Panduro Villasis','70485446','Hola Juanita Sadith Panduro Villasis ?\n\nTe enviamos tu *Boleta Electrónica*\nN° \nTotal: *S/ 2.00*\nDNI: 70485446\n\n\"? Muchas gracias por visitarnos hoy en Cevichería Yapez. Esperamos que hayas disfrutado de nuestro sabor marino. ??\n\nTe adjunto tu boleta electrónica por el consumo realizado. ¡Que tengas un excelente día y esperamos verte pronto! ?\"\n\nDescarga tu comprobante:\n\nhttp://localhost/yapez/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=15','2026-05-11 14:53:57',1,'cobro'),(17,NULL,NULL,17,1,'51982628170','PIERO JUNIOR RIOS VASQUEZ','46852096','Hola PIERO JUNIOR RIOS VASQUEZ ?\n\nTe enviamos tu *Boleta Electrónica*\nN° \nTotal: *S/ 20.00*\nDNI: 46852096\n\n\"? Muchas gracias por visitarnos hoy en Cevichería Yapez. Esperamos que hayas disfrutado de nuestro sabor marino. ??\n\nTe adjunto tu boleta electrónica por el consumo realizado. ¡Que tengas un excelente día y esperamos verte pronto! ?\"\n\nDescarga tu comprobante:\n\nhttp://localhost/yapez/ajax/comprobante_electronico.php?op=descargarPdf&idcomprobante=17','2026-05-12 17:55:02',1,'cobro');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_plantilla`
--

LOCK TABLES `whatsapp_plantilla` WRITE;
/*!40000 ALTER TABLE `whatsapp_plantilla` DISABLE KEYS */;
INSERT INTO `whatsapp_plantilla` VALUES (1,'boleta','Boleta emitida','Hola {nombre} ?\n\nTe enviamos tu *Boleta Electrónica*\nN° {comprobante}\nTotal: *{total}*\n{tipo_doc}: {documento}\n\n\"? Muchas gracias por visitarnos hoy en Cevichería Yapez. Esperamos que hayas disfrutado de nuestro sabor marino. ??\n\nTe adjunto tu boleta electrónica por el consumo realizado. ¡Que tengas un excelente día y esperamos verte pronto! ?\"\n\nDescarga tu comprobante:\n\n{link_pdf}',1,'cobro',1,'2026-05-10 12:24:44'),(2,'factura','Factura emitida','Hola {nombre} ?\n\nTe enviamos tu *Factura Electrónica*\nN° {comprobante}\nTotal: *{total}*\nRUC: {documento}\n\nGracias por confiar en *{empresa}* ?\n\nDescarga tu comprobante:\n{link_pdf}',1,'cobro',1,'2026-05-10 12:24:44'),(3,'cumple','Cumpleaños del cliente','? ¡Feliz cumpleaños {nombre}! ?\n\nEn *{empresa}* queremos celebrar contigo.\nTe invitamos a disfrutar de un *15% de descuento* en tu próximo pedido como regalo de cumpleaños.\n\nVálido durante todo el día.\n¡Esperamos verte pronto! ?\n\n? {telefono_empresa}',0,'cumple',1,'2026-05-10 12:24:44'),(4,'navidad','Navidad','? ¡Feliz Navidad {nombre}! ✨\n\nEn *{empresa}* te deseamos paz, amor y mucha alegría junto a tu familia en estas fiestas.\n\nGracias por ser parte de nuestra familia este año ?\n\nQue el 2027 te traiga muchas bendiciones.\n\n— El equipo de {empresa}',0,'festivo',1,'2026-05-10 12:24:44'),(5,'ano_nuevo','Año Nuevo','? ¡Feliz Año Nuevo {nombre}! ?\n\nQue este nuevo año te traiga salud, prosperidad y muchos momentos felices.\n\nGracias por confiar en *{empresa}* durante el año que termina.\n\n¡Te esperamos en el 2027 con nuevos sabores y promociones! ?\n\n— Tu cevichería de confianza',0,'festivo',1,'2026-05-10 12:24:44'),(6,'madres','Día de la Madre','? ¡Feliz Día de la Madre {nombre}! ❤♥♥\n\n? En Cevichería Yapez sabemos que no hay amor más puro que el tuyo. Gracias por existir y por permitirnos ser parte de tus momentos especiales. ❤\n\nHoy el homenajeado es tu paladar. ¡Te esperamos para celebrar como te mereces con el mejor sabor marino! ???\n\nReserva tu mesa: ✋ {telefono_empresa}\n\n¡Te esperamos para celebrar a las mamás como se merecen!',0,'festivo',1,'2026-05-10 12:24:44'),(7,'padres','Día del Padre','?‍? ¡Feliz Día del Padre {nombre}! ?\n\nCelebra a papá con un buen ceviche en *{empresa}*.\nTenemos *promociones especiales* para esta fecha.\n\nReserva: ? {telefono_empresa}\n\n¡Lo esperamos para hacer de su día algo inolvidable!',0,'festivo',1,'2026-05-10 12:24:44'),(8,'patrias','Fiestas Patrias','?? ¡Felices Fiestas Patrias {nombre}! ?\n\nCelebra el cumpleaños del Perú con la mejor comida criolla en *{empresa}*.\n\n? Combos especiales por Fiestas Patrias\n? Ambiente peruano\n\n? {telefono_empresa}\n\n¡Viva el Perú!',0,'festivo',1,'2026-05-10 12:24:44'),(9,'promocion','Promoción Especial','? ¡Hola {nombre}! ?\n\nTenemos una *promoción especial* solo para nuestros clientes:\n\n[Aquí describe tu promoción]\n\n? {telefono_empresa}\n? Visítanos en *{empresa}*\n\n¡No te lo pierdas! ⏰',0,'promocion',1,'2026-05-10 12:24:44'),(10,'gracias','Gracias por tu visita','¡Gracias por visitarnos {nombre}! ?\n\nEn *{empresa}* siempre es un gusto atenderte.\n\nTu opinión es muy importante para nosotros.\n¿Cómo estuvo tu experiencia hoy?\n\n¡Te esperamos pronto! ?',0,'generico',1,'2026-05-10 12:24:44');
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
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zona`
--

LOCK TABLES `zona` WRITE;
/*!40000 ALTER TABLE `zona` DISABLE KEYS */;
INSERT INTO `zona` VALUES (1,'INTERIOR LOCAL','#5b3df5',1,1),(2,'EXTERIOR LOCAL','#f59e0b',2,1);
/*!40000 ALTER TABLE `zona` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'habana_db'
--

--
-- Dumping routines for database 'habana_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-02 20:02:35
