<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/NotaCreditoDebito.php";
requireLogin();

$nota = new NotaCreditoDebito();
$op = $_GET['op'] ?? '';

switch ($op) {

    case 'crear':
        // Crear NC o ND a partir de un comprobante existente
        $tipoNota   = limpiarCadena($_POST['tipo_nota']     ?? '07'); // 07 NC, 08 ND
        $idCompOrig = (int)($_POST['idcomprobante']         ?? 0);
        $motivoCod  = limpiarCadena($_POST['motivo_codigo'] ?? '01');
        $motivoDesc = limpiarCadena($_POST['motivo_descripcion'] ?? '');
        $autoEnviar = !empty($_POST['auto_enviar']);

        if ($idCompOrig <= 0) jsonResponse(['ok' => false, 'msg' => 'Comprobante origen requerido']);

        $permiso = ($tipoNota === '07') ? 'emitir_nc' : 'emitir_nd';
        requirePermiso($permiso);

        $r = $nota->crearDesdeComprobante($idCompOrig, $tipoNota, $motivoCod, $motivoDesc, $_SESSION['idusuario'] ?? null);
        if (!$r['ok']) jsonResponse($r);

        if ($autoEnviar) {
            $env = $nota->enviarSunat($r['idcomprobante']);
            $r['envio'] = $env;
        }
        jsonResponse($r);
        break;

    case 'enviar':
        $idnota = (int)($_POST['idcomprobante'] ?? 0);
        if ($idnota <= 0) jsonResponse(['ok' => false, 'msg' => 'idcomprobante requerido']);
        // Determinar permiso segun tipo
        $row = ejecutarConsultaSimpleFila("SELECT tipo_documento FROM comprobante_electronico WHERE idcomprobante='$idnota'");
        if (!$row) jsonResponse(['ok' => false, 'msg' => 'Nota no encontrada']);
        requirePermiso($row['tipo_documento'] === '07' ? 'emitir_nc' : 'emitir_nd');
        jsonResponse($nota->enviarSunat($idnota));
        break;

    case 'motivos':
        // Devuelve catalogo 9 (NC) o catalogo 10 (ND) segun tipo
        $tipo = $_GET['tipo'] ?? '07';
        if ($tipo === '07') {
            jsonResponse([
                ['codigo' => '01', 'descripcion' => 'Anulación de la operación'],
                ['codigo' => '02', 'descripcion' => 'Anulación por error en el RUC'],
                ['codigo' => '03', 'descripcion' => 'Corrección por error en la descripción'],
                ['codigo' => '04', 'descripcion' => 'Descuento global'],
                ['codigo' => '05', 'descripcion' => 'Descuento por ítem'],
                ['codigo' => '06', 'descripcion' => 'Devolución total'],
                ['codigo' => '07', 'descripcion' => 'Devolución por ítem'],
                ['codigo' => '08', 'descripcion' => 'Bonificación'],
                ['codigo' => '09', 'descripcion' => 'Disminución en el valor'],
                ['codigo' => '10', 'descripcion' => 'Otros conceptos'],
                ['codigo' => '13', 'descripcion' => 'Ajustes - operaciones de exportación'],
            ]);
        } else {
            jsonResponse([
                ['codigo' => '01', 'descripcion' => 'Intereses por mora'],
                ['codigo' => '02', 'descripcion' => 'Aumento en el valor'],
                ['codigo' => '03', 'descripcion' => 'Penalidades / otros conceptos'],
                ['codigo' => '11', 'descripcion' => 'Ajustes de operaciones de exportación'],
            ]);
        }
        break;

    default:
        jsonResponse(['error' => 'op no valida']);
}
