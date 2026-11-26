<?php

class Dashboard
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTotalClientes(): int
    {
        $sql = "SELECT COUNT(*) FROM clientes";

        return (int) $this->conexion
            ->query($sql)
            ->fetchColumn();
    }

    public function obtenerTotalUsuarios(): int
    {
        $sql = "SELECT COUNT(*) FROM usuarios";

        return (int) $this->conexion
            ->query($sql)
            ->fetchColumn();
    }

    public function obtenerTotalCuentas(): int
    {
        $sql = "SELECT COUNT(*) FROM cuentas";

        return (int) $this->conexion
            ->query($sql)
            ->fetchColumn();
    }

    public function obtenerTotalTarjetas(): int
    {
        $sql = "SELECT COUNT(*)
                FROM tarjetas
                WHERE activa = 1";

        return (int) $this->conexion
            ->query($sql)
            ->fetchColumn();
    }

    public function obtenerTotalMovimientos(): int
    {
        $sql = "SELECT COUNT(*) FROM movimientos";

        return (int) $this->conexion
            ->query($sql)
            ->fetchColumn();
    }
}
