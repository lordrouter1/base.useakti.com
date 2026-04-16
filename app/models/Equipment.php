<?php

namespace Akti\Models;

use PDO;

/**
 * Model de equipamentos/máquinas de produção.
 */
class Equipment
{
    private PDO $conn;

    /**
     * Construtor da classe Equipment.
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
            INSERT INTO equipment (tenant_id, name, code, model, manufacturer, serial_number, location, sector_id, status, purchase_date, purchase_cost, warranty_end, notes)
            VALUES (:tenant_id, :name, :code, :model, :manufacturer, :serial_number, :location, :sector_id, :status, :purchase_date, :purchase_cost, :warranty_end, :notes)
        ");
        $stmt->execute([
            ':tenant_id'     => $data['tenant_id'],
            ':name'          => $data['name'],
            ':code'          => $data['code'] ?? null,
            ':model'         => $data['model'] ?? null,
            ':manufacturer'  => $data['manufacturer'] ?? null,
            ':serial_number' => $data['serial_number'] ?? null,
            ':location'      => $data['location'] ?? null,
            ':sector_id'     => $data['sector_id'] ?: null,
            ':status'        => $data['status'] ?? 'active',
            ':purchase_date' => $data['purchase_date'] ?: null,
            ':purchase_cost' => $data['purchase_cost'] ?: null,
            ':warranty_end'  => $data['warranty_end'] ?: null,
            ':notes'         => $data['notes'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Retorna todos os registros.
     *
     * @param int $tenantId ID do tenant
     * @return array
     */
    public function readAll(int $tenantId): array
    {
        $stmt = $this->conn->prepare("
            SELECT e.*, s.name AS sector_name
            FROM equipment e
            LEFT JOIN production_sectors s ON e.sector_id = s.id
            WHERE e.tenant_id = :tid AND e.deleted_at IS NULL
            ORDER BY e.name ASC
        ");
        $stmt->execute([':tid' => $tenantId]);
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
    public function readPaginated(int $tenantId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $where = 'e.tenant_id = :tid AND e.deleted_at IS NULL';
        $params = [':tid' => $tenantId];

        if (!empty($filters['status'])) {
            $where .= ' AND e.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (e.name LIKE :search OR e.code LIKE :search2 OR e.serial_number LIKE :search3)';
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
            $params[':search3'] = '%' . $filters['search'] . '%';
        }

        $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM equipment e WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->conn->prepare("
            SELECT e.*, s.name AS sector_name
            FROM equipment e
            LEFT JOIN production_sectors s ON e.sector_id = s.id
            WHERE {$where}
            ORDER BY e.name ASC
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
            SELECT e.*, s.name AS sector_name
            FROM equipment e
            LEFT JOIN production_sectors s ON e.sector_id = s.id
            WHERE e.id = :id AND e.tenant_id = :tid AND e.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
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
            UPDATE equipment SET name = :name, code = :code, model = :model, manufacturer = :manufacturer, serial_number = :serial_number, location = :location, sector_id = :sector_id, status = :status, purchase_date = :purchase_date, purchase_cost = :purchase_cost, warranty_end = :warranty_end, notes = :notes
            WHERE id = :id AND tenant_id = :tid
        ");
        return $stmt->execute([
            ':name'          => $data['name'],
            ':code'          => $data['code'] ?? null,
            ':model'         => $data['model'] ?? null,
            ':manufacturer'  => $data['manufacturer'] ?? null,
            ':serial_number' => $data['serial_number'] ?? null,
            ':location'      => $data['location'] ?? null,
            ':sector_id'     => $data['sector_id'] ?: null,
            ':status'        => $data['status'] ?? 'active',
            ':purchase_date' => $data['purchase_date'] ?: null,
            ':purchase_cost' => $data['purchase_cost'] ?: null,
            ':warranty_end'  => $data['warranty_end'] ?: null,
            ':notes'         => $data['notes'] ?? null,
            ':id'            => $id,
            ':tid'           => $tenantId,
        ]);
    }

 /**
  * Delete.
  *
  * @param int $id ID do registro
  * @param int $tenantId ID do tenant
  * @return bool
  */
    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->conn->prepare("UPDATE equipment SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }

 /**
  * Get schedules.
  *
  * @param int $equipmentId Equipment id
  * @param int $tenantId ID do tenant
  * @return array
  */
    public function getSchedules(int $equipmentId, int $tenantId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM maintenance_schedules WHERE equipment_id = :eid AND tenant_id = :tid AND is_active = 1 ORDER BY next_due_at ASC");
        $stmt->execute([':eid' => $equipmentId, ':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Get logs.
  *
  * @param int $equipmentId Equipment id
  * @param int $tenantId ID do tenant
  * @param int $limit Limite de registros
  * @return array
  */
    public function getLogs(int $equipmentId, int $tenantId, int $limit = 20): array
    {
        $stmt = $this->conn->prepare("
            SELECT ml.*, u.name AS performed_by_name
            FROM maintenance_logs ml
            LEFT JOIN users u ON ml.performed_by = u.id
            WHERE ml.equipment_id = :eid AND ml.tenant_id = :tid
            ORDER BY ml.performed_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':eid', $equipmentId, PDO::PARAM_INT);
        $stmt->bindValue(':tid', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Create schedule.
  *
  * @param array $data Dados para processamento
  * @return int
  */
    public function createSchedule(array $data): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO maintenance_schedules (tenant_id, equipment_id, title, description, frequency_type, frequency_value, next_due_at, alert_days_before)
            VALUES (:tenant_id, :equipment_id, :title, :description, :frequency_type, :frequency_value, :next_due_at, :alert_days_before)
        ");
        $stmt->execute([
            ':tenant_id'         => $data['tenant_id'],
            ':equipment_id'      => $data['equipment_id'],
            ':title'             => $data['title'],
            ':description'       => $data['description'] ?? null,
            ':frequency_type'    => $data['frequency_type'],
            ':frequency_value'   => $data['frequency_value'] ?? 1,
            ':next_due_at'       => $data['next_due_at'],
            ':alert_days_before' => $data['alert_days_before'] ?? 7,
        ]);
        return (int) $this->conn->lastInsertId();
    }

 /**
  * Create log.
  *
  * @param array $data Dados para processamento
  * @return int
  */
    public function createLog(array $data): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO maintenance_logs (tenant_id, equipment_id, schedule_id, type, title, description, performed_by, performed_at, duration_minutes, cost, parts_used, status)
            VALUES (:tenant_id, :equipment_id, :schedule_id, :type, :title, :description, :performed_by, :performed_at, :duration_minutes, :cost, :parts_used, :status)
        ");
        $stmt->execute([
            ':tenant_id'        => $data['tenant_id'],
            ':equipment_id'     => $data['equipment_id'],
            ':schedule_id'      => $data['schedule_id'] ?? null,
            ':type'             => $data['type'] ?? 'preventive',
            ':title'            => $data['title'],
            ':description'      => $data['description'] ?? null,
            ':performed_by'     => $data['performed_by'] ?? null,
            ':performed_at'     => $data['performed_at'],
            ':duration_minutes' => $data['duration_minutes'] ?? null,
            ':cost'             => $data['cost'] ?? 0,
            ':parts_used'       => !empty($data['parts_used']) ? json_encode($data['parts_used']) : null,
            ':status'           => $data['status'] ?? 'completed',
        ]);

        if (!empty($data['schedule_id'])) {
            $this->conn->prepare("UPDATE maintenance_schedules SET last_performed_at = :at WHERE id = :id")
                ->execute([':at' => $data['performed_at'], ':id' => $data['schedule_id']]);
        }

        return (int) $this->conn->lastInsertId();
    }

 /**
  * Get upcoming maintenance.
  *
  * @param int $tenantId ID do tenant
  * @param int $days Days
  * @return array
  */
    public function getUpcomingMaintenance(int $tenantId, int $days = 30): array
    {
        $stmt = $this->conn->prepare("
            SELECT ms.*, e.name AS equipment_name, e.location
            FROM maintenance_schedules ms
            JOIN equipment e ON ms.equipment_id = e.id
            WHERE ms.tenant_id = :tid AND ms.is_active = 1 AND e.deleted_at IS NULL
              AND ms.next_due_at <= DATE_ADD(NOW(), INTERVAL :days DAY)
            ORDER BY ms.next_due_at ASC
        ");
        $stmt->bindValue(':tid', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                COUNT(*) AS total_equipment,
                SUM(status = 'active') AS active_count,
                SUM(status = 'maintenance') AS in_maintenance,
                SUM(status = 'inactive') AS inactive_count
            FROM equipment WHERE tenant_id = :tid AND deleted_at IS NULL
        ");
        $stmt->execute([':tid' => $tenantId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt2 = $this->conn->prepare("
            SELECT COUNT(*) AS overdue FROM maintenance_schedules ms
            JOIN equipment e ON ms.equipment_id = e.id
            WHERE ms.tenant_id = :tid AND ms.is_active = 1 AND e.deleted_at IS NULL AND ms.next_due_at < NOW()
        ");
        $stmt2->execute([':tid' => $tenantId]);
        $stats['overdue_maintenance'] = (int) $stmt2->fetchColumn();

        return $stats;
    }
}
