<?php
require_once __DIR__.'/../middleware/roles.php';
require_once __DIR__.'/../services/CsrfService.php';
require_once __DIR__.'/../services/GestionUsuariosService.php';
require_once __DIR__.'/../services/GestionCuentasService.php';
require_once __DIR__.'/../services/ConsultaMovimientosService.php';
require_once __DIR__.'/../models/TarjetaAdministrativa.php';
require_once __DIR__.'/../libraries/QrPng.php';
/** Entrada común de los cuatro módulos; todas las escrituras requieren POST y CSRF. */
class ModuloAdministrativoController
{
    private PDO $db;
    public function __construct(PDO $db){$this->db=$db;}
    public static function texto(array $a,string $k, bool $recortar=true): string { $v=$a[$k]??'';if(!is_string($v))throw new DomainException('Parámetro inválido.');return $recortar?trim($v):$v; }
    public static function id($v): int {if(!is_string($v)&&!is_int($v))throw new DomainException('Identificador inválido.');$id=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>2147483647]]);if($id===false)throw new DomainException('Identificador inválido.');return $id;}
    public static function pagina($v): int {try{return self::id($v);}catch(DomainException $e){return 1;}}
    public function modelo(string $modulo): GestionAdministrativa
    {
        return match($modulo){'usuarios'=>new UsuarioAdministrativo($this->db),'cuentas'=>new CuentaAdministrativa($this->db),'tarjetas'=>new TarjetaAdministrativa($this->db),'movimientos'=>new MovimientoAdministrativo($this->db)};
    }
    public function procesarHttp(string $modulo): void
    {
        exigirRol('ADMIN');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            exit('Método no permitido.');
        }
        CsrfService::exigirToken();
        $this->procesar($modulo, $_POST);
    }

    // Operación de aplicación, reutilizable desde las pruebas CLI.
    public function procesar(string $modulo,array $p): void
    {
        if(!in_array($modulo,['usuarios','cuentas'],true))throw new DomainException('Módulo de solo consulta.');
        $accion=self::texto($p,'accion');
        if($modulo==='usuarios'){
            $s=new GestionUsuariosService($this->db);
            if($accion==='crear')$s->registrar(self::id($p['cliente']??null),self::texto($p,'usuario'),self::texto($p,'pin',false),self::texto($p,'confirmacion',false));
            else $s->cambiar(self::id($p['id']??null),$accion,self::texto($p,'pin',false),self::texto($p,'confirmacion',false));
        }else{
            $s=new GestionCuentasService($this->db);$n=self::texto($p,'numero');
            if($accion==='crear')$s->registrar(self::id($p['cliente']??null),$n,self::texto($p,'tipo'),self::texto($p,'saldo'));
            else $s->cambiar($n,$accion);
        }
    }
}
