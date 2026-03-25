# 🔍 Auditoria Completa — Portal do Cliente (Akti)

**Data da auditoria:** Gerada automaticamente  
**Escopo:** Inventário de todos os arquivos, responsabilidades, status (ativo/incompleto/ocioso), cruzamento com migrations SQL e identificação de gaps.

---

## 📊 Resumo Executivo

| Métrica | Valor |
|---------|-------|
| Total de arquivos do portal | **17** |
| Arquivos backend (PHP) | 8 |
| Arquivos frontend (Views) | 9 |
| Assets dedicados (CSS/JS/PWA) | 3 |
| Migrations SQL | 1 |
| Métodos implementados no Controller | 9 (incluindo `__construct`) |
| Actions declaradas nas Rotas | **28** |
| Actions **sem implementação** | **19** ❌ |
| Tabelas criadas pela migration | 4 + ALTER TABLE (5 colunas, 1 índice) |
| Chaves de tradução (pt-br) | 204 |

---

## 📁 1. Inventário de Backend (PHP)

### 1.1 Controller

| Arquivo | Linhas | Namespace | Responsabilidade | Status |
|---------|--------|-----------|-----------------|--------|
| `app/controllers/PortalController.php` | 473 | `Akti\Controllers` | Controller principal: index, login, loginMagic, logout, register, dashboard, profile, updateProfile | ⚠️ **Parcial** — apenas 8 actions públicas implementadas de 28 declaradas nas rotas |

**Métodos públicos implementados:**

| Método | Ação de Rota | Descrição | Status |
|--------|-------------|-----------|--------|
| `index()` | `index` | Redireciona para login ou dashboard | ✅ Ativo |
| `login()` | `login` | GET: renderiza login / POST: processa autenticação email+senha | ✅ Ativo |
| `loginMagic()` | `loginMagic` | Processa login via magic token (valida token GET) | ✅ Ativo |
| `logout()` | `logout` | Encerra sessão do portal | ✅ Ativo |
| `register()` | `register` | GET: formulário / POST: auto-registro de clientes | ✅ Ativo |
| `dashboard()` | `dashboard` | Dashboard com stats, pedidos recentes e notificações | ✅ Ativo |
| `profile()` | `profile` | Exibe dados do perfil do cliente | ✅ Ativo |
| `updateProfile()` | `updateProfile` | Processa alteração de perfil (POST) | ✅ Ativo |

**Métodos auxiliares privados:**

| Método | Descrição | Status |
|--------|-----------|--------|
| `isPortalEnabled()` | Verifica config `portal_enabled` | ✅ Ativo |
| `renderDisabled()` | Renderiza tela de portal desabilitado | ✅ Ativo |
| `isAjax()` | Detecta requisição AJAX | ✅ Ativo |
| `findCustomerByEmail()` | Busca cliente pelo e-mail na tabela `customers` | ✅ Ativo |

### 1.2 Models

| Arquivo | Linhas | Namespace | Tabela Principal | Responsabilidade | Status |
|---------|--------|-----------|-----------------|-----------------|--------|
| `app/models/PortalAccess.php` | 569 | `Akti\Models` | `customer_portal_access` | CRUD de acessos, autenticação (senha + magic link), bloqueio por tentativas, configurações do portal (`customer_portal_config`), estatísticas de dashboard, pedidos recentes, notificações | ✅ **Funcional e completo** |
| `app/models/PortalMessage.php` | 162 | `Akti\Models` | `customer_portal_messages` | CRUD de mensagens (create, getByCustomer, countUnread, markAsRead, findById, countUnreadFromCustomers) | ✅ **Funcional — mas sem view correspondente** |

**Observação sobre PortalAccess:** Este model acumula responsabilidades que poderiam ser separadas:
- Autenticação (login/magic link/lock)
- Configuração do portal (`customer_portal_config`)
- Estatísticas de dashboard (queries em `orders` e `order_installments`)
- Notificações

### 1.3 Middleware

