<?php

require_once __DIR__ . "/../../middleware/roles.php";

require_once __DIR__ .
    "/../../controllers/DashboardController.php";

// Verificar permisos del administrador.

$usuario = exigirRol("ADMIN");

// Configurar la pagina.

$tituloPagina = "Panel administrativo";

$paginaActual = "inicio";

// Obtener las estadisticas.

$errorDashboard = "";

$datos = [];

try {

    $controlador = new DashboardController(
        obtenerConexion()
    );

    $datos = $controlador->obtenerDatos();

} catch (Throwable $e) {

    error_log($e->getMessage());

    http_response_code(503);

    $errorDashboard =
        "No se pudieron cargar los módulos. " .
        "Intenta nuevamente.";
}

// Cargar la estructura administrativa.

require __DIR__ . "/../components/admin/inicio.php";

?>

<section class="admin-bienvenida">

    <h1>Panel administrativo</h1>

    <p class="texto-secundario">

        Bienvenido,

        <?= htmlspecialchars(
            $usuario["usuario"],
            ENT_QUOTES,
            "UTF-8"
        ) ?>.

        Selecciona un módulo para comenzar.

    </p>

</section>

<?php if ($errorDashboard !== ""): ?>

    <div class="alerta alerta-error" role="alert">

        <?= htmlspecialchars(
            $errorDashboard,
            ENT_QUOTES,
            "UTF-8"
        ) ?>

    </div>

    <a href="<?= urlAplicacion(
        'views/admin/index.php'
    ) ?>"
       class="btn btn-primario">

        Volver a intentar

    </a>

<?php else: ?>

    <?php

    $tarjetas = [

        [
            "titulo" => "Clientes registrados",
            "valor" => $datos["clientes"],
            "descripcion" =>
                "Registrar y administrar clientes.",
            "icono" => "♙",
            "color" => "azul",
            "ruta" => null
        ],

        [
            "titulo" => "Usuarios",
            "valor" => $datos["usuarios"],
            "descripcion" =>
                "Administrar usuarios y credenciales.",
            "icono" => "⚿",
            "color" => "morado",
            "ruta" => null
        ],

        [
            "titulo" => "Cuentas bancarias",
            "valor" => $datos["cuentas"],
            "descripcion" =>
                "Registrar y administrar cuentas bancarias.",
            "icono" => "$",
            "color" => "verde",
            "ruta" => null
        ],

        [
            "titulo" => "Tarjetas QR activas",
            "valor" => $datos["tarjetas"],
            "descripcion" =>
                "Consultar y descargar imágenes QR.",
            "icono" => "▦",
            "color" => "naranja",
            "ruta" => null
        ],

        [
            "titulo" => "Movimientos",
            "valor" => $datos["movimientos"],
            "descripcion" =>
                "Consultar movimientos y generar reportes.",
            "icono" => "⇄",
            "color" => "azul",
            "ruta" => null
        ]

    ];

    ?>

    <section class="modulos-grid"
             aria-label="Módulos administrativos">

        <?php foreach ($tarjetas as $tarjeta): ?>

            <?php require __DIR__ .
                "/../components/admin/tarjeta-modulo.php"; ?>

        <?php endforeach; ?>

    </section>

<?php endif; ?>

<?php

require __DIR__ . "/../components/admin/fin.php";
