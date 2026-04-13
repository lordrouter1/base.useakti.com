# 09 — Plano de Implementação Detalhado

> **Data:** 2026-04-13  
> **Baseado em:** Documentos 01 a 08 do módulo de insumos v1  
> **Método:** Implementação incremental por fases, com entregas testáveis a cada fase

---

## Sumário das Fases

| Fase | Nome | Dependências | Entregas |
|------|------|-------------|----------|
| **0** | Banco de Dados | — | 7 tabelas criadas via `sql-migration` |
| **1** | Cadastro de Insumos (CRUD) | Fase 0 | Model, Controller, Views, Rotas, Menu |
| **2** | Vínculo Fornecedor | Fase 1 | AJAX de vínculos, modal, fator UOM |
| **3** | Estoque Básico | Fase 1 | Dashboard, entrada, saída, transferência, ajuste |
| **4** | Lote/Validade + FEFO | Fase 3 | Campos de lote, FEFO na saída, dashboard de validade |
| **5** | CMP + Histórico de Preços | Fases 2, 3 | Recálculo automático, tabela `supply_price_history`, gráfico |
| **6** | BOM (Bill of Materials) | Fases 1, 5 | Aba no produto, vinculação, cálculo de custo MP |
| **7** | Custeio Automático + Where Used | Fase 6 | `updateBaseCostFromBOM`, análise de impacto |
| **8** | MRP Simplificado | Fases 3, 2 | Sugestões de compra, alertas, cron job |
| **9** | Testes | Todas | Unitários, integração, segurança |
| **10** | Revisão & Go-Live | Todas | QA, permissões, documentação final |

---

## Fase 0 — Banco de Dados

> **Pré-requisito de tudo.** Usar skill `sql-migration` para gerar arquivo em `/sql/`.

### Tarefas

| # | Tarefa | Detalhes | Referência |
|---|--------|----------|------------|
| 0.1 | Gerar migration SQL | Usar skill `sql-migration` com o SQL completo de 08-migrations.md | [08-migrations.md](08-migrations.md) |
| 0.2 | Criar `supply_categories` | 7 colunas, sem FKs, InnoDB utf8mb4 | §2.1 do doc 02 |
| 0.3 | Criar `supplies` | 19 colunas, FK → `supply_categories`, UNIQUE(`code`), soft delete | §2.2 do doc 02 |
| 0.4 | Criar `supply_suppliers` | 14 colunas, FKs → `supplies` + `suppliers`, `conversion_factor` DECIMAL(12,6), UNIQUE(`supply_id`, `supplier_id`) | §2.3 do doc 02 |
| 0.5 | Criar `product_supplies` | 10 colunas, FKs → `products` + `supplies`, UNIQUE(`product_id`, `supply_id`) | §2.4 do doc 02 |
| 0.6 | Criar `supply_stock_items` | 11 colunas, FKs → `warehouses` + `supplies`, `batch_number`, `expiry_date`, UNIQUE(`warehouse_id`, `supply_id`, `batch_number`) | §2.5 do doc 02 |
| 0.7 | Criar `supply_stock_movements` | 12 colunas, FKs → `warehouses` + `supplies`, `batch_number`, índice em `created_at` | §2.6 do doc 02 |
| 0.8 | Criar `supply_price_history` | 6 colunas, FKs → `supplies` + `suppliers`, índice composto `(supply_id, created_at)` | §2.7 do doc 02 |
| 0.9 | INSERT categorias padrão | 6 categorias: Matéria-Prima, Embalagem, Acabamento, Fixação, Químico, Consumível | §SQL do doc 08 |
| 0.10 | Verificar idempotência | Todos os CREATE com `IF NOT EXISTS`, INSERT com verificação | Regras do projeto |

### Validação da Fase 0

- [ ] Arquivo SQL gerado em `/sql/update_YYYYMMDDHHMM_N_criar_modulo_insumos.sql`
- [ ] SQL executado com sucesso no banco de teste
- [ ] Todas as 7 tabelas criadas com FKs corretas
- [ ] Arquivo movido para `/sql/prontos/` após teste
- [ ] Commit: `migration: criar tabelas do modulo de insumos`

---

## Fase 1 — Cadastro de Insumos (CRUD)

> **Entrega:** CRUD completo funcional com listagem, criação, edição, soft delete e categorias inline.

### 1A — Model `Supply.php`

| # | Tarefa | Assinatura do Método | SQL Envolvido |
|---|--------|---------------------|---------------|
| 1A.1 | Criar arquivo `app/models/Supply.php` | `namespace Akti\Models;` | — |
| 1A.2 | Construtor | `__construct(\PDO $db)` | — |
| 1A.3 | `readAll()` | `(): array` | `SELECT * FROM supplies WHERE deleted_at IS NULL ORDER BY name` |
| 1A.4 | `readPaginated()` | `(int $page, int $perPage, array $filters = []): array` | SELECT com LIMIT/OFFSET, filtros por `category_id`, `search` (name/code), `is_active` |
| 1A.5 | `readOne()` | `(int $id): array\|false` | `SELECT * FROM supplies WHERE id = :id AND deleted_at IS NULL` |
| 1A.6 | `create()` | `(array $data): int` | INSERT 19 campos, dispara `model.supply.created` |
| 1A.7 | `update()` | `(int $id, array $data): bool` | UPDATE com prepared statement, dispara `model.supply.updated` |
| 1A.8 | `delete()` | `(int $id): bool` | `UPDATE supplies SET deleted_at = NOW() WHERE id = :id`, dispara `model.supply.deleted` |
| 1A.9 | `countAll()` | `(array $filters = []): int` | SELECT COUNT com mesmos filtros de `readPaginated` |
| 1A.10 | `generateNextCode()` | `(): string` | `SELECT MAX(CAST(SUBSTR(code,5) AS UNSIGNED)) FROM supplies` → `INS-XXXX` |
| 1A.11 | `codeExists()` | `(string $code, ?int $excludeId = null): bool` | SELECT COUNT WHERE code = :code AND id != :excludeId |
| 1A.12 | `getCategories()` | `(): array` | `SELECT * FROM supply_categories WHERE is_active = 1 ORDER BY sort_order` |
| 1A.13 | `createCategory()` | `(array $data): int` | INSERT name, description |
| 1A.14 | `updateCategory()` | `(int $id, array $data): bool` | UPDATE |
| 1A.15 | `deleteCategory()` | `(int $id): bool` | Verificar se há insumos vinculados antes de excluir |

**Padrões a seguir (referência `Supplier.php`):**
- `private PDO $conn;` no construtor
- Prepared statements com `bindValue` para INT, `?` ou `:param` para strings
- `PDO::FETCH_ASSOC` em todos os fetchAll
- Soft delete: sempre filtrar `WHERE deleted_at IS NULL`
- EventDispatcher: `EventDispatcher::dispatch(new Event('model.supply.created', [...]))`

