<?php if(!isset($usuario,$modulo,$datos)||($usuario['rol']??'')!=='ADMIN'){http_response_code(403);exit;} ?>
<section class="crud-contenedor">
<form method="get" action="<?=h($ruta)?>" class="crud-buscador <?=$modulo==='movimientos'?'crud-filtros':''?>">
<?php if($modulo==='movimientos'):?>
<?php foreach(['cliente'=>'Nombre del cliente','cedula'=>'Cédula','cuenta'=>'Número de cuenta','desde'=>'Desde','hasta'=>'Hasta'] as $k=>$label):?>
<div class="crud-campo-busqueda"><label for="<?=h($k)?>"><?=h($label)?></label><input id="<?=h($k)?>" name="<?=h($k)?>" type="<?=in_array($k,['desde','hasta'],true)?'date':'text'?>" value="<?=h($filtros[$k])?>"></div><?php endforeach;?>
<div class="crud-campo-busqueda"><label for="tipo">Tipo</label><select id="tipo" name="tipo"><option value="">Todos</option><?php foreach(MovimientoAdministrativo::TIPOS as $t):?><option value="<?=h($t)?>" <?=$filtros['tipo']===$t?'selected':''?>><?=h(str_replace('_',' ',$t))?></option><?php endforeach;?></select></div>
<?php else:?><div class="crud-campo-busqueda"><label for="buscar">Buscar</label><input type="search" id="buscar" name="buscar" maxlength="100" value="<?=h($filtros['buscar'])?>" placeholder="Nombre, cédula o identificador"></div><?php endif;?>

<button class="btn btn-primario" type="submit">
    Buscar
</button>

<a class="btn btn-secundario" href="<?=h($ruta)?>">
    Limpiar
</a>

<?php if ($modulo === 'movimientos'): ?>

    <?php
    $rutaReporte = urlAplicacion(
        'views/admin/movimientos/reporte.php'
    );

    $rutaReporte .= '?' . http_build_query($filtros);
    ?>

    <a class="btn btn-reporte"
       href="<?=h($rutaReporte)?>"
       target="_blank"
       rel="noopener noreferrer">

        Generar reporte PDF

    </a>

<?php endif; ?>

</form>


<p class="crud-resumen">Total de registros: <strong><?=h($datos['total'])?></strong></p>
<?php $columnas=match($modulo){'usuarios'=>['Usuario','Cliente','Rol','Estado','Bloqueo','Acciones'],'cuentas'=>['Cuenta','Cliente','Tipo','Saldo','Estado','Acciones'],'tarjetas'=>['Código QR','Cuenta','Cliente','Estado de tarjeta','Estado de cuenta','Emisión','Acciones'],'movimientos'=>['Fecha','Cuenta','Cliente','Tipo','Monto','Acciones']};?>
<div class="crud-tabla-responsive"><table class="crud-tabla"><thead><tr><?php foreach($columnas as $c):?><th scope="col"><?=h($c)?></th><?php endforeach;?></tr></thead><tbody>
<?php if(!$datos['filas']):?><tr><td colspan="<?=count($columnas)?>" class="crud-sin-resultados"><?=$modulo==='movimientos'?'No hay movimientos que coincidan con los filtros.':'No se encontraron registros.'?></td></tr><?php endif;?>
<?php foreach($datos['filas'] as $r):?><tr>
<?php if($modulo==='usuarios'):?><td><?=h($r['usuario'])?></td><td><?=h($r['nombres']??'—')?><small class="crud-ayuda"><?=h($r['cedula']??'')?></small></td><td><?=h($r['rol'])?></td><td><?=estado($r['estado'])?></td><td><?=bloqueo($r['bloqueado'])?></td>
<?php elseif($modulo==='cuentas'):?><td><?=h($r['numero'])?></td><td><?=h($r['nombres'])?><small class="crud-ayuda"><?=h($r['cedula'])?></small></td><td><?=h($r['tipo'])?></td><td><?=dinero($r['saldo'])?></td><td><?=estado($r['estado'],'Activa','Inactiva')?></td>
<?php elseif($modulo==='tarjetas'):?><td><?=h($r['codigo_qr'])?></td><td><?=h($r['numero_cuenta'])?></td><td><?=h($r['nombres'])?></td><td><?=estado($r['activa'],'Vigente','Histórica (inactiva)')?></td><td><?=estado($r['cuenta_activa'],'Activa','Inactiva')?></td><td><?=h($r['fecha_emision'])?></td>
<?php else:?><td><?=h($r['fecha'])?></td><td><?=h($r['numero_cuenta'])?></td><td><?=h($r['nombres'])?></td><td><?=h(str_replace('_',' ',$r['tipo']))?></td><td><?=dinero($r['monto'])?></td><?php endif;?>
<td><div class="crud-acciones"><a class="crud-accion accion-ver" href="<?=enlace(['vista'=>'ver','id'=>$r['id']??$r['numero']??$r['codigo_qr']])?>">Ver</a>
</div></td></tr><?php endforeach;?>
</tbody></table></div>
<div class="crud-paginacion"><p class="crud-paginacion-info">Mostrando <?=$datos['total']?($datos['pagina']-1)*10+1:0?> a <?=min($datos['pagina']*10,$datos['total'])?> de <?=h($datos['total'])?> registros.</p><nav class="crud-paginacion-enlaces" aria-label="Paginación">
<?php if($datos['pagina']>1):?><a class="crud-pagina" href="<?=enlace($filtros+['pagina'=>$datos['pagina']-1])?>">Anterior</a><?php else:?><span class="crud-pagina pagina-deshabilitada">Anterior</span><?php endif;?>
<span class="crud-pagina pagina-activa"><?=h($datos['pagina'])?> / <?=h($datos['paginas'])?></span>
<?php if($datos['pagina']<$datos['paginas']):?><a class="crud-pagina" href="<?=enlace($filtros+['pagina'=>$datos['pagina']+1])?>">Siguiente</a><?php else:?><span class="crud-pagina pagina-deshabilitada">Siguiente</span><?php endif;?></nav></div></section>
