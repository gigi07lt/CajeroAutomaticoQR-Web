
<?php

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Acceso no permitido.");
}

require_once __DIR__ . "/../config/database.php";

require_once __DIR__
    . "/../services/AutenticacionService.php";

$conexion = obtenerConexion();

$servicio = new AutenticacionService($conexion);

$modelo = new Usuario($conexion);

$aprobadas = 0;
$fallidas = 0;

$idCliente = null;
$idUsuario = null;

function comprobar(
    string $nombre,
    bool $resultado
): void {

    global $aprobadas, $fallidas;

    if ($resultado) {

        echo "[OK] " . $nombre . "\n";
        $aprobadas++;

    } else {

        echo "[ERROR] " . $nombre . "\n";
        $fallidas++;
    }
}

echo "PRUEBAS DE AUTENTICACION\n";
echo "========================\n\n";

try {

    // PRUEBA 1: acceso correcto del administrador.

    $resultado = $servicio->autenticar(
        "admin",
        "AdminFisei2026!",
        "ADMIN"
    );

    comprobar(
        "Inicio de sesion del administrador",
        $resultado["exito"] &&
        $resultado["usuario"]["rol"] === "ADMIN"
    );

    // PRUEBA 2: acceso correcto del cliente.

    $resultado = $servicio->autenticar(
        "1800000001",
        "1234",
        "CLIENTE"
    );

    comprobar(
        "Inicio de sesion del cliente",
        $resultado["exito"] &&
        $resultado["usuario"]["rol"] === "CLIENTE"
    );

    // PRUEBA 3: usuario inexistente.

    $resultado = $servicio->autenticar(
        "usuario_inexistente",
        "1234",
        "ADMIN"
    );

    comprobar(
        "Rechazo de usuario inexistente",
        !$resultado["exito"]
    );

    // PRUEBA 4: rol incorrecto.

    $resultado = $servicio->autenticar(
        "1800000001",
        "1234",
        "ADMIN"
    );

    comprobar(
        "Rechazo de cliente como administrador",
        !$resultado["exito"]
    );

    // PRUEBA 5: cedula incorrecta.

    $resultado = $servicio->autenticar(
        "12345",
        "1234",
        "CLIENTE"
    );

    comprobar(
        "Rechazo de cedula incorrecta",
        !$resultado["exito"]
    );

    // PRUEBA 6: campos vacios.

    $resultado = $servicio->autenticar(
        "",
        "",
        "CLIENTE"
    );

    comprobar(
        "Rechazo de campos vacios",
        !$resultado["exito"]
    );

    // PRUEBA 7: contrasena administrativa incorrecta.

    $resultado = $servicio->autenticar(
        "admin",
        "incorrecta",
        "ADMIN"
    );

    comprobar(
        "Rechazo de contrasena administrativa incorrecta",
        !$resultado["exito"]
    );

    // Crear cliente temporal para las pruebas de bloqueo.

    $cedula = "99" . str_pad(
        (string) random_int(0, 99999999),
        8,
        "0",
        STR_PAD_LEFT
    );

    $stmt = $conexion->prepare(
        "INSERT INTO clientes (nombres, cedula)
         VALUES (?, ?)"
    );

    $stmt->execute([
        "Cliente temporal de pruebas",
        $cedula
    ]);

    $idCliente = (int) $conexion->lastInsertId();

    $stmt = $conexion->prepare(
        "INSERT INTO usuarios
         (id_cliente, usuario, clave_hash, rol)
         VALUES (?, ?, ?, 'CLIENTE')"
    );

    $stmt->execute([
        $idCliente,
        $cedula,
        password_hash("1234", PASSWORD_DEFAULT)
    ]);

    $idUsuario = (int) $conexion->lastInsertId();

    // PRUEBA 8: primer intento incorrecto.

    $resultado = $servicio->autenticar(
        $cedula,
        "9999",
        "CLIENTE"
    );

    $datos = $modelo->buscarPorId($idUsuario);

    comprobar(
        "Registro del primer intento fallido",
        !$resultado["exito"] &&
        (int) $datos["intentos_fallidos"] === 1 &&
        !(bool) $datos["bloqueado"]
    );

    // PRUEBA 9: reinicio de intentos.

    $resultado = $servicio->autenticar(
        $cedula,
        "1234",
        "CLIENTE"
    );

    $datos = $modelo->buscarPorId($idUsuario);

    comprobar(
        "Reinicio de intentos con PIN correcto",
        $resultado["exito"] &&
        (int) $datos["intentos_fallidos"] === 0
    );

    // PRUEBA 10: PIN con formato incorrecto.

    $resultado = $servicio->autenticar(
        $cedula,
        "12",
        "CLIENTE"
    );

    $datos = $modelo->buscarPorId($idUsuario);

    comprobar(
        "Rechazo de PIN con formato incorrecto",
        !$resultado["exito"] &&
        (int) $datos["intentos_fallidos"] === 1
    );

    // Segundo intento incorrecto.

    $resultado = $servicio->autenticar(
        $cedula,
        "9999",
        "CLIENTE"
    );

    $datos = $modelo->buscarPorId($idUsuario);

    // PRUEBA 11: segundo intento.

    comprobar(
        "Segundo intento sin bloqueo",
        !$resultado["exito"] &&
        (int) $datos["intentos_fallidos"] === 2 &&
        !(bool) $datos["bloqueado"]
    );

    // Tercer intento incorrecto.

    $resultado = $servicio->autenticar(
        $cedula,
        "9999",
        "CLIENTE"
    );

    $datos = $modelo->buscarPorId($idUsuario);

    // PRUEBA 12: bloqueo despues de tres intentos.

    comprobar(
        "Bloqueo despues de tres intentos",
        !$resultado["exito"] &&
        (int) $datos["intentos_fallidos"] === 3 &&
        (bool) $datos["bloqueado"]
    );

    // PRUEBA 13: usuario bloqueado con PIN correcto.

    $resultado = $servicio->autenticar(
        $cedula,
        "1234",
        "CLIENTE"
    );

    comprobar(
        "Rechazo de usuario bloqueado con PIN correcto",
        !$resultado["exito"]
    );

    // Desbloquear y desactivar el usuario temporal.

    $stmt = $conexion->prepare(
        "UPDATE usuarios
         SET bloqueado = 0,
             intentos_fallidos = 0,
             estado = 0
         WHERE id = ?"
    );

    $stmt->execute([$idUsuario]);

    // PRUEBA 14: usuario desactivado.

    $resultado = $servicio->autenticar(
        $cedula,
        "1234",
        "CLIENTE"
    );

    comprobar(
        "Rechazo de usuario desactivado",
        !$resultado["exito"]
    );

    // PRUEBA 15: reactivar usuario.

    $stmt = $conexion->prepare(
        "UPDATE usuarios
         SET estado = 1
         WHERE id = ?"
    );

    $stmt->execute([$idUsuario]);

    $resultado = $servicio->autenticar(
        $cedula,
        "1234",
        "CLIENTE"
    );

    comprobar(
        "Acceso correcto despues de reactivacion",
        $resultado["exito"]
    );

} catch (Throwable $e) {

    echo "\nERROR: " . $e->getMessage() . "\n";
    $fallidas++;

} finally {

    // Eliminar los registros temporales.

    if ($idUsuario !== null) {

        $stmt = $conexion->prepare(
            "DELETE FROM usuarios WHERE id = ?"
        );

        $stmt->execute([$idUsuario]);
    }

    if ($idCliente !== null) {

        $stmt = $conexion->prepare(
            "DELETE FROM clientes WHERE id = ?"
        );

        $stmt->execute([$idCliente]);
    }
}

echo "\n========================\n";
echo "RESULTADOS\n";
echo "========================\n";
echo "Aprobadas: " . $aprobadas . "\n";
echo "Fallidas: " . $fallidas . "\n";
echo "Total: " . ($aprobadas + $fallidas) . "\n";

exit($fallidas > 0 ? 1 : 0);
