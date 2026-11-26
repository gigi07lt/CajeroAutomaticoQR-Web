<?php

if (
    !isset($usuario, $tituloPagina) ||
    ($usuario["rol"] ?? "") !== "ADMIN"
) {
    http_response_code(403);
    exit("Acceso no permitido.");
}

?>

    </main>

    <footer class="admin-footer">

        Banco FISEI &copy; <?= date("Y") ?>

        | Sistema de simulación bancaria

    </footer>

</body>

</html>