### 1B — Controller `SupplyController.php`

| # | Tarefa | Método | Lógica |
|---|--------|--------|--------|
| 1B.1 | Criar arquivo `app/controllers/SupplyController.php` | — | `namespace Akti\Controllers;` |
| 1B.2 | Construtor | `__construct(\PDO $db, Supply $supplyModel, Supplier $supplierModel, Logger $logger)` | Atribuir dependências |
| 1B.3 | `index()` | GET | `Input::get('p', 'int')` para página, `Input::get('search')`, `Input::get('category_id', 'int')`, chamar `readPaginated`, `countAll`, `getCategories`, render `supplies/index` |
| 1B.4 | `create()` | GET | `generateNextCode()`, `getCategories()`, render `supplies/form` com `$supply = null` |
| 1B.5 | `store()` | POST | Validar CSRF, `Input::post()` todos campos, validar name/code/unit_measure, `codeExists()`, `$supplyModel->create()`, flash_success, redirect |
| 1B.6 | `edit()` | GET | `Input::get('id', 'int')`, `readOne($id)`, `getCategories()`, render `supplies/form` com dados |
| 1B.7 | `update()` | POST | Validar CSRF, capturar dados, validar, `codeExists($code, $id)`, `$supplyModel->update()`, flash_success, redirect |
| 1B.8 | `delete()` | POST | Validar CSRF, `Input::post('id', 'int')`, verificar BOM vinculado, `$supplyModel->delete()`, flash_success, redirect |
| 1B.9 | `createCategoryAjax()` | POST/AJAX | Validar CSRF, `Input::post('name')`, `createCategory()`, JSON response |
| 1B.10 | `getCategoriesAjax()` | GET/AJAX | `getCategories()`, JSON response |
| 1B.11 | `searchSelect2()` | GET/AJAX | `Input::get('term')`, busca por name/code, retorna JSON `[{id, text}]` |

**Padrões a seguir (referência `ProductController.php`):**
- DI via construtor com type hints
- `Input::post('campo', 'tipo')` / `Input::get('campo', 'tipo')` para captura
- `checkAdmin()` ou verificação de permissão no início
- `flash_success()` / `flash_error()` para mensagens
- `header('Location: ?page=supplies')` para redirect
- `$this->render(...)` para carregar views (padrão BaseController)

### 1C — Views

| # | Arquivo | Descrição | Componentes Bootstrap |
|---|---------|-----------|----------------------|
| 1C.1 | `app/views/supplies/index.php` | Listagem com filtros, paginação | `card > card-header + card-body`, `table table-hover`, `pagination`, filtros `row > col-md` |
| 1C.2 | `app/views/supplies/form.php` | Formulário create/edit reutilizável | `card`, `nav nav-tabs` (apenas edit), `row > col-lg-8 + col-lg-4`, collapse para fiscais |
| 1C.3 | `app/views/supplies/categories.php` | Gerenciamento de categorias | `card` com tabela editável inline |

**Obrigações de segurança nas views:**
- `<?= e($supply['name']) ?>` para texto
- `<?= eAttr($supply['code']) ?>` para atributos
- `<?= eNum($supply['cost_price']) ?>` para numéricos
- `<?= csrf_field() ?>` em cada `<form>`
- `headers: {'X-CSRF-TOKEN': csrfToken}` em cada `$.ajax()`

**JavaScript da index.php:**
- SweetAlert2 para confirmação de delete
- Badge visual `⚠` para estoque abaixo do mínimo (acessar via `getStockSummary`)
- Filtros aplicados via reload com query string

**JavaScript do form.php:**
- Select2 para categoria (com criação inline via `createCategoryAjax`)
- Máscara numérica em campos de custo/estoque/perda
- Validação client-side Bootstrap antes do submit

### 1D — Rotas e Menu

| # | Tarefa | Arquivo | Conteúdo |
|---|--------|---------|----------|
| 1D.1 | Registrar rota `supplies` | `app/config/routes.php` | Ver doc 07, §4 — todas as actions |
| 1D.2 | Registrar menu | `app/config/menu.php` | Dentro de `catalogo.children`: `'supplies' => ['label' => 'Insumos', 'icon' => 'fas fa-cubes', 'menu' => true, 'permission' => true]` |
| 1D.3 | Registrar permissão | `app/views/users/groups.php` | Adicionar checkbox `supplies` na grid de permissões |

### Validação da Fase 1

- [ ] Listagem funcional com filtro por categoria, busca e paginação
- [ ] Criação de insumo com validação server-side
- [ ] Edição com pré-carregamento de dados
- [ ] Soft delete com confirmação SweetAlert2
- [ ] Código auto-gerado `INS-XXXX`
- [ ] Criação de categoria inline via AJAX
- [ ] Permissão verificada no controller
- [ ] Testes: `tests/Unit/SupplyModelTest.php` com `test_create`, `test_readOne`, `test_delete_soft`, `test_code_unique`, `test_generate_code`
- [ ] Commit: `feat(supplies): CRUD completo de insumos com categorias`

---

## Fase 2 — Vínculo Fornecedor

> **Entrega:** Aba "Fornecedores" funcional na tela de edição do insumo, com fator de conversão UOM.

### 2A — Métodos no Model `Supply.php` (adicionar)

| # | Método | SQL |
|---|--------|-----|
| 2A.1 | `getSuppliers(int $supplyId): array` | `SELECT ss.*, s.company_name, s.trade_name FROM supply_suppliers ss JOIN suppliers s ON s.id = ss.supplier_id WHERE ss.supply_id = :id AND ss.is_active = 1 ORDER BY ss.is_preferred DESC` |
| 2A.2 | `linkSupplier(array $data): int` | INSERT em `supply_suppliers`, dispara `model.supply.supplier_linked` |
| 2A.3 | `updateSupplierLink(int $id, array $data): bool` | UPDATE `supply_suppliers` |
| 2A.4 | `unlinkSupplier(int $id): bool` | DELETE FROM `supply_suppliers` WHERE id = :id |
| 2A.5 | `setPreferredSupplier(int $supplyId, int $supplierId): bool` | Em transação: UPDATE todos is_preferred=0 WHERE supply_id, depois UPDATE is_preferred=1 WHERE supply_id AND supplier_id |
| 2A.6 | `getPreferredSupplier(int $supplyId): array\|false` | SELECT WHERE is_preferred = 1 |

### 2B — Actions no Controller `SupplyController.php` (adicionar)

