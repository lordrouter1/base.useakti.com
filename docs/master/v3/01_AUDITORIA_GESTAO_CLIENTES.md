# Auditoria — Gestão de Clientes no Master — Akti v3

> **Data da Auditoria:** 15/04/2026  
> **Escopo:** Análise completa do módulo de gestão de clientes (tenants) no painel Master  
> **Auditor:** Auditoria Automatizada via Análise Estática de Código  
> **Classificação de Severidade:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## 1. Resumo Executivo

| Aspecto | Nota | Observação |
|---------|------|------------|
| CRUD de Clientes | ✅ 9/10 | Completo e funcional |
| Provisionamento de DB | ✅ 8/10 | Robusto, multi-plataforma |
| Planos e Limites | ✅ 8/10 | Sync funcional, sem enforcement no Master |
| Segurança | ⚠️ 6/10 | Sem CSRF, sem rate limiting |
| Gestão de Permissões | ❌ 3/10 | Não existe controle de páginas por tenant |
| Suporte/Tickets | ❌ 2/10 | Não existe interação com tickets dos clientes |
| Auditoria/Logs | ✅ 9/10 | AdminLog completo com IP e detalhes |
| UX/UI | ✅ 8/10 | Layout clean, SweetAlert2, responsivo |

**Nota Global: 6.6/10** — Sistema funcional mas com lacunas importantes em controle de acesso e comunicação com clientes.

---

## 2. Inventário de Componentes

### 2.1 Arquivos do Módulo de Clientes

| Componente | Arquivo | Linhas |
|-----------|---------|--------|
| Controller | `master/app/controllers/ClientController.php` | ~375 |
| Model | `master/app/models/TenantClient.php` | ~550 |
| View — Listagem | `master/app/views/clients/index.php` | ~500 |
| View — Criação | `master/app/views/clients/create.php` | ~400 |
| View — Edição | `master/app/views/clients/edit.php` | ~400 |

### 2.2 Tabela Principal: `tenant_clients`

```sql
tenant_clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NULL,
    client_name VARCHAR(150) NOT NULL,
    subdomain VARCHAR(80) NOT NULL UNIQUE,
    db_host VARCHAR(100),
    db_port INT DEFAULT 3306,
    db_name VARCHAR(100) NOT NULL UNIQUE,
    db_user VARCHAR(100),
    db_password VARCHAR(255),
    db_charset VARCHAR(20) DEFAULT 'utf8mb4',
    max_users INT NULL,
    max_products INT NULL,
    max_warehouses INT NULL,
    max_price_tables INT NULL,
    max_sectors INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (db_name LIKE 'akti_%')
)
```

---

## 3. Análise do CRUD de Clientes

### 3.1 Criação (`store()`)

**Status:** ✅ Funcional

| Checkpoint | Status | Observação |
|-----------|--------|------------|
| Sanitização de subdomain | ✅ | `preg_replace('/[^a-zA-Z0-9]/', '')` + `strtolower()` |
| Validação obrigatórios | ✅ | `client_name` e `subdomain` obrigatórios |
| Verificação duplicata subdomain | ✅ | `findBySubdomain()` antes de criar |
| Verificação duplicata db_name | ✅ | `findByDbName()` antes de criar |
| Provisionamento automático | ✅ | Opcional via checkbox `create_database` |
| Criação de primeiro usuário | ✅ | Opcional via checkbox `create_first_user` |
| Sync de limites do plano | ✅ | Busca limites do plano selecionado |
| Log de auditoria | ✅ | `AdminLog::log()` com detalhes completos |

**Achados:**
- ⚠️ **MÉDIO** — `db_password` é armazenado em texto plano na tabela `tenant_clients` (`master/app/models/TenantClient.php`, método `create()`). Deveria ser criptografado com chave simétrica (AES-256).
- ⚠️ **MÉDIO** — Sem validação de tamanho mínimo de senha do DB user.
- ✅ Credenciais são passadas via arquivo `.cnf` temporário ao invés de CLI (seguro).

### 3.2 Edição (`update()`)

**Status:** ✅ Funcional

| Checkpoint | Status | Observação |
|-----------|--------|------------|
| Subdomain protegido | ✅ | Usa `$current['subdomain']` — imutável |
| db_name protegido | ✅ | Usa `$current['db_name']` — imutável |
| Validação client_name | ✅ | Obrigatório |
| Sync de limites | ✅ | Atualiza ao mudar plano |
| Log de auditoria | ✅ | Registra alteração |

**Achado:**
- 🔵 **BAIXO** — Não registra diff de campos (o que mudou exatamente). O log diz apenas "Cliente X atualizado".

### 3.3 Exclusão (`delete()`)

**Status:** ✅ Seguro

| Checkpoint | Status | Observação |
|-----------|--------|------------|
| Exige POST | ✅ | Reject GET requests |
| Confirmação db_name | ✅ | Usuário deve digitar nome do banco |
| Confirmação senha admin | ✅ | `password_verify()` com bcrypt |
| Drop database | ✅ | Remove DB + MySQL user |
| Log de auditoria | ✅ | Registra sucesso/falha do drop |

