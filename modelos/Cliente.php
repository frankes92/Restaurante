<?php
require_once __DIR__ . "/../config/Conexion.php";

class Cliente
{
    public function __construct() {}

    public function insertar($nombre, $documento, $telefono, $email)
    {
        $sql = "INSERT INTO cliente (nombre, documento, telefono, email, total_ordenes, total_gastado, estado)
                VALUES ('$nombre','$documento','$telefono','$email','0','0','1')";
        return ejecutarConsulta_retornarID($sql);
    }

    public function editar($idcliente, $nombre, $documento, $telefono, $email)
    {
        $sql = "UPDATE cliente SET
                    nombre='$nombre',
                    documento='$documento',
                    telefono='$telefono',
                    email='$email'
                WHERE idcliente='$idcliente'";
        return ejecutarConsulta($sql);
    }

    public function desactivar($idcliente)
    {
        $sql = "UPDATE cliente SET estado='0' WHERE idcliente='$idcliente'";
        return ejecutarConsulta($sql);
    }

    public function activar($idcliente)
    {
        $sql = "UPDATE cliente SET estado='1' WHERE idcliente='$idcliente'";
        return ejecutarConsulta($sql);
    }

    public function mostrar($idcliente)
    {
        $sql = "SELECT * FROM cliente WHERE idcliente='$idcliente'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listar()
    {
        $sql = "SELECT * FROM cliente WHERE estado=1 ORDER BY idcliente DESC";
        return ejecutarConsulta($sql);
    }

    public function buscar($key)
    {
        $sql = "SELECT * FROM cliente
                WHERE estado=1
                  AND (nombre LIKE '%$key%' OR documento LIKE '%$key%' OR telefono LIKE '%$key%' OR email LIKE '%$key%')
                ORDER BY nombre ASC";
        return ejecutarConsulta($sql);
    }

    // Busqueda exacta por numero de documento (DNI/RUC) para anti-duplicado.
    // Incluye clientes inactivos para reactivarlos si reaparecen.
    public function buscarPorDocumento($documento)
    {
        $doc = trim((string)$documento);
        if ($doc === '') return null;
        $sql = "SELECT * FROM cliente WHERE documento='$doc' ORDER BY idcliente DESC LIMIT 1";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function estadisticas()
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN total_gastado >= 500 THEN 1 ELSE 0 END) AS vip,
                    COALESCE(AVG(total_ordenes), 0) AS promedio_ordenes,
                    COALESCE(SUM(total_gastado), 0) AS ventas_total
                FROM cliente
                WHERE estado=1";
        return ejecutarConsultaSimpleFila($sql);
    }

    // ---------- DataTables server-side ----------

    public function listarServerSide($start, $length, $search, $orderCol, $orderDir)
    {
        $cols = ['idcliente','nombre','documento','telefono','email','total_ordenes','total_gastado','ultima_visita'];
        $orderCol = isset($cols[(int)$orderCol]) ? $cols[(int)$orderCol] : 'idcliente';
        $orderDir = strtolower($orderDir) === 'asc' ? 'ASC' : 'DESC';
        $start    = max(0, (int)$start);
        $length   = max(1, min(500, (int)$length));

        $where = " estado=1 ";
        if ($search !== '') {
            $s = $search;
            $where .= " AND (nombre LIKE '%$s%' OR documento LIKE '%$s%' OR telefono LIKE '%$s%' OR email LIKE '%$s%') ";
        }

        $sql = "SELECT * FROM cliente
                WHERE $where
                ORDER BY $orderCol $orderDir
                LIMIT $start, $length";
        return ejecutarConsulta($sql);
    }

    public function contarServerSide($search, $useFilter = true)
    {
        $where = " estado=1 ";
        if ($useFilter && $search !== '') {
            $s = $search;
            $where .= " AND (nombre LIKE '%$s%' OR documento LIKE '%$s%' OR telefono LIKE '%$s%' OR email LIKE '%$s%') ";
        }
        $sql = "SELECT COUNT(*) AS total FROM cliente WHERE $where";
        $row = ejecutarConsultaSimpleFila($sql);
        return (int)$row['total'];
    }

    public function recalcularTotales($idcliente)
    {
        $sql = "UPDATE cliente c
                SET
                    c.total_ordenes = (SELECT COUNT(*) FROM orden o WHERE o.idcliente=c.idcliente AND o.estado='pagada'),
                    c.total_gastado = (SELECT COALESCE(SUM(o.total),0) FROM orden o WHERE o.idcliente=c.idcliente AND o.estado='pagada'),
                    c.ultima_visita = (SELECT MAX(DATE(o.fecha_pago)) FROM orden o WHERE o.idcliente=c.idcliente AND o.estado='pagada')
                WHERE c.idcliente='$idcliente'";
        return ejecutarConsulta($sql);
    }
}
?>