| # | Action | Lógica |
|---|--------|--------|
| 2B.1 | `getSuppliers()` | GET/AJAX: `Input::get('id', 'int')`, JSON response com lista de fornecedores |
| 2B.2 | `linkSupplier()` | POST/AJAX: Validar CSRF, campos, `supply_id` + `supplier_id` uniqueness, `conversion_factor > 0`, `linkSupplier()`, se `is_preferred` chamar `setPreferredSupplier` |
| 2B.3 | `updateSupplierLink()` | POST/AJAX: Validar, `updateSupplierLink()` |
| 2B.4 | `unlinkSupplier()` | POST/AJAX: Confirmar, `unlinkSupplier()` |
| 2B.5 | `searchSuppliers()` | GET/AJAX: busca em `suppliers` por company_name/trade_name, retorna JSON Select2 |

### 2C — Views (modificar)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 2C.1 | Adicionar aba "Fornecedores" no `form.php` | `nav-tabs` no modo edit, conteúdo carregado via AJAX `loadSuppliers(supplyId)` |
| 2C.2 | Tabela de fornecedores vinculados | Colunas: Fornecedor, SKU, Preço, Ped.Mín, Prazo, UOM (×fator), Pref (★), Ações (✎🗑) |
| 2C.3 | Modal de vinculação SweetAlert2 | Campos: Select2 fornecedor, SKU, nome, preço, ped.mín, prazo, fator conversão, preferencial, obs |
| 2C.4 | Coluna UOM na tabela | Exibir `×{conversion_factor}` com tooltip explicativo |

### 2D — Rotas (adicionar actions)

Adicionar ao bloco `supplies` em `routes.php`:
```
'getSuppliers'       => 'getSuppliers',
'linkSupplier'       => 'linkSupplier',
'updateSupplierLink' => 'updateSupplierLink',
'unlinkSupplier'     => 'unlinkSupplier',
'searchSuppliers'    => 'searchSuppliers',
```

### 2E — Visão Reversa no Fornecedor

| # | Tarefa | Detalhes |
|---|--------|----------|
| 2E.1 | Na view de edição do fornecedor, adicionar aba "Insumos Fornecidos" | Tabela read-only: código, insumo, SKU, preço, preferencial |
| 2E.2 | Criar query no `Supply::getSupplierInsumos(supplierId)` | SELECT supply_suppliers + supplies WHERE supplier_id = :id |

### Validação da Fase 2

- [ ] Vincular fornecedor via modal AJAX com Select2
- [ ] Fator de conversão exibido e funcional (× na tabela)
- [ ] Fornecedor preferencial com toggle (★)
- [ ] Editar e remover vínculos com confirmação
- [ ] Visão reversa na tela do fornecedor (read-only)
- [ ] Testes: `test_link_supplier`, `test_unique_constraint`, `test_preferred_toggle`, `test_conversion_factor_validation`
- [ ] Commit: `feat(supplies): vinculacao insumo-fornecedor com fator UOM`

---

## Fase 3 — Estoque Básico

> **Entrega:** Dashboard de estoque, movimentações (entrada, saída, ajuste, transferência) sem lote/FEFO.

### 3A — Model `SupplyStock.php`

| # | Método | SQL |
|---|--------|-----|
| 3A.1 | `__construct(\PDO $db)` | — |
| 3A.2 | `getItems(int $warehouseId, string $search, bool $lowStockOnly): array` | SELECT supply_stock_items JOIN supplies, filtrar deleted_at IS NULL, optional estoque ≤ min_quantity |
| 3A.3 | `getOrCreateItem(int $warehouseId, int $supplyId, ?string $batch = null): array` | SELECT ou INSERT com quantity=0 |
| 3A.4 | `updateItemMeta(int $id, float $minQuantity, ?string $locationCode): bool` | UPDATE min_quantity, location_code |
| 3A.5 | `getDashboardSummary(?int $warehouseId): array` | COUNT itens, SUM valor (qty × cost_price), COUNT estoque baixo, COUNT movimentações mês |
| 3A.6 | `getLowStockItems(int $limit = 20): array` | WHERE quantity <= min_quantity AND min_quantity > 0 |
| 3A.7 | `addMovement(array $data): int` | INSERT supply_stock_movements |
| 3A.8 | `getMovements(array $filters, int $page, int $perPage): array` | SELECT paginado com filtros (warehouse, supply, type, período) |
| 3A.9 | `countMovements(array $filters): int` | COUNT com mesmos filtros |
| 3A.10 | `getTotalStock(int $supplyId): float` | `SUM(quantity) FROM supply_stock_items WHERE supply_id = :id` |

### 3B — Service `SupplyStockMovementService.php`

| # | Método | Lógica |
|---|--------|--------|
| 3B.1 | `__construct(\PDO $db, SupplyStock $stockModel, Supply $supplyModel, Logger $logger)` | DI |
| 3B.2 | `processEntry(int $warehouseId, array $items, string $reason, ?string $refType, ?int $refId): array` | Em transação: para cada item → `getOrCreateItem`, increment quantity, `addMovement(type='entrada')`, retorna `{success, processed, errors}` |
| 3B.3 | `processExit(int $warehouseId, array $items, string $reason): array` | Em transação: para cada item → validar estoque suficiente, decrement quantity, `addMovement(type='saida')` |
| 3B.4 | `processAdjustment(int $warehouseId, array $items, string $reason): array` | Em transação: SET quantity = novo valor, `addMovement(type='ajuste')` com diferença |
| 3B.5 | `processTransfer(int $originId, int $destId, array $items): array` | Em transação: saída na origem + entrada no destino, ambos com type='transferencia' |
| 3B.6 | `validateSufficientStock(int $warehouseId, int $supplyId, float $qty): bool` | SELECT quantity >= :qty |

### 3C — Controller `SupplyStockController.php`

| # | Action | Método HTTP | Lógica |
|---|--------|------------|--------|
| 3C.1 | `index()` | GET | Filtros, dashboard summary, lista paginada, render `supply_stock/index` |
| 3C.2 | `entry()` | GET | Carregar warehouses, render `supply_stock/entry` |
| 3C.3 | `storeEntry()` | POST | Validar CSRF, capturar `warehouse_id`, array de items `{supply_id, quantity, unit_price}`, chamar `processEntry()`, flash + redirect |
| 3C.4 | `exit()` | GET | Carregar warehouses, render `supply_stock/exit` |
| 3C.5 | `storeExit()` | POST | Validar, `processExit()`, flash + redirect |
| 3C.6 | `transfer()` | GET | Carregar warehouses (mín 2), render `supply_stock/transfer` |
| 3C.7 | `storeTransfer()` | POST | Validar origem ≠ destino, `processTransfer()`, flash + redirect |
| 3C.8 | `adjust()` | GET | Render `supply_stock/adjust` |
| 3C.9 | `storeAdjust()` | POST | `processAdjustment()`, flash + redirect |
| 3C.10 | `movements()` | GET | Filtros (armazém, insumo, tipo, período), `getMovements()`, render `supply_stock/movements` |
| 3C.11 | `searchSupplies()` | GET/AJAX | Busca Select2 para formulários (retorna JSON) |
| 3C.12 | `getStockInfo()` | GET/AJAX | `getTotalStock()`, retorna JSON com estoque e info |

