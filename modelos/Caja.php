<?php
require_once __DIR__ . "/../config/Conexion.php";

class Caja
{
    public function __construct() {}

    // ----- SESION -----

    public function sesionActual()
    {
        return dbFila("SELECT * FROM caja_sesion WHERE abierta=1 ORDER BY idsesion DESC LIMIT 1");
    }

    public function abrirSesion($cajaCodigo, $turno, $cajero, $montoInicial, $idusuario = null)
    {
        $monto = (float)$montoInicial;
        $idu   = $idusuario === null || $idusuario === '' ? null : (int)$idusuario;

        $idsesion = dbInsert(
            "INSERT INTO caja_sesion (caja_codigo, turno, cajero, idusuario, monto_inicial, fecha_apertura, abierta)
             VALUES (?, ?, ?, ?, ?, NOW(), 1)",
            'sssid',
            [$cajaCodigo, $turno, $cajero, $idu, $monto]
        );

        dbQuery(
            "INSERT INTO caja_movimiento (idsesion, tipo, monto, nota)
             VALUES (?, 'apertura', ?, 'Apertura de caja')",
            'id', [$idsesion, $monto]
        );

        return $idsesion;
    }

    public function cerrarSesion($idsesion, $montoCierre)
    {
        $ids = (int)$idsesion;
        $mc  = (float)$montoCierre;

        dbQuery(
            "INSERT INTO caja_movimiento (idsesion, tipo, monto, nota)
             VALUES (?, 'cierre', ?, 'Cierre de caja')",
            'id', [$ids, $mc]
        );

        return dbQuery(
            "UPDATE caja_sesion SET monto_cierre = ?, fecha_cierre = NOW(), abierta = 0
             WHERE idsesion = ?",
            'di', [$mc, $ids]
        );
    }

    // ----- MOVIMIENTOS -----

    public function agregarMovimiento($idsesion, $tipo, $monto, $nota, $metodoPago = '', $idorden = null)
    {
        $idordenVal = ($idorden === '' || $idorden === null) ? null : (int)$idorden;
        return dbInsert(
            "INSERT INTO caja_movimiento (idsesion, tipo, monto, nota, metodo_pago, idorden)
             VALUES (?, ?, ?, ?, ?, ?)",
            'isdssi',
            [(int)$idsesion, $tipo, (float)$monto, $nota, $metodoPago, $idordenVal]
        );
    }

    public function listarMovimientos($idsesion)
    {
        return dbQuery(
            "SELECT * FROM caja_movimiento WHERE idsesion = ? ORDER BY idmovimiento DESC",
            'i', [(int)$idsesion]
        );
    }

    public function resumenSesion($idsesion)
    {
        return dbFila(
            "SELECT
                s.idsesion, s.caja_codigo, s.turno, s.cajero,
                s.monto_inicial, s.fecha_apertura, s.fecha_cierre, s.abierta,
                COALESCE(SUM(CASE WHEN m.tipo='ingreso' THEN m.monto ELSE 0 END),0) AS total_ingresos,
                COALESCE(SUM(CASE WHEN m.tipo='egreso'  THEN m.monto ELSE 0 END),0) AS total_egresos,
                COALESCE(SUM(CASE WHEN m.tipo='venta'   THEN m.monto ELSE 0 END),0) AS total_ventas
             FROM caja_sesion s
             LEFT JOIN caja_movimiento m ON m.idsesion = s.idsesion
             WHERE s.idsesion = ?
             GROUP BY s.idsesion",
            'i', [(int)$idsesion]
        );
    }

    // ---------- DataTables server-side de movimientos ----------

