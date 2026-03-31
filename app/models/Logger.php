<?php
namespace Akti\Models;

use PDO;

class Logger {
    private $conn;
    private $table_name = "system_logs";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function log($action, $details = "", $user_id = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, action, details, ip_address, created_at) 
                  VALUES (:user_id, :action, :details, :ip_address, NOW())";
        
        $stmt = $this->conn->prepare($query);

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        if ($user_id === null && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':details', $details);
        $stmt->bindParam(':ip_address', $ip_address);

        return $stmt->execute();
    }

    /**
     * Lista logs com paginação e filtros.
     *
     * @param array $filters Filtros opcionais (action, user_id, date_from, date_to, search)
     * @param int   $page    Página (1-based)
     * @param int   $perPage Itens por página
     * @return array{data: array, total: int, page: int, perPage: int, totalPages: int}
     */
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $page = max(1, $page);
        $perPage = min(max(1, $perPage), 200);
        $offset = ($page - 1) * $perPage;

        $where = ["1=1"];
        $params = [];

        if (!empty($filters['action'])) {
            $where[] = "sl.action = :action";
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = "sl.user_id = :uid";
            $params[':uid'] = (int) $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "sl.created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "sl.created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $where[] = "(sl.details LIKE :search OR sl.action LIKE :search2)";
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
        }

        $whereStr = implode(' AND ', $where);

        // Contar total
        $countStmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} sl WHERE {$whereStr}"
        );
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        // Buscar página
        $dataStmt = $this->conn->prepare("
            SELECT sl.*, u.name as user_name
            FROM {$this->table_name} sl
            LEFT JOIN users u ON sl.user_id = u.id
            WHERE {$whereStr}
            ORDER BY sl.created_at DESC
            LIMIT :lim OFFSET :off
        ");
        foreach ($params as $k => $v) {
            $dataStmt->bindValue($k, $v);
        }
        $dataStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $dataStmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $dataStmt->execute();

        return [
            'data'       => $dataStmt->fetchAll(PDO::FETCH_ASSOC),
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Retorna a lista de ações distintas (para filtro dropdown).
     *
     * @return array
     */
    public function getDistinctActions(): array
    {
        $stmt = $this->conn->query(
            "SELECT DISTINCT action FROM {$this->table_name} ORDER BY action ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
