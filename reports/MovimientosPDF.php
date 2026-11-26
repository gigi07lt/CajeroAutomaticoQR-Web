<?php

require_once __DIR__ .
    '/../libraries/fpdf19/fpdf.php';

class MovimientosPDF extends FPDF
{
    // Convertir caracteres para las fuentes de FPDF.

    private function texto(string $valor): string
    {
        return mb_convert_encoding(
            $valor,
            'Windows-1252',
            'UTF-8'
        );
    }

    // Encabezado de cada página.

    public function Header()
    {
        $this->SetFillColor(11, 23, 43);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 20);

        $this->Cell(
            0, 13, 'BANCO FISEI',
            0, 1, 'L', true
        );

        $this->SetTextColor(100, 116, 139);
        $this->SetFont('Arial', '', 10);

        $this->Cell(
            0, 8,
            $this->texto('Sistema de simulación bancaria'),
            0, 1, 'L'
        );

        $this->SetDrawColor(37, 99, 235);
        $this->SetLineWidth(0.8);

        $this->Line(10, 33, 287, 33);

        $this->Ln(8);

        $this->SetTextColor(15, 23, 42);
        $this->SetFont('Arial', 'B', 15);

        $this->Cell(
            0, 10,
            'REPORTE DE MOVIMIENTOS BANCARIOS',
            0, 1, 'C'
        );

