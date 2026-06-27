<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Impresora.php";
requireLogin();

$imp = new Impresora();
$op  = $_GET['op'] ?? '';

$id          = $_POST['idimpresora']  ?? '';
$nombre      = $_POST['nombre']       ?? '';
$ip          = $_POST['ip']           ?? '';
$puerto      = $_POST['puerto']       ?? '9100';
$tipo        = $_POST['tipo']         ?? 'cocina';
$ancho       = $_POST['ancho_cols']   ?? '32';
$cortar      = $_POST['cortar_papel'] ?? '1';
$activa      = $_POST['activa']       ?? '1';

switch ($op) {

    case 'listar':
        requirePermiso('impresoras');
        $rs = $imp->listar(false);
        $data = []; while ($r = $rs->fetch_assoc()) { $data[] = $r; }
        jsonResponse($data);
        break;

    case 'mostrar':
        requirePermiso('impresoras');
        jsonResponse($imp->mostrar($id));
        break;

    case 'guardar':
        requirePermiso('impresoras');
        if (trim($nombre) === '' || trim($ip) === '') {
            jsonResponse(['ok' => false, 'msg' => 'Nombre e IP son obligatorios']);
        }
        $newId = $imp->guardar($id, $nombre, $ip, $puerto, $tipo, $ancho, $cortar, $activa);
        jsonResponse(['ok' => $newId > 0, 'idimpresora' => $newId]);
        break;

    case 'eliminar':
        requirePermiso('impresoras');
        jsonResponse(['ok' => (bool)$imp->eliminar($id)]);
        break;

    case 'prueba':
        // Encolar una impresion de prueba para la impresora indicada
        requirePermiso('impresoras');
        $r = $imp->mostrar($id);
        if (!$r) jsonResponse(['ok' => false, 'msg' => 'Impresora no encontrada']);
        $payload = [
            'titulo' => 'PRUEBA DE IMPRESION',
            'lineas' => [
                'Impresora: ' . $r['nombre'],
                'IP: ' . $r['ip'] . ':' . $r['puerto'],
                'Tipo: ' . strtoupper($r['tipo']),
                'Fecha: ' . date('d/m/Y H:i:s')
            ]
        ];
        $idc = $imp->encolar((int)$id, 'prueba', $payload, null);
        jsonResponse(['ok' => $idc > 0, 'idcola' => $idc]);
        break;

    default:
        jsonResponse(['error' => 'op no valida']);
}
?>
