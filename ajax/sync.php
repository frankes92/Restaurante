<?php
// =====================================================================
// SYNC — "firma" barata del estado en vivo (para tiempo real por polling)
// Devuelve una cadena que cambia cuando: hay nuevas ordenes, se agregan/
// quitan items, cambian cantidades/totales, o cambia el estado de mesas.
// El cliente la consulta cada pocos segundos; si cambió, recarga su vista.
// =====================================================================
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/Conexion.php";
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$row = dbFila(
    "SELECT CONCAT_WS('-',
        COALESCE((SELECT COUNT(*)   FROM orden), 0),
        COALESCE((SELECT MAX(idorden) FROM orden), 0),
        COALESCE((SELECT COUNT(*)   FROM orden_detalle), 0),
        COALESCE((SELECT MAX(iddetalle) FROM orden_detalle), 0),
        COALESCE((SELECT ROUND(SUM(cantidad),2) FROM orden_detalle), 0),
        COALESCE((SELECT COUNT(*)   FROM orden WHERE estado IN ('en_curso','enviada')), 0),
        COALESCE((SELECT ROUND(SUM(total),2) FROM orden WHERE estado IN ('en_curso','enviada')), 0),
        COALESCE((SELECT CONCAT(SUM(estado='ocupada'),'.',SUM(estado='en_cuenta'),'.',SUM(estado='reservada')) FROM mesa), '0.0.0')
    ) AS sig"
);

echo json_encode(['sig' => $row['sig'] ?? '']);
