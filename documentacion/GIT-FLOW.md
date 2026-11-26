# Integración de la entrega única con Git Flow

El ZIP contiene archivos, no historial Git ni commits nuevos. Se conserva la base `2c2e733`. Si quieres mantener el repositorio existente, no sustituyas su carpeta `.git` ni su configuración privada.

1. Antes de copiar el ZIP, sincroniza `develop` y crea una rama:

```bash
git switch develop
git pull --ff-only origin develop
git switch -c feature/modulos-administrativos-46
```

2. Extrae el ZIP en una carpeta temporal y copia su contenido sobre el proyecto. No copies una carpeta adicional dentro del proyecto. Comprueba `git status --short -uall`.
3. Ejecuta las pruebas de `PRUEBAS.md`. Una vez aprobadas, organiza los commits. Propuesta (en español):

| Grupo | Archivos principales | Mensaje |
|---|---|---|
| Base compartida | `models/GestionAdministrativa.php`, `controllers/ModuloAdministrativoController.php`, `views/components/admin/modulo*.php`, `assets/css/crud.css` | Arquitectura: componentes compartidos de gestion administrativa |
| Usuarios | `models/UsuarioAdministrativo.php`, `services/GestionUsuariosService.php`, `views/admin/usuarios/`, `services/AutenticacionService.php`, `assets/js/login.js`, `tests/autenticacion.php` | Usuarios: gestion de credenciales y acceso personalizado |
| Cuentas | `models/CuentaAdministrativa.php`, `services/GestionCuentasService.php`, `views/admin/cuentas/` | Cuentas: apertura y ciclo de vida transaccional |
| Tarjetas | `models/TarjetaAdministrativa.php`, `libraries/QrPng.php`, `views/admin/tarjetas/` | Tarjetas: consulta y descarga de codigos QR |
| Movimientos | `models/MovimientoAdministrativo.php`, `services/ConsultaMovimientosService.php`, `views/admin/movimientos/` | Movimientos: consultas y filtros combinables |
| Dashboard | `views/admin/index.php` | Integracion: habilitar modulos administrativos |
| Pruebas y documentación | `tests/fase46_*.php`, `README.md`, `documentacion/` | Pruebas: validacion integral y documentacion de fase 4.6 |

Como esta es una entrega integral, los commits intermedios pueden depender de archivos que se incorporan en commits posteriores. Comprueba la rama completa antes del PR. Usa `git diff --cached --check` en cada commit.

4. Sincroniza con `develop`, sube la rama y crea un PR con base **develop**, no main. Conserva los commits mediante la opción Create a merge commit.
