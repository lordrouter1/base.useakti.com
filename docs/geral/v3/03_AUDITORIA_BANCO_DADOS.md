# Auditoria de Banco de Dados — Akti v3

> **Data da Auditoria:** 14/04/2025
> **Escopo:** PDO config, models, queries, transações, migrations, multi-tenant
> **Auditor:** Auditoria Automatizada via Análise Estática de Código
> **Classificação de Severidade:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## 1. Resumo Executivo

| Aspecto | Nota | Tendência vs v2 |
|---------|------|------------------|
| PDO Configuration | ✅ A | = Mantido |
| Prepared Statements | ⚠️ B- | = Mantido |
| Transações | ✅ A | = Mantido |
| Multi-Tenant Isolation | ✅ A | = Mantido |
| Models Inventory | ✅ A | ↑ +25 novos models |
| Migrations | ⚠️ B | ↑ Melhorado |
| Consistência de Retorno | ⚠️ B- | ↑ Parcialmente melhorado |

**Nota Geral: B+** (v2: B-)

O banco de dados é bem configurado com PDO em modo exception, charset utf8mb4, e isolamento multi-tenant robusto. O principal gap é a presença de 5-8 models com interpolação de variáveis em queries (já reportado em SEC-003) e ausência de paginação cursor-based.

---

## 2. PDO Configuration

### Status: ✅ Aprovado

**Arquivo:** `app/config/database.php`

| Configuração | Valor | Status |
|-------------|-------|--------|
| `ATTR_ERRMODE` | `ERRMODE_EXCEPTION` | ✅ L52 |
| `charset` | `utf8mb4` | ✅ L46 (no DSN) |
| Singleton pattern | DSN caching | ✅ L57-72 |
| Master DB support | `getMasterInstance()` | ✅ L118+ |
| Multi-tenant switching | Via TenantManager | ✅ |

**Nota:** `EMULATE_PREPARES` não está explicitamente setado como `false`. O MySQL driver do PDO emula prepares por padrão — para segurança máxima, recomenda-se:
```php
$pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
```

---

## 3. Models Inventory

### Status: ✅ A — 70 Models

**Total de models em `app/models/`:** 70

**Amostra de models com padrão CRUD completo:**

| Model | create | readAll | readOne | update | delete | tenant_id |
|-------|--------|---------|---------|--------|--------|-----------|
| Customer.php | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Product.php | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Order.php | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supplier.php | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| CalendarEvent.php | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| EmailCampaign.php | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| AuditLog.php | ✅ | ✅ | ✅ | — | — | ✅ |
| Attachment.php | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| CheckoutToken.php | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| DashboardWidget.php | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

**Constructor Pattern:** Todos os models recebem `\PDO $db` via construtor:
```php
public function __construct(\PDO $db) {
    $this->db = $db;
}
```

---

## 4. Prepared Statements vs Raw Queries

### Status: ⚠️ B- — 5-8 models com interpolação

#### 4.1 Models com `query()` (sem bind)

| Model | Ocorrências | Risco |
|-------|------------|-------|
| `Stock.php` | L648-675 (5x) | 🟢 BAIXO — SQL estático, sem input externo |
| `ReportModel.php` | L372, L473 | 🟢 BAIXO — Dropdown queries estáticas |
| `PreparationStep.php` | L27, L39 | 🟢 BAIXO — Table existence check |
| `PriceTable.php` | L32, L101, L213, L219 | 🟡 MÉDIO — Multi-line queries |

**Total de `query()` sem bind:** ~20 instâncias, maioria com SQL estático.

#### 4.2 Models com Interpolação de Variáveis (PERIGOSO)

| Model | Linha | Código | Risco |
|-------|-------|--------|-------|
| `Stock.php` | L596 | `WHERE {$whereStr}` | 🟠 ALTO |
| `ReportTemplate.php` | L261 | `SELECT {$selectColumns} FROM \`{$table}\`{$joinClause}` | 🟠 ALTO |
| `RecurringTransaction.php` | L110-251 | `$db->query($sql)` dinâmico | 🟠 ALTO |
| `Supplier.php` | L44 | `SELECT * FROM suppliers {$where}` | 🟡 MÉDIO |
| `PriceTable.php` | L32-219 | Interpolação em múltiplas queries | 🟡 MÉDIO |

**Nota:** Embora as variáveis sejam geralmente construídas internamente (não diretamente de input do usuário), o padrão é perigoso e viola as boas práticas do projeto.

---

## 5. Transações

### Status: ✅ Aprovado

**Padrão consistente:**
```php
$this->db->beginTransaction();
try {
    // operações
    $this->db->commit();
} catch (\Exception $e) {
    $this->db->rollBack();
    throw $e;
}
```

**Models com transações:**

| Model | Método | Linhas |
|-------|--------|--------|
| `Supply.php` | `updateWithAdjustment()` | L326-337 |
| `SiteBuilder.php` | `saveSectionsBatch()` | L100-111 |
| `NfeDocument.php` | Emission | L254-297 |
| `Installment.php` | Payment processing | L658-683 |
| `Order.php` | Create with items | Múltiplas |
| `Product.php` | Grade operations | Múltiplas |
| `Stock.php` | Movements batch | Múltiplas |

**Resultado:** 20+ usos corretos de beginTransaction/commit/rollback com try/catch.

---

## 6. Multi-Tenant Isolation

