# Roadmap de Correções — Banco de Dados e Testes — Akti v2

> ## Por que este Roadmap existe?
>
> O banco de dados é o **coração do sistema** — toda informação crítica do negócio (clientes, pedidos, notas fiscais, transações financeiras) reside nele. Problemas na camada de dados podem causar **perda de dados**, **inconsistências financeiras** e **falhas em cálculos fiscais**. Já a suíte de testes é a **rede de segurança** que garante que refatorações e novas funcionalidades não quebrem o que já funciona.
>
> A auditoria revelou que o banco é **sólido e seguro** (zero SQL injections críticas, transações em operações financeiras), mas carece de **automação de migrations**, **logging de rollbacks**, **padronização de retornos** e **paginação universal**. A suíte de testes é **abrangente em unitários e rotas**, mas tem **lacunas em integração**, **segurança ofensiva** e **CI/CD**.
>
> Este roadmap unifica as correções de banco e testes em um único documento pois são **interdependentes** — toda mudança no banco deve ser acompanhada de testes, e melhorias nos testes frequentemente expõem necessidades no banco.

---

## Parte 1: Banco de Dados

### Prioridade ALTA

#### DB-001: Automação de Migrations
- **Problema:** Migrations executadas manualmente — risco de esquecer em deploy
- **Correção:** Implementar tabela `schema_migrations` + runner:
  ```sql
  CREATE TABLE IF NOT EXISTS schema_migrations (
      id INT AUTO_INCREMENT PRIMARY KEY,
      migration VARCHAR(255) NOT NULL UNIQUE,
      executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );
  ```
  ```php
  // scripts/migrate.php
  $files = glob(__DIR__ . '/../sql/update_*.sql');
  sort($files);
  foreach ($files as $file) {
      $name = basename($file);
      $exists = $db->prepare("SELECT 1 FROM schema_migrations WHERE migration = ?");
      $exists->execute([$name]);
      if (!$exists->fetch()) {
          $sql = file_get_contents($file);
          $db->exec($sql);
          $db->prepare("INSERT INTO schema_migrations (migration) VALUES (?)")->execute([$name]);
          echo "Executed: $name\n";
      }
  }
  ```
- **Status:** ⬜ Pendente

#### DB-002: Idempotência em SQL de Criação
- **Arquivo:** `sql/update_202604010400_site_builder_tables.sql`
- **Problema:** `CREATE TABLE` sem `IF NOT EXISTS` — falha se executado duas vezes
- **Correção:** Adicionar `IF NOT EXISTS` em todos os CREATE TABLE
- **Status:** ⬜ Pendente

#### DB-003: Logging em Rollback de Transações
- **Arquivos:** Financial.php, Installment.php, NfeDocument.php, IbptaxModel.php, DashboardWidget.php
- **Problema:** `catch` faz rollBack sem registrar o erro
- **Correção:**
  ```php
  catch (\PDOException $e) {
      $this->conn->rollBack();
      \Akti\Core\Log::error('Transaction rollback', [
          'method' => __METHOD__,
          'error'  => $e->getMessage(),
          'code'   => $e->getCode()
      ]);
      throw $e;
  }
  ```
- **Status:** ⬜ Pendente

### Prioridade MÉDIA

#### DB-004: Converter Queries Raw para Prepared
- **8 locais identificados:**
  1. `Stock.php:58` — `"$where"` concatenação
  2. `Category.php:141` — `query()` raw
  3. `Subcategory.php:51` — `query()` raw
  4. `CompanySettings.php:33` — `query()` raw
  5. `ProductionSector.php:26` — `query("{$where}")`
  6. `ReportModel.php:372` — `query()` raw
  7. `ReportModel.php:473` — `query()` raw
  8. `IbptaxModel.php:246` — `query("SELECT COUNT")`
- **Correção:** Converter para `prepare()` + `bindValue()` em cada local
- **Status:** ⬜ Pendente