| Arquivo | Linhas | Namespace | Responsabilidade | Status |
|---------|--------|-----------|-----------------|--------|
| `app/middleware/PortalAuthMiddleware.php` | 156 | `Akti\Middleware` | Sessão isolada do portal (separada da admin), `check()`, `isAuthenticated()`, `login()`, `logout()`, `touch()`, `checkInactivity()`, `getClientIp()` | ✅ **Funcional e completo** |

**Variáveis de sessão gerenciadas:**
- `$_SESSION['portal_customer_id']`
- `$_SESSION['portal_access_id']`
- `$_SESSION['portal_customer_name']`
- `$_SESSION['portal_email']`
- `$_SESSION['portal_lang']`
- `$_SESSION['portal_last_activity']`
- `$_SESSION['portal_cart']` (apenas no `logout()`)

### 1.4 Services

| Arquivo | Linhas | Namespace | Responsabilidade | Status |
|---------|--------|-----------|-----------------|--------|
| `app/services/PortalLang.php` | 116 | `Akti\Services` | Sistema de tradução i18n: `init()`, `get()` com placeholders, `getLang()`, `getAvailableLanguages()`, `loadTranslations()` com fallback para pt-br | ✅ **Funcional e completo** |

### 1.5 Utils / Helpers

| Arquivo | Linhas | Namespace | Responsabilidade | Status |
|---------|--------|-----------|-----------------|--------|
| `app/utils/portal_helper.php` | 109 | Global (sem namespace) | Funções globais: `__p()` (tradução), `portal_money()`, `portal_date()`, `portal_datetime()`, `portal_stage_class()`, `portal_stage_icon()` | ✅ **Funcional e completo** |

### 1.6 Tradução

| Arquivo | Linhas | Idioma | Chaves | Status |
|---------|--------|--------|--------|--------|
| `app/lang/pt-br/portal.php` | 204 | Português (Brasil) | 204 chaves organizadas por seção (geral, login, registro, dashboard, navegação, pedidos, aprovação, financeiro, rastreamento, mensagens, perfil, status, PWA, erros, formatos) | ✅ **Completo para Fase 1** |

**Nota:** Muitas chaves (ex: pedidos, aprovação, financeiro, rastreamento, mensagens) referenciam funcionalidades **ainda não implementadas** no controller.

---

## 📁 2. Inventário de Frontend (Views)

### 2.1 Layouts

| Arquivo | Linhas | Propósito | Usado em | Status |
|---------|--------|-----------|----------|--------|
| `app/views/portal/layout/header.php` | 68 | Layout autenticado: HTML head, meta PWA, topbar com logo/nome/logout, abertura de `<main>` | Dashboard, Profile, futuras páginas auth | ✅ Ativo |
| `app/views/portal/layout/footer.php` | 74 | Bottom navigation (5 itens: Home, Pedidos, Novo, Financeiro, Perfil), PWA install banner, JS imports, registro de Service Worker | Dashboard, Profile, futuras páginas auth | ✅ Ativo |
| `app/views/portal/layout/header_auth.php` | 42 | Layout público: HTML head, meta PWA, abertura de `<body class="portal-auth-body">` | Login, Register, Disabled | ✅ Ativo |
| `app/views/portal/layout/footer_auth.php` | 7 | Footer mínimo: Bootstrap JS + portal.js | Login, Register, Disabled | ✅ Ativo |

**Bottom Navigation — Links vs Implementação:**

| Item de Nav | Rota | Método no Controller | Status |
|-------------|------|---------------------|--------|
| Home | `?page=portal&action=dashboard` | `dashboard()` | ✅ Implementado |
| Pedidos | `?page=portal&action=orders` | — | ❌ **Não implementado** |
| Novo (+) | `?page=portal&action=newOrder` | — | ❌ **Não implementado** |
| Financeiro | `?page=portal&action=installments` | — | ❌ **Não implementado** |
| Perfil | `?page=portal&action=profile` | `profile()` | ✅ Implementado |

### 2.2 Páginas

