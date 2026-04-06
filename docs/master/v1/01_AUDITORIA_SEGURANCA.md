# Auditoria de Segurança — Akti Master v1

> **Data da Auditoria:** 06/04/2026  
> **Escopo:** Segurança do painel administrativo Master  
> **Classificação:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## 1. Resumo Executivo

O sistema Master possui **boas práticas parciais** de segurança (prepared statements na maioria dos models, bcrypt para senhas, verificação de sessão), mas apresenta **vulnerabilidades críticas** que precisam de correção imediata, especialmente considerando que este painel controla infraestrutura sensível (bancos de dados, git, backups).

| Aspecto | Nota | Status |
|---------|------|--------|
| Autenticação | 5/10 | ⚠️ |
| CSRF | 2/10 | ❌ |
| SQL Injection | 6/10 | ⚠️ |
| XSS | 7/10 | ⚠️ |
| Configuração | 4/10 | ❌ |
| Command Injection | 6/10 | ⚠️ |
| Session Management | 5/10 | ⚠️ |

---

## 2. Credenciais Hardcoded — CRÍTICO

**Arquivo:** `master/app/config/config.php:10-12`

```php
define('DB_USER', 'akti_master_user');
define('DB_PASS', '%7m5ns8d$UJe');
```

**Problema:** Credenciais de banco de dados diretamente no código-fonte, versionadas no Git.

**Risco:** Qualquer pessoa com acesso ao repositório (funcionários, CI/CD, backups) pode obter as credenciais master que controlam TODOS os bancos de dados de todos os tenants.

**Correção:**
```php
// Carregar de variável de ambiente ou arquivo .env fora do versionamento
define('DB_USER', getenv('AKTI_DB_USER') ?: 'fallback_user');
define('DB_PASS', getenv('AKTI_DB_PASS') ?: '');
```
Adicionar `master/app/config/config.php` ao `.gitignore` e criar `config.example.php`.

---

## 3. CSRF — Ausência Total — CRÍTICO

**Arquivos afetados:** Todos os formulários e requisições AJAX

**Problema:** Nenhum formulário no Master possui token CSRF. Nenhuma requisição AJAX envia header `X-CSRF-TOKEN`. O sistema principal Akti possui CSRF completo (`csrf_field()`, middleware), mas o Master não implementa nenhuma proteção.

**Evidências:**
- `master/app/views/auth/login.php` — form sem CSRF token
- `master/app/views/clients/create.php` — form sem CSRF token
- `master/app/views/migrations/index.php` — form de SQL sem CSRF token
- AJAX em `git/index.php`, `backup/index.php` — sem header CSRF

**Risco:** Um atacante pode forjar requisições como o admin logado:
- Criar/excluir clientes
- Executar SQL arbitrário em todos os bancos de dados
- Force-reset repositórios Git
- Excluir backups

**Correção:** Implementar middleware CSRF similar ao sistema principal:
```php
// No index.php do Master
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validar em toda action POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('CSRF token inválido');
    }
}
```

---

## 4. SQL Injection — Interpolação Direta — ALTO

**Arquivos afetados:**
- `master/app/models/Migration.php:76` — `SHOW FULL COLUMNS FROM \`{$table}\``
- `master/app/models/TenantClient.php:218` — `CREATE DATABASE IF NOT EXISTS \`{$dbName}\``
- `master/app/models/TenantClient.php:222` — `CREATE USER IF NOT EXISTS '{$dbUser}'@'{$dbHost}'`
- `master/app/models/TenantClient.php:307` — `DROP DATABASE IF EXISTS \`{$dbName}\``
- `master/app/models/TenantClient.php:313` — `DROP USER IF EXISTS '{$dbUser}'@'{$dbHost}'`

**Problema:** Nomes de bancos, tabelas e usuários são interpolados diretamente em queries SQL. Embora estes valores venham do banco de dados master (não do input direto do usuário), a prática é insegura.

**Risco:** Se um registro de `tenant_clients` for corrompido ou manipulado, pode permitir injeção SQL com privilégios de root.

**Correção:** Validar com regex whitelist antes de usar:
```php
if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
    throw new \InvalidArgumentException('Nome de banco inválido');
}
```

---

## 5. Login — Sem Rate Limiting — ALTO

**Arquivo:** `master/app/controllers/AuthController.php:27-43`

**Problema:** O login não possui:
- Rate limiting (tentativas ilimitadas)
- Bloqueio por IP após falhas
- CAPTCHA após N falhas
- Delay progressivo

**Risco:** Ataque de brute force pode ser executado sem restrição. O sistema Akti principal possui `LoginAttempt` com bloqueio após 5 falhas — o Master não usa nada.

