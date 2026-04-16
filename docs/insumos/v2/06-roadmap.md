# 06 — Roadmap de Implementação

## Visão Geral

O roadmap está dividido em **6 fases (F0–F5)**, respeitando as dependências técnicas. Cada fase é autocontida — pode ir para produção ao final, agregando valor incremental.

```
F0: Fundação          ──▶  F1: BOM Inteligente  ──▶  F2: Estoque Avançado
(completar v1 +            (fracionamento,            (FEFO ativo, CMP,
 MovementService)           variações, perda)          movimentações)
                                  │                          │
                                  ▼                          ▼
                           F3: Inteligência       ──▶  F4: Produção
                           (forecast, substitutos)     (consumo, apontamento,
                                                        eficiência)
                                                             │
                                                             ▼
                                                       F5: Validação Final
                                                       (testes, QA, docs)
```

---

## Fase 0 — Fundação (Completar Base v1)

**Objetivo:** Finalizar tudo que foi desenhado no v1 mas não implementado. Base obrigatória para tudo o mais.

### F0.1 — SupplyStockMovementService

| Item | Descrição | Tipo |
|------|-----------|------|
| F0.1.1 | Criar `app/services/SupplyStockMovementService.php` | Service |
| F0.1.2 | Implementar `processEntry()` — entrada com transação, registro de movimento | Método |
| F0.1.3 | Implementar `processExit()` — saída com validação de estoque | Método |
| F0.1.4 | Implementar `processAdjustment()` — ajuste de inventário | Método |
| F0.1.5 | Implementar `processTransfer()` — saída origem + entrada destino (mesma tx) | Método |
| F0.1.6 | Injetar service no `SupplyStockController` | Controller |
| F0.1.7 | Integrar `storeEntry()`, `storeExit()`, `storeTransfer()`, `storeAdjust()` no controller | Controller |

**Dependências:** Nenhuma
**Critérios de Aceite:**
- [ ] Entrada cria `supply_stock_items` (ou atualiza) + registra `supply_stock_movements`
- [ ] Saída valida estoque e registra movimento
- [ ] Transferência cria 2 movimentos atomicamente
- [ ] Ajuste registra com reason
- [ ] Todos os movimentos têm `created_by`

### F0.2 — Supply Model: Fornecedores

| Item | Descrição | Tipo |
|------|-----------|------|
| F0.2.1 | Implementar `getSuppliers()` em `Supply.php` | Model |
| F0.2.2 | Implementar `addSupplier()` com dados de pivot | Model |
| F0.2.3 | Implementar `updateSupplier()` | Model |
| F0.2.4 | Implementar `removeSupplier()` | Model |
| F0.2.5 | Implementar `setPreferredSupplier()` | Model |
| F0.2.6 | Adicionar actions de fornecedor no `SupplyController` | Controller |
| F0.2.7 | Implementar tab "Fornecedores" na view `supplies/form.php` | View |

**Dependências:** Nenhuma
**Critérios de Aceite:**
- [ ] AJAX: listar, adicionar, editar, remover fornecedores de um insumo
- [ ] Fator de conversão exibido e editável
- [ ] Apenas 1 fornecedor preferido por insumo
- [ ] Select2 para busca de fornecedores

### F0.3 — Supply Model: BOM e Preço

| Item | Descrição | Tipo |
|------|-----------|------|
| F0.3.1 | Implementar `getProductBom()` | Model |
| F0.3.2 | Implementar `addProductSupply()` | Model |
| F0.3.3 | Implementar `updateProductSupply()` | Model |
| F0.3.4 | Implementar `removeProductSupply()` | Model |
| F0.3.5 | Implementar `getWhereUsed()` | Model |
| F0.3.6 | Implementar `getPriceHistory()` e `addPriceHistory()` | Model |
| F0.3.7 | Adicionar aba "Insumos (BOM)" no formulário de produto | View |

