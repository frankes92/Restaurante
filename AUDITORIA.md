# Auditoría del sistema — Puerto Habana (YAPEZ POS)
Fecha: 2026-05-31

## 1. Base de datos `habana_db` (creada, funcional y verificada)
- **Creada** con `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`.
- **Importado** el dump `db/habana_db.sql` (estructura + datos). Es una copia de `yapez_db`
  exportada con Navicat (sin `CREATE DATABASE`, por eso se creó la BD manualmente antes).
- **33 tablas** → **copia completa**: faltantes respecto a `yapez_db` = **NINGUNA** (verificado).
- App apuntada a `habana_db`: en `config/global.php` se cambió el valor por defecto de
  `DB_NAME` de `yapez_db` a `habana_db` (respeta la variable de entorno `YAPEZ_DB_NAME`).
  **Verificado**: la app conecta y consulta `habana_db` correctamente.

## 2. Resultado de la auditoría de código

`php -l` sobre todo el código PHP propio (excluyendo librerías de terceros fpdf, phpqrcode,
api_signature):

- **Núcleo del POS (ajax/, modelos/, vistas/, config/): TODOS los archivos sin errores.**
- **`ajax/reporte.php`**: verificado correcto (256 líneas, sin errores). Respaldo en
  `ajax/reporte.php.bak_audit`.
- **`facturacion/apifacturacion/factura.php` — CORREGIDO**: tenía 3 typos en el bucle de
  cálculo de operaciones (líneas 102, 105, 108): `$['codigo_afectacion_alt']` con la variable
  sin nombre (`$` seguido de `[`), lo que producía `syntax error, unexpected token "["`. Se
  corrigió a `$v['codigo_afectacion_alt']` (la variable del `foreach ($detalle as $k => $v)`).
  Era un error **preexistente** (también en el código original de YAPEZ) del módulo de
  facturación legado/independiente (carpeta `facturacion/`, app AdminLTE aparte, NO el núcleo
  del POS). El núcleo factura por SUNAT con `modelos/SunatXml.php`/`SunatEnviador.php`/
  `ComprobanteElectronico.php`.

### Postura de seguridad del núcleo (revisada, correcta)
- **Inyección SQL**: prepared statements (`dbQuery`/`dbFila`), `limpiarCadena()`, casts `(int)`.
- **Autenticación**: los 22 endpoints AJAX incluyen `auth.php` con `requireLogin`/`requirePermiso`.
- **CSRF**: verificación de Origin/Referer en todo POST (`csrfCheck`).
- **Cifrado**: AES-256-CBC para datos sensibles; contraseñas con bcrypt (+automigración md5).
- **`.htaccess`**: bloquea `.sql/.bak/.md/.log`, listado de directorios y carpetas sensibles.

## 3. Recomendaciones (producción — no bloquean el funcionamiento)
| # | Sev. | Tema | Acción |
|---|------|------|--------|
| 1 | MEDIA | Secretos por defecto en `config/global.php` (`APP_SECRET_KEY`, `LICENSE_MASTER_KEY`, `BRIDGE_TOKEN` = "CHANGE-ME") | Definir `YAPEZ_SECRET_KEY`, `YAPEZ_LICENSE_KEY`, `YAPEZ_BRIDGE_TOKEN`. Cambiar `APP_SECRET_KEY` invalida lo ya cifrado. |
| 2 | MEDIA | MySQL `root` sin contraseña | Crear usuario MySQL dedicado con privilegios mínimos (`YAPEZ_DB_USERNAME`/`YAPEZ_DB_PASSWORD`). |
| 3 | — | `facturacion/apifacturacion/factura.php` (CORREGIDO) | Ya reparado; considere eliminar el módulo legado `facturacion/` si no se usa. |
| 4 | BAJA | Fallback de login con `md5()` | Tras migrar todos los usuarios a bcrypt, eliminar el `|| md5(...)`. |
| 5 | BAJA | `*.bak`, `.bak_audit`, `error_log` en el árbol web | Ya bloqueados por `.htaccess`; conviene moverlos fuera del webroot. |

## 4. Archivos temporales generados (puede eliminarlos)
- `ajax/reporte.php.bak_audit`, `_recovery_status.txt`, `_ESTADO_FINAL.txt`
