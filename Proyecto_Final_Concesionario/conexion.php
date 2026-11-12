<?php
// Conexión a MySQL con PDO. Se incluye en todas las páginas que usan la base.
$host = 'localhost';
$base = 'concesionario';
$user = 'root';   // usuario por defecto de XAMPP
$pass = '';       // contraseña por defecto de XAMPP (vacía)

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$base;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // consultas preparadas reales
        ]
    );
} catch (PDOException $e) {
    // El detalle técnico va al log del servidor, nunca a la pantalla
    error_log('Error de conexión: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos.');
}
