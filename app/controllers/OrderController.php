<?php
namespace Akti\Controllers;

use Akti\Models\Order;
use Akti\Models\Product;
use Akti\Models\Customer;
use Akti\Models\Pipeline;
use Akti\Models\Logger;
use Akti\Models\PriceTable;
use Akti\Models\CompanySettings;
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
        $agendaMonth = isset($_GET['agenda_month']) ? (int)$_GET['agenda_month'] : (int)date('m');
        $agendaYear = isset($_GET['agenda_year']) ? (int)$_GET['agenda_year'] : (int)date('Y');
        $scheduledContacts = $this->orderModel->getScheduledContacts($agendaMonth, $agendaYear);

        require 'app/views/layout/header.php';
        require 'app/views/orders/create.php';
        require 'app/views/layout/footer.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $initialStage = $_POST['initial_stage'] ?? 'contato';
            
            $this->orderModel->customer_id = $_POST['customer_id'];
            $this->orderModel->priority = $_POST['priority'] ?? 'normal';
            $this->orderModel->internal_notes = $_POST['notes'] ?? null;
            
            if ($initialStage === 'contato') {
                // Contato: sem produtos, valor zerado, com agendamento opcional
                $this->orderModel->total_amount = 0;
                $this->orderModel->status = 'orcamento';
                $this->orderModel->pipeline_stage = 'contato';
                $this->orderModel->scheduled_date = !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null;
            } else {
                // Orçamento: com produtos e valor
                $this->orderModel->total_amount = $_POST['total_amount'] ?? 0;
                $this->orderModel->status = 'orcamento';
                $this->orderModel->pipeline_stage = 'orcamento';
                $this->orderModel->scheduled_date = null;
            }
            
            if ($this->orderModel->create()) {
                // Se criou como orçamento, salvar os itens do pedido
                if ($initialStage === 'orcamento' && !empty($_POST['items'])) {
                    foreach ($_POST['items'] as $item) {
                        if (!empty($item['product_id']) && !empty($item['quantity']) && isset($item['price'])) {
                            $combId = !empty($item['combination_id']) ? (int)$item['combination_id'] : null;
                            $gradeDesc = $item['grade_description'] ?? null;
                            $this->orderModel->addItem(
                                $this->orderModel->id,
                                (int)$item['product_id'],
                                (int)$item['quantity'],
                                (float)$item['price'],
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
        if (!isset($_GET['id'])) {
            header('Location: ?page=orders');
            exit;
        }
        $order = $this->orderModel->readOne($_GET['id']);
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
            $this->orderModel->id = $_POST['id'];
            $this->orderModel->customer_id = $_POST['customer_id'];
            $this->orderModel->total_amount = $_POST['total_amount'] ?? 0;
            $this->orderModel->status = $_POST['status'];
            
            if ($this->orderModel->update()) {
                header('Location: ?page=orders&status=success');
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
            $orderId = $_POST['order_id'];
            $productId = $_POST['product_id'];
            $quantity = (int)($_POST['quantity'] ?? 1);
            $unitPrice = (float)($_POST['unit_price'] ?? 0);
            $combinationId = !empty($_POST['combination_id']) ? (int)$_POST['combination_id'] : null;
            $gradeDescription = $_POST['grade_description'] ?? null;

            $this->orderModel->addItem($orderId, $productId, $quantity, $unitPrice, $combinationId, $gradeDescription);

            $redirect = $_POST['redirect'] ?? 'orders';
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
            $itemId = $_POST['item_id'];
            $quantity = (int)($_POST['quantity'] ?? 1);
            $unitPrice = (float)($_POST['unit_price'] ?? 0);
            $orderId = $_POST['order_id'];

            $this->orderModel->updateItem($itemId, $quantity, $unitPrice);

            $redirect = $_POST['redirect'] ?? 'orders';
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
        $itemId = $_GET['item_id'] ?? null;
        $orderId = $_GET['order_id'] ?? null;
        $redirect = $_GET['redirect'] ?? 'orders';

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
     * Imprimir orçamento
     */
    public function printQuote() {
        $orderId = $_GET['id'] ?? null;
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

    public function delete() {
        if (isset($_GET['id'])) {
            $this->orderModel->delete($_GET['id']);
            header('Location: ?page=orders&status=success');
            exit;
        }
    }

    /**
     * Agenda de contatos agendados
     */
    public function agenda() {
        $agendaMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
        $agendaYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        
        $scheduledContacts = $this->orderModel->getScheduledContacts($agendaMonth, $agendaYear);
        
        require 'app/views/layout/header.php';
        require 'app/views/orders/agenda.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Relatório de contatos para impressão
     */
    public function report() {
        $date = $_GET['date'] ?? date('Y-m-d');
        $contacts = $this->orderModel->getScheduledContactsByDate($date);
        
        require 'app/views/orders/report.php';
    }
}
