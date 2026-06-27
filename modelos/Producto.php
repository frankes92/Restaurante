<?php
require_once __DIR__ . "/../config/Conexion.php";

class Producto
{
    public function __construct() {}

    public function insertar($codigo, $nombre, $precio, $idcategoria, $imagen, $popular, $favorito, $codigoAfectacion = '10')
    {
        $idcategoria = ($idcategoria === '' || $idcategoria === null) ? 'NULL' : "'$idcategoria'";
        $popular  = $popular  ? 1 : 0;
        $favorito = $favorito ? 1 : 0;
        $codigoAfectacion = in_array($codigoAfectacion, ['10','20','30','40'], true) ? $codigoAfectacion : '10';

        $sql = "INSERT INTO producto (codigo, nombre, precio, codigo_afectacion, idcategoria, imagen, popular, favorito, estado)
                VALUES ('$codigo','$nombre','$precio','$codigoAfectacion',$idcategoria,'$imagen','$popular','$favorito','1')";
        return ejecutarConsulta_retornarID($sql);
    }

    public function editar($idproducto, $codigo, $nombre, $precio, $idcategoria, $imagen, $popular, $favorito, $codigoAfectacion = '10')
    {
        $idcategoria = ($idcategoria === '' || $idcategoria === null) ? 'NULL' : "'$idcategoria'";
        $popular  = $popular  ? 1 : 0;
        $favorito = $favorito ? 1 : 0;
        $codigoAfectacion = in_array($codigoAfectacion, ['10','20','30','40'], true) ? $codigoAfectacion : '10';

        $sql = "UPDATE producto SET
                    codigo='$codigo',
                    nombre='$nombre',
                    precio='$precio',
                    codigo_afectacion='$codigoAfectacion',
                    idcategoria=$idcategoria,
                    imagen='$imagen',
                    popular='$popular',
                    favorito='$favorito'
                WHERE idproducto='$idproducto'";
        return ejecutarConsulta($sql);
    }

    public function desactivar($idproducto)
    {
        $sql = "UPDATE producto SET estado='0' WHERE idproducto='$idproducto'";
        return ejecutarConsulta($sql);
    }

    public function activar($idproducto)
    {
        $sql = "UPDATE producto SET estado='1' WHERE idproducto='$idproducto'";
        return ejecutarConsulta($sql);
    }

    public function toggleFavorito($idproducto)
    {
        $sql = "UPDATE producto SET favorito = 1 - favorito WHERE idproducto='$idproducto'";
        return ejecutarConsulta($sql);
    }

