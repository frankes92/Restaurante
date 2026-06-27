<?php
require_once __DIR__ . "/../config/Conexion.php";
require_once __DIR__ . "/Empresa.php";
require_once __DIR__ . "/Numeracion.php";
require_once __DIR__ . "/ComprobanteElectronico.php";
require_once __DIR__ . "/SunatXmlExtra.php";
require_once __DIR__ . "/SunatFirmador.php";
require_once __DIR__ . "/SunatEnviador.php";
require_once __DIR__ . "/Cargacertificado.php";
require_once __DIR__ . "/Rutas.php";

/**
 * Modelo unificado para Notas de Credito (07) y Notas de Debito (08).
 * Reutiliza la tabla comprobante_electronico marcando ref_idcomprobante.
 */
class NotaCreditoDebito
{
    /**
     * Crea una NC o ND a partir de un comprobante existente (boleta o factura).
     *
     * @param int    $idCompOrigen  comprobante origen (boleta/factura)
     * @param string $tipoNota      '07' = nota credito, '08' = nota debito
     * @param string $motivoCodigo  catalogo 9 (NC) o 10 (ND)
     * @param string $motivoDesc    descripcion del motivo
     * @param int    $idusuario     usuario que la emite
     * @return array {ok: bool, idcomprobante: int, msg: string}
     */
    public function crearDesdeComprobante($idCompOrigen, $tipoNota, $motivoCodigo, $motivoDesc, $idusuario = null)
    {
        if (!in_array($tipoNota, ['07','08'], true)) {
            return ['ok' => false, 'msg' => 'Tipo de nota invalido'];
        }

        $compModel = new ComprobanteElectronico();
        $origen = $compModel->mostrarCompleto($idCompOrigen);
        if (!$origen) return ['ok' => false, 'msg' => 'Comprobante origen no encontrado'];

        // Solo se puede emitir NC/ND sobre un comprobante aceptado
        if ($origen['estado'] !== 'aceptado' && $origen['estado'] !== 'aceptado_observado') {
            return ['ok' => false, 'msg' => 'El comprobante origen debe estar aceptado por SUNAT'];
        }

        // Si ya tiene una NC asociada, bloquear
        $existente = ejecutarConsultaSimpleFila(
            "SELECT idcomprobante, numero_completo, estado, tipo_documento FROM comprobante_electronico
             WHERE ref_idcomprobante='" . (int)$idCompOrigen . "' AND tipo_documento='$tipoNota'
             ORDER BY idcomprobante DESC LIMIT 1"
        );
        if ($existente && in_array($existente['estado'], ['pendiente','generado','aceptado','aceptado_observado'], true)) {
            return ['ok' => false, 'msg' => 'Ya existe una nota ' .
                ($tipoNota === '07' ? 'de crédito' : 'de débito') .
                ' (' . $existente['numero_completo'] . ') para este comprobante'];
        }

        // Determinar serie segun tipo del comprobante origen
        // Convencion SUNAT:
        //   - NC de Factura → FC01
        //   - NC de Boleta  → BC01
        //   - ND de Factura → FD01
        //   - ND de Boleta  → BD01
        $tipoOrigen = $origen['tipo_documento']; // 01 o 03
        if ($tipoNota === '07') {
            $serie = ($tipoOrigen === '01') ? 'FC01' : 'BC01';
        } else {
            $serie = ($tipoOrigen === '01') ? 'FD01' : 'BD01';
        }

        $num = new Numeracion();
        $numero = $num->siguiente($origen['idempresa'], $tipoNota, $serie);
        if (!$numero) return ['ok' => false, 'msg' => 'No se pudo asignar numero correlativo'];

        global $conexion;
        $E = function($v) use ($conexion){ return $conexion->real_escape_string($v); };

        $idemp = (int)$origen['idempresa'];
        $idu   = $idusuario === null || $idusuario === '' ? 'NULL' : "'" . (int)$idusuario . "'";

        // Para NC/ND el monto suele ser igual al original (anulacion total);
        // para anulacion parcial habria que reducir items, pero aqui copiamos todo.
        $subtotal = (float)$origen['subtotal'];
        $igv      = (float)$origen['igv'];
        $total    = (float)$origen['total'];
        $tasaIgv  = (float)$origen['tasa_igv'];
        $totalLetras = $E((new ComprobanteElectronico())->_numeroALetrasPublic($total));

        $sqlInsert = "INSERT INTO comprobante_electronico (
            idempresa, idorden, ref_idcomprobante, ref_tipo_documento, ref_serie, ref_numero,
            motivo_codigo, motivo_descripcion,
            idusuario,
            tipo_documento, serie, numero,
            fecha_emision, tipo_moneda, tipo_operacion,
            cliente_tipo_doc, cliente_num_doc, cliente_razon, cliente_direccion, cliente_email,
            subtotal, subtotal_gravado, subtotal_exonerado, subtotal_inafecto, subtotal_gratuito,
            igv, total, tasa_igv, total_letras, metodo_pago, estado
        ) VALUES (
            '$idemp', NULL, '" . (int)$idCompOrigen . "', '" . $E($tipoOrigen) . "',
            '" . $E($origen['serie']) . "', '" . $E($origen['numero']) . "',
            '" . $E($motivoCodigo) . "', '" . $E($motivoDesc) . "',
            $idu,
            '$tipoNota', '$serie', '$numero',
            NOW(), 'PEN', '0101',
            '" . $E($origen['cliente_tipo_doc']) . "', '" . $E($origen['cliente_num_doc']) . "',
            '" . $E($origen['cliente_razon'])    . "', '" . $E($origen['cliente_direccion']) . "',
            '" . $E($origen['cliente_email'])    . "',
            '$subtotal', '" . (float)$origen['subtotal_gravado'] . "', '" . (float)$origen['subtotal_exonerado'] . "',
            '" . (float)$origen['subtotal_inafecto']  . "', '" . (float)$origen['subtotal_gratuito']  . "',
            '$igv', '$total', '$tasaIgv', '$totalLetras', '" . $E($origen['metodo_pago']) . "', 'pendiente'
        )";