    public function movimientosServerSide($idsesion, $start, $length, $search, $orderCol, $orderDir, $tipoFiltro = '')
    {
        $cols = ['idmovimiento','fecha','tipo','metodo_pago','monto','nota'];
        $orderCol = isset($cols[(int)$orderCol]) ? $cols[(int)$orderCol] : 'idmovimiento';
        $orderDir = strtolower($orderDir) === 'asc' ? 'ASC' : 'DESC';
        $start    = max(0, (int)$start);
        $length   = max(1, min(500, (int)$length));
        $ids      = (int)$idsesion;

        if ($tipoFiltro === '' && $search === '') {
            return dbQuery(
                "SELECT * FROM caja_movimiento WHERE idsesion = ?
                 ORDER BY $orderCol $orderDir LIMIT ?, ?",
                'iii', [$ids, $start, $length]
            );
        }
        if ($tipoFiltro !== '' && $search === '') {
            return dbQuery(
                "SELECT * FROM caja_movimiento WHERE idsesion = ? AND tipo = ?
                 ORDER BY $orderCol $orderDir LIMIT ?, ?",
                'isii', [$ids, $tipoFiltro, $start, $length]
            );
        }
        $like = '%' . $search . '%';
        if ($tipoFiltro === '' && $search !== '') {
            return dbQuery(
                "SELECT * FROM caja_movimiento
                 WHERE idsesion = ? AND (nota LIKE ? OR tipo LIKE ? OR metodo_pago LIKE ?)
                 ORDER BY $orderCol $orderDir LIMIT ?, ?",
                'isssii', [$ids, $like, $like, $like, $start, $length]
            );
        }
        return dbQuery(
            "SELECT * FROM caja_movimiento
             WHERE idsesion = ? AND tipo = ? AND (nota LIKE ? OR tipo LIKE ? OR metodo_pago LIKE ?)
             ORDER BY $orderCol $orderDir LIMIT ?, ?",
            'issssii', [$ids, $tipoFiltro, $like, $like, $like, $start, $length]
        );
    }

    public function contarMovimientos($idsesion, $search = '', $tipoFiltro = '')
    {
        $ids = (int)$idsesion;
        if ($tipoFiltro === '' && $search === '') {
            $row = dbFila("SELECT COUNT(*) AS total FROM caja_movimiento WHERE idsesion = ?",
                'i', [$ids]);
        } elseif ($tipoFiltro !== '' && $search === '') {
            $row = dbFila("SELECT COUNT(*) AS total FROM caja_movimiento WHERE idsesion = ? AND tipo = ?",
                'is', [$ids, $tipoFiltro]);
        } else {
            $like = '%' . $search . '%';
            if ($tipoFiltro === '') {
                $row = dbFila(
                    "SELECT COUNT(*) AS total FROM caja_movimiento
                     WHERE idsesion = ? AND (nota LIKE ? OR tipo LIKE ? OR metodo_pago LIKE ?)",
                    'isss', [$ids, $like, $like, $like]
                );
            } else {
                $row = dbFila(
                    "SELECT COUNT(*) AS total FROM caja_movimiento
                     WHERE idsesion = ? AND tipo = ? AND (nota LIKE ? OR tipo LIKE ? OR metodo_pago LIKE ?)",
                    'issss', [$ids, $tipoFiltro, $like, $like, $like]
                );
            }
        }
        return (int)($row['total'] ?? 0);
    }

    public function ventasPorMetodo($idsesion)
    {
        return dbQuery(
            "SELECT metodo_pago, COALESCE(SUM(monto),0) AS total
             FROM caja_movimiento
             WHERE idsesion = ? AND tipo='venta'
             GROUP BY metodo_pago",
            'i', [(int)$idsesion]
        );
    }

    // ---------------------------------------------------------------
    // ARQUEO DE CAJA: calcula esperado vs contado, guarda diferencia
    // ---------------------------------------------------------------