        $this->Ln(3);
    }

    // Pie de cada página.

    public function Footer()
    {
        $this->SetY(-15);

        $this->SetDrawColor(203, 213, 225);
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY(), 287, $this->GetY());

        $this->Ln(2);

        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100, 116, 139);

        $this->Cell(
            180, 8,
            'Banco FISEI | Uso administrativo',
            0, 0, 'L'
        );

        $this->Cell(
            97, 8,
            $this->texto(
                'Página ' . $this->PageNo() . ' de {nb}'
            ),
            0, 0, 'R'
        );
    }

    // Mostrar los filtros utilizados.

    private function mostrarFiltros(
        array $filtros,
        string $administrador
    ): void
    {
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(15, 23, 42);

        $this->Cell(
            138, 7,
            $this->texto('Generado por: ' . $administrador),
            0, 0
        );

        $this->Cell(
            139, 7,
            $this->texto(
                'Fecha de emisión: ' . date('d/m/Y H:i')
            ),
            0, 1
        );

        $etiquetas = [
            'cliente' => 'Cliente',
            'cedula' => 'Cédula',
            'cuenta' => 'Cuenta bancaria',
            'tipo' => 'Tipo de movimiento',
            'desde' => 'Desde',
            'hasta' => 'Hasta'
        ];

        foreach ($etiquetas as $clave => $etiqueta) {

            $valor = $filtros[$clave] ?? '';

            if ($valor === '') {
                continue;
            }

            $this->MultiCell(
                277, 6,
                $this->texto($etiqueta . ': ' . $valor)
            );
        }

        if (count(array_filter(
            $filtros,
            function ($valor) {
                return $valor !== '';
            }
        )) === 0) {

            $this->Cell(
                0, 7,
                'Filtros: Todos los movimientos',
                0, 1
            );
        }

        $this->Ln(4);
    }

    // Encabezados de la tabla.

    private function encabezadoTabla(): void
    {
        $this->SetFillColor(11, 23, 43);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(203, 213, 225);
        $this->SetLineWidth(0.2);
        $this->SetFont('Arial', 'B', 9);

        $columnas = [
            ['ID', 12],
            ['Fecha y hora', 32],
            ['Cuenta', 25],
            ['Cliente', 45],
            ['Tipo', 48],
            ['Monto', 22],
            ['Saldo final', 24],
            ['Cuenta relacionada', 35],
            ['Referencia', 34]
        ];

        foreach ($columnas as $columna) {

            $this->Cell(
                $columna[1],
                9,
                $this->texto($columna[0]),
                1, 0, 'C', true
            );
        }

        $this->Ln();

        $this->SetTextColor(15, 23, 42);
        $this->SetFont('Arial', '', 8);
    }

    // Formato legible del tipo de movimiento.

    private function tipoMovimiento(string $tipo): string
    {
        $tipos = [
            'RETIRO' => 'Retiro',
            'DEPOSITO' => 'Depósito',
            'TRANSFERENCIA_ENVIADA' => 'Transferencia enviada',
            'TRANSFERENCIA_RECIBIDA' => 'Transferencia recibida'
        ];

        return $tipos[$tipo] ?? $tipo;
    }

    // Ajustar textos largos al ancho de una celda.

    private function ajustarTexto(
        string $valor,
        float $ancho
    ): string
    {
        $valor = $this->texto($valor);

        if ($this->GetStringWidth($valor) <= $ancho - 3) {
            return $valor;
        }

        while (
            $valor !== '' &&
            $this->GetStringWidth($valor . '...') > $ancho - 3
        ) {
            $valor = substr($valor, 0, -1);
        }

        return $valor . '...';
    }

    // Mostrar una fila de movimientos.

    private function filaMovimiento(
        array $movimiento,
        bool $relleno
    ): void
    {
        // Crear una página y repetir los encabezados.

        if ($this->GetY() + 8 > $this->GetPageHeight() - 20) {

            $this->AddPage();
            $this->encabezadoTabla();
        }

        $this->SetFillColor(241, 245, 249);

        $columnas = [
            [(string) $movimiento['id'], 12, 'C'],

            [
                date(
                    'd/m/Y H:i',
                    strtotime($movimiento['fecha'])
                ),
                32, 'C'
            ],

            [$movimiento['numero_cuenta'], 25, 'C'],

            [$movimiento['nombres'], 45, 'L'],

            [
                $this->tipoMovimiento($movimiento['tipo']),
                48, 'L'
            ],

            [
                '$' . number_format(
                    (float) $movimiento['monto'], 2
                ),
                22, 'R'
            ],

            [
                '$' . number_format(
                    (float) $movimiento['saldo_resultante'], 2
                ),
                24, 'R'
            ],

            [
                $movimiento['cuenta_relacionada'] ?? '-',
                35, 'C'
            ],

            [
                $movimiento['referencia'] ?? '-',
                34, 'L'
            ]
        ];

        foreach ($columnas as $columna) {

            $this->Cell(
                $columna[1],
                8,
                $this->ajustarTexto(
                    (string) $columna[0],
                    $columna[1]
                ),
                1,
                0,
                $columna[2],
                $relleno
            );
        }

        $this->Ln();
    }

    // Generar el documento completo.

    public function generar(
        array $datos,
        string $administrador
    ): void
    {
        $this->SetTitle(
            'Reporte de movimientos bancarios'
        );

        $this->SetAuthor('Banco FISEI');

        $this->SetMargins(10, 10, 10);
        $this->SetAutoPageBreak(true, 20);

        $this->AliasNbPages();

        $this->AddPage('L', 'A4');

        $this->mostrarFiltros(
            $datos['filtros'],
            $administrador
        );

        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(219, 234, 254);

        $this->Cell(
            0, 9,
            'Total de movimientos: ' . $datos['total'],
            0, 1, 'L', true
        );

        $this->Ln(4);

        $this->encabezadoTabla();

        // Reporte sin movimientos.

        if (empty($datos['movimientos'])) {

            $this->SetFont('Arial', '', 10);

            $this->Cell(
                277, 12,
                $this->texto(
                    'No existen movimientos que coincidan con los filtros.'
                ),
                1, 1, 'C'
            );

            return;
        }

        // Imprimir todos los movimientos.

        $relleno = false;

        foreach ($datos['movimientos'] as $movimiento) {

            $this->filaMovimiento(
                $movimiento,
                $relleno
            );

            $relleno = !$relleno;
        }
    }
}