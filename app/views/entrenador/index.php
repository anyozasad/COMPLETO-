<?php
require_once __DIR__.'/../../models/Configuracion.php';
$config=Configuracion::getInfo();
$e=static fn($x)=>htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');
?>
<!doctype html><html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Entrenador principal - <?= $e($config['nombre_sistema']??'Gimnasio') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="<?= $e(BASE_URL) ?>/css/gym-theme.css">
</head><body>
<?php require_once __DIR__.'/../inc/navbar.php'; ?>
<div class="container-fluid px-4 fade-in mt-5 mb-5"><div class="module-container">
<h3 class="fw-bold text-dark mb-3"><i class="fas fa-user-tie text-primary me-2"></i> Entrenador principal</h3>
<p class="text-muted">Se muestra únicamente el primer entrenador activo registrado en Usuarios. Guarda su fotografía real dentro del sistema para que el cliente la vea sin conexión a servicios externos.</p>
<?php if(isset($_GET['success'])): ?><div class="alert alert-success">Fotografía actualizada.</div><?php endif; ?>
<?php if(isset($_GET['error'])): ?><div class="alert alert-danger">No se pudo guardar la fotografía. Revisa formato, tamaño y permisos.</div><?php endif; ?>
<?php if(!$entrenador): ?>
<div class="alert alert-warning">No hay un usuario activo con rol Entrenador. Regístralo desde Usuarios antes de colocar su foto.</div>
<?php else: ?>
<div class="row g-4 align-items-start"><div class="col-lg-4">
<div class="card border-0 p-4 shadow-sm text-center">
<?php $foto=(string)($entrenador['foto']??'');
$ruta=__DIR__.'/../../../public/img/entrenadores/'.$foto;
if($foto!=='' && basename($foto)===$foto && is_file($ruta)): ?>
<img src="<?= $e(BASE_URL.'/img/entrenadores/'.rawurlencode($foto)) ?>" alt="Entrenador" class="img-fluid rounded-3 mb-3" style="max-height:320px;object-fit:contain">
<?php else: ?><div class="bg-light text-muted p-5 rounded-3 mb-3">Sin fotografía asignada</div><?php endif; ?>
<h5 class="fw-bold"><?= $e($entrenador['nombre']) ?></h5>
<p class="text-muted mb-0">Entrenador de Mallqui Gym</p>
</div></div>
<div class="col-lg-8"><div class="card border-0 p-4 shadow-sm">
<h5 class="fw-bold">Actualizar fotografía</h5>
<form action="<?= $e(BASE_URL) ?>/entrenador/guardarFoto" method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_entrenador']) ?>">
<input type="hidden" name="usuario_id" value="<?= (int)$entrenador['id'] ?>">
<label for="trainerFoto" class="form-label mt-2">Fotografía del entrenador (JPG, PNG o WEBP, hasta 5 MB)</label>
<input id="trainerFoto" class="form-control" type="file" name="foto" accept="image/jpeg,image/png,image/webp" required>
<div class="mt-3"><img id="fotoPreview" alt="Vista previa" style="width:170px;max-height:170px;object-fit:contain;display:none" class="rounded-3 border"></div>
<button type="submit" class="btn btn-primary mt-3 fw-bold">Guardar fotografía</button>
</form>
</div></div></div>
<?php endif; ?>
</div></div>
<?php require_once __DIR__.'/../inc/footer.php'; ?>
<script>
document.getElementById('trainerFoto')?.addEventListener('change',function(){
const file=this.files[0],img=document.getElementById('fotoPreview');
if(!file){img.style.display='none';return;}
if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>5242880){
alert('Selecciona JPG, PNG o WEBP de hasta 5 MB');this.value='';img.style.display='none';return;
}
const reader=new FileReader();
reader.onload=()=>{img.src=reader.result;img.style.display='inline-block';};
reader.readAsDataURL(file);
});
</script>
</body></html>
