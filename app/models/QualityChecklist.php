<?php

namespace Akti\Models;

use PDO;

/**
 * Model de checklists de controle de qualidade.
 */
class QualityChecklist
{
    private PDO $conn;

    /**
     * Construtor da classe QualityChecklist.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Retorna todos os registros.
     * @return array
     */
    public function readAll(): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM quality_checklists WHERE is_active = 1 ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna um registro pelo ID.
     *
     * @param int $id ID do registro
     * @return array|null
     */
    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM quality_checklists WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cria um novo registro no banco de dados.
     *
     * @param array $data Dados para processamento
     * @return int
     */
    public function create(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO quality_checklists (tenant_id, name, description, pipeline_stage_id, is_active)
             VALUES (:tenant_id, :name, :description, :pipeline_stage_id, :is_active)"
        );
        $stmt->execute([
            ':tenant_id'         => $data['tenant_id'],
            ':name'              => $data['name'],
            ':description'       => $data['description'] ?? null,
            ':pipeline_stage_id' => $data['pipeline_stage_id'] ?? null,
            ':is_active'         => $data['is_active'] ?? 1,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Atualiza um registro existente.
     *
     * @param int $id ID do registro
     * @param array $data Dados para processamento
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE quality_checklists SET name = :name, description = :description, pipeline_stage_id = :pipeline_stage_id, is_active = :is_active WHERE id = :id"
        );
        return $stmt->execute([
            ':id'                => $id,
            ':name'              => $data['name'],
            ':description'       => $data['description'] ?? null,
            ':pipeline_stage_id' => $data['pipeline_stage_id'] ?? null,
            ':is_active'         => $data['is_active'] ?? 1,
        ]);
    }

    /**
     * Remove um registro pelo ID.
     *
     * @param int $id ID do registro
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM quality_checklists WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ──── Items ────

    /**
     * Obtém dados específicos.
     *
     * @param int $checklistId Checklist id
     * @return array
     */
    public function getItems(int $checklistId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM quality_checklist_items WHERE checklist_id = :checklist_id ORDER BY sort_order ASC"
        );
        $stmt->execute([':checklist_id' => $checklistId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add item.
     *
     * @param array $data Dados para processamento
     * @return int
     */
    public function addItem(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO quality_checklist_items (tenant_id, checklist_id, description, required, sort_order)
             VALUES (:tenant_id, :checklist_id, :description, :required, :sort_order)"
        );
        $stmt->execute([
            ':tenant_id'    => $data['tenant_id'],
            ':checklist_id' => $data['checklist_id'],
            ':description'  => $data['description'],
            ':required'     => $data['required'] ?? 1,
            ':sort_order'   => $data['sort_order'] ?? 0,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Remove item.
     *
     * @param int $itemId Item id
     * @return bool
     */
    public function removeItem(int $itemId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM quality_checklist_items WHERE id = :id");
        return $stmt->execute([':id' => $itemId]);
    }

    // ──── Inspections ────

    /**
     * Create inspection.
     *
     * @param array $data Dados para processamento
     * @return int
     */
    public function createInspection(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO quality_inspections (tenant_id, checklist_id, order_id, inspector_id, status, results, notes)
             VALUES (:tenant_id, :checklist_id, :order_id, :inspector_id, :status, :results, :notes)"
        );
        $stmt->execute([
            ':tenant_id'    => $data['tenant_id'],
            ':checklist_id' => $data['checklist_id'],
            ':order_id'     => $data['order_id'] ?? null,
            ':inspector_id' => $data['inspector_id'] ?? null,
            ':status'       => $data['status'] ?? 'pending',
            ':results'      => json_encode($data['results'] ?? []),
            ':notes'        => $data['notes'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Update inspection.
     *
     * @param int $id ID do registro
     * @param array $data Dados para processamento
     * @return bool
     */
    public function updateInspection(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE quality_inspections SET status = :status, results = :results, notes = :notes, inspected_at = :inspected_at WHERE id = :id"
        );
        return $stmt->execute([
            ':id'           => $id,
            ':status'       => $data['status'],
            ':results'      => json_encode($data['results'] ?? []),
            ':notes'        => $data['notes'] ?? null,
            ':inspected_at' => $data['inspected_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Obtém dados específicos.
     *
     * @param int $orderId ID do pedido
     * @return array
     */
    public function getInspections(int $orderId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT qi.*, qc.name as checklist_name, u.name as inspector_name
             FROM quality_inspections qi
             LEFT JOIN quality_checklists qc ON qc.id = qi.checklist_id
             LEFT JOIN users u ON u.id = qi.inspector_id
             WHERE qi.order_id = :order_id
             ORDER BY qi.created_at DESC"
        );
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──── Non-Conformities ────

    /**
     * Create non conformity.
     *
     * @param array $data Dados para processamento
     * @return int
     */
    public function createNonConformity(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO quality_nonconformities (tenant_id, inspection_id, order_id, title, description, severity, status, responsible_id)
             VALUES (:tenant_id, :inspection_id, :order_id, :title, :description, :severity, :status, :responsible_id)"
        );
        $stmt->execute([
            ':tenant_id'     => $data['tenant_id'],
            ':inspection_id' => $data['inspection_id'] ?? null,
            ':order_id'      => $data['order_id'] ?? null,
            ':title'         => $data['title'],
            ':description'   => $data['description'] ?? null,
            ':severity'      => $data['severity'] ?? 'medium',
            ':status'        => $data['status'] ?? 'open',
            ':responsible_id' => $data['responsible_id'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Obtém dados específicos.
     *
     * @param array $filters Filtros aplicados
     * @return array
     */
    public function getNonConformities(array $filters = []): array
    {
        $where = ' WHERE 1=1';
        $params = [];
        if (!empty($filters['status'])) {
            $where .= ' AND qn.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['severity'])) {
            $where .= ' AND qn.severity = :severity';
            $params[':severity'] = $filters['severity'];
        }

        $stmt = $this->conn->prepare(
            "SELECT qn.*, u.name as responsible_name
             FROM quality_nonconformities qn
             LEFT JOIN users u ON u.id = qn.responsible_id
             {$where}
             ORDER BY qn.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Resolve non conformity.
  *
  * @param int $id ID do registro
  * @param string $correctiveAction Corrective action
  * @return bool
  */
    public function resolveNonConformity(int $id, string $correctiveAction): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE quality_nonconformities SET status = 'resolved', corrective_action = :action, resolved_at = NOW() WHERE id = :id"
        );
        return $stmt->execute([':id' => $id, ':action' => $correctiveAction]);
    }
}