**Achado:**
- ✅ Excelente implementação de segurança na exclusão. Tripla confirmação (SweetAlert → nome do banco → senha admin).

### 3.4 Ativação/Desativação (`toggleActive()`)

**Status:** ⚠️ Funcional com ressalvas

| Checkpoint | Status | Observação |
|-----------|--------|------------|
| Toggle simples | ✅ | `NOT is_active` |
| Log de auditoria | ✅ | Registra ação |
| Verifica existência | ✅ | `readOne()` antes de toggle |

**Achados:**
- ⚠️ **MÉDIO** — Usa GET para alteração de estado (`?page=clients&action=toggleActive&id=X`). Deveria ser POST para evitar CSRF via link.
- ⚠️ **MÉDIO** — Não há notificação ao tenant quando é desativado.

---

## 4. Análise do Provisionamento de Banco

### 4.1 Fluxo de Provisioning

```
1. Conecta ao servidor MySQL com credenciais master (DB_USER/DB_PASS)
2. CREATE DATABASE IF NOT EXISTS `akti_<subdomain>`
3. CREATE USER IF NOT EXISTS '<db_user>'@'<host>'
4. GRANT ALL PRIVILEGES ON `akti_<subdomain>`.*
5. Clona akti_init_base via: mysqldump | mysql (pipe seguro)
6. Conta tabelas clonadas para feedback
```

| Checkpoint | Status | Observação |
|-----------|--------|------------|
| Credenciais via .cnf | ✅ | Arquivo temporário com `chmod 0600` |
| Cross-platform | ✅ | Windows (cmd /c) e Linux (pipe nativo) |
| Validação binários | ✅ | Verifica existência de mysqldump/mysql |
| Validação banco base | ✅ | Verifica se `akti_init_base` existe |
| Cleanup .cnf | ✅ | `@unlink()` em try/catch/finally |
| Error handling | ✅ | Retorna success/message em todos os cenários |

**Achados:**
- 🔵 **BAIXO** — `escapeshellarg()` usado corretamente, mas `$dbName` é interpolado diretamente em `CREATE DATABASE`. Já é validado via regex no subdomain, mas poderia usar prepared statement pattern.
- 🔵 **INFORMATIVO** — `--single-transaction --routines --triggers --events` garante dump consistente.

---

## 5. Análise de Segurança

### 5.1 Vulnerabilidades Identificadas

| ID | Severidade | Arquivo | Descrição |
|----|-----------|---------|-----------|
| SEC-001 | 🟠 ALTO | `master/index.php` | Sem CSRF tokens em nenhum form do Master |
| SEC-002 | 🟡 MÉDIO | `master/app/controllers/AuthController.php` | Sem rate limiting no login (brute force possível) |
| SEC-003 | 🟡 MÉDIO | `master/app/controllers/ClientController.php:toggleActive()` | Usa GET para alteração de estado |
| SEC-004 | 🟡 MÉDIO | `master/app/models/TenantClient.php:create()` | `db_password` em texto plano |
| SEC-005 | 🔵 BAIXO | `master/app/views/layout/header.php` | CDN sem integrity check (SRI) |

### 5.2 Proteções Existentes ✅

- Prepared statements em 100% das queries (0 SQL injection)
- Password hashing com bcrypt para admin users
- Session-based auth com verificação em toda página protegida
- Triple confirmation para delete (SweetAlert + db_name + password)
- Audit trail completo (AdminLog) com IP tracking
- Path validation em operações de arquivo (backup, logs)
- Credenciais DB via .cnf temporário (não expostas em CLI)

---

## 6. Lacunas Funcionais Identificadas

### 6.1 ❌ Sem Sistema de Tickets no Master

**Impacto:** O sistema principal (app) já possui um módulo completo de tickets (`Akti\Models\Ticket`, `TicketController`) que os clientes (tenants) usam para abrir tickets de suporte. No entanto, **o painel Master não tem nenhuma interface para visualizar ou responder esses tickets**.

**Consequência:** O admin do Master precisa acessar cada banco de tenant individualmente para ver tickets, impossibilitando uma gestão centralizada de suporte.

**Solução proposta:** Ver documento [02_SISTEMA_TICKETS_MASTER.md](02_SISTEMA_TICKETS_MASTER.md).

### 6.2 ❌ Sem Controle de Páginas por Tenant

**Impacto:** Atualmente o Master pode apenas:
- Ativar/desativar tenant (`is_active`)
- Definir limites de recursos (users, products, warehouses, etc.)
- Habilitar/desabilitar módulos via `enabled_modules` JSON

Mas **não pode controlar quais páginas específicas** um tenant pode acessar. O `enabled_modules` cobre apenas módulos (financial, nfe, payment_gateways, boleto), não páginas individuais como `reports`, `email_marketing`, `site_builder`, `quality`, etc.

