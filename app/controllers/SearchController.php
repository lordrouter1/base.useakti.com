<?php
namespace Akti\Controllers;

use Akti\Models\Customer;
use Akti\Models\Product;
use Akti\Models\Order;

/**
 * SearchController
 *
 * Endpoint AJAX para busca global (Command Palette / Ctrl+K).
 * Busca em clientes, produtos, pedidos e páginas do sistema.
 */
class SearchController
{
    /** @var \PDO */
    private $db;

    public function __construct()
    {
        $database = new \Database();
        $this->db = $database->getConnection();
    }

    /**
     * Busca global AJAX — retorna JSON com resultados agrupados.
     */
    public function query(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->jsonError('Não autenticado.', 401);
        }

        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'results' => []]);
            exit;
        }

        $limit = min((int) ($_GET['limit'] ?? 5), 10);
        $results = [];

        // 1. Buscar Clientes
        try {
            $results = array_merge($results, $this->searchCustomers($q, $limit));
        } catch (\Throwable $e) {
            // Silently skip if model doesn't exist or table missing
        }

        // 2. Buscar Produtos
        try {
            $results = array_merge($results, $this->searchProducts($q, $limit));
        } catch (\Throwable $e) {
            // Silently skip
        }

        // 3. Buscar Pedidos
        try {
            $results = array_merge($results, $this->searchOrders($q, $limit));
        } catch (\Throwable $e) {
            // Silently skip
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'results' => $results,
            'query'   => $q,
        ]);
        exit;
    }

    /**
     * Busca clientes pelo nome, email, telefone ou documento.
     */
    private function searchCustomers(string $q, int $limit): array
    {
        $sql = "SELECT id, name, email, phone, document 
                FROM customers 
                WHERE (name LIKE :q OR email LIKE :q2 OR phone LIKE :q3 OR document LIKE :q4) 
                ORDER BY name ASC 
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $like = '%' . $q . '%';
        $stmt->bindValue(':q', $like);
        $stmt->bindValue(':q2', $like);
        $stmt->bindValue(':q3', $like);
        $stmt->bindValue(':q4', $like);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $results = [];

        foreach ($rows as $row) {
            $results[] = [
                'type'        => 'customer',
                'category'    => 'Clientes',
                'icon'        => 'fas fa-user',
                'title'       => $row['name'],
                'subtitle'    => $row['email'] ?: $row['phone'] ?: $row['document'] ?: '',
                'url'         => '?page=customers&action=view&id=' . $row['id'],
            ];
        }

        return $results;
    }

    /**
     * Busca produtos pelo nome ou descrição.
     */
    private function searchProducts(string $q, int $limit): array
    {
        $sql = "SELECT id, name, description, sale_price 
                FROM products 
                WHERE (name LIKE :q OR description LIKE :q2) 
                ORDER BY name ASC 
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $like = '%' . $q . '%';
        $stmt->bindValue(':q', $like);
        $stmt->bindValue(':q2', $like);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $results = [];

        foreach ($rows as $row) {
            $results[] = [
                'type'        => 'product',
                'category'    => 'Produtos',
                'icon'        => 'fas fa-box',
                'title'       => $row['name'],
                'subtitle'    => $row['sale_price'] ? 'R$ ' . number_format($row['sale_price'], 2, ',', '.') : '',
                'url'         => '?page=products&action=edit&id=' . $row['id'],
            ];
        }

        return $results;
    }

    /**
     * Busca pedidos pelo ID, nome do cliente ou observações.
     */
    private function searchOrders(string $q, int $limit): array
    {
        $sql = "SELECT o.id, o.status, o.pipeline_stage, c.name AS customer_name 
                FROM orders o 
                LEFT JOIN customers c ON c.id = o.customer_id 
                WHERE (o.id LIKE :q OR c.name LIKE :q2 OR o.notes LIKE :q3)
                ORDER BY o.id DESC 
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $like = '%' . $q . '%';
        $stmt->bindValue(':q', $like);
        $stmt->bindValue(':q2', $like);
        $stmt->bindValue(':q3', $like);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $results = [];

        $stageLabels = [
            'contato' => 'Contato', 'orcamento' => 'Orçamento', 'venda' => 'Venda',
            'producao' => 'Produção', 'preparacao' => 'Preparação',
            'envio' => 'Envio', 'financeiro' => 'Financeiro',
        ];

        foreach ($rows as $row) {
            $stage = $stageLabels[$row['pipeline_stage']] ?? $row['pipeline_stage'];
            $results[] = [
                'type'        => 'order',
                'category'    => 'Pedidos',
                'icon'        => 'fas fa-clipboard-list',
                'title'       => '#' . str_pad($row['id'], 4, '0', STR_PAD_LEFT) . ($row['customer_name'] ? ' — ' . $row['customer_name'] : ''),
                'subtitle'    => $stage,
                'url'         => '?page=pipeline&action=detail&id=' . $row['id'],
            ];
        }

        return $results;
    }

    // ── Helpers ──

    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}
