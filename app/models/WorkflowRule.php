<?php

namespace Akti\Models;

use PDO;

/**
 * Model de regras de workflow automatizado.
 */
class WorkflowRule
{
    private PDO $conn;

    /**
     * Construtor da classe WorkflowRule.
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
        $stmt = $this->conn->prepare(
            "SELECT * FROM workflow_rules ORDER BY priority ASC, name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Read active.
     * @return array
     */
    public function readActive(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM workflow_rules WHERE is_active = 1 ORDER BY priority ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Read by event.
     *
     * @param string $event Event
     * @return array
     */
    public function readByEvent(string $event): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM workflow_rules WHERE event = :event AND is_active = 1 ORDER BY priority ASC"
        );
        $stmt->execute([':event' => $event]);
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
        $stmt = $this->conn->prepare("SELECT * FROM workflow_rules WHERE id = :id");
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
            "INSERT INTO workflow_rules (tenant_id, name, description, event, conditions, actions, is_active, priority, created_by)
             VALUES (:tenant_id, :name, :description, :event, :conditions, :actions, :is_active, :priority, :created_by)"
        );
        $stmt->execute([
            ':tenant_id'   => $data['tenant_id'],
            ':name'        => $data['name'],
            ':description' => $data['description'] ?? null,
            ':event'       => $data['event'],
            ':conditions'  => json_encode($data['conditions'] ?? []),
            ':actions'     => json_encode($data['actions'] ?? []),
            ':is_active'   => $data['is_active'] ?? 1,
            ':priority'    => $data['priority'] ?? 0,
            ':created_by'  => $data['created_by'] ?? null,
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
            "UPDATE workflow_rules SET
                name = :name, description = :description, event = :event,
                conditions = :conditions, actions = :actions,
                is_active = :is_active, priority = :priority
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'          => $id,
            ':name'        => $data['name'],
            ':description' => $data['description'] ?? null,
            ':event'       => $data['event'],
            ':conditions'  => json_encode($data['conditions'] ?? []),
            ':actions'     => json_encode($data['actions'] ?? []),
            ':is_active'   => $data['is_active'] ?? 1,
            ':priority'    => $data['priority'] ?? 0,
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
        $stmt = $this->conn->prepare("DELETE FROM workflow_rules WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Alterna estado de propriedade.
     *
     * @param int $id ID do registro
     * @return bool
     */
    public function toggle(int $id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE workflow_rules SET is_active = NOT is_active WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Registra informação no log.
     *
     * @param array $data Dados para processamento
     * @return int
     */
    public function logExecution(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO workflow_logs (tenant_id, rule_id, event, entity_type, entity_id, conditions_met, actions_executed, status, error_message)
             VALUES (:tenant_id, :rule_id, :event, :entity_type, :entity_id, :conditions_met, :actions_executed, :status, :error_message)"
        );
        $stmt->execute([
            ':tenant_id'        => $data['tenant_id'],
            ':rule_id'          => $data['rule_id'],
            ':event'            => $data['event'],
            ':entity_type'      => $data['entity_type'] ?? null,
            ':entity_id'        => $data['entity_id'] ?? null,
            ':conditions_met'   => json_encode($data['conditions_met'] ?? []),
            ':actions_executed' => json_encode($data['actions_executed'] ?? []),
            ':status'           => $data['status'] ?? 'success',
            ':error_message'    => $data['error_message'] ?? null,
        ]);

        $this->conn->prepare(
            "UPDATE workflow_rules SET last_triggered_at = NOW(), trigger_count = trigger_count + 1 WHERE id = :id"
        )->execute([':id' => $data['rule_id']]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Obtém dados específicos.
     *
     * @param int $ruleId Rule id
     * @param int $limit Limite de registros
     * @return array
     */
    public function getLogs(int $ruleId, int $limit = 50): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM workflow_logs WHERE rule_id = :rule_id ORDER BY created_at DESC LIMIT :limit"
        );
        $stmt->bindValue(':rule_id', $ruleId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update priority.
     *
     * @param int $id ID do registro
     * @param int $priority Priority
     * @return bool
     */
    public function updatePriority(int $id, int $priority): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE workflow_rules SET priority = :priority WHERE id = :id"
        );
        return $stmt->execute([':priority' => $priority, ':id' => $id]);
    }
}
