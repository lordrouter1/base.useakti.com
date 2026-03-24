# 📋 RELATÓRIO TÉCNICO COMPLETO — Portal do Cliente (Akti)

> **Data:** 24/03/2026  
> **Versão:** 2.0 — Auditoria Completa + Roadmap de Melhorias  
> **Fase Atual:** 1 — Autenticação, Dashboard, Perfil, Registro  
> **Status Geral:** Base funcional com lacunas críticas e oportunidades de melhoria

---

## 📑 ÍNDICE

1. [Inventário Completo de Arquivos](#1-inventário-completo-de-arquivos)
2. [Banco de Dados — Estrutura e Estado](#2-banco-de-dados)
3. [Backend — Análise de Cada Componente](#3-backend)
4. [Frontend — Views, CSS, JS](#4-frontend)
5. [Fluxo de Autenticação Atual](#5-autenticação)
6. [Mapa de Actions — Status Real de Cada Uma](#6-mapa-de-actions)
7. [Campos e Funcionalidades NÃO Funcionais](#7-não-funcionais)
8. [Segurança — Auditoria](#8-segurança)
9. [Proposta: Login Unificado (Detecção Automática)](#9-login-unificado)
10. [Proposta: Visual Minimalista Akti](#10-visual)
11. [Proposta: Navegação Desktop](#11-navegação-desktop)
12. [Sugestões de Melhoria Priorizadas](#12-melhorias)
13. [Roadmap por Fases](#13-roadmap)
14. [Mapa de Dependências](#14-dependências)
15. [Referência Rápida de Actions](#15-referência)

---

## 📁 1. Inventário Completo de Arquivos

### 1.1 Backend (PHP)

| Arquivo | Linhas | Responsabilidade | Status |
|---------|--------|-----------------|--------|
| `app/controllers/PortalController.php` | 473 | Controller principal: login, register, dashboard, profile, logout | ✅ Funcional (parcial) |
| `app/models/PortalAccess.php` | 569 | CRUD de acessos, autenticação, magic link, stats do dashboard, config | ✅ Funcional |
| `app/models/PortalMessage.php` | 162 | CRUD de mensagens cliente↔empresa, contagem de não lidas | ✅ Funcional (sem view) |
| `app/middleware/PortalAuthMiddleware.php` | 156 | Sessão isolada, checagem de auth, inatividade, getClientIp | ✅ Funcional |
| `app/services/PortalLang.php` | 116 | i18n com fallback, placeholders, lista de idiomas | ✅ Funcional |
| `app/utils/portal_helper.php` | 109 | Helpers: `__p()`, `portal_money()`, `portal_date()`, `portal_stage_class()`, `portal_stage_icon()` | ✅ Funcional |
| `app/lang/pt-br/portal.php` | 204 | 204 chaves de tradução em português | ✅ Completo para Fase 1 |
| `app/config/routes.php` (seção portal) | ~45 | Mapa de rotas com 28 actions declaradas | ⚠️ Muitas sem método |

### 1.2 Frontend (Views)

| Arquivo | Linhas | Propósito | Status |
|---------|--------|-----------|--------|
| `app/views/portal/layout/header.php` | 68 | Layout autenticado: topbar + meta + CSS imports | ✅ Funcional |
| `app/views/portal/layout/footer.php` | 74 | Footer: bottom nav (5 itens) + PWA banner + JS imports + SW registration | ✅ Funcional |
| `app/views/portal/layout/header_auth.php` | 42 | Layout auth (login/register): sem nav, fundo gradient | ✅ Funcional |
| `app/views/portal/layout/footer_auth.php` | 7 | Footer simples: apenas Bootstrap JS + portal.js | ✅ Funcional |
| `app/views/portal/auth/login.php` | 143 | Tela de login: email+senha, magic link (toggle), link de registro | ✅ Funcional (magic link não envia) |
| `app/views/portal/auth/register.php` | 145 | Auto-registro: nome, email, telefone, CPF/CNPJ, senha | ✅ Funcional |
| `app/views/portal/dashboard.php` | 126 | Dashboard: saudação, 4 stat cards, notificações, pedidos recentes | ✅ Funcional |
| `app/views/portal/profile/index.php` | 114 | Perfil: dados pessoais, idioma, alterar senha, logout | ✅ Funcional |
| `app/views/portal/disabled.php` | 21 | Tela de portal desabilitado | ✅ Funcional |

### 1.3 Assets (CSS/JS/PWA)

| Arquivo | Linhas | Propósito | Status |
|---------|--------|-----------|--------|
| `assets/css/portal.css` | 904 | CSS mobile-first: variáveis, topbar, bottom nav, auth, forms, cards, stats, notifications, orders, PWA banner, dark mode, animations, responsive | ✅ Funcional (paleta desalinhada) |
| `assets/js/portal.js` | 231 | JS: CSRF token, AJAX helpers (Portal.post/get), Toast, PWA install, form loading, alert auto-hide | ✅ Funcional |
| `portal-sw.js` | 104 | Service Worker: cache estático, network-first para HTML | ⚠️ Sem scope limitado |

### 1.4 SQL

| Arquivo | Linhas | Propósito |
|---------|--------|-----------|
| `sql/update_202603241000_portal_cliente.sql` | 163 | Migration Fase 1: 4 tabelas + ALTER TABLE orders (5 colunas + 1 índice) |

---

## 🗄️ 2. Banco de Dados — Estrutura e Estado

### 2.1 Tabelas Criadas

#### `customer_portal_access` — Autenticação

| Coluna | Tipo | Nullable | Descrição | Usado? |
|--------|------|----------|-----------|--------|
| `id` | INT AUTO_INCREMENT PK | Não | ID do acesso | ✅ |
| `customer_id` | INT FK→customers.id | Não | Vínculo com cliente | ✅ |
| `email` | VARCHAR(150) UNIQUE | Não | E-mail de login | ✅ |
| `password_hash` | VARCHAR(255) | Sim | Hash bcrypt (null = apenas magic link) | ✅ |
| `magic_token` | VARCHAR(128) | Sim | Token de link mágico | ✅ (model) / ❌ (controller: `requestMagicLink` não existe) |
| `magic_token_expires_at` | DATETIME | Sim | Validade do token | ✅ (model) / ❌ (controller) |
| `is_active` | TINYINT(1) DEFAULT 1 | Não | Conta ativa/desativada | ✅ |
| `last_login_at` | DATETIME | Sim | Último login | ✅ |
| `last_login_ip` | VARCHAR(45) | Sim | IP do último login | ✅ |
| `failed_attempts` | INT DEFAULT 0 | Não | Tentativas falhas consecutivas | ✅ |
| `locked_until` | DATETIME | Sim | Bloqueado até (após 5 falhas) | ✅ |
| `lang` | VARCHAR(10) DEFAULT 'pt-br' | Não | Idioma preferido | ✅ |
| `created_at` | TIMESTAMP | Não | Data de criação | ✅ |
| `updated_at` | TIMESTAMP ON UPDATE | Não | Última atualização | ✅ (automático) |

**Constraints:** `UNIQUE(email)`, `UNIQUE(customer_id)`, `FK(customer_id) ON DELETE CASCADE`

#### `customer_portal_sessions` — Sessões Ativas

| Coluna | Tipo | Nullable | Descrição | Usado? |
|--------|------|----------|-----------|--------|
| `id` | INT AUTO_INCREMENT PK | Não | ID da sessão | ❌ Nunca lido/escrito |
| `customer_id` | INT FK→customers.id | Não | Vínculo com cliente | ❌ |
| `session_token` | VARCHAR(128) UNIQUE | Não | Token da sessão | ❌ |
| `device_info` | VARCHAR(255) | Sim | User-Agent / dispositivo | ❌ |
| `ip_address` | VARCHAR(45) | Sim | IP da sessão | ❌ |
| `expires_at` | DATETIME | Não | Validade | ❌ |
| `created_at` | TIMESTAMP | Não | Data de criação | ❌ |

**⚠️ STATUS: Tabela 100% ociosa.** Criada na migration mas nenhum código lê ou escreve nela. O sistema usa apenas `$_SESSION` nativas do PHP.

#### `customer_portal_messages` — Mensagens

| Coluna | Tipo | Nullable | Descrição | Usado? |
|--------|------|----------|-----------|--------|
| `id` | INT AUTO_INCREMENT PK | Não | ID da mensagem | ✅ (model) |
| `customer_id` | INT FK→customers.id | Não | Dono da conversa | ✅ |
| `order_id` | INT FK→orders.id | Sim | Pedido associado | ✅ |
| `sender_type` | ENUM('customer','admin') | Não | Quem enviou | ✅ |
| `sender_id` | INT | Sim | ID do admin (se admin) | ✅ |
| `message` | TEXT | Não | Texto da mensagem | ✅ |
| `is_read` | TINYINT(1) DEFAULT 0 | Não | Lida? | ✅ |
| `read_at` | DATETIME | Sim | Quando foi lida | ✅ |
| `attachment_path` | VARCHAR(500) | Sim | Caminho do anexo | ✅ (model) / ❌ (sem upload) |
| `created_at` | TIMESTAMP | Não | Data de envio | ✅ |

**⚠️ STATUS: Model completo, mas sem view/controller.** `countUnread()` é chamado no dashboard mas as actions `messages` e `sendMessage` não existem no controller.

#### `customer_portal_config` — Configurações

| Coluna | Tipo | Nullable | Descrição | Usado? |
|--------|------|----------|-----------|--------|
| `id` | INT AUTO_INCREMENT PK | Não | ID | ✅ |
| `config_key` | VARCHAR(100) UNIQUE | Não | Chave | ✅ |
| `config_value` | TEXT | Sim | Valor | ✅ |
| `descricao` | VARCHAR(255) | Sim | Descrição legível | ✅ (apenas seed) |

**Dados iniciais (seed):**

| Chave | Valor Padrão | Lido no código? | Efetivamente usado? |
|-------|-------------|-----------------|---------------------|
| `portal_enabled` | `1` | ✅ `isPortalEnabled()` | ✅ Funcional |
| `allow_self_register` | `1` | ✅ `register()`, `login()` | ✅ Funcional |
| `allow_new_orders` | `1` | ❌ Nunca lido | ❌ Sem view/action |
| `allow_order_approval` | `1` | ❌ Nunca lido | ❌ Sem view/action |
| `allow_messages` | `1` | ❌ Nunca lido | ❌ Sem view/action |
| `magic_link_expiry_hours` | `24` | ❌ Nunca lido (hardcoded no model) | ⚠️ Ignorado |
| `show_prices_in_catalog` | `1` | ❌ Nunca lido | ❌ Sem catálogo |
| `require_password` | `0` | ❌ Nunca lido | ❌ Não influencia login |

### 2.2 Colunas Adicionadas em `orders`

| Coluna | Tipo | Descrição | Usada no portal? |
|--------|------|-----------|-----------------|
| `customer_approval_status` | ENUM('pendente','aprovado','recusado') | Status de aprovação | ✅ Lida (com `hasApprovalColumn()`) |
| `customer_approval_at` | DATETIME | Data da aprovação | ❌ Nunca lida |
| `customer_approval_ip` | VARCHAR(45) | IP da aprovação | ❌ Nunca lido |
| `customer_approval_notes` | TEXT | Notas do cliente | ❌ Nunca lidas |
| `portal_origin` | TINYINT(1) DEFAULT 0 | Se veio do portal | ❌ Nunca escrito |
| Índice `idx_orders_customer_portal` | (customer_id, status, pipeline_stage) | Performance | ✅ Implícito |

---

## ⚙️ 3. Backend — Análise de Cada Componente

### 3.1 `PortalController.php` (473 linhas)

**Métodos implementados:**

| Método | Linhas | Função | Funcionando? |
|--------|--------|--------|-------------|
| `__construct()` | 34-48 | Instancia DB, PortalAccess, CompanySettings, PortalLang | ✅ |
| `index()` | 55-68 | Redireciona para login ou dashboard conforme sessão | ✅ |
| `login()` | 77-166 | GET: exibe form / POST: valida email+senha, session_regenerate_id, dispatch evento | ✅ |
| `loginMagic()` | 171-220 | Valida token de magic link, login, invalida token | ✅ (depende de token existir) |
| `logout()` | 225-230 | Remove variáveis de sessão portal, redireciona | ✅ |
| `register()` | 235-310 | GET: exibe form / POST: valida, cria customer + portal_access | ✅ |
| `dashboard()` | 318-334 | Carrega stats, recentOrders, notifications, unreadMessages | ✅ |
| `profile()` | 341-354 | Carrega dados do cliente e acesso, renderiza form | ✅ |
| `updateProfile()` | 359-408 | Atualiza nome/phone/address/lang/senha | ✅ (falta validar senha atual) |
| `isPortalEnabled()` | 415-418 | Verifica config `portal_enabled` | ✅ |
| `renderDisabled()` | 423-429 | Renderiza tela de portal off | ✅ |
| `isAjax()` | 434-440 | Detecta requisição AJAX | ✅ |
| `findCustomerByEmail()` | 445-451 | Busca cliente por email | ✅ |

**Métodos AUSENTES (declarados em routes.php mas não existem):**

- `requestMagicLink()` — ❌ **CRÍTICO** — Form de login faz POST para essa action
- `forgotPassword()` — ❌ Rota existe, sem método
- `resetPassword()` — ❌ Rota existe, sem método
- `orders()` — ❌ Fase 2
- `orderDetail()` — ❌ Fase 2
- `approveOrder()` — ❌ Fase 2
- `rejectOrder()` — ❌ Fase 2
- `newOrder()` — ❌ Fase 3
- `getProducts()` — ❌ Fase 3
- `addToCart()` / `removeFromCart()` / `updateCartItem()` / `getCart()` — ❌ Fase 3
- `submitOrder()` — ❌ Fase 3
- `installments()` / `installmentDetail()` — ❌ Fase 4
- `tracking()` — ❌ Fase 4
- `messages()` / `sendMessage()` — ❌ Fase 5
- `documents()` / `downloadDocument()` — ❌ Fase 5

### 3.2 `PortalAccess.php` (569 linhas)

**Métodos e estado:**

| Método | Função | Funcionando? | Notas |
|--------|--------|-------------|-------|
| `create()` | Cria acesso + hash + evento | ✅ | — |
| `findByEmail()` | Busca por email | ✅ | — |
| `findByCustomerId()` | Busca por customer_id | ✅ | — |
| `findById()` | Busca por ID | ✅ | — |
| `readAll()` | Lista todos com JOIN customers | ✅ | Usada só no admin (futuramente) |
| `update()` | Atualiza email/senha/active/lang | ✅ | — |
| `delete()` | Remove acesso | ✅ | — |
| `isLocked()` | Verifica bloqueio | ✅ | — |
| `verifyPassword()` | Valida bcrypt | ✅ | — |
| `registerFailedAttempt()` | Incrementa falhas + evento | ✅ | — |
| `registerSuccessfulLogin()` | Zera falhas, atualiza last_login | ✅ | — |
| `generateMagicToken()` | Gera token 128 hex chars | ✅ | Nunca chamado (falta `requestMagicLink`) |
| `validateMagicToken()` | Valida token + expiração | ✅ | Funciona se token existir |
| `invalidateMagicToken()` | Anula token após uso | ✅ | — |
| `emailExists()` | Verifica duplicidade | ✅ | — |
| `customerHasAccess()` | Verifica se cliente tem acesso | ✅ | — |
| `getConfig()` | Lê config por chave | ✅ | — |
| `getAllConfig()` | Lê todas as configs | ✅ | — |
| `setConfig()` | INSERT/UPDATE config | ✅ | Nunca chamado no portal (sem tela admin de configs) |
| `hasApprovalColumn()` | Cache: verifica coluna em orders | ✅ | Boa prática de resiliência |
| `getDashboardStats()` | Contadores: ativos, aprovação, parcelas | ✅ | Resiliente a tabelas faltantes |
| `getRecentOrders()` | 5 últimos pedidos | ✅ | Usa `total_amount AS total` (correto) |
| `getRecentNotifications()` | Parcelas + aprovações pendentes | ✅ | Resiliente |

### 3.3 `PortalMessage.php` (162 linhas)

| Método | Função | Chamado por? |
|--------|--------|-------------|
| `create()` | Cria mensagem + evento | ❌ Nenhum controller |
| `getByCustomer()` | Lista mensagens com join users | ❌ Nenhuma view |
| `countUnread()` | Conta não lidas (de admin) | ✅ `dashboard()` |
| `markAsRead()` | Marca como lidas | ❌ Nenhum controller |
| `findById()` | Busca com validação de customer_id | ❌ Nenhum controller |
| `countUnreadFromCustomers()` | Para painel admin | ❌ Nenhum uso |

### 3.4 `PortalAuthMiddleware.php` (156 linhas)

| Método | Função | Funcionando? |
|--------|--------|-------------|
| `check()` | Redireciona se não autenticado | ✅ |
| `isAuthenticated()` | Verifica `portal_customer_id` | ✅ |
| `getCustomerId()` | Retorna customer_id da sessão | ✅ |
| `getAccessId()` | Retorna access_id da sessão | ✅ |
| `getLang()` | Retorna idioma | ✅ |
| `login()` | Seta variáveis de sessão | ✅ |
| `logout()` | Remove variáveis (preserva admin) | ✅ |
| `touch()` | Atualiza last_activity | ✅ |
| `checkInactivity()` | Timeout 60min (configurável) | ⚠️ Nunca chamado! |
| `getClientIp()` | IP real (CF/Proxy support) | ✅ |

**⚠️ PROBLEMA:** `checkInactivity()` nunca é invocado. O timeout de inatividade do portal **não funciona**. No `index.php`, o `SessionGuard::checkInactivity()` roda apenas para `user_id` (admin). Para portal, ninguém chama `PortalAuthMiddleware::checkInactivity()`.

### 3.5 `PortalLang.php` (116 linhas)

| Aspecto | Estado |
|---------|--------|
| Inicialização automática | ✅ Se não inicializado, chama `init()` |
| Fallback para pt-br | ✅ |
| Placeholders `:name` | ✅ |
| Idiomas disponíveis | Apenas `pt-br` (en e es comentados) |
| Total de chaves traduzidas | 204 |

### 3.6 `portal_helper.php` (109 linhas)

| Helper | Função | Usado? |
|--------|--------|--------|
| `__p()` | Atalho para `PortalLang::get()` | ✅ Extensivamente |
| `portal_money()` | Formata R$ 1.500,00 | ✅ Dashboard |
| `portal_date()` | Formata d/m/Y | ✅ Dashboard, orders |
| `portal_datetime()` | Formata d/m/Y H:i | ❌ Sem uso atual |
| `portal_stage_class()` | CSS class por pipeline stage | ✅ Dashboard |
| `portal_stage_icon()` | Ícone por pipeline stage | ❌ Sem uso atual |

---

## 🖼️ 4. Frontend — Views, CSS, JS

### 4.1 Layout (header/footer)

**`header.php` (autenticado):**
- Topbar fixa: logo/nome da empresa + saudação + botão logout
- Meta tags PWA: theme-color `#667eea` (roxo — **desalinhado** com Akti)
- Importa: Bootstrap 5.3, Inter font, Font Awesome 6.4, portal.css
- `max-width: 600px` no container principal (boa prática mobile)
- **Problema desktop:** em ≥768px o bottom nav desaparece mas **NÃO há navegação substituta**

**`footer.php` (autenticado):**
- Bottom nav com 5 itens: Home, Pedidos, (+) Novo, Financeiro, Perfil
- Botão central (+) com gradiente flutuante
- PWA install banner
- Service Worker registration (sem scope limitado)

**`header_auth.php` (login/register):**
- Fundo com gradient roxo `#667eea → #764ba2` (desalinhado)
- Mesmo CSS/JS imports

**`footer_auth.php`:**
- Apenas Bootstrap JS + portal.js (mínimo)

### 4.2 CSS (`portal.css` — 904 linhas)

**Design System Atual:**

```
Cores:
  --portal-primary: #667eea (roxo/violeta) ← DESALINHADO com Akti (#3b82f6 blue)
  --portal-gradient: #667eea → #764ba2     ← Agressivo, não minimalista
  --portal-surface: #f0f2f5                ← OK
  --portal-bg: #ffffff                     ← OK
  --portal-text: #1a1a2e                   ← OK
  --portal-muted: #6c757d                  ← OK

Raio de borda: 16px (cards), 12px (buttons), 8px (inputs)
Sombras: Sutis (bom)
Font: Inter (bom)
```

**Componentes CSS implementados:**

| Componente | Linhas | Qualidade | Problema |
|------------|--------|-----------|----------|
| Base/reset | 19-55 | ✅ Bom | — |
| Topbar | 58-119 | ✅ Bom | Brand usa gradient text (estético, não funcional) |
| Main content | 122-137 | ✅ Bom | max-width 600px (mobile-only) |
| Bottom nav | 140-218 | ✅ Bom | Botão central com sombra colorida |
| Auth pages | 223-295 | ✅ Bom | Fundo gradient agressivo |
| Forms | 299-371 | ✅ Bom | — |
| Buttons | 376-421 | ✅ Bom | Gradiente 3D (não minimalista) |
| Cards | 425-462 | ✅ Bom | — |
| Greeting | 467-484 | ✅ Bom | — |
| Stats grid | 488-510 | ✅ Bom | 2 colunas (bom para mobile) |
| Sections | 512-540 | ✅ Bom | — |
| Notifications | 543-600 | ✅ Bom | — |
| Order list | 604-660 | ✅ Bom | — |
| Page header | 665-673 | ✅ Bom | — |
| Empty state | 678-707 | ✅ Bom | — |
| Alerts | 716-721 | ✅ Bom | — |
| PWA banner | 726-768 | ✅ Bom | — |
| Responsive | 783-815 | ⚠️ Incompleto | Desktop: nav desaparece sem substituto |
| Animations | 820-838 | ✅ Bom | FadeIn sutil |
| Dark mode | 844-890 | ⚠️ Automático | Sem toggle manual, pode conflitar |
| Utility | 896-904 | ✅ Bom | — |

### 4.3 JS (`portal.js` — 231 linhas)

**Funcionalidades implementadas:**

| Feature | Linhas | Funcionando? |
|---------|--------|-------------|
| CSRF token reader | 20-24 | ✅ |
| `Portal.post()` — AJAX POST | 33-60 | ✅ (não usado ainda por falta de views) |
| `Portal.get()` — AJAX GET | 68-95 | ✅ (idem) |
| `Portal.toast()` — notificação | 103-133 | ✅ (não chamado no fluxo atual) |
| PWA install prompt | 143-182 | ✅ |
| PWA dismiss (7 dias) | 185-192 | ✅ |
| Touch feedback listener | 197 | ⚠️ Apenas listener vazio |
| Form loading state | 203-216 | ✅ Bom (spinner no botão) |
| Alert auto-hide (5s) | 221-231 | ✅ |

### 4.4 Service Worker (`portal-sw.js` — 104 linhas)

| Aspecto | Estado |
|---------|--------|
| Estratégia de cache | Network-first para HTML, cache-first para assets estáticos | ✅ |
| Assets cacheados | portal.css, portal.js, logo SVG, Bootstrap CDN, Font Awesome CDN, Google Fonts | ✅ |
| Versioning | `CACHE_NAME = 'portal-v1'` (limpeza de caches antigos) | ✅ |
| Scope | ❌ **SEM SCOPE** — registrado na raiz, intercepta TODAS as requisições do domínio |
| Offline fallback | Apenas cache match (sem página offline customizada) | ⚠️ |

---

## 🔐 5. Fluxo de Autenticação Atual

### 5.1 Duas Entradas Separadas

```
ADMIN:   subdomain.akti.com?page=login
         → UserController::login()
         → Valida users table
         → $_SESSION['user_id'] = X
         → Redirect: ?page=home (dashboard admin)

PORTAL:  subdomain.akti.com?page=portal
         → PortalController::login()
         → Valida customer_portal_access table
         → $_SESSION['portal_customer_id'] = X
         → Redirect: ?page=portal&action=dashboard
```

**Problemas desta arquitetura:**
1. ❌ O cliente precisa saber que deve acessar `?page=portal`
2. ❌ São 2 telas de login completamente diferentes
3. ❌ Magic link não funciona (falta `requestMagicLink`)
4. ❌ "Esqueci minha senha" não funciona (falta `forgotPassword`)
5. ❌ Não existe detecção automática do tipo de conta

### 5.2 Sessões Isoladas

As sessões de admin e portal coexistem no mesmo `$_SESSION` PHP, mas usam prefixos diferentes:

| Admin | Portal |
|-------|--------|
| `$_SESSION['user_id']` | `$_SESSION['portal_customer_id']` |
| `$_SESSION['user_name']` | `$_SESSION['portal_customer_name']` |
| `$_SESSION['user_role']` | `$_SESSION['portal_email']` |
| `$_SESSION['group_id']` | `$_SESSION['portal_access_id']` |
| `$_SESSION['last_activity']` | `$_SESSION['portal_last_activity']` |
| — | `$_SESSION['portal_lang']` |
| — | `$_SESSION['portal_cart']` |

**Isolamento de logout:** `PortalAuthMiddleware::logout()` só remove variáveis `portal_*`, não destrói a sessão inteira. Isso permite que um admin esteja logado E visualize o portal simultaneamente (se implementado).

### 5.3 Fluxo de Timeout

| Admin | Portal |
|-------|--------|
| `SessionGuard::checkInactivity()` chamado no `index.php` | `PortalAuthMiddleware::checkInactivity()` **NUNCA chamado** ❌ |
| Configurável via `company_settings.session_timeout_minutes` | Fixo em 60 min (parâmetro do método, mas nunca invocado) |

---

## 📋 6. Mapa de Actions — Status Real

### 6.1 Actions Funcionais

| Action | Método HTTP | Auth? | Método | Eventos |
|--------|------------|-------|--------|---------|
| `index` | GET | Não | `index()` | — |
| `login` | GET/POST | Não | `login()` | `portal.customer.logged_in` |
| `loginMagic` | GET | Não | `loginMagic()` | `portal.customer.logged_in` (method=magic_link) |
| `logout` | GET | Sim | `logout()` | — |
| `register` | GET/POST | Não | `register()` | `portal.access.created` |
| `dashboard` | GET | Sim | `dashboard()` | — |
| `profile` | GET | Sim | `profile()` | — |
| `updateProfile` | POST | Sim | `updateProfile()` | — |

### 6.2 Actions Mapeadas Sem Implementação

| Action | Fase | Dependências |
|--------|------|-------------|
| `requestMagicLink` | 1 (urgente) | Precisa de serviço de e-mail |
| `forgotPassword` | 1 (urgente) | Precisa de serviço de e-mail + token |
| `resetPassword` | 1 (urgente) | Precisa de token de reset |
| `orders` | 2 | View de listagem |
| `orderDetail` | 2 | View de detalhe + items |
| `approveOrder` | 2 | Escreve em `customer_approval_*` |
| `rejectOrder` | 2 | Idem |
| `newOrder` | 3 | Catálogo + carrinho |
| `getProducts` | 3 | AJAX com Product model |
| `addToCart` | 3 | Sessão `portal_cart` |
| `removeFromCart` | 3 | Idem |
| `updateCartItem` | 3 | Idem |
| `getCart` | 3 | Idem |
| `submitOrder` | 3 | Order model + status orcamento |
| `installments` | 4 | Tabela `order_installments` |
| `installmentDetail` | 4 | Gateway de pagamento |
| `tracking` | 4 | Campo `tracking_code` em orders |
| `messages` | 5 | PortalMessage model (pronto) |
| `sendMessage` | 5 | PortalMessage::create() |
| `documents` | 5 | Tabela de documentos (NF-e etc) |
| `downloadDocument` | 5 | Filesystem + permissão |

---

## 🚨 7. Campos e Funcionalidades NÃO Funcionais

### 7.1 Crítico (Quebra UX)

| # | Item | Impacto | Correção |
|---|------|---------|----------|
| 1 | **`requestMagicLink` não existe** | Botão de "Receber link de acesso" na tela de login faz POST para action inexistente → erro 404 ou fallback para index | Implementar método no controller + serviço de e-mail |
| 2 | **`forgotPassword` não existe** | Sem recuperação de senha | Implementar método + token de reset + e-mail |
| 3 | **`resetPassword` não existe** | Idem | Idem |
| 4 | **Timeout de inatividade NÃO funciona** | `PortalAuthMiddleware::checkInactivity()` existe mas nunca é chamado. Sessão do portal dura indefinidamente | Chamar no `index.php` antes do dispatch de rotas do portal |
| 5 | **Navegação desktop inexistente** | Em telas ≥768px, a bottom nav desaparece e **nenhum menu substituto é mostrado**. O usuário fica sem navegação | Implementar sidebar ou top-nav para desktop |
| 6 | **Config `magic_link_expiry_hours` ignorada** | O model usa `$expiryHours = 24` como default do parâmetro, mas nunca lê a config do banco | Ler do `getConfig('magic_link_expiry_hours')` |
| 7 | **Config `require_password` ignorada** | Nunca lida. Não muda comportamento do login | Implementar lógica condicional no login |

### 7.2 Moderado (Funciona parcialmente)

| # | Item | Impacto |
|---|------|---------|
| 8 | `customer_portal_sessions` — tabela ociosa | Sem gerenciamento multi-dispositivo |
| 9 | `portal_origin` — nunca escrita | Não sabe se pedido veio do portal |
| 10 | `customer_approval_at/ip/notes` — nunca lidos | Dados de aprovação não utilizados |
| 11 | Configs `allow_new_orders`, `allow_order_approval`, `allow_messages`, `show_prices_in_catalog` — nunca lidas | Flags sem efeito |
| 12 | `Portal.post()` / `Portal.get()` / `Portal.toast()` — nunca chamados | JS preparado mas sem views que usem |
| 13 | `portal_datetime()` e `portal_stage_icon()` — nunca chamados | Helpers ociosos |
| 14 | Perfil: senha pode ser alterada sem informar a senha atual | Falha de segurança leve |
| 15 | Dark mode automático sem toggle | Pode surpreender usuário |

### 7.3 Visual/UX (Desalinhamento)

| # | Item | Impacto |
|---|------|---------|
| 16 | Paleta de cores portal ≠ Akti admin | `#667eea` (roxo) vs `#3b82f6` (azul) |
| 17 | Gradient agressivo no fundo de auth | Não é minimalista |
| 18 | Botões com sombra 3D e gradient | Não é clean |
| 19 | `manifest.json` é do sistema admin | PWA instala com dados errados |
| 20 | Service Worker sem scope | Intercepta requests do admin |
| 21 | theme-color `#667eea` nas meta tags | Barra do navegador roxa em vez de azul |

---

## 🔒 8. Segurança — Auditoria

| Recurso | Status | Detalhes |
|---------|--------|----------|
| CSRF em forms | ✅ OK | `csrf_field()` em todos os formulários POST |
| CSRF meta tag (AJAX) | ✅ OK | `csrf_meta()` no header, usado por `Portal.csrfToken()` |
| Password hash bcrypt | ✅ OK | `password_hash(PASSWORD_BCRYPT)` |
| Rate limiting (por conta) | ✅ OK | 5 tentativas → 15min lockout com `registerFailedAttempt()` |
| Rate limiting (por IP) | ❌ FALTA | Apenas por conta. IP não é bloqueado globalmente (spray attack) |
| Evento de bloqueio | ✅ OK | `portal.access.locked` disparado |
| Filtro por customer_id | ✅ OK | Todas queries filtram pelo ID do cliente logado |
| Session fixation | ✅ OK | `session_regenerate_id(true)` em `login()` e `loginMagic()` |
| Session timeout | ❌ FALHA | `checkInactivity()` existe mas **nunca é chamado** |
| IP logging | ✅ OK | `last_login_ip` atualizado no login |
| Magic link uso único | ✅ OK | Token invalidado via `invalidateMagicToken()` |
| Magic link expiry | ✅ OK | `magic_token_expires_at > NOW()` |
| Input sanitization | ✅ OK | Usa `Input::post()` e `Input::get()` |
| XSS prevention | ✅ OK | `e()` e `eAttr()` em todas as views |
| SQL injection | ✅ OK | Prepared statements em todas queries |
| Validação de senha atual no perfil | ❌ FALTA | Senha pode ser alterada sem confirmar a atual |
| Força de senha | ❌ FALTA | Apenas `minlength="6"` no HTML, sem validação server-side |
| Log de ações sensíveis | ❌ FALTA | Login é logado via eventos, mas alteração de senha, aprovação etc. não |
| 2FA | ❌ Não implementado | — |

---

## 🔀 9. Proposta: Login Unificado (Detecção Automática)

### 9.1 Conceito

Uma única tela de login (`?page=login`) que detecta automaticamente se o e-mail pertence a um **usuário do sistema** ou a um **cliente do portal**, redirecionando para o ambiente correto.

```
                    ┌─────────────────────────┐
                    │   TELA DE LOGIN ÚNICA    │
                    │   ?page=login            │
                    │   Email + Senha          │
                    └───────────┬──────────────┘
                                │
                    ┌───────────▼──────────────┐
                    │  1. Busca em `users`     │
                    └───────────┬──────────────┘
                                │
                   ┌────────────┴────────────┐
                   │                         │
              Encontrou?                Não encontrou
                   │                         │
              ✅ Login Admin            ┌────▼──────────────────┐
              $_SESSION['user_id']      │  2. Busca em          │
              → redirect ?page=home     │  customer_portal_access│
                                        └────┬──────────────────┘
                                             │
                                ┌────────────┴────────────┐
                                │                         │
                           Encontrou?                Não encontrou
                                │                         │
                           ✅ Login Portal           ❌ Erro genérico
                           $_SESSION['portal_*']     "E-mail ou senha
                           → redirect ?page=portal   inválidos."
                           &action=dashboard
```

### 9.2 Onde Alterar

**Arquivo:** `app/controllers/UserController.php`, método `login()`, **após o bloco de falha do login de usuário**.

```php
// Após: if (!$this->userModel->login($email, $password)) {
//   ANTES de definir $error = "Credenciais inválidas..."

// ── Tentativa de login como cliente do portal ──
$portalAccess = new \Akti\Models\PortalAccess($this->db);
$access = $portalAccess->findByEmail($email);

if ($access
    && $access['is_active']
    && !$portalAccess->isLocked($access)
    && !empty($access['password_hash'])
    && $portalAccess->verifyPassword($password, $access['password_hash'])
) {
    // Login como cliente do portal
    $portalAccess->registerSuccessfulLogin($access['id'], $ip);
    $this->loginAttempt->clearFailures($ip, $email);

    session_regenerate_id(true);

    $customer = (new \Akti\Models\Customer($this->db))->readOne($access['customer_id']);
    \Akti\Middleware\PortalAuthMiddleware::login(
        $access['customer_id'],
        $access['id'],
        $customer['name'] ?? 'Cliente',
        $access['email'],
        $access['lang'] ?? 'pt-br'
    );

    header('Location: ?page=portal&action=dashboard');
    exit;
}

// Rate limiting para portal também
if ($access && $access['is_active'] && !empty($access['password_hash'])) {
    $portalAccess->registerFailedAttempt($access['id']);
}

// Nenhum dos dois → erro genérico
$error = 'E-mail ou senha inválidos.';
```

### 9.3 Vantagens

- ✅ Cliente não precisa saber de URL diferente
- ✅ Uma URL única para o tenant
- ✅ Mantém sessões internamente isoladas
- ✅ A tela de login do portal (`?page=portal&action=login`) continua existindo como fallback
- ✅ No futuro, link mágico e "esqueci senha" podem ser unificados também

### 9.4 Considerações

- A mensagem de erro deve ser **genérica** ("E-mail ou senha inválidos") para não vazar se o e-mail é de usuário ou cliente
- O rate limiting do admin (`LoginAttempt`) deve cobrir ambas as tentativas
- Se o mesmo e-mail existir em `users` E em `customer_portal_access`, o admin tem prioridade
- O `UserController` precisa fazer `use Akti\Models\PortalAccess;` e `use Akti\Middleware\PortalAuthMiddleware;`

---

## 🎨 10. Proposta: Visual Minimalista Akti

### 10.1 Paleta de Cores Proposta

Alinhada com `assets/css/theme.css` (sistema admin):

```css
:root {
    /* ── Primary: Azul Akti (alinhado com --accent-color do admin) ── */
    --portal-primary: #3b82f6;          /* Blue 500 */
    --portal-primary-dark: #2563eb;     /* Blue 600 */
    --portal-primary-light: #60a5fa;    /* Blue 400 */
    --portal-primary-rgb: 59, 128, 246;

    /* ── Gradient: Sutil e profissional ── */
    --portal-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    --portal-gradient-soft: linear-gradient(135deg, rgba(59,130,246,0.08) 0%, rgba(29,78,216,0.08) 100%);

    /* ── Superfícies (tons claros Slate) ── */
    --portal-surface: #f8fafc;          /* Slate 50 */
    --portal-bg: #ffffff;               /* Branco */
    --portal-text: #1e293b;             /* Slate 800 */
    --portal-muted: #94a3b8;            /* Slate 400 */
    --portal-border: #e2e8f0;           /* Slate 200 */

    /* ── Status ── */
    --portal-success: #22c55e;
    --portal-warning: #f59e0b;
    --portal-danger: #ef4444;
    --portal-info: #06b6d4;

    /* ── Sombras sutis ── */
    --portal-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
    --portal-shadow-lg: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);

    /* ── Bordas mais suaves ── */
    --portal-radius: 12px;             /* Era 16px — mais contido */
    --portal-radius-md: 8px;           /* Era 12px */
    --portal-radius-sm: 6px;           /* Era 8px */
}
```

### 10.2 Princípios de Design Minimalista

| Princípio | Aplicação Concreta |
|-----------|-------------------|
| **Cores sólidas** | Remover gradientes dos botões. Usar `background: var(--portal-primary)` sólido |
| **Sombras sutis** | Trocar `box-shadow: 0 4px 15px rgba(102,126,234,0.3)` por `var(--portal-shadow)` |
| **Espaço em branco** | Manter paddings generosos (20px+). Gap 16px entre cards |
| **Tipografia contida** | Inter 400/500/600. Tamanhos: 0.8rem (caption), 0.875rem (body), 1rem (h3), 1.125rem (h2) |
| **Ícones outline** | Substituir ícones `fas` (filled) por `far` (regular/outline) na bottom nav |
| **Sem dark mode automático** | Remover `@media (prefers-color-scheme: dark)`. Adicionar toggle manual no futuro |
| **Sem gradient no fundo auth** | Fundo `var(--portal-surface)` com card centralizado branco |
| **Botão central simplificado** | Sem sombra colorida exagerada. Círculo azul sólido flat |

### 10.3 Componentes a Refatorar

| Componente | Atual | Proposto |
|------------|-------|----------|
| **Fundo auth (login/register)** | `background: linear-gradient(135deg, #667eea → #764ba2)` | `background: var(--portal-surface)` (branco acinzentado) |
| **Botão primário** | Gradient + sombra 3D | `background: #3b82f6`. Border-radius: 8px. Sombra mínima |
| **Botão outline** | Border `#667eea` | Border `#3b82f6` |
| **Stat cards** | Números com cores vibrantes | Números em `--portal-text`. Badge colorido pequeno |
| **Topbar brand** | Gradient text com `-webkit-background-clip` | Texto simples `color: var(--portal-text)` |
| **Bottom nav center** | Círculo com gradiente + sombra forte | Círculo azul sólido. Sombra sutil |
| **Nav item ativo** | `color: #667eea` | `color: #3b82f6` |
| **Input focus** | `border-color: #667eea` + sombra roxa | `border-color: #3b82f6` + sombra azul sutil |
| **theme-color** | `#667eea` | `#3b82f6` |
| **Auth icon** | `color: #667eea` | `color: #3b82f6` |
| **Links** | `color: #667eea` | `color: #3b82f6` |

---

## 🖥️ 11. Proposta: Navegação Desktop

### 11.1 Problema

Em `@media (min-width: 768px)`, o CSS atual faz:
```css
.portal-bottom-nav { display: none; }
```

Mas **nenhum menu substituto é mostrado**. O usuário desktop perde toda navegação.

### 11.2 Solução: Top Navigation Bar em Desktop

Adicionar links de navegação **dentro da topbar** existente, visíveis apenas em ≥768px:

```html
<!-- Dentro de .portal-topbar-inner, entre left e right -->
<nav class="portal-desktop-nav d-none d-md-flex">
    <a href="?page=portal&action=dashboard" class="portal-desktop-link active">
        <i class="far fa-home me-1"></i> Home
    </a>
    <a href="?page=portal&action=orders" class="portal-desktop-link">
        <i class="far fa-box me-1"></i> Pedidos
    </a>
    <a href="?page=portal&action=newOrder" class="portal-desktop-link">
        <i class="far fa-plus-circle me-1"></i> Novo Pedido
    </a>
    <a href="?page=portal&action=installments" class="portal-desktop-link">
        <i class="far fa-wallet me-1"></i> Financeiro
    </a>
    <a href="?page=portal&action=profile" class="portal-desktop-link">
        <i class="far fa-user me-1"></i> Perfil
    </a>
</nav>
```

**CSS necessário:**
```css
.portal-desktop-nav {
    display: flex;
    align-items: center;
    gap: 4px;
}
.portal-desktop-link {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--portal-muted);
    text-decoration: none;
    padding: 6px 12px;
    border-radius: var(--portal-radius-sm);
    transition: all 0.15s;
}
.portal-desktop-link:hover,
.portal-desktop-link.active {
    color: var(--portal-primary);
    background: rgba(59, 130, 246, 0.06);
}
```

### 11.3 Layout Desktop

```
┌─────────────────────────────────────────────────────────────┐
│  Logo   │ Home  Pedidos  Novo  Financeiro  Perfil │  Sair  │ ← Topbar (≥768px)
├─────────────────────────────────────────────────────────────┤
│                                                             │
│                   CONTEÚDO (max-width: 960px)               │
│                                                             │
└─────────────────────────────────────────────────────────────┘

┌───────────────────────────────────────┐
│  Logo                         │ Sair  │ ← Topbar (mobile)
├───────────────────────────────────────┤
│                                       │
│         CONTEÚDO (max-width: 600px)   │
│                                       │
├─ Home ─ Pedidos ─ [+] ─ R$ ─ Perfil ─┤ ← Bottom nav (mobile)
└───────────────────────────────────────┘
```

---

## 💡 12. Sugestões de Melhoria Priorizadas

### 12.1 Prioridade ALTA (Fase 1.1 — Correções Imediatas)

| # | Melhoria | Tipo | Complexidade | Impacto |
|---|----------|------|-------------|---------|
| 1 | **Login unificado** no `UserController::login()` | Funcional | Média | 🔴 Crítico — UX |
| 2 | **`requestMagicLink`** — gerar token + enviar e-mail | Funcional | Média | 🔴 Crítico — botão quebrado |
| 3 | **`forgotPassword` / `resetPassword`** | Funcional | Média | 🔴 Crítico — UX |
| 4 | **Chamar `checkInactivity()`** no index.php para rotas do portal | Segurança | Baixa | 🔴 Crítico — sessão infinita |
| 5 | **Navegação desktop** (top nav ≥768px) | UX | Baixa | 🔴 Crítico — desktop inutilizável |
| 6 | **Alinhar paleta de cores** com Akti (#3b82f6) | Visual | Média | 🟡 Alta — identidade |
| 7 | **Exigir senha atual** no `updateProfile` | Segurança | Baixa | 🟡 Alta |
| 8 | **Ler config `magic_link_expiry_hours`** no model | Bug | Mínima | 🟡 Média |
| 9 | **Ler config `require_password`** no login | Bug | Baixa | 🟡 Média |

### 12.2 Prioridade MÉDIA (Fase 1.2 — Polimento)

| # | Melhoria | Tipo | Complexidade |
|---|----------|------|-------------|
| 10 | Manifest JSON separado para o portal (`portal-manifest.json`) | PWA | Baixa |
| 11 | Limitar scope do service worker | PWA | Baixa |
| 12 | Usar `customer_portal_sessions` para multi-device | Segurança | Média |
| 13 | Log de ações sensíveis (alteração senha, login) via Logger | Segurança | Baixa |
| 14 | Rate limiting por IP global | Segurança | Média |
| 15 | Máscara de telefone e CPF/CNPJ nos formulários | UX | Baixa |
| 16 | Validação de força de senha (min 8, letras+números) | Segurança | Baixa |
| 17 | Remover dark mode automático (ou adicionar toggle) | Visual | Baixa |
| 18 | Skeleton loading nos cards do dashboard | UX | Média |
| 19 | Pull-to-refresh no dashboard | UX | Média |

### 12.3 Prioridade BAIXA (Futuro)

| # | Melhoria | Tipo |
|---|----------|------|
| 20 | 2FA opcional via e-mail | Segurança |
| 21 | Notificações push via SW | UX |
| 22 | Foto/avatar do cliente | UX |
| 23 | Haptic feedback em botões | UX |
| 24 | Swipe para ação em cards | UX |
| 25 | Splash screen PWA customizada | PWA |
| 26 | Indicador offline | PWA |
| 27 | Idiomas adicionais (en, es) | i18n |

---

## 📅 13. Roadmap por Fases

### Fase 1.1 — Correções Imediatas
```
[ ] Implementar requestMagicLink (action + método + envio de e-mail)
[ ] Implementar forgotPassword / resetPassword
[ ] Implementar login unificado no UserController::login()
[ ] Chamar PortalAuthMiddleware::checkInactivity() no index.php
[ ] Exigir senha atual no updateProfile
[ ] Alinhar paleta de cores com Akti (trocar roxo por azul)
[ ] Implementar navegação desktop (top nav ≥768px)
[ ] Ler magic_link_expiry_hours da config
[ ] Ler require_password da config
[ ] Criar portal-manifest.json separado
[ ] Limitar scope do SW
```

### Fase 2 — Pedidos
```
[ ] orders() — listagem com filtros (abertos, aprovação, concluídos)
[ ] orderDetail() — timeline visual + itens + parcelas
[ ] approveOrder() / rejectOrder() — com registro de IP e notas
[ ] Ler/usar allow_order_approval da config
[ ] Views: orders/index.php, orders/detail.php, orders/approve.php
```

### Fase 3 — Novo Pedido / Catálogo
```
[ ] newOrder() — catálogo de produtos com busca
[ ] Carrinho (add/remove/update) via sessão portal_cart
[ ] submitOrder() — cria pedido como status=orcamento
[ ] Ler/usar allow_new_orders e show_prices_in_catalog da config
[ ] Setar portal_origin=1 nos pedidos criados pelo portal
[ ] Views: orders/new.php, orders/cart.php
```

### Fase 4 — Financeiro + Rastreamento
```
[ ] installments() — parcelas abertas e pagas
[ ] installmentDetail() — link de pagamento
[ ] tracking() — código de rastreio + timeline
[ ] Views: financial/index.php, tracking/index.php
```

### Fase 5 — Mensagens + Documentos
```
[ ] messages() / sendMessage() — chat por pedido (model já existe)
[ ] Ler/usar allow_messages da config
[ ] documents() / downloadDocument() — NF-e, boletos, comprovantes
[ ] Views: messages/index.php, documents/index.php
```

### Fase 6 — Admin do Portal
```
[ ] Tela admin para gerenciar acessos do portal
[ ] Tela admin para configurações do portal (customer_portal_config)
[ ] Dashboard admin: métricas de acesso do portal
[ ] Gerenciamento de sessões ativas (customer_portal_sessions)
```

---

## 🔗 14. Mapa de Dependências

```
PortalController
  │
  ├── PortalAccess (model)
  │   ├── customer_portal_access (tabela)
  │   ├── customer_portal_config (tabela)
  │   ├── orders (tabela) — getDashboardStats(), getRecentOrders()
  │   │   └── Campos: total_amount, status, pipeline_stage, customer_id
  │   │   └── Campos opcionais: customer_approval_status (via hasApprovalColumn)
  │   │   └── Campos ociosos: customer_approval_at/ip/notes, portal_origin
  │   └── order_installments (tabela) — getDashboardStats(), getRecentNotifications()
  │       └── Campos: amount, due_date, status, installment_number, order_id
  │
  ├── PortalMessage (model)
  │   └── customer_portal_messages (tabela) — countUnread() [dashboard]
  │
  ├── Customer (model)
  │   └── customers (tabela) — readOne(), create()
  │
  ├── CompanySettings (model)
  │   └── company_settings (tabela) — company_name, company_logo
  │
  ├── PortalAuthMiddleware (middleware)
  │   └── $_SESSION['portal_*'] — sessão isolada
  │   └── ⚠️ checkInactivity() NUNCA chamado
  │
  ├── PortalLang (service)
  │   └── app/lang/pt-br/portal.php (204 chaves)
  │
  └── portal_helper.php (utils)
      └── __p(), portal_money(), portal_date(), portal_stage_class(), portal_stage_icon()

customer_portal_sessions → ❌ OCIOSA (nenhum código lê/escreve)

index.php (Router)
  └── routes.php → portal (public, before_auth)
      └── 28 actions mapeadas (8 funcionais, 20 stub)
```

---

## 📎 15. Referência Rápida de Actions

| Action | HTTP | Auth? | Status | Notas |
|--------|------|-------|--------|-------|
| `index` | GET | ❌ | ✅ OK | Redireciona para login/dashboard |
| `login` | GET/POST | ❌ | ✅ OK | session_regenerate_id(true) ✅ |
| `loginMagic` | GET | ❌ | ✅ OK | Precisa de token gerado previamente |
| `requestMagicLink` | POST | ❌ | ❌ **NÃO EXISTE** | Form no login.php aponta para cá |
| `logout` | GET | ✅ | ✅ OK | Preserva sessão admin |
| `register` | GET/POST | ❌ | ✅ OK | Controlado por allow_self_register |
| `forgotPassword` | GET/POST | ❌ | ❌ **NÃO EXISTE** | Rota mapeada, sem método |
| `resetPassword` | GET/POST | ❌ | ❌ **NÃO EXISTE** | Rota mapeada, sem método |
| `dashboard` | GET | ✅ | ✅ OK | Stats + orders + notifications |
| `profile` | GET | ✅ | ✅ OK | Dados + idioma + senha |
| `updateProfile` | POST | ✅ | ⚠️ PARCIAL | Falta validar senha atual |
| `orders` | GET | ✅ | ❌ Stub | Fase 2 |
| `orderDetail` | GET | ✅ | ❌ Stub | Fase 2 |
| `approveOrder` | POST | ✅ | ❌ Stub | Fase 2 |
| `rejectOrder` | POST | ✅ | ❌ Stub | Fase 2 |
| `newOrder` | GET | ✅ | ❌ Stub | Fase 3 |
| `getProducts` | GET/AJAX | ✅ | ❌ Stub | Fase 3 |
| `addToCart` | POST/AJAX | ✅ | ❌ Stub | Fase 3 |
| `removeFromCart` | POST/AJAX | ✅ | ❌ Stub | Fase 3 |
| `updateCartItem` | POST/AJAX | ✅ | ❌ Stub | Fase 3 |
| `getCart` | GET/AJAX | ✅ | ❌ Stub | Fase 3 |
| `submitOrder` | POST | ✅ | ❌ Stub | Fase 3 |
| `installments` | GET | ✅ | ❌ Stub | Fase 4 |
| `installmentDetail` | GET | ✅ | ❌ Stub | Fase 4 |
| `tracking` | GET | ✅ | ❌ Stub | Fase 4 |
| `messages` | GET | ✅ | ❌ Stub | Fase 5 |
| `sendMessage` | POST | ✅ | ❌ Stub | Fase 5 |
| `documents` | GET | ✅ | ❌ Stub | Fase 5 |
| `downloadDocument` | GET | ✅ | ❌ Stub | Fase 5 |

---

## 📊 Resumo Executivo

| Métrica | Valor |
|---------|-------|
| Total de arquivos do portal | 17 |
| Total de linhas de código | ~3.700 |
| Actions mapeadas no router | 28 |
| Actions com método no controller | 8 (28.5%) |
| Actions stub (sem método) | 20 (71.5%) |
| Tabelas criadas | 4 |
| Tabelas em uso | 3 (1 ociosa: sessions) |
| Colunas adicionadas em orders | 5 (+1 índice) |
| Colunas em uso efetivo | 1 de 5 |
| Configs no banco | 8 |
| Configs efetivamente lidas | 2 de 8 |
| Chaves de tradução | 204 |
| Vulnerabilidades encontradas | 3 (timeout, senha sem current, rate limit IP) |
| Problemas visuais | 5 (paleta, gradient, nav desktop, manifest, SW scope) |

---

*Relatório V2 — Gerado em 24/03/2026. Serve como base técnica completa para todas as decisões de implementação, refatoração e melhoria do Portal do Cliente Akti.*
