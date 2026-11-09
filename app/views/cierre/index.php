<?php
require_once __DIR__.'/../../models/Configuracion.php';
$config=Configuracion::getInfo();
$e=static fn($x)=>htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');
$conteos=$diagnostico['conteos'];
$errores=$conteos['observaciones_bd']+$conteos['consultas_sin_resultado']+
    $conteos['productos_sin_imagen_local']+$conteos['archivos_faltantes'];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cierre técnico · Semana 11 · <?= $e($config['nombre_sistema']??'Mallqui Gym') ?></title>
<link href="<?= $e(BASE_URL) ?>/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
<link href="<?= $e(BASE_URL) ?>/vendor/fontawesome/css/all.min.css" rel="stylesheet">
<link href="<?= $e(BASE_URL) ?>/css/gym-theme.css" rel="stylesheet">
<style>
.cierre-card {border:0;border-radius:16px;box-shadow:0 6px 20px rgba(30,41,59,.06)}
.cierre-grid {display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}
.cierre-metric {padding:18px;border-radius:14px;border:1px solid #e5e7eb;background:white}
.cierre-metric strong {font-size:2rem;color:#1f2937;display:block}
.cierre-note {background:#f8fafc;border-left:4px solid #6366f1;border-radius:9px;padding:16px}
.cierre-check {width:1.1rem;height:1.1rem;margin-right:8px}
@media print {.no-print,#sidebar-wrapper,.btn{display:none!important}
 #page-content-wrapper{width:100%!important} .container-fluid{padding:0!important}
 .cierre-card{box-shadow:none!important}}
</style>
</head><body>
<?php require_once __DIR__.'/../inc/navbar.php'; ?>
<div class="container-fluid px-4 fade-in mt-4 mb-5">
<div class="module-container">
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
<div>
<h3 class="fw-bold text-dark mb-1"><i class="fas fa-clipboard-check text-primary me-2"></i> Semana 11 · Verificación final</h3>
<p class="text-muted mb-0">Login, registro, dashboard, integridad de datos, interfaz, evidencias y respaldo.</p>
</div>
<div class="d-flex gap-2 no-print flex-wrap">
<a class="btn btn-outline-primary" href="<?= $e(BASE_URL) ?>/cierre/exportar"><i class="fas fa-download me-1"></i> Descargar diagnóstico</a>
<button class="btn btn-primary" type="button" onclick="window.print()"><i class="fas fa-print me-1"></i> Imprimir verificación</button>
</div>
</div>
<div class="cierre-note mb-4">
<strong>Resultados de la instalación actual · <?= $e($diagnostico['generado_en']) ?>.</strong>
Estas comprobaciones consultan MySQL y archivos del servidor.
El sistema no puede afirmar que el profesor haya realizado las pruebas ni que existan firmas o capturas de pantalla.
</div>
<div class="cierre-grid mb-4">
<div class="cierre-metric"><span class="small text-muted">Observaciones de BD</span>
<strong><?= (int)$conteos['observaciones_bd'] ?></strong><span class="small">Registros o grupos por revisar</span></div>
<div class="cierre-metric"><span class="small text-muted">Productos sin imagen</span>
<strong><?= (int)$conteos['productos_sin_imagen_local'] ?></strong><span class="small">Fotografías pendientes</span></div>
<div class="cierre-metric"><span class="small text-muted">Archivos de sistema ausentes</span>
<strong><?= (int)$conteos['archivos_faltantes'] ?></strong><span class="small">Módulos y recursos</span></div>
<div class="cierre-metric"><span class="small text-muted">Consultas no verificadas</span>
<strong><?= (int)$conteos['consultas_sin_resultado'] ?></strong><span class="small">Errores de consulta</span></div>
</div>

<h5 class="fw-bold text-dark mt-4 mb-3">1. Login, registro y navegación del usuario</h5>
<div class="card cierre-card p-3 mb-4">
<p class="text-muted small mb-3">Accede a estas pantallas y prueba con una cuenta de prueba. El resultado de cada prueba manual debe quedar anotado.</p>
<div class="d-flex flex-wrap gap-2 no-print mb-3">
<a href="<?= $e(BASE_URL) ?>/portal/index" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Portada del usuario</a>
<a href="<?= $e(BASE_URL) ?>/portal/registrar" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Registro</a>
<a href="<?= $e(BASE_URL) ?>/portal/login" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Login del socio</a>
<a href="<?= $e(BASE_URL) ?>/auth/index" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Login administrativo</a>
</div>
<div class="small">
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> El registro crea un nuevo socio en la BD.</label>
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> La contraseña errónea no permite ingresar.</label>
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> La sesión permite reservar y visualizar los cambios.</label>
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> Menú, botones y navegación funcionan en móvil y PC.</label>
</div>
</div>

<h5 class="fw-bold text-dark mb-3">2. Pruebas de módulos del dashboard</h5>
<div class="card cierre-card p-3 mb-4">
<p class="text-muted small">Se comprueba la existencia física de los componentes; los cambios de datos deben probarse manualmente en el equipo.</p>
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead><tr><th>Componente</th><th>Archivo</th><th>Verificación de estructura</th></tr></thead>
<tbody>
<?php foreach($diagnostico['componentes'] as $item): ?>
<tr><td class="fw-semibold"><?= $e($item['nombre']) ?></td>
<td><code><?= $e($item['ruta']) ?></code></td>
<td><span class="badge <?= $item['presente']?'bg-success':'bg-danger' ?>">
<?= $item['presente']?'Archivo presente':'Archivo ausente' ?></span></td></tr>
<?php endforeach; ?>
</tbody>
</table></div>
</div>

<h5 class="fw-bold text-dark mb-3">3. Duplicados y datos incompletos en MySQL</h5>
<div class="card cierre-card p-3 mb-4">
<div class="alert alert-info border-0">Este diagnóstico identifica problemas reales sin borrar socios, pagos, reservas ni productos. Las correcciones de datos existentes deben revisarse antes de ejecutarlas.</div>
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead><tr><th>Área</th><th>Control</th><th>Hallazgos</th><th>Estado</th></tr></thead>
<tbody>
<?php foreach($diagnostico['datos'] as $d): ?>
<tr><td><?= $e(ucfirst($d['area'])) ?></td>
<td><?= $e($d['nombre']) ?></td>
<td class="fw-bold"><?= $d['cantidad']===null?'No comprobado':(int)$d['cantidad'] ?></td>
<td><span class="badge <?= $d['estado']==='sin_hallazgos'?'bg-success':($d['estado']==='revisar'?'bg-warning text-dark':'bg-danger') ?>">
<?= $d['estado']==='sin_hallazgos'?'Sin hallazgos':($d['estado']==='revisar'?'Requiere revisión':'Error de consulta') ?>
</span></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php if($diagnostico['imagenes_pendientes']): ?>
<h6 class="fw-bold mt-3">Fotografías de productos pendientes</h6>
<div class="table-responsive"><table class="table table-hover">
<thead><tr><th>Id</th><th>Producto</th><th>Problema</th></tr></thead><tbody>
<?php foreach($diagnostico['imagenes_pendientes'] as $p): ?>
<tr><td>#<?= (int)$p['id'] ?></td><td><?= $e($p['nombre']) ?></td><td><?= $e($p['razon']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<a class="btn btn-sm btn-outline-primary no-print" href="<?= $e(BASE_URL) ?>/inventario/productos">Asignar imágenes correctas</a>
<?php endif; ?>
</div>

<h5 class="fw-bold text-dark mb-3">4. Últimos detalles de interfaz</h5>
<div class="card cierre-card p-3 mb-4">
<p class="small text-muted">Las pantallas mantienen las clases y el CSS existente. Verifica el diseño real en distintos anchos y sin Internet.</p>
<div class="small">
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> Sidebar y menú móvil abren y cierran correctamente.</label>
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> Gráficos, tablas y botones funcionan sin errores de JavaScript.</label>
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> Fotos de productos, portada y entrenador se ven nítidas y sin Internet.</label>
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> Los cambios de socio/reserva/pago aparecen en la base de datos.</label>
</div>
</div>

<h5 class="fw-bold text-dark mb-3">5. Evidencias y respaldo de la aplicación</h5>
<div class="card cierre-card p-3 mb-4">
<p class="small">La descarga de diagnóstico contiene resultados de esta instalación, con fecha y conteos reales. No incluye contraseñas ni datos personales de los socios.</p>
<p class="small">Para un respaldo con código, fotografías y SQL, ejecuta <code>scripts/respaldo_efsrt.ps1</code> en PowerShell desde el proyecto, con XAMPP/MySQL disponible. No ejecutes respaldos sobre una base de datos diferente sin comprobar el nombre configurado.</p>
<div class="small">
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> Descargué el diagnóstico real de la instalación.</label>
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> Guardé las capturas reales del login, dashboard y módulos.</label>
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> Generé y comprobé el ZIP de respaldo y el SQL.</label>
<label class="d-block mb-2"><input class="cierre-check" type="checkbox"> Las observaciones y firmas fueron revisadas con el supervisor.</label>
</div>
<p class="small text-muted mb-0">Estas casillas son para registrar comprobaciones reales; no se marcan automáticamente.</p>
</div>
</div></div>
<?php require_once __DIR__.'/../inc/footer.php'; ?>
</body></html>
