<?php
/**
 * Revisión de datos de la semana 05.
 * Solo lectura: compara registros reales de MySQL, sin modificar socios o pagos.
 *
 * Importante: "ventas" incluye cobros del punto de venta; una suscripción NO
 * supone un pago recibido, porque el esquema actual no tiene tabla de abonos
 * específica para suscripciones.
 */
class RevisionSemana05 {
    private PDO $conn;

    public function __construct() {
        $this->conn = (new Database())->getConnection();
        if (!$this->conn) throw new RuntimeException('Conexión MySQL no disponible.');
    }

    public function resumen(): array {
        $sql = "SELECT
          (SELECT COUNT(*) FROM socios) AS socios_total,
          (SELECT COUNT(*) FROM socios WHERE estado='activo') AS socios_activos,
          (SELECT COUNT(*) FROM suscripciones) AS suscripciones_total,
          (SELECT COUNT(*) FROM ventas) AS ventas_total,
          (SELECT COALESCE(SUM(total),0) FROM ventas) AS monto_ventas,
          (SELECT COUNT(*) FROM productos) AS productos_total,
          (SELECT COUNT(*) FROM productos WHERE foto IS NULL OR foto='') AS productos_sin_foto";
        return $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public function pagosVsSocios(int $limite = 100): array {
        $limite = max(1, min($limite, 500));
        $sql = "SELECT v.id, v.fecha, v.total, v.descuento, v.metodo_pago,
                  v.socio_id, COALESCE(s.nombre,'Cliente general') AS socio_nombre,
                  v.caja_id, COALESCE(d.lineas,0) AS lineas,
                  COALESCE(d.bruto,0) AS total_detalle,
                  ROUND(COALESCE(d.bruto,0) - v.descuento, 2) AS total_calculado,
                  ABS(ROUND(COALESCE(d.bruto,0) - v.descuento - v.total,2)) AS diferencia
                FROM ventas v
                LEFT JOIN socios s ON s.id=v.socio_id
                LEFT JOIN (
                   SELECT venta_id, COUNT(*) AS lineas, SUM(subtotal) AS bruto
                   FROM detalle_ventas GROUP BY venta_id
                ) d ON d.venta_id=v.id
                ORDER BY v.fecha DESC, v.id DESC
                LIMIT $limite";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function comparacionCajas(): array {
        $sql = "SELECT c.id, c.estado, c.total_ventas AS total_registrado,
                       COALESCE(v.total_calculado,0) AS total_ventas_bd,
                       ROUND(c.total_ventas - COALESCE(v.total_calculado,0), 2) AS diferencia,
                       COALESCE(v.cantidad,0) AS cantidad_ventas
                FROM cajas c
                LEFT JOIN (
                  SELECT caja_id, COUNT(*) cantidad, SUM(total) total_calculado
                  FROM ventas GROUP BY caja_id
                ) v ON v.caja_id=c.id
                ORDER BY c.id DESC LIMIT 100";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sociosConActividad(): array {
        $sql = "SELECT s.id, s.nombre, s.estado,
                       COALESCE(sc.suscripciones,0) AS suscripciones,
                       COALESCE(v.ventas,0) AS compras,
                       COALESCE(v.monto,0) AS total_compras
                FROM socios s
                LEFT JOIN (
                  SELECT socio_id, COUNT(*) AS suscripciones
                  FROM suscripciones WHERE socio_id IS NOT NULL
                  GROUP BY socio_id
                ) sc ON sc.socio_id=s.id
                LEFT JOIN (
                  SELECT socio_id, COUNT(*) AS ventas, SUM(total) AS monto
                  FROM ventas WHERE socio_id IS NOT NULL
                  GROUP BY socio_id
                ) v ON v.socio_id=s.id
                ORDER BY s.nombre ASC LIMIT 150";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function productosSinImagen(): array {
        $sql = "SELECT id, nombre, codigo, stock, foto FROM productos ORDER BY nombre ASC";
        $lista = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $carpeta = __DIR__ . '/../../public/img/productos/';
        $pendientes = [];
        foreach ($lista as $p) {
            $nombre = (string)($p['foto'] ?? '');
            if ($nombre === '' || basename($nombre) !== $nombre || !is_file($carpeta . $nombre)) {
                $p['problema'] = $nombre === '' ? 'Sin foto vinculada' : 'Archivo no encontrado';
                $pendientes[] = $p;
            }
        }
        return $pendientes;
    }

    public function reservasPorEstado(): array {
        // La tabla la inicializa el módulo Reserva.
        $stmt = $this->conn->query("SELECT estado, COUNT(*) AS total FROM reservas GROUP BY estado");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
