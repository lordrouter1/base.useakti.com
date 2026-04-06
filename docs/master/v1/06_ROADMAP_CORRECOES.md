# Roadmap de Correções — Akti Master v1

> **Este roadmap prioriza as correções de segurança, arquitetura e banco de dados identificadas na auditoria.**

---

## Prioridade CRÍTICA — Corrigir Imediatamente

### SEC-001: Credenciais Hardcoded no Código-Fonte
- **Arquivo:** `master/app/config/config.php:10-12`
- **Problema:** Senha do banco de dados (`%7m5ns8d$UJe`) em texto plano, versionada no Git
- **Risco:** Comprometimento total de todos os bancos de dados de todos os tenants
- **Correção:**
  1. Criar `config.env.php` fora do versionamento (`.gitignore`)
  2. Carregar credenciais de variáveis de ambiente ou arquivo externo
  3. Remover credenciais do histórico git (`git filter-branch` ou BFG)
  4. Rotacionar a senha imediatamente em produção
- **Teste:** Verificar que `git log -p -- master/app/config/config.php` não mostra senhas
- **Status:** ⬜ Pendente

### SEC-002: Ausência Total de CSRF
- **Arquivos:** Todos os formulários e requisições AJAX do Master
- **Problema:** Nenhum token CSRF implementado em nenhum formulário ou AJAX
- **Risco:** Atacante pode forjar requisições como admin: executar SQL em todos os BDs, excluir clientes, force-reset Git
- **Correção:**
  1. Gerar token CSRF na sessão: `$_SESSION['csrf_token'] = bin2hex(random_bytes(32))`
  2. Adicionar campo hidden em todos os forms: `<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">`
  3. Validar no `index.php` para toda requisição POST
  4. Adicionar header X-CSRF-TOKEN em todas as chamadas AJAX
- **Teste:** Enviar POST sem token → deve retornar 403
- **Status:** ⬜ Pendente

### SEC-003: SQL Injection em DDL Queries
- **Arquivos:** `master/app/models/TenantClient.php:218,222,307,313`, `master/app/models/Migration.php:76`
- **Problema:** Nomes de banco, tabela e usuário interpolados diretamente em queries
- **Risco:** Se dados do `tenant_clients` forem manipulados, permite injeção SQL com privilégios root
- **Correção:** Adicionar validação whitelist antes de usar em queries:
  ```php
  private function validateIdentifier($name) {
      if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
          throw new \InvalidArgumentException("Identificador inválido: {$name}");
      }
      return $name;
  }
  ```
- **Teste:** Tentar criar cliente com subdomain contendo caracteres especiais → deve rejeitar
- **Status:** ⬜ Pendente

### SEC-004: Login Sem Rate Limiting
- **Arquivo:** `master/app/controllers/AuthController.php`
- **Problema:** Tentativas ilimitadas de login, sem bloqueio por IP, sem CAPTCHA
- **Risco:** Brute force da senha do admin master
- **Correção:**
  1. Criar tabela `master_login_attempts` (ip, email, attempted_at)
  2. Bloquear após 5 tentativas em 30 minutos
  3. Registrar tentativa falhada com IP
- **Teste:** Fazer 6 tentativas incorretas → 6ª deve ser bloqueada
- **Status:** ⬜ Pendente

---

## Prioridade ALTA — Corrigir Esta Semana

### SEC-005: Session Management Inseguro
- **Arquivo:** `master/index.php:6`
- **Problema:** `session_start()` sem flags de segurança, sem regeneração de ID após login, sem timeout
- **Correção:**
  ```php
  ini_set('session.cookie_httponly', 1);
  ini_set('session.cookie_samesite', 'Strict');
  ini_set('session.use_strict_mode', 1);
  session_name(SESSION_NAME);
  session_start();
  ```
  E no `AuthController::authenticate()`: `session_regenerate_id(true);`
