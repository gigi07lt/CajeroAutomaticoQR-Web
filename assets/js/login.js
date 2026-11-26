document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.getElementById("formLogin");

    const rol = document.getElementById("rol");

    const usuario = document.getElementById("usuario");
    const clave = document.getElementById("clave");

    const labelUsuario =
        document.getElementById("labelUsuario");

    const labelClave =
        document.getElementById("labelClave");

    const btnCliente =
        document.getElementById("btnCliente");

    const btnAdmin =
        document.getElementById("btnAdmin");

    const btnMostrarClave =
        document.getElementById("btnMostrarClave");

    const teclado =
        document.getElementById("tecladoNumerico");

    const error =
        document.getElementById("errorFormulario");

    function configurarAcceso(tipo, limpiar = true) {

        rol.value = tipo;

        if (limpiar) {
            usuario.value = "";
            clave.value = "";
        }

        clave.type = "password";

        btnMostrarClave.textContent = "Mostrar";

        error.textContent = "";

        const esCliente = tipo === "CLIENTE";

        btnCliente.classList.toggle(
            "activo",
            esCliente
        );

        btnAdmin.classList.toggle(
            "activo",
            !esCliente
        );

        btnCliente.setAttribute(
            "aria-pressed",
            String(esCliente)
        );

        btnAdmin.setAttribute(
            "aria-pressed",
            String(!esCliente)
        );

        if (esCliente) {

            labelUsuario.textContent =
                "Cédula";

            usuario.placeholder =
                "Ingresa tu cédula";

            usuario.maxLength = 10;

            usuario.inputMode = "numeric";

            usuario.pattern = "[0-9]{10}";

            labelClave.textContent =
                "PIN de seguridad";

            clave.placeholder = "PIN de 4 dígitos";

            clave.maxLength = 4;

            clave.inputMode = "numeric";

            clave.pattern = "[0-9]{4}";

            teclado.hidden = false;

        } else {

            labelUsuario.textContent =
                "Nombre de usuario";

            usuario.placeholder =
                "Ingresa tu usuario";

            usuario.maxLength = 50;

            usuario.inputMode = "text";

            usuario.removeAttribute("pattern");

            labelClave.textContent =
                "Contraseña";

            clave.placeholder =
                "Ingresa tu contraseña";

            clave.maxLength = 72;

            clave.inputMode = "text";

            clave.removeAttribute("pattern");

            teclado.hidden = true;
        }
    }

    // Cambiar tipo de acceso.

    btnCliente.addEventListener("click", function () {

        configurarAcceso("CLIENTE");

        usuario.focus();
    });

    btnAdmin.addEventListener("click", function () {

        configurarAcceso("ADMIN");

        usuario.focus();
    });

    usuario.addEventListener("input", function () {
        error.textContent = "";
    });

    clave.addEventListener("input", function () {

        if (rol.value === "CLIENTE") {

            clave.value = clave.value
                .replace(/\D/g, "")
                .slice(0, 4);
        }

        error.textContent = "";
    });

    // Mostrar u ocultar la clave.

    btnMostrarClave.addEventListener(
        "click",
        function () {

            if (clave.type === "password") {

                clave.type = "text";

                btnMostrarClave.textContent =
                    "Ocultar";

            } else {

                clave.type = "password";

                btnMostrarClave.textContent =
                    "Mostrar";
            }
        }
    );

    // Teclado numerico virtual.

    teclado.querySelectorAll("button").forEach(
        function (boton) {

            boton.addEventListener(
                "click",
                function () {

                    if (rol.value !== "CLIENTE") {
                        return;
                    }

                    const tecla =
                        boton.dataset.tecla;

                    if (tecla === "C") {

                        clave.value = "";

                    } else if (tecla === "BORRAR") {

                        clave.value =
                            clave.value.slice(0, -1);

                    } else if (clave.value.length < 4) {

                        clave.value += tecla;
                    }

                    error.textContent = "";
                }
            );
        }
    );

    // Validar antes de enviar.

    formulario.addEventListener(
        "submit",
        function (evento) {

            error.textContent = "";

            if (
                usuario.value.trim() === "" ||
                clave.value === ""
            ) {

                evento.preventDefault();

                error.textContent =
                    "Completa todos los campos.";

                return;
            }

            if (rol.value === "CLIENTE") {

                if (!/^[0-9]{10}$/.test(usuario.value)) {

                    evento.preventDefault();

                    error.textContent =
                        "Ingresa una cédula de 10 dígitos.";

                    return;
                }

                if (!/^[0-9]{4}$/.test(clave.value)) {

                    evento.preventDefault();

                    error.textContent =
                        "El PIN debe tener 4 dígitos.";

                    return;
                }
            }
        }
    );

    // Restaurar el tipo de acceso seleccionado.

    configurarAcceso(
        rol.value === "ADMIN" ? "ADMIN" : "CLIENTE",
        false
    );

});
