<?php
require_once __DIR__ . "/../config/Conexion.php";

class Zona
{
    /** Lista zonas activas con conteo de mesas. */
    public function listar()
    {
        $rs = dbQuery(
            "SELECT z.idzona, z.nombre, z.color, z.orden, z.activo,
                    COALESCE((SELECT COUNT(*) FROM mesa m WHERE m.idzona = z.idzona AND m.activo = 1), 0) AS total_mesas
             FROM zona z
             WHERE z.activo = 1
             ORDER BY z.orden ASC, z.nombre ASC"
        );
        if (!$rs || $rs === true) return [];
        $rows = [];
        while ($r = $rs->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    public function mostrar($idzona)
    {
        return dbFila("SELECT * FROM zona WHERE idzona = ?", 'i', [(int)$idzona]);
    }

    /** Crea una zona simple. */
    public function crear($nombre, $color)
    {
        $color = $this->normalizarColor($color);
        $orden = (int)dbFila("SELECT COALESCE(MAX(orden),0) + 1 AS o FROM zona")['o'];

        $id = dbInsert(
            "INSERT INTO zona (nombre, color, orden, activo) VALUES (?, ?, ?, 1)",
            'ssi', [trim($nombre), $color, $orden]
        );
        return (int)$id;
    }

    /**
     * Crea una zona y de paso N mesas asignadas a esa zona.
     * $mesas = [['numero'=>4,'capacidad'=>4], ...]
     */
    public function crearConMesas($nombre, $color, array $mesas)
    {
        $idzona = $this->crear($nombre, $color);
        if ($idzona <= 0) return ['ok' => false, 'msg' => 'No se pudo crear la zona'];

        $creadas = 0;
        $reactivadas = 0;
        $duplicadas = [];
        foreach ($mesas as $m) {
            $num = (int)($m['numero']    ?? 0);
            $cap = (int)($m['capacidad'] ?? 4);
            if ($num <= 0) continue;

            // Si existe una mesa con ese numero, decidir si esta activa o inactiva
            $existe = dbFila("SELECT idmesa, activo FROM mesa WHERE numero = ?", 'i', [$num]);
            if ($existe) {
                if ((int)$existe['activo'] === 1) {
                    // Activa: numero ya en uso, no se puede duplicar
                    $duplicadas[] = $num;
                    continue;
                }
                // Inactiva: reactivar con la nueva zona/capacidad (reciclamos el numero)
                dbQuery(
                    "UPDATE mesa
                     SET idzona = ?, capacidad = ?, estado = 'libre', activo = 1
                     WHERE idmesa = ?",
                    'iii', [$idzona, $cap, (int)$existe['idmesa']]
                );
                $reactivadas++;
                continue;
            }

            dbInsert(
                "INSERT INTO mesa (idzona, numero, capacidad, estado, activo)
                 VALUES (?, ?, ?, 'libre', 1)",
                'iii', [$idzona, $num, $cap]
            );
            $creadas++;
        }
        return [
            'ok' => true,
            'idzona' => $idzona,
            'mesas_creadas' => $creadas + $reactivadas,
            'nuevas' => $creadas,
            'reactivadas' => $reactivadas,
            'duplicadas' => $duplicadas
        ];
    }

    public function editar($idzona, $nombre, $color)
    {
        $color = $this->normalizarColor($color);
        return dbQuery(
            "UPDATE zona SET nombre = ?, color = ? WHERE idzona = ?",
            'ssi', [trim($nombre), $color, (int)$idzona]
        );
    }

    /** Desactiva la zona (mesas quedan con idzona=NULL por la FK ON DELETE SET NULL,
     *  pero como sólo desactivamos, hay que limpiar el vínculo manualmente). */
    public function desactivar($idzona)
    {
        $id = (int)$idzona;
        dbQuery("UPDATE mesa SET idzona = NULL WHERE idzona = ?", 'i', [$id]);
        return dbQuery("UPDATE zona SET activo = 0 WHERE idzona = ?", 'i', [$id]);
    }

    /** Reordena zonas. $orden = [idzona1, idzona2, ...] */
    public function reordenar(array $idsEnOrden)
    {
        $i = 1;
        foreach ($idsEnOrden as $idz) {
            $idz = (int)$idz;
            if ($idz <= 0) continue;
            dbQuery("UPDATE zona SET orden = ? WHERE idzona = ?", 'ii', [$i, $idz]);
            $i++;
        }
        return true;
    }

    /** Asigna varias mesas existentes a la zona indicada (o quita asignación si idzona=null). */
    public function asignarMesas($idzona, array $idmesas)
    {
        $idz = ($idzona === null || $idzona === '' || (int)$idzona <= 0) ? 'NULL' : (int)$idzona;
        $afect = 0;
        foreach ($idmesas as $idm) {
            $idm = (int)$idm;
            if ($idm <= 0) continue;
            dbQuery("UPDATE mesa SET idzona = " . $idz . " WHERE idmesa = ?", 'i', [$idm]);
            $afect++;
        }
        return $afect;
    }

    /** Devuelve mesas asignadas a esta zona. */
    public function mesasDeZona($idzona)
    {
        $rs = dbQuery(
            "SELECT idmesa, numero, capacidad, estado
             FROM mesa
             WHERE idzona = ? AND activo = 1
             ORDER BY numero ASC",
            'i', [(int)$idzona]
        );
        if (!$rs || $rs === true) return [];
        $rows = [];
        while ($r = $rs->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /** Devuelve mesas sin zona asignada. */
    public function mesasSinZona()
    {
        $rs = dbQuery(
            "SELECT idmesa, numero, capacidad, estado
             FROM mesa
             WHERE idzona IS NULL AND activo = 1
             ORDER BY numero ASC"
        );
        if (!$rs || $rs === true) return [];
        $rows = [];
        while ($r = $rs->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    private function normalizarColor($color)
    {
        $c = trim((string)$color);
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $c)) return '#5b3df5';
        return strtolower($c);
    }
}
