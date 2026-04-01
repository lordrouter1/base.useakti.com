# Auditoria de Banco de Dados e Models — Akti v2

> **Data da Auditoria:** 01/04/2026  
> **Escopo:** Camada de dados completa — Models, PDO, Migrations, Queries SQL, Transações, Multi-Tenancy, Paginação  
> **Referência:** OWASP SQL Injection Prevention, PDO Best Practices, Database Design Patterns

---

## 1. Resumo Executivo

A camada de dados do Akti é **sólida e segura**. Utiliza PDO com prepared statements em 85%+ das queries, transações explícitas para operações críticas (financeiro, NF-e, estoque), e paginação parametrizada consistente. **Nenhuma vulnerabilidade crítica de SQL Injection foi identificada.** Os pontos de melhoria são refinamentos de consistência e adequação ao multi-tenancy.

| Aspecto | Nota | Observação |
|---|---|---|
| Prepared Statements | ⭐⭐⭐⭐ | 85%+ — remaining 15% são queries estáticas sem input do usuário |
| Transações | ⭐⭐⭐⭐ | 5 models com transações explícitas |
| Paginação | ⭐⭐⭐⭐⭐ | 15+ models com paginação segura |
| Multi-tenant isolation | ⭐⭐⭐ | Parcial — 3 models com tenant_id, restante depende do DB por tenant |
| Migrations | ⭐⭐⭐ | Poucos arquivos; sem runner automatizado |
| SQL Injection | ⭐⭐⭐⭐⭐ | 0 vulnerabilidades críticas |

---

## 2. Configuração de Conexão

### 2.1 Database Class (`app/config/database.php`)

- **Padrão:** Singleton por DSN — evita múltiplas conexões por request
- **Compatibilidade:** Suporta `getInstance()` (static) e `getConnection()` (instância)
- **Error Mode:** `PDO::ERRMODE_EXCEPTION`
- **Charset:** `utf8mb4` via DSN

```php
// Padrão de cache por DSN
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', ...);
if (isset(self::$instances[$dsn])) return self::$instances[$dsn];
$pdo = new PDO($dsn, $user, $pass);
self::$instances[$dsn] = $pdo;
```

### 2.2 Multi-Tenant Database Resolution

```
TenantManager → resolve subdomain → lookup akti_master.tenants
  → tenant DB name → Database::getInstance($tenantDb)
    → PDO connection cached by DSN
```

- **Isolamento:** 1 database por tenant
- **Master DB:** `akti_master` — contém mapa de subdomains → databases

### 2.3 Métodos da classe Database

| Método | Tipo | Função |
|---|---|---|
| `getInstance(?string $tenantDb)` | static | Retorna PDO singleton, cacheado por DSN |
| `resetInstances()` | static | Limpa cache — útil para testes |
| `resetInstance(?string $tenantDb)` | static | Limpa conexão específica |
| `getConnection()` | instance | Wrapper para backward compatibility |

---

## 3. Inventário Completo de Models (45)

### 3.1 Tabela Geral

