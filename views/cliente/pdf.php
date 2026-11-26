<?php
require_once __DIR__.'/../../middleware/roles.php';
require_once __DIR__.'/../../controllers/CajeroController.php';
require_once __DIR__.'/../../reports/CajeroPDF.php';
$u=exigirRol('CLIENTE');if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);header('Allow: GET');exit;}
try{
    $c=new CajeroController(obtenerConexion(),$u);$pdf=new CajeroPDF();$tipo=$_GET['tipo']??'';
    if($tipo==='saldo'){$pdf->saldo($c->exigirCuenta());$nombre='Consulta_Saldo';}
    elseif($tipo==='historial'){$f=array_intersect_key($_GET,array_flip(['cuenta','desde','hasta']));$f['tipo']=$_GET['filtro_tipo']??'';$d=$c->historial($f,null);$pdf->historial($d,$u['usuario']);$nombre='Mis_Movimientos';}
    elseif($tipo==='comprobante'){$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>2147483647]]);if(!$id)throw new DomainException('Comprobante no disponible.');$pdf->comprobante($c->comprobante($id));$nombre='Comprobante';}
    else throw new DomainException('Documento no disponible.');
    // Generar antes de enviar encabezados para no entregar un archivo incompleto si FPDF falla.
    $contenido=$pdf->Output('S');header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="'.$nombre.'_'.date('Y-m-d_H-i-s').'.pdf"');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');echo $contenido;
}catch(DomainException $e){http_response_code(400);echo htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8');}catch(Throwable $e){error_log($e->getMessage());http_response_code(500);echo 'No fue posible generar el documento.';}