    /**
     * Calcula el monto esperado en caja para la sesion:
     *   monto_inicial + ventas_efectivo + ingresos - egresos
     */
    public function arqueoEsperado($idsesion)
    {
        $ids = (int)$idsesion;
        $row = dbFila(
            "SELECT s.monto_inicial,
                    COALESCE((SELECT SUM(monto) FROM caja_movimiento WHERE idsesion=s.idsesion AND tipo='venta' AND metodo_pago='efectivo'),0) AS ventas_efectivo,
                    COALESCE((SELECT SUM(monto) FROM caja_movimiento WHERE idsesion=s.idsesion AND tipo='venta'),0) AS ventas_total,
                    COALESCE((SELECT SUM(monto) FROM caja_movimiento WHERE idsesion=s.idsesion AND tipo='ingreso'),0) AS ingresos,
                    COALESCE((SELECT SUM(monto) FROM caja_movimiento WHERE idsesion=s.idsesion AND tipo='egreso'),0)  AS egresos
             FROM caja_sesion s
             WHERE s.idsesion = ?",
            'i', [$ids]
        );
        if (!$row) return ['ok' => false, 'msg' => 'Sesión no encontrada'];

        $inicial  = (float)$row['monto_inicial'];
        $vEfec    = (float)$row['ventas_efectivo'];
        $vTotal   = (float)$row['ventas_total'];
        $ingr     = (float)$row['ingresos'];
        $egr      = (float)$row['egresos'];
        $esperado = round($inicial + $vEfec + $ingr - $egr, 2);

        // Desglose por metodo de pago (cantidad de movimientos + total).
        // Yape y Plin van separados. 'mixto' ya no se usa como metodo propio
        // (sus partes se registran como efectivo/yape/plin por separado).
        $metodos = ['efectivo', 'tarjeta', 'yape', 'plin', 'transferencia'];
        $desglose = [];
        foreach ($metodos as $m) {
            $r = dbFila(
                "SELECT COUNT(*) AS cantidad, COALESCE(SUM(monto),0) AS total
                 FROM caja_movimiento
                 WHERE idsesion = ? AND tipo = 'venta' AND metodo_pago = ?",
                'is', [$ids, $m]
            );
            $desglose[$m] = [
                'cantidad' => (int)($r['cantidad'] ?? 0),
                'total'    => (float)($r['total'] ?? 0),
            ];
        }

        return [
            'ok'              => true,
            'monto_inicial'   => $inicial,
            'ventas_efectivo' => $vEfec,
            'ventas_total'    => $vTotal,
            'ingresos'        => $ingr,
            'egresos'         => $egr,
            'esperado'        => $esperado,
            'desglose'        => $desglose,
        ];
    }

