<?php

namespace Akti\Controllers;

use Akti\Models\Supply;
use Akti\Models\Supplier;
use Akti\Utils\Input;

class SupplyController
{
    private \PDO $db;
    private Supply $supplyModel;
    private Supplier $supplierModel;

    public function __construct(\PDO $db, Supply $supplyModel, Supplier $supplierModel)
    {
        $this->db = $db;
        $this->supplyModel = $supplyModel;
        $this->supplierModel = $supplierModel;
    }

    // ──── CRUD (Fase 1) ────

    public function index()
    {
        $page = Input::get('p', 'int', 1);
        $filters = [
            'search'      => Input::get('search', 'string', ''),
            'category_id' => Input::get('category_id', 'int', 0) ?: null,
        ];
        $filters = array_filter($filters);

        $result = $this->supplyModel->readPaginated($page, 15, $filters);
        $supplies = $result['data'];
        $pagination = $result;
        $categories = $this->supplyModel->getCategories();

        require 'app/views/layout/header.php';
        require 'app/views/supplies/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create()
    {
        $supply = null;
        $nextCode = $this->supplyModel->generateNextCode();
        $categories = $this->supplyModel->getCategories();

        require 'app/views/layout/header.php';
        require 'app/views/supplies/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $data = [
            'category_id'    => Input::post('category_id', 'int', 0),
            'code'           => Input::post('code', 'string', ''),
            'name'           => Input::post('name', 'string', ''),
            'description'    => Input::post('description', 'string', ''),
            'unit_measure'   => Input::post('unit_measure', 'string', 'un'),
            'cost_price'     => Input::post('cost_price', 'float', 0),
            'min_stock'      => Input::post('min_stock', 'float', 0),
            'reorder_point'  => Input::post('reorder_point', 'float', 0),
            'waste_percent'  => Input::post('waste_percent', 'float', 0),
            'is_active'      => Input::post('is_active', 'int', 1),
            'notes'          => Input::post('notes', 'string', ''),
            'fiscal_ncm'     => Input::post('fiscal_ncm', 'string', ''),
            'fiscal_cest'    => Input::post('fiscal_cest', 'string', ''),
            'fiscal_origem'  => Input::post('fiscal_origem', 'string', ''),
            'fiscal_unidade' => Input::post('fiscal_unidade', 'string', ''),
        ];

        if (empty($data['name'])) {
            $_SESSION['flash_error'] = 'O nome do insumo é obrigatório.';
            header('Location: ?page=supplies&action=create');
            return;
        }

        if (empty($data['code'])) {
            $data['code'] = $this->supplyModel->generateNextCode();
        }

        if ($this->supplyModel->codeExists($data['code'])) {
            $_SESSION['flash_error'] = 'Já existe um insumo com este código.';
            header('Location: ?page=supplies&action=create');
            return;
        }

        $this->supplyModel->create($data);
        $_SESSION['flash_success'] = 'Insumo cadastrado com sucesso.';
        header('Location: ?page=supplies');
    }

    public function edit()
    {
        $id = Input::get('id', 'int', 0);
        $supply = $this->supplyModel->readOne($id);
        if (!$supply) {
            $_SESSION['flash_error'] = 'Insumo não encontrado.';
            header('Location: ?page=supplies');
            return;
        }

        $categories = $this->supplyModel->getCategories();
        $nextCode = null;

        require 'app/views/layout/header.php';
        require 'app/views/supplies/form.php';
        require 'app/views/layout/footer.php';
    }

    public function update()
    {
        $id = Input::post('id', 'int', 0);
        $data = [
            'category_id'    => Input::post('category_id', 'int', 0),
            'code'           => Input::post('code', 'string', ''),
            'name'           => Input::post('name', 'string', ''),
            'description'    => Input::post('description', 'string', ''),
            'unit_measure'   => Input::post('unit_measure', 'string', 'un'),
            'cost_price'     => Input::post('cost_price', 'float', 0),
            'min_stock'      => Input::post('min_stock', 'float', 0),
            'reorder_point'  => Input::post('reorder_point', 'float', 0),
            'waste_percent'  => Input::post('waste_percent', 'float', 0),
            'is_active'      => Input::post('is_active', 'int', 1),
            'notes'          => Input::post('notes', 'string', ''),
            'fiscal_ncm'     => Input::post('fiscal_ncm', 'string', ''),
            'fiscal_cest'    => Input::post('fiscal_cest', 'string', ''),
            'fiscal_origem'  => Input::post('fiscal_origem', 'string', ''),
            'fiscal_unidade' => Input::post('fiscal_unidade', 'string', ''),
        ];

        if (empty($data['name'])) {
            $_SESSION['flash_error'] = 'O nome do insumo é obrigatório.';
            header('Location: ?page=supplies&action=edit&id=' . $id);
            return;
        }

        if ($this->supplyModel->codeExists($data['code'], $id)) {
            $_SESSION['flash_error'] = 'Já existe um insumo com este código.';
            header('Location: ?page=supplies&action=edit&id=' . $id);
            return;
        }

        $this->supplyModel->update($id, $data);
        $_SESSION['flash_success'] = 'Insumo atualizado com sucesso.';
        header('Location: ?page=supplies');
    }

