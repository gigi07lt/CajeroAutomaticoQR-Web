<?php

require_once __DIR__ .
    "/../services/ClienteService.php";

require_once __DIR__ .
    "/../services/CsrfService.php";

require_once __DIR__ .
    "/../middleware/roles.php";

class ClienteController
{
    private ClienteService $servicio;

    private const RUTA_LISTADO =
        "views/admin/clientes/index.php";

    public function __construct(PDO $conexion)
    {
        $this->servicio = new ClienteService(
            $conexion
        );
    }

    // =========================================
    // VALIDACION DE PARAMETROS
    // =========================================

    public function obtenerId($valor): ?int
    {
        if (
            !is_string($valor) &&
            !is_int($valor)
        ) {

            return null;
        }

        $id = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
            [
                "options" => [
                    "min_range" => 1
                ]
            ]
        );

        return $id === false ? null : $id;
    }

    private function obtenerTexto(
        array $origen,
        string $campo
    ): string {

        $valor = $origen[$campo] ?? "";

        return is_string($valor)
            ? $valor
            : "";
    }

    // =========================================
    // SEGURIDAD DE OPERACIONES
    // =========================================

    private function exigirPostSeguro(): void
    {
        exigirRol("ADMIN");

        if (
            ($_SERVER["REQUEST_METHOD"] ?? "")
            !== "POST"
        ) {

            header("Allow: POST");

            http_response_code(405);

            exit("Metodo no permitido.");
        }

        CsrfService::exigirToken();
    }

    // =========================================
    // RESPUESTAS DE ERROR
    // =========================================

    private function respuestaError(
        string $mensaje
    ): array {

        return [
            "exito" => false,
            "mensaje" => $mensaje,
            "datos" => null
        ];
    }

    private function errorInesperado(
        Throwable $e
    ): array {

        error_log(
            "ClienteController: " .
            $e->getMessage()
        );

        return $this->respuestaError(
            "Ocurrió un error al procesar "
            . "la solicitud. Intenta nuevamente."
        );
    }

    // =========================================
    // LISTADO Y BUSQUEDA
    // =========================================

    public function listar(
        array $parametros
    ): array {

        $busqueda = $this->obtenerTexto(
            $parametros,
            "buscar"
        );

        $pagina = $this->obtenerId(
            $parametros["pagina"] ?? 1
        ) ?? 1;

        return $this->servicio->listar(
            $busqueda,
            $pagina
        );
    }

    // =========================================
    // CONSULTA INDIVIDUAL
    // =========================================

    public function consultar($valorId): array
    {
        $id = $this->obtenerId($valorId);

        if ($id === null) {

            return $this->respuestaError(
                "El identificador del cliente "
                . "no es válido."
            );
        }

        try {

            return $this->servicio->consultar(
                $id
            );

        } catch (Throwable $e) {

            return $this->errorInesperado($e);
        }
    }

    // =========================================
    // REGISTRO DE CLIENTES
    // =========================================

    public function registrar(): array
    {
        $this->exigirPostSeguro();

        $nombres = $this->obtenerTexto(
            $_POST,
            "nombres"
        );

        $cedula = $this->obtenerTexto(
            $_POST,
            "cedula"
        );

        try {

            return $this->servicio->registrar(
                $nombres,
                $cedula
            );

        } catch (Throwable $e) {

            return $this->errorInesperado($e);
        }
    }

    // =========================================
    // ACTUALIZACION DE CLIENTES
    // =========================================

    public function actualizar(): array
    {
        $this->exigirPostSeguro();

        $id = $this->obtenerId(
            $_POST["id"] ?? null
        );

        if ($id === null) {

            return $this->respuestaError(
                "El identificador del cliente "
                . "no es válido."
            );
        }

        $nombres = $this->obtenerTexto(
            $_POST,
            "nombres"
        );

        try {

            return $this->servicio->actualizar(
                $id,
                $nombres
            );

        } catch (Throwable $e) {

            return $this->errorInesperado($e);
        }
    }

    // =========================================
    // ELIMINACION CONTROLADA
    // =========================================

    public function eliminar(): array
    {
        $this->exigirPostSeguro();

        $id = $this->obtenerId(
            $_POST["id"] ?? null
        );

        if ($id === null) {

            return $this->respuestaError(
                "El identificador del cliente "
                . "no es válido."
            );
        }

        try {

            return $this->servicio->eliminar(
                $id
            );

        } catch (Throwable $e) {

            return $this->errorInesperado($e);
        }
    }

    // =========================================
    // MENSAJES TEMPORALES DE SESION
    // =========================================

    public function guardarMensajeFlash(
        array $resultado
    ): void {

        iniciarSesionPhp();

        $_SESSION["flash_clientes"] = [

            "exito" =>
                (bool) $resultado["exito"],

            "mensaje" =>
                (string) $resultado["mensaje"]

        ];
    }

    public function obtenerMensajeFlash(): ?array
    {
        iniciarSesionPhp();

        $mensaje =
            $_SESSION["flash_clientes"] ?? null;

        unset($_SESSION["flash_clientes"]);

        return is_array($mensaje)
            ? $mensaje
            : null;
    }

    // =========================================
    // REDIRECCION
    // =========================================

    public function redirigirListado(): void
    {
        header(
            "Location: " .
            urlAplicacion(self::RUTA_LISTADO),
            true,
            303
        );

        exit;
    }
}
