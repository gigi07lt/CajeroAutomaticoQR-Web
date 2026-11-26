<?php

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Acceso no permitido.");
}

require_once __DIR__ . "/../config/database.php";

try {

    $conexion = obtenerConexion();

    echo "Conexion establecida correctamente.\n\n";

    $tablas = [
        "clientes",
        "usuarios",
        "cuentas",
        "tarjetas",
        "movimientos"
    ];

    foreach ($tablas as $tabla) {

        $resultado = $conexion->query(
            "SELECT COUNT(*) FROM " . $tabla
        );

        $cantidad = $resultado->fetchColumn();

        echo $tabla . ": "
             . $cantidad . " registros\n";
    }

} catch (Throwable $e) {

    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}