**Consequência:** Todos os tenants têm acesso a todas as páginas do sistema, sem possibilidade de diferenciação por plano ou restrição manual.

**Solução proposta:** Ver documento [03_PERMISSOES_PAGINAS_TENANT.md](03_PERMISSOES_PAGINAS_TENANT.md).

---

## 7. Evolução vs. v2

### Issues Resolvidas desde v2

| ID | Descrição | Status |
|----|-----------|--------|
| BUG-001 | Password field mismatch em AuthService | ✅ Corrigido |
| BUG-002 | Password field mismatch em BackupController | ✅ Corrigido |
| BUG-003 | Security bypass em master_only routes | ✅ Corrigido |
| P1-001 a P1-005 | 5 issues de segurança | ✅ Todas corrigidas |
| P2-001 a P2-007 | 7 issues de boas práticas | ✅ Todas corrigidas |

### Novas Issues Encontradas nesta Versão

| ID | Severidade | Tipo | Descrição |
|----|-----------|------|-----------|
| FEAT-001 | 🔴 CRÍTICO | Feature | Controle de páginas por tenant inexistente |
| FEAT-002 | 🟠 ALTO | Feature | Sistema de tickets no Master inexistente |
| SEC-001 | 🟠 ALTO | Segurança | CSRF em forms do Master (pendente v2) |
| SEC-002 | 🟡 MÉDIO | Segurança | Rate limiting no login |
| SEC-003 | 🟡 MÉDIO | Segurança | toggleActive via GET |
| SEC-004 | 🟡 MÉDIO | Segurança | db_password em texto plano |

---

## 8. Inventário Completo do Master

### 8.1 Controllers (8)

| Controller | Métodos | Linhas | Status |
|-----------|---------|--------|--------|
| AuthController | login, authenticate, logout | ~70 | ✅ |
| DashboardController | index | ~35 | ✅ |
| PlanController | index, create, store, edit, update, delete | ~160 | ✅ |
| ClientController | index, create, store, edit, update, toggleActive, delete, createTenantUser, getPlanLimits | ~375 | ⚠️ SEC-003 |
| MigrationController | index, apply, compareSchema, users, createUser, toggleUserActive, deleteUser | ~300 | ✅ |
| GitController | index, fetch, pull, reset, detail, checkout, diagnose | ~350 | ✅ |
| BackupController | index, run, download, delete | ~200 | ✅ |
| LogController | index, read, search, download, analyze | ~155 | ✅ |

### 8.2 Models (8)

| Model | Tabela | Métodos CRUD | Status |
|-------|--------|-------------|--------|
| AdminUser | admin_users | findByEmail, findById, updateLastLogin, updatePassword, readAll | ✅ |
| AdminLog | admin_logs | log, readRecent | ✅ |
| Plan | plans | readAll, readActive, readOne, create, update, delete | ✅ |
| TenantClient | tenant_clients | readAll, readOne, create, update, delete, findBySubdomain, findByDbName, provisionDatabase, dropDatabase, createTenantUser, getStats, toggleActive, updateLimitsFromPlan | ⚠️ SEC-004 |
| Migration | migration_logs | executeSqlOnAllTenants, compareSchema, listAllTenantUsers | ✅ |
| GitVersion | N/A (filesystem) | getRepoInfo, fetch, pull, forceReset, diagnose | ✅ |
| Backup | N/A (filesystem) | listBackups, runBackup, deleteBackup | ✅ |
| NginxLog | N/A (filesystem) | listLogFiles, readTail, search, analyzeErrors | ✅ |

### 8.3 Views (16)

| View | Arquivo | Status |
|------|---------|--------|
| Login | auth/login.php | ✅ |
| Dashboard | dashboard/index.php | ✅ |
| Planos Lista | plans/index.php | ✅ |
| Planos Criar | plans/create.php | ✅ |
| Planos Editar | plans/edit.php | ✅ |
| Clientes Lista | clients/index.php | ✅ |
| Clientes Criar | clients/create.php | ✅ |
| Clientes Editar | clients/edit.php | ✅ |
| Migrações | migrations/index.php | ✅ |
| Migrações Resultados | migrations/results.php | ✅ |
| Migrações Usuários | migrations/users.php | ✅ |
| Git | git/index.php | ✅ |
| Backup | backup/index.php | ✅ |
| Logs | logs/index.php | ✅ |
| Layout Header | layout/header.php | ✅ |
| Layout Footer | layout/footer.php | ✅ |

### 8.4 Tabelas Master DB (5)

| Tabela | Registros Estimados | Status |
|--------|-------------------|--------|
| admin_users | Poucos (1-5 admins) | ✅ |
| admin_logs | Crescente (audit trail) | ✅ |
| plans | Poucos (3-10 planos) | ✅ |
| tenant_clients | Médio (tenants ativos) | ✅ |
| migration_logs | Crescente (histórico) | ✅ |
