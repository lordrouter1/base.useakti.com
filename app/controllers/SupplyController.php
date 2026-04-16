<?php

namespace Akti\Controllers;

use Akti\Models\Supply;
use Akti\Models\Supplier;
use Akti\Models\SupplySubstitute;
use Akti\Utils\Input;

/**
 * Class SupplyController.
 */
class SupplyController extends BaseController {
    private Supply $supplyModel;
    private Supplier $supplierModel;
    private SupplySubstitute $substituteModel;

    /**
     * Construtor da classe SupplyController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     * @param Supply $supplyModel Supply model
     * @param Supplier $supplierModel Supplier model
     */
    public function __construct(\PDO $db, Supply $supplyModel, Supplier $supplierModel)
    {
        $this->db = $db;
        $this->supplyModel = $supplyModel;
        $this->supplierModel = $supplierModel;
        $this->substituteModel = new SupplySubstitute($db);
    }

    // ──── CRUD (Fase 1) ────

    /**
     * Exibe a página de listagem.
     */
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

    /**
     * Cria um novo registro no banco de dados.
     */
    public function create()
    {
        $supply = null;
        $nextCode = $this->supplyModel->generateNextCode();
        $categories = $this->supplyModel->getCategories();

        require 'app/views/layout/header.php';
        require 'app/views/supplies/form.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Processa e armazena um novo registro.
     */
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
            'permite_fracionamento' => Input::post('permite_fracionamento', 'int', 1),
            'decimal_precision'     => Input::post('decimal_precision', 'int', 4),
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

    /**
     * Exibe o formulário de edição.
     */
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

    /**
     * Atualiza um registro existente.
     */
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
            'permite_fracionamento' => Input::post('permite_fracionamento', 'int', 1),
            'decimal_precision'     => Input::post('decimal_precision', 'int', 4),
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

    /**
     * Remove um registro pelo ID.
     */
    public function delete()
    {
        $id = Input::get('id', 'int', 0);
        $this->supplyModel->delete($id);
        $_SESSION['flash_success'] = 'Insumo removido com sucesso.';
        header('Location: ?page=supplies');
    }

    // ──── Categorias AJAX (Fase 1) ────

    /**
     * Create category ajax.
     */
    public function createCategoryAjax()
    {
        header('Content-Type: application/json');
        $name = Input::post('name', 'string', '');
        if (empty($name)) {
            $this->json(['success' => false, 'message' => 'Nome é obrigatório.']);}
        $id = $this->supplyModel->createCategory(['name' => $name]);
        $this->json(['success' => true, 'id' => $id, 'name' => $name]);
    }

    /**
     * Obtém dados específicos.
     */
    public function getCategoriesAjax()
    {
        header('Content-Type: application/json');
        $this->json($this->supplyModel->getCategories());
    }

    /**
     * Search select2.
     */
    public function searchSelect2()
    {
        header('Content-Type: application/json');
        $term = Input::get('term', 'string', '');
        $this->json(['results' => $this->supplyModel->searchSelect2($term)]);
    }

    // ──── Vínculo Fornecedor AJAX (Fase 2) ────

    /**
     * Obtém dados específicos.
     */
    public function getSuppliers()
    {
        header('Content-Type: application/json');
        $supplyId = Input::get('id', 'int', 0);
        $supplierId = Input::get('supplier_id', 'int', 0);

        if ($supplierId > 0) {
            $this->json(['items' => $this->supplyModel->getSupplierInsumos($supplierId)]);}

        $this->json($this->supplyModel->getSuppliers($supplyId));
    }

    /**
     * Link supplier.
     */
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
            $this->json(['success' => false, 'message' => 'Insumo e fornecedor são obrigatórios.']);}