    /**
     * Consolidado del día (por sesión): cuántos PLATOS de comida y cuántas
     * BEBIDAS se vendieron en general, más el desglose por bebida.
     * IMPORTANTE: incluye las cortesías (cuentan para stock/consumo) aunque
     * no afecten la caja. Se basa en ordenes pagadas de la sesión.
     * Bebidas = productos cuya categoría contiene "BEBIDA".
     */
    public function consolidadoDia($idsesion)
    {
        $ids = (int)$idsesion;

        // Totales generales: comidas vs bebidas.
        //  - CANT (unidades): incluye cortesías (como hasta ahora).
        //  - MONTO (dinero): solo lo VENDIDO (cortesia=0; las cortesías valen 0 en caja).
        //  - CORTESÍAS: cantidad y su VALOR (cantidad*precio, lo que se regaló).
        // Los TAPERS (categoría/nombre con "TAPER") NO cuentan como comida ni bebida.
        $gen = dbFila(
            "SELECT
                COALESCE(SUM(CASE WHEN (UPPER(c.nombre) LIKE '%TAPER%' OR UPPER(p.nombre) LIKE '%TAPER%') THEN 0
                                      WHEN UPPER(c.nombre) LIKE '%BEBIDA%' THEN d.cantidad ELSE 0 END),0) AS bebidas,
                COALESCE(SUM(CASE WHEN (UPPER(c.nombre) LIKE '%TAPER%' OR UPPER(p.nombre) LIKE '%TAPER%') THEN 0
                                      WHEN UPPER(c.nombre) LIKE '%BEBIDA%' THEN 0 ELSE d.cantidad END),0) AS comidas,
                COALESCE(SUM(CASE WHEN (UPPER(c.nombre) LIKE '%TAPER%' OR UPPER(p.nombre) LIKE '%TAPER%') THEN 0
                                      WHEN UPPER(c.nombre) LIKE '%BEBIDA%' AND d.cortesia=0 THEN d.subtotal ELSE 0 END),0) AS bebidas_monto,
                COALESCE(SUM(CASE WHEN (UPPER(c.nombre) LIKE '%TAPER%' OR UPPER(p.nombre) LIKE '%TAPER%') THEN 0
                                      WHEN UPPER(c.nombre) LIKE '%BEBIDA%' THEN 0
                                      WHEN d.cortesia=0 THEN d.subtotal ELSE 0 END),0) AS comidas_monto,
                COALESCE(SUM(CASE WHEN (UPPER(c.nombre) LIKE '%TAPER%' OR UPPER(p.nombre) LIKE '%TAPER%') THEN 0
                                      WHEN UPPER(c.nombre) LIKE '%BEBIDA%' AND d.cortesia=1 THEN d.cantidad ELSE 0 END),0) AS bebidas_cort_cant,
                COALESCE(SUM(CASE WHEN (UPPER(c.nombre) LIKE '%TAPER%' OR UPPER(p.nombre) LIKE '%TAPER%') THEN 0
                                      WHEN UPPER(c.nombre) LIKE '%BEBIDA%' THEN 0
                                      WHEN d.cortesia=1 THEN d.cantidad ELSE 0 END),0) AS comidas_cort_cant,
                COALESCE(SUM(CASE WHEN (UPPER(c.nombre) LIKE '%TAPER%' OR UPPER(p.nombre) LIKE '%TAPER%') THEN 0
                                      WHEN UPPER(c.nombre) LIKE '%BEBIDA%' AND d.cortesia=1 THEN d.cantidad*d.precio ELSE 0 END),0) AS bebidas_cort_monto,
                COALESCE(SUM(CASE WHEN (UPPER(c.nombre) LIKE '%TAPER%' OR UPPER(p.nombre) LIKE '%TAPER%') THEN 0
                                      WHEN UPPER(c.nombre) LIKE '%BEBIDA%' THEN 0
                                      WHEN d.cortesia=1 THEN d.cantidad*d.precio ELSE 0 END),0) AS comidas_cort_monto
             FROM orden_detalle d
             JOIN orden o     ON o.idorden = d.idorden
             JOIN producto p  ON p.idproducto = d.idproducto
             LEFT JOIN categoria c ON c.idcategoria = p.idcategoria
             WHERE o.idsesion = ? AND o.estado = 'pagada' AND d.estado <> 'anulado'",
            'i', [$ids]
        );

        // Desglose por bebida + sub-desglose por PRESENTACIÓN (variante).
        // Se agrupa por producto y presentación; luego se anida en PHP.
        $rs = dbQuery(
            "SELECT p.idproducto, p.nombre AS producto,
                    COALESCE(NULLIF(TRIM(pp.nombre),''),'—') AS presentacion,
                    COALESCE(SUM(d.cantidad),0) AS cantidad
             FROM orden_detalle d
             JOIN orden o     ON o.idorden = d.idorden
             JOIN producto p  ON p.idproducto = d.idproducto
             LEFT JOIN producto_precio pp ON pp.idprecio = d.idprecio
             LEFT JOIN categoria c ON c.idcategoria = p.idcategoria
             WHERE o.idsesion = ? AND o.estado = 'pagada' AND d.estado <> 'anulado'
                   AND UPPER(c.nombre) LIKE '%BEBIDA%'
             GROUP BY p.idproducto, p.nombre, COALESCE(NULLIF(TRIM(pp.nombre),''),'—')
             HAVING cantidad > 0
             ORDER BY p.nombre ASC, cantidad DESC",
            'i', [$ids]
        );
        // Anidar: cada producto con su total y la lista de sus presentaciones.
        $mapBeb = [];
        if ($rs) {
            while ($r = $rs->fetch_assoc()) {
                $pid = (int)$r['idproducto'];
                if (!isset($mapBeb[$pid])) {
                    $mapBeb[$pid] = ['nombre' => $r['producto'], 'cantidad' => 0.0, 'presentaciones' => []];
                }
                $mapBeb[$pid]['cantidad'] += (float)$r['cantidad'];
                $mapBeb[$pid]['presentaciones'][] = [
                    'nombre'   => $r['presentacion'],
                    'cantidad' => (float)$r['cantidad'],
                ];
            }
        }
        $bebidasDetalle = array_values($mapBeb);
        // Más vendidas primero (mismo orden que antes)
        usort($bebidasDetalle, function ($a, $b) { return $b['cantidad'] <=> $a['cantidad']; });

        return [
            'comidas'            => (float)($gen['comidas'] ?? 0),
            'bebidas'            => (float)($gen['bebidas'] ?? 0),
            'comidas_monto'      => (float)($gen['comidas_monto'] ?? 0),
            'bebidas_monto'      => (float)($gen['bebidas_monto'] ?? 0),
            'comidas_cort_cant'  => (float)($gen['comidas_cort_cant'] ?? 0),
            'bebidas_cort_cant'  => (float)($gen['bebidas_cort_cant'] ?? 0),
            'comidas_cort_monto' => (float)($gen['comidas_cort_monto'] ?? 0),
            'bebidas_cort_monto' => (float)($gen['bebidas_cort_monto'] ?? 0),
            'bebidas_detalle'    => $bebidasDetalle,
        ];
    }

