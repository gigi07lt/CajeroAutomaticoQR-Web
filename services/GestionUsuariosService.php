<?php
require_once __DIR__.'/../models/UsuarioAdministrativo.php';
class GestionUsuariosService
{
    private UsuarioAdministrativo $modelo;
    public function __construct(PDO $db){$this->modelo=new UsuarioAdministrativo($db);}
    public static function usuarioValido(string $usuario): bool {return preg_match('/\A[A-Za-z0-9._-]{1,50}\z/',$usuario)===1;}
    private function hashPin(string $pin,string $confirmacion): string
    {
        if(!preg_match('/\A[0-9]{4}\z/',$pin)||$pin!==$confirmacion) throw new DomainException('El PIN debe contener cuatro dígitos y coincidir con la confirmación.');
        return password_hash($pin,PASSWORD_DEFAULT);
    }
    public function registrar(int $id,string $usuario,string $pin,string $confirmacion): void
    {
        $usuario=trim($usuario);
        if($id<1||!self::usuarioValido($usuario)) throw new DomainException('Selecciona un cliente e ingresa un usuario de hasta 50 letras, números, puntos, guiones o guiones bajos.');
        $hash=$this->hashPin($pin,$confirmacion);
        try{$this->modelo->crear($id,$usuario,$hash);}catch(PDOException $e){
            if(($e->errorInfo[1]??0)==1062) throw new DomainException('El nombre de usuario o el cliente ya tiene credenciales.');
            if(($e->errorInfo[1]??0)==1452) throw new DomainException('El cliente no existe.'); throw $e;
        }
    }
    public function cambiar(int $id,string $accion,string $pin='',string $confirmacion=''): void
    {
        $u=$this->modelo->ver($id);
        if(!$u||$u['rol']!=='CLIENTE') throw new DomainException('Usuario inexistente o protegido.');
        if(!in_array($accion,['activar','desactivar','desbloquear','pin'],true)) throw new DomainException('Acción inválida.');
        $this->modelo->cambiar($id,$accion,$accion==='pin'?$this->hashPin($pin,$confirmacion):null);
    }
}
