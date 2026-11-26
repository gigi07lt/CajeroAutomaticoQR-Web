<?php

if (
    !isset($tarjeta, $usuario) ||
    ($usuario["rol"] ?? "") !== "ADMIN"
) {
    http_response_code(403);
    exit("Acceso no permitido.");
}

$coloresPermitidos = [
    "azul",
    "verde",
    "morado",
    "naranja"
];

$color = in_array(
    $tarjeta["color"],
    $coloresPermitidos,
    true
) ? $tarjeta["color"] : "azul";

?>

<article class="modulo-card">

    <div class="modulo-superior">

        <div class="modulo-icono <?= $color ?>"
             aria-hidden="true">

            <?= htmlspecialchars(
                $tarjeta["icono"],
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </div>

        <strong class="modulo-cantidad">

            <?= (int) $tarjeta["valor"] ?>

        </strong>

    </div>

    <h2>

        <?= htmlspecialchars(
            $tarjeta["titulo"],
            ENT_QUOTES,
            "UTF-8"
        ) ?>

    </h2>

    <p>

        <?= htmlspecialchars(
            $tarjeta["descripcion"],
            ENT_QUOTES,
            "UTF-8"
        ) ?>

    </p>

    <?php if ($tarjeta["ruta"] !== null): ?>

        <a class="modulo-enlace"
           href="<?= htmlspecialchars(
               urlAplicacion($tarjeta["ruta"]),
               ENT_QUOTES,
               "UTF-8"
           ) ?>">

            Acceder al módulo →

        </a>

    <?php else: ?>

        <span class="modulo-pendiente">

            Próximamente

        </span>

    <?php endif; ?>

</article>
