<?php

namespace Akti\Controllers;

use Akti\Models\SupplyStock;
use Akti\Models\Supply;
use Akti\Models\Logger;
use Akti\Services\SupplyStockMovementService;
use Akti\Utils\Input;

/**
 * Class SupplyStockController.
 */
class SupplyStockController extends BaseController
{
    private SupplyStock $stockModel;
    private Supply $supplyModel;
    private Logger $logger;
    private SupplyStockMovementService $movementService;

    /**
     * Construtor da classe SupplyStockController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     * @param SupplyStock $stockModel Stock model
     * @param Supply $supplyModel Supply model
     * @param Logger $logger Logger
     * @param SupplyStockMovementService $movementService Movement service
     */
    public function __construct(
        \PDO $db,
        SupplyStock $stockModel,
        Supply $supplyModel,
        Logger $logger,
        SupplyStockMovementService $movementService
    ) {
        $this->db = $db;
        $this->stockModel = $stockModel;
        $this->supplyModel = $supplyModel;
        $this->logger = $logger;
        $this->movementService = $movementService;
    }

    // ──── Página principal ────

    /**
     * Exibe a página de listagem.
     * @return void
     */
    public function index(): void
    {
        $warehouseId = Input::get('warehouse_id', 'int');
        $search      = Input::get('search');
        $lowStock    = Input::get('low_stock') === '1';

        $warehouses     = $this->stockModel->getWarehouses();
        $summary        = $this->stockModel->getDashboardSummary($warehouseId ?: null);
        $lowStockItems  = $this->stockModel->getLowStockItems(5);
        $expiringItems  = $this->stockModel->getExpiringBatches(30, 5);

        require 'app/views/layout/header.php';
        require 'app/views/supply_stock/index.php';
        require 'app/views/layout/footer.php';
    }

    // ──── Entrada ────

    /**
     * Entry.
     * @return void
     */
    public function entry(): void
    {
        $warehouses = $this->stockModel->getWarehouses();

        require 'app/views/layout/header.php';
        require 'app/views/supply_stock/entry.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Store entry.
     * @return void
     */
    public function storeEntry(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);
        }

        $result = $this->movementService->processEntry(
            Input::post('warehouse_id', 'int', 0),
            Input::post('reason'),
            Input::postArray('items') ?: [],
            $_SESSION['user']['id'] ?? 0
        );