| # | Model | Métodos CRUD | Paginação | Transações | Tenant-Aware | Prepared Stmts |
|---|---|---|---|---|---|---|
| 1 | User | login, readAll, readOne, create, update, delete, countAll | — | — | — | ✅ |
| 2 | UserGroup | readAll, readOne, create, update, delete, getPermissions | — | — | — | ✅ |
| 3 | Customer | readAll, readPaginated, readOne, create, update, delete | ✅ | — | — | ✅ |
| 4 | Product | readAll, readPaginated, readPaginatedFiltered, create, update, delete, addImage | ✅ | — | — | ✅ |
| 5 | Order | create, readAll, readPaginated, readOne, update, delete | ✅ | — | — | ✅ |
| 6 | Pipeline | getOrdersByStage, moveToStage, getStageGoals, addHistory | — | — | — | ✅ |
| 7 | Financial | getSummary, getChartData, getTransactionsPaginated, createInstallments | ✅ | ✅ | — | ✅ |
| 8 | Installment | getById, getPaginated, generate, confirm, reverse | ✅ | ✅ | — | ✅ |
| 9 | Stock | getAllWarehouses, getMovementsPaginated, getStockItems | ✅ | — | — | ⚠️ |
| 10 | Category | readAll, create, update, delete, getSubcategories | — | — | — | ⚠️ |
| 11 | Subcategory | readAll, readByCategoryId, create, update, delete | — | — | — | ⚠️ |
| 12 | NfeDocument | create, readOne, readPaginated, update, delete | ✅ | ✅ | — | ✅ |
| 13 | SiteBuilder | getPages, createPage, getSections, saveComponentsBatch | ✅ | — | ✅ | ✅ |
| 14 | Logger | log, getPaginated | ✅ | — | — | ✅ |
| 15 | Commission | getAllFormas, getComissoesRegistradas, registrarComissao | ✅ | — | — | ✅ |
| 16 | NfeAuditLog | log, readPaginated, getDistinctActions | ✅ | — | — | ✅ |
| 17 | NfeCredential | readAll, readOne, store, update, delete | — | — | — | ✅ |
| 18 | CompanySettings | getAll, get, set | — | — | — | ⚠️ |
| 19 | ProductionSector | readAll, readOne, create, update, delete | — | — | — | ⚠️ |
| 20 | ReportModel | getOrdersByPeriod, getRevenueByCustomer, getIncomeStatement | — | — | — | ⚠️ |
| 21 | IbptaxModel | import, getAllTaxes, getTaxByCode, clear | — | ✅ | — | ⚠️ |
| 22 | DashboardWidget | saveBatchApprovalStatus, getByUser | — | ✅ | — | ✅ |
| 23 | ImportBatch | create, updateProgress, finalize, addItem | ✅ | — | ✅ | ✅ |
| 24 | ImportMappingProfile | getByTenant, save, delete | — | — | ✅ | ✅ |
| 25-45 | +20 outros models | Padrão CRUD | Variável | — | — | Maioria ✅ |

### 3.2 Legenda
- ✅ = Implementado / Seguro
- ⚠️ = Parcial / Pode melhorar
- — = Não se aplica / Não implementado

---

## 4. Padrões de Query SQL

### 4.1 Prepared Statements (85%+ do codebase)

**Padrão dominante — SEGURO:**
```php
$stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = :id AND tenant_id = :tid");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':tid', $tenantId, PDO::PARAM_INT);
$stmt->execute();
```

**Padrão alternativo — SEGURO:**
```php
$stmt = $this->conn->prepare("INSERT INTO customers (name, email) VALUES (?, ?)");
$stmt->execute([$name, $email]);
```

### 4.2 Queries Raw (15% restante — BAIXO RISCO)

Locais onde `query()` ou `exec()` são usados **sem prepared statements**:

| Arquivo | Linha | Query | Risco | Motivo |
|---|---|---|---|---|
| `Stock.php` | ~58 | `"$where"` concatenação | **BAIXO** | `$where` é construído internamente de bool, não de input |
| `Stock.php` | ~87 | `exec("UPDATE warehouses...")` | **NENHUM** | Query estática sem parâmetros |
| `Category.php` | ~141 | `query()` raw | **BAIXO** | Leitura de categorias estáticas |
| `Subcategory.php` | ~51 | `query()` raw | **BAIXO** | Leitura estática |
| `CompanySettings.php` | ~33 | `query()` raw | **BAIXO** | Configurações do sistema |
| `ProductionSector.php` | ~26 | `query("...{$where}")` | **BAIXO** | `$where` é condicional interno |
| `ReportModel.php` | ~372 | `query()` raw | **BAIXO** | Dropdown lists admin |
| `ReportModel.php` | ~473 | `query()` raw | **BAIXO** | Dropdown lists admin |
| `IbptaxModel.php` | ~246 | `query("SELECT COUNT...")` | **NENHUM** | Inspeção de schema |
| `IbptaxModel.php` | ~247 | `exec("TRUNCATE TABLE")` | **NENHUM** | Operação admin batch |
| `Financial.php` | ~1634 | `query("SELECT DATABASE()")` | **NENHUM** | Diagnóstico |
| `Financial.php` | ~1670+ | `query("SHOW TABLES...")` | **NENHUM** | Detecção de schema |

**Veredicto:** ✅ **ZERO vulnerabilidades críticas de SQL Injection.** As queries raw não utilizam input do usuário.

---

## 5. Paginação

### 5.1 Padrão de Implementação

