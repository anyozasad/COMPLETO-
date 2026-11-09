<?php
declare(strict_types=1);

/**
 * Comprobaciones de la semana 11.
 * No borra ni modifica datos: cada observación se obtiene de MySQL o de archivos reales.
 * Los conteos pueden ser 0 en una BD vacía; 0 no acredita por sí solo que el
 * flujo haya sido probado manualmente.
 */
class CierreSistema {
    private PDO $db;
    private string $raiz;

    public function __construct() {
        $this->db=(new Database())->getConnection();
        $this->raiz=dirname(__DIR__, 2);
    }

    private function contar(string $sql): int {
        return (int)$this->db->query($sql)->fetchColumn();
    }

    public function datos(): array {
        $checks=[
            ['socio','Nombre o DNI de socio incompleto',
                "SELECT COUNT(*) FROM socios WHERE nombre IS NULL OR TRIM(nombre)='' OR dni IS NULL OR TRIM(dni)='' OR CHAR_LENGTH(TRIM(dni))<>8"],
            ['socio','Correos duplicados de socios',
                "SELECT COUNT(*) FROM (SELECT LOWER(TRIM(email)) e FROM socios WHERE email IS NOT NULL AND TRIM(email)<>'' GROUP BY LOWER(TRIM(email)) HAVING COUNT(*)>1) x"],
            ['usuario','Usuarios sin nombre o correo',
                "SELECT COUNT(*) FROM usuarios WHERE nombre IS NULL OR TRIM(nombre)='' OR email IS NULL OR TRIM(email)=''"],
            ['producto','Productos sin nombre, precios o stock válidos',
                "SELECT COUNT(*) FROM productos WHERE nombre IS NULL OR TRIM(nombre)='' OR precio_compra IS NULL OR precio_compra<0 OR precio_venta IS NULL OR precio_venta<0 OR stock IS NULL OR stock<0"],
            ['producto','Códigos de producto duplicados',
                "SELECT COUNT(*) FROM (SELECT LOWER(TRIM(codigo)) c FROM productos WHERE codigo IS NOT NULL AND TRIM(codigo)<>'' GROUP BY LOWER(TRIM(codigo)) HAVING COUNT(*)>1) x"],
            ['reserva','Reservas activas para la misma fecha y hora',
                "SELECT COUNT(*) FROM (SELECT fecha,hora FROM reservas WHERE estado<>'cancelada' GROUP BY fecha,hora HAVING COUNT(*)>1) x"],
            ['suscripción','Suscripciones con fechas incorrectas',
                "SELECT COUNT(*) FROM suscripciones WHERE fecha_inicio IS NULL OR fecha_fin IS NULL OR fecha_fin<fecha_inicio"],
            ['suscripción','Inscripciones repetidas al mismo plan y fecha',
                "SELECT COUNT(*) FROM (SELECT socio_id,plan_id,fecha_inicio FROM suscripciones WHERE socio_id IS NOT NULL AND plan_id IS NOT NULL GROUP BY socio_id,plan_id,fecha_inicio HAVING COUNT(*)>1) x"],
            ['cobro','Pagos superiores al precio de una suscripción',
                "SELECT COUNT(*) FROM (SELECT ps.suscripcion_id FROM pagos_suscripciones ps JOIN suscripciones s ON s.id=ps.suscripcion_id JOIN planes p ON p.id=s.plan_id GROUP BY ps.suscripcion_id,p.precio HAVING SUM(ps.monto)>p.precio+0.01) x"],
            ['venta','Ventas sin detalle',
                "SELECT COUNT(*) FROM ventas v LEFT JOIN detalle_ventas d ON d.venta_id=v.id WHERE d.id IS NULL"],
            ['venta','Ventas con importes distintos de su detalle',
                "SELECT COUNT(*) FROM ventas v JOIN (SELECT venta_id,SUM(subtotal) bruto FROM detalle_ventas GROUP BY venta_id) d ON d.venta_id=v.id WHERE ABS(v.total-(d.bruto-v.descuento))>0.01"],
        ];
        $salida=[];
        foreach($checks as [$area,$nombre,$sql]){
            try {
                $cantidad=$this->contar($sql);
                $salida[]=[
                    'area'=>$area, 'nombre'=>$nombre,
                    'cantidad'=>$cantidad,'estado'=>$cantidad===0?'sin_hallazgos':'revisar'
                ];
            } catch(PDOException $e) {
                error_log('Diagnóstico EFSRT SQL: '.$e->getMessage());
                $salida[]=[
                    'area'=>$area, 'nombre'=>$nombre,
                    'cantidad'=>null,'estado'=>'error_consulta'
                ];
            }
        }
        return $salida;
    }

