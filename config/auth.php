<?php
// Helper de autenticacion y autorizacion para todas las vistas PUERTO HABANA.

require_once __DIR__ . "/Conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF / origen verificado en TODAS las peticiones POST (vistas y AJAX).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
}

/**
 * Verifica que haya sesion. Si no, redirige a login.
 * URL de login es relativa porque las vistas viven en /puerto_habana/vistas/.
 */
function requireLogin()
{
    if (!isset($_SESSION['idusuario']) || empty($_SESSION['idusuario'])) {
        if (_isAjax()) { jsonResponse(['ok' => false, 'msg' => 'Sesión expirada']); }
        header('Location: login');
        exit;
    }
    // Verificar licencia activa para CUALQUIER usuario logueado (excepto endpoint de licencia)
    licenciaCheck();
}

/**
 * Verifica que el usuario tenga un permiso. Si no, manda a noacceso.
 */
function requirePermiso($codigo)
{
    requireLogin();
    if (!hasPermiso($codigo)) {
        if (_isAjax()) { jsonResponse(['ok' => false, 'msg' => 'No autorizado']); }
        header('Location: noacceso');
        exit;
    }
}

function hasPermiso($codigo)
{
    if (!isset($_SESSION['permisos']) || !is_array($_SESSION['permisos'])) return false;
    return in_array($codigo, $_SESSION['permisos'], true);
}

function _isAjax()
{
    return (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/ajax/') !== false)
        || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');
}

function currentUser()
{
    if (!isset($_SESSION['idusuario'])) return null;
    return [
        'idusuario'  => $_SESSION['idusuario'],
        'nombre'     => $_SESSION['nombre']     ?? '',
        'apellidos'  => $_SESSION['apellidos']  ?? '',
        'login'      => $_SESSION['login']      ?? '',
        'idrol'      => $_SESSION['idrol']      ?? null,
        'rol_codigo' => $_SESSION['rol_codigo'] ?? '',
        'rol_nombre' => $_SESSION['rol_nombre'] ?? '',
        'permisos'   => $_SESSION['permisos']   ?? [],
        'iniciales'  => strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1) . substr($_SESSION['apellidos'] ?? '', 0, 1))
    ];
}

/**
 * Setea variables de sesion al loguear. Regenera el ID para evitar fixation.
 */
function loginSession(array $usuario, array $permisos)
{
    session_regenerate_id(true);
    $_SESSION['idusuario']  = $usuario['idusuario'];
    $_SESSION['nombre']     = $usuario['nombre'];
    $_SESSION['apellidos']  = $usuario['apellidos'] ?? '';
    $_SESSION['login']      = $usuario['login'];
    $_SESSION['idrol']      = $usuario['idrol'];
    $_SESSION['rol_codigo'] = $usuario['rol_codigo'] ?? '';
    $_SESSION['rol_nombre'] = $usuario['rol_nombre'] ?? '';
    $_SESSION['permisos']   = $permisos;
    $_SESSION['login_at']   = time();
}

function logoutSession()
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// =====================================================================
// LICENCIA: validacion de vencimiento del sistema
// =====================================================================

function licenciaInfo()
{
    static $cache = null;
    if ($cache !== null) return $cache;

    // Si la tabla no existe (migracion v7 pendiente), tratar como sistema activo
    // sin licencia en BD para no romper la aplicacion.
    global $conexion;
    $check = @$conexion->query("SHOW TABLES LIKE 'licencia'");
    if (!$check || $check->num_rows === 0) {
        $cache = ['estado' => 'activa', 'fecha_vencimiento' => null, 'dias_restantes' => 9999, 'avisar' => false, 'cliente_nombre' => '', 'fecha_inicio' => null, 'dias_aviso' => 0, '__sin_tabla' => true];
        return $cache;
    }
    if ($check) $check->free();

    $row = ejecutarConsultaSimpleFila("SELECT * FROM licencia ORDER BY idlicencia DESC LIMIT 1");
    if (!$row) {
        $cache = ['estado' => 'sin_licencia', 'fecha_vencimiento' => null, 'dias_restantes' => 0, 'avisar' => false, 'cliente_nombre' => '', 'fecha_inicio' => null, 'dias_aviso' => 0];
        return $cache;
    }
    $hoy = new DateTime(date('Y-m-d'));
    $fv  = new DateTime($row['fecha_vencimiento']);
    $diff = (int)$hoy->diff($fv)->format('%r%a');  // diferencia firmada en dias
    $estado = $row['estado'];
    if ($estado === 'activa' && $diff < 0) $estado = 'vencida';
    $cache = [
        'idlicencia'        => (int)$row['idlicencia'],
        'cliente_nombre'    => $row['cliente_nombre'],
        'fecha_inicio'      => $row['fecha_inicio'],
        'fecha_vencimiento' => $row['fecha_vencimiento'],
        'dias_aviso'        => (int)$row['dias_aviso'],
        'estado'            => $estado,
        'dias_restantes'    => $diff,
        'avisar'            => ($estado === 'activa' && $diff <= (int)$row['dias_aviso']),
        'observacion'       => $row['observacion'] ?? '',
    ];
    return $cache;
}

/**
 * Si la licencia esta vencida o suspendida, redirige a la pagina de bloqueo.
 * Excepciones: la propia pagina de bloqueo, login, logout, y el endpoint
 * de gestion de licencia (para que el admin pueda extender desde alli).
 */
function licenciaCheck()
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $allow  = ['bloqueado.php', 'login.php', 'noacceso.php'];
    if (in_array($script, $allow, true)) return;

    // Endpoint AJAX de licencia siempre accesible (para reactivar)
    $path = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($path, '/ajax/licencia.php') !== false) return;
    if (strpos($path, '/ajax/usuario.php')  !== false) return; // login/logout

    $info = licenciaInfo();
    if ($info['estado'] === 'activa') return;

    // Bloqueada
    if (_isAjax()) {
        jsonResponse(['ok' => false, 'bloqueado' => true, 'msg' => 'Sistema bloqueado: licencia ' . $info['estado']]);
    }
    header('Location: bloqueado');
    exit;
}
