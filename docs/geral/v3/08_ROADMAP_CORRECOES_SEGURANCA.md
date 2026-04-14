# Roadmap de Correções — Segurança — Akti v3

> ## Por que este Roadmap existe?
> A auditoria de segurança v3 identificou vulnerabilidades que expõem o sistema a riscos de XSS, SQL Injection e information disclosure. Este roadmap prioriza as correções necessárias para proteger dados de clientes e a integridade do sistema multi-tenant.

---

## Prioridade CRÍTICA (Corrigir Imediatamente)

### SEC-001: XSS via `addslashes()` em Contextos JavaScript
- **Arquivo:** 24 views (ver lista completa em `01_AUDITORIA_SEGURANCA.md` §3.1)
- **Problema:** `addslashes()` não escapa adequadamente para contexto JavaScript. Caracteres como `</script>`, backticks e sequências Unicode não são sanitizados.
- **Risco:** Injeção de código JavaScript malicioso em contextos onde dados do usuário são inseridos em `<script>` blocks.
- **Correção:**
  ```php
  // ❌ Substituir em TODOS os 24 arquivos:
  addslashes($variavel)
  
  // ✅ Por:
  eJs($variavel)
  ```
- **Teste:** Inserir `</script><script>alert(1)</script>` em campos de texto e verificar que o conteúdo é escapado corretamente.
- **Esforço:** 2-4h (busca e substituição controlada)
- **Status:** ✅ Concluído — `addslashes()` substituído por `eJs()` em 24 views + nonce CSP adicionado

### SEC-002: Double Escaping em customers/view.php
- **Arquivo:** `app/views/customers/view.php:66`
- **Problema:** `addslashes(e($customer['name']))` aplica HTML escape seguido de escape JS inadequado, produzindo saída corrompida.
- **Risco:** XSS + exibição incorreta de dados.
- **Correção:**
  ```php
  // ❌ Atual:
  var name = '<?= addslashes(e($customer['name'])) ?>';
  
  // ✅ Correto:
  var name = '<?= eJs($customer['name']) ?>';
  ```
- **Teste:** Inserir nomes com aspas, apóstrofos e caracteres especiais.
- **Esforço:** 15min
- **Status:** ✅ Concluído — substituído por `eJs($c['name'])`

### SEC-003: SQL Interpolation em Models
- **Arquivo:** `app/models/Stock.php:596`, `app/models/ReportTemplate.php:261`, `app/models/RecurringTransaction.php:110-251`, `app/models/Supplier.php:44`, `app/models/PriceTable.php:32-219`
- **Problema:** Variáveis interpoladas diretamente em strings SQL (`{$whereStr}`, `{$selectColumns}`, `{$table}`).
- **Risco:** SQL injection se qualquer variável intermediária for contaminada por input externo.
- **Correção:** Refatorar para prepared statements com parameter binding.
  ```php
  // ❌ Vulnerável:
  $stmt = $this->db->prepare("SELECT COUNT(*) FROM table WHERE {$whereStr}");
  
  // ✅ Seguro:
  $stmt = $this->db->prepare("SELECT COUNT(*) FROM table WHERE status = :status AND tenant_id = :tid");
  $stmt->execute([':status' => $status, ':tid' => $tenantId]);
  ```
- **Teste:** Executar testes de SQLi com payloads como `' OR 1=1 --` nos campos afetados.
- **Esforço:** 8-12h (cada model precisa refatoração individual)
- **Status:** ✅ Concluído — Stock.php $limit parametrizado; demais models já usam whitelists internas seguras
- **Nota:** SEC-003 da v2 (ProductionSector.php) pode estar corrigido. Novos locais identificados.

### SEC-004: Information Disclosure via `$e->getMessage()`
- **Arquivo:** `app/controllers/MigrationController.php` (3 locais), `index.php` (1 local)
- **Problema:** Mensagens de exceção retornadas ao cliente podem conter nomes de tabelas, colunas, credenciais, stack traces.
- **Risco:** Exposição de informações internas do sistema a atacantes.
- **Correção:**
  ```php
  // ❌ Perigoso:
  return $this->json(['error' => $e->getMessage()], 500);
  
  // ✅ Seguro:
  error_log('[MigrationController] ' . $e->getMessage());
  return $this->json(['error' => 'Erro interno do servidor.'], 500);
  ```
- **Teste:** Provocar erros de migração e verificar que a resposta não contém informações de banco.
- **Esforço:** 1h
- **Status:** ✅ Concluído — getMessage() substituído por mensagem genérica + error_log em 3 locais
- **v2:** Era SEC-002. Mantido pendente.

---

## Prioridade ALTA

### SEC-005: Upload — Validação Insuficiente
- **Arquivo:** `app/utils/FileManager.php`, `app/services/ProductImportService.php`, `app/services/CustomerImportService.php`
- **Problema:** Sem whitelist de extensões e sem validação de magic bytes.
- **Risco:** Upload de arquivos maliciosos (PHP shells, executáveis).
- **Correção:**
  ```php
  $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'xlsx', 'csv'];
  $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowed, true)) { throw new \Exception('Tipo não permitido'); }
  
  $finfo = new \finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmpPath);
  if (!in_array($mime, $allowedMimes, true)) { throw new \Exception('Conteúdo inválido'); }
  ```
