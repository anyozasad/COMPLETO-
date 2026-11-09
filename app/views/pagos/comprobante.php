<?php
require_once __DIR__.'/../../models/Configuracion.php';
$config=Configuracion::getInfo();
$e=static fn($x)=>htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');
$money=static fn($x)=>'S/ '.number_format((float)$x,2);
?>
<!doctype html><html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Recibo interno <?= (int)$pago['id'] ?></title>
<style>
body{font-family:system-ui,Arial,sans-serif;color:#111827;background:#f3f4f6;margin:0}
.page{background:#fff;margin:24px auto;padding:32px;max-width:670px;border:1px solid #e5e7eb;border-radius:12px}
h1{font-size:24px;margin:0 0 5px}small{color:#64748b}
.row{display:flex;justify-content:space-between;gap:20px;border-bottom:1px solid #e5e7eb;padding:12px 0}
.total{font-size:24px;font-weight:800}
.notice{margin-top:22px;padding:12px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px}
.btn{display:inline-block;padding:10px 18px;background:#6d28d9;color:#fff;border-radius:8px;text-decoration:none;border:0;cursor:pointer}
@media print{body{background:white}.page{margin:0;padding:12px;border:none}.actions{display:none}}
</style></head><body>
<div class="page">
<h1><?= $e($config['nombre_sistema']??'Mallqui Gym') ?></h1>
<small>Registro de pago interno · N.º <?= str_pad((string)$pago['id'],6,'0',STR_PAD_LEFT) ?></small>
<div class="row"><strong>Fecha</strong><span><?= $e($pago['fecha_pago']) ?></span></div>
<div class="row"><strong>Socio</strong><span><?= $e($pago['socio_nombre']) ?></span></div>
<div class="row"><strong>DNI</strong><span><?= $e($pago['dni']) ?></span></div>
<div class="row"><strong>Plan</strong><span><?= $e($pago['plan_nombre']) ?></span></div>
<div class="row"><strong>Forma de pago</strong><span><?= $e(ucfirst($pago['metodo'])) ?></span></div>
<div class="row"><strong>Referencia</strong><span><?= $e($pago['referencia']?:'—') ?></span></div>
<div class="row"><strong>Cajero</strong><span><?= $e($pago['cajero']) ?></span></div>
<div class="row"><strong>Importe realmente recibido</strong><span class="total"><?= $e($money($pago['monto'])) ?></span></div>
<div class="notice"><strong>Recibo de control interno.</strong>
No es un comprobante de pago electrónico autorizado por SUNAT ni acredita envío a SUNAT. Para boleta fiscal se requiere integración y validación tributaria.</div>
<div class="actions" style="margin-top:20px">
<button type="button" class="btn" onclick="window.print()">Imprimir / Guardar PDF</button>
<a class="btn" href="<?= $e(BASE_URL) ?>/pagos/index">Volver a Pagos</a>
</div></div></body></html>
