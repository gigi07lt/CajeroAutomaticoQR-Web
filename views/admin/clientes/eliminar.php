<?php

require_once __DIR__ .
    "/../../../middleware/roles.php";

require_once __DIR__ .
    "/../../../controllers/ClienteController.php";

// Verificar permisos administrativos.

exigirRol("ADMIN");

// Permitir exclusivamente solicitudes POST.

if (
    ($_SERVER["REQUEST_METHOD"] ?? "")
    !== "POST"
) {

    header("Allow: POST");

    http_response_code(405);

    exit("Metodo no permitido.");
}

// Verificar el token de seguridad.

CsrfService::exigirToken();

// Procesar la eliminacion.

try {

    $controlador = new ClienteController(
        obtenerConexion()
    );

    $resultado = $controlador->eliminar();

    // Guardar el resultado de la operacion.

    $controlador->guardarMensajeFlash(
        $resultado
    );

    // Regresar al listado de clientes.

    $controlador->redirigirListado();

} catch (Throwable $e) {

    error_log(
        "Eliminacion de cliente: " .
        $e->getMessage()
    );

    iniciarSesionPhp();

    $_SESSION["flash_clientes"] = [

        "exito" => false,

        "mensaje" =>
            "No se pudo procesar la eliminación. "
            . "Intenta nuevamente."

    ];

    header(
        "Location: " .
        urlAplicacion(
            "views/admin/clientes/index.php"
        ),
        true,
        303
    );

    exit;
}
