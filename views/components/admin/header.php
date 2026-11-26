<?php

if (
    !isset($usuario, $tituloPagina, $paginaActual) ||
    ($usuario["rol"] ?? "") !== "ADMIN"
) {
    http_response_code(403);
    exit("Acceso no permitido.");
}

require_once __DIR__ .
    "/../../../services/CsrfService.php";

?>

<header class="panel-header">

    <div class="marca">

        <div class="marca-icono">F</div>

        <div>
            <strong>BANCO FISEI</strong>
            <small>Administración</small>
        </div>

    </div>

    <div class="admin-header-acciones">

        <?php if ($paginaActual !== "inicio"): ?>

            <a href="<?= urlAplicacion(
                'views/admin/index.php'
            ) ?>"
               class="btn btn-secundario">

                ← Volver al inicio

            </a>

        <?php endif; ?>

        <form method="POST"
              action="<?= urlAplicacion('logout.php') ?>">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= CsrfService::generarToken() ?>">

            <button type="submit"
                    class="btn btn-secundario">

                Cerrar sesión

            </button>

        </form>

    </div>

</header>