        $idnota = ejecutarConsulta_retornarID($sqlInsert);
        if (!$idnota) return ['ok' => false, 'msg' => 'No se pudo crear la nota'];

        // Copiar el detalle del comprobante origen
        ejecutarConsulta(
            "INSERT INTO comprobante_detalle (idcomprobante, linea, codigo, descripcion, unidad_medida,
                cantidad, precio_unitario, precio_con_igv, valor_venta, igv_item, total_item,
                tipo_afectacion, codigo_afectacion)
             SELECT '$idnota', linea, codigo, descripcion, unidad_medida,
                cantidad, precio_unitario, precio_con_igv, valor_venta, igv_item, total_item,
                tipo_afectacion, codigo_afectacion
             FROM comprobante_detalle
             WHERE idcomprobante='" . (int)$idCompOrigen . "'
             ORDER BY linea ASC"
        );

        return ['ok' => true, 'idcomprobante' => $idnota, 'serie' => $serie, 'numero' => $numero];
    }

    /**
     * Envia la NC o ND a SUNAT (firma + zip + sendBill).
     */
    public function enviarSunat($idnota)
    {
        $compModel = new ComprobanteElectronico();
        // Recalcular antes de enviar (por si el origen tenia datos viejos)
        $compModel->recalcularTotales($idnota);

        $nota = $compModel->mostrarCompleto($idnota);
        if (!$nota) return ['ok' => false, 'mensaje' => 'Nota no encontrada'];
        if (!in_array($nota['tipo_documento'], ['07','08'], true)) {
            return ['ok' => false, 'mensaje' => 'No es nota de credito/debito'];
        }
        if (in_array($nota['estado'], ['aceptado','baja'], true)) {
            return ['ok' => false, 'mensaje' => 'La nota ya fue procesada: ' . $nota['estado']];
        }

        // MODO DEMO: si el certificado activo es de prueba, firmar/enviar a
        // SUNAT BETA con el RUC y credenciales demo.
        if ((new Cargacertificado())->esDemo($nota['idempresa'])) {
            $nota = Cargacertificado::aplicarDatosDemo($nota);
        }

        // 1. Generar XML segun tipo
        $xmlBuilder = new SunatXmlExtra();
        $xmlString  = ($nota['tipo_documento'] === '07')
            ? $xmlBuilder->buildNotaCredito($nota)
            : $xmlBuilder->buildNotaDebito($nota);

        // 2. Resolver rutas
        $rutas = (new Rutas())->mostrar($nota['idempresa']);
        $rutaData  = $this->resolverRuta($rutas['ruta_data']  ?? '../sfs/data/');
        $rutaFirma = $this->resolverRuta($rutas['ruta_firma'] ?? '../sfs/firma/');
        $rutaEnvio = $this->resolverRuta($rutas['ruta_envio'] ?? '../sfs/envio/');
        $rutaRpta  = $this->resolverRuta($rutas['ruta_rpta']  ?? '../sfs/rpta/');
        $rutaUnzip = $this->resolverRuta($rutas['ruta_unzip'] ?? '../sfs/unziprpta/');
        @mkdir($rutaData,  0777, true);
        @mkdir($rutaFirma, 0777, true);
        @mkdir($rutaEnvio, 0777, true);
        @mkdir($rutaRpta,  0777, true);
        @mkdir($rutaUnzip, 0777, true);

        $numeroSinPad = ltrim($nota['numero'], '0') ?: '0';
        $nombreBase   = $nota['empresa_ruc'] . '-' . $nota['tipo_documento'] . '-' . $nota['serie'] . '-' . $numeroSinPad;
        $xmlPath      = $rutaData . $nombreBase . '.xml';
        file_put_contents($xmlPath, $xmlString);

        // 3. Firmar
        $cert = (new Cargacertificado())->activo($nota['idempresa']);
        if (!$cert) return ['ok' => false, 'mensaje' => 'No hay certificado activo'];
        $firmador = new SunatFirmador();
        $resFirma = $firmador->firmar($xmlPath, $this->resolverRuta($cert['ruta']), $cert['clave']);
        if (!$resFirma['ok']) {
            $compModel->logCdr($idnota, 'FIRMA_ERROR', '', $resFirma['mensaje']);
            return ['ok' => false, 'mensaje' => 'Error al firmar: ' . $resFirma['mensaje']];
        }
        $xmlFirmadoPath = $rutaFirma . $nombreBase . '.xml';
        copy($xmlPath, $xmlFirmadoPath);

        // 4. Comprimir
        $zipPath = $rutaEnvio . $nombreBase . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFile($xmlFirmadoPath, $nombreBase . '.xml');
            $zip->close();
        } else {
            return ['ok' => false, 'mensaje' => 'No se pudo crear ZIP'];
        }
        $compModel->actualizarRutas($idnota, $nombreBase . '.xml', $xmlFirmadoPath, $zipPath, null, $resFirma['hash'] ?? '');

        // 5. Enviar a SUNAT (notas usan sendBill como facturas/boletas)
        $enviador = new SunatEnviador();
        $resEnvio = $enviador->enviar(
            $zipPath, $nombreBase . '.zip',
            $nota['empresa_ruc'], $nota['usuario_sol'], $nota['clave_sol'], $nota['ambiente']
        );
        $compModel->logCdr($idnota, 'ENVIO_SUNAT', $resEnvio['codigo'] ?? '', $resEnvio['mensaje'] ?? '', '', json_encode($resEnvio));

        if ($resEnvio['ok']) {
            if (!empty($resEnvio['cdr_zip'])) {
                $cdrZipPath = $rutaRpta . 'R-' . $nombreBase . '.zip';
                file_put_contents($cdrZipPath, base64_decode($resEnvio['cdr_zip']));
                $cdrZip = new ZipArchive();
                if ($cdrZip->open($cdrZipPath) === TRUE) {
                    $cdrZip->extractTo($rutaUnzip);
                    $cdrZip->close();
                }
                $cdrXmlPath = $rutaUnzip . 'R-' . $nombreBase . '.xml';
                global $conexion;
                $cdrE = $conexion->real_escape_string($cdrXmlPath);
                ejecutarConsulta("UPDATE comprobante_electronico SET cdr_ruta='$cdrE' WHERE idcomprobante='" . (int)$idnota . "'");
            }
            $estado = ($resEnvio['codigo'] === '0') ? 'aceptado' : (!empty($resEnvio['observaciones']) ? 'aceptado_observado' : 'aceptado');
            $compModel->actualizarEstado($idnota, $estado, $resEnvio['codigo'] ?? '0', $resEnvio['mensaje']);
            return ['ok' => true, 'estado' => $estado, 'codigo' => $resEnvio['codigo'], 'mensaje' => $resEnvio['mensaje']];
        } else {
            $codErr = $resEnvio['codigo'] ?? '';
            $estado = (strpos($codErr, '0') === 0 || $codErr === '') ? 'error' : 'rechazado';
            $compModel->actualizarEstado($idnota, $estado, $codErr, $resEnvio['mensaje']);
            return ['ok' => false, 'estado' => $estado, 'codigo' => $codErr, 'mensaje' => $resEnvio['mensaje']];
        }
    }

    private function resolverRuta($ruta)
    {
        if (strpos($ruta, '..') === 0) {
            $base = realpath(__DIR__ . '/..');
            return rtrim($base, '/\\') . '/' . ltrim(substr($ruta, 3), '/\\');
        }
        return $ruta;
    }
}
