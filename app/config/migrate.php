<?php
declare(strict_types=1);

/**
 * Migración idempotente de un esquema existente del gimnasio.
 *
 * Ejecutar una vez con respaldo previo:
 *   php app/config/migrate.php
 *
 * No elimina registros ni crea usuarios con claves por defecto.
 */
if(PHP_SAPI!=='cli') {
    http_response_code(403);
    exit("La migración solo se ejecuta en consola.\n");
}
require_once __DIR__.'/Database.php';

try {
    $db=(new Database())->getConnection();
    $nombreBD=(string)$db->query('SELECT DATABASE()')->fetchColumn();
    if($nombreBD==='') throw new RuntimeException('Base de datos no seleccionada.');

    $stmt=$db->query("SHOW COLUMNS FROM ventas LIKE 'descuento'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE ventas ADD COLUMN descuento
            DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total");
        echo "Aplicado: ventas.descuento.\n";
    } else {
        echo "Existente: ventas.descuento.\n";
    }

    // No sobreescribir datos de una empresa instalada.
    $db->exec("INSERT IGNORE INTO configuracion
        (id,nombre_sistema,moneda) VALUES
        (1,'Mallqui Gym','S/')");

    foreach ([
        'Portal.php'=>'Portal',
        'Reserva.php'=>'Reserva',
        'PagoSuscripcion.php'=>'PagoSuscripcion',
        'RutinaRecomendada.php'=>'RutinaRecomendada',
        'Entrenador.php'=>'Entrenador',
        'TokenApi.php'=>'TokenApi'
    ] as $file=>$className) {
        require_once __DIR__.'/../models/'.$file;
        new $className();
        echo "OK: {$className}.\n";
    }
    echo "Migración completada (BD: {$nombreBD}).\n";
} catch(Throwable $e) {
    fwrite(STDERR,"No se pudo completar la migración: {$e->getMessage()}\n");
    exit(1);
}
