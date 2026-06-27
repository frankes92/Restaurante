<?php
/**
 * Cliente SOAP para enviar comprobantes a SUNAT (sendBill, sendSummary, getStatus).
 *
 * NO usa la clase SoapClient de PHP porque el WSDL de SUNAT tiene definiciones
 * duplicadas ('BillServicePortBinding' already defined) que SoapClient no puede
 * parsear. En su lugar arma manualmente el sobre SOAP y lo envia via curl.
 *
 * Soporta ambiente BETA y PRODUCCION.
 */
class SunatEnviador
{
    const ENDPOINT_BETA = 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService';
    const ENDPOINT_PROD = 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService';

    // Endpoints de consulta de CDR (getStatusCdr) - servicio billConsultService.
    // IMPORTANTE: SUNAT NO publica este servicio en BETA (solo en produccion,
    // confirmado en su doc oficial "Servicios Web disponibles"). En beta da 404,
    // por eso ComprobanteElectronico::consultarEnSunat() evita llamarlo y avisa.
    // CONSULT_BETA queda solo por compatibilidad; no hay endpoint de consulta beta real.
    const CONSULT_BETA = 'https://www.sunat.gob.pe/ol-it-wsconscpegem-beta/billConsultService';
    const CONSULT_PROD = 'https://e-factura.sunat.gob.pe/ol-it-wsconscpegem/billConsultService';

    /**
     * Envia un comprobante (boleta, factura, NC, ND) usando sendBill.
     * SUNAT responde con el CDR (zip en base64) en applicationResponse.
     */
    public function enviar($zipPath, $zipName, $rucEmisor, $usuarioSol, $claveSol, $ambiente = 'beta')
    {
        if (!file_exists($zipPath)) {
            return ['ok' => false, 'mensaje' => 'ZIP no encontrado: ' . $zipPath];
        }

        $endpoint = ($ambiente === 'produccion') ? self::ENDPOINT_PROD : self::ENDPOINT_BETA;
        $usuario  = $rucEmisor . $usuarioSol;
        $contenidoZipB64 = base64_encode(file_get_contents($zipPath));

        $sobreSoap = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
            <soapenv:Header>
                <wsse:Security>
                    <wsse:UsernameToken>
                        <wsse:Username>' . htmlspecialchars($usuario, ENT_XML1) . '</wsse:Username>
                        <wsse:Password>' . htmlspecialchars($claveSol, ENT_XML1) . '</wsse:Password>
                    </wsse:UsernameToken>
                </wsse:Security>
            </soapenv:Header>
            <soapenv:Body>
                <ser:sendBill>
                    <fileName>' . htmlspecialchars($zipName, ENT_XML1) . '</fileName>
                    <contentFile>' . $contenidoZipB64 . '</contentFile>
                </ser:sendBill>
            </soapenv:Body>
        </soapenv:Envelope>';

        $resp = $this->ejecutarSoap($endpoint, $sobreSoap);
        if (!$resp['ok']) return $resp;

        // Parsear respuesta: <applicationResponse> contiene el ZIP del CDR en base64
        $doc = new DOMDocument();
        @$doc->loadXML($resp['response']);

        $appRespNodes = $doc->getElementsByTagName('applicationResponse');
        if ($appRespNodes->length > 0) {
            $cdrB64 = trim($appRespNodes->item(0)->nodeValue);
            $cdrBytes = base64_decode($cdrB64);
            $cdrInfo = $this->parsearCdrZip($cdrBytes);
            return [
                'ok'            => true,
                'codigo'        => $cdrInfo['codigo']      ?? '0',
                'mensaje'       => $cdrInfo['descripcion'] ?? 'Aceptado',
                'observaciones' => $cdrInfo['observaciones'] ?? null,
                'cdr_zip'       => $cdrB64,
            ];
        }

        // Buscar fault de SOAP
        $fc = $doc->getElementsByTagName('faultcode');
        $fs = $doc->getElementsByTagName('faultstring');
        if ($fc->length > 0) {
            $code = $fc->item(0)->nodeValue;
            $msg  = $fs->length > 0 ? $fs->item(0)->nodeValue : '';
            if (preg_match('/(\d{3,4})/', $code, $m)) $code = $m[1];
            return ['ok' => false, 'codigo' => $code, 'mensaje' => $msg];
        }

        return ['ok' => false, 'codigo' => '', 'mensaje' => 'Respuesta SUNAT inesperada (HTTP ' . $resp['http'] . ')'];
    }