    public function delete()
    {
        $id = Input::get('id', 'int', 0);
        $this->supplyModel->delete($id);
        $_SESSION['flash_success'] = 'Insumo removido com sucesso.';
        header('Location: ?page=supplies');
    }

    // ──── Categorias AJAX (Fase 1) ────

    public function createCategoryAjax()
    {
        header('Content-Type: application/json');
        $name = Input::post('name', 'string', '');
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Nome é obrigatório.']);
            return;
        }
        $id = $this->supplyModel->createCategory(['name' => $name]);
        echo json_encode(['success' => true, 'id' => $id, 'name' => $name]);
    }

    public function getCategoriesAjax()
    {
        header('Content-Type: application/json');
        echo json_encode($this->supplyModel->getCategories());
    }

    public function searchSelect2()
    {
        header('Content-Type: application/json');
        $term = Input::get('term', 'string', '');
        echo json_encode(['results' => $this->supplyModel->searchSelect2($term)]);
    }

    // ──── Vínculo Fornecedor AJAX (Fase 2) ────

    public function getSuppliers()
    {
        header('Content-Type: application/json');
        $supplyId = Input::get('id', 'int', 0);
        $supplierId = Input::get('supplier_id', 'int', 0);

        if ($supplierId > 0) {
            echo json_encode(['items' => $this->supplyModel->getSupplierInsumos($supplierId)]);
            return;
        }

        echo json_encode($this->supplyModel->getSuppliers($supplyId));
    }

    public function linkSupplier()
    {
        header('Content-Type: application/json');
        $data = [
            'supply_id'         => Input::post('supply_id', 'int', 0),
            'supplier_id'       => Input::post('supplier_id', 'int', 0),
            'supplier_sku'      => Input::post('supplier_sku', 'string', ''),
            'supplier_name'     => Input::post('supplier_name', 'string', ''),
            'unit_price'        => Input::post('unit_price', 'float', 0),
            'min_order_qty'     => Input::post('min_order_qty', 'float', 1),
            'lead_time_days'    => Input::post('lead_time_days', 'int', 0) ?: null,
            'conversion_factor' => Input::post('conversion_factor', 'float', 1),
            'is_preferred'      => Input::post('is_preferred', 'int', 0),
            'notes'             => Input::post('notes', 'string', ''),
        ];

        if (!$data['supply_id'] || !$data['supplier_id']) {
            echo json_encode(['success' => false, 'message' => 'Insumo e fornecedor são obrigatórios.']);
            return;
        }

        try {
            $id = $this->supplyModel->linkSupplier($data);
            if ($data['is_preferred']) {
                $this->supplyModel->setPreferredSupplier($data['supply_id'], $data['supplier_id']);
            }
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                echo json_encode(['success' => false, 'message' => 'Este fornecedor já está vinculado a este insumo.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao vincular fornecedor.']);
            }
        }
    }

    public function updateSupplierLink()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int', 0);
        $data = [
            'supplier_sku'      => Input::post('supplier_sku', 'string', ''),
            'supplier_name'     => Input::post('supplier_name', 'string', ''),
            'unit_price'        => Input::post('unit_price', 'float', 0),
            'min_order_qty'     => Input::post('min_order_qty', 'float', 1),
            'lead_time_days'    => Input::post('lead_time_days', 'int', 0) ?: null,
            'conversion_factor' => Input::post('conversion_factor', 'float', 1),
            'is_preferred'      => Input::post('is_preferred', 'int', 0),
            'notes'             => Input::post('notes', 'string', ''),
        ];
        $this->supplyModel->updateSupplierLink($id, $data);
        echo json_encode(['success' => true]);
    }

    public function unlinkSupplier()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int', 0);
        $this->supplyModel->unlinkSupplier($id);
        echo json_encode(['success' => true]);
    }

    public function searchSuppliers()
    {
        header('Content-Type: application/json');
        $term = Input::get('term', 'string', '');
        $suppliers = $this->supplierModel->readAll();
        $results = [];
        foreach ($suppliers as $s) {
            $search = mb_strtolower($term);
            $name = mb_strtolower($s['company_name'] . ' ' . ($s['trade_name'] ?? ''));
            if (empty($term) || str_contains($name, $search)) {
                $results[] = [
                    'id'   => (int) $s['id'],
                    'text'  => $s['company_name'] . ($s['trade_name'] ? ' (' . $s['trade_name'] . ')' : ''),
                ];
            }
        }
        echo json_encode(['results' => $results]);
    }

    // ──── Histórico de Preços AJAX (Fase 5) ────

    public function getPriceHistory()
    {
        header('Content-Type: application/json');
        $supplyId = Input::get('id', 'int', 0);
        echo json_encode($this->supplyModel->getPriceHistory($supplyId));
    }

    // ──── BOM AJAX (Fase 6) ────

    public function getProductSupplies()
    {
        header('Content-Type: application/json');
        $productId = Input::get('product_id', 'int', 0);
        $supplies = $this->supplyModel->getProductSupplies($productId);

        foreach ($supplies as &$s) {
            $yieldQty = max((float)($s['yield_qty'] ?? 1), 0.0001);
            $perUnit = (float)$s['quantity'] / $yieldQty;
            $effective = $perUnit * (1 + $s['waste_percent'] / 100);
            $s['per_unit_qty'] = round($perUnit, 4);
            $s['effective_qty'] = round($effective, 4);
            $s['line_cost'] = round($effective * $s['cost_price'], 4);
        }
        unset($s);

        $totalCost = $this->supplyModel->calculateProductCost($productId);
        echo json_encode(['supplies' => $supplies, 'total_cost' => $totalCost]);
    }

    public function addProductSupply()
    {
        header('Content-Type: application/json');
        $data = [
            'product_id'    => Input::post('product_id', 'int', 0),
            'supply_id'     => Input::post('supply_id', 'int', 0),
            'quantity'      => Input::post('quantity', 'float', 0),
            'yield_qty'     => max(Input::post('yield_qty', 'float', 1), 0.0001),
            'unit_measure'  => Input::post('unit_measure', 'string', 'un'),
            'waste_percent' => Input::post('waste_percent', 'float', 0),
            'is_optional'   => Input::post('is_optional', 'int', 0),
            'notes'         => Input::post('notes', 'string', ''),
            'sort_order'    => Input::post('sort_order', 'int', 0),
        ];

        if (!$data['product_id'] || !$data['supply_id'] || $data['quantity'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Produto, insumo e quantidade são obrigatórios.']);
            return;
        }

        try {
            $id = $this->supplyModel->addProductSupply($data);
            $totalCost = $this->supplyModel->calculateProductCost($data['product_id']);
            echo json_encode(['success' => true, 'id' => $id, 'total_cost' => $totalCost]);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                echo json_encode(['success' => false, 'message' => 'Este insumo já está vinculado a este produto.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao vincular insumo.']);
            }
        }
    }

    public function updateProductSupply()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int', 0);
        $data = [
            'quantity'      => Input::post('quantity', 'float', 0),
            'yield_qty'     => max(Input::post('yield_qty', 'float', 1), 0.0001),
            'unit_measure'  => Input::post('unit_measure', 'string', 'un'),
            'waste_percent' => Input::post('waste_percent', 'float', 0),
            'is_optional'   => Input::post('is_optional', 'int', 0),
            'notes'         => Input::post('notes', 'string', ''),
            'sort_order'    => Input::post('sort_order', 'int', 0),
        ];
        $this->supplyModel->updateProductSupply($id, $data);
        $productId = Input::post('product_id', 'int', 0);
        $totalCost = $productId ? $this->supplyModel->calculateProductCost($productId) : 0;
        echo json_encode(['success' => true, 'total_cost' => $totalCost]);
    }

    public function removeProductSupply()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int', 0);
        $productId = Input::post('product_id', 'int', 0);
        $this->supplyModel->removeProductSupply($id);
        $totalCost = $productId ? $this->supplyModel->calculateProductCost($productId) : 0;
        echo json_encode(['success' => true, 'total_cost' => $totalCost]);
    }

    public function estimateConsumption()
    {
        header('Content-Type: application/json');
        $productId = Input::get('product_id', 'int', 0);
        $qty = Input::get('qty', 'float', 1);
        echo json_encode($this->supplyModel->estimateConsumption($productId, $qty));
    }

    public function getSupplyProducts()
    {
        header('Content-Type: application/json');
        $supplyId = Input::get('id', 'int', 0);
        echo json_encode($this->supplyModel->getSupplyProducts($supplyId));
    }

    // ──── Where Used Impact AJAX (Fase 7) ────

    public function getWhereUsedImpact()
    {
        header('Content-Type: application/json');
        $supplyId = Input::get('supply_id', 'int', 0);
        $newCMP = Input::get('new_cmp', 'float', 0);
        echo json_encode($this->supplyModel->getWhereUsedImpact($supplyId, $newCMP));
    }

    public function applyBOMCostUpdate()
    {
        header('Content-Type: application/json');
        $productIds = Input::post('product_ids', 'string', '');
        $ids = array_filter(array_map('intval', explode(',', $productIds)));
        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum produto informado.']);
            return;
        }
        $results = [];
        foreach ($ids as $pid) {
            $cost = $this->supplyModel->calculateProductCost($pid);
            $stmt = $this->db->prepare("UPDATE products SET cost_price = :cost WHERE id = :id");
            $stmt->execute([':cost' => $cost, ':id' => $pid]);
            $results[] = ['product_id' => $pid, 'new_cost' => $cost];
        }
        echo json_encode(['success' => true, 'updated' => $results]);
    }
}
