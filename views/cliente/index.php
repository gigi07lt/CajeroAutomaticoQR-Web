<?php
require_once __DIR__.'/../../middleware/roles.php';
require_once __DIR__.'/../../controllers/CajeroController.php';
require_once __DIR__.'/../../models/Cliente.php';
$usuario=exigirRol('CLIENTE');$c=new CajeroController(obtenerConexion(),$usuario);
function h($v): string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function enlace(array $p=[]): string{return urlAplicacion('views/cliente/index.php').($p?'?'.http_build_query($p):'');}
function formToken(string $accion): void{echo '<input type="hidden" name="csrf_token" value="'.h(CsrfService::generarToken()).'"><input type="hidden" name="accion" value="'.h($accion).'"><input type="hidden" name="operacion_token" value="'.h(CajeroController::token($accion)).'">';}
function dinero($v): string{return '$'.number_format((float)$v,2,'.',',');}
function tipo($t): string{return str_replace('_',' ',$t);}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $accion=is_string($_POST['accion']??null)?$_POST['accion']:'';
    try{$vista=$c->procesar($_POST);}
    catch(DomainException $e){$_SESSION['error_cajero']=$e->getMessage();$vista=['seleccionar'=>'validar','retirar'=>'retiro','depositar'=>'deposito','destino'=>'transferencia','transferir'=>'transferencia'][$accion]??'inicio';}
    catch(Throwable $e){error_log($e->getMessage());$_SESSION['error_cajero']='No se pudo completar la solicitud. Consulta el historial antes de repetir una operación.';$vista='inicio';}
    $p=['vista'=>$vista];if($vista==='validar' && is_string($_POST['numero']??null))$p['numero']=$_POST['numero'];
    header('Location: '.enlace($p),true,303);exit;
}
if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);header('Allow: GET, POST');exit;}
$vista=is_string($_GET['vista']??null)?$_GET['vista']:'inicio';
$permitidas=['inicio','cuentas','validar','panel','informacion','retiro','deposito','transferencia','confirmar','movimientos','tarjetas','comprobante'];
if(!in_array($vista,$permitidas,true)){http_response_code(404);$vista='inicio';}
if(in_array($vista,['inicio','cuentas','tarjetas','validar'],true))CajeroController::cerrarCuenta();
if($vista!=='comprobante')unset($_SESSION['comprobante_cajero']);
$cuenta=$c->seleccion();$error=$_SESSION['error_cajero']??'';unset($_SESSION['error_cajero']);
if(in_array($vista,['panel','informacion','retiro','deposito','transferencia','confirmar','movimientos','comprobante'],true)&&!$cuenta){$vista='cuentas';$error='Selecciona y valida una cuenta activa.';}
$titulos=['inicio'=>'Panel del cliente','cuentas'=>'Mis cuentas','validar'=>'Validar cuenta','panel'=>'Panel de la cuenta','informacion'=>'Información de la cuenta','retiro'=>'Retirar dinero','deposito'=>'Depositar dinero','transferencia'=>'Transferencias por QR','confirmar'=>'Confirmar transferencia','movimientos'=>'Mis movimientos','tarjetas'=>'Mis QR','comprobante'=>'Comprobante de operación'];
$cuentas=$c->modelo()->cuentas($c->cliente());
$numeroElegido=null;
if($vista==='validar'){foreach($cuentas as $a){if($a['estado']&&$a['numero']===($_GET['numero']??null))$numeroElegido=$a['numero'];}if($numeroElegido===null){$vista='cuentas';$error='Cuenta no disponible.';}}
$titular=(new Cliente(obtenerConexion()))->buscarPorId($c->cliente());
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($titulos[$vista])?> | Banco FISEI</title><link rel="stylesheet" href="<?=h(urlAplicacion('assets/css/style.css'))?>"><link rel="stylesheet" href="<?=h(urlAplicacion('assets/css/admin.css'))?>"><link rel="stylesheet" href="<?=h(urlAplicacion('assets/css/crud.css'))?>"><link rel="stylesheet" href="<?=h(urlAplicacion('assets/css/cajero.css'))?>"></head>
<body><header class="panel-header"><div class="marca"><div class="marca-icono">F</div><div><strong>BANCO FISEI</strong><small>Cajero automático</small></div></div><div class="cajero-acciones"><a class="btn btn-secundario" href="<?=h(enlace($cuenta?['vista'=>'panel']:[]))?>"><?=$cuenta?'Volver al menú de la cuenta':'Volver al inicio'?></a><form method="post" action="<?=h(urlAplicacion('logout.php'))?>"><input type="hidden" name="csrf_token" value="<?=h(CsrfService::generarToken())?>"><button class="btn btn-secundario">Cerrar sesión</button></form></div></header>
<main class="panel-contenedor cajero"><h1><?=h($titulos[$vista])?></h1><p class="texto-secundario"><?=h($titular['nombres']??$usuario['usuario'])?></p>
<?php if($error!==''):?><p class="alerta alerta-error" role="alert"><?=h($error)?></p><?php endif;?>
<?php if($cuenta):?><section class="cajero-saldo"><div><small>Cuenta <?=h($cuenta['tipo'])?> · <?=h($cuenta['numero'])?></small><h2><?=h(dinero($cuenta['saldo']))?></h2><small>Saldo disponible</small></div><form method="post" action="<?=h(enlace())?>"><?php formToken('cambiar');?><button class="btn btn-secundario">Volver a mis cuentas</button></form></section><?php endif;?>
<?php if($vista==='inicio'):?>
<div class="panel-grid"><?php foreach(['cuentas'=>['Mis cuentas','Consulta y accede a tus cuentas bancarias',count($cuentas)],'tarjetas'=>['Mis QR','Consulta y descarga tus tarjetas QR',count($c->modelo()->tarjetas($c->cliente()))]] as $v=>$d):?><article class="panel-tarjeta"><h3><?=h($d[0])?></h3><h2><?=h($d[2])?></h2><a class="btn btn-primario" href="<?=h(enlace(['vista'=>$v]))?>">Acceder</a></article><?php endforeach;?></div>
<?php elseif($vista==='cuentas'):?>
<section class="crud-contenedor"><h2>Cuentas registradas</h2><div class="crud-tabla-responsive"><table class="crud-tabla"><thead><tr><th>Cuenta</th><th>Tipo</th><th>Estado</th><th>Acceso</th></tr></thead><tbody><?php foreach($cuentas as $a):?><tr><td><?=h($a['numero'])?></td><td><?=h($a['tipo'])?></td><td><?=$a['estado']?'Activa':'Inactiva'?></td><td><?php if($a['estado']):?><a class="btn btn-primario" href="<?=h(enlace(['vista'=>'validar','numero'=>$a['numero']]))?>">Ingresar a la cuenta</a><?php else:?>No disponible<?php endif;?></td></tr><?php endforeach;?><?php if(!$cuentas):?><tr><td colspan="4">No tienes cuentas registradas.</td></tr><?php endif;?></tbody></table></div></section>
<?php elseif($vista==='validar'):?>
<section class="panel-tarjeta cajero-form">
<form method="post" action="<?=h(enlace())?>" data-form-qr><?php formToken('seleccionar');?>
<div class="grupo-campo"><label for="numero">Número de cuenta</label><input id="numero" name="numero" value="<?=h($numeroElegido)?>" readonly required pattern="[0-9]{10}"></div>
<?php $lectorId='cuenta'; require __DIR__.'/../components/lector-qr.php';?>
<button class="btn btn-primario" data-qr-enviar>Validar cuenta</button></form>
<p><a class="btn btn-secundario" href="<?=h(enlace(['vista'=>'cuentas']))?>">Volver a mis cuentas</a></p></section>
<?php elseif($vista==='panel'):?>
<?php foreach($c->recibidosRecientes() as $r):?><div class="alerta alerta-info">Transferencia recibida recientemente en <?=h($r['numero_cuenta'])?>: <?=h(dinero($r['monto']))?>. <a target="_blank" rel="noopener" href="<?=h(urlAplicacion('views/cliente/pdf.php?tipo=comprobante&id='.$r['id']))?>">Comprobante PDF</a></div><?php endforeach;?>
<div class="panel-grid"><?php foreach(['informacion'=>['Información de la cuenta','Datos, saldo y PDF'],'retiro'=>['Retiro','Retirar dinero simulado'],'deposito'=>['Depósito','Depositar en esta cuenta'],'transferencia'=>['Transferencia','Transferir mediante QR'],'movimientos'=>['Movimientos','Historial de esta cuenta y reportes PDF']] as $v=>$d):?><article class="panel-tarjeta"><h3><?=h($d[0])?></h3><a class="btn btn-primario" href="<?=h(enlace(['vista'=>$v]))?>">Acceder</a></article><?php endforeach;?></div>
<?php elseif($vista==='informacion'):?>
<section class="panel-tarjeta"><h2>Información de la cuenta</h2><dl class="cajero-datos"><dt>Titular</dt><dd><?=h($cuenta['nombres'])?></dd><dt>Cédula</dt><dd><?=h($cuenta['cedula'])?></dd><dt>Número</dt><dd><?=h($cuenta['numero'])?></dd><dt>Tipo</dt><dd><?=h($cuenta['tipo'])?></dd><dt>Estado</dt><dd>Activa</dd><dt>Saldo actual</dt><dd><?=h(dinero($cuenta['saldo']))?></dd></dl><a class="btn btn-reporte" target="_blank" rel="noopener" href="<?=h(urlAplicacion('views/cliente/pdf.php?tipo=saldo'))?>">PDF de consulta de saldo</a></section>
<?php elseif($vista==='retiro'||$vista==='deposito'):?>
<section class="panel-tarjeta cajero-form"><h2><?=h($titulos[$vista])?></h2><p><?=$vista==='retiro'?'Entre $10 y $300, múltiplos de $10. Límite diario de $1000 por cuenta.':'De $10 a $1000.'?></p>
<form method="post" action="<?=h(enlace())?>"><?php formToken($vista==='retiro'?'retirar':'depositar');?><div class="grupo-campo"><label for="monto">Monto en dólares</label><input id="monto" name="monto" type="number" min="10" max="<?=$vista==='retiro'?'300':'1000'?>" step="<?=$vista==='retiro'?'10':'0.01'?>" required></div><?php if($vista==='retiro'):?><div class="cajero-acciones"><?php foreach([10,20,50,100,200,300] as $m):?><button type="button" class="btn btn-secundario monto-rapido" data-monto="<?=$m?>">$<?=$m?></button><?php endforeach;?></div><?php endif;?><button class="btn btn-primario"><?=$vista==='retiro'?'Realizar retiro':'Registrar depósito'?></button></form></section>
<?php elseif($vista==='transferencia'):?>
<section class="panel-tarjeta cajero-form"><h2>Identificar destinatario</h2><p>De $10 a $500, sin comisión.</p>
<form method="post" action="<?=h(enlace())?>" data-form-qr><?php formToken('destino');?>
<?php $lectorId='destino'; require __DIR__.'/../components/lector-qr.php';?>
<div class="grupo-campo"><label for="monto">Monto</label><input id="monto" name="monto" type="number" min="10" max="500" step="0.01" required></div><button class="btn btn-primario" data-qr-enviar>Verificar destinatario</button></form></section>
<?php elseif($vista==='confirmar'):?>
<?php $b=$_SESSION['transferencia_cajero']??null;if($b&&$b['vence']>=time()&&$b['origen']===$_SESSION['cuenta_cajero']):?><section class="panel-tarjeta cajero-form"><h2>Revisa la transferencia</h2><dl class="cajero-datos"><dt>Origen</dt><dd><?=h($cuenta['numero'])?></dd><dt>Destinatario</dt><dd><?=h($b['destino']['nombres'])?></dd><dt>Cuenta destino</dt><dd><?=h($b['destino']['numero'])?></dd><dt>Monto</dt><dd><?=h(dinero($b['monto']))?></dd><dt>Comisión</dt><dd>$0.00</dd></dl><form method="post" action="<?=h(enlace())?>"><?php formToken('transferir');?><button class="btn btn-primario">Confirmar transferencia</button> </form><form method="post" action="<?=h(enlace())?>"><?php formToken('cancelar_transferencia');?><button class="btn btn-secundario">Cancelar</button></form></section><?php else:?><p class="alerta alerta-info">La confirmación expiró. <a href="<?=h(enlace(['vista'=>'transferencia']))?>">Volver a transferencias</a></p><?php endif;?>
<?php elseif($vista==='movimientos'):?>
<?php require __DIR__.'/movimientos.php';?>
<?php elseif($vista==='tarjetas'):?>
<section class="crud-contenedor"><h2>Tarjetas vigentes de mis cuentas</h2><?php $tarjetasVigentes=$c->modelo()->tarjetas($c->cliente()); if(!$tarjetasVigentes):?><p class="alerta alerta-info">No tienes tarjetas QR vigentes.</p><?php endif;?><div class="panel-grid"><?php foreach($tarjetasVigentes as $t):$url=urlAplicacion('views/cliente/qr.php?'.http_build_query(['codigo'=>$t['codigo_qr']]));?><article class="panel-tarjeta cajero-qr"><h3><?=h($t['numero_cuenta'])?></h3><p>Activa</p><img src="<?=h($url)?>" alt="Tarjeta QR de la cuenta <?=h($t['numero_cuenta'])?>" width="160" height="160"><p><?=h($t['codigo_qr'])?></p><p class="texto-secundario">Emisión: <?=h($t['fecha_emision'])?></p><a class="btn btn-secundario" href="<?=h($url.'&descargar=1')?>">Descargar PNG</a></article><?php endforeach;?></div></section>
<?php elseif($vista==='comprobante'):?>
<section class="panel-tarjeta"><h2>Resultado de operación</h2><?php $recibos=$c->comprobantes();foreach($recibos as $m):?><dl class="cajero-datos"><dt>Tipo</dt><dd><?=h(tipo($m['tipo']))?></dd><dt>Cuenta</dt><dd><?=h($m['numero_cuenta'])?></dd><dt>Monto</dt><dd><?=h(dinero($m['monto']))?></dd><dt>Saldo resultante</dt><dd><?=h(dinero($m['saldo_resultante']))?></dd><dt>Referencia</dt><dd><?=h($m['referencia'])?></dd><dt>Fecha</dt><dd><?=h($m['fecha'])?></dd></dl><a class="btn btn-reporte" target="_blank" rel="noopener" href="<?=h(urlAplicacion('views/cliente/pdf.php?tipo=comprobante&id='.$m['id']))?>">Descargar comprobante PDF</a><?php endforeach;?><?php if(!$recibos):?><p>No hay comprobante reciente disponible.</p><?php endif;?><p><a class="btn btn-secundario" href="<?=h(enlace(['vista'=>'panel']))?>">Volver al menú</a></p></section>
<?php endif;?></main><footer class="cajero-footer">Banco FISEI · Simulación académica</footer><script src="<?=h(urlAplicacion('assets/js/cajero.js'))?>"></script><?php if(in_array($vista,['validar','transferencia'],true)):?>
<?php if(is_file(__DIR__.'/../../assets/js/vendor/html5-qrcode.min.js')):?><script src="<?=h(urlAplicacion('assets/js/vendor/html5-qrcode.min.js'))?>"></script><?php endif;?>
<script src="<?=h(urlAplicacion('assets/js/lector-qr.js'))?>"></script><?php endif;?></body></html>
