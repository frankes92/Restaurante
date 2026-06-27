<?php
require_once __DIR__ . "/../config/Conexion.php";
require_once __DIR__ . "/Empresa.php";

/**
 * Modelo de WhatsApp:
 *   - Plantillas (CRUD)
 *   - Reemplazo de placeholders
 *   - Generacion del link wa.me
 *   - Historial de envios
 *   - Listado de clientes con WhatsApp (con filtros para envio masivo)
 */
class Whatsapp
{
    private $codigoPais = '51';  // Peru por defecto

    // ==================================================================
    // PLANTILLAS
    // ==================================================================

    public function listarPlantillas($soloActivas = false)
    {
        $where = $soloActivas ? "WHERE activo=1" : "";
        return ejecutarConsulta("SELECT * FROM whatsapp_plantilla $where ORDER BY tipo ASC, idplantilla ASC");
    }

    public function plantilla($idplantilla)
    {
        return dbFila("SELECT * FROM whatsapp_plantilla WHERE idplantilla = ?",
            'i', [(int)$idplantilla]);
    }

    public function plantillaPorCodigo($codigo)
    {
        return dbFila("SELECT * FROM whatsapp_plantilla WHERE codigo = ? AND activo=1",
            's', [$codigo]);
    }

    /**
     * Plantilla default segun el tipo de comprobante (boleta o factura).
     */
    public function plantillaParaCobro($tipoDocumento)
    {
        $codigo = ($tipoDocumento === '01' || $tipoDocumento === 'factura') ? 'factura' : 'boleta';
        return $this->plantillaPorCodigo($codigo);
    }

    public function guardarPlantilla($idplantilla, $codigo, $nombre, $mensaje, $tipo = 'generico', $activo = 1)
    {
        $codigo = preg_replace('/[^a-z0-9_]/i', '_', strtolower($codigo));
        if ($idplantilla > 0) {
            return dbQuery(
                "UPDATE whatsapp_plantilla
                 SET codigo=?, nombre=?, mensaje=?, tipo=?, activo=?
                 WHERE idplantilla=?",
                'ssssii',
                [$codigo, $nombre, $mensaje, $tipo, (int)$activo, (int)$idplantilla]
            );
        }
        return dbInsert(
            "INSERT INTO whatsapp_plantilla (codigo, nombre, mensaje, tipo, activo) VALUES (?, ?, ?, ?, ?)",
            'ssssi',
            [$codigo, $nombre, $mensaje, $tipo, (int)$activo]
        );
    }

    public function eliminarPlantilla($idplantilla)
    {
        return dbQuery("DELETE FROM whatsapp_plantilla WHERE idplantilla = ?",
            'i', [(int)$idplantilla]);
    }

    // ==================================================================
    // RENDER DE MENSAJE (placeholders)
    // ==================================================================

    /**
     * Toma un mensaje con placeholders y los reemplaza por datos reales.
     *
     * @param string $mensaje  Plantilla con {nombre}, {documento}, etc.
     * @param array  $datos    Datos del cliente / comprobante
     * @return string
     */
    public function renderMensaje($mensaje, array $datos)
    {
        $emp = (new Empresa())->mostrar(1) ?: [];
        $simbolo = $emp['simbolo_moneda'] ?? 'S/';

        $tipoDoc  = $datos['tipo_doc'] ?? '';   // '1' DNI, '6' RUC
        $tipoDocLabel = ($tipoDoc === '6') ? 'RUC' : 'DNI';

        $tipoComp = $datos['tipo_documento'] ?? '';
        $tipoCompLabel = $tipoComp === '01' ? 'Factura' :
                        ($tipoComp === '03' ? 'Boleta' :
                        ($tipoComp === '07' ? 'Nota de Crédito' :
                        ($tipoComp === '08' ? 'Nota de Débito' : 'Comprobante')));

        $totalNum = (float)($datos['total'] ?? 0);
        $totalFmt = $simbolo . ' ' . number_format($totalNum, 2);

        $reemplazos = [
            '{nombre}'           => $datos['nombre']           ?? '',
            '{documento}'        => $datos['documento']        ?? '',
            '{tipo_doc}'         => $tipoDocLabel,
            '{comprobante}'      => $datos['comprobante']      ?? '',
            '{tipo_comp}'        => $tipoCompLabel,
            '{total}'            => $totalFmt,
            '{fecha}'            => date('d/m/Y'),
            '{empresa}'          => $emp['nombre_comercial'] ?? $emp['razon_social'] ?? 'PUERTO HABANA POS',
            '{ruc_empresa}'      => $emp['numero_ruc']         ?? '',
            '{telefono_empresa}' => $emp['telefono']           ?? '',
            '{link_pdf}'         => $datos['link_pdf']         ?? '',
        ];

        return strtr($mensaje, $reemplazos);
    }