| Arquivo | Linhas | Propósito | Variáveis esperadas | Status |
|---------|--------|-----------|-------------------|--------|
| `app/views/portal/auth/login.php` | 143 | Login: email+senha, toggle magic link (form escondido), link de registro | `$error`, `$successMsg`, `$company`, `$allowSelfRegister` | ✅ Ativo |
| `app/views/portal/auth/register.php` | 145 | Auto-registro: 6 campos (nome, email, tel, CPF, senha, confirma) | `$error`, `$formData`, `$company` | ✅ Ativo |
| `app/views/portal/dashboard.php` | 126 | Dashboard: saudação, 4 stat cards, notificações recentes, pedidos recentes com links para detalhe | `$customerName`, `$stats`, `$recentOrders`, `$notifications`, `$unreadMessages`, `$company` | ✅ Ativo |
| `app/views/portal/profile/index.php` | 114 | Perfil: dados pessoais (nome, email disabled, tel, CPF disabled, endereço), idioma, alterar senha, logout | `$customer`, `$access`, `$company`, `$languages`, `$message` | ✅ Ativo |
| `app/views/portal/disabled.php` | 21 | Tela de portal desabilitado (ícone de cadeado + mensagem) | `$company` | ✅ Ativo |

---

## 📁 3. Inventário de Assets

### 3.1 CSS

| Arquivo | Linhas | Propósito | Status |
|---------|--------|-----------|--------|
| `assets/css/portal.css` | 761 | CSS mobile-first dedicado ao portal. Variáveis CSS, topbar, bottom nav, auth container, formulários, inputs, botões, cards, stat cards, notificações, order list, PWA banner, empty states, form actions, animations, media queries (sm/md/lg) | ✅ **Funcional** |

**Classes CSS definidas (principais):** `portal-body`, `portal-auth-body`, `portal-topbar*`, `portal-content`, `portal-page*`, `portal-bottom-nav`, `portal-nav-item*`, `portal-auth-container`, `portal-auth-card*`, `portal-input*`, `portal-btn-*`, `portal-card*`, `portal-greeting*`, `portal-stats-grid`, `portal-stat-card*`, `portal-section*`, `portal-notification-*`, `portal-order-*`, `portal-empty-state`, `portal-pwa-banner*`, `portal-form-actions`

### 3.2 JavaScript

| Arquivo | Linhas | Propósito | Status |
|---------|--------|-----------|--------|
| `assets/js/portal.js` | 231 | JS dedicado ao portal. Objeto global `Portal` com: `csrfToken()`, `post()`, `get()` (AJAX helpers), `toast()`. PWA install prompt com delay de 7 dias. Form loading state com spinner. Auto-hide alerts em 5s. | ✅ **Funcional** |

### 3.3 PWA

| Arquivo | Linhas | Propósito | Status |
|---------|--------|-----------|--------|
| `portal-sw.js` | 104 | Service Worker: cache estático de assets (CSS, JS, Bootstrap, Font Awesome, Google Fonts), strategy network-first para HTML, limpeza de caches antigos | ⚠️ **Funcional, mas sem scope limitado** ao portal — pode interferir com app admin |
| `manifest.json` | 26 | Manifest PWA geral do sistema (não específico do portal) | ⚠️ **Compartilhado** — `start_url: "."` aponta para app admin, não para `?page=portal` |

---

## 📁 4. Inventário SQL (Migrations)

### 4.1 Migration do Portal

| Arquivo | Linhas | Data | Propósito |
|---------|--------|------|-----------|
| `sql/update_202603241000_portal_cliente.sql` | 163 | 24/03/2026 | Migration Fase 1 do Portal do Cliente |

**Tabelas criadas:**

| Tabela | Colunas | FK | Descrição |
|--------|---------|-----|-----------|
| `customer_portal_access` | 14 | `customers(id) CASCADE` | Autenticação: email, hash, magic token, lockout, last login, idioma |
| `customer_portal_sessions` | 7 | `customers(id) CASCADE` | Sessões ativas (token, device, IP, expiração) — **Tabela criada mas NÃO utilizada pelo código** |
| `customer_portal_messages` | 10 | `customers(id) CASCADE`, `orders(id) SET NULL` | Mensagens cliente↔empresa: sender_type, is_read, attachment |
| `customer_portal_config` | 4 | — | Configurações key-value do portal (8 registros iniciais) |

