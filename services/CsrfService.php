<?php

require_once __DIR__ . "/../config/session.php";

class CsrfService
{
    public static function generarToken(): string
    {
        iniciarSesionPhp();

        if (empty($_SESSION["csrf_token"])) {

            $_SESSION["csrf_token"] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION["csrf_token"];
    }

    public static function validarToken(
        ?string $token
    ): bool {

        iniciarSesionPhp();

        if (
            $token === null ||
            $token === "" ||
            empty($_SESSION["csrf_token"])
        ) {
            return false;
        }

        return hash_equals(
            $_SESSION["csrf_token"],
            $token
        );
    }

    public static function exigirToken(): void
    {
        $token = $_POST["csrf_token"] ?? null;

        if (
            !is_string($token) ||
            !self::validarToken($token)
        ) {
            http_response_code(403);

            exit("Solicitud no autorizada.");
        }
    }
}
