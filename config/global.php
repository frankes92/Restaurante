<?php
// ---------------------------------------------------------------------
// CONFIGURACION GLOBAL PUERTO HABANA POS
// IMPORTANTE: en produccion, mover este archivo fuera del webroot o
// usar variables de entorno. Crear un usuario MySQL dedicado con
// privilegios minimos (NO usar root).
// ---------------------------------------------------------------------

/*define("DB_HOST",     getenv('YAPEZ_DB_HOST')     ?: "50.31.174.155");
define("DB_USERNAME", getenv('YAPEZ_DB_USERNAME') ?: "jprqwdud_puertohabana");
define("DB_PASSWORD", getenv('YAPEZ_DB_PASSWORD') ?: "ejLjG27QyzeFXJC");
define("DB_NAME",     getenv('YAPEZ_DB_NAME')     ?: "jprqwdud_puertohabana_db");*/

define("DB_HOST",     getenv('YAPEZ_DB_HOST')     ?: "localhost");
define("DB_USERNAME", getenv('YAPEZ_DB_USERNAME') ?: "root");
define("DB_PASSWORD", getenv('YAPEZ_DB_PASSWORD') ?: "");
define("DB_NAME",     getenv('YAPEZ_DB_NAME')     ?: "jprqwdud_puertohabana_db");

define("DB_ENCODE",   "utf8mb4");

define("PRO_NOMBRE",  "PUERTO HABANA POS");
define("PRO_IGV",     0.18);

// ---------------------------------------------------------------------
// TOPE NUEVO RUS (Nuevo Régimen Único Simplificado)
// La empresa está en RUS Categoría 1: hasta S/ 5,000 de ingresos al mes
// paga cuota de S/ 20. Si supera S/ 5,000 (hasta S/ 8,000) pasa a
// Categoría 2 y paga S/ 50. El sistema solo AVISA; nunca bloquea la
// emisión de comprobantes (eso queda a criterio del usuario).
// ---------------------------------------------------------------------
define("RUS_LIMITE",       (float)(getenv('HABANA_RUS_LIMITE')     ?: 5000)); // tope Categoría 1
define("RUS_UMBRAL_AVISO", (float)(getenv('HABANA_RUS_UMBRAL')     ?: 4500)); // 90%: empieza a avisar
define("RUS_LIMITE_MAX",   (float)(getenv('HABANA_RUS_LIMITE_MAX') ?: 8000)); // tope Categoría 2
define("RUS_CUOTA_CAT1",   (float)(getenv('HABANA_RUS_CUOTA1')     ?: 20));   // cuota S/ 20
define("RUS_CUOTA_CAT2",   (float)(getenv('HABANA_RUS_CUOTA2')     ?: 50));   // cuota S/ 50

// ---------------------------------------------------------------------
// MODO DEMO SUNAT
// Cuando el certificado ACTIVO es de tipo 'demo', el sistema firma y
// envia a SUNAT BETA usando estos datos de prueba (RUC y credenciales
// del certificado de demostracion de LLAMA.PE / MODDATOS). El ticket/PDF
// que ve el cliente sigue mostrando los datos reales de la empresa.
// Al cargar y activar un certificado de PRODUCCION valido, el modo demo
// se apaga automaticamente y se usan los datos reales de la empresa.
// ---------------------------------------------------------------------
define("SUNAT_DEMO_RUC",   getenv('HABANA_DEMO_RUC')   ?: "20607027685");
define("SUNAT_DEMO_USER",  getenv('HABANA_DEMO_USER')  ?: "MODDATOS");
define("SUNAT_DEMO_PASS",  getenv('HABANA_DEMO_PASS')  ?: "moddatos");
// Razon social a usar en el XML demo. Vacio = conservar la razon real.
define("SUNAT_DEMO_RAZON", getenv('HABANA_DEMO_RAZON') ?: "");

// Token para el bridge de impresion local (cambiar en produccion).
// El bridge envia este token en header X-Bridge-Token para autenticarse.
define("BRIDGE_TOKEN", getenv('YAPEZ_BRIDGE_TOKEN') ?: "K7p9mNx2zQ4bV8wR3fHcL5jY1tD6aE0s");

// Zona horaria del sistema: Peru (UTC-5)
// Esto afecta todas las fechas/horas que PHP genera con date(), strftime(), etc.
date_default_timezone_set(getenv('YAPEZ_TIMEZONE') ?: 'America/Lima');

// Clave maestra para cifrar datos sensibles (claves de certificado, etc.)
// CAMBIAR este valor por una cadena aleatoria propia en cada despliegue.
if (!defined('APP_SECRET_KEY')) {
    define("APP_SECRET_KEY", getenv('YAPEZ_SECRET_KEY') ?: 'YAPEZ-CHANGE-ME-32B-aA1B2c3D4e5F6g7H8i9J0kLmNoPqRsTu');
}

// Clave maestra para activar/extender licencia (solo el proveedor la conoce).
// CAMBIAR antes de entregar el sistema al cliente.
if (!defined('LICENSE_MASTER_KEY')) {
    define("LICENSE_MASTER_KEY", getenv('YAPEZ_LICENSE_KEY') ?: 'RIPASOFT-LIC-MASTER-CHANGE-ME-2026');
}

// Modo de depuracion (mostrar errores). En produccion: false.
if (!defined('APP_DEBUG')) {
    define("APP_DEBUG", getenv('YAPEZ_DEBUG') === '1');
}

// Configuracion de errores PHP
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Cookies de sesion seguras
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
}
