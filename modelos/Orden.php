<?php
require_once __DIR__ . "/../config/Conexion.php";

class Orden
{
    public function __construct() {}

    // Genera siguiente numero correlativo (00001, 00002, ...)
    public function siguienteNumero()
    {
        $sql = "SELECT MAX(CAST(numero AS UNSIGNED)) AS max_num FROM orden";
        $row = ejecutarConsultaSimpleFila($sql);
        $next = ((int)($row['max_num'] ?? 0)) + 1;
        return str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    // Crear cabecera de orden vacia (en_curso) — items se agregan despues
    public function crear($idmesa, $tipo, $mozo, $idsesion, $idusuario = null)
    {
        $idmesa     = ($idmesa     === '' || $idmesa     === null) ? 'NULL' : "'$idmesa'";
        $idsesion   = ($idsesion   === '' || $idsesion   === null) ? 'NULL' : "'$idsesion'";
        $idusuario  = ($idusuario  === '' || $idusuario  === null) ? 'NULL' : "'" . (int)$idusuario . "'";
        $tipo       = $tipo ?: 'dine_in';
        $numero     = $this->siguienteNumero();

        $sql = "INSERT INTO orden (numero, idmesa, idsesion, idusuario, tipo, estado, mozo, subtotal, igv, total, metodo_pago, fecha)
                VALUES ('$numero', $idmesa, $idsesion, $idusuario, '$tipo', 'en_curso', '$mozo', 0, 0, 0, '', NOW())";
        $idorden = ejecutarConsulta_retornarID($sql);

        // Si hay mesa, marcarla como ocupada
        if ($idmesa !== 'NULL') {
            ejecutarConsulta("UPDATE mesa SET estado='ocupada' WHERE idmesa=$idmesa");
        }
        return ['idorden' => $idorden, 'numero' => $numero];
    }

    // Recalcular totales en base al detalle (IGV configurable desde empresa.tasa_igv)
    public function recalcularTotales($idorden)
    {
        // Tomar tasa de la empresa (default 0.18 si no configurada)
        $tasaRow = ejecutarConsultaSimpleFila("SELECT COALESCE(tasa_igv, 0.18) AS t FROM empresa LIMIT 1");
        $tasa    = (float)($tasaRow['t'] ?? 0.18);
        $factor  = 1 + $tasa;

        // Los items marcados como CORTESIA no se cobran (no suman al total).
        $sql = "SELECT COALESCE(SUM(CASE WHEN cortesia=1 THEN 0 ELSE cantidad * precio END),0) AS suma
                FROM orden_detalle WHERE idorden='$idorden' AND estado <> 'anulado'";
        $row = ejecutarConsultaSimpleFila($sql);
        $totalConIgv = (float)$row['suma'];                // los precios ya incluyen IGV
        $subtotal    = round($totalConIgv / $factor, 2);
        $igv         = round($totalConIgv - $subtotal, 2);
        $total       = round($totalConIgv, 2);

        $sqlUpd = "UPDATE orden
                   SET subtotal='$subtotal', igv='$igv', total='$total'
                   WHERE idorden='$idorden'";
        ejecutarConsulta($sqlUpd);

        return ['subtotal' => $subtotal, 'igv' => $igv, 'total' => $total];
    }

    public function agregarItem($idorden, $idproducto, $cantidad, $nota, $idprecio = null)
    {
        $prod = ejecutarConsultaSimpleFila("SELECT nombre, precio FROM producto WHERE idproducto='$idproducto'");
        if (!$prod) return false;

        $nombre   = $prod['nombre'];
        $precio   = (float)$prod['precio'];
        $cantidad = (float)$cantidad;

        // Si NO se paso idprecio, resolver la presentacion por defecto del producto
        // (o la primera activa). Asi SIEMPRE queda ligado a una presentacion y el
        // descuento de inventario funciona aun para productos de una sola variante.
        if (!$idprecio) {
            $def = ejecutarConsultaSimpleFila(
                "SELECT idprecio FROM producto_precio
                 WHERE idproducto='$idproducto' AND estado=1
                 ORDER BY es_default DESC, orden ASC, idprecio ASC
                 LIMIT 1"
            );
            if ($def && !empty($def['idprecio'])) {
                $idprecio = (int)$def['idprecio'];
            }
        }

        // Si se paso (o resolvio) idprecio, usar ese precio + nombre de variante
        if ($idprecio) {
            $idprecio = (int)$idprecio;
            $pp = ejecutarConsultaSimpleFila("SELECT nombre, precio, controla_stock, stock FROM producto_precio
                                              WHERE idprecio='$idprecio' AND idproducto='$idproducto' AND estado=1");
            if ($pp) {
                // BLOQUEO POR STOCK: si la presentacion controla inventario.
                if ((int)$pp['controla_stock'] === 1) {
                    $stockDisp = (float)$pp['stock'];
                    // Sin stock: agotado.
                    if ($stockDisp <= 0) {
                        return ['ok' => false, 'agotado' => true, 'msg' => 'Producto agotado, no se puede agregar'];
                    }
                    // No exceder el stock: cantidad ya comprometida en ordenes
                    // abiertas (incluida esta) + lo que se quiere agregar no puede
                    // superar el inventario, para no dejarlo en negativo.
                    $comprometido = $this->comprometidoEnOrdenesAbiertas($idprecio);
                    if ($comprometido + $cantidad > $stockDisp) {
                        $restante = max(0, $stockDisp - $comprometido);
                        return ['ok' => false, 'msg' => 'Stock insuficiente: solo queda ' . $this->fmtCant($restante) . ' en inventario.'];
                    }
                }
                $precio = (float)$pp['precio'];
                $varNombre = trim($pp['nombre']);
                if ($varNombre !== '' && strtolower($varNombre) !== 'normal') {
                    $nombre .= ' (' . $varNombre . ')';
                }
            }
        }

        // Si ya existe el MISMO producto + MISMA variante (precio + nombre) + MISMA nota,
        // sumar la cantidad en vez de duplicar la linea.
        global $conexion;
        $notaEsc   = $conexion->real_escape_string($nota);
        $nombreEsc = $conexion->real_escape_string($nombre);
        // Solo fusionar con lineas NORMALES (no cortesia), para no convertir
        // sin querer un producto pagado en cortesia ni viceversa.
        $existente = ejecutarConsultaSimpleFila(
            "SELECT iddetalle, cantidad, precio FROM orden_detalle
             WHERE idorden='$idorden' AND idproducto='$idproducto'
                   AND ABS(precio - $precio) < 0.005
                   AND nombre='" . $nombreEsc . "'
                   AND IFNULL(nota,'')='" . $notaEsc . "'
                   AND cortesia=0
                   AND estado IN ('pendiente','en_preparacion')
             LIMIT 1"
        );

        if ($existente) {
            $nuevaCant = (float)$existente['cantidad'] + $cantidad;
            $nuevoSub  = round($nuevaCant * (float)$existente['precio'], 2);
            ejecutarConsulta(
                "UPDATE orden_detalle
                 SET cantidad='$nuevaCant', subtotal='$nuevoSub'
                 WHERE iddetalle='" . $existente['iddetalle'] . "'"
            );
            $this->recalcularTotales($idorden);
            return (int)$existente['iddetalle'];
        }

        $subtotal = round($cantidad * $precio, 2);
        // Guardar idprecio (presentacion vendida) para el descuento de inventario.
        $idprecioSql = $idprecio ? (int)$idprecio : 'NULL';
        $sql = "INSERT INTO orden_detalle (idorden, idproducto, idprecio, nombre, cantidad, precio, subtotal, nota, estado)
                VALUES ('$idorden','$idproducto',$idprecioSql,'$nombre','$cantidad','$precio','$subtotal','$notaEsc','pendiente')";
        $iddetalle = ejecutarConsulta_retornarID($sql);

        $this->recalcularTotales($idorden);
        return $iddetalle;
    }

    public function actualizarItemCantidad($iddetalle, $cantidad)
    {
        $iddetalle = (int)$iddetalle;
        $row = ejecutarConsultaSimpleFila("SELECT idorden, idprecio, precio, cantidad_enviada, cortesia FROM orden_detalle WHERE iddetalle='$iddetalle'");
        if (!$row) return false;

        $precio   = (float)$row['precio'];
        $idorden  = (int)$row['idorden'];
        $cantEnv  = (float)$row['cantidad_enviada'];
        $esCortesia = (int)$row['cortesia'] === 1;
        $cantidad = (float)$cantidad;

        // Si la cantidad solicitada es 0 o menos:
        //  - Si el item ya fue enviado a cocina, marcamos cantidad=0 (pendiente de anulacion).
        //  - Si no se envio nunca, eliminamos la fila por completo.
        if ($cantidad <= 0) {
            if ($cantEnv > 0) {
                ejecutarConsulta("UPDATE orden_detalle SET cantidad=0, subtotal=0 WHERE iddetalle='$iddetalle'");
            } else {
                ejecutarConsulta("DELETE FROM orden_detalle WHERE iddetalle='$iddetalle'");
            }
            $this->recalcularTotales($idorden);
            return true;
        }

        // BLOQUEO POR STOCK al AUMENTAR cantidad: lo comprometido en ordenes
        // abiertas (excluyendo esta linea) + la nueva cantidad no puede superar
        // el inventario, para no dejarlo en negativo.
        if (!empty($row['idprecio'])) {
            $idprecio = (int)$row['idprecio'];
            $pp = ejecutarConsultaSimpleFila("SELECT controla_stock, stock FROM producto_precio WHERE idprecio='$idprecio'");
            if ($pp && (int)$pp['controla_stock'] === 1) {
                $stockDisp = (float)$pp['stock'];
                $comprometido = $this->comprometidoEnOrdenesAbiertas($idprecio, $iddetalle);
                if ($comprometido + $cantidad > $stockDisp) {
                    $restante = max(0, $stockDisp - $comprometido);
                    return ['ok' => false, 'msg' => 'Stock insuficiente: solo queda ' . $this->fmtCant($restante) . ' en inventario.'];
                }
            }
        }

        // Si es cortesia, el subtotal sigue en 0 aunque cambie la cantidad.
        $subtotal = $esCortesia ? 0 : round($cantidad * $precio, 2);
        ejecutarConsulta("UPDATE orden_detalle SET cantidad='$cantidad', subtotal='$subtotal' WHERE iddetalle='$iddetalle'");
        $this->recalcularTotales($idorden);
        return true;
    }

    /**
     * Suma la cantidad de una presentacion (idprecio) ya comprometida en TODAS
     * las ordenes abiertas (no pagadas ni anuladas). Sirve para validar que no se
     * venda mas de lo que hay en inventario, ya que el stock recien se descuenta
     * al cobrar. Se puede excluir una linea de detalle (al editar su cantidad).
     */
    private function comprometidoEnOrdenesAbiertas($idprecio, $excluirDetalle = 0)
    {
        $idprecio = (int)$idprecio;
        $excluir  = (int)$excluirDetalle;
        $cond = $excluir > 0 ? " AND d.iddetalle <> '$excluir'" : '';
        $row = ejecutarConsultaSimpleFila(
            "SELECT IFNULL(SUM(d.cantidad),0) AS comp
             FROM orden_detalle d
             JOIN orden o ON o.idorden = d.idorden
             WHERE d.idprecio = '$idprecio'
               AND d.estado <> 'anulado'
               AND o.estado NOT IN ('pagada','anulada')" . $cond
        );
        return $row ? (float)$row['comp'] : 0;
    }

    /** Formatea una cantidad de stock sin decimales innecesarios (5 en vez de 5.00). */
    private function fmtCant($n)
    {
        $n = (float)$n;
        return ($n == (int)$n) ? (string)(int)$n : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    /**
     * Marca o desmarca un item como CORTESIA (invitacion).
     * Cortesia => no se cobra (subtotal 0), pero igual descuenta stock y
     * aparece en el comprobante impreso como cortesia.
     */
    public function marcarCortesia($iddetalle, $cortesia)
    {
        $iddetalle = (int)$iddetalle;
        $cortesia  = $cortesia ? 1 : 0;
        $row = ejecutarConsultaSimpleFila("SELECT idorden, cantidad, precio FROM orden_detalle WHERE iddetalle='$iddetalle'");
        if (!$row) return false;

        $idorden  = (int)$row['idorden'];
        // Si es cortesia, subtotal=0; si se quita, restaurar subtotal = cantidad*precio
        $subtotal = $cortesia ? 0 : round((float)$row['cantidad'] * (float)$row['precio'], 2);

        ejecutarConsulta("UPDATE orden_detalle
                          SET cortesia='$cortesia', subtotal='$subtotal'
                          WHERE iddetalle='$iddetalle'");
        // Tras cambiar el tipo (pago<->cortesia) fusionar con su gemelo del MISMO
        // tipo para no dejar dos filas iguales del mismo producto.
        $this->fusionarGemelo($iddetalle);
        $this->recalcularTotales($idorden);
        return true;
    }

    /**
     * Fusiona la linea $iddetalle con OTRA linea "gemela" (mismo producto,
     * presentacion, nombre, nota, estado y MISMO tipo cortesia) si existe.
     * La fila que SOBREVIVE es la de menor iddetalle (conserva su posicion);
     * la otra se elimina. Suma 'cantidad' y 'cantidad_enviada' (preserva los
     * totales => la cocina no ve cambios). Devuelve el iddetalle superviviente.
     */
    private function fusionarGemelo($iddetalle)
    {
        $iddetalle = (int)$iddetalle;
        $row = ejecutarConsultaSimpleFila(
            "SELECT idorden, idproducto, idprecio, nombre, nota, estado, cortesia, cantidad, cantidad_enviada, precio
             FROM orden_detalle WHERE iddetalle='$iddetalle'");
        if (!$row) return $iddetalle;

        global $conexion;
        $idorden    = (int)$row['idorden'];
        $idproducto = (int)$row['idproducto'];
        $idprecio   = ($row['idprecio'] !== null && $row['idprecio'] !== '') ? (int)$row['idprecio'] : null;
        $condPrecio = $idprecio ? " AND idprecio='$idprecio'" : " AND idprecio IS NULL";
        $nombreEsc  = $conexion->real_escape_string((string)$row['nombre']);
        $notaEsc    = $conexion->real_escape_string((string)$row['nota']);
        $estadoEsc  = $conexion->real_escape_string((string)$row['estado']);
        $cortesia   = (int)$row['cortesia'];
        $precio     = (float)$row['precio'];

        $twin = ejecutarConsultaSimpleFila(
            "SELECT iddetalle, cantidad, cantidad_enviada FROM orden_detalle
             WHERE idorden='$idorden' AND idproducto='$idproducto'" . $condPrecio . "
                   AND nombre='$nombreEsc' AND IFNULL(nota,'')='$notaEsc'
                   AND estado='$estadoEsc' AND cortesia='$cortesia'
                   AND iddetalle <> '$iddetalle'
             ORDER BY iddetalle ASC LIMIT 1");
        if (!$twin) return $iddetalle;

        // El gemelo de menor iddetalle es el que se queda (mantiene posicion).
        $keep = (int)$twin['iddetalle'];
        $drop = $iddetalle;
        if ($keep > $drop) { $keep = $iddetalle; $drop = (int)$twin['iddetalle']; }

        $nc  = (float)$row['cantidad']          + (float)$twin['cantidad'];
        $nce = (float)$row['cantidad_enviada']  + (float)$twin['cantidad_enviada'];
        $sub = $cortesia ? 0 : round($nc * $precio, 2);

        ejecutarConsulta(
            "UPDATE orden_detalle SET cantidad='$nc', cantidad_enviada='$nce', subtotal='$sub'
             WHERE iddetalle='$keep'");
        ejecutarConsulta("DELETE FROM orden_detalle WHERE iddetalle='$drop'");
        return $keep;
    }

    /**
     * CORTESIA PARCIAL: de una linea PAGA con varias unidades, convierte solo
     * $cantCortesia unidades en cortesia, dejando el resto pagado.
     *
     * Lo hace DIVIDIENDO la linea en dos filas de orden_detalle (misma
     * presentacion/nota): una paga (cortesia=0) y otra cortesia (cortesia=1,
     * subtotal=0). No cambia la cantidad TOTAL del producto en la orden.
     *
     * Clave para NO romper la cocina: la 'cantidad_enviada' se reparte entre
     * ambas filas de modo que su SUMA (y la suma de 'cantidad') se conserve;
     * asi enviarACocina calcula delta=0 y no genera ni adiciones ni anulaciones.
     *
     * Devuelve true en exito, o ['ok'=>false,'msg'=>...] si la validacion falla.
     */
    public function marcarCortesiaParcial($iddetalle, $cantCortesia)
    {
        $iddetalle    = (int)$iddetalle;
        $cantCortesia = (float)$cantCortesia;

        $row = ejecutarConsultaSimpleFila(
            "SELECT idorden, idproducto, idprecio, nombre, cantidad, cantidad_enviada, precio, nota, estado, cortesia
             FROM orden_detalle WHERE iddetalle='$iddetalle'");
        if (!$row) return false;

        $idorden  = (int)$row['idorden'];
        $cantTot  = (float)$row['cantidad'];

        // Validaciones
        if ($cantCortesia <= 0) {
            return ['ok' => false, 'msg' => 'Cantidad de cortesía inválida.'];
        }
        if ((int)$row['cortesia'] === 1) {
            return ['ok' => false, 'msg' => 'Esta línea ya es cortesía.'];
        }
        if ($cantCortesia > $cantTot) {
            return ['ok' => false, 'msg' => 'No puedes poner más unidades en cortesía que las de la línea (' . $this->fmtCant($cantTot) . ').'];
        }

        // Si son TODAS las unidades, es la cortesia de linea completa: se marca
        // y se fusiona con la cortesia existente (lo hace marcarCortesia).
        if ($cantCortesia >= $cantTot) {
            return $this->marcarCortesia($iddetalle, 1) ? true : ['ok' => false, 'msg' => 'No se pudo aplicar la cortesía.'];
        }

        // --- SPLIT PARCIAL ---
        global $conexion;
        $idproducto  = (int)$row['idproducto'];
        $idprecio    = ($row['idprecio'] !== null && $row['idprecio'] !== '') ? (int)$row['idprecio'] : null;
        $idprecioSql = $idprecio ? (int)$idprecio : 'NULL';
        $nombreEsc   = $conexion->real_escape_string((string)$row['nombre']);
        $precio      = (float)$row['precio'];
        $notaEsc     = $conexion->real_escape_string((string)$row['nota']);
        $estadoEsc   = $conexion->real_escape_string((string)$row['estado']); // heredar estado
        $cantEnv     = (float)$row['cantidad_enviada'];

        // Reparto de cantidad_enviada (conserva la suma => sin movimientos de cocina).
        $envCortesia = min($cantCortesia, $cantEnv);
        $envPaga     = $cantEnv - $envCortesia;

        $cantPaga    = $cantTot - $cantCortesia;
        $subPaga     = round($cantPaga * $precio, 2);

        // 1) La linea original queda PAGA con la cantidad restante.
        ejecutarConsulta(
            "UPDATE orden_detalle
             SET cantidad='$cantPaga', cantidad_enviada='$envPaga', subtotal='$subPaga'
             WHERE iddetalle='$iddetalle'");

        // 2) Crear la fila de cortesia y fusionarla con la cortesia existente (si
        //    la hay) para mantener UNA sola fila de cortesia por producto.
        $nuevoId = ejecutarConsulta_retornarID(
            "INSERT INTO orden_detalle
               (idorden, idproducto, idprecio, nombre, cantidad, cantidad_enviada, precio, subtotal, cortesia, nota, estado)
             VALUES
               ('$idorden','$idproducto',$idprecioSql,'$nombreEsc','$cantCortesia','$envCortesia','$precio',0,1,'$notaEsc','$estadoEsc')");
        $this->fusionarGemelo((int)$nuevoId);

        $this->recalcularTotales($idorden);
        return true;
    }

    public function eliminarItem($iddetalle)
    {
        $row = ejecutarConsultaSimpleFila("SELECT idorden, cantidad_enviada FROM orden_detalle WHERE iddetalle='$iddetalle'");
        if (!$row) return false;

        $idorden = (int)$row['idorden'];
        $cantEnv = (float)$row['cantidad_enviada'];

        if ($cantEnv > 0) {
            // Ya fue enviado a cocina: marcar para anulacion (no eliminar)
            ejecutarConsulta("UPDATE orden_detalle SET cantidad=0, subtotal=0 WHERE iddetalle='$iddetalle'");
        } else {
            // Nunca se envio: eliminar realmente
            ejecutarConsulta("DELETE FROM orden_detalle WHERE iddetalle='$iddetalle'");
        }
        $this->recalcularTotales($idorden);
        return true;
    }

    public function actualizarCabecera($idorden, $tipo, $observacion, $idcliente)
    {
        $idcliente = ($idcliente === '' || $idcliente === null) ? 'NULL' : "'$idcliente'";
        $sql = "UPDATE orden
                SET tipo='$tipo', observacion='$observacion', idcliente=$idcliente
                WHERE idorden='$idorden'";
        return ejecutarConsulta($sql);
    }

    public function enviarACocina($idorden)
    {
        // Calcula DOS delta-sets:
        //  - ADICIONES: items donde cantidad > cantidad_enviada (mas para cocinar)
        //  - ANULACIONES: items donde cantidad < cantidad_enviada (cancelar lo que ya esta en cocina)
        $idorden = (int)$idorden;

        // Atribuir la orden al turno (caja abierta) actual si no tiene sesión.
        // Así el "ACUMULADO DÍA" de la comanda cuenta correctamente, aunque la
        // orden se haya creado antes de abrir la caja.
        $sesAb = ejecutarConsultaSimpleFila("SELECT idsesion FROM caja_sesion WHERE abierta=1 ORDER BY idsesion DESC LIMIT 1");
        if ($sesAb && !empty($sesAb['idsesion'])) {
            $sid = (int)$sesAb['idsesion'];
            ejecutarConsulta("UPDATE orden SET idsesion='$sid' WHERE idorden='$idorden' AND (idsesion IS NULL OR idsesion=0)");
        }

        // --- Adiciones ---
        $rsAdd = ejecutarConsulta(
            "SELECT iddetalle, (cantidad - cantidad_enviada) AS delta
             FROM orden_detalle
             WHERE idorden='$idorden'
               AND estado IN ('pendiente','en_preparacion')
               AND cantidad > cantidad_enviada"
        );
        $adiciones = []; // [iddetalle => delta]
        while ($r = $rsAdd->fetch_assoc()) {
            $dq = (float)$r['delta'];
            if ($dq > 0) $adiciones[(int)$r['iddetalle']] = $dq;
        }

        // --- Anulaciones ---
        $rsCancel = ejecutarConsulta(
            "SELECT iddetalle, (cantidad_enviada - cantidad) AS delta
             FROM orden_detalle
             WHERE idorden='$idorden'
               AND cantidad < cantidad_enviada
               AND cantidad_enviada > 0"
        );
        $anulaciones = []; // [iddetalle => delta]
        while ($r = $rsCancel->fetch_assoc()) {
            $dq = (float)$r['delta'];
            if ($dq > 0) $anulaciones[(int)$r['iddetalle']] = $dq;
        }

        if (empty($adiciones) && empty($anulaciones)) {
            return ['adiciones' => [], 'anulaciones' => []];
        }

        // Cabecera y estados de detalle
        ejecutarConsulta("UPDATE orden SET estado='enviada' WHERE idorden='$idorden'");
        ejecutarConsulta("UPDATE orden_detalle
                          SET estado='en_preparacion'
                          WHERE idorden='$idorden' AND estado='pendiente'");

        // Sincronizar cantidad_enviada = cantidad para todos los items modificados
        $idsAll = array_unique(array_merge(array_keys($adiciones), array_keys($anulaciones)));
        if (!empty($idsAll)) {
            $inIds = implode(',', $idsAll);
            ejecutarConsulta("UPDATE orden_detalle
                              SET cantidad_enviada = cantidad
                              WHERE idorden='$idorden' AND iddetalle IN ($inIds)");
        }

        // Anulacion completa enviada: NO borrar la fila (la comanda de anulacion
        // todavia necesita leer el producto para imprimirlo). Se marca como
        // 'anulado' (queda con cantidad=0). Todos los totales/stock/reportes ya
        // excluyen estado='anulado', y el panel oculta las filas con cantidad=0.
        ejecutarConsulta("UPDATE orden_detalle
                          SET estado='anulado'
                          WHERE idorden='$idorden' AND cantidad=0 AND cantidad_enviada=0");

        // Encolar trabajos para el bridge de impresion (impresora de cocina)
        $this->encolarComandas($idorden, $adiciones, $anulaciones);

        return [
            'adiciones'   => $adiciones,
            'anulaciones' => $anulaciones,
        ];
    }

    /**
     * Encola comandas de cocina (adiciones y anulaciones) para que el bridge
     * local las imprima. Solo encola si existe una impresora con tipo='cocina'
     * y activa. Si no, no hace nada (no rompe el flujo).
     */
    private function encolarComandas($idorden, array $adiciones, array $anulaciones)
    {
        // Cargar modelo de impresora (lazy require para no romper si la tabla no existe)
        $modelo = __DIR__ . '/Impresora.php';
        if (!file_exists($modelo)) return;
        require_once $modelo;
        $imp = new Impresora();
        $impCocina = $imp->buscarPorTipo('cocina');
        if (!$impCocina) return; // sin impresora configurada, salimos sin error

        // Cabecera de la orden (mesa, mozo, etc.)
        $cab = $this->mostrarCabecera($idorden);
        if (!$cab) return;

        // Etiqueta amigable del tipo de orden para la comanda
        $tipoLabels = [
            'dine_in' => 'LOCAL', 'mesa' => 'LOCAL', 'local' => 'LOCAL',
            'para_llevar' => 'PARA LLEVAR', 'llevar' => 'PARA LLEVAR', 'takeaway' => 'PARA LLEVAR',
            'delivery' => 'DELIVERY',
        ];
        $tipoKey   = strtolower(trim((string)($cab['tipo'] ?? '')));
        $tipoLabel = $tipoLabels[$tipoKey] ?? strtoupper(str_replace('_', ' ', $tipoKey ?: 'ORDEN'));

        // Nombre del mozo: el del usuario que atiende la orden; si no, el campo libre.
        $mozoNombre = trim((string)($cab['propietario_nombre'] ?? '')) ?: ($cab['mozo'] ?? '');

        // Acumulado de consumo del TURNO (se calcula DESPUÉS de sincronizar
        // cantidad_enviada, así que ya refleja adiciones y anulaciones de este envío).
        $acum = $this->acumuladoCocinaTurno((int)($cab['idsesion'] ?? 0));

        $infoBase = [
            'numero_orden' => $cab['numero']        ?? '',
            'mesa'         => $cab['mesa_numero'] ? ('Mesa ' . $cab['mesa_numero']) : $tipoLabel,
            'tipo_label'   => $tipoLabel,
            'mozo'         => $mozoNombre,
            'observacion'  => $cab['observacion']   ?? '',
            'fecha'        => date('d/m/Y H:i'),
            'acum_comida'  => $acum['comida'],
            'acum_bebida'  => $acum['bebida'],
        ];

        // Helper local: construye items + contadores "de esta comanda" (comida/bebida)
        $construir = function ($mapa) use ($idorden) {
            $items = []; $cComida = 0; $cBebida = 0;
            $rs = ejecutarConsulta(
                "SELECT d.iddetalle, d.nombre, d.nota, d.cortesia, c.nombre AS categoria_nombre, c.mostrar_comanda
                 FROM orden_detalle d
                 LEFT JOIN producto p ON p.idproducto = d.idproducto
                 LEFT JOIN categoria c ON c.idcategoria = p.idcategoria
                 WHERE d.idorden='$idorden' AND d.iddetalle IN (" . implode(',', array_keys($mapa)) . ")"
            );
            while ($r = $rs->fetch_assoc()) {
                // Categoría configurada para NO mostrarse en comanda: se omite del
                // papel de cocina (igual se cobra y descuenta stock). NULL = mostrar.
                $mc = $r['mostrar_comanda'];
                if ($mc !== null && (int)$mc === 0) continue;
                $id   = (int)$r['iddetalle'];
                $cant = (float)$mapa[$id];
                $cat  = (string)($r['categoria_nombre'] ?? '');
                $nom  = (string)($r['nombre'] ?? '');
                $esTaper  = (stripos($cat, 'TAPER') !== false) || (stripos($nom, 'TAPER') !== false);
                $esBebida = (stripos($cat, 'BEBIDA') !== false);
                // Los tapers NO suman a comida ni bebida (igual aparecen en la comanda).
                if ($esTaper)      { /* no cuenta */ }
                elseif ($esBebida) $cBebida += $cant;
                else               $cComida += $cant;
                $items[] = [
                    'cantidad' => $mapa[$id],
                    'nombre'   => $r['nombre'],
                    'nota'     => $r['nota'] ?: '',
                    'cortesia' => (int)$r['cortesia'],
                ];
            }
            return ['items' => $items, 'comida' => $cComida, 'bebida' => $cBebida];
        };

        // ADICIONES (solo si quedan items visibles tras el filtro por categoría)
        if (!empty($adiciones)) {
            $b = $construir($adiciones);
            if (!empty($b['items'])) {
                $payload = $infoBase + ['items' => $b['items'], 'esta_comida' => $b['comida'], 'esta_bebida' => $b['bebida']];
                $imp->encolar((int)$impCocina['idimpresora'], 'comanda', $payload, $idorden);
            }
        }

        // ANULACIONES (idem: no imprimir comanda de anulación vacía)
        if (!empty($anulaciones)) {
            $b = $construir($anulaciones);
            if (!empty($b['items'])) {
                $payload = $infoBase + ['items' => $b['items'], 'esta_comida' => $b['comida'], 'esta_bebida' => $b['bebida']];
                $imp->encolar((int)$impCocina['idimpresora'], 'comanda_anular', $payload, $idorden);
            }
        }
    }

    // Devuelve los items con iddetalle en la lista pasada (para impresion de comanda delta).
    // Si $deltas es no-vacio, devuelve la 'cantidad' como la cantidad DELTA, no la total.
    public function detallesPorIds($idorden, array $ids, array $deltas = [])
    {
        $idorden = (int)$idorden;
        $ids = array_map('intval', $ids);
        if (empty($ids)) return [];
        $inIds = implode(',', $ids);
        $sql = "SELECT d.*, p.imagen AS producto_imagen, c.nombre AS categoria_nombre, c.mostrar_comanda
                FROM orden_detalle d
                LEFT JOIN producto p ON p.idproducto = d.idproducto
                LEFT JOIN categoria c ON c.idcategoria = p.idcategoria
                WHERE d.idorden='$idorden' AND d.iddetalle IN ($inIds)
                ORDER BY d.iddetalle ASC";
        $rs = ejecutarConsulta($sql);
        $out = [];
        while ($r = $rs->fetch_assoc()) {
            // Si nos pasaron un delta para este detalle, sobreescribimos cantidad
            $idd = (int)$r['iddetalle'];
            if (isset($deltas[$idd])) {
                $r['cantidad_total']    = $r['cantidad'];   // referencia opcional
                $r['cantidad']          = $deltas[$idd];    // mostrar solo el delta
                $r['_es_delta']         = true;
            }
            $out[] = $r;
        }
        return $out;
    }

    public function cobrar($idorden, $metodoPago, $idsesion, $tipoComprobante = 'ticket', $montoRecibido = 0, $pagoReferencia = '', $pagoMetadata = '', $imprimirComprobante = false)
    {
        $totales = $this->recalcularTotales($idorden);
        $orden   = $this->mostrarCabecera($idorden);
        if (!$orden) return false;

        // Validar metodo de pago (incluye plin y mixto)
        $metodoPago = in_array($metodoPago, ['efectivo','tarjeta','yape','plin','transferencia','mixto'], true)
            ? $metodoPago : 'efectivo';

        $total  = (float)$totales['total'];
        $vuelto = 0;
        if ($metodoPago === 'efectivo' && $montoRecibido > 0) {
            $vuelto = max(0, round($montoRecibido - $total, 2));
        } else {
            // Para no-efectivo, monto_recibido = total
            $montoRecibido = $total;
        }

        // Escapar metadata JSON
        global $conexion;
        $metaEsc = $conexion->real_escape_string($pagoMetadata);
        // Aceptar todos los tipos validos (ticket, nota_venta, boleta, factura)
        $tipoComp = in_array($tipoComprobante, ['ticket','nota_venta','boleta','factura'], true)
            ? $tipoComprobante
            : 'ticket';

        ejecutarConsulta("UPDATE orden SET
                            estado='pagada',
                            metodo_pago='$metodoPago',
                            tipo_comprobante='$tipoComp',
                            monto_recibido='$montoRecibido',
                            vuelto='$vuelto',
                            pago_referencia='$pagoReferencia',
                            pago_metadata='$metaEsc',
                            fecha_pago=NOW()
                          WHERE idorden='$idorden'");

        // Liberar mesa si tenia
        if (!empty($orden['idmesa'])) {
            ejecutarConsulta("UPDATE mesa SET estado='libre' WHERE idmesa='" . $orden['idmesa'] . "'");
        }

        // Registrar movimiento(s) de caja.
        // Para pago MIXTO/combinado: registrar una linea por cada parte con su
        // metodo real (efectivo, yape, plin...), asi el arqueo separa cada uno.
        if ($idsesion) {
            $numEsc = $conexion->real_escape_string($orden['numero']);
            $partes = [];
            if ($metodoPago === 'mixto') {
                $meta = json_decode($pagoMetadata, true) ?: [];
                // Estructura esperada: { partes: [ {metodo:'efectivo', monto:30}, {metodo:'yape', monto:24} ] }
                if (!empty($meta['partes']) && is_array($meta['partes'])) {
                    foreach ($meta['partes'] as $p) {
                        $m   = in_array($p['metodo'] ?? '', ['efectivo','yape','plin','tarjeta','transferencia'], true) ? $p['metodo'] : 'efectivo';
                        $mon = round((float)($p['monto'] ?? 0), 2);
                        if ($mon > 0) $partes[] = ['metodo' => $m, 'monto' => $mon];
                    }
                }
            }
            if (empty($partes)) {
                // Pago simple: una sola linea. Si fue efectivo con vuelto, el monto
                // a caja es el total (no lo recibido), igual que antes.
                $partes[] = ['metodo' => $metodoPago, 'monto' => $total];
            }
            $esMixto = ($metodoPago === 'mixto');
            foreach ($partes as $p) {
                $mEsc = $conexion->real_escape_string($p['metodo']);
                $mon  = $p['monto'];
                // En pago mixto, la nota deja claro que esta linea es una parte del total.
                $nota = $esMixto
                    ? "Cobro orden #$numEsc - MIXTO (S/ " . number_format($mon, 2) . " de S/ " . number_format($total, 2) . ")"
                    : "Cobro orden #$numEsc";
                $notaEsc = $conexion->real_escape_string($nota);
                ejecutarConsulta(
                    "INSERT INTO caja_movimiento (idsesion, tipo, monto, nota, metodo_pago, idorden)
                     VALUES ('$idsesion','venta','$mon','$notaEsc','$mEsc','$idorden')");
            }
        }

        // Actualizar totales del cliente
        if (!empty($orden['idcliente'])) {
            $idcliente = (int)$orden['idcliente'];
            ejecutarConsulta("UPDATE cliente
                              SET total_ordenes = total_ordenes + 1,
                                  total_gastado = total_gastado + $total,
                                  ultima_visita = CURDATE()
                              WHERE idcliente='$idcliente'");
        }

        // Descontar inventario por PRESENTACION (idprecio) vendida.
        // Va al final y protegido: si algo falla NUNCA invalida el cobro.
        try {
            $idu = $_SESSION['idusuario'] ?? null;
            $rsDet = ejecutarConsulta(
                "SELECT d.idprecio, SUM(d.cantidad) AS cant
                 FROM orden_detalle d
                 JOIN producto_precio pp ON pp.idprecio = d.idprecio AND pp.controla_stock = 1
                 WHERE d.idorden='$idorden' AND d.idprecio IS NOT NULL AND d.estado <> 'anulado'
                 GROUP BY d.idprecio"
            );
            if ($rsDet) {
                require_once __DIR__ . "/Producto.php";
                $prodModel = new Producto();
                while ($d = $rsDet->fetch_assoc()) {
                    $prodModel->descontarPorVenta((int)$d['idprecio'], (float)$d['cant'], $idorden, $idu);
                }
            }
        } catch (\Throwable $e) {
            error_log('[INVENTARIO] descuento venta orden ' . $idorden . ': ' . $e->getMessage());
        }

        // Encolar impresión del comprobante por el BRIDGE — SOLO si se solicitó
        // (lo pide la app MÓVIL al cobrar). En laptop/PC NO se encola: ahí se
        // imprime por el navegador como siempre (no se cambia esa lógica, ni se
        // duplica el ticket).
        if ($imprimirComprobante) {
            $this->encolarComprobante($idorden);
        }

        return true;
    }

    /**
     * Encola la impresión del comprobante (boleta/factura/nota) por el Bridge.
     * Usa la MISMA ticketera: busca tipo 'caja'; si no hay, usa 'cocina'; si
     * tampoco, la primera impresora activa. El Bridge lo renderiza por URL con
     * su diseño exacto. Protegido: si falla, NUNCA invalida el cobro.
     */
    private function encolarComprobante($idorden)
    {
        try {
            $modelo = __DIR__ . '/Impresora.php';
            if (!file_exists($modelo)) return;
            require_once $modelo;
            $imp = new Impresora();
            $impr = $imp->buscarPorTipo('caja');
            if (!$impr) $impr = $imp->buscarPorTipo('cocina');
            if (!$impr) {
                // Cualquier impresora activa (la misma ticketera del local)
                $impr = ejecutarConsultaSimpleFila("SELECT * FROM impresora WHERE activa=1 ORDER BY idimpresora ASC LIMIT 1");
            }
            if (!$impr) return;   // sin impresora, no imprime (sin error)
            $imp->encolar((int)$impr['idimpresora'], 'comprobante', ['idorden' => (int)$idorden], $idorden);
        } catch (\Throwable $e) {
            error_log('[COMPROBANTE PRINT] orden ' . $idorden . ': ' . $e->getMessage());
        }
    }

    /**
     * Encola la impresion del comprobante por el bridge DESPUES del cobro.
     * Se usa en movil cuando el cajero elige "Sí, imprimir" para boleta/factura:
     * primero se genera el comprobante electronico y recien aqui se imprime, asi
     * el ticket sale con su numero fiscal. Solo para ordenes ya pagadas.
     * No invalida nada: si no hay impresora, simplemente no imprime.
     */
    public function imprimirComprobante($idorden)
    {
        $idorden = (int)$idorden;
        $orden = $this->mostrarCabecera($idorden);
        if (!$orden) return false;
        if (($orden['estado'] ?? '') !== 'pagada') return false; // solo cobradas
        $this->encolarComprobante($idorden);
        return true;
    }

    public function anular($idorden)
    {
        $orden = $this->mostrarCabecera($idorden);
        if (!$orden) return false;

        ejecutarConsulta("UPDATE orden SET estado='anulada' WHERE idorden='$idorden'");

        if (!empty($orden['idmesa'])) {
            ejecutarConsulta("UPDATE mesa SET estado='libre' WHERE idmesa='" . $orden['idmesa'] . "'");
        }
        return true;
    }

    public function mostrarCabecera($idorden)
    {
        $sql = "SELECT o.*, m.numero AS mesa_numero, c.nombre AS cliente_nombre,
                       TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellidos,''))) AS propietario_nombre,
                       u.login AS propietario_login,
                       r.codigo AS propietario_rol
                FROM orden o
                LEFT JOIN mesa    m ON m.idmesa = o.idmesa
                LEFT JOIN cliente c ON c.idcliente = o.idcliente
                LEFT JOIN usuario u ON u.idusuario = o.idusuario
                LEFT JOIN rol     r ON r.idrol = u.idrol
                WHERE o.idorden='$idorden'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function mostrarDetalle($idorden)
    {
        // Excluye items anulados (cancelados) para que no aparezcan en el
        // comprobante/boleta ni en el panel de la orden. La comanda de anulación
        // usa detallesPorIds() aparte, que sí los recupera por iddetalle.
        $sql = "SELECT d.*, p.imagen AS producto_imagen, c.nombre AS categoria_nombre, c.mostrar_comanda
                FROM orden_detalle d
                LEFT JOIN producto p ON p.idproducto = d.idproducto
                LEFT JOIN categoria c ON c.idcategoria = p.idcategoria
                WHERE d.idorden='$idorden' AND d.estado <> 'anulado'
                ORDER BY d.iddetalle ASC";
        return ejecutarConsulta($sql);
    }

    /**
     * Acumulado de CONSUMO enviado a cocina en el TURNO (sesión de caja):
     * cuántas COMIDAS y cuántas BEBIDAS se han mandado a cocina (vigentes,
     * ya descontadas las anulaciones). Bebida = categoría que contiene "BEBIDA".
     * Los TAPERS (categoría/nombre con "TAPER") NO cuentan ni como comida ni
     * como bebida (son envases; igual van en la comanda y suman a caja).
     * Incluye cortesías (también se cocinan).
     */
    public function acumuladoCocinaTurno($idsesion)
    {
        $idsesion = (int)$idsesion;
        // Si la orden no trae sesión válida, usar la caja abierta actual (turno vigente).
        if ($idsesion <= 0) {
            $ses = ejecutarConsultaSimpleFila("SELECT idsesion FROM caja_sesion WHERE abierta=1 ORDER BY idsesion DESC LIMIT 1");
            $idsesion = (int)($ses['idsesion'] ?? 0);
        }
        if ($idsesion <= 0) return ['comida' => 0, 'bebida' => 0];
        $row = ejecutarConsultaSimpleFila(
            "SELECT
                COALESCE(SUM(CASE WHEN (UPPER(c.nombre) LIKE '%TAPER%' OR UPPER(p.nombre) LIKE '%TAPER%') THEN 0
                                      WHEN UPPER(c.nombre) LIKE '%BEBIDA%' THEN d.cantidad_enviada ELSE 0 END),0) AS bebida,
                COALESCE(SUM(CASE WHEN (UPPER(c.nombre) LIKE '%TAPER%' OR UPPER(p.nombre) LIKE '%TAPER%') THEN 0
                                      WHEN UPPER(c.nombre) LIKE '%BEBIDA%' THEN 0 ELSE d.cantidad_enviada END),0) AS comida
             FROM orden_detalle d
             JOIN orden o     ON o.idorden = d.idorden
             JOIN producto p  ON p.idproducto = d.idproducto
             LEFT JOIN categoria c ON c.idcategoria = p.idcategoria
             WHERE o.idsesion = '$idsesion' AND o.estado <> 'anulada' AND d.cantidad_enviada > 0"
        );
        return [
            'comida' => (float)($row['comida'] ?? 0),
            'bebida' => (float)($row['bebida'] ?? 0),
        ];
    }

    public function mostrarCompleta($idorden)
    {
        $cab = $this->mostrarCabecera($idorden);
        if (!$cab) return null;

        $det = $this->mostrarDetalle($idorden);
        $items = [];
        while ($r = $det->fetch_assoc()) { $items[] = $r; }

        $cab['items'] = $items;
        return $cab;
    }

    public function porMesa($idmesa, $estado = 'en_curso')
    {
        $sql = "SELECT * FROM orden
                WHERE idmesa='$idmesa' AND estado='$estado'
                ORDER BY idorden DESC LIMIT 1";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listar($estado = '', $tipo = '', $desde = '', $hasta = '')
    {
        $where = " 1=1 ";
        if ($estado !== '') $where .= " AND o.estado='$estado' ";
        if ($tipo   !== '') $where .= " AND o.tipo='$tipo' ";
        if ($desde  !== '') $where .= " AND DATE(o.fecha) >= '$desde' ";
        if ($hasta  !== '') $where .= " AND DATE(o.fecha) <= '$hasta' ";

        $sql = "SELECT o.*, m.numero AS mesa_numero, c.nombre AS cliente_nombre,
                       (SELECT COUNT(*) FROM orden_detalle d WHERE d.idorden=o.idorden) AS total_items
                FROM orden o
                LEFT JOIN mesa    m ON m.idmesa = o.idmesa
                LEFT JOIN cliente c ON c.idcliente = o.idcliente
                WHERE $where
                ORDER BY o.idorden DESC";
        return ejecutarConsulta($sql);
    }

    // ---------- DataTables server-side ----------

    public function listarServerSide($start, $length, $search, $orderCol, $orderDir, $filtros = [])
    {
        $cols = ['o.idorden','o.numero','o.fecha','o.fecha_pago','o.tipo','m.numero','total_items','o.metodo_pago','o.total','o.estado'];
        $orderCol = isset($cols[(int)$orderCol]) ? $cols[(int)$orderCol] : 'o.idorden';
        $orderDir = strtolower($orderDir) === 'asc' ? 'ASC' : 'DESC';
        $start    = max(0, (int)$start);
        $length   = max(1, min(500, (int)$length));

        $where = $this->buildWhereServerSide($search, $filtros);

        $sql = "SELECT o.*, m.numero AS mesa_numero, c.nombre AS cliente_nombre,
                       (SELECT COUNT(*) FROM orden_detalle d WHERE d.idorden=o.idorden) AS total_items,
                       ce.serie AS ce_serie, ce.numero AS ce_numero,
                       ce.numero_completo AS ce_numero_completo, ce.estado AS ce_estado
                FROM orden o
                LEFT JOIN mesa    m ON m.idmesa = o.idmesa
                LEFT JOIN cliente c ON c.idcliente = o.idcliente
                LEFT JOIN comprobante_electronico ce ON ce.idorden = o.idorden
                WHERE $where
                ORDER BY $orderCol $orderDir
                LIMIT $start, $length";
        return ejecutarConsulta($sql);
    }

    public function contarServerSide($search, $filtros = [], $useFilter = true)
    {
        $where = $useFilter
            ? $this->buildWhereServerSide($search, $filtros)
            : $this->buildWhereServerSide('', $filtros);
        $sql = "SELECT COUNT(*) AS total
                FROM orden o
                LEFT JOIN mesa    m ON m.idmesa = o.idmesa
                LEFT JOIN cliente c ON c.idcliente = o.idcliente
                WHERE $where";
        $row = ejecutarConsultaSimpleFila($sql);
        return (int)$row['total'];
    }

    private function buildWhereServerSide($search, $filtros)
    {
        $where = " 1=1 ";
        $estado     = $filtros['estado']      ?? '';
        $tipo       = $filtros['tipo']        ?? '';
        $metodo     = $filtros['metodo_pago'] ?? '';
        $comprobante= $filtros['comprobante'] ?? '';
        $rangoFecha = $filtros['rango']       ?? ''; // hoy / semana / mes
        $desde      = $filtros['desde']       ?? '';
        $hasta      = $filtros['hasta']       ?? '';

        if ($estado !== '') $where .= " AND o.estado='$estado' ";
        if ($tipo   !== '') $where .= " AND o.tipo='$tipo' ";
        if ($metodo !== '') $where .= " AND o.metodo_pago='$metodo' ";
        if ($comprobante !== '' && in_array($comprobante, ['ticket','nota_venta','boleta','factura'], true)) {
            $where .= " AND o.tipo_comprobante='$comprobante' ";
        }

        if ($rangoFecha === 'hoy') {
            $where .= " AND DATE(o.fecha_pago) = CURDATE() ";
        } elseif ($rangoFecha === 'semana') {
            $where .= " AND o.fecha_pago >= DATE_SUB(NOW(), INTERVAL 7 DAY) ";
        } elseif ($rangoFecha === 'mes') {
            $where .= " AND MONTH(o.fecha_pago)=MONTH(CURDATE()) AND YEAR(o.fecha_pago)=YEAR(CURDATE()) ";
        }
        // Solo se aceptan fechas con formato YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $where .= " AND DATE(o.fecha_pago) >= '$desde' ";
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $where .= " AND DATE(o.fecha_pago) <= '$hasta' ";

        if ($search !== '') {
            $s = $search;
            $where .= " AND (o.numero LIKE '%$s%' OR c.nombre LIKE '%$s%' OR m.numero LIKE '%$s%') ";
        }
        return $where;
    }

    // Resumen para dashboard / reportes
    public function ventasDelDia($fecha = '')
    {
        $fecha = $fecha ?: date('Y-m-d');
        $sql = "SELECT
                    COUNT(*) AS total_ordenes,
                    COALESCE(SUM(total),0) AS total_ventas,
                    COALESCE(AVG(total),0) AS ticket_promedio
                FROM orden
                WHERE estado='pagada' AND DATE(fecha_pago)='$fecha'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function ventasPorMetodo($fecha = '')
    {
        $fecha = $fecha ?: date('Y-m-d');
        $sql = "SELECT metodo_pago, COUNT(*) AS cantidad, COALESCE(SUM(total),0) AS total
                FROM orden
                WHERE estado='pagada' AND DATE(fecha_pago)='$fecha'
                GROUP BY metodo_pago";
        return ejecutarConsulta($sql);
    }

    /**
     * Conteo y total separado por tipo de comprobante (ticket, nota_venta, boleta, factura)
     * para un rango de fechas. Util para reportes.
     */
    public function ventasPorTipoComprobante($desde = '', $hasta = '')
    {
        $where = " estado='pagada' ";
        if ($desde !== '') $where .= " AND DATE(fecha_pago) >= '" . addslashes($desde) . "' ";
        if ($hasta !== '') $where .= " AND DATE(fecha_pago) <= '" . addslashes($hasta) . "' ";

        $sql = "SELECT tipo_comprobante,
                       COUNT(*)                AS cantidad,
                       COALESCE(SUM(total),0)  AS total,
                       COALESCE(SUM(igv),0)    AS igv,
                       COALESCE(SUM(subtotal),0) AS subtotal
                FROM orden
                WHERE $where
                GROUP BY tipo_comprobante";
        return ejecutarConsulta($sql);
    }

    /**
     * Listado completo de ventas pagadas con todos los datos para reporte/Excel.
     */
    public function ventasDetalle($desde = '', $hasta = '')
    {
        $where = " o.estado='pagada' ";
        if ($desde !== '') $where .= " AND DATE(o.fecha_pago) >= '" . addslashes($desde) . "' ";
        if ($hasta !== '') $where .= " AND DATE(o.fecha_pago) <= '" . addslashes($hasta) . "' ";

        $sql = "SELECT o.idorden, o.numero, o.fecha_pago, o.tipo, o.tipo_comprobante,
                       o.metodo_pago, o.subtotal, o.igv, o.total, o.monto_recibido, o.vuelto,
                       o.mozo, o.observacion,
                       m.numero AS mesa_numero,
                       c.nombre AS cliente_nombre,
                       c.documento AS cliente_documento,
                       (SELECT COUNT(*) FROM orden_detalle d WHERE d.idorden=o.idorden) AS total_items,
                       ce.serie AS ce_serie, ce.numero AS ce_numero, ce.numero_completo AS ce_numero_completo,
                       ce.estado AS ce_estado, ce.cliente_num_doc AS ce_cliente_doc, ce.cliente_razon AS ce_cliente_razon
                FROM orden o
                LEFT JOIN mesa m    ON m.idmesa = o.idmesa
                LEFT JOIN cliente c ON c.idcliente = o.idcliente
                LEFT JOIN comprobante_electronico ce ON ce.idorden = o.idorden
                WHERE $where
                ORDER BY o.fecha_pago DESC";
        return ejecutarConsulta($sql);
    }

    public function ventasUltimosDias($dias = 7)
    {
        $dias = (int)$dias;
        $sql = "SELECT DATE(fecha_pago) AS fecha,
                       COUNT(*) AS total_ordenes,
                       COALESCE(SUM(total),0) AS total_ventas
                FROM orden
                WHERE estado='pagada'
                  AND fecha_pago >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)
                GROUP BY DATE(fecha_pago)
                ORDER BY fecha ASC";
        return ejecutarConsulta($sql);
    }
}
?>
