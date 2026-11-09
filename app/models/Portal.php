<?php
/**
 * Área pública de clientes: cuentas conectadas por socio_id a la misma BD
 * del dashboard. No modifica la pantalla administrativa actual.
 */
class Portal {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        if (!$this->db) throw new RuntimeException('No se pudo conectar con MySQL.');
        $this->db->exec("CREATE TABLE IF NOT EXISTS clientes_portal (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            socio_id INT NOT NULL UNIQUE,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_cliente_portal_socio FOREIGN KEY (socio_id) REFERENCES socios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function registrar(string $nombre, string $dni, string $email,
                             string $password, string $telefono): int {
        if ($nombre === '' || strlen($nombre) > 200 ||
            !preg_match('/^\d{8}$/', $dni) ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 ||
            strlen($password) < 10 || strlen($password) > 72 ||
            !preg_match('/^\d{0,15}$/', $telefono)) {
            throw new InvalidArgumentException('Datos incorrectos. DNI de 8 dígitos y contraseña de 10 caracteres mínimo.');
        }
        $this->db->beginTransaction();
        try {
            // DNI nuevo: impide vincular la cuenta a otro socio sin verificar identidad.
            $check = $this->db->prepare('SELECT id FROM socios WHERE dni=:dni LIMIT 1');
            $check->execute(['dni'=>$dni]);
            if ($check->fetch()) {
                throw new InvalidArgumentException('El DNI ya está registrado; consulta al administrador.');
            }
            $insert = $this->db->prepare("INSERT INTO socios
                (nombre,dni,email,telefono,estado) VALUES (:nombre,:dni,:email,:telefono,'pendiente')");
            $insert->execute([
                'nombre'=>$nombre,'dni'=>$dni,'email'=>$email,'telefono'=>$telefono
            ]);
            $idSocio = (int)$this->db->lastInsertId();
            $cuenta = $this->db->prepare('INSERT INTO clientes_portal
                (socio_id,email,password_hash) VALUES (:socio,:email,:clave)');
            $cuenta->execute([
                'socio'=>$idSocio,
                'email'=>strtolower($email),
                'clave'=>password_hash($password, PASSWORD_DEFAULT)
            ]);
            $clienteId = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $clienteId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function autenticar(string $email, string $password): ?array {
        $stmt=$this->db->prepare("SELECT c.id,c.socio_id,c.password_hash,
                        s.nombre,s.estado FROM clientes_portal c
                        JOIN socios s ON s.id=c.socio_id WHERE c.email=:email LIMIT 1");
        $stmt->execute(['email'=>strtolower($email)]);
        $c=$stmt->fetch(PDO::FETCH_ASSOC);
        if (!$c || !password_verify($password, $c['password_hash']) ||
            $c['estado']==='inactivo') {
            return null;
        }
        unset($c['password_hash']);
        return $c;
    }

    public function productos(): array {
        $stmt=$this->db->query("SELECT p.id,p.nombre,p.codigo,p.precio_venta,p.stock,p.foto,
                                      c.nombre categoria
            FROM productos p JOIN categorias c ON c.id=p.categoria_id
            WHERE p.estado='activo' AND c.estado='activo'
            ORDER BY p.nombre ASC LIMIT 150");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function entrenador(): ?array {
        require_once __DIR__.'/Entrenador.php';
        return (new Entrenador())->principal();
    }

    public function rutinasPorSocio(int $idSocio): array {
        $stmt=$this->db->prepare("SELECT dia1,dia2,dia3,dia4,dia5,dia6,observaciones,fecha_asignacion
            FROM rutinas WHERE socio_id=:id ORDER BY fecha_asignacion DESC LIMIT 1");
        $stmt->execute(['id'=>$idSocio]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function socio(int $idSocio): ?array {
        $stmt = $this->db->prepare('SELECT id,nombre,estado FROM socios WHERE id=:id LIMIT 1');
        $stmt->execute(['id'=>$idSocio]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
