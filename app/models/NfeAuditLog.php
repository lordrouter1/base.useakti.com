<?php
namespace Akti\Models;

use PDO;

/**
 * Model: NfeAuditLog
 * CRUD para auditoria completa de acessos ao módulo NF-e (tabela nfe_audit_log).
 *
 * @package Akti\Models
 */
class NfeAuditLog
{
    private $conn;
    private $table = 'nfe_audit_log';

    /**
     * Construtor da classe NfeAuditLog.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Registra uma ação de auditoria.
     * @param array $data
     * @return int ID do registro
     */
    public function log(array $data): int
    {
        $q = "INSERT INTO {$this->table}
              (user_id, user_name, action, entity_type, entity_id, description, ip_address, user_agent, extra_data)
              VALUES
              (:user_id, :user_name, :action, :entity_type, :entity_id, :description, :ip_address, :user_agent, :extra_data)";

        $s = $this->conn->prepare($q);
        $s->execute([
            ':user_id'     => $data['user_id'] ?? ($_SESSION['user_id'] ?? null),
            ':user_name'   => $data['user_name'] ?? ($_SESSION['user_name'] ?? 'Sistema'),
            ':action'      => $data['action'],
            ':entity_type' => $data['entity_type'],
            ':entity_id'   => $data['entity_id'] ?? null,
            ':description' => $data['description'] ?? null,
            ':ip_address'  => $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            ':user_agent'  => $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
            ':extra_data'  => isset($data['extra_data']) ? json_encode($data['extra_data']) : null,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Listagem paginada de auditoria.
     * @param array $filters  action, entity_type, user_id, date_start, date_end, search
     * @param int   $page
     * @param int   $perPage
     * @return array ['data' => [], 'total' => int]
     */
    public function readPaginated(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['action'])) {
            $where[] = "action = :action";
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['entity_type'])) {
            $where[] = "entity_type = :entity_type";
            $params[':entity_type'] = $filters['entity_type'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['date_start'])) {
            $where[] = "created_at >= :date_start";
            $params[':date_start'] = $filters['date_start'] . ' 00:00:00';
        }
        if (!empty($filters['date_end'])) {
            $where[] = "created_at <= :date_end";
            $params[':date_end'] = $filters['date_end'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $where[] = "(user_name LIKE :search OR description LIKE :search2 OR ip_address LIKE :search3)";
            $params[':search']  = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
            $params[':search3'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        $qCount = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $sCount = $this->conn->prepare($qCount);
        $sCount->execute($params);
        $total = (int) $sCount->fetchColumn();

        $q = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $s = $this->conn->prepare($q);
        foreach ($params as $k => $v) {
            $s->bindValue($k, $v);
        }
        $s->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $s->bindValue(':offset', $offset, PDO::PARAM_INT);
        $s->execute();

        return [
            'data'  => $s->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }

    /**
     * Retorna ações distintas para filtro.
     * @return array
     */
    public function getDistinctActions(): array
    {
        $q = "SELECT DISTINCT action FROM {$this->table} ORDER BY action";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Retorna logs por entidade.
     * @param string $entityType
     * @param int    $entityId
     * @return array
     */
    public function getByEntity(string $entityType, int $entityId): array
    {
        $q = "SELECT * FROM {$this->table} WHERE entity_type = :et AND entity_id = :eid ORDER BY created_at DESC";
        $s = $this->conn->prepare($q);
        $s->execute([':et' => $entityType, ':eid' => $entityId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contagem de ações por tipo (para dashboard).
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function countByAction(?string $startDate = null, ?string $endDate = null): array
    {
        $where = [];
        $params = [];
        if ($startDate) {
            $where[] = "created_at >= :start";
            $params[':start'] = $startDate . ' 00:00:00';
        }
        if ($endDate) {
            $where[] = "created_at <= :end";
            $params[':end'] = $endDate . ' 23:59:59';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $q = "SELECT action, COUNT(*) as cnt FROM {$this->table} {$whereClause} GROUP BY action ORDER BY cnt DESC";
        $s = $this->conn->prepare($q);
        $s->execute($params);

        $result = [];
        while ($row = $s->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['action']] = (int) $row['cnt'];
        }
        return $result;
    }
}
