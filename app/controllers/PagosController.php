<?php
require_once __DIR__.'/../lib/Auth.php';
require_once __DIR__.'/../models/PagoSuscripcion.php';
class PagosController {
    private PagoSuscripcion $pagos;

    public function __construct() {
        if(session_status()===PHP_SESSION_NONE) session_start();
        Auth::requerirRol(['admin','recepcionista']);
        $this->pagos=new PagoSuscripcion();
        if(!isset($_SESSION['csrf_pagos'])) $_SESSION['csrf_pagos']=bin2hex(random_bytes(24));
    }
    public function index(): void {
        $planes=$this->pagos->suscripcionesConSaldo();
        $historial=$this->pagos->historial();
        require_once __DIR__.'/../views/pagos/index.php';
    }
    public function guardar(): void {
        if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);return;}
        if(!is_string($_POST['csrf']??null)||
           !hash_equals($_SESSION['csrf_pagos'],$_POST['csrf'])){
            http_response_code(403);exit('Solicitud no autorizada.');
        }
        try {
            $monto=filter_var($_POST['monto']??null,FILTER_VALIDATE_FLOAT);
            if($monto===false) throw new InvalidArgumentException('Importe no válido.');
            $id=$this->pagos->registrar(
                (int)($_POST['suscripcion_id']??0),
                (int)$_SESSION['user_id'],
                (float)$monto,
                (string)($_POST['metodo']??''),
                trim((string)($_POST['referencia']??''))
            );
            header('Location: '.BASE_URL.'/pagos/comprobante/'.$id);
        }catch(Throwable $e){
            error_log('Error al registrar pago: '.$e->getMessage());
            $_SESSION['pagos_error']=$e instanceof DomainException ?
                $e->getMessage():'No se pudo registrar el pago. Verifica importe y caja abierta.';
            header('Location: '.BASE_URL.'/pagos/index?error=1');
        }
        exit;
    }
    public function comprobante($id): void {
        $pago=$this->pagos->obtener((int)$id);
        if(!$pago){http_response_code(404);exit('Pago no encontrado.');}
        require_once __DIR__.'/../views/pagos/comprobante.php';
    }
}
