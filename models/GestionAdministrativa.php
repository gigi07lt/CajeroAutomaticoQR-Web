<?php
/** Consultas parametrizadas compartidas por los módulos administrativos. */
class GestionAdministrativa
{
    protected PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }
    public function ejecutar(string $sql, array $params = []): PDOStatement
    {
        $s = $this->db->prepare($sql); $s->execute($params); return $s;
    }
    public function uno(string $sql, array $params = []): ?array
    { return $this->ejecutar($sql, $params)->fetch() ?: null; }
    public function todos(string $sql, array $params = []): array
    { return $this->ejecutar($sql, $params)->fetchAll(); }
    protected function paginar(string $select, string $from, array $where, array $params, string $orden, int $pagina): array
    {
        $base = $from . ($where ? ' WHERE '.implode(' AND ', $where) : '');
        $total = (int)$this->ejecutar('SELECT COUNT(*) '.$base, $params)->fetchColumn();
        $paginas = max(1, (int)ceil($total / 10)); $pagina = max(1,min($pagina,$paginas));
        $offset = ($pagina-1)*10;
        return ['filas'=>$this->todos("SELECT $select $base ORDER BY $orden LIMIT 10 OFFSET $offset",$params), 'total'=>$total,'pagina'=>$pagina,'paginas'=>$paginas];
    }
    protected function like(string $texto): string
    { return '%'.strtr($texto,['!'=>'!!','%'=>'!%','_'=>'!_']).'%'; }
    protected function filtro(array &$where,array &$params,string $campo,string $valor): void
    { if ($valor !== '') { $where[]="$campo LIKE ? ESCAPE '!'"; $params[]=$this->like($valor); } }
}
