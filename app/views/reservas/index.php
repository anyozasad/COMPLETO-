<?php
require_once __DIR__ . '/../../models/Configuracion.php';
$config = Configuracion::getInfo();
$tipos = ['sesion'=>'Sesión','clase'=>'Clase','evaluacion'=>'Evaluación'];
$estados = ['pendiente'=>'Pendiente','confirmada'=>'Confirmada','completada'=>'Completada','cancelada'=>'Cancelada'];
$clases = ['pendiente'=>'bg-warning text-dark','confirmada'=>'bg-primary','completada'=>'bg-success','cancelada'=>'bg-secondary'];
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservas - <?= $e($config['nombre_sistema'] ?? 'Gimnasio') ?></title>
<link href="/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css">
<link rel="stylesheet" href="<?= $e(BASE_URL) ?>/css/gym-theme.css">
</head>
<body>
<?php require_once __DIR__ . '/../inc/navbar.php'; ?>
<div class="container-fluid px-4 fade-in mt-5 mb-5"><div class="module-container">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
<h3 class="fw-bold text-dark m-0"><i class="fas fa-calendar-check text-primary me-2"></i> Reservas de Sesiones</h3>
<button class="btn btn-primary px-4 fw-bold" type="button" data-bs-toggle="modal" data-bs-target="#modalReserva"><i class="fas fa-plus me-2"></i>Nueva Reserva</button>
</div>
<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">Reserva <?= $_GET['success'] === 'eliminada' ? 'eliminada' : 'guardada' ?> correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (isset($_GET['error'])):
$mensajes=['datos_invalidos'=>'Revisa los datos de la reserva.','horario_ocupado'=>'El horario ya está reservado.','guardar'=>'No se pudo guardar la reserva.','eliminar'=>'No se pudo eliminar la reserva.'];
?>
<div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $e($mensajes[$_GET['error']] ?? 'Error al procesar reserva.') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<div class="table-responsive"><table class="table table-hover align-middle table-data w-100 border-0">
<thead><tr><th>Fecha y hora</th><th>Socio</th><th>Servicio</th><th>Estado</th><th>Registrado por</th><th>Observaciones</th><th class="text-end">Acciones</th></tr></thead>
<tbody>
<?php foreach ($reservas as $r): ?>
<tr>
<td class="fw-bold"><?= $e($r['fecha']) ?><small class="d-block text-muted"><?= $e(substr($r['hora'],0,5)) ?></small></td>
<td><?= $e($r['socio_nombre']) ?></td>
<td><?= $e($tipos[$r['tipo']] ?? $r['tipo']) ?></td>
<td><span class="badge <?= $e($clases[$r['estado']] ?? 'bg-secondary') ?> px-3 py-2 rounded-pill"><?= $e($estados[$r['estado']] ?? $r['estado']) ?></span></td>
<td><?= $e($r['usuario_nombre']) ?></td>
<td><?= $e($r['observaciones'] ?? '') ?></td>
<td class="text-end text-nowrap">
<button type="button" class="btn btn-sm btn-outline-warning editar-reserva" data-bs-toggle="modal" data-bs-target="#modalReserva"
data-id="<?= (int)$r['id'] ?>" data-socio="<?= (int)$r['socio_id'] ?>" data-fecha="<?= $e($r['fecha']) ?>"
data-hora="<?= $e(substr($r['hora'],0,5)) ?>" data-tipo="<?= $e($r['tipo']) ?>"
data-estado="<?= $e($r['estado']) ?>" data-observaciones="<?= $e($r['observaciones'] ?? '') ?>" title="Editar"><i class="fas fa-edit"></i></button>
<form action="<?= $e(BASE_URL) ?>/reservas/eliminar" method="post" class="d-inline" onsubmit="return confirm('¿Eliminar esta reserva?')">
<input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_reservas']) ?>">
<input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<button class="btn btn-sm btn-outline-danger" type="submit" title="Eliminar"><i class="fas fa-trash"></i></button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php if (!$reservas): ?><tr><td colspan="7" class="text-center text-muted py-4">Todavía no hay reservas registradas.</td></tr><?php endif; ?>
</tbody>
</table></div></div></div>
<div class="modal fade" id="modalReserva" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content border-0 shadow-lg" style="border-radius:20px">
<div class="modal-header px-4 py-3"><h5 class="modal-title fw-bold" id="tituloReserva">Nueva Reserva</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
<form id="formReserva" method="post" action="<?= $e(BASE_URL) ?>/reservas/guardar">
<div class="modal-body p-4"><div class="row g-3">
<input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_reservas']) ?>">
<input type="hidden" name="id" id="reserva_id">
<div class="col-md-6"><label class="form-label fw-bold" for="reserva_socio">Socio *</label>
<select class="form-select" name="socio_id" id="reserva_socio" required><option value="">Selecciona socio</option>
<?php foreach ($socios as $s): ?><option value="<?= (int)$s['id'] ?>"><?= $e($s['nombre']) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-6"><label class="form-label fw-bold" for="reserva_tipo">Servicio *</label>
<select class="form-select" name="tipo" id="reserva_tipo">
<?php foreach ($tipos as $v=>$texto): ?><option value="<?= $e($v) ?>"><?= $e($texto) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-6"><label class="form-label fw-bold" for="reserva_fecha">Fecha *</label>
<input class="form-control" type="date" name="fecha" id="reserva_fecha" value="<?= date('Y-m-d') ?>" required></div>
<div class="col-md-6"><label class="form-label fw-bold" for="reserva_hora">Hora *</label>
<input class="form-control" type="time" name="hora" id="reserva_hora" value="08:00" required></div>
<div class="col-md-6"><label class="form-label fw-bold" for="reserva_estado">Estado</label>
<select class="form-select" name="estado" id="reserva_estado">
<?php foreach ($estados as $v=>$texto): ?><option value="<?= $e($v) ?>"><?= $e($texto) ?></option><?php endforeach; ?>
</select></div>
<div class="col-12"><label class="form-label fw-bold" for="reserva_observaciones">Observaciones</label>
<textarea class="form-control" name="observaciones" id="reserva_observaciones" rows="3" maxlength="500"></textarea></div>
</div></div>
<div class="modal-footer bg-light px-4 py-3"><button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
<button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Guardar Reserva</button></div>
</form></div></div></div>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
<script>
document.querySelectorAll('.editar-reserva').forEach(function(b){
b.addEventListener('click',function(){
document.getElementById('tituloReserva').textContent='Editar Reserva';
const nombres={'reserva_id':'id','reserva_socio':'socio','reserva_fecha':'fecha','reserva_hora':'hora','reserva_tipo':'tipo','reserva_estado':'estado','reserva_observaciones':'observaciones'};
Object.keys(nombres).forEach(function(id){document.getElementById(id).value=b.dataset[nombres[id]] || '';});
});
});
document.getElementById('modalReserva').addEventListener('hidden.bs.modal',function(){
document.getElementById('formReserva').reset();
document.getElementById('reserva_id').value='';
document.getElementById('tituloReserva').textContent='Nueva Reserva';
});
</script>
</body></html>
