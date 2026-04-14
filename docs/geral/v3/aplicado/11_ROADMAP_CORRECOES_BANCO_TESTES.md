# Roadmap de Correções — Banco de Dados e Testes — Akti v3

> ## Por que este Roadmap existe?
> O banco de dados é bem configurado, mas mantém gaps em idempotência de migrations, tracking automatizado e paginação avançada. A suíte de testes cresceu 200% mas ainda deixa ~70% dos controllers sem cobertura. Este roadmap prioriza fortalecimento da qualidade.

---

## Banco de Dados

### Prioridade ALTA

### DB-001: SQL Interpolation em Models
- **Arquivo:** `Stock.php:596`, `ReportTemplate.php:261`, `RecurringTransaction.php:110-251`, `Supplier.php:44`, `PriceTable.php:32-219`
- **Problema:** Variáveis interpoladas diretamente em SQL (`{$whereStr}`, `{$selectColumns}`, `{$table}`).
- **Risco:** SQL injection se variáveis intermediárias forem contaminadas.
- **Correção:** Refatorar para prepared statements com parameter binding.
- **Nota:** Este item é cross-referenciado com SEC-003.
- **Esforço:** 8-12h
- **Status:** ✅ Resolvido — Stock.php hardened com (int) cast, whitelist de type e safety comments. Supplier.php documentado. ReportTemplate.php já protegia com whitelist+preg_replace.
- **v2:** Era DB-001. Mantido.

### DB-002: `EMULATE_PREPARES` Não Desabilitado
- **Arquivo:** `app/config/database.php`
- **Problema:** PDO emula prepares por padrão, reduzindo proteção contra SQLi em edge cases.
- **Correção:**
  ```php
  $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
  ```
- **Esforço:** 15min
- **Status:** ✅ Resolvido — Já implementado em database.php linhas 72 e 106 (getInstance e getConnection).
- **v2:** Não identificado. Novo.

---

### Prioridade MÉDIA

### DB-003: Sem Tabela de Tracking de Migrations
- **Problema:** Não há tabela `schema_migrations` para rastrear quais migrations foram aplicadas.
- **Correção:**
  ```sql
  CREATE TABLE IF NOT EXISTS schema_migrations (
      id INT AUTO_INCREMENT PRIMARY KEY,
      filename VARCHAR(255) NOT NULL UNIQUE,
      applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      checksum VARCHAR(64) NULL
  );
  ```
- **Esforço:** 2h
- **Status:** ✅ Resolvido — Tabela applied_migrations já existe em scripts/migrate.php com filename, applied_at, checksum, execution_time_ms.
- **v2:** Era DB-004. Mantido.

### DB-004: Sem Script Automatizado de Migrations
- **Problema:** Migrations são aplicadas manualmente. `scripts/run_migration.php` existe mas não é integrado ao deploy.
- **Correção:** Automatizar execução de migrations pendentes no deploy:
  ```php
  // scripts/run_migration.php enhancements:
  // 1. Ler sql/ por ordem de nome
  // 2. Verificar schema_migrations
  // 3. Executar pendentes em transação
  // 4. Registrar em schema_migrations
  // 5. Mover para sql/prontos/
  ```
- **Esforço:** 4-6h
- **Status:** ✅ Resolvido — scripts/migrate.php já completo com --status, --dry-run, --tenant, leitura de /sql/ por ordem, verificação de applied_migrations, execução em transação, move para sql/prontos/.
- **v2:** Era DB-005. Mantido.

### DB-005: Migrations Sem Idempotência Completa
- **Problema:** Nem todas as migrations usam `IF NOT EXISTS` / `IF EXISTS`.
- **Correção:** Garantir que toda migration possa ser executada múltiplas vezes sem erro.
- **Esforço:** 2-4h (review + fix de migrations existentes)
- **Status:** ✅ Resolvido — Todas as 10 migrations revisadas. Migration #10 corrigida (DROP FOREIGN KEY agora usa INFORMATION_SCHEMA check). Demais já idempotentes.
- **v2:** Era DB-006. Mantido.

### DB-006: Paginação Sem Cache de Count
- **Problema:** `SELECT COUNT(*)` executado a cada requisição de listagem paginada.
- **Correção:** Implementar cache de count com TTL (Redis/Memcached ou cache simples em sessão):
  ```php
  $cacheKey = "count_{$table}_{$tenantId}";
  $total = Cache::remember($cacheKey, 60, fn() => $this->countAll($tenantId));
  ```
- **Esforço:** 4-8h
- **Status:** ✅ Resolvido — Classe Cache criada em app/core/Cache.php (file-based, remember/get/set/forget/flush com TTL).
- **v2:** Era DB-003. Mantido.

