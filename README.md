# CajeroAutomaticoQR-Web — Banco FISEI

Simulador académico de cajero automático. Entrega de la fase 5 con integración de captura QR, basada en `536722a` (PR #7, fase 4.8). No conecta con bancos reales ni representa dinero real.

## Ejecutar sobre el proyecto existente

1. Respalda tu proyecto y la base `cajero_web_db`. Desde Git Bash, en `develop` limpio y sincronizado, crea `feature/cajero-cliente`.
2. Extrae el ZIP a una carpeta temporal y copia **su contenido** sobre `C:\xampp\htdocs\CajeroAutomaticoQR-Web`. Conserva `.git` y `config/config.php`. El ZIP no contiene esos elementos.
3. No vuelvas a importar SQL ni ejecutar datos de prueba: se conserva el esquema y todos los registros. No hay migraciones nuevas.
4. Para probar los QR sin cámara, añade una clave al array privado de `config/config.php`: `"qr_manual_desarrollo" => true` (separa las entradas con comas). Por defecto es `false` y el servidor además exige una conexión loopback (`127.0.0.1`, `::1` o equivalente). No habilitar en hosting.
5. Inicia Apache y MySQL. Abre `http://localhost:8080/CajeroAutomaticoQR-Web/index.php`. Selecciona Cliente e inicia sesión exclusivamente con cédula y PIN. En Mis cuentas elige una cuenta y valida su número y QR activo.
6. Para una prueba local con los datos de demostración originales, el cliente `1800000001` usa el PIN `1234` y posee las cuentas `2200000001` y `2200000006`; la cuenta `2200000002` pertenece a otro cliente. Verifica los datos actuales en ADMIN antes de probar. Las imágenes QR vigentes se obtienen exclusivamente desde Mis QR del propio titular. El administrador consulta los registros e historial. No uses credenciales demo en hosting.

## Instalación desde cero

PHP 8.0+ de 64 bits, PDO MySQL, mbstring (o iconv para PDF), sesiones y zlib. Copia `config/config.example.php` a `config/config.php`; configura host, puerto, base, usuario, contraseña y `base_path`. Para el entorno XAMPP previo: MySQL 3307 y Apache 8080; la plantilla usa 3306. Solo en una BD nueva, importa `sql/cajero_db.sql` y `sql/002_seguridad.sql` en ese orden. Opcionalmente ejecuta `sql/datos_prueba.php` una vez desde CLI para datos de simulación. El esquema no cambió. PHP y MySQL utilizan la hora de Ecuador continental.

## Panel administrativo (4.1–4.8)

Se conserva el dashboard, gestión de clientes, credenciales, cuentas, tarjetas QR, movimientos y reportes administrativos FPDF. Los QR históricos permanecen inactivos al reactivar una cuenta; se emite un nuevo código. Se ajustó el PDF administrativo para conservar referencias completas de transferencias y mantener la orientación horizontal de todas sus páginas.

## Cajero cliente (fase 5)

- Inicio de sesión exclusivamente por cédula y PIN del cliente; el administrador conserva usuario y contraseña. Los nombres personalizados se conservan como datos administrativos. El panel inicial tiene solo Mis cuentas y Mis QR.
- Mis cuentas y tarjetas propias, con PNG únicamente de QR vigentes de cuentas activas. El listado previo a la selección no muestra saldos.
- Selección de cuenta mediante número y QR activo coincidente (escaneo o manual local). El saldo aparece únicamente dentro de la cuenta. Volver a Mis cuentas, al inicio o a Mis QR invalida la selección y los tokens. La selección se invalida si la cuenta se desactiva o su tarjeta cambia.
- Información de cuenta y comprobante PDF de consulta de saldo, sin crear movimiento.
- Retiro simulado: $10–$300, múltiplos de $10, límite diario $1000 por cuenta. Montos rápidos y manual.
- Depósito simulado inmediato: $10–$1000, solo a la cuenta seleccionada.
- Transferencia: destinatario solo mediante QR activo, $10–$500, sin comisión, cuenta distinta (puede ser propia), nombre y número para confirmar. Una transacción registra ambas partidas con referencia común. Sin PIN adicional para operaciones de dinero.
- Comprobantes PDF de operaciones recién realizadas, disponibles en la pantalla de resultado hasta salir de ella o cinco minutos; no se pueden solicitar comprobantes antiguos por ID desde el historial. Para transferencias recibidas de otra sesión, se muestra un acceso reciente en el menú si llegaron durante la sesión actual y en los últimos cinco minutos. El comprobante de salida pertenece a la cuenta de origen; el de recepción aparece como reciente en la cuenta destinataria validada.
- Historial exclusivo de la cuenta activa validada, filtros combinables por tipo y fechas, 20 por página. PDF general y filtrado, sin limitarse a la página actual.
- Seguridad: sesión y rol, CSRF, token de formulario de un solo uso, confirmación de transferencia ligada a cuenta/QR/monto y válida dos minutos, validación de propiedad en SQL, centavos enteros, bloqueo de filas y transacciones InnoDB. La sesión expira por inactividad en cinco minutos.

## Cámara y escaneo QR

El lector compartido está integrado en la validación de cuenta y en transferencias. Usa BarcodeDetector cuando está disponible y admite la biblioteca local html5-qrcode 2.3.8 como alternativa. La descarga de esta biblioteca quedó pendiente por falta de acceso externo en el entorno de construcción: ejecuta `powershell.exe -NoProfile -ExecutionPolicy Bypass -File documentacion/instalar-lector-qr.ps1` desde Git Bash para incorporarla. Consulta `documentacion/CAMARA.md` para compatibilidad, permisos y pruebas. El login no requiere cámara. Usa localhost en PC o HTTPS válido en celular/hosting; no se necesita Iriun ni DroidCam. No se exponen imágenes de cámara al servidor. El hosting no deberá exponer configuraciones, scripts de prueba ni SQL.

## Pruebas y Git Flow

Consulta `documentacion/PRUEBAS-FASE5.md` y `documentacion/GIT-FLOW.md`. Las pruebas completas con MySQL y navegador siguen pendientes en XAMPP; no se declara el proyecto listo para producción. Las pruebas de integración crean datos temporales y revierten la transacción; ejecútalas únicamente sobre una base local de pruebas sin actividad concurrente. Las operaciones manuales del navegador sí persisten.
