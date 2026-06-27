<?php
require_once __DIR__ . "/../config/Conexion.php";

class Rutas
{
    public function mostrar($idempresa = 1)
    {
        $sql = "SELECT * FROM rutas WHERE idempresa='$idempresa' LIMIT 1";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function editar($idempresa, $datos)
    {
        $campos = ['ruta_data','ruta_firma','ruta_envio','ruta_rpta','ruta_unzip','ruta_baja','ruta_resumen','ruta_pdf'];
        $sets = [];
        foreach ($campos as $c) {
            if (isset($datos[$c])) $sets[] = "$c='" . $datos[$c] . "'";
        }
        if (empty($sets)) return false;
        $sql = "UPDATE rutas SET " . implode(',', $sets) . " WHERE idempresa='$idempresa'";
        return ejecutarConsulta($sql);
    }
}
?>
