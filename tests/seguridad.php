<?php

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Acceso no permitido.");
}

require_once __DIR__ . "/../config/database.php";

require_once __DIR__ . "/../services/SesionService.php";

require_once __DIR__ . "/../services/CsrfService.php";

require_once __DIR__
    . "/../services/LimiteIntentosService.php";

$conexion = obtenerConexion();

$modelo = new Usuario($conexion);

$aprobadas = 0;
$fallidas = 0;

$resultados = [];

function comprobar(
    string $nombre,
    bool $resultado
): void {

    global $aprobadas, $fallidas, $resultados;

    if ($resultado) {

        $resultados[] = "[OK] " . $nombre;
        $aprobadas++;

    } else {

        $resultados[] = "[ERROR] " . $nombre;
        $fallidas++;
    }
}

function prepararUsuario(array $datos): array
{
    return [
        "id" => (int) $datos["id"],

        "id_cliente" =>
            $datos["id_cliente"] !== null
            ? (int) $datos["id_cliente"]
            : null,

        "usuario" => $datos["usuario"],

        "rol" => $datos["rol"]
    ];
}

// Identificador exclusivo para probar el limitador.

$ipPrueba = "prueba-" . bin2hex(random_bytes(16));

$clavePrueba = hash(
    "sha256",
    "login:" . $ipPrueba
);

try {

    iniciarSesionPhp();

    $_SESSION = [];

    // PRUEBA 1.

    comprobar(
        "Sesion inicialmente no autenticada",
        !SesionService::estaAutenticado()
    );

    // Obtener cliente existente.

    $datosCliente = $modelo->buscarPorUsuario(
        "1800000001"
    );

    $cliente = prepararUsuario($datosCliente);

    $idAnterior = session_id();

    SesionService::iniciar($cliente);

    // PRUEBA 2.

    comprobar(
        "Creacion de sesion del cliente",
        SesionService::estaAutenticado()
    );

    // PRUEBA 3.

    comprobar(
        "Regeneracion del identificador de sesion",
        session_id() !== $idAnterior
    );

    // PRUEBA 4.

    comprobar(
        "Identificacion correcta del cliente",
        SesionService::obtenerUsuario()["id"]
        === $cliente["id"]
    );

    // PRUEBA 5.

    comprobar(
        "Permiso para rol CLIENTE",
        SesionService::tieneRol("CLIENTE")
    );

    // PRUEBA 6.

    comprobar(
        "Rechazo del rol ADMIN para cliente",
        !SesionService::tieneRol("ADMIN")
    );

    // PRUEBA 7.

    comprobar(
        "Validacion del usuario en MySQL",
        SesionService::validarUsuario($conexion)
    );

    // PRUEBA 8.

    comprobar(
        "Tiempo de sesion del cliente",
        SesionService::tiempoPermitido() === 300
    );

    // PRUEBA 9.

    $_SESSION["ultima_actividad"] = time() - 300;

    comprobar(
        "Expiracion de sesion del cliente",
        SesionService::haExpirado()
    );

    // PRUEBA 10.

    comprobar(
        "Rechazo de usuario con sesion expirada",
        !SesionService::validarUsuario($conexion)
    );

    // Crear una nueva sesion valida.

    SesionService::iniciar($cliente);

    // PRUEBA 11.

    $_SESSION["ultima_actividad"] = time() - 10;

    SesionService::actualizarActividad();

    comprobar(
        "Actualizacion de ultima actividad",
        !SesionService::haExpirado() &&
        time() - $_SESSION["ultima_actividad"] <= 1
    );

    // PRUEBA 12.

    $token = CsrfService::generarToken();

    comprobar(
        "Generacion del token CSRF",
        strlen($token) === 64 &&
        ctype_xdigit($token)
    );

    // PRUEBA 13.

    comprobar(
        "Validacion de token CSRF correcto",
        CsrfService::validarToken($token)
    );

    // PRUEBA 14.

    comprobar(
        "Rechazo de token CSRF incorrecto",
        !CsrfService::validarToken("token-falso")
    );

    // PRUEBA 15.

    comprobar(
        "Rechazo de token CSRF ausente",
        !CsrfService::validarToken(null)
    );

    // PRUEBA 16.

    comprobar(
        "Reutilizacion del token en la misma sesion",
        CsrfService::generarToken() === $token
    );

    // PRUEBA 17.

    SesionService::iniciar($cliente);

    comprobar(
        "Invalidacion de token de sesion anterior",
        !CsrfService::validarToken($token)
    );

    // Probar sesion administrativa.

    $datosAdmin = $modelo->buscarPorUsuario("admin");

    $admin = prepararUsuario($datosAdmin);

    SesionService::iniciar($admin);

    // PRUEBA 18.

    comprobar(
        "Permiso para rol ADMIN",
        SesionService::tieneRol("ADMIN")
    );

    // PRUEBA 19.

    comprobar(
        "Rechazo del rol CLIENTE para administrador",
        !SesionService::tieneRol("CLIENTE")
    );

    // PRUEBA 20.

    comprobar(
        "Tiempo de sesion del administrador",
        SesionService::tiempoPermitido() === 900
    );

    // PRUEBA 21.

    $_SESSION["ultima_actividad"] = time() - 900;

    comprobar(
        "Expiracion de sesion administrativa",
        SesionService::haExpirado()
    );

    // Probar limitacion de solicitudes.

    $limitador = new LimiteIntentosService(
        $conexion
    );

    $permitidas = 0;

    for ($i = 0; $i < 10; $i++) {

        if ($limitador->permitir($ipPrueba)) {
            $permitidas++;
        }
    }

    // PRUEBA 22.

    comprobar(
        "Permitir las primeras diez solicitudes",
        $permitidas === 10
    );

    // PRUEBA 23.

    comprobar(
        "Rechazar la solicitud numero once",
        !$limitador->permitir($ipPrueba)
    );

    // Simular el transcurso de 60 segundos.

    $stmt = $conexion->prepare(
        "UPDATE limites_acceso
         SET inicio_ventana = ?
         WHERE clave = ?"
    );

    $stmt->execute([
        time() - 61,
        $clavePrueba
    ]);

    // PRUEBA 24.

    comprobar(
        "Restablecimiento del limite de solicitudes",
        $limitador->permitir($ipPrueba)
    );

    // PRUEBA 25.

    SesionService::cerrar();

    iniciarSesionPhp();

    comprobar(
        "Cierre correcto de sesion",
        !SesionService::estaAutenticado()
    );

} catch (Throwable $e) {

    $resultados[] = "ERROR: " . $e->getMessage();

    $fallidas++;

} finally {

    // Eliminar unicamente los datos del limitador de prueba.

    $stmt = $conexion->prepare(
        "DELETE FROM limites_acceso
         WHERE clave = ?"
    );

    $stmt->execute([$clavePrueba]);

    if (session_status() === PHP_SESSION_ACTIVE) {
        SesionService::cerrar();
    }
}

// Mostrar los resultados al finalizar,
// evitando enviar contenido antes de gestionar sesiones.

echo "PRUEBAS DE SEGURIDAD\n";
echo "========================\n\n";

foreach ($resultados as $resultado) {
    echo $resultado . "\n";
}

echo "\n========================\n";
echo "RESULTADOS\n";
echo "========================\n";

echo "Aprobadas: " . $aprobadas . "\n";
echo "Fallidas: " . $fallidas . "\n";

echo "Total: " .
    ($aprobadas + $fallidas) . "\n";

exit($fallidas > 0 ? 1 : 0);