#### DB-005: Paginação em Módulos Legados
- **Módulos sem paginação:** Users, Categories, Subcategories, CompanySettings, ProductionSector
- **Correção:** Implementar `readPaginated(int $page, int $perPage, string $search = '')` seguindo pattern:
  ```php
  return [
      'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
      'total'        => $total,
      'pages'        => ceil($total / $perPage),
      'current_page' => $page,
  ];
  ```
- **Status:** ⬜ Pendente

#### DB-006: Padronizar Retornos de Models
- **Models que retornam PDOStatement:** User, UserGroup, Category, Subcategory
- **Correção:** Converter para `return $stmt->fetchAll(PDO::FETCH_ASSOC)`
- **Status:** ⬜ Pendente

### Prioridade BAIXA

#### DB-007: Índices de Performance
- **Potencial:** Adicionar índices em colunas de busca frequente:
  - `customers.email` — busca duplicata
  - `orders.customer_id` — JOIN frequente
  - `orders.status` — filtro de listagem
  - `installments.due_date, status` — parcelas vencendo
  - `system_logs.created_at` — consulta por período
- **Correção:** Arquivo SQL com `CREATE INDEX IF NOT EXISTS`
- **Status:** ⬜ Pendente

#### DB-008: Cursor Pagination para Large Datasets
- **Problema:** Offset pagination fica lenta com milhões de registros
- **Correção:** Implementar cursor-based pagination para relatórios:
  ```sql
  SELECT * FROM orders WHERE id > :cursor ORDER BY id LIMIT :limit
  ```
- **Status:** ⬜ Pendente

#### DB-009: Cache de Count em Paginação
- **Problema:** `SELECT COUNT(*)` executado em cada page view
- **Correção:** Cache de count por 60s usando SimpleCache:
  ```php
  $cacheKey = "count_{$table}_{$filterHash}";
  $total = $cache->get($cacheKey) ?? $this->countTotal($filters);
  $cache->set($cacheKey, $total, 60);
  ```
- **Status:** ⬜ Pendente

---

## Parte 2: Testes e Qualidade

### Prioridade ALTA

#### TEST-001: CI/CD Pipeline (GitHub Actions)
- **Problema:** Sem pipeline automatizado — testes executados manualmente
- **Correção:**
  ```yaml
  name: CI
  on: [push, pull_request]
  jobs:
    test:
      runs-on: ubuntu-latest
      services:
        mysql:
          image: mysql:8
          env:
            MYSQL_ROOT_PASSWORD: test
            MYSQL_DATABASE: akti_test
          ports: ['3306:3306']
      steps:
        - uses: actions/checkout@v4
        - uses: shivammathur/setup-php@v2
          with:
            php-version: '8.1'
            extensions: pdo_mysql, mbstring, gd, zip, xml
            coverage: xdebug
        - run: composer install
        - run: vendor/bin/phpstan analyse
        - run: vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml
  ```
- **Status:** ⬜ Pendente

#### TEST-002: Medir Cobertura de Código
- **Problema:** Cobertura não medida — não se sabe quanto do código é testado
- **Correção:** Adicionar `--coverage-html reports/coverage` ao phpunit
- **Meta:** ≥70% cobertura global
- **Status:** ⬜ Pendente

#### TEST-003: Testes de Integração CRUD Completos
- **Lacunas identificadas:**
  - Produtos: create → edit → delete
  - Pedidos: create → pipeline → conclusão
  - Clientes: create → edit → delete
  - Estoque: entrada → transferência → ajuste
- **Correção:** Criar testes que exercitam o fluxo completo via HTTP
- **Status:** ⬜ Pendente

### Prioridade MÉDIA

#### TEST-004: Testes de Segurança Ofensivos
- **Lacunas:** XSS, SQLi, file upload bypass, auth bypass
- **Correção:**
  ```php
  // tests/Security/XssProtectionTest.php
  public function testXssInProductName()
  {
      $payload = '<script>alert(1)</script>';
      $response = $this->httpPost('?page=products&action=store', [
          'name' => $payload,
          'csrf_token' => $this->getCsrfToken(),
      ]);
      $this->assertStringNotContainsString($payload, $response['body']);
  }
  ```
