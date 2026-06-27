<?php
require_once __DIR__ . "/../config/Conexion.php";
require_once __DIR__ . "/Empresa.php";
require_once __DIR__ . "/SunatXmlExtra.php";
require_once __DIR__ . "/SunatFirmador.php";
require_once __DIR__ . "/SunatEnviador.php";
require_once __DIR__ . "/Cargacertificado.php";
require_once __DIR__ . "/Rutas.php";

/**
 * Gestiona los resumenes diarios SUNAT:
 *   - RC (Resumen de Comprobantes): obligatorio diario para boletas emitidas
 *   - RA (Comunicacion de Baja): para anular boletas emitidas
 *
 * SUNAT usa sendSummary y devuelve un ticket; el CDR se obtiene
 * despues con consultarTicket (getStatus).
 */
class ResumenSunat
{
    /**
     * Genera el RC del dia indicado: incluye TODAS las boletas (tipo 03)
     * con estado aceptado o pendiente cuya fecha_emision = $fecha.
     *
     * @param string $fecha   YYYY-MM-DD
     * @param int    $idempresa
     * @param int    $idusuario
     * @return array {ok, idresumen, msg}
     */
    public function crearRC($fecha, $idempresa = 1, $idusuario = null)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return ['ok' => false, 'msg' => 'Fecha invalida (YYYY-MM-DD)'];
        }

        // Boletas elegibles del dia (que no esten ya en otro RC enviado)
        $rs = ejecutarConsulta(
            "SELECT c.idcomprobante, c.tipo_documento, c.serie, c.numero, c.cliente_tipo_doc, c.cliente_num_doc,
                    c.subtotal_gravado, c.subtotal_exonerado, c.subtotal_inafecto, c.igv, c.total
             FROM comprobante_electronico c
             WHERE c.idempresa='" . (int)$idempresa . "'
               AND c.tipo_documento='03'
               AND DATE(c.fecha_emision)='" . $fecha . "'
               AND c.estado IN ('aceptado','aceptado_observado','pendiente','generado')
               AND NOT EXISTS (
                   SELECT 1 FROM resumen_detalle rd
                   JOIN resumen_sunat r ON r.idresumen = rd.idresumen
                   WHERE rd.idcomprobante = c.idcomprobante
                     AND r.tipo='RC' AND r.estado IN ('enviado','aceptado','aceptado_observado')
               )
             ORDER BY c.idcomprobante ASC"
        );
        $boletas = [];
        while ($r = $rs->fetch_assoc()) $boletas[] = $r;

        if (count($boletas) === 0) {
            return ['ok' => false, 'msg' => 'No hay boletas pendientes de informar para esta fecha'];
        }

        // Correlativo del dia
        $correlativo = $this->siguienteCorrelativo('RC', $fecha, $idempresa);
        $serieDoc    = 'RC-' . str_replace('-', '', $fecha) . '-' . str_pad($correlativo, 3, '0', STR_PAD_LEFT);

        $idu = $idusuario === null || $idusuario === '' ? 'NULL' : "'" . (int)$idusuario . "'";
        global $conexion;
        $serieDocE = $conexion->real_escape_string($serieDoc);

        $idresumen = ejecutarConsulta_retornarID(
            "INSERT INTO resumen_sunat (idempresa, tipo, correlativo, serie_doc, fecha_referencia, fecha_generacion, estado, idusuario)
             VALUES ('" . (int)$idempresa . "', 'RC', '$correlativo', '$serieDocE', '$fecha', NOW(), 'pendiente', $idu)"
        );

        $linea = 1;
        foreach ($boletas as $b) {
            $idc = (int)$b['idcomprobante'];
            $tipo = $conexion->real_escape_string($b['tipo_documento']);
            $serie = $conexion->real_escape_string($b['serie']);
            $numero = $conexion->real_escape_string($b['numero']);
            $cliTipo = $conexion->real_escape_string($b['cliente_tipo_doc'] ?? '1');
            $cliNum  = $conexion->real_escape_string($b['cliente_num_doc'] ?? '00000000');
            $tg = (float)$b['subtotal_gravado'];
            $te = (float)$b['subtotal_exonerado'];
            $ti = (float)$b['subtotal_inafecto'];
            $ig = (float)$b['igv'];
            $to = (float)$b['total'];
            ejecutarConsulta(
                "INSERT INTO resumen_detalle (idresumen, idcomprobante, linea, tipo_documento, serie, numero,
                    cliente_tipo_doc, cliente_num_doc, total, total_gravado, total_exonerado, total_inafecto, igv, estado_item)
                 VALUES ('$idresumen','$idc','$linea','$tipo','$serie','$numero',
                         '$cliTipo','$cliNum','$to','$tg','$te','$ti','$ig','1')"
            );
            $linea++;
        }

        return ['ok' => true, 'idresumen' => $idresumen, 'serie_doc' => $serieDoc, 'total' => count($boletas)];
    }

    /**
     * Genera el RA (Comunicacion de Baja) para anular boletas/facturas ya enviadas.
     *
     * @param array $idsComprobantes IDs de comprobantes a anular
     * @param string $motivoBaja     ej. "ERROR DE EMISION"
     * @param int    $idempresa
     * @param int    $idusuario
     */
    public function crearRA(array $idsComprobantes, $motivoBaja, $idempresa = 1, $idusuario = null)
    {
        if (count($idsComprobantes) === 0) return ['ok' => false, 'msg' => 'No hay comprobantes seleccionados'];

        $idsList = implode(',', array_map('intval', $idsComprobantes));
        $rs = ejecutarConsulta(
            "SELECT idcomprobante, tipo_documento, serie, numero, fecha_emision, estado
             FROM comprobante_electronico
             WHERE idempresa='" . (int)$idempresa . "' AND idcomprobante IN ($idsList)"
        );
        $comps = [];
        $tiposNoBoleta = ['01','07','08']; // Factura, NC, ND. NO 03 (boleta) - se anula via NC o RC
        while ($r = $rs->fetch_assoc()) {
            if (!in_array($r['estado'], ['aceptado','aceptado_observado'], true)) continue;
            if (!in_array($r['tipo_documento'], $tiposNoBoleta, true)) {
                return ['ok' => false, 'msg' => 'El RA no admite boletas (tipo 03). Para anular boletas usa una Nota de Crédito.'];
            }
            $comps[] = $r;
        }
        if (count($comps) === 0) return ['ok' => false, 'msg' => 'Solo se pueden anular comprobantes aceptados'];

        // Todos los comprobantes deben ser de la misma fecha (regla SUNAT)
        $fecha = date('Y-m-d', strtotime($comps[0]['fecha_emision']));
        foreach ($comps as $c) {
            if (date('Y-m-d', strtotime($c['fecha_emision'])) !== $fecha) {
                return ['ok' => false, 'msg' => 'Los comprobantes deben ser de la misma fecha de emision'];
            }
        }

        $correlativo = $this->siguienteCorrelativo('RA', $fecha, $idempresa);
        $serieDoc    = 'RA-' . str_replace('-', '', $fecha) . '-' . str_pad($correlativo, 3, '0', STR_PAD_LEFT);

        global $conexion;
        $serieDocE = $conexion->real_escape_string($serieDoc);
        $idu = $idusuario === null || $idusuario === '' ? 'NULL' : "'" . (int)$idusuario . "'";

        $idresumen = ejecutarConsulta_retornarID(
            "INSERT INTO resumen_sunat (idempresa, tipo, correlativo, serie_doc, fecha_referencia, fecha_generacion, estado, idusuario)
             VALUES ('" . (int)$idempresa . "', 'RA', '$correlativo', '$serieDocE', '$fecha', NOW(), 'pendiente', $idu)"
        );

        $motivoE = $conexion->real_escape_string($motivoBaja);
        $linea = 1;
        foreach ($comps as $c) {
            $tipo  = $conexion->real_escape_string($c['tipo_documento']);
            $serie = $conexion->real_escape_string($c['serie']);
            $num   = $conexion->real_escape_string($c['numero']);
            $idc   = (int)$c['idcomprobante'];
            ejecutarConsulta(
                "INSERT INTO resumen_detalle (idresumen, idcomprobante, linea, tipo_documento, serie, numero, motivo_baja, estado_item)
                 VALUES ('$idresumen','$idc','$linea','$tipo','$serie','$num','$motivoE','3')"
            );
            $linea++;
        }

        return ['ok' => true, 'idresumen' => $idresumen, 'serie_doc' => $serieDoc, 'total' => count($comps)];
    }

    /**
     * Genera XML, firma, comprime y envia el resumen a SUNAT (sendSummary).
     */
    public function enviar($idresumen)
    {
        $resumen = $this->mostrarCompleto($idresumen);
        if (!$resumen) return ['ok' => false, 'mensaje' => 'Resumen no encontrado'];
        if (in_array($resumen['estado'], ['aceptado','enviado'], true) && !empty($resumen['ticket'])) {
            return ['ok' => false, 'mensaje' => 'El resumen ya fue enviado, ticket: ' . $resumen['ticket']];
        }

        // MODO DEMO: si el certificado activo es de prueba, firmar/enviar a
        // SUNAT BETA con el RUC y credenciales demo.
        if ((new Cargacertificado())->esDemo($resumen['idempresa'])) {
            $resumen = Cargacertificado::aplicarDatosDemo($resumen);
        }

        // 1. Generar XML
        $xmlBuilder = new SunatXmlExtra();
        $xmlString  = ($resumen['tipo'] === 'RC')
            ? $xmlBuilder->buildResumenBoletas($resumen)
            : $xmlBuilder->buildComunicacionBaja($resumen);

        // 2. Rutas
        $rutas = (new Rutas())->mostrar($resumen['idempresa']);
        $rutaData  = $this->resolverRuta($rutas['ruta_resumen'] ?? '../sfs/resumen/');
        $rutaFirma = $this->resolverRuta($rutas['ruta_firma']   ?? '../sfs/firma/');
        $rutaEnvio = $this->resolverRuta($rutas['ruta_envio']   ?? '../sfs/envio/');
        $rutaRpta  = $this->resolverRuta($rutas['ruta_rpta']    ?? '../sfs/rpta/');
        @mkdir($rutaData,  0777, true);
        @mkdir($rutaFirma, 0777, true);
        @mkdir($rutaEnvio, 0777, true);
        @mkdir($rutaRpta,  0777, true);

        $nombreBase = $resumen['empresa_ruc'] . '-' . $resumen['serie_doc'];
        $xmlPath    = $rutaData . $nombreBase . '.xml';
        file_put_contents($xmlPath, $xmlString);

        // 3. Firmar
        $cert = (new Cargacertificado())->activo($resumen['idempresa']);
        if (!$cert) return ['ok' => false, 'mensaje' => 'No hay certificado activo'];
        $firmador = new SunatFirmador();
        $resFirma = $firmador->firmar($xmlPath, $this->resolverRuta($cert['ruta']), $cert['clave']);
        if (!$resFirma['ok']) {
            $this->actualizarEstado($idresumen, 'error', null, $resFirma['mensaje']);
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
        global $conexion;
        $E = function($v) use ($conexion){ return $conexion->real_escape_string((string)$v); };
        $hashE = $E($resFirma['hash'] ?? '');
        ejecutarConsulta(
            "UPDATE resumen_sunat
             SET xml_nombre='" . $nombreBase . ".xml',
                 xml_ruta='" . $E($xmlFirmadoPath) . "',
                 zip_ruta='" . $E($zipPath) . "',
                 xml_hash='$hashE',
                 estado='generado'
             WHERE idresumen='$idresumen'"
        );

        // 5. Enviar (sendSummary)
        $enviador = new SunatEnviador();
        $resEnvio = $enviador->enviarResumen(
            $zipPath, $nombreBase . '.zip',
            $resumen['empresa_ruc'], $resumen['usuario_sol'], $resumen['clave_sol'], $resumen['ambiente']
        );

        if ($resEnvio['ok']) {
            $ticket = $E($resEnvio['ticket']);
            ejecutarConsulta(
                "UPDATE resumen_sunat SET ticket='$ticket', estado='enviado', fecha_envio=NOW()
                 WHERE idresumen='$idresumen'"
            );
            return ['ok' => true, 'ticket' => $resEnvio['ticket'], 'mensaje' => $resEnvio['mensaje']];
        } else {
            $this->actualizarEstado($idresumen, 'error', $resEnvio['codigo'] ?? '', $resEnvio['mensaje']);
            return ['ok' => false, 'codigo' => $resEnvio['codigo'] ?? '', 'mensaje' => $resEnvio['mensaje']];
        }
    }

    /**
     * Consulta el ticket para obtener el CDR y actualizar el estado del resumen.
     */
    public function consultarTicket($idresumen)
    {
        $resumen = $this->mostrarCompleto($idresumen);
        if (!$resumen) return ['ok' => false, 'mensaje' => 'Resumen no encontrado'];
        if (empty($resumen['ticket'])) return ['ok' => false, 'mensaje' => 'El resumen no tiene ticket; envialo primero'];
        if ($resumen['estado'] === 'aceptado') {
            return ['ok' => true, 'estado' => 'aceptado', 'mensaje' => 'Ya fue aceptado: ' . $resumen['cdr_descripcion']];
        }

        // MODO DEMO: consultar a SUNAT BETA con las credenciales demo
        // (si el certificado activo es de prueba).
        if ((new Cargacertificado())->esDemo($resumen['idempresa'])) {
            $resumen = Cargacertificado::aplicarDatosDemo($resumen);
        }

        $enviador = new SunatEnviador();
        $r = $enviador->consultarTicket(
            $resumen['ticket'],
            $resumen['empresa_ruc'], $resumen['usuario_sol'], $resumen['clave_sol'], $resumen['ambiente']
        );

        if (!empty($r['en_proceso'])) {
            // Status 98: aun en proceso
            return ['ok' => false, 'en_proceso' => true, 'mensaje' => 'En proceso, intenta nuevamente en unos segundos'];
        }

        if ($r['ok']) {
            // Aceptado: guardar CDR y marcar aceptado, ademas marcar comprobantes como baja si era RA
            $rutas = (new Rutas())->mostrar($resumen['idempresa']);
            $rutaRpta = $this->resolverRuta($rutas['ruta_rpta'] ?? '../sfs/rpta/');
            $rutaUnzip= $this->resolverRuta($rutas['ruta_unzip']?? '../sfs/unziprpta/');
            @mkdir($rutaRpta, 0777, true);
            @mkdir($rutaUnzip,0777, true);
            $nombreBase = $resumen['empresa_ruc'] . '-' . $resumen['serie_doc'];
            if (!empty($r['cdr_zip'])) {
                $cdrZipPath = $rutaRpta . 'R-' . $nombreBase . '.zip';
                file_put_contents($cdrZipPath, base64_decode($r['cdr_zip']));
                $z = new ZipArchive();
                if ($z->open($cdrZipPath) === TRUE) { $z->extractTo($rutaUnzip); $z->close(); }
                $cdrXmlPath = $rutaUnzip . 'R-' . $nombreBase . '.xml';
                global $conexion;
                $cdrE = $conexion->real_escape_string($cdrXmlPath);
                ejecutarConsulta("UPDATE resumen_sunat SET cdr_ruta='$cdrE' WHERE idresumen='" . (int)$idresumen . "'");
            }
            $estado = $r['ok'] ? 'aceptado' : (!empty($r['observaciones']) ? 'aceptado_observado' : 'aceptado');
            $this->actualizarEstado($idresumen, $estado, $r['codigo'] ?? '0', $r['mensaje'] ?? 'Aceptado');
            ejecutarConsulta("UPDATE resumen_sunat SET fecha_aceptacion=NOW() WHERE idresumen='" . (int)$idresumen . "'");

            // Si es RA, marcar los comprobantes referenciados como 'baja'
            if ($resumen['tipo'] === 'RA') {
                ejecutarConsulta(
                    "UPDATE comprobante_electronico c
                     JOIN resumen_detalle rd ON rd.idcomprobante = c.idcomprobante
                     SET c.estado='baja', c.fecha_baja=NOW(), c.motivo_baja='Anulado por " . $resumen['serie_doc'] . "'
                     WHERE rd.idresumen='" . (int)$idresumen . "'"
                );
            }
            return ['ok' => true, 'estado' => $estado, 'codigo' => $r['codigo'] ?? '0', 'mensaje' => $r['mensaje']];
        } else {
            // Solo marcar 'rechazado' si SUNAT respondio con un codigo de error definitivo
            // (rangos conocidos de SUNAT para resumenes: 1xxx, 2xxx, 3xxx, 4xxx).
            // Codigos como 200 (HTTP code mal interpretado) o errores de red NO son rechazo.
            $codigo = (string)($r['codigo'] ?? '');
            $esRechazoDefinitivo = preg_match('/^[1-4]\d{3}$/', $codigo);
            if ($esRechazoDefinitivo) {
                $this->actualizarEstado($idresumen, 'rechazado', $codigo, $r['mensaje'] ?? 'Rechazado');
                return ['ok' => false, 'codigo' => $codigo, 'mensaje' => $r['mensaje']];
            }
            // Error transitorio (timeout, todavia procesando, etc.) — dejar en estado enviado
            return ['ok' => false, 'transitorio' => true, 'codigo' => $codigo, 'mensaje' => 'No fue posible obtener el resultado: ' . $r['mensaje'] . '. Intenta nuevamente en unos segundos.'];
        }
    }

    public function mostrar($idresumen)
    {
        $sql = "SELECT r.*,
                       e.numero_ruc           AS empresa_ruc,
                       e.razon_social         AS empresa_razon,
                       e.nombre_comercial     AS empresa_nombre_comercial,
                       e.usuario_sol, e.clave_sol, e.ambiente
                FROM resumen_sunat r
                LEFT JOIN empresa e ON e.idempresa = r.idempresa
                WHERE r.idresumen='" . (int)$idresumen . "'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function detalle($idresumen)
    {
        return ejecutarConsulta(
            "SELECT * FROM resumen_detalle WHERE idresumen='" . (int)$idresumen . "' ORDER BY linea ASC"
        );
    }

    public function mostrarCompleto($idresumen)
    {
        $cab = $this->mostrar($idresumen);
        if (!$cab) return null;
        $rs = $this->detalle($idresumen);
        $items = [];
        while ($r = $rs->fetch_assoc()) $items[] = $r;
        $cab['items'] = $items;
        return $cab;
    }

    public function listar($filtros = [])
    {
        $where = " 1=1 ";
        if (!empty($filtros['tipo']))             $where .= " AND r.tipo='" . $filtros['tipo'] . "' ";
        if (!empty($filtros['estado']))           $where .= " AND r.estado='" . $filtros['estado'] . "' ";
        if (!empty($filtros['fecha_referencia'])) $where .= " AND r.fecha_referencia='" . $filtros['fecha_referencia'] . "' ";
        $sql = "SELECT r.*, (SELECT COUNT(*) FROM resumen_detalle WHERE idresumen=r.idresumen) AS total_items
                FROM resumen_sunat r
                WHERE $where
                ORDER BY r.idresumen DESC";
        return ejecutarConsulta($sql);
    }

    private function siguienteCorrelativo($tipo, $fecha, $idempresa)
    {
        $row = ejecutarConsultaSimpleFila(
            "SELECT COALESCE(MAX(correlativo),0) AS m FROM resumen_sunat
             WHERE idempresa='" . (int)$idempresa . "' AND tipo='" . $tipo . "' AND fecha_referencia='" . $fecha . "'"
        );
        return ((int)($row['m'] ?? 0)) + 1;
    }

    private function actualizarEstado($idresumen, $estado, $codigo, $desc)
    {
        global $conexion;
        $codigoE = $codigo === null ? 'NULL' : "'" . $conexion->real_escape_string($codigo) . "'";
        $descE   = $conexion->real_escape_string(substr((string)$desc, 0, 500));
        ejecutarConsulta(
            "UPDATE resumen_sunat
             SET estado='" . $estado . "', cdr_codigo=$codigoE, cdr_descripcion='$descE'
             WHERE idresumen='" . (int)$idresumen . "'"
        );
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
