<?php

require_once __DIR__ . "/../../middleware/roles.php";
require_once __DIR__ . "/../../services/CsrfService.php";

$usuario = exigirRol("CLIENTE");

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Mi banca | Banco FISEI</title>

    <link rel="stylesheet"
          href="<?= urlAplicacion('assets/css/style.css') ?>">

</head>

<body>

    <header class="panel-header">

        <div class="marca">

            <div class="marca-icono">F</div>

            <div>
                <strong>BANCO FISEI</strong>
                <small>Mi banca digital</small>
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

        <h1>Bienvenido a tu banca digital</h1>

        <p class="texto-secundario">

            Usuario:

            <?= htmlspecialchars(
                $usuario["usuario"],
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </p>

        <div class="alerta alerta-info">
            Has iniciado sesión correctamente.
        </div>

        <div class="panel-grid">

            <article class="panel-tarjeta">
                <h3>Mis cuentas</h3>
                <p class="texto-secundario">
                    Consulta tus cuentas y saldos disponibles.
                </p>
                <small>Próximamente</small>
            </article>

            <article class="panel-tarjeta">
                <h3>Retirar dinero</h3>
                <p class="texto-secundario">
                    Realiza retiros simulados.
                </p>
                <small>Próximamente</small>
            </article>

            <article class="panel-tarjeta">
                <h3>Depositar dinero</h3>
                <p class="texto-secundario">
                    Realiza depósitos en tus cuentas.
                </p>
                <small>Próximamente</small>
            </article>

            <article class="panel-tarjeta">
                <h3>Transferencias</h3>
                <p class="texto-secundario">
                    Transfiere por cuenta bancaria o código QR.
                </p>
                <small>Próximamente</small>
            </article>

            <article class="panel-tarjeta">
                <h3>Mis tarjetas QR</h3>
                <p class="texto-secundario">
                    Consulta tus tarjetas digitales.
                </p>
                <small>Próximamente</small>
            </article>

            <article class="panel-tarjeta">
                <h3>Movimientos</h3>
                <p class="texto-secundario">
                    Consulta tu historial y comprobantes.
                </p>
                <small>Próximamente</small>
            </article>

        </div>

    </main>

</body>

</html>
