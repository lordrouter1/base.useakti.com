<?php
namespace Akti\Models;

// =============================================================================
// Model: CatalogLink
// Responsável por acesso e regras de negócio dos links de catálogo.
// Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
// =============================================================================
use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: CatalogLink
 * Gerencia links de catálogo público vinculados a pedidos.
 * Permite ao cliente visualizar produtos e montar um carrinho.
 */
class CatalogLink {
    private $conn;
    private $table = 'catalog_links';

    /**
     * Construtor do model CatalogLink
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Cria um novo link de catálogo para um pedido
     * @param int $orderId ID do pedido
     * @param bool $showPrices Se os preços devem ser exibidos (default: true)
     * @param string|null $expiresAt Data/hora de expiração (Y-m-d H:i:s) ou null
     * @return array|false Retorna array com dados do link criado ou false em caso de erro
     */
    public function create($orderId, $showPrices = true, $expiresAt = null) {
        // Desativar links anteriores do mesmo pedido
        $this->deactivateByOrder($orderId);

        $token = bin2hex(random_bytes(32)); // 64 chars hex

        $query = "INSERT INTO {$this->table} (order_id, token, show_prices, is_active, expires_at, created_at)
                  VALUES (:order_id, :token, :show_prices, 1, :expires_at, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':token', $token);
        $showPricesInt = $showPrices ? 1 : 0;
        $stmt->bindParam(':show_prices', $showPricesInt, PDO::PARAM_INT);
        $stmt->bindParam(':expires_at', $expiresAt);
        
        if ($stmt->execute()) {
            $newId = $this->conn->lastInsertId();
            EventDispatcher::dispatch('model.catalog_link.created', new Event('model.catalog_link.created', [
                'id' => $newId,
                'order_id' => $orderId,
            ]));
            return [
                'id' => $newId,
                'order_id' => $orderId,
                'token' => $token,
                'show_prices' => $showPricesInt,
                'is_active' => 1,
                'expires_at' => $expiresAt
            ];
        }
        return false;
    }

    /**
     * Busca link ativo por token (validando expiração)
     * @param string $token Token do link de catálogo
     * @return array|null Retorna array com dados do link ou null se não encontrado/expirado
     */
    public function findByToken($token) {
        $query = "SELECT cl.*, o.customer_id, o.total_amount, o.pipeline_stage,
                         c.name as customer_name, c.price_table_id as customer_price_table_id
                  FROM {$this->table} cl
                  JOIN orders o ON cl.order_id = o.id
                  LEFT JOIN customers c ON o.customer_id = c.id
                  WHERE cl.token = :token 
                    AND cl.is_active = 1
                    AND (cl.expires_at IS NULL OR cl.expires_at > NOW())
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca link ativo por pedido
     * @param int $orderId ID do pedido
     * @return array|null Retorna array com dados do link ou null se não encontrado/expirado
     */
    public function findActiveByOrder($orderId) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE order_id = :order_id 
                    AND is_active = 1
                    AND (expires_at IS NULL OR expires_at > NOW())
                  ORDER BY created_at DESC
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Desativa todos os links de um pedido
     * @param int $orderId ID do pedido
     * @return bool Retorna true se desativou com sucesso ou false em caso de falha
     */
    public function deactivateByOrder($orderId) {
        // Verificar se existem links ativos antes de tentar atualizar
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} WHERE order_id = :order_id AND is_active = 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ((int)$checkStmt->fetchColumn() === 0) {
            return true; // Nada para desativar
        }

        $query = "UPDATE {$this->table} SET is_active = 0 WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Desativa um link específico
     * @param int $id ID do link
     * @return bool Retorna true se desativou com sucesso ou false em caso de falha
     */
    public function deactivate($id) {
        $query = "UPDATE {$this->table} SET is_active = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Atualiza configuração de exibição de preços
     * @param int $id ID do link
     * @param bool $showPrices Se os preços devem ser exibidos
     * @return bool Retorna true se atualizado com sucesso
     */
    public function updateShowPrices($id, $showPrices) {
        $query = "UPDATE {$this->table} SET show_prices = :show_prices WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $val = $showPrices ? 1 : 0;
        $stmt->bindParam(':show_prices', $val, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Gera a URL completa do catálogo

    * @param string $token Token do link de catálogo
     * @return string Retorna a URL completa para acesso ao catálogo
     */
    public static function buildUrl($token) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$protocol}://{$host}?page=catalog&token={$token}";
    }
}
