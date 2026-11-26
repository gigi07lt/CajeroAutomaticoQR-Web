<?php

class Cliente
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    // =========================================
    // LISTADO Y BUSQUEDA
    // =========================================

    private function prepararBusqueda(string $busqueda): string
    {
        $busqueda = strtr($busqueda, [
            "!" => "!!",
            "%" => "!%",
            "_" => "!_"
        ]);

        return "%" . $busqueda . "%";
    }

    public function listar(
        string $busqueda = "",
        int $limite = 10,
        int $desplazamiento = 0
    ): array {

        $sql = "SELECT id, nombres, cedula
                FROM clientes
                WHERE nombres LIKE :nombre ESCAPE '!'
                   OR cedula LIKE :cedula ESCAPE '!'
                ORDER BY nombres ASC, id ASC
                LIMIT :limite OFFSET :desplazamiento";

        $sentencia = $this->conexion->prepare($sql);

        $patron = $this->prepararBusqueda($busqueda);

        $sentencia->bindValue(
            ":nombre",
            $patron,
            PDO::PARAM_STR
        );

        $sentencia->bindValue(
            ":cedula",
            $patron,
            PDO::PARAM_STR
        );

        $sentencia->bindValue(
            ":limite",
            $limite,
            PDO::PARAM_INT
        );

        $sentencia->bindValue(
            ":desplazamiento",
            $desplazamiento,
            PDO::PARAM_INT
        );

        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $busqueda = ""): int
    {
        $sql = "SELECT COUNT(*)
                FROM clientes
                WHERE nombres LIKE :nombre ESCAPE '!'
                   OR cedula LIKE :cedula ESCAPE '!'";

        $sentencia = $this->conexion->prepare($sql);

        $patron = $this->prepararBusqueda($busqueda);

        $sentencia->execute([
            ":nombre" => $patron,
            ":cedula" => $patron
        ]);

        return (int) $sentencia->fetchColumn();
    }

    // =========================================
    // CONSULTAS INDIVIDUALES
    // =========================================

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT id, nombres, cedula
                FROM clientes
                WHERE id = ?";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([$id]);

        $cliente = $sentencia->fetch(PDO::FETCH_ASSOC);

        return $cliente === false ? null : $cliente;
    }

    public function buscarPorCedula(string $cedula): ?array
    {
        $sql = "SELECT id, nombres, cedula
                FROM clientes
                WHERE cedula = ?";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([$cedula]);

        $cliente = $sentencia->fetch(PDO::FETCH_ASSOC);

        return $cliente === false ? null : $cliente;
    }

    // =========================================
    // REGISTRO DE CLIENTES
    // =========================================

    public function crear(
        string $nombres,
        string $cedula
    ): int {

        $sql = "INSERT INTO clientes (nombres, cedula)
                VALUES (?, ?)";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            $nombres,
            $cedula
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    // =========================================
    // ACTUALIZACION DE CLIENTES
    // =========================================

    public function actualizar(
        int $id,
        string $nombres
    ): bool {

        $sql = "UPDATE clientes
                SET nombres = ?
                WHERE id = ?";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            $nombres,
            $id
        ]);

        return $sentencia->rowCount() > 0;
    }

    // =========================================
    // USUARIO ASOCIADO
    // =========================================

    public function obtenerUsuario(int $id): ?array
    {
        $sql = "SELECT id, usuario, rol,
                       estado, bloqueado
                FROM usuarios
                WHERE id_cliente = ?";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([$id]);

        $usuario = $sentencia->fetch(PDO::FETCH_ASSOC);

        return $usuario === false ? null : $usuario;
    }

    // =========================================
    // CUENTAS BANCARIAS ASOCIADAS
    // =========================================

    public function obtenerCuentas(int $id): array
    {
        $sql = "SELECT numero, tipo, saldo, estado
                FROM cuentas
                WHERE id_cliente = ?
                ORDER BY numero ASC";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([$id]);

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================
    // VERIFICACION DE DEPENDENCIAS
    // =========================================

    public function tieneDependencias(int $id): bool
    {
        $sql = "SELECT (
                    EXISTS (
                        SELECT 1
                        FROM usuarios
                        WHERE id_cliente = :id_usuario
                    )
                    OR
                    EXISTS (
                        SELECT 1
                        FROM cuentas
                        WHERE id_cliente = :id_cuenta
                    )
                ) AS tiene_dependencias";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            ":id_usuario" => $id,
            ":id_cuenta" => $id
        ]);

        return (int) $sentencia->fetchColumn() === 1;
    }

    // =========================================
    // ELIMINACION CONTROLADA
    // =========================================

    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM clientes
                WHERE id = :id
                AND NOT EXISTS (
                    SELECT 1
                    FROM usuarios
                    WHERE id_cliente = :id_usuario
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM cuentas
                    WHERE id_cliente = :id_cuenta
                )";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            ":id" => $id,
            ":id_usuario" => $id,
            ":id_cuenta" => $id
        ]);

        return $sentencia->rowCount() > 0;
    }
}
