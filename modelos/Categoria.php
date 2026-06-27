<?php
require_once __DIR__ . "/../config/Conexion.php";

class Categoria
{
    public function __construct() {}

    public function insertar($codigo, $nombre, $icono, $color, $orden, $mostrarComanda = 1)
    {
        $mostrarComanda = $mostrarComanda ? 1 : 0;
        $sql = "INSERT INTO categoria (codigo, nombre, icono, color, orden, estado, mostrar_comanda)
                VALUES ('$codigo','$nombre','$icono','$color','$orden','1','$mostrarComanda')";
        return ejecutarConsulta_retornarID($sql);
    }

    public function editar($idcategoria, $codigo, $nombre, $icono, $color, $orden, $mostrarComanda = 1)
    {
        $mostrarComanda = $mostrarComanda ? 1 : 0;
        $sql = "UPDATE categoria SET
                    codigo='$codigo',
                    nombre='$nombre',
                    icono='$icono',
                    color='$color',
                    orden='$orden',
                    mostrar_comanda='$mostrarComanda'
                WHERE idcategoria='$idcategoria'";
        return ejecutarConsulta($sql);
    }

    public function desactivar($idcategoria)
    {
        $sql = "UPDATE categoria SET estado='0' WHERE idcategoria='$idcategoria'";
        return ejecutarConsulta($sql);
    }

    public function activar($idcategoria)
    {
        $sql = "UPDATE categoria SET estado='1' WHERE idcategoria='$idcategoria'";
        return ejecutarConsulta($sql);
    }

    public function mostrar($idcategoria)
    {
        $sql = "SELECT * FROM categoria WHERE idcategoria='$idcategoria'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listar()
    {
        $sql = "SELECT * FROM categoria ORDER BY orden ASC, idcategoria ASC";
        return ejecutarConsulta($sql);
    }

    public function listarActivas()
    {
        $sql = "SELECT * FROM categoria WHERE estado=1 ORDER BY orden ASC, idcategoria ASC";
        return ejecutarConsulta($sql);
    }

    public function listarServerSide($start, $length, $search, $orderCol, $orderDir)
    {
        $cols = ['idcategoria','codigo','nombre','orden','estado'];
        $orderCol = isset($cols[(int)$orderCol]) ? $cols[(int)$orderCol] : 'orden';
        $orderDir = strtolower($orderDir) === 'asc' ? 'ASC' : 'DESC';
        $start    = max(0, (int)$start);
        $length   = max(1, min(500, (int)$length));
        $where = " 1=1 ";
        if ($search !== '') $where .= " AND (nombre LIKE '%$search%' OR codigo LIKE '%$search%') ";
        $sql = "SELECT * FROM categoria WHERE $where ORDER BY $orderCol $orderDir LIMIT $start, $length";
        return ejecutarConsulta($sql);
    }

    public function contar($search = '', $useFilter = true)
    {
        $where = " 1=1 ";
        if ($useFilter && $search !== '') $where .= " AND (nombre LIKE '%$search%' OR codigo LIKE '%$search%') ";
        $sql = "SELECT COUNT(*) AS total FROM categoria WHERE $where";
        $row = ejecutarConsultaSimpleFila($sql);
        return (int)$row['total'];
    }
}
?>