    public function imagenesPendientes(): array {
        $filas=$this->db->query("SELECT id,nombre,foto FROM productos ORDER BY id ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
        $carpeta=$this->raiz.'/public/img/productos/';
        $pendientes=[];
        foreach($filas as $p) {
            $archivo=(string)($p['foto']??'');
            if($archivo==='' || basename($archivo)!==$archivo || !is_file($carpeta.$archivo)){
                $pendientes[]=[
                    'id'=>(int)$p['id'],
                    'nombre'=>$p['nombre'],
                    'razon'=>$archivo===''?'No tiene fotografía':'La fotografía no está en la carpeta local'
                ];
            }
        }
        return $pendientes;
    }

    public function componentes(): array {
        $items=[
            ['Inicio de sesión administrativo','app/controllers/AuthController.php'],
            ['Registro y acceso de socio','app/controllers/PortalController.php'],
            ['Navegación del usuario','app/views/portal/index.php'],
            ['Dashboard','app/views/home/index.php'],
            ['Socios','app/controllers/SociosController.php'],
            ['Reservas','app/controllers/ReservasController.php'],
            ['Productos','app/controllers/InventarioController.php'],
            ['Pagos','app/controllers/PagosController.php'],
            ['Asistencia','app/controllers/AsistenciaController.php'],
            ['Seguimiento','app/controllers/ProgresoController.php'],
            ['Rutinas','app/controllers/RutinasController.php'],
            ['Entrenador principal','app/controllers/EntrenadorController.php'],
            ['API para móvil','app/controllers/ApiController.php'],
            ['Reporte de comprobación','app/controllers/CierreController.php'],
            ['Bootstrap local','public/vendor/bootstrap/bootstrap.min.css'],
            ['JavaScript local','public/vendor/bootstrap/bootstrap.bundle.min.js'],
            ['Iconos locales','public/vendor/fontawesome/css/all.min.css'],
            ['Gráficos locales','public/vendor/chartjs/chart.js'],
            ['Respaldo local','scripts/respaldo_efsrt.ps1'],
        ];
        $out=[];
        foreach($items as [$nombre,$ruta]){
            $archivo=$this->raiz.'/'.$ruta;
            $out[]=[
                'nombre'=>$nombre, 'ruta'=>$ruta,
                'presente'=>is_file($archivo) && filesize($archivo)>0
            ];
        }
        return $out;
    }

    public function resumen(): array {
        $datos=$this->datos();
        $componentes=$this->componentes();
        $imagenes=$this->imagenesPendientes();
        return [
            'generado_en'=>date('Y-m-d H:i:s'),
            'tipo'=>'diagnostico_tecnico_local',
            'aclaracion'=>'No sustituye prueba manual, firma ni validación del supervisor.',
            'datos'=>$datos,
            'imagenes_pendientes'=>$imagenes,
            'componentes'=>$componentes,
            'conteos'=>[
                'observaciones_bd'=>array_sum(array_map(
                    static fn($x)=>is_int($x['cantidad'])?$x['cantidad']:0,$datos)),
                'consultas_sin_resultado'=>count(array_filter(
                    $datos,static fn($x)=>$x['estado']==='error_consulta')),
                'productos_sin_imagen_local'=>count($imagenes),
                'archivos_faltantes'=>count(array_filter(
                    $componentes,static fn($x)=>!$x['presente']))
            ],
        ];
    }
}