    public function guardarArqueo($idsesion, $idusuario, $montoContado, $denominacionesJson, $observacion)
    {
        $info = $this->arqueoEsperado($idsesion);
        if (!$info['ok']) return $info;

        $sistema   = (float)$info['esperado'];
        $contado   = (float)$montoContado;
        $diff      = round($contado - $sistema, 2);
        $idu       = $idusuario === null || $idusuario === '' ? null : (int)$idusuario;

        $id = dbInsert(
            "INSERT INTO caja_arqueo (idsesion, idusuario, monto_sistema, monto_contado, diferencia, observacion, denominaciones)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            'iidddss',
            [(int)$idsesion, $idu, $sistema, $contado, $diff, $observacion, $denominacionesJson]
        );
        return [
            'ok'            => $id > 0,
            'idarqueo'      => $id,
            'monto_sistema' => $sistema,
            'monto_contado' => $contado,
            'diferencia'    => $diff,
        ];
    }

    public function listarArqueos($idsesion)
    {
        return dbQuery(
            "SELECT a.*, u.nombre, u.apellidos
             FROM caja_arqueo a
             LEFT JOIN usuario u ON u.idusuario = a.idusuario
             WHERE a.idsesion = ?
             ORDER BY a.idarqueo DESC",
            'i', [(int)$idsesion]
        );
    }

    /**
     * Historial de CIERRES de caja: todas las sesiones ya cerradas, con sus
     * datos de apertura/cierre, cajero, total de ventas y la diferencia del
     * último arqueo. Permite reimprimir el consolidado/arqueo de cada fecha.
     */
    public function historialCierres()
    {
        return dbQuery(
            "SELECT s.idsesion, s.caja_codigo, s.turno,
                    s.fecha_apertura, s.fecha_cierre,
                    s.monto_inicial, s.monto_cierre,
                    TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellidos,''))) AS cajero_nombre,
                    s.cajero AS cajero_libre,
                    (SELECT a.diferencia FROM caja_arqueo a
                       WHERE a.idsesion = s.idsesion ORDER BY a.idarqueo DESC LIMIT 1) AS diferencia,
                    COALESCE((SELECT SUM(m.monto) FROM caja_movimiento m
                       WHERE m.idsesion = s.idsesion AND m.tipo = 'venta'),0) AS ventas
             FROM caja_sesion s
             LEFT JOIN usuario u ON u.idusuario = s.idusuario
             WHERE s.abierta = 0
             ORDER BY s.fecha_apertura DESC, s.idsesion DESC",
            '', []
        );
    }
}
