<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración del gimnasio</title>
    <link href="/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css">
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">Revisa los campos y la imagen. No se guardaron los cambios.</div>
                <?php endif; ?>
                <?php if(isset($_GET['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Configuración actualizada.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h4><i class="fas fa-cogs"></i> Datos de la Empresa</h4>
                    </div>
                    <div class="card-body">
                        <form action="/configuracion/actualizar" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_config'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            

                            <div class="row mb-4 align-items-center">
                                <div class="col-md-4 text-center">
                                    <label class="fw-bold mb-2">Logo Actual</label>
                                    <div class="border p-2 bg-light rounded">
                                        <?php if(!empty($datos['logo'])): ?>
                                            <img src="/img/<?= htmlspecialchars((string)($datos['logo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>?v=<?= time() ?>" class="img-fluid" style="max-height: 100px;">
                                        <?php else: ?>
                                            <p class="text-muted small py-3">Sin Logo</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Subir Nuevo Logo (PNG/JPG)</label>
                                    <input type="file" class="form-control" name="logo" accept="image/png,image/jpeg,image/webp">
                                </div>
                            </div>
                            <hr>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Nombre del Gimnasio</label>
                                    <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars((string)($datos['nombre_sistema'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold text-success">Símbolo Moneda</label>
                                    <input type="text" class="form-control" name="moneda" value="<?= htmlspecialchars((string)($datos['moneda'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: $, S/, €" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">RUC / NIT</label>
                                    <input type="text" class="form-control" name="ruc" value="<?= htmlspecialchars((string)($datos['ruc'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" name="telefono" value="<?= htmlspecialchars((string)($datos['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="direccion" value="<?= htmlspecialchars((string)($datos['direccion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email de Contacto</label>
                                <input type="text" class="form-control" name="email" value="<?= htmlspecialchars((string)($datos['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>