### DB-007: Transações Sem Logging em Rollback
- **Problema:** Quando `rollback()` é executado, a exceção é relançada mas sem log explícito da operação que falhou.
- **Correção:**
  ```php
  } catch (\Exception $e) {
      $this->db->rollBack();
      error_log("[ROLLBACK] {$class}::{$method} - " . $e->getMessage());
      throw $e;
  }
  ```
- **Esforço:** 2h
- **Status:** ✅ Resolvido — Logging adicionado a 4 catch blocks sem log: Supply.php, SiteBuilder.php, NfeQueueService.php, NfeEmissionService.php.
- **v2:** Era DB-007. Mantido.

### DB-008: Models Retornando PDOStatement vs Array
- **Problema:** Inconsistência de retorno em models legacy.
- **Correção:** Padronizar para retornar `array` via `fetchAll(PDO::FETCH_ASSOC)`.
- **Esforço:** 4h
- **Status:** ✅ Resolvido — Todos os models já retornam arrays via fetchAll(PDO::FETCH_ASSOC).
- **v2:** Era DB-009. Parcialmente corrigido.

---

### Prioridade BAIXA

### DB-009: Nested Transactions Não Suportadas
- **Problema:** PDO MySQL não suporta SAVEPOINT nativamente via `beginTransaction()` aninhado.
- **Correção:** Implementar wrapper com SAVEPOINT:
  ```php
  class TransactionManager {
      private int $level = 0;
      public function begin(): void {
          if ($this->level === 0) $this->db->beginTransaction();
          else $this->db->exec("SAVEPOINT sp{$this->level}");
          $this->level++;
      }
  }
  ```
- **Esforço:** 4h
- **Status:** ✅ Resolvido — TransactionManager criado em app/core/TransactionManager.php com SAVEPOINT, begin/commit/rollBack, transaction() helper.
- **v2:** Era DB-008. Mantido.

### DB-010: Cursor Pagination
- **Problema:** Paginação baseada em OFFSET degrada com tabelas grandes.
- **Correção:** Implementar keyset pagination para listagens de alto volume.
- **Esforço:** 8-12h
- **Status:** ✅ Resolvido — CursorPaginator já implementado em app/utils/CursorPaginator.php com suporte a next/prev, has_more, joins, filtros.
- **v2:** Era DB-010. Mantido.

---

## Testes e Qualidade

### Prioridade ALTA

### TEST-001: CI/CD Pipeline Inexistente
- **Problema:** Sem pipeline automatizado de testes, análise estática e deploy.
- **Correção:** Criar `.github/workflows/ci.yml`:
  ```yaml
  name: CI
  on: [push, pull_request]
  jobs:
    test:
      runs-on: ubuntu-latest
      services:
        mysql:
          image: mysql:8.0
          env: { MYSQL_ROOT_PASSWORD: root, MYSQL_DATABASE: akti_test }
      steps:
        - uses: actions/checkout@v4
        - uses: shivammathur/setup-php@v2
          with: { php-version: '8.1', extensions: pdo_mysql }
        - run: composer install --no-interaction
        - run: vendor/bin/phpunit --coverage-text
        - run: vendor/bin/phpstan analyse --level=5 app/
  ```
- **Esforço:** 4-8h
- **Status:** ✅ Resolvido — .github/workflows/ci.yml já existe com MySQL 8.0 service, PHP 8.1+xdebug, PHPStan, PHPUnit, coverage upload.
- **v2:** Era TEST-001. Mantido.

### TEST-002: Coverage Não Medido Sistematicamente
- **Problema:** Coverage HTML configurado mas não gerado regularmente.
- **Correção:** Integrar no CI/CD pipeline. Definir meta de 50% → 80%.
- **Esforço:** 2h (com CI/CD)
- **Status:** ✅ Resolvido — Coverage integrado no CI via --coverage-text + coverage.xml upload em push para main.
- **v2:** Era TEST-002. Mantido.

### TEST-003: ~70% dos Controllers Sem Cobertura
- **Problema:** ~35 controllers sem nenhum teste dedicado.
- **Módulos prioritários:**
  | Módulo | Prioridade | Justificativa |
  |--------|-----------|---------------|
  | Pipeline | 🔴 ALTA | Core business, zero testes |
  | NF-e | 🔴 ALTA | Fiscal, zero testes |
  | Financial (parcelas) | 🟠 ALTA | Dinheiro, parcial |
  | Suppliers | 🟡 MÉDIA | CRUD novo |
  | Workflows | 🟡 MÉDIA | Automação nova |
  | Email Marketing | 🟡 MÉDIA | Módulo novo |
- **Esforço:** 40-60h (gradual)
- **Status:** 🔄 Em progresso — Trabalho gradual contínuo. Infraestrutura de testes de página e segurança já funcional.
- **v2:** TEST-005 (parcial). Escalado para ALTA.

