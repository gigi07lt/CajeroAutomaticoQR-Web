<?php

class Usuario
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function buscarPorUsuario(
        string $usuario,
        bool $bloquearFila = false
    ): ?array {

        $sql = "SELECT *
                FROM usuarios
                WHERE usuario = ?";

        if ($bloquearFila) {
            $sql .= " FOR UPDATE";
        }

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([$usuario]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    // La cédula identifica al titular, aunque su nombre de usuario sea personalizado.
    public function buscarPorCedula(string $cedula, bool $bloquearFila = false): ?array
    {
        $sql = "SELECT u.* FROM usuarios u JOIN clientes c ON c.id = u.id_cliente
                WHERE c.cedula = ? AND u.rol = 'CLIENTE'";
        if ($bloquearFila) $sql .= " FOR UPDATE";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetch() ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT id, id_cliente, usuario,
                       rol, estado, bloqueado,
                       intentos_fallidos
                FROM usuarios
                WHERE id = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([$id]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    public function registrarIntentoFallido(
        int $id,
        int $intentos,
        bool $bloqueado
    ): void {

        $sql = "UPDATE usuarios
                SET intentos_fallidos = ?,
                    bloqueado = ?
                WHERE id = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            $intentos,
            $bloqueado ? 1 : 0,
            $id
        ]);
    }

    public function reiniciarIntentos(int $id): void
    {
        $sql = "UPDATE usuarios
                SET intentos_fallidos = 0
                WHERE id = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([$id]);
    }
}
