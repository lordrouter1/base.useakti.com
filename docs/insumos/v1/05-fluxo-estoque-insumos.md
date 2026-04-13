# 05 — Fluxo de Estoque de Insumos

## 1. Visão Geral

O estoque de insumos reutiliza a infraestrutura de **armazéns** (`warehouses`) existente no sistema, mas possui tabelas próprias (`supply_stock_items` e `supply_stock_movements`) para separar posição e histórico de insumos do estoque de produtos acabados.

Recursos avançados incluídos:
- **Controle de Lotes e Validade** (rastreabilidade por lote, estratégia FEFO)
- **Conversão de Unidade (UOM)** na entrada via fornecedor
- **Gatilhos de Reposição (MRP simplificado)** com alertas automáticos

---

## 2. Fluxo Geral — Diagrama

```
┌───────────────────────────────────────────────────────────────────────┐
│                     ESTOQUE DE INSUMOS                                │
│                ?page=supply_stock&action=index                        │
│                                                                       │
│  ┌─────────────┐ ┌──────────────┐ ┌────────────┐ ┌───────────────┐   │
│  │ Armazém     │ │ Busca        │ │ Só estoque │ │[+ Entrada]    │   │
│  │ [Todos ▼]   │ │ [_________]  │ │ baixo [✓]  │ │[+ Saída]      │   │
│  └─────────────┘ └──────────────┘ └────────────┘ │[+ Ajuste]     │   │
│                                                   └───────────────┘   │
│                                                                       │
│  ┌───── Dashboard Cards ────────────────────────────────────────────┐ │
│  │ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌────────────┐ │ │
│  │ │ Total Itens │ │ Valor Total │ │ Est. Baixo  │ │ Movim. Mês │ │ │
│  │ │    147      │ │ R$ 34.500   │ │   ⚠ 12     │ │    83      │ │ │
│  │ └─────────────┘ └─────────────┘ └─────────────┘ └────────────┘ │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                       │
│  ┌─────┬──────────┬────────┬────────┬────────┬────────┬──────┬─────┐ │
│  │ Cód │ Insumo   │Armazém │Qtd Atu.│ Mínimo │ Status │ Lote   │ Validade │ Loc. │ ⚙  │ │
│  ├─────┼──────────┼────────┼────────┼────────┼────────┼────────┼──────────┼──────┼─────┤ │
│  │ 001 │ Tecido   │ Princ. │ 150 m  │ 50 m   │ ✅ OK  │ L2026A │ —        │ A-01 │ ✎  │ │
│  │ 002 │ Tinta AZ │ Princ. │   5 L  │ 20 L   │ ⚠ Bx  │ LT-042 │ 15/07/26 │ B-03 │ ✎  │ │
│  │ 003 │ Parafuso │ Sec.   │ 5000un │ 500 un │ ✅ OK  │ —      │ —        │ C-12 │ ✎  │ │
│  │ 004 │ Cola PVA │ Princ. │   0 L  │ 10 L   │ 🔴 Sem │ —      │ 01/05/26 │ B-01 │ ✎  │ │
│  └─────┴──────────┴────────┴────────┴────────┴────────┴────────┴──────────┴──────┴─────┘ │
│                                                                       │
│  Status: ✅ OK (> mínimo)  ⚠ Baixo (<= mínimo)  🔴 Sem Estoque (0)  │
└───────────────────────────────────────────────────────────────────────┘
```

---

## 3. Tipos de Movimentação

| Tipo | Código | Descrição | Efeito no Estoque |
|------|--------|-----------|-------------------|
| **Entrada** | `entrada` | Recebimento de material (compra, doação, devolução) | + quantidade |
| **Saída** | `saida` | Retirada manual de material | - quantidade |
| **Ajuste** | `ajuste` | Correção de inventário (positivo ou negativo) | ± quantidade |
| **Transferência** | `transferencia` | Mover entre armazéns | - origem, + destino |
| **Consumo Produção** | `consumo_producao` | Consumo automático ao produzir (futuro v2) | - quantidade |

---

## 4. Fluxo — Entrada de Insumo