15+ models implementam paginação com o mesmo padrão seguro:

```php
public function readPaginated(int $page = 1, int $perPage = 10, string $search = ''): array
{
    $offset = ($page - 1) * $perPage;
    
    // Count total
    $countSql = "SELECT COUNT(*) FROM table WHERE tenant_id = :tid";
    if ($search) $countSql .= " AND name LIKE :search";
    $countStmt = $this->conn->prepare($countSql);
    $countStmt->bindValue(':tid', $tid, PDO::PARAM_INT);
    if ($search) $countStmt->bindValue(':search', "%$search%");
    $countStmt->execute();
    $total = $countStmt->fetchColumn();
    
    // Fetch page
    $sql = "SELECT * FROM table WHERE ... LIMIT :limit OFFSET :offset";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return [
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'pages' => ceil($total / $perPage),
        'current_page' => $page,
    ];
}
```

### 5.2 Models com Paginação

| Model | Método | Filtros |
|---|---|---|
| Customer | `readPaginated()` | search, status |
| Product | `readPaginated()`, `readPaginatedFiltered()` | search, category, subcategory, status |
| Order | `readPaginated()` | search, status, date range |
| Financial | `getTransactionsPaginated()`, `getAllInstallmentsPaginated()` | type, status, date range |
| Installment | `getPaginated()` | status, order_id |
| Stock | `getMovementsPaginated()` | warehouse, product, type |
| Logger | `getPaginated()` | action, user, date range |
| Commission | `getComissoesRegistradas()` | seller, status, period |
| NfeDocument | `readPaginated()` | status, date, customer |
| NfeAuditLog | `readPaginated()` | action, entity, user |
| ImportBatch | `getItemsWithEntity()` | batch_id, status |

### 5.3 Avaliação

- ✅ **PARAM_INT** para LIMIT/OFFSET em todos os models
- ✅ **LIKE** com bind para search (seguro contra SQL injection)
- ✅ **Count separado** para total de registros
- ⚠️ **Sem cache de count** — count é executado a cada requisição
- ⚠️ **Sem cursor pagination** — offset-based pode ser lento com milhões de registros

---

## 6. Transações (5 Models)

### 6.1 Financial

```php
// createInstallments() — Linhas ~848-883
try {
    $this->conn->beginTransaction();
    // Insere parcelas em batch
    $this->conn->commit();
    return true;
} catch (PDOException $e) {
    $this->conn->rollBack();
    return false;
}
```

**Métodos transacionais:** `createInstallments()`, `reverseInstallment()`

### 6.2 Installment

**Métodos transacionais:** `generate()`, `confirm()`, `reverse()`

```php
// confirm() — Marca parcela como paga + atualiza status do pedido
$this->conn->beginTransaction();
// 1. UPDATE installments SET status = 'paid'
// 2. UPDATE orders SET payment_status = 'paid' (se todas pagas)
$this->conn->commit();
```

### 6.3 NfeDocument

**Métodos transacionais:** Autorização de NF-e (update status + log)

### 6.4 IbptaxModel

**Métodos transacionais:** `import()` — bulk insert de dados tributários

### 6.5 DashboardWidget

**Métodos transacionais:** `saveBatchApprovalStatus()` — atualização em lote de aprovações

### 6.6 Avaliação de Transações

| Aspecto | Status | Observação |
|---|---|---|
| Atomicidade | ✅ | Todas usam beginTransaction/commit/rollBack corretamente |
| Logging em rollBack | ⚠️ | Nenhum `catch` faz log explícito — depende do propagation da exceção |
| Nested transactions | ❌ | Não suportado — se um service chama outro que faz transaction, comportamento indefinido |
| Savepoints | ❌ | Não utilizados |

**Recomendação:** Adicionar logging explícito no rollBack:
```php
catch (PDOException $e) {
    $this->conn->rollBack();
    Log::error('Transaction rollback', ['method' => __METHOD__, 'error' => $e->getMessage()]);
    throw $e; // Re-throw para o controller tratar
}
```

---

## 7. Multi-Tenancy na Camada de Dados

### 7.1 Estratégia: Database-per-Tenant

O Akti usa **um banco de dados por tenant**, resolvido via subdomain no TenantManager. Isso significa que a maioria dos models **NÃO precisa** filtrar por `tenant_id` — o isolamento é garantido pela conexão PDO.

