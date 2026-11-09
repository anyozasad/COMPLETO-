<?php
require_once __DIR__.'/../models/Usuario.php';

class AuthController {
    public function index() {
        // Compatibilidad con instalaciones locales antiguas del gimnasio.
        (new Usuario())->crearAdmin();
        if (isset($_SESSION['user_id'])) {
            header('Location: '.BASE_URL.'/home/index');
            exit;
        }
        if (!isset($_SESSION['csrf_admin'])) {
            $_SESSION['csrf_admin']=bin2hex(random_bytes(24));
        }
        require_once __DIR__.'/../views/auth/login.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD']!=='POST') {
            http_response_code(405);
            return;
        }
        if (!is_string($_POST['csrf']??null) ||
            !hash_equals($_SESSION['csrf_admin']??'',$_POST['csrf'])) {
            http_response_code(403);
            exit('Solicitud inválida. Recarga el formulario.');
        }
        $email=trim((string)($_POST['email']??''));
        $password=(string)($_POST['password']??'');
        $bloqueo=(int)($_SESSION['admin_bloqueo_hasta']??0);
        if($bloqueo>time()) {
            $error='Demasiados intentos. Vuelve a intentarlo dentro de unos minutos.';
            require_once __DIR__.'/../views/auth/login.php';
            return;
        }

        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || $password==='') {
            $usuario=false;
        } else {
            $usuario=(new Usuario())->login($email,$password);
        }

        if(is_array($usuario)) {
            session_regenerate_id(true);
            $_SESSION['user_id']=(int)$usuario['id'];
            $_SESSION['user_name']=$usuario['nombre'];
            $_SESSION['user_rol']=$usuario['rol'];
            unset($_SESSION['admin_intentos'],$_SESSION['admin_bloqueo_hasta']);
            $_SESSION['csrf_admin']=bin2hex(random_bytes(24));
            header('Location: '.BASE_URL.'/home/index');
            exit;
        }

        $_SESSION['admin_intentos']=((int)($_SESSION['admin_intentos']??0))+1;
        if($_SESSION['admin_intentos']>=5) {
            $_SESSION['admin_bloqueo_hasta']=time()+120;
            $_SESSION['admin_intentos']=0;
        }
        $error=$usuario==='inactivo'
            ? 'La cuenta está desactivada. Contacta al administrador.'
            : 'Correo o contraseña incorrectos.';
        require_once __DIR__.'/../views/auth/login.php';
    }

    public function logout() {
        unset($_SESSION['user_id'],$_SESSION['user_name'],$_SESSION['user_rol']);
        session_regenerate_id(true);
        header('Location: '.BASE_URL.'/auth/index');
        exit;
    }
}
