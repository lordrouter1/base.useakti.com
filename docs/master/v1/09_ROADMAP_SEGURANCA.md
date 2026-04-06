# Roadmap de Segurança — Master Integrado ao Akti

> **Data:** 06/04/2026  
> **Pré-requisito:** Roadmap de Integração (08_ROADMAP_INTEGRACAO.md) implementado.  
> **Objetivo:** Garantir que o painel master integrado atenda ou supere os padrões de  
> segurança do sistema Akti principal, eliminando todas as vulnerabilidades identificadas  
> na auditoria de segurança (01_AUDITORIA_SEGURANCA.md).

---

## Vulnerabilidades Resolvidas pela Integração

As seguintes vulnerabilidades do master antigo são **automaticamente eliminadas** ao integrar com o Akti:

| # | Vulnerabilidade | Severidade | Como é resolvida |
|---|----------------|------------|-------------------|
| 1 | Credenciais hardcoded em `config.php` | 🔴 CRÍTICA | Eliminado — usa `.env` via `TenantManager::getMasterConfig()` |
| 2 | Sem CSRF em nenhum formulário | 🔴 CRÍTICA | Resolvido — `CsrfMiddleware` global + `csrf_field()` em forms |
| 3 | Sem rate limiting no login | 🔴 CRÍTICA | Resolvido — `LoginAttempt` (3 fails → CAPTCHA, 5+ → block 30min) |
| 4 | `session_regenerate_id()` ausente | 🔴 CRÍTICA | Resolvido — `AuthService::attemptMasterLogin()` regenera ID |
| 5 | `htmlspecialchars` inconsistente | 🟠 ALTA | Resolvido — migração para `e()`, `eAttr()`, `eJs()` |
| 6 | Sem Security Headers | 🟠 ALTA | Resolvido — `SecurityHeadersMiddleware` global |
| 7 | Cookie de sessão sem flags seguras | 🟠 ALTA | Resolvido — `session.php` (httponly, samesite=strict) |
| 8 | `$_SESSION['admin_id']` sem timeout | 🟠 ALTA | Resolvido — `SessionGuard` com timeout configurável |

---

## Vulnerabilidades que Requerem Ação Manual

### 🔴 CRÍTICA — Execução de Comandos do Sistema (RCE)

**Contexto:** Os módulos Git, Backup e Migration executam comandos shell (`exec()`, `shell_exec()`).

**Risco:** Command injection via input malicioso.

**Ações:**

#### S1. Sanitizar todos os inputs usados em comandos shell

| Controller | Método | Comando | Ação |
|-----------|--------|---------|------|
| GitController | `pull()` | `git -C $path pull` | Validar `$path` com whitelist |
| GitController | `forceReset()` | `git reset --hard` | Requerer confirmação 2FA |
| BackupController | `run()` | `mysqldump $dbName` | Validar `$dbName` contra regex `^[a-zA-Z0-9_]+$` |
| MigrationController | `apply()` | `mysql ... < $file` | Validar `$file` contra whitelist de extensões `.sql` |

**Implementação:**
```php
// Helper para validar nomes de banco/arquivo
function validateDbName(string $name): string 
{
    if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $name)) {
        throw new \InvalidArgumentException('Nome de banco inválido: ' . $name);
    }
    return $name;
}

function validateFilePath(string $path, string $allowedDir): string 
{
    $realPath = realpath($path);
    $realAllowed = realpath($allowedDir);
    if ($realPath === false || !str_starts_with($realPath, $realAllowed)) {
        throw new \InvalidArgumentException('Caminho de arquivo inválido');
    }
    return $realPath;
}

// Usar escapeshellarg() em TODOS os inputs passados para exec/shell_exec
$cmd = sprintf('mysqldump -u %s -p%s %s',
    escapeshellarg($user),
    escapeshellarg($pass),
    escapeshellarg(validateDbName($dbName))
);
```

