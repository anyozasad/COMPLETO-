<?php
require_once '../app/models/Configuracion.php';

class ConfiguracionController {

    private function verificarAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }
    }

    public function index() {
        $this->verificarAuth();
        $configModel = new Configuracion();
        $datos = $configModel->obtenerDatos();
        require_once '../app/views/configuracion/index.php';
    }

    public function actualizar() {
        $this->verificarAuth();
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $nombre_logo = $_POST['logo_actual'];

            if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                $carpeta = __DIR__ . '/../../public/img/';
                if (!file_exists($carpeta)) { mkdir($carpeta, 0777, true); }

                $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $nombre_archivo = "logo_empresa." . $extension;
                $ruta_destino = $carpeta . $nombre_archivo;

                if(move_uploaded_file($_FILES['logo']['tmp_name'], $ruta_destino)) {
                    $nombre_logo = $nombre_archivo;
                }
            }

            $data = [
                'nombre' => $_POST['nombre'],
                'ruc' => $_POST['ruc'],
                'direccion' => $_POST['direccion'],
                'telefono' => $_POST['telefono'],
                'email' => $_POST['email'],
                'logo' => $nombre_logo,
                'moneda' => $_POST['moneda'] // Nuevo campo
            ];

            $configModel = new Configuracion();
            if ($configModel->actualizar($data)) {
                header('Location: /configuracion/index?msg=ok');
            } else {
                echo "Error al guardar configuración.";
            }
        }
    }
}