- **Status:** ⬜ Pendente

### SEC-006: Arquivos de Teste em Produção
- **Arquivos:** `master/_test_backup.php`, `master/_test_git.php`, `master/_write_backup_view.php`, `master/reset_password.php`
- **Problema:** Arquivos de debug/teste que podem expor informações sensíveis
- **Correção:** Remover antes do deploy ou proteger com verificação de sessão
- **Status:** ⬜ Pendente

### SEC-007: Exposição de Erros PDO
- **Arquivo:** `master/app/config/database.php:15`
- **Problema:** `die('Erro de conexão: ' . $e->getMessage())` expõe detalhes de infraestrutura
- **Correção:** Logar erro e mostrar mensagem genérica em produção
- **Status:** ⬜ Pendente

### ARCH-001: Autoloader com require_once Manual
- **Arquivo:** `master/index.php:8-28`
- **Problema:** 16 `require_once` manuais, sem namespaces
- **Correção:** Implementar autoloader PSR-4 ou registrar namespace no composer.json
- **Status:** ⬜ Pendente

### DB-004: Senha de BD do Tenant em Texto Plano
- **Tabela:** `tenant_clients.db_password`
- **Problema:** Senhas de banco armazenadas sem criptografia
- **Correção:** Criptografar com `openssl_encrypt()` (AES-256-CBC) usando chave em variável de ambiente
- **Status:** ⬜ Pendente

---

## Prioridade MÉDIA — Corrigir Este Mês

### ARCH-002: Router Switch/Case Monolítico
- **Arquivo:** `master/index.php`
- **Problema:** 170+ linhas de switch/case aninhado
- **Correção:** Implementar router declarativo baseado em array
- **Status:** ⬜ Pendente

### FE-001: Dependência Total de CDNs
- **Arquivo:** `master/app/views/layout/header.php`
- **Problema:** Todos os assets (Bootstrap, jQuery, FA, SweetAlert2) de CDNs externos
- **Correção:** Manter CDN + fallback local
- **Status:** ⬜ Pendente

### FE-002: JS Inline Extenso nas Views
- **Arquivos:** Views de git, backup, clients, migrations
- **Problema:** Centenas de linhas de JS inline
- **Correção:** Extrair para arquivos JS por módulo
- **Status:** ⬜ Pendente

### DB-005: Falta Índice em admin_logs.created_at
- **Tabela:** `admin_logs`
- **Correção:** `ALTER TABLE admin_logs ADD INDEX idx_created_at (created_at);`
- **Status:** ⬜ Pendente

### SEC-008: HTTP Headers de Segurança
- **Arquivo:** `master/docs/nginx.conf`
- **Problema:** Faltam X-Frame-Options, HSTS, CSP, X-Content-Type-Options
- **Correção:** Adicionar headers no nginx.conf
- **Status:** ⬜ Pendente

### SEC-009: .htaccess Vazio
- **Arquivo:** `master/.htaccess`
- **Correção:** Adicionar regras de proteção para Apache (dev local)
- **Status:** ⬜ Pendente

---

## Prioridade BAIXA — Backlog

### FE-003: Dark Mode
- Implementar dark mode com variáveis CSS
- **Status:** ⬜ Pendente

### FE-004: Indicador de Ambiente
- Exibir "DEV" ou "PROD" no header para evitar ações acidentais
- **Status:** ⬜ Pendente

### FE-005: Breadcrumbs
- Navegação em sub-páginas
- **Status:** ⬜ Pendente

### CS-001 a CS-007: Code Smells
- Decompor views grandes em partials
- Refatorar GitVersion.php em classes menores
- **Status:** ⬜ Pendente

---

## Resumo de Contagem

| Severidade | Quantidade |
|-----------|-----------|
| CRÍTICO | 4 |
| ALTO | 8 |
| MÉDIO | 6 |
| BAIXO | 5+ |
| **Total** | **23+** |
