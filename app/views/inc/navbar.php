<?php
// 1. Cargar el modelo de Configuración de forma segura
require_once __DIR__ . '/../../models/Configuracion.php';
if (file_exists(__DIR__ . '/../../lib/Auth.php')) require_once __DIR__ . '/../../lib/Auth.php';

// 2. Obtener los datos de la empresa
$config = Configuracion::getInfo();
$_rolActual = $_SESSION['user_rol'] ?? '';

// 3. Helper para clase activa
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
$is_active = function($path) use ($current_uri) {
    // Check if the current URI starts with the path (e.g., /socios matches /socios/index and /socios/crear)
    if ($path === '/home/index') {
        return ($current_uri === '/' || strpos($current_uri, '/home') === 0) ? 'active' : '';
    }
    return strpos($current_uri, $path) === 0 ? 'active' : '';
};
?>

<link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
<style>
  /* Base wrapper logic for mobile sidebar - Theme styles are in gym-theme.css */
  #sidebar-wrapper {
    width: 260px;
    transition: margin .25s ease-out;
  }
  #page-content-wrapper {
    width: 100%;
    min-width: 0;
    transition: all 0.2s ease-out;
  }
  @media (max-width: 768px) {
    #sidebar-wrapper {
      margin-left: -260px;
      position: fixed;
      z-index: 1050;
      height: 100%;
    }
    #wrapper.toggled #sidebar-wrapper {
      margin-left: 0;
    }
    /* Añadir capa oscura sobre el contenido al abrir el menú en móviles */
    #wrapper.toggled::after {
        content: '';
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
    }
  }
</style>

