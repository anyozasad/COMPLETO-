<?php
/**
 * Foto local del entrenador principal.
 * Usa la cuenta con rol entrenador (sin inventar personal).
 */
class Entrenador {
    private PDO $conn;
    public function __construct() {
        $this->conn=(new Database())->getConnection();
        $this->conn->exec("CREATE TABLE IF NOT EXISTS entrenador_fotos (
            usuario_id INT NOT NULL PRIMARY KEY,
            foto VARCHAR(255) NOT NULL,
            actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_foto_entrenador FOREIGN KEY(usuario_id) REFERENCES usuarios(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    public function principal(): ?array {
        $stmt=$this->conn->query("SELECT u.id,u.nombre,ef.foto
            FROM usuarios u LEFT JOIN entrenador_fotos ef ON ef.usuario_id=u.id
            WHERE u.rol='entrenador' AND u.estado='activo'
            ORDER BY u.id ASC LIMIT 1");
        $v=$stmt->fetch(PDO::FETCH_ASSOC);
        return $v?:null;
    }
    public function guardarFoto(int $usuarioId,string $foto): bool {
        $actual=$this->principal();
        if (!$actual || (int)$actual['id']!==$usuarioId ||
            basename($foto)!==$foto ||
            !preg_match('/^[a-zA-Z0-9_.-]+\\.(png|jpe?g|webp)$/i',$foto) ||
            !is_file(__DIR__.'/../../public/img/entrenadores/'.$foto)) {
            return false;
        }
        $stmt=$this->conn->prepare("INSERT INTO entrenador_fotos(usuario_id,foto)
            VALUES(:usuario,:foto) ON DUPLICATE KEY UPDATE foto=VALUES(foto)");
        return $stmt->execute(['usuario'=>$usuarioId,'foto'=>$foto]);
    }
}