### Status: ✅ Aprovado

**Mecanismo de isolamento:**
1. **Database-per-tenant:** Cada tenant tem seu próprio banco `akti_<clientname>`
2. **TenantManager:** Resolve subdomínio → seleciona banco correto
3. **tenant_id filtering:** Queries adicionais filtram por `tenant_id` dentro do banco

**Verificação (amostra de 10 models):**

| Model | Método | Filtra tenant_id | Bind seguro |
|-------|--------|-------------------|-------------|
| CalendarEvent.php | readAll (L47) | ✅ | ✅ `:tenant_id` |
| AuditLog.php | readAll (L19) | ✅ | ✅ `:tenant_id` |
| Attachment.php | readAll (L40) | ✅ | ✅ `:tenant_id` |
| EmailCampaign.php | readAll (L59) | ✅ | ✅ `:tenant_id` |
| CheckoutToken.php | readAll (L25) | ✅ | ✅ `:tenant_id` |
| Customer.php | readAll | ✅ | ✅ parametrizado |
| Product.php | readAll | ✅ | ✅ parametrizado |
| Order.php | readAll | ✅ | ✅ parametrizado |
| Supplier.php | readAll | ✅ | ⚠️ interpolação |
| DashboardWidget.php | readAll (L20) | ✅ | ✅ parametrizado |

**Resultado:** 9/10 models usam parameter binding seguro. 1 model (Supplier) usa interpolação.

---

## 7. Paginação

### Status: ⚠️ B

**Padrão encontrado:** LIMIT/OFFSET com parâmetros:
```php
$stmt = $this->db->prepare("SELECT * FROM table WHERE tenant_id = :tid LIMIT :limit OFFSET :offset");
```

**Gap:** Paginação baseada em cursor (keyset pagination) não implementada — para tabelas grandes (>100k registros), OFFSET degrada performance.

---

## 8. Migrations

### Status: ⚠️ B (Melhorado vs v2)

**Estrutura:**
```
sql/                    ← Pendentes
sql/prontos/            ← Aplicadas em teste
```

**Arquivos pendentes:**
- `update_202603291500_import_batches_profiles.sql`
- `update_202603291600_must_change_password.sql`

**Convenção de nomenclatura:** `update_YYYYMMDDhhmm_<N>_descricao.sql` ✅

**Idempotência:** Verificar se migrations usam `IF NOT EXISTS` / `IF EXISTS`.

**Gaps:**
- ⚠️ Sem tabela de tracking de migrations aplicadas (DB-004 da v2)
- ⚠️ Sem script automatizado de execução (DB-005 da v2)
- Script manual: `scripts/run_migration.php` existe mas não é automatizado

---

## 9. Consistência de Retorno dos Models

### Status: ⚠️ B-

**Padrão esperado:** Models devem retornar `array` (nunca `PDOStatement`).

**Resultado da auditoria:**
- ✅ Maioria dos models retorna arrays via `fetchAll(PDO::FETCH_ASSOC)` ou `fetch(PDO::FETCH_ASSOC)`
- ⚠️ Alguns models legacy retornam `PDOStatement` direto (melhoria gradual em andamento)

---

## 10. Schema Patterns

### Verificação de padrões obrigatórios:

| Padrão | Status |
|--------|--------|
| Engine InnoDB | ✅ Padrão do projeto |
| Charset utf8mb4 | ✅ No DSN e migrations |
| Collation utf8mb4_unicode_ci | ✅ |
| `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` | ✅ Presente em todas as tabelas |
| `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | ✅ |
| `tenant_id INT NOT NULL` com FK | ✅ Em tabelas de tenant |
| Soft delete (`deleted_at`) | ⚠️ Parcial — não em todas as tabelas |

---

## 11. Evolução vs. v2

### Issues Resolvidas

| ID v2 | Descrição | Status v3 |
|--------|-----------|-----------|
| DB-002 | Ausência de alias consistency | ✅ Melhorado — padrão mais consistente |
| DB-009 | Models retornando PDOStatement inconsistente | ✅ Parcialmente corrigido |

### Issues Mantidas

| ID | Descrição | Severidade |
|----|-----------|-----------|
| DB-001 | Concatenação dinâmica em queries | 🟠 ALTO |
| DB-003 | Paginação sem cache de count | 🟡 MÉDIO |
| DB-004 | Sem tabela de tracking de migrations | 🟡 MÉDIO |
| DB-005 | Sem script automatizado de migrations | 🟡 MÉDIO |
| DB-006 | Migrations sem idempotência (parcial) | 🟡 MÉDIO |
| DB-007 | Transações sem logging em rollback | 🟡 MÉDIO |
| DB-008 | Nested transactions não suportadas | 🟡 MÉDIO |
| DB-010 | Sem cursor pagination | 🟢 BAIXO |

### Novas Issues

| ID | Descrição | Severidade |
|----|-----------|-----------|
| DB-011 | `EMULATE_PREPARES` não setado como `false` | 🟡 MÉDIO |

### Métricas Comparativas

| Métrica | v2 | v3 | Δ |
|---------|----|----|---|
| Total Models | 45 | 70 | +25 |
| Models com CRUD completo | ~30 | ~55 | +25 |
| Query interpolation issues | ~5 | 5-8 | ~= |
| Transações corretas | ~10 | 20+ | ↑ |
| Total Issues | 10 | 9 | -1 |
