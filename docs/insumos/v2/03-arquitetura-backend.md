# 03 — Arquitetura Backend

## 1. Visão Geral dos Componentes

```
app/
├── models/
│   ├── Supply.php              ← MODIFICAR (adicionar métodos v2)
│   ├── SupplyStock.php         ← MODIFICAR (completar métodos v1 + v2)
│   ├── SupplySubstitute.php    ← CRIAR (novo)
│   └── ProductionConsumption.php ← CRIAR (novo)
│
├── services/
│   ├── SupplyStockMovementService.php  ← CRIAR (crítico — processamento transacional)
│   ├── InsumoService.php               ← CRIAR (ratio, fracionamento, BOM)
│   ├── ProducaoService.php             ← CRIAR (consumo, apontamento, eficiência)
│   ├── SupplyForecastService.php       ← CRIAR (previsão de ruptura)
│   └── SupplyCostService.php           ← CRIAR (CMP, alertas de margem)
│
├── controllers/
│   ├── SupplyController.php            ← MODIFICAR (adicionar actions v2)
│   ├── SupplyStockController.php       ← MODIFICAR (completar actions)
│   └── SupplyDashboardController.php   ← CRIAR (dashboard de eficiência)
│
└── views/
    ├── supplies/                        ← MODIFICAR (tabs, substitutos)
    ├── supply_stock/                    ← MODIFICAR (FEFO, forecast)
    └── supply_dashboard/                ← CRIAR (eficiência)
```

---

## 2. Models — Detalhamento

### 2.1 `Supply.php` — Métodos a Adicionar

```php
// === FORNECEDORES (v1 pendente) ===

/**
 * Retorna fornecedores vinculados a um insumo.
 * @param int $supplyId
 * @return array Lista de fornecedores com dados do pivot
 */
public function getSuppliers(int $supplyId): array

/**
 * Vincula fornecedor ao insumo com metadados.
 * @param int $supplyId
 * @param array $data [supplier_id, unit_price, min_order_qty, lead_time_days, conversion_factor, is_preferred]
 * @return int ID do vínculo criado
 */
public function addSupplier(int $supplyId, array $data): int

/**
 * Atualiza dados do vínculo fornecedor-insumo.
 */
public function updateSupplier(int $linkId, array $data): bool

/**
 * Remove vínculo fornecedor-insumo.
 */
public function removeSupplier(int $linkId): bool

/**
 * Define fornecedor preferido (desmarca os outros).
 */
public function setPreferredSupplier(int $supplyId, int $supplierId): bool

// === BOM — BILL OF MATERIALS (v1 pendente) ===

/**
 * Retorna composição (BOM) de um produto.
 * @param int $productId
 * @param int|null $variationId Se NULL, retorna insumos do produto pai
 * @return array Lista de insumos com quantidades e perdas
 */
public function getProductBom(int $productId, ?int $variationId = null): array

/**
 * Adiciona insumo à BOM de um produto/variação.
 */
public function addProductSupply(int $productId, array $data): int

/**
 * Atualiza quantidade/perda de insumo na BOM.
 */
public function updateProductSupply(int $id, array $data): bool

/**
 * Remove insumo da BOM.
 */
public function removeProductSupply(int $id): bool

/**
 * "Onde é Usado" — lista produtos que usam este insumo.
 */
public function getWhereUsed(int $supplyId): array

// === PREÇO E CMP (v1 pendente) ===

/**
 * Retorna histórico de preços de um insumo.
 */
public function getPriceHistory(int $supplyId, int $limit = 50): array

/**
 * Registra novo preço no histórico.
 */
public function addPriceHistory(int $supplyId, array $data): int

// === v2 NOVOS ===

/**
 * Calcula consumo considerando fracionamento e perda.
 * @param int $supplyId
 * @param float $baseQuantity Quantidade base (ratio × lote)
 * @param float $lossPercent Percentual de perda
 * @return float Quantidade efetiva a consumir
 */
public function calculateEffectiveConsumption(int $supplyId, float $baseQuantity, float $lossPercent = 0.0): float
```

### 2.2 `SupplyStock.php` — Métodos a Adicionar