### 3D — Views

| # | Arquivo | Componentes |
|---|---------|-------------|
| 3D.1 | `app/views/supply_stock/index.php` | 4 cards KPI (Total, Valor, Estoque Baixo, Movim.), tabela com status visual (✅⚠🔴), filtros |
| 3D.2 | `app/views/supply_stock/entry.php` | Select warehouse, tabela dinâmica de itens (jQuery), Select2 insumo, custo unitário, total calculado, botão [+ Adicionar item] |
| 3D.3 | `app/views/supply_stock/exit.php` | Select warehouse, tabela de itens com validação de estoque suficiente em real-time |
| 3D.4 | `app/views/supply_stock/transfer.php` | 2 selects armazém (origem ≠ destino), tabela de itens |
| 3D.5 | `app/views/supply_stock/movements.php` | Tabela paginada, filtros (armazém, tipo, período, insumo), indicadores visuais por tipo (↓↑±↔) |

**JavaScript dinâmico nos formulários:**
```javascript
// Padrão para adicionar linhas dinamicamente (mesma pattern de stock existente):
let itemIndex = 0;
$('#addItem').on('click', function() {
    const row = buildItemRow(itemIndex++);
    $('#itemsTable tbody').append(row);
    initSelect2OnRow(row); // Select2 no campo de busca de insumo
});
```

### 3E — Rotas e Menu

```php
// routes.php
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
    ],
],

// menu.php — grupo 'estoque'
'supply_stock' => [
    'label'      => 'Estoque Insumos',
    'icon'       => 'fas fa-cubes-stacked',
    'menu'       => true,
    'permission' => true,
],
```

### Validação da Fase 3

- [ ] Dashboard com 4 KPIs corretos
- [ ] Entrada com múltiplos itens funcional
- [ ] Saída com validação de estoque
- [ ] Transferência entre armazéns (atomic)
- [ ] Ajuste de inventário
- [ ] Histórico de movimentações com filtros
- [ ] Select2 funcional para busca de insumos
- [ ] Testes: `tests/Unit/SupplyStockModelTest.php`, `tests/Integration/SupplyStockEntryTest.php`
- [ ] Commit: `feat(supply_stock): estoque de insumos com movimentacoes completas`

---

## Fase 4 — Lote/Validade + FEFO

> **Entrega:** Campos de lote e validade no estoque, saída com estratégia FEFO automática, dashboard de lotes próximos do vencimento.

### 4A — Ajustes no Model `SupplyStock.php`

| # | Método | Alteração |
|---|--------|-----------|
| 4A.1 | `getOrCreateItem()` | Parâmetro `?string $batch` passa a ser obrigatório para criar `UNIQUE(warehouse_id, supply_id, batch_number)` — batch_number pode ser NULL |
| 4A.2 | `getBatchesBySupply(int $supplyId, int $warehouseId): array` | SELECT WHERE qty > 0 ORDER BY `expiry_date ASC NULLS LAST, created_at ASC` (FEFO) |
| 4A.3 | `getExpiringBatches(int $days = 30, int $limit = 20): array` | SELECT WHERE expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY) AND quantity > 0 |
| 4A.4 | `getExpiredBatches(int $limit = 20): array` | SELECT WHERE expiry_date < NOW() AND quantity > 0 |

### 4B — Ajustes no Service `SupplyStockMovementService.php`

| # | Método | Alteração |
|---|--------|-----------|
| 4B.1 | `processEntry()` | Aceitar `batch_number` e `expiry_date` por item; gravar no `supply_stock_items` e nos `supply_stock_movements` |
| 4B.2 | `processExit()` | Implementar FEFO: se insumo tem lotes com validade, chamar `suggestBatchForExit()`, dividir saída entre lotes se necessário |
| 4B.3 | `suggestBatchForExit(int $supplyId, int $warehouseId, float $qty): array` | Retorna array de `[{batch_number, expiry_date, qty_available, qty_to_consume}]` ordenado por FEFO |

### 4C — Ajustes nas Views

| # | Tarefa |
|---|--------|
| 4C.1 | `entry.php`: Adicionar campos `Lote` (text) e `Validade` (date input) por linha de item |
| 4C.2 | `exit.php`: Ao selecionar insumo, AJAX carrega lotes disponíveis (`getBatches`), exibe grid de seleção FEFO com sugestão automática |
| 4C.3 | `index.php`: Adicionar colunas `Lote` e `Validade` na tabela de estoque |
| 4C.4 | `movements.php`: Adicionar coluna `Lote` no histórico |
| 4C.5 | Criar `app/views/supply_stock/_expiring_card.php` | Card partial incluído no index: "Lotes Próximos do Vencimento" com indicadores 🔴 (≤30d) e ⚠ (31-60d) |

### 4D — Rotas (adicionar)

```php
'getBatches' => 'getBatches',  // GET/AJAX — lotes por insumo/armazém
```

### 4E — Action no Controller

| # | Action | Lógica |
|---|--------|--------|
| 4E.1 | `getBatches()` | GET/AJAX: `Input::get('supply_id', 'int')`, `Input::get('warehouse_id', 'int')`, chamar `getBatchesBySupply()`, retorna JSON ordenado FEFO |

### Validação da Fase 4

- [ ] Entrada registra lote e validade corretamente
- [ ] Saída sugere automaticamente lote FEFO
- [ ] Saída divide entre lotes quando necessário
- [ ] Dashboard exibe lotes próximos do vencimento
- [ ] Lotes vencidos destacados em vermelho
- [ ] Movimentações registram batch_number
- [ ] Testes: `test_fefo_order`, `test_batch_split_exit`, `test_expiring_query`
- [ ] Commit: `feat(supply_stock): controle de lotes validade e FEFO`

---

## Fase 5 — CMP + Histórico de Preços

> **Entrega:** Recálculo de CMP a cada entrada, tabela `supply_price_history` preenchida, gráfico Chart.js na tela do insumo.

### 5A — Ajustes no Service

| # | Método | Lógica |
|---|--------|--------|
| 5A.1 | `calculateWeightedAverageCost(int $supplyId, float $newQty, float $newPrice): float` | `CMP = (est_atual × custo_atual + newQty × newPrice) / (est_atual + newQty)` |
| 5A.2 | `processEntry()` — adicionar | Após gravar movimentação: 1) calcular novo CMP, 2) `UPDATE supplies SET cost_price = :cmp`, 3) `INSERT supply_price_history`, 4) disparar `model.supply.price_changed` se CMP mudou |
| 5A.3 | `applyConversionFactor(int $supplyId, int $supplierId, float $qty): float` | Buscar `conversion_factor` de `supply_suppliers`, retornar `qty × factor` |

