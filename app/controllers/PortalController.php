<?php
require_once __DIR__ . '/../models/Portal.php';
require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/RutinaRecomendada.php';

class PortalController {
    private Portal $portal;
    private Reserva $reservas;

    public function __construct() {
        if (session_status()===PHP_SESSION_NONE) session_start();
        $this->portal=new Portal();
        $this->reservas=new Reserva();
        if (!isset($_SESSION['csrf_portal'])) {
            $_SESSION['csrf_portal']=bin2hex(random_bytes(24));
        }
    }

    private function enlace(string $ruta): string {
        return BASE_URL . '/portal/' . $ruta;
    }
    private function csrf(): void {
        if (!is_string($_POST['csrf'] ?? null) ||
            !hash_equals($_SESSION['csrf_portal'] ?? '', $_POST['csrf'])) {
            http_response_code(403);
            exit('Solicitud no autorizada. Recarga la página.');
        }
    }
    private function requerirLogin(): void {
        if (empty($_SESSION['portal_cliente_id']) || empty($_SESSION['portal_socio_id'])) {
            header('Location: ' . $this->enlace('login'));
            exit();
        }
    }
    private function redirigir(string $destino, string $codigo, string $tipo='error'): void {
        header('Location: ' . $this->enlace($destino) .
            '?' . $tipo . '=' . rawurlencode($codigo));
        exit();
    }

    public function index(): void {
        $vista='inicio';
        $productos=$this->portal->productos();
        $entrenador=$this->portal->entrenador();
        $cliente=$this->portal->socio((int)($_SESSION['portal_socio_id'] ?? 0));
        $misReservas=[];
        $rutina=[];
        $programa=null;
        if ($cliente) {
            $misReservas=$this->reservas->obtenerPorCliente((int)$_SESSION['portal_cliente_id']);
            $rutina=$this->portal->rutinasPorSocio((int)$cliente['id']);
            $programa=(new RutinaRecomendada())->obtener((int)$cliente['id']);
        }
        require_once __DIR__ . '/../views/portal/index.php';
    }
    public function registrar(): void {
        $vista='registrar';
        require_once __DIR__ . '/../views/portal/index.php';
    }
    public function login(): void {
        $vista='login';
        require_once __DIR__ . '/../views/portal/index.php';
    }
    public function guardarRegistro(): void {
        if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); return; }
        $this->csrf();
        try {
            $this->portal->registrar(
                trim((string)($_POST['nombre'] ?? '')),
                trim((string)($_POST['dni'] ?? '')),
                strtolower(trim((string)($_POST['email'] ?? ''))),
                (string)($_POST['password'] ?? ''),
                trim((string)($_POST['telefono'] ?? ''))
            );
            $this->redirigir('login','registrado','success');
        } catch (InvalidArgumentException $e) {
            $_SESSION['portal_error_registro']=$e->getMessage();
            $this->redirigir('registrar','datos');
        } catch (Throwable $e) {
            error_log('Error registro portal: '.$e->getMessage());
            $this->redirigir('registrar','registro');
        }
    }
    public function autenticar(): void {
        if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); return; }
        $this->csrf();
        $cuenta=$this->portal->autenticar(
            trim((string)($_POST['email'] ?? '')),
            (string)($_POST['password'] ?? '')
        );
        if (!$cuenta) $this->redirigir('login','credenciales');
        session_regenerate_id(true);
        $_SESSION['portal_cliente_id']=(int)$cuenta['id'];
        $_SESSION['portal_socio_id']=(int)$cuenta['socio_id'];
        $_SESSION['portal_nombre']=$cuenta['nombre'];
        header('Location: '.$this->enlace('index'));
        exit();
    }
    public function salir(): void {
        if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); return; }
        $this->csrf();
        unset($_SESSION['portal_cliente_id'],$_SESSION['portal_socio_id'],$_SESSION['portal_nombre']);
        session_regenerate_id(true);
        header('Location: '.$this->enlace('index'));
        exit();
    }
    public function reservar(): void {
        if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); return; }
        $this->requerirLogin();
        $this->csrf();
        $fecha=trim((string)($_POST['fecha'] ?? ''));
        $hora=trim((string)($_POST['hora'] ?? ''));
        $tipo=trim((string)($_POST['tipo'] ?? 'sesion'));
        $d=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha);
        if (!$d || $d->format('Y-m-d')!==$fecha ||
            $fecha<date('Y-m-d') ||
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$hora) ||
            !in_array($tipo,['sesion','clase','evaluacion'],true)) {
            $this->redirigir('index','reserva_invalida');
        }
        if ($this->reservas->existeConflicto($fecha,$hora.':00')) {
            $this->redirigir('index','ocupada');
        }
        try {
            $ok=$this->reservas->crearDesdePortal(
                (int)$_SESSION['portal_cliente_id'],
                (int)$_SESSION['portal_socio_id'],
                $fecha,$hora.':00',$tipo
            );
            if (!$ok) throw new RuntimeException('No se guardó.');
            $this->redirigir('index','reservada','success');
        } catch (Throwable $e) {
            error_log('Error al reservar desde portal: '.$e->getMessage());
            $this->redirigir('index','guardar');
        }
    }
    public function cancelar(): void {
        if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); return; }
        $this->requerirLogin();
        $this->csrf();
        $id=filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT);
        if (!$id || $id < 1 ||
            !$this->reservas->cancelarDesdePortal((int)$_SESSION['portal_cliente_id'],$id)) {
            $this->redirigir('index','cancelar');
        }
        $this->redirigir('index','cancelada','success');
    }
}
