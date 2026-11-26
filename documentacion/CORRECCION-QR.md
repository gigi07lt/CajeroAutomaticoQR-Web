# Corrección final de tarjetas QR

El estado de la tarjeta y el estado de la cuenta son distintos. Una cuenta reactivada tiene un nuevo QR vigente; el anterior permanece histórico. El listado administrativo ordena vigentes primero y muestra ambas columnas. No se alteró la transacción de reactivación: ya actualiza cuentas y emite el nuevo QR de forma atómica.

Mis QR y su contador solo incluyen tarjetas vigentes de cuentas activas del titular. La ruta PNG del cliente verifica las mismas condiciones, incluso con URL directa. Las antiguas rutas administrativas de imagen y descarga devuelven 403.

## Validación local

- Ejecutar `tests/fase5_integracion.php` (47 comprobaciones previstas) y las demás suites.
- Desactivar una cuenta: estado inactivo en listado y detalle de cuentas; desaparece de Mis QR. Reactivar: estado activo en ambos; un nuevo QR aparece en Mis QR.
- En administración, buscar por número: QR vigente primero y anterior histórico, ambos con cuenta activa. No hay botón PNG ni imagen.
- Abrir la antigua ruta administrativa `?vista=descargar&id=CODIGO`: 403.
- Como titular, abrir `views/cliente/qr.php?codigo=CODIGO_ANTERIOR&descargar=1`: 404. El nuevo QR devuelve PNG.
- Comprobar QR ajeno rechazado y escanear el vigente en acceso y transferencia.

No hay migraciones SQL ni cambios en cámara, PDF u operaciones monetarias.
