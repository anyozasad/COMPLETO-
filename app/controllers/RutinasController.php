<?php
require_once __DIR__.'/../lib/Auth.php';
require_once __DIR__.'/../models/Socio.php';
require_once __DIR__.'/../models/RutinaRecomendada.php';

class RutinasController {
    private RutinaRecomendada $modelo;

    public function __construct() {
        if(session_status()===PHP_SESSION_NONE) session_start();
        Auth::requerirRol(['admin','entrenador']);
        $this->modelo=new RutinaRecomendada();
        if(!isset($_SESSION['csrf_rutinas'])) $_SESSION['csrf_rutinas']=bin2hex(random_bytes(24));
    }

    public function index(): void {
        $socios=(new Socio())->obtenerTodos()->fetchAll(PDO::FETCH_ASSOC);
        $planes=$this->modelo->listado();
        require_once __DIR__.'/../views/rutinas/index.php';
    }

    public function guardar(): void {
        if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);return;}
        if(!is_string($_POST['csrf']??null) ||
           !hash_equals($_SESSION['csrf_rutinas'],$_POST['csrf'])){
            http_response_code(403);exit('Solicitud no autorizada.');
        }
        try{
            $this->modelo->asignar(
                (int)($_POST['socio_id']??0),
                (string)($_POST['nivel']??''),
                (string)($_POST['tipo']??''),
                (string)($_POST['fecha_revision']??'')
            );
            header('Location: '.BASE_URL.'/rutinas/index?success=1');
        }catch(Throwable $e){
            error_log('Error de rutina: '.$e->getMessage());
            header('Location: '.BASE_URL.'/rutinas/index?error=1');
        }
        exit();
    }
}
