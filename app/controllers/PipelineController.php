<?php
namespace Akti\Controllers;

use Akti\Models\Pipeline;
use Akti\Models\Order;
use Akti\Models\User;
use Akti\Models\Stock;
use Akti\Models\Logger;
use Akti\Models\OrderItemLog;
use Akti\Models\OrderPreparation;
use Akti\Models\Financial;
use Akti\Models\PriceTable;
use Akti\Models\CompanySettings;
use Akti\Services\PipelineService;
use Akti\Services\PipelineAlertService;
use PDO;
use Akti\Services\PipelinePaymentService;
use Akti\Services\PipelineDetailService;
use Akti\Utils\Input;
use Akti\Utils\Sanitizer;

class PipelineController extends BaseController {

    private Pipeline $pipelineModel;
    private \PDO $db;
    private Stock $stockModel;
    private PipelineService $pipelineService;
    private PipelineAlertService $alertService;
    private PipelinePaymentService $paymentService;
    private PipelineDetailService $detailService;

    public function __construct(
        \PDO $db,
        Pipeline $pipelineModel,
        Stock $stockModel,
        PipelineService $pipelineService,
        PipelineAlertService $alertService,
        PipelinePaymentService $paymentService,
        PipelineDetailService $detailService
    ) {
        $this->db = $db;
        $this->pipelineModel = $pipelineModel;
        $this->stockModel = $stockModel;
        // Auto-migrate stock tables/columns
        $this->stockModel->ensureDeductionsTable();
        $this->stockModel->ensureDefaultColumn();
        $this->stockModel->ensureOrderWarehouseColumn();
        // Services
        $this->pipelineService = $pipelineService;
        $this->alertService = $alertService;
        $this->paymentService = $paymentService;
        $this->detailService = $detailService;
    }

