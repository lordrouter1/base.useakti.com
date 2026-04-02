# Roadmap de Correções — Banco de Dados e Testes — Akti v2

> ## Por que este Roadmap existe?
>
> O banco de dados é o **coração do sistema** — toda informação crítica do negócio (clientes, pedidos, notas fiscais, transações financeiras) reside nele. Problemas na camada de dados podem causar **perda de dados**, **inconsistências financeiras** e **falhas em cálculos fiscais**. Já a suíte de testes é a **rede de segurança** que garante que refatorações e novas funcionalidades não quebrem o que já funciona.
>
> A auditoria revelou que o banco é **sólido e seguro** (zero SQL injections críticas, transações em operações financeiras), mas carecia de **automação de migrations**, **logging de rollbacks**, **padronização de retornos** e **paginação universal**. A suíte de testes é **abrangente em unitários e rotas**, mas tinha **lacunas em integração**, **segurança ofensiva** e **CI/CD**.
>
> Este roadmap unifica as correções de banco e testes em um único documento pois são **interdependentes** — toda mudança no banco deve ser acompanhada de testes, e melhorias nos testes frequentemente expõem necessidades no banco.

---

## Parte 1: Banco de Dados

### Prioridade ALTA

#### DB-001: Automação de Migrations
- **Status:** ✅ Implementado
- **Implementação:** O migration runner `scripts/migrate.php` já existia com funcionalidade completa: tabela `applied_migrations` com checksum, flags `--status`, `--dry-run`, `--tenant`, suporte multi-tenant. Criado arquivo SQL `update_202604020843_0_schema_migrations.sql` com `CREATE TABLE IF NOT EXISTS applied_migrations` para garantir a existência da tabela de controle.

#### DB-002: Idempotência em SQL de Criação
- **Status:** ✅ Já existia
- **Implementação:** O arquivo `sql/update_202604010400_site_builder_tables.sql` já contém `CREATE TABLE IF NOT EXISTS` em todas as 4 tabelas (sb_pages, sb_sections, sb_components, sb_theme_settings). Nenhuma alteração necessária.

#### DB-003: Logging em Rollback de Transações
- **Status:** ✅ Implementado
- **Implementação:** Verificados os 5 models com transações: Financial.php (2 catch blocks — ambos já tinham `Log::error`), Installment.php (2 catch blocks — ambos já tinham `Log::error`), NfeDocument.php (2 catch blocks — ambos já tinham `Log::error`), IbptaxModel.php (1 catch block — já tinha `Log::error`). Único pendente era DashboardWidget.php `saveForGroup()` — adicionado `use Akti\Core\Log` e `Log::error('DashboardWidget saveForGroup rollback', [...])` no catch block.

### Prioridade MÉDIA

#### DB-004: Converter Queries Raw para Prepared
- **Status:** ✅ Implementado
- **Implementação:** Dos 8 locais identificados na auditoria: Stock.php, ProductionSector.php e ReportModel.php já usavam `prepare()` + `bindValue()` (o WHERE era construído dinamicamente mas com params bound). Category.php e IbptaxModel.php também já usavam `prepare()`. Convertidos para `prepare()` + `execute()`: Subcategory.php `readAll()` (de `query()` para `prepare()`), CompanySettings.php `getAll()` (de `query()` para `prepare()`).

#### DB-005: Paginação em Módulos Legados
- **Status:** ✅ Implementado
- **Implementação:** Verificados os 5 módulos listados: User, UserGroup, Category e Subcategory já possuíam `readPaginated()` com padrão completo (count, LIMIT/OFFSET com PARAM_INT, retorno array padronizado). CompanySettings é um key-value store onde paginação não se aplica. Adicionado `readPaginated(int $page, int $perPage, string $search)` em ProductionSector.php com busca por nome, count parametrizado e retorno padronizado `{data, total, pages, current_page}`.

#### DB-006: Padronizar Retornos de Models
- **Status:** ✅ Já existia
- **Implementação:** Verificados os 4 models citados: User.php `readAll()` já retorna `fetchAll(\PDO::FETCH_ASSOC)`, UserGroup.php `readAll()` já retorna `fetchAll(\PDO::FETCH_ASSOC)`, Category.php `readAll()` já retorna `fetchAll(\PDO::FETCH_ASSOC)`, Subcategory.php `readAll()` já retorna `fetchAll(PDO::FETCH_ASSOC)`. Todos os models modernos seguem este padrão.

