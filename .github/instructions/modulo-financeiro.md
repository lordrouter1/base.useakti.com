# Módulo Financeiro

---

## Sumário
- [Visão Geral](#visão-geral)
- [Status da Refatoração](#status-da-refatoração)
- [Recursos Financeiros](#recursos-financeiros)
- [Arquitetura (pós-refatoração)](#arquitetura-pós-refatoração)
- [Fluxo de Pagamento](#fluxo-de-pagamento)
- [Tipos de Transação](#tipos-de-transação)
- [Categorias de Transação](#categorias-de-transação)
- [Soft-Delete de Transações](#soft-delete-de-transações)
- [Importação OFX — Controle de Duplicidade](#importação-ofx--controle-de-duplicidade)
- [Eventos Financeiros](#eventos-financeiros)
- [Roteamento e Delegação de Actions](#roteamento-e-delegação-de-actions)
- [Arquivos do Módulo](#arquivos-do-módulo)
- [SQL Migrations](#sql-migrations)
- [Referências](#referências)

---

## Visão Geral
O módulo financeiro controla pagamentos, parcelas, estornos, importação de extratos e integração com pedidos.
Após a refatoração (Fases 1 e 2 concluídas), a arquitetura segue o padrão **Service Layer** com
controllers especializados, eventos auditados e suporte a categorias dinâmicas, soft-delete e
controle de duplicidade OFX.

---

## Status da Refatoração

| Fase | Status | Resumo |
|------|--------|--------|
| **Fase 1** — Segurança | ✅ Concluída | CSRF, permissões, open redirect, otimização de queries, índices |
| **Fase 2** — Estrutural | ✅ Concluída | Split de Model/Controller, Service Layer, eventos com listeners, auditoria, extração de JS, categorias dinâmicas, soft-delete, duplicidade OFX |
| **Fase 3** — Views & Gateways | ✅ Concluída | Divisão de `payments.php` em partials (sidebar, payments, transactions, import, new, DRE, cashflow, recurring), integração gateways com parcelas |
| **Fase 4** — Novas Features | ✅ Concluída | DRE simplificado, fluxo de caixa projetado, transações recorrentes (CRUD + processamento mensal + projeção), exportação CSV, testes unitários |

> Para detalhes completos, consulte: `docs/RELATORIO_REFATORACAO_FINANCEIRO.md`

---

## Recursos Financeiros
- Controle de parcelas e pagamentos.
- Entradas e saídas de caixa.
- Estornos com auditoria.
- Importação de extratos bancários (OFX, CSV, Excel) com controle de duplicidade por FITID.
- Categorias dinâmicas de transação (via tabela `financial_categories`).
- Soft-delete em transações financeiras.
- Auditoria completa em `financial_audit_log`.
- Merge e split de parcelas.

---

## Arquitetura (pós-refatoração)

### Controllers
| Controller | Linhas | Responsabilidade | Actions principais |
|-----------|--------|-----------------|-------------------|
| `FinancialController.php` | ~270 | Dashboard financeiro, página de pagamentos unificada, resumo JSON, DRE, fluxo de caixa, exportações CSV | `index`, `payments`, `getSummaryJson`, `getDre`, `getCashflow`, `exportTransactionsCsv`, `exportDreCsv`, `exportCashflowCsv` |
| `InstallmentController.php` | ~470 | CRUD de parcelas, merge, split, upload de comprovantes | `installments`, `generate`, `pay`, `confirm`, `cancel`, `merge`, `split`, `uploadAttachment`, `removeAttachment`, `getPaginated`, `getJson` |
| `TransactionController.php` | ~250 | CRUD de transações financeiras (entradas/saídas) | `index`, `add`, `delete`, `get`, `update`, `getPaginated` |
| `FinancialImportController.php` | ~280 | Parsing e importação de OFX/CSV/Excel | `parseFile`, `importCsv`, `importOfxSelected`, `importOfx` |
| `RecurringTransactionController.php` | ~230 | CRUD + processamento de transações recorrentes | `list`, `store`, `update`, `delete`, `toggle`, `process`, `get` |

> **Nota:** O `FinancialController` original (949 linhas, 30+ actions) foi reduzido para ~150 linhas (84% de redução).

### Services
| Service | Responsabilidade |
|---------|-----------------|
| `InstallmentService.php` | Orquestra parcelas (gerar, pagar, confirmar, cancelar, merge, split) |
| `TransactionService.php` | Orquestra transações (CRUD, estornos, registro de pagamentos) |
| `FinancialImportService.php` | Parsing e importação de OFX/CSV/Excel com controle de duplicidade |
| `FinancialReportService.php` | Dashboard, resumos financeiros, DRE, fluxo de caixa, exportações CSV |
| `FinancialAuditService.php` | Auditoria em `financial_audit_log` |

### Models
| Model | Responsabilidade |
|-------|-----------------|
| `Financial.php` | Transações, resumos, gráficos, OFX parsing, categorias dinâmicas, soft-delete |
| `Installment.php` | Parcelas (CRUD, merge, split, renumeração) |
| `RecurringTransaction.php` | Transações recorrentes (CRUD, processamento mensal, projeção de meses, resumo mensal) |

### Views
| View | Descrição |
|------|-----------|
| `financial/index.php` | Dashboard financeiro (cards + gráfico) |
| `financial/payments_new.php` | Página unificada com sidebar (parcelas, transações, importação, nova transação, DRE, fluxo de caixa, recorrências) |
| `financial/payments.php` | Página unificada legada (anterior à divisão em partials) |
| `financial/installments.php` | Parcelas de um pedido específico |
| `financial/partials/_sidebar.php` | Sidebar com navegação entre seções |
| `financial/partials/_section_payments.php` | Seção de pagamentos (parcelas) |
| `financial/partials/_section_transactions.php` | Seção de transações (entradas/saídas) |
| `financial/partials/_section_import.php` | Seção de importação OFX/CSV/Excel |
| `financial/partials/_section_new_transaction.php` | Formulário de nova transação |
| `financial/partials/_section_dre.php` | DRE simplificado |
| `financial/partials/_section_cashflow.php` | Fluxo de caixa projetado |
| `financial/partials/_section_recurring.php` | Transações recorrentes (CRUD com modal) |
| `financial/partials/_modals.php` | Modals compartilhados (detalhe de parcela, gateway charge, etc.) |

### Assets
| Arquivo | Descrição |
|---------|-----------|
| `assets/js/financial-payments.js` | JavaScript extraído e modularizado (~1752 linhas, IIFE com strict mode). Suporta navegação SPA entre seções, AJAX para todas as operações (parcelas, transações, DRE, cashflow, recorrências), Chart.js para gráficos, SweetAlert2 para notificações |

> A view `payments_new.php` injeta dados PHP via `window.AktiFinancial = { statusMap, methodLabels, allCats, bankConfig, initialSection, activeGateways }` antes de carregar o script externo. Cache-busting é feito via `filemtime()`.

---

## Fluxo de Pagamento
- Pagamentos vinculados ao pedido.
- Parcelas pagas bloqueiam alterações no pedido.
- Estornos liberam alterações.

---

## Tipos de Transação
O campo `type` da tabela `financial_transactions` aceita três valores:

| Tipo | Descrição | Contabiliza no saldo? | Badge na listagem |
|------|-----------|----------------------|-------------------|
| `entrada` | Dinheiro que entra no caixa | ✅ Sim (soma em Entradas) | 🟢 Verde + seta ↓ |
| `saida` | Dinheiro que sai do caixa | ✅ Sim (soma em Saídas) | 🔴 Vermelho + seta ↑ |
| `registro` | Lançamento informativo (estornos, importações OFX sem contabilizar) | ❌ Não contabiliza | ⚫ Cinza + risco (—) |

---

## Categorias de Transação

As categorias são gerenciadas dinamicamente pela tabela `financial_categories`.
Se a tabela não existir, o sistema usa fallback para array estático (retrocompatibilidade).

**Entradas:**
- `pagamento_pedido` — Pagamento de Pedido
- `servico_avulso` — Serviço Avulso
- `outra_entrada` — Outra Entrada

**Saídas:**
- `material` — Compra de Material
- `salario` — Salários
- `aluguel` — Aluguel
- `energia` — Energia/Água
- `internet` — Internet/Telefone
- `manutencao` — Manutenção
- `imposto` — Impostos/Taxas
- `outra_saida` — Outra Saída

**Internas (sistema):**
- `estorno_pagamento` — Estorno de Pagamento
- `registro_ofx` — Registro OFX

> **Métodos relevantes:**
> - `Financial::getCategories()` — retorna categorias ativas agrupadas por tipo (com cache estático por request)
> - `Financial::getInternalCategories()` — retorna categorias internas
> - `Financial::getAllCategoriesDetailed()` — retorna todas com metadados (icon, color, is_system) para admin
> - `Financial::clearCategoriesCache()` — limpa cache após CRUD de categorias

---

## Soft-Delete de Transações

A exclusão de transações financeiras usa **soft-delete** (coluna `deleted_at`):

- `Financial::deleteTransaction($id)` — marca `deleted_at = NOW()` se a coluna existir, senão faz hard delete (retrocompatível).
- `Financial::restoreTransaction($id)` — limpa `deleted_at` para restaurar.
- Todas as queries de leitura adicionam `AND deleted_at IS NULL` automaticamente.
- Método `hasSoftDeleteColumn()` verifica a existência da coluna com cache estático.

> Queries afetadas: `getTransactionById()`, `getTransactions()`, `getTransactionsPaginated()`, `getSummary()`, `getChartData()`

---

## Importação OFX — Controle de Duplicidade

A importação OFX verifica duplicidade via tabela `ofx_imported_transactions`:

1. Antes de importar, consulta `ofx_imported_transactions` por `(fitid, bank_account)`.
2. Se já existe, a transação é pulada (`skipped`).
3. Após importar, registra o FITID + dados na tabela de controle.

> **Métodos em `FinancialImportService`:**
> - `hasDuplicityTable()` — verifica existência da tabela (cache por request)
> - `isOfxDuplicate(fitid, account)` — consulta duplicidade
> - `registerOfxImport(fitid, account, date, amount, desc, txId)` — registra importação

---

## Transações Recorrentes

Gerencia receitas e despesas fixas mensais (aluguel, salários, assinaturas, etc.).
Cada recorrência pode gerar automaticamente transações no `financial_transactions` quando processada.

### Tabela: `financial_recurring_transactions`
| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT PK AI | Identificador |
| `type` | ENUM('entrada','saida') | Tipo da recorrência |
| `category` | VARCHAR(50) | Categoria da transação |
| `description` | VARCHAR(255) | Descrição da recorrência |
| `amount` | DECIMAL(12,2) | Valor mensal |
| `due_day` | TINYINT(2) | Dia do vencimento (1-28) |
| `payment_method` | VARCHAR(50) NULL | Forma de pagamento |
| `notes` | TEXT NULL | Observação |
| `start_month` | DATE | Mês de início (formato YYYY-MM-01) |
| `end_month` | DATE NULL | Mês de término (NULL = sem fim) |
| `is_active` | TINYINT(1) DEFAULT 1 | Se a recorrência está ativa |
| `last_generated_month` | DATE NULL | Último mês em que foi gerada (evita duplicidade) |
| `user_id` | INT NULL | Usuário que criou |
| `created_at` | TIMESTAMP | Data de criação |

### Endpoints AJAX (via `RecurringTransactionController`)
| Action | Método HTTP | Descrição |
|--------|-----------|-----------|
| `recurringList` | GET | Lista todas as recorrências com resumo mensal |
| `recurringStore` | POST (JSON) | Cria nova recorrência |
| `recurringUpdate` | POST (JSON) | Atualiza recorrência existente |
| `recurringDelete` | POST | Exclui recorrência |
| `recurringToggle` | POST (JSON) | Ativa/desativa recorrência |
| `recurringProcess` | POST | Processa recorrências pendentes do mês atual |
| `recurringGet` | GET | Busca recorrência por ID |

### Processamento Mensal
- `processMonth()` verifica cada recorrência ativa, checa se já foi gerada no mês corrente (via `last_generated_month`), e gera a transação financeira correspondente com `is_confirmed = 0` (pendente de confirmação).
- Transações geradas recebem o prefixo `[Recorrência]` na descrição e referência `recurring_id` para rastreamento.
- O processamento pode ser disparado manualmente pelo botão na interface.

### Projeção de Meses
- `projectMonths(N)` projeta entradas e saídas recorrentes para os próximos N meses, considerando `start_month`, `end_month` e status ativo.
- Utilizado pelo `FinancialReportService::getCashflowProjection()` para compor o fluxo de caixa projetado.

---

## DRE — Demonstrativo de Resultado do Exercício

Relatório simplificado de receitas e despesas agrupadas por categoria para um período.

### Endpoint AJAX
- **Action:** `getDre` (GET) — Retorna JSON com receitas, despesas, parcelas pagas, totais e resultado líquido.
- **Parâmetros:** `from` (YYYY-MM) e `to` (YYYY-MM).
- **Export:** `exportDreCsv` (GET) — Download direto em CSV.

### Estrutura do DRE
| Campo | Descrição |
|-------|-----------|
| `receitas[]` | Array de `{category, category_name, total}` agrupado |
| `despesas[]` | Array de `{category, category_name, total}` agrupado |
| `parcelas_pagas` | Total de parcelas pagas no período (ordem de pedidos) |
| `total_receitas` | Soma de receitas + parcelas pagas |
| `total_despesas` | Soma de despesas |
| `resultado` | `total_receitas - total_despesas` |
| `periodo` | `{de, ate}` |

---

## Fluxo de Caixa Projetado

Projeção de entradas e saídas para os próximos N meses (3, 6 ou 12).

### Endpoint AJAX
- **Action:** `getCashflow` (GET) — Retorna JSON com projeção mês a mês.
- **Parâmetros:** `months` (1-24, default 6), `recurring` (0 ou 1, default 1).
- **Export:** `exportCashflowCsv` (GET) — Download direto em CSV.

### Fontes de Dados
1. **Parcelas pendentes** — Agrupadas por mês de vencimento como entradas previstas.
2. **Transações realizadas** — Entradas e saídas confirmadas do mês atual.
3. **Recorrências ativas** — Projeção de receitas e despesas fixas (opcional).

### Estrutura por Mês
| Campo | Descrição |
|-------|-----------|
| `month` | YYYY-MM |
| `label` | Ex: "Mar/2026" |
| `entradas_parcelas` | Parcelas pendentes do mês |
| `entradas_recorrencias` | Receitas recorrentes projetadas |
| `saidas_recorrencias` | Despesas recorrentes projetadas |
| `total_entradas` | Soma de todas as entradas |
| `total_saidas` | Soma de todas as saídas |
| `saldo_mes` | `total_entradas - total_saidas` |
| `saldo_acumulado` | Saldo progressivo desde o mês 1 |

### Visualização
- Gráfico Chart.js (barras para entradas/saídas + linha para saldo acumulado).
- Tabela detalhada mês a mês com formatação de cores.

---

## Eventos Financeiros

Todos os eventos abaixo possuem **listeners registrados** em `app/bootstrap/events.php`,
que gravam log em `storage/logs/financial.log` e auditoria em `financial_audit_log` via `FinancialAuditService`.

| Evento | Disparado por | Payload |
|--------|--------------|---------|
| `model.installment.generated` | `Installment::generate()` / `Financial::generateInstallments()` | order_id, total_amount, num_installments |
| `model.installment.paid` | `Installment::pay()` | installment_id, order_id, paid_amount, auto_confirmed |
| `model.installment.confirmed` | `Installment::confirm()` | installment_id, order_id, confirmed_by |
| `model.installment.cancelled` | `Installment::cancel()` | installment_id, order_id, cancelled_by |
| `model.installment.deleted_all` | `Installment::deleteByOrder()` / `Financial::deleteInstallmentsByOrder()` | order_id, count |
| `model.installment.merged` | `Installment::merge()` / `Financial::mergeInstallments()` | order_id, merged_ids, new_id, amount |
| `model.installment.split` | `Installment::split()` / `Financial::splitInstallment()` | order_id, original_id, parts, new_ids |
| `model.installment.due_date_updated` | `Installment::updateDueDate()` / `Financial::updateInstallmentDueDate()` | id, due_date |
| `model.order.financial_updated` | `Installment::updateOrderFinancialFields()` / `Financial::updateOrderFinancialFields()` | id, payment_method, installments |
| `model.financial_transaction.created` | `Financial::addTransaction()` | id, type, category, amount |
| `model.financial_transaction.updated` | `Financial::updateTransaction()` | id, type, category, amount |
| `model.financial_transaction.deleted` | `Financial::deleteTransaction()` | id |
| `model.recurring_transaction.created` | `RecurringTransaction::create()` | id, type, amount, description |
| `model.recurring_transaction.updated` | `RecurringTransaction::update()` | id, type, amount |
| `model.recurring_transaction.deleted` | `RecurringTransaction::delete()` | id |
| `model.recurring_transaction.processed` | `RecurringTransaction::processMonth()` | generated, skipped, errors |

---

## Roteamento e Delegação de Actions

O roteamento em `app/config/routes.php` suporta **delegação de actions para controllers especializados**:

```php
// Exemplo de delegação no routes.php (page=financial):
'installments'      => ['controller' => 'InstallmentController',      'method' => 'installments'],
'addTransaction'    => ['controller' => 'TransactionController',      'method' => 'add'],
'parseImportFile'   => ['controller' => 'FinancialImportController',  'method' => 'parseFile'],
'recurringList'     => ['controller' => 'RecurringTransactionController', 'method' => 'list'],
'recurringStore'    => ['controller' => 'RecurringTransactionController', 'method' => 'store'],
// ...demais actions delegadas
```

**Retrocompatibilidade mantida:** todas as URLs existentes (`?page=financial&action=X`) continuam funcionando.
O `FinancialController` atua como ponto de entrada principal e o Router delega automaticamente para o sub-controller correto conforme a action.

---

## Arquivos do Módulo

### Core
- `app/models/Financial.php`
- `app/models/Installment.php`
- `app/models/RecurringTransaction.php`
- `app/controllers/FinancialController.php`
- `app/controllers/InstallmentController.php`
- `app/controllers/TransactionController.php`
- `app/controllers/FinancialImportController.php`
- `app/controllers/RecurringTransactionController.php`
- `app/services/InstallmentService.php`
- `app/services/TransactionService.php`
- `app/services/FinancialImportService.php`
- `app/services/FinancialReportService.php`
- `app/services/FinancialAuditService.php`

### Views
- `app/views/financial/index.php`
- `app/views/financial/payments_new.php`
- `app/views/financial/payments.php` (legado)
- `app/views/financial/installments.php`
- `app/views/financial/partials/_sidebar.php`
- `app/views/financial/partials/_section_payments.php`
- `app/views/financial/partials/_section_transactions.php`
- `app/views/financial/partials/_section_import.php`
- `app/views/financial/partials/_section_new_transaction.php`
- `app/views/financial/partials/_section_dre.php`
- `app/views/financial/partials/_section_cashflow.php`
- `app/views/financial/partials/_section_recurring.php`
- `app/views/financial/partials/_modals.php`

### Assets
- `assets/js/financial-payments.js`

### Eventos
- `app/bootstrap/events.php` — Listeners financeiros (seção "Módulo Financeiro")

### Testes
- `tests/Unit/FinancialAjaxTest.php` — 8 testes: DRE, cashflow, recorrências, exportações CSV, verificação de rotas

### SQL Migrations
- `sql/prontos/update_20260306_financial_module.sql` — Criação inicial de `order_installments` e `financial_transactions`
- `sql/update_202603231200_financial_indexes.sql` — Índices de performance (due_date, status, pipeline)
- `sql/update_202603231300_financial_audit_log.sql` — Tabela de auditoria financeira
- `sql/update_202603231301_financial_categories.sql` — Categorias dinâmicas com seed (13 categorias padrão)
- `sql/update_202603231302_ofx_duplicity_control.sql` — Controle de duplicidade OFX (`ofx_imported_transactions`)
- `sql/update_202603231303_financial_soft_delete_improvements.sql` — Soft-delete + juros/multa em parcelas
- `sql/update_202603231400_recurring_transactions.sql` — Tabela `financial_recurring_transactions` + coluna `recurring_id` em `financial_transactions`

---

## Referências

- **Relatório completo da refatoração:** `docs/RELATORIO_REFATORACAO_FINANCEIRO.md`
- **Testes:** 164 testes, 1590 assertions — todos passando (inclui 8 testes específicos de AJAX financeiro + 2 testes de eventos RecurringTransaction)
- **Arquitetura geral do sistema:** `.github/instructions/architecture.md`
- **Sistema de eventos:** `.github/instructions/events.md`
- **Banco de dados e migrations:** `.github/instructions/database.md`
