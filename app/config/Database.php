<?php
declare(strict_types=1);

date_default_timezone_set('America/Lima');

/**
 * Conexión MySQL compartida para PHP, dashboard y API.
 * En Laragon / XAMPP funciona con DB_HOST=localhost, DB_USER=root y
 * contraseña vacía; en CI se configuran variables de entorno.
 */
class Database {
    public function getConnection(): PDO {
        $host=getenv('DB_HOST') ?: '127.0.0.1';
        $name=getenv('DB_NAME') ?: 'gym_system';
        $user=getenv('DB_USER') ?: 'root';
        $pass=getenv('DB_PASS');
        if ($pass===false) $pass='';

        return new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false
            ]
        );
    }
}