**ALTER TABLE `orders` (5 colunas adicionadas):**

| Coluna | Tipo | Descrição | Usada no código? |
|--------|------|-----------|-----------------|
| `customer_approval_status` | ENUM('pendente','aprovado','recusado') | Status de aprovação do cliente | ✅ Sim (com check dinâmico via `information_schema`) |
| `customer_approval_at` | DATETIME | Data/hora da aprovação | ❌ Não (nenhum método implementado para gravar) |
| `customer_approval_ip` | VARCHAR(45) | IP do cliente na aprovação | ❌ Não |
| `customer_approval_notes` | TEXT | Observações do cliente | ❌ Não |
| `portal_origin` | TINYINT(1) | Se pedido veio do portal | ❌ Não |

**Índice adicionado:**

| Índice | Tabela | Colunas | Usado? |
|--------|--------|---------|--------|
| `idx_orders_customer_portal` | `orders` | `customer_id, status, pipeline_stage` | ✅ Sim (queries de dashboard) |

**Configurações iniciais inseridas (`customer_portal_config`):**

| Chave | Valor | Consumida pelo código? |
|-------|-------|----------------------|
| `portal_enabled` | `1` | ✅ Sim (`isPortalEnabled()`) |
| `allow_self_register` | `1` | ✅ Sim (`register()`) |
| `allow_new_orders` | `1` | ❌ Não (sem método `newOrder`) |
| `allow_order_approval` | `1` | ❌ Não (sem método `approveOrder`) |
| `allow_messages` | `1` | ❌ Não (sem view de mensagens) |
| `magic_link_expiry_hours` | `24` | ⚠️ Parcial (token é gerado/validado mas envio de email não implementado) |
| `show_prices_in_catalog` | `1` | ❌ Não (sem catálogo) |
| `require_password` | `0` | ❌ Não (não verificado na autenticação) |

---

## 🚨 5. Análise de Gaps — Rotas Declaradas vs Implementadas

Das **28 actions** declaradas em `app/config/routes.php` para `page=portal`, apenas **9** possuem método correspondente no controller.

### ❌ Actions NÃO implementadas (19 de 28):

| Action de Rota | Categoria | Dependências estimadas |
|----------------|-----------|----------------------|
| `forgotPassword` | Auth | View, Model (reset token), e-mail |
| `resetPassword` | Auth | View, Model (validar token reset) |
| `orders` | Pedidos | View (listagem), query no Model |
| `orderDetail` | Pedidos | View (detalhe completo + timeline) |
| `approveOrder` | Aprovação | View + POST, Model (gravar aprovação) |
| `rejectOrder` | Aprovação | View + POST, Model (gravar recusa) |
| `newOrder` | Novo Pedido | View (catálogo), Model (Product), session cart |
| `getProducts` | Novo Pedido | JSON endpoint, Model (Product) |
| `addToCart` | Novo Pedido | Session cart logic |
| `removeFromCart` | Novo Pedido | Session cart logic |
| `updateCartItem` | Novo Pedido | Session cart logic |
| `getCart` | Novo Pedido | JSON endpoint, session cart |
| `submitOrder` | Novo Pedido | POST, Model (Order create) |
| `installments` | Financeiro | View (listagem), Model (Financial/Order) |
| `installmentDetail` | Financeiro | View (detalhe parcela) |
| `tracking` | Rastreamento | View + API externa |
| `messages` | Mensagens | View (chat), Model (PortalMessage) |
| `sendMessage` | Mensagens | POST, Model (PortalMessage.create) |
| `documents` | Documentos | View (listagem) |
| `downloadDocument` | Documentos | File download logic |

### ⚠️ Funcionalidades parcialmente implementadas:

