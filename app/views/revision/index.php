<?php
require_once __DIR__ . '/../../models/Configuracion.php';
$config = Configuracion::getInfo();
$esc = static fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$soles = static fn ($v) => 'S/ ' . number_format((float)$v, 2, '.', ',');
$colores = ['pendiente'=>'bg-warning text-dark','confirmada'=>'bg-primary','completada'=>'bg-success','cancelada'=>'bg-secondary'];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verificación del proyecto EFSRT - <?= $esc($config['nombre_sistema'] ?? 'Gimnasio') ?></title>
<link href="/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css">
<link rel="stylesheet" href="<?= $esc(BASE_URL) ?>/css/gym-theme.css">
</head>
<body>
<?php require_once __DIR__ . '/../inc/navbar.php'; ?>
<div class="container-fluid px-4 fade-in mt-5 mb-5">
<div class="module-container">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
<div>
<h3 class="fw-bold text-dark mb-1"><i class="fas fa-clipboard-check text-primary me-2"></i> Verificación del proyecto EFSRT</h3>
<p class="text-muted small mb-0">Reservas, productos, imágenes, pagos y socios · Datos reales de MySQL</p>
</div>
<div class="d-flex gap-2">
<a href="<?= $esc(BASE_URL) ?>/reservas/index" class="btn btn-outline-primary">Reservas</a>
<a href="<?= $esc(BASE_URL) ?>/inventario/productos" class="btn btn-primary">Productos</a>
</div>
</div>
<div class="alert alert-info border-0 rounded-3 mb-4" role="note">
Esta pantalla compara la base de datos actual sin modificarla. <strong>Ventas</strong> son cobros de punto de venta;
<strong>suscripciones</strong> son servicios registrados y no se consideran pagos confirmados sin un registro de cobro.
Las ventas a «Cliente general» están permitidas.
</div>
<div class="row g-3 mb-4">
<?php foreach ([
  ['Socios registrados',$resumen['socios_total'],'fa-users'],
  ['Socios activos',$resumen['socios_activos'],'fa-user-check'],
  ['Suscripciones',$resumen['suscripciones_total'],'fa-id-card'],
  ['Ventas cobradas',$resumen['ventas_total'],'fa-receipt'],
  ['Total ventas',$soles($resumen['monto_ventas']),'fa-coins'],
  ['Productos sin foto válida',count($imagenesPendientes),'fa-image'],
] as $dato): ?>
<div class="col-6 col-lg-4 col-xl-2"><div class="card h-100 shadow-sm border-0 rounded-3 p-3">
<div class="text-muted small mb-2"><i class="fas <?= $esc($dato[2]) ?> me-1 text-primary"></i><?= $esc($dato[0]) ?></div>
<div class="fw-bold fs-4 text-dark"><?= $esc($dato[1]) ?></div>
</div></div>
<?php endforeach; ?>
</div>
<h5 class="fw-bold text-dark mb-3">1. Reservas relacionadas con socios y usuarios</h5>
<div class="d-flex flex-wrap gap-2 mb-4">
<?php if (!$estados): ?><span class="text-muted">No hay reservas registradas.</span><?php endif; ?>
<?php foreach ($estados as $estado): ?>
<span class="badge <?= $esc($colores[$estado['estado']] ?? 'bg-secondary') ?> py-2 px-3"><?= $esc(ucfirst($estado['estado'])) ?>: <?= (int)$estado['total'] ?></span>
<?php endforeach; ?>
<a href="<?= $esc(BASE_URL) ?>/reservas/index" class="btn btn-sm btn-outline-primary">Crear, editar y controlar reservas</a>
</div>
<h5 class="fw-bold text-dark mb-3">2. Productos e imágenes almacenadas</h5>
<?php if (!$imagenesPendientes): ?>
<div class="alert alert-success border-0">Todos los productos registrados tienen una imagen local existente.</div>
<?php else: ?>
<p class="text-muted small">Hay <?= count($imagenesPendientes) ?> productos sin imagen vinculada o con archivo no encontrado. Elige la foto correcta desde Inventario → Editar producto. No se asignan fotos equivocadas automáticamente.</p>
<div class="table-responsive mb-4"><table class="table table-hover align-middle">
<thead><tr><th>Producto</th><th>Código</th><th>Stock</th><th>Observación</th><th>Corregir</th></tr></thead><tbody>
<?php foreach ($imagenesPendientes as $p): ?>
<tr><td><?= $esc($p['nombre']) ?></td><td><?= $esc($p['codigo']) ?></td><td><?= (int)$p['stock'] ?></td>
<td><span class="badge bg-warning text-dark"><?= $esc($p['problema']) ?></span></td>
<td><a href="<?= $esc(BASE_URL) ?>/inventario/productos" class="btn btn-sm btn-outline-primary">Ir a producto</a></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<h5 class="fw-bold text-dark mb-3">3. Comparación de pagos y socios con la base de datos</h5>
<p class="text-muted small">Ventas registradas con el socio asociado, método de pago y comprobación de importes contra el detalle. Las diferencias mayores a S/ 0.01 se señalan.</p>
<?php if ($cobrosInconsistentes): ?>
<div class="alert alert-warning border-0"><strong><?= count($cobrosInconsistentes) ?> ventas revisadas con observación.</strong> Comprobar importes y líneas antes de modificar datos.</div>
<?php else: ?>
<div class="alert alert-success border-0">Sin diferencias en las <?= count($cobros) ?> ventas mostradas.</div>
<?php endif; ?>
<div class="table-responsive mb-4"><table class="table table-hover align-middle">
<thead><tr><th>Venta</th><th>Fecha</th><th>Socio / cliente</th><th>Medio de pago</th><th>Total registrado</th><th>Total recalculado</th><th>Diferencia</th></tr></thead>
<tbody>
<?php foreach ($cobros as $p): ?>
<tr>
<td class="fw-bold">#<?= (int)$p['id'] ?></td>
<td><?= $esc($p['fecha']) ?></td><td><?= $esc($p['socio_nombre']) ?></td><td><?= $esc(ucfirst($p['metodo_pago'])) ?></td>
<td><?= $esc($soles($p['total'])) ?></td><td><?= $esc($soles($p['total_calculado'])) ?></td>
<td><span class="badge <?= (float)$p['diferencia'] > 0.01 || (int)$p['lineas']===0 ? 'bg-danger' : 'bg-success' ?>">
<?= $esc($soles($p['diferencia'])) ?></span></td>
</tr>
<?php endforeach; ?>
<?php if (!$cobros): ?><tr><td colspan="7" class="text-center text-muted py-4">No hay ventas todavía.</td></tr><?php endif; ?>
</tbody></table></div>
<h5 class="fw-bold text-dark mb-3">4. Comparación de caja contra ventas</h5>
<?php if ($cajasInconsistentes): ?>
<div class="alert alert-warning border-0">Se detectaron diferencias de caja. Revisar operaciones antes de cerrar o ajustar.</div>
<?php endif; ?>
<div class="table-responsive mb-4"><table class="table table-hover align-middle">
<thead><tr><th>Caja</th><th>Estado</th><th>Ventas</th><th>Registrado en caja</th><th>Calculado desde ventas</th><th>Diferencia</th></tr></thead><tbody>
<?php foreach ($cajas as $c): ?>
<tr><td>#<?= (int)$c['id'] ?></td><td><?= $esc($c['estado']) ?></td><td><?= (int)$c['cantidad_ventas'] ?></td>
<td><?= $esc($soles($c['total_registrado'])) ?></td><td><?= $esc($soles($c['total_ventas_bd'])) ?></td>
<td><span class="badge <?= abs((float)$c['diferencia']) > 0.01 ? 'bg-danger' : 'bg-success' ?>"><?= $esc($soles($c['diferencia'])) ?></span></td></tr>
<?php endforeach; ?>
<?php if (!$cajas): ?><tr><td colspan="6" class="text-center text-muted py-4">Todavía no hay cajas registradas.</td></tr><?php endif; ?>
</tbody></table></div>
<h5 class="fw-bold text-dark mb-3">5. Socios, suscripciones y compras</h5>
<p class="text-muted small">Los registros de suscripción y de ventas se cuentan por separado, para no duplicar montos.</p>
<div class="table-responsive mb-3"><table class="table table-hover align-middle">
<thead><tr><th>Socio</th><th>Estado</th><th>Suscripciones</th><th>Compras</th><th>Total compras</th></tr></thead><tbody>
<?php foreach ($socios as $s): ?>
<tr><td><?= $esc($s['nombre']) ?></td><td><?= $esc($s['estado']) ?></td>
<td><?= (int)$s['suscripciones'] ?></td><td><?= (int)$s['compras'] ?></td><td><?= $esc($soles($s['total_compras'])) ?></td></tr>
<?php endforeach; ?>
<?php if (!$socios): ?><tr><td colspan="5" class="text-center text-muted py-4">No hay socios registrados.</td></tr><?php endif; ?>
</tbody></table></div>
<p class="text-muted small mb-0">Se muestran hasta 100 ventas y cajas, y hasta 150 socios. Esta revisión no corrige datos automáticamente; sirve para localizar incidencias y registrar evidencias reales.</p>
<h5 class="fw-bold text-dark mb-3 mt-4">6. Registros que requieren revisión (semana 9 y 11)</h5>
<p class="text-muted small">Los posibles registros repetidos se indican para revisión manual. No se borran automáticamente.</p>
<div class="table-responsive"><table class="table table-hover align-middle">
<thead><tr><th>Validación</th><th>Grupos sospechosos</th><th>Estado</th></tr></thead><tbody>
<?php foreach ($duplicados as $d): ?>
<tr><td><?= $esc($d['tipo']) ?></td><td><?= (int)$d['grupos'] ?></td>
<td><span class="badge <?= (int)$d['grupos'] ? 'bg-warning text-dark' : 'bg-success' ?>">
<?= (int)$d['grupos'] ? 'Revisar' : 'Sin duplicados detectados' ?></span></td></tr>
<?php endforeach; ?>
</tbody></table></div>
</div></div>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
</body></html>
