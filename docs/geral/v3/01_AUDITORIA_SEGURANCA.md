# Auditoria de Segurança — Akti v3

> **Data da Auditoria:** 14/04/2025
> **Escopo:** Análise estática de segurança (CSRF, XSS, SQLi, uploads, headers, auth, API)
> **Auditor:** Auditoria Automatizada via Análise Estática de Código
> **Classificação de Severidade:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## 1. Resumo Executivo

| Aspecto | Nota | Tendência vs v2 |
|---------|------|------------------|
| CSRF | ✅ A | = Mantido |
| XSS | ❌ D | ↓ Piorado (mais arquivos afetados) |
| SQL Injection | ⚠️ C+ | = Mantido |
| Information Disclosure | ⚠️ C | = Mantido |
| Upload / File Security | ⚠️ C | = Mantido |
| Session Security | ✅ A | ↑ Melhorado |
| HTTP Headers | ⚠️ B- | ↑ Melhorado (CSP adicionado) |
| Rate Limiting / Brute Force | ✅ A | = Mantido |
| Autenticação | ✅ A | = Mantido |
| API Security (Node.js) | ⚠️ C+ | = Mantido |
| Dependências | ✅ B+ | = Mantido |

**Nota Geral: C+** (v2: D+)

O sistema evoluiu significativamente em infraestrutura de segurança (CSP headers, SRI, BaseController com auth helpers), mas mantém vulnerabilidades XSS críticas em views que usam `addslashes()` para contexto JavaScript, e expõe informações sensíveis via `$e->getMessage()`.

---

## 2. CSRF Protection

### Status: ✅ Aprovado

**Implementação:**
- Token gerado via `csrf_token()` em `app/utils/helpers.php`
- Validação com `hash_equals()` — timing-safe comparison
- Grace period configurável para evitar falsos positivos
- Middleware `CsrfMiddleware` aplicado globalmente
- Helper `csrf_field()` para formulários HTML
- Header `X-CSRF-TOKEN` para requisições AJAX (via `$.ajaxSetup`)

**Evidências:**
- `app/middleware/CsrfMiddleware.php` — validação centralizada
- `app/utils/helpers.php` — funções `csrf_token()`, `csrf_field()`
- `app/views/layout/footer.php` — setup global do header AJAX

**Vulnerabilidades:** Nenhuma encontrada.

---

## 3. XSS (Cross-Site Scripting)

### Status: ❌ CRÍTICO — 24 arquivos afetados

#### 3.1 SEC-001: `addslashes()` em Contextos JavaScript (CRÍTICO)

**Problema:** 24 arquivos de view usam `addslashes()` para inserir dados PHP em contextos JavaScript. A função `addslashes()` **não é adequada** para escape em contexto JS — ela não escapa caracteres como `</script>`, backticks, ou sequências Unicode.

**Arquivos Afetados:**
| # | Arquivo | Contexto |
|---|---------|----------|
| 1 | `app/views/workflows/index.php` | Variáveis em script inline |
| 2 | `app/views/email_marketing/campaigns.php` | Dados de campanha |
| 3 | `app/views/email_marketing/compose.php` | Conteúdo de email |
| 4 | `app/views/custom_reports/form.php` | Configuração de relatório |
| 5 | `app/views/custom_reports/results.php` | Dados de resultado |
| 6 | `app/views/supplies/index.php` | Lista de insumos |
| 7 | `app/views/supplies/form.php` | Formulário |
| 8 | `app/views/suppliers/index.php` | Lista de fornecedores |
| 9 | `app/views/suppliers/form.php` | Formulário |
| 10 | `app/views/quotes/index.php` | Lista de orçamentos |
| 11 | `app/views/quotes/form.php` | Formulário |
| 12 | `app/views/quality/index.php` | Lista de qualidade |
| 13 | `app/views/quality/form.php` | Formulário |
| 14 | `app/views/commissions/index.php` | Lista de comissões |
| 15 | `app/views/commissions/form.php` | Formulário |
| 16 | `app/views/calendar/index.php` | Eventos de calendário |
| 17 | `app/views/financial/installments.php` | Parcelas |
| 18 | `app/views/financial/recurring.php` | Transações recorrentes |
| 19 | `app/views/financial/accounts.php` | Contas financeiras |
| 20 | `app/views/financial/categories.php` | Categorias financeiras |
| 21 | `app/views/financial/cost_centers.php` | Centros de custo |
| 22 | `app/views/financial/import.php` | Importação OFX |
| 23 | `app/views/financial/payment_methods.php` | Métodos de pagamento |
| 24 | `app/views/customers/view.php` | Detalhes do cliente |