| Funcionalidade | O que existe | O que falta |
|----------------|-------------|-------------|
| **Magic Link** | Geração de token (`generateMagicToken`), validação (`validateMagicToken`), invalidação (`invalidateMagicToken`), login via token (`loginMagic()`) | **Envio do e-mail** — não há action `requestMagicLink` nem integração com serviço de e-mail. O form de login referencia `?page=portal&action=requestMagicLink` que não existe. |
| **Mensagens** | Model completo (`PortalMessage`), contagem no dashboard (`countUnread`) | View de chat, action `messages`, action `sendMessage` |
| **Sessões persistentes** | Tabela `customer_portal_sessions` criada | Código usa `$_SESSION` nativa (não a tabela) — tabela está **ociosa** |
| **Aprovação** | Colunas na tabela `orders`, check dinâmico no dashboard | Actions `approveOrder`/`rejectOrder`, view de aprovação |

---

## 📋 6. Quadro Consolidado — Status por Arquivo

### Legenda:
- ✅ **Ativo** — Funcional e utilizado pelo sistema
- ⚠️ **Incompleto** — Existe mas com funcionalidades faltando
- 🔴 **Ocioso** — Existe mas não é utilizado por nenhum código ativo

| # | Arquivo | Tipo | Linhas | Status | Observação |
|---|---------|------|--------|--------|------------|
| 1 | `app/controllers/PortalController.php` | Controller | 473 | ⚠️ Incompleto | 9/28 actions implementadas |
| 2 | `app/models/PortalAccess.php` | Model | 569 | ✅ Ativo | Model principal mais completo |
| 3 | `app/models/PortalMessage.php` | Model | 162 | ⚠️ Incompleto | Model funcional mas sem view/action consumindo |
| 4 | `app/middleware/PortalAuthMiddleware.php` | Middleware | 156 | ✅ Ativo | Completo e funcional |
| 5 | `app/services/PortalLang.php` | Service | 116 | ✅ Ativo | i18n completo |
| 6 | `app/utils/portal_helper.php` | Helper | 109 | ✅ Ativo | Funções globais úteis |
| 7 | `app/lang/pt-br/portal.php` | Tradução | 204 | ⚠️ Incompleto | Chaves definidas para features inexistentes |
| 8 | `app/config/routes.php` (seção portal) | Config | ~45 | ⚠️ Incompleto | 19 rotas apontam para métodos inexistentes |
| 9 | `app/views/portal/layout/header.php` | View/Layout | 68 | ✅ Ativo | Layout autenticado |
| 10 | `app/views/portal/layout/footer.php` | View/Layout | 74 | ⚠️ Incompleto | Bottom nav com 3 links quebrados |
| 11 | `app/views/portal/layout/header_auth.php` | View/Layout | 42 | ✅ Ativo | Layout público |
| 12 | `app/views/portal/layout/footer_auth.php` | View/Layout | 7 | ✅ Ativo | Footer mínimo |
| 13 | `app/views/portal/auth/login.php` | View | 143 | ⚠️ Incompleto | Form magic link aponta para action inexistente |
| 14 | `app/views/portal/auth/register.php` | View | 145 | ✅ Ativo | Completa |
| 15 | `app/views/portal/dashboard.php` | View | 126 | ⚠️ Incompleto | Links para `orders`, `orderDetail` quebrados |
| 16 | `app/views/portal/profile/index.php` | View | 114 | ✅ Ativo | Completa |
| 17 | `app/views/portal/disabled.php` | View | 21 | ✅ Ativo | Completa |
| 18 | `assets/css/portal.css` | CSS | 761 | ✅ Ativo | CSS mobile-first dedicado |
| 19 | `assets/js/portal.js` | JS | 231 | ✅ Ativo | AJAX helpers, PWA, UX |
| 20 | `portal-sw.js` | PWA/SW | 104 | ⚠️ Incompleto | Sem scope limitado ao portal |
| 21 | `manifest.json` | PWA | 26 | ⚠️ Incompleto | Compartilhado com admin, start_url genérico |
| 22 | `sql/update_202603241000_portal_cliente.sql` | SQL | 163 | ✅ Ativo | Migration Fase 1 |