```php
// === v1 PENDENTES ===

/**
 * Registra movimentação de estoque (entrada, saída, ajuste, transferência, consumo).
 * DELEGADO ao SupplyStockMovementService para lógica transacional.
 */
public function recordMovement(array $data): int

/**
 * Retorna itens de estoque com lote mais próximo do vencimento (FEFO).
 * @param int $supplyId
 * @param int $warehouseId
 * @return array Itens ordenados por expiry_date ASC NULLS LAST
 */
public function getFefoItems(int $supplyId, int $warehouseId): array

/**
 * Retorna movimentações filtradas.
 */
public function getMovements(array $filters, int $page, int $perPage): array

/**
 * Conta movimentações para paginação.
 */
public function countMovements(array $filters): int

// === v2 NOVOS ===

/**
 * Retorna itens próximos do vencimento (para alertas).
 * @param int $daysAhead Considerar itens que vencem nos próximos N dias
 */
public function getExpiringItems(int $daysAhead = 30): array

/**
 * Calcula CMP (Custo Médio Ponderado) de um insumo.
 */
public function calculateCmp(int $supplyId): float
```

### 2.3 `SupplySubstitute.php` — Novo Model

```php
<?php

namespace Akti\Models;

class SupplySubstitute
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Lista substitutos de um insumo, ordenados por prioridade.
     */
    public function getSubstitutes(int $supplyId): array

    /**
     * Adiciona substituto com taxa de conversão e prioridade.
     */
    public function addSubstitute(int $supplyId, array $data): int

    /**
     * Atualiza substituto (conversão, prioridade, notas).
     */
    public function updateSubstitute(int $id, array $data): bool

    /**
     * Remove substituto.
     */
    public function removeSubstitute(int $id): bool

    /**
     * Retorna primeiro substituto ativo com estoque disponível.
     * @param int $supplyId Insumo principal
     * @param float $requiredQty Quantidade necessária (já convertida)
     * @param int $warehouseId Depósito
     * @return array|null Substituto com estoque ou null
     */
    public function findAvailableSubstitute(int $supplyId, float $requiredQty, int $warehouseId): ?array
}
```

### 2.4 `ProductionConsumption.php` — Novo Model

```php
<?php

namespace Akti\Models;

class ProductionConsumption
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Registra consumo planejado para uma ordem.
     */
    public function logPlannedConsumption(int $orderId, array $items): void

    /**
     * Registra consumo real apontado pelo operador.
     */
    public function logActualConsumption(int $logId, float $actualQty, ?string $notes = null): bool

    /**
     * Retorna log de consumo de uma ordem.
     */
    public function getOrderConsumption(int $orderId): array

    /**
     * Dashboard: previsto vs real agregado por período.
     */
    public function getEfficiencyData(array $filters): array

    /**
     * Top N insumos com maior desperdício.
     */
    public function getTopWaste(int $limit = 10, ?string $dateFrom = null, ?string $dateTo = null): array
}
```

---

## 3. Services — Detalhamento

### 3.1 `SupplyStockMovementService.php` — Processamento Transacional

**Responsabilidade:** Toda operação de movimentação de estoque passa por este service. Ele garante transações atômicas, cálculo de CMP, FEFO e auditoria.

