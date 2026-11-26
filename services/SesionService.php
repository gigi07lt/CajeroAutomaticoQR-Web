<?php

require_once __DIR__ . "/../config/session.php";

require_once __DIR__ . "/../models/Usuario.php";

class SesionService
{
    public static function iniciar(array $usuario): void
    {
        iniciarSesionPhp();

        if (!session_regenerate_id(true)) {

    throw new RuntimeException(
        "No se pudo regenerar el identificador de sesion."
    );
}

        $_SESSION = [];

        $_SESSION["usuario"] = [
            "id" => (int) $usuario["id"],

            "id_cliente" => $usuario["id_cliente"],

            "usuario" => $usuario["usuario"],

            "rol" => $usuario["rol"]
        ];

        $_SESSION["ultima_actividad"] = time();
    }

    public static function obtenerUsuario(): ?array
    {
        iniciarSesionPhp();

        return $_SESSION["usuario"] ?? null;
    }

    public static function estaAutenticado(): bool
    {
        return self::obtenerUsuario() !== null;
    }

    public static function tiempoPermitido(): int
    {
        $usuario = self::obtenerUsuario();

        if ($usuario === null) {
            return 0;
        }

        return $usuario["rol"] === "ADMIN"
            ? TIEMPO_SESION_ADMIN
            : TIEMPO_SESION_CLIENTE;
    }

    public static function haExpirado(): bool
    {
        if (!self::estaAutenticado()) {
            return true;
        }

        $ultimaActividad =
            $_SESSION["ultima_actividad"] ?? 0;

        $tiempoTranscurrido = time() - $ultimaActividad;

        return $tiempoTranscurrido >=
               self::tiempoPermitido();
    }

    public static function actualizarActividad(): void
    {
        if (self::estaAutenticado()) {
            $_SESSION["ultima_actividad"] = time();
        }
    }

    public static function tieneRol(string $rol): bool
    {
        $usuario = self::obtenerUsuario();

        return $usuario !== null &&
               $usuario["rol"] === $rol;
    }

    public static function validarUsuario(
        PDO $conexion
    ): bool {

        $usuario = self::obtenerUsuario();

        if ($usuario === null || self::haExpirado()) {
            return false;
        }

        $modelo = new Usuario($conexion);

        $datos = $modelo->buscarPorId(
            (int) $usuario["id"]
        );

        if ($datos === null) {
            return false;
        }

        if (
            !(bool) $datos["estado"] ||
            (bool) $datos["bloqueado"]
        ) {
            return false;
        }

        if ($datos["rol"] !== $usuario["rol"]) {
            return false;
        }

        $idClienteActual = $datos["id_cliente"] !== null
            ? (int) $datos["id_cliente"]
            : null;

        if ($idClienteActual !== $usuario["id_cliente"]) {
            return false;
        }

        return true;
    }

    public static function cerrar(): void
    {
        iniciarSesionPhp();

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {

            $parametros = session_get_cookie_params();

            setcookie(
                session_name(),
                "",
                [
                    "expires" => time() - 42000,
                    "path" => $parametros["path"],
                    "domain" => $parametros["domain"],
                    "secure" => $parametros["secure"],
                    "httponly" => $parametros["httponly"],
                    "samesite" => "Lax"
                ]
            );
        }

        session_destroy();
    }
}