### 5B — Ajustes no Model `Supply.php`

| # | Método | SQL |
|---|--------|-----|
| 5B.1 | `getPriceHistory(int $supplyId, ?int $supplierId = null, int $limit = 50): array` | SELECT supply_price_history JOIN suppliers, ORDER BY created_at DESC, filtro opcional por supplier_id |
| 5B.2 | `recordPriceHistory(array $data): int` | INSERT supply_price_history (`supply_id`, `supplier_id`, `unit_price`, `quantity`, `source`, `notes`, `created_by`) |
| 5B.3 | `calculateWeightedAverageCost(int $supplyId): float` | SELECT cost_price FROM supplies — valor já atualizado pelo service |

### 5C — Action + View

| # | Tarefa |
|---|--------|
| 5C.1 | `getPriceHistory()` action no `SupplyController` | GET/AJAX, retorna JSON com dados para Chart.js |
| 5C.2 | Criar `app/views/supplies/_price_chart.php` | Partial com canvas Chart.js: eixo X = meses, eixo Y = preço, uma linha por fornecedor, tooltip com detalhes |
| 5C.3 | Incluir `_price_chart.php` na aba "Fornecedores" do `form.php` | Abaixo da tabela de fornecedores vinculados |
| 5C.4 | Adicionar rota `'getPriceHistory' => 'getPriceHistory'` | Em `routes.php` no bloco `supplies` |

### 5D — Integração com Entrada

Ao processar entrada com fornecedor selecionado:
```
1. SupplyStockMovementService::processEntry()
2. Se supplierId informado:
   a. applyConversionFactor(supplyId, supplierId, qty) → qtd_convertida
   b. Usar preço da nota / conversion_factor → preço unitário na UOM do insumo
3. calculateWeightedAverageCost(supplyId, qtd_convertida, precoUnitConvertido)
4. UPDATE supplies SET cost_price = novo_CMP
5. INSERT supply_price_history (source = 'compra')
6. Disparar event model.supply.price_changed se abs(cmp_novo - cmp_antigo) > 0.0001
```

### Validação da Fase 5

- [ ] CMP recalculado a cada entrada com valores corretos
- [ ] `supply_price_history` preenchida automaticamente
- [ ] Gráfico Chart.js funcional com dados reais
- [ ] Fator de conversão aplicado corretamente na entrada
- [ ] Evento `model.supply.price_changed` disparado
- [ ] Testes: `test_cmp_calculation`, `test_price_history_insertion`, `test_conversion_factor_applied`
- [ ] Commit: `feat(supplies): CMP automatico e historico de precos com grafico`

---

## Fase 6 — BOM (Bill of Materials)

> **Entrega:** Aba "Insumos (BOM)" na tela do produto, CRUD de composição via AJAX, cálculo de custo MP.

### 6A — Métodos no Model `Supply.php` (adicionar)

| # | Método | SQL |
|---|--------|-----|
| 6A.1 | `getProductSupplies(int $productId): array` | SELECT ps.*, s.name, s.code, s.cost_price, s.unit_measure FROM product_supplies ps JOIN supplies s ON s.id = ps.supply_id WHERE ps.product_id = :id ORDER BY ps.sort_order |
| 6A.2 | `getSupplyProducts(int $supplyId): array` | SELECT ps.*, p.name, p.code, p.price FROM product_supplies ps JOIN products p ON p.id = ps.product_id WHERE ps.supply_id = :id |
| 6A.3 | `addProductSupply(array $data): int` | INSERT product_supplies, dispara `model.supply.product_linked` |
| 6A.4 | `updateProductSupply(int $id, array $data): bool` | UPDATE product_supplies |
| 6A.5 | `removeProductSupply(int $id): bool` | DELETE FROM product_supplies |
| 6A.6 | `calculateProductCost(int $productId): float` | SELECT SUM(quantity × (1 + waste_percent/100) × s.cost_price) FROM product_supplies ps JOIN supplies s ON ... WHERE ps.product_id = :id AND ps.is_optional = 0 |
| 6A.7 | `estimateConsumption(int $productId, float $qty): array` | Para cada insumo do BOM: `{supply_name, qty_per_unit, total_needed, stock_available, sufficient}` |

### 6B — Actions no `SupplyController.php`

| # | Action | Lógica |
|---|--------|--------|
| 6B.1 | `getProductSupplies()` | GET/AJAX: buscar insumos do produto, calcular efetivo e custo de cada, retornar JSON |
| 6B.2 | `addProductSupply()` | POST/AJAX: validar CSRF, `product_id + supply_id` unique, `quantity > 0`, `waste_percent 0-100`, INSERT, recalcular custo total, retornar JSON |
| 6B.3 | `updateProductSupply()` | POST/AJAX: validar, UPDATE, recalcular custo |
| 6B.4 | `removeProductSupply()` | POST/AJAX: DELETE, recalcular custo |
| 6B.5 | `estimateConsumption()` | GET/AJAX: `Input::get('product_id')`, `Input::get('qty')`, chamar `estimateConsumption()`, JSON response |
| 6B.6 | `getSupplyProducts()` | GET/AJAX: "onde é usado" — lista produtos que usam o insumo |

### 6C — Views

| # | Tarefa |
|---|--------|
| 6C.1 | Na view de edição do **produto** (`app/views/products/form.php`), adicionar aba "Insumos (BOM)" | Tab condicional (apenas edit), conteúdo carregado via AJAX |
| 6C.2 | Tabela BOM dinâmica | Colunas: Insumo, Qtd, Un., %Perda, Efetivo (calculado), Custo (calculado), Ações (✎🗑) |
| 6C.3 | Modal "Adicionar Insumo ao Produto" | Select2 insumo (com info do insumo ao selecionar), campos: quantidade, unidade (pré-preenchida), %perda (pré-preenchida), opcional, obs |
| 6C.4 | Card de resumo de custo | Custo Total MP, Custo Obrigatórios, Preço Venda, Margem % — atualizado a cada operação AJAX |
| 6C.5 | Na tela de edição do **insumo** (`form.php`), aba "Produtos (BOM)" | Tabela read-only com produtos que usam o insumo, com CustoMP e Margem |
| 6C.6 | Card "Estimativa de Consumo" | Select produto + input quantidade + botão Calcular → tabela com necessidade vs estoque |

### 6D — Rotas (adicionar ao bloco `supplies`)

```php
'getProductSupplies'  => 'getProductSupplies',
'addProductSupply'    => 'addProductSupply',
'updateProductSupply' => 'updateProductSupply',
'removeProductSupply' => 'removeProductSupply',
'estimateConsumption' => 'estimateConsumption',
'getSupplyProducts'   => 'getSupplyProducts',
```

