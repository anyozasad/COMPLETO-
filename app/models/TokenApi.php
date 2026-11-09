<?php
/**
 * Tokens de API con hash y caducidad para el cliente móvil.
 * El servidor nunca guarda el token de acceso en texto plano.
 */
class TokenApi {
    private PDO $db;
    public function __construct() {
        $this->db=(new Database())->getConnection();
        $this->db->exec("CREATE TABLE IF NOT EXISTS tokens_api (
            id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            cliente_portal_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expira_en DATETIME NOT NULL,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_tokens_cliente FOREIGN KEY (cliente_portal_id)
                REFERENCES clientes_portal(id) ON DELETE CASCADE,
            INDEX idx_tokens_expira (expira_en)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    public function crear(int $clienteId): array {
        $token=bin2hex(random_bytes(32));
        $expira=(new DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s');
        $stmt=$this->db->prepare("INSERT INTO tokens_api
            (cliente_portal_id,token_hash,expira_en)
            VALUES (:cliente,:hash,:expira)");
        $stmt->execute(['cliente'=>$clienteId,'hash'=>hash('sha256',$token),'expira'=>$expira]);
        return ['access_token'=>$token,'token_type'=>'Bearer','expira_en'=>$expira];
    }
    public function autenticar(string $token): ?array {
        if (!preg_match('/^[a-f0-9]{64}$/i',$token)) return null;
        $stmt=$this->db->prepare("SELECT c.id AS cliente_id,c.socio_id,
                     s.nombre,s.estado
            FROM tokens_api t
            JOIN clientes_portal c ON c.id=t.cliente_portal_id
            JOIN socios s ON s.id=c.socio_id
            WHERE t.token_hash=:hash AND t.expira_en>NOW()
              AND s.estado<>'inactivo'
            LIMIT 1");
        $stmt->execute(['hash'=>hash('sha256',$token)]);
        return $stmt->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public function revocar(string $token): void {
        if (!preg_match('/^[a-f0-9]{64}$/i',$token)) return;
        $stmt=$this->db->prepare('DELETE FROM tokens_api WHERE token_hash=:hash');
        $stmt->execute(['hash'=>hash('sha256',$token)]);
    }
}
