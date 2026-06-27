<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Usuario.php";
require_once __DIR__ . "/../modelos/Rol.php";

$usuario = new Usuario();

$idusuario = isset($_POST['idusuario']) ? limpiarCadena($_POST['idusuario']) : '';
$idrol     = isset($_POST['idrol'])     ? limpiarCadena($_POST['idrol'])     : '';
$nombre    = isset($_POST['nombre'])    ? limpiarCadena($_POST['nombre'])    : '';
$apellidos = isset($_POST['apellidos']) ? limpiarCadena($_POST['apellidos']) : '';
$login     = isset($_POST['login'])     ? limpiarCadena($_POST['login'])     : '';
$clave     = isset($_POST['clave'])     ? $_POST['clave']                    : '';
$tipoDoc   = isset($_POST['tipo_documento']) ? limpiarCadena($_POST['tipo_documento']) : '';
$numDoc    = isset($_POST['num_documento'])  ? limpiarCadena($_POST['num_documento'])  : '';
$telefono  = isset($_POST['telefono']) ? limpiarCadena($_POST['telefono']) : '';
$email     = isset($_POST['email'])    ? limpiarCadena($_POST['email'])    : '';

$op = $_GET['op'] ?? '';

switch ($op) {

    case 'login':
        // Login no requiere licencia activa, pero advierte si esta vencida
        $loginIn  = trim($_POST['login'] ?? '');
        $claveIn  = $_POST['clave'] ?? '';
        if ($loginIn === '' || $claveIn === '') {
            jsonResponse(['ok' => false, 'msg' => 'Login y contraseña son obligatorios']);
        }
        $u = $usuario->verificarLogin($loginIn, $claveIn);
        if (is_array($u) && !empty($u['__bloqueado'])) {
            seguridadLog('login_bloqueado', $loginIn, null, 'Cuenta bloqueada hasta ' . $u['hasta']);
            jsonResponse(['ok' => false, 'msg' => 'Cuenta bloqueada por intentos fallidos. Intente más tarde.']);
        }
        if (!$u) {
            seguridadLog('login_fallido', $loginIn, null, 'Credenciales invalidas');
            jsonResponse(['ok' => false, 'msg' => 'Credenciales inválidas o usuario inactivo']);
        }

        // Verificar licencia
        $lic = licenciaInfo();
        if ($lic['estado'] !== 'activa') {
            // Permitir login solo si tiene permiso de gestionar licencia (admin)
            $permisos = $usuario->permisosEfectivos($u['idusuario']);
            if (!in_array('config_licencia', $permisos, true)) {
                seguridadLog('login_bloqueado_lic', $loginIn, $u['idusuario'], 'Licencia ' . $lic['estado']);
                jsonResponse(['ok' => false, 'msg' => 'Sistema bloqueado: la licencia está ' . $lic['estado'] . '. Contacte al proveedor.']);
            }
            // Admin entra en modo licencia para reactivar
            loginSession($u, $permisos);
            $usuario->actualizarUltimoAcceso($u['idusuario']);
            seguridadLog('login_ok_lic_vencida', $loginIn, $u['idusuario']);
            jsonResponse([
                'ok' => true,
                'redirect_to' => 'bloqueado',
                'idusuario' => $u['idusuario'],
                'nombre'    => $u['nombre'],
                'permisos'  => $permisos,
            ]);
        }

        $permisos = $usuario->permisosEfectivos($u['idusuario']);
        loginSession($u, $permisos);
        $usuario->actualizarUltimoAcceso($u['idusuario']);
        seguridadLog('login_ok', $loginIn, $u['idusuario']);
        jsonResponse([
            'ok'             => true,
            'idusuario'      => $u['idusuario'],
            'nombre'         => $u['nombre'],
            'rol_nombre'     => $u['rol_nombre'] ?? '',
            'permisos'       => $permisos,
            'licencia_aviso' => $lic['avisar'] ? ('La licencia vence en ' . max(0, $lic['dias_restantes']) . ' días.') : null,
        ]);
        break;

    case 'logout':
        seguridadLog('logout', $_SESSION['login'] ?? null, $_SESSION['idusuario'] ?? null);
        logoutSession();
        jsonResponse(['ok' => true]);
        break;

    case 'sesion':
        // Devuelve datos del usuario logueado (o null si no hay)
        jsonResponse(currentUser());
        break;

    case 'guardaryeditar':
        requirePermiso('usuarios');
        if (empty($idusuario)) {
            if ($clave === '') jsonResponse(['ok' => false, 'msg' => 'La clave es obligatoria al crear']);
            if (strlen($clave) < 6) jsonResponse(['ok' => false, 'msg' => 'La clave debe tener al menos 6 caracteres']);
            $id = $usuario->insertar($idrol, $nombre, $apellidos, $login, $clave, $tipoDoc, $numDoc, $telefono, $email);
            jsonResponse(['ok' => $id > 0, 'idusuario' => $id]);
        } else {
            $r = $usuario->editar($idusuario, $idrol, $nombre, $apellidos, $login, $tipoDoc, $numDoc, $telefono, $email);
            if ($clave !== '') {
                if (strlen($clave) < 6) jsonResponse(['ok' => false, 'msg' => 'La clave debe tener al menos 6 caracteres']);
                $usuario->cambiarClave($idusuario, $clave);
            }
            jsonResponse(['ok' => (bool)$r]);
        }
        break;

    case 'cambiarClave':
        requireLogin();
        $idu = $idusuario ?: $_SESSION['idusuario'];
        // un usuario solo puede cambiar su propia clave salvo que tenga permiso usuarios
        if ($idu != $_SESSION['idusuario'] && !hasPermiso('usuarios')) {
            jsonResponse(['ok' => false, 'msg' => 'No autorizado']);
        }
        if ($clave === '') jsonResponse(['ok' => false, 'msg' => 'Clave vacía']);
        if (strlen($clave) < 6) jsonResponse(['ok' => false, 'msg' => 'La clave debe tener al menos 6 caracteres']);
        seguridadLog('cambio_clave', $_SESSION['login'] ?? null, $idu);
        jsonResponse(['ok' => (bool)$usuario->cambiarClave($idu, $clave)]);
        break;

    case 'desactivar':
        requirePermiso('usuarios');
        jsonResponse(['ok' => (bool)$usuario->desactivar($idusuario)]);
        break;

    case 'activar':
        requirePermiso('usuarios');
        jsonResponse(['ok' => (bool)$usuario->activar($idusuario)]);
        break;

    case 'mostrar':
        requirePermiso('usuarios');
        jsonResponse($usuario->mostrar($idusuario));
        break;

    case 'listar':
        requirePermiso('usuarios');
        $rs = $usuario->listar();
        $data = [];
        while ($r = $rs->fetch_assoc()) { unset($r['clave']); $data[] = $r; }
        jsonResponse($data);
        break;

    case 'datatable':
        requirePermiso('usuarios');
        $draw     = (int)($_REQUEST['draw']   ?? 1);
        $start    = (int)($_REQUEST['start']  ?? 0);
        $length   = (int)($_REQUEST['length'] ?? 10);
        $searchV  = trim($_REQUEST['search']['value'] ?? '');
        $orderCol = (int)($_REQUEST['order'][0]['column'] ?? 0);
        $orderDir = $_REQUEST['order'][0]['dir'] ?? 'asc';

        $rs = $usuario->listarServerSide($start, $length, $searchV, $orderCol, $orderDir);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $usuario->contarServerSide('', false),
            'recordsFiltered' => $usuario->contarServerSide($searchV, true),
            'data'            => $data,
        ]);
        exit;
        break;

    case 'overrides':
        requirePermiso('usuarios');
        $rs = $usuario->overrides($idusuario);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'setOverrides':
        requirePermiso('usuarios');
        $grants  = $_POST['grants']  ?? [];
        $revokes = $_POST['revokes'] ?? [];
        if (!is_array($grants))  $grants  = [];
        if (!is_array($revokes)) $revokes = [];
        jsonResponse(['ok' => $usuario->setOverrides($idusuario, $grants, $revokes)]);
        break;

    default:
        jsonResponse(['error' => 'op no válida']);
}
