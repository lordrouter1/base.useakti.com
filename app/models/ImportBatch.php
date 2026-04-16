<?php
namespace Akti\Models;

use PDO;

/**
 * Model: ImportBatch
 * Gerencia lotes de importação — rastreamento, progresso e desfazer.
 * Suporta qualquer entity_type (customers, products, etc.).
 */
class ImportBatch
{
    private $conn;
    private $table = 'import_batches';
    private $itemsTable = 'import_batch_items';

    /**
     * Construtor da classe ImportBatch.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Cria um novo lote de importação.
     */
    public function create(array $data): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table}
                (tenant_id, entity_type, file_name, total_rows, import_mode, mapping_json, created_by, status, progress)
            VALUES
                (:tenant_id, :entity_type, :file_name, :total_rows, :import_mode, :mapping_json, :created_by, 'processing', 0)
        ");
        $stmt->execute([
            ':tenant_id'   => $data['tenant_id'] ?? 0,
            ':entity_type' => $data['entity_type'] ?? 'customers',
            ':file_name'   => $data['file_name'] ?? null,
            ':total_rows'  => $data['total_rows'] ?? 0,
            ':import_mode' => $data['import_mode'] ?? 'create',
            ':mapping_json'=> $data['mapping_json'] ?? null,
            ':created_by'  => $data['created_by'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Atualiza o progresso de um lote.
     */
    public function updateProgress(int $batchId, int $progress, int $imported = 0, int $skipped = 0, int $errorCount = 0, int $warningCount = 0): void
    {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET progress = :progress,
                imported_count = :imported,
                skipped_count = :skipped,
                error_count = :errors,
                warning_count = :warnings
            WHERE id = :id
        ");
        $stmt->execute([
            ':progress' => min(100, $progress),
            ':imported' => $imported,
            ':skipped'  => $skipped,
            ':errors'   => $errorCount,
            ':warnings' => $warningCount,
            ':id'       => $batchId,
        ]);
    }

    /**
     * Finaliza um lote (status = completed/failed/completed_with_errors).
     */
    public function finalize(int $batchId, string $status, int $importedCount = 0, int $updatedCount = 0, int $skippedCount = 0, ?string $errorsJson = null, ?string $warningsJson = null): void
    {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET status = :status,
                progress = 100,
                imported_count = :imported,
                updated_count = :updated,
                skipped_count = :skipped,
                errors_json = :errors_json,
                warnings_json = :warnings_json
            WHERE id = :id
        ");
        $stmt->execute([
            ':status'        => $status,
            ':imported'      => $importedCount,
            ':updated'       => $updatedCount,
            ':skipped'       => $skippedCount,
            ':errors_json'   => $errorsJson,
            ':warnings_json' => $warningsJson,
            ':id'            => $batchId,
        ]);
    }

    /**
     * Registra um item importado no lote.
     */
    public function addItem(int $batchId, int $entityId, string $action = 'created', ?string $originalData = null, ?int $lineNumber = null): void
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->itemsTable} (batch_id, entity_id, action, original_data, line_number)
            VALUES (:batch_id, :entity_id, :action, :original_data, :line_number)
        ");
        $stmt->execute([
            ':batch_id'      => $batchId,
            ':entity_id'     => $entityId,
            ':action'        => $action,
            ':original_data' => $originalData,
            ':line_number'   => $lineNumber,
        ]);
    }

    /**
     * Busca um lote por ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Lista lotes de importação do tenant.
     */
    public function listByTenant(int $tenantId, string $entityType = 'customers', int $limit = 20): array
    {
        $stmt = $this->conn->prepare("
            SELECT *, imported_count AS created_count FROM {$this->table}
            WHERE tenant_id = :tenant_id AND entity_type = :entity_type
            ORDER BY created_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':tenant_id', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue(':entity_type', $entityType);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca todos os itens de um lote.
     */
    public function getItems(int $batchId): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->itemsTable}
            WHERE batch_id = :batch_id
            ORDER BY line_number ASC
        ");
        $stmt->execute([':batch_id' => $batchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca apenas itens 'created' de um lote (para undo).
     */
    public function getCreatedItems(int $batchId): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->itemsTable}
            WHERE batch_id = :batch_id AND action = 'created'
            ORDER BY id ASC
        ");
        $stmt->execute([':batch_id' => $batchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca itens de um lote com dados da entidade (join com customers).
     */
    public function getItemsWithEntity(int $batchId, string $entityType = 'customers', int $limit = 200, int $offset = 0): array
    {
        if ($entityType === 'customers') {
            $stmt = $this->conn->prepare("
                SELECT i.id, i.entity_id, i.action, i.line_number,
                       c.name AS entity_name, c.email AS entity_email, c.document AS entity_document
                FROM {$this->itemsTable} i
                LEFT JOIN customers c ON c.id = i.entity_id
                WHERE i.batch_id = :batch_id
                ORDER BY i.line_number ASC
                LIMIT :lim OFFSET :off
            ");
        } else {
            $stmt = $this->conn->prepare("
                SELECT i.id, i.entity_id, i.action, i.line_number,
                       NULL AS entity_name, NULL AS entity_email, NULL AS entity_document
                FROM {$this->itemsTable} i
                WHERE i.batch_id = :batch_id
                ORDER BY i.line_number ASC
                LIMIT :lim OFFSET :off
            ");
        }
        $stmt->bindValue(':batch_id', $batchId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marca lote como desfeito.
     */
    public function markUndone(int $batchId, int $userId): void
    {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET status = 'undone', undone_at = NOW(), undone_by = :user_id
            WHERE id = :id
        ");
        $stmt->execute([':user_id' => $userId, ':id' => $batchId]);
    }

    /**
     * Busca o progresso de um lote (para polling).
     */
    public function getProgress(int $batchId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT id, status, progress, imported_count, skipped_count, error_count, warning_count, total_rows
            FROM {$this->table}
            WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $batchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
