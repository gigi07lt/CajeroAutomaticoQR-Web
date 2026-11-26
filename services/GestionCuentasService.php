<?php
require_once __DIR__.'/../models/CuentaAdministrativa.php';
class GestionCuentasService
{
    private PDO $db; private CuentaAdministrativa $modelo;
    public function __construct(PDO $db){$this->db=$db;$this->modelo=new CuentaAdministrativa($db);}
    public static function numeroValido(string $numero): bool {return preg_match('/\A[0-9]{10}\z/',$numero)===1;}
    public static function saldoValido(string $saldo): bool {return preg_match('/\A[0-9]{1,10}(?:\.[0-9]{1,2})?\z/',$saldo)===1;}
    private function transaccion(callable $fn): void
    {
        $propia=!$this->db->inTransaction();
        if($propia)$this->db->beginTransaction(); else $this->db->exec('SAVEPOINT gestion_cuentas');
        try{$fn();if($propia)$this->db->commit();else $this->db->exec('RELEASE SAVEPOINT gestion_cuentas');}
        catch(Throwable $e){if($this->db->inTransaction()){if($propia)$this->db->rollBack();else {$this->db->exec('ROLLBACK TO SAVEPOINT gestion_cuentas');$this->db->exec('RELEASE SAVEPOINT gestion_cuentas');}}throw $e;}
    }
    public function registrar(int $id,string $numero,string $tipo,string $saldo): void
    {
        if($id<1||!self::numeroValido($numero)||!in_array($tipo,['AHORROS','CORRIENTE'],true)||!self::saldoValido($saldo)) throw new DomainException('Verifica cliente, número de diez dígitos, tipo y saldo no negativo con hasta dos decimales.');
        try{$this->transaccion(function()use($id,$numero,$tipo,$saldo){
            $u=$this->modelo->uno("SELECT id,estado FROM usuarios WHERE id_cliente=? AND rol='CLIENTE' FOR UPDATE",[$id]);
            if(!$u||!(bool)$u['estado']) throw new DomainException('El cliente debe tener un usuario activo.');
            $this->modelo->crear($id,$numero,$tipo,$saldo);$this->modelo->emitir($numero);
        });}catch(PDOException $e){if(($e->errorInfo[1]??0)==1062)throw new DomainException('El número de cuenta ya existe.');throw $e;}
    }
    public function cambiar(string $numero,string $accion): void
    {
        if(!self::numeroValido($numero)||!in_array($accion,['activar','desactivar','eliminar'],true))throw new DomainException('Solicitud inválida.');
        $this->transaccion(function()use($numero,$accion){
            $a=$this->modelo->bloquear($numero);if(!$a)throw new DomainException('La cuenta no existe.');
            if($accion==='eliminar'){
                if((float)$a['saldo']!==0.0||$this->modelo->tieneMovimientos($numero))throw new DomainException('Solo se elimina una cuenta con saldo cero y sin movimientos propios ni relacionados.');
                $this->modelo->eliminar($numero);return;
            }
            $activo=$accion==='activar';
            if((bool)$a['estado']===$activo)return;
            $this->modelo->anular($numero);
            $this->modelo->estado($numero,$activo);
            if($activo)$this->modelo->emitir($numero);
        });
    }
}