**Correção:** Implementar rate limiting similar ao sistema principal ou pelo menos:
```php
// Verificar tentativas recentes por IP
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $this->db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = :ip AND attempted_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
$stmt->execute(['ip' => $ip]);
if ($stmt->fetchColumn() >= 5) {
    $_SESSION['login_error'] = 'Muitas tentativas. Tente novamente em 30 minutos.';
    header('Location: ?page=login');
    exit;
}
```

---

## 6. Session Management — MÉDIO

**Arquivo:** `master/index.php:6`

**Problemas:**
1. `session_start()` sem configurar flags de segurança
2. Session name customizado definido em `config.php` (`SESSION_NAME`) mas não usado
3. Session ID não é regenerado após login
4. Sem timeout de inatividade implementado
5. Sem flag `httponly`, `samesite`, `secure`

**Correção:**
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_secure', 1); // em produção com HTTPS
ini_set('session.use_strict_mode', 1);
session_name(SESSION_NAME);
session_start();

// Após login bem-sucedido:
session_regenerate_id(true);

// Timeout de inatividade:
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
    session_destroy();
    header('Location: ?page=login');
    exit;
}
$_SESSION['last_activity'] = time();
```

---

## 7. XSS — Parcialmente Protegido — MÉDIO

**Status:** A maioria das views usa `htmlspecialchars()` para escape de dados. No entanto:

**Problemas encontrados:**
- Algumas views usam `echo $variavel` sem escape em contextos HTML
- Não há função helper centralizada `e()` como no sistema principal
- Dados de log e output de comandos são exibidos sem sanitização em algumas views

**Exemplo vulnerável:** `master/app/views/git/index.php` — output de git commands exibido diretamente

**Correção:** Criar helper `e()` no Master ou importar do sistema principal:
```php
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
```

---

## 8. Command Injection — MÉDIO

**Arquivos:**
- `master/app/models/GitVersion.php` — Executa comandos git via `exec()`
- `master/app/models/Backup.php` — Executa `sudo /bin/bkp` via `exec()`
- `master/app/models/TenantClient.php` — Executa `mysqldump | mysql` via `exec()`

**Status:** O `GitController::resolveRepoPath()` usa `basename()` para sanitizar o nome do repo — **boa prática**. O `Backup.php` usa comando fixo. O `TenantClient.php` usa `escapeshellarg()` — **boa prática**.

**Risco residual:** Nomes de BD são interpolados na construção de comandos antes do `escapeshellarg()`. Validar com whitelist antes.

---

## 9. HTTP Headers — BAIXO

**Arquivo:** `master/docs/nginx.conf`

**Status:** O nginx.conf de referência possui:
- ✅ SSL/TLS com Let's Encrypt
- ✅ Bloqueio de arquivos sensíveis (.sql, .env, .git)
- ❌ Falta `X-Frame-Options: DENY`
- ❌ Falta `X-Content-Type-Options: nosniff`
- ❌ Falta `Content-Security-Policy`
- ❌ Falta `Strict-Transport-Security` (HSTS)

**Correção:** Adicionar ao nginx.conf:
```nginx
add_header X-Frame-Options "DENY" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; font-src 'self' cdnjs.cloudflare.com" always;
```

---

## 10. .htaccess — INFORMATIVO

**Arquivo:** `master/.htaccess` — VAZIO

**Problema:** Para deploy em Apache (XAMPP local), o `.htaccess` deveria proteger pastas sensíveis. Em produção com Nginx, o nginx.conf cuida disso.

**Correção para Apache:**
```apache
RewriteEngine On
RewriteBase /master/

# Bloquear acesso direto a app/, config/, models/
RewriteRule ^app/ - [F,L]

# Bloquear arquivos sensíveis
<FilesMatch "\.(sql|env|md|log)$">
    Require all denied
</FilesMatch>
```

---

## 11. Exposição de Informações — MÉDIO

**Arquivo:** `master/app/config/database.php:15`

```php
die('Erro de conexão: ' . $e->getMessage());
```

**Problema:** Mensagem de erro do PDO pode expor host, porta, nome do banco em produção.

**Correção:**
```php
if (getenv('APP_ENV') === 'production') {
    error_log('DB Connection Error: ' . $e->getMessage());
    die('Erro interno. Contate o administrador.');
} else {
    die('Erro de conexão: ' . $e->getMessage());
}
```

---

## 12. Arquivos de Teste em Produção — ALTO

**Arquivos:**
- `master/_test_backup.php`
- `master/_test_git.php`
- `master/_write_backup_view.php`
- `master/reset_password.php`

**Problema:** Arquivos de teste/debug que não deveriam existir em produção. Podem expor informações sensíveis ou permitir operações não autorizadas.

**Correção:** Remover antes do deploy ou proteger com autenticação.
