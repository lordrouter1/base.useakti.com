<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

class Order {
    private $conn;
    private $table_name = "orders";

    public $id;
    public $customer_id;
    public $total_amount;
    public $status;
    public $pipeline_stage;
    public $priority;
    public $internal_notes;
    protected $quote_notes;
    public $scheduled_date;
    protected $created_at;

    public function __construct(\PDO $db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (customer_id, total_amount, status, pipeline_stage, pipeline_entered_at, priority, internal_notes, scheduled_date, created_at) 
                  VALUES (:customer_id, :total_amount, :status, :pipeline_stage, NOW(), :priority, :internal_notes, :scheduled_date, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $this->customer_id);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':status', $this->status);
        $pipelineStage = $this->pipeline_stage ?? 'contato';
        $stmt->bindParam(':pipeline_stage', $pipelineStage);
        $priority = $this->priority ?? 'normal';
        $stmt->bindParam(':priority', $priority);
        $internalNotes = $this->internal_notes ?? null;
        $stmt->bindParam(':internal_notes', $internalNotes);
        $scheduledDate = !empty($this->scheduled_date) ? $this->scheduled_date : null;
        $stmt->bindParam(':scheduled_date', $scheduledDate);
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            EventDispatcher::dispatch('model.order.created', new Event('model.order.created', [
                'id' => $this->id,
                'customer_id' => $this->customer_id,
                'total_amount' => $this->total_amount,
                'pipeline_stage' => $pipelineStage,
            ]));
            return true;
        }
        return false;
    }

    /**
     * Busca contatos agendados para um determinado mês/ano
     */
    public function getScheduledContacts($month = null, $year = null) {
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');
        
        $query = "SELECT o.id, o.scheduled_date, o.internal_notes as notes, o.priority, o.created_at, o.pipeline_stage,
                         c.name as customer_name, c.phone as customer_phone, c.email as customer_email
                  FROM " . $this->table_name . " o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  WHERE o.scheduled_date IS NOT NULL
                    AND MONTH(o.scheduled_date) = :month 
                    AND YEAR(o.scheduled_date) = :year
                    AND o.pipeline_stage = 'contato'
                    AND o.status != 'cancelado'
                  ORDER BY o.scheduled_date ASC, o.priority DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':month', $month, PDO::PARAM_INT);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca contatos agendados para um dia específico (para relatório)
     */
    public function getScheduledContactsByDate($date) {
        $query = "SELECT o.id, o.scheduled_date, o.internal_notes as notes, o.priority, o.created_at, o.pipeline_stage,
                         o.total_amount,
                         c.name as customer_name, c.phone as customer_phone, 
                         c.email as customer_email, c.document as customer_document,
                         c.address as customer_address
                  FROM " . $this->table_name . " o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  WHERE o.scheduled_date = :date
                    AND o.pipeline_stage = 'contato'
                    AND o.status != 'cancelado'
                  ORDER BY o.priority DESC, c.name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readAll(): array {
        $query = "SELECT o.id, o.total_amount, o.status, o.pipeline_stage, o.priority, 
                         o.deadline, o.payment_status, o.created_at, o.customer_approval_status,
                         c.name as customer_name 
                  FROM " . $this->table_name . " o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  ORDER BY o.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna pedidos com paginação
     * @param int $page  Página atual (1-based)
     * @param int $perPage Itens por página
     * @return array Lista de pedidos
     */
    public function readPaginated(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT o.id, o.total_amount, o.status, o.pipeline_stage, o.priority, 
                         o.deadline, o.payment_status, o.created_at, o.customer_approval_status,
                         c.name as customer_name 
                  FROM {$this->table_name} o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  ORDER BY o.created_at DESC
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne($id) {
        $query = "SELECT o.*, c.name as customer_name, c.phone as customer_phone, 
                         c.document as customer_document, c.email as customer_email, 
                         c.address as customer_address
                  FROM " . $this->table_name . " o 
                  LEFT JOIN customers c ON o.customer_id = c.id 
                  WHERE o.id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET customer_id = :customer_id, total_amount = :total_amount, status = :status 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $this->customer_id);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            EventDispatcher::dispatch('model.order.updated', new Event('model.order.updated', [
                'id' => $this->id,
                'customer_id' => $this->customer_id,
                'total_amount' => $this->total_amount,
                'status' => $this->status,
            ]));
        }
        return $result;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $result = $stmt->execute();
        if ($result) {
            EventDispatcher::dispatch('model.order.deleted', new Event('model.order.deleted', ['id' => $id]));
        }
        return $result;
    }

    /**
     * Atualiza os campos de link de pagamento do pedido.
     *
     * @param int   $orderId ID do pedido
     * @param array $data    Dados do link: payment_link_url, payment_link_gateway, payment_link_method, payment_link_created_at
     * @return bool
     */
    public function updatePaymentLink(int $orderId, array $data): bool
    {
        $fields = [];
        $params = [':id' => $orderId];

        $allowed = ['payment_link_url', 'payment_link_gateway', 'payment_link_method', 'payment_link_created_at'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $q = "UPDATE {$this->table_name} SET " . implode(', ', $fields) . " WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute($params);
    }

    /**
     * Define o status de aprovação do cliente no pedido.
     * Utilizado por catálogo, portal e pipeline para manter ambos os mecanismos
     * de aprovação sincronizados (customer_approval_status).
     *
     * @param int    $orderId
     * @param string $status  'pendente', 'aprovado' ou 'recusado'
     * @return bool
     */
    public function setCustomerApprovalStatus(int $orderId, string $status): bool
    {
        // Verificar se a coluna existe antes de tentar atualizar
        $check = $this->conn->prepare(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = 'orders'
               AND column_name = 'customer_approval_status'"
        );
        $check->execute();
        if ((int) $check->fetchColumn() === 0) {
            return false;
        }

        $allowed = ['pendente', 'aprovado', 'recusado'];
        if (!in_array($status, $allowed)) {
            return false;
        }

        $q = "UPDATE {$this->table_name}
              SET customer_approval_status = :status
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':status' => $status, ':id' => $orderId]);
    }

    /**
     * Conta pedidos por status para o dashboard
     */
    public function countByStatus() {
        $query = "SELECT status, COUNT(*) as total FROM " . $this->table_name . " GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Total de pedidos
     */
    public function countAll() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Total de valor dos pedidos ativos
     */
    public function totalActiveValue() {
        $query = "SELECT COALESCE(SUM(total_amount), 0) FROM " . $this->table_name . " WHERE status != 'cancelado' AND status != 'concluido'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // ─────────────────────────────────────────────────
    // Itens do Pedido (order_items)
    // ─────────────────────────────────────────────────

    /**
     * Busca os itens de um pedido com nome do produto
     */
    public function getItems($orderId) {
        $query = "SELECT oi.*, p.name as product_name, pgc.combination_label
                  FROM order_items oi
                  LEFT JOIN products p ON oi.product_id = p.id
                  LEFT JOIN product_grade_combinations pgc ON oi.grade_combination_id = pgc.id
                  WHERE oi.order_id = :order_id
                  ORDER BY oi.id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona um item ao pedido
     */
    public function addItem($orderId, $productId, $quantity, $unitPrice, $combinationId = null, $gradeDescription = null) {
        $subtotal = $quantity * $unitPrice;
        $query = "INSERT INTO order_items (order_id, product_id, grade_combination_id, grade_description, quantity, unit_price, subtotal)
                  VALUES (:order_id, :product_id, :combination_id, :grade_description, :quantity, :unit_price, :subtotal)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':combination_id', $combinationId ? (int)$combinationId : null, $combinationId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':grade_description', $gradeDescription);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':unit_price', $unitPrice);
        $stmt->bindParam(':subtotal', $subtotal);
        $result = $stmt->execute();
        if ($result) {
            $this->recalculateTotal($orderId);
        }
        return $result;
    }

    /**
     * Atualiza um item do pedido
     */
    public function updateItem($itemId, $quantity, $unitPrice) {
        $subtotal = $quantity * $unitPrice;
        $query = "UPDATE order_items SET quantity = :quantity, unit_price = :unit_price, subtotal = :subtotal WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':unit_price', $unitPrice);
        $stmt->bindParam(':subtotal', $subtotal);
        $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);
        $result = $stmt->execute();
        if ($result) {
            // Buscar order_id do item para recalcular total
            $q = "SELECT order_id FROM order_items WHERE id = :id";
            $s = $this->conn->prepare($q);
            $s->bindParam(':id', $itemId, PDO::PARAM_INT);
            $s->execute();
            $row = $s->fetch(PDO::FETCH_ASSOC);
            if ($row) $this->recalculateTotal($row['order_id']);
        }
        return $result;
    }

    /**
     * Remove um item do pedido
     */
    public function deleteItem($itemId) {
        // Buscar order_id antes de deletar
        $q = "SELECT order_id FROM order_items WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->bindParam(':id', $itemId, PDO::PARAM_INT);
        $s->execute();
        $row = $s->fetch(PDO::FETCH_ASSOC);

        $query = "DELETE FROM order_items WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);
        $result = $stmt->execute();
        
        if ($result && $row) {
            $this->recalculateTotal($row['order_id']);
        }
        return $result;
    }

    /**
     * Recalcula o total do pedido com base nos itens (subtotal - discount) + custos extras
     */
    public function recalculateTotal($orderId) {
        $query = "SELECT COALESCE(SUM(subtotal - COALESCE(discount, 0)), 0) FROM order_items WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        $totalItems = $stmt->fetchColumn();

        // Somar custos extras
        $query2 = "SELECT COALESCE(SUM(amount), 0) FROM order_extra_costs WHERE order_id = :order_id";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt2->execute();
        $totalExtras = $stmt2->fetchColumn();

        $total = $totalItems + $totalExtras;

        $update = "UPDATE orders SET total_amount = :total WHERE id = :id";
        $stmt3 = $this->conn->prepare($update);
        $stmt3->bindParam(':total', $total);
        $stmt3->bindParam(':id', $orderId, PDO::PARAM_INT);
        return $stmt3->execute();
    }

    /**
     * Atualiza a quantidade de um item do pedido via AJAX e recalcula totais
     */
    public function updateItemQty($itemId, $quantity) {
        $quantity = max(1, (int)$quantity);
        // Buscar unit_price atual
        $q = "SELECT unit_price, order_id FROM order_items WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->bindParam(':id', $itemId, PDO::PARAM_INT);
        $s->execute();
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        $subtotal = $quantity * (float)$row['unit_price'];
        $query = "UPDATE order_items SET quantity = :quantity, subtotal = :subtotal WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':subtotal', $subtotal);
        $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);
        $result = $stmt->execute();

        if ($result) {
            $this->recalculateTotal($row['order_id']);
        }
        return $result;
    }

    // ─────────────────────────────────────────────────
    // Desconto por Item (order_items.discount)
    // ─────────────────────────────────────────────────

    /**
     * Atualiza o desconto de um item do pedido e recalcula o total
     */
    public function updateItemDiscount($itemId, $discount) {
        $discount = max(0, (float)$discount);
        $query = "UPDATE order_items SET discount = :discount WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':discount', $discount);
        $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);
        $result = $stmt->execute();

        if ($result) {
            // Buscar order_id do item para recalcular total
            $q = "SELECT order_id FROM order_items WHERE id = :id";
            $s = $this->conn->prepare($q);
            $s->bindParam(':id', $itemId, PDO::PARAM_INT);
            $s->execute();
            $row = $s->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $this->recalculateTotal($row['order_id']);
            }
        }
        return $result;
    }

    // ─────────────────────────────────────────────────
    // Custos Extras do Pedido (order_extra_costs)
    // ─────────────────────────────────────────────────

    /**
     * Busca os custos extras de um pedido
     */
    public function getExtraCosts($orderId) {
        $query = "SELECT * FROM order_extra_costs WHERE order_id = :order_id ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona um custo extra ao pedido
     */
    public function addExtraCost($orderId, $description, $amount) {
        $query = "INSERT INTO order_extra_costs (order_id, description, amount) VALUES (:order_id, :description, :amount)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':amount', $amount);
        $result = $stmt->execute();
        if ($result) {
            $this->recalculateTotal($orderId);
        }
        return $result;
    }

    /**
     * Remove um custo extra do pedido
     */
    public function deleteExtraCost($costId) {
        // Buscar order_id antes de deletar
        $q = "SELECT order_id FROM order_extra_costs WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->bindParam(':id', $costId, PDO::PARAM_INT);
        $s->execute();
        $row = $s->fetch(PDO::FETCH_ASSOC);

        $query = "DELETE FROM order_extra_costs WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $costId, PDO::PARAM_INT);
        $result = $stmt->execute();

        if ($result && $row) {
            $this->recalculateTotal($row['order_id']);
        }
        return $result;
    }
}
