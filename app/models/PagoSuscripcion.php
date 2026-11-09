<?php
/**
 * Pagos de suscripciones realmente recibidos. Mantiene trazabilidad con caja.
 * Las suscripciones, por sí solas, no son comprobantes de pago.
 */
class PagoSuscripcion {
    private PDO $db;

    public function __construct() {
        $this->db=(new Database())->getConnection();
        $this->db->exec("CREATE TABLE IF NOT EXISTS pagos_suscripciones (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            suscripcion_id INT NOT NULL,
            caja_id INT NOT NULL,
            usuario_id INT NOT NULL,
            monto DECIMAL(10,2) NOT NULL,
            metodo ENUM('efectivo','tarjeta','transferencia') NOT NULL,
            referencia VARCHAR(100) NULL,
            fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_pago_suscripcion FOREIGN KEY (suscripcion_id) REFERENCES suscripciones(id),
            CONSTRAINT fk_pago_caja FOREIGN KEY (caja_id) REFERENCES cajas(id),
            CONSTRAINT fk_pago_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            INDEX idx_pago_fecha (fecha_pago),
            INDEX idx_pago_suscripcion (suscripcion_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function suscripcionesConSaldo(): array {
        $q="SELECT s.id,s.socio_id, so.nombre AS socio_nombre, p.nombre AS plan_nombre,
                  p.precio AS importe, COALESCE(pagos.pagado,0) AS pagado,
                  GREATEST(p.precio-COALESCE(pagos.pagado,0),0) AS saldo
            FROM suscripciones s
            JOIN socios so ON so.id=s.socio_id
            JOIN planes p ON p.id=s.plan_id
            LEFT JOIN (SELECT suscripcion_id,SUM(monto) AS pagado
                       FROM pagos_suscripciones GROUP BY suscripcion_id) pagos
              ON pagos.suscripcion_id=s.id
            ORDER BY s.id DESC";
        return $this->db->query($q)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function historial(int $limite=200): array {
        $limite=max(1,min($limite,500));
        $q="SELECT ps.*,so.nombre AS socio_nombre,p.nombre AS plan_nombre,
                  u.nombre AS cajero
            FROM pagos_suscripciones ps
            JOIN suscripciones s ON s.id=ps.suscripcion_id
            JOIN socios so ON so.id=s.socio_id
            JOIN planes p ON p.id=s.plan_id
            JOIN usuarios u ON u.id=ps.usuario_id
            ORDER BY ps.fecha_pago DESC,ps.id DESC LIMIT $limite";
        return $this->db->query($q)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener(int $id): ?array {
        $s=$this->db->prepare("SELECT ps.*,s.socio_id,so.nombre AS socio_nombre,
                so.dni, p.nombre AS plan_nombre, u.nombre AS cajero
            FROM pagos_suscripciones ps
            JOIN suscripciones s ON s.id=ps.suscripcion_id
            JOIN socios so ON so.id=s.socio_id
            JOIN planes p ON p.id=s.plan_id
            JOIN usuarios u ON u.id=ps.usuario_id
            WHERE ps.id=:id LIMIT 1");
        $s->execute(['id'=>$id]);
        return $s->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function registrar(int $suscripcionId,int $usuarioId,float $monto,
                             string $metodo,string $referencia=''): int {
        if ($suscripcionId<1 || $usuarioId<1 || !is_finite($monto) ||
            round($monto,2)<=0 ||
            !in_array($metodo,['efectivo','tarjeta','transferencia'],true) ||
            strlen($referencia)>100) {
            throw new InvalidArgumentException('Datos de pago inválidos.');
        }
        $this->db->beginTransaction();
        try {
            $sub=$this->db->prepare("SELECT s.id,p.precio FROM suscripciones s
                 JOIN planes p ON p.id=s.plan_id
                 WHERE s.id=:id FOR UPDATE");
            $sub->execute(['id'=>$suscripcionId]);
            $registro=$sub->fetch(PDO::FETCH_ASSOC);
            if (!$registro) throw new DomainException('Suscripción inexistente.');

            $caja=$this->db->prepare("SELECT id FROM cajas
                WHERE usuario_id=:uid AND estado='abierta'
                ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $caja->execute(['uid'=>$usuarioId]);
            $idCaja=$caja->fetchColumn();
            if (!$idCaja) throw new DomainException('Abre caja antes de registrar el cobro.');

            $total=$this->db->prepare("SELECT COALESCE(SUM(monto),0)
                FROM pagos_suscripciones WHERE suscripcion_id=:id");
            $total->execute(['id'=>$suscripcionId]);
            $pagado=(float)$total->fetchColumn();
            $restante=round((float)$registro['precio']-$pagado,2);
            $valor=round($monto,2);
            if ($valor>$restante+0.005 || $restante<=0) {
                throw new DomainException('El importe excede el saldo del plan.');
            }

            $insert=$this->db->prepare("INSERT INTO pagos_suscripciones
                (suscripcion_id,caja_id,usuario_id,monto,metodo,referencia)
                VALUES (:suscripcion,:caja,:usuario,:monto,:metodo,:referencia)");
            $insert->execute([
                'suscripcion'=>$suscripcionId,
                'caja'=>(int)$idCaja,
                'usuario'=>$usuarioId,
                'monto'=>$valor,
                'metodo'=>$metodo,
                'referencia'=>$referencia!==''?$referencia:null
            ]);
            $idPago=(int)$this->db->lastInsertId();
            $actualizar=$this->db->prepare(
                "UPDATE cajas SET total_ventas=total_ventas+:monto
                 WHERE id=:id AND estado='abierta'"
            );
            $actualizar->execute(['monto'=>$valor,'id'=>(int)$idCaja]);
            if ($actualizar->rowCount()!==1) {
                throw new RuntimeException('No se pudo actualizar caja.');
            }
            $this->db->commit();
            return $idPago;
        } catch(Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function totalMesActual(): float {
        $stmt=$this->db->query("SELECT COALESCE(SUM(monto),0)
            FROM pagos_suscripciones
            WHERE fecha_pago>=DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01')
              AND fecha_pago<DATE_FORMAT(CURRENT_DATE()+INTERVAL 1 MONTH,'%Y-%m-01')");
        return (float)$stmt->fetchColumn();
    }
}