    /**
     * View principal: Kanban Board
     */
    public function index() {
        $ordersByStage = $this->pipelineModel->getOrdersByStage();
        $stages = Pipeline::$stages;
        $goals = $this->pipelineModel->getStageGoals();
        $stats = $this->pipelineModel->getStats();
        $delayedOrders = $stats['delayed_orders'];

        require 'app/views/layout/header.php';
        require 'app/views/pipeline/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Mover pedido para outra etapa (GET â€” usado no detalhe do pedido)
     * Delegado ao PipelineService para lÃ³gica de estoque e regras.
     */
    /**
     * Etapas bloqueadas quando existem parcelas pagas â€” gerenciado pelo PipelineService.
     */

    /**
     * Remove a confirmaÃ§Ã£o de orÃ§amento quando o pedido Ã© modificado.
     * Delegado ao PipelineService.
     */
    private function clearQuoteConfirmation($orderId) {
        $this->pipelineService->clearQuoteConfirmation($orderId);
    }

    public function move() {
        $orderId = Input::get('id', 'int');
        $newStage = Input::get('stage');
        if (!$orderId || !$newStage) {
            header('Location: ?page=pipeline');
            exit;
        }

        $notes = Input::post('notes') ?: Input::get('notes', 'string', '');
        $userId = $_SESSION['user_id'] ?? null;
        $warehouseId = Input::get('warehouse_id', 'int') ?: Input::post('warehouse_id', 'int');

        $result = $this->pipelineService->moveOrder($orderId, $newStage, $userId, $warehouseId, $notes);

        if (!$result['success']) {
            $_SESSION['error'] = $result['message'];
            header('Location: ?page=pipeline&action=detail&id=' . $orderId);
            exit;
        }

        header('Location: ?page=pipeline&status=moved');
        exit;
    }

    /**
     * Mover pedido via AJAX (drag-and-drop).
     * Delegado ao PipelineService para regras de negÃ³cio.
     */
    public function moveAjax() {
        header('Content-Type: application/json');

        $orderId = Input::post('order_id', 'int');
        $newStage = Input::post('stage');
        $userId = $_SESSION['user_id'] ?? null;
        $warehouseId = Input::post('warehouse_id', 'int');

        if (!$orderId || !$newStage) {
            $this->json(['success' => false, 'message' => 'ParÃ¢metros invÃ¡lidos']);
        }

        $currentStage = $this->pipelineService->getCurrentStage($orderId);
        if (!$currentStage) {
            $this->json(['success' => false, 'message' => 'Pedido nÃ£o encontrado']);
        }

        if ($currentStage === $newStage) {
            $this->json(['success' => true, 'message' => 'Sem alteraÃ§Ã£o']);
        }

        // Se a transiÃ§Ã£o precisa de armazÃ©m e nÃ£o foi informado, retorna flag
        if ($this->pipelineService->transitionNeedsWarehouse($currentStage, $newStage) && !$warehouseId) {
            $this->json([
                'success' => false,
                'needs_warehouse' => true,
                'message' => 'Selecione o armazÃ©m para deduÃ§Ã£o de estoque.',
            ]);
        }

        $result = $this->pipelineService->moveOrder($orderId, $newStage, $userId, $warehouseId, 'Movido via drag-and-drop');

        $this->json($result);
    }

    /**
     * Detalhes de um pedido no pipeline.
     * Dados carregados via PipelineDetailService.
     */
    public function detail() {
        $detailId = Input::get('id', 'int');
        if (!$detailId) {
            header('Location: ?page=pipeline');
            exit;
        }

        $data = $this->detailService->loadDetailData($detailId);
        if (!$data) {
            header('Location: ?page=pipeline');
            exit;
        }

        // Extrair variÃ¡veis para a view
        extract($data);

        require 'app/views/layout/header.php';
        require 'app/views/pipeline/detail.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Atualizar detalhes do pedido (POST).
     * RegeneraÃ§Ã£o de parcelas delegada ao PipelineService.
     */
    public function updateDetails() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id' => Input::post('id', 'int'),
                'priority' => Input::post('priority', 'enum', 'normal', ['baixa', 'normal', 'alta', 'urgente']),
                'assigned_to' => Input::post('assigned_to', 'int') ?: null,
                'seller_id' => Input::post('seller_id', 'int') ?: null,
                'internal_notes' => Input::post('internal_notes'),
                'quote_notes' => Input::post('quote_notes'),
                'deadline' => Input::post('deadline', 'date') ?: null,
                'payment_status' => Input::post('payment_status', 'enum', 'pendente', ['pendente', 'pago']),
                'payment_method' => Input::post('payment_method'),
                'installments' => Input::post('installments', 'int') ?: null,
                'installment_value' => Input::post('installment_value', 'float') ?: null,
                'discount' => Input::post('discount', 'float', 0),
                'down_payment' => Input::post('down_payment', 'float', 0),
                'shipping_type' => Input::post('shipping_type', 'enum', 'retirada', ['retirada', 'entrega', 'correios']),
                'shipping_address' => Input::post('shipping_address'),
                'tracking_code' => Input::post('tracking_code'),
                'price_table_id' => Input::post('price_table_id', 'int') ?: null,
                // Campos fiscais (NF-e)
                'nf_number' => Input::post('nf_number') ?: null,
                'nf_series' => Input::post('nf_series') ?: null,
                'nf_status' => Input::post('nf_status') ?: null,
                'nf_access_key' => Input::post('nf_access_key') ?: null,
                'nf_notes' => Input::post('nf_notes') ?: null,
            ];

            $this->pipelineModel->updateOrderDetails($data);

            $logger = new Logger($this->db);
            $logger->log('PIPELINE_UPDATE', "Updated order details #" . $data['id']);

            // Limpar confirmaÃ§Ã£o de orÃ§amento se desconto foi alterado
            $this->pipelineService->clearQuoteConfirmation($data['id']);

            // Auto-gerar/regenerar parcelas via service
            $this->pipelineService->regenerateInstallmentsIfNeeded($data['id'], $data);

            // Redirecionar (com flag para impressÃ£o se solicitado)
            $printOrder = Input::post('print_order_after_save', 'bool');
            $redirectUrl = '?page=pipeline&action=detail&id=' . $data['id'] . '&status=success';
            if ($printOrder) {
                $redirectUrl .= '&print_order=1';
            }

            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * ConfiguraÃ§Ãµes de metas por etapa
     */
    public function settings() {
        $goals = $this->pipelineModel->getStageGoals();
        $stages = Pipeline::$stages;

        require 'app/views/layout/header.php';
        require 'app/views/pipeline/settings.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Salvar configuraÃ§Ãµes de metas (POST)
     */
    public function saveSettings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maxHours = Input::postArray('max_hours');
            foreach ($maxHours as $stage => $hours) {
                $this->pipelineModel->updateStageGoal(Sanitizer::string($stage), Sanitizer::int($hours, 0));
            }

            $logger = new Logger($this->db);
            $logger->log('PIPELINE_SETTINGS', 'Updated pipeline stage goals');

            header('Location: ?page=pipeline&action=settings&status=success');
            exit;
        }
    }

