<?php
require_once __DIR__ . "/../config/Conexion.php";
require_once __DIR__ . "/Empresa.php";
require_once __DIR__ . "/Numeracion.php";
require_once __DIR__ . "/Rutas.php";
require_once __DIR__ . "/Cargacertificado.php";
require_once __DIR__ . "/ClienteFacturacion.php";
require_once __DIR__ . "/SunatXml.php";
require_once __DIR__ . "/SunatFirmador.php";
require_once __DIR__ . "/SunatEnviador.php";

class ComprobanteElectronico
{
    /**
     * Crea un comprobante electronico (cabecera + detalle) en estado 'pendiente'.
     * Llamado desde el flujo de cobro.
     *
     * @param array $datos {
     *   idempresa, idorden, idclifact, idusuario,
     *   tipo_documento ('01' factura | '03' boleta),
     *   cliente_tipo_doc, cliente_num_doc, cliente_razon, cliente_direccion, cliente_email,
     *   subtotal, igv, total, tasa_igv, metodo_pago,
     *   items: [{ codigo, descripcion, unidad_medida, cantidad, precio_unitario, precio_con_igv, valor_venta, igv_item, total_item }]
     * }
     */
    public function crear($datos)
    {
        global $conexion;
        $emp = new Empresa();
        $num = new Numeracion();

        $idempresa     = (int)($datos['idempresa'] ?? 1);
        $tipoDocumento = $datos['tipo_documento'];                    // '01' o '03'
        $serie         = $tipoDocumento === '01' ? 'F001' : 'B001';
        $numero        = $num->siguiente($idempresa, $tipoDocumento, $serie);
        if (!$numero) return false;

        $idorden    = isset($datos['idorden'])   && $datos['idorden']   !== '' ? (int)$datos['idorden']   : null;
        $idclifact  = isset($datos['idclifact']) && $datos['idclifact'] !== '' ? (int)$datos['idclifact'] : null;
        $idusuario  = isset($datos['idusuario']) && $datos['idusuario'] !== '' ? (int)$datos['idusuario'] : null;

        $clienteTipoDoc = $datos['cliente_tipo_doc'] ?? '1';
        $clienteNumDoc  = $datos['cliente_num_doc']  ?? '00000000';
        $clienteRazon   = $datos['cliente_razon']    ?? 'CLIENTE VARIOS';
        $clienteDir     = $datos['cliente_direccion'] ?? '';
        $clienteEmail   = $datos['cliente_email']    ?? '';

        $subtotal   = (float)($datos['subtotal'] ?? 0);
        $igv        = (float)($datos['igv'] ?? 0);
        $total      = (float)($datos['total'] ?? 0);
        $tasaIgv    = (float)($datos['tasa_igv'] ?? 0.18);
        $metodoPago = $datos['metodo_pago'] ?? '';
        $totalLetras = $this->numeroALetras($total);

        $idoStr = $idorden   === null ? 'NULL' : "'$idorden'";
        $idcStr = $idclifact === null ? 'NULL' : "'$idclifact'";
        $iduStr = $idusuario === null ? 'NULL' : "'$idusuario'";

        // Escape strings
        $E = function($v) use ($conexion){ return $conexion->real_escape_string($v); };

        $sql = "INSERT INTO comprobante_electronico (
                    idempresa, idorden, idclifact, idusuario,
                    tipo_documento, serie, numero,
                    fecha_emision, tipo_moneda, tipo_operacion,
                    cliente_tipo_doc, cliente_num_doc, cliente_razon, cliente_direccion, cliente_email,
                    subtotal, igv, total, tasa_igv, total_letras, metodo_pago, estado
                ) VALUES (
                    '$idempresa', $idoStr, $idcStr, $iduStr,
                    '$tipoDocumento', '$serie', '$numero',
                    NOW(), 'PEN', '0101',
                    '" . $E($clienteTipoDoc) . "', '" . $E($clienteNumDoc) . "',
                    '" . $E($clienteRazon)   . "', '" . $E($clienteDir)    . "', '" . $E($clienteEmail) . "',
                    '$subtotal','$igv','$total','$tasaIgv','" . $E($totalLetras) . "','" . $E($metodoPago) . "',
                    'pendiente'
                )";
        $idcomprobante = ejecutarConsulta_retornarID($sql);
        if (!$idcomprobante) return false;