        try {
            $id = $this->supplyModel->linkSupplier($data);
            if ($data['is_preferred']) {
                $this->supplyModel->setPreferredSupplier($data['supply_id'], $data['supplier_id']);
            }
            $this->json(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->json(['success' => false, 'message' => 'Este fornecedor já está vinculado a este insumo.']);
            } else {
                $this->json(['success' => false, 'message' => 'Erro ao vincular fornecedor.']);
            }
        }
    }

    /**
     * Update supplier link.
     */
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
        $this->json(['success' => true]);
    }

    /**
     * Unlink supplier.
     */
    public function unlinkSupplier()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int', 0);
        $this->supplyModel->unlinkSupplier($id);
        $this->json(['success' => true]);
    }

    /**
     * Search suppliers.
     */
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
        $this->json(['results' => $results]);
    }

    // ──── Histórico de Preços AJAX (Fase 5) ────

    /**
     * Obtém dados específicos.
     */
    public function getPriceHistory()
    {
        header('Content-Type: application/json');
        $supplyId = Input::get('id', 'int', 0);
        $this->json($this->supplyModel->getPriceHistory($supplyId));
    }

    // ──── BOM AJAX (Fase 6) ────

    /**
     * Obtém dados específicos.
     */
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
        $this->json(['supplies' => $supplies, 'total_cost' => $totalCost]);
    }

    /**
     * Add product supply.
     */
    public function addProductSupply()
    {
        header('Content-Type: application/json');
        $data = [
            'product_id'    => Input::post('product_id', 'int', 0),
            'supply_id'     => Input::post('supply_id', 'int', 0),
            'variation_id'  => Input::post('variation_id', 'int', 0) ?: null,
            'quantity'      => Input::post('quantity', 'float', 0),
            'yield_qty'     => max(Input::post('yield_qty', 'float', 1), 0.0001),
            'unit_measure'  => Input::post('unit_measure', 'string', 'un'),
            'waste_percent' => Input::post('waste_percent', 'float', 0),
            'loss_percent'  => Input::post('loss_percent', 'float', 0),
            'is_optional'   => Input::post('is_optional', 'int', 0),
            'notes'         => Input::post('notes', 'string', ''),
            'sort_order'    => Input::post('sort_order', 'int', 0),
        ];

        if (!$data['product_id'] || !$data['supply_id'] || $data['quantity'] <= 0) {
            $this->json(['success' => false, 'message' => 'Produto, insumo e quantidade são obrigatórios.']);}

        try {
            $id = $this->supplyModel->addProductSupply($data);
            $totalCost = $this->supplyModel->calculateProductCost($data['product_id']);
            $this->json(['success' => true, 'id' => $id, 'total_cost' => $totalCost]);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->json(['success' => false, 'message' => 'Este insumo já está vinculado a este produto.']);
            } else {
                $this->json(['success' => false, 'message' => 'Erro ao vincular insumo.']);
            }
        }
    }

    /**
     * Update product supply.
     */
    public function updateProductSupply()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int', 0);
        $data = [
            'variation_id'  => Input::post('variation_id', 'int', 0) ?: null,
            'quantity'      => Input::post('quantity', 'float', 0),
            'yield_qty'     => max(Input::post('yield_qty', 'float', 1), 0.0001),
            'unit_measure'  => Input::post('unit_measure', 'string', 'un'),
            'waste_percent' => Input::post('waste_percent', 'float', 0),
            'loss_percent'  => Input::post('loss_percent', 'float', 0),
            'is_optional'   => Input::post('is_optional', 'int', 0),
            'notes'         => Input::post('notes', 'string', ''),
            'sort_order'    => Input::post('sort_order', 'int', 0),
        ];
        $this->supplyModel->updateProductSupply($id, $data);
        $productId = Input::post('product_id', 'int', 0);
        $totalCost = $productId ? $this->supplyModel->calculateProductCost($productId) : 0;
        $this->json(['success' => true, 'total_cost' => $totalCost]);
    }

    /**
     * Remove product supply.
     */
    public function removeProductSupply()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int', 0);
        $productId = Input::post('product_id', 'int', 0);
        $this->supplyModel->removeProductSupply($id);
        $totalCost = $productId ? $this->supplyModel->calculateProductCost($productId) : 0;
        $this->json(['success' => true, 'total_cost' => $totalCost]);
    }

    /**
     * Estimate consumption.
     */
    public function estimateConsumption()
    {
        header('Content-Type: application/json');
        $productId = Input::get('product_id', 'int', 0);
        $qty = Input::get('qty', 'float', 1);
        $this->json($this->supplyModel->estimateConsumption($productId, $qty));
    }

    /**
     * Obtém dados específicos.
     */
    public function getSupplyProducts()
    {
        header('Content-Type: application/json');
        $supplyId = Input::get('id', 'int', 0);
        $this->json($this->supplyModel->getSupplyProducts($supplyId));
    }

    // ──── Where Used Impact AJAX (Fase 7) ────

    /**
     * Obtém dados específicos.
     */
    public function getWhereUsedImpact()
    {
        header('Content-Type: application/json');
        $supplyId = Input::get('supply_id', 'int', 0);
        $newCMP = Input::get('new_cmp', 'float', 0);
        $this->json($this->supplyModel->getWhereUsedImpact($supplyId, $newCMP));
    }

    /**
     * Apply b o m cost update.
     */
    public function applyBOMCostUpdate()
    {
        header('Content-Type: application/json');
        $productIds = Input::post('product_ids', 'string', '');
        $ids = array_filter(array_map('intval', explode(',', $productIds)));
        if (empty($ids)) {
            $this->json(['success' => false, 'message' => 'Nenhum produto informado.']);}
        $results = [];
        foreach ($ids as $pid) {
            $cost = $this->supplyModel->calculateProductCost($pid);
            $stmt = $this->db->prepare("UPDATE products SET cost_price = :cost WHERE id = :id");
            $stmt->execute([':cost' => $cost, ':id' => $pid]);
            $results[] = ['product_id' => $pid, 'new_cost' => $cost];
        }
        $this->json(['success' => true, 'updated' => $results]);
    }

    // ──── Substitutos AJAX (v2) ────

    /**
     * Retorna substitutos de um insumo.
     */
    public function getSubstitutes()
    {
        header('Content-Type: application/json');
        $supplyId = Input::get('id', 'int', 0);
        $this->json($this->substituteModel->getBySupply($supplyId));
    }

    /**
     * Adiciona substituto.
     */
    public function addSubstitute()
    {
        header('Content-Type: application/json');
        $data = [
            'supply_id'       => Input::post('supply_id', 'int', 0),
            'substitute_id'   => Input::post('substitute_id', 'int', 0),
            'conversion_rate' => Input::post('conversion_rate', 'float', 1.0),
            'priority'        => Input::post('priority', 'int', 1),
            'is_active'       => Input::post('is_active', 'int', 1),
            'notes'           => Input::post('notes', 'string', ''),
        ];

        if (!$data['supply_id'] || !$data['substitute_id']) {
            $this->json(['success' => false, 'message' => 'Insumo principal e substituto são obrigatórios.']);
            return;
        }

        if ($data['supply_id'] === $data['substitute_id']) {
            $this->json(['success' => false, 'message' => 'Um insumo não pode ser substituto de si mesmo.']);
            return;
        }

        try {
            $id = $this->substituteModel->create($data);
            $this->json(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->json(['success' => false, 'message' => 'Este substituto já está cadastrado.']);
            } else {
                $this->json(['success' => false, 'message' => 'Erro ao cadastrar substituto.']);
            }
        }
    }

    /**
     * Atualiza substituto.
     */
    public function updateSubstitute()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int', 0);
        $data = [
            'conversion_rate' => Input::post('conversion_rate', 'float', 1.0),
            'priority'        => Input::post('priority', 'int', 1),
            'is_active'       => Input::post('is_active', 'int', 1),
            'notes'           => Input::post('notes', 'string', ''),
        ];
        $this->substituteModel->update($id, $data);
        $this->json(['success' => true]);
    }

    /**
     * Remove substituto.
     */
    public function removeSubstitute()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int', 0);
        $this->substituteModel->delete($id);
        $this->json(['success' => true]);
    }
}