    /**
     * API JSON: pedidos atrasados (para notificaÃ§Ãµes).
     * Delegado ao PipelineAlertService.
     */
    public function alerts() {
        header('Content-Type: application/json');
        $result = $this->alertService->getDelayedOrders();
        $this->json($result);
    }

    /**
     * API JSON: Retorna preÃ§os de uma tabela de preÃ§o especÃ­fica (AJAX)
     */
    public function getPricesByTable() {
        $priceTableModel = new PriceTable($this->db);
        $tableId = Input::get('table_id', 'int');
        $customerId = Input::get('customer_id', 'int');

        $prices = [];
        if ($tableId) {
            // Buscar preÃ§os da tabela especÃ­fica com fallback ao preÃ§o base
            $products = $this->db->query("SELECT id, price FROM products")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $p) {
                $prices[$p['id']] = (float)$p['price'];
            }
            // Sobrepor com preÃ§os da tabela selecionada
            $items = $priceTableModel->getItems($tableId);
            foreach ($items as $item) {
                $prices[$item['product_id']] = (float)$item['price'];
            }
        } elseif ($customerId) {
            $prices = $priceTableModel->getAllPricesForCustomer($customerId);
        }
        $this->json($prices);
    }

    /**
     * API JSON: Verifica disponibilidade de estoque dos itens de um pedido num armazÃ©m (AJAX).
     * Delegado ao PipelineAlertService.
     */
    public function checkOrderStock() {
        header('Content-Type: application/json');

        $orderId = Input::get('order_id', 'int');
        $warehouseId = Input::get('warehouse_id', 'int');

        if (!$orderId) {
            $this->json(['success' => false, 'message' => 'Pedido nÃ£o informado']);
        }

        $result = $this->alertService->checkOrderStock($orderId, $warehouseId, $this->stockModel);
        $this->json($result);
    }

    /**
     * Adicionar custo extra ao pedido (POST)
     */
    public function addExtraCost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = Input::post('order_id', 'int');
            $description = Input::post('extra_description');
            $amount = Input::post('extra_amount', 'float', 0);

            // â•â•â• BLOQUEIO: NÃ£o permitir alterar custos se hÃ¡ parcelas pagas â•â•â•
            if ($orderId) {
                $financialModel = new Financial($this->db);
                if ($financialModel->hasAnyPaidInstallment($orderId)) {
                    $_SESSION['error'] = 'NÃ£o Ã© possÃ­vel adicionar custos extras porque existem parcelas jÃ¡ pagas. Estorne os pagamentos primeiro no mÃ³dulo Financeiro.';
                    header('Location: ?page=pipeline&action=detail&id=' . $orderId);
                    exit;
                }
            }

            if ($orderId && $description && $amount != 0) {
                $orderModel = new Order($this->db);
                $orderModel->addExtraCost($orderId, $description, $amount);

                // â•â•â• Limpar confirmaÃ§Ã£o de orÃ§amento (cliente precisa reaprovar) â•â•â•
                $this->clearQuoteConfirmation($orderId);
            }

            header('Location: ?page=pipeline&action=detail&id=' . $orderId . '&status=extra_added');
            exit;
        }
    }

    /**
     * Remover custo extra do pedido
     */
    public function deleteExtraCost() {
        $costId = Input::get('cost_id', 'int');
        $orderId = Input::get('order_id', 'int');

        // â•â•â• BLOQUEIO: NÃ£o permitir remover custos se hÃ¡ parcelas pagas â•â•â•
        if ($orderId) {
            $financialModel = new Financial($this->db);
            if ($financialModel->hasAnyPaidInstallment($orderId)) {
                $_SESSION['error'] = 'NÃ£o Ã© possÃ­vel remover custos extras porque existem parcelas jÃ¡ pagas. Estorne os pagamentos primeiro no mÃ³dulo Financeiro.';
                header('Location: ?page=pipeline&action=detail&id=' . $orderId);
                exit;
            }
        }

        if ($costId) {
            $orderModel = new Order($this->db);
            $orderModel->deleteExtraCost($costId);

            // â•â•â• Limpar confirmaÃ§Ã£o de orÃ§amento (cliente precisa reaprovar) â•â•â•
            $this->clearQuoteConfirmation($orderId);
        }

        header('Location: ?page=pipeline&action=detail&id=' . $orderId . '&status=extra_deleted');
        exit;
    }

    /**
     * Imprimir Ordem de ProduÃ§Ã£o.
     * Dados carregados via PipelineDetailService.
     */
    public function printProductionOrder() {
        $printId = Input::get('id', 'int');
        if (!$printId) {
            header('Location: ?page=pipeline');
            exit;
        }

        $data = $this->detailService->loadPrintProductionData($printId);
        if (!$data) {
            header('Location: ?page=pipeline');
            exit;
        }

        extract($data);
        require 'app/views/pipeline/print_production_order.php';
    }

    /**
     * Alternar item do checklist de preparaÃ§Ã£o (AJAX POST)
     */
    public function togglePreparation() {
        header('Content-Type: application/json');
        $prepModel = new OrderPreparation($this->db);

        $orderId = Input::post('order_id', 'int');
        $key = Input::post('key');
        $userId = $_SESSION['user_id'] ?? null;

        if (!$orderId || !$key) {
            $this->json(['success' => false, 'message' => 'ParÃ¢metros invÃ¡lidos']);
        }

        // Verificar se o pedido estÃ¡ na etapa de preparaÃ§Ã£o
        $order = $this->pipelineModel->getOrderDetail($orderId);
        if (!$order || $order['pipeline_stage'] !== 'preparacao') {
            $this->json(['success' => false, 'message' => 'Pedido nÃ£o estÃ¡ em preparaÃ§Ã£o']);
        }

        $checked = $prepModel->toggle($orderId, $key, $userId);

        // Log do sistema
        $logger = new Logger($this->db);
        $action = $checked ? 'checked' : 'unchecked';
        $logger->log('PREPARATION_TOGGLE', "Preparation '$key' $action for order #$orderId");

        $this->json(['success' => true, 'checked' => $checked]);
    }

    /**
     * Imprimir cupom nÃ£o fiscal (impressora tÃ©rmica).
     * Dados carregados via PipelineDetailService.
     */
    public function printThermalReceipt() {
        $printId = Input::get('id', 'int');
        if (!$printId) {
            header('Location: ?page=pipeline');
            exit;
        }

        $data = $this->detailService->loadThermalReceiptData($printId);
        if (!$data) {
            header('Location: ?page=pipeline');
            exit;
        }

        extract($data);
        require 'app/views/pipeline/print_thermal_receipt.php';
    }
}
