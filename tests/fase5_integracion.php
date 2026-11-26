<?php
// Ejecutar únicamente en una base de pruebas sin otros usuarios concurrentes.
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../services/CajeroService.php';
require_once __DIR__.'/../services/GestionCuentasService.php';
require_once __DIR__.'/../services/GestionUsuariosService.php';
require_once __DIR__.'/../controllers/CajeroController.php';
require_once __DIR__.'/../models/TarjetaAdministrativa.php';
$ok=0;$errores=0;$db=null;
function verificar(string $n,bool $c): void {global $ok,$errores;echo ($c?'[OK] ':'[ERROR] ').$n.PHP_EOL;if($c)$ok++;else $errores++;}
function rechazar(string $n,callable $f): void{try{$f();verificar($n,false);}catch(DomainException $e){verificar($n,true);}}
function estado(PDO $db): array{$r=[];foreach(['clientes'=>'id','usuarios'=>'id','cuentas'=>'numero','tarjetas'=>'codigo_qr','movimientos'=>'id'] as $t=>$k)$r[$t]=$db->query("SELECT * FROM $t ORDER BY $k")->fetchAll();return $r;}
function numeroLibre(GestionAdministrativa $m,string $t,string $k): string{do{$n='98'.str_pad((string)random_int(0,99999999),8,'0',STR_PAD_LEFT);}while($m->uno("SELECT $k FROM $t WHERE $k=?",[$n]));return $n;}
try{
    $db=obtenerConexion();$base=estado($db);$db->beginTransaction();$m=new Cajero($db);$s=new CajeroService($db);$gs=new GestionCuentasService($db);$us=new GestionUsuariosService($db);
    $ids=[];$uids=[];$nombres=[];
    for($i=0;$i<2;$i++){$nom='Prueba Cajero '.bin2hex(random_bytes(6));$m->ejecutar('INSERT INTO clientes(nombres,cedula) VALUES(?,?)',[$nom,numeroLibre($m,'clientes','cedula')]);$id=(int)$db->lastInsertId();$nombre='cajero_'.bin2hex(random_bytes(8));$us->registrar($id,$nombre,'0123','0123');$u=$m->uno('SELECT id FROM usuarios WHERE id_cliente=?',[$id]);$ids[]=$id;$uids[]=(int)$u['id'];$nombres[]=$nombre;}
    [$id,$otro]=$ids;[$uid,$uidOtro]=$uids;
    $a=numeroLibre($m,'cuentas','numero');$gs->registrar($id,$a,'AHORROS','2000');
    $b=numeroLibre($m,'cuentas','numero');$gs->registrar($otro,$b,'AHORROS','100');
    $c=numeroLibre($m,'cuentas','numero');$gs->registrar($id,$c,'AHORROS','0');
    $sel=$s->seleccion($id,$a,$m->qrActivo($a)['codigo_qr']);$selB=$s->seleccion($otro,$b,$m->qrActivo($b)['codigo_qr']);$selC=$s->seleccion($id,$c,$m->qrActivo($c)['codigo_qr']);
    verificar('Listado de cuentas propias sin saldo',count($m->cuentas($id))===2&&!array_key_exists('saldo',$m->cuentas($id)[0]));
    rechazar('Selección de cuenta ajena',fn()=>$s->seleccion($id,$b,$m->qrActivo($b)['codigo_qr']));
    rechazar('Selección con QR de otra cuenta',fn()=>$s->seleccion($id,$a,$selB['qr']));
    verificar('Validación de tarjeta y saldo',$s->verificar($id,$sel)['saldo']==='2000.00');
    verificar('Identificación de usuario por cuenta',$m->usuarioCuenta($a)['usuario']===$nombres[0]);
    verificar('Descarga de QR ajeno no autorizada',$m->tarjetaPropia($id,$selB['qr'])===null);
    verificar('Solo QR vigentes propios en Mis QR',count($m->tarjetas($id))===2 && $m->tarjetaPropia($id,$sel['qr'])!==null);
    $r=$s->operar($uid,$id,$sel,'RETIRO','300');verificar('Retiro y saldo', $m->cuenta($a)['saldo']==='1700.00'&&$m->detalle($id,$r['id'])['tipo']==='RETIRO');
    $s->operar($uid,$id,$sel,'RETIRO','300');$s->operar($uid,$id,$sel,'RETIRO','300');$s->operar($uid,$id,$sel,'RETIRO','100');
    rechazar('Límite diario de 1000 por cuenta',fn()=>$s->operar($uid,$id,$sel,'RETIRO','10'));
    verificar('Límite sin alterar saldo ni movimientos',$m->cuenta($a)['saldo']==='1000.00'&&(string)$m->retirosHoy($a)==='1000.00');
    rechazar('Retiro sin saldo',fn()=>$s->operar($uid,$id,$selC,'RETIRO','10'));
    $r=$s->operar($uid,$id,$sel,'DEPOSITO','10.25');verificar('Depósito con centavos',$m->cuenta($a)['saldo']==='1010.25'&&$m->detalle($id,$r['id'])['monto']==='10.25');
    $d=$s->destino($sel,$selB['qr']);verificar('Destino mediante QR',$d['numero']===$b&&$d['nombres']!=='');
    rechazar('Transferencia a sí misma',fn()=>$s->destino($sel,$sel['qr']));
    $r=$s->operar($uid,$id,$sel,'TRANSFERENCIA_ENVIADA','50.15',$d);$env=$m->detalle($id,$r['id']);$rec=$m->detalle($otro,$r['recibido']);
    verificar('Transferencia atómica y saldos exactos',$m->cuenta($a)['saldo']==='960.10'&&$m->cuenta($b)['saldo']==='150.15');
    verificar('Dos movimientos con referencia compartida',$env['referencia']===$rec['referencia']&&$env['cuenta_relacionada']===$b&&$rec['cuenta_relacionada']===$a&&$rec['tipo']==='TRANSFERENCIA_RECIBIDA');
    verificar('Movimiento ajeno inaccesible',$m->detalle($id,$r['recibido'])===null&&$m->detalle($otro,$r['id'])===null);
    $r2=$s->operar($uid,$id,$sel,'TRANSFERENCIA_ENVIADA','20',$s->destino($sel,$selC['qr']));verificar('Transferencia entre cuentas propias',$m->cuenta($c)['saldo']==='20.00'&&$m->detalle($id,$r2['recibido'])!==null);
    rechazar('Transferencia con saldo insuficiente',fn()=>$s->operar($uid,$id,$selC,'TRANSFERENCIA_ENVIADA','30',$s->destino($selC,$selB['qr'])));
    verificar('Cuenta de destino no alterada por rechazo',$m->cuenta($b)['saldo']==='150.15');
    $gs->cambiar($b,'desactivar');rechazar('QR destinatario inactivo',fn()=>$s->destino($sel,$selB['qr']));rechazar('Transferencia confirmada con destino desactivado',fn()=>$s->operar($uid,$id,$sel,'TRANSFERENCIA_ENVIADA','10',$d));$gs->cambiar($b,'activar');rechazar('QR anterior no reutilizable',fn()=>$s->operar($uid,$id,$sel,'TRANSFERENCIA_ENVIADA','10',$d));
    $gs->cambiar($a,'desactivar');verificar('QR desactivado no listado ni descargable',count($m->tarjetas($id))===1 && $m->tarjetaPropia($id,$sel['qr'])===null);rechazar('Selección invalidada al desactivar',fn()=>$s->verificar($id,$sel));rechazar('Operación con origen inactivo',fn()=>$s->operar($uid,$id,$sel,'DEPOSITO','10'));$gs->cambiar($a,'activar');rechazar('Selección anterior invalidada tras reactivar',fn()=>$s->operar($uid,$id,$sel,'DEPOSITO','10'));$selAnterior=$sel;$sel=$s->seleccion($id,$a,$m->qrActivo($a)['codigo_qr']);
    verificar('Nuevo QR propio visible y anterior no descargable',count($m->tarjetas($id))===2 && $selAnterior['qr']!==$sel['qr'] && $m->tarjetaPropia($id,$selAnterior['qr'])===null && $m->tarjetaPropia($id,$sel['qr'])!==null);
    $adminTarjetas=new TarjetaAdministrativa($db);$adminCuentas=new CuentaAdministrativa($db);$listaQr=$adminTarjetas->listar($a,1)['filas'];$listaCuenta=$adminCuentas->listar($a,1)['filas'];
    verificar('Admin: estado de cuenta activo coincide en listado y detalle',(int)$listaCuenta[0]['estado']===1 && (int)$adminCuentas->ver($a)['estado']===1);
    verificar('Admin: QR vigente primero e histórico con cuenta activa',count($listaQr)===2 && $listaQr[0]['codigo_qr']===$sel['qr'] && (int)$listaQr[0]['activa']===1 && (int)$listaQr[1]['activa']===0 && (int)$listaQr[1]['cuenta_activa']===1);
    rechazar('Usuario ajeno no puede operar',fn()=>$s->operar($uidOtro,$id,$sel,'DEPOSITO','10'));
    $us->cambiar($uid,'desactivar');rechazar('Usuario inactivo bloquea operación',fn()=>$s->operar($uid,$id,$sel,'DEPOSITO','10'));$us->cambiar($uid,'activar');
    for($i=0;$i<22;$i++)$s->operar($uid,$id,$sel,'DEPOSITO','10');
    $f=CajeroService::filtros(['cuenta'=>$a,'tipo'=>'DEPOSITO','desde'=>date('Y-m-d'),'hasta'=>date('Y-m-d')]);$p1=$m->historial($id,$f,1);$p2=$m->historial($id,$f,2);
    verificar('Paginación de 20 y 3 movimientos',$p1['total']===23&&count($p1['filas'])===20&&count($p2['filas'])===3&&!array_intersect(array_column($p1['filas'],'id'),array_column($p2['filas'],'id')));
    verificar('PDF sin paginación SQL',count($m->historial($id,$f,null)['filas'])===23);
    verificar('Aislamiento de historial ajeno',$m->historial($otro,$f,null)['total']===0);
    $gs->cambiar($a,'desactivar');verificar('Historial conserva cuentas inactivas',$m->historial($id,$f,null)['total']===23);
    verificar('Transferencias recibidas recientes propias',count($m->recibidosDesde($otro,date('Y-m-d H:i:s',time()-300)))===1);
    $ctrl=new CajeroController($db,['id'=>$uid,'id_cliente'=>$id,'usuario'=>$nombres[0],'rol'=>'CLIENTE']);$_SESSION=[];
    rechazar('Sin comprobantes históricos',fn()=>$ctrl->comprobante($r2['id']));
    rechazar('Historial sin selección',fn()=>$ctrl->historial([],null));
    $gs->cambiar($a,'activar');$_SESSION['cuenta_cajero']=$s->seleccion($id,$a,$m->qrActivo($a)['codigo_qr']);
    verificar('Historial limitado a la cuenta seleccionada',$ctrl->historial([],null)['filtros']['cuenta']===$a && !array_diff(array_column($ctrl->historial([],null)['filas'],'numero_cuenta'),[$a]));
    rechazar('Historial de otra cuenta propia rechazado',fn()=>$ctrl->historial(['cuenta'=>$c],null));
    rechazar('Historial de cuenta ajena rechazado',fn()=>$ctrl->historial(['cuenta'=>$b],null));
    $_SESSION['comprobante_cajero']=['ids'=>[$r2['id']],'vence'=>time()+300];verificar('Comprobante reciente propio',$ctrl->comprobante($r2['id'])['id']===$r2['id']);
    rechazar('ID de comprobante manipulado',fn()=>$ctrl->comprobante($r['recibido']));
    CajeroController::cerrarCuenta();rechazar('PDF e historial requieren volver a validar',fn()=>$ctrl->historial([],null));rechazar('Comprobante invalidado al salir',fn()=>$ctrl->comprobante($r2['id']));
    $db->rollBack();verificar('Conservación exacta de registros originales',$base===estado($db));
}catch(Throwable $e){$errores++;echo '[ERROR] '.$e->getMessage().PHP_EOL;}
finally{if($db instanceof PDO&&$db->inTransaction())$db->rollBack();}
echo "Aprobadas: $ok; Fallidas: $errores\n";exit($errores?1:0);
