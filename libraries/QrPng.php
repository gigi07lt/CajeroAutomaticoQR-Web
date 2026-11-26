<?php
/** QR Model 2, versión 1, nivel L, modo alfanumérico, máscara 0.
 * Implementación local para códigos TK- de hasta 20 caracteres ASCII.
 * PNG gris de 8 bits, sin dependencias externas (zlib).
 */
class QrPng
{
    private const ALFABETO='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';
    private static function multiplicar(int $a,int $b): int
    { $r=0;while($b){if($b&1)$r^=$a;$b>>=1;$a<<=1;if($a&256)$a^=0x11d;}return $r; }
    private static function bits(int $v,int $n): string {return str_pad(decbin($v),$n,'0',STR_PAD_LEFT);}
    public static function matriz(string $texto): array
    {
        if(strlen($texto)<1||strlen($texto)>25||strspn($texto,self::ALFABETO)!==strlen($texto))throw new DomainException('Código QR no compatible.');
        $bits='0010'.self::bits(strlen($texto),9);
        for($i=0;$i<strlen($texto);$i+=2){$a=strpos(self::ALFABETO,$texto[$i]);$bits.=isset($texto[$i+1])?self::bits($a*45+strpos(self::ALFABETO,$texto[$i+1]),11):self::bits($a,6);}
        $bits.=str_repeat('0',min(4,152-strlen($bits)));$bits.=str_repeat('0',(8-strlen($bits)%8)%8);
        $datos=array_map('bindec',str_split($bits,8));for($i=0;count($datos)<19;$i++)$datos[]=$i%2?17:236;
        $g=[1];$raiz=1;for($i=0;$i<7;$i++){ $n=array_fill(0,count($g)+1,0);foreach($g as $j=>$v){$n[$j]^=$v;$n[$j+1]^=self::multiplicar($v,$raiz);} $g=$n;$raiz=self::multiplicar($raiz,2); }
        $r=array_merge($datos,array_fill(0,7,0));for($i=0;$i<19;$i++){ $v=$r[$i];foreach($g as $j=>$c)$r[$i+$j]^=self::multiplicar($v,$c); }
        $flujo='';foreach(array_merge($datos,array_slice($r,19)) as $v)$flujo.=self::bits($v,8);
        $m=array_fill(0,21,array_fill(0,21,null));
        $poner=function(int $x,int $y,bool $v)use(&$m){if($x>=0&&$x<21&&$y>=0&&$y<21)$m[$y][$x]=$v;};
        foreach([[3,3],[17,3],[3,17]] as [$cx,$cy])for($dy=-4;$dy<=4;$dy++)for($dx=-4;$dx<=4;$dx++){ $d=max(abs($dx),abs($dy));$poner($cx+$dx,$cy+$dy,$d!==2&&$d!==4); }
        for($i=8;$i<=12;$i++){ $poner($i,6,$i%2===0);$poner(6,$i,$i%2===0); }
        $f=0x77c4; $b=fn($i)=>(bool)(($f>>$i)&1);
        for($i=0;$i<=5;$i++)$poner(8,$i,$b($i));$poner(8,7,$b(6));$poner(8,8,$b(7));$poner(7,8,$b(8));
        for($i=9;$i<15;$i++)$poner(14-$i,8,$b($i));
        for($i=0;$i<8;$i++)$poner(20-$i,8,$b($i));for($i=8;$i<15;$i++)$poner(8,6+$i,$b($i));$poner(8,13,true);
        $k=0;$arriba=true;for($x=20;$x>=1;$x-=2){if($x===6)$x=5;for($i=0;$i<21;$i++){ $y=$arriba?20-$i:$i;for($j=0;$j<2;$j++){ $xx=$x-$j;if($m[$y][$xx]!==null)continue;$m[$y][$xx]=((($flujo[$k++]??'0')==='1') !== (($xx+$y)%2===0)); }}$arriba=!$arriba;}
        if($k!==208)throw new RuntimeException('Matriz QR incompleta.');return $m;
    }
    public static function png(string $texto): string
    {
        $m=self::matriz($texto);$escala=8;$lado=29*$escala;$raw='';
        for($y=-4;$y<25;$y++){ $fila="\0";for($x=-4;$x<25;$x++)$fila.=str_repeat(($m[$y][$x]??false)?"\0":"\xff",$escala);$raw.=str_repeat($fila,$escala); }
        $chunk=fn($t,$d)=>pack('N',strlen($d)).$t.$d.pack('N',crc32($t.$d));
        return "\x89PNG\r\n\x1a\n".$chunk('IHDR',pack('NNCCCCC',$lado,$lado,8,0,0,0,0)).$chunk('IDAT',gzcompress($raw,9)).$chunk('IEND','');
    }
}
