<?php
require_once __DIR__.'/GestionAdministrativa.php';

/** Persistencia del cajero. Las consultas de cliente siempre delimitan al titular. */
class Cajero extends GestionAdministrativa
{
    public function cuentas(int $cliente): array {
        return $this->todos('SELECT numero,tipo,estado FROM cuentas WHERE id_cliente=? ORDER BY numero',[$cliente]);
    }
    public function cuenta(string $numero, bool $lock=false): ?array {
        if($lock)return $this->uno('SELECT * FROM cuentas WHERE numero=? FOR UPDATE',[$numero]);
        return $this->uno('SELECT a.*,c.nombres,c.cedula FROM cuentas a JOIN clientes c ON c.id=a.id_cliente WHERE a.numero=?',[$numero]);
    }
    public function cuentaValidada(int $cliente,string $numero,?string $codigo=null): ?array {
        $sql='SELECT a.*,c.nombres,c.cedula,t.codigo_qr FROM cuentas a JOIN clientes c ON c.id=a.id_cliente JOIN tarjetas t ON t.cuenta_activa=a.numero WHERE a.numero=? AND a.id_cliente=? AND a.estado=1';
        $p=[$numero,$cliente];if($codigo!==null){$sql.=' AND t.codigo_qr=?';$p[]=$codigo;}
        return $this->uno($sql,$p);
    }
    public function qr(string $codigo, bool $lock=false): ?array {
        return $this->uno('SELECT codigo_qr,numero_cuenta,activa FROM tarjetas WHERE codigo_qr=?'.($lock?' FOR UPDATE':''),[$codigo]);
    }
    public function qrActivo(string $numero, bool $lock=false): ?array {
        return $this->uno('SELECT codigo_qr,numero_cuenta,activa FROM tarjetas WHERE cuenta_activa=?'.($lock?' FOR UPDATE':''),[$numero]);
    }
    public function usuarioCuenta(string $numero): ?array {
        return $this->uno("SELECT u.usuario,u.id_cliente FROM cuentas a JOIN usuarios u ON u.id_cliente=a.id_cliente WHERE a.numero=? AND a.estado=1 AND u.rol='CLIENTE'",[$numero]);
    }
    public function usuarioActivo(int $id,int $cliente,bool $lock=false): bool {
        return $this->uno("SELECT id FROM usuarios WHERE id=? AND id_cliente=? AND rol='CLIENTE' AND estado=1 AND bloqueado=0".($lock?' FOR UPDATE':''),[$id,$cliente])!==null;
    }
    public function tarjetas(int $cliente): array {
        return $this->todos('SELECT t.codigo_qr,t.numero_cuenta,t.activa,t.fecha_emision,t.fecha_anulacion FROM tarjetas t JOIN cuentas a ON a.numero=t.numero_cuenta WHERE a.id_cliente=? AND a.estado=1 AND t.activa=1 ORDER BY t.numero_cuenta,t.codigo_qr',[$cliente]);
    }
    public function tarjetaPropia(int $cliente,string $qr): ?array {
        return $this->uno('SELECT t.codigo_qr,t.activa FROM tarjetas t JOIN cuentas a ON a.numero=t.numero_cuenta WHERE a.id_cliente=? AND t.codigo_qr=? AND a.estado=1 AND t.activa=1',[$cliente,$qr]);
    }
    public function retirosHoy(string $numero): string {
        return (string)$this->ejecutar("SELECT COALESCE(SUM(monto),0) FROM movimientos WHERE numero_cuenta=? AND tipo='RETIRO' AND fecha>=CURRENT_DATE AND fecha<CURRENT_DATE + INTERVAL 1 DAY",[$numero])->fetchColumn();
    }
    public function saldo(string $numero,string $saldo): void {
        $this->ejecutar('UPDATE cuentas SET saldo=? WHERE numero=?',[$saldo,$numero]);
    }
    public function movimiento(string $numero,string $tipo,string $monto,string $saldo,string $ref,?string $rel): int {
        $this->ejecutar('INSERT INTO movimientos(numero_cuenta,tipo,monto,saldo_resultante,referencia,cuenta_relacionada) VALUES(?,?,?,?,?,?)',[$numero,$tipo,$monto,$saldo,$ref,$rel]);
        return (int)$this->db->lastInsertId();
    }
    public function recibidosDesde(int $cliente,string $desde): array {
        return $this->todos("SELECT m.*,c.nombres,c.cedula FROM movimientos m JOIN cuentas a ON a.numero=m.numero_cuenta JOIN clientes c ON c.id=a.id_cliente WHERE a.id_cliente=? AND m.tipo='TRANSFERENCIA_RECIBIDA' AND m.fecha>=? ORDER BY m.fecha DESC,m.id DESC",[$cliente,$desde]);
    }
    public function detalle(int $cliente,int $id): ?array {
        return $this->uno('SELECT m.*,c.nombres,c.cedula FROM movimientos m JOIN cuentas a ON a.numero=m.numero_cuenta JOIN clientes c ON c.id=a.id_cliente WHERE a.id_cliente=? AND m.id=?',[$cliente,$id]);
    }
    public function historial(int $cliente,array $f,?int $pagina): array {
        $w=['a.id_cliente=?']; $p=[$cliente];
        foreach(['cuenta'=>'m.numero_cuenta','tipo'=>'m.tipo'] as $k=>$campo) if($f[$k]!=='') {$w[]="$campo=?";$p[]=$f[$k];}
        if($f['desde']!=='') {$w[]='m.fecha>=?';$p[]=$f['desde'].' 00:00:00';}
        if($f['hasta']!=='') {$w[]='m.fecha<=?';$p[]=$f['hasta'].' 23:59:59';}
        $base=' FROM movimientos m JOIN cuentas a ON a.numero=m.numero_cuenta JOIN clientes c ON c.id=a.id_cliente WHERE '.implode(' AND ',$w);
        $total=(int)$this->ejecutar('SELECT COUNT(*)'.$base,$p)->fetchColumn();
        $paginas=max(1,(int)ceil($total/20)); $limit='';
        if($pagina!==null){$pagina=max(1,min($pagina,$paginas));$limit=' LIMIT 20 OFFSET '.(($pagina-1)*20);}
        $filas=$this->todos('SELECT m.*,c.nombres,c.cedula'.$base.' ORDER BY m.fecha DESC,m.id DESC'.$limit,$p);
        return ['filas'=>$filas,'total'=>$total,'pagina'=>$pagina,'paginas'=>$paginas];
    }
}
