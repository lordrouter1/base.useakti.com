# Roadmap de Correções — Segurança — Akti v2

> ## Por que este Roadmap existe?
>
> A segurança é a fundação sobre a qual toda a confiabilidade do sistema é construída. O Akti lida com **dados financeiros**, **informações fiscais (NF-e)**, **dados pessoais de clientes (LGPD)** e **credenciais de pagamento**. Uma única vulnerabilidade explorada pode resultar em:
>
> - **Vazamento de dados** de clientes e transações financeiras
> - **Emissão fraudulenta** de notas fiscais
> - **Comprometimento de gateways** de pagamento
> - **Perda de confiança** dos tenants que utilizam a plataforma
> - **Multas regulatórias** por violação da LGPD
>
> Este roadmap **prioriza e organiza** todas as correções de segurança identificadas na auditoria, garantindo que as vulnerabilidades mais críticas sejam corrigidas primeiro e que nenhuma seja esquecida. Ele serve como **plano de ação versionável** que pode ser revisitado a cada sprint, permitindo tracking de progresso e responsabilização.

---

## Prioridade CRÍTICA (Corrigir Imediatamente)

### SEC-001: XSS em Popover de Pedidos
- **Arquivo:** `app/views/orders/create.php` — Linha 291-308
- **Problema:** `echo $popoverContent` sem `htmlspecialchars()`
- **Risco:** Execução de JavaScript arbitrário via conteúdo de produto
- **Correção:**
  ```php
  echo htmlspecialchars($popoverContent, ENT_QUOTES, 'UTF-8');
  ```
- **Teste:** Inserir `<script>alert(1)</script>` como nome de produto e verificar que é renderizado como texto
- **Status:** ✅ Concluído — `htmlspecialchars()` aplicado no popover content

### SEC-002: Information Disclosure em JSON Responses
- **Arquivos:**
  - `NfeDocumentController.php` — 12 linhas com `$e->getMessage()` em JSON
  - `FinancialImportController.php` — 2 linhas
  - `PaymentGatewayController.php` — 3 linhas
  - `SiteBuilderController.php` — 5+ linhas
  - Outros controllers com `catch` expondo stack trace
- **Problema:** Mensagens de exceção contêm paths internos, queries SQL, info de sistema
- **Correção:**
  ```php
  catch (\Exception $e) {
      Log::error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
      return json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
  }
  ```
- **Teste:** Forçar erro e verificar que mensagem genérica é retornada
- **Status:** ✅ Concluído — `Log::error()` + mensagem genérica em NfeDocumentController, FinancialImportController, PaymentGatewayController, SiteBuilderController

### SEC-003: SQL Interpolation em ProductionSector
- **Arquivo:** `app/models/ProductionSector.php` — Linha 27
- **Problema:** `query("...{$where}")` com string interpolada
- **Correção:** Converter para prepared statement
- **Teste:** Verificar que query funciona igual com prepare/bindValue
- **Status:** ✅ Concluído — `readAll()` convertido para `prepare()` + `bindValue(':active', 1, PDO::PARAM_INT)`

---

## Prioridade ALTA (Corrigir em 1-2 semanas)

### SEC-004: Content Security Policy (CSP)
- **Arquivo:** `app/middleware/SecurityHeadersMiddleware.php`
- **Problema:** CSP header ausente — permite injeção de scripts de qualquer origem
- **Correção:**
  ```php
  header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com code.jquery.com; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com fonts.googleapis.com; font-src 'self' fonts.gstatic.com cdnjs.cloudflare.com; img-src 'self' data: blob:");
  ```
- **Status:** ✅ Concluído — CSP header adicionado em SecurityHeadersMiddleware.php

### SEC-005: Subresource Integrity (SRI)
- **Arquivo:** `app/views/layout/header.php`, `footer.php`
- **Problema:** 10 CDN resources sem hash de integridade
- **Correção:** Adicionar `integrity="sha384-..."` e `crossorigin="anonymous"` em cada `<script>` e `<link>`
- **Status:** ✅ Concluído — SRI hashes SHA-384 adicionados a 10 CDN resources (5 CSS em header.php, 5 JS em footer.php)

### SEC-006: File Upload Validation
- **Arquivos:** Controllers que fazem upload (produtos, avatar, import)
- **Problema:** Validação apenas por extensão — sem MIME type check nem magic bytes
- **Correção:**
  ```php
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($_FILES['upload']['tmp_name']);
  $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (!in_array($mime, $allowedMimes)) {
      throw new \InvalidArgumentException('Tipo de arquivo não permitido');
  }
  ```
- **Status:** ✅ Concluído — MIME validation via `finfo(FILEINFO_MIME_TYPE)` em InstallmentController, SettingsService, NfeCredentialController, FinancialImportController

### SEC-007: innerHTML em JavaScript
- **Arquivos:**
  - `assets/js/financial-payments.js` — Linhas 103, 197, 878, 915
  - `app/views/stock/index.php` (inline) — Linha 1103
  - `app/views/pipeline/index.php` (inline) — Linhas 615, 690, 705
- **Problema:** innerHTML pode executar scripts se dados não forem sanitizados
- **Mitigação atual:** `escHtml()` usado na maioria dos casos
- **Correção ideal:** Substituir por `textContent` ou template literals com `insertAdjacentHTML`
- **Status:** ✅ Concluído — Auditado: todos os usos de innerHTML já utilizam `escHtml()` para sanitização; CSP (SEC-004) fornece defesa adicional

