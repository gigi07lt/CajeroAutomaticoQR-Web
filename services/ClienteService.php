<?php

require_once __DIR__ . "/../models/Cliente.php";

class ClienteService
{
    private Cliente $modelo;

    private const REGISTROS_POR_PAGINA = 10;

    public function __construct(PDO $conexion)
    {
        $this->modelo = new Cliente($conexion);
    }

    // =========================================
    // RESPUESTAS DEL SERVICIO
    // =========================================

    private function respuesta(
        bool $exito,
        string $mensaje,
        ?array $datos = null
    ): array {

        return [
            "exito" => $exito,
            "mensaje" => $mensaje,
            "datos" => $datos
        ];
    }

    // =========================================
    // NORMALIZACION Y VALIDACIONES
    // =========================================

    private function normalizarNombres(
        string $nombres
    ): string {

        $nombres = trim($nombres);

        return preg_replace(
            "/\s+/u",
            " ",
            $nombres
        ) ?? "";
    }

    private function validarNombres(
        string $nombres
    ): ?string {

        if ($nombres === "") {

            return "Los nombres son obligatorios.";
        }

        if (mb_strlen($nombres, "UTF-8") > 100) {

            return "Los nombres no pueden superar "
                . "los 100 caracteres.";
        }

        if (!preg_match(
            "/\A[\p{L}\p{M} .'\x{2019}-]+\z/u",
            $nombres
        ) || !preg_match("/\p{L}/u", $nombres)) {

            return "Los nombres contienen "
                . "caracteres no permitidos.";
        }

        return null;
    }

    private function validarCedula(
        string $cedula
    ): ?string {

        if (!preg_match("/\A[0-9]{10}\z/", $cedula)) {

            return "La cédula debe contener "
                . "exactamente 10 dígitos.";
        }

        return null;
    }

    // =========================================
    // LISTADO Y PAGINACION
    // =========================================

    public function listar(
        string $busqueda = "",
        int $pagina = 1
    ): array {

        $busqueda = trim($busqueda);

        $busqueda = mb_substr(
            $busqueda,
            0,
            100,
            "UTF-8"
        );

        $limite = self::REGISTROS_POR_PAGINA;

        $total = $this->modelo->contar($busqueda);

        $totalPaginas = max(
            1,
            (int) ceil($total / $limite)
        );

        $pagina = max(
            1,
            min($pagina, $totalPaginas)
        );

        $desplazamiento = ($pagina - 1) * $limite;

        $clientes = $this->modelo->listar(
            $busqueda,
            $limite,
            $desplazamiento
        );

        return [
            "clientes" => $clientes,
            "busqueda" => $busqueda,
            "pagina" => $pagina,
            "limite" => $limite,
            "total" => $total,
            "total_paginas" => $totalPaginas
        ];
    }

    // =========================================
    // CONSULTA INDIVIDUAL
    // =========================================

    public function consultar(int $id): array
    {
        $cliente = $this->modelo->buscarPorId($id);

        if ($cliente === null) {

            return $this->respuesta(
                false,
                "El cliente solicitado no existe."
            );
        }

        $usuario = $this->modelo->obtenerUsuario($id);

        $cuentas = $this->modelo->obtenerCuentas($id);

        return $this->respuesta(
            true,
            "Cliente encontrado.",
            [
                "cliente" => $cliente,
                "usuario" => $usuario,
                "cuentas" => $cuentas,
                "puede_eliminar" =>
                    $usuario === null &&
                    count($cuentas) === 0
            ]
        );
    }

    // =========================================
    // REGISTRO DE CLIENTES
    // =========================================

    public function registrar(
        string $nombres,
        string $cedula
    ): array {

        $nombres = $this->normalizarNombres($nombres);

        $cedula = trim($cedula);

        $error = $this->validarNombres($nombres);

        if ($error !== null) {

            return $this->respuesta(false, $error);
        }

        $error = $this->validarCedula($cedula);

        if ($error !== null) {

            return $this->respuesta(false, $error);
        }

        if (
            $this->modelo->buscarPorCedula($cedula)
            !== null
        ) {

            return $this->respuesta(
                false,
                "Ya existe un cliente con esa cédula."
            );
        }

        try {

            $id = $this->modelo->crear(
                $nombres,
                $cedula
            );

        } catch (PDOException $e) {

            // MySQL: registro duplicado.

            if (
                (int) ($e->errorInfo[1] ?? 0)
                === 1062
            ) {

                return $this->respuesta(
                    false,
                    "Ya existe un cliente con esa cédula."
                );
            }

            throw $e;
        }

        return $this->respuesta(
            true,
            "Cliente registrado correctamente.",
            ["id" => $id]
        );
    }

    // =========================================
    // ACTUALIZACION DE CLIENTES
    // =========================================

    public function actualizar(
        int $id,
        string $nombres
    ): array {

        $cliente = $this->modelo->buscarPorId($id);

        if ($cliente === null) {

            return $this->respuesta(
                false,
                "El cliente solicitado no existe."
            );
        }

        $nombres = $this->normalizarNombres($nombres);

        $error = $this->validarNombres($nombres);

        if ($error !== null) {

            return $this->respuesta(false, $error);
        }

        if ($cliente["nombres"] === $nombres) {

            return $this->respuesta(
                true,
                "No se realizaron cambios.",
                ["id" => $id]
            );
        }

        $actualizado = $this->modelo->actualizar(
            $id,
            $nombres
        );

        if (!$actualizado) {

            $clienteActual =
                $this->modelo->buscarPorId($id);

            if (
                $clienteActual !== null &&
                $clienteActual["nombres"] === $nombres
            ) {

                return $this->respuesta(
                    true,
                    "No se realizaron cambios.",
                    ["id" => $id]
                );
            }

            return $this->respuesta(
                false,
                "No se pudo actualizar el cliente."
            );
        }

        return $this->respuesta(
            true,
            "Cliente actualizado correctamente.",
            ["id" => $id]
        );
    }

    // =========================================
    // ELIMINACION CONTROLADA
    // =========================================

    public function eliminar(int $id): array
    {
        $cliente = $this->modelo->buscarPorId($id);

        if ($cliente === null) {

            return $this->respuesta(
                false,
                "El cliente solicitado no existe."
            );
        }

        if ($this->modelo->tieneDependencias($id)) {

            return $this->respuesta(
                false,
                "No se puede eliminar el cliente "
                . "porque tiene usuarios o cuentas "
                . "bancarias asociados."
            );
        }

        try {

            $eliminado = $this->modelo->eliminar($id);

        } catch (PDOException $e) {

            // MySQL: restriccion de clave foranea.

            if (
                (int) ($e->errorInfo[1] ?? 0)
                === 1451
            ) {

                return $this->respuesta(
                    false,
                    "No se puede eliminar el cliente "
                    . "porque tiene registros asociados."
                );
            }

            throw $e;
        }

        if (!$eliminado) {

            return $this->respuesta(
                false,
                "No se pudo eliminar el cliente. "
                . "Es posible que tenga dependencias "
                . "o ya no exista."
            );
        }

        return $this->respuesta(
            true,
            "Cliente eliminado correctamente.",
            ["id" => $id]
        );
    }
}
