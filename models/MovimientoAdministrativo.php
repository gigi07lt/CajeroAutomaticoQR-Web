<?php

require_once __DIR__ . '/GestionAdministrativa.php';

class MovimientoAdministrativo extends GestionAdministrativa
{
    public const TIPOS = [
        'RETIRO',
        'DEPOSITO',
        'TRANSFERENCIA_ENVIADA',
        'TRANSFERENCIA_RECIBIDA'
    ];

    private const CAMPOS =
        'm.id,
         m.numero_cuenta,
         m.tipo,
         m.monto,
         m.fecha,
         m.cuenta_relacionada,
         m.saldo_resultante,
         m.referencia,
         c.nombres,
         c.cedula';

    private const ORIGEN =
        'FROM movimientos m
         JOIN cuentas a
            ON a.numero = m.numero_cuenta
         JOIN clientes c
            ON c.id = a.id_cliente';

    private const ORDEN =
        'm.fecha DESC, m.id DESC';

    // Construir las condiciones de busqueda.

    private function construirFiltros(array $f): array
    {
        $where = [];
        $params = [];

        $campos = [
            'cliente' => 'c.nombres',
            'cedula' => 'c.cedula',
            'cuenta' => 'm.numero_cuenta'
        ];

        foreach ($campos as $clave => $campo) {

            $this->filtro(
                $where,
                $params,
                $campo,
                $f[$clave] ?? ''
            );
        }

        if (($f['tipo'] ?? '') !== '') {

            $where[] = 'm.tipo = ?';

            $params[] = $f['tipo'];
        }

        if (($f['desde'] ?? '') !== '') {

            $where[] = 'm.fecha >= ?';

            $params[] =
                $f['desde'] . ' 00:00:00';
        }

        if (($f['hasta'] ?? '') !== '') {

            $where[] = 'm.fecha <= ?';

            $params[] =
                $f['hasta'] . ' 23:59:59';
        }

        return [
            'where' => $where,
            'params' => $params
        ];
    }

    // Listar movimientos con paginacion.

    public function listar(
        array $f,
        int $pagina
    ): array
    {
        $filtros = $this->construirFiltros($f);

        return $this->paginar(
            self::CAMPOS,
            self::ORIGEN,
            $filtros['where'],
            $filtros['params'],
            self::ORDEN,
            $pagina
        );
    }

    // Consultar un movimiento individual.

    public function ver(int $id): ?array
    {
        $sql = 'SELECT ' .
            self::CAMPOS . ' ' .
            self::ORIGEN .
            ' WHERE m.id = ?';

        return $this->uno(
            $sql,
            [$id]
        );
    }

    // Obtener todos los movimientos del reporte.

    public function listarReporte(array $f): array
    {
        $filtros = $this->construirFiltros($f);

        $sql = 'SELECT ' .
            self::CAMPOS . ' ' .
            self::ORIGEN;

        if (!empty($filtros['where'])) {

            $sql .= ' WHERE ' .
                implode(
                    ' AND ',
                    $filtros['where']
                );
        }

        $sql .= ' ORDER BY ' . self::ORDEN;

        return $this->todos(
            $sql,
            $filtros['params']
        );
    }
}