```php
<?php

namespace Akti\Services;

class SupplyStockMovementService
{
    private \PDO $db;
    private SupplyStock $stockModel;
    private Supply $supplyModel;
    private AuditLogService $auditService;

    public function __construct(\PDO $db, SupplyStock $stockModel, Supply $supplyModel, AuditLogService $auditService)

    /**
     * Processa entrada de insumo no estoque.
     * - Atualiza/cria supply_stock_items
     * - Registra movimento
     * - Aplica fator de conversão do fornecedor
     * - Recalcula CMP (se auto_recalculate_cmp = true)
     * - Registra no supply_price_history
     * - Verifica limites de margem (dispara alerta se necessário)
     *
     * @param array $data [warehouse_id, supply_id, quantity, unit_price, batch_number, expiry_date, reason, supplier_id]
     * @return int ID da movimentação
     * @throws \RuntimeException Em caso de falha na transação
     */
    public function processEntry(array $data): int

    /**
     * Processa saída de insumo do estoque.
     * - Aplica estratégia FEFO/FIFO para seleção de lote
     * - Valida estoque suficiente (ou permite negativo conforme config)
     * - Registra movimento
     * - Atualiza supply_stock_items
     */
    public function processExit(array $data): int

    /**
     * Processa ajuste de estoque (inventário).
     */
    public function processAdjustment(array $data): int

    /**
     * Processa transferência entre depósitos.
     * - Saída no depósito origem + Entrada no depósito destino (mesma transação)
     */
    public function processTransfer(array $data): int

    /**
     * Processa consumo de produção.
     * - Calcula quantidade efetiva (ratio × lote × (1 + perda%))
     * - Aplica fracionamento (CEIL se não fracionável)
     * - Aplica FEFO
     * - Registra em production_consumption_log
     * - Registra movimento de estoque
     * - Se estoque insuficiente, busca substituto
     */
    public function processProductionConsumption(int $orderId, int $productId, ?int $variationId, int $warehouseId, int $quantity): array

    /**
     * Recalcula CMP de um insumo.
     * Fórmula: ((estoque_atual × custo_atual) + (qtd_entrada × preço_entrada)) / (estoque_atual + qtd_entrada)
     */
    private function recalculateCmp(int $supplyId, float $entryQty, float $entryPrice): float

    /**
     * Seleciona lotes pela estratégia configurada (FEFO/FIFO).
     * @return array Lista de lotes a consumir com quantidades parciais
     */
    private function selectLotsByStrategy(int $supplyId, int $warehouseId, float $requiredQty): array
}
```

### 3.2 `InsumoService.php` — Lógica de Insumo e BOM

```php
<?php

namespace Akti\Services;

class InsumoService
{
    private \PDO $db;
    private Supply $supplyModel;
    private AuditLogService $auditService;

    public function __construct(\PDO $db, Supply $supplyModel, AuditLogService $auditService)

    /**
     * Calcula quantidade efetiva considerando fracionamento e perda.
     *
     * @param float $baseQuantity Quantidade base da BOM
     * @param float $lossPercent Percentual de perda
     * @param bool $allowsFractionation Se permite fracionamento
     * @param int $precision Casas decimais
     * @return float Quantidade efetiva
     */
    public function calculateEffectiveQuantity(
        float $baseQuantity,
        float $lossPercent,
        bool $allowsFractionation = true,
        int $precision = 4
    ): float

    /**
     * Calcula BOM completa para um lote de produção.
     *
     * @param int $productId
     * @param int|null $variationId
     * @param int $lotSize Tamanho do lote
     * @return array Lista de insumos com quantidades calculadas
     */
    public function calculateBomForLot(int $productId, ?int $variationId, int $lotSize): array

    /**
     * Calcula custo de produção baseado na BOM.
     * Soma: Σ(qty_efetiva × CMP_insumo) para todos insumos obrigatórios.
     */
    public function calculateProductionCost(int $productId, ?int $variationId = null): float

    /**
     * Verifica disponibilidade de insumos para produzir N unidades.
     * @return array [available => bool, missing => [...], substitutes => [...]]
     */
    public function checkAvailability(int $productId, ?int $variationId, int $quantity, int $warehouseId): array
}
```

### 3.3 `ProducaoService.php` — Gestão de Produção

```php
<?php

namespace Akti\Services;

class ProducaoService
{
    private \PDO $db;
    private InsumoService $insumoService;
    private SupplyStockMovementService $movementService;
    private ProductionConsumption $consumptionModel;
    private AuditLogService $auditService;

    public function __construct(...)

    /**
     * Inicia consumo de insumos para uma ordem de produção.
     * 1. Calcula BOM para o lote
     * 2. Verifica disponibilidade (com substitutos)
     * 3. Registra consumo planejado
     * 4. Processa baixa de estoque
     *
     * @return array [success, items_consumed, substitutions, warnings]
     */
    public function startProduction(int $orderId, int $productId, ?int $variationId, int $quantity, int $warehouseId): array

    /**
     * Registra apontamento de consumo real pelo operador.
     */
    public function reportActualConsumption(int $logId, float $actualQuantity, ?string $notes = null): bool

    /**
     * Dados para dashboard de eficiência.
     * @return array [by_period, by_supply, by_product, totals]
     */
    public function getEfficiencyDashboard(array $filters): array
}
```

