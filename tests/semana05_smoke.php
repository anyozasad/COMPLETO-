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
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(150) NOT NULL,
    estado ENUM('activo','inactivo','pendiente') NOT NULL DEFAULT 'activo'
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


// Prueba de relación de compras, pagos y caja contra la BD (actividad 14/10).
$db->exec("CREATE TABLE planes (
    id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(50) NOT NULL,
    precio DECIMAL(10,2) NOT NULL, duracion_dias INT NOT NULL
) ENGINE=InnoDB");
$db->exec("CREATE TABLE suscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY, socio_id INT,
    plan_id INT, fecha_inicio DATE, fecha_fin DATE,
    estado ENUM('activa','vencida') DEFAULT 'activa'
) ENGINE=InnoDB");
$db->exec("CREATE TABLE cajas (
    id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL,
    monto_inicial DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_ventas DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado ENUM('abierta','cerrada') DEFAULT 'abierta'
) ENGINE=InnoDB");
$db->exec("CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY, caja_id INT NOT NULL,
    socio_id INT NULL, total DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) NOT NULL DEFAULT 0,
    metodo_pago ENUM('efectivo','tarjeta','transferencia') DEFAULT 'efectivo',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");
$db->exec("CREATE TABLE detalle_ventas (
    id INT AUTO_INCREMENT PRIMARY KEY, venta_id INT NOT NULL,
    producto_id INT NOT NULL, cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB");
$db->exec("INSERT INTO planes (nombre,precio,duracion_dias) VALUES ('Mensual',60,30)");
$db->exec("INSERT INTO suscripciones (socio_id,plan_id,fecha_inicio,fecha_fin)
    VALUES (1,1,'2026-11-01','2026-12-01')");
$db->exec("INSERT INTO cajas (usuario_id,monto_inicial) VALUES (1,50)");

require_once __DIR__ . '/../app/models/Venta.php';
require_once __DIR__ . '/../app/models/RevisionSemana05.php';

$ventas = new Venta();
$ventaId = $ventas->registrarVenta(1, 1, 80, 'efectivo', [
    ['id' => $pid, 'cantidad' => 2, 'precio' => 42]
], 4);
verificar(is_int($ventaId) && $ventaId > 0, 'venta registrada para socio vinculado');
verificar((int)$productos->obtenerPorId($pid)['stock'] === 2,
    'venta descuenta el stock de forma atómica');
verificar(abs((float)$db->query("SELECT total_ventas FROM cajas WHERE id=1")->fetchColumn() - 80) < 0.01,
    'caja registra exactamente el total cobrado');
verificar($ventas->registrarVenta(1,1,126,'tarjeta', [
    ['id'=>$pid,'cantidad'=>3,'precio'=>42]
],0) === false, 'se rechaza venta si falta stock');
verificar($ventas->registrarVenta(1,1,10,'efectivo', [
    ['id'=>$pid,'cantidad'=>1,'precio'=>42]
],0) === false, 'se rechaza total manipulado o desactualizado');
verificar((int)$db->query("SELECT COUNT(*) FROM ventas")->fetchColumn() === 1,
    'no quedan ventas parciales tras errores');
verificar((int)$productos->obtenerPorId($pid)['stock'] === 2,
    'los errores no alteran stock');

$revision = new RevisionSemana05();
$resumen = $revision->resumen();
verificar((int)$resumen['socios_total']===1 && (int)$resumen['suscripciones_total']===1 &&
    (int)$resumen['ventas_total']===1 && abs((float)$resumen['monto_ventas']-80)<0.01,
    'resumen reúne socios, suscripciones y ventas sin mezclar cobros');
$cobros = $revision->pagosVsSocios();
verificar(count($cobros) === 1 && $cobros[0]['socio_nombre'] === 'Socio de prueba' &&
    abs((float)$cobros[0]['total_calculado'] - 80) < 0.01 &&
    abs((float)$cobros[0]['diferencia']) < 0.01,
    'conciliación entre venta, detalle, descuento y socio');
$cajaReportada = $revision->comparacionCajas();
verificar(count($cajaReportada)===1 &&
    abs((float)$cajaReportada[0]['diferencia']) < 0.01,
    'conciliación de caja con ventas registradas');
$sociosRevisados = $revision->sociosConActividad();
verificar(count($sociosRevisados)===1 && (int)$sociosRevisados[0]['compras']===1 &&
    (int)$sociosRevisados[0]['suscripciones']===1 &&
    abs((float)$sociosRevisados[0]['total_compras']-80)<0.01,
    'relaciones de socios con compras y suscripciones sin montos duplicados');
verificar(count($revision->productosSinImagen())===0,
    'auditoría confirma imágenes locales asignadas');
verificar(count($revision->reservasPorEstado())===0,
    'resumen de estados de reserva funciona aunque no existan registros');

$db->exec("UPDATE ventas SET total = 70 WHERE id = " . (int)$ventaId);
verificar(abs((float)$revision->pagosVsSocios()[0]['diferencia']-10)<0.01,
    'auditoría detecta diferencias de pago en la BD');
verificar(abs((float)$revision->comparacionCajas()[0]['diferencia']-10)<0.01,
    'auditoría detecta descuadre de caja');
$db->exec("UPDATE ventas SET total = 80 WHERE id = " . (int)$ventaId);



// Semanas 5, 9 y 10: cobros reales de suscripción y conciliación completa.
require_once __DIR__ . '/../app/models/PagoSuscripcion.php';
$pagos = new PagoSuscripcion();
$saldos = $pagos->suscripcionesConSaldo();
verificar(count($saldos)===1 && abs((float)$saldos[0]['saldo']-60)<0.01,
    'suscripción pendiente no se trata como pagada');
$idPago1=$pagos->registrar(1,1,20,'efectivo','PRUEBA-1');
verificar($idPago1>0,'primer abono de suscripción registrado');
$idPago2=$pagos->registrar(1,1,40,'transferencia','PRUEBA-2');
verificar($idPago2>$idPago1,'segundo abono de suscripción registrado');
$saldos=$pagos->suscripcionesConSaldo();
verificar(abs((float)$saldos[0]['pagado']-60)<0.01 &&
    abs((float)$saldos[0]['saldo'])<0.01,
    'abonos suman exactamente el precio y saldo queda en cero');
$rechazo=false;
try { $pagos->registrar(1,1,5,'efectivo'); } catch(DomainException $e) {$rechazo=true;}
verificar($rechazo, 'impedir pago duplicado por encima del saldo');
verificar(abs((float)$db->query("SELECT total_ventas FROM cajas WHERE id=1")->fetchColumn()-140)<0.01,
    'cuadrar caja: venta de productos más pagos de planes');
verificar(abs((float)$revision->comparacionCajas()[0]['diferencia'])<0.01,
    'revisión de caja incluye correctamente abonos de suscripciones');
verificar(count($pagos->historial())===2 &&
    $pagos->obtener($idPago1)['socio_nombre']==='Socio de prueba',
    'historial de pagos queda asociado al socio y al cajero');

// Semana 6: no dejar registrar accesos duplicados ni sin membresía.
$db->exec("CREATE TABLE asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY, socio_id INT NOT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");
$db->exec("UPDATE suscripciones SET fecha_inicio=DATE_SUB(CURRENT_DATE(),INTERVAL 1 DAY),
    fecha_fin=DATE_ADD(CURRENT_DATE(),INTERVAL 10 DAY) WHERE id=1");
require_once __DIR__.'/../app/models/Asistencia.php';
$asistencia = new Asistencia();
verificar($asistencia->registrar(1),'asistencia con plan vigente');
verificar(!$asistencia->registrar(1),'rechaza repetir asistencia el mismo día');
$db->exec("UPDATE suscripciones SET fecha_fin=DATE_SUB(CURRENT_DATE(),INTERVAL 1 DAY) WHERE id=1");
$db->exec("DELETE FROM asistencias");
verificar(!$asistencia->registrar(1),'rechaza acceso con plan vencido');


echo "\nPRUEBAS DE SEMANA 5 A 11 COMPLETADAS CORRECTAMENTE\n";
