<?php
namespace Akti\Models;
use PDO;

/**
 * Model: CatalogLink
 * Gerencia links de catálogo público vinculados a pedidos.
 * Permite ao cliente visualizar produtos e montar um carrinho.
 */
class CatalogLink {
    private $conn;
    private $table = 'catalog_links';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Cria um novo link de catálogo para um pedido
     * @return array|false Dados do link criado ou false
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
            return [
                'id' => $this->conn->lastInsertId(),
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
     */
    public function deactivate($id) {
        $query = "UPDATE {$this->table} SET is_active = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Atualiza configuração de exibição de preços
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
     */
    public static function buildUrl($token) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$protocol}://{$host}?page=catalog&token={$token}";
    }
}
