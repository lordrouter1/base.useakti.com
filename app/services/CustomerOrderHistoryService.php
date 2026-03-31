<?php
namespace Akti\Services;

use PDO;

/**
 * Service: CustomerOrderHistoryService
 * Consulta de histórico de pedidos de um cliente.
 */
class CustomerOrderHistoryService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Busca pedidos recentes de um cliente.
     */
    public function getRecentOrders(int $customerId, int $limit = 5): array
    {
        $query = "SELECT o.id, o.total_amount, o.status, o.created_at
                  FROM orders o
                  WHERE o.customer_id = :cid
                  ORDER BY o.created_at DESC
                  LIMIT :lim";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca pedidos paginados de um cliente com formatação.
     */
    public function getOrderHistoryPaginated(int $customerId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        // Contar total
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = :cid");
        $countStmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $countStmt->execute();
        $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Buscar pedidos
        $query = "SELECT o.id, o.total_amount, o.status, o.created_at
                  FROM orders o
                  WHERE o.customer_id = :cid
                  ORDER BY o.created_at DESC
                  LIMIT :lim OFFSET :off";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatar
        $formatted = [];
        foreach ($orders as $order) {
            $formatted[] = [
                'id'           => (int) $order['id'],
                'total_amount' => number_format($order['total_amount'] ?? 0, 2, ',', '.'),
                'status'       => $order['status'] ?? '',
                'created_at'   => !empty($order['created_at']) ? date('d/m/Y', strtotime($order['created_at'])) : '—',
            ];
        }

        return [
            'orders'      => $formatted,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }
}
