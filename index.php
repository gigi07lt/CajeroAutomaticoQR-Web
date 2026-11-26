<?php

require_once __DIR__ . "/controllers/AuthController.php";

iniciarSesionPhp();

header("Cache-Control: no-store");

$controlador = new AuthController();

// Procesar el formulario de inicio de sesion.

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $controlador->iniciarSesion();
}

// Verificar si existe una sesion anterior.

if (SesionService::estaAutenticado()) {

    if (
        SesionService::validarUsuario(
            obtenerConexion()
        )
    ) {

        $usuario = SesionService::obtenerUsuario();

        $controlador->redirigirSegunRol(
            $usuario["rol"]
        );

    } else {

        SesionService::cerrar();

        iniciarSesionPhp();
    }
}

// Recuperar mensajes temporales.

$error = $_SESSION["error_login"] ?? "";

$usuarioAnterior =
    $_SESSION["usuario_login"] ?? "";

$rolSeleccionado =
    $_SESSION["rol_login"] ?? "CLIENTE";

unset(
    $_SESSION["error_login"],
    $_SESSION["usuario_login"],
    $_SESSION["rol_login"]
);

// Mensajes relacionados con la sesion.

$estadoSesion = $_GET["sesion"] ?? "";

$mensaje = "";

if ($estadoSesion === "expirada") {

    $mensaje = "Tu sesion ha expirado por inactividad.";

} elseif ($estadoSesion === "cerrada") {

    $mensaje = "Has cerrado sesion correctamente.";

} elseif ($estadoSesion === "invalida") {

    $mensaje = "Tu sesion ya no se encuentra disponible.";
}

require __DIR__ . "/views/auth/login.php";