        $this->json($result);
    }

    // ──── Saída ────

    /**
     * .
     * @return void
     */
    public function exit(): void
    {
        $warehouses = $this->stockModel->getWarehouses();

        require 'app/views/layout/header.php';
        require 'app/views/supply_stock/exit.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Store exit.
     * @return void
     */
    public function storeExit(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);
        }

        $result = $this->movementService->processExit(
            Input::post('warehouse_id', 'int', 0),
            Input::post('reason'),
            Input::postArray('items') ?: [],
            $_SESSION['user']['id'] ?? 0
        );

        $this->json($result);
    }

    // ──── Transferência ────

    /**
     * Transfer.
     * @return void
     */
    public function transfer(): void
    {
        $warehouses = $this->stockModel->getWarehouses();

        require 'app/views/layout/header.php';
        require 'app/views/supply_stock/transfer.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Store transfer.
     * @return void
     */
    public function storeTransfer(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);
        }

        $result = $this->movementService->processTransfer(
            Input::post('origin_warehouse_id', 'int', 0),
            Input::post('dest_warehouse_id', 'int', 0),
            Input::post('reason'),
            Input::postArray('items') ?: [],
            $_SESSION['user']['id'] ?? 0
        );

        $this->json($result);
    }

    // ──── Ajuste ────

    /**
     * Adjust.
     * @return void
     */
    public function adjust(): void
    {
        $warehouses = $this->stockModel->getWarehouses();

        require 'app/views/layout/header.php';
        require 'app/views/supply_stock/adjust.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Store adjust.
     * @return void
     */
    public function storeAdjust(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);
        }

        $result = $this->movementService->processAdjustment(
            Input::post('warehouse_id', 'int', 0),
            Input::post('reason'),
            Input::postArray('items') ?: [],
            $_SESSION['user']['id'] ?? 0
        );

        $this->json($result);
    }

    // ──── Movimentações (listagem paginada via AJAX) ────

    /**
     * Move registro de posição.
     * @return void
     */
    public function movements(): void
    {
        if (Input::get('format') === 'json') {
            $this->getMovementsJson();
            return;
        }

        $warehouses = $this->stockModel->getWarehouses();

        require 'app/views/layout/header.php';
        require 'app/views/supply_stock/movements.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Obtém dados específicos.
     * @return void
     */
    private function getMovementsJson(): void
    {
        header('Content-Type: application/json');

        $filters = [
            'warehouse_id' => Input::get('warehouse_id', 'int'),
            'supply_id'    => Input::get('supply_id', 'int'),
            'type'         => Input::get('type'),
            'date_from'    => Input::get('date_from', 'date'),
            'date_to'      => Input::get('date_to', 'date'),
        ];
        $page    = max(1, Input::get('pg', 'int', 1));
        $perPage = Input::get('per_page', 'int', 25);

        $result = $this->stockModel->getMovements($filters, $page, $perPage);

        $this->json([
            'success'     => true,
            'items'       => $result['data'],
            'total'       => $result['total'],
            'page'        => $result['current_page'],
            'per_page'    => $perPage,
            'total_pages' => $result['pages'],
        ]);
    }

    // ──── AJAX helpers ────

    /**
     * Search supplies.
     * @return void
     */
    public function searchSupplies(): void
    {
        header('Content-Type: application/json');
        $term = Input::get('q');
        $results = $this->supplyModel->searchSelect2($term ?: '');
        $this->json(['results' => $results]);
    }

    /**
     * Obtém dados específicos.
     * @return void
     */
    public function getStockInfo(): void
    {
        header('Content-Type: application/json');
        $supplyId    = Input::get('supply_id', 'int', 0);
        $warehouseId = Input::get('warehouse_id', 'int', 0);

        if (!$supplyId || !$warehouseId) {
            $this->json(['success' => false, 'message' => 'Parâmetros inválidos.']);
        }

        $batches = $this->stockModel->getBatchesBySupply($supplyId, $warehouseId);
        $total   = $this->stockModel->getTotalStock($supplyId);
        $supply  = $this->supplyModel->readOne($supplyId);

        $this->json([
            'success'  => true,
            'batches'  => $batches,
            'total'    => $total,
            'supply'   => $supply,
        ]);
    }

    /**
     * Obtém dados específicos.
     * @return void
     */
    public function getBatches(): void
    {
        header('Content-Type: application/json');
        $supplyId    = Input::get('supply_id', 'int', 0);
        $warehouseId = Input::get('warehouse_id', 'int', 0);

        $batches = $this->stockModel->getBatchesBySupply($supplyId, $warehouseId);
        $this->json(['success' => true, 'batches' => $batches]);
    }

    /**
     * Obtém dados específicos.
     * @return void
     */
    public function getStockItems(): void
    {
        header('Content-Type: application/json');

        $filters = [
            'warehouse_id'  => Input::get('warehouse_id', 'int'),
            'search'        => Input::get('search'),
            'low_stock_only' => Input::get('low_stock') === '1',
        ];
        $page    = max(1, Input::get('pg', 'int', 1));
        $perPage = Input::get('per_page', 'int', 20);

        $result = $this->stockModel->getItems($filters, $page, $perPage);

        $this->json([
            'success'     => true,
            'items'       => $result['data'],
            'total'       => $result['total'],
            'page'        => $result['current_page'],
            'per_page'    => $perPage,
            'total_pages' => $result['pages'],
        ]);
    }

    // ──── MRP / Reorder (Fase 8) ────

    /**
     * Reordena registros.
     * @return void
     */
    public function reorderSuggestions(): void
    {
        header('Content-Type: application/json');
        $items = $this->stockModel->getReorderItems();
        $this->json(['success' => true, 'items' => $items]);
    }

    // ──── Dashboard KPIs ────

    /**
     * Obtém dados específicos.
     * @return void
     */
    public function getDashboard(): void
    {
        header('Content-Type: application/json');
        $warehouseId = Input::get('warehouse_id', 'int');
        $summary = $this->stockModel->getDashboardSummary($warehouseId ?: null);
        $this->json(['success' => true, 'data' => $summary]);
    }
}