```
┌─────────────────────────────────────────────────────────────────┐
│              REGISTRAR ENTRADA DE INSUMO                         │
│           ?page=supply_stock&action=entry                        │
│                                                                  │
│  Armazém*: [Principal ▼]                                        │
│  Motivo:   [Compra ▼]  (Compra | Devolução | Ajuste Inventário │
│                          | Transferência Recebida | Outro)       │
│  Referência: [Pedido Compra #PC-0042]  (opcional, link)         │
│                                                                  │
│  ┌─ Itens ──────────────────────────────────────────────────┐   │
│  │                                                          │   │
│  │ Insumo*        │ Quantidade* │ Custo Unit.│ Subtotal    │   │
│  │ [🔍 Buscar ▼]  │ [_______]  │ [______]   │ R$ 0,00    │   │
│  │                                                          │   │
│  │ Lote: [________]  Validade: [__/__/____]  (opcionais)   │   │
│  │ Fornecedor: [Select2 ▼]  → Fator UOM: ×50 (auto)       │   │
│  │ Qtd na Nota: 10 cx → Qtd Estoque: 500 un (calculado)   │   │
│  │                                                          │   │
│  │ [+ Adicionar item]                                       │   │
│  │                                                          │   │
│  │ ┌──────────────┬──────────┬──────────┬──────────┬─────┐ │   │
│  │ │ Insumo       │ Qtd      │ Custo    │ Subtotal │  ✕  │ │   │
│  │ ├──────────────┼──────────┼──────────┼──────────┼─────┤ │   │
│  │ │ Tecido Alg.  │ 100 m    │ 12,50    │ 1.250,00 │  ✕  │ │   │
│  │ │ Fio Poliést. │ 50 m     │ 8,30     │   415,00 │  ✕  │ │   │
│  │ └──────────────┴──────────┴──────────┴──────────┴─────┘ │   │
│  │                                                          │   │
│  │ Total: R$ 1.665,00                                       │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  Observação geral: [________________________________]           │
│                                                                  │
│  [Cancelar]                          [✓ Registrar Entrada]      │
└─────────────────────────────────────────────────────────────────┘
```

### 4.1 Processamento da Entrada

```
Para CADA item na lista:
  1. Se fornecedor selecionado, buscar conversion_factor do supply_suppliers
     → qtd_estoque = qtd_nota × conversion_factor
     → Se sem fornecedor, qtd_estoque = qtd_informada (fator = 1)
  2. Buscar/criar supply_stock_items (warehouse_id + supply_id + batch_number)
  3. supply_stock_items.quantity += qtd_estoque
  4. Se batch_number informado, gravar em supply_stock_items.batch_number
  5. Se expiry_date informado, gravar em supply_stock_items.expiry_date
  6. INSERT supply_stock_movements (type='entrada', quantity, unit_price, batch_number, reason, reference)
  7. Recalcular Custo Médio Ponderado (CMP) do insumo:
     → CMP = (est_atual × custo_atual + qtd_estoque × preço_unit) / (est_atual + qtd_estoque)
     → UPDATE supplies SET cost_price = CMP
  8. INSERT supply_price_history (supply_id, supplier_id, price, movement_id)
  9. Atualizar supply_stock_items.last_updated = NOW()
  10. Verificar gatilho de reposição (ver seção 12)

Se todos os itens processados com sucesso:
  → flash_success = "Entrada registrada: X itens processados."
  → redirect supply_stock

Se erro em algum item:
  → Rollback transação
  → flash_error = "Erro ao processar item: {nome}"
```

---

## 5. Fluxo — Saída de Insumo

```
┌─────────────────────────────────────────────────────────────────┐
│              REGISTRAR SAÍDA DE INSUMO                           │
│           ?page=supply_stock&action=exit                         │
│                                                                  │
│  Armazém*: [Principal ▼]                                        │
│  Motivo:   [Consumo ▼]  (Consumo Produção | Perda/Avaria |     │
│                          Amostra | Uso Interno | Outro)          │
│                                                                  │
│  ┌─ Itens ──────────────────────────────────────────────────┐   │
│  │ Insumo*        │ Quantidade* │ Est. Atual │ Est. Após    │   │
│  │ [🔍 Buscar ▼]  │ [_______]  │ 150 m      │ 50 m        │   │
│  │                                                          │   │
│  │ ⚠ Alerta se quantidade > estoque atual                   │   │
│  │ 🚫 Bloquear se estoque insuficiente (configurável)       │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  [Cancelar]                            [✓ Registrar Saída]      │
└─────────────────────────────────────────────────────────────────┘
```

### 5.1 Validações de Saída

- **Estoque suficiente:** quantidade solicitada <= estoque atual
- **Permitir negativo:** configuração do tenant (padrão: NÃO)
- Se quantidade > estoque: exibir alerta SweetAlert2 antes de confirmar
- Registrar `created_by` (usuário logado) para auditoria

### 5.2 Estratégia FEFO (First Expired, First Out)

Ao registrar saída/consumo de um insumo que possui lotes com validade:

