<?php
declare(strict_types=1);

/**
 * Prueba deliberada de fallos de calidad de datos; EXCLUSIVA para la BD efímera CI.
 * Inserta registros ficticios, verifica que el diagnóstico los detecte y
 * después restaura la base de pruebas.
 */
if(PHP_SAPI!=='cli' || getenv('CI')!=='true' ||
   getenv('DB_NAME')!=='gym_system_ci') {
    fwrite(STDERR,"Solo se permite en la BD aislada gym_system_ci.\n");
    exit(1);
}
require_once __DIR__.'/../app/config/Database.php';
require_once __DIR__.'/../app/models/CierreSistema.php';
require_once __DIR__.'/../app/models/Reserva.php';
require_once __DIR__.'/../app/models/PagoSuscripcion.php';
$pdo=(new Database())->getConnection();
$comprobaciones=[];
function validar(bool $ok,string $texto): void {
    global $comprobaciones;
    $comprobaciones[]=['prueba'=>$texto,'resultado'=>$ok?'correcto':'fallo'];
    echo ($ok?'OK: ':'FALLÓ: ').$texto."\n";
    if(!$ok) throw new RuntimeException($texto);
}
$limpiarSocios=[];
$limpiarProductos=[];
$limpiarReservas=[];
$limpiarSuscripciones=[];
$limpio=false;
try {
    new Reserva();
    new PagoSuscripcion();
    $auditor=new CierreSistema();
    $antes=$auditor->resumen();
    validar($antes['conteos']['consultas_sin_resultado']===0,
        'todas las consultas de auditoría funcionan');
    validar($antes['conteos']['archivos_faltantes']===0,
        'módulos y bibliotecas de cierre presentes');

    $s=$pdo->prepare("INSERT INTO socios (nombre,dni,email,estado)
        VALUES (:nombre,:dni,:email,'activo')");
    $s->execute(['nombre'=>'','dni'=>'123','email'=>'repetido-ci@example.test']);
    $limpiarSocios[]=(int)$pdo->lastInsertId();
    $s->execute(['nombre'=>'Usuario de prueba','dni'=>'87654321','email'=>'REPETIDO-CI@example.test']);
    $limpiarSocios[]=(int)$pdo->lastInsertId();

    $p=$pdo->prepare("INSERT INTO productos
        (categoria_id,codigo,nombre,precio_compra,precio_venta,stock,foto,estado)
        VALUES (1,'PROD-001','',-1,0,-3,'no-existe.jpg','activo')");
    $p->execute();
    $limpiarProductos[]=(int)$pdo->lastInsertId();

    $planId=(int)$pdo->query("SELECT id FROM planes ORDER BY id ASC LIMIT 1")->fetchColumn();
    $adminId=(int)$pdo->query("SELECT id FROM usuarios WHERE rol='admin' ORDER BY id ASC LIMIT 1")->fetchColumn();
    validar($planId>0 && $adminId>0,'plan y administrador de pruebas presentes');
    $m=$pdo->prepare("INSERT INTO suscripciones
        (socio_id,plan_id,fecha_inicio,fecha_fin,estado)
        VALUES (:socio,:plan,'2026-12-20','2026-11-20','activa')");
    $m->execute(['socio'=>$limpiarSocios[0],'plan'=>$planId]);
    $limpiarSuscripciones[]=(int)$pdo->lastInsertId();
    $m->execute(['socio'=>$limpiarSocios[0],'plan'=>$planId]);
    $limpiarSuscripciones[]=(int)$pdo->lastInsertId();

    $reserva=new Reserva();
    $idsAntes=array_map(static fn($r)=>(int)$r['id'],$reserva->obtenerTodos());
    $datos=[
        'id'=>0,'usuario_id'=>$adminId,'socio_id'=>$limpiarSocios[0],
        'fecha'=>'2026-12-31','hora'=>'18:30:00',
        'tipo'=>'sesion','estado'=>'pendiente','observaciones'=>'Prueba de auditoría'
    ];
    validar($reserva->guardar($datos),'insertar reserva ficticia para auditoría');
    validar($reserva->guardar($datos),'insertar reserva ficticia duplicada');
    $idsDespues=array_map(static fn($r)=>(int)$r['id'],$reserva->obtenerTodos());
    $limpiarReservas=array_values(array_diff($idsDespues,$idsAntes));

    $despues=$auditor->resumen();
    $mapa=[];
    foreach($despues['datos'] as $d) $mapa[$d['nombre']]=$d['cantidad'];
    validar(($mapa['Nombre o DNI de socio incompleto']??0)>=1,
        'detectar socio incompleto');
    validar(($mapa['Correos duplicados de socios']??0)>=1,
        'detectar correos repetidos');
    validar(($mapa['Productos sin nombre, precios o stock válidos']??0)>=1,
        'detectar producto incompleto e inventario incorrecto');
    validar(($mapa['Códigos de producto duplicados']??0)>=1,
        'detectar códigos duplicados');
    validar(($mapa['Inscripciones repetidas al mismo plan y fecha']??0)>=1,
        'detectar suscripciones duplicadas');
    validar(($mapa['Suscripciones con fechas incorrectas']??0)>=2,
        'detectar fechas incorrectas de suscripción');
    validar(($mapa['Reservas activas para la misma fecha y hora']??0)>=1,
        'detectar reservas superpuestas');
    validar($despues['conteos']['productos_sin_imagen_local']>=1,
        'detectar fotografía inexistente');

} finally {
    foreach($limpiarReservas as $id)
        $pdo->prepare('DELETE FROM reservas WHERE id=?')->execute([$id]);
    foreach($limpiarSuscripciones as $id)
        $pdo->prepare('DELETE FROM suscripciones WHERE id=?')->execute([$id]);
    foreach($limpiarProductos as $id)
        $pdo->prepare('DELETE FROM productos WHERE id=?')->execute([$id]);
    foreach($limpiarSocios as $id)
        $pdo->prepare('DELETE FROM socios WHERE id=?')->execute([$id]);
}
$final=(new CierreSistema())->resumen();
validar($final['conteos']['observaciones_bd']===$antes['conteos']['observaciones_bd'],
    'datos ficticios eliminados y base restaurada');
validar($final['conteos']['productos_sin_imagen_local']===$antes['conteos']['productos_sin_imagen_local'],
    'fotografías pendientes restauradas al estado previo');
if(!is_dir(__DIR__.'/../reports')) mkdir(__DIR__.'/../reports',0755,true);
file_put_contents(__DIR__.'/../reports/semana11_integridad.json',
    json_encode([
        'entorno'=>'MySQL temporal CI',
        'resultado'=>'correcto',
        'inicio'=>$antes['conteos'],
        'anomalías_ficticias_detectadas'=>$despues['conteos'],
        'final'=>$final['conteos'],
        'pruebas'=>$comprobaciones,
        'advertencia'=>'Nunca se ejecutó ni modificó la BD real del alumno.'
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo "INTEGRIDAD SEMANA 11: validaciones y limpieza correctas.\n";
