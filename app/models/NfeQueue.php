<?php
namespace Akti\Models;

use PDO;

/**
 * Model: NfeQueue
 * CRUD para fila de emissão assíncrona de NF-e (tabela nfe_queue).
 *
 * @package Akti\Models
 */
class NfeQueue
{
    private $conn;
    private $table = 'nfe_queue';

    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Adiciona item à fila.
     * @param array $data
     * @return int ID do item na fila
     */
    public function enqueue(array $data): int
    {
        $q = "INSERT INTO {$this->table}
              (order_id, modelo, status, priority, max_attempts, scheduled_at, user_id)
              VALUES
              (:order_id, :modelo, 'pending', :priority, :max_attempts, :scheduled_at, :user_id)";

        $s = $this->conn->prepare($q);
        $s->execute([
            ':order_id'     => $data['order_id'],
            ':modelo'       => $data['modelo'] ?? 55,
            ':priority'     => $data['priority'] ?? 5,
            ':max_attempts' => $data['max_attempts'] ?? 3,
            ':scheduled_at' => $data['scheduled_at'] ?? null,
            ':user_id'      => $data['user_id'] ?? ($_SESSION['user_id'] ?? null),
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Adiciona múltiplos itens à fila (emissão em lote).
     * @param array  $orderIds
     * @param string $batchId
     * @param int    $modelo
     * @return int Quantidade enfileirada
     */
    public function enqueueBatch(array $orderIds, string $batchId, int $modelo = 55): int
    {
        $count = 0;
        $q = "INSERT INTO {$this->table}
              (order_id, modelo, status, priority, max_attempts, user_id, batch_id)
              VALUES
              (:order_id, :modelo, 'pending', 5, 3, :user_id, :batch_id)";

        $s = $this->conn->prepare($q);
        $userId = $_SESSION['user_id'] ?? null;

        foreach ($orderIds as $idx => $orderId) {
            $s->execute([
                ':order_id'  => (int) $orderId,
                ':modelo'    => $modelo,
                ':user_id'   => $userId,
                ':batch_id'  => $batchId,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Busca o próximo item pendente para processamento.
     * Usa SELECT FOR UPDATE para evitar concorrência.
     * @return array|false
     */
    public function fetchNext()
    {
        $q = "SELECT * FROM {$this->table}
              WHERE status = 'pending'
                AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                AND attempts < max_attempts
              ORDER BY priority ASC, id ASC
              LIMIT 1
              FOR UPDATE";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Marca item como em processamento.
     * @param int $id
     * @return bool
     */
    public function markProcessing(int $id): bool
    {
        $q = "UPDATE {$this->table}
              SET status = 'processing', started_at = NOW(), attempts = attempts + 1, updated_at = NOW()
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':id' => $id]);
    }

    /**
     * Marca item como concluído.
     * @param int      $id
     * @param int|null $nfeDocumentId
     * @return bool
     */
    public function markCompleted(int $id, ?int $nfeDocumentId = null): bool
    {
        $q = "UPDATE {$this->table}
              SET status = 'completed', completed_at = NOW(), nfe_document_id = :doc_id, error_message = NULL, updated_at = NOW()
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':id' => $id, ':doc_id' => $nfeDocumentId]);
    }

    /**
     * Marca item como falho.
     * @param int    $id
     * @param string $errorMessage
     * @return bool
     */
    public function markFailed(int $id, string $errorMessage): bool
    {
        // Se atingiu max_attempts, manter como failed; senão, voltar para pending (retry)
        $item = $this->readOne($id);
        $newStatus = ($item && $item['attempts'] >= $item['max_attempts']) ? 'failed' : 'pending';

        $q = "UPDATE {$this->table}
              SET status = :status, error_message = :error, updated_at = NOW()
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':id' => $id, ':status' => $newStatus, ':error' => $errorMessage]);
    }

    /**
     * Cancela um item da fila.
     * @param int $id
     * @return bool
     */
    public function cancel(int $id): bool
    {
        $q = "UPDATE {$this->table} SET status = 'cancelled', updated_at = NOW() WHERE id = :id AND status IN ('pending','failed')";
        $s = $this->conn->prepare($q);
        return $s->execute([':id' => $id]);
    }

    /**
     * Retorna um item da fila pelo ID.
     * @param int $id
     * @return array|false
     */
    public function readOne(int $id)
    {
        $q = "SELECT * FROM {$this->table} WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id]);
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Listagem paginada.
     * @param array $filters  status, search
     * @param int   $page
     * @param int   $perPage
     * @return array
     */
    public function readPaginated(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "q.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['batch_id'])) {
            $where[] = "q.batch_id = :batch_id";
            $params[':batch_id'] = $filters['batch_id'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        $qCount = "SELECT COUNT(*) FROM {$this->table} q {$whereClause}";
        $sCount = $this->conn->prepare($qCount);
        $sCount->execute($params);
        $total = (int) $sCount->fetchColumn();

        $q = "SELECT q.*, o.id as order_num, c.name as customer_name
              FROM {$this->table} q
              LEFT JOIN orders o ON q.order_id = o.id
              LEFT JOIN customers c ON o.customer_id = c.id
              {$whereClause}
              ORDER BY q.id DESC
              LIMIT :limit OFFSET :offset";
        $s = $this->conn->prepare($q);
        foreach ($params as $k => $v) {
            $s->bindValue($k, $v);
        }
        $s->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $s->bindValue(':offset', $offset, PDO::PARAM_INT);
        $s->execute();

        return [
            'data'  => $s->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }

    /**
     * Contagem por status.
     * @return array
     */
    public function countByStatus(): array
    {
        $q = "SELECT status, COUNT(*) as cnt FROM {$this->table} GROUP BY status";
        $s = $this->conn->prepare($q);
        $s->execute();
        $result = [];
        while ($row = $s->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['status']] = (int) $row['cnt'];
        }
        return $result;
    }

    /**
     * Lista itens de um lote específico.
     * @param string $batchId
     * @return array
     */
    public function getByBatch(string $batchId): array
    {
        $q = "SELECT q.*, o.id as order_num, c.name as customer_name
              FROM {$this->table} q
              LEFT JOIN orders o ON q.order_id = o.id
              LEFT JOIN customers c ON o.customer_id = c.id
              WHERE q.batch_id = :batch_id
              ORDER BY q.created_at ASC";
        $s = $this->conn->prepare($q);
        $s->execute([':batch_id' => $batchId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista lotes distintos com contagens e progresso.
     * @param int $limit
     * @return array
     */
    public function listBatches(int $limit = 20): array
    {
        $q = "SELECT 
                  batch_id,
                  COUNT(*) AS total,
                  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                  SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                  SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing,
                  MIN(created_at) AS started_at,
                  MAX(completed_at) AS finished_at
              FROM {$this->table}
              WHERE batch_id IS NOT NULL AND batch_id != ''
              GROUP BY batch_id
              ORDER BY started_at DESC
              LIMIT :lim";
        $s = $this->conn->prepare($q);
        $s->bindValue(':lim', $limit, PDO::PARAM_INT);
        $s->execute();
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }
}
