<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../modelos/Cargacertificado.php";
requireLogin();

$cert = new Cargacertificado();
$op = $_GET['op'] ?? '';

/**
 * Convierte un .p12/.pfx con algoritmos legacy (RC2-40, MD5...) a formato
 * compatible con OpenSSL 3.x usando el binario openssl con -legacy.
 *
 * Solucion: openssl_pkcs12_read() en PHP+OpenSSL 3.x rechaza certificados
 * que usan pbeWithSHA1And40BitRC2-CBC (comunes en .p12 generados por
 * Llama.pe, certificadosdigital.pe, SUNAT, etc.). Re-empaquetamos el .p12
 * con AES-256-CBC moderno usando el binario openssl que SI tiene -legacy.
 *
 * @param string $pfxPath  Ruta al .p12 original
 * @param string $pass     Clave del certificado
 * @return string|false    Ruta a un nuevo .p12 convertido, o false si fallo
 */
function convertirP12Legacy($pfxPath, $pass)
{
    $opensslExe = 'C:\\xampp\\apache\\bin\\openssl.exe';
    if (!is_file($opensslExe)) return false;

    // Paso 1: extraer clave y certificado en PEM (modo -legacy)
    $tmpPem = tempnam(sys_get_temp_dir(), 'cert_') . '.pem';
    $cmd1 = sprintf(
        '"%s" pkcs12 -legacy -in "%s" -out "%s" -nodes -passin pass:%s 2>&1',
        $opensslExe, $pfxPath, $tmpPem, escapeshellarg($pass)
    );
    // escapeshellarg agrega comillas, removerlas porque ya estamos usando pass:
    $cmd1 = sprintf(
        '"%s" pkcs12 -legacy -in "%s" -out "%s" -nodes -passin "pass:%s" 2>&1',
        $opensslExe, $pfxPath, $tmpPem, str_replace('"', '\\"', $pass)
    );
    exec($cmd1, $out1, $rc1);
    if ($rc1 !== 0 || !is_file($tmpPem) || filesize($tmpPem) === 0) {
        @unlink($tmpPem);
        return false;
    }

    // Paso 2: re-empaquetar como .p12 moderno (AES-256, sin RC2)
    $tmpP12 = tempnam(sys_get_temp_dir(), 'cert_') . '.p12';
    $cmd2 = sprintf(
        '"%s" pkcs12 -in "%s" -export -out "%s" -passin "pass:%s" -passout "pass:%s" 2>&1',
        $opensslExe, $tmpPem, $tmpP12,
        str_replace('"', '\\"', $pass),
        str_replace('"', '\\"', $pass)
    );
    exec($cmd2, $out2, $rc2);
    @unlink($tmpPem);

    if ($rc2 !== 0 || !is_file($tmpP12) || filesize($tmpP12) === 0) {
        @unlink($tmpP12);
        return false;
    }
    return $tmpP12;
}