### Validação da Fase 6

- [ ] Aba BOM visível na edição do produto
- [ ] Adicionar/editar/remover insumos via AJAX
- [ ] Cálculo de efetivo e custo correto em tempo real
- [ ] Card de resumo com margem
- [ ] Constraint UNIQUE impede duplicatas
- [ ] "Onde é usado" na tela do insumo
- [ ] Estimativa de consumo funcional
- [ ] Testes: `test_add_product_supply`, `test_calculate_cost`, `test_estimate_consumption`, `test_unique_bom_constraint`
- [ ] Commit: `feat(supplies): BOM vinculacao insumo-produto com custeio`

---

## Fase 7 — Custeio Automático + Where Used Impact

> **Entrega:** Recálculo automático do `cost_price` do produto quando CMP do insumo muda, modal de impacto.

### 7A — Métodos no Model `Product.php` (adicionar)

| # | Método | Lógica |
|---|--------|--------|
| 7A.1 | `updateBaseCostFromBOM(int $productId): float` | SELECT product_supplies JOIN supplies WHERE is_optional=0, calcular soma, UPDATE products SET cost_price, disparar `model.product.cost_updated` |
| 7A.2 | `getMarginAnalysis(int $productId): array` | Retorna `{cost_mp, price, margin_percent, margin_value}` |
| 7A.3 | `bulkUpdateBOMCosts(array $productIds): array` | Loop sobre productIds chamando updateBaseCostFromBOM, retorna lista de resultados |

### 7B — Métodos no Model `Supply.php` (adicionar)

| # | Método | Lógica |
|---|--------|--------|
| 7B.1 | `getWhereUsedImpact(int $supplyId, float $newCMP): array` | Para cada produto que usa o insumo: calcula custo atual vs custo com novo CMP, retorna `{product_id, name, old_cost, new_cost, variation, old_margin, new_margin}` |
| 7B.2 | `getAffectedProducts(int $supplyId): array` | SELECT DISTINCT product_id FROM product_supplies WHERE supply_id = :id |

### 7C — Evento + Listener

```php
// app/bootstrap/events.php — adicionar:
EventDispatcher::listen('model.supply.price_changed', function(Event $event) {
    $supplyId = $event->getData()['supply_id'];
    $newCMP   = $event->getData()['new_cmp'];
    
    // Buscar produtos afetados
    $supply = new Supply($db);
    $affectedProducts = $supply->getAffectedProducts($supplyId);
    
    if (!empty($affectedProducts)) {
        // Armazenar impacto em sessão para exibição na próxima requisição
        $_SESSION['supply_price_impact'] = [
            'supply_id'  => $supplyId,
            'new_cmp'    => $newCMP,
            'products'   => $supply->getWhereUsedImpact($supplyId, $newCMP),
        ];
    }
});
```

### 7D — Actions + Views

| # | Tarefa |
|---|--------|
| 7D.1 | `getWhereUsedImpact()` action no `SupplyController` | GET/AJAX: receber `supply_id` + `new_cmp`, retorna JSON com lista de impactos |
| 7D.2 | `applyBOMCostUpdate()` action no `SupplyController` | POST/AJAX: receber array de `product_ids`, chamar `bulkUpdateBOMCosts()`, retorna JSON success |
| 7D.3 | Criar `app/views/supplies/_impact_modal.php` | Partial: tabela de impacto (Produto, Custo Ant., Custo Novo, Variação, Margem), 3 botões: Cancelar / Atualizar CMP / Atualizar CMP + BOM |
| 7D.4 | Trigger modal após entrada de estoque | Se evento `price_changed` gerou impacto em sessão, mostrar modal SweetAlert2 customizado com o partial |
| 7D.5 | Botão "Recalcular Custo" na tela do produto | Na aba BOM, botão que chama `updateBaseCostFromBOM()` manualmente |

### 7E — Rotas (adicionar)

```php
'getWhereUsedImpact'  => 'getWhereUsedImpact',
'applyBOMCostUpdate'  => 'applyBOMCostUpdate',
```

### Validação da Fase 7

- [ ] Ao processar entrada com CMP diferente, modal de impacto aparece
- [ ] Modal exibe corretamente produtos afetados com variação de custo e margem
- [ ] Botão "Atualizar CMP + Custos BOM" atualiza `products.cost_price` via `bulkUpdateBOMCosts`
- [ ] Botão "Recalcular Custo" no produto funciona manualmente
- [ ] Evento `model.product.cost_updated` disparado
- [ ] Testes: `test_where_used_impact`, `test_bulk_update_bom_costs`, `test_margin_calculation`
- [ ] Commit: `feat(supplies): custeio automatico BOM e analise de impacto where-used`

---

## Fase 8 — MRP Simplificado (Alertas de Reposição)

> **Entrega:** Card de sugestões de compra no dashboard de estoque, alertas automáticos, cron job.

### 8A — Ajustes no Model `SupplyStock.php`

| # | Método | SQL |
|---|--------|-----|
| 8A.1 | `getReorderItems(): array` | SELECT supplies + SUM(supply_stock_items.quantity) AS total_stock + supply_suppliers (is_preferred) WHERE total_stock <= reorder_point AND reorder_point > 0 |

### 8B — Ajustes no Service

| # | Método | Lógica |
|---|--------|--------|
| 8B.1 | `checkReorderAlerts(): array` | Buscar `getReorderItems()`, para cada: buscar fornecedor preferencial, calcular qtd sugerida (`min_order_qty` ou `reorder_point × 2 - stock`), disparar `model.supply_stock.reorder_alert` |
| 8B.2 | `processExit()` — adicionar | Após saída, chamar `checkReorderAlerts()` para os insumos envolvidos |

### 8C — Views

| # | Tarefa |
|---|--------|
| 8C.1 | Criar `app/views/supply_stock/_reorder_card.php` | Partial: tabela com Insumo, Estoque, Pto.Pedido, Qtd.Sugerida, Forn.Preferencial, [Pedir] |
| 8C.2 | Incluir `_reorder_card.php` no `index.php` | Abaixo dos KPIs, visível apenas se há itens no ponto de pedido |
| 8C.3 | Botão [Pedir] | Link para `?page=suppliers&action=createPurchase&supplier_id=X&supply_id=Y&qty=Z` (pré-preenchido) |
| 8C.4 | Rota `reorderSuggestions` | GET/AJAX no `SupplyStockController`, retorna JSON |

### 8D — Cron Job

| # | Tarefa |
|---|--------|
| 8D.1 | Criar `scripts/check_supply_reorder.php` | Bootstrap do sistema, instanciar service, chamar `checkReorderAlerts()`, se há alertas: inserir notificações no banco (se módulo de notificações existir) ou logar em `storage/logs/` |
| 8D.2 | Documentar configuração cron | `0 */6 * * * php /path/to/scripts/check_supply_reorder.php` |

