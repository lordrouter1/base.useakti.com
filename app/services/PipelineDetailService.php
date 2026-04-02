<?php
namespace Akti\Services;

use Akti\Models\Pipeline;
use Akti\Models\Order;
use Akti\Models\Customer;
use Akti\Models\User;
use Akti\Models\Stock;
use Akti\Models\Product;
use Akti\Models\PriceTable;
use Akti\Models\OrderItemLog;
use Akti\Models\OrderPreparation;
use Akti\Models\PreparationStep;
use Akti\Models\CompanySettings;
use Akti\Models\Financial;
use PDO;

/**
 * Service responsável por agregar todos os dados necessários para a view de detalhes do pipeline.
 * Extraído do PipelineController::detail() (Fase 2 — Refatoração de Controllers Monolíticos).
 */
class PipelineDetailService
{
    private $db;
    private $pipelineModel;
    private $stockModel;

    public function __construct(PDO $db, Pipeline $pipelineModel, Stock $stockModel)
    {
        $this->db = $db;
        $this->pipelineModel = $pipelineModel;
        $this->stockModel = $stockModel;
    }

    /**
     * Carrega todos os dados necessários para exibir o detalhe de um pedido no pipeline.
     *
     * @param int $orderId ID do pedido
     * @return array|null Array com todos os dados ou null se pedido não encontrado
     */
    public function loadDetailData(int $orderId): ?array
    {
        $order = $this->pipelineModel->getOrderDetail($orderId);
        if (!$order) {
            return null;
        }

        $history = $this->pipelineModel->getHistory($orderId);
        $stages = Pipeline::$stages;
        $goals = $this->pipelineModel->getStageGoals();

        // Usuários para atribuição
        $userModel = new User($this->db);
        $users = $userModel->readAll();

        // Produtos e combinações de grade
        $productModel = new Product($this->db);
        $products = $productModel->readAll();

        $productCombinations = [];
        foreach ($products as $p) {
            $combos = $productModel->getActiveCombinations($p['id']);
            if (!empty($combos)) {
                $productCombinations[$p['id']] = $combos;
            }
        }

        // Itens e custos extras do pedido
        $orderModel = new Order($this->db);
        $orderItems = $orderModel->getItems($orderId);
        $extraCosts = $orderModel->getExtraCosts($orderId);

        // Preços específicos do cliente (tabela de preço)
        $priceTableModel = new PriceTable($this->db);
        $customerPrices = [];
        if (!empty($order['customer_id'])) {
            $customerPrices = $priceTableModel->getAllPricesForCustomer($order['customer_id']);
        }

        // Todas as tabelas de preço para o seletor
        $priceTables = $priceTableModel->readAll();

        // Identificar tabela de preço atual do pedido ou do cliente
        $currentPriceTableId = $order['price_table_id'] ?? null;
        if (!$currentPriceTableId && !empty($order['customer_id'])) {
            $customerModel = new Customer($this->db);
            $customerData = $customerModel->readOne($order['customer_id']);
            $currentPriceTableId = $customerData['price_table_id'] ?? null;
        }

        // Setores de produção
        $orderProductionSectors = [];
        $userAllowedSectorIds = [];
        $isProduction = in_array($order['pipeline_stage'], ['producao', 'preparacao']);
        if ($isProduction) {
            $this->pipelineModel->initOrderProductionSectors($orderId);
        }
        $orderProductionSectors = $this->pipelineModel->getOrderProductionSectors($orderId);
        $userAllowedSectorIds = $userModel->getAllowedSectorIds($_SESSION['user_id'] ?? 0);

        // Logs dos itens do pedido
        $logModel = new OrderItemLog($this->db);
        $logModel->createTableIfNotExists();
        $orderItemLogs = $logModel->getLogsByOrder($orderId);
        $orderItemLogCounts = $logModel->countLogsByOrderGrouped($orderId);

        // Checklist de preparação
        $prepModel = new OrderPreparation($this->db);
        $orderPreparationChecklist = $prepModel->getChecklist($orderId);

        // Etapas de preparo configuráveis (globais)
        $prepStepModel = new PreparationStep($this->db);
        $preparoItems = $prepStepModel->getActiveAsMap();

        // Dados da empresa
        $companySettings = new CompanySettings($this->db);
        $company = $companySettings->getAll();
        $companyAddress = $companySettings->getFormattedAddress();

        // Armazéns ativos
        $warehouses = $this->stockModel->getAllWarehouses(true);
        $defaultWarehouse = $this->stockModel->getDefaultWarehouse();

        // Deduções ativas do pedido
        $activeDeductions = $this->stockModel->getActiveDeductions($orderId);

        // Contagem de parcelas existentes
        $financialModel = new Financial($this->db);
        $existingInstallmentCount = $financialModel->countInstallments($orderId);
        $hasAnyPaidInstallment = $financialModel->hasAnyPaidInstallment($orderId);

        return compact(
            'order', 'history', 'stages', 'goals',
            'users', 'products', 'productCombinations',
            'orderItems', 'extraCosts',
            'customerPrices', 'priceTables', 'currentPriceTableId',
            'orderProductionSectors', 'userAllowedSectorIds',
            'orderItemLogs', 'orderItemLogCounts',
            'orderPreparationChecklist', 'preparoItems',
            'company', 'companyAddress',
            'warehouses', 'defaultWarehouse', 'activeDeductions',
            'existingInstallmentCount', 'hasAnyPaidInstallment'
        );
    }

