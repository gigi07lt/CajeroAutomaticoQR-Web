
<?php

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Acceso no permitido.");
}

require_once __DIR__ . "/../config/database.php";

$conexion = obtenerConexion();

// Clientes ficticios para pruebas locales.

$clientes = [
    ["Ana Torres", "1800000001", "1234"],
    ["Carlos Mena", "1800000002", "1111"],
    ["Lucía Paredes", "1800000003", "2222"],
    ["Diego Salazar", "1800000004", "3333"],
    ["Valeria Cruz", "1800000005", "4444"]
];

// Cuentas bancarias de prueba.

$cuentas = [
    ["2200000001", "1800000001", "AHORROS", "1500.00"],
    ["2200000002", "1800000002", "CORRIENTE", "820.50"],
    ["2200000003", "1800000003", "AHORROS", "3000.00"],
    ["2200000004", "1800000004", "AHORROS", "250.00"],
    ["2200000005", "1800000005", "CORRIENTE", "5000.00"],
    ["2200000006", "1800000001", "CORRIENTE", "700.00"]
];

try {

    $conexion->beginTransaction();

    // 1. Crear administrador.

    $stmt = $conexion->prepare(
        "INSERT INTO usuarios
        (usuario, clave_hash, rol)
        VALUES (?, ?, 'ADMIN')"
    );

    $stmt->execute([
        "admin",
        password_hash("AdminFisei2026!", PASSWORD_DEFAULT)
    ]);

    echo "Administrador creado correctamente.\n";

    // 2. Crear clientes y usuarios.

    $idsClientes = [];

    $stmtCliente = $conexion->prepare(
        "INSERT INTO clientes (nombres, cedula)
        VALUES (?, ?)"
    );

    $stmtUsuario = $conexion->prepare(
        "INSERT INTO usuarios
        (id_cliente, usuario, clave_hash, rol)
        VALUES (?, ?, ?, 'CLIENTE')"
    );

    foreach ($clientes as $cliente) {

        [$nombres, $cedula, $pin] = $cliente;

        $stmtCliente->execute([
            $nombres,
            $cedula
        ]);

        $idCliente = $conexion->lastInsertId();

        $idsClientes[$cedula] = $idCliente;

        $stmtUsuario->execute([
            $idCliente,
            $cedula,
            password_hash($pin, PASSWORD_DEFAULT)
        ]);

        echo "Cliente y usuario creados: "
             . $nombres . "\n";
    }

    // 3. Crear cuentas bancarias.

    $stmtCuenta = $conexion->prepare(
        "INSERT INTO cuentas
        (numero, id_cliente, tipo, saldo)
        VALUES (?, ?, ?, ?)"
    );

    // 4. Crear tarjetas QR de prueba.

    $stmtTarjeta = $conexion->prepare(
        "INSERT INTO tarjetas
        (codigo_qr, numero_cuenta)
        VALUES (?, ?)"
    );

    foreach ($cuentas as $i => $cuenta) {

        [$numero, $cedula, $tipo, $saldo] = $cuenta;

        $stmtCuenta->execute([
            $numero,
            $idsClientes[$cedula],
            $tipo,
            $saldo
        ]);

        $codigo = "TK-A1B2C3D4"
                . str_pad($i + 1, 4, "0", STR_PAD_LEFT);

        $stmtTarjeta->execute([
            $codigo,
            $numero
        ]);

        echo "Cuenta creada: " . $numero
             . " | QR: " . $codigo . "\n";
    }

    $conexion->commit();

    echo "\nDatos de prueba creados correctamente.\n";

} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
