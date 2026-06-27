<?php
// ============================================================
// CONFIGURACION DEL BRIDGE — copiar este archivo a `config.php`
// y editar los valores
// ============================================================

// URL de tu servidor PUERTO HABANA POS (sin slash final)
$CLOUD_URL = 'https://puertohabana.ripasoft.com';

// Token de autenticacion — debe coincidir con BRIDGE_TOKEN en config/global.php del servidor
$TOKEN = 'puertohabana-bridge-2026-cambiar-en-produccion';

// Intervalo de polling en segundos (recomendado: 2-5)
$POLL_SEC = 3;

// Archivo de log (opcional)
$LOG_FILE = __DIR__ . '/bridge.log';

// --- Impresión de comprobantes (boleta/factura/nota) con diseño EXACTO ---
// El bridge "fotografía" el comprobante con Chrome y lo manda como imagen a la
// térmica. Si dejas $CHROME_PATH vacío, intenta detectar Chrome/Edge solo.
$CHROME_PATH = '';                 // ej: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
$PRINT_WIDTH = 576;                // ancho en puntos: 80mm ≈ 576, 58mm ≈ 384
