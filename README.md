# CajeroAutomaticoQR-Web — Banco FISEI

Simulador académico de cajero automático. Entrega administrativa hasta la fase 4.6, basada en el proyecto del commit `2c2e733`.

## Ejecutar en XAMPP

**Si ya tienes funcionando la fase 4.2:** no vuelvas a importar las tablas ni a ejecutar los datos de prueba. Conserva la base `cajero_web_db` y tu archivo privado `config/config.php`. Haz una copia de seguridad del proyecto y de la base de datos antes de reemplazar archivos.

1. Extrae el contenido del ZIP en una carpeta temporal. El ZIP tiene los archivos del proyecto en su raíz (no contiene `.git` ni configuración privada).
2. Copia los archivos a `C:\xampp\htdocs\CajeroAutomaticoQR-Web`, conservando `config/config.php` y `.git` de tu proyecto anterior. Consulta `documentacion/GIT-FLOW.md` si vas a registrar los cambios en Git.
3. Inicia Apache y MySQL. Abre `http://localhost:8080/CajeroAutomaticoQR-Web/index.php` (ajusta el puerto si corresponde).
4. Entra como administrador con las credenciales locales existentes. Los datos de demostración originales usan `admin` y `AdminFisei2026!`; no son credenciales de producción.

**Instalación desde cero:** requiere PHP 8.0 o superior, PDO MySQL, mbstring, sesiones y zlib. XAMPP dispone habitualmente de estas extensiones. Copia `config/config.example.php` a `config/config.php` y configura host, puerto, base de datos, usuario, contraseña y `base_path`. Para tu entorno local anterior, el puerto MySQL es 3307 y Apache 8080; la plantilla mantiene el puerto MySQL estándar 3306. En phpMyAdmin importa, en este orden, `sql/cajero_db.sql` y `sql/002_seguridad.sql` **solo en una base nueva**. Si quieres los datos académicos iniciales, ejecuta una sola vez `/c/xampp/php/php.exe sql/datos_prueba.php` desde Git Bash. No ejecutes el script sobre una base ya poblada.

## Módulos administrativos

- 4.1 Dashboard: indicadores dinámicos y acceso a los cinco módulos.
- 4.2 Clientes: CRUD, búsqueda, paginación, dependencias y cédula inmutable (conservado).
- 4.3 Usuarios: registro de credenciales CLIENTE con nombre personalizado y PIN de cuatro dígitos; activación, desactivación, desbloqueo y restablecimiento del PIN. ADMIN protegido y sin eliminación física de usuarios.
- 4.4 Cuentas: número manual de diez dígitos, saldo inicial no negativo y usuario activo requerido; múltiples cuentas por cliente. Estado editable; eliminación transaccional solo con saldo cero y sin movimientos propios ni relacionados.
- 4.5 Tarjetas: emisión automática al crear una cuenta, desactivación conjunta y nueva emisión al reactivar; consulta de tarjetas activas e históricas, PNG generado localmente, descarga protegida por sesión ADMIN.
- 4.6 Movimientos: solo lectura, filtros combinables, detalle, orden reciente primero y diez registros por página. No se agregan movimientos de demostración; el módulo queda vacío si la base no contiene movimientos.

No se necesita migración de esquema para estas cuatro fases. No se incluyen reportes FPDF, operaciones del cajero para clientes ni despliegue de hosting (fases futuras).

## Cambio de acceso de clientes

Ahora se inicia sesión con **nombre de usuario + PIN**, no necesariamente cédula. Los usuarios originales cuyo nombre de usuario coincide con su cédula conservan su acceso. Se admiten entre 1 y 50 caracteres ASCII: letras, números, puntos, guiones y guiones bajos. El PIN conserva ceros iniciales y se almacena mediante `password_hash`. Restablecerlo desbloquea y reinicia intentos sin activar automáticamente un usuario inactivo. El estado de usuario y el estado de sus cuentas son independientes. Un bloqueo de intentos no equivale a desactivación administrativa.

## Pruebas

Consulta `documentacion/PRUEBAS.md`. Ejecuta en una base local de pruebas, sin usuarios concurrentes. El archivo `tests/fase46_validaciones.php` no requiere MySQL; `tests/fase46_integracion.php` requiere una base configurada y utiliza una transacción revertida al finalizar.

## Arquitectura

Los módulos nuevos comparten `ModuloAdministrativoController`, las vistas `views/components/admin/modulo*.php`, el modelo base `GestionAdministrativa` y `crud.css`, manteniendo servicios y modelos específicos para usuarios, cuentas, tarjetas y movimientos. Las acciones de escritura se protegen mediante rol ADMIN, POST, CSRF, validaciones y consultas preparadas PDO. Las operaciones de cuenta y QR usan transacciones InnoDB.
