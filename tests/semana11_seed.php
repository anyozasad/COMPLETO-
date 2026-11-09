<?php
declare(strict_types=1);

/**
 * Solo se ejecuta en CI y sobre una base de datos efímera.
 * NUNCA ejecutar en la base de datos de la empresa.
 */
if (PHP_SAPI!=='cli' || getenv('CI')!=='true' ||
    getenv('DB_NAME')!=='gym_system_ci') {
    fwrite(STDERR,"Restringido a la base de pruebas aislada de GitHub Actions.\n");
    exit(1);
}
require_once __DIR__.'/../app/config/Database.php';
require_once __DIR__.'/../app/models/Usuario.php';

$db=(new Database())->getConnection();
$clave=(string)(getenv('GYM_INITIAL_ADMIN_PASSWORD') ?: '');
$email=(string)(getenv('GYM_INITIAL_ADMIN_EMAIL') ?: '');
if (strlen($clave)<12 || !filter_var($email,FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('Faltan credenciales CI de prueba.');
}
if (!(new Usuario())->crearAdmin()) {
    throw new RuntimeException('La cuenta admin de pruebas no se creó.');
}
$db->prepare("INSERT INTO configuracion
    (id,nombre_sistema,ruc,direccion,telefono,email,logo,moneda)
    VALUES (1,'Mallqui Gym','00000000000','Dirección de pruebas',
            '000000000','pruebas@example.test','logo_empresa.png','S/')
    ON DUPLICATE KEY UPDATE logo=VALUES(logo),email=VALUES(email),
        nombre_sistema=VALUES(nombre_sistema)")
   ->execute();
$db->exec("INSERT INTO categorias (nombre,estado) VALUES
    ('Suplementos','activo'),('Ropa','activo')");
$imagenes=glob(__DIR__.'/../public/img/productos/*.{png,jpg,jpeg,webp}',GLOB_BRACE) ?: [];
$foto=$imagenes?basename($imagenes[0]):null;
$stmt=$db->prepare("INSERT INTO productos
    (categoria_id,codigo,nombre,precio_compra,precio_venta,stock,foto,estado)
    VALUES (1,'PROD-001','Producto prueba',10,20,5,:foto,'activo')");
$stmt->execute(['foto'=>$foto]);
$db->exec("INSERT INTO planes (nombre,precio,duracion_dias,descripcion,estado)
    VALUES ('Mensual prueba',60,30,'Solo entorno de prueba','activo')");
$stmt=$db->prepare("INSERT INTO usuarios (nombre,email,password,rol,estado)
    VALUES ('Entrenador de prueba','trainer-ci@example.test',:pw,'entrenador','activo')");
$stmt->execute(['pw'=>password_hash('TrainerPassword!OnlyCI', PASSWORD_DEFAULT)]);
echo "SEMANA11 SEED OK. BD aislada gym_system_ci.\n";
