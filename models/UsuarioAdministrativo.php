<?php
require_once __DIR__.'/GestionAdministrativa.php';
class UsuarioAdministrativo extends GestionAdministrativa
{
    private const CAMPOS = 'u.id,u.id_cliente,u.usuario,u.rol,u.estado,u.bloqueado,u.intentos_fallidos,c.nombres,c.cedula';
    private const ORIGEN = 'FROM usuarios u LEFT JOIN clientes c ON c.id=u.id_cliente';
    public function listar(string $buscar,int $pagina): array
    {
        $w=[];$p=[]; if($buscar!==''){ $w[]="(u.usuario LIKE ? ESCAPE '!' OR c.nombres LIKE ? ESCAPE '!' OR c.cedula LIKE ? ESCAPE '!')"; $p=array_fill(0,3,$this->like($buscar)); }
        return $this->paginar(self::CAMPOS,self::ORIGEN,$w,$p,'u.usuario,u.id',$pagina);
    }
    public function ver(int $id): ?array { return $this->uno('SELECT '.self::CAMPOS.' '.self::ORIGEN.' WHERE u.id=?',[$id]); }
    public function disponibles(): array { return $this->todos('SELECT c.id,c.nombres,c.cedula FROM clientes c WHERE NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id_cliente=c.id) ORDER BY c.nombres,c.id'); }
    public function crear(int $id,string $usuario,string $hash): void { $this->ejecutar("INSERT INTO usuarios(id_cliente,usuario,clave_hash,rol) VALUES(?,?,?,'CLIENTE')",[$id,$usuario,$hash]); }
    public function cambiar(int $id,string $accion,?string $hash=null): void
    {
        $sets=['activar'=>'estado=1','desactivar'=>'estado=0','desbloquear'=>'bloqueado=0,intentos_fallidos=0','pin'=>'clave_hash=?,bloqueado=0,intentos_fallidos=0'];
        $p=$accion==='pin'?[$hash,$id]:[$id];
        $this->ejecutar('UPDATE usuarios SET '.$sets[$accion]." WHERE id=? AND rol='CLIENTE'",$p);
    }
}
