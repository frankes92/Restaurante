<?php
require_once __DIR__ . "/../config/Conexion.php";

class Empresa
{
    public function mostrar($idempresa = 1)
    {
        return dbFila("SELECT * FROM empresa WHERE idempresa = ?", 'i', [(int)$idempresa]);
    }

    public function mostrarPorRuc($ruc)
    {
        return dbFila("SELECT * FROM empresa WHERE numero_ruc = ? LIMIT 1", 's', [$ruc]);
    }

    public function listar()
    {
        return ejecutarConsulta("SELECT * FROM empresa ORDER BY idempresa ASC");
    }

    public function editar($idempresa, $datos)
    {
        // Whitelist estricta de campos editables
        $campos = ['numero_ruc','razon_social','nombre_comercial','domicilio_fiscal','ubigeo',
                   'departamento','provincia','distrito','telefono','correo','web',
                   'usuario_sol','clave_sol','ambiente',
                   'tasa_igv','simbolo_moneda','codigo_moneda',
                   'logo','formato_comprobante','envio_sunat_automatico','mostrar_glosa_interna',
                   'yape_qr','plin_qr'];

        $sets = [];
        $types = '';
        $params = [];
        foreach ($campos as $c) {
            if (array_key_exists($c, $datos)) {
                $sets[] = "$c = ?";
                $params[] = $datos[$c];
                // Tipos numericos
                $types .= ($c === 'tasa_igv') ? 'd' : 's';
            }
        }
        if (empty($sets)) return false;

        // Si la fila no existe (ej. BD recien limpiada), crear una base para
        // que el UPDATE tenga sobre que actuar. Solo campos obligatorios.
        $existe = dbFila("SELECT idempresa FROM empresa WHERE idempresa = ?", 'i', [(int)$idempresa]);
        if (!$existe) {
            dbQuery("INSERT INTO empresa (idempresa, numero_ruc, razon_social, domicilio_fiscal)
                     VALUES (?, '00000000000', 'MI EMPRESA', 'Direccion')", 'i', [(int)$idempresa]);
        }

        $params[] = (int)$idempresa;
        $types   .= 'i';

        $sql = "UPDATE empresa SET " . implode(', ', $sets) . " WHERE idempresa = ?";
        return dbQuery($sql, $types, $params);
    }

    /**
     * Sube un logo y guarda la ruta relativa en la empresa.
     */
    public function subirLogo($idempresa, $archivo)
    {
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'msg' => 'Debes seleccionar un archivo'];
        }
        // Validar tipo por MIME real (no por extension)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'msg' => 'Formato no permitido (PNG, JPG, WEBP o SVG)'];
        }
        // Limite de 2 MB
        if (($archivo['size'] ?? 0) > 2 * 1024 * 1024) {
            return ['ok' => false, 'msg' => 'El logo no debe exceder 2 MB'];
        }
        $ext = $allowed[$mime];

        $dir = __DIR__ . '/../public/img';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        // Nombre seguro (no usa el nombre del cliente)
        $fname = 'logo_' . (int)$idempresa . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest  = $dir . DIRECTORY_SEPARATOR . $fname;

        if (!@move_uploaded_file($archivo['tmp_name'], $dest)) {
            return ['ok' => false, 'msg' => 'No se pudo guardar el archivo'];
        }
        $rutaRel = 'public/img/' . $fname;

        // Borrar el logo anterior si existia
        $row = $this->mostrar($idempresa);
        if (!empty($row['logo'])) {
            $old = __DIR__ . '/../' . $row['logo'];
            if (is_file($old) && strpos(realpath($old) ?: '', realpath($dir) ?: '') === 0) {
                @unlink($old);
            }
        }

        $this->editar($idempresa, ['logo' => $rutaRel]);
        return ['ok' => true, 'logo' => $rutaRel];
    }

    /**
     * Sube el QR de Yape o Plin. $tipo = 'yape' | 'plin'.
     * Guarda la ruta relativa en la columna correspondiente (yape_qr / plin_qr).
     */
    public function subirQr($idempresa, $archivo, $tipo)
    {
        $tipo = ($tipo === 'plin') ? 'plin' : 'yape';
        $campo = $tipo . '_qr';

        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'msg' => 'Debes seleccionar un archivo'];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'msg' => 'Formato no permitido (PNG, JPG o WEBP)'];
        }
        if (($archivo['size'] ?? 0) > 2 * 1024 * 1024) {
            return ['ok' => false, 'msg' => 'La imagen no debe exceder 2 MB'];
        }
        $ext = $allowed[$mime];

        $dir = __DIR__ . '/../public/img';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $fname = 'qr_' . $tipo . '_' . (int)$idempresa . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest  = $dir . DIRECTORY_SEPARATOR . $fname;

        if (!@move_uploaded_file($archivo['tmp_name'], $dest)) {
            return ['ok' => false, 'msg' => 'No se pudo guardar el archivo'];
        }
        $rutaRel = 'public/img/' . $fname;

        // Borrar el QR anterior si existia
        $row = $this->mostrar($idempresa);
        if (!empty($row[$campo])) {
            $old = __DIR__ . '/../' . $row[$campo];
            if (is_file($old) && strpos(realpath($old) ?: '', realpath($dir) ?: '') === 0) {
                @unlink($old);
            }
        }

        $this->editar($idempresa, [$campo => $rutaRel]);
        return ['ok' => true, 'qr' => $rutaRel, 'tipo' => $tipo];
    }

    /**
     * Elimina el QR de Yape o Plin. $tipo = 'yape' | 'plin'.
     */
    public function eliminarQr($idempresa, $tipo)
    {
        $tipo  = ($tipo === 'plin') ? 'plin' : 'yape';
        $campo = $tipo . '_qr';
        $row = $this->mostrar($idempresa);
        if (!empty($row[$campo])) {
            $abs = __DIR__ . '/../' . $row[$campo];
            $dir = realpath(__DIR__ . '/../public/img');
            if (is_file($abs) && $dir && strpos(realpath($abs) ?: '', $dir) === 0) {
                @unlink($abs);
            }
        }
        return $this->editar($idempresa, [$campo => null]);
    }

    public function eliminarLogo($idempresa)
    {
        $row = $this->mostrar($idempresa);
        if (!empty($row['logo'])) {
            $abs = __DIR__ . '/../' . $row['logo'];
            $dir = realpath(__DIR__ . '/../public/img');
            if (is_file($abs) && $dir && strpos(realpath($abs) ?: '', $dir) === 0) {
                @unlink($abs);
            }
        }
        return $this->editar($idempresa, ['logo' => null]);
    }
}
