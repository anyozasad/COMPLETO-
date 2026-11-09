<?php
require_once __DIR__.'/../../models/Configuracion.php';
$config=Configuracion::getInfo();
$e=static fn($x)=>htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');
$money=static fn($x)=>'S/ '.number_format((float)$x,2);
$err=$_SESSION['pagos_error']??null;
unset($_SESSION['pagos_error']);
?>
<!doctype html><html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pagos de planes - <?= $e($config['nombre_sistema']??'Gimnasio') ?></title>
<link href="/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css">
<link rel="stylesheet" href="<?= $e(BASE_URL) ?>/css/gym-theme.css">
</head><body>
<?php require_once __DIR__.'/../inc/navbar.php'; ?>
<div class="container-fluid px-4 fade-in mt-5 mb-5"><div class="module-container">
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
<h3 class="fw-bold text-dark m-0"><i class="fas fa-money-bill-wave text-primary me-2"></i> Pagos de planes</h3>
<a class="btn btn-outline-primary" href="<?= $e(BASE_URL) ?>/suscripciones/index">Ver suscripciones</a>
</div>
<div class="alert alert-info border-0">
Esta pantalla registra pagos realmente cobrados y los vincula al socio, su plan y una caja abierta.
Contratar una suscripción no equivale a pagarla. Los recibos internos no reemplazan una boleta electrónica SUNAT.
</div>
<?php if($err): ?><div class="alert alert-danger"><?= $e($err) ?></div><?php endif; ?>
<div class="row g-3 mb-4">
<div class="col-lg-5"><div class="card shadow-sm border-0 p-4 h-100">
<h5 class="fw-bold">Registrar cobro</h5>
<form method="post" action="<?= $e(BASE_URL) ?>/pagos/guardar">
<input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_pagos']) ?>">
<label for="ps" class="form-label fw-bold mt-2">Plan de socio</label>
<select class="form-select" name="suscripcion_id" id="ps" required>
<option value="">Seleccionar...</option>
<?php foreach($planes as $p): ?>
<?php if((float)$p['saldo']>0): ?>
<option value="<?= (int)$p['id'] ?>" data-saldo="<?= $e($p['saldo']) ?>">
#<?= (int)$p['id'] ?> · <?= $e($p['socio_nombre']) ?> · <?= $e($p['plan_nombre']) ?> · saldo <?= $e($money($p['saldo'])) ?>
</option>
<?php endif; ?>
<?php endforeach; ?>
</select>
<label for="monto" class="form-label fw-bold mt-3">Importe cobrado (S/)</label>
<input class="form-control" type="number" min="0.01" step="0.01" id="monto" name="monto" required>
<label for="metodo" class="form-label fw-bold mt-3">Medio de pago</label>
<select class="form-select" name="metodo" id="metodo" required>
<option value="efectivo">Efectivo</option><option value="tarjeta">Tarjeta</option>
<option value="transferencia">Transferencia</option>
</select>
<label for="ref" class="form-label fw-bold mt-3">Referencia (opcional)</label>
<input class="form-control" id="ref" maxlength="100" name="referencia" placeholder="N.º de operación">
<button class="btn btn-primary mt-4" type="submit">Registrar pago</button>
<p class="text-muted small mt-2">Debes tener caja abierta; se valida el saldo y no se permite cobrar más de lo pendiente.</p>
</form></div></div>
<div class="col-lg-7"><div class="card shadow-sm border-0 p-4 h-100">
<h5 class="fw-bold">Saldos de suscripciones</h5>
<div class="table-responsive"><table class="table table-hover align-middle">
<thead><tr><th>Socio</th><th>Plan</th><th>Importe</th><th>Pagado</th><th>Pendiente</th></tr></thead>
<tbody>
<?php foreach($planes as $p): ?>
<tr><td><?= $e($p['socio_nombre']) ?></td><td><?= $e($p['plan_nombre']) ?></td>
<td><?= $e($money($p['importe'])) ?></td><td><?= $e($money($p['pagado'])) ?></td>
<td class="fw-bold"><?= $e($money($p['saldo'])) ?></td></tr>
<?php endforeach; ?>
<?php if(!$planes): ?><tr><td colspan="5" class="text-muted text-center">No hay suscripciones.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
</div>
<h5 class="fw-bold">Historial de pagos confirmados</h5>
<div class="table-responsive"><table class="table table-hover align-middle table-data">
<thead><tr><th>N.º</th><th>Fecha</th><th>Socio</th><th>Plan</th><th>Importe</th><th>Método</th><th>Responsable</th><th>Recibo</th></tr></thead>
<tbody>
<?php foreach($historial as $p): ?>
<tr>
<td>#<?= (int)$p['id'] ?></td><td><?= $e($p['fecha_pago']) ?></td>
<td><?= $e($p['socio_nombre']) ?></td><td><?= $e($p['plan_nombre']) ?></td>
<td class="fw-bold"><?= $e($money($p['monto'])) ?></td>
<td><?= $e($p['metodo']) ?></td><td><?= $e($p['cajero']) ?></td>
<td><a class="btn btn-sm btn-outline-primary" href="<?= $e(BASE_URL) ?>/pagos/comprobante/<?= (int)$p['id'] ?>">Ver recibo</a></td></tr>
<?php endforeach; ?>
<?php if(!$historial): ?><tr><td colspan="8" class="text-center text-muted">Aún no hay cobros registrados.</td></tr><?php endif; ?>
</tbody></table></div>
</div></div>
<?php require_once __DIR__.'/../inc/footer.php'; ?>
<script>
document.getElementById('ps')?.addEventListener('change',function(){
 const op=this.options[this.selectedIndex];
 const input=document.getElementById('monto');
 if(op?.dataset.saldo){input.value=Number(op.dataset.saldo).toFixed(2);input.max=op.dataset.saldo;}
 else{input.removeAttribute('max');input.value='';}
});
</script></body></html>
