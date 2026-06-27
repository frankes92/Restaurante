<?php
require_once __DIR__ . "/../config/Conexion.php";

class Licencia
{
    /**
     * Devuelve la licencia activa (la mas reciente).
     */
    public function actual()
    {
        return dbFila("SELECT * FROM licencia ORDER BY idlicencia DESC LIMIT 1");
    }

    public function listar()
    {
        return ejecutarConsulta("SELECT * FROM licencia ORDER BY idlicencia DESC");
    }

    public function listarHistorial($idlicencia)
    {
        return dbQuery(
            "SELECT lh.*, u.nombre AS usuario_nombre
             FROM licencia_historial lh
             LEFT JOIN usuario u ON u.idusuario = lh.idusuario
             WHERE lh.idlicencia = ?
             ORDER BY lh.idhistorial DESC",
            'i', [(int)$idlicencia]
        );
    }

    /**
     * Crea una nueva licencia (reemplaza la anterior si existe).
     */
    public function crear($cliente, $fechaInicio, $fechaVenc, $diasAviso, $obs, $idusuario = null)
    {
        $id = dbInsert(
            "INSERT INTO licencia (cliente_nombre, fecha_inicio, fecha_vencimiento, dias_aviso, estado, observacion)
             VALUES (?, ?, ?, ?, 'activa', ?)",
            'sssis',
            [$cliente, $fechaInicio, $fechaVenc, (int)$diasAviso, $obs]
        );
        $this->_historial($id, 'crear', null, $fechaVenc, null, $obs, $idusuario);
        return $id;
    }

    /**
     * Extiende la fecha de vencimiento (suma N dias o establece fecha).
     */
    public function extender($idlicencia, $nuevaFechaVenc, $monto, $obs, $idusuario = null)
    {
        $row = $this->actual();
        if (!$row) return false;
        $anterior = $row['fecha_vencimiento'];
        $r = dbQuery(
            "UPDATE licencia SET fecha_vencimiento = ?, estado = 'activa', observacion = ?
             WHERE idlicencia = ?",
            'ssi',
            [$nuevaFechaVenc, $obs, (int)$idlicencia]
        );
        $this->_historial($idlicencia, 'extender', $anterior, $nuevaFechaVenc, $monto, $obs, $idusuario);
        return (bool)$r;
    }

    public function suspender($idlicencia, $obs, $idusuario = null)
    {
        $r = dbQuery("UPDATE licencia SET estado='suspendida', observacion=? WHERE idlicencia=?",
            'si', [$obs, (int)$idlicencia]);
        $this->_historial($idlicencia, 'suspender', null, null, null, $obs, $idusuario);
        return (bool)$r;
    }

    public function reactivar($idlicencia, $obs, $idusuario = null)
    {
        $r = dbQuery("UPDATE licencia SET estado='activa', observacion=? WHERE idlicencia=?",
            'si', [$obs, (int)$idlicencia]);
        $this->_historial($idlicencia, 'reactivar', null, null, null, $obs, $idusuario);
        return (bool)$r;
    }

    private function _historial($idlicencia, $accion, $venAnt, $venNue, $monto, $obs, $idusuario)
    {
        $idu = $idusuario === null || $idusuario === '' ? null : (int)$idusuario;
        $mon = $monto === null || $monto === '' ? null : (float)$monto;
        dbQuery(
            "INSERT INTO licencia_historial (idlicencia, accion, vencimiento_anterior, vencimiento_nuevo, monto_pagado, observacion, idusuario)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            'isssdsi',
            [(int)$idlicencia, $accion, $venAnt, $venNue, $mon, $obs, $idu]
        );
    }

    /**
     * Verifica una clave maestra del proveedor (la unica que puede crear/extender).
     */
    public function verificarMaster($clave)
    {
        if (!defined('LICENSE_MASTER_KEY')) return false;
        return hash_equals(LICENSE_MASTER_KEY, (string)$clave);
    }
}
