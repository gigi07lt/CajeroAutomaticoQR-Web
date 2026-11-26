<?php
require_once __DIR__.'/../../../controllers/ModuloAdministrativoController.php';
$usuario=exigirRol('ADMIN');
if(!isset($modulo)||!in_array($modulo,['usuarios','cuentas','tarjetas','movimientos'],true)){http_response_code(404);exit;}
$paginaActual=$modulo;
$titulos=['usuarios'=>'Gestión de usuarios','cuentas'=>'Gestión de cuentas bancarias','tarjetas'=>'Consulta de tarjetas QR','movimientos'=>'Consulta de movimientos'];
$tituloPagina=$titulos[$modulo];$ruta=urlAplicacion('views/admin/'.$modulo.'/index.php');
function h($v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function enlace(array $p): string {global $ruta;return h($ruta.'?'.http_build_query($p));}
function token(): string {return '<input type="hidden" name="csrf_token" value="'.h(CsrfService::generarToken()).'">';}
function estado($activo,string $si='Activo',string $no='Inactivo'): string {return '<span class="crud-estado '.($activo?'estado-activo':'estado-inactivo').'">'.h($activo?$si:$no).'</span>';}
function bloqueo($v): string {return '<span class="crud-estado '.($v?'estado-bloqueado':'estado-activo').'">'.($v?'Bloqueado':'No bloqueado').'</span>';}
function dinero($v): string {return '$'.number_format((float)$v,2,'.',',');}
function accion(string $accion,string $etiqueta,array $campos): void {global $ruta; ?>
<form method="post" action="<?=h($ruta)?>" class="crud-formulario-eliminar" onsubmit="return confirm('¿Confirmas esta operación?')">
<?=token()?><input type="hidden" name="accion" value="<?=h($accion)?>">
<?php foreach($campos as $k=>$v):?><input type="hidden" name="<?=h($k)?>" value="<?=h($v)?>"><?php endforeach;?>
<button class="crud-accion <?=$accion==='eliminar'||$accion==='desactivar'?'accion-eliminar':'accion-editar'?>" type="submit"><?=h($etiqueta)?></button></form><?php }
$metodo=$_SERVER['REQUEST_METHOD']??'GET';
if(!in_array($metodo,['GET','POST'],true)||($metodo==='POST'&&in_array($modulo,['tarjetas','movimientos'],true))){header('Allow: '.(in_array($modulo,['tarjetas','movimientos'],true)?'GET':'GET, POST'));http_response_code(405);exit('Método no permitido.');}
if($metodo==='POST')CsrfService::exigirToken();
$error='';$flash=$_SESSION['flash_modulo_'.$modulo]??null;unset($_SESSION['flash_modulo_'.$modulo]);
$vista='lista';$filtros=[];$datos=null;$detalle=null;$disponibles=[];
try{
    $control=new ModuloAdministrativoController(obtenerConexion());$modelo=$control->modelo($modulo);
    if($metodo==='POST'){
        try{$control->procesarHttp($modulo);$_SESSION['flash_modulo_'.$modulo]=['ok'=>true,'texto'=>'Operación realizada correctamente.'];}
        catch(DomainException $e){$_SESSION['flash_modulo_'.$modulo]=['ok'=>false,'texto'=>$e->getMessage()];}
        catch(Throwable $e){error_log($e->getMessage());$_SESSION['flash_modulo_'.$modulo]=['ok'=>false,'texto'=>'No se pudo completar la operación.'];}
        $destino=$ruta;if(!$_SESSION['flash_modulo_'.$modulo]['ok']&&($_POST['accion']??'')==='crear')$destino.='?vista=crear';
        header('Location: '.$destino,true,303);exit;
    }
    $vista=ModuloAdministrativoController::texto($_GET,'vista')?:'lista';
    if(!in_array($vista,['lista','crear','ver','qr','descargar'],true))throw new DomainException('Vista inválida.');
    if($vista==='crear'){
        if(!in_array($modulo,['usuarios','cuentas'],true))throw new DomainException('Módulo de solo consulta.');$disponibles=$modelo->disponibles();
    }elseif(in_array($vista,['ver','qr','descargar'],true)){
        if(in_array($vista,['qr','descargar'],true)&&$modulo!=='tarjetas')throw new DomainException('Vista inválida.');
        $id=in_array($modulo,['usuarios','movimientos'],true)?ModuloAdministrativoController::id($_GET['id']??null):ModuloAdministrativoController::texto($_GET,'id');
        $detalle=$modelo->ver($id);if(!$detalle){http_response_code(404);throw new DomainException('Registro no encontrado.');}
        if($vista==='qr'||$vista==='descargar'){
            $png=QrPng::png($detalle['codigo_qr']);header('Content-Type: image/png');header('X-Content-Type-Options: nosniff');
            if($vista==='descargar')header('Content-Disposition: attachment; filename="QR-'.preg_replace('/[^A-Z0-9-]/','',$detalle['codigo_qr']).'.png"');
            echo $png;exit;
        }
    }else{
        $pagina=ModuloAdministrativoController::pagina($_GET['pagina']??1);
        if($modulo==='movimientos'){$filtros=ConsultaMovimientosService::filtros($_GET);$datos=$modelo->listar($filtros,$pagina);}
        else{$filtros=['buscar'=>substr(ModuloAdministrativoController::texto($_GET,'buscar'),0,400)];$datos=$modelo->listar($filtros['buscar'],$pagina);}
    }
}catch(DomainException $e){if(http_response_code()!==404)http_response_code(400);$error=$e->getMessage();}
catch(Throwable $e){error_log($e->getMessage());http_response_code(503);$error='No se pudo cargar el módulo. Intenta nuevamente.';}
require __DIR__.'/inicio.php';
?>
<section class="crud-encabezado"><div><h1><?=h($tituloPagina)?></h1><p class="texto-secundario">Administración de Banco FISEI.</p></div>
<?php if($vista!=='lista'):?><a class="btn btn-secundario" href="<?=h($ruta)?>">← Volver al listado</a><?php elseif(in_array($modulo,['usuarios','cuentas'],true)):?><a class="btn btn-primario" href="<?=enlace(['vista'=>'crear'])?>">+ <?=$modulo==='usuarios'?'Nuevo usuario':'Nueva cuenta'?></a><?php endif;?></section>
<?php if($flash):?><div role="status" class="alerta <?=$flash['ok']?'alerta-exito':'alerta-error'?>"><?=h($flash['texto'])?></div><?php endif;?>
<?php if($error!==''):?><div role="alert" class="alerta alerta-error"><?=h($error)?></div><a class="btn btn-secundario" href="<?=h($ruta)?>">Regresar</a>
<?php elseif($vista==='crear'):require __DIR__.'/modulo-formulario.php';
elseif($vista==='ver'):require __DIR__.'/modulo-detalle.php';
else:require __DIR__.'/modulo-listado.php';endif;
require __DIR__.'/fin.php';
