<?php
class Configuracion {
    private $conn;
    private $table_name = "configuracion";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function obtenerDatos() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- ACTUALIZADO CON MONEDA ---
    public function actualizar($datos) {
        $query = "UPDATE " . $this->table_name . " 
                 SET nombre_sistema = :nombre, 
                     ruc = :ruc, 
                     direccion = :dir, 
                     telefono = :tel, 
                     email = :email, 
                     logo = :logo,
                     moneda = :moneda
                 WHERE id = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $datos['nombre']);
        $stmt->bindParam(":ruc", $datos['ruc']);
        $stmt->bindParam(":dir", $datos['direccion']);
        $stmt->bindParam(":tel", $datos['telefono']);
        $stmt->bindParam(":email", $datos['email']);
        $stmt->bindParam(":logo", $datos['logo']);
        $stmt->bindParam(":moneda", $datos['moneda']); // Nuevo
        
        return $stmt->execute();
    }

    public static function getInfo() {
        $database = new Database();
        $conn = $database->getConnection();
        $query = "SELECT * FROM configuracion WHERE id = 1 LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}