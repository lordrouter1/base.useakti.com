<?php
namespace Akti\Models;

use PDO;

/**
 * Model: NfeWebhook
 * CRUD para webhooks NF-e e seus logs (tabelas nfe_webhooks e nfe_webhook_logs).
 *
 * @package Akti\Models
 */
class NfeWebhook
{
    private $conn;
    private $table = 'nfe_webhooks';
    private $logsTable = 'nfe_webhook_logs';

    /**
     * Construtor da classe NfeWebhook.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    // ═══════════════════════════════════════════════════════
    // CRUD — Webhooks
    // ═══════════════════════════════════════════════════════

    /**
     * Cria um webhook.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $q = "INSERT INTO {$this->table}
              (name, url, secret, events, headers, is_active, retry_count, timeout_seconds)
              VALUES
              (:name, :url, :secret, :events, :headers, :is_active, :retry_count, :timeout_seconds)";

        $s = $this->conn->prepare($q);
        $s->execute([
            ':name'            => $data['name'],
            ':url'             => $data['url'],
            ':secret'          => $data['secret'] ?? null,
            ':events'          => is_array($data['events']) ? json_encode($data['events']) : $data['events'],
            ':headers'         => isset($data['headers']) ? (is_array($data['headers']) ? json_encode($data['headers']) : $data['headers']) : null,
            ':is_active'       => $data['is_active'] ?? 1,
            ':retry_count'     => $data['retry_count'] ?? 3,
            ':timeout_seconds' => $data['timeout_seconds'] ?? 10,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Retorna um webhook pelo ID.
     * @param int $id
     * @return array|false
     */
    public function readOne(int $id): array|false
    {
        $q = "SELECT * FROM {$this->table} WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['events'] = json_decode($row['events'] ?? '[]', true);
            $row['headers'] = json_decode($row['headers'] ?? '{}', true);
        }
        return $row;
    }

    /**
     * Retorna todos os webhooks.
     * @param bool $onlyActive
     * @return array
     */
    public function readAll(bool $onlyActive = false): array
    {
        $q = "SELECT * FROM {$this->table}" . ($onlyActive ? " WHERE is_active = 1" : "") . " ORDER BY id";
        $s = $this->conn->prepare($q);
        $s->execute();
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['events'] = json_decode($row['events'] ?? '[]', true);
            $row['headers'] = json_decode($row['headers'] ?? '{}', true);
        }
        return $rows;
    }

    /**
     * Retorna webhooks ativos que escutam um evento específico.
     * @param string $eventName
     * @return array
     */
    public function getByEvent(string $eventName): array
    {
        $all = $this->readAll(true);
        return array_filter($all, function ($wh) use ($eventName) {
            return in_array($eventName, $wh['events'] ?? []) || in_array('*', $wh['events'] ?? []);
        });
    }

    /**
     * Atualiza um webhook.
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['name', 'url', 'secret', 'events', 'headers', 'is_active', 'retry_count', 'timeout_seconds'];
        $fields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if (in_array($field, ['events', 'headers']) && is_array($value)) {
                    $value = json_encode($value);
                }
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }
        }

        if (empty($fields)) return false;

        $q = "UPDATE {$this->table} SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute($params);
    }

    /**
     * Exclui um webhook e seus logs.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $q = "DELETE FROM {$this->table} WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':id' => $id]);
    }

    // ═══════════════════════════════════════════════════════
    // Logs de webhook
    // ═══════════════════════════════════════════════════════

    /**
     * Registra uma entrega de webhook.
     * @param array $data
     * @return int
     */
    public function logDelivery(array $data): int
    {
        $q = "INSERT INTO {$this->logsTable}
              (webhook_id, event_name, payload, response_code, response_body, status, attempt, error_message)
              VALUES
              (:webhook_id, :event_name, :payload, :response_code, :response_body, :status, :attempt, :error_message)";

        $s = $this->conn->prepare($q);
        $s->execute([
            ':webhook_id'    => $data['webhook_id'],
            ':event_name'    => $data['event_name'],
            ':payload'       => $data['payload'] ?? null,
            ':response_code' => $data['response_code'] ?? null,
            ':response_body' => $data['response_body'] ?? null,
            ':status'        => $data['status'] ?? 'pending',
            ':attempt'       => $data['attempt'] ?? 1,
            ':error_message' => $data['error_message'] ?? null,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Retorna logs de um webhook paginados.
     * @param int $webhookId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getLogs(int $webhookId, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;

        $qCount = "SELECT COUNT(*) FROM {$this->logsTable} WHERE webhook_id = :wid";
        $sCount = $this->conn->prepare($qCount);
        $sCount->execute([':wid' => $webhookId]);
        $total = (int) $sCount->fetchColumn();

        $q = "SELECT * FROM {$this->logsTable} WHERE webhook_id = :wid ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $s = $this->conn->prepare($q);
        $s->bindValue(':wid', $webhookId, PDO::PARAM_INT);
        $s->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $s->bindValue(':offset', $offset, PDO::PARAM_INT);
        $s->execute();

        return [
            'data'  => $s->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }
}
