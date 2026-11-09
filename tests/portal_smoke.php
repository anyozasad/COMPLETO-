<?php
declare(strict_types=1);
class Database {
    public function getConnection(): PDO {
        return new PDO(
            'mysql:host='.(getenv('DB_TEST_HOST')?:'127.0.0.1').
            ';dbname='.(getenv('DB_TEST_NAME')?:'gym_system').';charset=utf8mb4',
            getenv('DB_TEST_USER')?:'root',
            getenv('DB_TEST_PASS')?:'testroot',
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]
        );
    }
}
function revisar(bool $bien,string $nombre): void {
    if (!$bien) throw new RuntimeException('FALLÓ: '.$nombre);
    echo "OK: $nombre\n";
}
$db=(new Database())->getConnection();
$db->exec("CREATE TABLE socios (
    id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL,
    dni VARCHAR(20) NOT NULL UNIQUE, email VARCHAR(100) NULL,
    telefono VARCHAR(20) NULL, estado ENUM('activo','inactivo','pendiente') DEFAULT 'activo'
) ENGINE=InnoDB");
$db->exec("CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL,
    rol ENUM('admin','recepcionista','entrenador') NOT NULL DEFAULT 'admin',
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB");
$db->exec("CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(150) NOT NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB");
$db->exec("CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY, categoria_id INT NOT NULL,
    codigo VARCHAR(50), nombre VARCHAR(150) NOT NULL,
    precio_compra DECIMAL(10,2) NOT NULL, precio_venta DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL, foto VARCHAR(255), estado ENUM('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB");
$db->exec("CREATE TABLE rutinas (
    id INT AUTO_INCREMENT PRIMARY KEY, socio_id INT NOT NULL,
    dia1 TEXT, dia2 TEXT, dia3 TEXT, dia4 TEXT, dia5 TEXT, dia6 TEXT,
    observaciones TEXT, fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");
$db->exec("INSERT INTO usuarios (nombre,rol) VALUES ('Admin prueba','admin'),('Entrenador prueba','entrenador')");
$db->exec("INSERT INTO categorias (nombre) VALUES ('Suplementos')");
$db->exec("INSERT INTO productos (categoria_id,codigo,nombre,precio_compra,precio_venta,stock,foto)
    VALUES (1,'SUP1','Proteína prueba',40,60,9,'prod_1774149004_315.png')");

require_once __DIR__.'/../app/models/Portal.php';
require_once __DIR__.'/../app/models/Reserva.php';
$portal=new Portal();
$id=$portal->registrar('Socio de prueba','11112222','socio.prueba@example.com','claveSegura123','987654321');
revisar($id===1,'registro público crea cuenta');
$cuenta=$portal->autenticar('socio.prueba@example.com','claveSegura123');
revisar($cuenta!==null && $cuenta['id']===1,'login público verifica contraseña hash');
revisar($portal->autenticar('socio.prueba@example.com','incorrecta')===null,'contraseña incorrecta rechazada');
revisar($portal->socio((int)$cuenta['socio_id'])['estado']==='pendiente','cuenta ligada a socio pendiente');
revisar($portal->entrenador()['nombre']==='Entrenador prueba','entrenador único del sistema');
revisar(count($portal->productos())===1,'productos reales compartidos en portal');

$duplicado=false;
try {
    $portal->registrar('DNI repetido','11112222','otro@example.com','claveSegura123','987654321');
} catch(InvalidArgumentException $e) { $duplicado=true; }
revisar($duplicado,'evitar repetir DNI de socio');

$reserva=new Reserva();
$creada=$reserva->crearDesdePortal($id,(int)$cuenta['socio_id'],'2026-12-20','10:00:00','sesion');
revisar($creada,'crear reserva desde cliente');
revisar($reserva->existeConflicto('2026-12-20','10:00:00'),'dashboard detecta horario reservado por cliente');
$admin=$reserva->obtenerTodos();
revisar(count($admin)===1 && $admin[0]['usuario_nombre']==='Cliente desde portal',
    'misma reserva visible en dashboard sin suplantar un administrador');
revisar(count($reserva->obtenerPorCliente($id))===1,'cliente ve únicamente sus reservas');
revisar(count($reserva->obtenerPorCliente(999))===0,'aislar reservas de otros usuarios');
$rid=(int)$admin[0]['id'];
revisar(!$reserva->cancelarDesdePortal(999,$rid),'otro cliente no puede cancelar una reserva ajena');
revisar($reserva->cancelarDesdePortal($id,$rid),'cliente cancela su reserva');
revisar($reserva->obtenerTodos()[0]['estado']==='cancelada','cancelación sincronizada en dashboard');

$db->exec("INSERT INTO rutinas (socio_id,dia1,dia2,observaciones)
    VALUES (1,'Calentamiento y entrenamiento guiado','Descanso activo','Consultar al entrenador')");
revisar($portal->rutinasPorSocio((int)$cuenta['socio_id'])['dia1']==='Calentamiento y entrenamiento guiado',
    'rutina del usuario sincronizada con seguimiento');


// Semana 1 y 6: API y asignación automática de rutinas por fecha/nivel/tipo.
require_once __DIR__.'/../app/models/RutinaRecomendada.php';
require_once __DIR__.'/../app/models/TokenApi.php';

$rutinasPlan=new RutinaRecomendada();
revisar($rutinasPlan->asignar((int)$cuenta['socio_id'],'principiante','funcional','2026-12-30'),
    'programa por nivel y tipo guardado en MySQL');
$programa=$rutinasPlan->obtener((int)$cuenta['socio_id']);
revisar($programa['nivel']==='principiante' &&
    $programa['tipo']==='funcional' && $programa['fecha_revision']==='2026-12-30',
    'fecha de revisión almacenada y sincronizada');
revisar(count($rutinasPlan->listado())===1,'dashboard lista programas asignados');
revisar(str_contains(RutinaRecomendada::propuesta('principiante','funcional',1),'Movilidad'),
    'recomendación diaria cambia según tipo y día');
revisar(RutinaRecomendada::propuesta('principiante','funcional',7)==='Descanso y recuperación.',
    'domingo contempla descanso');

$tokens=new TokenApi();
$nuevoToken=$tokens->crear((int)$cuenta['id']);
revisar(strlen($nuevoToken['access_token'])===64,'token de API aleatorio');
revisar($tokens->autenticar($nuevoToken['access_token'])['socio_id']==$cuenta['socio_id'],
    'API autentica usuario y socio correcto');
revisar($tokens->autenticar('falso')===null,'API rechaza token inválido');
$tokens->revocar($nuevoToken['access_token']);
revisar($tokens->autenticar($nuevoToken['access_token'])===null,'cerrar sesión revoca token');


echo "\nPORTAL USUARIO Y DASHBOARD SINCRONIZADOS EN MYSQL DE PRUEBA\n";
