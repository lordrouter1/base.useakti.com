<?php

namespace Akti\Models;

use PDO;

/**
 * Model de métricas ESG (Environmental, Social, Governance).
 */
class EsgMetric
{
    private PDO $conn;

    /**
     * Construtor da classe EsgMetric.
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
            INSERT INTO esg_metrics (tenant_id, name, unit, category, is_active)
            VALUES (:tenant_id, :name, :unit, :category, :is_active)
        ");
        $stmt->execute([
            ':tenant_id' => $data['tenant_id'],
            ':name'      => $data['name'],
            ':unit'      => $data['unit'],
            ':category'  => $data['category'],
            ':is_active' => $data['is_active'] ?? 1,
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
        $stmt = $this->conn->prepare("SELECT * FROM esg_metrics WHERE tenant_id = :tid AND is_active = 1 ORDER BY category, name");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna um registro pelo ID.
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @return array|null
     */
    public function readOne(int $id, int $tenantId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM esg_metrics WHERE id = :id AND tenant_id = :tid");
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Atualiza um registro existente.
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @param array $data Dados para processamento
     * @return bool
     */
    public function update(int $id, int $tenantId, array $data): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE esg_metrics SET name = :name, unit = :unit, category = :category, is_active = :is_active
            WHERE id = :id AND tenant_id = :tid
        ");
        return $stmt->execute([
            ':name'      => $data['name'],
            ':unit'      => $data['unit'],
            ':category'  => $data['category'],
            ':is_active' => $data['is_active'] ?? 1,
            ':id'        => $id,
            ':tid'       => $tenantId,
        ]);
    }

    /**
     * Remove um registro pelo ID.
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @return bool
     */
    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->conn->prepare("UPDATE esg_metrics SET is_active = 0 WHERE id = :id AND tenant_id = :tid");
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }

    /**
     * Add record.
     *
     * @param array $data Dados para processamento
     * @return int
     */
    public function addRecord(array $data): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO esg_records (tenant_id, metric_id, sector_id, value, reference_date, notes, recorded_by)
            VALUES (:tid, :metric_id, :sector_id, :value, :reference_date, :notes, :recorded_by)
        ");
        $stmt->execute([
            ':tid'            => $data['tenant_id'],
            ':metric_id'      => $data['metric_id'],
            ':sector_id'      => $data['sector_id'] ?? null,
            ':value'          => $data['value'],
            ':reference_date' => $data['reference_date'],
            ':notes'          => $data['notes'] ?? null,
            ':recorded_by'    => $data['recorded_by'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Obtém dados específicos.
     *
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros aplicados
     * @return array
     */
    public function getRecords(int $tenantId, array $filters = []): array
    {
        $where = 'er.tenant_id = :tid';
        $params = [':tid' => $tenantId];

        if (!empty($filters['metric_id'])) {
            $where .= ' AND er.metric_id = :mid';
            $params[':mid'] = (int) $filters['metric_id'];
        }
        if (!empty($filters['category'])) {
            $where .= ' AND em.category = :cat';
            $params[':cat'] = $filters['category'];
        }
        if (!empty($filters['date_from'])) {
            $where .= ' AND er.reference_date >= :from';
            $params[':from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND er.reference_date <= :to';
            $params[':to'] = $filters['date_to'];
        }

        $stmt = $this->conn->prepare("
            SELECT er.*, em.name AS metric_name, em.unit, em.category, s.name AS sector_name
            FROM esg_records er
            JOIN esg_metrics em ON er.metric_id = em.id
            LEFT JOIN production_sectors s ON er.sector_id = s.id
            WHERE {$where}
            ORDER BY er.reference_date DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Save target.
  *
  * @param array $data Dados para processamento
  * @return int
  */
    public function saveTarget(array $data): int
    {
        if (!empty($data['id'])) {
            $stmt = $this->conn->prepare("
                UPDATE esg_targets SET target_value = :val, period_start = :start, period_end = :end
                WHERE id = :id AND tenant_id = :tid
            ");
            $stmt->execute([
                ':val'   => $data['target_value'],
                ':start' => $data['period_start'],
                ':end'   => $data['period_end'],
                ':id'    => $data['id'],
                ':tid'   => $data['tenant_id'],
            ]);
            return (int) $data['id'];
        }
        $stmt = $this->conn->prepare("
            INSERT INTO esg_targets (tenant_id, metric_id, target_value, period_start, period_end)
            VALUES (:tid, :metric_id, :target_value, :period_start, :period_end)
        ");
        $stmt->execute([
            ':tid'          => $data['tenant_id'],
            ':metric_id'    => $data['metric_id'],
            ':target_value' => $data['target_value'],
            ':period_start' => $data['period_start'],
            ':period_end'   => $data['period_end'],
        ]);
        return (int) $this->conn->lastInsertId();
    }

 /**
  * Get targets.
  *
  * @param int $tenantId ID do tenant
  * @return array
  */
    public function getTargets(int $tenantId): array
    {
        $stmt = $this->conn->prepare("
            SELECT et.*, em.name AS metric_name, em.unit, em.category
            FROM esg_targets et
            JOIN esg_metrics em ON et.metric_id = em.id
            WHERE et.tenant_id = :tid
            ORDER BY et.period_start DESC
        ");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Get dashboard summary.
  *
  * @param int $tenantId ID do tenant
  * @return array
  */
    public function getDashboardSummary(int $tenantId): array
    {
        $stmt = $this->conn->prepare("
            SELECT em.category,
                   SUM(er.value) AS total_value,
                   em.unit,
                   COUNT(er.id) AS record_count
            FROM esg_records er
            JOIN esg_metrics em ON er.metric_id = em.id
            WHERE er.tenant_id = :tid AND er.reference_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY em.category, em.unit
            ORDER BY em.category
        ");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
