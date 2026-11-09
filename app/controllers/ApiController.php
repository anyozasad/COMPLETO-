<?php
require_once __DIR__.'/../models/Portal.php';
require_once __DIR__.'/../models/Reserva.php';
require_once __DIR__.'/../models/TokenApi.php';
require_once __DIR__.'/../models/RutinaRecomendada.php';

/**
 * API compartida para el cliente móvil y el dashboard PHP.
 * Rutas: /api/salud, /api/productos, /api/entrenador,
 * /api/registrar, /api/login, /api/reservas, /api/reservar,
 * /api/cancelar, /api/rutina, /api/logout
 */
class ApiController {
    private Portal $portal;
    private Reserva $reservas;
    private TokenApi $tokens;

    public function __construct() {
        $this->portal=new Portal();
        $this->reservas=new Reserva();
        $this->tokens=new TokenApi();
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
    }

    private function responder(array $datos,int $codigo=200): void {
        http_response_code($codigo);
        echo json_encode($datos,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit();
    }
    private function solo(string $metodo): void {
        if($_SERVER['REQUEST_METHOD']!==$metodo) {
            header('Allow: '.$metodo);
            $this->responder(['ok'=>false,'mensaje'=>'Método no permitido'],405);
        }
    }
    private function cuerpo(): array {
        $tipo=$_SERVER['CONTENT_TYPE']??'';
        if(stripos($tipo,'application/json')===false) {
            $this->responder(['ok'=>false,'mensaje'=>'Usar application/json'],415);
        }
        $raw=file_get_contents('php://input');
        if($raw===false || strlen($raw)>16384) {
            $this->responder(['ok'=>false,'mensaje'=>'Contenido inválido o demasiado grande'],400);
        }
        $data=json_decode($raw,true);
        if(!is_array($data) || json_last_error()!==JSON_ERROR_NONE) {
            $this->responder(['ok'=>false,'mensaje'=>'JSON incorrecto'],400);
        }
        return $data;
    }
    private function bearer(): string {
        $header=(string)($_SERVER['HTTP_AUTHORIZATION']
            ??$_SERVER['REDIRECT_HTTP_AUTHORIZATION']??'');
        return preg_match('/^Bearer\s+(\S+)$/i',$header,$m)?$m[1]:'';
    }
    private function acceso(): array {
        $cliente=$this->tokens->autenticar($this->bearer());
        if(!$cliente) $this->responder(['ok'=>false,'mensaje'=>'Acceso no autorizado'],401);
        return $cliente;
    }
    private function fallo(Throwable $e,string $codigo='Error al procesar datos'): void {
        error_log('API gym: '.$e->getMessage());
        $this->responder(['ok'=>false,'mensaje'=>$codigo],422);
    }

    public function salud(): void {
        $this->solo('GET');
        $this->responder(['ok'=>true,'servicio'=>'Mallqui Gym API','version'=>1]);
    }
    public function productos(): void {
        $this->solo('GET');
        $lista=$this->portal->productos();
        foreach($lista as &$p) {
            $nombre=basename((string)($p['foto']??''));
            $p['foto_path']=$nombre!=='' && is_file(__DIR__.'/../../public/img/productos/'.$nombre)
                ? BASE_URL.'/img/productos/'.rawurlencode($nombre)
                : null;
        }
        unset($p);
        $this->responder(['ok'=>true,'productos'=>$lista]);
    }
    public function entrenador(): void {
        $this->solo('GET');
        $entrenador=$this->portal->entrenador();
        if($entrenador) {
            $foto=basename((string)($entrenador['foto']??''));
            $entrenador['foto_path']=$foto!=='' &&
                is_file(__DIR__.'/../../public/img/entrenadores/'.$foto)
                ? BASE_URL.'/img/entrenadores/'.rawurlencode($foto) : null;
        }
        $this->responder(['ok'=>true,'entrenador'=>$entrenador]);
    }
    public function registrar(): void {
        $this->solo('POST');
        $d=$this->cuerpo();
        try {
            $id=$this->portal->registrar(
                trim((string)($d['nombre']??'')),
                trim((string)($d['dni']??'')),
                trim((string)($d['email']??'')),
                (string)($d['password']??''),
                trim((string)($d['telefono']??''))
            );
            $this->responder(['ok'=>true,'mensaje'=>'Cuenta creada. Pendiente de validación.','cliente_id'=>$id],201);
        } catch(Throwable $e) {$this->fallo($e,'No se pudo registrar. Verifica los datos o consulta al gimnasio.');}
    }
    public function login(): void {
        $this->solo('POST');
        $d=$this->cuerpo();
        $c=$this->portal->autenticar(
            trim((string)($d['email']??'')),
            (string)($d['password']??'')
        );
        if(!$c) $this->responder(['ok'=>false,'mensaje'=>'Credenciales incorrectas'],401);
        $token=$this->tokens->crear((int)$c['id']);
        $this->responder(['ok'=>true,'usuario'=>[
            'id'=>(int)$c['id'],'socio_id'=>(int)$c['socio_id'],
            'nombre'=>$c['nombre'],'estado'=>$c['estado']
        ],'token'=>$token]);
    }
    public function reservas(): void {
        $this->solo('GET');
        $c=$this->acceso();
        $this->responder(['ok'=>true,
            'reservas'=>$this->reservas->obtenerPorCliente((int)$c['cliente_id'])]);
    }
    public function reservar(): void {
        $this->solo('POST');
        $c=$this->acceso();
        $d=$this->cuerpo();
        $fecha=trim((string)($d['fecha']??''));
        $hora=trim((string)($d['hora']??''));
        $tipo=trim((string)($d['tipo']??'sesion'));
        $f=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha);
        if(!$f || $f->format('Y-m-d')!==$fecha || $fecha<date('Y-m-d') ||
           !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$hora) ||
           !in_array($tipo,['sesion','clase','evaluacion'],true)) {
            $this->responder(['ok'=>false,'mensaje'=>'Fecha, hora o tipo inválidos'],422);
        }
        if($this->reservas->existeConflicto($fecha,$hora.':00')) {
            $this->responder(['ok'=>false,'mensaje'=>'Horario ya reservado'],409);
        }
        try {
            $creada=$this->reservas->crearDesdePortal(
                (int)$c['cliente_id'],(int)$c['socio_id'],$fecha,$hora.':00',$tipo);
            if(!$creada) throw new RuntimeException('No se pudo registrar.');
            $this->responder(['ok'=>true,'mensaje'=>'Reserva pendiente de confirmación'],201);
        } catch(Throwable $e) {$this->fallo($e,'No se pudo crear la reserva');}
    }
    public function cancelar(): void {
        $this->solo('POST');
        $c=$this->acceso();
        $d=$this->cuerpo();
        $id=filter_var($d['id']??null,FILTER_VALIDATE_INT);
        if($id===false || $id<1) {
            $this->responder(['ok'=>false,'mensaje'=>'Reserva inválida'],422);
        }
        $ok=$this->reservas->cancelarDesdePortal((int)$c['cliente_id'],$id);
        $this->responder(['ok'=>$ok,'mensaje'=>$ok?'Reserva cancelada':'No se puede cancelar'],
            $ok?200:403);
    }
    public function rutina(): void {
        $this->solo('GET');
        $c=$this->acceso();
        $p=(new RutinaRecomendada())->obtener((int)$c['socio_id']);
        $r=$this->portal->rutinasPorSocio((int)$c['socio_id']);
        $hoy=$p?RutinaRecomendada::propuesta($p['nivel'],$p['tipo'],(int)date('N')):null;
        $this->responder(['ok'=>true,'programa'=>$p,'rutina_asignada'=>$r,'sugerencia_hoy'=>$hoy]);
    }
    public function logout(): void {
        $this->solo('POST');
        $this->acceso();
        $this->tokens->revocar($this->bearer());
        $this->responder(['ok'=>true,'mensaje'=>'Sesión cerrada']);
    }
}