    /**
     * Construye el link de WhatsApp.
     * Usamos api.whatsapp.com/send (URL oficial recomendada por WhatsApp)
     * en vez de wa.me porque api.whatsapp preserva mejor los emojis y caracteres
     * unicode de 4 bytes (emojis modernos).
     */
    public function construirLinkWaMe($numero, $mensaje)
    {
        $numero = $this->normalizarNumero($numero);
        if (!$numero) return '';
        // Asegurar que el mensaje esta en UTF-8 valido (defensa contra encodings rotos)
        if (!mb_check_encoding($mensaje, 'UTF-8')) {
            $mensaje = mb_convert_encoding($mensaje, 'UTF-8', 'auto');
        }
        return 'https://api.whatsapp.com/send?phone=' . $numero . '&text=' . rawurlencode($mensaje);
    }

    /**
     * Normaliza un numero a formato internacional sin "+", listo para wa.me.
     * - Elimina espacios, guiones, parentesis, puntos
     * - Si tiene 9 digitos y empieza con 9, agrega el codigo de pais (51)
     * - Si ya tiene codigo de pais, lo respeta
     */
    public function normalizarNumero($numero)
    {
        $solo = preg_replace('/\D+/', '', (string)$numero);
        if ($solo === '') return '';
        // Si empieza con codigo de pais ya esta OK
        if (strlen($solo) >= 11 && substr($solo, 0, 2) === $this->codigoPais) {
            return $solo;
        }
        // Numero peruano de 9 digitos sin codigo
        if (strlen($solo) === 9 && $solo[0] === '9') {
            return $this->codigoPais . $solo;
        }
        // Numero corto invalido
        if (strlen($solo) < 9) return '';
        return $solo;
    }

    // ==================================================================
    // HISTORIAL DE ENVIOS
    // ==================================================================

    public function registrarEnvio(array $datos)
    {
        $E = function($v){ global $conexion; return $conexion->real_escape_string((string)$v); };
        $idcliente     = empty($datos['idcliente'])     ? 'NULL' : "'" . (int)$datos['idcliente'] . "'";
        $idclifact     = empty($datos['idclifact'])     ? 'NULL' : "'" . (int)$datos['idclifact'] . "'";
        $idcomprobante = empty($datos['idcomprobante']) ? 'NULL' : "'" . (int)$datos['idcomprobante'] . "'";
        $idplantilla   = empty($datos['idplantilla'])   ? 'NULL' : "'" . (int)$datos['idplantilla'] . "'";
        $idusuario     = empty($datos['idusuario'])     ? 'NULL' : "'" . (int)$datos['idusuario'] . "'";
        $tipo          = in_array($datos['tipo'] ?? 'manual', ['cobro','masivo','manual'], true)
                         ? $datos['tipo'] : 'manual';

        $sql = "INSERT INTO whatsapp_envio
                (idcliente, idclifact, idcomprobante, idplantilla, numero,
                 nombre_cliente, documento, mensaje, idusuario, tipo)
                VALUES
                ($idcliente, $idclifact, $idcomprobante, $idplantilla,
                 '" . $E($datos['numero'] ?? '') . "',
                 '" . $E($datos['nombre_cliente'] ?? '') . "',
                 '" . $E($datos['documento'] ?? '') . "',
                 '" . $E($datos['mensaje'] ?? '') . "',
                 $idusuario,
                 '$tipo')";
        return ejecutarConsulta_retornarID($sql);
    }

    public function listarHistorial($filtros = [])
    {
        $where = " 1=1 ";
        if (!empty($filtros['tipo']))   $where .= " AND we.tipo='" . addslashes($filtros['tipo']) . "' ";
        if (!empty($filtros['desde']))  $where .= " AND DATE(we.enviado) >= '" . addslashes($filtros['desde']) . "' ";
        if (!empty($filtros['hasta']))  $where .= " AND DATE(we.enviado) <= '" . addslashes($filtros['hasta']) . "' ";
        if (!empty($filtros['numero'])) $where .= " AND we.numero LIKE '%" . addslashes($filtros['numero']) . "%' ";

        $sql = "SELECT we.*, p.codigo AS plantilla_codigo, p.nombre AS plantilla_nombre,
                       u.login AS usuario_login
                FROM whatsapp_envio we
                LEFT JOIN whatsapp_plantilla p ON p.idplantilla = we.idplantilla
                LEFT JOIN usuario u ON u.idusuario = we.idusuario
                WHERE $where
                ORDER BY we.idenvio DESC
                LIMIT 500";
        return ejecutarConsulta($sql);
    }

    // ==================================================================
    // LISTADO DE CLIENTES (para envio masivo)
    // ==================================================================

