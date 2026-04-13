# 05 — Fluxo de Estoque de Insumos

## 1. Visão Geral

O estoque de insumos reutiliza a infraestrutura de **armazéns** (`warehouses`) existente no sistema, mas possui tabelas próprias (`supply_stock_items` e `supply_stock_movements`) para separar posição e histórico de insumos do estoque de produtos acabados.

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
│  │ Cód │ Insumo   │Armazém │Qtd Atu.│ Mínimo │ Status │ Loc. │ ⚙  │ │
│  ├─────┼──────────┼────────┼────────┼────────┼────────┼──────┼─────┤ │
│  │ 001 │ Tecido   │ Princ. │ 150 m  │ 50 m   │ ✅ OK  │ A-01 │ ✎  │ │
│  │ 002 │ Tinta AZ │ Princ. │   5 L  │ 20 L   │ ⚠ Bx  │ B-03 │ ✎  │ │
│  │ 003 │ Parafuso │ Sec.   │ 5000un │ 500 un │ ✅ OK  │ C-12 │ ✎  │ │
│  │ 004 │ Cola PVA │ Princ. │   0 L  │ 10 L   │ 🔴 Sem │ B-01 │ ✎  │ │
│  └─────┴──────────┴────────┴────────┴────────┴────────┴──────┴─────┘ │
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
  1. Buscar/criar supply_stock_items (warehouse_id + supply_id)
  2. supply_stock_items.quantity += quantidade
  3. INSERT supply_stock_movements (type='entrada', quantity, unit_price, reason, reference)
  4. Atualizar cost_price do insumo (média ponderada ou último custo)
  5. Atualizar supply_stock_items.last_updated = NOW()

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
│  ┌──────┬──────────┬──────────┬──────────┬────────┬──────┬──────┐ │
│  │ Data │ Insumo   │ Armazém  │ Tipo     │ Qtd    │ Ref. │ User │ │
│  ├──────┼──────────┼──────────┼──────────┼────────┼──────┼──────┤ │
│  │ 13/04│ Tecido   │ Princip. │ ↓Entrada │ +100 m │ PC42 │ João │ │
│  │ 12/04│ Tinta AZ │ Princip. │ ↑Saída   │ -15 L  │ —    │ Maria│ │
│  │ 11/04│ Parafuso │ Princip→ │ ↔Transf  │ -500un │ —    │ João │ │
│  │      │          │ Secund.  │          │ +500un │      │      │ │
│  │ 10/04│ Cola PVA │ Princip. │ ±Ajuste  │ +2 L   │ Inv. │ Admin│ │
│  └──────┴──────────┴──────────┴──────────┴────────┴──────┴──────┘ │
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
SupplyStock::getOrCreateItem(warehouseId, supplyId)         → busca/cria item
SupplyStock::updateItemMeta(id, minQuantity, locationCode)  → atualizar metadata
SupplyStock::getDashboardSummary(warehouseId)               → totais do dashboard
SupplyStock::getLowStockItems(limit)                        → itens abaixo do mínimo
SupplyStock::addMovement(data)                              → registrar movimentação
SupplyStock::getMovements(filters, page, perPage)           → histórico paginado
SupplyStock::getMovement(id)                                → uma movimentação
SupplyStock::getTotalStock(supplyId)                        → total em todos armazéns
```

---

## 10. Service Layer (`SupplyStockMovementService`)

Seguindo o padrão de `StockMovementService` existente:

```
SupplyStockMovementService::processEntry(warehouseId, items, reason, reference)
SupplyStockMovementService::processExit(warehouseId, items, reason, reference)
SupplyStockMovementService::processAdjustment(warehouseId, items, reason)
SupplyStockMovementService::processTransfer(originId, destId, items)
SupplyStockMovementService::validateSufficientStock(warehouseId, supplyId, qty)
SupplyStockMovementService::calculateAverageCost(supplyId, newQty, newPrice)
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
