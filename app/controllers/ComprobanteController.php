<?php
define('FPDF_FONTPATH', '../app/lib/fpdf/font/');
require_once '../app/lib/fpdf/fpdf.php';
require_once '../app/config/Database.php';
require_once '../app/models/Configuracion.php';

class ComprobanteController {
    
    private function verificarAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }
    }

    private function texto($str) {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
    }

    public function generar($id) {
        $this->verificarAuth();
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT s.*, so.nombre as socio, so.dni, so.email, p.nombre as plan, p.precio 
                  FROM suscripciones s
                  INNER JOIN socios so ON s.socio_id = so.id
                  INNER JOIN planes p ON s.plan_id = p.id
                  WHERE s.id = :id LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$datos) { die("Error: El comprobante no existe."); }

        $configModel = new Configuracion();
        $empresa = $configModel->obtenerDatos();
        
        // --- VARIABLE MONEDA ---
        $simbolo = $empresa['moneda']; 

        $pdf = new FPDF('P','mm','A4');
        $pdf->AddPage();

        // Barra superior decorativa
        $pdf->SetFillColor(99, 102, 241); // Indigo color
        $pdf->Rect(0, 0, 210, 6, 'F');
        $pdf->Ln(5);

        // Logo y Encabezado
        $hay_logo = false;
        if (!empty($empresa['logo'])) {
            $ruta_logo = '../public/img/' . $empresa['logo'];
            if (file_exists($ruta_logo)) {
                $pdf->Image($ruta_logo, 10, 12, 25); 
                $hay_logo = true;
            }
        }

        $pdf->SetFont('Arial','B',20);
        $pdf->SetTextColor(33, 37, 41);
        if($hay_logo) { $pdf->Cell(28); } 
        $pdf->Cell(0, 8, $this->texto($empresa['nombre_sistema']), 0, 1, 'L');
        
        $pdf->SetFont('Arial','B',10);
        $pdf->SetTextColor(99, 102, 241);
        if($hay_logo) { $pdf->Cell(28); }
        $pdf->Cell(0, 5, 'COMPROBANTE DE PAGO', 0, 1, 'L');
        
        $pdf->SetFont('Arial','',9);
        $pdf->SetTextColor(100, 100, 100);
        if($hay_logo) { $pdf->Cell(28); }
        $pdf->Cell(0, 5, $this->texto('RUC: ' . $empresa['ruc'] . '  |  Tel: ' . $empresa['telefono']), 0, 1, 'L');

        if($hay_logo) { $pdf->Cell(28); }
        $pdf->Cell(0, 5, $this->texto($empresa['direccion']), 0, 1, 'L');
        
        $pdf->SetTextColor(0, 0, 0); // Reset text color
        
        $pdf->Ln(12);

        // Cliente Box
        $pdf->SetFillColor(250, 250, 252);
        $pdf->SetDrawColor(220, 220, 225);
        $pdf->Rect(10, $pdf->GetY(), 190, 26, 'DF'); // Draw box
        
        $pdf->Ln(3); // Padding inside box
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(25, 6, '  Socio:', 0, 0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(95, 6, $this->texto($datos['socio']), 0, 0);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(30, 6, 'Comprobante:', 0, 0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(40, 6, str_pad($datos['id'], 6, '0', STR_PAD_LEFT), 0, 1);
        
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(25, 6, '  DNI:', 0, 0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(95, 6, $datos['dni'], 0, 0);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(30, 6, 'Emision:', 0, 0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(40, 6, date('d/m/Y'), 0, 1);
        
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(25, 6, '  Email:', 0, 0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(95, 6, $this->texto($datos['email']), 0, 1);
        
        $pdf->Ln(10); // Spacing before table

        // Detalle
        $pdf->SetFont('Arial','B',11);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->Cell(190, 8, $this->texto('   DETALLE DE LA SUSCRIPCION'), 0, 1, 'L');
        
        // Headers with lighter gray and subtle borders
        $pdf->SetFillColor(245, 245, 248); 
        $pdf->SetDrawColor(200, 200, 200); 
        $pdf->SetLineWidth(0.2);
        $pdf->SetFont('Arial','B',9);
        $pdf->SetTextColor(100, 100, 100);

        $pdf->Cell(25, 9, ' ID PLAN', 'B', 0, 'C', true);
        $pdf->Cell(75, 9, $this->texto(' DESCRIPCION'), 'B', 0, 'L', true);
        $pdf->Cell(50, 9, ' PERIODO', 'B', 0, 'C', true);
        $pdf->Cell(40, 9, ' IMPORTE', 'B', 1, 'R', true);

        // Body with bottom border
        $pdf->SetFont('Arial','',10);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->Cell(25, 10, $datos['plan_id'], 'B', 0, 'C');
        $pdf->Cell(75, 10, $this->texto(" " . $datos['plan']), 'B', 0, 'L');
        $fecha_txt = date('d/m/Y', strtotime($datos['fecha_inicio'])) . " - " . date('d/m/Y', strtotime($datos['fecha_fin']));
        $pdf->Cell(50, 10, $fecha_txt, 'B', 0, 'C');
        
        // --- USO DE MONEDA DINÁMICA ---
        $pdf->Cell(40, 10, $simbolo . ' ' . number_format($datos['precio'], 2) . ' ', 'B', 1, 'R');

        // Totales
        $pdf->Ln(2);
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(150, 10, 'TOTAL PAGADO: ', 0, 0, 'R');
        $pdf->SetTextColor(99, 102, 241); // Indigo color for emphasis
        $pdf->Cell(40, 10, $simbolo . ' ' . number_format($datos['precio'], 2) . ' ', 0, 1, 'R');
        $pdf->SetTextColor(0, 0, 0); // Reset text color

        $pdf->Ln(30);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(65, $pdf->GetY(), 145, $pdf->GetY());
        $pdf->Ln(2);
        $pdf->SetFont('Arial','',9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, $this->texto('Firma Conformidad'), 0, 1, 'C');

        $pdf->Ln(15);
        $pdf->SetFont('Arial','I',8);
        $pdf->Cell(0, 5, $this->texto('Gracias por su preferencia - Documento generado el ' . date('d/m/Y H:i')), 0, 1, 'C');
        $pdf->Cell(0, 5, $this->texto('Cualquier duda, contáctenos en ' . $empresa['email']), 0, 1, 'C');

        $pdf->Output('I', 'Comprobante_'.$datos['id'].'.pdf');
    }
}