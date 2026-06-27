<?php
require_once __DIR__ . "/../config/Conexion.php";

class Impresora
{
    public function listar($soloActivas = false)
    {
        $where = $soloActivas ? "WHERE activa = 1" : "";
        $sql = "SELECT * FROM impresora $where ORDER BY tipo, nombre";
        return ejecutarConsulta($sql);
    }

    public function mostrar($idimpresora)
    {
        $idimpresora = (int)$idimpresora;
        return ejecutarConsultaSimpleFila("SELECT * FROM impresora WHERE idimpresora='$idimpresora'");
    }

    public function buscarPorTipo($tipo)
    {
        global $conexion;
        $tipo = $conexion->real_escape_string($tipo);
        return ejecutarConsultaSimpleFila(
            "SELECT * FROM impresora WHERE tipo='$tipo' AND activa=1 ORDER BY idimpresora ASC LIMIT 1"
        );
    }

    public function guardar($id, $nombre, $ip, $puerto, $tipo, $anchoCols, $cortarPapel, $activa)
    {
        global $conexion;
        $nombre   = $conexion->real_escape_string(trim($nombre));
        $ip       = $conexion->real_escape_string(trim($ip));
        $puerto   = (int)($puerto ?: 9100);
        $tipo     = in_array($tipo, ['cocina','bar','caja','otro'], true) ? $tipo : 'cocina';
        $ancho    = (int)($anchoCols ?: 32);
        $cortar   = (int)(!!$cortarPapel);
        $activa   = (int)(!!$activa);

        if (empty($id)) {
            $sql = "INSERT INTO impresora (nombre, ip, puerto, tipo, ancho_cols, cortar_papel, activa)
                    VALUES ('$nombre','$ip','$puerto','$tipo','$ancho','$cortar','$activa')";
            return ejecutarConsulta_retornarID($sql);
        }
        $id = (int)$id;
        $sql = "UPDATE impresora
                SET nombre='$nombre', ip='$ip', puerto='$puerto', tipo='$tipo',
                    ancho_cols='$ancho', cortar_papel='$cortar', activa='$activa'
                WHERE idimpresora='$id'";
        ejecutarConsulta($sql);
        return $id;
    }

    public function eliminar($idimpresora)
    {
        $idimpresora = (int)$idimpresora;
        return ejecutarConsulta("DELETE FROM impresora WHERE idimpresora='$idimpresora'");
    }

    // ============================================================
    // COLA DE IMPRESION
    // ============================================================

    public function encolar($idimpresora, $tipo, $payload, $idorden = null)
    {
        global $conexion;
        $idimpresora = (int)$idimpresora;
        $tipo        = in_array($tipo, ['comanda','comanda_anular','ticket','prueba','comprobante'], true) ? $tipo : 'comanda';
        $payloadEsc  = $conexion->real_escape_string(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $idordenSql  = ($idorden ? "'" . (int)$idorden . "'" : 'NULL');

        $sql = "INSERT INTO cola_impresion (idimpresora, idorden, tipo, payload, estado)
                VALUES ('$idimpresora', $idordenSql, '$tipo', '$payloadEsc', 'pendiente')";
        return ejecutarConsulta_retornarID($sql);
    }

    public function pendientes($limit = 20)
    {
        $limit = (int)$limit;
        $sql = "SELECT c.*, i.nombre AS impresora_nombre, i.ip AS impresora_ip,
                       i.puerto AS impresora_puerto, i.ancho_cols, i.cortar_papel
                FROM cola_impresion c
                INNER JOIN impresora i ON i.idimpresora = c.idimpresora
                WHERE c.estado = 'pendiente'
                  AND c.intentos < 5
                  AND i.activa = 1
                ORDER BY c.fecha_creacion ASC
                LIMIT $limit";
        return ejecutarConsulta($sql);
    }

    public function marcarImprimiendo($idcola)
    {
        $idcola = (int)$idcola;
        return ejecutarConsulta(
            "UPDATE cola_impresion SET estado='imprimiendo', intentos=intentos+1
             WHERE idcola='$idcola' AND estado='pendiente'"
        );
    }

    public function marcarImpreso($idcola)
    {
        $idcola = (int)$idcola;
        return ejecutarConsulta(
            "UPDATE cola_impresion SET estado='impreso', fecha_impresion=NOW()
             WHERE idcola='$idcola'"
        );
    }

    public function marcarError($idcola, $mensaje = '')
    {
        global $conexion;
        $idcola = (int)$idcola;
        $msg    = $conexion->real_escape_string(substr($mensaje, 0, 250));
        return ejecutarConsulta(
            "UPDATE cola_impresion
             SET estado='error', error_msg='$msg'
             WHERE idcola='$idcola'"
        );
    }

    public function reintentar($idcola)
    {
        $idcola = (int)$idcola;
        return ejecutarConsulta(
            "UPDATE cola_impresion SET estado='pendiente', intentos=0, error_msg=NULL
             WHERE idcola='$idcola'"
        );
    }

    public function limpiarHistorial($dias = 7)
    {
        $dias = (int)$dias;
        return ejecutarConsulta(
            "DELETE FROM cola_impresion
             WHERE estado='impreso' AND fecha_impresion < DATE_SUB(NOW(), INTERVAL $dias DAY)"
        );
    }
}
?>
