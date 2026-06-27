-- =====================================================================
-- YAPEZ POS - Activar modo BETA para pruebas SUNAT
-- Guarda config de produccion en empresa.observacion para revertir luego
-- =====================================================================
USE `yapez_db`;

-- 1) Crear columna temporal para guardar config produccion (si no existe)
DROP PROCEDURE IF EXISTS `sp_yapez_backup_prod`;
DELIMITER $$
CREATE PROCEDURE `sp_yapez_backup_prod`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresa' AND COLUMN_NAME='backup_prod_json'
    ) THEN
        ALTER TABLE `empresa` ADD COLUMN `backup_prod_json` TEXT NULL COMMENT 'config produccion respaldada al activar BETA';
    END IF;
END$$
DELIMITER ;
CALL `sp_yapez_backup_prod`();
DROP PROCEDURE IF EXISTS `sp_yapez_backup_prod`;

-- 2) Guardar config actual de PRODUCCION en backup_prod_json (solo si esta en produccion)
UPDATE `empresa`
SET `backup_prod_json` = JSON_OBJECT(
    'numero_ruc',       numero_ruc,
    'razon_social',     razon_social,
    'nombre_comercial', nombre_comercial,
    'domicilio_fiscal', domicilio_fiscal,
    'usuario_sol',      usuario_sol,
    'clave_sol',        clave_sol,
    'ambiente',         ambiente,
    'idcert_activo',    (SELECT idcertificado FROM certificado WHERE idempresa=1 AND activo=1 LIMIT 1)
)
WHERE idempresa=1 AND ambiente='produccion';

-- 3) Cambiar empresa a config DEMO de SUNAT BETA
UPDATE `empresa` SET
    numero_ruc       = '20607027685',
    razon_social     = 'YAPEZ POS DEMO E.I.R.L.',
    nombre_comercial = 'YAPEZ POS',
    usuario_sol      = 'MODDATOS',
    clave_sol        = 'moddatos',
    ambiente         = 'beta',
    tasa_igv         = 0.18
WHERE idempresa=1;

-- 4) Activar el certificado DEMO de LLAMA.PE
UPDATE `certificado` SET activo=0 WHERE idempresa=1;
UPDATE `certificado` SET activo=1
WHERE idempresa=1 AND nombre_archivo LIKE '%DEMO%' LIMIT 1;

-- Verificacion
SELECT '=== EMPRESA AHORA (BETA) ===' AS '';
SELECT numero_ruc, razon_social, ambiente, usuario_sol FROM empresa WHERE idempresa=1;
SELECT '=== CERTIFICADO ACTIVO ===' AS '';
SELECT idcertificado, nombre_archivo, tipo, activo FROM certificado WHERE idempresa=1 AND activo=1;
SELECT '=== BACKUP PRODUCCION ===' AS '';
SELECT JSON_EXTRACT(backup_prod_json, '$.numero_ruc') AS prod_ruc,
       JSON_EXTRACT(backup_prod_json, '$.usuario_sol') AS prod_usuario_sol,
       JSON_EXTRACT(backup_prod_json, '$.idcert_activo') AS prod_idcert
FROM empresa WHERE idempresa=1;
