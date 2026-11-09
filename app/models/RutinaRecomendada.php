<?php
/**
 * Planes de entrenamiento configurados por socio.
 * Se mantiene intacta la tabla anterior 'rutinas' y su historial.
 */
class RutinaRecomendada {
    private PDO $db;
    public const NIVELES=['principiante','intermedio','avanzado'];
    public const TIPOS=['fuerza','cardio','funcional'];

    public function __construct() {
        $this->db=(new Database())->getConnection();
        $this->db->exec("CREATE TABLE IF NOT EXISTS programas_rutina (
            socio_id INT NOT NULL PRIMARY KEY,
            nivel ENUM('principiante','intermedio','avanzado') NOT NULL DEFAULT 'principiante',
            tipo ENUM('fuerza','cardio','funcional') NOT NULL DEFAULT 'funcional',
            fecha_revision DATE NOT NULL,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_programa_socio FOREIGN KEY(socio_id) REFERENCES socios(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function listado(): array {
        return $this->db->query("SELECT pr.*,s.nombre AS socio_nombre FROM programas_rutina pr
            JOIN socios s ON s.id=pr.socio_id ORDER BY s.nombre ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener(int $socioId): ?array {
        $stmt=$this->db->prepare("SELECT * FROM programas_rutina WHERE socio_id=:id LIMIT 1");
        $stmt->execute(['id'=>$socioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function asignar(int $socioId,string $nivel,string $tipo,string $fecha): bool {
        $f=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha);
        if ($socioId < 1 || !in_array($nivel,self::NIVELES,true) ||
            !in_array($tipo,self::TIPOS,true) || !$f ||
            $f->format('Y-m-d')!==$fecha) {
            throw new InvalidArgumentException('Datos de rutina inválidos.');
        }
        $stmt=$this->db->prepare("INSERT INTO programas_rutina
            (socio_id,nivel,tipo,fecha_revision)
            VALUES (:socio,:nivel,:tipo,:revision)
            ON DUPLICATE KEY UPDATE nivel=VALUES(nivel), tipo=VALUES(tipo),
                fecha_revision=VALUES(fecha_revision)");
        return $stmt->execute([
            'socio'=>$socioId,'nivel'=>$nivel,'tipo'=>$tipo,'revision'=>$fecha
        ]);
    }

    /** Propuesta diaria (orientativa) que el entrenador puede cambiar en Seguimiento. */
    public static function propuesta(string $nivel,string $tipo,int $diaSemana): string {
        if ($diaSemana===7) return 'Descanso y recuperación.';
        $duraciones=['principiante'=>'15–25','intermedio'=>'25–40','avanzado'=>'35–50'];
        $min=$duraciones[$nivel]??$duraciones['principiante'];
        $temas=[
           'fuerza'=>['Movimientos de piernas','Empuje de tren superior',
                       'Movimientos de espalda','Técnica de fuerza','Circuito de cuerpo completo',
                       'Movilidad y estiramientos'],
           'cardio'=>['Caminata o bici a ritmo cómodo','Trabajo aeróbico guiado',
                       'Movilidad y cardio suave','Actividad aeróbica por intervalos adaptados',
                       'Caminata de recuperación','Cardio recreativo'],
           'funcional'=>['Movilidad y sentadillas adaptadas','Equilibrio y coordinación',
                       'Movilidad de tren superior','Movimientos funcionales guiados',
                       'Circuito funcional adaptado','Recuperación activa']
        ];
        $t=$temas[$tipo]??$temas['funcional'];
        $tema=$t[$diaSemana-1]??'Movilidad y recuperación';
        return "{$tema}. Duración de referencia: {$min} minutos, adaptada por el entrenador.";
    }
}
