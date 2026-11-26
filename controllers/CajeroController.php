<?php
require_once __DIR__.'/AccesoCuentaController.php';

class CajeroController
{
    private PDO $db;
    private Cajero $modelo;
    private CajeroService $servicio;
    private array $usuario;
    private int $cliente;
    public function __construct(PDO $db,array $usuario){$this->db=$db;$this->modelo=new Cajero($db);$this->servicio=new CajeroService($db);$this->usuario=$usuario;$this->cliente=(int)$usuario['id_cliente'];}
    public function modelo(): Cajero {return $this->modelo;}
    public function cliente(): int {return $this->cliente;}
    public static function texto(array $p,string $k): string { $v=$p[$k]??'';if(!is_string($v)||strlen($v)>400)throw new DomainException('Solicitud inválida.');return trim($v); }
    public function seleccion(): ?array {
        $sel=$_SESSION['cuenta_cajero']??null;
        if(!$sel)return null;
        try{return $this->servicio->verificar($this->cliente,$sel);}
        catch(DomainException $e){self::cerrarCuenta();return null;}
    }
    public static function cerrarCuenta(): void {
        unset($_SESSION['cuenta_cajero'],$_SESSION['transferencia_cajero'],$_SESSION['comprobante_cajero'],$_SESSION['tokens_cajero']);
    }
    public static function filtrosCuenta(array $entrada,string $numero): array {
        $f=CajeroService::filtros($entrada);
        if($f['cuenta']!=='' && $f['cuenta']!==$numero)throw new DomainException('Cuenta no disponible.');
        $f['cuenta']=$numero;
        return $f;
    }
    public function exigirCuenta(): array { $a=$this->seleccion();if(!$a)throw new DomainException('Selecciona y valida una cuenta activa primero.');return $a; }
    public static function token(string $accion): string {
        $t=bin2hex(random_bytes(32));$_SESSION['tokens_cajero'][$accion]=$t;return $t;
    }
    public static function consumir(string $accion,array $p): void {
        $t=$p['operacion_token']??null;$esperado=$_SESSION['tokens_cajero'][$accion]??null;
        if(!is_string($t)||!is_string($esperado)||!hash_equals($esperado,$t))throw new DomainException('Formulario vencido o ya utilizado. Vuelve a intentar.');
        unset($_SESSION['tokens_cajero'][$accion]);
    }
    public function historial(array $entrada,?int $pagina): array {
        $f=self::filtrosCuenta($entrada,$this->exigirCuenta()['numero']);
        return ['filtros'=>$f]+$this->modelo->historial($this->cliente,$f,$pagina);
    }
    public function procesar(array $p): string {
        CsrfService::exigirToken();
        $accion=self::texto($p,'accion');self::consumir($accion,$p);
        if($accion==='cambiar'){
            self::cerrarCuenta();return 'cuentas';
        }
        if($accion==='seleccionar'){
            self::cerrarCuenta();
            $sel=(new AccesoCuentaController())->validar($p,$this->usuario,$this->db);
            $_SESSION['cuenta_cajero']=$sel;return 'panel';
        }
        $this->exigirCuenta();$sel=$_SESSION['cuenta_cajero'];
        if($accion==='cancelar_transferencia'){unset($_SESSION['transferencia_cajero'],$_SESSION['tokens_cajero']['transferir']);return 'transferencia';}
        if($accion==='destino'){
            unset($_SESSION['transferencia_cajero'],$_SESSION['tokens_cajero']['transferir']);
            exigirModoQr($p);$d=$this->servicio->destino($sel,self::texto($p,'qr'));
            $m=CajeroService::monto('TRANSFERENCIA_ENVIADA',self::texto($p,'monto'));
            $_SESSION['transferencia_cajero']=['destino'=>$d,'monto'=>CajeroService::decimal($m),'origen'=>$sel,'vence'=>time()+120];return 'confirmar';
        }
        if($accion==='transferir'){
            $b=$_SESSION['transferencia_cajero']??null;unset($_SESSION['transferencia_cajero']);
            if(!$b||$b['vence']<time()||$b['origen']!==$sel)throw new DomainException('La confirmación expiró. Vuelve a escanear el QR.');
            $r=$this->servicio->operar((int)$this->usuario['id'],$this->cliente,$sel,'TRANSFERENCIA_ENVIADA',$b['monto'],$b['destino']);
        }elseif(in_array($accion,['retirar','depositar'],true)){
            $r=$this->servicio->operar((int)$this->usuario['id'],$this->cliente,$sel,$accion==='retirar'?'RETIRO':'DEPOSITO',self::texto($p,'monto'));
        }else throw new DomainException('Operación no disponible.');
        // Solo comprobantes de la operación recién realizada. Nunca aceptar un ID arbitrario.
        $ids=[$r['id']];
        $_SESSION['comprobante_cajero']=['ids'=>$ids,'vence'=>time()+300];return 'comprobante';
    }
    public function comprobantes(): array {
        $a=$this->exigirCuenta();$c=$_SESSION['comprobante_cajero']??null;if(!$c||$c['vence']<time())return [];
        $r=[];foreach($c['ids'] as $id){$m=$this->modelo->detalle($this->cliente,$id);if($m&&$m['numero_cuenta']===$a['numero'])$r[]=$m;}return $r;
    }
    public function recibidosRecientes(): array {
        $a=$this->exigirCuenta();$inicio=(int)($_SESSION['inicio_sesion']??0);if(!$inicio)return [];
        return array_values(array_filter($this->modelo->recibidosDesde($this->cliente,date('Y-m-d H:i:s',max($inicio,time()-300))),fn($m)=>$m['numero_cuenta']===$a['numero']));
    }
    public function comprobante(int $id): array {
        foreach($this->comprobantes() as $m)if((int)$m['id']===$id)return $m;
        foreach($this->recibidosRecientes() as $m)if((int)$m['id']===$id)return $m;
        throw new DomainException('Comprobante no disponible.');
    }
}
