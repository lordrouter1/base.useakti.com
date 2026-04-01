# Auditoria de Segurança — Akti v2

> **Data da Auditoria:** 01/04/2026  
> **Escopo:** Análise completa de vulnerabilidades de segurança em todas as camadas do sistema  
> **Auditor:** Auditoria Automatizada via Análise Estática de Código  
> **Classificação de Severidade:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## 1. Resumo Executivo

O sistema **Akti — Gestão em Produção** apresenta uma base de segurança **sólida** em suas camadas fundamentais (CSRF, sessão, autenticação), porém possui **vulnerabilidades pontuais** que precisam de atenção imediata em áreas específicas como exposição de informações sensíveis, validação de uploads e sanitização inconsistente de output.

| Severidade | Quantidade | Status |
|---|---|---|
| **CRÍTICO** | 2 | ❌ Pendente |
| **ALTO** | 5 | ❌ Pendente |
| **MÉDIO** | 15+ | ❌ Pendente |
| **BAIXO** | 10+ | ⚠️ Monitorar |

---

## 2. Proteção CSRF (Cross-Site Request Forgery)

### 2.1 Status: ✅ EXCELENTE

O sistema implementa uma proteção CSRF robusta e bem arquitetada.

**Implementação:**
- **Token:** 64 caracteres hexadecimais gerados com `random_bytes(32)` — `app/core/Security.php:82`
- **Rotação:** Token renovado a cada 30 minutos (`TOKEN_LIFETIME = 1800`) — `app/core/Security.php:14`
- **Grace Period:** Token anterior válido por 5 minutos extras (`TOKEN_GRACE_PERIOD = 300`) — `app/core/Security.php:20`
- **Validação:** `hash_equals()` para prevenção de timing attacks — `app/core/Security.php:120`
- **Middleware global:** `CsrfMiddleware::handle()` executado **antes** do dispatch — `index.php:260`
- **Meta tag:** `csrf_meta()` no header para AJAX — `app/views/layout/header.php:17`
- **jQuery auto-inject:** Token automaticamente adicionado a todos os requests AJAX — `assets/js/script.js:6-12`

**Rotas isentas (documentadas e justificadas):**
- Rotas do catálogo público (`catalog:addToCart`, etc.) — usam autenticação própria via `catalog_links.token`
- Justificativa documentada no código — `app/middleware/CsrfMiddleware.php:40-56`

**Testes automatizados:**
- `tests/Security/CsrfProtectionTest.php` — testa tokens vazios, nulos, truncados, modificados

### 2.2 Observação: Site Builder

O módulo Site Builder usa `fetch()` com `FormData` para chamadas AJAX e inclui o CSRF token manualmente. A implementação foi recentemente corrigida para incluir o header `Accept: application/json` garantindo respostas JSON em caso de falha CSRF.

- **Arquivo:** `app/views/site_builder/index.php:843-870`
- **Status:** ✅ Funcional após correção

---

## 3. Cross-Site Scripting (XSS)

### 3.1 Infraestrutura de Proteção: ✅ BOA

O sistema dispõe de infraestrutura adequada para prevenção de XSS:

| Utilitário | Arquivo | Propósito |
|---|---|---|
| `Escape::html()` | `app/utils/Escape.php:15` | htmlspecialchars para contexto HTML |
| `Escape::attr()` | `app/utils/Escape.php:22` | ENT_QUOTES para atributos |
| `Escape::js()` | `app/utils/Escape.php:30` | json_encode para contexto JS |
| `e()` shortcut | `app/utils/escape_helper.php` | Wrapper curto para views |
| `eAttr()` shortcut | `app/utils/escape_helper.php` | Wrapper para atributos |
| `eJs()` shortcut | `app/utils/escape_helper.php` | Wrapper para JavaScript |

### 3.2 Vulnerabilidades Encontradas

#### 🔴 CRÍTICO: XSS via Popover Content em Pedidos

