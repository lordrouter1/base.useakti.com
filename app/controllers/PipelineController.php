<?php
namespace Akti\Controllers;

use Akti\Models\Pipeline;
use Akti\Models\Order;
use Akti\Models\Customer;
use Akti\Models\User;
use Akti\Models\Stock;
use Akti\Models\Product;
use Akti\Models\PriceTable;
use Akti\Models\Logger;
use Akti\Models\OrderItemLog;
use Akti\Models\OrderPreparation;
use Akti\Models\PreparationStep;
use Akti\Models\CompanySettings;
use Akti\Models\Financial;
use Akti\Core\ModuleBootloader;
use Akti\Utils\Input;
use Akti\Utils\Sanitizer;
use Database;
use PDO;

class PipelineController {

    private $pipelineModel;
    private $db;
    private $stockModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->pipelineModel = new Pipeline($this->db);
        $this->stockModel = new Stock($this->db);
        // Auto-migrate stock tables/columns
        $this->stockModel->ensureDeductionsTable();
        $this->stockModel->ensureDefaultColumn();
        $this->stockModel->ensureOrderWarehouseColumn();
    }

    /**
     * Gera automaticamente as parcelas de pagamento quando o pedido
     * chega nas etapas financeiro/concluido e ainda não possui parcelas.
     * Para boleto e cartão crédito: gera N parcelas conforme configurado.
     * Para outros meios (dinheiro, pix, etc.): gera 1 parcela única (à vista).
     */
    private function autoGenerateInstallments($orderId) {
        $financialModel = new Financial($this->db);
        
        // Verificar se já existem parcelas — não sobrescrever
        $existingCount = $financialModel->countInstallments($orderId);
        if ($existingCount > 0) {
            return false;
        }

        // Buscar dados do pedido
        $q = "SELECT total_amount, COALESCE(discount, 0) as discount, 
                     payment_method, COALESCE(installments, 0) as installments,
                     COALESCE(down_payment, 0) as down_payment
              FROM orders WHERE id = :id";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $orderId]);
        $order = $s->fetch(PDO::FETCH_ASSOC);

        if (!$order || (float)$order['total_amount'] <= 0) {
            return false;
        }

        $totalAmount = (float)$order['total_amount'] - (float)$order['discount'];
        if ($totalAmount <= 0) {
            return false;
        }

        $paymentMethod = $order['payment_method'] ?? '';
        $numInstallments = (int)$order['installments'];
        $downPayment = (float)$order['down_payment'];

        // Formas parceláveis: cartão crédito e, quando habilitado, boleto
        $parcelableMethods = ['cartao_credito'];
        if (ModuleBootloader::isModuleEnabled('boleto')) {
            $parcelableMethods[] = 'boleto';
        }

        if (in_array($paymentMethod, $parcelableMethods) && $numInstallments >= 2) {
            // Gerar N parcelas
            $financialModel->generateInstallments($orderId, $totalAmount, $numInstallments, $downPayment);
        } else {
            // Pagamento à vista (1 parcela única)
            $singleAmount = $totalAmount - $downPayment;
            if ($singleAmount <= 0) $singleAmount = $totalAmount;
            $financialModel->generateInstallments($orderId, $totalAmount, 1, $downPayment);
        }

        $logger = new Logger($this->db);
        $logger->log('INSTALLMENTS_AUTO', "Auto-generated installments for order #$orderId (method: $paymentMethod, installments: $numInstallments)");

        return true;
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
     * Zonas do pipeline para lógica de estoque:
     * - Pré-produção (contato, orcamento, venda): sem estoque deduzido
     * - Produção+ (producao, preparacao, envio, financeiro, concluido): estoque deduzido
     * 
     * Regras:
     * - Mover de pré-produção → produção+: pedir armazém, deduzir estoque
     * - Mover de produção+ → pré-produção: devolver estoque
     * - Mover dentro da mesma zona: nada
     * - Cancelado: se tinha estoque deduzido, devolve
     */
    private static $preProductionStages = ['contato', 'orcamento', 'venda'];
    private static $productionStages = ['producao', 'preparacao', 'envio', 'financeiro', 'concluido'];

    private function isPreProduction($stage) {
        return in_array($stage, self::$preProductionStages);
    }

    private function isProduction($stage) {
        return in_array($stage, self::$productionStages);
    }

    /**
     * Processa a lógica de estoque ao mudar de etapa.
     * Retorna array ['success' => bool, 'notes' => string, 'message' => string]
     */
    private function handleStockTransition($orderId, $currentStage, $newStage, $warehouseId = null, $userId = null) {
        $notes = '';
        $wasPreProd = $this->isPreProduction($currentStage);
        $willBeProd = $this->isProduction($newStage);
        $willBePreProd = $this->isPreProduction($newStage);
        $wasProd = $this->isProduction($currentStage);

        // ═══ PRÉ-PRODUÇÃO → PRODUÇÃO+: deduzir estoque ═══
        if ($wasPreProd && $willBeProd) {
            $orderModel = new Order($this->db);
            $productModel = new Product($this->db);
            $orderItems = $orderModel->getItems($orderId);

            // Determinar armazém: parâmetro > armazém do pedido > padrão
            if (!$warehouseId) {
                $stmtWh = $this->db->prepare("SELECT stock_warehouse_id FROM orders WHERE id = :id");
                $stmtWh->bindParam(':id', $orderId);
                $stmtWh->execute();
                $whRow = $stmtWh->fetch(PDO::FETCH_ASSOC);
                $warehouseId = $whRow['stock_warehouse_id'] ?? null;
            }
            if (!$warehouseId) {
                $defaultWarehouse = $this->stockModel->getDefaultWarehouse();
                $warehouseId = $defaultWarehouse ? $defaultWarehouse['id'] : null;
            }

            // Salvar armazém no pedido
            if ($warehouseId) {
                $stmtWh2 = $this->db->prepare("UPDATE orders SET stock_warehouse_id = :wid WHERE id = :id");
                $stmtWh2->bindParam(':wid', $warehouseId, PDO::PARAM_INT);
                $stmtWh2->bindParam(':id', $orderId, PDO::PARAM_INT);
                $stmtWh2->execute();
            }

            // Deduzir estoque para itens com controle ativo
            $deducted = 0;
            if ($warehouseId && !empty($orderItems)) {
                foreach ($orderItems as $item) {
                    $product = $productModel->readOne($item['product_id']);
                    if (!$product || empty($product['use_stock_control'])) {
                        continue;
                    }

                    $combinationId = $item['grade_combination_id'] ?? null;
                    $qty = (int)$item['quantity'];

                    // Registrar movimentação de saída
                    $movementId = $this->stockModel->addMovement([
                        'warehouse_id'   => $warehouseId,
                        'product_id'     => $item['product_id'],
                        'combination_id' => $combinationId,
                        'type'           => 'saida',
                        'quantity'       => $qty,
                        'reason'         => 'Dedução automática — Pedido #' . $orderId . ' entrou em ' . $newStage,
                        'reference_type' => 'order',
                        'reference_id'   => $orderId,
                    ]);

                    // Registrar dedução para possível reversão futura
                    $this->stockModel->addStockDeduction([
                        'order_id'       => $orderId,
                        'order_item_id'  => $item['id'],
                        'warehouse_id'   => $warehouseId,
                        'product_id'     => $item['product_id'],
                        'combination_id' => $combinationId,
                        'quantity'       => $qty,
                        'movement_id'    => $movementId,
                    ]);
                    $deducted++;
                }
            }

            if ($deducted > 0) {
                $notes = "Estoque deduzido: $deducted item(ns) do armazém.";
            }
        }

        // ═══ PRODUÇÃO+ → PRÉ-PRODUÇÃO: devolver estoque ═══
        if ($wasProd && $willBePreProd) {
            $reversed = $this->stockModel->reverseDeductions($orderId, $userId);
            if ($reversed > 0) {
                $notes = "Estoque devolvido: $reversed item(ns) retornados ao armazém.";
            }
        }

        // ═══ Qualquer → CANCELADO: devolver estoque se existir ═══
        if ($newStage === 'cancelado') {
            $reversed = $this->stockModel->reverseDeductions($orderId, $userId);
            if ($reversed > 0) {
                $notes = "Estoque devolvido: $reversed item(ns) retornados ao armazém (cancelamento).";
            }
        }

        return ['success' => true, 'notes' => $notes];
    }

    /**
     * Verifica se a transição de etapa precisa de seleção de armazém (para o frontend)
     */
    private function transitionNeedsWarehouse($currentStage, $newStage) {
        return $this->isPreProduction($currentStage) && $this->isProduction($newStage);
    }

    /**
     * Mover pedido para outra etapa (GET — usado no detalhe do pedido)
     * Integra lógica de dedução/devolução de estoque conforme zona.
     */
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

        // Buscar etapa atual do pedido
        $stmtCurrent = $this->db->prepare("SELECT pipeline_stage FROM orders WHERE id = :id");
        $stmtCurrent->bindParam(':id', $orderId);
        $stmtCurrent->execute();
        $currentOrder = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
        $currentStage = $currentOrder ? $currentOrder['pipeline_stage'] : null;

        // Processar lógica de estoque
        $stockResult = $this->handleStockTransition($orderId, $currentStage, $newStage, $warehouseId, $userId);
        if (!empty($stockResult['notes'])) {
            $notes = ($notes ? $notes . ' | ' : '') . $stockResult['notes'];
        }

        $this->pipelineModel->moveToStage($orderId, $newStage, $userId, $notes);

        // Log
        $logger = new Logger($this->db);
        $logger->log('PIPELINE_MOVE', "Order #$orderId moved from $currentStage to stage: $newStage");

        // ═══ AUTO-GERAR PARCELAS ao mover para financeiro/concluido ═══
        if (in_array($newStage, ['financeiro', 'concluido'])) {
            $this->autoGenerateInstallments($orderId);
        }

        header('Location: ?page=pipeline&status=moved');
        exit;
    }

    /**
     * Mover pedido via AJAX (drag-and-drop)
     * Se a transição requer armazém e nenhum foi informado, retorna needs_warehouse=true
     * para o frontend pedir ao usuário.
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

        // Buscar etapa atual
        $stmtCurrent = $this->db->prepare("SELECT pipeline_stage FROM orders WHERE id = :id");
        $stmtCurrent->bindParam(':id', $orderId);
        $stmtCurrent->execute();
        $currentOrder = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

        if (!$currentOrder) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
            exit;
        }

        $currentStage = $currentOrder['pipeline_stage'];

        if ($currentStage === $newStage) {
            echo json_encode(['success' => true, 'message' => 'Sem alteração']);
            exit;
        }

        // Se a transição precisa de armazém e não foi informado, retorna flag
        if ($this->transitionNeedsWarehouse($currentStage, $newStage) && !$warehouseId) {
            echo json_encode([
                'success' => false,
                'needs_warehouse' => true,
                'message' => 'Selecione o armazém para dedução de estoque.',
            ]);
            exit;
        }

        // Processar lógica de estoque
        $stockResult = $this->handleStockTransition($orderId, $currentStage, $newStage, $warehouseId, $userId);
        $notes = 'Movido via drag-and-drop';
        if (!empty($stockResult['notes'])) {
            $notes .= ' | ' . $stockResult['notes'];
        }

        $this->pipelineModel->moveToStage($orderId, $newStage, $userId, $notes);

        $logger = new Logger($this->db);
        $logger->log('PIPELINE_MOVE', "Order #$orderId dragged from $currentStage to $newStage");

        // ═══ AUTO-GERAR PARCELAS ao mover para financeiro/concluido ═══
        if (in_array($newStage, ['financeiro', 'concluido'])) {
            $this->autoGenerateInstallments($orderId);
        }

        echo json_encode(['success' => true, 'message' => 'Pedido movido com sucesso', 'stock_notes' => $stockResult['notes'] ?? '']);
        exit;
    }

    /**
     * Detalhes de um pedido no pipeline
     */
    public function detail() {
        $detailId = Input::get('id', 'int');
        if (!$detailId) {
            header('Location: ?page=pipeline');
            exit;
        }

        $order = $this->pipelineModel->getOrderDetail($detailId);
        if (!$order) {
            header('Location: ?page=pipeline');
            exit;
        }

        $history = $this->pipelineModel->getHistory($detailId);
        $stages = Pipeline::$stages;
        $goals = $this->pipelineModel->getStageGoals();

        // Buscar usuários para atribuição
        $userModel = new User($this->db);
        $usersStmt = $userModel->readAll();
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar produtos e itens do pedido (para seção de orçamento)
        $productModel = new Product($this->db);
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

        $orderModel = new Order($this->db);
        $orderItems = $orderModel->getItems($detailId);
        $extraCosts = $orderModel->getExtraCosts($detailId);

        // Carregar preços específicos do cliente (tabela de preço)
        $priceTableModel = new PriceTable($this->db);
        $customerPrices = [];
        if (!empty($order['customer_id'])) {
            $customerPrices = $priceTableModel->getAllPricesForCustomer($order['customer_id']);
        }

        // Carregar todas as tabelas de preço para o seletor
        $priceTables = $priceTableModel->readAll();

        // Identificar tabela de preço atual do pedido ou do cliente
        $currentPriceTableId = $order['price_table_id'] ?? null;
        if (!$currentPriceTableId && !empty($order['customer_id'])) {
            $customerModel = new Customer($this->db);
            $customerData = $customerModel->readOne($order['customer_id']);
            $currentPriceTableId = $customerData['price_table_id'] ?? null;
        }

        // Carregar setores de produção do pedido e permissões do usuário
        // Carrega sempre (inclusive para concluido/cancelado, para visualização)
        $orderProductionSectors = [];
        $userAllowedSectorIds = [];
        $isProduction = in_array($order['pipeline_stage'], ['producao', 'preparacao']);
        if ($isProduction) {
            // Garantir que setores existam (para pedidos que já estavam em produção antes do recurso)
            $this->pipelineModel->initOrderProductionSectors($detailId);
        }
        // Carregar setores mesmo em concluido/cancelado para exibição read-only
        $orderProductionSectors = $this->pipelineModel->getOrderProductionSectors($detailId);
        $userAllowedSectorIds = $userModel->getAllowedSectorIds($_SESSION['user_id'] ?? 0);

        // Carregar logs dos itens do pedido
        $logModel = new OrderItemLog($this->db);
        $logModel->createTableIfNotExists();
        $orderItemLogs = $logModel->getLogsByOrder($detailId);
        $orderItemLogCounts = $logModel->countLogsByOrderGrouped($detailId);

        // Carregar checklist de preparação do pedido
        $prepModel = new OrderPreparation($this->db);
        $orderPreparationChecklist = $prepModel->getChecklist($detailId);

        // Carregar etapas de preparo configuráveis (globais)
        $prepStepModel = new PreparationStep($this->db);
        $preparoItems = $prepStepModel->getActiveAsMap();

        // Carregar dados da empresa (para impressão de boletos e guias)
        $companySettings = new CompanySettings($this->db);
        $company = $companySettings->getAll();
        $companyAddress = $companySettings->getFormattedAddress();

        // Carregar armazéns ativos para seleção de estoque no pipeline
        $warehouses = $this->stockModel->getAllWarehouses(true);
        $defaultWarehouse = $this->stockModel->getDefaultWarehouse();

        // Carregar deduções ativas do pedido (para exibição no detalhe)
        $activeDeductions = $this->stockModel->getActiveDeductions($detailId);

        // Carregar contagem de parcelas existentes (order_installments)
        $financialModel = new Financial($this->db);
        $existingInstallmentCount = $financialModel->countInstallments($detailId);

        require 'app/views/layout/header.php';
        require 'app/views/pipeline/detail.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Atualizar detalhes do pedido (POST)
     */
    public function updateDetails() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id' => Input::post('id', 'int'),
                'priority' => Input::post('priority', 'enum', 'normal', ['baixa', 'normal', 'alta', 'urgente']),
                'assigned_to' => Input::post('assigned_to', 'int') ?: null,
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
                'nf_number' => Input::post('nf_number'),
                'nf_series' => Input::post('nf_series'),
                'nf_status' => Input::post('nf_status'),
                'nf_access_key' => Input::post('nf_access_key'),
                'nf_notes' => Input::post('nf_notes'),
            ];

            $this->pipelineModel->updateOrderDetails($data);

            $logger = new Logger($this->db);
            $logger->log('PIPELINE_UPDATE', "Updated order details #" . $data['id']);

            // ═══ AUTO-GERAR/REGENERAR PARCELAS ═══
            $orderId = $data['id'];
            $stmtStage = $this->db->prepare("SELECT pipeline_stage, total_amount FROM orders WHERE id = :id");
            $stmtStage->execute([':id' => $orderId]);
            $currentOrderData = $stmtStage->fetch(PDO::FETCH_ASSOC);
            $currentOrderStage = $currentOrderData['pipeline_stage'] ?? '';

            if (in_array($currentOrderStage, ['venda', 'financeiro', 'concluido']) && !empty($data['payment_method'])) {
                $financialModel = new Financial($this->db);
                $existingCount = $financialModel->countInstallments($orderId);

                if ($existingCount === 0) {
                    // Nenhuma parcela existe — gerar automaticamente
                    $this->autoGenerateInstallments($orderId);
                } else {
                    // Parcelas existem — verificar se a configuração de pagamento mudou
                    // Comparar com os dados reais das parcelas no banco
                    $existingInstallments = $financialModel->getInstallments($orderId);
                    // Contar apenas parcelas regulares (excluir entrada — installment_number = 0)
                    $regularInstallments = array_filter($existingInstallments, function($i) {
                        return (int)$i['installment_number'] > 0;
                    });
                    $existingRegularCount = count($regularInstallments);
                    $hasExistingDownPayment = !empty(array_filter($existingInstallments, function($i) {
                        return (int)$i['installment_number'] === 0;
                    }));

                    // Verificar se alguma parcela já foi paga — se sim, não regenerar automaticamente
                    $anyPaid = false;
                    foreach ($existingInstallments as $inst) {
                        if ($inst['status'] === 'pago') {
                            $anyPaid = true;
                            break;
                        }
                    }

                    if (!$anyPaid) {
                        // Nenhuma parcela paga — podemos regenerar se a config mudou
                        $newInstallments = (int)($data['installments'] ?? 0);
                        $newDownPayment = (float)($data['down_payment'] ?? 0);

                        // Determinar numero esperado de parcelas com base na forma de pagamento
                        $parcelableMethods = ['cartao_credito'];
                        if (ModuleBootloader::isModuleEnabled('boleto')) {
                            $parcelableMethods[] = 'boleto';
                        }
                        $isParcelable = in_array($data['payment_method'], $parcelableMethods);
                        $expectedRegularCount = ($isParcelable && $newInstallments >= 2) ? $newInstallments : 1;
                        $expectDownPayment = ($newDownPayment > 0);

                        // Regenerar se: número de parcelas mudou OU status da entrada mudou
                        $needsRegeneration = ($existingRegularCount !== $expectedRegularCount)
                            || ($hasExistingDownPayment !== $expectDownPayment);

                        if ($needsRegeneration) {
                            $totalAmount = (float)($currentOrderData['total_amount'] ?? 0) - (float)($data['discount'] ?? 0);
                            if ($totalAmount > 0) {
                                $financialModel->generateInstallments($orderId, $totalAmount, $expectedRegularCount, $newDownPayment);
                                $logger->log('INSTALLMENTS_REGENERATED', "Regenerated installments for order #$orderId (method: {$data['payment_method']}, count: $expectedRegularCount, down_payment: $newDownPayment)");
                            }
                        }
                    }
                }
            }

            // Se veio do botão "Imprimir Nota de Pedido", redirecionar com flag para abrir a nota
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
     * API JSON: Conta parcelas existentes de um pedido (AJAX GET)
     */
    public function countInstallments() {
        header('Content-Type: application/json');
        $orderId = Input::get('order_id', 'int');
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'ID do pedido não informado']);
            exit;
        }
        $financial = new Financial($this->db);
        $count = $financial->countInstallments($orderId);
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    }

    /**
     * API JSON: Remove todas as parcelas de um pedido (AJAX POST)
     */
    public function deleteInstallments() {
        header('Content-Type: application/json');
        $orderId = Input::post('order_id', 'int');
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'ID do pedido não informado']);
            exit;
        }
        $financial = new Financial($this->db);
        $deleted = $financial->deleteInstallmentsByOrder($orderId);

        // Limpar campos de parcelamento no pedido
        $q = "UPDATE orders SET installments = NULL, installment_value = NULL, down_payment = 0 WHERE id = :id";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $orderId]);

        $logger = new Logger($this->db);
        $logger->log('INSTALLMENTS_DELETED', "Deleted $deleted installments for order #$orderId (payment method changed)");

        echo json_encode(['success' => true, 'deleted' => $deleted]);
        exit;
    }

    /**
     * API JSON: Gera link de pagamento via Mercado Pago para pedido em etapa financeira.
     */
    public function generateMercadoPagoLink() {
        header('Content-Type: application/json');

        $orderId = Input::post('order_id', 'int');
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Pedido não informado.']);
            exit;
        }

        if (!ModuleBootloader::isModuleEnabled('financial')) {
            echo json_encode(['success' => false, 'message' => 'Módulo financeiro desativado para este tenant.']);
            exit;
        }

        $orderModel = new Order($this->db);
        $order = $orderModel->readOne($orderId);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
            exit;
        }

        if (($order['pipeline_stage'] ?? '') !== 'financeiro') {
            echo json_encode(['success' => false, 'message' => 'Link de pagamento disponível apenas para pedidos na etapa financeira.']);
            exit;
        }

        $settingsModel = new CompanySettings($this->db);
        $settings = $settingsModel->getAll();
        $accessToken = trim((string)($settings['mercadopago_access_token'] ?? getenv('MERCADOPAGO_ACCESS_TOKEN') ?? ''));

        if ($accessToken === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Access Token do Mercado Pago não configurado. Vá em Configurações → Boleto/Bancário.',
            ]);
            exit;
        }

        $grossTotal = (float)($order['total_amount'] ?? 0);
        $discount = (float)($order['discount'] ?? 0);
        $totalAmount = round(max(0, $grossTotal - $discount), 2);

        if ($totalAmount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Pedido com valor inválido para gerar link de pagamento.']);
            exit;
        }

        $baseUrl = $this->getAppBaseUrl();
        $externalRef = 'order_' . (int)$orderId . '_tenant_' . ($_SESSION['tenant']['database'] ?? 'default');

        $payload = [
            'items' => [[
                'id' => (string)$orderId,
                'title' => 'Pedido #' . str_pad((string)$orderId, 4, '0', STR_PAD_LEFT),
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => $totalAmount,
            ]],
            'payer' => [
                'name' => (string)($order['customer_name'] ?? ''),
                'email' => (string)($order['customer_email'] ?? ''),
            ],
            'external_reference' => $externalRef,
            'statement_descriptor' => substr(preg_replace('/[^A-Za-z0-9 ]/', '', (string)($settings['company_name'] ?? 'AKTI')), 0, 13),
            'back_urls' => [
                'success' => $baseUrl . '/?page=pipeline&action=detail&id=' . (int)$orderId . '&mp_status=success',
                'pending' => $baseUrl . '/?page=pipeline&action=detail&id=' . (int)$orderId . '&mp_status=pending',
                'failure' => $baseUrl . '/?page=pipeline&action=detail&id=' . (int)$orderId . '&mp_status=failure',
            ],
            'auto_return' => 'approved',
            'notification_url' => $baseUrl . '/?page=financial&action=payments',
            'metadata' => [
                'order_id' => (int)$orderId,
                'tenant' => (string)($_SESSION['tenant']['database'] ?? ''),
            ],
        ];

        $result = $this->createMercadoPagoPreference($accessToken, $payload);
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => $result['message']]);
            exit;
        }

        $link = $result['data']['init_point'] ?? $result['data']['sandbox_init_point'] ?? '';
        if (!$link) {
            echo json_encode(['success' => false, 'message' => 'Mercado Pago não retornou URL de pagamento.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'payment_url' => $link,
            'preference_id' => $result['data']['id'] ?? null,
        ]);
        exit;
    }

    private function createMercadoPagoPreference(string $accessToken, array $payload): array {
        $url = 'https://api.mercadopago.com/checkout/preferences';

        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'Extensão cURL não disponível no servidor.'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
                'X-Idempotency-Key: akti-' . uniqid('', true),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError) {
            return ['success' => false, 'message' => 'Falha ao conectar com Mercado Pago: ' . $curlError];
        }

        $data = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $mpMessage = $data['message'] ?? $data['error'] ?? 'Erro ao gerar link no Mercado Pago.';
            return ['success' => false, 'message' => $mpMessage];
        }

        return ['success' => true, 'data' => is_array($data) ? $data : []];
    }

    private function getAppBaseUrl(): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
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
     * API JSON: pedidos atrasados (para notificações)
     */
    public function alerts() {
        $delayed = $this->pipelineModel->getDelayedOrders();
        header('Content-Type: application/json');
        echo json_encode(['delayed' => $delayed, 'count' => count($delayed)]);
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
     * API JSON: Verifica disponibilidade de estoque dos itens de um pedido num armazém (AJAX)
     * Retorna: warehouses, defaultWarehouseId, items (com stock disponível), allFromStock
     */
    public function checkOrderStock() {
        header('Content-Type: application/json');

        $orderId = Input::get('order_id', 'int');
        $warehouseId = Input::get('warehouse_id', 'int');

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Pedido não informado']);
            exit;
        }

        $orderModel = new Order($this->db);
        $productModel = new Product($this->db);
        $orderItems = $orderModel->getItems($orderId);

        $warehouses = $this->stockModel->getAllWarehouses(true);
        $defaultWarehouse = $this->stockModel->getDefaultWarehouse();
        $defaultWarehouseId = $defaultWarehouse ? $defaultWarehouse['id'] : null;

        // Se nenhum armazém informado, usar o padrão
        if (!$warehouseId && $defaultWarehouseId) {
            $warehouseId = $defaultWarehouseId;
        }

        $items = [];
        $allFromStock = true;

        if (!empty($orderItems)) {
            foreach ($orderItems as $item) {
                $product = $productModel->readOne($item['product_id']);
                $useStock = $product && !empty($product['use_stock_control']);
                $combinationId = $item['grade_combination_id'] ?? null;

                $stockQty = 0;
                if ($useStock && $warehouseId) {
                    $stockQty = $this->stockModel->getProductStockInWarehouse($warehouseId, $item['product_id'], $combinationId);
                }

                $sufficient = !$useStock || ($warehouseId && $stockQty >= (int)$item['quantity']);
                if ($useStock && !$sufficient) {
                    $allFromStock = false;
                }

                $items[] = [
                    'id' => $item['id'],
                    'product_name' => $item['product_name'] ?? ($product['name'] ?? '—'),
                    'combination_label' => $item['combination_label'] ?? null,
                    'quantity' => (int)$item['quantity'],
                    'use_stock_control' => $useStock,
                    'stock_available' => (float)$stockQty,
                    'sufficient' => $sufficient,
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'warehouses' => $warehouses,
            'default_warehouse_id' => $defaultWarehouseId,
            'warehouse_id' => $warehouseId,
            'items' => $items,
            'all_from_stock' => $allFromStock,
        ]);
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

            if ($orderId && $description && $amount != 0) {
                $orderModel = new Order($this->db);
                $orderModel->addExtraCost($orderId, $description, $amount);
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

        if ($costId) {
            $orderModel = new Order($this->db);
            $orderModel->deleteExtraCost($costId);
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
     * Painel de Produção: visão por setor com tabs
     */
    public function productionBoard() {
        $userModel = new User($this->db);
        $userAllowedSectorIds = $userModel->getAllowedSectorIds($_SESSION['user_id'] ?? 0);

        // Garantir que todos os pedidos em produção tenham setores inicializados
        $stmtOrders = $this->db->prepare("SELECT id FROM orders WHERE pipeline_stage = 'producao' AND status != 'cancelado'");
        $stmtOrders->execute();
        $prodOrders = $stmtOrders->fetchAll(PDO::FETCH_COLUMN);
        foreach ($prodOrders as $oid) {
            $this->pipelineModel->initOrderProductionSectors($oid);
        }
        
        $boardData = $this->pipelineModel->getProductionBoardData($userAllowedSectorIds);
        
        // Carregar contagem de logs por item para badges
        $logModel = new OrderItemLog($this->db);
        $logModel->createTableIfNotExists();
        $allItemIds = [];
        foreach ($boardData as &$sec) {
            foreach ($sec['items'] as &$it) {
                $allItemIds[] = $it['order_item_id'];
            }
            unset($it);
        }
        unset($sec);
        // Buscar contagens em batch
        $itemLogCounts = [];
        if (!empty($allItemIds)) {
            $placeholders = implode(',', array_fill(0, count($allItemIds), '?'));
            $stmtCounts = $this->db->prepare("SELECT order_item_id, COUNT(*) as total FROM order_item_logs WHERE order_item_id IN ($placeholders) GROUP BY order_item_id");
            $stmtCounts->execute($allItemIds);
            foreach ($stmtCounts->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $itemLogCounts[$row['order_item_id']] = (int)$row['total'];
            }
        }

        $stages = Pipeline::$stages;

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
     * Imprimir Ordem de Produção
     */
    public function printProductionOrder() {
        $printId = Input::get('id', 'int');
        if (!$printId) {
            header('Location: ?page=pipeline');
            exit;
        }

        $order = $this->pipelineModel->getOrderDetail($printId);
        if (!$order) {
            header('Location: ?page=pipeline');
            exit;
        }

        // Inicializar setores se ainda não existem
        $this->pipelineModel->initOrderProductionSectors($printId);

        // Carregar setores de produção do pedido
        $orderProductionSectors = $this->pipelineModel->getOrderProductionSectors($printId);

        // Carregar itens do pedido
        $orderModel = new Order($this->db);
        $orderItems = $orderModel->getItems($printId);

        // Carregar imagens em destaque dos produtos do pedido
        $productModel = new Product($this->db);
        $productImages = [];
        foreach ($orderItems as $item) {
            $pid = $item['product_id'];
            if (!isset($productImages[$pid])) {
                $images = $productModel->getImages($pid);
                $mainImage = null;
                foreach ($images as $img) {
                    if ($img['is_main']) { $mainImage = $img['image_path']; break; }
                }
                if (!$mainImage && !empty($images)) {
                    $mainImage = $images[0]['image_path'];
                }
                $productImages[$pid] = $mainImage;
            }
        }

        // Carregar dados da empresa
        $companySettings = new CompanySettings($this->db);
        $company = $companySettings->getAll();
        $companyAddress = $companySettings->getFormattedAddress();

        // Carregar checklist de preparação do pedido
        $prepModel = new OrderPreparation($this->db);
        $orderPreparationChecklist = $prepModel->getChecklist($printId);

        // Carregar etapas de preparo configuráveis (globais)
        $prepStepModel = new PreparationStep($this->db);
        $preparoItems = $prepStepModel->getActiveAsMap();

        // Carregar logs dos itens do pedido
        $logModel = new OrderItemLog($this->db);
        $logModel->createTableIfNotExists();
        $orderItemLogs = $logModel->getLogsByOrder($printId);

        // Renderizar a view de impressão (sem header/footer do sistema)
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
}
