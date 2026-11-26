<?php

require_once __DIR__ . "/../config/database.php";

require_once __DIR__ . "/../services/SesionService.php";

function exigirAutenticacion(): array
{
    iniciarSesionPhp();

    header("Cache-Control: no-store");

    if (!SesionService::estaAutenticado()) {

        header(
            "Location: " . urlAplicacion("index.php")
        );

        exit;
    }

    if (SesionService::haExpirado()) {

        SesionService::cerrar();

        header(
            "Location: " .
            urlAplicacion("index.php?sesion=expirada")
        );

        exit;
    }

    $conexion = obtenerConexion();

    if (!SesionService::validarUsuario($conexion)) {

        SesionService::cerrar();

        header(
            "Location: " .
            urlAplicacion("index.php?sesion=invalida")
        );

        exit;
    }

    SesionService::actualizarActividad();

    return SesionService::obtenerUsuario();
}
