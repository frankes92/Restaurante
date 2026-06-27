<?php
require_once __DIR__ . "/../config/Conexion.php";

class Mesa
{
    public function __construct() {}

    public function insertar($numero, $capacidad, $estado, $idzona = null)
    {
        $estado = $estado ?: 'libre';
        $idz = ($idzona === '' || $idzona === null || (int)$idzona <= 0) ? 'NULL' : (int)$idzona;
        $num = (int)$numero;
        $cap = (int)$capacidad;

        // Si existe una mesa con ese numero pero inactiva, reactivarla (reciclar el numero)
        $existe = ejecutarConsultaSimpleFila("SELECT idmesa, activo FROM mesa WHERE numero = '$num'");
        if ($existe) {
            if ((int)$existe['activo'] === 1) {
                return 0; // numero ya en uso por mesa activa
            }
            $idm = (int)$existe['idmesa'];
            ejecutarConsulta("UPDATE mesa SET idzona=$idz, capacidad=$cap, estado='$estado', activo=1 WHERE idmesa=$idm");
            return $idm;
        }

        $sql = "INSERT INTO mesa (idzona, numero, capacidad, estado, activo)
                VALUES ($idz, '$num','$cap','$estado','1')";
        return ejecutarConsulta_retornarID($sql);
    }

    public function editar($idmesa, $numero, $capacidad, $estado, $idzona = null)
    {
        $estado = $estado ?: 'libre';
        $idz = ($idzona === '' || $idzona === null || (int)$idzona <= 0) ? 'NULL' : (int)$idzona;
        $sql = "UPDATE mesa SET
                    numero='$numero',
                    capacidad='$capacidad',
                    estado='$estado',
                    idzona=$idz
                WHERE idmesa='$idmesa'";
        return ejecutarConsulta($sql);
    }

    public function cambiarEstado($idmesa, $estado)
    {
        $sql = "UPDATE mesa SET estado='$estado' WHERE idmesa='$idmesa'";
        return ejecutarConsulta($sql);
    }

    public function desactivar($idmesa)
    {
        $sql = "UPDATE mesa SET activo='0' WHERE idmesa='$idmesa'";
        return ejecutarConsulta($sql);
    }

    public function activar($idmesa)
    {
        $sql = "UPDATE mesa SET activo='1' WHERE idmesa='$idmesa'";
        return ejecutarConsulta($sql);
    }

    public function mostrar($idmesa)
    {
        $sql = "SELECT * FROM mesa WHERE idmesa='$idmesa'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function mostrarPorNumero($numero)
    {
        $sql = "SELECT * FROM mesa WHERE numero='$numero'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listar()
    {
        // m.orden tiene default 0; si todas valen 0 el resultado es identico a
        // ordenar solo por numero. Al reordenar se asigna 1,2,3... a las mesas.
        $sql = "SELECT m.*, z.nombre AS zona_nombre, z.color AS zona_color
                FROM mesa m
                LEFT JOIN zona z ON z.idzona = m.idzona AND z.activo = 1
                WHERE m.activo=1
                ORDER BY z.orden ASC, m.orden ASC, m.numero ASC";
        return ejecutarConsulta($sql);
    }

    public function listarTodas()
    {
        $sql = "SELECT m.*, z.nombre AS zona_nombre, z.color AS zona_color
                FROM mesa m
                LEFT JOIN zona z ON z.idzona = m.idzona
                ORDER BY z.orden ASC, m.orden ASC, m.numero ASC";
        return ejecutarConsulta($sql);
    }

    /**
     * Guarda el orden manual de visualizacion de las mesas.
     * Recibe un arreglo de idmesa en el orden deseado y les asigna
     * posiciones 1,2,3... Las mesas no incluidas conservan su valor.
     * Solo toca la columna 'orden'; no afecta numero/estado/zona.
     */
    public function reordenar(array $idsEnOrden)
    {
        global $conexion;
        $pos = 1;
        foreach ($idsEnOrden as $idmesa) {
            $idmesa = (int)$idmesa;
            if ($idmesa <= 0) continue;
            $p = $pos++;
            ejecutarConsulta("UPDATE mesa SET orden = $p WHERE idmesa = $idmesa");
        }
        return true;
    }

    /**
     * Restablece el orden manual (todas vuelven a 0 = ordenar por numero).
     */
    public function resetOrden()
    {
        return ejecutarConsulta("UPDATE mesa SET orden = 0 WHERE activo = 1");
    }

    /**
     * Lee la preferencia de columnas de la vista de mesas (config global).
     * Devuelve 'auto' o un numero como cadena ('2'..'8').
     */
    public function getColumnas()
    {
        $row = dbFila("SELECT mesas_columnas FROM empresa WHERE idempresa = 1");
        return $row['mesas_columnas'] ?? 'auto';
    }

    /**
     * Guarda la preferencia de columnas (config global, en tabla empresa).
     * Solo acepta 'auto' o un entero 1..12; cualquier otro valor => 'auto'.
     */
    public function setColumnas($valor)
    {
        $valor = (string)$valor;
        if ($valor !== 'auto') {
            $n = (int)$valor;
            $valor = ($n >= 1 && $n <= 12) ? (string)$n : 'auto';
        }
        return dbQuery("UPDATE empresa SET mesas_columnas = ? WHERE idempresa = 1", 's', [$valor]);
    }

    public function contarPorEstado()
    {
        $sql = "SELECT estado, COUNT(*) AS total
                FROM mesa
                WHERE activo=1
                GROUP BY estado";
        return ejecutarConsulta($sql);
    }
}
?>