**Correção:** Substituir `addslashes($var)` por `eJs($var)` (helper já existente em `app/utils/helpers.php`).

```php
// ❌ VULNERÁVEL
var name = '<?= addslashes($customer['name']) ?>';

// ✅ SEGURO
var name = '<?= eJs($customer['name']) ?>';
```

#### 3.2 SEC-002: Double Escaping em customers/view.php (CRÍTICO)

**Arquivo:** `app/views/customers/view.php:66`
**Problema:** `addslashes(e($customer['name']))` — aplica HTML escape seguido de JS escape inadequado, criando saída corrompida e potencialmente explorável.

**Correção:** Usar apenas `eJs()`:
```php
// ❌ VULNERÁVEL
var name = '<?= addslashes(e($customer['name'])) ?>';

// ✅ SEGURO
var name = '<?= eJs($customer['name']) ?>';
```

#### 3.3 Escape Helpers Disponíveis

O sistema possui helpers de escape completos:
- `e($val)` — HTML escape (`htmlspecialchars`)
- `eAttr($val)` — Atributos HTML
- `eJs($val)` — Contexto JavaScript (json_encode + opções seguras)
- `eNum($val)` — Numérico (intval/floatval)
- `eUrl($val)` — URLs (`urlencode`)

**O problema não é a ausência de helpers, mas o uso incorreto de `addslashes()` em vez dos helpers.**

---

## 4. SQL Injection

### Status: ⚠️ ALTO — 5-8 models com interpolação

#### 4.1 SEC-003: Interpolação de Variáveis em SQL

**Arquivos com problemas confirmados:**

| Arquivo | Linha | Código Problemático |
|---------|-------|---------------------|
| `app/models/Stock.php` | L596 | `"SELECT COUNT(*) FROM stock_movements sm WHERE {$whereStr}"` |
| `app/models/Stock.php` | L648-675 | 5x `query()` com SQL estático (BAIXO risco) |
| `app/models/ReportTemplate.php` | L261 | `"SELECT {$selectColumns} FROM \`{$table}\`{$joinClause}{$where}{$orderBy}"` |
| `app/models/RecurringTransaction.php` | L110-251 | `$db->query($sql)` com `{$sql}` construído dinamicamente |
| `app/models/Supplier.php` | L44 | `"SELECT * FROM suppliers {$where} ORDER BY..."` |
| `app/models/PriceTable.php` | L32-219 | Múltiplas queries com interpolação |

**Contexto:** A maioria dessas interpolações usa variáveis construídas internamente (não diretamente de `$_GET`/`$_POST`), mas o padrão é perigoso e deve ser corrigido para prepared statements.

**Correção recomendada:** Refatorar para usar parameter binding em todas as queries dinâmicas.

```php
// ❌ PERIGOSO
$sql = "SELECT * FROM suppliers {$where} ORDER BY name";
$stmt = $this->db->query($sql);

// ✅ SEGURO
$sql = "SELECT * FROM suppliers WHERE tenant_id = :tenant_id ORDER BY name";
$stmt = $this->db->prepare($sql);
$stmt->execute([':tenant_id' => $tenantId]);
```

---

## 5. Information Disclosure

### Status: ⚠️ CRÍTICO

#### 5.1 SEC-004: `$e->getMessage()` em JSON Responses

**Arquivos afetados:**
| Arquivo | Linhas | Contexto |
|---------|--------|----------|
| `app/controllers/MigrationController.php` | 3 locais | Erros de migração retornados ao cliente |
| `index.php` | 1 local | Modo de desenvolvimento |