    /**
     * Devuelve clientes con whatsapp segun filtros:
     *   - 'todos'        : todos con whatsapp
     *   - 'cumple_mes'   : cumpleañeros del mes actual
     *   - 'cumple_hoy'   : cumpleañeros hoy
     *   - 'frecuentes'   : con >=N ordenes
     *   - 'vip'          : con total_gastado >= N
     *   - 'inactivos'    : sin visita en >N dias
     */
    public function clientesParaWhatsapp($filtro = 'todos', $param = 0)
    {
        $where = " (c.whatsapp IS NOT NULL AND c.whatsapp <> '') OR (c.telefono IS NOT NULL AND c.telefono <> '') ";

        switch ($filtro) {
            case 'cumple_mes':
                $where .= " AND MONTH(c.fecha_nacimiento) = MONTH(CURDATE()) ";
                break;
            case 'cumple_hoy':
                $where .= " AND MONTH(c.fecha_nacimiento) = MONTH(CURDATE())
                            AND DAY(c.fecha_nacimiento) = DAY(CURDATE()) ";
                break;
            case 'frecuentes':
                $n = max(1, (int)$param);
                $where .= " AND c.total_ordenes >= $n ";
                break;
            case 'vip':
                $n = max(1, (int)$param);
                $where .= " AND c.total_gastado >= $n ";
                break;
            case 'inactivos':
                $n = max(1, (int)$param);
                $where .= " AND (c.ultima_visita IS NULL OR c.ultima_visita < DATE_SUB(CURDATE(), INTERVAL $n DAY)) ";
                break;
        }

        $sql = "SELECT c.idcliente, c.nombre, c.documento, c.telefono, c.whatsapp,
                       c.fecha_nacimiento, c.total_ordenes, c.total_gastado, c.ultima_visita
                FROM cliente c
                WHERE c.estado=1 AND ($where)
                ORDER BY c.nombre ASC";
        return ejecutarConsulta($sql);
    }

    /**
     * Actualiza el numero de whatsapp del cliente (cuando se envia desde el cobro).
     */
    public function guardarWhatsappCliente($idcliente, $whatsapp)
    {
        if (!$idcliente) return false;
        return dbQuery("UPDATE cliente SET whatsapp = ? WHERE idcliente = ?",
            'si', [$whatsapp, (int)$idcliente]);
    }

    /**
     * Actualiza el whatsapp en cliente_facturacion (para boletas/facturas).
     */
    public function guardarWhatsappFacturacion($idclifact, $whatsapp)
    {
        if (!$idclifact) return false;
        return dbQuery("UPDATE cliente_facturacion SET whatsapp = ? WHERE idclifact = ?",
            'si', [$whatsapp, (int)$idclifact]);
    }

    /**
     * Busca el numero ya guardado del cliente (preferencia: cliente_facturacion > cliente).
     */
    public function buscarWhatsappPorDocumento($documento)
    {
        if (!$documento) return null;
        $row = dbFila(
            "SELECT COALESCE(NULLIF(whatsapp,''), telefono) AS num
             FROM cliente_facturacion
             WHERE numero_documento = ?
             ORDER BY idclifact DESC LIMIT 1",
            's', [$documento]
        );
        if ($row && !empty($row['num'])) return $row['num'];

        $row = dbFila(
            "SELECT COALESCE(NULLIF(whatsapp,''), telefono) AS num
             FROM cliente
             WHERE documento = ?
             ORDER BY idcliente DESC LIMIT 1",
            's', [$documento]
        );
        return $row['num'] ?? null;
    }

    /**
     * Estadisticas para el dashboard de WhatsApp
     */
    public function estadisticas()
    {
        $stats = ejecutarConsultaSimpleFila(
            "SELECT
                COUNT(*) AS total_envios,
                SUM(CASE WHEN tipo='cobro'   THEN 1 ELSE 0 END) AS cobros,
                SUM(CASE WHEN tipo='masivo'  THEN 1 ELSE 0 END) AS masivos,
                SUM(CASE WHEN tipo='manual'  THEN 1 ELSE 0 END) AS manuales,
                SUM(CASE WHEN DATE(enviado) = CURDATE() THEN 1 ELSE 0 END) AS hoy,
                SUM(CASE WHEN DATE(enviado) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS ultimos_7
             FROM whatsapp_envio"
        );
        $clientes = ejecutarConsultaSimpleFila(
            "SELECT COUNT(*) AS con_wa FROM cliente
             WHERE estado=1 AND ((whatsapp IS NOT NULL AND whatsapp<>'') OR (telefono IS NOT NULL AND telefono<>''))"
        );
        $cumples = ejecutarConsultaSimpleFila(
            "SELECT COUNT(*) AS total FROM cliente
             WHERE estado=1 AND fecha_nacimiento IS NOT NULL
               AND MONTH(fecha_nacimiento)=MONTH(CURDATE())"
        );
        return [
            'total_envios' => (int)($stats['total_envios'] ?? 0),
            'cobros'       => (int)($stats['cobros'] ?? 0),
            'masivos'      => (int)($stats['masivos'] ?? 0),
            'manuales'     => (int)($stats['manuales'] ?? 0),
            'hoy'          => (int)($stats['hoy'] ?? 0),
            'ultimos_7'    => (int)($stats['ultimos_7'] ?? 0),
            'clientes_con_wa' => (int)($clientes['con_wa'] ?? 0),
            'cumples_del_mes' => (int)($cumples['total'] ?? 0),
        ];
    }
}
