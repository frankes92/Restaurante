<?php
require_once __DIR__ . "/../config/Conexion.php";

class Numeracion
{
    /**
     * Devuelve el siguiente numero disponible y lo INCREMENTA atomicamente.
     * Devuelve string con padding a 8 ceros (ej: "00000001").
     */
    public function siguiente($idempresa, $tipoDocumento, $serie)
    {
        global $conexion;
        $conexion->begin_transaction();
        try {
            // SELECT FOR UPDATE para evitar race conditions
            $rs = $conexion->query("SELECT idnumeracion, ultimo_numero FROM numeracion
                                    WHERE idempresa='$idempresa' AND tipo_documento='$tipoDocumento'
                                          AND serie='$serie' AND estado=1
                                    LIMIT 1 FOR UPDATE");
            if (!$rs || $rs->num_rows === 0) {
                $conexion->rollback();
                return false;
            }
            $row = $rs->fetch_assoc();
            $next = ((int)$row['ultimo_numero']) + 1;
            $conexion->query("UPDATE numeracion SET ultimo_numero='$next'
                              WHERE idnumeracion='" . $row['idnumeracion'] . "'");
            $conexion->commit();
            return str_pad($next, 8, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            $conexion->rollback();
            return false;
        }
    }

    /**
     * Indica si existe una serie ACTIVA para el tipo de documento dado.
     * Usado para validar antes de cobrar boleta/factura.
     */
    public function existeSerieActiva($tipoDocumento, $idempresa = 1)
    {
        global $conexion;
        $td = $conexion->real_escape_string($tipoDocumento);
        $idempresa = (int)$idempresa;
        $row = ejecutarConsultaSimpleFila(
            "SELECT COUNT(idnumeracion) AS n FROM numeracion
             WHERE idempresa='$idempresa' AND tipo_documento='$td' AND estado=1"
        );
        return $row && (int)$row['n'] > 0;
    }

    /**
     * Devuelve que tipos de comprobante electronico tienen serie activa.
     * Util para mostrar/ocultar botones en el modal de cobro.
     */
    public function tiposDisponibles($idempresa = 1)
    {
        $idempresa = (int)$idempresa;
        return [
            'boleta'  => $this->existeSerieActiva('03', $idempresa),
            'factura' => $this->existeSerieActiva('01', $idempresa),
        ];
    }

    public function listar($idempresa = 1)
    {
        $sql = "SELECT * FROM numeracion WHERE idempresa='$idempresa' ORDER BY tipo_documento ASC, serie ASC";
        return ejecutarConsulta($sql);
    }

    public function mostrar($idnumeracion)
    {
        $sql = "SELECT * FROM numeracion WHERE idnumeracion='$idnumeracion'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function insertar($idempresa, $tipoDocumento, $serie, $ultimoNumero, $descripcion)
    {
        $sql = "INSERT INTO numeracion (idempresa, tipo_documento, serie, ultimo_numero, descripcion, estado)
                VALUES ('$idempresa','$tipoDocumento','$serie','$ultimoNumero','$descripcion',1)";
        return ejecutarConsulta_retornarID($sql);
    }

    public function editar($idnumeracion, $serie, $ultimoNumero, $descripcion)
    {
        $sql = "UPDATE numeracion SET serie='$serie', ultimo_numero='$ultimoNumero', descripcion='$descripcion'
                WHERE idnumeracion='$idnumeracion'";
        return ejecutarConsulta($sql);
    }

    public function activar($idnumeracion)   { return ejecutarConsulta("UPDATE numeracion SET estado=1 WHERE idnumeracion='$idnumeracion'"); }
    public function desactivar($idnumeracion){ return ejecutarConsulta("UPDATE numeracion SET estado=0 WHERE idnumeracion='$idnumeracion'"); }
}
?>