1. O sistema **sugere automaticamente** o lote com a **data de validade mais próxima**
2. O operador pode **aceitar ou alterar** o lote sugerido
3. Se há múltiplos lotes, a saída consome primeiro o mais próximo do vencimento
4. Lotes vencidos são exibidos com destaque vermelho e alerta

```
Seleção automática de lote (FEFO):
  1. SELECT FROM supply_stock_items
     WHERE supply_id = :id AND warehouse_id = :wh AND quantity > 0
     ORDER BY expiry_date ASC NULLS LAST, created_at ASC
  2. Primeiro resultado = lote sugerido
  3. Se quantidade solicitada > estoque do lote, dividir entre lotes
```

**Wireframe — Seleção de Lote na Saída:**

```
┌──────────────────────────────────────────────────────────────┐
│ Insumo: Tinta Azul                                           │
│                                                              │
│ Lotes disponíveis:                                           │
│ ┌─────────┬──────────┬──────────┬──────────────────────────┐│
│ │ Lote    │ Validade │ Estoque  │ Selecionar               ││
│ ├─────────┼──────────┼──────────┼──────────────────────────┤│
│ │ LT-039  │ 15/05/26 │ 3 L     │ ⚡ Sugerido (FEFO)       ││
│ │ LT-042  │ 15/07/26 │ 8 L     │ [  ] Selecionar          ││
│ │ LT-045  │ 20/09/26 │ 12 L    │ [  ] Selecionar          ││
│ └─────────┴──────────┴──────────┴──────────────────────────┘│
│                                                              │
│ Quantidade a retirar: [5 L]                                  │
│ → Consumirá: 3 L do LT-039 + 2 L do LT-042                 │
└──────────────────────────────────────────────────────────────┘
```

---

## 6. Fluxo — Transferência entre Armazéns

```
┌─────────────────────────────────────────────────────────────────┐
│           TRANSFERÊNCIA DE INSUMO ENTRE ARMAZÉNS                 │
│          ?page=supply_stock&action=transfer                      │
│                                                                  │
│  Armazém Origem*:  [Principal ▼]                                │
│  Armazém Destino*: [Secundário ▼]                               │
│                                                                  │
│  ┌─ Itens ──────────────────────────────────────────────────┐   │
│  │ Insumo*        │ Qtd* │ Est. Origem │ Est. Destino       │   │
│  │ [🔍 Buscar ▼]  │ [__] │ 150 m       │ 20 m → 120 m     │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  [Cancelar]                       [✓ Realizar Transferência]    │
└─────────────────────────────────────────────────────────────────┘
```

### 6.1 Processamento

```
Em uma TRANSAÇÃO:
  1. Saída no armazém origem (type='transferencia', quantity negativo)
  2. Entrada no armazém destino (type='transferencia', quantity positivo)
  3. Ambos os movimentos com reference_type='transfer', reference_id=linkados
```

---

## 7. Fluxo — Histórico de Movimentações

```
┌────────────────────────────────────────────────────────────────────┐
│            HISTÓRICO DE MOVIMENTAÇÕES DE INSUMOS                   │
│           ?page=supply_stock&action=movements                      │
│                                                                    │
│  Filtros:                                                          │
│  Armazém: [Todos ▼]  Insumo: [🔍 Buscar ▼]  Tipo: [Todos ▼]     │
│  Período: [__/__/____] a [__/__/____]           [🔍 Filtrar]      │
│                                                                    │
│  ┌──────┬──────────┬──────────┬──────────┬────────┬────────┬──────┬──────┐ │
│  │ Data │ Insumo   │ Armazém  │ Tipo     │ Qtd    │ Lote   │ Ref. │ User │ │
│  ├──────┼──────────┼──────────┼──────────┼────────┼────────┼──────┼──────┤ │
│  │ 13/04│ Tecido   │ Princip. │ ↓Entrada │ +100 m │ L2026A │ PC42 │ João │ │
│  │ 12/04│ Tinta AZ │ Princip. │ ↑Saída   │ -15 L  │ LT-039 │ —    │ Maria│ │
│  │ 11/04│ Parafuso │ Princip→ │ ↔Transf  │ -500un │ —      │ —    │ João │ │
│  │      │          │ Secund.  │          │ +500un │        │      │      │ │
│  │ 10/04│ Cola PVA │ Princip. │ ±Ajuste  │ +2 L   │ —      │ Inv. │ Admin│ │
│  └──────┴──────────┴──────────┴──────────┴────────┴────────┴──────┴──────┘ │
│                                                                    │
│  Exportar: [CSV] [PDF]                   ◄ 1 [2] 3 4 ►           │
└────────────────────────────────────────────────────────────────────┘
```

