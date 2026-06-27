<?php
require_once __DIR__ . "/../config/Conexion.php";

class Permiso
{
    public function __construct() {}

    public function listar()
    {
        $sql = "SELECT * FROM permiso ORDER BY orden ASC, idpermiso ASC";
        return ejecutarConsulta($sql);
    }

    public function listarPorGrupo()
    {
        $sql = "SELECT * FROM permiso ORDER BY grupo ASC, orden ASC, idpermiso ASC";
        return ejecutarConsulta($sql);
    }

    public function mostrar($idpermiso)
    {
        $sql = "SELECT * FROM permiso WHERE idpermiso='$idpermiso'";
        return ejecutarConsultaSimpleFila($sql);
    }

    // Devuelve los codigos efectivos de un usuario:
    // (permisos del rol UNION grants del usuario) MINUS revokes del usuario
    public function efectivosDeUsuario($idusuario)
    {
        $sql = "
            SELECT DISTINCT p.codigo
            FROM permiso p
            WHERE p.idpermiso IN (
                SELECT rp.idpermiso
                FROM usuario u
                JOIN rol_permiso rp ON rp.idrol = u.idrol
                WHERE u.idusuario = '$idusuario'
                UNION
                SELECT up.idpermiso
                FROM usuario_permiso up
                WHERE up.idusuario = '$idusuario' AND up.tipo='grant'
            )
            AND p.idpermiso NOT IN (
                SELECT up.idpermiso
                FROM usuario_permiso up
                WHERE up.idusuario = '$idusuario' AND up.tipo='revoke'
            )
            ORDER BY p.codigo";
        $rs = ejecutarConsulta($sql);
        $codes = [];
        while ($r = $rs->fetch_assoc()) { $codes[] = $r['codigo']; }
        return $codes;
    }
}
?>
