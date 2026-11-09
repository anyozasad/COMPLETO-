<?php
declare(strict_types=1);
require_once __DIR__.'/../models/Configuracion.php';
require_once __DIR__.'/../lib/Auth.php';

class ConfiguracionController {
    public function __construct() {
        if(session_status()===PHP_SESSION_NONE)session_start();
        Auth::requerirRol(['admin']);
        if(!isset($_SESSION['csrf_config'])) {
            $_SESSION['csrf_config']=bin2hex(random_bytes(24));
        }
    }

    public function index(): void {
        $datos=(new Configuracion())->obtenerDatos();
        require_once __DIR__.'/../views/configuracion/index.php';
    }

    public function actualizar(): void {
        if($_SERVER['REQUEST_METHOD']!=='POST'){
            http_response_code(405);
            return;
        }
        if(!is_string($_POST['csrf']??null) ||
           !hash_equals($_SESSION['csrf_config']??'',$_POST['csrf'])) {
            http_response_code(403);
            exit('Solicitud no autorizada.');
        }
        $modelo=new Configuracion();
        $actual=$modelo->obtenerDatos();
        $nombreLogo=(string)($actual['logo']??'');
        $creado=null;
        try {
            $nombre=trim((string)($_POST['nombre']??''));
            $ruc=trim((string)($_POST['ruc']??''));
            $direccion=trim((string)($_POST['direccion']??''));
            $telefono=trim((string)($_POST['telefono']??''));
            $email=trim((string)($_POST['email']??''));
            $moneda=trim((string)($_POST['moneda']??'S/'));
            if ($nombre==='' || strlen($nombre)>100 ||
                strlen($ruc)>20 || strlen($direccion)>255 ||
                strlen($telefono)>20 || strlen($email)>100 ||
                ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) ||
                $moneda==='' || strlen($moneda)>10) {
                throw new InvalidArgumentException('Datos de empresa incorrectos.');
            }

            $archivo=$_FILES['logo']??null;
            if($archivo && ($archivo['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
                if($archivo['error']!==UPLOAD_ERR_OK ||
                   (int)($archivo['size']??0)<1 ||
                   (int)$archivo['size']>5*1024*1024 ||
                   !is_uploaded_file((string)($archivo['tmp_name']??''))) {
                    throw new InvalidArgumentException('La foto supera 5 MB o no se recibió correctamente.');
                }
                $mime=(new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
                $ext=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$mime]??null;
                if(!$ext || getimagesize($archivo['tmp_name'])===false) {
                    throw new InvalidArgumentException('Solo se aceptan imágenes PNG, JPG o WebP reales.');
                }
                $carpeta=dirname(__DIR__,2).'/public/img/';
                if(!is_dir($carpeta) && !mkdir($carpeta,0755,true)){
                    throw new RuntimeException('No se pudo crear carpeta de imágenes.');
                }
                $nombreLogo='logo_'.bin2hex(random_bytes(12)).'.'.$ext;
                $creado=$carpeta.$nombreLogo;
                if(!move_uploaded_file($archivo['tmp_name'],$creado)) {
                    throw new RuntimeException('Error al guardar el logo.');
                }
            }

            $ok=$modelo->actualizar([
                'nombre'=>$nombre,'ruc'=>$ruc,'direccion'=>$direccion,
                'telefono'=>$telefono,'email'=>$email,
                'logo'=>$nombreLogo,'moneda'=>$moneda
            ]);
            if(!$ok) throw new RuntimeException('No se actualizó configuración.');
            header('Location: '.BASE_URL.'/configuracion/index?msg=ok');
        } catch(Throwable $e) {
            if($creado && is_file($creado)) unlink($creado);
            error_log('Configuración: '.$e->getMessage());
            header('Location: '.BASE_URL.'/configuracion/index?error=1');
        }
        exit;
    }
}
