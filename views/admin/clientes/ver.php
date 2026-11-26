<?php

require_once __DIR__ .
    "/../../../middleware/roles.php";

require_once __DIR__ .
    "/../../../controllers/ClienteController.php";

// Verificar permisos administrativos.

$usuario = exigirRol("ADMIN");

// Configuracion de la pagina.

$tituloPagina = "Detalle del cliente";

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

$usuarioAsociado = null;

$cuentas = [];

$puedeEliminar = false;

$errorPagina = "";

// Funcion para escapar contenido HTML.

function escaparCliente(string $valor): string
{
    return htmlspecialchars(
        $valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

// Permitir exclusivamente consultas GET.

if (
    ($_SERVER["REQUEST_METHOD"] ?? "GET")
    !== "GET"
) {

    header("Allow: GET");

    http_response_code(405);

    exit("Metodo no permitido.");
}

// Consultar informacion del cliente.

try {

    $controlador = new ClienteController(
        obtenerConexion()
    );

    $id = $controlador->obtenerId(
        $_GET["id"] ?? null
    );

    if ($id === null) {

        http_response_code(400);

        $errorPagina =
            "El identificador del cliente no es válido.";

    } else {

        $resultado = $controlador->consultar($id);

        if (!$resultado["exito"]) {

            $errorPagina = $resultado["mensaje"];

        } else {

            $datos = $resultado["datos"];

            $cliente = $datos["cliente"];

            $usuarioAsociado = $datos["usuario"];

            $cuentas = $datos["cuentas"];

            $puedeEliminar =
                $datos["puede_eliminar"];
        }
    }

} catch (Throwable $e) {

    error_log(
        "Consulta individual de cliente: " .
        $e->getMessage()
    );

    http_response_code(503);

    $errorPagina =
        "No se pudo consultar la información "
        . "del cliente. Intenta nuevamente.";
}

// Cargar estructura administrativa.

require __DIR__ .
    "/../../components/admin/inicio.php";

?>

<section class="crud-encabezado">

    <div>

        <h1>Detalle del cliente</h1>

        

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

    <!-- DATOS PERSONALES -->

    <section class="crud-detalle-seccion">

        <div class="crud-detalle-encabezado">

            <h2>Datos personales</h2>

            <a href="<?= escaparCliente(
                $rutaEditar .
                "?id=" . (int) $cliente["id"]
            ) ?>"
               class="btn btn-primario">

                Editar cliente

            </a>

        </div>

        <dl class="crud-detalle-grid">

            <div class="crud-detalle-campo">

                <dt>Nombres completos</dt>

                <dd>

                    <?= escaparCliente(
                        $cliente["nombres"]
                    ) ?>

                </dd>

            </div>

            <div class="crud-detalle-campo">

                <dt>Número de cédula</dt>

                <dd>

                    <?= escaparCliente(
                        $cliente["cedula"]
                    ) ?>

                </dd>

            </div>

        </dl>

    </section>

    <!-- USUARIO ASOCIADO -->

    <section class="crud-detalle-seccion">

        <h2>Usuario asociado</h2>

        <?php if ($usuarioAsociado === null): ?>

            <div class="alerta alerta-info">

                Este cliente todavía no tiene
                un usuario asociado.

                Las credenciales podrán crearse
                desde el módulo de usuarios.

            </div>

        <?php else: ?>

            <dl class="crud-detalle-grid">

                <div class="crud-detalle-campo">

                    <dt>Nombre de usuario</dt>

                    <dd>

                        <?= escaparCliente(
                            $usuarioAsociado["usuario"]
                        ) ?>

                    </dd>

                </div>

                <div class="crud-detalle-campo">

                    <dt>Rol</dt>

                    <dd>

                        <?= escaparCliente(
                            $usuarioAsociado["rol"]
                        ) ?>

                    </dd>

                </div>

                <div class="crud-detalle-campo">

                    <dt>Estado</dt>

                    <dd>

                        <?php if (
                            (bool) $usuarioAsociado["estado"]
                        ): ?>

                            <span class="crud-estado
                                         estado-activo">

                                Activo

                            </span>

                        <?php else: ?>

                            <span class="crud-estado
                                         estado-inactivo">

                                Inactivo

                            </span>

                        <?php endif; ?>

                    </dd>

                </div>

                <div class="crud-detalle-campo">

                    <dt>Bloqueo de acceso</dt>

                    <dd>

                        <?php if (
                            (bool) $usuarioAsociado["bloqueado"]
                        ): ?>

                            <span class="crud-estado
                                         estado-bloqueado">

                                Bloqueado

                            </span>

                        <?php else: ?>

                            <span class="crud-estado
                                         estado-activo">

                                No bloqueado

                            </span>

                        <?php endif; ?>

                    </dd>

                </div>

            </dl>

        <?php endif; ?>

    </section>

    <!-- CUENTAS BANCARIAS -->

    <section class="crud-detalle-seccion">

        <div class="crud-detalle-encabezado">

            <h2>Cuentas bancarias asociadas</h2>

            <span class="texto-secundario">

                Total: <?= count($cuentas) ?>

            </span>

        </div>

        <?php if (count($cuentas) === 0): ?>

            <div class="alerta alerta-info">

                Este cliente todavía no tiene
                cuentas bancarias asociadas.

            </div>

        <?php else: ?>

            <div class="crud-tabla-responsive">

                <table class="crud-tabla">

                    <thead>

                        <tr>

                            <th scope="col">
                                Número de cuenta
                            </th>

                            <th scope="col">Tipo</th>

                            <th scope="col">Saldo</th>

                            <th scope="col">Estado</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach (
                            $cuentas as $cuenta
                        ): ?>

                            <tr>

                                <td>

                                    <?= escaparCliente(
                                        $cuenta["numero"]
                                    ) ?>

                                </td>

                                <td>

                                    <?= escaparCliente(
                                        ucfirst(
                                            strtolower(
                                                $cuenta["tipo"]
                                            )
                                        )
                                    ) ?>

                                </td>

                                <td class="crud-saldo">

                                    $<?= number_format(
                                        (float) $cuenta["saldo"],
                                        2,
                                        ".",
                                        ","
                                    ) ?>

                                </td>

                                <td>

                                    <?php if (
                                        (bool) $cuenta["estado"]
                                    ): ?>

                                        <span class="crud-estado
                                                     estado-activo">

                                            Activa

                                        </span>

                                    <?php else: ?>

                                        <span class="crud-estado
                                                     estado-inactivo">

                                            Inactiva

                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

    <!-- INFORMACION DE DEPENDENCIAS -->

    <section class="crud-detalle-seccion">

        <h2>Información administrativa</h2>

        <?php if ($puedeEliminar): ?>

            <div class="alerta alerta-info">

                Este cliente no tiene usuarios
                ni cuentas bancarias asociados.

                Cumple las condiciones necesarias
                para su eliminación.

            </div>

        <?php else: ?>

            <div class="alerta alerta-info">

                Este cliente no puede eliminarse
                porque tiene usuarios o cuentas
                bancarias asociados.

            </div>

        <?php endif; ?>

    </section>

<?php endif; ?>

<?php

require __DIR__ .
    "/../../components/admin/fin.php";