#### S2. Usar `proc_open()` em vez de `exec()` para controle de output

```php
// Substituir exec($cmd, $output, $returnCode) por:
$process = proc_open($cmd, [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
], $pipes);

$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
$returnCode = proc_close($process);
```

#### S3. Restringir exec() a IPs internos

```php
// No middleware ou no controller master:
$allowedIps = ['127.0.0.1', '::1', '192.168.0.0/16', '10.0.0.0/8'];
if (!isIpInRange($_SERVER['REMOTE_ADDR'], $allowedIps)) {
    http_response_code(403);
    exit('Acesso restrito à rede interna');
}
```

---

### 🟠 ALTA — SQL Injection em Queries Dinâmicas

**Contexto:** O `MigrationController` monta queries com nomes de banco dinâmicos.

**Risco:** Injection via `$dbName` em queries como `USE $dbName`.

**Ação:**
```php
// ANTES (vulnerável):
$this->db->exec("USE $dbName");

// DEPOIS (seguro):
$dbName = validateDbName($dbName);
$this->db->exec("USE `" . str_replace('`', '``', $dbName) . "`");
```

**Nota:** Para nomes de banco/tabela, NÃO é possível usar prepared statements.  
A validação por regex é a proteção primária. O escape de backtick é defesa em profundidade.

---

### 🟠 ALTA — Path Traversal em Backup/Logs

**Contexto:** Download de backups e leitura de logs usam caminhos informados pelo usuário.

**Ação:**
```php
// Validar que o arquivo está dentro do diretório permitido
$requestedFile = $_GET['file'];
$allowedDir = realpath(__DIR__ . '/../../storage/backups');
$fullPath = realpath($allowedDir . '/' . basename($requestedFile));

if ($fullPath === false || !str_starts_with($fullPath, $allowedDir)) {
    http_response_code(403);
    exit('Acesso negado');
}

// Validar extensão
$allowedExtensions = ['sql', 'gz', 'zip', 'log'];
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExtensions, true)) {
    http_response_code(403);
    exit('Tipo de arquivo não permitido');
}
```

---

### 🟡 MÉDIA — Logging e Auditoria

**Ação:**

#### S4. Registrar log de auditoria para toda ação destrutiva

```php
// Na model AdminLog, registrar:
// - WHO: admin_id, admin_name
// - WHAT: ação (backup_run, migration_apply, git_pull, client_delete, etc.)
// - WHEN: timestamp
// - WHERE: IP, user-agent
// - RESULT: sucesso/falha, detalhes

$adminLog->log($adminId, 'backup_run', 'backup', null, json_encode([
    'database'    => $dbName,
    'ip'          => $_SERVER['REMOTE_ADDR'],
    'user_agent'  => $_SERVER['HTTP_USER_AGENT'],
    'result'      => $success ? 'success' : 'failed',
    'file_size'   => $fileSize ?? null,
]));
```

#### S5. Implementar retenção de logs

```sql
-- Criar job ou cron para limpar logs antigos (> 90 dias)
DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

### 🟡 MÉDIA — Autenticação Avançada

#### S6. 2FA para ações destrutivas (Fase 2)

Para operações de alto risco, requerer confirmação extra:

| Ação | Nível de risco | Proteção |
|------|---------------|----------|
| `git reset --hard` | 🔴 | 2FA ou re-autenticação |
| `DROP DATABASE` (se existir) | 🔴 | 2FA + confirmação textual |
| `backup delete` | 🟠 | Re-autenticação (senha) |
| `client delete` | 🟠 | Re-autenticação (senha) |
| `migration apply` (produção) | 🟠 | Re-autenticação + confirmation prompt |

**Implementação da re-autenticação:**
```php
// No controller, antes de executar ação destrutiva:
if (!$this->verifyReauth()) {
    return $this->requestReauth('?page=master_git&action=forceReset&id=' . $id);
}

