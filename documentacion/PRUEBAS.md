# Pruebas y verificación de la fase 4.6

## Comprobaciones realizadas en el entorno de entrega

- Sintaxis PHP de todos los archivos del proyecto con PHP 8.4.24 CLI.
- Sintaxis JavaScript de `assets/js/login.js` con Node.js.
- Suite `tests/fase46_validaciones.php`: 51 aprobadas, 0 fallidas.
- 20 códigos QR (incluidos códigos de prueba existentes y nuevos aleatorios): las matrices coincidieron con una implementación independiente de QR versión 1-L máscara 0 y los PNG se decodificaron correctamente con OpenCV.
- Servidor PHP local: login HTTP 200, los cuatro módulos y la descarga QR sin sesión redirigen a login (HTTP 302).
- No hay controlador PDO MySQL ni servidor MySQL disponible en el entorno de entrega. Las pruebas de integración, regresión con MySQL y pruebas HTTP autenticadas están pendientes de ejecutar en XAMPP. No se presentan como aprobadas.

## Ejecutar en Git Bash (XAMPP)

Con Apache y MySQL iniciados, y `config/config.php` configurado:

```bash
/c/xampp/php/php.exe tests/fase46_validaciones.php &&
/c/xampp/php/php.exe tests/fase46_integracion.php &&
/c/xampp/php/php.exe tests/autenticacion.php &&
/c/xampp/php/php.exe tests/seguridad.php &&
/c/xampp/php/php.exe tests/dashboard.php &&
/c/xampp/php/php.exe tests/clientes.php &&
/c/xampp/php/php.exe tests/conexion.php
```

Los tests previos de autenticación y seguridad requieren las credenciales ficticias originales activas (admin y cliente 1800000001). El test de autenticación ahora comprueba también el acceso mediante nombre personalizado. No los ejecutes en producción. La suite nueva de integración crea fixtures temporales dentro de una transacción y ejecuta rollback; verifica igualdad exacta de los registros originales. Sus movimientos son únicamente datos transaccionales de prueba, no se confirman ni se muestran en el módulo. Los contadores AUTO_INCREMENT pueden aumentar después del rollback.

## Pruebas manuales HTTP y visuales

Usa una base de pruebas. Crea clientes nuevos para estas comprobaciones, no modifiques los originales.

1. Accede al dashboard como ADMIN. Verifica los cinco enlaces y que los indicadores respondan al registro de nuevos datos.
2. Registra un cliente temporal desde Clientes. En Usuarios, registra un nombre personalizado, por ejemplo `cliente.prueba`, con PIN `0123`; prueba PIN diferente, duplicidad de nombre y cliente ya asociado. No se debe permitir registrar ADMIN ni eliminar usuarios físicamente.
3. Cierra sesión e inicia como CLIENTE con `cliente.prueba` y `0123`. Confirma el acceso. Comprueba que el cliente original `1800000001` conserva su acceso.
4. Como ADMIN, comprueba desactivar/reactivar, bloquear con tres intentos de PIN erróneo, desbloquear y restablecer PIN; verifica que el PIN viejo deja de autenticar y que el administrador principal no tiene acciones modificables.
5. Registra una cuenta de diez dígitos no usada y saldo inicial de 125.50. Comprueba rechazo de saldo negativo, más de dos decimales, número duplicado y cliente sin usuario activo. Crea una segunda cuenta del mismo tipo con saldo cero.
6. En Tarjetas verifica un QR por cada cuenta activa. Desde Mis QR del titular descarga el PNG vigente y escanéalo con un lector independiente: el texto debe coincidir con el código mostrado. Desactiva la primera cuenta: conserva saldo y QR histórico inactivo con fecha de anulación. Reactívala: debe existir un QR distinto activo y el anterior inactivo. No deben duplicarse tarjetas activas al repetir la operación.
7. Intenta eliminar la cuenta con saldo 125.50: debe rechazarse. Elimina la cuenta de saldo cero y sin movimientos: sus tarjetas deben desaparecer en la misma transacción. Los casos con movimiento propio y relacionado están cubiertos por la suite de integración.
8. Abre Movimientos: si no hay operaciones previas, debe mostrar cero resultados. Prueba filtros combinados, fechas inválidas e invertidas, limpiar filtros y navegación. No hay formulario de escritura ni datos demo. Los casos de varias páginas están cubiertos por la suite transaccional.
9. Comprueba con navegador en modo móvil el listado, tablas desplazables, formularios y paginación de los cuatro módulos.
10. Comprueba protección de acceso: sin sesión los cuatro módulos redirigen a login; con sesión CLIENTE devuelven 403. Desde ADMIN, POST sin CSRF o con CSRF inválido a Usuarios/Cuentas devuelve 403; POST a Tarjetas/Movimientos devuelve 405. Verifica que no cambió ningún registro. Descarga de QR sin sesión no devuelve una imagen.
11. Comprueba con `tests/conexion.php` los totales. Los usuarios de prueba no se eliminan desde la interfaz por diseño: para repetir pruebas manuales sin residuos, restaura una copia de la base local de pruebas. No elimines clientes originales.

## Límites del simulador

El saldo inicial es un atributo de apertura, no un movimiento del cajero; no se genera un depósito ficticio. Los QR históricos se conservan cuando una cuenta se desactiva/reactiva y se retiran al eliminar físicamente una cuenta autorizada. El módulo administrativo de tarjetas solo consulta; las imágenes y descargas vigentes son exclusivas del titular. La futura fase transaccional debe validar que la cuenta y su QR estén activos antes de cualquier operación. Las credenciales de demostración y los PIN de cuatro dígitos no son apropiados para un banco real.

## Actualización posterior

El usuario confirmó en XAMPP las 234 pruebas de la fase 4.6 antes de agregar FPDF. Las pruebas posteriores a la entrega de la fase 5 y la validación final están descritas en `PRUEBAS-FASE5.md`.