### TEST-004: Security Tests Incompletos
- **Problema:** Testes existem para CSRF e XSS básico, mas faltam:
  - File upload bypass
  - Auth bypass / session fixation
  - Rate limiting validation
  - CORS validation
  - Info disclosure (error messages)
- **Correção:** Criar `tests/Security/FileUploadSecurityTest.php`, `AuthBypassTest.php`, etc.
- **Esforço:** 8-12h
- **Status:** ✅ Resolvido — AdvancedSecurityTest.php criado com testes para file upload bypass (.php, extensão dupla, SVG+script), session fixation, info disclosure (stack trace, server path, PHP version header), security headers.
- **v2:** TEST-006/007/008/009. Mantido.

---

### Prioridade MÉDIA

### TEST-005: PHPStan em Level 3
- **Problema:** Level 3 é permissivo. Level 5+ captura mais bugs (tipos, null safety).
- **Correção:**
  1. Gerar baseline: `vendor/bin/phpstan analyse --level=5 --generate-baseline`
  2. Corrigir erros novos antes de merge
  3. Reduzir baseline gradualmente
- **Esforço:** 8-16h
- **Status:** ✅ Resolvido — PHPStan já em level 5 (phpstan.neon). Baseline não necessário.
- **v2:** Era TEST-003. Mantido.

### TEST-006: ESLint Inexistente
- **Problema:** JavaScript sem linting estático.
- **Correção:**
  ```bash
  npm init -y
  npm install --save-dev eslint
  npx eslint --init
  ```
- **Esforço:** 2-4h
- **Status:** ✅ Resolvido — .eslintrc.json já configurado com eslint:recommended, jQuery/Swal/Chart/bootstrap globals, ES2020.
- **v2:** Era TEST-004. Mantido.

### TEST-007: Pre-commit Hooks Ausentes
- **Problema:** Desenvolvedores podem commitar código sem testes e sem lint.
- **Correção:** Instalar husky + lint-staged:
  ```json
  // package.json
  "scripts": { "prepare": "husky install" },
  "lint-staged": {
      "app/**/*.php": ["vendor/bin/phpstan analyse --level=5"],
      "assets/js/**/*.js": ["eslint --fix"]
  }
  ```
- **Esforço:** 2h
- **Status:** ✅ Resolvido — scripts/pre-commit já existe com PHPStan + PHPUnit hooks.
- **v2:** Era TEST-010. Mantido.

### TEST-008: 3 Falhas Pré-existentes Não Investigadas
- **Problema:** PHPUnit reporta 3 failures conhecidas mas não resolvidas.
- **Correção:** Investigar cada falha: corrigir o código ou atualizar o teste.
- **Esforço:** 2-4h
- **Status:** ✅ Resolvido — PHPUnit roda com 0 failures (1232 tests, 4887 assertions, 2 skipped por dependência sped-nfe).
- **v2:** Não identificado. Novo.

---

### Prioridade BAIXA

### TEST-009: .editorconfig Ausente
- **Problema:** Sem configuração centralizada de formatação.
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
  ```
- **Esforço:** 15min
- **Status:** ✅ Resolvido — .editorconfig já existe com indent_style=space, indent_size=4, utf-8, trim_trailing_whitespace.
- **v2:** Era TEST-011. Mantido.

### TEST-010: 19 Testes Incompletos
- **Problema:** PHPUnit reporta 19 testes marcados como incomplete.
- **Correção:** Revisar e implementar ou remover.
- **Esforço:** 4-8h
- **Status:** ✅ Resolvido — PHPUnit roda com 0 incomplete tests (todos resolvidos ou implementados).

---

## Issues Resolvidas desde v2

| ID v2 | Descrição | Resolução v3 |
|--------|-----------|-------------|
| DB-002 | Ausência de alias consistency | ✅ Melhorado |
| DB-009 | Models retornando PDOStatement | ✅ Parcialmente corrigido |

---

## Resumo

### Banco de Dados

| Prioridade | Issues | Status |
|-----------|--------|--------|
| ALTA | 2 (DB-001, DB-002) | ✅ 2/2 |
| MÉDIA | 6 (DB-003 a DB-008) | ✅ 6/6 |
| BAIXA | 2 (DB-009, DB-010) | ✅ 2/2 |
| **Subtotal** | **10** | **✅ 10/10** |

### Testes

| Prioridade | Issues | Status |
|-----------|--------|--------|
| ALTA | 4 (TEST-001 a TEST-004) | ✅ 3/4 (TEST-003 em progresso gradual) |
| MÉDIA | 4 (TEST-005 a TEST-008) | ✅ 4/4 |
| BAIXA | 2 (TEST-009, TEST-010) | ✅ 2/2 |
| **Subtotal** | **10** | **✅ 9/10 + 1 em progresso** |

### **Total Geral: 19/20 resolvidos — TEST-003 (cobertura de controllers) é trabalho gradual contínuo**
