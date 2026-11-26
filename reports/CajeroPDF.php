<?php
require_once __DIR__.'/../libraries/fpdf19/fpdf.php';

/** Documentos de cliente. Los datos se suministran ya autorizados por el controlador. */
class CajeroPDF extends FPDF
{
    private string $titulo='';
    private bool $tabla=false;
    private const ANCHOS=[31,26,47,25,31,30,87];
    private function texto(string $s): string {
        // FPDF usa las fuentes básicas Windows-1252. No emitir UTF-8 crudo.
        $s=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',' ',$s)??'';
        return function_exists('mb_convert_encoding')?mb_convert_encoding($s,'Windows-1252','UTF-8'):(iconv('UTF-8','Windows-1252//TRANSLIT',$s)?:($s==='0'?'0':''));
    }
    private function preparar(string $titulo,bool $horizontal=false): void {
        $this->titulo=$titulo;$this->SetTitle($this->texto($titulo));$this->SetAuthor('Banco FISEI');
        $this->SetMargins(10,10,10);$this->SetAutoPageBreak(true,20);$this->AliasNbPages();$this->AddPage($horizontal?'L':'P','A4');
    }
    public function Header(){
        $this->SetFillColor(11,23,43);$this->SetTextColor(255);$this->SetFont('Arial','B',20);$this->Cell(0,13,'BANCO FISEI',0,1,'L',true);
        $this->SetTextColor(100,116,139);$this->SetFont('Arial','',10);$this->Cell(0,7,$this->texto('Cajero automático · Simulación académica'),0,1);
        $this->SetDrawColor(37,99,235);$this->SetLineWidth(.6);$this->Line(10,$this->GetY()+1,$this->GetPageWidth()-10,$this->GetY()+1);$this->Ln(5);
        $this->SetTextColor(15,23,42);$this->SetFont('Arial','B',14);$this->Cell(0,9,$this->texto($this->titulo),0,1,'C');$this->Ln(3);
        if($this->tabla)$this->encabezadoTabla();
    }
    public function Footer(){
        $this->SetY(-15);$this->SetDrawColor(203,213,225);$this->SetLineWidth(.2);$this->Line(10,$this->GetY(),$this->GetPageWidth()-10,$this->GetY());$this->Ln(2);
        $this->SetFont('Arial','',8);$this->SetTextColor(100,116,139);$this->Cell(0,6,$this->texto('Banco FISEI | Uso personal | Página '.$this->PageNo().' de {nb}'),0,0,'R');
    }
    private function dato(string $etiqueta,string $valor): void {
        $this->SetFont('Arial','B',10);$this->SetTextColor(15,23,42);$this->Cell(0,6,$this->texto($etiqueta),0,1);
        $this->SetFont('Arial','',11);$this->MultiCell(0,6,$this->texto($valor),0,'L');$this->Ln(3);
    }
    private function moneda($m): string {return '$'.number_format((float)$m,2,'.',',');}
    private function tipo(string $t): string {return ['RETIRO'=>'Retiro','DEPOSITO'=>'Depósito','TRANSFERENCIA_ENVIADA'=>'Transferencia enviada','TRANSFERENCIA_RECIBIDA'=>'Transferencia recibida'][$t]??$t;}
    public function saldo(array $a): void {
        $this->preparar('COMPROBANTE DE CONSULTA DE SALDO');
        foreach(['Fecha de emisión'=>date('d/m/Y H:i:s'),'Titular'=>$a['nombres'],'Cédula'=>$a['cedula'],'Número de cuenta'=>$a['numero'],'Tipo de cuenta'=>$a['tipo'],'Estado'=>'Activa','Saldo disponible'=>$this->moneda($a['saldo'])] as $k=>$v)$this->dato($k,(string)$v);
        $this->dato('Observación','Consulta informativa. No constituye un movimiento bancario.');
    }
    public function comprobante(array $m): void {
        $this->preparar('COMPROBANTE DE OPERACIÓN');
        foreach(['Tipo de operación'=>$this->tipo($m['tipo']),'Fecha y hora de operación'=>$m['fecha'],'Referencia'=>$m['referencia'],'Titular'=>$m['nombres'],'Cuenta'=>$m['numero_cuenta'],'Cuenta relacionada'=>$m['cuenta_relacionada']??'No aplica','Monto'=>$this->moneda($m['monto']),'Saldo resultante'=>$this->moneda($m['saldo_resultante']),'Comisión'=>'$0.00'] as $k=>$v)$this->dato($k,(string)$v);
        $this->dato('Observación','Operación simulada, sin dinero real.');
    }
    private function encabezadoTabla(): void {
        $this->SetFillColor(11,23,43);$this->SetTextColor(255);$this->SetDrawColor(203,213,225);$this->SetLineWidth(.2);$this->SetFont('Arial','B',9);
        foreach(['Fecha y hora','Cuenta','Tipo','Monto','Saldo final','Cta. relacionada','Referencia'] as $i=>$t)$this->Cell(self::ANCHOS[$i],8,$this->texto($t),1,0,'C',true);
        $this->Ln();$this->SetTextColor(15,23,42);$this->SetFont('Arial','',8);
    }
    public function historial(array $d,string $usuario): void {
        $this->preparar('REPORTE DE MIS MOVIMIENTOS',true);
        $this->dato('Generado por', $usuario.' · '.date('d/m/Y H:i:s'));
        $et=['cuenta'=>'Cuenta','tipo'=>'Tipo','desde'=>'Desde','hasta'=>'Hasta'];$f=[];
        foreach($et as $k=>$label)if(($d['filtros'][$k]??'')!=='')$f[]=$label.': '.($k==='tipo'?$this->tipo($d['filtros'][$k]):$d['filtros'][$k]);
        $this->dato('Filtros', $f?implode(' | ',$f):'Todos los movimientos de la cuenta validada');
        $this->SetFont('Arial','B',10);$this->SetFillColor(219,234,254);$this->Cell(0,8,'Total de movimientos: '.count($d['filas']),0,1,'L',true);$this->Ln(3);
        $this->tabla=true;$this->encabezadoTabla();$alterno=false;
        if(!$d['filas']){$this->Cell(277,10,$this->texto('No existen movimientos que coincidan con los filtros.'),1,1,'C');return;}
        foreach($d['filas'] as $m){
            if($this->GetY()+8>$this->GetPageHeight()-20)$this->AddPage('L','A4');
            $this->SetFillColor(241,245,249);$this->SetFont('Arial','',8);$this->SetTextColor(15,23,42);
            $v=[$m['fecha'],$m['numero_cuenta'],$this->tipo($m['tipo']),$this->moneda($m['monto']),$this->moneda($m['saldo_resultante']),$m['cuenta_relacionada']??'-',$m['referencia']];
            foreach($v as $i=>$t)$this->Cell(self::ANCHOS[$i],8,$this->texto((string)$t),1,0,in_array($i,[3,4],true)?'R':'L',$alterno);
            $this->Ln();$alterno=!$alterno;
        }
    }
}
