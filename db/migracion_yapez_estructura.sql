-- ============================================================
-- MIGRACIÓN DE ESTRUCTURA — Base de datos del cliente (yapez)
-- Solo AGREGA tablas/columnas/índices faltantes. No toca datos.
-- Idempotente: se puede ejecutar varias veces sin error.
-- Ejecutar sobre la BD del cliente (selecciónala e importa este archivo).
-- ============================================================
SET FOREIGN_KEY_CHECKS=0;

-- Helpers: agregan columna/índice solo si NO existen
DELIMITER $$
DROP PROCEDURE IF EXISTS _mig_add_col $$
CREATE PROCEDURE _mig_add_col(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl TEXT)
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=tbl COLLATE utf8_general_ci AND column_name=col COLLATE utf8_general_ci)=0 THEN
    SET @s=CONCAT('ALTER TABLE `',tbl,'` ADD COLUMN ',ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END $$
DROP PROCEDURE IF EXISTS _mig_add_idx $$
CREATE PROCEDURE _mig_add_idx(IN tbl VARCHAR(64), IN idx VARCHAR(64), IN ddl TEXT)
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=tbl COLLATE utf8_general_ci AND index_name=idx COLLATE utf8_general_ci)=0 THEN
    SET @s=CONCAT('ALTER TABLE `',tbl,'` ADD ',ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END $$
DELIMITER ;

-- Tabla nueva: inventario_movimiento
CREATE TABLE IF NOT EXISTS `inventario_movimiento` (
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
) ENGINE=InnoDB AUTO_INCREMENT=769 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- categoria
CALL _mig_add_col('categoria','mostrar_comanda','`mostrar_comanda` tinyint(1) NOT NULL DEFAULT 1 AFTER `estado`');

-- empresa
CALL _mig_add_col('empresa','yape_qr','`yape_qr` varchar(200) NULL DEFAULT NULL AFTER `logo`');
CALL _mig_add_col('empresa','plin_qr','`plin_qr` varchar(200) NULL DEFAULT NULL AFTER `yape_qr`');
CALL _mig_add_col('empresa','envio_sunat_automatico','`envio_sunat_automatico` tinyint(4) NOT NULL DEFAULT 0 AFTER `formato_comprobante`');
CALL _mig_add_col('empresa','mesas_columnas','`mesas_columnas` varchar(10) NOT NULL DEFAULT ''auto'' AFTER `backup_prod_json`');
CALL _mig_add_col('empresa','mostrar_glosa_interna','`mostrar_glosa_interna` tinyint(1) NOT NULL DEFAULT 1 AFTER `mesas_columnas`');

-- mesa
CALL _mig_add_col('mesa','orden','`orden` int(11) NOT NULL DEFAULT 0 AFTER `capacidad`');

-- orden_detalle
CALL _mig_add_col('orden_detalle','idprecio','`idprecio` int(10) unsigned NULL DEFAULT NULL AFTER `idproducto`');
CALL _mig_add_col('orden_detalle','cortesia','`cortesia` tinyint(1) NOT NULL DEFAULT 0 AFTER `subtotal`');

-- producto
CALL _mig_add_col('producto','controla_stock','`controla_stock` tinyint(4) NOT NULL DEFAULT 0 AFTER `estado`');
CALL _mig_add_col('producto','stock','`stock` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `controla_stock`');
CALL _mig_add_col('producto','stock_minimo','`stock_minimo` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `stock`');

-- producto_precio
CALL _mig_add_col('producto_precio','controla_stock','`controla_stock` tinyint(4) NOT NULL DEFAULT 0 AFTER `estado`');
CALL _mig_add_col('producto_precio','stock','`stock` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `controla_stock`');
CALL _mig_add_col('producto_precio','stock_minimo','`stock_minimo` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `stock`');

-- Limpieza de helpers
DROP PROCEDURE IF EXISTS _mig_add_col;
DROP PROCEDURE IF EXISTS _mig_add_idx;
SET FOREIGN_KEY_CHECKS=1;

-- Resumen: 1 tabla(s), 15 columna(s), 0 índice(s) agregados.