---

## 8. Dashboard — Alertas de Estoque

```
┌────────────────────────────────────────────────────────────────┐
│  ⚠ Insumos com Estoque Crítico (12)                           │
│                                                                │
│  ┌────────────┬────────────┬──────────┬──────────┬──────────┐ │
│  │ Insumo     │ Est. Atual │ Mínimo   │ Deficit  │ Fornec.  │ │
│  ├────────────┼────────────┼──────────┼──────────┼──────────┤ │
│  │ Tinta AZ   │ 5 L        │ 20 L     │ -15 L    │ Quím.BR  │ │
│  │ Cola PVA   │ 0 L        │ 10 L     │ -10 L    │ Cola&Cia │ │
│  │ Fio Nylon  │ 30 m       │ 50 m     │ -20 m    │ Fios SA  │ │
│  └────────────┴────────────┴──────────┴──────────┴──────────┘ │
│                                                                │
│  [Ver todos] [Gerar Relatório]                                 │
└────────────────────────────────────────────────────────────────┘
```

---

## 9. Métodos do Model (`SupplyStock`)

```
SupplyStock::getItems(warehouseId, search, lowStockOnly)    → itens no armazém
SupplyStock::getOrCreateItem(warehouseId, supplyId, batch)  → busca/cria item (com lote)
SupplyStock::updateItemMeta(id, minQuantity, locationCode)  → atualizar metadata
SupplyStock::getDashboardSummary(warehouseId)               → totais do dashboard
SupplyStock::getLowStockItems(limit)                        → itens abaixo do mínimo
SupplyStock::getReorderItems()                              → itens no ponto de pedido
SupplyStock::addMovement(data)                              → registrar movimentação
SupplyStock::getMovements(filters, page, perPage)           → histórico paginado
SupplyStock::getMovement(id)                                → uma movimentação
SupplyStock::getTotalStock(supplyId)                        → total em todos armazéns
SupplyStock::getBatchesBySupply(supplyId, warehouseId)      → lotes disponíveis (FEFO order)
SupplyStock::getExpiringBatches(days, limit)                → lotes próximos do vencimento
SupplyStock::getExpiredBatches(limit)                       → lotes vencidos
```

---

## 10. Service Layer (`SupplyStockMovementService`)

Seguindo o padrão de `StockMovementService` existente:

```
SupplyStockMovementService::processEntry(warehouseId, items, reason, reference, supplierId)
  → aplica conversion_factor se supplierId informado
  → grava batch_number e expiry_date se informados
  → recalcula CMP e insere supply_price_history
SupplyStockMovementService::processExit(warehouseId, items, reason, reference)
  → aplica estratégia FEFO automaticamente se insumo tem lotes
SupplyStockMovementService::processAdjustment(warehouseId, items, reason)
SupplyStockMovementService::processTransfer(originId, destId, items)
SupplyStockMovementService::validateSufficientStock(warehouseId, supplyId, qty)
SupplyStockMovementService::calculateWeightedAverageCost(supplyId, newQty, newPrice)
  → CMP = (est × custo + qtd × preço) / (est + qtd)
SupplyStockMovementService::applyConversionFactor(supplyId, supplierId, qty)
  → retorna qty × conversion_factor
SupplyStockMovementService::suggestBatchForExit(supplyId, warehouseId, qty)
  → retorna lotes ordenados por FEFO com quantidades sugeridas
SupplyStockMovementService::checkReorderAlerts()
  → verifica insumos no ponto de pedido e dispara notificações
```

---

## 11. Actions do Controller (`SupplyStockController`)

| Action | HTTP | Método | Descrição |
|--------|------|--------|-----------|
| `index` | GET | `index()` | Dashboard de estoque de insumos |
| `entry` | GET | `entry()` | Formulário de entrada |
| `storeEntry` | POST | `storeEntry()` | Processar entrada |
| `exit` | GET | `exit()` | Formulário de saída |
| `storeExit` | POST | `storeExit()` | Processar saída |
| `transfer` | GET | `transfer()` | Formulário de transferência |
| `storeTransfer` | POST | `storeTransfer()` | Processar transferência |
| `adjust` | GET | `adjust()` | Formulário de ajuste |
| `storeAdjust` | POST | `storeAdjust()` | Processar ajuste |
| `movements` | GET | `movements()` | Histórico de movimentações |
| `searchSupplies` | GET | `searchSupplies()` | Buscar insumos (Select2 JSON) |
| `getStockInfo` | GET | `getStockInfo()` | Info de estoque por insumo (JSON) |
| `getBatches` | GET | `getBatches()` | Lotes de um insumo por armazém (JSON/FEFO) |
| `reorderSuggestions` | GET | `reorderSuggestions()` | Sugestões de reposição MRP (JSON) |

