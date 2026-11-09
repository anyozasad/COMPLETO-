<?php
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../lib/Auth.php';

class InventarioController {
    private $categoriaModel;
    private $productoModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        Auth::requerirRol(['admin']);
        $this->categoriaModel = new Categoria();
        $this->productoModel = new Producto();
    }

    /* ==============================================
             GESTIÓN DE CATEGORÍAS
       ============================================== */
    public function categorias() {
        $categorias = $this->categoriaModel->obtenerTodos();
        require_once __DIR__ . '/../views/inventario/categorias.php';
    }

    public function guardarCategoria() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nombre = trim($_POST['nombre']);
            
            if(empty($nombre)) {
                // Manejo de error básico
                header('Location: ' . BASE_URL . '/inventario/categorias?error=vacío');
                exit();
            }

            if(isset($_POST['id']) && !empty($_POST['id'])) {
                // Editar
                $this->categoriaModel->actualizar($_POST['id'], $nombre);
            } else {
                // Nuevo
                $this->categoriaModel->agregar($nombre);
            }
            
            header('Location: ' . BASE_URL . '/inventario/categorias?success=1');
            exit();
        }
    }

    public function cambiarEstadoCategoria($id, $estado = 'inactivo') {
        $this->categoriaModel->cambiarEstado($id, $estado);
        header('Location: ' . BASE_URL . '/inventario/categorias');
        exit();
    }

    /* ==============================================
             GESTIÓN DE PRODUCTOS
       ============================================== */
    public function productos() {
        $productos = $this->productoModel->obtenerTodos();
        $categorias = $this->categoriaModel->obtenerActivos();

        // Imágenes que ya existen en el proyecto: se pueden vincular a un producto.
        $imagenesDisponibles = [];
        $carpetaImagenes = __DIR__ . '/../../public/img/productos/';
        if (is_dir($carpetaImagenes)) {
            foreach (scandir($carpetaImagenes) ?: [] as $archivo) {
                if (preg_match('/\.(?:jpe?g|png|webp)$/i', $archivo) && is_file($carpetaImagenes . $archivo)) {
                    $imagenesDisponibles[] = $archivo;
                }
            }
            natcasesort($imagenesDisponibles);
            $imagenesDisponibles = array_values($imagenesDisponibles);
        }

        require_once __DIR__ . '/../views/inventario/productos.php';
    }

    public function guardarProducto() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $error = static function (string $codigo): void {
            header('Location: ' . BASE_URL . '/inventario/productos?error=' . rawurlencode($codigo));
            exit();
        };

        $datos = [
            'id' => $_POST['id'] ?? null,
            'categoria_id' => $_POST['categoria_id'] ?? '',
            'codigo' => trim((string)($_POST['codigo'] ?? '')),
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'precio_compra' => $_POST['precio_compra'] ?? '',
            'precio_venta' => $_POST['precio_venta'] ?? '',
            'stock' => $_POST['stock'] ?? 0,
            'foto' => ''
        ];

        // Validar datos básicos antes de guardar archivos.
        if ((int)$datos['categoria_id'] <= 0 || $datos['nombre'] === '' ||
            !is_numeric($datos['precio_compra']) || (float)$datos['precio_compra'] < 0 ||
            !is_numeric($datos['precio_venta']) || (float)$datos['precio_venta'] < 0 ||
            filter_var($datos['stock'], FILTER_VALIDATE_INT) === false || (int)$datos['stock'] < 0) {
            $error('datos_invalidos');
        }

        $carpeta = __DIR__ . '/../../public/img/productos/';
        $fotoElegida = trim((string)($_POST['foto_existente'] ?? ''));
        if ($fotoElegida !== '') {
            // No aceptar rutas ni nombres enviados arbitrariamente desde el navegador.
            if ($fotoElegida !== basename($fotoElegida) ||
                !preg_match('/^[a-zA-Z0-9_.-]+\.(?:jpe?g|png|webp)$/i', $fotoElegida) ||
                !is_file($carpeta . $fotoElegida)) {
                $error('foto_no_encontrada');
            }
            $datos['foto'] = $fotoElegida;
        }

        $archivoSubido = null;
        if (isset($_FILES['foto']) && ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $foto = $_FILES['foto'];
            if ($foto['error'] !== UPLOAD_ERR_OK ||
                !isset($foto['tmp_name'], $foto['size']) ||
                (int)$foto['size'] <= 0 || (int)$foto['size'] > 5 * 1024 * 1024 ||
                !is_uploaded_file($foto['tmp_name'])) {
                $error('foto_subida');
            }

            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($foto['tmp_name']);
            $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($extensiones[$mime]) || getimagesize($foto['tmp_name']) === false) {
                $error('foto_formato');
            }
            if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
                $error('foto_carpeta');
            }
            $nombreArchivo = 'prod_' . bin2hex(random_bytes(12)) . '.' . $extensiones[$mime];
            $archivoSubido = $carpeta . $nombreArchivo;
            if (!move_uploaded_file($foto['tmp_name'], $archivoSubido)) {
                $error('foto_subida');
            }
            $datos['foto'] = $nombreArchivo;
        }

        try {
            $guardado = !empty($datos['id'])
                ? $this->productoModel->actualizar($datos)
                : $this->productoModel->agregar($datos);
            if (!$guardado) {
                throw new RuntimeException('No se guardó el producto.');
            }
        } catch (Throwable $e) {
            if ($archivoSubido && is_file($archivoSubido)) {
                unlink($archivoSubido);
            }
            error_log('Error al guardar producto: ' . $e->getMessage());
            $error('guardar_producto');
        }

        header('Location: ' . BASE_URL . '/inventario/productos?success=1');
        exit();
    }

    public function cambiarEstadoProducto($id, $estado = 'inactivo') {
        $this->productoModel->cambiarEstado($id, $estado);
        header('Location: ' . BASE_URL . '/inventario/productos');
        exit();
    }

    public function ajusteStock() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        $id = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
        $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);
        $operacion = $_POST['operacion'] ?? '';
        if ($id === false || $id < 1 || $cantidad === false || $cantidad < 1 ||
            !in_array($operacion, ['sumar', 'restar'], true)) {
            header('Location: ' . BASE_URL . '/inventario/productos?error=stock_invalido');
            exit();
        }
        if (!$this->productoModel->actualizarStock($id, $cantidad, $operacion)) {
            header('Location: ' . BASE_URL . '/inventario/productos?error=stock_insuficiente');
            exit();
        }
        header('Location: ' . BASE_URL . '/inventario/productos?success=stock');
        exit();
    }
}
