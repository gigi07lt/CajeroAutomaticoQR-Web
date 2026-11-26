<?php

require_once __DIR__ . "/../models/Dashboard.php";

class DashboardController
{
    private Dashboard $modelo;

    public function __construct(PDO $conexion)
    {
        $this->modelo = new Dashboard($conexion);
    }

    public function obtenerDatos(): array
    {
        return [

            "clientes" =>
                $this->modelo->obtenerTotalClientes(),

            "usuarios" =>
                $this->modelo->obtenerTotalUsuarios(),

            "cuentas" =>
                $this->modelo->obtenerTotalCuentas(),

            "tarjetas" =>
                $this->modelo->obtenerTotalTarjetas(),

            "movimientos" =>
                $this->modelo->obtenerTotalMovimientos()

        ];
    }
}
