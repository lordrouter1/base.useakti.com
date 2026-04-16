<?php

namespace Akti\Models;

use PDO;

/**
 * Model de remessas/entregas.
 */
class Shipment
{
    private PDO $conn;

    /**
     * Construtor da classe Shipment.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Cria um novo registro no banco de dados.
     *
     * @param array $data Dados para processamento
     * @return int
     */
    public function create(array $data): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO shipments (tenant_id, order_id, carrier_id, tracking_code, status, shipped_at, estimated_delivery, notes)
            VALUES (:tenant_id, :order_id, :carrier_id, :tracking_code, :status, :shipped_at, :estimated_delivery, :notes)
        ");
        $stmt->execute([
            ':tenant_id'          => $data['tenant_id'],
            ':order_id'           => $data['order_id'],
            ':carrier_id'         => $data['carrier_id'] ?? null,
            ':tracking_code'      => $data['tracking_code'] ?? null,
            ':status'             => $data['status'] ?? 'preparing',
            ':shipped_at'         => $data['shipped_at'] ?? null,
            ':estimated_delivery' => $data['estimated_delivery'] ?? null,
            ':notes'              => $data['notes'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Retorna todos os registros.
     *
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros aplicados
     * @return array
     */
    public function readAll(int $tenantId, array $filters = []): array
    {
        $where = 's.tenant_id = :tid';
        $params = [':tid' => $tenantId];

        if (!empty($filters['status'])) {
            $where .= ' AND s.status = :status';
            $params[':status'] = $filters['status'];
        }

        $stmt = $this->conn->prepare("
            SELECT s.*, c.name AS carrier_name, c.tracking_url_pattern
            FROM shipments s
            LEFT JOIN carriers c ON s.carrier_id = c.id
            WHERE {$where}
            ORDER BY s.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Read paginated.
  *
  * @param int $tenantId ID do tenant
  * @param int $page Número da página
  * @param int $perPage Registros por página
  * @param array $filters Filtros aplicados
  * @return array
  */
    public function readPaginated(int $tenantId, int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $where = 's.tenant_id = :tid';
        $params = [':tid' => $tenantId];

        if (!empty($filters['status'])) {
            $where .= ' AND s.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (s.tracking_code LIKE :search OR c.name LIKE :search2)';
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
        }

        $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM shipments s LEFT JOIN carriers c ON s.carrier_id = c.id WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->conn->prepare("
            SELECT s.*, c.name AS carrier_name, c.tracking_url_pattern
            FROM shipments s
            LEFT JOIN carriers c ON s.carrier_id = c.id
            WHERE {$where}
            ORDER BY s.created_at DESC
            LIMIT :lim OFFSET :off
        ");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'        => $total,
            'pages'        => (int) ceil($total / $perPage),
            'current_page' => $page,
        ];
    }

 /**
  * Read one.
  *
  * @param int $id ID do registro
  * @param int $tenantId ID do tenant
  * @return array|null
  */
    public function readOne(int $id, int $tenantId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT s.*, c.name AS carrier_name, c.tracking_url_pattern
            FROM shipments s LEFT JOIN carriers c ON s.carrier_id = c.id
            WHERE s.id = :id AND s.tenant_id = :tid
        ");
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

 /**
  * Read by order.
  *
  * @param int $orderId ID do pedido
  * @param int $tenantId ID do tenant
  * @return array|null
  */
    public function readByOrder(int $orderId, int $tenantId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT s.*, c.name AS carrier_name, c.tracking_url_pattern
            FROM shipments s LEFT JOIN carriers c ON s.carrier_id = c.id
            WHERE s.order_id = :oid AND s.tenant_id = :tid
            ORDER BY s.created_at DESC LIMIT 1
        ");
        $stmt->execute([':oid' => $orderId, ':tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

 /**
  * Update.
  *
  * @param int $id ID do registro
  * @param int $tenantId ID do tenant
  * @param array $data Dados para processamento
  * @return bool
  */
    public function update(int $id, int $tenantId, array $data): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE shipments SET carrier_id = :carrier_id, tracking_code = :tracking_code, status = :status, shipped_at = :shipped_at, estimated_delivery = :estimated_delivery, notes = :notes
            WHERE id = :id AND tenant_id = :tid
        ");
        return $stmt->execute([
            ':carrier_id'         => $data['carrier_id'] ?? null,
            ':tracking_code'      => $data['tracking_code'] ?? null,
            ':status'             => $data['status'],
            ':shipped_at'         => $data['shipped_at'] ?? null,
            ':estimated_delivery' => $data['estimated_delivery'] ?? null,
            ':notes'              => $data['notes'] ?? null,
            ':id'                 => $id,
            ':tid'                => $tenantId,
        ]);
    }

 /**
  * Update status.
  *
  * @param int $id ID do registro
  * @param int $tenantId ID do tenant
  * @param string $status Status do registro
  * @return bool
  */
    public function updateStatus(int $id, int $tenantId, string $status): bool
    {
        $extra = '';
        if ($status === 'delivered') {
            $extra = ', delivered_at = NOW()';
        } elseif ($status === 'shipped') {
            $extra = ', shipped_at = COALESCE(shipped_at, NOW())';
        }
        $stmt = $this->conn->prepare("UPDATE shipments SET status = :status{$extra} WHERE id = :id AND tenant_id = :tid");
        return $stmt->execute([':status' => $status, ':id' => $id, ':tid' => $tenantId]);
    }

 /**
  * Add event.
  *
  * @param array $data Dados para processamento
  * @return int
  */
    public function addEvent(array $data): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO shipment_events (tenant_id, shipment_id, status, description, location, occurred_at)
            VALUES (:tenant_id, :shipment_id, :status, :description, :location, :occurred_at)
        ");
        $stmt->execute([
            ':tenant_id'   => $data['tenant_id'],
            ':shipment_id' => $data['shipment_id'],
            ':status'      => $data['status'],
            ':description' => $data['description'] ?? null,
            ':location'    => $data['location'] ?? null,
            ':occurred_at' => $data['occurred_at'] ?? date('Y-m-d H:i:s'),
        ]);
        return (int) $this->conn->lastInsertId();
    }

 /**
  * Get events.
  *
  * @param int $shipmentId Shipment id
  * @param int $tenantId ID do tenant
  * @return array
  */
    public function getEvents(int $shipmentId, int $tenantId): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM shipment_events WHERE shipment_id = :sid AND tenant_id = :tid ORDER BY occurred_at ASC
        ");
        $stmt->execute([':sid' => $shipmentId, ':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Get carriers.
  *
  * @param int $tenantId ID do tenant
  * @return array
  */
    public function getCarriers(int $tenantId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM carriers WHERE tenant_id = :tid AND is_active = 1 ORDER BY name");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Save carrier.
  *
  * @param array $data Dados para processamento
  * @return int
  */
    public function saveCarrier(array $data): int
    {
        if (!empty($data['id'])) {
            $stmt = $this->conn->prepare("
                UPDATE carriers SET name = :name, tracking_url_pattern = :url, api_type = :api_type, is_active = :is_active
                WHERE id = :id AND tenant_id = :tid
            ");
            $stmt->execute([
                ':name'     => $data['name'],
                ':url'      => $data['tracking_url_pattern'] ?? null,
                ':api_type' => $data['api_type'] ?? 'manual',
                ':is_active' => $data['is_active'] ?? 1,
                ':id'       => $data['id'],
                ':tid'      => $data['tenant_id'],
            ]);
            return (int) $data['id'];
        }
        $stmt = $this->conn->prepare("
            INSERT INTO carriers (tenant_id, name, tracking_url_pattern, api_type, is_active)
            VALUES (:tid, :name, :url, :api_type, :is_active)
        ");
        $stmt->execute([
            ':tid'       => $data['tenant_id'],
            ':name'      => $data['name'],
            ':url'       => $data['tracking_url_pattern'] ?? null,
            ':api_type'  => $data['api_type'] ?? 'manual',
            ':is_active' => $data['is_active'] ?? 1,
        ]);
        return (int) $this->conn->lastInsertId();
    }

 /**
  * Get dashboard stats.
  *
  * @param int $tenantId ID do tenant
  * @return array
  */
    public function getDashboardStats(int $tenantId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'preparing') AS preparing,
                SUM(status IN ('shipped','in_transit','out_for_delivery')) AS in_transit,
                SUM(status = 'delivered') AS delivered,
                SUM(status = 'returned') AS returned
            FROM shipments WHERE tenant_id = :tid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
