# 07 — Arquitetura Técnica

## 1. Componentes a Criar

### 1.1 Models

| Arquivo | Namespace | Descrição |
|---------|-----------|-----------|
| `app/models/Supply.php` | `Akti\Models` | CRUD de insumos, categorias, vínculo fornecedor, vínculo produto (BOM) |
| `app/models/SupplyStock.php` | `Akti\Models` | Posição de estoque e movimentações de insumos |

### 1.2 Controllers

| Arquivo | Namespace | Descrição |
|---------|-----------|-----------|
| `app/controllers/SupplyController.php` | `Akti\Controllers` | CRUD de insumos, categorias, vínculos |
| `app/controllers/SupplyStockController.php` | `Akti\Controllers` | Estoque e movimentações de insumos |

### 1.3 Services

| Arquivo | Namespace | Descrição |
|---------|-----------|-----------|
| `app/services/SupplyStockMovementService.php` | `Akti\Services` | Processamento de movimentações de estoque, CMP, conversão UOM, FEFO |

### 1.4 Views

| Diretório/Arquivo | Descrição |
|-------------------|-----------|
| `app/views/supplies/index.php` | Listagem de insumos |
| `app/views/supplies/form.php` | Formulário create/edit (reusável) |
| `app/views/supplies/categories.php` | Gerenciamento de categorias |
| `app/views/supplies/_price_chart.php` | Partial: gráfico histórico de preços (Chart.js) |
| `app/views/supplies/_impact_modal.php` | Partial: modal de análise de impacto (Where Used) |
| `app/views/supply_stock/index.php` | Dashboard de estoque de insumos |
| `app/views/supply_stock/entry.php` | Formulário de entrada (com lote/validade/fornecedor) |
| `app/views/supply_stock/exit.php` | Formulário de saída (com seleção FEFO de lote) |
| `app/views/supply_stock/transfer.php` | Formulário de transferência |
| `app/views/supply_stock/movements.php` | Histórico de movimentações (com coluna lote) |
| `app/views/supply_stock/_reorder_card.php` | Partial: card de sugestões de compra (MRP) |
| `app/views/supply_stock/_expiring_card.php` | Partial: card de lotes próximos do vencimento |

### 1.5 Scripts/Jobs

| Arquivo | Descrição |
|---------|-----------|
| `scripts/check_supply_reorder.php` | Cron job: verificar pontos de pedido e gerar alertas |

### 1.5 Migrations

| Arquivo | Descrição |
|---------|-----------|
| `sql/update_YYYYMMDDHHMM_N_criar_tabelas_insumos.sql` | Criação de todas as tabelas |

---

## 2. Estrutura do Model — Supply

```php
<?php

namespace Akti\Models;

use Akti\Bootstrap\EventDispatcher;
use Akti\Core\Event;

class Supply
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    // ── CRUD Insumos ─────────────────────────────────

    public function readAll(): array { /* ... */ }

    public function readPaginated(int $page, int $perPage, array $filters = []): array { /* ... */ }

    public function readOne(int $id): array|false { /* ... */ }

    public function create(array $data): int { /* ... */ }

    public function update(int $id, array $data): bool { /* ... */ }

    public function delete(int $id): bool { /* soft delete */ }

    public function countAll(array $filters = []): int { /* ... */ }

    public function generateNextCode(): string { /* INS-XXXX */ }

    public function codeExists(string $code, ?int $excludeId = null): bool { /* ... */ }

    // ── Categorias ─────────────────────────────────

    public function getCategories(): array { /* ... */ }

    public function createCategory(array $data): int { /* ... */ }

    public function updateCategory(int $id, array $data): bool { /* ... */ }

    public function deleteCategory(int $id): bool { /* ... */ }

    // ── Vínculo Fornecedor ──────────────────────────

    public function getSuppliers(int $supplyId): array { /* ... */ }

    public function linkSupplier(array $data): int { /* ... */ }

    public function updateSupplierLink(int $id, array $data): bool { /* ... */ }

    public function unlinkSupplier(int $id): bool { /* ... */ }

    public function setPreferredSupplier(int $supplyId, int $supplierId): bool { /* ... */ }

    public function getPreferredSupplier(int $supplyId): array|false { /* ... */ }

    // ── Histórico de Preços & CMP ────────────────────

    public function getPriceHistory(int $supplyId, ?int $supplierId = null, int $limit = 50): array { /* ... */ }

    public function recordPriceHistory(array $data): int { /* supply_id, supplier_id, unit_price, source, ... */ }

    public function calculateWeightedAverageCost(int $supplyId): float { /* CMP atual */ }

    // ── BOM (Bill of Materials) ──────────────────────

    public function getProductSupplies(int $productId): array { /* ... */ }

    public function getSupplyProducts(int $supplyId): array { /* ... */ }

    public function addProductSupply(array $data): int { /* ... */ }

    public function updateProductSupply(int $id, array $data): bool { /* ... */ }

    public function removeProductSupply(int $id): bool { /* ... */ }

    public function calculateProductCost(int $productId): float { /* usa CMP do insumo */ }

    public function estimateConsumption(int $productId, float $qty): array { /* ... */ }

    // ── Where Used (Onde é Usado) ────────────────────

    public function getWhereUsedImpact(int $supplyId, float $newCMP): array { /* impacto em produtos */ }

    public function getAffectedProducts(int $supplyId): array { /* IDs de produtos que usam o insumo */ }

    // ── Busca ────────────────────────────────────────

    public function searchSelect2(string $term, int $limit = 20): array { /* ... */ }

    public function getStockSummary(int $supplyId): array { /* ... */ }
}
```

