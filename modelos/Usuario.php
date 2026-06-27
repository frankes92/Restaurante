<?php
require_once __DIR__ . "/../config/Conexion.php";
require_once __DIR__ . "/Permiso.php";

class Usuario
{
    public function __construct() {}

    /**
     * Verifica login y password en texto plano. Soporta:
     *  - password_hash() moderno (bcrypt/argon2)
     *  - SHA-256 legado (de v2). En ese caso, re-hashea al moderno automaticamente.
     */
    public function verificarLogin($login, $clave)
    {
        $row = dbFila(
            "SELECT u.*, r.codigo AS rol_codigo, r.nombre AS rol_nombre
             FROM usuario u
             LEFT JOIN rol r ON r.idrol = u.idrol
             WHERE u.login = ? AND u.condicion = 1
             LIMIT 1",
            's', [$login]
        );
        if (!$row) return null;

        // Detectar si la migracion v7 ya esta aplicada (columnas anti-bruteforce)
        $hasLockCols = array_key_exists('intentos_fallidos', $row) && array_key_exists('bloqueado_hasta', $row);

        // Bloqueo por intentos fallidos
        if ($hasLockCols && !empty($row['bloqueado_hasta']) && strtotime($row['bloqueado_hasta']) > time()) {
            return ['__bloqueado' => true, 'hasta' => $row['bloqueado_hasta']];
        }

        $hashAlmacenado = $row['clave'];
        $ok = false;

        // Intento 1: password_hash moderno
        if (strlen($hashAlmacenado) > 60 || (strlen($hashAlmacenado) >= 60 && $hashAlmacenado[0] === '$')) {
            $ok = password_verify($clave, $hashAlmacenado);
        }
        // Intento 2: SHA-256 legado (64 hex chars)
        if (!$ok && strlen($hashAlmacenado) === 64 && ctype_xdigit($hashAlmacenado)) {
            if (hash_equals($hashAlmacenado, hash('sha256', $clave))) {
                $ok = true;
                // Migrar a hash moderno (si la columna soporta el largo)
                $nuevo = password_hash($clave, PASSWORD_BCRYPT);
                @dbQuery("UPDATE usuario SET clave = ? WHERE idusuario = ?", 'si', [$nuevo, (int)$row['idusuario']]);
            }
        }

        if (!$ok) {
            if ($hasLockCols) {
                $intentos = (int)$row['intentos_fallidos'] + 1;
                if ($intentos >= 5) {
                    dbQuery("UPDATE usuario SET intentos_fallidos = ?, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                             WHERE idusuario = ?", 'ii', [$intentos, (int)$row['idusuario']]);
                } else {
                    dbQuery("UPDATE usuario SET intentos_fallidos = ? WHERE idusuario = ?",
                        'ii', [$intentos, (int)$row['idusuario']]);
                }
            }
            return null;
        }

        // Login OK: reset contadores
        if ($hasLockCols) {
            dbQuery("UPDATE usuario SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE idusuario = ?",
                'i', [(int)$row['idusuario']]);
        }

        unset($row['clave']);
        return $row;
    }

    public function actualizarUltimoAcceso($idusuario)
    {
        return dbQuery("UPDATE usuario SET ultimo_acceso=NOW() WHERE idusuario = ?", 'i', [(int)$idusuario]);
    }

    public function insertar($idrol, $nombre, $apellidos, $login, $clave, $tipoDoc, $numDoc, $telefono, $email)
    {
        $hash = password_hash($clave, PASSWORD_BCRYPT);
        $idrolVal = ($idrol === '' || $idrol === null) ? null : (int)$idrol;
        return dbInsert(
            "INSERT INTO usuario (idrol, nombre, apellidos, login, clave, tipo_documento, num_documento, telefono, email, condicion)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
            'issssssss',
            [$idrolVal, $nombre, $apellidos, $login, $hash, $tipoDoc, $numDoc, $telefono, $email]
        );
    }

    public function editar($idusuario, $idrol, $nombre, $apellidos, $login, $tipoDoc, $numDoc, $telefono, $email)
    {
        $idrolVal = ($idrol === '' || $idrol === null) ? null : (int)$idrol;
        return dbQuery(
            "UPDATE usuario SET idrol=?, nombre=?, apellidos=?, login=?, tipo_documento=?, num_documento=?, telefono=?, email=?
             WHERE idusuario=?",
            'isssssssi',
            [$idrolVal, $nombre, $apellidos, $login, $tipoDoc, $numDoc, $telefono, $email, (int)$idusuario]
        );
    }

    public function cambiarClave($idusuario, $nuevaClave)
    {
        $hash = password_hash($nuevaClave, PASSWORD_BCRYPT);
        return dbQuery("UPDATE usuario SET clave=? WHERE idusuario=?", 'si', [$hash, (int)$idusuario]);
    }

    public function desactivar($idusuario)
    {
        return dbQuery("UPDATE usuario SET condicion=0 WHERE idusuario=?", 'i', [(int)$idusuario]);
    }

    public function activar($idusuario)
    {
        return dbQuery("UPDATE usuario SET condicion=1, intentos_fallidos=0, bloqueado_hasta=NULL WHERE idusuario=?",
            'i', [(int)$idusuario]);
    }

