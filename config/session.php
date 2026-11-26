<?php

const TIEMPO_SESION_CLIENTE = 300;
const TIEMPO_SESION_ADMIN = 900;

function iniciarSesionPhp(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    if (headers_sent($archivo, $linea)) {

    throw new RuntimeException(
        "No se puede iniciar la sesion. " .
        "Salida previa en: " . $archivo .
        ", linea: " . $linea
    );
}
    $https = isset($_SERVER["HTTPS"]) &&
             $_SERVER["HTTPS"] !== "off" &&
             $_SERVER["HTTPS"] !== "";

    ini_set("session.use_strict_mode", "1");

    ini_set("session.use_only_cookies", "1");

    ini_set("session.use_trans_sid", "0");

    ini_set("session.gc_maxlifetime", "1800");

    session_name("BANCO_FISEI_SESION");

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => $https,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

if (!session_start()) {
    throw new RuntimeException(
        "No se pudo iniciar la sesion PHP."
    );
}
}

function urlAplicacion(string $ruta = ""): string
{
    static $base = null;

    if ($base === null) {

        $config = require __DIR__ . "/config.php";

        $base = rtrim(
            $config["base_path"] ?? "/CajeroAutomaticoQR-Web",
            "/"
        );
    }

    return $base . "/" . ltrim($ruta, "/");
}
