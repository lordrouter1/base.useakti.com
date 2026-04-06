# 06 — Bugs Críticos Encontrados

> **Data:** 06/04/2026
> **Escopo:** Bugs que impedem funcionamento ou comprometem segurança

---

## 🔴 BUG-001: Campo de senha incorreto no AuthService

**Severidade:** 🔴 CRÍTICO — Login master não funciona
**Arquivo:** `app/services/AuthService.php` (método `attemptMasterLogin()`)

### Problema

```php
// ERRADO — campo 'password_hash' NÃO EXISTE na tabela admin_users
if (!$admin || !password_verify($password, $admin['password_hash'])) {
    return null;
}
```

A coluna na tabela `admin_users` se chama `password` (não `password_hash`). O `SELECT *` do `findByEmail()` retorna `$admin['password']`.

### Evidência

Código original em `master/app/controllers/AuthController.php:40`:
```php
if (!$user || !password_verify($password, $user['password'])) {
```

Código do AdminUser model (`updatePassword`):
```php
$stmt = $this->db->prepare("UPDATE admin_users SET password = :password WHERE id = :id");
```

### Impacto

- **Login master completamente quebrado** — `$admin['password_hash']` retorna `null`, `password_verify()` sempre falha
- Master admin nunca consegue logar pelo fluxo unificado

### Fix

```php
// CORRETO
if (!$admin || !password_verify($password, $admin['password'])) {
    return null;
}
```

**Arquivo:** `app/services/AuthService.php`, linha ~192

---

## 🔴 BUG-002: Campo de senha incorreto no BackupController

**Severidade:** 🔴 CRÍTICO — Exclusão de backup falha
**Arquivo:** `app/controllers/Master/BackupController.php` (método `delete()`)

### Problema

```php
// ERRADO — mesmo problema do BUG-001
if (!$admin || !password_verify($password, $admin['password_hash'])) {
    $this->json(['success' => false, 'message' => 'Senha de admin incorreta']);
}
```

### Evidência

O `ClientController.php` usa o campo correto:
```php
// CORRETO (ClientController.php:260)
if (!$admin || !password_verify($adminPassword, $admin['password'])) {
```

O código original do master usa o campo correto:
```php
// master/app/controllers/BackupController.php:159
if (!$admin || !password_verify($password, $admin['password'])) {
```

### Impacto

- **Exclusão de backup sempre falha** — administrador nunca consegue confirmar senha
- Exibe "Senha de admin incorreta" mesmo com senha correta

### Fix

```php
// CORRETO
if (!$admin || !password_verify($password, $admin['password'])) {
    $this->json(['success' => false, 'message' => 'Senha de admin incorreta']);
}
```

**Arquivo:** `app/controllers/Master/BackupController.php`, linha ~107

---

## 🔴 BUG-003: Bypass de segurança em rotas master_only

**Severidade:** 🔴 CRÍTICO — Qualquer usuário logado acessa páginas master
**Arquivo:** `app/core/Application.php` (método `handle()`)

### Problema

```php
// INSEGURO — não verifica se usuário é master admin
$routeConfig = $this->router->getRouteConfig($this->page);
if (!empty($routeConfig['master_only'])) {
    CsrfMiddleware::handle();
    Security::generateCsrfToken();
    return true;  // ← Permite acesso de QUALQUER usuário logado
}
```

O código verifica se a rota tem flag `master_only`, mas **não verifica se o usuário tem a flag `is_master_admin` na sessão**. Resultado: qualquer admin de tenant pode acessar URLs como `?page=master_dashboard`.

### Mitigação Parcial Existente

Os controllers chamam `requireMasterAuth()` no início de cada método, que bloqueia usuários não-master. Porém:
1. O controller é instanciado antes do bloqueio
2. A resposta de erro vem do controller (redirect/403) em vez de um bloqueio limpo no middleware
3. Se um controller esquecer de chamar `requireMasterAuth()`, a página fica aberta

### Fix

```php
$routeConfig = $this->router->getRouteConfig($this->page);
if (!empty($routeConfig['master_only'])) {
    if (empty($_SESSION['is_master_admin'])) {
        header('Location: ?page=login');
        exit;
    }
    CsrfMiddleware::handle();
    Security::generateCsrfToken();
    return true;
}
```

**Arquivo:** `app/core/Application.php`, dentro do método `handle()`

---

## Resumo

| ID | Bug | Severidade | Impacto | Dificuldade Fix |
|----|-----|-----------|---------|-----------------|
| BUG-001 | `password_hash` → `password` no AuthService | 🔴 | Login master quebrado | Trivial (1 linha) |
| BUG-002 | `password_hash` → `password` no BackupController | 🔴 | Exclusão backup quebrada | Trivial (1 linha) |
| BUG-003 | Falta verificação `is_master_admin` no Application | 🔴 | Bypass de segurança | Simples (4 linhas) |