    public function mostrar($idusuario)
    {
        $row = dbFila(
            "SELECT u.idusuario, u.idrol, u.nombre, u.apellidos, u.login, u.email, u.telefono,
                    u.tipo_documento, u.num_documento, u.condicion, u.ultimo_acceso,
                    r.codigo AS rol_codigo, r.nombre AS rol_nombre
             FROM usuario u
             LEFT JOIN rol r ON r.idrol = u.idrol
             WHERE u.idusuario = ?",
            'i', [(int)$idusuario]
        );
        return $row;
    }

    public function listar()
    {
        $sql = "SELECT u.idusuario, u.nombre, u.apellidos, u.login, u.email, u.telefono,
                       u.condicion, u.ultimo_acceso,
                       r.idrol, r.nombre AS rol_nombre, r.codigo AS rol_codigo
                FROM usuario u
                LEFT JOIN rol r ON r.idrol = u.idrol
                ORDER BY u.idusuario ASC";
        return ejecutarConsulta($sql);
    }

    // ---------- DataTables server-side ----------

    public function listarServerSide($start, $length, $search, $orderCol, $orderDir)
    {
        $cols = ['u.idusuario','u.nombre','u.login','r.nombre','u.email','u.ultimo_acceso','u.condicion'];
        $orderCol = isset($cols[(int)$orderCol]) ? $cols[(int)$orderCol] : 'u.idusuario';
        $orderDir = strtolower($orderDir) === 'asc' ? 'ASC' : 'DESC';
        $start    = max(0, (int)$start);
        $length   = max(1, min(500, (int)$length));

        if ($search === '') {
            $sql = "SELECT u.idusuario, u.nombre, u.apellidos, u.login, u.email, u.telefono,
                           u.condicion, u.ultimo_acceso,
                           r.idrol, r.nombre AS rol_nombre, r.codigo AS rol_codigo
                    FROM usuario u
                    LEFT JOIN rol r ON r.idrol = u.idrol
                    ORDER BY $orderCol $orderDir
                    LIMIT ?, ?";
            return dbQuery($sql, 'ii', [$start, $length]);
        }
        $like = '%' . $search . '%';
        $sql = "SELECT u.idusuario, u.nombre, u.apellidos, u.login, u.email, u.telefono,
                       u.condicion, u.ultimo_acceso,
                       r.idrol, r.nombre AS rol_nombre, r.codigo AS rol_codigo
                FROM usuario u
                LEFT JOIN rol r ON r.idrol = u.idrol
                WHERE (u.nombre LIKE ? OR u.apellidos LIKE ? OR u.login LIKE ? OR u.email LIKE ? OR r.nombre LIKE ?)
                ORDER BY $orderCol $orderDir
                LIMIT ?, ?";
        return dbQuery($sql, 'sssssii', [$like, $like, $like, $like, $like, $start, $length]);
    }

    public function contarServerSide($search, $useFilter = true)
    {
        if (!$useFilter || $search === '') {
            $row = dbFila("SELECT COUNT(*) AS total FROM usuario u LEFT JOIN rol r ON r.idrol=u.idrol");
        } else {
            $like = '%' . $search . '%';
            $row = dbFila(
                "SELECT COUNT(*) AS total FROM usuario u LEFT JOIN rol r ON r.idrol=u.idrol
                 WHERE (u.nombre LIKE ? OR u.apellidos LIKE ? OR u.login LIKE ? OR u.email LIKE ? OR r.nombre LIKE ?)",
                'sssss', [$like, $like, $like, $like, $like]
            );
        }
        return (int)($row['total'] ?? 0);
    }

    // Permisos efectivos del usuario (rol + grants - revokes)
    public function permisosEfectivos($idusuario)
    {
        $perm = new Permiso();
        return $perm->efectivosDeUsuario($idusuario);
    }

    // Override puntual: setea grants y revokes del usuario
    public function setOverrides($idusuario, array $grants, array $revokes)
    {
        $idu = (int)$idusuario;
        dbQuery("DELETE FROM usuario_permiso WHERE idusuario = ?", 'i', [$idu]);
        foreach ($grants as $idp) {
            $idp = (int)$idp;
            if ($idp > 0) {
                dbQuery("INSERT IGNORE INTO usuario_permiso (idusuario, idpermiso, tipo) VALUES (?, ?, 'grant')",
                    'ii', [$idu, $idp]);
            }
        }
        foreach ($revokes as $idp) {
            $idp = (int)$idp;
            if ($idp > 0) {
                dbQuery("INSERT IGNORE INTO usuario_permiso (idusuario, idpermiso, tipo) VALUES (?, ?, 'revoke')",
                    'ii', [$idu, $idp]);
            }
        }
        return true;
    }

    public function overrides($idusuario)
    {
        return dbQuery(
            "SELECT up.idpermiso, up.tipo, p.codigo, p.nombre
             FROM usuario_permiso up
             JOIN permiso p ON p.idpermiso = up.idpermiso
             WHERE up.idusuario = ?",
            'i', [(int)$idusuario]
        );
    }
}