    /**
     * Ejecuta una peticion SOAP via curl directo. Devuelve {ok, response, http, mensaje}.
     * Incluye reintento automatico ante timeouts/errores transitorios.
     */
    private function ejecutarSoap($endpoint, $sobreSoap, $intento = 1)
    {
        $headers = [
            'Content-Type: text/xml; charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction: ',
            'Connection: close',
            'Content-Length: ' . strlen($sobreSoap),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sobreSoap);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);   // timeout de conexion (30s)
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);          // timeout total (120s - SUNAT puede tardar)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PUERTO HABANA-POS/1.0 PHP-cURL');
        // Forzar IPv4 — SUNAT a veces no responde por IPv6
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        // TCP keepalive para conexiones lentas
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 60);
        curl_setopt($ch, CURLOPT_TCP_KEEPINTVL, 60);

        // Cargar cacert.pem si existe (mejor practica)
        $cacert = __DIR__ . '/../config/cacert.pem';
        if (is_file($cacert)) {
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        }

        $response = curl_exec($ch);
        $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        $errno    = curl_errno($ch);
        curl_close($ch);

        // Reintentar 1 vez ante timeouts o errores de red transitorios
        $esTimeout = in_array($errno, [
            CURLE_OPERATION_TIMEOUTED,    // 28
            CURLE_COULDNT_CONNECT,        // 7
            CURLE_GOT_NOTHING,            // 52
            CURLE_RECV_ERROR,             // 56
        ], true);

        if ($esTimeout && $intento === 1) {
            error_log("[SUNAT] Timeout intento $intento, reintentando... ($err)");
            sleep(2);
            return $this->ejecutarSoap($endpoint, $sobreSoap, 2);
        }

        if ($err) {
            $hint = '';
            if ($errno === CURLE_OPERATION_TIMEOUTED) {
                $hint = ' SUNAT no respondió a tiempo. Vuelve a intentar en unos segundos.';
            } elseif ($errno === CURLE_COULDNT_CONNECT) {
                $hint = ' No se pudo conectar a SUNAT. Verifica tu conexión a Internet.';
            }
            return ['ok' => false, 'mensaje' => 'cURL error: ' . $err . '.' . $hint, 'http' => $http];
        }
        if (!$response) return ['ok' => false, 'mensaje' => 'Respuesta vacía de SUNAT (HTTP ' . $http . ')', 'http' => $http];

        return ['ok' => true, 'response' => $response, 'http' => $http];
    }

    /**
     * Envia un Resumen Diario (RC) o Comunicacion de Baja (RA) a SUNAT.
     * SUNAT responde con un ticket; el CDR se obtiene despues con consultarTicket().
     */
    public function enviarResumen($zipPath, $zipName, $rucEmisor, $usuarioSol, $claveSol, $ambiente = 'beta')
    {
        if (!file_exists($zipPath)) {
            return ['ok' => false, 'mensaje' => 'ZIP no encontrado: ' . $zipPath];
        }

        $endpoint = ($ambiente === 'produccion') ? self::ENDPOINT_PROD : self::ENDPOINT_BETA;
        $usuario  = $rucEmisor . $usuarioSol;
        $contenidoZipB64 = base64_encode(file_get_contents($zipPath));

        $sobreSoap = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
            <soapenv:Header>
                <wsse:Security>
                    <wsse:UsernameToken>
                        <wsse:Username>' . htmlspecialchars($usuario, ENT_XML1) . '</wsse:Username>
                        <wsse:Password>' . htmlspecialchars($claveSol, ENT_XML1) . '</wsse:Password>
                    </wsse:UsernameToken>
                </wsse:Security>
            </soapenv:Header>
            <soapenv:Body>
                <ser:sendSummary>
                    <fileName>' . htmlspecialchars($zipName, ENT_XML1) . '</fileName>
                    <contentFile>' . $contenidoZipB64 . '</contentFile>
                </ser:sendSummary>
            </soapenv:Body>
        </soapenv:Envelope>';

        $resp = $this->ejecutarSoap($endpoint, $sobreSoap);
        if (!$resp['ok']) return $resp;

        $doc = new DOMDocument();
        @$doc->loadXML($resp['response']);

        $ticketNodes = $doc->getElementsByTagName('ticket');
        if ($ticketNodes->length > 0) {
            $ticket = trim($ticketNodes->item(0)->nodeValue);
            return [
                'ok'      => true,
                'ticket'  => $ticket,
                'mensaje' => 'Resumen enviado, ticket recibido: ' . $ticket,
            ];
        }

        $fc = $doc->getElementsByTagName('faultcode');
        $fs = $doc->getElementsByTagName('faultstring');
        if ($fc->length > 0) {
            $code = $fc->item(0)->nodeValue;
            $msg  = $fs->length > 0 ? $fs->item(0)->nodeValue : '';
            if (preg_match('/(\d{3,4})/', $code, $m)) $code = $m[1];
            return ['ok' => false, 'codigo' => $code, 'mensaje' => $msg];
        }

        return ['ok' => false, 'codigo' => '', 'mensaje' => 'Respuesta SUNAT sin ticket (HTTP ' . $resp['http'] . ')'];
    }

    /**
     * Consulta el estado de un ticket de resumen (getStatus) usando curl directo.
     * SoapClient de PHP a veces falla parseando la respuesta de SUNAT, asi que
     * armamos el sobre SOAP manualmente y parseamos el response con DOMDocument.
     */
    public function consultarTicket($ticket, $rucEmisor, $usuarioSol, $claveSol, $ambiente = 'beta')
    {
        $endpoint = ($ambiente === 'produccion') ? self::ENDPOINT_PROD : self::ENDPOINT_BETA;
        $usuario = $rucEmisor . $usuarioSol;

        $xmlEnvio = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
            <soapenv:Header>
                <wsse:Security>
                    <wsse:UsernameToken>
                        <wsse:Username>' . $usuario . '</wsse:Username>
                        <wsse:Password>' . $claveSol . '</wsse:Password>
                    </wsse:UsernameToken>
                </wsse:Security>
            </soapenv:Header>
            <soapenv:Body>
                <ser:getStatus>
                    <ticket>' . $ticket . '</ticket>
                </ser:getStatus>
            </soapenv:Body>
        </soapenv:Envelope>';

        $headers = [
            'Content-Type: text/xml; charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction: ',
            'Content-Length: ' . strlen($xmlEnvio),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlEnvio);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // SUNAT a veces responde 301 (www → ww1): seguir la redireccion
        // reenviando el POST/cuerpo, igual que en el envio.
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['ok' => false, 'mensaje' => 'cURL error: ' . $curlErr];
        }
        if (!$response) {
            return ['ok' => false, 'mensaje' => 'Respuesta vacia (HTTP ' . $httpCode . ')'];
        }

        // Parsear el SOAP response
        $doc = new DOMDocument();
        @$doc->loadXML($response);

        // Buscar statusCode y content (los nombres no llevan namespace en el body de la respuesta SUNAT)
        $statusCodeNodes = $doc->getElementsByTagName('statusCode');
        $contentNodes    = $doc->getElementsByTagName('content');
        $faultcodeNodes  = $doc->getElementsByTagName('faultcode');
        $faultstrNodes   = $doc->getElementsByTagName('faultstring');

        // Caso de fault SOAP
        if ($faultcodeNodes->length > 0) {
            $code = $faultcodeNodes->item(0)->nodeValue;
            $msg  = $faultstrNodes->length > 0 ? $faultstrNodes->item(0)->nodeValue : '';
            if (preg_match('/(\d{3,4})/', $code, $m)) $code = $m[1];
            return ['ok' => false, 'codigo' => $code, 'mensaje' => $msg];
        }

        if ($statusCodeNodes->length === 0) {
            return ['ok' => false, 'mensaje' => 'Sin respuesta de SUNAT (HTTP ' . $httpCode . ')'];
        }

        $statusCode = $statusCodeNodes->item(0)->nodeValue;
        $contentB64 = $contentNodes->length > 0 ? $contentNodes->item(0)->nodeValue : '';
        $cdrInfo = [];
        if ($contentB64) {
            $cdrBytes = base64_decode($contentB64);
            $cdrInfo = $this->parsearCdrZip($cdrBytes);
        }

        // Codigos: 0=aceptado, 98=en proceso, 99=rechazado
        if ($statusCode === '0' || $statusCode === '98' || $statusCode === '99') {
            return [
                'ok'            => $statusCode === '0',
                'estado'        => $statusCode,
                'codigo'        => $cdrInfo['codigo']      ?? '',
                'mensaje'       => $cdrInfo['descripcion'] ?? ('Estado: ' . $statusCode),
                'observaciones' => $cdrInfo['observaciones'] ?? null,
                'cdr_zip'       => $contentB64,
                'en_proceso'    => $statusCode === '98',
            ];
        }
        return ['ok' => false, 'mensaje' => 'Status desconocido: ' . $statusCode];
    }

    /**
     * Consulta el CDR de un comprobante individual ya enviado (operacion getStatusCdr).
     * No requiere el ticket, solo los datos del comprobante (RUC, tipo, serie, numero).
     * Util para re-descargar el CDR perdido o reconsultar el estado real en SUNAT.
     */
    public function consultarCdr($rucEmisor, $tipoDoc, $serie, $numero, $usuarioSol, $claveSol, $ambiente = 'beta')
    {
        // getStatusCdr esta en el servicio billConsultService, NO en billService
        $endpoint = ($ambiente === 'produccion') ? self::CONSULT_PROD : self::CONSULT_BETA;
        $usuario  = $rucEmisor . $usuarioSol;
        $numeroInt = (int)$numero;

        $xmlEnvio = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
            <soapenv:Header>
                <wsse:Security>
                    <wsse:UsernameToken>
                        <wsse:Username>' . htmlspecialchars($usuario, ENT_XML1) . '</wsse:Username>
                        <wsse:Password>' . htmlspecialchars($claveSol, ENT_XML1) . '</wsse:Password>
                    </wsse:UsernameToken>
                </wsse:Security>
            </soapenv:Header>
            <soapenv:Body>
                <ser:getStatusCdr>
                    <rucComprobante>' . htmlspecialchars($rucEmisor, ENT_XML1) . '</rucComprobante>
                    <tipoComprobante>' . htmlspecialchars($tipoDoc, ENT_XML1) . '</tipoComprobante>
                    <serieComprobante>' . htmlspecialchars($serie, ENT_XML1) . '</serieComprobante>
                    <numeroComprobante>' . $numeroInt . '</numeroComprobante>
                </ser:getStatusCdr>
            </soapenv:Body>
        </soapenv:Envelope>';

        $headers = [
            'Content-Type: text/xml; charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction: ',
            'Content-Length: ' . strlen($xmlEnvio),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlEnvio);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        // Seguir redireccion 301 (www → ww1) reenviando el POST.
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) return ['ok' => false, 'mensaje' => 'cURL error: ' . $curlErr];
        if (!$response) return ['ok' => false, 'mensaje' => 'Respuesta vacia (HTTP ' . $httpCode . ')'];

        $doc = new DOMDocument();
        @$doc->loadXML($response);

        // Fault SOAP (ej. 0156 No existe el comprobante)
        $fc = $doc->getElementsByTagName('faultcode');
        $fs = $doc->getElementsByTagName('faultstring');
        if ($fc->length > 0) {
            $code = $fc->item(0)->nodeValue;
            $msg  = $fs->length > 0 ? $fs->item(0)->nodeValue : '';
            if (preg_match('/(\d{3,4})/', $code, $m)) $code = $m[1];
            return ['ok' => false, 'codigo' => $code, 'mensaje' => $msg];
        }

        $statusCodeNodes = $doc->getElementsByTagName('statusCode');
        $statusMsgNodes  = $doc->getElementsByTagName('statusMessage');
        $contentNodes    = $doc->getElementsByTagName('content');

        if ($statusCodeNodes->length === 0) {
            // Si SUNAT devolvio HTML/texto plano por timeout/balanceador, mostrar pista util
            $snippet = strip_tags(substr(trim($response), 0, 200));
            return ['ok' => false, 'mensaje' => 'SUNAT no devolvio statusCode (HTTP ' . $httpCode . '). Respuesta: ' . $snippet];
        }

        $statusCode = $statusCodeNodes->item(0)->nodeValue;
        $statusMsg  = $statusMsgNodes->length > 0 ? $statusMsgNodes->item(0)->nodeValue : '';
        $contentB64 = $contentNodes->length > 0 ? trim($contentNodes->item(0)->nodeValue) : '';

        // Codigos SUNAT getStatusCdr:
        //   0004 = Aceptado (CDR presente)
        //   0005 = Aceptado con observaciones
        //   0006 = Rechazado
        //   0156 = No existe (numero no enviado)
        //   otros: error de credenciales/perfil
        $cdrInfo = [];
        if ($contentB64) {
            $cdrBytes = base64_decode($contentB64);
            $cdrInfo  = $this->parsearCdrZip($cdrBytes);
        }

        $aceptado = in_array($statusCode, ['0004', '0005'], true);
        return [
            'ok'            => $aceptado,
            'estado'        => $statusCode,
            'codigo'        => $cdrInfo['codigo']      ?? $statusCode,
            'mensaje'       => $cdrInfo['descripcion'] ?? $statusMsg ?: 'Estado: ' . $statusCode,
            'observaciones' => $cdrInfo['observaciones'] ?? null,
            'cdr_zip'       => $contentB64,
            'con_observ'    => $statusCode === '0005',
            'rechazado'     => $statusCode === '0006',
            'no_existe'     => $statusCode === '0156',
        ];
    }

    private function parsearCdrZip($cdrBytes)
    {
        // Guardar temporalmente y descomprimir para extraer el XML
        $tmpZip = tempnam(sys_get_temp_dir(), 'cdr_') . '.zip';
        file_put_contents($tmpZip, $cdrBytes);
        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== TRUE) { @unlink($tmpZip); return []; }
        $tmpDir = sys_get_temp_dir() . '/cdr_' . uniqid();
        @mkdir($tmpDir);
        $zip->extractTo($tmpDir);
        $zip->close();
        @unlink($tmpZip);

        $xmlFile = null;
        foreach (scandir($tmpDir) as $f) {
            if (preg_match('/\.xml$/i', $f)) { $xmlFile = $tmpDir . '/' . $f; break; }
        }
        if (!$xmlFile || !file_exists($xmlFile)) return [];

        $doc = new DOMDocument();
        @$doc->load($xmlFile);
        $codigo = '';
        $desc   = '';
        $obs    = [];
        $codeNodes = $doc->getElementsByTagName('ResponseCode');
        $descNodes = $doc->getElementsByTagName('Description');
        if ($codeNodes->length > 0) $codigo = $codeNodes->item(0)->nodeValue;
        if ($descNodes->length > 0) $desc   = $descNodes->item(0)->nodeValue;
        $notes = $doc->getElementsByTagName('Note');
        foreach ($notes as $n) $obs[] = $n->nodeValue;

        @unlink($xmlFile);
        @rmdir($tmpDir);

        return ['codigo' => $codigo, 'descripcion' => $desc, 'observaciones' => $obs];
    }
}
?>
