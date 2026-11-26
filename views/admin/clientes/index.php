<?php

require_once __DIR__ .
    "/../../../middleware/roles.php";

require_once __DIR__ .
    "/../../../controllers/ClienteController.php";

// Verificar permisos administrativos.

$usuario = exigirRol("ADMIN");

// Configuracion de la pagina.

$tituloPagina = "Gestión de clientes";

$paginaActual = "clientes";

// Variables iniciales.

$errorListado = "";

$mensajeFlash = null;

$datos = [];

$rutaListado = urlAplicacion(
    "views/admin/clientes/index.php"
);

// Funcion para escapar contenido HTML.

function escaparCliente(string $valor): string
{
    return htmlspecialchars(
        $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Generar enlaces de paginacion.

function enlacePaginaClientes(
    string $ruta,
    string $busqueda,
    int $pagina
): string {

    return $ruta . "?" . http_build_query([
        "buscar" => $busqueda,
        "pagina" => $pagina
    ]);
}

// Recuperar informacion del controlador.

try {

    $controlador = new ClienteController(
        obtenerConexion()
    );

    $datos = $controlador->listar($_GET);

    $mensajeFlash =
        $controlador->obtenerMensajeFlash();

} catch (Throwable $e) {

    error_log(
        "Listado de clientes: " .
        $e->getMessage()
    );

    http_response_code(503);

    $errorListado =
        "No se pudo cargar el listado de clientes. "
        . "Intenta nuevamente.";
}

// Cargar estructura administrativa.

require __DIR__ .
    "/../../components/admin/inicio.php";

?>

<section class="crud-encabezado">

    <div>

        <h1>Gestión de clientes</h1>

        

    </div>

<a href="<?= escaparCliente(
    urlAplicacion("views/admin/clientes/crear.php")
) ?>"
   class="btn btn-primario">

    + Nuevo cliente

</a>

</section>

<?php if ($mensajeFlash !== null): ?>

    <div class="alerta <?= $mensajeFlash["exito"]
        ? "alerta-exito"
        : "alerta-error" ?>"
        role="<?= $mensajeFlash["exito"]
        ? "status"
        : "alert" ?>">

        <?= escaparCliente(
            $mensajeFlash["mensaje"]
        ) ?>

    </div>

<?php endif; ?>

<?php if ($errorListado !== ""): ?>

    <div class="alerta alerta-error"
         role="alert">

        <?= escaparCliente($errorListado) ?>

    </div>

    <a href="<?= escaparCliente($rutaListado) ?>"
       class="btn btn-primario">

        Volver a intentar

    </a>

<?php else: ?>

    <section class="crud-contenedor">

        <!-- BUSCADOR -->

        <form method="GET"
              action="<?= escaparCliente($rutaListado) ?>"
              class="crud-buscador">

            <div class="crud-campo-busqueda">

                <label for="buscar">

                    Buscar cliente

                </label>

                <input type="search"
                       id="buscar"
                       name="buscar"
                       maxlength="100"
                       placeholder="Nombre o cédula"
                       value="<?= escaparCliente(
                           $datos["busqueda"]
                       ) ?>">

            </div>

            <button type="submit"
                    class="btn btn-primario">

                Buscar

            </button>

            <?php if ($datos["busqueda"] !== ""): ?>

                <a href="<?= escaparCliente(
                    $rutaListado
                ) ?>"
                   class="btn btn-secundario">

                    Limpiar

                </a>

            <?php endif; ?>

        </form>

        <!-- TOTAL DE REGISTROS -->

        <div class="crud-resumen">

            <p>

                Total de clientes:

                <strong>
                    <?= (int) $datos["total"] ?>
                </strong>

            </p>

        </div>

        <!-- TABLA DE CLIENTES -->

        <div class="crud-tabla-responsive">

            <table class="crud-tabla">

                <thead>

                    <tr>

                        <th scope="col">Cédula</th>

                        <th scope="col">
                            Nombres completos
                        </th>

                        <th scope="col">Acciones</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (
                        count($datos["clientes"]) === 0
                    ): ?>

                        <tr>

                            <td colspan="3"
                                class="crud-sin-resultados">

                                <?php if (
                                    $datos["busqueda"] !== ""
                                ): ?>

                                    No se encontraron clientes
                                    que coincidan con la búsqueda.

                                <?php else: ?>

                                    No existen clientes registrados.

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $datos["clientes"] as $cliente
                        ): ?>

                            <tr>

                                <td>

                                    <?= escaparCliente(
                                        $cliente["cedula"]
                                    ) ?>

                                </td>

                                <td>

                                    <?= escaparCliente(
                                        $cliente["nombres"]
                                    ) ?>

                                </td>

                                <td>

                                    <div class="crud-acciones">

                                        <a href="<?= escaparCliente(
                                            urlAplicacion(
                                                "views/admin/clientes/ver.php"
                                            ) . "?id=" . (int) $cliente["id"]
                                        ) ?>"
                                        class="crud-accion accion-ver">

                                            Ver

                                        </a>

                                        <a href="<?= escaparCliente(
                                            urlAplicacion(
                                                "views/admin/clientes/editar.php"
                                            ) . "?id=" . (int) $cliente["id"]
                                        ) ?>"
                                        class="crud-accion accion-editar">

                                            Editar

                                        </a>


                                    <form method="POST"
                                        action="<?= escaparCliente(
                                            urlAplicacion(
                                                "views/admin/clientes/eliminar.php"
                                            )
                                        ) ?>"
                                        class="crud-formulario-eliminar"
                                        onsubmit="return confirm('¿Estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.');">

                                        <input type="hidden"
                                            name="csrf_token"
                                            value="<?= escaparCliente(
                                                CsrfService::generarToken()
                                            ) ?>">

                                        <input type="hidden"
                                            name="id"
                                            value="<?= (int) $cliente["id"] ?>">

                                        <button type="submit"
                                                class="crud-accion accion-eliminar">

                                            Eliminar

                                        </button>

                                    </form>


                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- PAGINACION -->

        <?php

        $desde = $datos["total"] === 0
            ? 0
            : (
                ($datos["pagina"] - 1)
                * $datos["limite"]
            ) + 1;

        $hasta = $desde === 0
            ? 0
            : $desde +
                count($datos["clientes"]) - 1;

        ?>

        <div class="crud-paginacion">

            <p class="crud-paginacion-info">

                Mostrando <?= $desde ?>

                a <?= $hasta ?>

                de <?= (int) $datos["total"] ?>

                clientes.

            </p>

            <nav class="crud-paginacion-enlaces"
                 aria-label="Paginación de clientes">

                <?php if ($datos["pagina"] > 1): ?>

                    <a class="crud-pagina"
                       href="<?= escaparCliente(
                           enlacePaginaClientes(
                               $rutaListado,
                               $datos["busqueda"],
                               $datos["pagina"] - 1
                           )
                       ) ?>">

                        Anterior

                    </a>

                <?php else: ?>

                    <span class="crud-pagina
                                 pagina-deshabilitada"
                          aria-disabled="true">

                        Anterior

                    </span>

                <?php endif; ?>

                <span class="crud-pagina pagina-activa"
                      aria-current="page">

                    <?= (int) $datos["pagina"] ?>

                    /

                    <?= (int) $datos["total_paginas"] ?>

                </span>

                <?php if (
                    $datos["pagina"] <
                    $datos["total_paginas"]
                ): ?>

                    <a class="crud-pagina"
                       href="<?= escaparCliente(
                           enlacePaginaClientes(
                               $rutaListado,
                               $datos["busqueda"],
                               $datos["pagina"] + 1
                           )
                       ) ?>">

                        Siguiente

                    </a>

                <?php else: ?>

                    <span class="crud-pagina
                                 pagina-deshabilitada"
                          aria-disabled="true">

                        Siguiente

                    </span>

                <?php endif; ?>

            </nav>

        </div>

    </section>

<?php endif; ?>

<?php

require __DIR__ .
    "/../../components/admin/fin.php";