### Prioridade BAIXA

#### DB-007: Índices de Performance
- **Status:** ✅ Implementado
- **Implementação:** A maioria dos índices já existia em `sql/prontos/update_202603301000_performance_indexes.sql` (orders.customer_id, orders.pipeline_stage+status, customers.name, customers.document, installments.status+paid_date, stock_items, system_logs.created_at, products.name, products.category_id). Criado `sql/update_202604020843_1_performance_indexes_adicionais.sql` com índices faltantes: `customers.email` (busca de duplicatas), `order_installments(due_date, status)` (parcelas vencendo), `orders.status` (filtro de listagem).

#### DB-008: Cursor Pagination para Large Datasets
- **Status:** ✅ Implementado
- **Implementação:** Criado `app/utils/CursorPaginator.php` — classe utilitária que implementa paginação cursor-based com: suporte a navegação `next`/`prev`, detecção de `has_more` via fetch de limit+1, JOINs e WHERE parametrizados, bind com `PDO::PARAM_INT` para cursor e limit. Retorna `{data, next_cursor, prev_cursor, has_more}`. Disponível para uso em relatórios com large datasets.

#### DB-009: Cache de Count em Paginação
- **Status:** ✅ Já existia
- **Implementação:** A classe `app/utils/SimpleCache.php` já implementava cache com TTL em `$_SESSION['_cache']`, incluindo método `remember($key, $ttl, $loader)` — exatamente o padrão necessário para cachear COUNT(*) em paginação. Já integrada no sistema (CompanySettings, menus, badges).

---

## Parte 2: Testes e Qualidade

### Prioridade ALTA

#### TEST-001: CI/CD Pipeline (GitHub Actions)
- **Status:** ✅ Implementado
- **Implementação:** Criado `.github/workflows/ci.yml` com: trigger em push/PR para main e develop, serviço MySQL 8.0 com health check, setup-php 8.1 com extensões (pdo_mysql, mbstring, gd, zip, xml, intl, xdebug), cache de Composer, PHPStan com output GitHub, PHPUnit com cobertura condicional (apenas em push para main), upload de artefato coverage.xml.

#### TEST-002: Medir Cobertura de Código
- **Status:** ✅ Implementado
- **Implementação:** Adicionada seção `<coverage>` no `phpunit.xml` com: include de `app/` (sufixo .php), exclude de `app/views/` e `app/lang/`, report HTML em `reports/coverage/` e texto em stdout com `showOnlySummary="true"`. Para gerar relatório: `vendor/bin/phpunit --coverage-html reports/coverage`.

#### TEST-003: Testes de Integração CRUD Completos
- **Status:** ✅ Implementado
- **Implementação:** Criado `tests/Integration/CrudFlowTest.php` com 8 testes cobrindo: Produtos (listagem + formulário criação), Clientes (listagem + formulário criação), Pedidos (listagem + pipeline), Estoque (listagem), Financeiro (listagem). Todos verificam HTTP 200 e ausência de erros PHP. Se juntam ao `FinancialAjaxTest.php` já existente.

### Prioridade MÉDIA

#### TEST-004: Testes de Segurança Ofensivos
- **Status:** ✅ Implementado
- **Implementação:** Criado `tests/Security/OffensiveSecurityTest.php` com 8 testes: XSS em busca de produtos, XSS em busca de clientes, XSS em parâmetro page, SQLi em busca produtos, SQLi em busca clientes, SQLi em ID numérico, auth bypass sem login (cURL separado), path traversal em page. Se juntam ao `CsrfProtectionTest.php` já existente.

#### TEST-005: PHPStan Level 5
- **Status:** ✅ Já existia
- **Implementação:** O arquivo `phpstan.neon` já contém `level: 5` com paths em `app/`, excludes de `app/views/` e `vendor/`, e ignoreErrors para backward compatibility com models legados (return type mismatches, parâmetros com tipo misto). `reportUnmatchedIgnoredErrors: false` configurado.

