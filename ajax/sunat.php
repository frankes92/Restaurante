<?php
require_once __DIR__ . "/../config/auth.php";
requireLogin();

header('Content-Type: application/json; charset=utf-8');

// =====================================================================
// Configuracion del API decolecta.com (RENIEC/SUNAT)
// Si el token vence, genera uno nuevo en https://decolecta.com
// =====================================================================
$TOKEN    = 'sk_15625.SZFK0AwYTHL8IAu7j1HZKaTW5NSyH59W';
$BASE_URL = 'https://api.decolecta.com/v1';

$op       = $_GET['op']        ?? '';
$tipoDoc  = $_POST['tipo_doc'] ?? $_GET['tipo_doc'] ?? '';   // '1' = DNI, '6' = RUC
$numero   = trim($_POST['numero'] ?? $_GET['numero'] ?? '');

if ($op !== 'consultar') {
    echo json_encode(['ok' => false, 'msg' => 'op no valida']);
    exit;
}

if ($numero === '') {
    echo json_encode(['ok' => false, 'msg' => 'Numero requerido']);
    exit;
}

// Validacion basica de longitud
if ($tipoDoc === '1' && strlen($numero) !== 8) {
    echo json_encode(['ok' => false, 'msg' => 'DNI debe tener 8 digitos']);
    exit;
}
if ($tipoDoc === '6' && strlen($numero) !== 11) {
    echo json_encode(['ok' => false, 'msg' => 'RUC debe tener 11 digitos']);
    exit;
}

$endpoint = ($tipoDoc === '6')
    ? $BASE_URL . '/sunat/ruc?numero='  . urlencode($numero)
    : $BASE_URL . '/reniec/dni?numero=' . urlencode($numero);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'GET',
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $TOKEN,
        'Accept: application/json',
    ],
]);
$resp     = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$err      = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode(['ok' => false, 'msg' => 'Error red: ' . $err]);
    exit;
}
if ($httpCode === 404) {
    $tipoLbl = ($tipoDoc === '6') ? 'RUC' : 'DNI';
    echo json_encode(['ok' => false, 'msg' => $tipoLbl . ' no encontrado en ' . ($tipoDoc === '6' ? 'SUNAT' : 'RENIEC')]);
    exit;
}
if ($httpCode === 401 || $httpCode === 403) {
    echo json_encode(['ok' => false, 'msg' => 'Token expirado o invalido. Actualizalo en ajax/sunat.php', 'http' => $httpCode]);
    exit;
}
if ($httpCode === 429) {
    echo json_encode(['ok' => false, 'msg' => 'Limite de consultas alcanzado, intenta luego', 'http' => $httpCode]);
    exit;
}
if ($httpCode >= 400) {
    echo json_encode(['ok' => false, 'msg' => 'API respondio ' . $httpCode, 'http' => $httpCode]);
    exit;
}

$data = json_decode($resp, true);
if (!is_array($data)) {
    echo json_encode(['ok' => false, 'msg' => 'Respuesta invalida del API']);
    exit;
}

// Normalizar respuesta a formato uniforme para el frontend
$out = ['ok' => true, 'tipo_doc' => $tipoDoc, 'numero' => $numero];

if ($tipoDoc === '6') {
    // RUC -> decolecta: razon_social, direccion, distrito, provincia, departamento, ubigeo, estado, condicion
    $out['razon_social'] = $data['razon_social']     ?? '';
    $out['direccion']    = $data['direccion']        ?? '';
    $out['departamento'] = $data['departamento']     ?? '';
    $out['provincia']    = $data['provincia']        ?? '';
    $out['distrito']     = $data['distrito']         ?? '';
    $out['ubigeo']       = $data['ubigeo']           ?? '';
    $out['estado']       = $data['estado']           ?? '';
    $out['condicion']    = $data['condicion']        ?? '';
} else {
    // DNI -> decolecta: first_name, first_last_name, second_last_name, full_name, document_number
    $nombres  = trim($data['first_name']        ?? '');
    $apePat   = trim($data['first_last_name']   ?? '');
    $apeMat   = trim($data['second_last_name']  ?? '');
    $completo = trim($data['full_name'] ?? trim($nombres . ' ' . $apePat . ' ' . $apeMat));
    $out['razon_social']     = $completo;
    $out['nombres']          = $nombres;
    $out['apellido_paterno'] = $apePat;
    $out['apellido_materno'] = $apeMat;
}

echo json_encode($out);
