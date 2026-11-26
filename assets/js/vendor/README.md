# Decodificador alternativo local

El lector usa BarcodeDetector si el navegador lo admite. Para navegadores sin esa API, instala html5-qrcode 2.3.8 mediante `documentacion/instalar-lector-qr.ps1`. Esta descarga se hace una sola vez en el equipo de desarrollo, no en el hosting ni desde el navegador. Conserva el archivo JS y su licencia en este directorio y súbelos con el proyecto. No se incluye el binario JS en esta entrega porque la descarga externa no estuvo disponible en el entorno de construcción.
