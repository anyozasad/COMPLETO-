-- Reserva de sesiones (semana 5). Usar si se desea instalar manualmente.
-- El modulo PHP crea esta tabla automaticamente cuando se abre por primera vez.
USE gym_system;
CREATE TABLE IF NOT EXISTS reservas (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