    /**
     * Carrega dados para a view de impressão da ordem de produção.
     *
     * @param int $orderId ID do pedido
     * @return array|null Array com dados ou null se pedido não encontrado
     */
    public function loadPrintProductionData(int $orderId): ?array
    {
        $order = $this->pipelineModel->getOrderDetail($orderId);
        if (!$order) {
            return null;
        }

        // Inicializar setores se ainda não existem
        $this->pipelineModel->initOrderProductionSectors($orderId);
        $orderProductionSectors = $this->pipelineModel->getOrderProductionSectors($orderId);

        // Itens do pedido
        $orderModel = new Order($this->db);
        $orderItems = $orderModel->getItems($orderId);

        // Imagens em destaque dos produtos
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

        // Dados da empresa
        $companySettings = new CompanySettings($this->db);
        $company = $companySettings->getAll();
        $companyAddress = $companySettings->getFormattedAddress();

        // Checklist de preparação
        $prepModel = new OrderPreparation($this->db);
        $orderPreparationChecklist = $prepModel->getChecklist($orderId);

        // Etapas de preparo configuráveis (globais)
        $prepStepModel = new PreparationStep($this->db);
        $preparoItems = $prepStepModel->getActiveAsMap();

        // Logs dos itens do pedido
        $logModel = new OrderItemLog($this->db);
        $logModel->createTableIfNotExists();
        $orderItemLogs = $logModel->getLogsByOrder($orderId);

        return compact(
            'order', 'orderProductionSectors', 'orderItems', 'productImages',
            'company', 'companyAddress',
            'orderPreparationChecklist', 'preparoItems', 'orderItemLogs'
        );
    }

    /**
     * Carrega dados para a view de impressão do cupom térmico.
     *
     * @param int $orderId ID do pedido
     * @return array|null Array com dados ou null se pedido não encontrado
     */
    public function loadThermalReceiptData(int $orderId): ?array
    {
        $order = $this->pipelineModel->getOrderDetail($orderId);
        if (!$order) {
            return null;
        }

        $orderModel = new Order($this->db);
        $orderItems = $orderModel->getItems($orderId);
        $extraCosts = $orderModel->getExtraCosts($orderId);

        $companySettings = new CompanySettings($this->db);
        $company = $companySettings->getAll();
        $companyAddress = $companySettings->getFormattedAddress();

        $financialModel = new Financial($this->db);
        $installmentsList = $financialModel->getInstallments($orderId);

        return compact(
            'order', 'orderItems', 'extraCosts',
            'company', 'companyAddress', 'installmentsList'
        );
    }

    /**
     * Carrega dados para o painel de produção (production board).
     *
     * @param array $userAllowedSectorIds IDs dos setores permitidos ao usuário
     * @return array Array com boardData, itemLogCounts, stages
     */
    public function loadProductionBoardData(array $userAllowedSectorIds): array
    {
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

        return compact('boardData', 'itemLogCounts', 'stages');
    }
}