### 3.4 `SupplyForecastService.php` — Previsão de Ruptura

```php
<?php

namespace Akti\Services;

class SupplyForecastService
{
    private \PDO $db;
    private Supply $supplyModel;
    private SupplyStock $stockModel;

    public function __construct(...)

    /**
     * Recalcula previsão de ruptura para todos os insumos.
     * Cruza pedidos em aberto (pipeline stages ativos) com estoque atual.
     *
     * Lógica:
     * 1. Para cada pedido em aberto, calcular BOM
     * 2. Somar consumo comprometido por insumo
     * 3. Comparar com estoque disponível
     * 4. Calcular dias até ruptura (baseado em média de consumo)
     * 5. Classificar status: ok / warning / critical / ruptured
     *
     * @return array Resumo do forecast
     */
    public function recalculateForecasts(): array

    /**
     * Retorna insumos com ruptura iminente.
     * @param string $minStatus Mínimo: 'warning', 'critical', 'ruptured'
     */
    public function getRuptureAlerts(string $minStatus = 'warning'): array

    /**
     * Retorna forecast detalhado de um insumo específico.
     * Inclui: pedidos que demandam, estoque por depósito, substitutos disponíveis.
     */
    public function getSupplyForecastDetail(int $supplyId): array
}
```

### 3.5 `SupplyCostService.php` — Gestão de Custo

```php
<?php

namespace Akti\Services;

class SupplyCostService
{
    private \PDO $db;
    private Supply $supplyModel;
    private InsumoService $insumoService;

    public function __construct(...)

    /**
     * Após entrada com novo preço, verifica impacto nos produtos.
     * Se margem cai abaixo do threshold, gera alerta em supply_cost_alerts.
     *
     * @param int $supplyId Insumo cujo CMP foi atualizado
     * @param float $newCmp Novo CMP calculado
     */
    public function checkMarginImpact(int $supplyId, float $newCmp): void

    /**
     * Retorna alertas de custo pendentes.
     */
    public function getPendingAlerts(): array

    /**
     * Marca alerta como reconhecido/aplicado/dispensado.
     */
    public function updateAlertStatus(int $alertId, string $status, int $userId): bool

    /**
     * Calcula preço sugerido para manter margem mínima.
     * @return float Preço sugerido
     */
    public function suggestPrice(int $productId, float $minMarginPercent): float
}
```

---

## 4. Controllers — Modificações

### 4.1 `SupplyController.php` — Actions a Adicionar

```php
// === FORNECEDORES ===
public function suppliers(int $supplyId): void    // GET — Tab fornecedores (AJAX)
public function addSupplier(): void               // POST — Vincular fornecedor
public function updateSupplier(): void            // POST — Atualizar vínculo
public function removeSupplier(): void            // POST — Remover vínculo

// === BOM ===
public function productBom(int $productId): void  // GET — Aba BOM no produto (AJAX)
public function addProductSupply(): void          // POST — Adicionar insumo à BOM
public function updateProductSupply(): void       // POST — Atualizar vínculo BOM
public function removeProductSupply(): void       // POST — Remover vínculo BOM

// === SUBSTITUTOS (v2) ===
public function substitutes(int $supplyId): void  // GET — Tab substitutos (AJAX)
public function addSubstitute(): void             // POST — Adicionar substituto
public function updateSubstitute(): void          // POST — Atualizar substituto
public function removeSubstitute(): void          // POST — Remover substituto

// === CUSTO (v2) ===
public function costAlerts(): void                // GET — Listagem de alertas de custo
public function updateAlertStatus(): void         // POST — Ação sobre alerta
public function costImpactAnalysis(): void        // GET — Análise de impacto (AJAX)
```

### 4.2 `SupplyStockController.php` — Actions a Completar/Adicionar

