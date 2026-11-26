<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Acceso no permitido.'); }
require_once __DIR__.'/../controllers/ModuloAdministrativoController.php';
$ok=0;$error=0;
function verificar(string $nombre,bool $condicion): void {global $ok,$error;echo ($condicion?'[OK] ':'[ERROR] ').$nombre.PHP_EOL;if($condicion)$ok++;else $error++;}
function rechazar(string $nombre,callable $fn): void {try{$fn();verificar($nombre,false);}catch(DomainException $e){verificar($nombre,true);}}
foreach(['ana.torres','1800000001','usuario_01','a-b',str_repeat('A',50)] as $v)verificar('Usuario válido: '.$v,GestionUsuariosService::usuarioValido($v));
foreach(['','a b','<script>',str_repeat('a',51),'á','abc/def'] as $v)verificar('Usuario inválido: '.$v,!GestionUsuariosService::usuarioValido($v));
foreach(['0000000001','2200000001'] as $v)verificar('Cuenta válida: '.$v,GestionCuentasService::numeroValido($v));
foreach(['123','12345678901','123456789a','1e9'] as $v)verificar('Cuenta inválida: '.$v,!GestionCuentasService::numeroValido($v));
foreach(['0','0.00','10.5','9999999999.99'] as $v)verificar('Saldo válido: '.$v,GestionCuentasService::saldoValido($v));
foreach(['-1','1.001','10000000000','1e2','1,25',''] as $v)verificar('Saldo inválido: '.$v,!GestionCuentasService::saldoValido($v));
verificar('ID entero',ModuloAdministrativoController::id('1')===1);
foreach([null,0,-1,[],true,'abc','2147483648'] as $v)rechazar('ID inválido '.json_encode($v),fn()=>ModuloAdministrativoController::id($v));
verificar('Página inválida se corrige',ModuloAdministrativoController::pagina(['x'])===1);
rechazar('Parámetro de arreglo',fn()=>ModuloAdministrativoController::texto(['x'=>[]],'x'));
$f=ConsultaMovimientosService::filtros(['cliente'=>' Ana ','cedula'=>'180','cuenta'=>'220','tipo'=>'DEPOSITO','desde'=>'2026-01-01','hasta'=>'2026-01-31']);
verificar('Filtros combinables y normalizados',$f===['cliente'=>'Ana','cedula'=>'180','cuenta'=>'220','tipo'=>'DEPOSITO','desde'=>'2026-01-01','hasta'=>'2026-01-31']);
verificar('Filtros vacíos',count(array_filter(ConsultaMovimientosService::filtros([])))===0);
foreach([['desde'=>'2026-02-30'],['desde'=>'2026-02-01','hasta'=>'2026-01-01'],['tipo'=>'INVALIDO'],['cuenta'=>[]],['hasta'=>'ayer']] as $v)rechazar('Filtro inválido '.json_encode($v),fn()=>ConsultaMovimientosService::filtros($v));
foreach(['TK-A1B2C3D40001','TK-0123456789AB','TK-FFFFFFFFFFFF'] as $v){$m=QrPng::matriz($v);$png=QrPng::png($v);verificar('Matriz QR 21x21 '.$v,count($m)===21&&count($m[0])===21&&count(array_filter(array_merge(...$m),fn($x)=>!is_bool($x)))===0);verificar('PNG '.$v,substr($png,0,8)==="\x89PNG\r\n\x1a\n"&&unpack('N',substr($png,16,4))[1]===232);}
rechazar('QR incompatible',fn()=>QrPng::png('abc'));
echo "Aprobadas: $ok; Fallidas: $error\n";exit($error?1:0);
