<?php
/**
 * Firmador XML-DSig usando la libreria xmlseclibs.
 * Esta libreria es la implementacion estandar de XML Digital Signatures
 * y maneja correctamente la canonicalizacion y los namespaces, evitando
 * los problemas de la implementacion manual.
 */
require_once __DIR__ . '/api_signature/XMLSecurityKey.php';
require_once __DIR__ . '/api_signature/XMLSecurityDSig.php';
require_once __DIR__ . '/api_signature/XMLSecEnc.php';

class SunatFirmador
{
    /**
     * Convierte un .p12 con algoritmos legacy (RC2-40, MD5...) a formato
     * compatible con OpenSSL 3.x usando el binario openssl con -legacy.
     */
    private function convertirP12Legacy($pfxPath, $pass)
    {
        $opensslExe = 'C:\\xampp\\apache\\bin\\openssl.exe';
        if (!is_file($opensslExe)) return false;

        $tmpPem = tempnam(sys_get_temp_dir(), 'cert_') . '.pem';
        $cmd1 = sprintf(
            '"%s" pkcs12 -legacy -in "%s" -out "%s" -nodes -passin "pass:%s" 2>&1',
            $opensslExe, $pfxPath, $tmpPem, str_replace('"', '\\"', $pass)
        );
        exec($cmd1, $out1, $rc1);
        if ($rc1 !== 0 || !is_file($tmpPem) || filesize($tmpPem) === 0) {
            @unlink($tmpPem);
            return false;
        }

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

    /**
     * Firma el XML in-place: lee el archivo, inserta la firma dentro de
     * <ext:ExtensionContent> y guarda el resultado.
     */
    public function firmar($xmlPath, $pfxPath, $clavePfx)
    {
        if (!file_exists($xmlPath)) return ['ok' => false, 'mensaje' => 'XML no encontrado: ' . $xmlPath];
        if (!file_exists($pfxPath)) return ['ok' => false, 'mensaje' => 'Certificado .pfx no encontrado: ' . $pfxPath];

        try {
            // 1. Cargar el XML preservando espacios
            $doc = new DOMDocument();
            $doc->formatOutput      = false;
            $doc->preserveWhiteSpace = true;
            if (!$doc->load($xmlPath)) {
                return ['ok' => false, 'mensaje' => 'No se pudo cargar el XML'];
            }

            // 2. Cargar la clave privada y certificado desde el .pfx
            // Si falla por algoritmo legacy (RC2-40), convertirlo automaticamente.
            $pfxContent = file_get_contents($pfxPath);
            $keyData = [];
            if (!@openssl_pkcs12_read($pfxContent, $keyData, $clavePfx)) {
                $tmpP12 = $this->convertirP12Legacy($pfxPath, $clavePfx);
                if ($tmpP12) {
                    $pfxContent = file_get_contents($tmpP12);
                    @unlink($tmpP12);
                }
                if (!@openssl_pkcs12_read($pfxContent, $keyData, $clavePfx)) {
                    return ['ok' => false, 'mensaje' => 'Clave del .pfx incorrecta o archivo invalido'];
                }
            }

            // 3. Crear el objeto de firma (no usar prefijo por defecto)
            $objDSig = new XMLSecurityDSig(false);
            $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);

            $options = [
                'force_uri' => true,
                'id_name'   => 'ID',
                'overwrite' => false,
            ];
            $objDSig->addReference(
                $doc,
                XMLSecurityDSig::SHA1,
                ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
                $options
            );

            // 4. Crear la clave RSA-SHA1
            $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
            $objKey->loadKey($keyData['pkey']);

            // 5. Adjuntar el certificado X.509
            $objDSig->add509Cert($keyData['cert'], true, false);

            // 6. Localizar <ext:ExtensionContent> donde se insertara la firma
            $extContent = $doc->documentElement
                ->getElementsByTagName('ExtensionContent')
                ->item(0);
            if (!$extContent) {
                return ['ok' => false, 'mensaje' => 'No se encontro <ext:ExtensionContent> en el XML'];
            }

            // 7. Firmar el documento (la firma queda dentro de ExtensionContent)
            $objDSig->sign($objKey, $extContent);

            // 8. Setear atributo Id="SignatureSP" en el bloque Signature
            $sigNode = $doc->getElementsByTagName('Signature')->item(0);
            if ($sigNode) {
                $sigNode->setAttribute('Id', 'SignatureSP');
            }

            // 9. Extraer hash y firma para auditoria
            $digestNodes = $doc->getElementsByTagName('DigestValue');
            $sigValueNodes = $doc->getElementsByTagName('SignatureValue');
            $hashCpe  = $digestNodes->length   > 0 ? $digestNodes->item(0)->nodeValue   : '';
            $firmaCpe = $sigValueNodes->length > 0 ? $sigValueNodes->item(0)->nodeValue : '';

            // 10. Guardar el XML firmado
            $doc->save($xmlPath);

            return [
                'ok'      => true,
                'mensaje' => 'Firmado correctamente',
                'hash'    => $hashCpe,
                'digest'  => $hashCpe,
                'firma'   => $firmaCpe,
            ];
        } catch (\Throwable $e) {
            error_log('[SunatFirmador] ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'Error al firmar: ' . $e->getMessage()];
        }
    }
}