---

## 12. Gatilhos de Reposição (MRP Simplificado)

### 12.1 Lógica de Verificação

O sistema verifica automaticamente o ponto de pedido (`reorder_point`) do insumo após cada movimentação de saída. Pode também ser executado como **job/cron** periódico.

```
Para CADA insumo com reorder_point > 0:
  1. total_stock = SUM(supply_stock_items.quantity) WHERE supply_id = X
  2. Se total_stock <= supplies.reorder_point:
     → Gerar alerta de reposição
     → Buscar fornecedor preferencial (is_preferred = 1)
     → Enviar notificação ao setor de compras
```

### 12.2 Card "Sugestões de Compra" no Dashboard

```
┌────────────────────────────────────────────────────────────────────┐
│ 🛒 Sugestões de Compra                                  [Ver Todas]│
│                                                                    │
│ ┌────────────┬────────┬────────┬───────────┬────────────┬────────┐│
│ │ Insumo     │Estoque │Pto.Ped.│ Qtd.Sug.  │ Forn.Pref. │ Ação   ││
│ ├────────────┼────────┼────────┼───────────┼────────────┼────────┤│
│ │ Tinta AZ   │   5 L  │ 20 L   │ 50 L      │ Quím.BR    │ [Pedir]││
│ │ Cola PVA   │   0 L  │ 10 L   │ 30 L      │ Cola&Cia   │ [Pedir]││
│ │ Fio Nylon  │  30 m  │ 50 m   │ 100 m     │ Fios SA    │ [Pedir]││
│ └────────────┴────────┴────────┴───────────┴────────────┴────────┘│
│                                                                    │
│ Qtd Sugerida = min_order_qty do fornecedor preferencial            │
│          (ou reorder_point × 2 - estoque_atual se sem fornecedor)  │
│                                                                    │
│ [Pedir] → Abre formulário de Pedido de Compra pré-preenchido      │
└────────────────────────────────────────────────────────────────────┘
```

### 12.3 Regras do MRP

- **Ponto de Pedido:** Quando `total_stock <= reorder_point`, insumo entra na lista
- **Quantidade Sugerida:** `min_order_qty` do fornecedor preferencial (ou `reorder_point × 2 - estoque_atual`)
- **Fornecedor Sugerido:** O marcado como `is_preferred = 1` no `supply_suppliers`
- **Botão [Pedir]:** Abre `?page=suppliers&action=createPurchase` pré-preenchido com fornecedor e itens
- **Notificação:** Integrar com módulo de notificações do sistema (se disponível):
  - Tipo: `supply_reorder`
  - Destinários: usuários com permissão de `supplies` ou `suppliers`
  - Mensagem: _"Insumo {nome} atingiu o ponto de pedido. Estoque: {qtd}. Fornec. preferencial: {nome}."_
  - Link: `?page=supply_stock&action=reorderSuggestions`

### 12.4 Execução Periódica (Cron/Job)

```php
// scripts/check_supply_reorder.php — Executar via cron (ex: a cada 6h)
// 1. Buscar todos os insumos com reorder_point > 0
// 2. Para cada um, calcular total_stock
// 3. Se total_stock <= reorder_point E não há alerta aberto recente (< 24h):
//    → Criar notificação
//    → Disparar evento: model.supply.reorder_alert
```

---

## 13. Dashboard de Lotes e Validade

```
┌────────────────────────────────────────────────────────────────────┐
│ 📦 Lotes Próximos do Vencimento (próximos 30 dias)                │
│                                                                    │
│ ┌────────────┬──────────┬──────────┬──────────┬──────────────────┐│
│ │ Insumo     │ Lote     │ Validade │ Estoque  │ Status           ││
│ ├────────────┼──────────┼──────────┼──────────┼──────────────────┤│
│ │ Cola PVA   │ LT-030   │ 01/05/26 │ 3 L     │ 🔴 Vence em 18d ││
│ │ Tinta AZ   │ LT-039   │ 15/05/26 │ 5 L     │ ⚠ Vence em 32d  ││
│ │ Resina EP  │ RE-012   │ 28/05/26 │ 2 kg    │ ⚠ Vence em 45d  ││
│ └────────────┴──────────┴──────────┴──────────┴──────────────────┘│
│                                                                    │
│ 🔴 = Vence em ≤ 30 dias    ⚠ = Vence em 31-60 dias               │
└────────────────────────────────────────────────────────────────────┘
```
