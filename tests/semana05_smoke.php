<?php
declare(strict_types=1);

/**
 * Pruebas de integración de semana 5 contra MySQL de pruebas (NO la BD real).
 * Ejecutar con: DB_TEST_HOST=127.0.0.1 DB_TEST_PASS=testroot php tests/semana05_smoke.php
 */
class Database {
    public function getConnection(): PDO {
        $dsn = 'mysql:host=' . (getenv('DB_TEST_HOST') ?: '127.0.0.1') .
            ';dbname=' . (getenv('DB_TEST_NAME') ?: 'gym_system') . ';charset=utf8mb4';
        return new PDO($dsn, getenv('DB_TEST_USER') ?: 'root',
            getenv('DB_TEST_PASS') ?: 'testroot', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
    }
}
function verificar(bool $condicion, string $detalle): void {
    if (!$condicion) throw new RuntimeException('FALLÓ: ' . $detalle);
    echo "OK: {$detalle}\n";
}

$db = (new Database())->getConnection();
$db->exec("CREATE TABLE usuarios (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(150) NOT NULL
) ENGINE=InnoDB");
$db->exec("CREATE TABLE socios (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(150) NOT NULL
) ENGINE=InnoDB");
$db->exec("CREATE TABLE categorias (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(150) NOT NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB");
$db->exec("CREATE TABLE productos (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, categoria_id INT NOT NULL,
    codigo VARCHAR(50) NULL, nombre VARCHAR(150) NOT NULL,
    precio_compra DECIMAL(10,2) NOT NULL, precio_venta DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0, foto VARCHAR(255) NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB");

$db->exec("INSERT INTO usuarios (nombre) VALUES ('Administrador de prueba')");
$db->exec("INSERT INTO socios (nombre) VALUES ('Socio de prueba')");
$db->exec("INSERT INTO categorias (nombre) VALUES ('Suplementos')");

require_once __DIR__ . '/../app/models/Reserva.php';
require_once __DIR__ . '/../app/models/Producto.php';

$reservas = new Reserva(); // Comprueba creación automática de tabla y claves foráneas.
verificar($reservas->guardar([
    'id'=>0, 'usuario_id'=>1, 'socio_id'=>1, 'fecha'=>'2026-12-02',
    'hora'=>'09:00:00', 'tipo'=>'sesion', 'estado'=>'pendiente',
    'observaciones'=>'Prueba de reserva'
]), 'crear reserva ligada a usuario y socio');
$lista = $reservas->obtenerTodos();
verificar(count($lista) === 1 && $lista[0]['socio_nombre'] === 'Socio de prueba' &&
    $lista[0]['usuario_nombre'] === 'Administrador de prueba', 'listar con relaciones correctas');
$id = (int)$lista[0]['id'];
verificar($reservas->existeConflicto('2026-12-02','09:00:00'), 'detectar hora ocupada');
verificar(!$reservas->existeConflicto('2026-12-02','10:00:00'), 'permitir hora libre');
verificar($reservas->guardar([
    'id'=>$id, 'usuario_id'=>1, 'socio_id'=>1, 'fecha'=>'2026-12-02',
    'hora'=>'09:00:00', 'tipo'=>'evaluacion', 'estado'=>'confirmada',
    'observaciones'=>'Cambio de estado'
]), 'editar reserva y confirmar estado');
verificar($reservas->obtenerTodos()[0]['estado'] === 'confirmada', 'persistir estado confirmado');
verificar($reservas->eliminar($id), 'eliminar reserva');
verificar(count($reservas->obtenerTodos()) === 0, 'verificar eliminación en la BD');

$imagenes = glob(__DIR__ . '/../public/img/productos/*.{png,jpg,jpeg,webp}', GLOB_BRACE) ?: [];
verificar(count($imagenes) > 0, 'existen fotografías de producto guardadas localmente');
$foto = basename($imagenes[0]);
$productos = new Producto();
verificar($productos->agregar([
    'categoria_id'=>1, 'codigo'=>'GYM-001', 'nombre'=>'Producto prueba',
    'precio_compra'=>20, 'precio_venta'=>40, 'stock'=>5, 'foto'=>$foto
]), 'guardar producto con nombre, precios, stock y foto');
$filas=$productos->obtenerTodos();
verificar(count($filas) === 1 && $filas[0]['foto'] === $foto &&
    is_file(__DIR__ . '/../public/img/productos/' . $filas[0]['foto']),
    'foto de producto vinculada en MySQL y presente en el proyecto');
$pid=(int)$filas[0]['id'];
verificar($productos->actualizarStock($pid,3,'restar'), 'descontar stock');
verificar(!$productos->actualizarStock($pid,10,'restar'), 'impedir stock negativo');
verificar($productos->actualizarStock($pid,2,'sumar'), 'aumentar stock');
verificar((int)$productos->obtenerPorId($pid)['stock']===4, 'stock final correcto');
verificar($productos->actualizar([
    'id'=>$pid,'categoria_id'=>1,'codigo'=>'GYM-001','nombre'=>'Producto editado',
    'precio_compra'=>22,'precio_venta'=>42,'stock'=>4,'foto'=>''
]), 'editar producto conservando fotografía');
verificar($productos->obtenerPorId($pid)['foto'] === $foto,
    'imagen permanece tras editar producto sin cambiar la foto');

echo "\nPRUEBAS DE SEMANA 5 COMPLETADAS CORRECTAMENTE\n";
