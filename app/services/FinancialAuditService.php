<?php
namespace Akti\Services;

use PDO;

/**
 * FinancialAuditService — Serviço de auditoria para o módulo financeiro.
 *
 * Grava registros na tabela `financial_audit_log` para rastreamento
 * de alterações em parcelas, transações e pedidos.
 *
 * Uso:
 *   Chamado pelos listeners de eventos em app/bootstrap/events.php.
 *   Não deve ser usado diretamente pelos controllers.
 *
 * Tabela: financial_audit_log
 *   - entity_type: 'installment', 'transaction', 'order'
 *   - entity_id: ID da entidade afetada
 *   - action: descrição da ação (ex: 'paid', 'confirmed', 'cancelled')
 *   - old_values: JSON dos valores anteriores (quando aplicável)
 *   - new_values: JSON dos novos valores
 *   - user_id: ID do usuário que executou a ação
 *   - ip_address: IP do cliente
 *
 * @package Akti\Services
 */
class FinancialAuditService
{
    private PDO $db;

    /** @var bool Flag para indicar se a tabela de auditoria existe */
    private static ?bool $tableExists = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Registra uma entrada de auditoria.
     *
     * @param string   $entityType  Tipo da entidade: 'installment', 'transaction', 'order'
     * @param int      $entityId    ID da entidade
     * @param string   $action      Ação executada (ex: 'created', 'paid', 'confirmed', 'cancelled', 'updated', 'deleted')
     * @param array    $newValues   Valores novos / dados do evento
     * @param array    $oldValues   Valores anteriores (opcional)
     * @param int|null $userId      ID do usuário (usa session se null)
     * @return bool
     */
    public function log(
        string $entityType,
        int $entityId,
        string $action,
        array $newValues = [],
        array $oldValues = [],
        ?int $userId = null
    ): bool {
        if (!$this->ensureTableExists()) {
            return false;
        }

        $userId = $userId ?? ($_SESSION['user_id'] ?? null);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        try {
            $sql = "INSERT INTO financial_audit_log (entity_type, entity_id, action, old_values, new_values, user_id, ip_address)
                    VALUES (:entity_type, :entity_id, :action, :old_values, :new_values, :user_id, :ip_address)";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
                ':action'      => $action,
                ':old_values'  => !empty($oldValues) ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                ':new_values'  => !empty($newValues) ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                ':user_id'     => $userId,
                ':ip_address'  => $ipAddress,
            ]);
        } catch (\PDOException $e) {
            // Auditoria não deve quebrar o fluxo principal
            error_log('[FinancialAudit] Erro ao gravar log: ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════
    // Atalhos por tipo de entidade
    // ═══════════════════════════════════════════

    /**
     * Log de ação em parcela (installment).
     */
    public function logInstallment(int $id, string $action, array $data = [], array $oldData = [], ?int $userId = null): bool
    {
        return $this->log('installment', $id, $action, $data, $oldData, $userId);
    }

    /**
     * Log de ação em transação financeira.
     */
    public function logTransaction(int $id, string $action, array $data = [], array $oldData = [], ?int $userId = null): bool
    {
        return $this->log('transaction', $id, $action, $data, $oldData, $userId);
    }

    /**
     * Log de ação em pedido (financial context).
     */
    public function logOrder(int $id, string $action, array $data = [], array $oldData = [], ?int $userId = null): bool
    {
        return $this->log('order', $id, $action, $data, $oldData, $userId);
    }

    // ═══════════════════════════════════════════
    // Consulta de auditoria
    // ═══════════════════════════════════════════

    /**
     * Retorna histórico de auditoria de uma entidade.
     *
     * @param string $entityType
     * @param int    $entityId
     * @param int    $limit
     * @return array
     */
    public function getHistory(string $entityType, int $entityId, int $limit = 50): array
    {
        if (!$this->ensureTableExists()) {
            return [];
        }

        try {
            $sql = "SELECT fal.*, u.name as user_name
                    FROM financial_audit_log fal
                    LEFT JOIN users u ON fal.user_id = u.id
                    WHERE fal.entity_type = :type AND fal.entity_id = :id
                    ORDER BY fal.created_at DESC
                    LIMIT :lim";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':type', $entityType, PDO::PARAM_STR);
            $stmt->bindValue(':id', $entityId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('[FinancialAudit] Erro ao consultar histórico: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna últimas ações de auditoria do módulo financeiro.
     *
     * @param int $limit
     * @param string|null $entityType Filtrar por tipo
     * @return array
     */
    public function getRecent(int $limit = 100, ?string $entityType = null): array
    {
        if (!$this->ensureTableExists()) {
            return [];
        }

        try {
            $sql = "SELECT fal.*, u.name as user_name
                    FROM financial_audit_log fal
                    LEFT JOIN users u ON fal.user_id = u.id";

            $params = [];
            if ($entityType) {
                $sql .= " WHERE fal.entity_type = :type";
                $params[':type'] = $entityType;
            }

            $sql .= " ORDER BY fal.created_at DESC LIMIT :lim";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('[FinancialAudit] Erro ao consultar recentes: ' . $e->getMessage());
            return [];
        }
    }

    // ═══════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════

    /**
     * Verifica se a tabela financial_audit_log existe (cache estático).
     */
    private function ensureTableExists(): bool
    {
        if (self::$tableExists !== null) {
            return self::$tableExists;
        }

        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'financial_audit_log'");
            self::$tableExists = ($stmt->rowCount() > 0);
        } catch (\PDOException $e) {
            self::$tableExists = false;
        }

        return self::$tableExists;
    }
}
