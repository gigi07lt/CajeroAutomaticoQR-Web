<?php
require_once __DIR__.'/GestionAdministrativa.php';
class TarjetaAdministrativa extends GestionAdministrativa
{
    private const CAMPOS='t.codigo_qr,t.numero_cuenta,t.activa,t.fecha_emision,t.fecha_anulacion,a.estado AS cuenta_activa,c.nombres,c.cedula';
    private const ORIGEN='FROM tarjetas t JOIN cuentas a ON a.numero=t.numero_cuenta JOIN clientes c ON c.id=a.id_cliente';
    public function listar(string $buscar,int $pagina): array
    {
        $w=[];$p=[];if($buscar!==''){ $w[]="(t.codigo_qr LIKE ? ESCAPE '!' OR t.numero_cuenta LIKE ? ESCAPE '!' OR c.nombres LIKE ? ESCAPE '!' OR c.cedula LIKE ? ESCAPE '!')";$p=array_fill(0,4,$this->like($buscar)); }
        return $this->paginar(self::CAMPOS,self::ORIGEN,$w,$p,'t.fecha_emision DESC,t.codigo_qr',$pagina);
    }
    public function ver(string $codigo): ?array { return $this->uno('SELECT '.self::CAMPOS.' '.self::ORIGEN.' WHERE t.codigo_qr=?',[$codigo]); }
}
