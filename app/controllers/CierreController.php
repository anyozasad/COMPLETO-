<?php
declare(strict_types=1);

require_once __DIR__.'/../lib/Auth.php';
require_once __DIR__.'/../models/CierreSistema.php';
require_once __DIR__.'/../models/Reserva.php';
require_once __DIR__.'/../models/PagoSuscripcion.php';

class CierreController {
    public function __construct() {
        if(session_status()===PHP_SESSION_NONE) session_start();
        Auth::requerirRol(['admin']);
    }

    private function diagnostico(): array {
        // Compatibilidad con instalaciones creadas antes de los módulos.
        new Reserva();
        new PagoSuscripcion();
        return (new CierreSistema())->resumen();
    }

    public function index(): void {
        $diagnostico=$this->diagnostico();
        require_once __DIR__.'/../views/cierre/index.php';
    }

    public function exportar(): void {
        $diagnostico=$this->diagnostico();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="diagnostico_EFSRT_semana11_'.date('Ymd_His').'.json"');
        echo json_encode($diagnostico,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit;
    }
}
