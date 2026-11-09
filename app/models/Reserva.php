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
        // La tabla de clientes usa el mismo socio de MySQL: no se duplican socios.
        $this->conn->exec("CREATE TABLE IF NOT EXISTS clientes_portal (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            socio_id INT NOT NULL UNIQUE,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_cliente_portal_socio FOREIGN KEY (socio_id) REFERENCES socios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS reservas (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            socio_id INT NOT NULL,
            usuario_id INT NULL,
            cliente_portal_id INT NULL,
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
            CONSTRAINT fk_reservas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            CONSTRAINT fk_reservas_cliente_portal FOREIGN KEY (cliente_portal_id) REFERENCES clientes_portal(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Compatibilidad con las tablas de la semana 5 creadas antes del portal.
        $cols = $this->conn->query("SHOW COLUMNS FROM reservas")->fetchAll(PDO::FETCH_ASSOC);
        $columnas = array_column($cols, 'Field');
        if (!in_array('cliente_portal_id', $columnas, true)) {
            $this->conn->exec("ALTER TABLE reservas
                 ADD COLUMN cliente_portal_id INT NULL,
                 ADD CONSTRAINT fk_reservas_cliente_portal
                     FOREIGN KEY (cliente_portal_id) REFERENCES clientes_portal(id)");
        }
        foreach ($cols as $columna) {
            if ($columna['Field'] === 'usuario_id' && strtoupper($columna['Null']) === 'NO') {
                $this->conn->exec("ALTER TABLE reservas MODIFY COLUMN usuario_id INT NULL");
                break;
            }
        }
    }

    public function obtenerTodos(): array {
        $consulta = "SELECT r.*, s.nombre AS socio_nombre,
                     COALESCE(u.nombre, CASE WHEN r.cliente_portal_id IS NOT NULL
                       THEN 'Cliente desde portal' ELSE 'No asignado' END) AS usuario_nombre
                     FROM reservas r
                     JOIN socios s ON s.id = r.socio_id
                     LEFT JOIN usuarios u ON u.id = r.usuario_id
                     ORDER BY r.fecha DESC, r.hora DESC, r.id DESC";
        return $this->conn->query($consulta)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorCliente(int $clienteId): array {
        $stmt = $this->conn->prepare("SELECT r.* FROM reservas r
            WHERE r.cliente_portal_id=:cliente ORDER BY r.fecha DESC,r.hora DESC");
        $stmt->execute(['cliente'=>$clienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearDesdePortal(int $clienteId, int $socioId, string $fecha,
                                    string $hora, string $tipo): bool {
        // El vínculo del socio con el cliente se verifica en la consulta.
        $stmt = $this->conn->prepare("INSERT INTO reservas
            (socio_id, usuario_id, cliente_portal_id, fecha, hora, tipo, estado)
            SELECT c.socio_id, NULL, c.id, :fecha, :hora, :tipo, 'pendiente'
            FROM clientes_portal c WHERE c.id=:cliente AND c.socio_id=:socio");
        $stmt->execute([
            'fecha'=>$fecha,'hora'=>$hora,'tipo'=>$tipo,'cliente'=>$clienteId,'socio'=>$socioId
        ]);
        return $stmt->rowCount() === 1;
    }

    public function cancelarDesdePortal(int $clienteId, int $reservaId): bool {
        $stmt = $this->conn->prepare("UPDATE reservas SET estado='cancelada'
            WHERE id=:id AND cliente_portal_id=:cliente
            AND estado IN ('pendiente','confirmada') AND fecha >= CURRENT_DATE()");
        $stmt->execute(['id'=>$reservaId,'cliente'=>$clienteId]);
        return $stmt->rowCount() === 1;
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