```php
// === v1 COMPLETAR ===
public function storeEntry(): void    // POST — Processar entrada (via MovementService)
public function storeExit(): void     // POST — Processar saída (via MovementService)
public function storeTransfer(): void // POST — Processar transferência
public function storeAdjust(): void   // POST — Processar ajuste

// === v2 NOVOS ===
public function forecast(): void      // GET — Dashboard de previsão de ruptura
public function expiringItems(): void // GET — Itens próximos do vencimento
```

### 4.3 `SupplyDashboardController.php` — Novo Controller

```php
<?php

namespace Akti\Controllers;

class SupplyDashboardController extends BaseController
{
    public function efficiency(): void      // GET — Dashboard de eficiência (Previsto vs Real)
    public function efficiencyData(): void  // GET/AJAX — Dados do gráfico (JSON para Chart.js)
    public function orderDetail(): void     // GET/AJAX — Detalhe de consumo de uma ordem
}
```

---

## 5. Rotas a Adicionar (`app/config/routes.php`)

```php
'supplies' => [
    // ... actions existentes ...
    // Adicionar:
    'actions' => [
        // ... existentes ...
        'suppliers'          => 'suppliers',
        'addSupplier'        => 'addSupplier',
        'updateSupplier'     => 'updateSupplier',
        'removeSupplier'     => 'removeSupplier',
        'substitutes'        => 'substitutes',
        'addSubstitute'      => 'addSubstitute',
        'updateSubstitute'   => 'updateSubstitute',
        'removeSubstitute'   => 'removeSubstitute',
        'costAlerts'         => 'costAlerts',
        'updateAlertStatus'  => 'updateAlertStatus',
        'costImpactAnalysis' => 'costImpactAnalysis',
    ],
],

'supply_stock' => [
    // ... actions existentes ...
    // Adicionar:
    'actions' => [
        // ... existentes ...
        'forecast'       => 'forecast',
        'expiringItems'  => 'expiringItems',
    ],
],

// NOVA ROTA
'supply_dashboard' => [
    'controller'     => 'SupplyDashboardController',
    'default_action' => 'efficiency',
    'public'         => false,
    'actions' => [
        'efficiency'     => 'efficiency',
        'efficiencyData' => 'efficiencyData',
        'orderDetail'    => 'orderDetail',
    ],
],
```

---

## 6. Injeção de Dependência

```
SupplyController
  ├── Supply (model)
  ├── Supplier (model)
  ├── SupplySubstitute (model)
  ├── InsumoService
  └── SupplyCostService

SupplyStockController
  ├── SupplyStock (model)
  ├── Supply (model)
  ├── SupplyStockMovementService
  └── SupplyForecastService

SupplyDashboardController
  ├── ProducaoService
  └── ProductionConsumption (model)

SupplyStockMovementService
  ├── SupplyStock (model)
  ├── Supply (model)
  ├── SupplySubstitute (model)
  ├── AuditLogService
  └── SupplyCostService

InsumoService
  ├── Supply (model)
  └── AuditLogService

ProducaoService
  ├── InsumoService
  ├── SupplyStockMovementService
  ├── ProductionConsumption (model)
  └── AuditLogService

SupplyForecastService
  ├── Supply (model)
  └── SupplyStock (model)

SupplyCostService
  ├── Supply (model)
  └── InsumoService
```

---

## 7. Eventos v2

| Evento | Disparado quando | Listeners |
|--------|-----------------|-----------|
| `supply.stock.entry` | Entrada de estoque processada | CMP recalc, cost alert check |
| `supply.stock.exit` | Saída processada | Forecast recalc |
| `supply.stock.low` | Estoque abaixo do mínimo | Alerta visual |
| `supply.cmp.updated` | CMP recalculado | Margin check, cost alert |
| `supply.rupture.detected` | Ruptura iminente detectada | Alerta dashboard |
| `production.consumption.logged` | Consumo planejado registrado | — |
| `production.actual.logged` | Consumo real apontado | Eficiência recalc |

---

*Anterior: [02 — Modelo de Dados](02-modelo-dados.md) | Próximo: [04 — Frontend/UI](04-frontend-ui.md)*
