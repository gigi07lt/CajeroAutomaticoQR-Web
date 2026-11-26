# Git Flow — fase 5

Base: `536722a` (merge PR #7 hacia `develop`). Este ZIP contiene archivos y no crea commits ni reemplaza el historial.

```bash
git switch develop
git pull --ff-only origin develop
git switch -c feature/cajero-cliente
```

Copia el contenido del ZIP sobre el repositorio, conservando `.git` y `config/config.php`. Verifica `git status --short -uall`. No subas configuración privada, dumps, imágenes QR descargadas ni PDF de clientes.

Después de revisar la ejecución, una propuesta de commits en español:

| Grupo | Archivos | Mensaje |
|---|---|---|
| Persistencia | `models/Cajero.php` | Modelo: consultas de cuentas y movimientos del cliente |
| Reglas bancarias | `services/CajeroService.php` | Servicio: operaciones bancarias transaccionales y validaciones |
| Acceso | `config/cajero.php`, `config/config.example.php`, `controllers/AccesoCuentaController.php`, `models/Usuario.php`, `services/AutenticacionService.php`, `assets/js/login.js`, `tests/autenticacion.php`, `services/SesionService.php`, `views/cliente/acceso.php`, `views/auth/login.php`, `index.html` | Autenticacion: acceso por cedula y validacion de cuenta con QR |
| Controlador | `controllers/CajeroController.php` | Controlador: seleccion de cuenta y flujo seguro del cajero |
| Interfaz | `views/cliente/index.php`, `views/cliente/movimientos.php`, `views/cliente/qr.php`, `assets/css/cajero.css`, `assets/js/cajero.js` | Interfaz: operaciones, tarjetas e historial del cliente |
| PDF | `reports/CajeroPDF.php`, `views/cliente/pdf.php`, `reports/MovimientosPDF.php` | Reportes: comprobantes y movimientos bancarios en PDF |
| Pruebas | `tests/fase5_validaciones.php`, `tests/fase5_integracion.php` | Pruebas: validaciones e integracion del cajero |
| Documentación | `README.md`, `documentacion/` | Documentacion: fase cinco y preparacion del lector QR |

Los commits intermedios de esta entrega integrada pueden depender de cambios posteriores. Valida la rama completa antes de crear PR. Ejecuta `git diff --cached --check` antes de cada commit y comprueba que no existan secretos en el diff. Publica `feature/cajero-cliente`, crea PR hacia **develop**, nunca main, y conserva los commits con merge commit. La cámara se integra en la misma rama. Añade un commit específico para el lector, componente, controladores, estilos y documentación. Instala y versiona también la biblioteca alternativa y su licencia antes del PR.
