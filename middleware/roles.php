<?php

require_once __DIR__ . "/auth.php";

function exigirRol(string $rol): array
{
    $usuario = exigirAutenticacion();

    if ($usuario["rol"] !== $rol) {

        http_response_code(403);

        exit(
            "Acceso denegado. " .
            "No tienes permisos para realizar esta accion."
        );
    }

    return $usuario;
}
