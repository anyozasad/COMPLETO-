<?php
require_once __DIR__.'/../lib/Auth.php';
require_once __DIR__.'/../models/Entrenador.php';
class EntrenadorController {
    private Entrenador $modelo;
    public function __construct() {
        if(session_status()===PHP_SESSION_NONE) session_start();
        Auth::requerirRol(['admin']);
        $this->modelo=new Entrenador();
        if(!isset($_SESSION['csrf_entrenador'])) {
            $_SESSION['csrf_entrenador']=bin2hex(random_bytes(24));
        }
    }
    public function index(): void {
        $entrenador=$this->modelo->principal();
        require_once __DIR__.'/../views/entrenador/index.php';
    }
    public function guardarFoto(): void {
        if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);return;}
        if(!is_string($_POST['csrf']??null) ||
           !hash_equals($_SESSION['csrf_entrenador'],$_POST['csrf'])){
            http_response_code(403);exit('Solicitud no autorizada.');
        }
        $foto=$_FILES['foto']??[];
        $carpeta=__DIR__.'/../../public/img/entrenadores/';
        $destino=null;
        try {
            if(($foto['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK ||
               (int)($foto['size']??0)<1 || (int)$foto['size']>5*1024*1024 ||
               !is_uploaded_file((string)($foto['tmp_name']??''))) {
                throw new InvalidArgumentException('Imagen inválida o mayor a 5 MB.');
            }
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($foto['tmp_name']);
            $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
            if(!$ext || getimagesize($foto['tmp_name'])===false) {
                throw new InvalidArgumentException('Solo JPG, PNG o WEBP real.');
            }
            if(!is_dir($carpeta) && !mkdir($carpeta,0755,true)) {
                throw new RuntimeException('No se creó la carpeta.');
            }
            $nombre='entrenador_'.bin2hex(random_bytes(12)).'.'.$ext;
            $destino=$carpeta.$nombre;
            if(!move_uploaded_file($foto['tmp_name'],$destino) ||
               !$this->modelo->guardarFoto((int)($_POST['usuario_id']??0),$nombre)) {
                throw new RuntimeException('No se pudo guardar la foto.');
            }
            header('Location: '.BASE_URL.'/entrenador/index?success=1');
        }catch(Throwable $e){
            if($destino && is_file($destino)) unlink($destino);
            error_log('Entrenador: '.$e->getMessage());
            header('Location: '.BASE_URL.'/entrenador/index?error=1');
        }
        exit;
    }
}