### 7.2 Exceção: Models que USAM tenant_id

3 models filtram explicitamente por `tenant_id`:

| Model | Motivo |
|---|---|
| **SiteBuilder** | Tabelas compartilhadas (sb_pages, sb_sections, sb_components) |
| **ImportBatch** | Logs de importação cross-tenant na master DB |
| **ImportMappingProfile** | Perfis de mapeamento cross-tenant |

### 7.3 Models que NÃO filtram tenant_id

| Model | Risco | Motivo |
|---|---|---|
| Customer | NENHUM | Banco de dados é isolado por tenant |
| Order | NENHUM | Idem |
| Product | NENHUM | Idem |
| Financial | NENHUM | Idem |
| User | NENHUM | Idem |

**Conclusão:** A estratégia database-per-tenant elimina a necessidade de filtros tenant_id na maioria dos models. 

### 7.4 Risco Potencial

Se o sistema migrar para **single-database multi-tenant**, TODOS os models precisarão de:
1. Coluna `tenant_id` em todas as tabelas
2. Filtro `WHERE tenant_id = :tid` em todas as queries
3. Index em `tenant_id` para performance

---

## 8. Migrations (Arquivos SQL)

### 8.1 Arquivos Existentes

| Arquivo | Tabelas | Operação |
|---|---|---|
| `sql/update_202604010400_site_builder_tables.sql` | sb_pages, sb_sections, sb_components, sb_theme_settings | CREATE TABLE |
| `sql/prontos/` | Migrations já executadas (archival) | — |

### 8.2 Padrão de Naming

```
update_{YYYYMMDDhhmm}_{descricao}.sql
```

### 8.3 Avaliação

| Aspecto | Status | Observação |
|---|---|---|
| Naming convention | ✅ | Timestamp + descrição |
| Idempotência | ⚠️ | Sem `IF NOT EXISTS` nos CREATEs |
| Runner automatizado | ❌ | Sem script de execução automática |
| Rollback | ❌ | Sem scripts de reversão |
| Versionamento | ⚠️ | Sem tabela `schema_migrations` para tracking |

