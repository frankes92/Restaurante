<?php
require_once __DIR__ . "/../config/Conexion.php";

class Rol
{
    public function __construct() {}

    public function insertar($codigo, $nombre, $descripcion)
    {
        $sql = "INSERT INTO rol (codigo, nombre, descripcion, estado)
                VALUES ('$codigo','$nombre','$descripcion','1')";
        return ejecutarConsulta_retornarID($sql);
    }

    public function editar($idrol, $codigo, $nombre, $descripcion)
    {
        $sql = "UPDATE rol SET codigo='$codigo', nombre='$nombre', descripcion='$descripcion'
                WHERE idrol='$idrol'";
        return ejecutarConsulta($sql);
    }

    public function desactivar($idrol)
    {
        $sql = "UPDATE rol SET estado='0' WHERE idrol='$idrol'";
        return ejecutarConsulta($sql);
    }

    public function activar($idrol)
    {
        $sql = "UPDATE rol SET estado='1' WHERE idrol='$idrol'";
        return ejecutarConsulta($sql);
    }

    public function mostrar($idrol)
    {
        $sql = "SELECT * FROM rol WHERE idrol='$idrol'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listar()
    {
        $sql = "SELECT * FROM rol WHERE estado=1 ORDER BY idrol ASC";
        return ejecutarConsulta($sql);
    }

    // Permisos asignados al rol
    public function permisos($idrol)
    {
        $sql = "SELECT p.idpermiso, p.codigo, p.nombre, p.grupo
                FROM rol_permiso rp
                JOIN permiso p ON p.idpermiso = rp.idpermiso
                WHERE rp.idrol='$idrol'
                ORDER BY p.orden ASC";
        return ejecutarConsulta($sql);
    }

    // Reemplaza permisos del rol con los del array de ids dado
    public function setPermisos($idrol, array $idsPermisos)
    {
        ejecutarConsulta("DELETE FROM rol_permiso WHERE idrol='$idrol'");
        foreach ($idsPermisos as $idp) {
            $idp = (int)$idp;
            if ($idp > 0) {
                ejecutarConsulta("INSERT IGNORE INTO rol_permiso (idrol, idpermiso) VALUES ('$idrol','$idp')");
            }
        }
        return true;
    }
}
?>
