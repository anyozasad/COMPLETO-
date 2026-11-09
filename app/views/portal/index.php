<?php
$esc = static fn($valor) => htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
$base = BASE_URL;
$logueado = !empty($_SESSION['portal_cliente_id']);
$mensajes = [
  'reserva_invalida'=>'Selecciona una fecha futura o de hoy y una hora válida.',
  'ocupada'=>'Ya hay otra reserva activa en ese horario.',
  'guardar'=>'La reserva no se pudo guardar.',
  'cancelar'=>'No se puede cancelar esa reserva.',
  'credenciales'=>'Correo o contraseña incorrectos, o cuenta desactivada.',
  'registro'=>'No fue posible registrar la cuenta. Revisa los datos.',
  'datos'=>$_SESSION['portal_error_registro'] ?? 'Datos de registro incorrectos.'
];
$exitos = [
 'reservada'=>'Tu sesión quedó registrada como pendiente de confirmación.',
 'cancelada'=>'Se canceló la sesión.',
 'registrado'=>'La cuenta se creó. Entra con tus credenciales.'
];
unset($_SESSION['portal_error_registro']);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mallqui Gym | <?= $esc(ucfirst($vista)) ?></title>
<link rel="stylesheet" href="<?= $esc($base) ?>/css/gym-theme.css">
<style>
:root{--primary:#6d28d9;--ink:#1f2937;--muted:#64748b;--bg:#f8fafc;--line:#e2e8f0}
*{box-sizing:border-box}
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;margin:0;background:var(--bg);color:var(--ink);line-height:1.5}
a{color:var(--primary);text-decoration:none}
.top{background:#fff;border-bottom:1px solid var(--line)}
.wrap{max-width:1160px;margin:0 auto;padding:0 20px}
.nav{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:76px;flex-wrap:wrap}
.logo{display:flex;align-items:center;gap:10px;font-size:1.25rem;font-weight:800;color:var(--ink)}
.logo img{width:42px;height:42px;object-fit:contain}
.links{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.links a{font-weight:600}
.btn{display:inline-flex;align-items:center;justify-content:center;border:1px solid transparent;background:var(--primary);color:#fff;border-radius:12px;padding:11px 16px;font:600 1rem system-ui;cursor:pointer;text-align:center}
.btn:hover{filter:brightness(.92);color:#fff}
.btn.alt{background:#fff;color:var(--primary);border-color:#c4b5fd}
.btn.red{color:#b91c1c;border-color:#fecaca;background:white}
main{padding:36px 0 60px}
.hero{padding:44px;background:#fff;border-radius:22px;box-shadow:0 10px 40px #1f293708;border:1px solid var(--line)}
.hero h1{font-size:clamp(1.65rem,3vw,2.8rem);line-height:1.15;max-width:700px;margin:0 0 12px}
p.lead{color:var(--muted);font-size:1.05rem;max-width:670px}
section{margin:35px 0}
section h2{margin:0 0 14px;font-size:1.45rem}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
.panel{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;box-shadow:0 4px 18px #1f293705}
.panel h3{margin:0 0 8px;font-size:1.12rem}
.meta{color:var(--muted);font-size:.9rem}
.product{padding:0;overflow:hidden}
.product img,.placeholder{width:100%;height:185px;object-fit:cover}
.placeholder{display:flex;align-items:center;justify-content:center;background:#eef2ff;color:var(--muted);font-size:2rem}
.product .inside{padding:16px}
.price{font-weight:800;color:var(--primary)}
form.form{max-width:560px;margin:0 auto;background:#fff;border:1px solid var(--line);border-radius:20px;padding:28px}
label{display:block;font-weight:650;margin-top:14px;margin-bottom:5px}
input,select,textarea{font:inherit;width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:10px;background:white;color:var(--ink)}
input:focus,select:focus{outline:2px solid #ddd6fe;border-color:var(--primary)}
.actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:20px}
.alert{padding:14px 18px;border-radius:12px;margin-bottom:20px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412}
.alert.ok{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
.small{font-size:.88rem;color:var(--muted)}
.tableWrap{overflow:auto}
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:12px;border-bottom:1px solid var(--line);white-space:nowrap}
.badge{display:inline-block;border-radius:100px;background:#ede9fe;color:#5b21b6;padding:5px 10px;font-size:.8rem;font-weight:650}
.note{background:#fff;color:var(--muted);font-size:.95rem}
footer{text-align:center;padding:25px;border-top:1px solid var(--line)}
@media(max-width:600px){.nav{padding:12px 0}.links{gap:8px}.hero{padding:26px 20px}main{padding-top:20px}form.form{padding:20px}}
</style>
</head>
<body>
<header class="top"><div class="wrap nav">
<a class="logo" href="<?= $esc($base) ?>/portal/index">
<img src="<?= $esc($base) ?>/img/logo_empresa.png" alt="Mallqui Gym">
<span>Mallqui Gym</span>
</a>
<nav class="links" aria-label="Menú principal">
<a href="<?= $esc($base) ?>/portal/index#planes">Planes y rutinas</a>
<a href="<?= $esc($base) ?>/portal/index#productos">Productos</a>
<a href="<?= $esc($base) ?>/portal/index#reservas">Reservas</a>
<?php if (!$logueado): ?>
<a href="<?= $esc($base) ?>/portal/login">Ingresar</a>
<a class="btn" href="<?= $esc($base) ?>/portal/registrar">Registrarse</a>
<?php else: ?>
<span class="small"><?= $esc($_SESSION['portal_nombre'] ?? 'Socio') ?></span>
<form action="<?= $esc($base) ?>/portal/salir" method="post">
<input type="hidden" name="csrf" value="<?= $esc($_SESSION['csrf_portal']) ?>">
<button type="submit" class="btn alt">Salir</button></form>
<?php endif; ?>
</nav>
</div></header>
<main><div class="wrap">
<?php if(isset($_GET['error'])): ?>
<div class="alert" role="alert"><?= $esc($mensajes[(string)$_GET['error']] ?? 'No se pudo completar la operación.') ?></div>
<?php endif; ?>
<?php if(isset($_GET['success'])): ?>
<div class="alert ok" role="status"><?= $esc($exitos[(string)$_GET['success']] ?? 'Operación correcta.') ?></div>
<?php endif; ?>
<?php if($vista==='registrar'): ?>
<h1 style="text-align:center">Crear cuenta de socio</h1>
<form class="form" action="<?= $esc($base) ?>/portal/guardarRegistro" method="post">
<input type="hidden" name="csrf" value="<?= $esc($_SESSION['csrf_portal']) ?>">
<label for="nombre">Nombre completo</label><input id="nombre" name="nombre" maxlength="100" required>
<label for="dni">DNI (8 dígitos)</label><input id="dni" name="dni" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" required>
<label for="email">Correo electrónico</label><input id="email" name="email" type="email" maxlength="190" required>
<label for="telefono">Teléfono</label><input id="telefono" name="telefono" inputmode="tel" maxlength="15" pattern="[0-9]*">
<label for="password">Contraseña (mínimo 10 caracteres)</label><input id="password" name="password" type="password" minlength="10" maxlength="72" autocomplete="new-password" required>
<p class="small">Tu perfil queda pendiente de validación administrativa. Si tu DNI ya existe, consulta al administrador para vincular tu cuenta de forma segura.</p>
<div class="actions"><button class="btn" type="submit">Crear cuenta</button><a href="<?= $esc($base) ?>/portal/login">Ya tengo cuenta</a></div>
</form>
<?php elseif($vista==='login'): ?>
<h1 style="text-align:center">Iniciar sesión</h1>
<form class="form" action="<?= $esc($base) ?>/portal/autenticar" method="post">
<input type="hidden" name="csrf" value="<?= $esc($_SESSION['csrf_portal']) ?>">
<label for="correo">Correo</label><input id="correo" name="email" type="email" autocomplete="username" required>
<label for="clave">Contraseña</label><input id="clave" name="password" type="password" autocomplete="current-password" required>
<div class="actions"><button type="submit" class="btn">Ingresar</button><a href="<?= $esc($base) ?>/portal/registrar">Crear cuenta</a></div>
</form>
<?php else: ?>
<div class="hero">
<h1>Entrena con propósito, avanza cada día.</h1>
<p class="lead">Consulta tus entrenamientos, descubre los productos del gimnasio y reserva una sesión. Todos los registros se consultan en el mismo sistema que administra el gimnasio.</p>
<div class="actions">
<a class="btn" href="#reservas">Reservar sesión</a>
<a class="btn alt" href="#productos">Ver productos</a>
</div>
</div>

<section id="planes">
<h2>Entrenamiento y entrenador</h2>
<div class="grid">
<div class="panel">
<h3>Tu entrenador</h3>
<?php if($entrenador): ?>
<p><?= $esc($entrenador['nombre']) ?></p>
<?php else: ?><p class="meta">El gimnasio todavía no ha registrado un entrenador activo.</p><?php endif; ?>
<p class="small">La información se consulta desde la administración; no se muestran entrenadores inventados.</p>
</div>
<div class="panel">
<h3>Mi rutina</h3>
<?php if(!$logueado): ?>
<p class="meta">Inicia sesión para consultar la rutina asignada a tu cuenta.</p>
<?php elseif(!$rutina && !$programa): ?>
<p class="meta">Aún no tienes una rutina asignada. El entrenador puede registrarla en Seguimiento o Rutinas por nivel.</p>
<?php elseif(!$rutina && $programa): ?>
<p class="meta">Nivel: <?= $esc(ucfirst($programa['nivel'])) ?> · Tipo: <?= $esc(ucfirst($programa['tipo'])) ?></p>
<p><?= $esc(RutinaRecomendada::propuesta($programa['nivel'],$programa['tipo'],(int)date('N'))) ?></p>
<p class="small">Próxima revisión: <?= $esc($programa['fecha_revision']) ?>. Consulta con el entrenador antes de aumentar la intensidad.</p>
<?php else:
$dia = (int)date('N');
$campo = $dia <= 6 ? 'dia'.$dia : null;
?>
<p class="meta">Rutina de hoy · <?= $esc(date('d/m/Y')) ?></p>
<p><?= nl2br($esc($campo ? ($rutina[$campo] ?? 'Día de descanso') : 'Día de descanso')) ?></p>
<p class="small"><?= $esc($rutina['observaciones'] ?? '') ?></p>
<?php endif; ?>
</div>
</div>
</section>

<section id="productos">
<h2>Productos del gimnasio</h2>
<div class="grid">
<?php foreach($productos as $p):
$imagen = (string)($p['foto'] ?? '');
$valida = $imagen!=='' && basename($imagen)===$imagen &&
  is_file(__DIR__.'/../../../public/img/productos/'.$imagen);
?>
<div class="panel product">
<?php if($valida): ?>
<img alt="<?= $esc($p['nombre']) ?>" src="<?= $esc($base.'/img/productos/'.rawurlencode($imagen)) ?>" loading="lazy">
<?php else: ?><div class="placeholder" role="img" aria-label="Producto sin fotografía">▣</div><?php endif; ?>
<div class="inside">
<h3><?= $esc($p['nombre']) ?></h3>
<p class="small"><?= $esc($p['categoria']) ?> · Stock: <?= (int)$p['stock'] ?></p>
<p class="price">S/ <?= number_format((float)$p['precio_venta'],2) ?></p>
</div></div>
<?php endforeach; ?>
<?php if(!$productos): ?><p class="meta">No hay productos activos publicados.</p><?php endif; ?>
</div>
</section>

<section id="reservas">
<h2>Reservar una sesión</h2>
<?php if(!$logueado): ?>
<div class="panel"><p>Ingresa a tu cuenta para registrar y consultar tus sesiones.</p>
<a class="btn" href="<?= $esc($base) ?>/portal/login">Ingresar a mi cuenta</a></div>
<?php else: ?>
<div class="panel" style="max-width:700px">
<form action="<?= $esc($base) ?>/portal/reservar" method="post">
<input type="hidden" name="csrf" value="<?= $esc($_SESSION['csrf_portal']) ?>">
<div class="grid">
<div><label for="fecha">Fecha</label><input id="fecha" type="date" name="fecha" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required></div>
<div><label for="hora">Hora</label><input id="hora" name="hora" type="time" value="09:00" required></div>
</div>
<label for="tipo">Tipo de sesión</label>
<select id="tipo" name="tipo"><option value="sesion">Entrenamiento</option><option value="clase">Clase</option><option value="evaluacion">Evaluación</option></select>
<div class="actions"><button class="btn" type="submit">Solicitar reserva</button></div>
</form>
<p class="small">La reserva se registra como pendiente de confirmación; el administrador verá el mismo registro.</p>
</div>
<div class="panel" style="margin-top:18px">
<h3>Mis reservas</h3>
<div class="tableWrap"><table>
<thead><tr><th>Fecha</th><th>Hora</th><th>Tipo</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php foreach($misReservas as $r): ?>
<tr>
<td><?= $esc($r['fecha']) ?></td><td><?= $esc(substr($r['hora'],0,5)) ?></td>
<td><?= $esc($r['tipo']) ?></td><td><span class="badge"><?= $esc($r['estado']) ?></span></td>
<td>
<?php if(in_array($r['estado'],['pendiente','confirmada'],true) && $r['fecha']>=date('Y-m-d')): ?>
<form method="post" action="<?= $esc($base) ?>/portal/cancelar">
<input type="hidden" name="csrf" value="<?= $esc($_SESSION['csrf_portal']) ?>">
<input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<button class="btn red" type="submit">Cancelar</button></form>
<?php else: ?>—<?php endif; ?>
</td></tr>
<?php endforeach; ?>
<?php if(!$misReservas): ?><tr><td colspan="5">Todavía no tienes reservas.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php endif; ?>
</section>
<?php endif; ?>
</div></main>
<footer>Mallqui Gym · Información vinculada a la base de datos del gimnasio</footer>
</body>
</html>