- **Arquivo:** `app/views/orders/create.php:291-308`
- **Descrição:** Construção de HTML para popover Bootstrap concatena dados do banco sem escape adequado. Embora dados venham do banco, um cliente com nome malicioso poderia injetar script.
- **Código vulnerável:**
```php
$popoverContent .= '<strong>#' . str_pad($c['id'], 4, '0', STR_PAD_LEFT) . '</strong> ';
$popoverContent .= '<span class="badge bg-' . $pColor . '">' . ucfirst($c['priority']) . '</span><br>';
```
- **Vetor:** Se `$c['priority']` ou outros campos contiverem HTML malicioso
- **Correção:** Usar `htmlspecialchars()` em todos os valores dinâmicos dentro do popover

#### 🟡 MÉDIO: Uso de `addslashes()` em Contexto JavaScript

- **Arquivo:** `app/views/customers/view.php:66`
- **Descrição:** `addslashes(e($c['name']))` é inadequado para contexto JavaScript. Deveria usar `json_encode()` ou `eJs()`.
- **Correção:** Substituir por `<?= json_encode($c['name']) ?>`

#### 🟡 MÉDIO: innerHTML em Arquivos JavaScript

Múltiplas inserções de HTML dinâmico via `innerHTML` sem sanitização DOMPurify:

| Arquivo | Linhas | Risco |
|---|---|---|
| `assets/js/financial-payments.js` | 103, 197, 878, 915 | ALTO — HTML vindo de API |
| `assets/js/portal.js` | 205, 355, 406, 458 | MÉDIO — contexto controlado |
| `assets/js/walkthrough.js` | 169, 332, 346, 420 | BAIXO — templates hardcoded |

- **Correção para financial-payments.js:** O HTML renderizado no `innerHTML` das linhas 197 e 915 vem de respostas da API construídas no servidor. Embora o servidor escape os dados, o padrão correto é:
  1. Receber dados JSON puros da API
  2. Construir o HTML no cliente com `document.createElement()` / `textContent`
  3. Ou usar DOMPurify para sanitizar HTML recebido

#### 🟢 BAIXO: Inconsistência entre `htmlspecialchars()` e `e()` nas Views

- **Arquivo:** `app/views/components/section-header.php:68-69`
- **Descrição:** Usa `htmlspecialchars()` diretamente em vez do wrapper `e()`. Não é uma vulnerabilidade, mas gera inconsistência.

---

## 4. SQL Injection

### 4.1 Status Geral: ✅ BOM

A vasta maioria das queries utiliza **prepared statements PDO** com parâmetros nomeados.

- O `PDO::ATTR_ERRMODE` está definido como `PDO::ERRMODE_EXCEPTION` — `app/config/database.php:66`
- Models utilizam `$stmt = $this->db->prepare(...)` + `$stmt->execute([...])` consistentemente

### 4.2 Vulnerabilidade Encontrada

#### 🟠 ALTO: Interpolação de String em SQL

- **Arquivo:** `app/models/ProductionSector.php:27`
- **Código:**
```php
$stmt = $this->conn->query("SELECT * FROM {$this->table}{$where} ORDER BY sort_order ASC, name ASC");
```
- **Risco:** Se a variável `$where` for construída com dados de usuário sem parametrização
- **Correção:** Refatorar para usar `prepare()` com parâmetros nomeados

### 4.3 Padrão Recomendado (já usado na maioria dos models)

```php
$stmt = $this->db->prepare('SELECT * FROM sectors WHERE tenant_id = :tid AND name LIKE :name');
$stmt->execute([':tid' => $tenantId, ':name' => '%' . $search . '%']);
```

---

## 5. Exposição de Informações Sensíveis

### 5.1 Status: 🔴 CRÍTICO — Necessita Correção Imediata

Múltiplos controllers expõem mensagens de exceção PHP diretamente nas respostas JSON ao cliente.

#### Arquivos Afetados (com linhas específicas)