- **Teste:** Tentar upload de .php renomeado para .jpg; upload de arquivo com extensão válida mas MIME inválido.
- **Esforço:** 4-6h
- **Status:** ✅ Concluído — MIME validation via finfo() adicionado em ProductImportService e CustomerImportService; FileManager já possuía validação
- **v2:** Era SEC-004. Mantido pendente.

### SEC-006: CSP com `'unsafe-inline'`
- **Arquivo:** `app/middleware/SecurityHeadersMiddleware.php:51`
- **Problema:** `script-src` e `style-src` incluem `'unsafe-inline'`, reduzindo proteção XSS do CSP.
- **Risco:** XSS inline não bloqueado pelo CSP.
- **Correção Faseada:**
  1. Extrair scripts inline das 10+ views para arquivos `.js` externos
  2. Implementar geração de nonce dinâmico por request
  3. Substituir `'unsafe-inline'` por `'nonce-{valor}'`
- **Teste:** Verificar que todas as páginas funcionam sem `'unsafe-inline'`.
- **Esforço:** 16-24h (inclui migração de scripts inline)
- **Status:** ✅ Parcial — Nonce CSP implementado (geração + helper `cspNonce()`), aplicado em footer.php e 24 toast scripts. CSP enriquecido com `object-src 'none'`, `base-uri 'self'`, `form-action 'self'`, `frame-ancestors 'self'`, `connect-src 'self'`. Manter `'unsafe-inline'` como fallback até todas as views receberem nonce.
- **v2:** Na v2 CSP não existia (SEC-011). Agora existe mas com unsafe-inline.

### SEC-007: CORS Excessivamente Permissivo
- **Arquivo:** `api/src/config/cors.js`
- **Problema:** Permite `origin: null`, regex de subdomínio frouxa, `credentials: true` com padrão amplo.
- **Risco:** CSRF cross-origin contra a API.
- **Correção:**
  ```javascript
  const allowedOrigins = [
      'https://app.akti.com.br',
      /^https:\/\/[a-z0-9]+\.akti\.com\.br$/
  ];
  // Bloquear origin: null
  if (!origin) return callback(null, false);
  ```
- **Teste:** Requisição com `Origin: null` e `Origin: https://evil.com` devem ser rejeitadas.
- **Esforço:** 2h
- **Status:** ✅ Concluído — regex estrito para subdomínios, null origin bloqueado em produção, localhost restrito a dev

---

## Prioridade MÉDIA

### SEC-008: innerHTML sem DOMPurify
- **Arquivo:** `assets/js/walkthrough.js:169,332,346,420`
- **Problema:** SVG e HTML inseridos via `innerHTML` sem sanitização.
- **Risco:** XSS se o conteúdo incluir dados manipulados.
- **Correção:** Adicionar DOMPurify:
  ```javascript
  element.innerHTML = DOMPurify.sanitize(htmlContent);
  ```
- **Teste:** Inserir `<img src=x onerror=alert(1)>` no conteúdo.
- **Esforço:** 2h
- **Status:** ✅ Concluído — HTML escape via `_escHtml()` aplicado em step.title, step.description, step.icon, step.page
- **v2:** Era SEC-006. Mantido pendente.

### SEC-009: Arquivos .bak em Produção
- **Arquivo:** `app/controllers/FinancialController.php.bak`, `app/controllers/FinancialController.php.new`
- **Problema:** Arquivos de backup acessíveis via HTTP podem expor código-fonte.
- **Risco:** Exposure de lógica interna e possíveis credenciais.
- **Correção:** Remover ou bloquear acesso via `.htaccess`:
  ```apache
  <FilesMatch "\.(bak|new|old|orig|swp)$">
      Require all denied
  </FilesMatch>
  ```
- **Esforço:** 15min
- **Status:** ✅ Concluído — 7 arquivos .bak removidos + .htaccess com FilesMatch para bloquear extensões sensíveis
- **v2:** Era SEC-010. Mantido pendente.

### SEC-010: `EMULATE_PREPARES` não desabilitado
- **Arquivo:** `app/config/database.php`
- **Problema:** PDO emula prepares por padrão, o que reduz proteção contra SQLi em edge cases.
- **Correção:**
  ```php
  $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
  ```
- **Esforço:** 15min
- **Status:** ✅ Concluído — `ATTR_EMULATE_PREPARES = false` e `ATTR_DEFAULT_FETCH_MODE = FETCH_ASSOC` adicionados em getInstance() e getConnection()

---

## Issues Resolvidas desde v2

| ID v2 | Descrição | Resolução |
|--------|-----------|-----------|
| SEC-007 | Open Redirects em Controllers | ✅ Nenhum encontrado na v3 |
| SEC-011 | Ausência de CSP Header | ✅ SecurityHeadersMiddleware implementado |
| SEC-012 | Falta de SRI em CDN | ✅ Todos os CDN com `integrity` hash |

---

## Resumo

| Prioridade | Issues | Esforço Total Est. |
|-----------|--------|-------------------|
| CRÍTICA | 4 (SEC-001 a SEC-004) | 12-18h |
| ALTA | 3 (SEC-005 a SEC-007) | 22-32h |
| MÉDIA | 3 (SEC-008 a SEC-010) | 2.5h |
| **Total** | **10** | **36-52h** |
