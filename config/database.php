<?php
// Zona horaria de Ecuador continental.
date_default_timezone_set('America/Guayaquil');
function obtenerConexion(): PDO
{
    static $conexion = null;

    if ($conexion !== null) {
        return $conexion;
    }

    $config = require __DIR__ . "/config.php";

    $dsn = "mysql:host=" . $config["host"]
         . ";port=" . $config["port"]
         . ";dbname=" . $config["database"]
         . ";charset=utf8mb4";

    $conexion = new PDO(
        $dsn,
        $config["username"],
        $config["password"],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $conexion->exec("SET time_zone = '-05:00'");

    return $conexion;
}