---

## 3. Estrutura do Controller — SupplyController

```php
<?php

namespace Akti\Controllers;

use Akti\Models\Supply;
use Akti\Models\Supplier;
use Akti\Core\Logger;
use Akti\Utils\Input;

class SupplyController extends BaseController
{
    private Supply $supplyModel;
    private Supplier $supplierModel;
    private Logger $logger;

    public function __construct(
        \PDO $db,
        Supply $supplyModel,
        Supplier $supplierModel,
        Logger $logger
    ) {
        parent::__construct($db);
        $this->supplyModel = $supplyModel;
        $this->supplierModel = $supplierModel;
        $this->logger = $logger;
    }

    // ── CRUD ──────────────────────────────────────

    public function index(): void
    {
        // Filtros: category_id, search, is_active
        // Paginação: p, perPage
        // Carrega categorias para dropdown
        // render('supplies/index', compact(...))
    }

    public function create(): void
    {
        // Gerar próximo código
        // Carregar categorias
        // render('supplies/form', ['supply' => null, ...])
    }

    public function store(): void
    {
        // Input::post() para todos os campos
        // Validar: name, code (único), unit_measure
        // $this->supplyModel->create($data)
        // flash_success → redirect supplies
    }

    public function edit(): void
    {
        // $id = Input::get('id', 'int')
        // $supply = $this->supplyModel->readOne($id)
        // Carregar: categorias, fornecedores vinculados, produtos vinculados
        // render('supplies/form', compact('supply', ...))
    }

    public function update(): void
    {
        // Input::post() + validação
        // $this->supplyModel->update($id, $data)
        // flash_success → redirect supplies&action=edit&id=X
    }

    public function delete(): void
    {
        // Soft delete
        // flash_success → redirect supplies
    }

    // ── Categorias AJAX ───────────────────────────

    public function createCategoryAjax(): void { /* JSON response */ }

    public function getCategoriesAjax(): void { /* JSON response */ }

    // ── Vínculo Fornecedor AJAX ────────────────────

    public function getSuppliers(): void { /* JSON: fornecedores do insumo */ }

    public function linkSupplier(): void { /* JSON: vincular */ }

    public function updateSupplierLink(): void { /* JSON: atualizar */ }

    public function unlinkSupplier(): void { /* JSON: desvincular */ }

    public function searchSuppliers(): void { /* JSON: busca Select2 */ }

    // ── BOM AJAX ──────────────────────────────────

    public function getProductSupplies(): void { /* JSON */ }

    public function addProductSupply(): void { /* JSON */ }

    public function updateProductSupply(): void { /* JSON */ }

    public function removeProductSupply(): void { /* JSON */ }

    public function estimateConsumption(): void { /* JSON */ }

    public function getSupplyProducts(): void { /* JSON */ }

    // ── Histórico de Preços AJAX ──────────────────

    public function getPriceHistory(): void { /* JSON: histórico de preços com Chart.js data */ }

    // ── Where Used / Impacto AJAX ─────────────────

    public function getWhereUsedImpact(): void { /* JSON: análise de impacto de preço nos produtos */ }

    public function applyBOMCostUpdate(): void { /* JSON: executa Product::bulkUpdateBOMCosts */ }

    // ── Busca ─────────────────────────────────────

    public function searchSelect2(): void { /* JSON: busca para Select2 */ }
}
```

