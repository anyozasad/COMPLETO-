<?php
/**
 * Reservas de sesiones: relación entre socios y el usuario que las registra.
 * Se crea la tabla al abrir el módulo si la instalación antigua aún no la tiene.
 */
class Reserva {
    private PDO $conn;

    public function __construct() {
        $this->conn = (new Database())->getConnection();
        if (!$this->conn) {
            throw new RuntimeException('No hay conexión con la base de datos.');
        }
        $this->conn->exec("CREATE TABLE IF NOT EXISTS reservas (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            socio_id INT NOT NULL,
            usuario_id INT NOT NULL,
            fecha DATE NOT NULL,
            hora TIME NOT NULL,
            tipo ENUM('sesion','clase','evaluacion') NOT NULL DEFAULT 'sesion',
            estado ENUM('pendiente','confirmada','completada','cancelada') NOT NULL DEFAULT 'pendiente',
            observaciones VARCHAR(500) NULL,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_fecha_hora (fecha, hora),
            INDEX idx_estado (estado),
            INDEX idx_socio (socio_id),
            CONSTRAINT fk_reservas_socio FOREIGN KEY (socio_id) REFERENCES socios(id),
            CONSTRAINT fk_reservas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function obtenerTodos(): array {
        $consulta = "SELECT r.*, s.nombre AS socio_nombre, u.nombre AS usuario_nombre
                     FROM reservas r
                     JOIN socios s ON s.id = r.socio_id
                     JOIN usuarios u ON u.id = r.usuario_id
                     ORDER BY r.fecha DESC, r.hora DESC, r.id DESC";
        return $this->conn->query($consulta)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existeConflicto(string $fecha, string $hora, int $exceptoId = 0): bool {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM reservas
                                      WHERE fecha = :fecha AND hora = :hora
                                        AND estado <> 'cancelada' AND id <> :id");
        $stmt->execute(['fecha' => $fecha, 'hora' => $hora, 'id' => $exceptoId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function guardar(array $datos): bool {
        if ((int)$datos['id'] > 0) {
            $stmt = $this->conn->prepare("UPDATE reservas SET socio_id=:socio_id,
                fecha=:fecha, hora=:hora, tipo=:tipo, estado=:estado,
                observaciones=:observaciones WHERE id=:id");
            return $stmt->execute([
                'id' => $datos['id'],
                'socio_id' => $datos['socio_id'],
                'fecha' => $datos['fecha'],
                'hora' => $datos['hora'],
                'tipo' => $datos['tipo'],
                'estado' => $datos['estado'],
                'observaciones' => $datos['observaciones']
            ]);
        }

        $stmt = $this->conn->prepare("INSERT INTO reservas
            (socio_id, usuario_id, fecha, hora, tipo, estado, observaciones)
            VALUES (:socio_id, :usuario_id, :fecha, :hora, :tipo, :estado, :observaciones)");
        return $stmt->execute([
            'socio_id' => $datos['socio_id'],
            'usuario_id' => $datos['usuario_id'],
            'fecha' => $datos['fecha'],
            'hora' => $datos['hora'],
            'tipo' => $datos['tipo'],
            'estado' => $datos['estado'],
            'observaciones' => $datos['observaciones']
        ]);
    }

    public function eliminar(int $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM reservas WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
