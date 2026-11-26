<?php

require_once __DIR__ . "/../models/Usuario.php";

class AutenticacionService
{
    private PDO $conexion;
    private Usuario $usuarioModelo;

    private const MAX_INTENTOS = 3;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;

        $this->usuarioModelo = new Usuario($conexion);
    }

    public function autenticar(
        string $usuario,
        string $clave,
        string $rol
    ): array {

        $usuario = trim($usuario);

        // Validar el tipo de acceso.

        if (!in_array($rol, ["ADMIN", "CLIENTE"], true)) {
            return $this->error("Credenciales incorrectas.");
        }

        // Validar campos obligatorios.

        if (
            $usuario === "" ||
            $clave === "" ||
            strlen($usuario) > 50 ||
            strlen($clave) > 72
        ) {
            return $this->error("Credenciales incorrectas.");
        }

        // Validar formato de la cédula del cliente.

        if (
            $rol === "CLIENTE" &&
            !preg_match('/\A[0-9]{10}\z/', $usuario)
        ) {
            return $this->error(
                "Cédula incorrecta."
            );
        }

        try {

            $this->conexion->beginTransaction();

            $datos = $rol === "CLIENTE"
                ? $this->usuarioModelo->buscarPorCedula($usuario, true)
                : $this->usuarioModelo->buscarPorUsuario($usuario, true);

            // Verificar existencia y rol del usuario.

            if ($datos === null || $datos["rol"] !== $rol) {

                // Reducir diferencias de tiempo de respuesta.
                password_verify(
                    $clave,
                    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
                );

                $this->conexion->commit();

                return $this->error(
                    "Credenciales incorrectas."
                );
            }

            // Verificar si el usuario esta desactivado.

            if (!(bool) $datos["estado"]) {

                $this->conexion->commit();

                return $this->error(
                    "Credenciales incorrectas o acceso no disponible."
                );
            }

            // Verificar si el usuario esta bloqueado.

            if ((bool) $datos["bloqueado"]) {

                $this->conexion->commit();

                return $this->error(
                    "Credenciales incorrectas o acceso no disponible."
                );
            }

            // Validar formato del PIN para clientes.

            $formatoValido = true;

            if ($rol === "CLIENTE") {

                $formatoValido = preg_match(
                    '/^[0-9]{4}$/D',
                    $clave
                ) === 1;
            }

            // Comparar la clave con su hash.

            $claveValida = password_verify(
                $clave,
                $datos["clave_hash"]
            );

            if (!$formatoValido || !$claveValida) {

                // El bloqueo permanente se aplica a clientes.

                if ($rol === "CLIENTE") {

                    $intentos = (int) $datos["intentos_fallidos"] + 1;

                    $bloqueado = $intentos >= self::MAX_INTENTOS;

                    $this->usuarioModelo
                         ->registrarIntentoFallido(
                             (int) $datos["id"],
                             $intentos,
                             $bloqueado
                         );
                }

                $this->conexion->commit();

                return $this->error(
                    "Credenciales incorrectas."
                );
            }

            // Inicio de sesion correcto.

            $this->usuarioModelo->reiniciarIntentos(
                (int) $datos["id"]
            );

            $this->conexion->commit();

            return [
                "exito" => true,
                "mensaje" => "Autenticacion correcta.",
                "usuario" => [
                    "id" => (int) $datos["id"],

                    "id_cliente" =>
                        $datos["id_cliente"] !== null
                        ? (int) $datos["id_cliente"]
                        : null,

                    "usuario" => $datos["usuario"],
                    "rol" => $datos["rol"]
                ]
            ];

        } catch (Throwable $e) {

            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }

    private function error(string $mensaje): array
    {
        return [
            "exito" => false,
            "mensaje" => $mensaje,
            "usuario" => null
        ];
    }
}
