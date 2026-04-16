<?php

namespace Akti\Utils;

use PDO;

/**
 * CursorPaginator — Paginação baseada em cursor para large datasets.
 *
 * Ao contrário da paginação por offset (LIMIT/OFFSET), a paginação por cursor
 * usa o ID do último registro retornado como ponto de partida, mantendo
 * performance constante independentemente do "número da página".
 *
 * Uso:
 *   $paginator = new CursorPaginator($db);
 *   $result = $paginator->paginate(
 *       table: 'orders',
 *       columns: 'o.*, c.name AS customer_name',
 *       joins: 'LEFT JOIN customers c ON o.customer_id = c.id',
 *       where: 'o.status = :status',
 *       params: [':status' => 'ativo'],
 *       cursor: $lastId,
 *       limit: 50,
 *       direction: 'next'
 *   );
 */
class CursorPaginator
{
    private PDO $db;

    /**
     * Construtor da classe CursorPaginator.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Executa paginação cursor-based.
     *
     * @param string $table     Tabela principal (com alias se necessário)
     * @param string $columns   Colunas a selecionar
     * @param string $joins     JOINs adicionais (opcional)
     * @param string $where     Condição WHERE sem o cursor (opcional)
     * @param array  $params    Parâmetros bind para WHERE
     * @param int|null $cursor  ID do último registro (null = primeira página)
     * @param int    $limit     Registros por página
     * @param string $direction 'next' (> cursor) ou 'prev' (< cursor)
     * @param string $orderCol  Coluna de ordenação (deve ser indexada e única)
     * @return array{data: array, next_cursor: int|null, prev_cursor: int|null, has_more: bool}
     */
    public function paginate(
        string $table,
        string $columns = '*',
        string $joins = '',
        string $where = '',
        array $params = [],
        ?int $cursor = null,
        int $limit = 50,
        string $direction = 'next',
        string $orderCol = 'id'
    ): array {
        $conditions = [];
        if ($where !== '') {
            $conditions[] = $where;
        }

        if ($cursor !== null) {
            if ($direction === 'prev') {
                $conditions[] = "{$orderCol} < :cursor";
            } else {
                $conditions[] = "{$orderCol} > :cursor";
            }
            $params[':cursor'] = $cursor;
        }

        $whereStr = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $order = $direction === 'prev' ? 'DESC' : 'ASC';

        // Buscar limit + 1 para detectar has_more
        $fetchLimit = $limit + 1;

        $sql = "SELECT {$columns} FROM {$table} {$joins} {$whereStr} ORDER BY {$orderCol} {$order} LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            if ($k === ':cursor' || $k === ':limit') {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, $v);
            }
        }
        $stmt->bindValue(':limit', $fetchLimit, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hasMore = count($data) > $limit;
        if ($hasMore) {
            array_pop($data);
        }

        // Reverter ordem se buscou "prev" (foi DESC)
        if ($direction === 'prev') {
            $data = array_reverse($data);
        }

        $nextCursor = !empty($data) ? (int) end($data)[$orderCol] : null;
        $prevCursor = !empty($data) ? (int) reset($data)[$orderCol] : null;

        return [
            'data'        => $data,
            'next_cursor' => $hasMore ? $nextCursor : null,
            'prev_cursor' => $cursor !== null ? $prevCursor : null,
            'has_more'    => $hasMore,
        ];
    }
}