// Método no BaseController ou MasterBaseController:
protected function verifyReauth(): bool 
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return false;
    if (empty($_POST['reauth_password'])) return false;

    $masterDb = \Database::getMasterInstance();
    $adminUser = new \Akti\Models\Master\AdminUser($masterDb);
    $admin = $adminUser->findById($_SESSION['master_admin_id']);

    return $admin && password_verify($_POST['reauth_password'], $admin['password']);
}
```

---

### 🟢 BAIXA — Hardening Adicional

#### S7. Content-Security-Policy específica para Master

```php
// No header master, adicionar CSP mais restritiva:
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com; font-src 'self' fonts.gstatic.com cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self'");
```

#### S8. Bloquear acesso master por subdomínio

O master só deve ser acessível pelo domínio principal ou por subdomínio específico:

```php
// No middleware master_only:
$allowedHosts = ['admin.akti.com', 'localhost', '127.0.0.1'];
if (!in_array($_SERVER['HTTP_HOST'], $allowedHosts, true)) {
    http_response_code(403);
    exit;
}
```

#### S9. Timeout de sessão mais curto para master

```php
// No SessionGuard, após verificar is_master_admin:
$masterTimeout = 15 * 60; // 15 minutos (vs 30 min do tenant)
if ($_SESSION['is_master_admin'] && 
    (time() - $_SESSION['last_activity'] > $masterTimeout)) {
    session_destroy();
    header('Location: ?page=login&msg=session_expired');
    exit;
}
```

---

## Checklist de Implementação por Prioridade

### 🔴 Prioridade Imediata (fazer na integração)

- [ ] Eliminar credenciais hardcoded de `master/app/config/config.php`
- [ ] CSRF em todos os formulários master (automático via middleware)
- [ ] Rate limiting no login master (automático via `LoginAttempt`)
- [ ] `session_regenerate_id()` no login master (automático via `AuthService`)
- [ ] Escape XSS com `e()` em todas as views migradas
- [ ] Sanitizar inputs de shell com `escapeshellarg()` + validação

### 🟠 Prioridade Alta (fazer logo após integração)

- [ ] Validar nomes de banco com regex antes de usar em SQL
- [ ] Validar caminhos de arquivo com `realpath()` + `str_starts_with()`
- [ ] Implementar `AdminLog` em toda ação destrutiva
- [ ] Security headers específicas para páginas master

### 🟡 Prioridade Média (Sprint seguinte)

- [ ] Re-autenticação para ações de alto risco
- [ ] Timeout de sessão reduzido para master (15min)
- [ ] IP whitelist para funcionalidades de sistema (exec)
- [ ] Retenção automática de logs (> 90 dias)

### 🟢 Prioridade Baixa (Backlog)

- [ ] 2FA completo para admins master
- [ ] Restrição por subdomínio
- [ ] CSP específica para master
- [ ] proc_open() substituindo exec()

---

## Matriz de Risco Residual Pós-Integração

| Risco | Antes | Após Integração | Após Segurança Completa |
|-------|-------|-----------------|------------------------|
| SQL Injection | 🟠 Alto | 🟡 Médio | 🟢 Baixo |
| XSS | 🟠 Alto | 🟢 Baixo | 🟢 Mínimo |
| CSRF | 🔴 Crítico | 🟢 Baixo | 🟢 Mínimo |
| Command Injection | 🔴 Crítico | 🟠 Alto | 🟢 Baixo |
| Brute Force | 🔴 Crítico | 🟢 Baixo | 🟢 Mínimo |
| Session Hijacking | 🟠 Alto | 🟢 Baixo | 🟢 Mínimo |
| Credential Exposure | 🔴 Crítico | 🟢 Baixo | 🟢 Mínimo |
| Path Traversal | 🟠 Alto | 🟠 Alto | 🟢 Baixo |
| Audit Trail | 🟡 Médio | 🟡 Médio | 🟢 Baixo |
