<?php
require_once __DIR__.'/../../middleware/roles.php';
require_once __DIR__.'/../../services/CajeroService.php';
require_once __DIR__.'/../../libraries/QrPng.php';
$u=exigirRol('CLIENTE');if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);header('Allow: GET');exit;}
try{$v=$_GET['codigo']??'';if(!is_string($v))throw new DomainException();$q=CajeroService::codigo($v);if(!(new Cajero(obtenerConexion()))->tarjetaPropia((int)$u['id_cliente'],$q))throw new DomainException();$png=QrPng::png($q);header('Content-Type: image/png');header('X-Content-Type-Options: nosniff');header('Cache-Control: no-store');if(($_GET['descargar']??'')==='1')header('Content-Disposition: attachment; filename="'.$q.'.png"');echo $png;}
catch(DomainException $e){http_response_code(404);echo 'Tarjeta no disponible.';}catch(Throwable $e){error_log($e->getMessage());http_response_code(500);echo 'No se pudo generar la tarjeta.';}