### Resumo de status:
- ✅ **Ativos:** 12 arquivos
- ⚠️ **Incompletos:** 10 arquivos
- 🔴 **Ociosos:** 0 arquivos stand-alone (mas 1 tabela SQL `customer_portal_sessions` criada e não utilizada)

---

## 🔄 7. Cruzamento: Relatório V2 vs Realidade

| Item do Relatório V2 | Valor documentado | Valor real auditado | Divergência |
|----------------------|-------------------|-------------------|-------------|
| Linhas `portal.css` | 904 | **761** | ⚠️ Divergente (relatório cita valor maior) |
| Status `login.php` | "magic link não envia" | Correto — action `requestMagicLink` inexistente | ✅ Confirmado |
| Actions sem método | "Muitas sem método" | **19 de 28 sem implementação** | Agora quantificado |
| Status `PortalMessage.php` | "sem view" | Correto — model completo sem view/action | ✅ Confirmado |
| Status `portal-sw.js` | "sem scope limitado" | Correto — registra no escopo global `/` | ✅ Confirmado |
| Tabela `customer_portal_sessions` | Mencionada como criada | Criada mas **nunca utilizada** pelo código PHP | Agora detalhado |

---

## 🎯 8. Recomendações Prioritárias

### Alta Prioridade (funcionalidades referenciadas pela UI):
1. **Implementar `orders()`** — listagem de pedidos (a bottom nav já linka)
2. **Implementar `orderDetail()`** — detalhe de pedido (dashboard já gera links)
3. **Implementar `installments()`** — financeiro (bottom nav já linka)
4. **Implementar `newOrder()`** — novo pedido (bottom nav já linka com ícone "+" central)
5. **Implementar `requestMagicLink`** — o form de login já referencia esta action

### Média Prioridade (infraestrutura existente no Model):
6. **Implementar `messages()` + `sendMessage()`** — model `PortalMessage` já está pronto
7. **Implementar `approveOrder()` + `rejectOrder()`** — colunas no BD e config já existem

### Baixa Prioridade:
8. **Decidir sobre `customer_portal_sessions`** — usar a tabela ou removê-la
9. **Criar `manifest-portal.json`** separado com `start_url: "?page=portal"`
10. **Limitar scope do Service Worker** a `/portal` ou equivalente

---

## 📐 9. Diagrama de Dependências

```
[Routes: 28 actions]
    ├─► [PortalController: 8 actions] ──► [PortalAccess Model]
    │       │                                ├─ customer_portal_access (CRUD)
    │       │                                ├─ customer_portal_config (R/W)
    │       │                                ├─ orders (READ - stats)
    │       │                                └─ order_installments (READ - stats)
    │       │
    │       ├──► [PortalMessage Model] ──► customer_portal_messages
    │       │       (usado apenas countUnread no dashboard)
    │       │
    │       ├──► [Customer Model] ──► customers
    │       │
    │       ├──► [CompanySettings Model] ──► company_settings
    │       │
    │       ├──► [PortalAuthMiddleware] ──► $_SESSION
    │       │
    │       ├──► [PortalLang Service] ──► app/lang/pt-br/portal.php
    │       │
    │       └──► [Views: 5 pages + 4 layouts]
    │
    └─► [19 actions NÃO implementadas] ❌
         └─ Pedidos, Novo Pedido, Financeiro, Mensagens,
            Documentos, Rastreamento, Forgot/Reset Password

[customer_portal_sessions] ──► 🔴 OCIOSA (criada mas não utilizada)
```

---

## ✅ 10. Eventos Disparados

| Evento | Disparado em | Dados |
|--------|-------------|-------|
| `portal.access.created` | `PortalAccess::create()` | id, customer_id, email |
| `portal.customer.logged_in` | `PortalController::login()`, `loginMagic()` | customer_id, email, ip, [method] |
| `portal.access.locked` | `PortalAccess::registerFailedAttempt()` | access_id, customer_id, email |
| `portal.message.sent` | `PortalMessage::create()` | id, customer_id, order_id, sender_type |

---

*Auditoria realizada sobre o estado real dos arquivos no workspace.*