switch ($op) {
    case 'activo':
        $idempresa = (int)($_REQUEST['idempresa'] ?? 1);
        $r = $cert->activo($idempresa);
        if ($r) $r['clave'] = $r['clave'] ? '••••••••' : '';
        jsonResponse($r);
        break;

    case 'listar':
        $idempresa = (int)($_REQUEST['idempresa'] ?? 1);
        $rs = $cert->listar($idempresa);
        $data = [];
        while ($r = $rs->fetch_assoc()) { $r['clave'] = $r['clave'] ? '••••••••' : ''; $data[] = $r; }
        jsonResponse($data);
        break;

    case 'subir':
        requirePermiso('config_certificado');
        $idempresa = (int)($_POST['idempresa'] ?? 1);
        $clave     = $_POST['clave'] ?? '';
        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['ok' => false, 'msg' => 'Debes seleccionar un archivo .pfx o .p12']);
        }
        $tmp = $_FILES['archivo']['tmp_name'];
        $nombre = basename($_FILES['archivo']['name']);
        if (!preg_match('/\.(pfx|p12)$/i', $nombre)) {
            jsonResponse(['ok' => false, 'msg' => 'Solo archivos .pfx o .p12']);
        }
        if ($clave === '') {
            jsonResponse(['ok' => false, 'msg' => 'La clave del certificado es obligatoria']);
        }

        $contenido = file_get_contents($tmp);
        $check = [];
        $exitoso = @openssl_pkcs12_read($contenido, $check, $clave);

        // Si fallo, intentar detectar si es por algoritmo legacy (RC2-40, MD5)
        // y convertir el .p12 automaticamente
        $tmpConvertido = null;
        if (!$exitoso) {
            $errores = [];
            while ($e = openssl_error_string()) $errores[] = $e;
            $errStr = implode(' ', $errores);

            // Si el error indica algoritmo legacy O simplemente fallo (puede ser legacy
            // sin error explicito), intentar conversion
            $tmpConvertido = convertirP12Legacy($tmp, $clave);
            if ($tmpConvertido) {
                $contenido = file_get_contents($tmpConvertido);
                $exitoso = @openssl_pkcs12_read($contenido, $check, $clave);
            }

            if (!$exitoso) {
                // Limpiar nuevos errores
                while ($e = openssl_error_string()) $errores[] = $e;
                if ($tmpConvertido) @unlink($tmpConvertido);
                jsonResponse([
                    'ok' => false,
                    'msg' => 'La clave del certificado es incorrecta o el archivo es inválido',
                    'detalle' => $errores,
                ]);
            }
            // Si llegamos aqui, la conversion funciono. Reemplazar el archivo origen
            // en $tmp por el convertido para que se guarde el .p12 moderno.
            @copy($tmpConvertido, $tmp);
            @unlink($tmpConvertido);
        }

        // Validar que el certificado tenga clave privada y certificado
        if (empty($check['cert']) || empty($check['pkey'])) {
            jsonResponse(['ok' => false, 'msg' => 'El archivo no contiene un certificado completo (falta clave privada o certificado)']);
        }

        // Extraer info para mostrar al usuario
        $info = openssl_x509_parse($check['cert']);
        $cn   = $info['subject']['CN'] ?? '';
        $ruc  = '';
        if (preg_match('/(\d{11})/', $info['subject']['serialNumber'] ?? $cn, $m)) $ruc = $m[1];
        $vence = $info['validTo_time_t'] ?? null;

        // Guardar en puerto_habana/certificado/
        $destDir = realpath(__DIR__ . '/../certificado');
        if (!$destDir) {
            @mkdir(__DIR__ . '/../certificado', 0755, true);
            $destDir = realpath(__DIR__ . '/../certificado');
        }
        // Nombre seguro: ruc-timestamp.ext
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $nombreSeguro = ($ruc ?: 'cert') . '-' . date('YmdHis') . '.' . $ext;
        $destino = $destDir . DIRECTORY_SEPARATOR . $nombreSeguro;
        if (!@move_uploaded_file($tmp, $destino)) {
            jsonResponse(['ok' => false, 'msg' => 'No se pudo guardar el archivo']);
        }
        $rutaRel = '../certificado/' . $nombreSeguro;
        $tipo = (stripos($cn, 'demo') !== false || stripos($cn, 'beta') !== false) ? 'demo' : 'produccion';
        $id = $cert->insertar($idempresa, $nombreSeguro, $rutaRel, $clave, $tipo);

        jsonResponse([
            'ok' => $id > 0,
            'idcertificado' => $id,
            'cn' => $cn,
            'ruc' => $ruc,
            'vencimiento' => $vence ? date('Y-m-d', $vence) : null,
            'tipo' => $tipo,
        ]);
        break;

    case 'activar':
        requirePermiso('config_certificado');
        $idempresa = (int)($_POST['idempresa'] ?? 1);
        $id = (int)($_POST['idcertificado'] ?? 0);
        jsonResponse(['ok' => (bool)$cert->activar($id, $idempresa)]);
        break;

    case 'eliminar':
        requirePermiso('config_certificado');
        $id = (int)($_POST['idcertificado'] ?? 0);
        jsonResponse(['ok' => (bool)$cert->eliminar($id)]);
        break;

    default:
        jsonResponse(['error' => 'op no valida']);
}
