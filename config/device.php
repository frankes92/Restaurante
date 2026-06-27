<?php
/**
 * Deteccion de dispositivo para servir vista movil o desktop.
 *
 * Prioridad:
 *   1. Cookie 'yapez_view' (override manual: "desktop" o "movil")
 *   2. Header User-Agent (regex contra patrones de moviles y tablets)
 *
 * Tras include, las variables globales son:
 *   $__usarMovil  : bool  - true si debe servirse la vista movil
 *   $__viewSource : string - 'cookie' o 'ua' (de donde vino la decision)
 */

if (!isset($__usarMovil)) {

    $forzado = $_COOKIE['yapez_view'] ?? '';
    if ($forzado === 'desktop') {
        $__usarMovil  = false;
        $__viewSource = 'cookie';
    } elseif ($forzado === 'movil') {
        $__usarMovil  = true;
        $__viewSource = 'cookie';
    } else {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        // Patrones comunes de moviles y tablets (incluye iPad moderno que reporta como Mac)
        $esMobil = (bool)preg_match(
            '/Mobile|Android|iPhone|iPad|iPod|Windows Phone|BlackBerry|Opera Mini|IEMobile|Silk|Kindle|webOS/i',
            $ua
        );
        // iPad en iPadOS 13+ reporta UA de Mac. Detectamos por capacidad touch via JS-cookie,
        // o si no, sigue siendo desktop (el usuario puede forzar movil con el boton).
        $__usarMovil  = $esMobil;
        $__viewSource = 'ua';
    }
}
