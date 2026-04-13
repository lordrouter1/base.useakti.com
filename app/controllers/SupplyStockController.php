<?php

namespace Akti\Controllers;

use Akti\Models\SupplyStock;
use Akti\Models\Supply;
use Akti\Models\Logger;
use Akti\Services\SupplyStockMovementService;
use Akti\Utils\Input;

class SupplyStockController extends BaseController
{
    private \PDO $db;
    private SupplyStock $stockModel;
    private Supply $supplyModel;
    private Logger $logger;
    private SupplyStockMovementService $movementService;

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

    public function entry(): void
    {
        $warehouses = $this->stockModel->getWarehouses();

        require 'app/views/layout/header.php';
        require 'app/views/supply_stock/entry.php';
        require 'app/views/layout/footer.php';
    }

    public function storeEntry(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $result = $this->movementService->processEntry(
            Input::post('warehouse_id', 'int', 0),
            Input::post('reason'),
            Input::postArray('items') ?: [],
            $_SESSION['user']['id'] ?? 0
        );

        echo json_encode($result);
        exit;
    }

    // ──── Saída ────

    public function exit(): void
    {
        $warehouses = $this->stockModel->getWarehouses();

        require 'app/views/layout/header.php';
        require 'app/views/supply_stock/exit.php';
        require 'app/views/layout/footer.php';
    }

    public function storeExit(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $result = $this->movementService->processExit(
            Input::post('warehouse_id', 'int', 0),
            Input::post('reason'),
            Input::postArray('items') ?: [],
            $_SESSION['user']['id'] ?? 0
        );

        echo json_encode($result);
        exit;
    }

    // ──── Transferência ────

    public function transfer(): void
    {
        $warehouses = $this->stockModel->getWarehouses();

        require 'app/views/layout/header.php';
        require 'app/views/supply_stock/transfer.php';
        require 'app/views/layout/footer.php';
    }

    public function storeTransfer(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $result = $this->movementService->processTransfer(
            Input::post('origin_warehouse_id', 'int', 0),
            Input::post('dest_warehouse_id', 'int', 0),
            Input::post('reason'),
            Input::postArray('items') ?: [],
            $_SESSION['user']['id'] ?? 0
        );

        echo json_encode($result);
        exit;
    }

    // ──── Ajuste ────

    public function adjust(): void
    {
        $warehouses = $this->stockModel->getWarehouses();

        require 'app/views/layout/header.php';
        require 'app/views/supply_stock/adjust.php';
        require 'app/views/layout/footer.php';
    }

    public function storeAdjust(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $result = $this->movementService->processAdjustment(
            Input::post('warehouse_id', 'int', 0),
            Input::post('reason'),
            Input::postArray('items') ?: [],
            $_SESSION['user']['id'] ?? 0
        );

        echo json_encode($result);
        exit;
    }

    // ──── Movimentações (listagem paginada via AJAX) ────

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

        echo json_encode([
            'success'     => true,
            'items'       => $result['data'],
            'total'       => $result['total'],
            'page'        => $result['current_page'],
            'per_page'    => $perPage,
            'total_pages' => $result['pages'],
        ]);
        exit;
    }

    // ──── AJAX helpers ────

    public function searchSupplies(): void
    {
        header('Content-Type: application/json');
        $term = Input::get('q');
        $results = $this->supplyModel->searchSelect2($term ?: '');
        echo json_encode(['results' => $results]);
        exit;
    }

    public function getStockInfo(): void
    {
        header('Content-Type: application/json');
        $supplyId    = Input::get('supply_id', 'int', 0);
        $warehouseId = Input::get('warehouse_id', 'int', 0);

        if (!$supplyId || !$warehouseId) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
            exit;
        }

        $batches = $this->stockModel->getBatchesBySupply($supplyId, $warehouseId);
        $total   = $this->stockModel->getTotalStock($supplyId);
        $supply  = $this->supplyModel->readOne($supplyId);

        echo json_encode([
            'success'  => true,
            'batches'  => $batches,
            'total'    => $total,
            'supply'   => $supply,
        ]);
        exit;
    }

    public function getBatches(): void
    {
        header('Content-Type: application/json');
        $supplyId    = Input::get('supply_id', 'int', 0);
        $warehouseId = Input::get('warehouse_id', 'int', 0);

        $batches = $this->stockModel->getBatchesBySupply($supplyId, $warehouseId);
        echo json_encode(['success' => true, 'batches' => $batches]);
        exit;
    }

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

        echo json_encode([
            'success'     => true,
            'items'       => $result['data'],
            'total'       => $result['total'],
            'page'        => $result['current_page'],
            'per_page'    => $perPage,
            'total_pages' => $result['pages'],
        ]);
        exit;
    }

    // ──── MRP / Reorder (Fase 8) ────

    public function reorderSuggestions(): void
    {
        header('Content-Type: application/json');
        $items = $this->stockModel->getReorderItems();
        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    }

    // ──── Dashboard KPIs ────

    public function getDashboard(): void
    {
        header('Content-Type: application/json');
        $warehouseId = Input::get('warehouse_id', 'int');
        $summary = $this->stockModel->getDashboardSummary($warehouseId ?: null);
        echo json_encode(['success' => true, 'data' => $summary]);
        exit;
    }
}
