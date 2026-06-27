-- =====================================================================
-- PUERTO HABANA POS — Limpiar TODA la base de datos dejando solo admin
-- =====================================================================
-- Borra: ventas, caja, comprobantes, inventario, catalogo (productos,
--        categorias, mesas, zonas), empresa, certificado, numeracion,
--        clientes, whatsapp, logs y los usuarios distintos de admin.
-- CONSERVA: usuario admin (idusuario=1), roles, permisos, rol_permiso
--           y los permisos del admin (usuario_permiso).
--
-- USO (desde la terminal):
--   "C:\xampp\mysql\bin\mysql.exe" -u root habana_db < db\limpiar_dejar_admin.sql
--
-- O desde phpMyAdmin: seleccionar la BD habana_db -> pestaña SQL -> pegar todo.
--
-- IMPORTANTE: esto es IRREVERSIBLE. Haz un backup antes si tienes datos reales.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --- Ventas / operacion ---
TRUNCATE TABLE orden_detalle;
TRUNCATE TABLE orden;
TRUNCATE TABLE caja_arqueo;
TRUNCATE TABLE caja_movimiento;
TRUNCATE TABLE caja_sesion;

-- --- Comprobantes electronicos / SUNAT ---
TRUNCATE TABLE comprobante_detalle;
TRUNCATE TABLE comprobante_electronico;
TRUNCATE TABLE cdr_log;
TRUNCATE TABLE resumen_detalle;
TRUNCATE TABLE resumen_sunat;
TRUNCATE TABLE numeracion;

-- --- Inventario ---
TRUNCATE TABLE inventario_movimiento;

-- --- Catalogo ---
TRUNCATE TABLE producto_precio;
TRUNCATE TABLE producto;
TRUNCATE TABLE categoria;
TRUNCATE TABLE mesa;
TRUNCATE TABLE zona;

-- --- Clientes ---
TRUNCATE TABLE cliente;
TRUNCATE TABLE cliente_facturacion;

-- --- Configuracion del negocio ---
TRUNCATE TABLE empresa;
TRUNCATE TABLE certificado;
TRUNCATE TABLE rutas;

-- --- Licencia ---
TRUNCATE TABLE licencia;
TRUNCATE TABLE licencia_historial;

-- --- Impresion ---
TRUNCATE TABLE impresora;
TRUNCATE TABLE cola_impresion;

-- --- WhatsApp ---
TRUNCATE TABLE whatsapp_envio;
TRUNCATE TABLE whatsapp_plantilla;

-- --- Seguridad / logs ---
TRUNCATE TABLE seguridad_log;

-- --- Usuarios: dejar SOLO admin (idusuario = 1) ---
DELETE FROM usuario_permiso WHERE idusuario <> 1;
DELETE FROM usuario        WHERE idusuario <> 1;

-- NOTA: NO se tocan rol, permiso ni rol_permiso (estructura de seguridad).

SET FOREIGN_KEY_CHECKS = 1;

-- --- Verificacion ---
SELECT '=== USUARIOS (solo admin) ===' AS info;
SELECT idusuario, login, nombre, apellidos, idrol FROM usuario;

SELECT '=== CONSERVADO ===' AS info;
SELECT
  (SELECT COUNT(*) FROM rol)         AS roles,
  (SELECT COUNT(*) FROM permiso)     AS permisos,
  (SELECT COUNT(*) FROM rol_permiso) AS rol_permiso,
  (SELECT COUNT(*) FROM usuario)     AS usuarios;
