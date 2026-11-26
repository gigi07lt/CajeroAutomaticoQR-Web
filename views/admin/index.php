<?php

require_once __DIR__ . "/../../middleware/roles.php";
require_once __DIR__ . "/../../services/CsrfService.php";

$usuario = exigirRol("ADMIN");

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Administración | Banco FISEI</title>

    <link rel="stylesheet"
          href="<?= urlAplicacion('assets/css/style.css') ?>">

</head>

<body>

    <header class="panel-header">

        <div class="marca">

            <div class="marca-icono">F</div>

            <div>
                <strong>BANCO FISEI</strong>
                <small>Administración</small>
            </div>

        </div>

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

    </header>

    <main class="panel-contenedor">

        <h1>Panel administrativo</h1>

        <p class="texto-secundario">

            Bienvenido,

            <?= htmlspecialchars(
                $usuario["usuario"],
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </p>

        <div class="alerta alerta-info">
            Has iniciado sesión correctamente
            con permisos administrativos.
        </div>

        <div class="panel-grid">

            <article class="panel-tarjeta">
                <h3>Gestión de usuarios</h3>
                <p class="texto-secundario">
                    Crear, editar y administrar usuarios.
                </p>
                <small>Próximamente</small>
            </article>

            <article class="panel-tarjeta">
                <h3>Cuentas bancarias</h3>
                <p class="texto-secundario">
                    Administrar las cuentas de los clientes.
                </p>
                <small>Próximamente</small>
            </article>

            <article class="panel-tarjeta">
                <h3>Tarjetas QR</h3>
                <p class="texto-secundario">
                    Emitir, consultar y anular tarjetas.
                </p>
                <small>Próximamente</small>
            </article>

            <article class="panel-tarjeta">
                <h3>Reportes</h3>
                <p class="texto-secundario">
                    Consultar movimientos y generar reportes.
                </p>
                <small>Próximamente</small>
            </article>

        </div>

    </main>

</body>

</html>
