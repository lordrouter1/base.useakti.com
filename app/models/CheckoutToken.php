<?php

namespace Akti\Models;

use PDO;

class CheckoutToken
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Cria um novo token de checkout.
     * @return int ID inserido
     */
    public function create(array $data): int
    {
        $q = "INSERT INTO checkout_tokens
              (token, order_id, installment_id, gateway_slug, amount, currency,
               allowed_methods, status, customer_name, customer_email, customer_document,
               metadata, expires_at, created_by, tenant_id)
              VALUES
              (:token, :order_id, :installment_id, :gateway_slug, :amount, :currency,
               :allowed_methods, 'active', :customer_name, :customer_email, :customer_document,
               :metadata, :expires_at, :created_by, :tenant_id)";
        $s = $this->conn->prepare($q);
        $s->execute([
            ':token'             => $data['token'],
            ':order_id'          => $data['order_id'],
            ':installment_id'    => $data['installment_id'] ?? null,
            ':gateway_slug'      => $data['gateway_slug'] ?? null,
            ':amount'            => $data['amount'],
            ':currency'          => $data['currency'] ?? 'BRL',
            ':allowed_methods'   => isset($data['allowed_methods']) ? json_encode($data['allowed_methods']) : null,
            ':customer_name'     => $data['customer_name'] ?? null,
            ':customer_email'    => $data['customer_email'] ?? null,
            ':customer_document' => $data['customer_document'] ?? null,
            ':metadata'          => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            ':expires_at'        => $data['expires_at'],
            ':created_by'        => $data['created_by'] ?? null,
            ':tenant_id'         => $data['tenant_id'],
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Busca token pelo hash (com JOIN em orders).
     */
    public function findByToken(string $token): ?array
    {
        $q = "SELECT ct.*,
                     o.total_amount AS order_total_amount,
                     o.customer_id,
                     o.payment_status AS order_payment_status,
                     c.name AS customer_name_order,
                     c.email AS customer_email_order,
                     c.document AS customer_document_order
              FROM checkout_tokens ct
              JOIN orders o ON ct.order_id = o.id
              LEFT JOIN customers c ON o.customer_id = c.id
              WHERE ct.token = :token";
        $s = $this->conn->prepare($q);
        $s->execute([':token' => $token]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Busca token pelo ID.
     */
    public function findById(int $id): ?array
    {
        $q = "SELECT * FROM checkout_tokens WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Lista tokens de um pedido.
     */
    public function findByOrder(int $orderId): array
    {
        $q = "SELECT * FROM checkout_tokens WHERE order_id = :oid ORDER BY created_at DESC";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca token ativo de um pedido.
     */
    public function getActiveByOrder(int $orderId): ?array
    {
        $q = "SELECT * FROM checkout_tokens
              WHERE order_id = :oid AND status = 'active' AND expires_at > NOW()
              ORDER BY created_at DESC LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Marca token como usado (atômico — só atualiza se status='active').
     * @return bool true se atualizou, false se já usado (race condition prevenida)
     */
    public function markUsed(int $id, string $usedMethod = '', string $externalId = ''): bool
    {
        $q = "UPDATE checkout_tokens
              SET status = 'used', used_at = NOW(), used_method = :method, external_id = :ext_id
              WHERE id = :id AND status = 'active'";
        $s = $this->conn->prepare($q);
        $s->execute([
            ':id'     => $id,
            ':method' => $usedMethod,
            ':ext_id' => $externalId,
        ]);
        return $s->rowCount() > 0;
    }

    /**
     * Marca token como usado pelo order_id (para webhooks).
     */
    public function markUsedByOrder(int $orderId, string $usedMethod = '', string $externalId = '', ?int $installmentId = null): bool
    {
        $q = "UPDATE checkout_tokens
              SET status = 'used', used_at = NOW(), used_method = :method, external_id = :ext_id
              WHERE order_id = :oid AND status = 'active'
              AND (installment_id = :iid OR installment_id IS NULL)";
        $s = $this->conn->prepare($q);
        $s->execute([
            ':oid'    => $orderId,
            ':method' => $usedMethod,
            ':ext_id' => $externalId,
            ':iid'    => $installmentId,
        ]);
        return $s->rowCount() > 0;
    }

    /**
     * Marca token como expirado.
     */
    public function markExpired(int $id): bool
    {
        $q = "UPDATE checkout_tokens SET status = 'expired' WHERE id = :id AND status = 'active'";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id]);
        return $s->rowCount() > 0;
    }

    /**
     * Cancela token.
     */
    public function cancel(int $id): bool
    {
        $q = "UPDATE checkout_tokens SET status = 'cancelled' WHERE id = :id AND status = 'active'";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id]);
        return $s->rowCount() > 0;
    }

    /**
     * Expira todos os tokens vencidos.
     * @return int Quantidade expirada
     */
    public function expireAll(): int
    {
        $q = "UPDATE checkout_tokens SET status = 'expired' WHERE status = 'active' AND expires_at < NOW()";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->rowCount();
    }

    /**
     * Grava IP do visitante.
     */
    public function updateIp(int $id, string $ip): bool
    {
        $q = "UPDATE checkout_tokens SET ip_address = :ip WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id, ':ip' => $ip]);
        return $s->rowCount() > 0;
    }
}
