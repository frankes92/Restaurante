<?php
require_once __DIR__ . "/../config/Conexion.php";

class Cargacertificado
{
    public function activo($idempresa = 1)
    {
        $row = dbFila("SELECT * FROM certificado WHERE idempresa = ? AND activo = 1
                       ORDER BY idcertificado DESC LIMIT 1", 'i', [(int)$idempresa]);
        if ($row && isset($row['clave'])) $row['clave'] = appDecrypt($row['clave']);
        return $row;
    }

    /**
     * Indica si el certificado activo de la empresa es de tipo 'demo'.
     * Mientras sea demo, los comprobantes se firman/envian a SUNAT BETA
     * con los datos de prueba (ver aplicarDatosDemo).
     */
    public function esDemo($idempresa = 1)
    {
        $cert = $this->activo($idempresa);
        return $cert && (($cert['tipo'] ?? '') === 'demo');
    }

    /**
     * Sobrescribe en el arreglo de datos del comprobante/nota/resumen los
     * campos de EMISOR/credenciales por los de DEMO SUNAT BETA. Solo afecta
     * lo que se firma y envia a SUNAT; el ticket/PDF se genera aparte con
     * los datos reales de la empresa.
     *
     * Espera las claves: empresa_ruc, empresa_razon, usuario_sol, clave_sol, ambiente.
     */
    public static function aplicarDatosDemo(array $datos)
    {
        $datos['empresa_ruc'] = SUNAT_DEMO_RUC;
        $datos['usuario_sol'] = SUNAT_DEMO_USER;
        $datos['clave_sol']   = SUNAT_DEMO_PASS;
        $datos['ambiente']    = 'beta';
        if (defined('SUNAT_DEMO_RAZON') && SUNAT_DEMO_RAZON !== '') {
            $datos['empresa_razon'] = SUNAT_DEMO_RAZON;
        }
        return $datos;
    }

    public function listar($idempresa = 1)
    {
        return dbQuery("SELECT * FROM certificado WHERE idempresa = ? ORDER BY idcertificado DESC",
            'i', [(int)$idempresa]);
    }

    public function insertar($idempresa, $nombreArchivo, $ruta, $clave, $tipo = 'demo')
    {
        dbQuery("UPDATE certificado SET activo=0 WHERE idempresa = ?", 'i', [(int)$idempresa]);
        $claveCifrada = appEncrypt($clave);
        return dbInsert(
            "INSERT INTO certificado (idempresa, nombre_archivo, ruta, clave, tipo, activo)
             VALUES (?, ?, ?, ?, ?, 1)",
            'issss',
            [(int)$idempresa, $nombreArchivo, $ruta, $claveCifrada, $tipo]
        );
    }

    public function activar($idcertificado, $idempresa)
    {
        dbQuery("UPDATE certificado SET activo=0 WHERE idempresa = ?", 'i', [(int)$idempresa]);
        return dbQuery("UPDATE certificado SET activo=1 WHERE idcertificado = ?",
            'i', [(int)$idcertificado]);
    }

    public function eliminar($idcertificado)
    {
        return dbQuery("DELETE FROM certificado WHERE idcertificado = ?", 'i', [(int)$idcertificado]);
    }
}
