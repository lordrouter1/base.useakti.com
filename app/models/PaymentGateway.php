<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: PaymentGateway
 * CRUD para configuração de gateways de pagamento e log de transações.
 *
 * Tabelas: payment_gateways, payment_gateway_transactions
 *
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, booleanos, IDs inseridos.
 * Eventos: 'model.payment_gateway.updated', 'model.gateway_transaction.created'
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class PaymentGateway
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ══════════════════════════════════════════════════════════════
    // CRUD — payment_gateways
    // ══════════════════════════════════════════════════════════════

    /**
     * Retorna todos os gateways cadastrados.
     * @return array
     */
    public function readAll(): array
    {
        $q = "SELECT * FROM payment_gateways ORDER BY display_name ASC";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna um gateway pelo ID.
     * @param int $id
     * @return array|false
     */
    public function readOne(int $id)
    {
        $q = "SELECT * FROM payment_gateways WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id]);
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna um gateway pelo slug.
     * @param string $slug
     * @return array|false
     */
    public function readBySlug(string $slug)
    {
        $q = "SELECT * FROM payment_gateways WHERE gateway_slug = :slug";
        $s = $this->conn->prepare($q);
        $s->execute([':slug' => $slug]);
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna o gateway padrão (is_default=1 e is_active=1).
     * @return array|false
     */
    public function getDefault()
    {
        $q = "SELECT * FROM payment_gateways WHERE is_default = 1 AND is_active = 1 LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todos os gateways ativos.
     * @return array
     */
    public function getActive(): array
    {
        $q = "SELECT * FROM payment_gateways WHERE is_active = 1 ORDER BY is_default DESC, display_name ASC";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza configurações de um gateway (credenciais, settings, ambiente, status).
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'display_name', 'is_active', 'is_default', 'environment',
            'credentials', 'settings_json', 'webhook_secret',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        // Se estiver marcando como default, desmarcar os outros
        if (isset($data['is_default']) && $data['is_default']) {
            $this->conn->exec("UPDATE payment_gateways SET is_default = 0");
        }

        $q = "UPDATE payment_gateways SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $s = $this->conn->prepare($q);
        $result = $s->execute($params);

        if ($result) {
            EventDispatcher::dispatch('model.payment_gateway.updated', new Event('model.payment_gateway.updated', [
                'id'   => $id,
                'data' => array_diff_key($data, ['credentials' => 1]), // Não logar credenciais
            ]));
        }

        return $result;
    }

    /**
     * Atualiza apenas as credenciais (JSON).
     * @param int    $id
     * @param array  $credentials Array associativo de credenciais
     * @return bool
     */
    public function updateCredentials(int $id, array $credentials): bool
    {
        $q = "UPDATE payment_gateways SET credentials = :cred, updated_at = NOW() WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([
            ':cred' => json_encode($credentials),
            ':id'   => $id,
        ]);
    }

    /**
     * Atualiza apenas as settings (JSON).
     * @param int   $id
     * @param array $settings
     * @return bool
     */
    public function updateSettings(int $id, array $settings): bool
    {
        $q = "UPDATE payment_gateways SET settings_json = :settings, updated_at = NOW() WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([
            ':settings' => json_encode($settings),
            ':id'       => $id,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // CRUD — payment_gateway_transactions (log)
    // ══════════════════════════════════════════════════════════════

    /**
     * Registra uma transação de gateway (webhook ou criação de cobrança).
     * @param array $data
     * @return int ID da transação criada
     */
    public function logTransaction(array $data): int
    {
        $q = "INSERT INTO payment_gateway_transactions
              (gateway_slug, installment_id, order_id, external_id, external_status,
               amount, currency, payment_method_type, raw_payload, event_type, processed_at)
              VALUES
              (:gateway_slug, :installment_id, :order_id, :external_id, :external_status,
               :amount, :currency, :payment_method_type, :raw_payload, :event_type, :processed_at)";

        $s = $this->conn->prepare($q);
        $s->execute([
            ':gateway_slug'        => $data['gateway_slug'] ?? '',
            ':installment_id'      => $data['installment_id'] ?? null,
            ':order_id'            => $data['order_id'] ?? null,
            ':external_id'         => $data['external_id'] ?? null,
            ':external_status'     => $data['external_status'] ?? null,
            ':amount'              => $data['amount'] ?? 0,
            ':currency'            => $data['currency'] ?? 'BRL',
            ':payment_method_type' => $data['payment_method_type'] ?? null,
            ':raw_payload'         => is_array($data['raw_payload'] ?? null) ? json_encode($data['raw_payload']) : ($data['raw_payload'] ?? null),
            ':event_type'          => $data['event_type'] ?? null,
            ':processed_at'        => $data['processed_at'] ?? date('Y-m-d H:i:s'),
        ]);

        $txId = (int) $this->conn->lastInsertId();

        EventDispatcher::dispatch('model.gateway_transaction.created', new Event('model.gateway_transaction.created', [
            'id'            => $txId,
            'gateway_slug'  => $data['gateway_slug'] ?? '',
            'external_id'   => $data['external_id'] ?? '',
            'event_type'    => $data['event_type'] ?? '',
        ]));

        return $txId;
    }

    /**
     * Busca transações de gateway por external_id.
     * @param string $externalId
     * @return array
     */
    public function getTransactionsByExternalId(string $externalId): array
    {
        $q = "SELECT * FROM payment_gateway_transactions WHERE external_id = :eid ORDER BY created_at DESC";
        $s = $this->conn->prepare($q);
        $s->execute([':eid' => $externalId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca transações de gateway por installment_id.
     * @param int $installmentId
     * @return array
     */
    public function getTransactionsByInstallment(int $installmentId): array
    {
        $q = "SELECT * FROM payment_gateway_transactions WHERE installment_id = :iid ORDER BY created_at DESC";
        $s = $this->conn->prepare($q);
        $s->execute([':iid' => $installmentId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca transações de gateway por order_id.
     * @param int $orderId
     * @return array
     */
    public function getTransactionsByOrder(int $orderId): array
    {
        $q = "SELECT * FROM payment_gateway_transactions WHERE order_id = :oid ORDER BY created_at DESC";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca transações recentes (para dashboard/log).
     * @param int $limit
     * @return array
     */
    public function getRecentTransactions(int $limit = 50): array
    {
        $q = "SELECT pgt.*, pg.display_name as gateway_name
              FROM payment_gateway_transactions pgt
              LEFT JOIN payment_gateways pg ON pgt.gateway_slug = pg.gateway_slug
              ORDER BY pgt.created_at DESC
              LIMIT :lim";
        $s = $this->conn->prepare($q);
        $s->bindValue(':lim', $limit, PDO::PARAM_INT);
        $s->execute();
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }
}
