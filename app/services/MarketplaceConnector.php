<?php

namespace Akti\Services;

/**
 * MarketplaceConnector — Integração com marketplaces externos.
 * FEAT-014: Integração com Marketplaces
 *
 * Classe base para conectores de marketplace (Mercado Livre, Shopee, etc.)
 * Cada marketplace deve estender esta classe e implementar os métodos abstratos.
 */
abstract class MarketplaceConnector
{
    protected \PDO $db;
    protected int $tenantId;
    protected array $config;

    public function __construct(\PDO $db, int $tenantId, array $config = [])
    {
        $this->db = $db;
        $this->tenantId = $tenantId;
        $this->config = $config;
    }

    /**
     * Autenticar com a API do marketplace (OAuth2 ou API key).
     */
    abstract public function authenticate(): bool;

    /**
     * Sincronizar produtos: enviar catálogo local → marketplace.
     */
    abstract public function syncProducts(array $productIds = []): array;

    /**
     * Importar pedidos do marketplace → sistema local.
     */
    abstract public function importOrders(string $since = ''): array;

    /**
     * Atualizar status de um pedido no marketplace.
     */
    abstract public function updateOrderStatus(int $orderId, string $status): bool;

    /**
     * Sincronizar estoque local → marketplace.
     */
    abstract public function syncStock(array $productIds = []): array;

    /**
     * Retorna o nome do conector.
     */
    abstract public function getName(): string;

    /**
     * Log de operação do marketplace.
     */
    protected function log(string $action, string $message, string $level = 'info'): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO audit_logs (tenant_id, user_id, entity_type, entity_id, action, old_values, new_values, ip_address, created_at)
             VALUES (:tenant_id, 0, 'marketplace', 0, :action, NULL, :message, :ip, NOW())"
        );
        $stmt->execute([
            ':tenant_id' => $this->tenantId,
            ':action'    => 'marketplace.' . $this->getName() . '.' . $action,
            ':message'   => json_encode(['level' => $level, 'message' => $message]),
            ':ip'        => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    }
}
