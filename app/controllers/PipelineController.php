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
use Akti\Services\PipelinePaymentService;
use Akti\Services\PipelineDetailService;
use Akti\Utils\Input;
use Akti\Utils\Sanitizer;

class PipelineController {

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
     * Mover pedido para outra etapa (GET — usado no detalhe do pedido)
     * Delegado ao PipelineService para lógica de estoque e regras.
     */
    /**
     * Etapas bloqueadas quando existem parcelas pagas — gerenciado pelo PipelineService.
     */

    /**
     * Remove a confirmação de orçamento quando o pedido é modificado.
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
     * Delegado ao PipelineService para regras de negócio.
     */
    public function moveAjax() {
        header('Content-Type: application/json');

        $orderId = Input::post('order_id', 'int');
        $newStage = Input::post('stage');
        $userId = $_SESSION['user_id'] ?? null;
        $warehouseId = Input::post('warehouse_id', 'int');

        if (!$orderId || !$newStage) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
            exit;
        }

        $currentStage = $this->pipelineService->getCurrentStage($orderId);
        if (!$currentStage) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
            exit;
        }

        if ($currentStage === $newStage) {
            echo json_encode(['success' => true, 'message' => 'Sem alteração']);
            exit;
        }

        // Se a transição precisa de armazém e não foi informado, retorna flag
        if ($this->pipelineService->transitionNeedsWarehouse($currentStage, $newStage) && !$warehouseId) {
            echo json_encode([
                'success' => false,
                'needs_warehouse' => true,
                'message' => 'Selecione o armazém para dedução de estoque.',
            ]);
            exit;
        }

        $result = $this->pipelineService->moveOrder($orderId, $newStage, $userId, $warehouseId, 'Movido via drag-and-drop');

        echo json_encode($result);
        exit;
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

        // Extrair variáveis para a view
        extract($data);

        require 'app/views/layout/header.php';
        require 'app/views/pipeline/detail.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Atualizar detalhes do pedido (POST).
     * Regeneração de parcelas delegada ao PipelineService.
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
                'payment_status' => Input::post('payment_status', 'enum', 'pendente', ['pendente', 'parcial', 'pago']),
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

            // Limpar confirmação de orçamento se desconto foi alterado
            $this->pipelineService->clearQuoteConfirmation($data['id']);

            // Auto-gerar/regenerar parcelas via service
            $this->pipelineService->regenerateInstallmentsIfNeeded($data['id'], $data);

            // Redirecionar (com flag para impressão se solicitado)
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
     * API JSON: Conta parcelas existentes de um pedido (AJAX GET).
     * Delegado ao PipelineAlertService.
     */
    public function countInstallments() {
        header('Content-Type: application/json');
        $orderId = Input::get('order_id', 'int');
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'ID do pedido não informado']);
            exit;
        }
        $count = $this->alertService->countInstallments($orderId);
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    }

    /**
     * API JSON: Remove todas as parcelas de um pedido (AJAX POST).
     * Delegado ao PipelineAlertService.
     */
    public function deleteInstallments() {
        header('Content-Type: application/json');
        $orderId = Input::post('order_id', 'int');
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'ID do pedido não informado']);
            exit;
        }
        $result = $this->alertService->deleteInstallments($orderId);
        echo json_encode($result);
        exit;
    }

    /**
     * API JSON: Gera link de pagamento via Gateway configurado.
     * Delegado ao PipelinePaymentService.
     */
    public function generatePaymentLink() {
        header('Content-Type: application/json');

        $orderId = Input::post('order_id', 'int');
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Pedido não informado.']);
            exit;
        }

        $gatewaySlug = Input::post('gateway_slug', 'string', '');
        $method = Input::post('method', 'string', 'auto');

        $result = $this->paymentService->generatePaymentLink($orderId, $gatewaySlug, $method);
        echo json_encode($result);
        exit;
    }

    /**
     * Alias para manter compatibilidade com chamadas antigas.
     */
    public function generateMercadoPagoLink() {
        $this->generatePaymentLink();
    }

    /**
     * Configurações de metas por etapa
     */
    public function settings() {
        $goals = $this->pipelineModel->getStageGoals();
        $stages = Pipeline::$stages;

        require 'app/views/layout/header.php';
        require 'app/views/pipeline/settings.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Salvar configurações de metas (POST)
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
     * API JSON: pedidos atrasados (para notificações).
     * Delegado ao PipelineAlertService.
     */
    public function alerts() {
        header('Content-Type: application/json');
        $result = $this->alertService->getDelayedOrders();
        echo json_encode($result);
        exit;
    }

    /**
     * API JSON: Retorna preços de uma tabela de preço específica (AJAX)
     */
    public function getPricesByTable() {
        $priceTableModel = new PriceTable($this->db);
        $tableId = Input::get('table_id', 'int');
        $customerId = Input::get('customer_id', 'int');

        $prices = [];
        if ($tableId) {
            // Buscar preços da tabela específica com fallback ao preço base
            $products = $this->db->query("SELECT id, price FROM products")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $p) {
                $prices[$p['id']] = (float)$p['price'];
            }
            // Sobrepor com preços da tabela selecionada
            $items = $priceTableModel->getItems($tableId);
            foreach ($items as $item) {
                $prices[$item['product_id']] = (float)$item['price'];
            }
        } elseif ($customerId) {
            $prices = $priceTableModel->getAllPricesForCustomer($customerId);
        }

        header('Content-Type: application/json');
        echo json_encode($prices);
        exit;
    }

    /**
     * API JSON: Verifica disponibilidade de estoque dos itens de um pedido num armazém (AJAX).
     * Delegado ao PipelineAlertService.
     */
    public function checkOrderStock() {
        header('Content-Type: application/json');

        $orderId = Input::get('order_id', 'int');
        $warehouseId = Input::get('warehouse_id', 'int');

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Pedido não informado']);
            exit;
        }

        $result = $this->alertService->checkOrderStock($orderId, $warehouseId, $this->stockModel);
        echo json_encode($result);
        exit;
    }

    /**
     * Adicionar custo extra ao pedido (POST)
     */
    public function addExtraCost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = Input::post('order_id', 'int');
            $description = Input::post('extra_description');
            $amount = Input::post('extra_amount', 'float', 0);

            // ═══ BLOQUEIO: Não permitir alterar custos se há parcelas pagas ═══
            if ($orderId) {
                $financialModel = new Financial($this->db);
                if ($financialModel->hasAnyPaidInstallment($orderId)) {
                    $_SESSION['error'] = 'Não é possível adicionar custos extras porque existem parcelas já pagas. Estorne os pagamentos primeiro no módulo Financeiro.';
                    header('Location: ?page=pipeline&action=detail&id=' . $orderId);
                    exit;
                }
            }

            if ($orderId && $description && $amount != 0) {
                $orderModel = new Order($this->db);
                $orderModel->addExtraCost($orderId, $description, $amount);

                // ═══ Limpar confirmação de orçamento (cliente precisa reaprovar) ═══
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

        // ═══ BLOQUEIO: Não permitir remover custos se há parcelas pagas ═══
        if ($orderId) {
            $financialModel = new Financial($this->db);
            if ($financialModel->hasAnyPaidInstallment($orderId)) {
                $_SESSION['error'] = 'Não é possível remover custos extras porque existem parcelas já pagas. Estorne os pagamentos primeiro no módulo Financeiro.';
                header('Location: ?page=pipeline&action=detail&id=' . $orderId);
                exit;
            }
        }

        if ($costId) {
            $orderModel = new Order($this->db);
            $orderModel->deleteExtraCost($costId);

            // ═══ Limpar confirmação de orçamento (cliente precisa reaprovar) ═══
            $this->clearQuoteConfirmation($orderId);
        }

        header('Location: ?page=pipeline&action=detail&id=' . $orderId . '&status=extra_deleted');
        exit;
    }

    /**
     * Mover setor de produção de um item do pedido (AJAX)
     */
    public function moveSector() {
        header('Content-Type: application/json');
        
        $orderId = Input::post('order_id', 'int') ?: Input::get('order_id', 'int');
        $orderItemId = Input::post('order_item_id', 'int') ?: Input::get('order_item_id', 'int');
        $sectorId = Input::post('sector_id', 'int') ?: Input::get('sector_id', 'int');
        $action = Input::post('move_action') ?: Input::get('move_action', 'string', 'advance');
        $userId = $_SESSION['user_id'] ?? null;

        if (!$orderId || !$orderItemId || !$sectorId) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
            exit;
        }

        // Verificar permissão do usuário para este setor
        $userModel = new User($this->db);
        $allowedSectors = $userModel->getAllowedSectorIds($userId);
        if (!empty($allowedSectors) && !in_array((int)$sectorId, $allowedSectors)) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão para este setor']);
            exit;
        }

        $result = false;
        if ($action === 'advance') {
            $result = $this->pipelineModel->advanceItemSector($orderId, $orderItemId, $sectorId, $userId);
        } elseif ($action === 'revert') {
            $result = $this->pipelineModel->revertItemSector($orderId, $orderItemId, $sectorId, $userId);
        }

        if ($result) {
            $logger = new Logger($this->db);
            $logger->log('PRODUCTION_SECTOR_MOVE', "Order #$orderId item #$orderItemId sector #$sectorId action:$action");
        }

        echo json_encode(['success' => $result]);
        exit;
    }

    /**
     * Painel de Produção: visão por setor com tabs.
     * Dados carregados via PipelineDetailService.
     */
    public function productionBoard() {
        $userModel = new User($this->db);
        $userAllowedSectorIds = $userModel->getAllowedSectorIds($_SESSION['user_id'] ?? 0);

        $data = $this->detailService->loadProductionBoardData($userAllowedSectorIds);
        extract($data);

        require 'app/views/layout/header.php';
        require 'app/views/pipeline/production_board.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * API JSON: Buscar logs de um item do pedido (AJAX — usado pelo modal do painel de produção e detalhe)
     */
    public function getItemLogs() {
        header('Content-Type: application/json');
        $logModel = new OrderItemLog($this->db);
        $logModel->createTableIfNotExists();

        $orderItemId = Input::get('order_item_id', 'int');
        if (!$orderItemId) {
            echo json_encode(['success' => false, 'message' => 'Item não informado']);
            exit;
        }

        $logs = $logModel->getLogsByItem($orderItemId);
        echo json_encode(['success' => true, 'logs' => $logs]);
        exit;
    }

    /**
     * Adicionar log a um item do pedido (AJAX POST, com suporte a upload)
     * Suporta "todos os produtos" via order_item_ids[] + all_items=1
     */
    public function addItemLog() {
        header('Content-Type: application/json');
        $logModel = new OrderItemLog($this->db);
        $logModel->createTableIfNotExists();

        $orderId = Input::post('order_id', 'int');
        $orderItemId = Input::post('order_item_id', 'int');
        $allItems = Input::post('all_items');
        $orderItemIds = Input::postArray('order_item_ids');
        $message = Input::post('message');
        $userId = $_SESSION['user_id'] ?? null;

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
            exit;
        }

        // Se "todos os produtos" ou nem item individual
        if ($allItems && !empty($orderItemIds)) {
            // Registrar para todos os itens
        } elseif ($orderItemId) {
            $orderItemIds = [$orderItemId];
        } else {
            echo json_encode(['success' => false, 'message' => 'Selecione um produto']);
            exit;
        }

        // Processar upload se houver
        $filePath = null;
        $fileName = null;
        $fileType = null;

        if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $firstItemId = $orderItemIds[0] ?? 0;
            $uploadResult = $logModel->handleFileUpload($_FILES['file'], $orderId, $firstItemId);
            if (isset($uploadResult['error'])) {
                echo json_encode(['success' => false, 'message' => $uploadResult['error']]);
                exit;
            }
            $filePath = $uploadResult['file_path'];
            $fileName = $uploadResult['file_name'];
            $fileType = $uploadResult['file_type'];
        }

        // Precisa ter pelo menos mensagem ou arquivo
        if (empty($message) && empty($filePath)) {
            echo json_encode(['success' => false, 'message' => 'Informe uma mensagem ou envie um arquivo.']);
            exit;
        }

        $logIds = [];
        foreach ($orderItemIds as $iid) {
            $logId = $logModel->addLog($orderId, $iid, $userId, $message ?: null, $filePath, $fileName, $fileType);
            $logIds[] = $logId;
        }

        // Log do sistema
        $logger = new Logger($this->db);
        $itemCount = count($logIds);
        $logger->log('ITEM_LOG_ADDED', "Log added to order #$orderId for $itemCount item(s)");

        echo json_encode(['success' => true, 'log_ids' => $logIds]);
        exit;
    }

    /**
     * Excluir um log de item (AJAX POST)
     */
    public function deleteItemLog() {
        header('Content-Type: application/json');
        $logModel = new OrderItemLog($this->db);

        $logId = Input::post('log_id', 'int');
        $userId = $_SESSION['user_id'] ?? null;

        if (!$logId) {
            echo json_encode(['success' => false, 'message' => 'ID do log não informado']);
            exit;
        }

        $result = $logModel->deleteLog($logId, $userId);
        echo json_encode(['success' => $result]);
        exit;
    }

    /**
     * Imprimir Ordem de Produção.
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
     * Alternar item do checklist de preparação (AJAX POST)
     */
    public function togglePreparation() {
        header('Content-Type: application/json');
        $prepModel = new OrderPreparation($this->db);

        $orderId = Input::post('order_id', 'int');
        $key = Input::post('key');
        $userId = $_SESSION['user_id'] ?? null;

        if (!$orderId || !$key) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
            exit;
        }

        // Verificar se o pedido está na etapa de preparação
        $order = $this->pipelineModel->getOrderDetail($orderId);
        if (!$order || $order['pipeline_stage'] !== 'preparacao') {
            echo json_encode(['success' => false, 'message' => 'Pedido não está em preparação']);
            exit;
        }

        $checked = $prepModel->toggle($orderId, $key, $userId);

        // Log do sistema
        $logger = new Logger($this->db);
        $action = $checked ? 'checked' : 'unchecked';
        $logger->log('PREPARATION_TOGGLE', "Preparation '$key' $action for order #$orderId");

        echo json_encode(['success' => true, 'checked' => $checked]);
        exit;
    }

    /**
     * Imprimir cupom não fiscal (impressora térmica).
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

    /**
     * API JSON: Sincroniza parcelas do pedido (AJAX POST).
     * Delegado ao PipelineService.
     */
    public function syncInstallments() {
        header('Content-Type: application/json');

        $orderId       = Input::post('order_id', 'int');
        $paymentMethod = Input::post('payment_method');
        $numInst       = Input::post('installments', 'int') ?: 0;
        $downPayment   = Input::post('down_payment', 'float', 0);
        $discount      = Input::post('discount', 'float', 0);

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'ID do pedido não informado.']);
            exit;
        }

        // Sanitizar datas de vencimento customizadas
        $dueDates = [];
        $rawDueDates = $_POST['due_dates'] ?? [];
        if (is_array($rawDueDates)) {
            foreach ($rawDueDates as $num => $dateVal) {
                $sanitizedDate = Sanitizer::date($dateVal);
                if ($sanitizedDate) {
                    $dueDates[(int)$num] = $sanitizedDate;
                }
            }
        }

        $result = $this->pipelineService->syncInstallments($orderId, $paymentMethod, $numInst, $downPayment, $discount, $dueDates);
        echo json_encode($result);
        exit;
    }

    /**
     * API JSON: Atualiza a data de vencimento de uma parcela individual (AJAX POST)
     */
    public function updateInstallmentDueDate() {
        header('Content-Type: application/json');

        $installmentId = Input::post('installment_id', 'int');
        $dueDate       = Input::post('due_date', 'date');

        if (!$installmentId || !$dueDate) {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
            exit;
        }

        $financialModel = new Financial($this->db);
        $result = $financialModel->updateInstallmentDueDate($installmentId, $dueDate);

        if ($result) {
            $logger = new Logger($this->db);
            $logger->log('INSTALLMENT_DUE_DATE', "Updated due date of installment #$installmentId to $dueDate");
        }

        echo json_encode(['success' => (bool)$result]);
        exit;
    }
}
