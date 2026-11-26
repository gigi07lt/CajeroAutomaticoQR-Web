<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../services/AutenticacionService.php";
require_once __DIR__ . "/../services/SesionService.php";
require_once __DIR__ . "/../services/CsrfService.php";
require_once __DIR__ . "/../services/LimiteIntentosService.php";

class AuthController
{
    public function iniciarSesion(): void
    {
        iniciarSesionPhp();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Metodo no permitido.");
        }

        CsrfService::exigirToken();

        $usuario = $_POST["usuario"] ?? "";
        $clave = $_POST["clave"] ?? "";
        $rol = $_POST["rol"] ?? "CLIENTE";

        if (
            !is_string($usuario) ||
            !is_string($clave) ||
            !is_string($rol)
        ) {
            $this->mostrarError(
                "Solicitud incorrecta.",
                "",
                "CLIENTE"
            );
        }

        try {

            $conexion = obtenerConexion();

            // Limitar solicitudes por direccion IP.

            $limitador = new LimiteIntentosService(
                $conexion
            );

            $ip = $_SERVER["REMOTE_ADDR"] ?? "desconocida";

            if (!$limitador->permitir($ip)) {

                $this->mostrarError(
                    "Demasiados intentos. Espera un minuto.",
                    $usuario,
                    $rol
                );
            }

            // Verificar credenciales.

            $servicio = new AutenticacionService(
                $conexion
            );

            $resultado = $servicio->autenticar(
                $usuario,
                $clave,
                $rol
            );

            if (!$resultado["exito"]) {

                $this->mostrarError(
                    $resultado["mensaje"],
                    $usuario,
                    $rol
                );
            }

            // Crear la sesion del usuario.

            SesionService::iniciar(
                $resultado["usuario"]
            );

            $this->redirigirSegunRol(
                $resultado["usuario"]["rol"]
            );

        } catch (Throwable $e) {

            error_log($e->getMessage());

            $this->mostrarError(
                "No se pudo iniciar sesion. Intenta nuevamente.",
                $usuario,
                $rol
            );
        }
    }

    private function mostrarError(
        string $mensaje,
        string $usuario,
        string $rol
    ): void {

        $_SESSION["error_login"] = $mensaje;

        $_SESSION["usuario_login"] = substr(
            $usuario,
            0,
            50
        );

        $_SESSION["rol_login"] =
            $rol === "ADMIN" ? "ADMIN" : "CLIENTE";

        header(
            "Location: " . urlAplicacion("index.php"),
            true,
            303
        );

        exit;
    }

    public function redirigirSegunRol(
        string $rol
    ): void {

        if ($rol === "ADMIN") {

            $ruta = "views/admin/index.php";

        } elseif ($rol === "CLIENTE") {

            $ruta = "views/cliente/index.php";

        } else {

            SesionService::cerrar();

            $ruta = "index.php";
        }

        header(
            "Location: " . urlAplicacion($ruta),
            true,
            303
        );

        exit;
    }
}