**Risco:** Mensagens de exceção podem conter informações sensíveis: nomes de tabelas, colunas, credenciais de conexão, stack traces, caminhos de arquivo.

**Correção:**
```php
// ❌ PERIGOSO
return $this->json(['error' => $e->getMessage()], 500);

// ✅ SEGURO
error_log($e->getMessage()); // Log detalhado
return $this->json(['error' => 'Erro interno do servidor'], 500);
```

---

## 6. Upload / File Security

### Status: ⚠️ MÉDIO

#### 6.1 SEC-005: Validação Insuficiente de Upload

**Arquivos afetados:**
- `app/utils/FileManager.php`
- `app/services/ProductImportService.php`
- `app/services/CustomerImportService.php`

**Problemas encontrados:**
- Sem whitelist de extensões permitidas (apenas blacklist parcial)
- Sem validação de magic bytes (file signature)
- Tipo MIME confiado via `$_FILES['type']` (client-supplied, manipulável)

**Correção recomendada:**
```php
// Whitelist de extensões
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'xlsx', 'csv'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) { throw new \Exception('Tipo de arquivo não permitido'); }

// Validação de magic bytes
$finfo = new \finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpPath);
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
if (!in_array($mimeType, $allowedMimes)) { throw new \Exception('Conteúdo inválido'); }
```

---

## 7. Session Security

### Status: ✅ Aprovado

**Configuração em `app/config/session.php`:**
- ✅ `session.cookie_httponly = true`
- ✅ `session.cookie_samesite = Strict`
- ✅ `session.use_strict_mode = 1`
- ✅ `session.cookie_secure` condicional (proxy reverso)
- ✅ `session_regenerate_id()` após login (previne session fixation)
- ✅ Timeout de inatividade configurável por tenant

---

## 8. HTTP Security Headers

### Status: ⚠️ B- (Melhorado vs v2, mas com gaps)

**Implementado via `SecurityHeadersMiddleware.php`:**

| Header | Valor | Status |
|--------|-------|--------|
| `Content-Security-Policy` | `script-src 'self' 'unsafe-inline' ...` | ⚠️ `unsafe-inline` |
| `X-Frame-Options` | `DENY` | ✅ |
| `X-Content-Type-Options` | `nosniff` | ✅ |
| `X-XSS-Protection` | `1; mode=block` | ✅ |
| `Strict-Transport-Security` | Presente | ✅ |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | ✅ |

#### 8.1 SEC-006: CSP com `'unsafe-inline'` (ALTO)

**Arquivo:** `app/middleware/SecurityHeadersMiddleware.php:51`

**Problema:** O CSP inclui `'unsafe-inline'` tanto em `script-src` quanto em `style-src`, o que reduz significativamente a proteção contra XSS.

**Plano de Correção:** Migrar scripts inline para arquivos externos e usar nonces dinâmicos:
```
Content-Security-Policy: script-src 'self' 'nonce-{random}' https://cdn.jsdelivr.net; style-src 'self' 'nonce-{random}'
```

---

## 9. Rate Limiting / Brute Force

### Status: ✅ Aprovado

- `LoginAttempt` model rastreia tentativas falhadas por IP
- 3 falhas → reCAPTCHA ativado
- 5+ falhas → bloqueio de 30 minutos
- `IpGuard` middleware bloqueia IPs na blacklist
- Tabela `ip_blacklist` no master DB

---

## 10. Autenticação

### Status: ✅ Aprovado

- Senhas com `bcrypt` via `password_hash()` / `password_verify()`
- `must_change_password` flag para primeiro login
- Session regeneration após login
- Logout limpa completamente a sessão

---

## 11. API Security (Node.js)

### Status: ⚠️ C+

#### 11.1 SEC-007: CORS Excessivamente Permissivo (ALTO)

**Arquivo:** `api/src/config/cors.js`

**Problemas:**
- Permite `origin: null` (permite requisições de `file://` e redirects)
- Matching de subdomínio com regex frouxa (pode ser bypassado)
- `credentials: true` com padrão amplo = risco de CSRF cross-origin