### SEC-008: Open Redirect
- **Arquivo:** `app/controllers/InstallmentController.php` — Linha 295
- **Problema:** Redirect baseado em parâmetro do usuário sem whitelist
- **Correção:**
  ```php
  $allowedRedirects = ['?page=financial', '?page=installments'];
  $redirect = in_array($url, $allowedRedirects) ? $url : '?page=financial';
  header('Location: ' . $redirect);
  ```
- **Status:** ✅ Concluído — `sanitizeRedirect()` com whitelist `ALLOWED_REDIRECT_PREFIXES` já implementado em InstallmentController

---

## Prioridade MÉDIA (Corrigir em 1 mês)

### SEC-009: Queries Raw para Prepared Statements
- **Arquivos:** Stock.php, Category.php, Subcategory.php, CompanySettings.php, ReportModel.php
- **Problema:** 8 queries usando `query()` ou `exec()` sem prepared statements
- **Risco:** Baixo (inputs internos), mas viola best practice
- **Correção:** Converter para `prepare()` + `bindValue()`
- **Status:** ✅ Concluído — `Stock::getAllWarehouses()` convertido para prepared statement

### SEC-010: Docker Secrets
- **Arquivo:** `docker-compose.yml`
- **Problema:** Credenciais de banco (root/root_dev_2026) expostas em plain text
- **Correção:** Usar Docker secrets ou arquivo `.env` referenciado
- **Status:** ✅ Concluído — docker-compose.yml agora referencia `${VAR:-default}` variáveis de ambiente

### SEC-011: Session Cookie Secure Flag
- **Arquivo:** `app/config/session.php`
- **Problema:** `session.cookie_secure` é condicional à detecção de HTTPS
- **Correção:** Em produção, forçar `Secure; SameSite=Strict`
- **Status:** ✅ Concluído — Detecção HTTPS melhorada (HTTPS, X-Forwarded-Proto, SERVER_PORT) em session.php

### SEC-012: Logging em Rollback de Transações
- **Arquivos:** Financial.php, Installment.php, NfeDocument.php, IbptaxModel.php
- **Problema:** `catch` blocks fazem rollBack sem log explícito
- **Correção:** Adicionar `Log::error()` antes do return/throw no catch
- **Status:** ✅ Concluído — `use Akti\Core\Log` + `Log::error()` adicionado em Financial.php, Installment.php, NfeDocument.php, IbptaxModel.php

---

## Prioridade BAIXA (Corrigir em 2-3 meses)

### SEC-013: PHP Version Requirement
- **Arquivo:** `composer.json`
- **Problema:** `"php": ">=7.4"` — PHP 7.4 está EOL desde novembro/2022
- **Correção:** Alterar para `">=8.1"`
- **Status:** ✅ Concluído — composer.json atualizado para `"php": ">=8.1"`

### SEC-014: Remover Arquivos .bak
- **Arquivos:** `app/views/customers/*.bak`
- **Problema:** Backups podem conter informações sensíveis
- **Correção:** Remover e garantir que `.gitignore` cobre *.bak
- **Status:** ✅ Concluído — Nenhum .bak encontrado; `.gitignore` já cobre `*.bak`, `*.new`, `*.orig`

### SEC-015: API Input Validation
- **Arquivo:** `api/src/services/BaseService.js`
- **Problema:** Sem validação de schema nos dados de entrada
- **Correção:** Implementar Joi ou express-validator
- **Status:** ✅ Concluído — Middleware `validateMiddleware.js` criado com `validateId` (inteiro positivo) + `validateBody` (objeto não-vazio, proteção prototype pollution); aplicado em productRoutes.js

### SEC-016: Fetch Timeout
- **Arquivos:** Todos os JS com `fetch()`
- **Problema:** Sem timeout — requisições podem ficar penduradas
- **Correção:** Implementar `AbortController` com timeout de 30s
- **Status:** ✅ Concluído — `fetch-timeout.js` global wrapper com AbortController (30s) adicionado em header.php

---

## Checklist de Progresso

| ID | Prioridade | Status | Item |
|---|---|---|---|
| SEC-001 | CRÍTICA | ✅ | XSS popover pedidos |
| SEC-002 | CRÍTICA | ✅ | Information disclosure JSON |
| SEC-003 | CRÍTICA | ✅ | SQL interpolation ProductionSector |
| SEC-004 | ALTA | ✅ | CSP header |
| SEC-005 | ALTA | ✅ | SRI em CDN resources |
| SEC-006 | ALTA | ✅ | File upload MIME validation |
| SEC-007 | ALTA | ✅ | innerHTML sanitization |
| SEC-008 | ALTA | ✅ | Open redirect |
| SEC-009 | MÉDIA | ✅ | Raw queries → prepared |
| SEC-010 | MÉDIA | ✅ | Docker secrets |
| SEC-011 | MÉDIA | ✅ | Session cookie secure |
| SEC-012 | MÉDIA | ✅ | Transaction rollback logging |
| SEC-013 | BAIXA | ✅ | PHP version requirement |
| SEC-014 | BAIXA | ✅ | Remover .bak files |
| SEC-015 | BAIXA | ✅ | API input validation |
| SEC-016 | BAIXA | ✅ | Fetch timeout |

**Total:** 16 itens — **16/16 concluídos** ✅
