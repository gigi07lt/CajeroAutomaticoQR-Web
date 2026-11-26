# Pruebas del cajero — fase 5

## Resultados de entrega

- Suite `tests/fase5_validaciones.php`: 62 aprobadas, 0 fallidas (PHP 8.4 CLI). Incluye precisión monetaria, límites, filtros, tokens de un solo uso y generación real de PDF FPDF (saldo, cuatro tipos de comprobante, historial vacío y multipágina, reporte administrativo multipágina).
- Regresión sin BD `tests/fase46_validaciones.php`: 51 aprobadas, 0 fallidas.
- No se dispone de PDO MySQL ni servidor MySQL en el entorno de entrega. La nueva suite de integración no fue ejecutada aquí; las pruebas SQL, HTTP autenticadas, concurrencia y regresión completa quedan pendientes en tu XAMPP. Las 234 pruebas aprobadas anteriormente por el usuario corresponden a la fase 4.6 antes de los reportes y esta nueva implementación.
- Se verificó la generación real y visualización de muestras de saldo, comprobante e historial, incluyendo PDF de varias páginas y referencias largas. Las muestras no se incluyen en el ZIP ni se insertan en MySQL.

## Ejecución local (cuando decidas realizar la validación final)

```bash
/c/xampp/php/php.exe tests/fase5_validaciones.php
/c/xampp/php/php.exe tests/fase5_integracion.php
/c/xampp/php/php.exe tests/fase46_validaciones.php
/c/xampp/php/php.exe tests/fase46_integracion.php
/c/xampp/php/php.exe tests/autenticacion.php
/c/xampp/php/php.exe tests/seguridad.php
/c/xampp/php/php.exe tests/dashboard.php
/c/xampp/php/php.exe tests/clientes.php
/c/xampp/php/php.exe tests/conexion.php
```

Ejecuta exclusivamente sobre una base local de pruebas, sin otras sesiones concurrentes. La suite de integración de fase 5 crea clientes, usuarios, cuentas, tarjetas y movimientos en una transacción revertida al finalizar, verificando igualdad exacta de registros. Los contadores AUTO_INCREMENT pueden aumentar. Las pruebas originales de autenticación/seguridad necesitan las credenciales demo originales.

## Flujo manual (las operaciones persisten; usar copia de BD)

1. Prueba inicio únicamente por cédula + PIN, incluso con nombre administrativo personalizado. Rechaza usuario personalizado y QR como credenciales de login. Verifica que el panel solo muestra Mis cuentas y Mis QR. Valida cuenta con número y QR coincidentes (flag local), rechaza QR ajeno, inactivo o de otra cuenta propia.
2. Selecciona una cuenta de prueba y valida su QR; vuelve a Mis cuentas y comprueba que exige validación otra vez. Comprueba saldo, información y PDF de consulta. No se debe insertar movimiento por consultar saldo.
3. Retira $10, $300, y comprueba rechazo de $15, $301, saldo insuficiente y superar $1000 diarios por cuenta. Comprueba botones rápidos y que no se solicite PIN adicional. Verifica saldo resultante, referencia y comprobante PDF.
4. Deposita $10 y $1000 en la cuenta seleccionada, prueba $9.99 y $1000.01. No debe permitirse elegir otro destino ni confirmar dos veces el mismo formulario. No se solicita confirmación adicional.
5. En Transferencia introduce QR de destino desde ADMIN. Comprueba nombre, cuenta y monto antes de confirmar. Prueba transferencias propias y ajenas, QR inactivo, mismo origen, montos $9.99 y $500.01, saldo insuficiente y cancelar. La confirmación debe caducar después de dos minutos. Comprueba débito/crédito, dos movimientos, referencia común y cero comisión. Verifica que una desactivación/reactivación del destino antes de confirmar invalida el QR anterior.
6. Descarga los comprobantes inmediatamente después de cada operación. Al volver al menú no debe aparecer una opción de comprobantes históricos; para ingresos recibidos durante la sesión, se permite comprobante reciente (máximo cinco minutos). El historial sigue disponible en PDF general/filtrado. Manipula ID de comprobante o código de tarjeta para intentar acceder a otra cuenta: debe rechazarse.
7. Desde ADMIN desactiva y reactiva una cuenta que esté seleccionada en un segundo navegador. El cliente debe perder la selección, incluso si la cuenta vuelve a activarse, y requerir nueva validación; el historial de la cuenta inactiva se conserva en la BD, pero no se puede consultar desde el cliente hasta validar la cuenta nuevamente.
8. Historial: filtros combinados, fechas inválidas, páginas de 20, PDF general y filtrado con todos los resultados, sin registros de otras cuentas, incluso del mismo cliente. Comprueba QR PNG propios y rechazo de QR ajeno. Los saldos y movimientos deben aparecer en dashboard y reporte ADMIN.
9. Pruebas de seguridad HTTP: solicitudes POST sin CSRF deben rechazarse con 403; formularios repetidos con token consumido, cuenta manipulada y confirmación vieja deben rechazarse sin efectos; sin sesión redirigir al login y rol incorrecto 403. Los endpoints PDF/PNG solo GET. Verifica manual QR deshabilitado por defecto y con flag true solo loopback.
10. Prueba dos retiros simultáneos desde sesiones independientes sobre la misma cuenta con saldo/límite insuficiente para ambos; solo una operación debe completarse. Prueba transferencias simultáneas en sentidos opuestos sin saldos negativos ni movimientos parciales; si MySQL detecta un deadlock, la operación rechazada debe revertirse por completo y el cliente debe revisar el historial antes de repetir.
11. Prueba diseño móvil, expiración de sesión, cierre, selección y recuperación de datos. Cámara/HTTPS: sigue documentacion/CAMARA.md; pruebas físicas pendientes.

No utilizar esta aplicación de simulación como sistema bancario real.

## Comprobaciones adicionales en la entrega

- 72 archivos PHP sin errores de sintaxis (PHP 8.4 CLI); sintaxis de `login.js` y `cajero.js` correcta (Node.js).
- Con servidor PHP local y configuración temporal: login HTTP 200 en la entrega anterior; cajero, PDF y QR propios sin sesión redirigieron al inicio (302), igual que dashboard y PDF ADMIN. La revisión actual elimina el acceso alternativo sin sesión. No se probaron rutas autenticadas porque no hay MySQL disponible. La configuración temporal fue eliminada.

## Revisión de navegación

Se ejecutaron nuevamente las suites sin BD (62 y 51 casos), la sintaxis de los 72 archivos PHP y ambos JavaScript. Se agregaron comprobaciones de alcance de cuenta y cierre de selección. Las pruebas de autenticación por cédula y las nuevas comprobaciones SQL de historial y comprobantes quedan pendientes en XAMPP.
