<?php

require_once __DIR__ .
    '/../../../middleware/roles.php';

require_once __DIR__ .
    '/../../../config/database.php';

require_once __DIR__ .
    '/../../../services/ConsultaMovimientosService.php';

require_once __DIR__ .
    '/../../../reports/MovimientosPDF.php';

// Permitir el acceso únicamente al administrador.

$usuario = exigirRol('ADMIN');

// Permitir únicamente solicitudes GET.

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);
    header('Allow: GET');

    exit('Método no permitido.');
}

try {

    // Obtener la conexión con MySQL.

    $conexion = obtenerConexion();

    // Obtener los movimientos según los filtros.

    $datos = ConsultaMovimientosService::obtenerReporte(
        $conexion,
        $_GET
    );

    // Crear el documento PDF.

    $pdf = new MovimientosPDF();

    $pdf->generar(
        $datos,
        $usuario['usuario']
    );

    // Establecer el nombre del archivo.

    $nombreArchivo =
        'Reporte_Movimientos_' .
        date('Y-m-d_H-i-s') .
        '.pdf';

    // Evitar el almacenamiento en caché.

    header(
        'Cache-Control: no-store, no-cache, must-revalidate'
    );

    // Mostrar el PDF en el navegador.

    $pdf->Output('I', $nombreArchivo);

    exit;

} catch (DomainException $e) {

    http_response_code(400);

    echo htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );

} catch (Throwable $e) {

    error_log(
        'Error al generar reporte de movimientos: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo 'No fue posible generar el reporte de movimientos.';
}
