<?php

require_once __DIR__ . "/../../services/CsrfService.php";

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | Banco FISEI</title>

    <link rel="stylesheet"
          href="<?= urlAplicacion('assets/css/style.css') ?>">

    <link rel="stylesheet"
          href="<?= urlAplicacion('assets/css/login.css') ?>">

</head>

<body class="login-body">

    <header class="login-header">

        <div class="marca">

            <div class="marca-icono">F</div>

            <div>
                <strong>BANCO FISEI</strong>
                <small>Banca digital</small>
            </div>

        </div>

        <span class="etiqueta-seguridad">
            Simulación académica
        </span>

    </header>

    <main class="login-contenedor">

        <!-- PRESENTACION DEL BANCO -->

        <section class="presentacion">

            <span class="subtitulo">
                BIENVENIDO A TU BANCA DIGITAL
            </span>

            <h1>
                Tu banco,<br>
                siempre contigo.
            </h1>

            <p>
                Consulta tus cuentas, realiza operaciones
                bancarias y transfiere mediante códigos QR
                desde un solo lugar.
            </p>

            <div class="tarjetas-decorativas">

                <div class="tarjeta-secundaria"></div>

                <div class="tarjeta-bancaria">

                    <strong>BANCO FISEI</strong>

                    <div class="chip"></div>

                    <div class="numero-tarjeta">
                        •••• •••• •••• 0001
                    </div>

                    <div class="tarjeta-inferior">
                        <small>TARJETA DIGITAL QR</small>
                        <span>◉</span>
                    </div>

                </div>

            </div>

            <div class="beneficios">

                <span>✓ Gestión de cuentas</span>

                <span>✓ Transferencias QR</span>

                <span>✓ Acceso protegido</span>

            </div>

        </section>

        <!-- FORMULARIO DE ACCESO -->

        <section class="login-panel">

            <h2>Iniciar sesión</h2>

            <p class="texto-secundario">
                Ingresa tus credenciales de acceso.
            </p>

            <?php if ($error !== ""): ?>

                <div class="alerta alerta-error" role="alert">

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>

                </div>

            <?php endif; ?>

            <?php if ($mensaje !== ""): ?>

                <div class="alerta alerta-info" role="status">

                    <?= htmlspecialchars(
                        $mensaje,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>

                </div>

            <?php endif; ?>

            <form id="formLogin"
                  method="POST"
                  action="<?= urlAplicacion('index.php') ?>">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= CsrfService::generarToken() ?>">

                <input type="hidden"
                       name="rol"
                       id="rol"
                       value="<?= htmlspecialchars(
                           $rolSeleccionado,
                           ENT_QUOTES,
                           'UTF-8'
                       ) ?>">

                <div class="selector-acceso">

                    <button type="button"
                            id="btnCliente"
                            class="tipo-acceso"
                            aria-pressed="false">
                        Cliente
                    </button>

                    <button type="button"
                            id="btnAdmin"
                            class="tipo-acceso"
                            aria-pressed="false">
                        Administrador
                    </button>

                </div>

                <div class="grupo-campo">

                    <label for="usuario"
                           id="labelUsuario">
                        Usuario
                    </label>

                    <input type="text"
                           name="usuario"
                           id="usuario"
                           maxlength="50"
                           autocomplete="username"
                           value="<?= htmlspecialchars(
                               $usuarioAnterior,
                               ENT_QUOTES,
                               'UTF-8'
                           ) ?>"
                           required>

                </div>

                <div class="grupo-campo">

                    <label for="clave"
                           id="labelClave">
                        Clave de acceso
                    </label>

                    <div class="campo-clave">

                        <input type="password"
                               name="clave"
                               id="clave"
                               maxlength="72"
                               autocomplete="current-password"
                               required>

                        <button type="button"
                                id="btnMostrarClave">
                            Mostrar
                        </button>

                    </div>

                </div>

                <!-- TECLADO NUMERICO -->

                <div id="tecladoNumerico"
                     class="teclado"
                     hidden>

                    <?php for ($i = 1; $i <= 9; $i++): ?>

                        <button type="button"
                                data-tecla="<?= $i ?>">
                            <?= $i ?>
                        </button>

                    <?php endfor; ?>

                    <button type="button"
                            data-tecla="C">
                        C
                    </button>

                    <button type="button"
                            data-tecla="0">
                        0
                    </button>

                    <button type="button"
                            data-tecla="BORRAR"
                            aria-label="Borrar último dígito">
                        ⌫
                    </button>

                </div>

                <p id="errorFormulario"
                   class="error-formulario"
                   role="alert"></p>

                <button type="submit"
                        class="btn btn-primario btn-ingresar">

                    Ingresar al sistema →

                </button>

            </form>

            <p class="nota-acceso">
                Acceso exclusivo para usuarios registrados.
            </p>

        </section>

    </main>

    <footer class="login-footer">

        Banco FISEI &copy; <?= date("Y") ?>
        | Proyecto académico de simulación bancaria

    </footer>

    <script src="<?= urlAplicacion('assets/js/login.js') ?>">
    </script>

</body>

</html>