- **Status:** ⬜ Pendente

#### TEST-005: PHPStan Level 5
- **Arquivo:** `phpstan.neon`
- **Plano incremental:**
  1. Level 3 → 4: Verificação de tipos em chamadas
  2. Level 4 → 5: Return types obrigatórios
  3. Corrigir erros em cada nível antes de subir
- **Status:** ⬜ Pendente

#### TEST-006: ESLint para JavaScript
- **Problema:** Sem linting de JS — inconsistências de estilo e potenciais bugs
- **Correção:**
  ```json
  // .eslintrc.json
  {
    "env": { "browser": true, "jquery": true, "es2020": true },
    "extends": "eslint:recommended",
    "rules": {
      "no-unused-vars": "warn",
      "no-undef": "error",
      "eqeqeq": "error"
    }
  }
  ```
- **Status:** ⬜ Pendente

### Prioridade BAIXA

#### TEST-007: .editorconfig
- **Problema:** Sem padronização de editor (tabs vs spaces, encoding, EOL)
- **Correção:**
  ```ini
  # .editorconfig
  root = true
  [*]
  indent_style = space
  indent_size = 4
  end_of_line = lf
  charset = utf-8
  trim_trailing_whitespace = true
  insert_final_newline = true
  [*.{js,json}]
  indent_size = 2
  ```
- **Status:** ⬜ Pendente

#### TEST-008: Pre-commit Hooks
- **Problema:** Sem verificação automática antes de commit
- **Correção:** Implementar via `husky` (JS) ou `pre-commit` (PHP):
  - `vendor/bin/phpstan analyse --no-progress`
  - `vendor/bin/phpunit tests/Unit --no-coverage`
- **Status:** ⬜ Pendente

#### TEST-009: Testes para Services NF-e
- **Problema:** 24 services NF-e sem testes unitários
- **Correção:** Mock de SEFAZ + testes de XML building, tax calculation, PDF generation
- **Status:** ⬜ Pendente

#### TEST-010: Testes de Gateway Payment
- **Problema:** Gateways sem testes automatizados
- **Correção:** Mock de API + testes de signature validation, payload parsing, charge creation
- **Status:** ⬜ Pendente

---

## Checklist de Progresso

### Banco de Dados

| ID | Prioridade | Status | Item |
|---|---|---|---|
| DB-001 | ALTA | ⬜ | Automação de migrations |
| DB-002 | ALTA | ⬜ | Idempotência em SQL |
| DB-003 | ALTA | ⬜ | Logging em rollback |
| DB-004 | MÉDIA | ⬜ | Raw queries → prepared |
| DB-005 | MÉDIA | ⬜ | Paginação em legados |
| DB-006 | MÉDIA | ⬜ | Padronizar retornos models |
| DB-007 | BAIXA | ⬜ | Índices de performance |
| DB-008 | BAIXA | ⬜ | Cursor pagination |
| DB-009 | BAIXA | ⬜ | Cache de count |

### Testes

| ID | Prioridade | Status | Item |
|---|---|---|---|
| TEST-001 | ALTA | ⬜ | CI/CD pipeline |
| TEST-002 | ALTA | ⬜ | Medir cobertura |
| TEST-003 | ALTA | ⬜ | Testes integração CRUD |
| TEST-004 | MÉDIA | ⬜ | Testes segurança ofensivos |
| TEST-005 | MÉDIA | ⬜ | PHPStan Level 5 |
| TEST-006 | MÉDIA | ⬜ | ESLint para JS |
| TEST-007 | BAIXA | ⬜ | .editorconfig |
| TEST-008 | BAIXA | ⬜ | Pre-commit hooks |
| TEST-009 | BAIXA | ⬜ | Testes NF-e services |
| TEST-010 | BAIXA | ⬜ | Testes gateway payment |

**Total:** 19 itens (6 altos, 6 médios, 7 baixos)
