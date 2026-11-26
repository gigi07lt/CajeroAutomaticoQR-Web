<?php

if (
    !isset($usuario, $tituloPagina, $paginaActual) ||
    ($usuario["rol"] ?? "") !== "ADMIN"
) {
    http_response_code(403);
    exit("Acceso no permitido.");
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars(
            $tituloPagina,
            ENT_QUOTES,
            "UTF-8"
        ) ?> | Banco FISEI
    </title>

    <link rel="stylesheet"
          href="<?= urlAplicacion('assets/css/style.css') ?>">

    <link rel="stylesheet"
          href="<?= urlAplicacion('assets/css/admin.css') ?>">

</head>

<body class="admin-body">

    <?php require __DIR__ . "/header.php"; ?>

    <main class="admin-contenido">