---

## 4. Registro de Rotas (`app/config/routes.php`)

```php
// ── Insumos (Cadastro) ──────────────────────────
'supplies' => [
    'controller'     => 'SupplyController',
    'default_action' => 'index',
    'public'         => false,
    'actions'        => [
        'create'              => 'create',
        'store'               => 'store',
        'edit'                => 'edit',
        'update'              => 'update',
        'delete'              => 'delete',
        // Categorias
        'categories'          => 'categories',
        'createCategoryAjax'  => 'createCategoryAjax',
        'getCategoriesAjax'   => 'getCategoriesAjax',
        // Vínculo Fornecedor
        'getSuppliers'        => 'getSuppliers',
        'linkSupplier'        => 'linkSupplier',
        'updateSupplierLink'  => 'updateSupplierLink',
        'unlinkSupplier'      => 'unlinkSupplier',
        'searchSuppliers'     => 'searchSuppliers',
        // BOM
        'getProductSupplies'  => 'getProductSupplies',
        'addProductSupply'    => 'addProductSupply',
        'updateProductSupply' => 'updateProductSupply',
        'removeProductSupply' => 'removeProductSupply',
        'estimateConsumption' => 'estimateConsumption',
        'getSupplyProducts'   => 'getSupplyProducts',
        // Histórico de Preços
        'getPriceHistory'     => 'getPriceHistory',
        // Where Used / Impacto
        'getWhereUsedImpact'  => 'getWhereUsedImpact',
        'applyBOMCostUpdate'  => 'applyBOMCostUpdate',
        // Busca
        'searchSelect2'       => 'searchSelect2',
    ],
],

// ── Estoque de Insumos ──────────────────────────
'supply_stock' => [
    'controller'     => 'SupplyStockController',
    'default_action' => 'index',
    'public'         => false,
    'actions'        => [
        'entry'          => 'entry',
        'storeEntry'     => 'storeEntry',
        'exit'           => 'exit',
        'storeExit'      => 'storeExit',
        'transfer'       => 'transfer',
        'storeTransfer'  => 'storeTransfer',
        'adjust'         => 'adjust',
        'storeAdjust'    => 'storeAdjust',
        'movements'      => 'movements',
        'searchSupplies' => 'searchSupplies',
        'getStockInfo'   => 'getStockInfo',
        'getBatches'     => 'getBatches',
        'reorderSuggestions' => 'reorderSuggestions',
    ],
],
```

---

## 5. Registro de Menu (`app/config/menu.php`)

```php
// Dentro do grupo 'catalogo' (Catálogo)
'catalogo' => [
    'label'    => 'Catálogo',
    'icon'     => 'fas fa-box-open',
    'menu'     => true,
    'children' => [
        'products' => [
            'label'      => 'Produtos',
            'icon'       => 'fas fa-box',
            'menu'       => true,
            'permission' => true,
        ],
        'supplies' => [
            'label'      => 'Insumos',
            'icon'       => 'fas fa-cubes',
            'menu'       => true,
            'permission' => true,
        ],
        'categories' => [
            'label'      => 'Categorias',
            'icon'       => 'fas fa-tags',
            'menu'       => true,
            'permission' => true,
        ],
        // ... outros itens existentes
    ],
],

// Dentro do grupo 'estoque' (se existir) ou criar novo grupo
'estoque' => [
    'label'    => 'Estoque',
    'icon'     => 'fas fa-warehouse',
    'menu'     => true,
    'children' => [
        'stock' => [
            'label'      => 'Estoque Produtos',
            'icon'       => 'fas fa-boxes-stacked',
            'menu'       => true,
            'permission' => true,
        ],
        'supply_stock' => [
            'label'      => 'Estoque Insumos',
            'icon'       => 'fas fa-cubes-stacked',
            'menu'       => true,
            'permission' => true,
        ],
    ],
],
```

---

## 6. View — Padrão de Implementação

### 6.1 Formulário Reutilizável (create/edit)

