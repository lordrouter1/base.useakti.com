<?php
namespace Akti\Services;

use Akti\Core\Log;

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

    /**
     * Construtor da classe FinancialAuditService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
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
     * @param string|null $reason   Motivo informado pelo usuário (obrigatório em exclusões)
     * @return bool
     */
    public function log(
        string $entityType,
        int $entityId,
        string $action,
        array $newValues = [],
        array $oldValues = [],
        ?int $userId = null,
        ?string $reason = null
    ): bool {
        if (!$this->ensureTableExists()) {
            return false;
        }

        $userId = $userId ?? ($_SESSION['user_id'] ?? null);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        // Verifica se a coluna reason existe (adicionada na migração 202506151200)
        $hasReasonCol = $this->hasReasonColumn();

        try {
            if ($hasReasonCol) {
                $sql = "INSERT INTO financial_audit_log (entity_type, entity_id, action, old_values, new_values, reason, user_id, ip_address)
                        VALUES (:entity_type, :entity_id, :action, :old_values, :new_values, :reason, :user_id, :ip_address)";
            } else {
                $sql = "INSERT INTO financial_audit_log (entity_type, entity_id, action, old_values, new_values, user_id, ip_address)
                        VALUES (:entity_type, :entity_id, :action, :old_values, :new_values, :user_id, :ip_address)";
            }

            $stmt = $this->db->prepare($sql);
            $params = [
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
                ':action'      => $action,
                ':old_values'  => !empty($oldValues) ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                ':new_values'  => !empty($newValues) ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                ':user_id'     => $userId,
                ':ip_address'  => $ipAddress,
            ];
            if ($hasReasonCol) {
                $params[':reason'] = $reason;
            }
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            // Auditoria não deve quebrar o fluxo principal
            Log::error('FinancialAudit: Erro ao gravar log', ['exception' => $e->getMessage()]);
            return false;
        }
    }

    // ═══════════════════════════════════════════
    // Atalhos por tipo de entidade
    // ═══════════════════════════════════════════

    /**
     * Log de ação em parcela (installment).
     */
    public function logInstallment(int $id, string $action, array $data = [], array $oldData = [], ?int $userId = null, ?string $reason = null): bool
    {
        return $this->log('installment', $id, $action, $data, $oldData, $userId, $reason);
    }

    /**
     * Log de ação em transação financeira.
     */
    public function logTransaction(int $id, string $action, array $data = [], array $oldData = [], ?int $userId = null, ?string $reason = null): bool
    {
        return $this->log('transaction', $id, $action, $data, $oldData, $userId, $reason);
    }

    /**
     * Log de ação em pedido (financial context).
     */
    public function logOrder(int $id, string $action, array $data = [], array $oldData = [], ?int $userId = null, ?string $reason = null): bool
    {
        return $this->log('order', $id, $action, $data, $oldData, $userId, $reason);
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
            Log::error('FinancialAudit: Erro ao consultar histórico', ['exception' => $e->getMessage()]);
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
            Log::error('FinancialAudit: Erro ao consultar recentes', ['exception' => $e->getMessage()]);
            return [];
        }
    }

    // ═══════════════════════════════════════════
    // Consulta paginada para relatório de auditoria
    // ═══════════════════════════════════════════

    /**
     * Retorna registros de auditoria com paginação e filtros para o relatório.
     *
     * @param array $filters  Filtros: entity_type, action, user_id, date_from, date_to, search
     * @param int   $page
     * @param int   $perPage
     * @return array ['data' => [...], 'total' => int, 'page' => int, 'perPage' => int, 'totalPages' => int]
     */
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $empty = ['data' => [], 'total' => 0, 'page' => $page, 'perPage' => $perPage, 'totalPages' => 0];

        if (!$this->ensureTableExists()) {
            return $empty;
        }

        try {
            $where = "WHERE 1=1";
            $params = [];

            if (!empty($filters['entity_type'])) {
                $where .= " AND fal.entity_type = :etype";
                $params[':etype'] = $filters['entity_type'];
            }
            if (!empty($filters['action'])) {
                $where .= " AND fal.action = :action";
                $params[':action'] = $filters['action'];
            }
            if (!empty($filters['user_id'])) {
                $where .= " AND fal.user_id = :uid";
                $params[':uid'] = (int) $filters['user_id'];
            }
            if (!empty($filters['date_from'])) {
                $where .= " AND DATE(fal.created_at) >= :dfrom";
                $params[':dfrom'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where .= " AND DATE(fal.created_at) <= :dto";
                $params[':dto'] = $filters['date_to'];
            }
            if (!empty($filters['search'])) {
                $where .= " AND (fal.old_values LIKE :search OR fal.new_values LIKE :search2 OR fal.action LIKE :search3)";
                $params[':search']  = '%' . $filters['search'] . '%';
                $params[':search2'] = '%' . $filters['search'] . '%';
                $params[':search3'] = '%' . $filters['search'] . '%';
            }

            // Count
            $countSql = "SELECT COUNT(*) FROM financial_audit_log fal $where";
            $countStmt = $this->db->prepare($countSql);
            foreach ($params as $k => $v) {
                $countStmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();

            $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;

            $hasReason = $this->hasReasonColumn();
            $reasonCol = $hasReason ? ', fal.reason' : '';

            // Data
            $dataSql = "SELECT fal.id, fal.entity_type, fal.entity_id, fal.action,
                               fal.old_values, fal.new_values{$reasonCol},
                               fal.user_id, fal.ip_address, fal.created_at,
                               u.name as user_name
                        FROM financial_audit_log fal
                        LEFT JOIN users u ON fal.user_id = u.id
                        $where
                        ORDER BY fal.created_at DESC
                        LIMIT :lim OFFSET :off";

            $dataStmt = $this->db->prepare($dataSql);
            foreach ($params as $k => $v) {
                $dataStmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $dataStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
            $dataStmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $dataStmt->execute();

            return [
                'data'       => $dataStmt->fetchAll(PDO::FETCH_ASSOC),
                'total'      => $total,
                'page'       => $page,
                'perPage'    => $perPage,
                'totalPages' => $totalPages,
            ];
        } catch (\PDOException $e) {
            Log::error('FinancialAudit: Erro ao consultar paginado', ['exception' => $e->getMessage()]);
            return $empty;
        }
    }

    /**
     * Exporta log de auditoria em CSV.
     *
     * @param array $filters
     * @return string CSV content
     */
    public function exportCsv(array $filters = []): string
    {
        // Buscar todos os registros (sem paginação)
        $result = $this->getPaginated($filters, 1, 10000);

        $actionLabels = [
            'created'   => 'Criado',
            'updated'   => 'Atualizado',
            'deleted'   => 'Excluído',
            'paid'      => 'Pago',
            'confirmed' => 'Confirmado',
            'cancelled' => 'Cancelado',
            'reversed'  => 'Estornado',
        ];

        $entityLabels = [
            'transaction' => 'Transação',
            'installment' => 'Parcela',
            'order'       => 'Pedido',
            'recurring'   => 'Recorrência',
        ];

        $output = fopen('php://temp', 'r+');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
        fputcsv($output, ['Data/Hora', 'Entidade', 'ID', 'Ação', 'Motivo', 'Usuário', 'IP', 'Valores Anteriores', 'Valores Novos'], ';');

        foreach ($result['data'] as $row) {
            fputcsv($output, [
                $row['created_at'] ?? '',
                $entityLabels[$row['entity_type']] ?? $row['entity_type'],
                $row['entity_id'],
                $actionLabels[$row['action']] ?? $row['action'],
                $row['reason'] ?? '',
                $row['user_name'] ?? ('User #' . ($row['user_id'] ?? '?')),
                $row['ip_address'] ?? '',
                $row['old_values'] ?? '',
                $row['new_values'] ?? '',
            ], ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    // ═══════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════

    /** @var bool|null Cache estático para coluna reason */
    private static ?bool $hasReason = null;

    /**
     * Verifica se a coluna reason existe em financial_audit_log.
     */
    public function hasReasonColumn(): bool
    {
        if (self::$hasReason !== null) {
            return self::$hasReason;
        }

        try {
            $dbname = $this->db->query("SELECT DATABASE()")->fetchColumn();
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'financial_audit_log' AND COLUMN_NAME = 'reason'"
            );
            $stmt->execute([':db' => $dbname]);
            self::$hasReason = (int) $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            self::$hasReason = false;
        }

        return self::$hasReason;
    }

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
