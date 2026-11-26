<?php

require_once __DIR__ .
    '/../models/MovimientoAdministrativo.php';

class ConsultaMovimientosService
{
    // Validar y normalizar filtros.

    public static function filtros(array $entrada): array
    {
        $f = [];

        $campos = [
            'cliente',
            'cedula',
            'cuenta',
            'tipo',
            'desde',
            'hasta'
        ];

        foreach ($campos as $k) {

            $v = $entrada[$k] ?? '';

            if (!is_string($v) || strlen($v) > 400) {

                throw new DomainException(
                    'Filtro inválido.'
                );
            }

            $f[$k] = trim($v);
        }

        // Validar fechas.

        foreach (['desde', 'hasta'] as $k) {

            if ($f[$k] === '') {

                continue;
            }

            $v = $f[$k];

            if (
                !preg_match(
                    '/\A(\d{4})-(\d{2})-(\d{2})\z/',
                    $v,
                    $m
                )
                || !checkdate(
                    (int) $m[2],
                    (int) $m[3],
                    (int) $m[1]
                )
                || (int) $m[1] < 1000
            ) {

                throw new DomainException(
                    'Fecha inválida.'
                );
            }
        }

        // Validar el rango de fechas.

        if (
            $f['desde'] !== ''
            && $f['hasta'] !== ''
            && $f['desde'] > $f['hasta']
        ) {

            throw new DomainException(
                'La fecha inicial no puede ser posterior a la final.'
            );
        }

        // Validar el tipo de movimiento.

        if (
            $f['tipo'] !== ''
            && !in_array(
                $f['tipo'],
                MovimientoAdministrativo::TIPOS,
                true
            )
        ) {

            throw new DomainException(
                'Tipo de movimiento inválido.'
            );
        }

        return $f;
    }

    // Obtener los datos del reporte PDF.

    public static function obtenerReporte(
        PDO $conexion,
        array $entrada
    ): array
    {
        // Reutilizar las validaciones existentes.

        $filtros = self::filtros($entrada);

        // Consultar todos los movimientos.

        $modelo = new MovimientoAdministrativo(
            $conexion
        );

        $movimientos = $modelo->listarReporte(
            $filtros
        );

        // Preparar los datos del reporte.

        return [
            'filtros' => $filtros,
            'movimientos' => $movimientos,
            'total' => count($movimientos)
        ];
    }
}