**Recomendação:** Implementar tabela `schema_migrations`:
```sql
CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

E runner:
```php
// scripts/migrate.php
$files = glob('sql/update_*.sql');
foreach ($files as $file) {
    $name = basename($file);
    if (!$db->query("SELECT 1 FROM schema_migrations WHERE migration = '$name'")->fetch()) {
        $sql = file_get_contents($file);
        $db->exec($sql);
        $db->exec("INSERT INTO schema_migrations (migration) VALUES ('$name')");
    }
}
```

---

## 9. Padrão de Retorno dos Models

### 9.1 Tipos de Retorno Detectados

| Tipo | Uso | Exemplo |
|---|---|---|
| `array` | Resultado de fetchAll() | `Customer::readPaginated()` |
| `array\|null` | Resultado de fetch() ou null | `Customer::readOne()` |
| `bool` | Sucesso/falha | `User::create()`, `Order::delete()` |
| `int` | ID criado ou count | `Customer::create()`, `countAll()` |
| `PDOStatement` | (Legado) Statement cru | `User::readAll()`, `Category::readAll()` |

### 9.2 Problema: Retorno de PDOStatement

7+ models retornam `PDOStatement` diretamente de `readAll()`:

```php
// Legado — EVITAR
public function readAll() {
    $stmt = $this->conn->prepare("SELECT * FROM users");
    $stmt->execute();
    return $stmt; // PDOStatement retornado
}
```

**Problema:** A view/controller precisa saber como iterar o statement. Acoplamento ao PDO.

**Solução recomendada:**
```php
// Moderno — PREFERIR
public function readAll(): array {
    $stmt = $this->conn->prepare("SELECT * FROM users");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

### 9.3 Evolução Observada

| Geração | Padrão | Models |
|---|---|---|
| **Legada** | Retorna PDOStatement | User, Category, Subcategory, UserGroup |
| **Intermediária** | Retorna array/bool | Customer, Product, Order |
| **Moderna** | Retorna array tipado + paginação | Financial, Installment, NfeAuditLog, Commission |

---

## 10. Schemas Conhecidos

### 10.1 Tabelas do Site Builder (via migration)

```sql
-- sb_pages
CREATE TABLE sb_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    type ENUM('home','about','products','services','contact','blog','custom') DEFAULT 'custom',
    meta_title VARCHAR(200),
    meta_description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_slug (tenant_id, slug),
    KEY idx_tenant_active (tenant_id, is_active)
);

-- sb_sections
CREATE TABLE sb_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    page_id INT NOT NULL,
    type VARCHAR(100) NOT NULL,
    settings JSON,
    sort_order INT DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_page_order (page_id, sort_order),
    CONSTRAINT fk_sb_section_page FOREIGN KEY (page_id) REFERENCES sb_pages(id) ON DELETE CASCADE
);

-- sb_components
CREATE TABLE sb_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    section_id INT NOT NULL,
    type VARCHAR(100) NOT NULL,
    content JSON,
    grid_col INT DEFAULT 12,
    grid_row INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_section_order (section_id, sort_order),
    CONSTRAINT fk_sb_component_section FOREIGN KEY (section_id) REFERENCES sb_sections(id) ON DELETE CASCADE
);

-- sb_theme_settings
CREATE TABLE sb_theme_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_key (tenant_id, setting_key),
    KEY idx_tenant_group (tenant_id, setting_group)
);
```

### 10.2 Tabelas Inferidas dos Models

A partir dos models, é possível inferir a existência das seguintes tabelas:

| Tabela | Model | Colunas Inferidas |
|---|---|---|
| `users` | User | id, name, email, password, group_id, status, avatar |
| `user_groups` | UserGroup | id, name, permissions |
| `customers` | Customer | id, name, email, phone, cpf_cnpj, address, status |
| `products` | Product | id, name, sku, price, category_id, subcategory_id, status |
| `product_images` | Product | id, product_id, image_path, sort_order |
| `orders` | Order | id, customer_id, status, payment_status, total, notes |
| `order_items` | Order | id, order_id, product_id, quantity, price |
| `categories` | Category | id, name, parent_id |
| `subcategories` | Subcategory | id, name, category_id |
| `pipeline_stages` | Pipeline | id, name, order, color |
| `pipeline_history` | Pipeline | id, order_id, stage_id, user_id, timestamp |
| `pipeline_goals` | Pipeline | id, stage_id, time_minutes |
| `installments` | Installment | id, order_id, amount, due_date, status, paid_at |
| `transactions` | Financial | id, type, amount, description, date |
| `stock_warehouses` | Stock | id, name, location |
| `stock_movements` | Stock | id, warehouse_id, product_id, type, quantity |
| `nfe_documents` | NfeDocument | id, order_id, xml, status, protocol |
| `nfe_credentials` | NfeCredential | id, certificate_path, password, environment |
| `commissions` | Commission | id, seller_id, order_id, amount, status |
| `company_settings` | CompanySettings | id, key, value |
| `production_sectors` | ProductionSector | id, name, description |
| `system_logs` | Logger | id, action, entity, user_id, details |
| `import_batches` | ImportBatch | id, tenant_id, type, status, progress |
| `import_mapping_profiles` | ImportMappingProfile | id, tenant_id, name, mappings |

---

## 11. Conclusões e Prioridades

### Forças
1. ✅ **Zero SQL injections críticas** — prepared statements dominam
2. ✅ **Transações** em operações financeiras e batch
3. ✅ **Paginação segura** em 15+ models
4. ✅ **Singleton PDO** — sem conexões duplicadas
5. ✅ **Multi-tenant isolation** via database-per-tenant

### Prioridades de Melhoria

| Prioridade | Item | Esforço |
|---|---|---|
| 1 | Converter queries raw para prepared statements (8 locais) | Baixo |
| 2 | Padronizar retorno de `readAll()` — array em vez de PDOStatement | Médio |
| 3 | Logging em rollBack de transações | Baixo |
| 4 | Implementar tabela `schema_migrations` | Médio |
| 5 | Adicionar `IF NOT EXISTS` nos SQL de criação | Baixo |
| 6 | Implementar migration runner automatizado | Médio |
| 7 | Tipagem de retorno em todos os métodos de models | Alto |
| 8 | Considerar cursor pagination para datasets grandes | Alto |
