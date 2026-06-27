<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Rol.php";

$rol = new Rol();

$idrol      = isset($_POST['idrol'])      ? limpiarCadena($_POST['idrol'])      : '';
$codigo     = isset($_POST['codigo'])     ? limpiarCadena($_POST['codigo'])     : '';
$nombre     = isset($_POST['nombre'])     ? limpiarCadena($_POST['nombre'])     : '';
$descripcion = isset($_POST['descripcion'])? limpiarCadena($_POST['descripcion']): '';

$op = $_GET['op'] ?? '';

switch ($op) {

    case 'guardaryeditar':
        requirePermiso('usuarios');
        if (empty($idrol)) {
            $id = $rol->insertar($codigo, $nombre, $descripcion);
            jsonResponse(['ok' => $id > 0, 'idrol' => $id]);
        } else {
            jsonResponse(['ok' => (bool)$rol->editar($idrol, $codigo, $nombre, $descripcion)]);
        }
        break;

    case 'desactivar':
        requirePermiso('usuarios');
        jsonResponse(['ok' => (bool)$rol->desactivar($idrol)]);
        break;

    case 'activar':
        requirePermiso('usuarios');
        jsonResponse(['ok' => (bool)$rol->activar($idrol)]);
        break;

    case 'mostrar':
        jsonResponse($rol->mostrar($idrol));
        break;

    case 'listar':
        $rs = $rol->listar();
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'permisos':
        $rs = $rol->permisos($idrol ?: ($_GET['idrol'] ?? ''));
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'setPermisos':
        requirePermiso('usuarios');
        $idsPerm = $_POST['permisos'] ?? [];
        if (!is_array($idsPerm)) $idsPerm = [];
        jsonResponse(['ok' => $rol->setPermisos($idrol, $idsPerm)]);
        break;

    default:
        jsonResponse(['error' => 'op no válida']);
}
?>
