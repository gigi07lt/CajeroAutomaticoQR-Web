<?php
require_once __DIR__.'/GestionAdministrativa.php';
class CuentaAdministrativa extends GestionAdministrativa
{
    private const CAMPOS='a.numero,a.id_cliente,a.tipo,a.saldo,a.estado,c.nombres,c.cedula';
    private const ORIGEN='FROM cuentas a JOIN clientes c ON c.id=a.id_cliente';
    public function listar(string $buscar,int $pagina): array
    {
        $w=[];$p=[];if($buscar!==''){ $w[]="(a.numero LIKE ? ESCAPE '!' OR c.nombres LIKE ? ESCAPE '!' OR c.cedula LIKE ? ESCAPE '!')";$p=array_fill(0,3,$this->like($buscar)); }
        return $this->paginar(self::CAMPOS,self::ORIGEN,$w,$p,'a.numero',$pagina);
    }
    public function ver(string $numero): ?array { return $this->uno('SELECT '.self::CAMPOS.' '.self::ORIGEN.' WHERE a.numero=?',[$numero]); }
    public function disponibles(): array { return $this->todos("SELECT c.id,c.nombres,c.cedula FROM clientes c JOIN usuarios u ON u.id_cliente=c.id WHERE u.rol='CLIENTE' AND u.estado=1 ORDER BY c.nombres,c.id"); }
    public function bloquear(string $numero): ?array { return $this->uno('SELECT * FROM cuentas WHERE numero=? FOR UPDATE',[$numero]); }
    public function crear(int $id,string $numero,string $tipo,string $saldo): void { $this->ejecutar('INSERT INTO cuentas(numero,id_cliente,tipo,saldo) VALUES(?,?,?,?)',[$numero,$id,$tipo,$saldo]); }
    public function emitir(string $numero): string
    {
        for($i=0;$i<5;$i++) {
            $codigo='TK-'.strtoupper(bin2hex(random_bytes(6)));
            try { $this->ejecutar('INSERT INTO tarjetas(codigo_qr,numero_cuenta) VALUES(?,?)',[$codigo,$numero]); return $codigo; }
            catch(PDOException $e){ if(($e->errorInfo[1]??0)!=1062) throw $e; }
        }
        throw new RuntimeException('No se pudo emitir un código único.');
    }
    public function estado(string $numero,bool $activo): void { $this->ejecutar('UPDATE cuentas SET estado=? WHERE numero=?',[(int)$activo,$numero]); }
    public function anular(string $numero): void { $this->ejecutar('UPDATE tarjetas SET activa=0,fecha_anulacion=CURRENT_TIMESTAMP WHERE numero_cuenta=? AND activa=1',[$numero]); }
    public function tieneMovimientos(string $numero): bool { return (bool)$this->ejecutar('SELECT EXISTS(SELECT 1 FROM movimientos WHERE numero_cuenta=? OR cuenta_relacionada=?)',[$numero,$numero])->fetchColumn(); }
    public function eliminar(string $numero): void { $this->ejecutar('DELETE FROM tarjetas WHERE numero_cuenta=?',[$numero]); $this->ejecutar('DELETE FROM cuentas WHERE numero=?',[$numero]); }
}
