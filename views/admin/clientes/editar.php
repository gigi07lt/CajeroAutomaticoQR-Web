<?php

require_once __DIR__ .
    "/../../../middleware/roles.php";

require_once __DIR__ .
    "/../../../controllers/ClienteController.php";

// Verificar permisos administrativos.

$usuario = exigirRol("ADMIN");

// Configuracion de la pagina.

$tituloPagina = "Editar cliente";

$paginaActual = "clientes";

// Rutas del modulo.

$rutaListado = urlAplicacion(
    "views/admin/clientes/index.php"
);

$rutaEditar = urlAplicacion(
    "views/admin/clientes/editar.php"
);

// Variables iniciales.

$cliente = null;

$nombres = "";

$cedula = "";

$errorPagina = "";

$errorFormulario = "";

// Funcion para escapar contenido HTML.

function escaparCliente(string $valor): string
{
    return htmlspecialchars(
        $valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

// Verificar el metodo HTTP.

$metodo = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($metodo !== "GET" && $metodo !== "POST") {

    header("Allow: GET, POST");

    http_response_code(405);

    exit("Metodo no permitido.");
}

// Recuperar y procesar el cliente.

try {

    $controlador = new ClienteController(
        obtenerConexion()
    );

    // Validar seguridad de solicitudes POST.

    if ($metodo === "POST") {

        CsrfService::exigirToken();
    }

    // Obtener el identificador.

    $valorId = $metodo === "POST"
        ? ($_POST["id"] ?? null)
        : ($_GET["id"] ?? null);

    $id = $controlador->obtenerId($valorId);

    if ($id === null) {

        http_response_code(400);

        $errorPagina =
            "El identificador del cliente no es válido.";

    } else {

        // Consultar los datos originales.

        $resultado = $controlador->consultar($id);

        if (!$resultado["exito"]) {

            $errorPagina = $resultado["mensaje"];

        } else {

            $cliente = $resultado["datos"]["cliente"];

            $nombres = $cliente["nombres"];

            $cedula = $cliente["cedula"];

            // Procesar la actualizacion.

            if ($metodo === "POST") {

                $nombres = is_string(
                    $_POST["nombres"] ?? null
                ) ? $_POST["nombres"] : "";

                $resultado =
                    $controlador->actualizar();

                if ($resultado["exito"]) {

                    $controlador->guardarMensajeFlash(
                        $resultado
                    );

                    $controlador->redirigirListado();
                }

                $errorFormulario =
                    $resultado["mensaje"];
            }
        }
    }

} catch (Throwable $e) {

    error_log(
        "Edicion de cliente: " .
        $e->getMessage()
    );

    http_response_code(503);

    $errorPagina =
        "No se pudo procesar la solicitud. "
        . "Intenta nuevamente.";
}

// Generar token de seguridad.

$tokenCsrf = CsrfService::generarToken();

// Cargar estructura administrativa.

require __DIR__ .
    "/../../components/admin/inicio.php";

?>

<section class="crud-encabezado">

    <div>

        <h1>Editar cliente</h1>

        

    </div>

    <a href="<?= escaparCliente($rutaListado) ?>"
       class="btn btn-secundario">

        ← Volver a clientes

    </a>

</section>

<?php if ($errorPagina !== ""): ?>

    <div class="alerta alerta-error"
         role="alert">

        <?= escaparCliente($errorPagina) ?>

    </div>

    <a href="<?= escaparCliente($rutaListado) ?>"
       class="btn btn-primario">

        Regresar al listado

    </a>

<?php else: ?>

    <section class="crud-formulario-contenedor">

        <h2>Datos personales</h2>

        

        <?php if ($errorFormulario !== ""): ?>

            <div class="alerta alerta-error"
                 role="alert">

                <?= escaparCliente(
                    $errorFormulario
                ) ?>

            </div>

        <?php endif; ?>

        <form method="POST"
              action="<?= escaparCliente($rutaEditar) ?>"
              class="crud-formulario">

            <!-- TOKEN DE SEGURIDAD -->

            <input type="hidden"
                   name="csrf_token"
                   value="<?= escaparCliente(
                       $tokenCsrf
                   ) ?>">

            <!-- IDENTIFICADOR DEL CLIENTE -->

            <input type="hidden"
                   name="id"
                   value="<?= (int) $cliente["id"] ?>">

            <!-- NOMBRES COMPLETOS -->

            <div class="grupo-campo">

                <label for="nombres">

                    Nombres completos

                </label>

                <input type="text"
                       id="nombres"
                       name="nombres"
                       maxlength="100"
                       autocomplete="off"
                       value="<?= escaparCliente(
                           $nombres
                       ) ?>"
                       required>

            </div>

            <!-- CEDULA NO MODIFICABLE -->

            <div class="grupo-campo">

                <label for="cedula">

                    Número de cédula

                </label>

                <input type="text"
                       id="cedula"
                       value="<?= escaparCliente(
                           $cedula
                       ) ?>"
                       aria-describedby="ayuda-cedula"
                       readonly>

                <small id="ayuda-cedula"
                       class="crud-ayuda">

                    La cédula no puede modificarse
                    después del registro.

                </small>

            </div>

            

            <!-- BOTONES DEL FORMULARIO -->

            <div class="crud-formulario-acciones">

                <button type="submit"
                        class="btn btn-primario">

                    Guardar cambios

                </button>

                <a href="<?= escaparCliente(
                    $rutaListado
                ) ?>"
                   class="btn btn-secundario">

                    Cancelar

                </a>

            </div>

        </form>

    </section>

<?php endif; ?>

<?php

require __DIR__ .
    "/../../components/admin/fin.php";