### Validação da Fase 8

- [ ] Card de sugestões aparece com dados corretos
- [ ] Qtd sugerida baseada em min_order_qty do fornecedor preferencial
- [ ] Botão [Pedir] redireciona com dados pré-preenchidos
- [ ] Após saída que atinge ponto de pedido, alerta gerado
- [ ] Cron job funciona via terminal: `php scripts/check_supply_reorder.php`
- [ ] Testes: `test_reorder_detection`, `test_suggested_quantity`, `test_cron_job_execution`
- [ ] Commit: `feat(supply_stock): MRP simplificado com sugestoes de compra e cron`

---

## Fase 9 — Testes

### Estrutura de Testes

```
tests/
├── Unit/
│   ├── SupplyModelTest.php                   # CRUD, categorias, code generation
│   ├── SupplyStockModelTest.php              # Estoque, dashboard, movimentações
│   ├── SupplyBOMTest.php                     # BOM, custo, estimativa
│   └── SupplyPriceHistoryTest.php            # CMP, histórico, conversão
├── Integration/
│   ├── SupplyStockEntryTest.php              # Entrada completa (com CMP e lote)
│   ├── SupplyStockExitFEFOTest.php           # Saída com FEFO
│   ├── SupplyBOMCostUpdateTest.php           # Custeio automático end-to-end
│   └── SupplyReorderAlertTest.php            # MRP trigger + notificação
├── Pages/
│   ├── SupplyPageTest.php                    # Rotas de insumos acessíveis
│   └── SupplyStockPageTest.php               # Rotas de estoque acessíveis
└── Security/
    ├── SupplyCSRFTest.php                    # CSRF em todos os POSTs
    ├── SupplyXSSTest.php                     # Inputs escapam corretamente
    └── SupplyPermissionTest.php              # Acesso sem permissão = 403
```

### Cenários Críticos por Teste

**SupplyModelTest.php:**
```
test_create_supply_with_all_fields()
test_create_supply_minimum_fields()
test_code_auto_generation_sequential()
test_code_uniqueness_validation()
test_soft_delete_sets_deleted_at()
test_soft_deleted_not_in_readAll()
test_readPaginated_with_filters()
test_category_crud()
test_search_select2_returns_format()
```

**SupplyStockModelTest.php:**
```
test_entry_increases_quantity()
test_exit_decreases_quantity()
test_exit_insufficient_stock_throws()
test_transfer_atomic_origin_dest()
test_adjustment_sets_exact_quantity()
test_dashboard_summary_values()
test_low_stock_items_filter()
test_batch_creation_on_entry()
test_fefo_order_by_expiry()
test_expiring_batches_query()
```

**SupplyBOMTest.php:**
```
test_add_product_supply()
test_unique_constraint_product_supply()
test_calculate_product_cost_mandatory_only()
test_calculate_product_cost_with_waste()
test_optional_supply_excluded_from_cost()
test_estimate_consumption_correct_totals()
test_where_used_lists_all_products()
```

**SupplyPriceHistoryTest.php:**
```
test_cmp_calculation_correct()
test_cmp_first_entry_equals_price()
test_price_history_recorded_on_entry()
test_conversion_factor_applied()
test_price_changed_event_dispatched()
test_bulk_update_bom_costs()
```

### Validação da Fase 9

- [ ] Todos os testes passando: `vendor/bin/phpunit --testsuite=Unit`
- [ ] Cobertura dos cenários críticos > 80%
- [ ] Nenhum teste verifica existência de arquivos `.sql` (regra do projeto)
- [ ] Commit: `test(supplies): testes unitarios integração e seguranca do modulo insumos`

---

## Fase 10 — Revisão & Go-Live

### 10.1 Checklist de Segurança

| # | Item | Status |
|---|------|--------|
| 1 | Prepared statements em **todas** as queries dos 2 models + 1 service | [ ] |
| 2 | `e()` / `eAttr()` / `eNum()` em **todas** as views (6+ arquivos) | [ ] |
| 3 | `csrf_field()` em todos os forms (entry, exit, transfer, adjust, form) | [ ] |
| 4 | `X-CSRF-TOKEN` em todos os AJAX (link/unlink supplier, BOM operations) | [ ] |
| 5 | Permissão verificada em **todas** as actions dos 2 controllers | [ ] |
| 6 | `Input::post()`/`Input::get()` com type hints em todos os controllers | [ ] |
| 7 | Soft delete (nunca DELETE FROM real em `supplies`) | [ ] |
| 8 | Logger para ações críticas (delete, ajuste, transferência) | [ ] |

### 10.2 Checklist de UI/UX

| # | Item | Status |
|---|------|--------|
| 1 | Todas as telas responsivas (mobile) | [ ] |
| 2 | SweetAlert2 em todas as confirmações de exclusão | [ ] |
| 3 | Toasts de sucesso/erro (SweetAlert2 toast) | [ ] |
| 4 | Loading indicators em operações AJAX | [ ] |
| 5 | Validação client-side Bootstrap em formulários | [ ] |
| 6 | Filtros preservados ao paginar | [ ] |
| 7 | Empty states ("Nenhum insumo cadastrado") | [ ] |
| 8 | Menu destacado corretamente (active state) | [ ] |

### 10.3 Checklist de Permissões

| # | Item | Status |
|---|------|--------|
| 1 | Página `supplies` registrada em `menu.php` com `'permission' => true` | [ ] |
| 2 | Página `supply_stock` registrada em `menu.php` com `'permission' => true` | [ ] |
| 3 | Checkbox de permissão no `groups.php` para ambas as páginas | [ ] |
| 4 | Testar acesso como usuário sem permissão → bloqueado | [ ] |
| 5 | Testar acesso como Admin → acesso total | [ ] |
| 6 | Testar acesso como Estoquista → movimentações OK, CRUD limitado | [ ] |

### 10.4 Checklist Multi-Tenant

| # | Item | Status |
|---|------|--------|
| 1 | Tabelas criadas no DB do tenant (akti_<cliente>) | [ ] |
| 2 | Todas as queries respeitam escopo do tenant (PDO do tenant) | [ ] |
| 3 | Uploads (se houver) no diretório correto do tenant | [ ] |
| 4 | Módulo registrado no `ModuleBootloader` (feature flag) | [ ] |

### 10.5 Documentação Final

| # | Tarefa |
|---|--------|
| 1 | Atualizar `docs/MANUAL_DO_SISTEMA.md` com seção do módulo de insumos |
| 2 | Atualizar `docs/CHANGELOG.md` com a entrada da versão |
| 3 | Verificar se README do módulo (`docs/insumos/v1/README.md`) está atualizado |

### Validação da Fase 10

