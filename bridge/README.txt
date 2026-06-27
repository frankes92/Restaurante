============================================================
PUERTO HABANA POS - BRIDGE DE IMPRESION LOCAL
============================================================

QUE ES
------
El Bridge es un script PHP que corre en una PC del LOCAL del
restaurante. Su trabajo es:

  1. Consultar al servidor de PUERTO HABANA POS cada 3 segundos
     si hay tickets/comandas pendientes de imprimir.
  2. Si los hay, conectarse a la impresora termica via TCP/IP y enviar
     los bytes ESC/POS para que imprima.
  3. Marcar el trabajo como impreso (o error) en el servidor.

Por que es necesario:
El servidor no puede alcanzar IPs locales (192.168.x.x), asi
que necesitamos una PC del local que sirva de puente.

REQUISITOS
----------
- PC con Windows (o Linux, pero estas instrucciones son para Windows)
- PHP 7.4+ instalado (puede ser el de XAMPP, no necesita servidor web)
- La PC debe estar en la misma red WiFi/LAN que las impresoras
- La PC debe quedar PRENDIDA mientras el local opera
- Impresora termica de RED con IP fija y puerto 9100 (ESC/POS)

INSTALACION (5 minutos)
-----------------------

PASO 1: Copiar los archivos
   Copia toda la carpeta `bridge/` a una ubicacion permanente:
   C:\puerto_habana_bridge\

   Deberias tener:
     C:\puerto_habana_bridge\bridge.php
     C:\puerto_habana_bridge\config.example.php
     C:\puerto_habana_bridge\README.txt

PASO 2: Crear el config (2 formas)

   FORMA FACIL (recomendada):
   - Entra al sistema PUERTO HABANA POS como admin
   - Menu lateral: "Empresa" -> seccion "Bridge de impresion"
   - Click "Descargar config.php" (ya viene con la URL y el TOKEN correctos)
   - Copia ese config.php dentro de C:\puerto_habana_bridge\

   FORMA MANUAL:
   - Renombra `config.example.php` -> `config.php`
   - Abrelo con Notepad y edita las 4 variables:
       $CLOUD_URL = 'https://puertohabana.ripasoft.com';   (o http://localhost/puerto_habana si es local)
       $TOKEN     = 'puertohabana-bridge-2026-cambiar-en-produccion';
       $POLL_SEC  = 3;
       $LOG_FILE  = 'C:\puerto_habana_bridge\bridge.log';

   IMPORTANTE: El $TOKEN debe ser EXACTAMENTE el mismo que tienes en
   config/global.php del servidor (constante BRIDGE_TOKEN).

PASO 3: Configurar las impresoras en el sistema
   - Entra al sistema PUERTO HABANA POS web como admin
   - Menu lateral: "Impresoras"
   - Click "Nueva Impresora":
       Nombre:  "Cocina principal"
       IP:      "192.168.1.50"   (la IP de tu termica)
       Puerto:  9100
       Tipo:    Cocina
       Activa:  SI
   - Guardar
   - Click el icono de "Prueba" para encolar una impresion test

PASO 4: Probar el bridge manualmente
   Abre CMD (Command Prompt) y ejecuta:
       C:\xampp\php\php.exe C:\puerto_habana_bridge\bridge.php

   Si todo esta bien, veras algo como:
       [2026-06-09 14:30:00] === Bridge iniciado ===
       [2026-06-09 14:30:00] Cloud:    https://puertohabana.ripasoft.com
       [2026-06-09 14:30:00] Polling:  cada 3s
       [2026-06-09 14:30:03] OK #1 -> Cocina principal (192.168.1.50:9100)

   Y debe imprimir el ticket de prueba en la termica.

PASO 5: Auto-iniciar con Windows (recomendado)
   Para que el bridge arranque cada vez que prendes la PC, hay 2 opciones:

   OPCION A - Tarea programada (mas simple):
     1. Abre "Programador de tareas" (Task Scheduler)
     2. Crear tarea basica
        Nombre: "Puerto Habana Bridge"
        Disparador: "Al iniciar Windows"
        Accion: "Iniciar programa"
        Programa: C:\xampp\php\php.exe
        Argumentos: C:\puerto_habana_bridge\bridge.php
     3. Click "Finalizar"
     4. En la lista, click derecho -> "Ejecutar"

   OPCION B - Servicio Windows (mas robusto):
     1. Descarga NSSM: https://nssm.cc/download
     2. Extrae y abre CMD como administrador
     3. nssm install PuertoHabanaBridge
     4. En el dialogo:
         Path:      C:\xampp\php\php.exe
         Arguments: C:\puerto_habana_bridge\bridge.php
     5. Install service
     6. nssm start PuertoHabanaBridge

VERIFICAR QUE FUNCIONA
----------------------
- Abre el log: C:\puerto_habana_bridge\bridge.log
- Deberias ver lineas cada pocos segundos
- En el sistema PUERTO HABANA POS, ve a Impresoras y haz click en "Prueba"
- En 3-5 segundos debe imprimir en la termica

PROBLEMAS COMUNES
-----------------
"Conexion fallida (192.168.x.x:9100)"
  - La PC del bridge no puede ver la impresora
  - Verifica: ping 192.168.1.50 (debe responder)
  - Verifica que la impresora y la PC esten en la misma red

"Token invalido" / "HTTP 401"
  - El $TOKEN del config.php NO coincide con BRIDGE_TOKEN del servidor
  - Vuelve a descargar el config.php desde Empresa -> Bridge de impresion

"HTTP 0" / "Could not resolve host"
  - La PC del bridge no tiene acceso al servidor
  - Verifica que pueda abrir la direccion del sistema en el navegador

El bridge se cierra solo
  - Es porque ejecutas desde CMD y cierras la ventana
  - Usa el Programador de tareas o NSSM (paso 5)

CONTACTO
--------
Si algo no funciona, revisa C:\puerto_habana_bridge\bridge.log
Los errores quedan registrados ahi.
