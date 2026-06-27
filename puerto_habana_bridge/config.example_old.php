<?php
// ============================================================
// CONFIGURACION DEL BRIDGE — copiar este archivo a `config.php`
// y editar los valores
// ============================================================

// URL de tu servidor PUERTO HABANA POS (sin slash final)
$CLOUD_URL = 'https://puertohabana.ripasoft.com';

// Token de autenticacion — debe coincidir con BRIDGE_TOKEN en config/global.php del servidor
$TOKEN = 'K7p9mNx2zQ4bV8wR3fHcL5jY1tD6aE0s';

// Intervalo de polling en segundos (recomendado: 2-5)
$POLL_SEC = 3;

// Archivo de log (opcional)
$LOG_FILE = __DIR__ . '/bridge.log';
