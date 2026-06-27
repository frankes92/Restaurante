-- =====================================================================
-- YAPEZ POS - Restaurar config de PRODUCCION desde backup
-- Ejecutar cuando SUNAT confirme la afiliacion del RUC real.
-- =====================================================================
USE `yapez_db`;

-- 1) Restaurar empresa con config de produccion guardada en backup_prod_json
UPDATE `empresa`
SET numero_ruc       = JSON_UNQUOTE(JSON_EXTRACT(backup_prod_json, '$.numero_ruc')),
    razon_social     = JSON_UNQUOTE(JSON_EXTRACT(backup_prod_json, '$.razon_social')),
    nombre_comercial = JSON_UNQUOTE(JSON_EXTRACT(backup_prod_json, '$.nombre_comercial')),
    domicilio_fiscal = JSON_UNQUOTE(JSON_EXTRACT(backup_prod_json, '$.domicilio_fiscal')),
    usuario_sol      = JSON_UNQUOTE(JSON_EXTRACT(backup_prod_json, '$.usuario_sol')),
    clave_sol        = JSON_UNQUOTE(JSON_EXTRACT(backup_prod_json, '$.clave_sol')),
    ambiente         = 'produccion'
WHERE idempresa=1 AND backup_prod_json IS NOT NULL;

-- 2) Reactivar el certificado real de produccion
UPDATE `certificado` SET activo=0 WHERE idempresa=1;
UPDATE `certificado` SET activo=1
WHERE idcertificado = (
    SELECT JSON_EXTRACT(backup_prod_json, '$.idcert_activo')
    FROM (SELECT backup_prod_json FROM empresa WHERE idempresa=1) AS x
);

-- 3) Limpiar el backup (ya restaurado)
UPDATE `empresa` SET backup_prod_json = NULL WHERE idempresa=1;

-- Verificacion
SELECT '=== EMPRESA RESTAURADA ===' AS '';
SELECT numero_ruc, razon_social, ambiente, usuario_sol FROM empresa WHERE idempresa=1;
SELECT '=== CERTIFICADO ACTIVO ===' AS '';
SELECT idcertificado, nombre_archivo, tipo, activo FROM certificado WHERE idempresa=1 AND activo=1;

-- Recordatorio: tambien puedes querer borrar comprobantes de prueba con:
-- SET FOREIGN_KEY_CHECKS=0;
-- TRUNCATE comprobante_detalle; TRUNCATE cdr_log;
-- TRUNCATE resumen_detalle; TRUNCATE resumen_sunat; TRUNCATE comprobante_electronico;
-- UPDATE numeracion SET ultimo_numero=0;
-- SET FOREIGN_KEY_CHECKS=1;
