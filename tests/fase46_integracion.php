<?php
// Suite transaccional: no deja usuarios, cuentas, tarjetas ni movimientos de prueba.
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Acceso no permitido.');}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../controllers/ModuloAdministrativoController.php';
$ok=0;$error=0;$db=null;
function verificar(string $nombre,bool $condicion): void {global $ok,$error;echo ($condicion?'[OK] ':'[ERROR] ').$nombre.PHP_EOL;if($condicion)$ok++;else $error++;}
function rechazar(string $nombre,callable $fn): void {try{$fn();verificar($nombre,false);}catch(DomainException $e){verificar($nombre,true);}}
function estadoBD(PDO $db): array { $r=[];foreach(['clientes'=>'id','usuarios'=>'id','cuentas'=>'numero','tarjetas'=>'codigo_qr','movimientos'=>'id'] as $t=>$orden)$r[$t]=$db->query("SELECT * FROM $t ORDER BY $orden")->fetchAll();return $r; }
function numeroLibre(GestionAdministrativa $m,string $tabla,string $campo): string {do{$n='99'.str_pad((string)random_int(0,99999999),8,'0',STR_PAD_LEFT);}while($m->uno("SELECT $campo FROM $tabla WHERE $campo=?",[$n]));return $n;}
try{
    $db=obtenerConexion();$base=estadoBD($db);$db->beginTransaction();
    $m=new GestionAdministrativa($db);$u=new UsuarioAdministrativo($db);$a=new CuentaAdministrativa($db);$t=new TarjetaAdministrativa($db);$mov=new MovimientoAdministrativo($db);
    $us=new GestionUsuariosService($db);$cs=new GestionCuentasService($db);$controller=new ModuloAdministrativoController($db);
    $nombre='Prueba Fase '.bin2hex(random_bytes(6));$cedula=numeroLibre($m,'clientes','cedula');
    $m->ejecutar('INSERT INTO clientes(nombres,cedula) VALUES(?,?)',[$nombre,$cedula]);$id=(int)$db->lastInsertId();
    $usuario='fase46_'.bin2hex(random_bytes(8));$num=numeroLibre($m,'cuentas','numero');
    rechazar('Cuenta sin credenciales',fn()=>$cs->registrar($id,$num,'AHORROS','0'));
    rechazar('PIN diferente',fn()=>$us->registrar($id,$usuario,'1234','1235'));
    rechazar('PIN no numérico',fn()=>$us->registrar($id,$usuario,'abcd','abcd'));
    $controller->procesar('usuarios',['accion'=>'crear','cliente'=>(string)$id,'usuario'=>$usuario,'pin'=>'0123','confirmacion'=>'0123']);
    $row=$m->uno('SELECT * FROM usuarios WHERE id_cliente=?',[$id]);$uid=(int)$row['id'];
    verificar('Usuario personalizado y activo',$row['usuario']===$usuario&&$row['rol']==='CLIENTE'&&(bool)$row['estado']);
    verificar('PIN inicial guardado como hash',password_verify('0123',$row['clave_hash']));
    verificar('Listado y detalle no exponen hash',!array_key_exists('clave_hash',$u->ver($uid))&&!array_key_exists('clave_hash',$u->listar($usuario,1)['filas'][0]));
    rechazar('Cliente con credenciales duplicadas',fn()=>$us->registrar($id,$usuario.'b','1234','1234'));
    $m->ejecutar('INSERT INTO clientes(nombres,cedula) VALUES(?,?)',[$nombre.' B',numeroLibre($m,'clientes','cedula')]);$id2=(int)$db->lastInsertId();
    rechazar('Nombre de usuario duplicado',fn()=>$us->registrar($id2,$usuario,'1234','1234'));
    $us->cambiar($uid,'desactivar');verificar('Desactivación de usuario',!(bool)$u->ver($uid)['estado']);
    rechazar('Cuenta con usuario inactivo',fn()=>$cs->registrar($id,$num,'AHORROS','0'));
    $us->cambiar($uid,'activar');verificar('Reactivación de usuario',(bool)$u->ver($uid)['estado']);
    $m->ejecutar('UPDATE usuarios SET bloqueado=1,intentos_fallidos=3 WHERE id=?',[$uid]);$us->cambiar($uid,'desbloquear');
    verificar('Desbloqueo y contador en cero',!(bool)$u->ver($uid)['bloqueado']&&(int)$u->ver($uid)['intentos_fallidos']===0);
    $m->ejecutar('UPDATE usuarios SET bloqueado=1,intentos_fallidos=3 WHERE id=?',[$uid]);$us->cambiar($uid,'pin','4567','4567');$row=$m->uno('SELECT * FROM usuarios WHERE id=?',[$uid]);
    verificar('Restablecimiento de PIN y desbloqueo',password_verify('4567',$row['clave_hash'])&&!password_verify('0123',$row['clave_hash'])&&!(bool)$row['bloqueado']&&(int)$row['intentos_fallidos']===0);
    $admin=$m->uno("SELECT id FROM usuarios WHERE rol='ADMIN' LIMIT 1");if($admin)rechazar('Administrador protegido',fn()=>$us->cambiar((int)$admin['id'],'desactivar'));
    rechazar('No existe eliminación de usuario',fn()=>$us->cambiar($uid,'eliminar'));
    rechazar('Número inválido',fn()=>$cs->registrar($id,'123','AHORROS','0'));
    rechazar('Saldo negativo',fn()=>$cs->registrar($id,$num,'AHORROS','-1'));
    $controller->procesar('cuentas',['accion'=>'crear','cliente'=>(string)$id,'numero'=>$num,'tipo'=>'AHORROS','saldo'=>'125.50']);
    verificar('Número manual y saldo inicial',(string)$a->ver($num)['saldo']==='125.50');
    $qr=$m->uno('SELECT * FROM tarjetas WHERE numero_cuenta=? AND activa=1',[$num]);
    verificar('QR automático y consultable',$qr!==null&&$t->ver($qr['codigo_qr'])['numero_cuenta']===$num);
    verificar('PNG generado a partir del QR',substr(QrPng::png($qr['codigo_qr']),0,8)==="\x89PNG\r\n\x1a\n");
    rechazar('Cuenta duplicada',fn()=>$cs->registrar($id,$num,'AHORROS','0'));
    rechazar('Cuenta con saldo no se elimina',fn()=>$cs->cambiar($num,'eliminar'));
    $cs->cambiar($num,'desactivar');verificar('Cuenta desactivada y saldo conservado',!(bool)$a->ver($num)['estado']&&(string)$a->ver($num)['saldo']==='125.50');
    verificar('QR desactivado y fecha de anulación',!(bool)$t->ver($qr['codigo_qr'])['activa']&&$t->ver($qr['codigo_qr'])['fecha_anulacion']!==null);
    $cs->cambiar($num,'activar');$nuevo=$m->uno('SELECT * FROM tarjetas WHERE numero_cuenta=? AND activa=1',[$num]);
    verificar('Nuevo QR tras reactivar',$nuevo['codigo_qr']!==$qr['codigo_qr']&&!(bool)$t->ver($qr['codigo_qr'])['activa']);
    $cs->cambiar($num,'activar');verificar('Reactivación repetida no duplica QR',(int)$m->ejecutar('SELECT COUNT(*) FROM tarjetas WHERE numero_cuenta=?',[$num])->fetchColumn()===2);
    $num2=numeroLibre($m,'cuentas','numero');$cs->registrar($id,$num2,'AHORROS','0');
    verificar('Varias cuentas del mismo tipo',$a->listar($cedula,1)['total']===2);
    $num3=numeroLibre($m,'cuentas','numero');$cs->registrar($id,$num3,'CORRIENTE','0');$cs->cambiar($num3,'desactivar');$cs->cambiar($num3,'activar');$cs->cambiar($num3,'eliminar');
    verificar('Eliminación de cuenta sin dependencias y todos sus QR',$a->ver($num3)===null&&(int)$m->ejecutar('SELECT COUNT(*) FROM tarjetas WHERE numero_cuenta=?',[$num3])->fetchColumn()===0);
    // Movimientos de prueba exclusivamente no confirmados; nunca son datos demo visibles.
    $ref='TEST-'.bin2hex(random_bytes(8));
    $m->ejecutar("INSERT INTO movimientos(numero_cuenta,tipo,monto,fecha,cuenta_relacionada,saldo_resultante,referencia) VALUES(?,'TRANSFERENCIA_ENVIADA',1,'2026-01-15 12:00:00',?,124.50,?)",[$num,$num2,$ref]);
    rechazar('Cuenta relacionada con movimiento no se elimina',fn()=>$cs->cambiar($num2,'eliminar'));
    $m->ejecutar("INSERT INTO movimientos(numero_cuenta,tipo,monto,fecha,saldo_resultante,referencia) VALUES(?,'RETIRO',1,'2026-01-16 12:00:00',0,?)",[$num2,$ref]);
    $num4=numeroLibre($m,'cuentas','numero');$cs->registrar($id,$num4,'AHORROS','0');
    $m->ejecutar("INSERT INTO movimientos(numero_cuenta,tipo,monto,fecha,saldo_resultante,referencia) VALUES(?,'RETIRO',1,'2026-01-16 12:00:00',0,?)",[$num4,$ref]);
    rechazar('Cuenta con movimiento propio no se elimina',fn()=>$cs->cambiar($num4,'eliminar'));
    $m->ejecutar('DELETE FROM movimientos WHERE numero_cuenta=?',[$num4]);$cs->cambiar($num4,'eliminar');
    $f=ConsultaMovimientosService::filtros(['cliente'=>$nombre,'cedula'=>$cedula,'desde'=>'2026-01-15','hasta'=>'2026-01-16']);$r=$mov->listar($f,1);
    verificar('Filtros combinados y orden descendente',$r['total']===2&&$r['filas'][0]['tipo']==='RETIRO');
    verificar('Detalle de movimiento',$mov->ver((int)$r['filas'][0]['id'])['referencia']===$ref);
    $f['cuenta']=$num;$f['tipo']='TRANSFERENCIA_ENVIADA';verificar('Filtro por cuenta y tipo',$mov->listar($f,1)['total']===1);
    $f['desde']='2026-01-01';$f['hasta']='2026-01-14';verificar('Filtro sin resultados',$mov->listar($f,1)['total']===0);
    for($i=0;$i<11;$i++)$m->ejecutar("INSERT INTO movimientos(numero_cuenta,tipo,monto,fecha,saldo_resultante,referencia) VALUES(?,'DEPOSITO',1,'2026-02-01 12:00:00',1,?)",[$num,$ref]);
    $fp=ConsultaMovimientosService::filtros(['cuenta'=>$num,'tipo'=>'DEPOSITO','desde'=>'2026-02-01','hasta'=>'2026-02-01']);
    $mp1=$mov->listar($fp,1);$mp2=$mov->listar($fp,2);
    verificar('Paginación de movimientos diez y uno',$mp1['total']===11&&count($mp1['filas'])===10&&count($mp2['filas'])===1);
    verificar('Orden estable sin duplicados con fechas iguales',!array_intersect(array_column($mp1['filas'],'id'),array_column($mp2['filas'],'id'))&&(int)$mp1['filas'][9]['id']>(int)$mp2['filas'][0]['id']);
    rechazar('Movimientos solo lectura',fn()=>$controller->procesar('movimientos',['accion'=>'crear']));
    rechazar('Tarjetas solo lectura',fn()=>$controller->procesar('tarjetas',['accion'=>'eliminar']));
    verificar('Búsqueda literal de comodines',$a->listar($cedula.'%',1)['total']===0);
    for($i=0;$i<11;$i++)$cs->registrar($id,numeroLibre($m,'cuentas','numero'),'AHORROS','0');
    $p1=$a->listar($cedula,1);$p2=$a->listar($cedula,2);
    verificar('Paginación de cuentas 10 y 3',$p1['total']===13&&count($p1['filas'])===10&&count($p2['filas'])===3&&$p2['paginas']===2);
    verificar('Sin duplicados entre páginas',!array_intersect(array_column($p1['filas'],'numero'),array_column($p2['filas'],'numero')));
    verificar('Página fuera de rango corregida',$a->listar($cedula,999)['pagina']===2);
    $db->rollBack();verificar('Conservación exacta de registros originales',$base===estadoBD($db));
}catch(Throwable $e){$error++;echo '[ERROR] '.$e->getMessage().PHP_EOL;}
finally{if($db instanceof PDO&&$db->inTransaction())$db->rollBack();}
echo "Aprobadas: $ok; Fallidas: $error\n";exit($error?1:0);