```php
<?php
// app/views/supplies/form.php
$isEdit = !empty($supply);
$s = $supply ?? [];
$title = $isEdit ? 'Editar Insumo: ' . e($s['name'] ?? '') : 'Novo Insumo';
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-cubes me-2"></i><?= e($title) ?></h5>
            <a href="?page=supplies" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Voltar
            </a>
        </div>
        <div class="card-body">
            <!-- Nav tabs (apenas no edit) -->
            <?php if ($isEdit): ?>
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tabDados">Dados</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tabFornecedores">
                        Fornecedores <span class="badge bg-secondary"><?= count($suppliers ?? []) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tabProdutos">
                        Produtos (BOM) <span class="badge bg-secondary"><?= count($productSupplies ?? []) ?></span>
                    </a>
                </li>
            </ul>
            <?php endif; ?>

            <form method="post" action="?page=supplies&action=<?= $isEdit ? 'update' : 'store' ?>">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= eNum($s['id']) ?>">
                <?php endif; ?>

                <!-- Campos do insumo -->
                <div class="row">
                    <div class="col-lg-8">
                        <!-- nome, código, categoria, descrição, unidade -->
                    </div>
                    <div class="col-lg-4">
                        <!-- custo, estoque mínimo, ponto pedido, % perda, status -->
                    </div>
                </div>

                <!-- Seção fiscal (collapse) -->
                <!-- Observações -->
                <!-- Botões -->
            </form>

            <!-- Abas de fornecedores e BOM (apenas edit, carregam via AJAX) -->
        </div>
    </div>
</div>
```

### 6.2 Padrões de Escape

```php
<?= e($supply['name']) ?>          <!-- Texto -->
<?= eAttr($supply['code']) ?>      <!-- Atributo HTML -->
<?= eNum($supply['cost_price']) ?> <!-- Numérico -->
<?= eUrl($link) ?>                 <!-- URL -->
```

### 6.3 AJAX Pattern

```javascript
// Carregar fornecedores do insumo via AJAX
function loadSuppliers(supplyId) {
    $.ajax({
        url: '?page=supplies&action=getSuppliers',
        data: { id: supplyId },
        headers: { 'X-CSRF-TOKEN': csrfToken },
        success: function(response) {
            if (response.success) {
                renderSuppliersTable(response.data);
            }
        }
    });
}

// Vincular fornecedor
function linkSupplier(formData) {
    $.ajax({
        url: '?page=supplies&action=linkSupplier',
        method: 'POST',
        data: formData,
        headers: { 'X-CSRF-TOKEN': csrfToken },
        success: function(response) {
            if (response.success) {
                Swal.fire({ icon: 'success', title: 'Fornecedor vinculado!', toast: true, ... });
                loadSuppliers(formData.supply_id);
            } else {
                Swal.fire({ icon: 'error', title: 'Erro', text: response.message });
            }
        }
    });
}
```

---

## 7. Eventos Disparados

Seguindo o padrão de `EventDispatcher` do sistema:

| Evento | Quando | Payload |
|--------|--------|---------|
| `model.supply.created` | Após criar insumo | `['id', 'name', 'code']` |
| `model.supply.updated` | Após atualizar | `['id', 'name', 'code']` |
| `model.supply.deleted` | Após soft delete | `['id', 'name']` |
| `model.supply.supplier_linked` | Ao vincular fornecedor | `['supply_id', 'supplier_id']` |
| `model.supply.product_linked` | Ao vincular produto (BOM) | `['supply_id', 'product_id']` |
| `model.supply.price_changed` | Quando CMP é recalculado | `['supply_id', 'old_cmp', 'new_cmp']` |
| `model.supply_stock.movement` | Ao registrar movimentação | `['supply_id', 'type', 'quantity', 'batch_number']` |
| `model.supply_stock.reorder_alert` | Estoque ≤ ponto de pedido | `['supply_id', 'current_stock', 'reorder_point']` |
| `model.supply_stock.batch_expiring` | Lote próximo do vencimento | `['supply_id', 'batch_number', 'expiry_date', 'days_until']` |
| `model.product.cost_updated` | Custo recalculado do BOM | `['product_id', 'old_cost', 'new_cost']` |

---

## 8. Checklist de Segurança

- [ ] Prepared statements em todas as queries
- [ ] `e()` / `eAttr()` / `eNum()` em todas as views
- [ ] `csrf_field()` em todos os formulários
- [ ] `X-CSRF-TOKEN` header em todas as chamadas AJAX
- [ ] Validação de permissão no início de cada action do controller
- [ ] Input sanitizado via `Input::post()` / `Input::get()` com type hints
- [ ] Soft delete com `deleted_at` (não DELETE FROM real)
- [ ] Logs de auditoria via `Logger` para ações críticas