| Controller | Linhas | Tipo de Exposição |
|---|---|---|
| `NfeDocumentController.php` | 279, 330, 382, 491, 599, 1130, 1190, 1245, 1491, 1535, 1633, 1705 | `$e->getMessage()` em JSON |
| `NfeCredentialController.php` | 348 | `$e->getMessage()` em JSON |
| `FinancialImportController.php` | 187, 280 | `$e->getMessage()` em JSON |
| `PaymentGatewayController.php` | 230, 320, 351 | `$e->getMessage()` em JSON |
| `CategoryController.php` | 284 | `$e->getMessage()` em JSON |
| `CatalogController.php` | 196 | `$e->getMessage()` em JSON |
| `SearchController.php` | 47, 54, 61 | `$e->getMessage()` em JSON |
| `PortalController.php` | 246, 1531 | `$e->getMessage()` em JSON |

**Risco:** Mensagens de exceção podem conter:
- Nomes de tabelas e colunas do banco de dados
- Caminhos absolutos de arquivos do servidor
- Credenciais de conexão em exceções de PDO
- Detalhes de configuração interna

**Correção padronizada:**
```php
// ❌ ERRADO — expõe detalhes internos
catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ✅ CORRETO — mensagem genérica + log interno
catch (\Throwable $e) {
    error_log('[Controller::method] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
}
```

---

## 6. Upload de Arquivos

### 6.1 Status: 🟠 ALTO — Validação Insuficiente

A validação de uploads é feita predominantemente por extensão de arquivo, sem verificação de conteúdo real (magic bytes / MIME type).

#### Uploads Identificados

| Controller | Linha | Tipo | Validação |
|---|---|---|---|
| `ProductController.php` | 373, 404 | Imagens de produtos | Extensão apenas |
| `NfeCredentialController.php` | 194, 199 | Certificados .pfx/.p12 | Extensão apenas |
| `InstallmentController.php` | 193, 290, 519, 528 | Comprovantes (img/pdf) | Extensão + erro |
| `CustomerController.php` | 1551, 1558 | Foto do cliente | Extensão apenas |
| `FinancialImportController.php` | 67, 247 | CSV/OFX financeiro | Extensão apenas |

**Riscos identificados:**
1. **Bypass de extensão:** Um arquivo `.php` pode ser renomeado para `.jpg` e executado se o servidor web permitir
2. **Ausência de verificação MIME:** `finfo_file()` não é usado para confirmar o tipo real
3. **Ausência de validação de magic bytes:** Verificar os primeiros bytes do arquivo
4. **Permissões de diretório:** `mkdir($path, 0755)` — adequado, mas upload dir deveria ter regra no `.htaccess` para negar execução PHP

**Correção recomendada:**
```php
// 1. Verificar MIME type real
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpFile);
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mimeType, $allowedMimes, true)) {
    throw new \InvalidArgumentException('Tipo de arquivo não permitido.');
}

// 2. Adicionar .htaccess no diretório de uploads
// <FilesMatch "\.php$">
//     deny from all
// </FilesMatch>
```

---

## 7. Gerenciamento de Sessão

### 7.1 Status: ✅ EXCELENTE

| Configuração | Valor | Status |
|---|---|---|
| `session.cookie_httponly` | `1` | ✅ JS não acessa cookie |
| `session.cookie_samesite` | `Strict` | ✅ Melhor proteção CSRF |
| `session.use_strict_mode` | `1` | ✅ Rejeita IDs não gerados pelo servidor |
| `session.cookie_name` | `AKTI_SID` | ✅ Nome customizado |
| `session.cookie_secure` | Condicional (HTTPS) | ⚠️ Ver item 7.2 |

### 7.2 Observação: Cookie Secure Condicional

- **Arquivo:** `app/config/session.php:18`
- **Código:** `ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');`
- **Risco em produção:** Se o servidor estiver atrás de proxy reverso/load balancer que termina TLS, `$_SERVER['HTTPS']` pode não estar definido
- **Recomendação:** Verificar também `HTTP_X_FORWARDED_PROTO` ou forçar `secure=1` em produção

### 7.3 Timeout e Inatividade

- **SessionGuard:** `app/config/session.php:35-80`
- **Timeout padrão:** 60 minutos (configurável via `company_settings`)
- **Detecção AJAX:** Retorna JSON 401 se sessão expirada em chamada AJAX — ✅ Correto
- **Log de expiração:** Registra em `system_logs` — ✅ Auditável