**Dependências:** Nenhuma
**Critérios de Aceite:**
- [ ] AJAX: adicionar/editar/remover insumos de um produto
- [ ] Cálculo de custo total de BOM exibido na tela
- [ ] "Onde é Usado" exibe lista de produtos que usam o insumo
- [ ] Histórico de preços com dados

### F0.4 — SupplyStock Model: Completar

| Item | Descrição | Tipo |
|------|-----------|------|
| F0.4.1 | Implementar `getMovements()` com filtros e paginação | Model |
| F0.4.2 | Implementar `countMovements()` | Model |
| F0.4.3 | Completar lógica do `SupplyStockController` para todas as views existentes | Controller |

**Dependências:** F0.1 (MovementService)
**Critérios de Aceite:**
- [ ] Histórico de movimentações filtrado por tipo, período, insumo, depósito
- [ ] Paginação funcionando
- [ ] Dashboard com KPIs corretos

---

## Fase 1 — BOM Inteligente

**Objetivo:** Adicionar fracionamento, variações e fator de perda à composição de produto.

### F1.1 — Migration de Banco

| Item | Descrição | Tipo |
|------|-----------|------|
| F1.1.1 | ALTER `supplies` — add `permite_fracionamento`, `decimal_precision` | SQL |
| F1.1.2 | ALTER `product_supplies` — add `variation_id`, `loss_percent`, mod UNIQUE index | SQL |
| F1.1.3 | Gerar arquivo via skill `sql-migration` | SQL |

**Dependências:** Nenhuma
**Critérios de Aceite:**
- [ ] Arquivo SQL gerado em `/sql/` com nomenclatura correta
- [ ] Colunas com defaults sensatos (permite_fracionamento=1, decimal_precision=4, loss_percent=0)

### F1.2 — InsumoService

| Item | Descrição | Tipo |
|------|-----------|------|
| F1.2.1 | Criar `app/services/InsumoService.php` | Service |
| F1.2.2 | Implementar `calculateEffectiveQuantity()` — lógica de fracionamento + perda | Método |
| F1.2.3 | Implementar `calculateBomForLot()` — BOM completa para lote, com herança de variação | Método |
| F1.2.4 | Implementar `calculateProductionCost()` — custo baseado em CMP × BOM | Método |
| F1.2.5 | Implementar `checkAvailability()` — verificar estoque para produzir N unidades | Método |

**Dependências:** F0.3 (BOM no model), F1.1 (novas colunas)
**Critérios de Aceite:**
- [ ] `calculateEffectiveQuantity()` respeita `permite_fracionamento` (CEIL para não fracionáveis)
- [ ] `calculateBomForLot()` herda insumos do pai e faz override por variação
- [ ] `calculateProductionCost()` soma custo de todos insumos obrigatórios
- [ ] `checkAvailability()` retorna lista de insumos faltantes

### F1.3 — UI de Fracionamento e Variação

| Item | Descrição | Tipo |
|------|-----------|------|
| F1.3.1 | Adicionar campo `permite_fracionamento` (checkbox) no form de insumo | View |
| F1.3.2 | Adicionar campo `decimal_precision` (select) no form de insumo | View |
| F1.3.3 | Adicionar select de variação na aba BOM do produto | View |
| F1.3.4 | Adicionar campo `loss_percent` no modal de vínculo BOM | View |
| F1.3.5 | Exibir custo calculado com perda na tabela BOM | View |

**Dependências:** F1.1, F1.2, F0.3 (aba BOM)
**Critérios de Aceite:**
- [ ] Checkbox funcional com tooltip explicativo
- [ ] Select de variação filtra insumos por variação ou produto pai
- [ ] Perda % calculada e exibida em tempo real

### F1.4 — Testes Fase 1

| Item | Descrição | Tipo |
|------|-----------|------|
| F1.4.1 | `tests/Unit/InsumoServiceTest.php` — fracionamento, perda, CEIL | Teste |
| F1.4.2 | `tests/Unit/InsumoServiceBomTest.php` — BOM com variações, herança | Teste |
| F1.4.3 | `tests/Unit/InsumoServiceCostTest.php` — custo de produção | Teste |

