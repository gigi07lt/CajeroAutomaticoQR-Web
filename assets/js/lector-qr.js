'use strict';

/*
 * BANCO FISEI
 * Lector QR reutilizable para cuentas y transferencias.
 * Compatible con cámaras físicas y virtuales.
 */

(function () {

    const patron = /^TK-[A-F0-9]{12}$/;

    function codigo(valor) {

        const texto = String(valor).trim().toUpperCase();

        return patron.test(texto) ? texto : null;
    }

    document.querySelectorAll('[data-lector-qr]').forEach(root => {

        const form = root.closest('form');

        const input = root.querySelector('[name=qr]');
        const modo = root.querySelector('[name=modo_qr]');

        const inicio = root.querySelector('[data-qr-iniciar]');
        const detener = root.querySelector('[data-qr-detener]');

        const selector = root.querySelector('[data-qr-camara]');
        const dispositivos = root.querySelector('[data-qr-dispositivos]');

        const area = root.querySelector('[data-qr-area]');
        const video = root.querySelector('[data-qr-video]');
        const html5 = root.querySelector('[data-qr-html5]');

        const estado = root.querySelector('[data-qr-estado]');
        const enviar = form.querySelector('[data-qr-enviar]');

        const manual = root.dataset.manual === '1';

        let scanner = null;
        let activo = false;
        let ocupado = false;
        let generacion = 0;

        // Mostrar mensajes al usuario.

        function informar(mensaje) {

            estado.textContent = mensaje;
        }

        // Controlar la disponibilidad del formulario.

        function actualizar() {

            enviar.disabled =
                !codigo(input.value) ||
                (!manual && modo.value !== 'camara');
        }

        function actualizarBotones() {

            inicio.disabled = ocupado || activo;
            detener.hidden = !activo;
            detener.disabled = ocupado;
            selector.disabled = ocupado || activo;
        }

        // Detectar las cámaras antes de iniciar el lector.

        async function listarCamaras() {

            if (!navigator.mediaDevices) {
                return [];
            }

            const lista = (
    await navigator.mediaDevices.enumerateDevices()
).filter(d =>
    d.kind === 'videoinput' &&
    !/HP TrueVision/i.test(d.label)
);

            const anterior = selector.value;

            selector.replaceChildren(
                new Option('Automática', '')
            );

            lista.forEach((camara, indice) => {

                if (camara.deviceId) {

                    selector.add(
                        new Option(
                            camara.label || 'Cámara ' + (indice + 1),
                            camara.deviceId
                        )
                    );
                }
            });

            if (
                anterior &&
                lista.some(d => d.deviceId === anterior)
            ) {

                selector.value = anterior;

            } else {

                // Preferir cámara trasera en celulares.

                const trasera = lista.find(d =>
                    /back|rear|environment|trasera/i.test(d.label)
                );

                // Preferir cámara física en computadoras.

                const fisica = lista.find(d =>
                    d.deviceId &&
                    d.label &&
                    !/iriun|droidcam|virtual|obs/i.test(d.label)
                );

                const iriun = lista.find(d =>
                    /iriun/i.test(d.label)
                );

                const preferida = iriun || trasera || fisica;

                if (preferida) {
                    selector.value = preferida.deviceId;
                }
            }

            dispositivos.hidden =
                !lista.some(d => d.deviceId);

            return lista;
        }

        // Solicitar permisos si todavía no están disponibles.

        async function prepararCamaras() {

            let lista = await listarCamaras();

            if (!lista.some(d => d.deviceId && d.label)) {

                const stream =
                    await navigator.mediaDevices.getUserMedia({
                        video: true,
                        audio: false
                    });

                // Liberar la cámara antes de iniciar html5-qrcode.

                stream.getTracks().forEach(track => track.stop());

                lista = await listarCamaras();
            }

            return lista;
        }

        // Liberar los recursos del lector.

        async function liberar(instancia) {

            if (!instancia) {
                return;
            }

            try {

                if (instancia.isScanning) {
                    await instancia.stop();
                }

            } catch (error) {

                console.warn('Error al detener cámara:', error);
            }

            try {

                instancia.clear();

            } catch (error) {

                console.warn('Error al limpiar lector:', error);
            }
        }

        // Detener la cámara.

        async function cerrar() {

            generacion++;
            activo = false;

            const instancia = scanner;
            scanner = null;

            await liberar(instancia);

            video.srcObject = null;
            video.hidden = true;
            html5.hidden = true;
            area.hidden = true;

            actualizarBotones();
        }

        // Procesar un QR detectado.

        async function aceptar(valor, id) {

            if (id !== generacion || !activo) {
                return;
            }

            const qr = codigo(valor);

            if (!qr) {

                informar(
                    'QR no reconocido. Utiliza una tarjeta de Banco FISEI.'
                );

                return;
            }

            input.value = qr;
            modo.value = 'camara';

            actualizar();

            await cerrar();

            informar(
                'QR leído correctamente. Puedes continuar.'
            );
        }

        // Iniciar la cámara seleccionada.

        async function iniciar() {

            if (ocupado || activo) {
                return;
            }

            if (
                !window.isSecureContext ||
                !navigator.mediaDevices ||
                !navigator.mediaDevices.getUserMedia
            ) {

                informar(
                    'La cámara requiere localhost o una conexión HTTPS.'
                );

                return;
            }

            if (typeof window.Html5Qrcode !== 'function') {

                informar(
                    'No se encontró la biblioteca html5-qrcode.'
                );

                return;
            }

            ocupado = true;
            actualizarBotones();

            const id = ++generacion;

            let instancia = null;
            let lecturaPendiente = null;

            input.value = '';
            actualizar();

            try {

                informar('Buscando cámaras disponibles...');

                const lista = await prepararCamaras();

                if (id !== generacion) {
                    return;
                }

                if (!lista.length) {

                    throw new Error(
                        'No se encontraron cámaras disponibles.'
                    );
                }

                area.hidden = false;
                html5.hidden = false;
                video.hidden = true;

                // Usar directamente el ID de la cámara.
                // Si no existe selección, utilizar la trasera.

                const camara = selector.value || {
                    facingMode: 'environment'
                };

                console.log(
                    '[Banco FISEI] Cámara seleccionada:',
                    selector.options[selector.selectedIndex]?.text
                );

                instancia = new Html5Qrcode(html5.id);

                scanner = instancia;

                informar('Iniciando cámara...');

                // Configuración sencilla, sin resolución
                // obligatoria ni restricciones adicionales.

                await instancia.start(

                    camara,

                    {
                        fps: 10,
                        disableFlip: false
                    },

                    function (texto) {

                        if (id !== generacion) {
                            return;
                        }

                        // Esperar hasta que termine la inicialización.

                        if (!activo) {

                            if (codigo(texto)) {
                                lecturaPendiente = texto;
                            }

                            return;
                        }

                        void aceptar(texto, id);
                    },

                    function () {

                        // Todavía no se detecta un QR.
                        // Continuar escaneando.
                    }
                );

                if (id !== generacion) {

                    await liberar(instancia);

                    return;
                }

                activo = true;

                informar(
                    'Apunta la cámara al código QR de Banco FISEI.'
                );

                if (lecturaPendiente) {

                    await aceptar(lecturaPendiente, id);
                }

            } catch (error) {

                console.error(
                    '[Banco FISEI] Error al iniciar lector:',
                    error
                );

                if (id === generacion) {

                    await cerrar();

                    const mensaje = error?.message || String(error);

                    informar(
                        'No se pudo iniciar la cámara: ' +
                        mensaje +
                        '. Selecciona otra cámara e inténtalo nuevamente.'
                    );
                }

            } finally {

                ocupado = false;
                actualizarBotones();
            }
        }

        // Escaneo mediante cámara.

        inicio.addEventListener('click', () => {

            void iniciar();
        });

        detener.addEventListener('click', async () => {

            await cerrar();

            informar('Cámara detenida.');
        });

        // Ingreso manual permitido únicamente en desarrollo.

        input.addEventListener('input', () => {

            if (manual) {
                modo.value = 'manual';
            }

            actualizar();
        });

        // Validar el formulario antes de enviarlo.

        form.addEventListener('submit', evento => {

            const qr = codigo(input.value);

            if (
                !qr ||
                (!manual && modo.value !== 'camara')
            ) {

                evento.preventDefault();

                informar(
                    'Escanea un código QR válido antes de continuar.'
                );

                return;
            }

            input.value = qr;

            void cerrar();
        });

        // Liberar la cámara al abandonar la página.

        window.addEventListener('pagehide', () => {

            void cerrar();
        });

        document.addEventListener('visibilitychange', () => {

            if (document.hidden && (activo || ocupado)) {

                void cerrar();

                informar('Cámara detenida.');
            }
        });

        // Mostrar las cámaras disponibles desde el principio.

        if (
            window.isSecureContext &&
            navigator.mediaDevices
        ) {

            listarCamaras().catch(error => {

                console.warn(
                    'No se pudieron listar las cámaras:',
                    error
                );
            });
        }

        actualizar();
        actualizarBotones();
    });

})();