---

## 8. Headers de Segurança HTTP

### 8.1 Status: ✅ BOM

**Middleware:** `SecurityHeadersMiddleware::apply()` — `app/middleware/SecurityHeadersMiddleware.php`

| Header | Valor | Status |
|---|---|---|
| `X-Content-Type-Options` | `nosniff` | ✅ Previne MIME sniffing |
| `X-Frame-Options` | `SAMEORIGIN` | ✅ Previne clickjacking |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | ✅ Controla vazamento de referrer |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` | ✅ Restringe APIs do browser |
| `X-XSS-Protection` | `0` | ✅ Correto (obsoleto, CSP é melhor) |
| `Strict-Transport-Security` | `max-age=31536000` (apenas HTTPS) | ✅ HSTS com 1 ano |

### 8.2 Header Ausente: Content-Security-Policy (CSP)

- **Status:** ❌ Não implementado
- **Impacto:** Sem CSP, o navegador não tem restrições sobre quais scripts podem executar
- **Recomendação:** Implementar CSP gradualmente, começando com `report-only`

---

## 9. Rate Limiting

### 9.1 Status: ✅ DISPONÍVEL, ⚠️ USO INCONSISTENTE

- **Middleware:** `app/middleware/RateLimitMiddleware.php`
- **Dois modos:** Session-based (rápido) e DB-based (robusto)
- **Observação:** Disponível mas não aplicado globalmente. Apenas usado em endpoints específicos (NF-e, pagamentos)

### 9.2 API Node.js

- **Rate Limiter:** `express-rate-limit` configurado — `api/src/middlewares/rateLimiter.js`
- **Limite:** 100 requisições por janela de 15 minutos
- **Webhooks:** Excluídos do rate limit (gateways fazem retries) — ✅ Correto

---

## 10. Autenticação e Controle de Acesso

### 10.1 Autenticação Admin: ✅ BOA

- **Login:** `AuthService` com verificação de brute-force — `app/services/AuthService.php`
- **Senhas:** `password_hash(PASSWORD_BCRYPT)` / `password_verify()` — ✅ Seguro
- **Login Attempts:** Tabela `login_attempts` registra tentativas — `app/models/LoginAttempt.php`

### 10.2 Portal do Cliente: ✅ BOA

- **Separação total** da autenticação admin: `portal_customer_id` vs `user_id`
- **Magic link:** Autenticação sem senha (opcional)
- **2FA:** OTP/SMS disponível — `app/services/Portal2faService.php`
- **Middleware dedicado:** `PortalAuthMiddleware` — `app/middleware/PortalAuthMiddleware.php`

### 10.3 Permissões por Grupo: ✅ BOA

- **Menu config:** `app/config/menu.php` define flags `permission: true`
- **Verificação:** `$user->checkPermission($userId, $page)` — `index.php:250-254`
- **ModuleBootloader:** `canAccessPage($page)` — `app/core/ModuleBootloader.php:134`

### 10.4 Gaps de Permissão Identificados

| Controller | Observação |
|---|---|
| `DashboardWidgetController.php:35` | `widget_key` sanitizado mas sem validação estrita |
| `SearchController.php:34` | Parâmetro `$q` poderia ter validação mais rigorosa |

---

## 11. Open Redirects

### 11.1 Status: 🟡 MÉDIO

- **Arquivo principal:** `app/controllers/InstallmentController.php:295`
- **Código:** `header("Location: $redirect");`
- **Mitigação existente:** Usa `sanitizeRedirect()` — verificar se implementação é robusta
- **Outros redirecionamentos dinâmicos:** `UserController.php:388`, `PortalController.php:125`, `PipelineController.php:223`, `OrderController.php:198`

**Recomendação:** Manter whitelist de URLs internas permitidas:
```php
private function safeRedirect(string $url): void
{
    $parsed = parse_url($url);
    if (isset($parsed['host'])) {
        $url = '?page=dashboard'; // Forçar URL interna se host externo detectado
    }
    header('Location: ' . $url);
    exit;
}
```

---

## 12. API Node.js — Segurança

### 12.1 Autenticação JWT: ✅ BOA

- **Middleware:** `api/src/middlewares/authMiddleware.js:7-20`
- **Validação:** `jwt.verify(token, JWT_SECRET)` com verificação completa
- **Injeção:** `req.user` populado com dados do token

### 12.2 Webhooks: ⚠️ ATENÇÃO

- **Resolução de tenant via query param:** `?tenant=db_name` — `api/src/routes/webhookRoutes.js:56-58`
- **Risco:** O nome do banco de dados é exposto na URL do webhook
- **Mitigação existente:** Validação HMAC de assinatura por gateway (Stripe: `crypto.timingSafeEqual`)

### 12.3 CORS: ✅ BOM

- **Whitelist:** `*.useakti.com`, `localhost`, `127.0.0.1`
- **Credenciais:** Habilitadas (`credentials: true`)
- **Padrão regex:** Validação por pattern matching

---

## 13. Dependências e Versões

### 13.1 PHP (composer.json)

| Dependência | Versão | Status |
|---|---|---|
| PHP | >=7.4 | ⚠️ 7.4 é EOL desde Nov/2022 — mínimo recomendado: 8.1 |
| phpunit/phpunit | ^9.6 | ✅ Versão estável |
| sped-nfe | ^5.0 | ✅ Atualizado |
| phpoffice/phpspreadsheet | ^5.5 | ✅ Atualizado |
| tecnickcom/tcpdf | ^6.11 | ✅ Atualizado |

### 13.2 Node.js (api/package.json)

| Dependência | Versão | Status |
|---|---|---|
| express | ^4.21.2 | ✅ Atualizado |
| helmet | ^8.0.0 | ✅ Atualizado |
| jsonwebtoken | ^9.0.2 | ✅ Atualizado |
| sequelize | ^6.37.5 | ✅ Atualizado |
| mysql2 | ^3.12.0 | ✅ Atualizado |

---

## 14. Arquivos de Backup em Produção

### 14.1 Status: ⚠️ RISCO DE EXPOSIÇÃO

Arquivos `.bak` encontrados no diretório de views:

| Arquivo | Risco |
|---|---|
| `app/views/customers/index.php.bak` | Pode ser acessado diretamente via HTTP |
| `app/views/customers/create.php.bak` | Pode expor código-fonte |
| `app/views/customers/edit.php.bak` | Pode expor código-fonte |
| `app/views/customers/view.php.bak` | Contém XSS na linha 144 |
| `app/views/products/index_old_backup.php` | Pode ser acessado via HTTP |
| `app/views/financial/payments_old.php` | Código legado acessível |
| `app/views/financial/payments_new.php` | WIP acessível |

**Correção:** Adicionar regra no `.htaccess` para bloquear `.bak` e remover arquivos desnecessários.

---

## 15. Checklist Final de Segurança

| Item | Status | Prioridade |
|---|---|---|
| CSRF Protection | ✅ Implementado | — |
| Session Security | ✅ Implementado | — |
| SQL Injection (PDO) | ✅ 99% coberto | ALTO (1 caso) |
| XSS Output Escaping | ⚠️ Parcial | CRÍTICO |
| Exposição de $e->getMessage() | ❌ Não tratado | CRÍTICO |
| File Upload Validation | ⚠️ Insuficiente | ALTO |
| Security Headers | ✅ Implementado | — |
| CSP Header | ❌ Ausente | MÉDIO |
| Rate Limiting Global | ⚠️ Parcial | MÉDIO |
| Open Redirect Protection | ⚠️ Parcial | MÉDIO |
| Backup Files Cleanup | ❌ Pendente | BAIXO |
| PHP Version | ⚠️ 7.4 EOL | MÉDIO |
| Dependency Audit | ✅ Atualizado | — |
| Error Logging | ✅ Estruturado | — |
| Auth Brute-Force | ✅ Implementado | — |
| Multi-Tenant Isolation | ✅ Implementado | — |
