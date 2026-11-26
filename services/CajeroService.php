<?php
require_once __DIR__.'/../models/Cajero.php';
require_once __DIR__.'/ConsultaMovimientosService.php';

class CajeroService
{
    private PDO $db;
    private Cajero $modelo;
    public const MAX_SALDO=999999999999; // centavos, DECIMAL(12,2)
    public function __construct(PDO $db){$this->db=$db;$this->modelo=new Cajero($db);}
    public static function centavos(string $valor): int {
        if(PHP_INT_SIZE<8)throw new RuntimeException('El cajero requiere PHP de 64 bits.');
        if(!preg_match('/\A[0-9]{1,10}(?:\.[0-9]{1,2})?\z/',$valor)) throw new DomainException('Monto inválido; utiliza hasta dos decimales.');
        $p=explode('.',$valor);return (int)$p[0]*100+(int)str_pad($p[1]??'',2,'0');
    }
    public static function decimal(int $c): string {return intdiv($c,100).'.'.str_pad((string)($c%100),2,'0',STR_PAD_LEFT);}
    public static function monto(string $tipo,string $valor): int {
        $c=self::centavos($valor);
        $max=['RETIRO'=>30000,'DEPOSITO'=>100000,'TRANSFERENCIA_ENVIADA'=>50000];
        if(!isset($max[$tipo])||$c<1000||$c>$max[$tipo]) throw new DomainException('Monto fuera de los límites de la operación.');
        if($tipo==='RETIRO'&&$c%1000!==0)throw new DomainException('El retiro debe ser múltiplo de $10.');
        return $c;
    }
    public static function numero(string $n): string {if(!preg_match('/\A[0-9]{10}\z/',$n))throw new DomainException('Número de cuenta inválido.');return $n;}
    public static function codigo(string $q): string { $q=strtoupper(trim($q));if(!preg_match('/\ATK-[A-F0-9]{12}\z/',$q))throw new DomainException('Código QR inválido.');return $q; }
    public function seleccion(int $cliente,string $numero,string $codigo): array {
        $a=$this->modelo->cuentaValidada($cliente,self::numero($numero),self::codigo($codigo));
        if(!$a)throw new DomainException('La cuenta o tarjeta no está disponible.');
        return ['numero'=>$numero,'qr'=>$a['codigo_qr']];
    }
    public function verificar(int $cliente,array $sel): array {
        $a=$this->modelo->cuentaValidada($cliente,self::numero($sel['numero']),$sel['qr']);
        if(!$a)throw new DomainException('Cuenta no disponible.');
        return $a;
    }
    public function destino(array $sel,string $codigo): array {
        $q=$this->modelo->qr(self::codigo($codigo));
        if(!$q||!$q['activa']||$q['numero_cuenta']===$sel['numero'])throw new DomainException('Tarjeta destinataria no disponible o igual a la cuenta de origen.');
        $a=$this->modelo->cuenta($q['numero_cuenta']);
        if(!$a||!$a['estado'])throw new DomainException('Cuenta destinataria no disponible.');
        return ['numero'=>$a['numero'],'nombres'=>$a['nombres'],'qr'=>$q['codigo_qr']];
    }
    public function operar(int $usuario,int $cliente,array $sel,string $tipo,string $valor,?array $destino=null): array {
        $m=self::monto($tipo,$valor);$origen=self::numero($sel['numero']);
        $transfer=$tipo==='TRANSFERENCIA_ENVIADA';
        if($transfer&&(!$destino||$destino['numero']===$origen))throw new DomainException('Destinatario inválido.');
        $numeros=[$origen];if($transfer)$numeros[]=self::numero($destino['numero']);sort($numeros,SORT_STRING);
        $propia=!$this->db->inTransaction();
        if($propia)$this->db->beginTransaction();else $this->db->exec('SAVEPOINT cajero_operacion');
        try {
            if(!$this->modelo->usuarioActivo($usuario,$cliente,true))throw new DomainException('Usuario no disponible.');
            $cuentas=[];foreach($numeros as $n){$a=$this->modelo->cuenta($n,true);if(!$a||!$a['estado'])throw new DomainException('Cuenta no disponible.');$cuentas[$n]=$a;}
            $a=$cuentas[$origen];$q=$this->modelo->qrActivo($origen,true);
            if((int)$a['id_cliente']!==$cliente||!$q||$q['codigo_qr']!==$sel['qr'])throw new DomainException('La selección de cuenta ya no es válida.');
            $saldo=self::centavos((string)$a['saldo']);$nuevo=$tipo==='DEPOSITO'?$saldo+$m:$saldo-$m;
            if($nuevo<0)throw new DomainException('Saldo insuficiente.');
            if($nuevo>self::MAX_SALDO)throw new DomainException('Se supera el saldo máximo admitido.');
            if($tipo==='RETIRO'&&self::centavos($this->modelo->retirosHoy($origen))+$m>100000)throw new DomainException('Límite diario de retiros de $1000 alcanzado.');
            $rel=null;$nuevoDestino=0;
            if($transfer){$rel=$destino['numero'];$qd=$this->modelo->qrActivo($rel,true);if(!$qd||$qd['codigo_qr']!==$destino['qr'])throw new DomainException('La tarjeta destinataria cambió.');$nuevoDestino=self::centavos((string)$cuentas[$rel]['saldo'])+$m;if($nuevoDestino>self::MAX_SALDO)throw new DomainException('Saldo máximo del destinatario excedido.');}
            $ref='OP-'.strtoupper(bin2hex(random_bytes(16)));
            $this->modelo->saldo($origen,self::decimal($nuevo));
            $id=$this->modelo->movimiento($origen,$tipo,self::decimal($m),self::decimal($nuevo),$ref,$rel);
            $recibido=null;
            if($transfer){$this->modelo->saldo($rel,self::decimal($nuevoDestino));$recibido=$this->modelo->movimiento($rel,'TRANSFERENCIA_RECIBIDA',self::decimal($m),self::decimal($nuevoDestino),$ref,$origen);}
            if($propia)$this->db->commit();else $this->db->exec('RELEASE SAVEPOINT cajero_operacion');
            return ['id'=>$id,'recibido'=>$recibido];
        }catch(Throwable $e){if($this->db->inTransaction()){if($propia)$this->db->rollBack();else {$this->db->exec('ROLLBACK TO SAVEPOINT cajero_operacion');$this->db->exec('RELEASE SAVEPOINT cajero_operacion');}}throw $e;}
    }
    public static function filtros(array $entrada): array {
        $f=ConsultaMovimientosService::filtros(array_intersect_key($entrada,array_flip(['cuenta','tipo','desde','hasta'])));
        if($f['cuenta']!=='')self::numero($f['cuenta']);return $f;
    }
}