<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="bg-white shadow-sm" id="sidebar-wrapper">
        <!-- Sidebar Brand / Logo -->
        <div class="sidebar-heading p-4 d-flex align-items-center justify-content-center">
            <?php if(!empty($config['logo'])): ?>
                <img src="/img/<?= $config['logo'] ?>?v=<?= time() ?>" 
                     alt="Logo" width="30" height="30" 
                     class="d-inline-block align-text-top rounded-circle me-2">
            <?php endif; ?>
            <span class="fs-5 fw-bold text-dark tracking-wider"><?= $config['nombre_sistema'] ?></span>
            <button class="btn btn-sm text-dark ms-auto d-md-none" id="close-menu-btn"><i class="fas fa-times fs-4"></i></button>
        </div>

        <!-- User Profile Area -->
        <div class="text-center mt-3 mb-4 px-4">
            <div class="position-relative d-inline-block mb-3">
                <div class="bg-light rounded-circle shadow-sm d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; border: 3px solid #E0E7FF;">
                    <i class="fas fa-user text-muted" style="font-size: 2.5rem;"></i>
                </div>
                <!-- Online status dot -->
                <span class="position-absolute bottom-0 end-0 p-2 bg-success border border-light rounded-circle" style="transform: translate(-10px, -5px);"></span>
            </div>
            <h6 class="fw-bold text-dark mb-1"><?= $_SESSION['user_name'] ?? 'Usuario' ?></h6>
            <p class="text-muted small mb-0"><?= ucfirst($_SESSION['user_rol'] ?? 'Invitado') ?></p>
        </div>

        <!-- Action Bubbles -->
        <div class="d-flex justify-content-center gap-3 mb-4 px-3">
            <a href="/notificaciones/index" class="btn btn-primary rounded-circle shadow p-0 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: linear-gradient(135deg, #8B5CF6, #6D28D9); border:none;">
                <i class="fas fa-envelope text-white"></i>
            </a>
            <a href="/asistencia/index" class="btn rounded-circle shadow p-0 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: linear-gradient(135deg, #EC4899, #BE185D); border:none;">
                <i class="fas fa-bell text-white"></i>
            </a>
            <a href="/configuracion/index" class="btn rounded-circle shadow p-0 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: linear-gradient(135deg, #F97316, #D97706); border:none;">
                <i class="fas fa-cog text-white"></i>
            </a>
        </div>
        
        <div id="sidebar-nav-scroll">
            <div class="list-group list-group-flush mt-2 px-2">
                
                <p class="text-dark fw-bold ms-3 mt-2 mb-2 fs-6">Inicio</p>
                <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/home') ?>" href="/home/index">
                    <i class="fas fa-home w-20px text-center"></i> Dashboard
                </a>
                <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/asistencia') ?>" href="/asistencia/index">
                    <i class="fas fa-clock w-20px text-center"></i> Asistencia
                </a>
                
                <p class="text-dark fw-bold ms-3 mt-4 mb-2 fs-6">Gestión</p>
                <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/socios') ?>" href="/socios/index">
                    <i class="fas fa-users w-20px text-center"></i> Socios
                </a>

                <?php if($_rolActual === 'admin' || $_rolActual === 'recepcionista'): ?>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/caja') ?>" href="/caja/index">
                        <i class="fas fa-cash-register w-20px text-center"></i> Caja
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/suscripciones') ?>" href="/suscripciones/index">
                        <i class="fas fa-file-invoice-dollar w-20px text-center"></i> Suscripciones
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/pos') ?>" href="/pos/index">
                        <i class="fas fa-shopping-cart w-20px text-center"></i> Punto de Venta
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/notificaciones') ?>" href="/notificaciones/index">
                        <i class="fab fa-whatsapp w-20px text-center"></i> Notificaciones
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/pos/historial') ?>" href="/pos/historial">
                        <i class="fas fa-history w-20px text-center"></i> Historial de Ventas
                    </a>
                <?php endif; ?>

                <?php if($_rolActual === 'admin'): ?>
                    <p class="text-dark fw-bold ms-3 mt-4 mb-2 fs-6">Administración</p>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/planes') ?>" href="/planes/index">
                        <i class="fas fa-tags w-20px text-center"></i> Planes
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/gastos') ?>" href="/gastos/index">
                        <i class="fas fa-money-bill-wave w-20px text-center"></i> Gastos
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/inventario/productos') ?>" href="/inventario/productos">
                        <i class="fas fa-boxes w-20px text-center"></i> Inventario
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/inventario/categorias') ?>" href="/inventario/categorias">
                        <i class="fas fa-layer-group w-20px text-center"></i> Categorías
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/reportes') ?>" href="/reportes/index">
                        <i class="fas fa-chart-line w-20px text-center"></i> Reportes
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/asistencia/reporte') ?>" href="/asistencia/reporte">
                        <i class="fas fa-clipboard-list w-20px text-center"></i> Rep. Asistencias
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/usuarios') ?>" href="/usuarios/index">
                        <i class="fas fa-user-shield w-20px text-center"></i> Usuarios
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/mantenimiento') ?>" href="/mantenimiento/index">
                        <i class="fas fa-tools w-20px text-center"></i> Mantenimiento
                    </a>
                <?php endif; ?>
                <p class="text-dark fw-bold ms-3 mt-4 mb-2 fs-6">Sistema</p>
                <a class="list-group-item list-group-item-action rounded mb-1 text-danger" href="/auth/logout">
                    <i class="fas fa-sign-out-alt w-20px text-center"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
    <!-- /#sidebar-wrapper -->

    <!-- Save Navbar Scroll Position Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var sidebar = document.getElementById('sidebar-wrapper');
            var navScroll = document.getElementById('sidebar-nav-scroll');
            
            // Restore scroll position
            var scrollPos = localStorage.getItem('gymSidebarScrollPos');
            if (scrollPos) {
                if(sidebar) sidebar.scrollTop = parseInt(scrollPos, 10);
                if(navScroll) navScroll.scrollTop = parseInt(scrollPos, 10);
            }

            // Save scroll position
            window.addEventListener('beforeunload', function() {
                var currentScroll = (navScroll && navScroll.scrollTop > 0) ? navScroll.scrollTop : (sidebar ? sidebar.scrollTop : 0);
                localStorage.setItem('gymSidebarScrollPos', currentScroll);
            });
        });
    </script>

    <!-- Page Content -->
    <div id="page-content-wrapper" class="d-flex flex-column" style="min-height: 100vh; background-color: var(--gym-bg-light);">
        
        <!-- Mobile Navbar (Only visible on small screens) -->
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2 d-md-none shadow-sm">
            <button class="btn btn-light shadow-sm text-dark rounded-3" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <span class="fw-bold text-dark"><?= $config['nombre_sistema'] ?></span>
            <div style="width:30px;"></div>
        </nav>

        <?php if (!empty($_SESSION['acceso_denegado'])): ?>
        <div class="alert alert-warning alert-dismissible border-0 m-3 shadow-sm" role="alert">
            <i class="fas fa-shield-alt me-2"></i>
            <?= htmlspecialchars($_SESSION['acceso_denegado']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['acceso_denegado']); endif; ?>

        <!-- Main content container -->
        <div class="container-fluid p-4 flex-grow-1">