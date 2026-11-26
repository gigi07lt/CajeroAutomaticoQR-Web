# Lector QR del cajero

El mismo componente se usa en Mis cuentas > Validar cuenta y Transferencia > Identificar destinatario. No se solicita cámara en el login, retiros, depósitos ni consulta de saldo. No se necesita Iriun ni DroidCam. El navegador captura y decodifica el vídeo localmente; solo envía el texto QR por POST. Se prefiere la cámara trasera y se permite seleccionar otra si hay varias disponibles. El vídeo se detiene al leer un QR válido, cancelar, enviar el formulario o salir de la pantalla.

## Instalación de la alternativa de compatibilidad

El código incluye integración con BarcodeDetector y html5-qrcode 2.3.8. La biblioteca externa no pudo descargarse en el entorno de construcción; **no está incluida todavía**. Para ampliar compatibilidad, desde Git Bash en la raíz del proyecto, con conexión a Internet, ejecuta:

```bash
powershell.exe -NoProfile -ExecutionPolicy Bypass -File documentacion/instalar-lector-qr.ps1
ls -lh assets/js/vendor
```

El script descarga el JS y la licencia Apache 2.0 de una versión fija, verifica características básicas e imprime su SHA256 (no equivale a verificar contra un hash de confianza). Revisa los archivos antes de incorporarlos a Git. Alternativamente descarga los dos archivos de https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/ y guárdalos como `assets/js/vendor/html5-qrcode.min.js` y `assets/js/vendor/LICENSE-html5-qrcode.txt`. No hay CDN en tiempo de ejecución. Sin la biblioteca, solo funciona en navegadores que dispongan de BarcodeDetector con QR; la compatibilidad real varía por plataforma.

## Entornos

- PC: `http://localhost:8080/CajeroAutomaticoQR-Web/`, cámara integrada o USB.
- Celular: abrir directamente la aplicación publicada con HTTPS y certificado válido. La IP LAN de XAMPP con HTTP no suele permitir cámara; localhost del teléfono se refiere al teléfono, no a la PC. Para probar antes del hosting se requiere HTTPS local confiable para el celular.
- El celular no actúa como cámara remota de una página abierta en la PC. Ese flujo requeriría otro mecanismo de vinculación entre dispositivos y no forma parte de esta entrega.
- En hosting, `qr_manual_desarrollo` debe ser false. En localhost puede mantenerse true para pruebas. El campo QR será de solo lectura cuando el modo manual no esté permitido.

## Validaciones

1. En Mis cuentas selecciona una cuenta, pulsa Escanear QR, acepta permisos y apunta a una tarjeta impresa o mostrada en otro dispositivo. El texto debe coincidir y la cámara detenerse. Pulsa Validar cuenta. Rechaza tarjetas de otra cuenta, ajenas, inexistentes o inactivas.
2. En Transferencia escanea QR de otra cuenta, escribe el monto y verifica destinatario. Confirma únicamente después de revisar nombre, número y monto. El escaneo nunca ejecuta la transferencia por sí solo.
3. Comprueba negar permisos, detener, reabrir, cambiar cámara, salir de la página y sesión expirada; verifica que el indicador de cámara se apague. Prueba también con la biblioteca local instalada en un navegador sin BarcodeDetector.
4. Verifica que la opción manual no se muestre en hosting. El parámetro `modo_qr` indica la modalidad de interfaz y **no prueba** que una cadena se obtuvo de una cámara; puede ser manipulado por el cliente. No se usa como factor de autenticación. La protección real sigue siendo sesión CLIENTE, CSRF, token de un solo uso, propiedad y estado de cuenta y tarjeta, validación de montos y confirmación transaccional.
5. Las pruebas físicas, autenticadas con MySQL y en hosting están pendientes. El proyecto es una simulación académica.
