<?php
declare(strict_types=1);

/**
 * Inicializa el primer administrador sin contraseñas por defecto.
 *
 * Windows PowerShell:
 *   $env:GYM_INITIAL_ADMIN_EMAIL='administrador@tugimnasio.com'
 *   $env:GYM_INITIAL_ADMIN_PASSWORD='UnaClaveLargaYPrivada!'
 *   php scripts/crear_admin.php
 *
 * El script NO modifica otros usuarios ni sobrescribe contraseñas.
 */
if (PHP_SAPI!=='cli') {
    http_response_code(403);
    exit("Solo se puede ejecutar en consola.\n");
}
require_once __DIR__.'/../app/config/Database.php';
require_once __DIR__.'/../app/models/Usuario.php';

$email=trim((string)(getenv('GYM_INITIAL_ADMIN_EMAIL') ?: ''));
$password=(string)(getenv('GYM_INITIAL_ADMIN_PASSWORD') ?: '');
if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<12) {
    fwrite(STDERR,"Configura un correo válido y una contraseña de al menos 12 caracteres mediante variables de entorno.\n");
    exit(1);
}
try {
    $conn=(new Database())->getConnection();
    $total=(int)$conn->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    if($total>0) {
        fwrite(STDERR,"Ya existen usuarios. Por seguridad no se genera otro administrador automáticamente.\n");
        exit(2);
    }
    $ok=(new Usuario())->crearAdmin();
    if(!$ok) throw new RuntimeException("No se creó el administrador.");
    echo "Administrador inicial creado. Se recomienda borrar las variables GYM_INITIAL_ADMIN_* tras el primer acceso.\n";
} catch(Throwable $e) {
    fwrite(STDERR,"No se pudo inicializar: ".$e->getMessage()."\n");
    exit(1);
}
