<?php
/**
 * Conexión a la Base de Datos
 * Sistema de Gestión Escolar - Colegio Pedagógico
 */

$host     = "localhost";
$dbname   = "colegio_pedagogico";
$usuario  = "root";
$password = "";
$charset  = "utf8mb4";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $conexion = new PDO($dsn, $usuario, $password, $opciones);
} catch (PDOException $e) {
    // Si se está ejecutando desde probar_conexion.php, propagar la excepción para el diagnóstico visual
    if (defined('PROBAR_CONEXION')) {
        throw $e;
    }
    die("❌ Error de conexión a la base de datos: " . $e->getMessage());
}