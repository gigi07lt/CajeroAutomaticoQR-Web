<?php

require_once __DIR__ .
    "/../../../middleware/roles.php";

require_once __DIR__ .
    "/../../../controllers/ClienteController.php";

// Verificar permisos administrativos.

$usuario = exigirRol("ADMIN");

// Configuracion de la pagina.

$tituloPagina = "Registrar cliente";

$paginaActual = "clientes";

// Rutas del modulo.

$rutaListado = urlAplicacion(
    "views/admin/clientes/index.php"
);

$rutaCrear = urlAplicacion(
    "views/admin/clientes/crear.php"
);

// Variables iniciales del formulario.

$nombres = "";

$cedula = "";

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

// Procesar el formulario.

if ($metodo === "POST") {

    // Conservar los datos ingresados.

    $nombres = is_string(
        $_POST["nombres"] ?? null
    ) ? $_POST["nombres"] : "";

    $cedula = is_string(
        $_POST["cedula"] ?? null
    ) ? $_POST["cedula"] : "";

    try {

        $controlador = new ClienteController(
            obtenerConexion()
        );

        $resultado = $controlador->registrar();

        if ($resultado["exito"]) {

            $controlador->guardarMensajeFlash(
                $resultado
            );

            $controlador->redirigirListado();
        }

        $errorFormulario =
            $resultado["mensaje"];

    } catch (Throwable $e) {

        error_log(
            "Registro de cliente: " .
            $e->getMessage()
        );

        http_response_code(503);

        $errorFormulario =
            "No se pudo procesar el registro. "
            . "Intenta nuevamente.";
    }
}

// Generar token de seguridad.

$tokenCsrf = CsrfService::generarToken();

// Cargar estructura administrativa.

require __DIR__ .
    "/../../components/admin/inicio.php";

?>

<section class="crud-encabezado">

    <div>

        <h1>Registrar cliente</h1>

        

    </div>

    <a href="<?= escaparCliente($rutaListado) ?>"
       class="btn btn-secundario">

        ← Volver a clientes

    </a>

</section>

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
          action="<?= escaparCliente($rutaCrear) ?>"
          class="crud-formulario">

        <!-- TOKEN DE SEGURIDAD -->

        <input type="hidden"
               name="csrf_token"
               value="<?= escaparCliente(
                   $tokenCsrf
               ) ?>">

        <!-- NOMBRES COMPLETOS -->

        <div class="grupo-campo">

            <label for="nombres">

                Nombres completos

            </label>

            <input type="text"
                   id="nombres"
                   name="nombres"
                   maxlength="100"
                   placeholder="Ej. María Pérez"
                   autocomplete="off"
                   value="<?= escaparCliente(
                       $nombres
                   ) ?>"
                   required>

        </div>

        <!-- CEDULA -->

        <div class="grupo-campo">

            <label for="cedula">

                Número de cédula

            </label>

            <input type="text"
                   id="cedula"
                   name="cedula"
                   inputmode="numeric"
                   minlength="10"
                   maxlength="10"
                   pattern="[0-9]{10}"
                   placeholder="Ingrese 10 dígitos"
                   autocomplete="off"
                   aria-describedby="ayuda-cedula"
                   value="<?= escaparCliente(
                       $cedula
                   ) ?>"
                   required>

            <small id="ayuda-cedula"
                   class="crud-ayuda">

                La cédula debe contener 10 dígitos
                y no podrá modificarse después
                del registro.

            </small>

        </div>

        

        <!-- BOTONES DEL FORMULARIO -->

        <div class="crud-formulario-acciones">

            <button type="submit"
                    class="btn btn-primario">

                Guardar cliente

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

<?php

require __DIR__ .
    "/../../components/admin/fin.php";
