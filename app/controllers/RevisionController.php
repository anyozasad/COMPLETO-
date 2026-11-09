<?php
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../models/RevisionSemana05.php';
require_once __DIR__ . '/../models/Reserva.php';

class RevisionController {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        Auth::requerirRol(['admin']);
    }

    public function index(): void {
        // La creación de la tabla de reservas ocurre desde el modelo oficial.
        new Reserva();
        $modelo = new RevisionSemana05();
        $resumen = $modelo->resumen();
        $cobros = $modelo->pagosVsSocios();
        $cajas = $modelo->comparacionCajas();
        $socios = $modelo->sociosConActividad();
        $imagenesPendientes = $modelo->productosSinImagen();
        $estados = $modelo->reservasPorEstado();

        $cobrosInconsistentes = array_values(array_filter($cobros, static function ($v) {
            return (int)$v['lineas'] === 0 || (float)$v['diferencia'] > 0.01;
        }));
        $cajasInconsistentes = array_values(array_filter($cajas, static function ($c) {
            return abs((float)$c['diferencia']) > 0.01;
        }));

        require_once __DIR__ . '/../views/revision/index.php';
    }
}