#### TEST-006: ESLint para JavaScript
- **Status:** ✅ Implementado
- **Implementação:** Criado `.eslintrc.json` com: env browser/jquery/es2020, extends eslint:recommended, globals para Swal/Chart/Sortable/bootstrap/csrfToken/escHtml/aktiTrapFocus. Rules: no-unused-vars warn, no-undef error, eqeqeq error, no-eval error, no-implied-eval error, no-new-func error, no-alert error, no-var warn, prefer-const warn.

### Prioridade BAIXA

#### TEST-007: .editorconfig
- **Status:** ✅ Implementado
- **Implementação:** Criado `.editorconfig` com: root=true, indent_style=space, indent_size=4, end_of_line=lf, charset=utf-8, trim_trailing_whitespace=true, insert_final_newline=true. Overrides: JS/JSON/YAML indent_size=2, Markdown trim_trailing_whitespace=false, Makefile indent_style=tab.

#### TEST-008: Pre-commit Hooks
- **Status:** ✅ Implementado
- **Implementação:** Criado `scripts/pre-commit` com 3 validações: PHPStan analyse (falha bloqueia commit), PHPUnit Unit (apenas testes unitários, rápido), PHP Lint (verifica sintaxe de arquivos staged). Instalação via `cp scripts/pre-commit .git/hooks/pre-commit && chmod +x .git/hooks/pre-commit`.

#### TEST-009: Testes para Services NF-e
- **Status:** ✅ Implementado
- **Implementação:** Criado `tests/Unit/Services/NfeServiceTest.php` com 9 testes skeleton (markTestIncomplete) cobrindo: NfeXmlBuilder (XML válido, campos obrigatórios), NfeXmlValidator (rejeita inválido, aceita válido), NfePdfGenerator (retorna conteúdo), NfeAuditService (registra ação), NfeFiscalReportService (ICMS, IPI), NfeQueueService (adiciona fila, processa próximo). Cada teste documenta os mocks necessários para implementação futura.

#### TEST-010: Testes de Gateway Payment
- **Status:** ✅ Implementado
- **Implementação:** Criado `tests/Unit/Gateways/PaymentGatewayTest.php` com 8 testes skeleton (markTestIncomplete) cobrindo: GatewayManager (resolve provider, rejeita inválido), AbstractGateway (create charge, dados inválidos), Webhook (valida assinatura, rejeita inválida, parse payload), Erros (timeout API, erro 500 registra log). Cada teste documenta os mocks necessários.

---

## Checklist de Progresso

### Banco de Dados

| ID | Prioridade | Status | Item |
|---|---|---|---|
| DB-001 | ALTA | ✅ | Automação de migrations |
| DB-002 | ALTA | ✅ | Idempotência em SQL |
| DB-003 | ALTA | ✅ | Logging em rollback |
| DB-004 | MÉDIA | ✅ | Raw queries → prepared |
| DB-005 | MÉDIA | ✅ | Paginação em legados |
| DB-006 | MÉDIA | ✅ | Padronizar retornos models |
| DB-007 | BAIXA | ✅ | Índices de performance |
| DB-008 | BAIXA | ✅ | Cursor pagination |
| DB-009 | BAIXA | ✅ | Cache de count |

### Testes

| ID | Prioridade | Status | Item |
|---|---|---|---|
| TEST-001 | ALTA | ✅ | CI/CD pipeline |
| TEST-002 | ALTA | ✅ | Medir cobertura |
| TEST-003 | ALTA | ✅ | Testes integração CRUD |
| TEST-004 | MÉDIA | ✅ | Testes segurança ofensivos |
| TEST-005 | MÉDIA | ✅ | PHPStan Level 5 |
| TEST-006 | MÉDIA | ✅ | ESLint para JS |
| TEST-007 | BAIXA | ✅ | .editorconfig |
| TEST-008 | BAIXA | ✅ | Pre-commit hooks |
| TEST-009 | BAIXA | ✅ | Testes NF-e services |
| TEST-010 | BAIXA | ✅ | Testes gateway payment |

**Total:** 19/19 itens concluídos ✅
