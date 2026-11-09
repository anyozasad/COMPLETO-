<?php
class Venta {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Registra una venta completa en una sola transacción atómica:
     * 1. Inserta la cabecera en `ventas`
     * 2. Inserta cada línea en `detalle_ventas`
     * 3. Reduce el stock de cada producto vendido
     * 4. Suma el total a `cajas.total_ventas`
     */
    public function registrarVenta($caja_id, $socio_id, $total, $metodo_pago, $carrito, $descuento = 0) {
        // El importe y el stock se verifican contra MySQL en la misma transacción.
        if (empty($carrito) || !in_array($metodo_pago, ['efectivo', 'tarjeta', 'transferencia'], true) ||
            !is_numeric($descuento) || $descuento < 0 || !is_numeric($total) || $total < 0 ||
            (int)$caja_id < 1) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            // Se bloquea la caja para no registrar ventas en cajas ya cerradas.
            $stmtCaja = $this->conn->prepare("SELECT id, estado FROM cajas WHERE id=:id FOR UPDATE");
            $stmtCaja->execute(['id' => (int)$caja_id]);
            $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
            if (!$caja || $caja['estado'] !== 'abierta') {
                throw new RuntimeException('La caja ya no está abierta.');
            }

            // Se bloquean los productos antes de reducir el inventario.
            $consultaProducto = $this->conn->prepare(
                "SELECT id, precio_venta, stock, estado FROM productos WHERE id=:id FOR UPDATE"
            );
            $lineas = [];
            $totalBruto = 0.0;
            foreach ($carrito as $item) {
                $productoId = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
                $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT);
                if ($productoId === false || $productoId < 1 || $cantidad === false || $cantidad < 1) {
                    throw new RuntimeException('Cantidad o producto inválido.');
                }
                $consultaProducto->execute(['id' => $productoId]);
                $producto = $consultaProducto->fetch(PDO::FETCH_ASSOC);
                if (!$producto || $producto['estado'] !== 'activo' || $producto['stock'] < $cantidad) {
                    throw new RuntimeException('Producto agotado o inactivo.');
                }
                $precio = (float)$producto['precio_venta'];
                $subtotal = round($precio * $cantidad, 2);
                $lineas[] = [
                    'id' => (int)$productoId,
                    'cantidad' => (int)$cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal
                ];
                $totalBruto += $subtotal;
            }

            $importeFinal = round($totalBruto - (float)$descuento, 2);
            if ($importeFinal < 0 || abs($importeFinal - (float)$total) > 0.01) {
                throw new RuntimeException('El total no coincide con los productos actuales.');
            }

            $socioFinal = $socio_id === null || $socio_id === '' ? null : (int)$socio_id;
            if ($socioFinal !== null && $socioFinal < 1) {
                throw new RuntimeException('Socio inválido.');
            }
            $stmt = $this->conn->prepare(
                "INSERT INTO ventas (caja_id, socio_id, total, descuento, metodo_pago)
                 VALUES (:caja, :socio, :total, :descuento, :pago)"
            );
            $stmt->execute([
                'caja' => (int)$caja_id,
                'socio' => $socioFinal,
                'total' => $importeFinal,
                'descuento' => (float)$descuento,
                'pago' => $metodo_pago
            ]);
            $ventaId = (int)$this->conn->lastInsertId();

            $detalle = $this->conn->prepare(
                "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal)
                 VALUES (:venta, :producto, :cantidad, :precio, :subtotal)"
            );
            $reducir = $this->conn->prepare(
                "UPDATE productos SET stock=stock-:cantidad
                 WHERE id=:id AND stock >= :minimo"
            );
            foreach ($lineas as $linea) {
                $detalle->execute([
                    'venta' => $ventaId,
                    'producto' => $linea['id'],
                    'cantidad' => $linea['cantidad'],
                    'precio' => $linea['precio'],
                    'subtotal' => $linea['subtotal']
                ]);
                $reducir->execute([
                    'cantidad' => $linea['cantidad'],
                    'id' => $linea['id'],
                    'minimo' => $linea['cantidad']
                ]);
                if ($reducir->rowCount() !== 1) {
                    throw new RuntimeException('No hay stock suficiente.');
                }
            }

            $stmtTotal = $this->conn->prepare(
                "UPDATE cajas SET total_ventas=total_ventas+:total
                 WHERE id=:id AND estado='abierta'"
            );
            $stmtTotal->execute(['total' => $importeFinal, 'id' => (int)$caja_id]);
            if ($stmtTotal->rowCount() !== 1) {
                throw new RuntimeException('No se pudo actualizar la caja.');
            }

            $this->conn->commit();
            return $ventaId;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Error al procesar venta: ' . $e->getMessage());
            return false;
        }
    }

    // Historial filtrable con socios y cajero
    public function obtenerHistorialFiltrado($desde, $hasta, $busqueda = '') {
        $sql = "SELECT v.id, v.fecha, v.total, v.descuento, v.metodo_pago,
                       u.nombre as cajero,
                       IFNULL(s.nombre, 'Cliente General') as cliente
                FROM ventas v
                INNER JOIN cajas c ON v.caja_id = c.id
                INNER JOIN usuarios u ON c.usuario_id = u.id
                LEFT JOIN socios s ON v.socio_id = s.id
                WHERE DATE(v.fecha) BETWEEN :desde AND :hasta";
        if (!empty($busqueda)) {
            $sql .= " AND (s.nombre LIKE :busqueda OR u.nombre LIKE :busqueda2)";
        }
        $sql .= " ORDER BY v.fecha DESC LIMIT 200";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':desde', $desde);
        $stmt->bindParam(':hasta', $hasta);
        if (!empty($busqueda)) {
            $like = '%' . $busqueda . '%';
            $stmt->bindParam(':busqueda', $like);
            $stmt->bindParam(':busqueda2', $like);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Detalle de ítems de una venta
    public function obtenerDetalle($venta_id) {
        $stmt = $this->conn->prepare(
            "SELECT dv.cantidad, dv.precio_unitario, dv.subtotal, p.nombre
             FROM detalle_ventas dv
             INNER JOIN productos p ON dv.producto_id = p.id
             WHERE dv.venta_id = :id"
        );
        $stmt->bindParam(':id', $venta_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener historial resumido de ventas por fecha
    public function obtenerHistorial($limite = 50) {
        $stmt = $this->conn->prepare("SELECT v.*, u.nombre as cajero 
                                      FROM ventas v 
                                      INNER JOIN cajas c ON v.caja_id = c.id 
                                      INNER JOIN usuarios u ON c.usuario_id = u.id 
                                      ORDER BY v.fecha DESC LIMIT :limite");
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
