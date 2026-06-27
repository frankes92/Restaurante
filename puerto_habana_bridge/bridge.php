<?php
// =====================================================================
// PUERTO HABANA POS — BRIDGE DE IMPRESION LOCAL
// =====================================================================
// Este script se ejecuta en una PC del LOCAL del restaurante.
// Consulta cada 3 segundos al servidor cloud para ver si hay trabajos
// de impresion pendientes. Si los hay, los envia a las impresoras
// termicas LAN via TCP/IP en puerto 9100 (protocolo ESC/POS).
//
// EJECUCION:
//   php bridge.php
//
// Para correrlo como servicio Windows, ver README.txt
// =====================================================================

// ---------------- CONFIGURACION ----------------
$CONFIG_FILE = __DIR__ . '/config.php';
if (!file_exists($CONFIG_FILE)) {
    fwrite(STDERR, "ERROR: Falta archivo config.php junto a bridge.php\n");
    fwrite(STDERR, "Copia 'config.example.php' a 'config.php' y editalo.\n");
    exit(1);
}
require $CONFIG_FILE;

// Variables esperadas del config.php:
//   $CLOUD_URL  = 'https://puertohabana.ripasoft.com'
//   $TOKEN      = 'puertohabana-bridge-2026-...'
//   $POLL_SEC   = 3
//   $LOG_FILE   = __DIR__ . '/bridge.log'

if (empty($CLOUD_URL) || empty($TOKEN)) {
    fwrite(STDERR, "ERROR: config.php debe definir \$CLOUD_URL y \$TOKEN\n");
    exit(1);
}
$POLL_SEC = isset($POLL_SEC) ? max(1, (int)$POLL_SEC) : 3;
$LOG_FILE = $LOG_FILE ?? __DIR__ . '/bridge.log';
// Comprobantes (boleta/factura/nota) con diseño exacto: se renderizan con
// Chrome a imagen y se envían como raster a la térmica.
$CHROME_PATH = isset($CHROME_PATH) ? $CHROME_PATH : '';   // vacío = autodetectar
$PRINT_WIDTH = isset($PRINT_WIDTH) ? (int)$PRINT_WIDTH : 576; // 80mm≈576, 58mm≈384

// ---------------- LOG ----------------
function logMsg($msg) {
    global $LOG_FILE;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    echo $line;
    @file_put_contents($LOG_FILE, $line, FILE_APPEND);
}

logMsg("=== Bridge iniciado ===");
logMsg("Cloud:    $CLOUD_URL");
logMsg("Polling:  cada {$POLL_SEC}s");

// ---------------- HTTP helpers ----------------
function httpGet($url) {
    global $TOKEN;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['X-Bridge-Token: ' . $TOKEN, 'Accept: application/json'],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err) return ['ok' => false, 'msg' => $err];
    if ($code !== 200) return ['ok' => false, 'msg' => "HTTP $code"];
    $data = json_decode($resp, true);
    return $data ?: ['ok' => false, 'msg' => 'JSON invalido'];
}