**Critérios de Aceite:**
- [ ] ≥ 10 test cases cobrindo cenários de fracionamento
- [ ] ≥ 5 test cases de herança pai → variação
- [ ] 100% de cobertura nos métodos do InsumoService

---

## Fase 2 — Estoque Avançado

**Objetivo:** FEFO ativo, CMP automático, alertas de custo.

### F2.1 — FEFO nas Saídas

| Item | Descrição | Tipo |
|------|-----------|------|
| F2.1.1 | Implementar `getFefoItems()` no `SupplyStock` model | Model |
| F2.1.2 | Implementar `selectLotsByStrategy()` no MovementService | Service |
| F2.1.3 | Integrar FEFO no `processExit()` | Service |
| F2.1.4 | Suporte a consumo parcial de lotes (multi-lote) | Service |
| F2.1.5 | Exibir sugestão FEFO na tela de saída | View |

**Dependências:** F0.1 (MovementService), F0.4 (stock model completo)
**Critérios de Aceite:**
- [ ] Saída consome lote com menor `expiry_date` primeiro
- [ ] Consumo parcial de lotes funciona corretamente
- [ ] Exibição clara do lote selecionado na UI

### F2.2 — CMP Automático

| Item | Descrição | Tipo |
|------|-----------|------|
| F2.2.1 | Implementar `recalculateCmp()` no MovementService | Service |
| F2.2.2 | Integrar CMP no `processEntry()` — recalcular a cada entrada | Service |
| F2.2.3 | Registrar CMP em `supply_price_history` automaticamente | Service |
| F2.2.4 | Aplicar fator de conversão do fornecedor no cálculo de preço | Service |
| F2.2.5 | Atualizar `cost_price` na tabela `supplies` após recálculo | Service |

**Dependências:** F0.1, F0.2 (fornecedores com conversion_factor)
**Critérios de Aceite:**
- [ ] Fórmula CMP correta com 4 casas decimais
- [ ] Conversão de fornecedor aplicada antes do CMP
- [ ] Histórico de preço registrado com source = 'cmp_calculado'

### F2.3 — Alertas de Custo

| Item | Descrição | Tipo |
|------|-----------|------|
| F2.3.1 | Migration: criar tabelas `supply_cost_alerts` e `supply_settings` | SQL |
| F2.3.2 | Criar `app/services/SupplyCostService.php` | Service |
| F2.3.3 | Implementar `checkMarginImpact()` — verificar impacto em produtos | Método |
| F2.3.4 | Implementar `suggestPrice()` — cálculo de preço sugerido | Método |
| F2.3.5 | Integrar check no fluxo de entrada (após CMP) | Service |
| F2.3.6 | Criar view `supplies/cost_alerts.php` | View |
| F2.3.7 | Implementar ações sobre alertas (reconhecer, aplicar, dispensar) | Controller |

**Dependências:** F2.2 (CMP), F1.2 (InsumoService para custo de produção)
**Critérios de Aceite:**
- [ ] Alerta gerado automaticamente quando margem cai abaixo do threshold
- [ ] Preço sugerido calculado corretamente
- [ ] Ação "Aplicar" atualiza preço do produto
- [ ] Configuração de margem mínima por tenant

### F2.4 — Itens Próximos ao Vencimento

| Item | Descrição | Tipo |
|------|-----------|------|
| F2.4.1 | Implementar `getExpiringItems()` no SupplyStock model | Model |
| F2.4.2 | Card de alerta no dashboard de estoque | View |
| F2.4.3 | Filtro na listagem de estoque | View |

**Dependências:** F0.4
**Critérios de Aceite:**
- [ ] Card exibe itens que vencem em ≤ 30 dias
- [ ] Ordenação por urgência (mais próximo primeiro)

### F2.5 — Testes Fase 2

