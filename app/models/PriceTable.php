<?php
namespace Akti\Models;
use PDO;

/**
 * Model: PriceTable
 * Gerencia tabelas de preço e seus itens
 */
class PriceTable {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Conta o total de tabelas de preço cadastradas
     */
    public function countAll() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM price_tables");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Lista todas as tabelas de preço
     */
    public function readAll() {
        $stmt = $this->conn->query("SELECT pt.*, 
            (SELECT COUNT(*) FROM price_table_items WHERE price_table_id = pt.id) as item_count 
            FROM price_tables pt ORDER BY pt.is_default DESC, pt.name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lê uma tabela de preço
     */
    public function readOne($id) {
        $stmt = $this->conn->prepare("SELECT * FROM price_tables WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria tabela de preço
     */
    public function create($name, $description = '') {
        $stmt = $this->conn->prepare("INSERT INTO price_tables (name, description) VALUES (:name, :desc)");
        $stmt->execute([':name' => $name, ':desc' => $description]);
        return $this->conn->lastInsertId();
    }

    /**
     * Atualiza tabela de preço
     */
    public function update($id, $name, $description = '') {
        $stmt = $this->conn->prepare("UPDATE price_tables SET name = :name, description = :desc WHERE id = :id");
        return $stmt->execute([':name' => $name, ':desc' => $description, ':id' => $id]);
    }

    /**
     * Exclui tabela de preço (se não for padrão)
     */
    public function delete($id) {
        // Não permite excluir a tabela padrão
        $check = $this->conn->prepare("SELECT is_default FROM price_tables WHERE id = :id");
        $check->execute([':id' => $id]);
        $table = $check->fetch(PDO::FETCH_ASSOC);
        if ($table && $table['is_default']) {
            return false;
        }
        // Limpar FK nos customers
        $this->conn->prepare("UPDATE customers SET price_table_id = NULL WHERE price_table_id = :id")->execute([':id' => $id]);
        $stmt = $this->conn->prepare("DELETE FROM price_tables WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Retorna tabela padrão
     */
    public function getDefault() {
        $stmt = $this->conn->query("SELECT * FROM price_tables WHERE is_default = 1 LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ──────── Itens da Tabela de Preço ────────

    /**
     * Retorna itens de uma tabela de preço com dados do produto
     */
    public function getItems($tableId) {
        $stmt = $this->conn->prepare("SELECT pti.*, p.name as product_name, p.price as product_default_price 
            FROM price_table_items pti 
            JOIN products p ON pti.product_id = p.id 
            WHERE pti.price_table_id = :tid 
            ORDER BY p.name ASC");
        $stmt->execute([':tid' => $tableId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Define preço de um produto na tabela
     */
    public function setItemPrice($tableId, $productId, $price) {
        $stmt = $this->conn->prepare("INSERT INTO price_table_items (price_table_id, product_id, price) 
            VALUES (:tid, :pid, :price) 
            ON DUPLICATE KEY UPDATE price = :price2");
        return $stmt->execute([':tid' => $tableId, ':pid' => $productId, ':price' => $price, ':price2' => $price]);
    }

    /**
     * Remove item de uma tabela de preço
     */
    public function removeItem($itemId) {
        $stmt = $this->conn->prepare("DELETE FROM price_table_items WHERE id = :id");
        return $stmt->execute([':id' => $itemId]);
    }

    /**
     * Retorna todos os preços de todas as tabelas para um dado produto
     * Retorna array [price_table_id => price]
     */
    public function getPricesForProduct($productId) {
        $stmt = $this->conn->prepare("SELECT price_table_id, price FROM price_table_items WHERE product_id = :pid");
        $stmt->execute([':pid' => $productId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $prices = [];
        foreach ($rows as $r) {
            $prices[$r['price_table_id']] = $r['price'];
        }
        return $prices;
    }

    /**
     * Salva os preços de um produto em múltiplas tabelas de uma vez.
     * $tablePrices = [price_table_id => price_value, ...]
     * Se o valor for vazio/null, remove o item da tabela (usa preço padrão).
     */
    public function saveProductPrices($productId, $tablePrices) {
        foreach ($tablePrices as $tableId => $price) {
            if ($price === '' || $price === null) {
                // Remover — usa preço padrão do produto
                $stmt = $this->conn->prepare("DELETE FROM price_table_items WHERE price_table_id = :tid AND product_id = :pid");
                $stmt->execute([':tid' => $tableId, ':pid' => $productId]);
            } else {
                $this->setItemPrice($tableId, $productId, (float)$price);
            }
        }
    }

    /**
     * Retorna o preço de um produto para um determinado cliente
     * Prioridade: Tabela do cliente > Tabela padrão > Preço do produto
     */
    public function getProductPriceForCustomer($productId, $customerId) {
        // 1. Verificar se cliente tem tabela de preço atribuída
        $stmt = $this->conn->prepare("SELECT price_table_id FROM customers WHERE id = :cid");
        $stmt->execute([':cid' => $customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $tableId = $customer['price_table_id'] ?? null;

        if ($tableId) {
            // Buscar preço na tabela do cliente
            $stmt2 = $this->conn->prepare("SELECT price FROM price_table_items WHERE price_table_id = :tid AND product_id = :pid");
            $stmt2->execute([':tid' => $tableId, ':pid' => $productId]);
            $item = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($item) return (float)$item['price'];
        }

        // 2. Buscar preço na tabela padrão
        $stmt3 = $this->conn->prepare("SELECT pti.price FROM price_table_items pti 
            JOIN price_tables pt ON pti.price_table_id = pt.id 
            WHERE pt.is_default = 1 AND pti.product_id = :pid");
        $stmt3->execute([':pid' => $productId]);
        $item = $stmt3->fetch(PDO::FETCH_ASSOC);
        if ($item) return (float)$item['price'];

        // 3. Fallback: preço padrão do produto
        $stmt4 = $this->conn->prepare("SELECT price FROM products WHERE id = :pid");
        $stmt4->execute([':pid' => $productId]);
        $product = $stmt4->fetch(PDO::FETCH_ASSOC);
        return $product ? (float)$product['price'] : 0;
    }

    /**
     * Retorna todos os preços para um cliente (para preencher JS no frontend)
     * Retorna array [product_id => price]
     */
    public function getAllPricesForCustomer($customerId) {
        $prices = [];
        
        // Buscar todos os produtos
        $products = $this->conn->query("SELECT id, price FROM products")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as $p) {
            $prices[$p['id']] = (float)$p['price']; // Preço padrão
        }

        // Sobrepor com tabela padrão
        $defaultItems = $this->conn->query("SELECT pti.product_id, pti.price FROM price_table_items pti 
            JOIN price_tables pt ON pti.price_table_id = pt.id WHERE pt.is_default = 1")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($defaultItems as $di) {
            $prices[$di['product_id']] = (float)$di['price'];
        }

        // Sobrepor com tabela do cliente (se houver)
        if ($customerId) {
            $stmt = $this->conn->prepare("SELECT price_table_id FROM customers WHERE id = :cid");
            $stmt->execute([':cid' => $customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!empty($customer['price_table_id'])) {
                $stmt2 = $this->conn->prepare("SELECT product_id, price FROM price_table_items WHERE price_table_id = :tid");
                $stmt2->execute([':tid' => $customer['price_table_id']]);
                $custItems = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                foreach ($custItems as $ci) {
                    $prices[$ci['product_id']] = (float)$ci['price'];
                }
            }
        }

        return $prices;
    }
}
