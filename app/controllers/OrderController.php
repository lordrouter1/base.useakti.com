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
use Akti\Services\OrderItemService;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use PDO;

class OrderController {
    
    private Order $orderModel;
    private OrderItemService $itemService;
    private \PDO $db;

    public function __construct(\PDO $db, Order $orderModel, OrderItemService $itemService) {
        $this->db = $db;
        $this->orderModel = $orderModel;
        $this->itemService = $itemService;
    }

    public function index() {
        $perPage     = 15;
        $ctPage = max(1, (Input::get('pg', 'int')?? 1));
        $totalItems  = (int) $this->orderModel->countAll();
        $totalPages  = max(1, (int) ceil($totalItems / $perPage));
        $ctPage = min($ctPage, $totalPages);

        $orders = $this->orderModel->readPaginated($ctPage, $perPage);

        // Variáveis para o componente de paginação
        $baseUrl = '?page=orders';

        require 'app/views/layout/header.php';
        require 'app/views/orders/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create() {
        
        $productModel = new Product($this->db);
        $products = $productModel->readAll();

        // Buscar combinações de grade ativas para cada produto
        $productCombinations = [];
        foreach ($products as $p) {
            $combos = $productModel->getActiveCombinations($p['id']);
            if (!empty($combos)) {
                $productCombinations[$p['id']] = $combos;
            }
        }

        $customerModel = new Customer($this->db);
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
                $pipeline = new Pipeline($this->db);
                $stageLabel = $initialStage === 'contato' ? 'Pedido criado como Contato' : 'Pedido criado como Orçamento';
                $pipeline->addHistory($this->orderModel->id, null, $initialStage, $_SESSION['user_id'] ?? null, $stageLabel);

                // Log
                $logger = new Logger($this->db);
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

        $customerModel = new Customer($this->db);
        $stmt_customers = $customerModel->readAll();
        $customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);

        // Buscar itens do pedido
        $orderItems = $this->orderModel->getItems($order['id']);

        // Buscar produtos para o select
        $productModel = new Product($this->db);
        $products = $productModel->readAll();

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
            $priceTableModel = new PriceTable($this->db);
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
            if ($this->itemService->orderHasPaidInstallments($orderId)) {
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

            // ═══ Limpar confirmação de orçamento (cliente precisa reaprovar) ═══
            $this->itemService->clearQuoteConfirmation($orderId);

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
            if ($this->itemService->orderHasPaidInstallments($orderId)) {
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

            // ═══ Limpar confirmação de orçamento (cliente precisa reaprovar) ═══
            $this->itemService->clearQuoteConfirmation($orderId);

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
        if ($this->itemService->orderHasPaidInstallments($orderId)) {
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

        // ═══ Limpar confirmação de orçamento (cliente precisa reaprovar) ═══
        $this->itemService->clearQuoteConfirmation($orderId);

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
        $orderId = $this->itemService->getOrderIdFromItem($itemId);
        if ($orderId && $this->itemService->orderHasPaidInstallments($orderId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Não é possível alterar a quantidade porque existem parcelas já pagas. Estorne os pagamentos primeiro.',
                'blocked_by_paid' => true,
            ]);
            exit;
        }

        $result = $this->itemService->updateItemQuantity($itemId, $quantity);
        echo json_encode($result);
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
        $orderId = $this->itemService->getOrderIdFromItem($itemId);
        if ($orderId && $this->itemService->orderHasPaidInstallments($orderId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Não é possível alterar o desconto porque existem parcelas já pagas. Estorne os pagamentos primeiro.',
                'blocked_by_paid' => true,
            ]);
            exit;
        }

        $result = $this->itemService->updateItemDiscount($itemId, $discount);
        echo json_encode($result);
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
        $companyModel = new CompanySettings($this->db);
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
        $companyModel = new CompanySettings($this->db);
        $company = $companyModel->getAll();
        $companyAddress = $companyModel->getFormattedAddress();

        // Carregar parcelas (se existirem)
        $financialModel = new Financial($this->db);
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