| Item | Descrição | Tipo |
|------|-----------|------|
| F2.5.1 | `tests/Unit/SupplyStockMovementServiceTest.php` — FEFO, multi-lote | Teste |
| F2.5.2 | `tests/Unit/SupplyCostServiceTest.php` — CMP, margem, preço sugerido | Teste |
| F2.5.3 | `tests/Integration/StockEntryFlowTest.php` — fluxo completo de entrada | Teste |

---

## Fase 3 — Inteligência

**Objetivo:** Previsão de ruptura e substitutos de emergência.

### F3.1 — Substitutos de Emergência

| Item | Descrição | Tipo |
|------|-----------|------|
| F3.1.1 | Migration: criar tabela `supply_substitutes` | SQL |
| F3.1.2 | Criar `app/models/SupplySubstitute.php` | Model |
| F3.1.3 | Implementar CRUD de substitutos | Model |
| F3.1.4 | Implementar `findAvailableSubstitute()` — busca por prioridade com estoque | Model |
| F3.1.5 | Adicionar tab "Substitutos" no form de insumo | View |
| F3.1.6 | Adicionar actions no `SupplyController` | Controller |
| F3.1.7 | Adicionar rotas de substitutos | Rota |

**Dependências:** F0.2 (form de insumo com tabs)
**Critérios de Aceite:**
- [ ] CRUD via AJAX com SweetAlert2
- [ ] Busca de substituto por prioridade + estoque
- [ ] Conversão de taxa aplicada corretamente

### F3.2 — Previsão de Ruptura

| Item | Descrição | Tipo |
|------|-----------|------|
| F3.2.1 | Migration: criar tabela `supply_rupture_forecasts` | SQL |
| F3.2.2 | Criar `app/services/SupplyForecastService.php` | Service |
| F3.2.3 | Implementar `recalculateForecasts()` — algoritmo completo | Método |
| F3.2.4 | Implementar `getRuptureAlerts()` — filtrado por status | Método |
| F3.2.5 | Implementar `getSupplyForecastDetail()` — detalhe por insumo | Método |
| F3.2.6 | Criar view `supply_stock/forecast.php` | View |
| F3.2.7 | Criar partial `supply_stock/_forecast_detail.php` | View |
| F3.2.8 | Adicionar action `forecast()` no `SupplyStockController` | Controller |
| F3.2.9 | Adicionar rota e menu | Config |

**Dependências:** F1.2 (InsumoService para BOM), F0.4 (stock model)
**Critérios de Aceite:**
- [ ] Dashboard com KPIs (ruptured, critical, warning, ok)
- [ ] Tabela filtrável com todos os insumos
- [ ] Detalhe mostra pedidos que demandam + substitutos
- [ ] Status calculado corretamente

### F3.3 — Integração Substitutos + Forecast

| Item | Descrição | Tipo |
|------|-----------|------|
| F3.3.1 | No detalhe de forecast, exibir substitutos disponíveis | View |
| F3.3.2 | Sugestão de substituto quando ruptura detectada | Service |
| F3.3.3 | Integrar busca de substituto no `processProductionConsumption()` | Service |

**Dependências:** F3.1, F3.2
**Critérios de Aceite:**
- [ ] Quando ruptura, sistema mostra substituto com estoque e taxa de conversão
- [ ] Consumo de produção oferece substituição automática

### F3.4 — Testes Fase 3

| Item | Descrição | Tipo |
|------|-----------|------|
| F3.4.1 | `tests/Unit/SupplySubstituteTest.php` — CRUD, busca por prioridade | Teste |
| F3.4.2 | `tests/Unit/SupplyForecastServiceTest.php` — cálculo de forecast, status | Teste |
| F3.4.3 | `tests/Integration/SubstitutionFlowTest.php` — fluxo de substituição parcial | Teste |

---

## Fase 4 — Produção e Eficiência

**Objetivo:** Consumo de produção automático, apontamento real e dashboard de eficiência.

### F4.1 — Consumo de Produção