        $items = $datos['items'] ?? [];
        $linea = 1;
        foreach ($items as $i) {
            $cod   = $E($i['codigo'] ?? '');
            $des   = $E($i['descripcion']);
            $um    = $E($i['unidad_medida'] ?? 'NIU');
            $cnt   = (float)$i['cantidad'];
            $pu    = (float)$i['precio_unitario'];
            $pci   = (float)$i['precio_con_igv'];
            $vv    = (float)$i['valor_venta'];
            $iv    = (float)$i['igv_item'];
            $ti    = (float)$i['total_item'];
            $afect = $E($i['codigo_afectacion'] ?? '10');
            // Tipo de afectacion (catalogo descriptivo, no obligatorio en BD)
            $tipoAfect = ($afect === '20' || $afect === '30') ? '20' : '10';

            $sqlD = "INSERT INTO comprobante_detalle
                     (idcomprobante, linea, codigo, descripcion, unidad_medida,
                      cantidad, precio_unitario, precio_con_igv, valor_venta, igv_item, total_item,
                      tipo_afectacion, codigo_afectacion)
                     VALUES ('$idcomprobante','$linea','$cod','$des','$um',
                             '$cnt','$pu','$pci','$vv','$iv','$ti',
                             '$tipoAfect','$afect')";
            ejecutarConsulta($sqlD);
            $linea++;
        }
        return $idcomprobante;
    }

    /**
     * Crea comprobante a partir de una orden ya cobrada del POS.
     */
    public function crearDesdeOrden($idorden, $tipoDocumento, $cliente)
    {
        require_once __DIR__ . "/Orden.php";
        $orden = (new Orden())->mostrarCompleta($idorden);
        if (!$orden) return false;

        $emp = (new Empresa())->mostrar(1);
        $tasaIgv = (float)$emp['tasa_igv'];

        $items = [];
        foreach ($orden['items'] as $i) {
            // Las CORTESIAS no se facturan ante SUNAT (no se cobraron). Se excluyen
            // del comprobante fiscal; igual aparecen en el documento impreso.
            if ((int)($i['cortesia'] ?? 0) === 1) continue;
            $cantidad     = (float)$i['cantidad'];
            $precioConIgv = (float)$i['precio'];
            $idprod       = (int)($i['idproducto'] ?? 0);

            // Heredar codigo_afectacion del producto (default 10 = Gravado)
            $codAfect = '10';
            if ($idprod > 0) {
                $rowProd = ejecutarConsultaSimpleFila(
                    "SELECT codigo_afectacion FROM producto WHERE idproducto='$idprod'"
                );
                if ($rowProd && !empty($rowProd['codigo_afectacion'])) {
                    $codAfect = $rowProd['codigo_afectacion'];
                }
            }

            // Calcular precio_unitario y valor_venta segun afectacion:
            //   - Gravado:  precio_unitario = pci / (1 + tasa); valor_venta = cantidad * precio_unitario; igv = total - valor_venta
            //   - Exonerado/Inafecto: NO hay IGV → precio_unitario = pci; valor_venta = cantidad * pci; igv = 0
            if ($codAfect === '20' || $codAfect === '30') {
                $precioUnit = $precioConIgv;
                $valorVenta = round($cantidad * $precioUnit, 2);
                $totalItem  = round($cantidad * $precioConIgv, 2);
                $igvItem    = 0;
            } else {
                $precioUnit = round($precioConIgv / (1 + $tasaIgv), 4);
                $valorVenta = round($cantidad * $precioUnit, 2);
                $totalItem  = round($cantidad * $precioConIgv, 2);
                $igvItem    = round($totalItem - $valorVenta, 2);
            }

            $items[] = [
                'codigo'            => $i['idproducto'] ?? '',
                'descripcion'       => $i['nombre'],
                'unidad_medida'     => 'NIU',
                'cantidad'          => $cantidad,
                'precio_unitario'   => $precioUnit,
                'precio_con_igv'    => $precioConIgv,
                'valor_venta'       => $valorVenta,
                'igv_item'          => $igvItem,
                'total_item'        => $totalItem,
                'codigo_afectacion' => $codAfect,
            ];
        }

        return $this->crear([
            'idempresa'        => 1,
            'idorden'          => $idorden,
            'idclifact'        => $cliente['idclifact'] ?? null,
            'idusuario'        => $cliente['idusuario'] ?? null,
            'tipo_documento'   => $tipoDocumento,
            'cliente_tipo_doc' => $cliente['tipo_doc'],
            'cliente_num_doc'  => $cliente['num_doc'],
            'cliente_razon'    => $cliente['razon'],
            'cliente_direccion'=> $cliente['direccion'] ?? '',
            'cliente_email'    => $cliente['email'] ?? '',
            'subtotal'         => $orden['subtotal'],
            'igv'              => $orden['igv'],
            'total'            => $orden['total'],
            'tasa_igv'         => $tasaIgv,
            'metodo_pago'      => $orden['metodo_pago'],
            'items'            => $items,
        ]);
    }

    public function mostrar($idcomprobante)
    {
        $sql = "SELECT c.*,
                       e.numero_ruc           AS empresa_ruc,
                       e.razon_social         AS empresa_razon,
                       e.nombre_comercial     AS empresa_nombre_comercial,
                       e.domicilio_fiscal     AS empresa_direccion,
                       e.ubigeo               AS empresa_ubigeo,
                       e.departamento         AS empresa_departamento,
                       e.provincia            AS empresa_provincia,
                       e.distrito             AS empresa_distrito,
                       e.codigo_pais          AS empresa_pais,
                       e.usuario_sol, e.clave_sol, e.ambiente
                FROM comprobante_electronico c
                LEFT JOIN empresa e ON e.idempresa = c.idempresa
                WHERE c.idcomprobante='$idcomprobante'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function detalle($idcomprobante)
    {
        $sql = "SELECT * FROM comprobante_detalle WHERE idcomprobante='$idcomprobante' ORDER BY linea ASC";
        return ejecutarConsulta($sql);
    }

    public function mostrarCompleto($idcomprobante)
    {
        $cab = $this->mostrar($idcomprobante);
        if (!$cab) return null;
        $rs = $this->detalle($idcomprobante);
        $items = [];
        while ($r = $rs->fetch_assoc()) $items[] = $r;
        $cab['items'] = $items;
        return $cab;
    }

    public function actualizarEstado($idcomprobante, $estado, $cdrCodigo = null, $cdrDesc = null)
    {
        $cdrCodigo = $cdrCodigo === null ? 'NULL' : "'" . addslashes($cdrCodigo) . "'";
        $cdrDesc   = $cdrDesc   === null ? 'NULL' : "'" . addslashes($cdrDesc) . "'";
        $sql = "UPDATE comprobante_electronico
                SET estado='$estado',
                    cdr_codigo=$cdrCodigo,
                    cdr_descripcion=$cdrDesc,
                    fecha_envio=CASE WHEN '$estado' IN ('aceptado','aceptado_observado','rechazado') THEN NOW() ELSE fecha_envio END,
                    intentos_envio = intentos_envio + 1
                WHERE idcomprobante='$idcomprobante'";
        return ejecutarConsulta($sql);
    }

    public function actualizarRutas($idcomprobante, $xmlNombre, $xmlRuta, $zipRuta = null, $cdrRuta = null, $hash = null)
    {
        global $conexion;
        $E = function($v) use ($conexion){ return $conexion->real_escape_string((string)$v); };
        $sets = [
            "xml_nombre='" . $E($xmlNombre) . "'",
            "xml_ruta='"   . $E($xmlRuta)   . "'",
            "fecha_generacion=NOW()",
            "estado='generado'"
        ];
        if ($zipRuta) $sets[] = "zip_ruta='" . $E($zipRuta) . "'";
        if ($cdrRuta) $sets[] = "cdr_ruta='" . $E($cdrRuta) . "'";
        if ($hash)    $sets[] = "xml_hash='" . $E($hash) . "'";
        $sql = "UPDATE comprobante_electronico SET " . implode(',', $sets) . " WHERE idcomprobante='" . (int)$idcomprobante . "'";
        return ejecutarConsulta($sql);
    }

    public function logCdr($idcomprobante, $accion, $codigo, $mensaje, $request = '', $response = '')
    {
        global $conexion;
        $E = function($v) use ($conexion){ return $conexion->real_escape_string((string)$v); };
        $sql = "INSERT INTO cdr_log (idcomprobante, accion, codigo, mensaje, request, response)
                VALUES ('$idcomprobante','" . $E($accion) . "','" . $E($codigo) . "','" . $E($mensaje) . "',
                        '" . $E($request) . "','" . $E($response) . "')";
        return ejecutarConsulta($sql);
    }

    // ------------------- Listado / DataTables -------------------

    public function listarServerSide($start, $length, $search, $orderCol, $orderDir, $filtros = [])
    {
        $cols = ['c.idcomprobante','c.fecha_emision','c.tipo_documento','c.numero_completo','c.cliente_num_doc','c.cliente_razon','c.total','c.estado'];
        $orderCol = isset($cols[(int)$orderCol]) ? $cols[(int)$orderCol] : 'c.idcomprobante';
        $orderDir = strtolower($orderDir) === 'asc' ? 'ASC' : 'DESC';
        $start    = max(0, (int)$start);
        $length   = max(1, min(500, (int)$length));

        $where = $this->buildWhere($search, $filtros);
        $sql = "SELECT c.* FROM comprobante_electronico c
                WHERE $where
                ORDER BY $orderCol $orderDir
                LIMIT $start, $length";
        return ejecutarConsulta($sql);
    }

    public function contar($search = '', $filtros = [], $useFilter = true)
    {
        $where = $useFilter ? $this->buildWhere($search, $filtros) : $this->buildWhere('', $filtros);
        $sql = "SELECT COUNT(*) AS total FROM comprobante_electronico c WHERE $where";
        $row = ejecutarConsultaSimpleFila($sql);
        return (int)$row['total'];
    }

    private function buildWhere($search, $filtros)
    {
        $where = " 1=1 ";
        if (!empty($filtros['estado']))         $where .= " AND c.estado='" . $filtros['estado'] . "' ";
        if (!empty($filtros['tipo_documento'])) $where .= " AND c.tipo_documento='" . $filtros['tipo_documento'] . "' ";
        if (!empty($filtros['desde']))          $where .= " AND DATE(c.fecha_emision) >= '" . $filtros['desde'] . "' ";
        if (!empty($filtros['hasta']))          $where .= " AND DATE(c.fecha_emision) <= '" . $filtros['hasta'] . "' ";
        if ($search !== '') {
            $s = $search;
            $where .= " AND (c.numero_completo LIKE '%$s%' OR c.cliente_num_doc LIKE '%$s%' OR c.cliente_razon LIKE '%$s%') ";
        }
        return $where;
    }

    // ------------------- Acciones SUNAT -------------------

    /**
     * Genera el XML, lo firma, lo comprime y lo envia a SUNAT.
     * Retorna ['ok'=>bool, 'estado'=>..., 'codigo'=>..., 'mensaje'=>...]
     */
    public function enviarASunat($idcomprobante)
    {
        // Recalcular totales antes de enviar (autoreparacion de comprobantes con IGV 0
        // generados antes de que la empresa tuviera la tasa configurada).
        $this->recalcularTotales($idcomprobante);

        $comp = $this->mostrarCompleto($idcomprobante);
        if (!$comp) return ['ok' => false, 'mensaje' => 'Comprobante no encontrado'];

        if (in_array($comp['estado'], ['aceptado','baja'], true)) {
            return ['ok' => false, 'mensaje' => 'El comprobante ya fue procesado: ' . $comp['estado']];
        }

        // MODO DEMO: si el certificado activo es de prueba, firmar/enviar a
        // SUNAT BETA con el RUC y credenciales demo (el ticket/PDF conserva
        // los datos reales de la empresa porque se generan aparte).
        if ((new Cargacertificado())->esDemo($comp['idempresa'])) {
            $comp = Cargacertificado::aplicarDatosDemo($comp);
        }

        // 1. Generar XML
        $xmlBuilder = new SunatXml();
        $xmlString  = $xmlBuilder->buildBoletaFactura($comp);

        $rutas = (new Rutas())->mostrar($comp['idempresa']);
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

        // SUNAT exige que el numero del nombre del archivo NO lleve ceros a la izquierda
        // y coincida con el cbc:ID del XML (que tampoco los lleva).
        $numeroSinPad = ltrim($comp['numero'], '0');
        if ($numeroSinPad === '') $numeroSinPad = '0';
        $nombreBase = $comp['empresa_ruc'] . '-' . $comp['tipo_documento'] . '-' . $comp['serie'] . '-' . $numeroSinPad;
        $xmlPath    = $rutaData . $nombreBase . '.xml';
        file_put_contents($xmlPath, $xmlString);

        // 2. Firmar
        $cert = (new Cargacertificado())->activo($comp['idempresa']);
        if (!$cert) {
            $this->logCdr($idcomprobante, 'ERROR', '', 'No hay certificado activo');
            return ['ok' => false, 'mensaje' => 'No hay certificado activo'];
        }
        $certRuta = $this->resolverRuta($cert['ruta']);

        $firmador = new SunatFirmador();
        $resFirma = $firmador->firmar($xmlPath, $certRuta, $cert['clave']);
        if (!$resFirma['ok']) {
            $this->logCdr($idcomprobante, 'FIRMA_ERROR', '', $resFirma['mensaje']);
            return ['ok' => false, 'mensaje' => 'Error al firmar: ' . $resFirma['mensaje']];
        }

        $xmlFirmadoPath = $rutaFirma . $nombreBase . '.xml';
        copy($xmlPath, $xmlFirmadoPath);

        // 3. Comprimir a ZIP
        $zipPath = $rutaEnvio . $nombreBase . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFile($xmlFirmadoPath, $nombreBase . '.xml');
            $zip->close();
        } else {
            return ['ok' => false, 'mensaje' => 'No se pudo crear ZIP'];
        }

        $this->actualizarRutas($idcomprobante, $nombreBase . '.xml', $xmlFirmadoPath, $zipPath, null, $resFirma['hash'] ?? '');

        // 4. Enviar a SUNAT
        $enviador = new SunatEnviador();
        $resEnvio = $enviador->enviar(
            $zipPath,
            $nombreBase . '.zip',
            $comp['empresa_ruc'],
            $comp['usuario_sol'],
            $comp['clave_sol'],
            $comp['ambiente']
        );

        $this->logCdr($idcomprobante, 'ENVIO_SUNAT', $resEnvio['codigo'] ?? '', $resEnvio['mensaje'] ?? '', '', json_encode($resEnvio));

        if ($resEnvio['ok']) {
            // Guardar CDR
            if (!empty($resEnvio['cdr_zip'])) {
                $cdrZipPath = $rutaRpta . 'R-' . $nombreBase . '.zip';
                file_put_contents($cdrZipPath, base64_decode($resEnvio['cdr_zip']));
                // Descomprimir
                $cdrZip = new ZipArchive();
                if ($cdrZip->open($cdrZipPath) === TRUE) {
                    $cdrZip->extractTo($rutaUnzip);
                    $cdrZip->close();
                }
                $cdrXmlPath = $rutaUnzip . 'R-' . $nombreBase . '.xml';
                global $conexion;
                $cdrE = $conexion->real_escape_string($cdrXmlPath);
                ejecutarConsulta("UPDATE comprobante_electronico SET cdr_ruta='$cdrE' WHERE idcomprobante='" . (int)$idcomprobante . "'");
            }

            $estado = $resEnvio['codigo'] === '0' ? 'aceptado' : ($resEnvio['observaciones'] ? 'aceptado_observado' : 'aceptado');
            $this->actualizarEstado($idcomprobante, $estado, $resEnvio['codigo'], $resEnvio['mensaje']);
            return ['ok' => true, 'estado' => $estado, 'codigo' => $resEnvio['codigo'], 'mensaje' => $resEnvio['mensaje']];
        } else {
            $codErr = $resEnvio['codigo'] ?? '';
            $estado = (strpos($codErr, '0') === 0 || $codErr === '') ? 'error' : 'rechazado';
            $this->actualizarEstado($idcomprobante, $estado, $codErr, $resEnvio['mensaje']);
            return ['ok' => false, 'estado' => $estado, 'codigo' => $codErr, 'mensaje' => $resEnvio['mensaje']];
        }
    }

    /**
     * Consulta a SUNAT el estado actual de un comprobante ya enviado (getStatusCdr).
     * Re-descarga el CDR y actualiza el estado local con la respuesta de SUNAT.
     */
    public function consultarEnSunat($idcomprobante)
    {
        $comp = $this->mostrarCompleto($idcomprobante);
        if (!$comp) return ['ok' => false, 'mensaje' => 'Comprobante no encontrado'];

        if (empty($comp['serie']) || empty($comp['numero'])) {
            return ['ok' => false, 'mensaje' => 'El comprobante no tiene serie/numero asignado'];
        }

        // MODO DEMO: consultar a SUNAT BETA con el mismo RUC/credenciales demo
        // con que se envio (si el certificado activo es de prueba).
        if ((new Cargacertificado())->esDemo($comp['idempresa'])) {
            $comp = Cargacertificado::aplicarDatosDemo($comp);
        }

        // SUNAT NO ofrece servicio de consulta (billConsultService) en el
        // ambiente BETA/pruebas — solo existe en produccion (confirmado en la
        // doc oficial de SUNAT). Evitar la llamada que daria 404 y avisar claro.
        if (($comp['ambiente'] ?? 'beta') !== 'produccion') {
            return ['ok' => false, 'estado' => $comp['estado'],
                    'mensaje' => 'La consulta de estado solo está disponible en PRODUCCIÓN. '
                               . 'SUNAT no ofrece este servicio en el ambiente de pruebas (beta).'];
        }

        $enviador = new SunatEnviador();
        $res = $enviador->consultarCdr(
            $comp['empresa_ruc'],
            $comp['tipo_documento'],
            $comp['serie'],
            $comp['numero'],
            $comp['usuario_sol'],
            $comp['clave_sol'],
            $comp['ambiente']
        );

        $this->logCdr($idcomprobante, 'CONSULTA_SUNAT', $res['codigo'] ?? '', $res['mensaje'] ?? '', '', json_encode($res));

        // Si SUNAT devolvio un CDR, guardarlo en el sistema
        if (!empty($res['cdr_zip'])) {
            $rutas = (new Rutas())->mostrar($comp['idempresa']);
            $rutaRpta  = $this->resolverRuta($rutas['ruta_rpta']  ?? '../sfs/rpta/');
            $rutaUnzip = $this->resolverRuta($rutas['ruta_unzip'] ?? '../sfs/unziprpta/');
            @mkdir($rutaRpta,  0777, true);
            @mkdir($rutaUnzip, 0777, true);

            $numeroSinPad = ltrim($comp['numero'], '0') ?: '0';
            $nombreBase   = $comp['empresa_ruc'] . '-' . $comp['tipo_documento'] . '-' . $comp['serie'] . '-' . $numeroSinPad;
            $cdrZipPath   = $rutaRpta . 'R-' . $nombreBase . '.zip';
            file_put_contents($cdrZipPath, base64_decode($res['cdr_zip']));

            $cdrZip = new ZipArchive();
            if ($cdrZip->open($cdrZipPath) === TRUE) {
                $cdrZip->extractTo($rutaUnzip);
                $cdrZip->close();
            }
            $cdrXmlPath = $rutaUnzip . 'R-' . $nombreBase . '.xml';
            global $conexion;
            $cdrE = $conexion->real_escape_string($cdrXmlPath);
            ejecutarConsulta("UPDATE comprobante_electronico SET cdr_ruta='$cdrE' WHERE idcomprobante='" . (int)$idcomprobante . "'");
        }

        // Actualizar estado local segun lo que dijo SUNAT
        if ($res['ok']) {
            $estado = !empty($res['con_observ']) ? 'aceptado_observado' : 'aceptado';
            $this->actualizarEstado($idcomprobante, $estado, $res['codigo'], $res['mensaje']);
            return ['ok' => true, 'estado' => $estado, 'codigo' => $res['codigo'], 'mensaje' => $res['mensaje'],
                    'observaciones' => $res['observaciones'] ?? null];
        }

        if (!empty($res['rechazado'])) {
            $this->actualizarEstado($idcomprobante, 'rechazado', $res['codigo'], $res['mensaje']);
            return ['ok' => false, 'estado' => 'rechazado', 'codigo' => $res['codigo'], 'mensaje' => $res['mensaje']];
        }

        if (!empty($res['no_existe'])) {
            // SUNAT no lo tiene registrado: queda pendiente para reenviar
            return ['ok' => false, 'estado' => $comp['estado'], 'codigo' => $res['codigo'] ?? '0156',
                    'mensaje' => 'SUNAT no tiene registrado este comprobante. Puedes reenviarlo.'];
        }

        return ['ok' => false, 'estado' => $comp['estado'], 'codigo' => $res['codigo'] ?? '',
                'mensaje' => $res['mensaje'] ?? 'No se pudo consultar SUNAT'];
    }

    /**
     * Recalcula valor_venta / igv_item / total_item de cada linea y los
     * totales del comprobante usando la tasa actual de la empresa cuando
     * el comprobante tiene tasa 0. Esto repara comprobantes generados
     * antes de configurar el IGV en empresa.
     */
    public function recalcularTotales($idcomprobante)
    {
        $idcomprobante = (int)$idcomprobante;
        $cab = $this->mostrar($idcomprobante);
        if (!$cab) return false;

        $tasa = (float)$cab['tasa_igv'];
        if ($tasa <= 0) {
            // Tomar la tasa vigente de la empresa
            $emp = (new Empresa())->mostrar($cab['idempresa']);
            $tasa = (float)($emp['tasa_igv'] ?? 0.18);
        }
        if ($tasa <= 0) $tasa = 0.18; // fallback final
        $factor = 1 + $tasa;

        // Recalcular cada linea respetando el tipo de afectacion
        global $conexion;
        $rs = $conexion->query("SELECT iddetalle, cantidad, precio_con_igv, codigo_afectacion
                                FROM comprobante_detalle
                                WHERE idcomprobante='$idcomprobante'");
        $sumGravado    = 0;
        $sumExonerado  = 0;
        $sumInafecto   = 0;
        $sumGratuito   = 0;
        $sumIgv        = 0;
        $sumTotal      = 0;
        while ($row = $rs->fetch_assoc()) {
            $cantidad     = (float)$row['cantidad'];
            $precioConIgv = (float)$row['precio_con_igv'];
            $afect        = $row['codigo_afectacion'] ?: '10';

            if ($afect === '20' || $afect === '30') {
                // Exonerado o Inafecto: sin IGV
                $precioUnit = $precioConIgv;
                $valorVenta = round($cantidad * $precioUnit, 2);
                $totalItem  = round($cantidad * $precioConIgv, 2);
                $igvItem    = 0;
                if ($afect === '20') $sumExonerado += $valorVenta;
                else                 $sumInafecto  += $valorVenta;
            } else {
                // Gravado
                $precioUnit = round($precioConIgv / $factor, 4);
                $valorVenta = round($cantidad * $precioUnit, 2);
                $totalItem  = round($cantidad * $precioConIgv, 2);
                $igvItem    = round($totalItem - $valorVenta, 2);
                $sumGravado += $valorVenta;
                $sumIgv     += $igvItem;
            }

            $iddet = (int)$row['iddetalle'];
            $conexion->query("UPDATE comprobante_detalle
                              SET precio_unitario = '$precioUnit',
                                  valor_venta    = '$valorVenta',
                                  igv_item       = '$igvItem',
                                  total_item     = '$totalItem'
                              WHERE iddetalle = '$iddet'");

            $sumTotal += $totalItem;
        }
        $rs->free();

        $sumGravado   = round($sumGravado, 2);
        $sumExonerado = round($sumExonerado, 2);
        $sumInafecto  = round($sumInafecto, 2);
        $sumGratuito  = round($sumGratuito, 2);
        $sumIgv       = round($sumIgv, 2);
        $sumTotal     = round($sumTotal, 2);
        $sumSubtotal  = round($sumGravado + $sumExonerado + $sumInafecto, 2);
        $totalLetras  = $conexion->real_escape_string($this->numeroALetras($sumTotal));

        $conexion->query("UPDATE comprobante_electronico
                          SET subtotal           = '$sumSubtotal',
                              subtotal_gravado   = '$sumGravado',
                              subtotal_exonerado = '$sumExonerado',
                              subtotal_inafecto  = '$sumInafecto',
                              subtotal_gratuito  = '$sumGratuito',
                              igv                = '$sumIgv',
                              total              = '$sumTotal',
                              tasa_igv           = '$tasa',
                              total_letras       = '$totalLetras'
                          WHERE idcomprobante='$idcomprobante'");
        return true;
    }

    private function resolverRuta($ruta)
    {
        // Convierte rutas relativas '../sfs/...' a absolutas usando el dir de PUERTO HABANA
        if (strpos($ruta, '..') === 0) {
            $base = realpath(__DIR__ . '/..');
            return rtrim($base, '/\\') . '/' . ltrim(substr($ruta, 3), '/\\') ;
        }
        return $ruta;
    }

    /**
     * Wrapper publico para numeroALetras (usado por NotaCreditoDebito).
     */
    public function _numeroALetrasPublic($numero)
    {
        return $this->numeroALetras($numero);
    }

    /**
     * Convierte numero a letras en castellano para "SON: ... NUEVOS SOLES"
     */
    private function numeroALetras($numero)
    {
        $entero   = (int)floor($numero);
        $decimal  = (int)round(($numero - $entero) * 100);
        $letras   = $this->convertirEntero($entero);
        $letras   = strtoupper($letras);
        $decStr   = str_pad($decimal, 2, '0', STR_PAD_LEFT);
        return "SON: $letras CON $decStr/100 SOLES";
    }

    private function convertirEntero($n)
    {
        if ($n === 0) return 'CERO';
        $unidades = ['','UNO','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE','DIEZ',
                     'ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISEIS','DIECISIETE','DIECIOCHO','DIECINUEVE'];
        $decenas  = ['','','VEINTI','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA'];
        $centenas = ['','CIENTO','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS',
                     'SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];

        $convertir3 = function($n) use ($unidades, $decenas, $centenas) {
            if ($n === 0) return '';
            if ($n === 100) return 'CIEN';
            $c = (int)floor($n / 100);
            $resto = $n % 100;
            $res = $centenas[$c];
            if ($resto > 0) {
                if ($resto < 20) {
                    $res .= ($res ? ' ' : '') . $unidades[$resto];
                } else {
                    $d = (int)floor($resto / 10);
                    $u = $resto % 10;
                    if ($d === 2 && $u > 0) {
                        $res .= ($res ? ' ' : '') . 'VEINTI' . strtolower($unidades[$u]);
                    } else {
                        $res .= ($res ? ' ' : '') . $decenas[$d];
                        if ($u > 0) $res .= ' Y ' . $unidades[$u];
                    }
                }
            }
            return $res;
        };

        if ($n < 1000) return $convertir3($n);
        if ($n < 1000000) {
            $miles = (int)floor($n / 1000);
            $resto = $n % 1000;
            $res = ($miles === 1 ? 'MIL' : $convertir3($miles) . ' MIL');
            if ($resto > 0) $res .= ' ' . $convertir3($resto);
            return $res;
        }
        // millones
        $mill  = (int)floor($n / 1000000);
        $resto = $n % 1000000;
        $res = ($mill === 1 ? 'UN MILLON' : $convertir3($mill) . ' MILLONES');
        if ($resto > 0) $res .= ' ' . $this->convertirEntero($resto);
        return $res;
    }
}
?>
