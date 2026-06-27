<?php
/**
 * TopeRus — cálculo del acumulado mensual de BOLETAS ELECTRÓNICAS para el
 * control del tope del Nuevo RUS (S/ 5,000 → cuota S/ 20; hasta S/ 8,000 → S/ 50).
 *
 * Solo INFORMA / AVISA. Nunca bloquea la emisión de comprobantes.
 * Ligero a propósito: solo requiere Conexion (una consulta SUM), para poder
 * incluirlo en head.php sin cargar las librerías SUNAT.
 */
require_once __DIR__ . "/../config/Conexion.php";

if (!function_exists('topeRusInfo')) {

    /**
     * Devuelve el estado del tope RUS para el mes en curso.
     *
     * @param int    $idempresa
     * @param string $mesRef  'YYYY-MM' opcional (por defecto el mes actual)
     * @return array
     */
    function topeRusInfo($idempresa = 1, $mesRef = '')
    {
        $limite    = defined('RUS_LIMITE')       ? RUS_LIMITE       : 5000.0;
        $umbral    = defined('RUS_UMBRAL_AVISO') ? RUS_UMBRAL_AVISO : 4500.0;
        $limiteMax = defined('RUS_LIMITE_MAX')   ? RUS_LIMITE_MAX   : 8000.0;
        $cuota1    = defined('RUS_CUOTA_CAT1')   ? RUS_CUOTA_CAT1   : 20.0;
        $cuota2    = defined('RUS_CUOTA_CAT2')   ? RUS_CUOTA_CAT2   : 50.0;

        // Rango del mes (actual o el indicado)
        if (preg_match('/^\d{4}-\d{2}$/', $mesRef)) {
            $ini = $mesRef . '-01';
            $fin = date('Y-m-t', strtotime($ini));
        } else {
            $ini = date('Y-m-01');
            $fin = date('Y-m-t');
        }

        // Sumar SOLO boletas electrónicas (tipo_documento '03') del mes.
        // Se excluyen las que no representan ingreso vigente: anuladas (baja)
        // y rechazadas por SUNAT.
        $monto = 0.0; $cantidad = 0;
        $row = dbFila(
            "SELECT COALESCE(SUM(total),0) AS monto, COUNT(*) AS cantidad
               FROM comprobante_electronico
              WHERE idempresa = ?
                AND tipo_documento = '03'
                AND estado NOT IN ('baja','rechazado')
                AND DATE(fecha_emision) BETWEEN ? AND ?",
            'iss', [(int)$idempresa, $ini, $fin]
        );
        if ($row) {
            $monto    = (float)$row['monto'];
            $cantidad = (int)$row['cantidad'];
        }

        // Estado: ok < umbral ; cerca >= umbral y < limite ; excedido >= limite
        $estado = 'ok';
        if ($monto >= $limite)      $estado = 'excedido';
        elseif ($monto >= $umbral)  $estado = 'cerca';

        return [
            'monto'      => round($monto, 2),
            'cantidad'   => $cantidad,
            'limite'     => $limite,
            'umbral'     => $umbral,
            'limite_max' => $limiteMax,
            'cuota1'     => $cuota1,
            'cuota2'     => $cuota2,
            'restante'   => round(max(0, $limite - $monto), 2),
            'excedente'  => round(max(0, $monto - $limite), 2),
            'porcentaje' => $limite > 0 ? round($monto / $limite * 100, 1) : 0,
            'estado'     => $estado,
            'mes'        => date('m/Y', strtotime($ini)),
        ];
    }
}
