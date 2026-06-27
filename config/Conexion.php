<?php
require_once __DIR__ . "/global.php";

$conexion = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conexion->connect_errno) {
    error_log("[PUERTO HABANA] Conexion BD fallida: " . $conexion->connect_error);
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die("Falló la conexión a la base de datos: " . $conexion->connect_error);
    }
    http_response_code(500);
    die("Servicio temporalmente no disponible. Contacte al administrador.");
}

$conexion->set_charset(DB_ENCODE);

// Sincronizar zona horaria de MySQL con la de PHP (America/Lima = UTC-5)
// Asi NOW(), CURDATE(), etc. devuelven hora peruana.
@$conexion->query("SET time_zone = '-05:00'");

if (!function_exists('ejecutarConsulta')) {

    /**
     * Manejador centralizado de error SQL: loguea y devuelve mensaje generico.
     * En modo debug, expone el error real al usuario.
     */
    function _sqlError($sql, $error)
    {
        error_log("[PUERTO HABANA-SQL] " . $error . " | SQL: " . $sql);
        if (defined('APP_DEBUG') && APP_DEBUG) {
            die("Error en la consulta: " . $error);
        }
        if (function_exists('jsonResponse') && !headers_sent()) {
            // Si estamos en un endpoint AJAX, devolver JSON limpio
            jsonResponse(['ok' => false, 'msg' => 'Error procesando la solicitud']);
        }
        http_response_code(500);
        die("Error procesando la solicitud. Intente nuevamente.");
    }

    function ejecutarConsulta($sql) {
        global $conexion;
        $query = $conexion->query($sql);
        if (!$query) _sqlError($sql, $conexion->error);
        return $query;
    }

    function ejecutarConsultaSimpleFila($sql) {
        $result = ejecutarConsulta($sql);
        $row = $result->fetch_assoc();
        $result->free();
        return $row;
    }

    function ejecutarConsulta_retornarID($sql) {
        global $conexion;
        if (!$conexion->query($sql)) _sqlError($sql, $conexion->error);
        return $conexion->insert_id;
    }

    /**
     * Saneo para SQL: solo escapa para mysqli, NO aplica htmlspecialchars
     * (eso se hace al renderizar a HTML). Mantener compatibilidad con codigo
     * legado que llama limpiarCadena() para inputs.
     */
    function limpiarCadena($str) {
        global $conexion;
        return $conexion->real_escape_string(trim((string)$str));
    }

    /**
     * Helper para escape al renderizar HTML.
     */
    function h($str) {
        return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function jsonResponse($data) {
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    // -----------------------------------------------------------------
    // Prepared statements helpers
    // -----------------------------------------------------------------

    /**
     * Ejecuta un statement preparado y devuelve el mysqli_result (SELECT)
     * o true/false (UPDATE/INSERT/DELETE). Usar siempre que el SQL incluya
     * datos venidos del usuario.
     *
     * @param string $sql    SQL con placeholders ?
     * @param string $types  ej. 'sssi' (s=string, i=int, d=double, b=blob)
     * @param array  $params parametros en el mismo orden
     * @return mysqli_result|bool
     */
    function dbQuery($sql, $types = '', array $params = []) {
        global $conexion;
        $stmt = $conexion->prepare($sql);
        if (!$stmt) _sqlError($sql, $conexion->error);
        if ($types !== '' && count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            _sqlError($sql, $err);
        }
        $meta = $stmt->result_metadata();
        if ($meta) {
            $rs = $stmt->get_result();
            $stmt->close();
            return $rs;
        }
        $stmt->close();
        return true;
    }

    /**
     * Devuelve la primera fila como array asociativo o null.
     */
    function dbFila($sql, $types = '', array $params = []) {
        $rs = dbQuery($sql, $types, $params);
        if ($rs === true || $rs === false) return null;
        $row = $rs->fetch_assoc();
        $rs->free();
        return $row;
    }

    /**
     * Ejecuta INSERT con prepared statement y devuelve insert_id.
     */
    function dbInsert($sql, $types = '', array $params = []) {
        global $conexion;
        $stmt = $conexion->prepare($sql);
        if (!$stmt) _sqlError($sql, $conexion->error);
        if ($types !== '' && count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            _sqlError($sql, $err);
        }
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    // -----------------------------------------------------------------
    // Cifrado simetrico para datos sensibles (clave de certificado, etc.)
    // -----------------------------------------------------------------

    function appEncrypt($plaintext) {
        if ($plaintext === null || $plaintext === '') return '';
        $key = hash('sha256', APP_SECRET_KEY, true);
        $iv  = random_bytes(16);
        $ct  = openssl_encrypt((string)$plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return 'enc:' . base64_encode($iv . $ct);
    }

    function appDecrypt($cipher) {
        if (!$cipher) return '';
        if (strpos($cipher, 'enc:') !== 0) return $cipher; // valor legado en plano
        $raw = base64_decode(substr($cipher, 4));
        if ($raw === false || strlen($raw) < 17) return '';
        $iv  = substr($raw, 0, 16);
        $ct  = substr($raw, 16);
        $key = hash('sha256', APP_SECRET_KEY, true);
        $pt  = openssl_decrypt($ct, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $pt === false ? '' : $pt;
    }

    // -----------------------------------------------------------------
    // CSRF: protección basada en Origin/Referer + token de sesion
    // -----------------------------------------------------------------

    /**
     * Verifica que la peticion sea same-origin (defensa CSRF basica que no
     * requiere cambios en el frontend). Bloquea POST si Origin/Referer no
     * coincide con el host del servidor.
     */
    function csrfCheck() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $ref    = $_SERVER['HTTP_REFERER'] ?? '';
        $okOrigin = ($origin !== '' && parse_url($origin, PHP_URL_HOST) === $host);
        $okRef    = ($ref    !== '' && parse_url($ref,    PHP_URL_HOST) === $host);
        if (!$okOrigin && !$okRef) {
            if (function_exists('jsonResponse')) jsonResponse(['ok' => false, 'msg' => 'Origen no permitido']);
            http_response_code(403); die('CSRF: origen no permitido');
        }
    }

    // -----------------------------------------------------------------
    // Logger de eventos de seguridad (login OK/fallo, licencia, etc.)
    // -----------------------------------------------------------------

    function seguridadLog($evento, $login = null, $idusuario = null, $mensaje = '') {
        global $conexion;
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);
        $idu = $idusuario === null ? null : (int)$idusuario;
        // Suprimir excepciones si la tabla no existe (migracion v7 pendiente)
        try {
            $stmt = @$conexion->prepare("INSERT INTO seguridad_log (evento, idusuario, login, ip, user_agent, mensaje)
                                         VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) return false;
            $stmt->bind_param('sissss', $evento, $idu, $login, $ip, $ua, $mensaje);
            $ok = @$stmt->execute();
            $stmt->close();
            return $ok;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
