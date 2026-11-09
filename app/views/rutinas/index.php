<?php
require_once __DIR__.'/../../models/Configuracion.php';
$config=Configuracion::getInfo();
$e=static fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
?>
<!doctype html><html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Rutinas por nivel - <?= $e($config['nombre_sistema']??'Gimnasio') ?></title>
<link href="/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css">
<link rel="stylesheet" href="<?= $e(BASE_URL) ?>/css/gym-theme.css">
</head><body>
<?php require_once __DIR__.'/../inc/navbar.php'; ?>
<div class="container-fluid px-4 fade-in mt-5 mb-5">
<div class="module-container">
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
<h3 class="fw-bold text-dark m-0"><i class="fas fa-dumbbell text-primary me-2"></i> Rutinas por nivel</h3>
<a class="btn btn-outline-primary" href="<?= $e(BASE_URL) ?>/socios/index">Ver socios</a>
</div>
<?php if(isset($_GET['success'])): ?><div class="alert alert-success">Programa de entrenamiento guardado correctamente.</div><?php endif; ?>
<?php if(isset($_GET['error'])): ?><div class="alert alert-danger">Revisa los datos. No se pudo asignar la rutina.</div><?php endif; ?>
<p class="text-muted">Asigna a cada socio un nivel y tipo de entrenamiento. La página del socio mostrará el entrenamiento recomendado del día y la próxima fecha de revisión.</p>
<form action="<?= $e(BASE_URL) ?>/rutinas/guardar" method="post" class="card border-0 shadow-sm p-4 mb-4">
<input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_rutinas']) ?>">
<div class="row g-3">
<div class="col-md-4">
<label for="rs" class="form-label fw-bold">Socio</label>
<select name="socio_id" class="form-select" id="rs" required><option value="">Seleccionar...</option>
<?php foreach($socios as $s): ?>
<option value="<?= (int)$s['id'] ?>"><?= $e($s['nombre']) ?></option>
<?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label fw-bold" for="rn">Nivel</label>
<select name="nivel" id="rn" class="form-select" required>
<?php foreach(RutinaRecomendada::NIVELES as $nivel): ?><option value="<?= $e($nivel) ?>"><?= $e(ucfirst($nivel)) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-3"><label class="form-label fw-bold" for="rt">Tipo</label>
<select name="tipo" id="rt" class="form-select" required>
<?php foreach(RutinaRecomendada::TIPOS as $tipo): ?><option value="<?= $e($tipo) ?>"><?= $e(ucfirst($tipo)) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-2"><label for="rf" class="form-label fw-bold">Revisión</label>
<input type="date" class="form-control" name="fecha_revision" id="rf" value="<?= date('Y-m-d',strtotime('+30 days')) ?>" required></div>
</div>
<div class="mt-3"><button type="submit" class="btn btn-primary fw-bold">Guardar programa</button></div>
</form>
<div class="table-responsive"><table class="table table-hover align-middle table-data">
<thead><tr><th>Socio</th><th>Nivel</th><th>Tipo</th><th>Próxima revisión</th><th>Entrenamiento recomendado hoy</th></tr></thead>
<tbody>
<?php foreach($planes as $p): ?>
<tr><td class="fw-bold"><?= $e($p['socio_nombre']) ?></td>
<td><?= $e(ucfirst($p['nivel'])) ?></td><td><?= $e(ucfirst($p['tipo'])) ?></td>
<td><?= $e($p['fecha_revision']) ?></td>
<td><?= $e(RutinaRecomendada::propuesta($p['nivel'],$p['tipo'],(int)date('N'))) ?></td></tr>
<?php endforeach; ?>
<?php if(!$planes): ?><tr><td colspan="5" class="text-center text-muted py-4">Aún no hay programas asignados.</td></tr><?php endif; ?>
</tbody></table></div>
</div></div>
<?php require_once __DIR__.'/../inc/footer.php'; ?></body></html>
