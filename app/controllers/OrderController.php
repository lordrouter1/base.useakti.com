<?php
namespace Akti\Controllers;

use Akti\Models\Order;
use Akti\Models\Product;
use Akti\Models\Customer;
use Akti\Models\Pipeline;
use Akti\Models\Logger;
use Akti\Models\PriceTable;
use Akti\Models\CompanySettings;
use Akti\Models\Financial;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use Database;
use PDO;

class OrderController {
    
    private $orderModel;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->orderModel = new Order($db);
    }

    public function index() {
        $stmt = $this->orderModel->readAll();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        require 'app/views/layout/header.php';
        require 'app/views/orders/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create() {
        
        $database = new Database();
        $db = $database->getConnection();
        
        $productModel = new Product($db);
        $stmt_products = $productModel->readAll();
        $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

        // Buscar combinações de grade ativas para cada produto
        $productCombinations = [];
        foreach ($products as $p) {
            $combos = $productModel->getActiveCombinations($p['id']);
            if (!empty($combos)) {
                $productCombinations[$p['id']] = $combos;
            }
        }

        $customerModel = new Customer($db);
        $stmt_customers = $customerModel->readAll();
        $customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);

        // Buscar contatos agendados para a agenda
        $agendaMonth = Input::get('agenda_month', 'int') ?: (int)date('m');
        $agendaYear = Input::get('agenda_year', 'int') ?: (int)date('Y');
        $scheduledContacts = $this->orderModel->getScheduledContacts($agendaMonth, $agendaYear);

        require 'app/views/layout/header.php';
        require 'app/views/orders/create.php';
        require 'app/views/layout/footer.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $initialStage = Input::post('initial_stage', 'enum', 'contato', ['contato', 'orcamento']);
            
            $this->orderModel->customer_id = Input::post('customer_id', 'int');
            $this->orderModel->priority = Input::post('priority', 'enum', 'normal', ['baixa', 'normal', 'alta', 'urgente']);
            $this->orderModel->internal_notes = Input::post('notes');
            
            if ($initialStage === 'contato') {
                $this->orderModel->total_amount = 0;
                $this->orderModel->status = 'orcamento';
                $this->orderModel->pipeline_stage = 'contato';
                $this->orderModel->scheduled_date = Input::post('scheduled_date', 'date');
            } else {
                $this->orderModel->total_amount = Input::post('total_amount', 'float', 0);
                $this->orderModel->status = 'orcamento';
                $this->orderModel->pipeline_stage = 'orcamento';
                $this->orderModel->scheduled_date = null;
            }
            
            if ($this->orderModel->create()) {
                // Se criou como orçamento, salvar os itens do pedido
                if ($initialStage === 'orcamento' && !empty(Input::postArray('items'))) {
                    foreach (Input::postArray('items') as $item) {
                        if (!empty($item['product_id']) && !empty($item['quantity']) && isset($item['price'])) {
                            $combId = !empty($item['combination_id']) ? \Akti\Utils\Sanitizer::int($item['combination_id']) : null;
                            $gradeDesc = isset($item['grade_description']) ? \Akti\Utils\Sanitizer::string($item['grade_description']) : null;
                            $this->orderModel->addItem(
                                $this->orderModel->id,
                                \Akti\Utils\Sanitizer::int($item['product_id']),
                                \Akti\Utils\Sanitizer::int($item['quantity'], 1),
                                \Akti\Utils\Sanitizer::float($item['price'], 0),
                                $combId,
                                $gradeDesc
                            );
                        }
                    }
                }

                // Registrar entrada no pipeline
                $database = new Database();
                $db = $database->getConnection();
                $pipeline = new Pipeline($db);
                $stageLabel = $initialStage === 'contato' ? 'Pedido criado como Contato' : 'Pedido criado como Orçamento';
                $pipeline->addHistory($this->orderModel->id, null, $initialStage, $_SESSION['user_id'] ?? null, $stageLabel);

                // Log
                $logger = new Logger($db);
                $logger->log('ORDER_CREATE', "Pedido #{$this->orderModel->id} criado na etapa " . ucfirst($initialStage));

                header('Location: ?page=orders&status=success');
                exit;
            } else {
                echo "Erro ao criar pedido.";
            }
        }
    }

    public function edit() {
        $id = Input::get('id', 'int');
        if (!$id) {
            header('Location: ?page=orders');
            exit;
        }
        $order = $this->orderModel->readOne($id);
        if (!$order) {
            header('Location: ?page=orders');
            exit;
        }

        $database = new Database();
        $db = $database->getConnection();
        $customerModel = new Customer($db);
        $stmt_customers = $customerModel->readAll();
        $customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);

        // Buscar itens do pedido
        $orderItems = $this->orderModel->getItems($order['id']);

        // Buscar produtos para o select
        $productModel = new Product($db);
        $stmt_products = $productModel->readAll();
        $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

        // Buscar combinações de grade ativas para cada produto
        $productCombinations = [];
        foreach ($products as $p) {
            $combos = $productModel->getActiveCombinations($p['id']);
            if (!empty($combos)) {
                $productCombinations[$p['id']] = $combos;
            }
        }

        // Carregar preços específicos do cliente (tabela de preço)
        $customerPrices = [];
        if (!empty($order['customer_id'])) {
            $priceTableModel = new PriceTable($db);
            $customerPrices = $priceTableModel->getAllPricesForCustomer($order['customer_id']);
        }

        require 'app/views/layout/header.php';
        require 'app/views/orders/edit.php';
        require 'app/views/layout/footer.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = Input::post('id', 'int');
            $this->orderModel->id = $id;
            $this->orderModel->customer_id = Input::post('customer_id', 'int');
            $this->orderModel->total_amount = Input::post('total_amount', 'float', 0);
            $this->orderModel->status = Input::post('status');
            
            if ($this->orderModel->update()) {
                $printOrder = Input::post('print_order_after_save', 'bool');
                $redirectUrl = '?page=orders&action=edit&id=' . $id . '&status=success';
                if ($printOrder) {
                    $redirectUrl .= '&print_order=1';
                }
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                echo "Erro ao atualizar pedido.";
            }
        }
    }

    /**
     * Verifica se o pedido possui parcelas pagas (bloqueia alteração de produtos)
     */
    private function orderHasPaidInstallments($orderId) {
        if (!$orderId) return false;
        $database = new Database();
        $db = $database->getConnection();
        $financialModel = new Financial($db);
        return $financialModel->hasAnyPaidInstallment($orderId);
    }

    /**
     * Obtém o order_id a partir de um item_id
     */
    private function getOrderIdFromItem($itemId) {
        if (!$itemId) return null;
        $database = new Database();
        $db = $database->getConnection();
        $q = "SELECT order_id FROM order_items WHERE id = :id LIMIT 1";
        $s = $db->prepare($q);
        $s->execute([':id' => $itemId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['order_id'] : null;
    }

    /**
     * Adicionar item ao pedido (POST via AJAX ou form)
     */
    public function addItem() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = Input::post('order_id', 'int');
            $productId = Input::post('product_id', 'int');
            $quantity = Input::post('quantity', 'int', 1);
            $unitPrice = Input::post('unit_price', 'float', 0);
            $combinationId = Input::post('combination_id', 'int');
            $gradeDescription = Input::post('grade_description');

            // ═══ BLOQUEIO: Não permitir alterar produtos se há parcelas pagas ═══
            if ($this->orderHasPaidInstallments($orderId)) {
                $_SESSION['error'] = 'Não é possível adicionar produtos porque existem parcelas já pagas. Estorne os pagamentos primeiro no módulo Financeiro.';
                $redirect = Input::post('redirect', 'enum', 'orders', ['orders', 'pipeline']);
                if ($redirect === 'pipeline') {
                    header('Location: ?page=pipeline&action=detail&id=' . $orderId);
                } else {
                    header('Location: ?page=orders&action=edit&id=' . $orderId);
                }
                exit;
            }

            $this->orderModel->addItem($orderId, $productId, $quantity, $unitPrice, $combinationId ?: null, $gradeDescription ?: null);

            $redirect = Input::post('redirect', 'enum', 'orders', ['orders', 'pipeline']);
            if ($redirect === 'pipeline') {
                header('Location: ?page=pipeline&action=detail&id=' . $orderId . '&status=item_added');
            } else {
                header('Location: ?page=orders&action=edit&id=' . $orderId . '&status=item_added');
            }
            exit;
        }
    }

    /**
     * Atualizar item do pedido (POST)
     */
    public function updateItem() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemId = Input::post('item_id', 'int');
            $quantity = Input::post('quantity', 'int', 1);
            $unitPrice = Input::post('unit_price', 'float', 0);
            $orderId = Input::post('order_id', 'int');

            // ═══ BLOQUEIO: Não permitir alterar item se há parcelas pagas ═══
            if ($this->orderHasPaidInstallments($orderId)) {
                $_SESSION['error'] = 'Não é possível alterar produtos porque existem parcelas já pagas. Estorne os pagamentos primeiro no módulo Financeiro.';
                $redirect = Input::post('redirect', 'enum', 'orders', ['orders', 'pipeline']);
                if ($redirect === 'pipeline') {
                    header('Location: ?page=pipeline&action=detail&id=' . $orderId);
                } else {
                    header('Location: ?page=orders&action=edit&id=' . $orderId);
                }
                exit;
            }

            $this->orderModel->updateItem($itemId, $quantity, $unitPrice);

            $redirect = Input::post('redirect', 'enum', 'orders', ['orders', 'pipeline']);
            if ($redirect === 'pipeline') {
                header('Location: ?page=pipeline&action=detail&id=' . $orderId . '&status=item_updated');
            } else {
                header('Location: ?page=orders&action=edit&id=' . $orderId . '&status=item_updated');
            }
            exit;
        }
    }

    /**
     * Remover item do pedido
     */
    public function deleteItem() {
        $itemId = Input::get('item_id', 'int');
        $orderId = Input::get('order_id', 'int');
        $redirect = Input::get('redirect', 'enum', 'orders', ['orders', 'pipeline']);

        // ═══ BLOQUEIO: Não permitir remover produtos se há parcelas pagas ═══
        if ($this->orderHasPaidInstallments($orderId)) {
            $_SESSION['error'] = 'Não é possível remover produtos porque existem parcelas já pagas. Estorne os pagamentos primeiro no módulo Financeiro.';
            if ($redirect === 'pipeline') {
                header('Location: ?page=pipeline&action=detail&id=' . $orderId);
            } else {
                header('Location: ?page=orders&action=edit&id=' . $orderId);
            }
            exit;
        }

        if ($itemId) {
            $this->orderModel->deleteItem($itemId);
        }

        if ($redirect === 'pipeline') {
            header('Location: ?page=pipeline&action=detail&id=' . $orderId . '&status=item_deleted');
        } else {
            header('Location: ?page=orders&action=edit&id=' . $orderId . '&status=item_deleted');
        }
        exit;
    }

    /**
     * Atualizar quantidade de um item do pedido (AJAX POST)
     */
    public function updateItemQty() {
        header('Content-Type: application/json');

        $itemId = Input::post('item_id', 'int');
        $quantity = Input::post('quantity', 'int', 1);

        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'ID do item não informado']);
            exit;
        }

        // ═══ BLOQUEIO: Não permitir alterar quantidade se há parcelas pagas ═══
        $orderId = $this->getOrderIdFromItem($itemId);
        if ($orderId && $this->orderHasPaidInstallments($orderId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Não é possível alterar a quantidade porque existem parcelas já pagas. Estorne os pagamentos primeiro.',
                'blocked_by_paid' => true,
            ]);
            exit;
        }

        $result = $this->orderModel->updateItemQty($itemId, $quantity);

        if ($result) {
            $database = new \Database();
            $db = $database->getConnection();
            $q = "SELECT oi.order_id, oi.quantity, oi.unit_price, oi.subtotal, oi.discount, o.total_amount 
                  FROM order_items oi 
                  JOIN orders o ON oi.order_id = o.id 
                  WHERE oi.id = :id";
            $s = $db->prepare($q);
            $s->execute([':id' => $itemId]);
            $row = $s->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Quantidade atualizada com sucesso',
                'new_subtotal' => $row ? (float)$row['subtotal'] : 0,
                'new_total' => $row ? (float)$row['total_amount'] : 0,
                'quantity' => $row ? (int)$row['quantity'] : 1,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar quantidade']);
        }
        exit;
    }

    /**
     * Atualizar desconto de um item do pedido (AJAX POST)
     */
    public function updateItemDiscount() {
        header('Content-Type: application/json');

        $itemId = Input::post('item_id', 'int');
        $discount = Input::post('discount', 'float', 0);

        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'ID do item não informado']);
            exit;
        }

        // ═══ BLOQUEIO: Não permitir alterar desconto de item se há parcelas pagas ═══
        $orderId = $this->getOrderIdFromItem($itemId);
        if ($orderId && $this->orderHasPaidInstallments($orderId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Não é possível alterar o desconto porque existem parcelas já pagas. Estorne os pagamentos primeiro.',
                'blocked_by_paid' => true,
            ]);
            exit;
        }

        $result = $this->orderModel->updateItemDiscount($itemId, $discount);

        if ($result) {
            // Buscar novo total do pedido (recalculado pelo model)
            $database = new \Database();
            $db = $database->getConnection();
            $q = "SELECT oi.order_id, o.total_amount 
                  FROM order_items oi 
                  JOIN orders o ON oi.order_id = o.id 
                  WHERE oi.id = :id";
            $s = $db->prepare($q);
            $s->execute([':id' => $itemId]);
            $row = $s->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Desconto atualizado com sucesso',
                'new_total' => $row ? (float)$row['total_amount'] : 0,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar desconto']);
        }
        exit;
    }

    /**
     * Imprimir orçamento
     */
    public function printQuote() {
        $orderId = Input::get('id', 'int');
        if (!$orderId) {
            header('Location: ?page=orders');
            exit;
        }
        $order = $this->orderModel->readOne($orderId);
        if (!$order) {
            header('Location: ?page=orders');
            exit;
        }
        $orderItems = $this->orderModel->getItems($orderId);
        $extraCosts = $this->orderModel->getExtraCosts($orderId);

        // Carregar dados da empresa
        $database = new Database();
        $db = $database->getConnection();
        $companyModel = new CompanySettings($db);
        $company = $companyModel->getAll();
        $companyAddress = $companyModel->getFormattedAddress();
        
        require 'app/views/orders/print_quote.php';
    }

    /**
     * Imprimir nota de pedido (pedido de compra)
     * Disponível nas etapas: venda e financeiro
     */
    public function printOrder() {
        $orderId = Input::get('id', 'int');
        if (!$orderId) {
            header('Location: ?page=orders');
            exit;
        }
        $order = $this->orderModel->readOne($orderId);
        if (!$order) {
            header('Location: ?page=orders');
            exit;
        }
        $orderItems = $this->orderModel->getItems($orderId);
        $extraCosts = $this->orderModel->getExtraCosts($orderId);

        // Carregar dados da empresa
        $database = new Database();
        $db = $database->getConnection();
        $companyModel = new CompanySettings($db);
        $company = $companyModel->getAll();
        $companyAddress = $companyModel->getFormattedAddress();

        // Carregar parcelas (se existirem)
        $financialModel = new Financial($db);
        $installments = $financialModel->getInstallments($orderId);

        require 'app/views/orders/print_order.php';
    }

    public function delete() {
        $id = Input::get('id', 'int');
        if ($id) {
            $this->orderModel->delete($id);
            header('Location: ?page=orders&status=success');
            exit;
        }
    }

    /**
     * Agenda de contatos agendados
     */
    public function agenda() {
        $agendaMonth = Input::get('month', 'int', (int)date('m'));
        $agendaYear = Input::get('year', 'int', (int)date('Y'));
        
        $scheduledContacts = $this->orderModel->getScheduledContacts($agendaMonth, $agendaYear);
        
        require 'app/views/layout/header.php';
        require 'app/views/orders/agenda.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Relatório de contatos para impressão
     */
    public function report() {
        $date = Input::get('date', 'date', date('Y-m-d'));
        $contacts = $this->orderModel->getScheduledContactsByDate($date);
        
        require 'app/views/orders/report.php';
    }
}
