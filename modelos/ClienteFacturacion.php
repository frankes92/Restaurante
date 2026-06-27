<?php
require_once __DIR__ . "/../config/Conexion.php";

class ClienteFacturacion
{
    public function buscarPorDoc($numeroDocumento)
    {
        $sql = "SELECT * FROM cliente_facturacion WHERE numero_documento='$numeroDocumento' ORDER BY idclifact DESC LIMIT 1";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function insertar($tipoDoc, $numeroDoc, $razonSocial, $direccion, $email, $telefono, $idcliente = null)
    {
        $idcli = ($idcliente === null || $idcliente === '') ? 'NULL' : "'$idcliente'";
        $sql = "INSERT INTO cliente_facturacion
                (idcliente, tipo_documento, numero_documento, razon_social, direccion, email, telefono)
                VALUES ($idcli, '$tipoDoc', '$numeroDoc', '$razonSocial', '$direccion', '$email', '$telefono')";
        return ejecutarConsulta_retornarID($sql);
    }

    public function buscarOInsertar($tipoDoc, $numeroDoc, $razonSocial, $direccion = '', $email = '', $telefono = '')
    {
        $existe = $this->buscarPorDoc($numeroDoc);
        if ($existe) {
            // Si el registro existente tiene campos vacios y llegamos con datos,
            // los completamos sin sobrescribir lo que ya tiene contenido.
            $sets = [];
            global $conexion;
            $upd = function($col, $valor) use ($existe, &$sets, $conexion) {
                $actual = trim((string)($existe[$col] ?? ''));
                $nuevo  = trim((string)$valor);
                if ($actual === '' && $nuevo !== '') {
                    $sets[] = "$col='" . $conexion->real_escape_string($nuevo) . "'";
                }
            };
            $upd('razon_social', $razonSocial);
            $upd('direccion',    $direccion);
            $upd('email',        $email);
            $upd('telefono',     $telefono);

            if (!empty($sets)) {
                $idclifact = (int)$existe['idclifact'];
                ejecutarConsulta("UPDATE cliente_facturacion SET " . implode(',', $sets) . " WHERE idclifact='$idclifact'");
            }
            return (int)$existe['idclifact'];
        }
        return (int)$this->insertar($tipoDoc, $numeroDoc, $razonSocial, $direccion, $email, $telefono);
    }

    public function listar()
    {
        $sql = "SELECT * FROM cliente_facturacion ORDER BY idclifact DESC LIMIT 200";
        return ejecutarConsulta($sql);
    }

    public function mostrar($idclifact)
    {
        $sql = "SELECT * FROM cliente_facturacion WHERE idclifact='$idclifact'";
        return ejecutarConsultaSimpleFila($sql);
    }
}
?>
