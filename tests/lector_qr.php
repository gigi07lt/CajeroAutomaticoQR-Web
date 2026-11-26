<?php
// Prueba aislada de la política de modalidad; no toca MySQL ni la configuración real.
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$dir=sys_get_temp_dir().'/qr-test-'.bin2hex(random_bytes(8));mkdir($dir);
copy(__DIR__.'/../config/cajero.php',$dir.'/cajero.php');
require $dir.'/cajero.php';
$n=0;$fallos=0;
function comprobar(bool $cond,string $nombre): void {global $n,$fallos;$n++;if(!$cond)$fallos++;echo ($cond?'[OK] ':'[ERROR] ').$nombre.PHP_EOL;}
function acepta(array $p): bool {try{exigirModoQr($p);return true;}catch(DomainException $e){return false;}}
try {
    foreach([false,true] as $flag){
        file_put_contents($dir.'/config.php','<?php return '.var_export(['qr_manual_desarrollo'=>$flag],true).';');
        foreach(['127.0.0.1','::1','203.0.113.10'] as $ip){
            $_SERVER['REMOTE_ADDR']=$ip;
            $manual=$flag && $ip!=='203.0.113.10';
            comprobar(acepta(['modo_qr'=>'manual'])===$manual,'Manual '.json_encode($flag).' '.$ip);
            comprobar(acepta(['modo_qr'=>'camara']),'Modalidad cámara '.$ip);
            comprobar(!acepta(['modo_qr'=>[]]),'Modalidad manipulada '.$ip);
        }
    }
} finally {unlink($dir.'/config.php');unlink($dir.'/cajero.php');rmdir($dir);}
echo "Aprobadas: ".($n-$fallos)."; Fallidas: $fallos\n";exit($fallos?1:0);
