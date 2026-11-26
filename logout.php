<?php

require_once __DIR__ . "/services/SesionService.php";
require_once __DIR__ . "/services/CsrfService.php";

iniciarSesionPhp();

header("Cache-Control: no-store");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    exit("Metodo no permitido.");
}

if (!SesionService::estaAutenticado()) {

    header(
        "Location: " . urlAplicacion("index.php"),
        true,
        303
    );

    exit;
}

CsrfService::exigirToken();

SesionService::cerrar();

header(
    "Location: " .
    urlAplicacion("index.php?sesion=cerrada"),
    true,
    303
);

exit;
