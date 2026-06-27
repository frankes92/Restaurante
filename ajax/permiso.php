<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Permiso.php";

$permiso = new Permiso();
$op = $_GET['op'] ?? '';

switch ($op) {

    case 'listar':
        $rs = $permiso->listar();
        $data = [];
        while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'listarPorGrupo':
        $rs = $permiso->listarPorGrupo();
        $data = [];
        while ($r = $rs->fetch_assoc()) {
            $g = $r['grupo'];
            if (!isset($data[$g])) $data[$g] = [];
            $data[$g][] = $r;
        }
        jsonResponse($data);
        break;

    default:
        jsonResponse(['error' => 'op no válida']);
}
?>