- [ ] Todos os checklists acima concluídos
- [ ] Teste manual end-to-end: criar insumo → vincular fornecedor → dar entrada → dar saída (FEFO) → vincular no BOM → verificar custeio → verificar MRP
- [ ] Teste com 2 tenants diferentes simultaneamente
- [ ] Commit: `feat(supplies): modulo de insumos v1 completo`

---

## Resumo de Arquivos a Criar/Modificar

### Arquivos Novos (15)

| # | Arquivo | Fase |
|---|---------|------|
| 1 | `sql/update_YYYYMMDDHHMM_N_criar_modulo_insumos.sql` | 0 |
| 2 | `app/models/Supply.php` | 1 |
| 3 | `app/models/SupplyStock.php` | 3 |
| 4 | `app/controllers/SupplyController.php` | 1 |
| 5 | `app/controllers/SupplyStockController.php` | 3 |
| 6 | `app/services/SupplyStockMovementService.php` | 3 |
| 7 | `app/views/supplies/index.php` | 1 |
| 8 | `app/views/supplies/form.php` | 1 |
| 9 | `app/views/supplies/categories.php` | 1 |
| 10 | `app/views/supplies/_price_chart.php` | 5 |
| 11 | `app/views/supplies/_impact_modal.php` | 7 |
| 12 | `app/views/supply_stock/index.php` | 3 |
| 13 | `app/views/supply_stock/entry.php` | 3 |
| 14 | `app/views/supply_stock/exit.php` | 3 |
| 15 | `app/views/supply_stock/transfer.php` | 3 |
| 16 | `app/views/supply_stock/movements.php` | 3 |
| 17 | `app/views/supply_stock/_reorder_card.php` | 8 |
| 18 | `app/views/supply_stock/_expiring_card.php` | 4 |
| 19 | `scripts/check_supply_reorder.php` | 8 |

### Arquivos Existentes a Modificar (5)

| # | Arquivo | Fase | Alteração |
|---|---------|------|-----------|
| 1 | `app/config/routes.php` | 1, 3 | Adicionar blocos `supplies` e `supply_stock` |
| 2 | `app/config/menu.php` | 1, 3 | Adicionar itens de menu |
| 3 | `app/views/users/groups.php` | 1 | Adicionar checkboxes de permissão |
| 4 | `app/views/products/form.php` | 6 | Adicionar aba "Insumos (BOM)" |
| 5 | `app/bootstrap/events.php` | 7 | Registrar listener `model.supply.price_changed` |
| 6 | `app/models/Product.php` | 7 | Adicionar `updateBaseCostFromBOM()`, `getMarginAnalysis()`, `bulkUpdateBOMCosts()` |

### Arquivos de Teste (10)

| # | Arquivo | Fase |
|---|---------|------|
| 1 | `tests/Unit/SupplyModelTest.php` | 1 |
| 2 | `tests/Unit/SupplyStockModelTest.php` | 3 |
| 3 | `tests/Unit/SupplyBOMTest.php` | 6 |
| 4 | `tests/Unit/SupplyPriceHistoryTest.php` | 5 |
| 5 | `tests/Integration/SupplyStockEntryTest.php` | 3 |
| 6 | `tests/Integration/SupplyStockExitFEFOTest.php` | 4 |
| 7 | `tests/Integration/SupplyBOMCostUpdateTest.php` | 7 |
| 8 | `tests/Integration/SupplyReorderAlertTest.php` | 8 |
| 9 | `tests/Pages/SupplyPageTest.php` | 9 |
| 10 | `tests/Security/SupplyCSRFTest.php` | 9 |

---

## Ordem de Commits

```
1.  migration: criar tabelas do modulo de insumos
2.  feat(supplies): CRUD completo de insumos com categorias
3.  feat(supplies): vinculacao insumo-fornecedor com fator UOM
4.  feat(supply_stock): estoque de insumos com movimentacoes completas
5.  feat(supply_stock): controle de lotes validade e FEFO
6.  feat(supplies): CMP automatico e historico de precos com grafico
7.  feat(supplies): BOM vinculacao insumo-produto com custeio
8.  feat(supplies): custeio automatico BOM e analise de impacto where-used
9.  feat(supply_stock): MRP simplificado com sugestoes de compra e cron
10. test(supplies): testes unitarios integracao e seguranca do modulo insumos
11. feat(supplies): modulo de insumos v1 completo
```

---

## Dependências entre Fases (Diagrama)

```
Fase 0 (BD)
    │
    ├── Fase 1 (CRUD)
    │       │
    │       ├── Fase 2 (Fornecedor + UOM)
    │       │       │
    │       │       └──┐
    │       │          │
    │       ├── Fase 3 (Estoque Básico)
    │       │       │
    │       │       ├── Fase 4 (Lote/FEFO)
    │       │       │
    │       │       └── Fase 8 (MRP) ←── Fase 2
    │       │
    │       └── Fase 5 (CMP + Histórico) ←── Fase 2, 3
    │               │
    │               └── Fase 6 (BOM)
    │                       │
    │                       └── Fase 7 (Custeio Auto + Where Used)
    │
    └── Fase 9 (Testes) ←── Todas
            │
            └── Fase 10 (Go-Live)
```

---

## Notas Importantes de Implementação

### Padrões obrigatórios do projeto Akti

1. **PSR-4 Autoload:** Nunca usar `require_once`. Namespace `Akti\Models\Supply` → `app/models/Supply.php`
2. **Prepared Statements:** Toda query com input externo usa `bindValue` ou `?` placeholders
3. **Escape nas Views:** `e()` para texto, `eAttr()` para atributos, `eNum()` para numéricos
4. **CSRF:** `csrf_field()` em forms, `X-CSRF-TOKEN` header em AJAX
5. **SweetAlert2:** Nunca `alert()`, `confirm()` ou `prompt()` nativos
6. **jQuery + Bootstrap 5:** Stack de frontend obrigatória
7. **Git workflow:** Commit ao início e ao final de cada tarefa
8. **Migrations:** Sempre via skill `sql-migration`, nunca SQL direto

### Referências de implementação

| Componente | Arquivo de referência existente |
|-----------|-------------------------------|
| Model com PDO | `app/models/Supplier.php` — `readPaginated`, `create`, `update` |
| Controller com DI | `app/controllers/ProductController.php` — construtor multi-dependência |
| Service transacional | `app/services/StockMovementService.php` — `processMovement` |
| View com tabs | `app/views/products/form.php` — nav-tabs com conteúdo condicional |
| Select2 AJAX | `app/views/products/form.php` — busca de categorias/subcategorias |
| Event dispatch | `app/models/Product.php` — `EventDispatcher::dispatch(new Event(...))` |
| Listagem paginada | `app/views/suppliers/index.php` — tabela + paginação + filtros |