function httpPost($url, $params = []) {
    global $TOKEN;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_HTTPHEADER     => ['X-Bridge-Token: ' . $TOKEN, 'Accept: application/json'],
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

// ---------------- ESC/POS BUILDER ----------------

// Codigos basicos ESC/POS
const ESC = "\x1B";
const GS  = "\x1D";

function escposInit() {
    // Inicializa + selecciona code page CP858 (multilingüe latino con €)
    // y fuente A (la mas grande/legible por defecto)
    return ESC . "@" . escposCodePage() . escposFontA();
}
function escposCodePage() { return ESC . "t" . chr(19); } // CP858 multilingue latino
function escposFontA()    { return ESC . "M" . "\x00"; }   // fuente A (default, mas legible)
function escposFontB()    { return ESC . "M" . "\x01"; }   // fuente B (mas pequena)
function escposCut()      { return GS  . "V" . "\x00"; }   // corte completo
function escposCutPartial(){ return GS . "V" . "\x01"; }
function escposFeed($n = 1) { return ESC . "d" . chr($n); }
function escposCenter()   { return ESC . "a" . "\x01"; }
function escposLeft()     { return ESC . "a" . "\x00"; }
function escposBoldOn()   { return ESC . "E" . "\x01"; }
function escposBoldOff()  { return ESC . "E" . "\x00"; }
function escposSize($w = 0, $h = 0) {
    // GS ! n  : n = (w<<4) | h  ; w, h en 0..7 (0 = normal)
    return GS . "!" . chr(($w << 4) | $h);
}

/**
 * Convierte texto UTF-8 al code page CP858 (que la termica entiende)
 * para que se impriman correctamente acentos, n, ¿, ¡, €, etc.
 * Si algun caracter no existe en CP858, lo translitera (ej. emoji -> ?).
 */
function txt($s) {
    if ($s === null || $s === '') return '';
    $conv = @iconv('UTF-8', 'CP858//TRANSLIT//IGNORE', (string)$s);
    return $conv !== false ? $conv : (string)$s;
}

/**
 * Renderiza un payload de comanda (cocina) o ticket en bytes ESC/POS
 * imitando el diseno de vistas/comanda.php (negrita, dividers, chips +N).
 */
function renderTrabajo($trabajo) {
    $tipo    = $trabajo['tipo'];
    $payload = $trabajo['payload'] ?? [];
    $cols    = max(20, (int)($trabajo['ancho_cols'] ?? 48));

    $out  = escposInit();

    // ============== HEADER ==============
    $out .= escposCenter();

    // Titulo "COMANDA" en doble ancho + alto (la mas grande)
    $out .= escposBoldOn() . escposSize(1, 1);
    $out .= txt("COMANDA") . "\n";
    $out .= escposSize(0, 0) . escposBoldOff();

    // Subtitulo "ORDEN [#00056]" — imita el pill negro del HTML
    $numero = $payload['numero_orden'] ?? '';
    if ($numero) {
        $out .= escposBoldOn();
        $out .= "ORDEN [#" . txt($numero) . "]\n";
        $out .= escposBoldOff();
    }

    // Tag central con guiones (--- POR PREPARAR ---)
    $out .= "\n";
    $tagText = '';
    if ($tipo === 'comanda')           $tagText = 'POR PREPARAR';
    elseif ($tipo === 'comanda_anular') $tagText = 'ANULAR';
    elseif ($tipo === 'prueba')        $tagText = 'PRUEBA';
    else                               $tagText = 'TICKET';

    $tagLine = '--- ' . $tagText . ' ---';
    $out .= escposBoldOn() . escposSize(0, 1);
    $out .= txt($tagLine) . "\n";
    $out .= escposSize(0, 0) . escposBoldOff();

    // Tipo de orden bien visible: LOCAL / PARA LLEVAR / DELIVERY
    if (($tipo === 'comanda' || $tipo === 'comanda_anular') && !empty($payload['tipo_label'])) {
        $out .= escposBoldOn() . escposSize(1, 1);
        $out .= txt($payload['tipo_label']) . "\n";
        $out .= escposSize(0, 0) . escposBoldOff();
    }

    $out .= escposLeft();

    // Divisor grueso (===)
    $out .= str_repeat('=', $cols) . "\n";

    // ============== INFO MESA / MOZO ==============
    if (!empty($payload['mesa'])) {
        $out .= escposBoldOn();
        $out .= txt(rellenar($payload['mesa'], $payload['fecha'] ?? '', $cols)) . "\n";
        $out .= escposBoldOff();
    }
    if (!empty($payload['mozo'])) {
        $out .= txt(rellenar('Mozo:', $payload['mozo'], $cols)) . "\n";
    }

    // ============== RESUMEN DE CONSUMO (comida / bebida) ==============
    if ($tipo === 'comanda' || $tipo === 'comanda_anular') {
        $undF = function ($n) {
            $n = (float)$n;
            return (floor($n) == $n) ? (string)(int)$n : number_format($n, 2);
        };
        $etiqueta = ($tipo === 'comanda_anular') ? 'ESTA ANULACION' : 'ESTA COMANDA';
        $estaTxt  = 'Com ' . $undF($payload['esta_comida'] ?? 0) . '  Beb ' . $undF($payload['esta_bebida'] ?? 0);
        $acumTxt  = 'Com ' . $undF($payload['acum_comida'] ?? 0) . '  Beb ' . $undF($payload['acum_bebida'] ?? 0);
        $out .= str_repeat('-', $cols) . "\n";
        $out .= txt(rellenar($etiqueta, $estaTxt, $cols)) . "\n";
        $out .= escposBoldOn();
        $out .= txt(rellenar('ACUMULADO DIA', $acumTxt, $cols)) . "\n";
        $out .= escposBoldOff();
    }

    // Divisor grueso doble (=== / ===)
    $out .= str_repeat('=', $cols) . "\n";

    // ============== ITEMS ==============
    if (!empty($payload['items']) && is_array($payload['items'])) {
        foreach ($payload['items'] as $idx => $it) {
            $cant = $it['cantidad'] ?? 0;
            $cantTxt = (float)$cant == (int)$cant ? (string)(int)$cant : number_format((float)$cant, 2);
            $signo   = ($tipo === 'comanda_anular') ? '-' : '+';
            // Mayusculas con mb_strtoupper para que acentos (á→Á) tambien se conviertan
            $nombre  = mb_strtoupper($it['nombre'] ?? '', 'UTF-8');
            $esCortesia = (int)($it['cortesia'] ?? 0) === 1;

            $out .= "\n";

            // Item compacto: chip + nombre en una sola linea, tamano normal con negrita
            $out .= escposBoldOn();
            $out .= " [ $signo$cantTxt ]  " . txt($nombre) . "\n";
            $out .= escposBoldOff();

            // Marca de CORTESIA debajo del nombre
            if ($esCortesia) {
                $out .= escposBoldOn();
                $out .= "         *** CORTESIA ***\n";
                $out .= escposBoldOff();
            }

            // Nota del item (mas pequena, sin negrita)
            if (!empty($it['nota'])) {
                $out .= "         > " . txt($it['nota']) . "\n";
            }

            // Divisor punteado entre items (- - - - -)
            if ($idx < count($payload['items']) - 1) {
                $out .= str_repeat('- ', (int)($cols / 2)) . "\n";
            }
        }
    } elseif (!empty($payload['lineas']) && is_array($payload['lineas'])) {
        // Para impresiones de prueba
        foreach ($payload['lineas'] as $linea) {
            $out .= txt($linea) . "\n";
        }
    }

    // ============== OBSERVACION ==============
    if (!empty($payload['observacion'])) {
        $out .= "\n";
        $out .= str_repeat('=', $cols) . "\n";
        $out .= escposBoldOn();
        $out .= ">> OBSERVACION:\n";
        $out .= escposBoldOff();
        $out .= txt($payload['observacion']) . "\n";
    }

    // ============== FOOTER ==============
    $out .= "\n";
    $out .= str_repeat('=', $cols) . "\n";
    $out .= escposCenter();
    $out .= escposBoldOn() . escposSize(0, 1);
    $out .= txt(">>> COCINA <<<") . "\n";
    $out .= escposSize(0, 0) . escposBoldOff();

    if (!empty($payload['items'])) {
        $totalUnidades = 0;
        foreach ($payload['items'] as $it) { $totalUnidades += (float)($it['cantidad'] ?? 0); }
        $totalTxt = (int)$totalUnidades == $totalUnidades ? (string)(int)$totalUnidades : number_format($totalUnidades, 2);
        $sufijo = ($tipo === 'comanda_anular') ? ' ANULADAS' : '';
        $out .= txt("$totalTxt unidad(es)$sufijo") . "\n";
    }
    $out .= escposLeft();

    // Feed y corte
    $out .= escposFeed(4);
    if (!empty($trabajo['cortar_papel'])) {
        $out .= escposCut();
    }
    return $out;
}

function rellenar($izq, $der, $cols) {
    $izq = (string)$izq; $der = (string)$der;
    $espacio = $cols - mb_strlen($izq) - mb_strlen($der);
    if ($espacio < 1) $espacio = 1;
    return $izq . str_repeat(' ', $espacio) . $der;
}

// ---------------- TCP PRINT ----------------
function enviarATcp($ip, $puerto, $bytes, &$errMsg) {
    $puerto = (int)$puerto ?: 9100;
    $sock = @stream_socket_client("tcp://$ip:$puerto", $errno, $errstr, 5);
    if (!$sock) {
        $errMsg = "Conexion fallida ($ip:$puerto): $errstr";
        return false;
    }
    stream_set_timeout($sock, 5);
    $w = fwrite($sock, $bytes);
    fclose($sock);
    if ($w === false || $w < strlen($bytes)) {
        $errMsg = 'Escritura parcial al socket';
        return false;
    }
    return true;
}

// ---------------- COMPROBANTE: imagen exacta -> raster ESC/POS ----------------

// Ubica el ejecutable de Chrome/Edge (para "fotografiar" el comprobante).
function rutaChrome() {
    global $CHROME_PATH;
    if (!empty($CHROME_PATH) && file_exists($CHROME_PATH)) return $CHROME_PATH;
    $cands = [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        (getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA') . '\\Google\\Chrome\\Application\\chrome.exe' : ''),
        'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
    ];
    foreach ($cands as $c) { if ($c && file_exists($c)) return $c; }
    return null;
}

// Genera los bytes ESC/POS de un comprobante (boleta/factura/nota) tomando una
// "foto" del diseño real del sistema y convirtiéndola a imagen térmica.
function comprobanteABytes($trabajo, &$err) {
    global $CLOUD_URL, $TOKEN, $PRINT_WIDTH;
    $payload = $trabajo['payload'] ?? [];
    $idorden = (int)($payload['idorden'] ?? 0);
    if ($idorden <= 0) { $err = 'comprobante sin idorden'; return null; }

    $chrome = rutaChrome();
    if (!$chrome) { $err = 'Chrome/Edge no encontrado (define $CHROME_PATH en config.php)'; return null; }

    $w = (int)($PRINT_WIDTH ?: 576);
    $url = rtrim($CLOUD_URL, '/') . '/vistas/comprobante.php?id=' . $idorden
         . '&token=' . rawurlencode($TOKEN) . '&formato=ticket&w=' . $w;

    $png = tempnam(sys_get_temp_dir(), 'comp_') . '.png';
    $perfil = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ph_bridge_chrome';
    $cmd = '"' . $chrome . '" --headless=new --disable-gpu --no-sandbox --hide-scrollbars'
         . ' --user-data-dir="' . $perfil . '" --no-first-run --disable-extensions --disable-default-apps'
         . ' --force-device-scale-factor=1 --window-size=' . $w . ',3000'
         . ' --run-all-compositor-stages-before-draw'
         . ' --virtual-time-budget=8000 --screenshot="' . $png . '" "' . $url . '" 2>&1';
    @exec($cmd, $o, $rc);

    if (!file_exists($png) || filesize($png) < 100) {
        $err = 'Chrome no generó la imagen del comprobante: ' . trim(implode(' | ', (array)$o));
        @unlink($png); return null;
    }

    $bytes = imagenARasterEscpos($png, $w, $err);
    @unlink($png);
    return $bytes;
}

// Convierte una imagen PNG a bytes ESC/POS (comando raster GS v 0), en bandas.
function imagenARasterEscpos($pngFile, $maxWidth, &$err) {
    if (!function_exists('imagecreatefrompng')) { $err = 'PHP sin extensión GD'; return null; }
    $img = @imagecreatefrompng($pngFile);
    if (!$img) { $err = 'No se pudo leer la imagen'; return null; }
    $w = imagesx($img); $h = imagesy($img);
    if ($w > $maxWidth) {
        $nh  = max(1, (int)round($h * $maxWidth / $w));
        $res = imagecreatetruecolor($maxWidth, $nh);
        imagefill($res, 0, 0, imagecolorallocate($res, 255, 255, 255));
        imagecopyresampled($res, $img, 0, 0, 0, 0, $maxWidth, $nh, $w, $h);
        imagedestroy($img); $img = $res; $w = $maxWidth; $h = $nh;
    }
    $bytesPerRow = (int)(($w + 7) / 8);
    $out = escposInit();
    $banda = 128; // filas por bloque (evita saturar el buffer de la térmica)
    for ($y0 = 0; $y0 < $h; $y0 += $banda) {
        $rows = min($banda, $h - $y0);
        $data = '';
        for ($y = $y0; $y < $y0 + $rows; $y++) {
            for ($bx = 0; $bx < $bytesPerRow; $bx++) {
                $b = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = $bx * 8 + $bit;
                    if ($x < $w) {
                        $rgb = imagecolorat($img, $x, $y);
                        $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $bl = $rgb & 0xFF;
                        if ((0.299 * $r + 0.587 * $g + 0.114 * $bl) < 140) $b |= (0x80 >> $bit);
                    }
                }
                $data .= chr($b);
            }
        }
        $xL = $bytesPerRow & 0xFF; $xH = ($bytesPerRow >> 8) & 0xFF;
        $yL = $rows & 0xFF;        $yH = ($rows >> 8) & 0xFF;
        $out .= GS . "v0" . chr(0) . chr($xL) . chr($xH) . chr($yL) . chr($yH) . $data;
    }
    imagedestroy($img);
    $out .= escposFeed(4) . escposCut();
    return $out;
}

// ---------------- COMPROBANTE: ESC/POS desde el servidor (PLAN B) ----------------
// Pide al servidor los bytes ESC/POS ya armados (texto + QR nativo) y los
// devuelve listos para enviar a la térmica. Sin Chrome, sin GD.
function comprobanteServidor($trabajo, &$err) {
    global $CLOUD_URL, $TOKEN, $PRINT_WIDTH;
    $payload = $trabajo['payload'] ?? [];
    $idorden = (int)($payload['idorden'] ?? 0);
    if ($idorden <= 0) { $err = 'comprobante sin idorden'; return null; }

    // Columnas según el ancho del papel: 80mm ~ 48, 58mm ~ 32
    $cols = ((int)($PRINT_WIDTH ?: 576) <= 420) ? 32 : 48;
    $url = rtrim($CLOUD_URL, '/') . '/ajax/comprobante_escpos.php?id=' . $idorden
         . '&token=' . rawurlencode($TOKEN) . '&cols=' . $cols;

    $r = httpGet($url);
    if (empty($r['ok']) || empty($r['b64'])) {
        $err = 'comprobante: ' . ($r['msg'] ?? 'respuesta inválida del servidor');
        return null;
    }
    $bytes = base64_decode($r['b64'], true);
    if ($bytes === false || $bytes === '') { $err = 'comprobante: base64 inválido'; return null; }
    return $bytes;
}

// ---------------- LOOP ----------------
$urlPendientes = rtrim($CLOUD_URL, '/') . '/ajax/cola_impresion.php?op=pendientes';
$urlImpreso    = rtrim($CLOUD_URL, '/') . '/ajax/cola_impresion.php?op=marcar_impreso';
$urlError      = rtrim($CLOUD_URL, '/') . '/ajax/cola_impresion.php?op=marcar_error';

while (true) {
    $r = httpGet($urlPendientes);
    if (empty($r['ok'])) {
        logMsg('Poll error: ' . ($r['msg'] ?? 'desconocido'));
        sleep($POLL_SEC);
        continue;
    }

    $trabajos = $r['trabajos'] ?? [];
    foreach ($trabajos as $trabajo) {
        $idcola = (int)$trabajo['idcola'];
        $nombre = $trabajo['impresora_nombre'] ?? '';
        $ip     = $trabajo['impresora_ip']     ?? '';
        $puerto = $trabajo['impresora_puerto'] ?? 9100;

        try {
            $err = '';
            if (($trabajo['tipo'] ?? '') === 'comprobante') {
                // Boleta/factura/nota: el SERVIDOR arma el ESC/POS (texto + QR
                // nativo de la térmica). No depende de Chrome ni de GD.
                $bytes = comprobanteServidor($trabajo, $err);
            } else {
                $bytes = renderTrabajo($trabajo);   // comandas/ticket por texto ESC/POS
            }
            if ($bytes === null || $bytes === false || $bytes === '') {
                logMsg("ERR #$idcola -> " . ($err ?: 'no se pudo generar el trabajo'));
                httpPost($urlError, ['idcola' => $idcola, 'msg' => ($err ?: 'gen')]);
                continue;
            }
            $ok = enviarATcp($ip, $puerto, $bytes, $err);
            if ($ok) {
                logMsg("OK #$idcola -> $nombre ($ip:$puerto)");
                httpPost($urlImpreso, ['idcola' => $idcola]);
            } else {
                logMsg("ERR #$idcola -> $err");
                httpPost($urlError, ['idcola' => $idcola, 'msg' => $err]);
            }
        } catch (Throwable $e) {
            logMsg("EXC #$idcola -> " . $e->getMessage());
            httpPost($urlError, ['idcola' => $idcola, 'msg' => $e->getMessage()]);
        }
    }

    sleep($POLL_SEC);
}
