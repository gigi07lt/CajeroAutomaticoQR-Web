<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../services/CajeroService.php';
require_once __DIR__.'/../reports/CajeroPDF.php';
require_once __DIR__.'/../reports/MovimientosPDF.php';
require_once __DIR__.'/../controllers/CajeroController.php';
$ok=0;$errores=0;
function verificar(string $nombre,bool $cond): void {global $ok,$errores;echo ($cond?'[OK] ':'[ERROR] ').$nombre.PHP_EOL;if($cond)$ok++;else $errores++;}
function rechazar(string $nombre,callable $fn): void {try{$fn();verificar($nombre,false);}catch(DomainException $e){verificar($nombre,true);}}
foreach(['0'=>'0.00','10'=>'10.00','10.5'=>'10.50','10.01'=>'10.01','9999999999.99'=>'9999999999.99'] as $v=>$esperado)verificar('Precisión exacta '.$v,CajeroService::decimal(CajeroService::centavos((string)$v))===$esperado);
foreach(['','-1','1.001','1e3','10,50','10000000000','NaN',' 10','+10'] as $v)rechazar('Monto mal formado '.$v,fn()=>CajeroService::centavos($v));
foreach([['RETIRO','10',1000],['RETIRO','300',30000],['DEPOSITO','10',1000],['DEPOSITO','1000',100000],['DEPOSITO','10.01',1001],['TRANSFERENCIA_ENVIADA','10',1000],['TRANSFERENCIA_ENVIADA','500',50000],['TRANSFERENCIA_ENVIADA','99.99',9999]] as [$t,$v,$c])verificar('Límite válido '.$t.' '.$v,CajeroService::monto($t,$v)===$c);
foreach([['RETIRO','9.99'],['RETIRO','15'],['RETIRO','301'],['RETIRO','10.01'],['DEPOSITO','9.99'],['DEPOSITO','1000.01'],['TRANSFERENCIA_ENVIADA','9.99'],['TRANSFERENCIA_ENVIADA','500.01'],['TRANSFERENCIA_RECIBIDA','10']] as [$t,$v])rechazar('Límite inválido '.$t.' '.$v,fn()=>CajeroService::monto($t,$v));
verificar('Ceros iniciales en cuenta',CajeroService::numero('0000000001')==='0000000001');
foreach(['123','220000000a','22000000000'] as $v)rechazar('Cuenta inválida '.$v,fn()=>CajeroService::numero($v));
verificar('QR normalizado',CajeroService::codigo(' tk-a1b2c3d40001 ')==='TK-A1B2C3D40001');
foreach(['2200000001','TK-ABC','TK-G123456789AB','<script>'] as $v)rechazar('QR inválido '.$v,fn()=>CajeroService::codigo($v));
$f=CajeroService::filtros(['cuenta'=>'2200000001','tipo'=>'RETIRO','desde'=>'2026-01-01','hasta'=>'2026-01-31','cliente'=>'otro']);
verificar('Filtros normalizados y exclusión de filtros de administrador',$f['cuenta']==='2200000001'&&$f['tipo']==='RETIRO'&&$f['cliente']==='');
foreach([['cuenta'=>'220'],['tipo'=>'OTRO'],['desde'=>'2026-02-30'],['desde'=>'2026-03-01','hasta'=>'2026-01-01'],['cuenta'=>[]]] as $v)rechazar('Filtro inválido '.json_encode($v),fn()=>CajeroService::filtros($v));
$_SESSION=[];$t=CajeroController::token('retirar');CajeroController::consumir('retirar',['operacion_token'=>$t]);verificar('Token de operación consumido',!isset($_SESSION['tokens_cajero']['retirar']));rechazar('Envío duplicado',fn()=>CajeroController::consumir('retirar',['operacion_token'=>$t]));$t=CajeroController::token('retirar');rechazar('Token incorrecto',fn()=>CajeroController::consumir('retirar',['operacion_token'=>'falso']));rechazar('Token de otra acción',fn()=>CajeroController::consumir('depositar',['operacion_token'=>$t]));
verificar('Historial general fija la cuenta validada',CajeroController::filtrosCuenta([], '2200000001')['cuenta']==='2200000001');
verificar('Historial filtrado conserva tipo',CajeroController::filtrosCuenta(['tipo'=>'RETIRO'], '2200000001')['tipo']==='RETIRO');
rechazar('Historial rechaza otra cuenta propia o ajena',fn()=>CajeroController::filtrosCuenta(['cuenta'=>'2200000006'],'2200000001'));
$_SESSION=['cuenta_cajero'=>['numero'=>'2200000001'],'transferencia_cajero'=>[1],'comprobante_cajero'=>[1],'tokens_cajero'=>[1],'usuario'=>['id'=>1]];
CajeroController::cerrarCuenta();verificar('Salir de cuenta conserva solo sesión del cliente',$_SESSION===['usuario'=>['id'=>1]]);
$a=['nombres'=>'Lucía Paredes','cedula'=>'1800000003','numero'=>'2200000003','tipo'=>'AHORROS','saldo'=>'125.50'];
$m=['id'=>1,'nombres'=>$a['nombres'],'cedula'=>$a['cedula'],'numero_cuenta'=>$a['numero'],'tipo'=>'TRANSFERENCIA_RECIBIDA','fecha'=>'2026-11-26 04:00:00','referencia'=>'OP-'.str_repeat('A',32),'monto'=>'10.50','saldo_resultante'=>'125.50','cuenta_relacionada'=>'2200000001'];
$pdf=new CajeroPDF();$pdf->saldo($a);verificar('PDF de saldo',str_starts_with($pdf->Output('S'),'%PDF-'));
foreach(MovimientoAdministrativo::TIPOS as $tipo){$pdf=new CajeroPDF();$pdf->comprobante(array_replace($m,['tipo'=>$tipo]));verificar('PDF comprobante '.$tipo,str_starts_with($pdf->Output('S'),'%PDF-'));}
$pdf=new CajeroPDF();$pdf->historial(['filtros'=>$f,'filas'=>[]],'lucia');verificar('PDF historial vacío',str_starts_with($pdf->Output('S'),'%PDF-'));
$pdf=new CajeroPDF();$pdf->historial(['filtros'=>$f,'filas'=>array_fill(0,45,$m)],'lucia');verificar('PDF historial multipágina',str_starts_with($pdf->Output('S'),'%PDF-')&&$pdf->PageNo()>1);
$pdf=new MovimientosPDF();$pdf->generar(['filtros'=>$f,'movimientos'=>array_fill(0,45,$m),'total'=>45],'admin');verificar('PDF administrativo multipágina',str_starts_with($pdf->Output('S'),'%PDF-')&&$pdf->PageNo()>1);
echo "Aprobadas: $ok; Fallidas: $errores\n";exit($errores?1:0);