**Correção:** Whitelist explícita de origens permitidas:
```javascript
const allowedOrigins = [
    'https://app.akti.com.br',
    /^https:\/\/[a-z0-9]+\.akti\.com\.br$/
];
```

#### 11.2 JWT e Rate Limiting

- ✅ JWT implementado para autenticação da API
- ✅ Token expiry configurado
- ⚠️ Rate limiting por IP (deveria incluir por usuário)

---

## 12. Subresource Integrity (SRI)

### Status: ✅ Aprovado (Novo em v3)

**Evolução:** Esta proteção **não existia na v2** e foi implementada.

Todos os recursos CDN incluem atributos `integrity` e `crossorigin`:
```html
<script src="https://cdn.jsdelivr.net/..." integrity="sha384-..." crossorigin="anonymous"></script>
```

---

## 13. Evolução vs. v2

### Issues Resolvidas desde v2

| ID v2 | Descrição | Status v3 |
|--------|-----------|-----------|
| SEC-007 | Open Redirects em múltiplos Controllers | ✅ Corrigido — nenhum encontrado |
| SEC-011 | Ausência de CSP Header | ✅ Corrigido — `SecurityHeadersMiddleware` adicionado |
| SEC-012 | Falta de SRI em CDN | ✅ Corrigido — todos os CDN com `integrity` |
| SEC-013 | Cookie Secure condicional | ✅ Mantido — aceito para ambientes sem HTTPS |

### Issues Mantidas (ainda pendentes)

| ID | Descrição | Severidade | Nota |
|----|-----------|-----------|------|
| SEC-001 | XSS via `addslashes()` em JS | 🔴 CRÍTICO | **Escalado**: 24 arquivos afetados (v2: identificado como popover) |
| SEC-002 | Information Disclosure (`$e->getMessage()`) | 🔴 CRÍTICO | Agora em `MigrationController` |
| SEC-003 | SQL Interpolation em models | 🟠 ALTO | 5-8 models afetados |
| SEC-004 | Upload validation insuficiente | 🟡 MÉDIO | Sem whitelist de extensões |
| SEC-005 | `addslashes()` em contexto JS | 🔴 CRÍTICO | **Merged com SEC-001** |
| SEC-006 | innerHTML sem DOMPurify | 🟡 MÉDIO | `walkthrough.js` principal afetado |

### Novas Issues (v3)

| ID | Descrição | Severidade |
|----|-----------|-----------|
| SEC-006-v3 | CSP com `unsafe-inline` | 🟠 ALTO |
| SEC-007-v3 | CORS excessivamente permissivo na API | 🟠 ALTO |

### Métricas Comparativas

| Métrica | v2 | v3 | Δ |
|---------|----|----|---|
| Issues CRÍTICAS | 3 | 3 | = |
| Issues ALTAS | 2 | 4 | +2 |
| Issues MÉDIAS | 7 | 2 | -5 |
| Issues BAIXAS | 2 | 0 | -2 |
| Total Issues | 13 | 9 | -4 |
| SRI Implementado | ❌ | ✅ | ↑ |
| CSP Header | ❌ | ✅ | ↑ |
| BaseController Auth | ❌ | ✅ | ↑ |

---

## 14. Resumo de Ações Prioritárias

1. **[CRÍTICO]** Substituir `addslashes()` por `eJs()` em 24 views → SEC-001
2. **[CRÍTICO]** Remover `$e->getMessage()` de respostas JSON → SEC-004
3. **[ALTO]** Refatorar SQL interpolation para prepared statements → SEC-003
4. **[ALTO]** Remover `'unsafe-inline'` do CSP com nonces → SEC-006-v3
5. **[ALTO]** Restringir CORS na API Node.js → SEC-007-v3
6. **[MÉDIO]** Adicionar whitelist + magic bytes em uploads → SEC-005
7. **[MÉDIO]** Adicionar DOMPurify para `walkthrough.js` innerHTML → SEC-006
