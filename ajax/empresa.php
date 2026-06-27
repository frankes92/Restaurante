<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Empresa.php";
requireLogin();

$empresa = new Empresa();
$op = $_GET['op'] ?? '';

switch ($op) {
    case 'mostrar':
        $idempresa = (int)($_REQUEST['idempresa'] ?? 1);
        $row = $empresa->mostrar($idempresa);
        if ($row) {
            // Nunca exponer la clave SOL ni datos sensibles via API
            unset($row['clave_sol']);
        }
        jsonResponse($row);
        break;

    case 'editar':
        requirePermiso('config_empresa');
        $idempresa = (int)($_POST['idempresa'] ?? 1);
        $datos = [];
        $campos = ['numero_ruc','razon_social','nombre_comercial','domicilio_fiscal','ubigeo',
                   'departamento','provincia','distrito','telefono','correo','web',
                   'usuario_sol','clave_sol','ambiente',
                   'tasa_igv','simbolo_moneda','codigo_moneda','formato_comprobante'];
        foreach ($campos as $c) {
            if (isset($_POST[$c])) $datos[$c] = trim($_POST[$c]);
        }
        // Toggle de envio automatico a SUNAT (checkbox: 1 o 0)
        if (isset($_POST['envio_sunat_automatico'])) {
            $datos['envio_sunat_automatico'] = $_POST['envio_sunat_automatico'] ? 1 : 0;
        }
        // Sin clave_sol vacia: no actualizar
        if (isset($datos['clave_sol']) && $datos['clave_sol'] === '') unset($datos['clave_sol']);
        // Validar formato_comprobante
        if (isset($datos['formato_comprobante']) && !in_array($datos['formato_comprobante'], ['ticket','a4'], true)) {
            unset($datos['formato_comprobante']);
        }
        jsonResponse(['ok' => (bool)$empresa->editar($idempresa, $datos)]);
        break;

    case 'subirLogo':
        requirePermiso('config_logo');
        $idempresa = (int)($_POST['idempresa'] ?? 1);
        $r = $empresa->subirLogo($idempresa, $_FILES['archivo'] ?? null);
        jsonResponse($r);
        break;

    case 'eliminarLogo':
        requirePermiso('config_logo');
        $idempresa = (int)($_POST['idempresa'] ?? 1);
        jsonResponse(['ok' => (bool)$empresa->eliminarLogo($idempresa)]);
        break;

    case 'subirQr':
        requirePermiso('config_empresa');
        $idempresa = (int)($_POST['idempresa'] ?? 1);
        $tipo = ($_POST['tipo'] ?? 'yape');
        $r = $empresa->subirQr($idempresa, $_FILES['archivo'] ?? null, $tipo);
        jsonResponse($r);
        break;

    case 'eliminarQr':
        requirePermiso('config_empresa');
        $idempresa = (int)($_POST['idempresa'] ?? 1);
        $tipo = ($_POST['tipo'] ?? 'yape');
        jsonResponse(['ok' => (bool)$empresa->eliminarQr($idempresa, $tipo)]);
        break;

    case 'bridgeInfo':
        requirePermiso('config_empresa');
        // Sugerir CLOUD_URL a partir del request actual (esquema + host + ruta base)
        $scheme = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $base   = preg_replace('#/ajax/[^/]*$#', '', $script);   // quitar /ajax/empresa.php
        $url    = rtrim($scheme . '://' . $host . $base, '/');
        jsonResponse([
            'token'     => defined('BRIDGE_TOKEN') ? BRIDGE_TOKEN : '',
            'cloud_url' => $url,
            'poll_sec'  => 3,
        ]);
        break;

    case 'bridgeConfig':
        requirePermiso('config_empresa');
        // Genera el archivo config.php del bridge con los datos de ESTA empresa.
        $url   = trim($_REQUEST['cloud_url'] ?? '');
        $url   = rtrim($url, '/');
        // Validar URL basica
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            http_response_code(400);
            echo 'URL invalida';
            exit;
        }
        $poll  = (int)($_REQUEST['poll_sec'] ?? 3);
        if ($poll < 1 || $poll > 30) $poll = 3;
        $token = defined('BRIDGE_TOKEN') ? BRIDGE_TOKEN : '';

        // Escapar para cadena PHP entre comillas simples
        $urlEsc   = str_replace(['\\', "'"], ['\\\\', "\\'"], $url);
        $tokenEsc = str_replace(['\\', "'"], ['\\\\', "\\'"], $token);

        $contenido  = "<?php\n";
        $contenido .= "// ============================================================\n";
        $contenido .= "// CONFIGURACION DEL BRIDGE — generado desde Empresa\n";
        $contenido .= "// Coloca este archivo como 'config.php' junto a bridge.php\n";
        $contenido .= "// ============================================================\n\n";
        $contenido .= "// URL del servidor de este sistema (sin slash final)\n";
        $contenido .= "\$CLOUD_URL = '{$urlEsc}';\n\n";
        $contenido .= "// Token de autenticacion (coincide con el servidor de esta empresa)\n";
        $contenido .= "\$TOKEN = '{$tokenEsc}';\n\n";
        $contenido .= "// Intervalo de polling en segundos\n";
        $contenido .= "\$POLL_SEC = {$poll};\n\n";
        $contenido .= "// Archivo de log (opcional)\n";
        $contenido .= "\$LOG_FILE = __DIR__ . '/bridge.log';\n";

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="config.php"');
        header('Content-Length: ' . strlen($contenido));
        echo $contenido;
        exit;

    default:
        jsonResponse(['error' => 'op no valida']);
}