| Item | Descrição | Tipo |
|------|-----------|------|
| F4.1.1 | Migration: criar tabela `production_consumption_log` | SQL |
| F4.1.2 | Criar `app/models/ProductionConsumption.php` | Model |
| F4.1.3 | Criar `app/services/ProducaoService.php` | Service |
| F4.1.4 | Implementar `startProduction()` — calcula BOM, verifica disponibilidade, baixa estoque | Método |
| F4.1.5 | Implementar `processProductionConsumption()` no MovementService | Método |
| F4.1.6 | Lógica de fracionamento (CEIL) no consumo | Service |
| F4.1.7 | Lógica de substituição parcial no consumo | Service |

**Dependências:** F1.2 (InsumoService), F2.1 (FEFO), F3.1 (substitutos)
**Critérios de Aceite:**
- [ ] Consumo calcula BOM completa para o lote
- [ ] FEFO aplicado na seleção de lotes
- [ ] Fracionamento (CEIL) aplicado corretamente
- [ ] Substituição oferecida quando necessário
- [ ] Log de consumo planejado registrado

### F4.2 — Apontamento de Consumo Real

| Item | Descrição | Tipo |
|------|-----------|------|
| F4.2.1 | Implementar `logActualConsumption()` no model | Model |
| F4.2.2 | Implementar `reportActualConsumption()` no ProducaoService | Service |
| F4.2.3 | Criar view `supply_dashboard/report.php` — tela de apontamento | View |
| F4.2.4 | Validar: consumo real não pode ser negativo | Service |
| F4.2.5 | Se real > planejado, ajustar estoque (baixa adicional) | Service |
| F4.2.6 | Se real < planejado, devolver ao estoque (ajuste positivo) | Service |

**Dependências:** F4.1
**Critérios de Aceite:**
- [ ] Operador preenche consumo real por insumo
- [ ] Variância calculada automaticamente
- [ ] Ajuste de estoque automático na confirmação
- [ ] Registro de audit trail

### F4.3 — Dashboard de Eficiência

| Item | Descrição | Tipo |
|------|-----------|------|
| F4.3.1 | Criar `app/controllers/SupplyDashboardController.php` | Controller |
| F4.3.2 | Implementar `getEfficiencyDashboard()` no ProducaoService | Service |
| F4.3.3 | Implementar `getTopWaste()` no model | Model |
| F4.3.4 | Criar view `supply_dashboard/efficiency.php` | View |
| F4.3.5 | Gráfico Chart.js: Previsto vs Real (barras agrupadas) | Frontend |
| F4.3.6 | Tabela: Top 10 desperdícios | View |
| F4.3.7 | KPIs: eficiência global, variação média, custo de perda, ordens no período | View |
| F4.3.8 | Adicionar rotas e menu | Config |

**Dependências:** F4.2 (dados de apontamento)
**Critérios de Aceite:**
- [ ] Gráfico renderiza dados reais
- [ ] Filtros por período e produto funcionando
- [ ] KPIs calculados corretamente
- [ ] Responsivo em mobile

### F4.4 — Testes Fase 4

| Item | Descrição | Tipo |
|------|-----------|------|
| F4.4.1 | `tests/Unit/ProducaoServiceTest.php` — consumo, apontamento | Teste |
| F4.4.2 | `tests/Unit/ProductionConsumptionTest.php` — eficiência, waste | Teste |
| F4.4.3 | `tests/Integration/ProductionFlowTest.php` — fluxo completo start → report | Teste |

---

## Fase 5 — Validação Final

**Objetivo:** Testes de integração, QA, documentação e deploy.

### F5.1 — Testes de Integração E2E

| Item | Descrição | Tipo |
|------|-----------|------|
| F5.1.1 | Fluxo completo: cadastrar insumo → vincular BOM → produzir → apontar → dashboard | Teste |
| F5.1.2 | Fluxo CMP: entrada → recálculo → alerta → aplicar preço | Teste |
| F5.1.3 | Fluxo substituição: ruptura → busca substituto → consumo parcial | Teste |
| F5.1.4 | Fluxo FEFO: entrada com lotes → saída consome FEFO correto | Teste |

### F5.2 — Testes de Segurança