    public function mostrar($idproducto)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.codigo AS categoria_codigo
                FROM producto p
                LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                WHERE p.idproducto='$idproducto'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listar()
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.codigo AS categoria_codigo
                FROM producto p
                LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                ORDER BY p.idproducto ASC";
        return ejecutarConsulta($sql);
    }

    public function listarActivos()
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.codigo AS categoria_codigo,
                       (SELECT COUNT(*) FROM producto_precio pp WHERE pp.idproducto=p.idproducto AND pp.estado=1) AS num_precios
                FROM producto p
                LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                WHERE p.estado=1
                ORDER BY p.nombre ASC";
        return ejecutarConsulta($sql);
    }

    public function listarPorCategoria($idcategoria)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.codigo AS categoria_codigo
                FROM producto p
                LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                WHERE p.estado=1 AND p.idcategoria='$idcategoria'
                ORDER BY p.nombre ASC";
        return ejecutarConsulta($sql);
    }

    /**
     * Paginacion para POS (scroll infinito). Solo productos activos.
     * Devuelve { data, total, hasMore }.
     */
    public function listarPaginado($start, $length, $idcategoria = '', $search = '')
    {
        $start  = max(0, (int)$start);
        $length = max(1, min(200, (int)$length));
        $where  = " p.estado=1 ";
        if ($idcategoria !== '' && $idcategoria !== 'all') {
            $where .= " AND c.codigo='$idcategoria' ";
        }
        if ($search !== '') $where .= " AND p.nombre LIKE '%$search%' ";

        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.codigo AS categoria_codigo,
                       (SELECT COUNT(*) FROM producto_precio pp WHERE pp.idproducto=p.idproducto AND pp.estado=1) AS num_precios,
                       (SELECT COUNT(*) FROM producto_precio pp WHERE pp.idproducto=p.idproducto AND pp.estado=1 AND pp.controla_stock=1) AS num_con_stock,
                       (SELECT COALESCE(SUM(pp.stock),0) FROM producto_precio pp WHERE pp.idproducto=p.idproducto AND pp.estado=1 AND pp.controla_stock=1) AS stock_total
                FROM producto p
                LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                WHERE $where
                ORDER BY p.nombre ASC
                LIMIT $start, $length";
        $rs = ejecutarConsulta($sql);
        $items = [];
        while ($r = $rs->fetch_assoc()) $items[] = $r;

        $sqlTotal = "SELECT COUNT(*) AS t FROM producto p
                     LEFT JOIN categoria c ON p.idcategoria=c.idcategoria
                     WHERE $where";
        $row = ejecutarConsultaSimpleFila($sqlTotal);
        $total = (int)$row['t'];

        return [
            'data'    => $items,
            'total'   => $total,
            'hasMore' => ($start + count($items)) < $total,
        ];
    }

    public function listarServerSide($start, $length, $search, $orderCol, $orderDir, $idcategoria = '')
    {
        $cols = ['p.idproducto','p.codigo','p.nombre','c.nombre','p.precio','p.estado'];
        $orderCol = isset($cols[(int)$orderCol]) ? $cols[(int)$orderCol] : 'p.idproducto';
        $orderDir = strtolower($orderDir) === 'asc' ? 'ASC' : 'DESC';
        $start = max(0, (int)$start);
        $length = max(1, min(500, (int)$length));
        $where = " 1=1 ";
        if ($idcategoria !== '') $where .= " AND p.idcategoria='$idcategoria' ";
        if ($search !== '')      $where .= " AND (p.nombre LIKE '%$search%' OR p.codigo LIKE '%$search%') ";
        $sql = "SELECT p.*, c.nombre AS categoria_nombre
                FROM producto p LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                WHERE $where ORDER BY $orderCol $orderDir LIMIT $start, $length";
        return ejecutarConsulta($sql);
    }

    public function contar($search = '', $idcategoria = '', $useFilter = true)
    {
        $where = " 1=1 ";
        if ($idcategoria !== '') $where .= " AND p.idcategoria='$idcategoria' ";
        if ($useFilter && $search !== '') $where .= " AND (p.nombre LIKE '%$search%' OR p.codigo LIKE '%$search%') ";
        $sql = "SELECT COUNT(*) AS total FROM producto p WHERE $where";
        $row = ejecutarConsultaSimpleFila($sql);
        return (int)$row['total'];
    }

    /**
     * Importa categorias + productos desde filas de Excel/CSV.
     * Columnas: [Categoria, Codigo, Producto, Precio, Presentacion?, Afectacion?]
     * (la primera fila es el encabezado y se ignora). Las 2 ultimas son opcionales.
     *
     * - Crea la categoria si no existe (por nombre, sin distinguir mayusculas).
     * - Varias filas con el MISMO codigo => varias presentaciones del producto.
     * - Si el codigo ya existia en BD, se actualiza (sus precios se reemplazan
     *   por los de esta importacion).
     * - Afectacion SUNAT: 10 gravado (default), 20 exonerado, 30 inafecto.
     *
     * @return array resumen {ok, creados, actualizados, categorias_nuevas, presentaciones, errores[]}
     */
    public function importarDesdeFilas(array $rows)
    {
        global $conexion;
        $res = ['ok' => true, 'creados' => 0, 'actualizados' => 0, 'categorias_nuevas' => 0, 'presentaciones' => 0, 'errores' => []];
        if (count($rows) < 2) {
            return ['ok' => false, 'creados' => 0, 'actualizados' => 0, 'categorias_nuevas' => 0, 'presentaciones' => 0,
                    'errores' => ['El archivo no tiene filas de datos (solo encabezado o vacío).']];
        }

        // Cache de categorias por nombre normalizado
        $catCache = [];
        $rsCat = ejecutarConsulta("SELECT idcategoria, nombre FROM categoria");
        while ($c = $rsCat->fetch_assoc()) {
            $catCache[mb_strtolower(trim($c['nombre']))] = (int)$c['idcategoria'];
        }
        $maxOrdenCat = (int)(ejecutarConsultaSimpleFila("SELECT COALESCE(MAX(orden),0) AS m FROM categoria")['m'] ?? 0);

        $E = function ($v) use ($conexion) { return $conexion->real_escape_string(trim((string)$v)); };

        // Codigos ya procesados EN ESTA importacion (para acumular presentaciones)
        $vistos = [];   // codigo => ['idprod'=>, 'orden'=>]

        for ($i = 1; $i < count($rows); $i++) {
            $fila = $rows[$i];
            if (count(array_filter($fila, fn($x) => trim((string)$x) !== '')) === 0) continue;

            $catNombre = trim((string)($fila[0] ?? ''));
            $codigo    = trim((string)($fila[1] ?? ''));
            $prodNom   = trim((string)($fila[2] ?? ''));
            $precioRaw = trim((string)($fila[3] ?? ''));
            $presNom   = trim((string)($fila[4] ?? ''));  // opcional
            $afectRaw  = trim((string)($fila[5] ?? ''));  // opcional

            $nLinea = $i + 1;

            if ($prodNom === '')   { $res['errores'][] = "Fila $nLinea: falta el nombre del producto."; continue; }
            if ($catNombre === '') { $res['errores'][] = "Fila $nLinea: falta la categoría."; continue; }

            $precio = (float)str_replace(',', '.', preg_replace('/[^0-9,.\-]/', '', $precioRaw));
            if ($precio <= 0) { $res['errores'][] = "Fila $nLinea: precio inválido ('$precioRaw') en '$prodNom'."; continue; }

            // Afectacion SUNAT (default 10 gravado)
            $afect = in_array($afectRaw, ['10','20','30','40'], true) ? $afectRaw : '10';
            // Nombre de presentacion (default 'Normal')
            if ($presNom === '') $presNom = 'Normal';

            // --- Categoria: buscar o crear ---
            $catKey = mb_strtolower($catNombre);
            if (isset($catCache[$catKey])) {
                $idcat = $catCache[$catKey];
            } else {
                $maxOrdenCat++;
                $codCat = $this->generarCodigoCategoria($catNombre);
                $idcat = ejecutarConsulta_retornarID(
                    "INSERT INTO categoria (codigo,nombre,icono,color,orden,estado)
                     VALUES ('" . $E($codCat) . "','" . $E($catNombre) . "','fa-utensils','#5b3df5','$maxOrdenCat','1')");
                $catCache[$catKey] = (int)$idcat;
                $res['categorias_nuevas']++;
            }

            if ($codigo === '') $codigo = $this->generarCodigoProducto($prodNom);
            $codKey = mb_strtolower($codigo);

            // ¿Ya procesado en esta importacion? => agregar presentacion adicional
            if (isset($vistos[$codKey])) {
                $idprod = $vistos[$codKey]['idprod'];
                $ord    = ++$vistos[$codKey]['orden'];
                ejecutarConsulta(
                    "INSERT INTO producto_precio (idproducto,nombre,precio,es_default,orden,estado)
                     VALUES ('$idprod','" . $E($presNom) . "','$precio',0,'$ord',1)");
                $res['presentaciones']++;
                continue;
            }

            // --- Producto: primera vez en esta importacion (crear o actualizar) ---
            $existe = ejecutarConsultaSimpleFila("SELECT idproducto FROM producto WHERE codigo='" . $E($codigo) . "' LIMIT 1");
            if ($existe) {
                $idprod = (int)$existe['idproducto'];
                ejecutarConsulta(
                    "UPDATE producto SET nombre='" . $E($prodNom) . "', precio='$precio',
                         codigo_afectacion='$afect', idcategoria='$idcat', estado=1
                     WHERE idproducto='$idprod'");
                $res['actualizados']++;
            } else {
                $idprod = ejecutarConsulta_retornarID(
                    "INSERT INTO producto (codigo,nombre,precio,codigo_afectacion,idcategoria,imagen,popular,favorito,estado)
                     VALUES ('" . $E($codigo) . "','" . $E($prodNom) . "','$precio','$afect','$idcat','',0,0,1)");
                $res['creados']++;
            }

            // Reemplazar precios y crear la primera presentacion (default)
            ejecutarConsulta("DELETE FROM producto_precio WHERE idproducto='$idprod'");
            ejecutarConsulta(
                "INSERT INTO producto_precio (idproducto,nombre,precio,es_default,orden,estado)
                 VALUES ('$idprod','" . $E($presNom) . "','$precio',1,1,1)");
            $res['presentaciones']++;
            $vistos[$codKey] = ['idprod' => $idprod, 'orden' => 1];
        }

        return $res;
    }

    private function generarCodigoCategoria($nombre)
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $nombre));
        $base = substr($base, 0, 4) ?: 'CAT';
        // asegurar unicidad
        $cod = $base; $n = 1;
        while (ejecutarConsultaSimpleFila("SELECT idcategoria FROM categoria WHERE codigo='$cod' LIMIT 1")) {
            $cod = $base . $n; $n++;
        }
        return $cod;
    }

    private function generarCodigoProducto($nombre)
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $nombre));
        $base = substr($base, 0, 6) ?: 'PROD';
        $cod = $base; $n = 1;
        while (ejecutarConsultaSimpleFila("SELECT idproducto FROM producto WHERE codigo='$cod' LIMIT 1")) {
            $cod = $base . $n; $n++;
        }
        return $cod;
    }

    // ---------- Multiprecios ----------

    public function precios($idproducto)
    {
        $sql = "SELECT * FROM producto_precio WHERE idproducto='$idproducto' AND estado=1 ORDER BY orden ASC, idprecio ASC";
        return ejecutarConsulta($sql);
    }

    /**
     * Sincroniza los precios/presentaciones del producto SIN perder el stock
     * de cada presentacion. Hace match por nombre de presentacion: las que se
     * mantienen conservan su stock; las que ya no aparecen se desactivan
     * (soft-delete) para no perder su historial de inventario.
     *
     * Recibe array de: [{ nombre, precio, es_default }]
     */
    public function setPrecios($idproducto, array $precios)
    {
        global $conexion;
        $idproducto = (int)$idproducto;

        // Mapa de presentaciones existentes por nombre (para conservar idprecio + stock)
        $existentes = [];
        $rsEx = ejecutarConsulta("SELECT idprecio, nombre FROM producto_precio WHERE idproducto='$idproducto'");
        while ($e = $rsEx->fetch_assoc()) {
            $existentes[mb_strtolower(trim($e['nombre']))] = (int)$e['idprecio'];
        }

        $precioDefault = null;
        $tieneDefault  = false;
        $orden = 1;
        $idsVigentes = [];

        foreach ($precios as $p) {
            $nombreRaw = trim($p['nombre'] ?? 'Normal');
            if ($nombreRaw === '') $nombreRaw = 'Normal';
            $nombre = $conexion->real_escape_string($nombreRaw);
            $valor  = (float)($p['precio'] ?? 0);
            $esDef  = !empty($p['es_default']) ? 1 : 0;
            if ($esDef) { $precioDefault = $valor; $tieneDefault = true; }

            $clave = mb_strtolower($nombreRaw);
            if (isset($existentes[$clave])) {
                // Existe: actualizar precio/orden/default SIN tocar stock
                $idp = $existentes[$clave];
                ejecutarConsulta("UPDATE producto_precio
                                  SET precio='$valor', es_default='$esDef', orden='$orden', estado=1
                                  WHERE idprecio='$idp'");
                $idsVigentes[] = $idp;
            } else {
                // Nueva presentacion: stock arranca en 0, sin control
                $idp = ejecutarConsulta_retornarID(
                    "INSERT INTO producto_precio (idproducto, nombre, precio, es_default, orden, estado)
                     VALUES ('$idproducto','$nombre','$valor','$esDef','$orden',1)");
                $idsVigentes[] = (int)$idp;
            }
            $orden++;
        }

        // Desactivar (soft-delete) las presentaciones que ya no estan en la lista,
        // asi se conserva su historial de inventario.
        if (count($idsVigentes) > 0) {
            $in = implode(',', array_map('intval', $idsVigentes));
            ejecutarConsulta("UPDATE producto_precio SET estado=0
                              WHERE idproducto='$idproducto' AND idprecio NOT IN ($in)");
        }

        // Si ninguno es default, marcar el primero vigente
        if (!$tieneDefault && count($idsVigentes) > 0) {
            $primero = (int)$idsVigentes[0];
            ejecutarConsulta("UPDATE producto_precio SET es_default=1 WHERE idprecio='$primero'");
            $precioDefault = (float)($precios[0]['precio'] ?? 0);
        }
        // Sincronizar producto.precio con el default
        if ($precioDefault !== null) {
            ejecutarConsulta("UPDATE producto SET precio='$precioDefault' WHERE idproducto='$idproducto'");
        }
        return true;
    }

    public function precioPorId($idprecio)
    {
        $sql = "SELECT pp.*, p.nombre AS producto_nombre
                FROM producto_precio pp
                JOIN producto p ON pp.idproducto = p.idproducto
                WHERE pp.idprecio='$idprecio' LIMIT 1";
        return ejecutarConsultaSimpleFila($sql);
    }

    // ---------- Subir imagen ----------
    public function subirImagen($archivo, $idproducto = null)
    {
        if (empty($archivo) || $archivo['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'msg' => 'No se recibio el archivo'];
        }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'msg' => 'Tipo no permitido (solo JPG, PNG, WEBP, GIF)'];
        }
        if ($archivo['size'] > 2 * 1024 * 1024) {
            return ['ok' => false, 'msg' => 'Maximo 2 MB'];
        }
        $ext      = $allowed[$mime];
        $base     = realpath(__DIR__ . '/../public/img/productos');
        if (!$base) {
            @mkdir(__DIR__ . '/../public/img/productos', 0777, true);
            $base = realpath(__DIR__ . '/../public/img/productos');
        }
        $nombre   = ($idproducto ? 'p' . (int)$idproducto . '_' : '') . substr(md5(uniqid('', true)), 0, 12) . '.' . $ext;
        $destino  = $base . DIRECTORY_SEPARATOR . $nombre;
        if (!@move_uploaded_file($archivo['tmp_name'], $destino)) {
            return ['ok' => false, 'msg' => 'No se pudo guardar el archivo'];
        }
        // Ruta accesible web (relativa al directorio vistas/)
        $url = '../public/img/productos/' . $nombre;
        return ['ok' => true, 'url' => $url, 'archivo' => $nombre];
    }

    // ===================================================================
    // INVENTARIO / STOCK  (por PRESENTACION: tabla producto_precio)
    // ===================================================================

    /**
     * Activa/desactiva control de stock y fija stock + minimo para una
     * PRESENTACION concreta (idprecio). No toca otras presentaciones.
     */
    public function setInventarioPrecio($idprecio, $controla, $stock, $stockMinimo, $idusuario = null)
    {
        $idprecio    = (int)$idprecio;
        $controla    = $controla ? 1 : 0;
        $stock       = (float)$stock;
        $stockMinimo = max(0, (float)$stockMinimo);

        $actual = ejecutarConsultaSimpleFila("SELECT idproducto, stock FROM producto_precio WHERE idprecio='$idprecio'");
        if (!$actual) return false;
        $stockPrevio = (float)$actual['stock'];

        ejecutarConsulta("UPDATE producto_precio
                          SET controla_stock='$controla', stock='$stock', stock_minimo='$stockMinimo'
                          WHERE idprecio='$idprecio'");

        if ($controla && abs($stock - $stockPrevio) > 0.0001) {
            $this->registrarMovimiento((int)$actual['idproducto'], $idprecio, 'ajuste',
                                       $stock - $stockPrevio, $stock, 'Ajuste manual de inventario', $idusuario);
        }
        return true;
    }

    /**
     * Suma (entrada) o resta (salida) stock manual de una presentacion.
     * $tipo: 'entrada' | 'salida' | 'ajuste'.
     */
    public function moverStockPrecio($idprecio, $tipo, $cantidad, $nota = '', $idusuario = null)
    {
        $idprecio = (int)$idprecio;
        $cantidad = abs((float)$cantidad);
        $pp = ejecutarConsultaSimpleFila("SELECT idproducto, stock, controla_stock FROM producto_precio WHERE idprecio='$idprecio'");
        if (!$pp || (int)$pp['controla_stock'] !== 1) return false;

        $delta = ($tipo === 'salida') ? -$cantidad : $cantidad;
        $nuevo = (float)$pp['stock'] + $delta;

        global $conexion;
        $notaEsc = $conexion->real_escape_string($nota);
        ejecutarConsulta("UPDATE producto_precio SET stock='$nuevo' WHERE idprecio='$idprecio'");
        $this->registrarMovimiento((int)$pp['idproducto'], $idprecio, $tipo, $delta, $nuevo, $notaEsc, $idusuario);
        return true;
    }

    /**
     * Descuenta stock por venta de una presentacion concreta. Protegido:
     * si la presentacion no controla stock, simplemente no hace nada.
     */
    public function descontarPorVenta($idprecio, $cantidad, $idorden = null, $idusuario = null)
    {
        $idprecio = (int)$idprecio;
        $cantidad = (float)$cantidad;
        if ($idprecio <= 0 || $cantidad <= 0) return;

        $pp = ejecutarConsultaSimpleFila("SELECT idproducto, stock, controla_stock FROM producto_precio WHERE idprecio='$idprecio'");
        if (!$pp || (int)$pp['controla_stock'] !== 1) return;

        $nuevo = (float)$pp['stock'] - $cantidad;
        ejecutarConsulta("UPDATE producto_precio SET stock='$nuevo' WHERE idprecio='$idprecio'");
        $this->registrarMovimiento((int)$pp['idproducto'], $idprecio, 'venta', -$cantidad, $nuevo,
                                   'Venta' . ($idorden ? " orden #$idorden" : ''), $idusuario, $idorden);
    }

    /** Stock disponible de una presentacion (o null si no controla stock). */
    public function stockDePresentacion($idprecio)
    {
        $idprecio = (int)$idprecio;
        $pp = ejecutarConsultaSimpleFila("SELECT stock, controla_stock FROM producto_precio WHERE idprecio='$idprecio' AND estado=1");
        if (!$pp || (int)$pp['controla_stock'] !== 1) return null;
        return (float)$pp['stock'];
    }

    private function registrarMovimiento($idproducto, $idprecio, $tipo, $cantidad, $stockResultante, $nota, $idusuario, $idorden = null)
    {
        $idproducto = (int)$idproducto;
        $idprecio   = (int)$idprecio;
        $cantidad   = (float)$cantidad;
        $stockResultante = (float)$stockResultante;
        $idu = ($idusuario === null || $idusuario === '') ? 'NULL' : (int)$idusuario;
        $ido = ($idorden === null || $idorden === '') ? 'NULL' : (int)$idorden;
        global $conexion;
        $notaEsc = $conexion->real_escape_string((string)$nota);
        $tipoEsc = in_array($tipo, ['entrada','salida','ajuste','venta'], true) ? $tipo : 'ajuste';
        @ejecutarConsulta("INSERT INTO inventario_movimiento
            (idproducto, idprecio, tipo, cantidad, stock_resultante, nota, idusuario, idorden)
            VALUES ('$idproducto','$idprecio','$tipoEsc','$cantidad','$stockResultante','$notaEsc',$idu,$ido)");
    }

    /**
     * Lista las PRESENTACIONES con control de stock + su semaforo.
     * Cada fila es una presentacion (ej. "Cerveza X · Vidrio 1LT").
     */
    public function listarInventario($search = '')
    {
        global $conexion;
        $where = " p.estado=1 AND pp.estado=1 AND pp.controla_stock=1 ";
        if ($search !== '') {
            $se = $conexion->real_escape_string($search);
            $where .= " AND (p.nombre LIKE '%$se%' OR p.codigo LIKE '%$se%' OR pp.nombre LIKE '%$se%') ";
        }
        $sql = "SELECT pp.idprecio, pp.idproducto, pp.nombre AS presentacion, pp.stock, pp.stock_minimo, pp.precio,
                       p.nombre AS producto_nombre, p.codigo,
                       c.nombre AS categoria_nombre,
                       CASE
                         WHEN pp.stock <= 0 THEN 'rojo'
                         WHEN pp.stock <= pp.stock_minimo THEN 'amarillo'
                         ELSE 'verde'
                       END AS semaforo
                FROM producto_precio pp
                JOIN producto p ON p.idproducto = pp.idproducto
                LEFT JOIN categoria c ON c.idcategoria = p.idcategoria
                WHERE $where
                ORDER BY (pp.stock <= 0) DESC, (pp.stock <= pp.stock_minimo) DESC, p.nombre ASC, pp.orden ASC";
        return ejecutarConsulta($sql);
    }

    /** Resumen para el semaforo (conteos por estado, por presentacion). */
    public function resumenInventario()
    {
        $sql = "SELECT
                  COUNT(*) AS total,
                  SUM(CASE WHEN pp.stock <= 0 THEN 1 ELSE 0 END) AS agotados,
                  SUM(CASE WHEN pp.stock > 0 AND pp.stock <= pp.stock_minimo THEN 1 ELSE 0 END) AS bajos,
                  SUM(CASE WHEN pp.stock > pp.stock_minimo THEN 1 ELSE 0 END) AS ok,
                  COALESCE(SUM(pp.stock * pp.precio),0) AS valor_total
                FROM producto_precio pp
                JOIN producto p ON p.idproducto = pp.idproducto
                WHERE p.estado=1 AND pp.estado=1 AND pp.controla_stock=1";
        return ejecutarConsultaSimpleFila($sql);
    }

    /**
     * Reporte de inventario DETALLADO por presentacion (controla_stock=1):
     *  - stock, precio y valor_stock (stock * precio, en soles)
     *  - vendido  (unidades cobradas, NO cortesia)
     *  - cortesia (unidades entregadas como cortesia)
     *  - stock_minimo y semaforo
     */
    public function reporteInventarioDetallado($search = '')
    {
        global $conexion;
        $where = " p.estado=1 AND pp.estado=1 AND pp.controla_stock=1 ";
        if ($search !== '') {
            $se = $conexion->real_escape_string($search);
            $where .= " AND (p.nombre LIKE '%$se%' OR p.codigo LIKE '%$se%' OR pp.nombre LIKE '%$se%') ";
        }
        $sql = "SELECT pp.idprecio, pp.idproducto, pp.nombre AS presentacion, pp.stock, pp.stock_minimo, pp.precio,
                       p.nombre AS producto_nombre, p.codigo,
                       COALESCE(c.nombre,'—') AS categoria_nombre,
                       (pp.stock * pp.precio) AS valor_stock,
                       CASE
                         WHEN pp.stock <= 0 THEN 'rojo'
                         WHEN pp.stock <= pp.stock_minimo THEN 'amarillo'
                         ELSE 'verde'
                       END AS semaforo,
                       COALESCE((SELECT SUM(d.cantidad) FROM orden_detalle d
                                 JOIN orden o ON o.idorden=d.idorden
                                 WHERE d.idprecio=pp.idprecio AND d.cortesia=0 AND d.estado<>'anulado'
                                   AND o.estado='pagada'),0) AS vendido,
                       COALESCE((SELECT SUM(d.cantidad) FROM orden_detalle d
                                 JOIN orden o ON o.idorden=d.idorden
                                 WHERE d.idprecio=pp.idprecio AND d.cortesia=1 AND d.estado<>'anulado'
                                   AND o.estado='pagada'),0) AS cortesia
                FROM producto_precio pp
                JOIN producto p ON p.idproducto = pp.idproducto
                LEFT JOIN categoria c ON c.idcategoria = p.idcategoria
                WHERE $where
                ORDER BY (pp.stock <= 0) DESC, (pp.stock <= pp.stock_minimo) DESC, p.nombre ASC, pp.orden ASC";
        return ejecutarConsulta($sql);
    }

    /** Historial de movimientos de una presentacion. */
    public function movimientos($idprecio, $limit = 50)
    {
        $idprecio = (int)$idprecio;
        $limit = (int)$limit;
        $sql = "SELECT m.*, u.nombre AS usuario_nombre
                FROM inventario_movimiento m
                LEFT JOIN usuario u ON u.idusuario = m.idusuario
                WHERE m.idprecio='$idprecio'
                ORDER BY m.idmov DESC LIMIT $limit";
        return ejecutarConsulta($sql);
    }

    public function topVendidos($limit = 10)
    {
        $limit = (int)$limit;
        $sql = "SELECT p.idproducto, p.nombre, p.precio, p.imagen,
                       COALESCE(SUM(d.cantidad),0) AS total_vendido,
                       COALESCE(SUM(d.subtotal),0) AS total_ingresos
                FROM producto p
                LEFT JOIN orden_detalle d ON d.idproducto = p.idproducto AND d.cortesia = 0
                LEFT JOIN orden o ON o.idorden = d.idorden AND o.estado='pagada'
                WHERE p.estado = 1
                GROUP BY p.idproducto, p.nombre, p.precio, p.imagen
                ORDER BY total_vendido DESC
                LIMIT $limit";
        return ejecutarConsulta($sql);
    }

    /**
     * Reporte detallado por producto en un rango de fechas (ventas pagadas):
     *  - cantidad_vendida  (cobradas, NO cortesia)
     *  - total_ingresos    (cobradas, NO cortesia)
     *  - cantidad_cortesia (entregadas como cortesia, NO suman venta)
     *  - num_con_stock / stock_actual (stock disponible si controla inventario)
     * Devuelve solo productos con actividad (vendidos o de cortesia) en el rango.
     */
    public function reporteProductosDetallado($desde, $hasta)
    {
        $desde = ($desde !== '' && $desde !== null) ? $desde : '2000-01-01';
        $hasta = ($hasta !== '' && $hasta !== null) ? $hasta : date('Y-m-d');

        $sql = "SELECT p.idproducto, p.nombre,
                       COALESCE(c.nombre,'—') AS categoria_nombre,
                       COALESCE((SELECT SUM(d.cantidad) FROM orden_detalle d
                                 JOIN orden o ON o.idorden=d.idorden
                                 WHERE d.idproducto=p.idproducto AND d.cortesia=0 AND d.estado<>'anulado'
                                   AND o.estado='pagada' AND DATE(o.fecha_pago) BETWEEN ? AND ?),0) AS cantidad_vendida,
                       COALESCE((SELECT SUM(d.subtotal) FROM orden_detalle d
                                 JOIN orden o ON o.idorden=d.idorden
                                 WHERE d.idproducto=p.idproducto AND d.cortesia=0 AND d.estado<>'anulado'
                                   AND o.estado='pagada' AND DATE(o.fecha_pago) BETWEEN ? AND ?),0) AS total_ingresos,
                       COALESCE((SELECT SUM(d.cantidad) FROM orden_detalle d
                                 JOIN orden o ON o.idorden=d.idorden
                                 WHERE d.idproducto=p.idproducto AND d.cortesia=1 AND d.estado<>'anulado'
                                   AND o.estado='pagada' AND DATE(o.fecha_pago) BETWEEN ? AND ?),0) AS cantidad_cortesia,
                       (SELECT COUNT(*) FROM producto_precio pp WHERE pp.idproducto=p.idproducto AND pp.estado=1 AND pp.controla_stock=1) AS num_con_stock,
                       (SELECT COALESCE(SUM(pp.stock),0) FROM producto_precio pp WHERE pp.idproducto=p.idproducto AND pp.estado=1 AND pp.controla_stock=1) AS stock_actual
                FROM producto p
                LEFT JOIN categoria c ON c.idcategoria=p.idcategoria
                WHERE p.estado=1
                HAVING cantidad_vendida > 0 OR cantidad_cortesia > 0
                ORDER BY cantidad_vendida DESC, total_ingresos DESC, p.nombre ASC";
        return dbQuery($sql, 'ssssss', [$desde,$hasta,$desde,$hasta,$desde,$hasta]);
    }

    /**
     * Detalle TRANSACCIONAL de ventas: una fila por cada línea vendida (no
     * cortesía, no anulada) de órdenes pagadas en el rango. Incluye fecha/hora
     * del pago y la PRESENTACIÓN (variante) vendida, para desglosar por ejemplo
     * Inka Cola Personal / Gordita / Vidrio 1LT por separado.
     */
    public function reporteVentasDetalle($desde, $hasta)
    {
        $desde = ($desde !== '' && $desde !== null) ? $desde : '2000-01-01';
        $hasta = ($hasta !== '' && $hasta !== null) ? $hasta : date('Y-m-d');

        // Incluye ventas y cortesías (no anuladas) de órdenes pagadas. Trae el
        // stock ACTUAL de la presentación para la columna correspondiente.
        $sql = "SELECT o.fecha_pago,
                       o.numero AS orden_numero,
                       p.idproducto,
                       p.nombre AS producto_nombre,
                       COALESCE(c.nombre,'—') AS categoria_nombre,
                       d.idprecio,
                       COALESCE(NULLIF(TRIM(pp.nombre),''),'—') AS presentacion,
                       d.cantidad,
                       d.precio,
                       d.subtotal,
                       d.cortesia,
                       pp.controla_stock,
                       pp.stock AS stock_actual
                FROM orden_detalle d
                JOIN orden o        ON o.idorden = d.idorden
                JOIN producto p     ON p.idproducto = d.idproducto
                LEFT JOIN producto_precio pp ON pp.idprecio = d.idprecio
                LEFT JOIN categoria c ON c.idcategoria = p.idcategoria
                WHERE d.estado <> 'anulado'
                  AND o.estado = 'pagada'
                  AND DATE(o.fecha_pago) BETWEEN ? AND ?
                ORDER BY o.fecha_pago DESC, d.iddetalle DESC";
        return dbQuery($sql, 'ss', [$desde, $hasta]);
    }
}
?>
