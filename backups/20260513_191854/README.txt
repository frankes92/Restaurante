BACKUP YAPEZ POS - 20260513_191854
=======================================
Generado automaticamente antes de implementar el modulo de Zonas.

CONTENIDO:
- yapez_db_20260513_191854.sql    : Dump completo de la BD (mysqldump con --routines, --triggers, --single-transaction)
- yapez_files_20260513_191854.zip : Archivos del proyecto (excluye backups/, node_modules/, .git/, vendor/, tmp/)

COMO RESTAURAR LA BD:
  Opcion 1 (consola):
    cd C:\xampp\mysql\bin
    .\mysql.exe -u root yapez_db < "C:\xampp\htdocs\YAPEZ\backups\20260513_191854\yapez_db_20260513_191854.sql"

  Opcion 2 (phpMyAdmin):
    1. Abre http://localhost/phpmyadmin
    2. Selecciona la base de datos 'yapez_db'
    3. Importar > elige el archivo .sql > Continuar

COMO RESTAURAR LOS ARCHIVOS:
    1. Detén Apache desde XAMPP Control Panel
    2. Renombra C:\xampp\htdocs\YAPEZ a YAPEZ_old
    3. Extrae yapez_files_20260513_191854.zip dentro de C:\xampp\htdocs\YAPEZ
    4. Reinicia Apache

NOTA: este backup NO incluye la carpeta backups/ (autoexclusion) ni node_modules/vendor (regenerables).
