<?php if(!isset($usuario,$modulo,$disponibles)||($usuario['rol']??'')!=='ADMIN'){http_response_code(403);exit;} ?>
<section class="crud-formulario-contenedor"><h2><?=$modulo==='usuarios'?'Registrar credenciales':'Registrar cuenta'?></h2>
<?php if(!$disponibles):?><div class="alerta alerta-info">No hay clientes disponibles para esta operación.</div><?php else:?>
<form class="crud-formulario" method="post" action="<?=h($ruta)?>">
<?=token()?><input type="hidden" name="accion" value="crear">
<div class="grupo-campo"><label for="cliente">Cliente</label><select id="cliente" name="cliente" required><option value="">Selecciona un cliente</option><?php foreach($disponibles as $c):?><option value="<?=h($c['id'])?>"><?=h($c['nombres'].' — '.$c['cedula'])?></option><?php endforeach;?></select></div>
<?php if($modulo==='usuarios'):?>
<div class="grupo-campo"><label for="usuario">Usuario</label><input id="usuario" name="usuario" maxlength="50" pattern="[A-Za-z0-9._\-]+" required autocomplete="off"><small class="crud-ayuda">Letras, números, puntos, guiones y guiones bajos.</small></div>
<?php require __DIR__.'/modulo-pin.php';?>

<?php else:?>
<div class="grupo-campo"><label for="numero">Número de cuenta</label><input id="numero" name="numero" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" required></div>
<div class="grupo-campo"><label for="tipo">Tipo de cuenta</label><select id="tipo" name="tipo" required><option value="AHORROS">Ahorros</option><option value="CORRIENTE">Corriente</option></select></div>
<div class="grupo-campo"><label for="saldo">Saldo inicial (USD)</label><input id="saldo" name="saldo" inputmode="decimal" value="0.00" pattern="[0-9]{1,10}(\.[0-9]{1,2})?" required><small class="crud-ayuda">Importe no negativo, hasta dos decimales. Usa punto decimal.</small></div>

<?php endif;?>
<div class="crud-formulario-acciones"><button class="btn btn-primario" type="submit">Guardar</button><a class="btn btn-secundario" href="<?=h($ruta)?>">Cancelar</a></div></form><?php endif;?></section>
