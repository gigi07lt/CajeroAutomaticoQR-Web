<?php

if (PHP_SAPI !== "cli") {

    http_response_code(403);

    exit("Acceso no permitido.");
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/Dashboard.php";
require_once __DIR__ . "/../controllers/DashboardController.php";

$aprobadas = 0;
$fallidas = 0;

function comprobar(string $nombre, bool $condicion): void
{
    global $aprobadas, $fallidas;

    if ($condicion) {

        echo "[OK] " . $nombre . PHP_EOL;

        $aprobadas++;

    } else {

        echo "[ERROR] " . $nombre . PHP_EOL;

        $fallidas++;
    }
}

echo "PRUEBAS DEL DASHBOARD ADMINISTRATIVO" . PHP_EOL;
echo "===================================" . PHP_EOL;

try {

    $conexion = obtenerConexion();

    $modelo = new Dashboard($conexion);

    $controlador = new DashboardController($conexion);

    $datos = $controlador->obtenerDatos();

    // PRUEBA 1: Total de clientes.

    $esperado = (int) $conexion->query(
        "SELECT COUNT(*) FROM clientes"
    )->fetchColumn();

    comprobar(
        "Total de clientes registrados",
        $modelo->obtenerTotalClientes() === $esperado
    );

    // PRUEBA 2: Total de usuarios.

    $esperado = (int) $conexion->query(
        "SELECT COUNT(*) FROM usuarios"
    )->fetchColumn();

    comprobar(
        "Total de usuarios",
        $modelo->obtenerTotalUsuarios() === $esperado
    );

    // PRUEBA 3: Total de cuentas.

    $esperado = (int) $conexion->query(
        "SELECT COUNT(*) FROM cuentas"
    )->fetchColumn();

    comprobar(
        "Total de cuentas bancarias",
        $modelo->obtenerTotalCuentas() === $esperado
    );

    // PRUEBA 4: Tarjetas QR activas.

    $esperado = (int) $conexion->query(
        "SELECT COUNT(*) FROM tarjetas
         WHERE activa = 1"
    )->fetchColumn();

    comprobar(
        "Total de tarjetas QR activas",
        $modelo->obtenerTotalTarjetas() === $esperado
    );

    // PRUEBA 5: Total de movimientos.

    $esperado = (int) $conexion->query(
        "SELECT COUNT(*) FROM movimientos"
    )->fetchColumn();

    comprobar(
        "Total de movimientos bancarios",
        $modelo->obtenerTotalMovimientos() === $esperado
    );

    // PRUEBA 6: Estructura del controlador.

    comprobar(
        "Estructura correcta del controlador",
        $datos === [
            "clientes" =>
                $modelo->obtenerTotalClientes(),

            "usuarios" =>
                $modelo->obtenerTotalUsuarios(),

            "cuentas" =>
                $modelo->obtenerTotalCuentas(),

            "tarjetas" =>
                $modelo->obtenerTotalTarjetas(),

            "movimientos" =>
                $modelo->obtenerTotalMovimientos()
        ]
    );

    // PRUEBAS DE ACTUALIZACION DINAMICA.

    $datosAntes = $controlador->obtenerDatos();

    $conexion->beginTransaction();

    try {

        // Buscar una cedula temporal disponible.

        $cedula = null;

        $buscar = $conexion->prepare(
            "SELECT COUNT(*) FROM clientes
             WHERE cedula = ?"
        );

        for ($i = 0; $i < 100; $i++) {

            $candidata = "99999999" .
                str_pad(
                    (string) $i,
                    2,
                    "0",
                    STR_PAD_LEFT
                );

            $buscar->execute([$candidata]);

            if ((int) $buscar->fetchColumn() === 0) {

                $cedula = $candidata;

                break;
            }
        }

        if ($cedula === null) {

            throw new RuntimeException(
                "No existe una cedula temporal disponible."
            );
        }

        // Registrar un cliente temporal.

        $sentencia = $conexion->prepare(
            "INSERT INTO clientes (nombres, cedula)
             VALUES (?, ?)"
        );

        $sentencia->execute([
            "CLIENTE TEMPORAL DASHBOARD",
            $cedula
        ]);

        $datosActualizados =
            $controlador->obtenerDatos();

        // PRUEBA 7: Actualizacion dinamica.

        $datosEsperados = $datosAntes;

        $datosEsperados["clientes"]++;

        comprobar(
            "Actualizacion dinamica de los indicadores",
            $datosActualizados === $datosEsperados
        );

    } finally {

        if ($conexion->inTransaction()) {

            $conexion->rollBack();
        }
    }

    // PRUEBA 8: Conservacion de datos.

    comprobar(
        "Conservacion de datos despues del rollback",
        $controlador->obtenerDatos() === $datosAntes
    );

} catch (Throwable $e) {

    echo "[ERROR] " . $e->getMessage() . PHP_EOL;

    $fallidas++;
}

echo PHP_EOL;
echo "===================================" . PHP_EOL;
echo "RESULTADOS" . PHP_EOL;
echo "===================================" . PHP_EOL;

echo "Aprobadas: " . $aprobadas . PHP_EOL;
echo "Fallidas: " . $fallidas . PHP_EOL;
echo "Total: " . ($aprobadas + $fallidas) . PHP_EOL;

exit($fallidas > 0 ? 1 : 0);