| Item | Descrição | Tipo |
|------|-----------|------|
| F5.2.1 | Validar CSRF em todas as actions POST | Teste |
| F5.2.2 | Validar XSS em todas as views novas | Teste |
| F5.2.3 | Validar SQL injection nos novos models | Teste |
| F5.2.4 | Validar permissões (acesso sem login, sem permissão) | Teste |

### F5.3 — QA e Polish

| Item | Descrição | Tipo |
|------|-----------|------|
| F5.3.1 | Revisão de responsividade em mobile | QA |
| F5.3.2 | Revisão de performance (queries N+1, índices) | QA |
| F5.3.3 | Revisão de UX (feedback, loading states, erros claros) | QA |
| F5.3.4 | Dark mode compatibility (se aplicável) | QA |

### F5.4 — Documentação

| Item | Descrição | Tipo |
|------|-----------|------|
| F5.4.1 | Atualizar CHANGELOG.md | Docs |
| F5.4.2 | Atualizar MANUAL_DO_SISTEMA.md com módulo de insumos v2 | Docs |
| F5.4.3 | Criar guia do operador para apontamento de consumo | Docs |

---

## Diagrama de Dependências

```
F0.1 (MovementService)  ────────────────────────┐
F0.2 (Fornecedores)     ──┐                     │
F0.3 (BOM + Preço)      ──┼── F1.2 (InsumoSvc)  │
F0.4 (Stock completo)   ──┘        │             │
                                   │             │
F1.1 (Migration cols)   ──────────┘             │
                                                 │
F1.2 (InsumoService)    ──┐                     │
F0.4 (Stock)             ──┼── F2.1 (FEFO) ────┤
                           │                     │
F0.1 + F0.2              ──┼── F2.2 (CMP)       │
                           │       │             │
F2.2 + F1.2               ──── F2.3 (Alertas)   │
                                                 │
F0.2 (form tabs)         ──── F3.1 (Substitutos) │
F1.2 + F0.4              ──── F3.2 (Forecast)    │
F3.1 + F3.2              ──── F3.3 (Integração)  │
                                                  │
F1.2 + F2.1 + F3.1       ──── F4.1 (Consumo) ──┤
F4.1                      ──── F4.2 (Apontamento)│
F4.2                      ──── F4.3 (Dashboard)  │
                                                  │
F0–F4 (todos)             ──── F5 (Validação)   ─┘
```

---

## Estimativa de Complexidade

| Fase | Itens | Complexidade | Arquivos Novos | Arquivos Modificados |
|------|-------|-------------|----------------|---------------------|
| F0 | 18 | Alta | 1 (service) | 4 (models, controllers, views) |
| F1 | 13 | Alta | 2 (service, testes) | 3 (model, views, SQL) |
| F2 | 16 | Alta | 3 (service, views, testes) | 3 (service, model, SQL) |
| F3 | 14 | Média-Alta | 5 (model, service, views, testes, SQL) | 3 (controller, service, config) |
| F4 | 15 | Alta | 6 (model, service, controller, views, testes) | 2 (service, config) |
| F5 | 11 | Média | 4 (testes) | 2 (docs) |
| **Total** | **87** | | **~21** | **~17** |

---

## Checklist Global por Fase

Cada fase deve cumprir antes de ser considerada "Done":

- [ ] Código segue PSR-4, namespace `Akti\`
- [ ] Prepared statements em todas as queries
- [ ] Escape `e()` em todas as views
- [ ] `csrf_field()` em todos os forms
- [ ] `X-CSRF-TOKEN` em todos os AJAX
- [ ] SweetAlert2 para confirmações (nunca `alert()`/`confirm()`)
- [ ] Responsivo (testado em mobile)
- [ ] Testes PHPUnit passando
- [ ] Migration SQL gerada via skill `sql-migration`
- [ ] Git commit com tipo adequado (`feat`, `fix`, `migration`, etc.)

---

*Anterior: [05 — Regras de Negócio](05-regras-negocio.md)*
