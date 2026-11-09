<?php
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/Socio.php';

class ReservasController {
    private Reserva $modelo;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        Auth::requerirRol(['admin', 'recepcionista']);
        $this->modelo = new Reserva();
        if (!isset($_SESSION['csrf_reservas'])) {
            $_SESSION['csrf_reservas'] = bin2hex(random_bytes(24));
        }
    }

    public function index(): void {
        $reservas = $this->modelo->obtenerTodos();
        $socios = (new Socio())->obtenerTodos()->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/reservas/index.php';
    }

    private function validarToken(): void {
        if (!isset($_POST['csrf']) ||
            !hash_equals($_SESSION['csrf_reservas'] ?? '', (string)$_POST['csrf'])) {
            http_response_code(403);
            exit('Solicitud no autorizada. Actualiza la página e inténtalo de nuevo.');
        }
    }

    private function redirigir(string $campo, string $codigo): void {
        header('Location: ' . BASE_URL . '/reservas/index?' . $campo . '=' . rawurlencode($codigo));
        exit();
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        $this->validarToken();

        $id = (int)($_POST['id'] ?? 0);
        $socio = (int)($_POST['socio_id'] ?? 0);
        $fecha = trim((string)($_POST['fecha'] ?? ''));
        $hora = trim((string)($_POST['hora'] ?? ''));
        $tipo = trim((string)($_POST['tipo'] ?? 'sesion'));
        $estado = trim((string)($_POST['estado'] ?? 'pendiente'));
        $observaciones = trim((string)($_POST['observaciones'] ?? ''));

        $fechaObj = DateTime::createFromFormat('!Y-m-d', $fecha);
        if ($socio < 1 || !$fechaObj || $fechaObj->format('Y-m-d') !== $fecha ||
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora) ||
            !in_array($tipo, ['sesion', 'clase', 'evaluacion'], true) ||
            !in_array($estado, ['pendiente', 'confirmada', 'completada', 'cancelada'], true) ||
            strlen($observaciones) > 500) {
            $this->redirigir('error', 'datos_invalidos');
        }
        if ($estado !== 'cancelada' && $this->modelo->existeConflicto($fecha, $hora . ':00', $id)) {
            $this->redirigir('error', 'horario_ocupado');
        }

        $datos = [
            'id' => $id, 'socio_id' => $socio,
            'usuario_id' => (int)$_SESSION['user_id'],
            'fecha' => $fecha, 'hora' => $hora . ':00',
            'tipo' => $tipo, 'estado' => $estado,
            'observaciones' => $observaciones
        ];
        try {
            if (!$this->modelo->guardar($datos)) {
                throw new RuntimeException('No se pudo guardar la reserva.');
            }
            $this->redirigir('success', 'guardada');
        } catch (Throwable $e) {
            error_log('Error en reservas: ' . $e->getMessage());
            $this->redirigir('error', 'guardar');
        }
    }

    public function eliminar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        $this->validarToken();
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) {
            $this->redirigir('error', 'datos_invalidos');
        }
        try {
            $this->modelo->eliminar($id);
            $this->redirigir('success', 'eliminada');
        } catch (Throwable $e) {
            error_log('Error al eliminar reserva: ' . $e->getMessage());
            $this->redirigir('error', 'eliminar');
        }
    }
}
