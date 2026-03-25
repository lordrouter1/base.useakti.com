# 🤖 PROMPTS DE IMPLEMENTAÇÃO — Portal do Cliente (Akti)

> **Referência:** `ROADMAP_PORTAL.md`, `AUDITORIA_PORTAL_CLIENTE.md`, `RELATORIO_PORTAL_CLIENTE_V2.md`  
> **Como usar:** Cada prompt abaixo deve ser enviado ao assistente de IA para executar a fase correspondente.  
> **Ordem:** Respeite a sequência das fases. 1A e 1B podem ser paralelas.

---

## 📋 Índice de Prompts

| # | Fase | Prompt |
|---|------|--------|
| 1 | Fase 1A | [Correções Críticas & Segurança](#fase-1a--correções-críticas--segurança) |
| 2 | Fase 1B | [Visual & Navegação](#fase-1b--visual--navegação) |
| 3 | Fase 2 | [Meus Pedidos](#fase-2--meus-pedidos) |
| 4 | Fase 3 | [Financeiro & Rastreamento](#fase-3--financeiro--rastreamento) |
| 5 | Fase 4 | [Novo Pedido / Catálogo](#fase-4--novo-pedido--catálogo) |
| 6 | Fase 5 | [Mensagens & Documentos](#fase-5--mensagens--documentos) |
| 7 | Fase 6 | [Admin do Portal](#fase-6--admin-do-portal) |
| 8 | Fase 7 | [Polimento & PWA Avançado](#fase-7--polimento--pwa-avançado) |

---

## Fase 1A — Correções Críticas & Segurança

### Prompt

```
Implemente a Fase 1A do Portal do Cliente (Akti) — Correções Críticas & Segurança.

Consulte os seguintes documentos de referência na pasta docs/portal/:
- ROADMAP_PORTAL.md (seção Fase 1A)
- AUDITORIA_PORTAL_CLIENTE.md (gaps identificados)
- RELATORIO_PORTAL_CLIENTE_V2.md (seções 7, 8, 9 — problemas, segurança, login unificado)

## Contexto do Estado Atual

O portal tem 8 actions implementadas de 28 declaradas em routes.php. As seguintes funcionalidades estão quebradas ou ausentes:

1. O form de login (app/views/portal/auth/login.php, linha 85) faz POST para `?page=portal&action=requestMagicLink` mas essa action NÃO EXISTE no PortalController.php — o botão "Receber link de acesso" está quebrado.
2. As rotas `forgotPassword` e `resetPassword` estão declaradas no routes.php mas NÃO possuem método no controller — não há recuperação de senha.
3. O `PortalAuthMiddleware::checkInactivity()` existe (linha 125 do middleware) mas NUNCA é chamado no index.php — sessão do portal dura indefinidamente (falha de segurança).
4. O `updateProfile()` do PortalController permite alterar a senha SEM exigir a senha atual (falha de segurança).
5. A config `magic_link_expiry_hours` (valor 24 no banco) é ignorada — o model usa o valor hardcoded como default do parâmetro.
6. A config `require_password` (valor 0 no banco) nunca é lida — não influencia o login.
7. O login do admin (UserController::login()) e do portal (PortalController::login()) são completamente separados — o cliente precisa saber acessar ?page=portal.

## O que implementar (em ordem)

### 1. Migration SQL
Criar `sql/update_YYYYMMDDHHMM_portal_fase1a.sql` com:
- ALTER TABLE customer_portal_access ADD COLUMN reset_token VARCHAR(128) DEFAULT NULL;
- ALTER TABLE customer_portal_access ADD COLUMN reset_token_expires_at DATETIME DEFAULT NULL;
(Usar procedimento condicional como na migration existente update_202603241000_portal_cliente.sql)

### 2. PortalAccess.php — Novos métodos
- `generateResetToken(int $accessId, int $expiryHours = 1): string` — gera token hex 128 chars, salva no BD
- `validateResetToken(string $token): ?array` — valida token + expiração + is_active
- `invalidateResetToken(int $accessId): void` — limpa token após uso
- `resetPassword(int $accessId, string $newPassword): bool` — atualiza password_hash
- Modificar `generateMagicToken()` para ler `getConfig('magic_link_expiry_hours')` ao invés de usar 24 hardcoded

### 3. PortalController.php — Novos métodos
- `requestMagicLink(): void` — POST, valida email, chama generateMagicToken(), prepara link (não envia e-mail real por enquanto, apenas exibe mensagem de sucesso genérica), dispara evento `portal.magic_link.requested`
- `forgotPassword(): void` — GET: renderiza form / POST: valida email, chama generateResetToken(), prepara link, mensagem genérica de sucesso (não vazar se email existe)
- `resetPassword(): void` — GET: valida token GET, se inválido redireciona / se válido renderiza form de nova senha / POST: valida token + senha, chama PortalAccess::resetPassword() + invalidateResetToken()
- Modificar `login()` para ler config `require_password`:
  - Se require_password=0 e conta sem password_hash: exibir opção de magic link ao invés de erro
- Modificar `updateProfile()`: 
  - Ler `current_password` do POST
  - Se `new_password` preenchido, validar que `current_password` confere com PortalAccess::verifyPassword() antes de alterar
  - Validação de força: min 8 chars, pelo menos 1 letra e 1 número
  - Se senha atual incorreta, retornar erro

### 4. Novas Views
- `app/views/portal/auth/forgot.php` — form com campo email, botão "Enviar link de recuperação"
- `app/views/portal/auth/reset.php` — form com campos nova_senha + confirma_senha, campo hidden token

### 5. Modificar Views existentes
- `app/views/portal/profile/index.php` — adicionar campo "Senha atual" antes de "Nova senha" no card de alterar senha
- `app/views/portal/auth/login.php` — adicionar link "Esqueci minha senha" apontando para `?page=portal&action=forgotPassword`

### 6. Login Unificado no UserController
Editar `app/controllers/UserController.php`, método `login()`:
- Após o bloco de falha do login admin (quando email+senha não batem na tabela users), ANTES de definir a mensagem de erro final:
  - Instanciar PortalAccess, buscar findByEmail()
  - Se encontrou, is_active, não locked, tem password_hash, e password confere:
    - Fazer login via PortalAuthMiddleware::login()
    - Redirecionar para ?page=portal&action=dashboard
  - Se encontrou mas senha errada: registrar failed attempt no portal
  - Mensagem de erro sempre genérica: "E-mail ou senha inválidos."
- Adicionar `use Akti\Models\PortalAccess;` e `use Akti\Middleware\PortalAuthMiddleware;` no topo

### 7. Timeout de sessão do portal
Editar `index.php`, após o bloco de checkInactivity do admin (linha ~60-67):
- Adicionar: se isset($_SESSION['portal_customer_id']), chamar PortalAuthMiddleware::checkInactivity()

### 8. routes.php
Adicionar `'requestMagicLink' => 'requestMagicLink'` na seção de Auth do portal (se não existir)

### 9. portal.php (tradução)
Adicionar chaves:
- forgot_title, forgot_subtitle, forgot_email, forgot_btn, forgot_success, forgot_back
- reset_title, reset_password, reset_password_confirm, reset_btn, reset_success, reset_invalid_token
- profile_password_current, profile_password_current_required, profile_password_weak
- login_forgot (link "Esqueci minha senha")
- magic_link_requested (mensagem de sucesso ao solicitar magic link)

### Regras importantes:
- Seguir padrão MVC: queries no Model, lógica no Controller, HTML na View
- Namespace obrigatório: Akti\Controllers, Akti\Models
- Usar Input::post() e Input::get() para capturar dados
- Usar e() e eAttr() para escape nas views
- Usar csrf_field() em todos os forms POST
- Usar __p() para todas as strings visíveis
- Mensagens de erro devem ser GENÉRICAS (não vazar se email existe)
- session_regenerate_id(true) após login bem-sucedido
- Disparar eventos via EventDispatcher onde relevante
```

---

## Fase 1B — Visual & Navegação

### Prompt

```
Implemente a Fase 1B do Portal do Cliente (Akti) — Visual & Navegação.

Consulte os seguintes documentos de referência na pasta docs/portal/:
- ROADMAP_PORTAL.md (seção Fase 1B)
- RELATORIO_PORTAL_CLIENTE_V2.md (seções 10, 11 — visual minimalista e navegação desktop)

## Contexto do Estado Atual

O portal usa paleta roxa (#667eea) desalinhada com o Akti admin que usa azul (#3b82f6). Os botões têm gradients agressivos e sombras 3D. Em desktop (≥768px), a bottom nav desaparece via CSS mas NENHUM menu substituto é mostrado — o usuário fica sem navegação. O manifest.json é compartilhado com o admin. O Service Worker intercepta todas as requisições do domínio sem scope.

## O que implementar

### 1. portal.css — Trocar paleta de cores
Substituir TODAS as ocorrências de:
- `#667eea` → `#3b82f6`
- `#764ba2` → `#1d4ed8`
- `#5a6fd6` → `#2563eb`

Atualizar variáveis CSS (:root):
```css
--portal-primary: #3b82f6;
--portal-primary-dark: #2563eb;
--portal-primary-light: #60a5fa;
--portal-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
--portal-gradient-soft: linear-gradient(135deg, rgba(59,130,246,0.08) 0%, rgba(29,78,216,0.08) 100%);
--portal-surface: #f8fafc;
--portal-border: #e2e8f0;
--portal-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
--portal-shadow-lg: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
--portal-radius: 12px;
--portal-radius-md: 8px;
--portal-radius-sm: 6px;
```

### 2. portal.css — Botões: remover gradients
- `.portal-btn-primary`: background sólido `var(--portal-primary)`, sem gradient, border-radius 8px, sombra sutil
- `.portal-btn-outline`: border `var(--portal-primary)`, sem gradient hover

### 3. portal.css — Auth body: remover gradient agressivo
- `.portal-auth-body`: background `var(--portal-surface)` ao invés de gradient roxo
- `.portal-auth-card`: sombra sutil ao invés de forte

### 4. portal.css — Sombras sutis
- Substituir todas as box-shadow fortes (rgba com alpha > 0.15) por `var(--portal-shadow)` ou `var(--portal-shadow-lg)`
- Especialmente no botão central da bottom nav

### 5. portal.css — Navegação desktop
Adicionar classes:
```css
.portal-desktop-nav { display: none; } /* hidden por padrão (mobile) */

@media (min-width: 768px) {
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
    .portal-content {
        max-width: 960px; /* era 600px */
    }
    .portal-stats-grid {
        grid-template-columns: repeat(4, 1fr); /* 4 colunas em desktop */
    }
}
```

### 6. portal.css — Remover dark mode automático
Remover completamente o bloco `@media (prefers-color-scheme: dark) { ... }` (pode ser reimplementado como toggle manual no futuro)

### 7. header.php — Navegação desktop + theme-color
- Trocar `<meta name="theme-color" content="#667eea">` → `content="#3b82f6"`
- Adicionar `<nav class="portal-desktop-nav">` com 5 links (Home, Pedidos, Novo, Financeiro, Perfil) entre portal-topbar-left e portal-topbar-right
- Cada link com classe `portal-desktop-link` e `.active` condicional baseado em `$currentAction`
- Ícones `far` (outline) nos links desktop

### 8. header_auth.php — theme-color
- Trocar `<meta name="theme-color" content="#667eea">` → `content="#3b82f6"`

### 9. footer.php — Ícones outline
- Trocar ícones `fas fa-home`, `fas fa-box`, `fas fa-wallet`, `fas fa-user` por versões `far` (outline) na bottom nav
- O ícone `fas fa-plus` do centro pode permanecer solid

### 10. portal-manifest.json — Novo arquivo
Criar `portal-manifest.json` na raiz:
```json
{
    "name": "Portal do Cliente — Akti",
    "short_name": "Portal",
    "description": "Portal do Cliente — Akti Gestão em Produção",
    "start_url": "?page=portal",
    "display": "standalone",
    "background_color": "#f8fafc",
    "theme_color": "#3b82f6",
    "orientation": "any",
    "icons": [
        {
            "src": "assets/logos/akti-icon-dark.svg",
            "sizes": "any",
            "type": "image/svg+xml",
            "purpose": "any maskable"
        }
    ],
    "categories": ["business"],
    "lang": "pt-BR"
}
```
Atualizar `<link rel="manifest">` em header.php e header_auth.php para apontar para `portal-manifest.json`

### 11. portal-sw.js — Scope
Registrar o SW com scope limitado. No footer.php, alterar o registro:
```javascript
navigator.serviceWorker.register('portal-sw.js', { scope: './' })
```
E adicionar no portal-sw.js, no evento fetch, um filtro para só interceptar requests que contenham `page=portal` ou sejam assets estáticos do portal.

### 12. portal.js — Toast color
Atualizar a cor `info` no objeto `colors` do método `toast` de `#667eea` para `#3b82f6`

### Regras importantes:
- NÃO alterar nenhum arquivo do admin (theme.css, style.css, script.js)
- Manter 100% de compatibilidade mobile (bottom nav continua funcionando em < 768px)
- Manter a fonte Inter
- Usar variáveis CSS sempre que possível (não hardcodar cores)
```

---

## Fase 2 — Meus Pedidos

### Prompt

```
Implemente a Fase 2 do Portal do Cliente (Akti) — Meus Pedidos.

Consulte os seguintes documentos de referência na pasta docs/portal/:
- ROADMAP_PORTAL.md (seção Fase 2)
- AUDITORIA_PORTAL_CLIENTE.md (seções 4 e 5 — SQL e gaps)
- RELATORIO_PORTAL_CLIENTE_V2.md (seções 2.2, 6, 13 — BD orders, actions, roadmap)

## Contexto do Estado Atual

- O dashboard já mostra `recentOrders` e links para `?page=portal&action=orderDetail&id=X` — mas essa action não existe.
- A bottom nav já linka para `?page=portal&action=orders` — mas essa action não existe.
- As rotas `orders`, `orderDetail`, `approveOrder`, `rejectOrder` estão declaradas em routes.php.
- A tabela `orders` já tem as colunas `customer_approval_status`, `customer_approval_at`, `customer_approval_ip`, `customer_approval_notes` (criadas pela migration da Fase 1).
- O PortalAccess.php já tem o método `hasApprovalColumn()` que faz check dinâmico.
- A config `allow_order_approval` existe no BD com valor '1' mas nunca é lida pelo código.
- As chaves de tradução para pedidos e aprovação já existem em portal.php (orders_*, order_detail_*, approval_*).
- Os helpers `portal_money()`, `portal_date()`, `portal_stage_class()`, `portal_stage_icon()` já existem.

## O que implementar

### 1. PortalController.php — 4 novos métodos

#### orders()
- GET, auth check via PortalAuthMiddleware::check()
- Capturar filtro: Input::get('filter') — valores: 'all' (default), 'open', 'approval', 'done'
- Capturar paginação: Input::get('p') — page number
- Buscar pedidos do cliente via Model com filtro e paginação (limit 10, offset)
- Contar total para paginação
- Renderizar view: header.php + orders/index.php + footer.php

#### orderDetail()
- GET &id=X, auth check
- Capturar Input::get('id'), validar inteiro
- Buscar pedido completo: dados do pedido + itens (JOIN order_items + products) + parcelas (JOIN order_installments se existir)
- Validar que pedido pertence ao customer_id da sessão (segurança!)
- Montar array de timeline baseado em pipeline_stage (quais etapas já passou)
- Renderizar view: header.php + orders/detail.php + footer.php

#### approveOrder()
- POST, auth check, validar CSRF
- Capturar: id (POST), notes (POST)
- Buscar pedido, validar pertence ao cliente, validar customer_approval_status = 'pendente'
- Ler config allow_order_approval — se '0', retornar erro
- UPDATE orders SET customer_approval_status='aprovado', customer_approval_at=NOW(), customer_approval_ip=IP, customer_approval_notes=notas
- Disparar evento portal.order.approved
- Redirect para orderDetail

#### rejectOrder()
- POST, auth check, validar CSRF
- Mesma lógica mas com status='recusado'
- Disparar evento portal.order.rejected
- Redirect para orderDetail

### 2. Model — Métodos de consulta (adicionar ao PortalAccess.php ou criar PortalOrder.php separado)

- `getOrdersByCustomer(int $customerId, string $filter = 'all', int $limit = 10, int $offset = 0): array`
  - Filtros: all = todos; open = status NOT IN ('concluido','cancelado'); approval = customer_approval_status='pendente'; done = status IN ('concluido')
  - Retornar: id, status, pipeline_stage, total_amount, created_at, customer_approval_status, items_count (subquery COUNT)
  
- `countOrdersByCustomer(int $customerId, string $filter = 'all'): int`

- `getOrderDetail(int $orderId, int $customerId): ?array`
  - Retornar: dados completos do pedido

- `getOrderItems(int $orderId): array`
  - JOIN com products para nome, preço unitário, quantidade, subtotal

- `getOrderInstallments(int $orderId): array`
  - Se tabela existir (try/catch), retornar parcelas

- `updateApprovalStatus(int $orderId, int $customerId, string $status, string $ip, ?string $notes): bool`
  - UPDATE com WHERE customer_id = $customerId (segurança)

### 3. Views

#### app/views/portal/orders/index.php
- Tabs de filtro: Todos, Abertos, Aprovação, Concluídos (links com ?page=portal&action=orders&filter=X)
- Lista de cards de pedido (reutilizar estilo .portal-order-card do dashboard)
- Cada card: #id, badge status pipeline, data, total, badge aprovação se pendente
- Link para detalhe: ?page=portal&action=orderDetail&id=X
- Empty state quando sem pedidos
- Paginação simples (anterior/próxima)

#### app/views/portal/orders/detail.php
- Cabeçalho: "Pedido #X" com badge de status
- Timeline visual do pipeline (etapas: contato → orçamento → venda → produção → preparação → envio → financeiro → concluído)
- Etapas completadas com check, etapa atual com destaque, futuras com cinza
- Tabela/lista de itens: produto, qtd, preço unitário, subtotal
- Totais: subtotal, desconto, total
- Se tem parcelas: lista resumida de parcelas com status
- Se customer_approval_status = 'pendente' E allow_order_approval = '1':
  - Card de aprovação com textarea para notas, botões Aprovar (verde) e Recusar (vermelho)
  - Forms POST com CSRF para approveOrder e rejectOrder
- Se já aprovado/recusado: badge com status e data

### 4. portal.css — Novos componentes
- `.portal-tab-filter` — tabs horizontais scrolláveis para filtros
- `.portal-timeline` — timeline vertical com etapas do pipeline
- `.portal-timeline-step`, `.portal-timeline-step.completed`, `.portal-timeline-step.current`
- `.portal-items-table` — tabela responsiva de itens
- `.portal-approval-card` — card de aprovação com destaque
- `.portal-pagination` — botões de paginação simples

### 5. portal.php — Novas chaves de tradução (se alguma faltar)
Verificar se todas as chaves orders_* e approval_* já existem. Adicionar apenas as faltantes.

### Regras importantes:
- SEMPRE validar que o pedido pertence ao customer_id da sessão (WHERE customer_id = :cid)
- Usar prepared statements para todas as queries
- Disparar eventos para ações de aprovação/recusa
- Respeitar padrão MVC: queries no Model, lógica no Controller, HTML na View
- Usar __p() para todas as strings visíveis
- Usar e(), eAttr(), csrf_field() nas views
- Se a tabela order_installments não existir, usar try/catch (resiliência)
```

---

## Fase 3 — Financeiro & Rastreamento

### Prompt

```
Implemente a Fase 3 do Portal do Cliente (Akti) — Financeiro & Rastreamento.

Consulte os seguintes documentos de referência na pasta docs/portal/:
- ROADMAP_PORTAL.md (seção Fase 3)
- RELATORIO_PORTAL_CLIENTE_V2.md (seções 2, 13)

## Contexto do Estado Atual

- A bottom nav já linka para `?page=portal&action=installments` — action não existe.
- As rotas `installments`, `installmentDetail`, `tracking` estão declaradas em routes.php.
- O dashboard já mostra stats de `open_installments` e `total_open_amount` (via PortalAccess::getDashboardStats).
- Notificações de parcelas próximas do vencimento já são geradas no dashboard (PortalAccess::getRecentNotifications).
- As chaves de tradução financial_* e tracking_* já existem em portal.php.
- A tabela `order_installments` pode não existir em todos os tenants — usar try/catch.
- A Fase 2 (Pedidos) já deve estar implementada, incluindo getOrderDetail().

## O que implementar

### 1. PortalController.php — 3 novos métodos

#### installments()
- GET, auth check
- Filtro: Input::get('tab') — valores: 'open' (default), 'paid', 'all'
- Buscar parcelas do cliente (JOIN orders WHERE orders.customer_id = :cid)
- Agrupar por status: pendente/atrasada = open; paga = paid
- Renderizar: header + financial/index.php + footer

#### installmentDetail()
- GET &id=X, auth check
- Buscar parcela com JOIN order + validar pertence ao cliente
- Verificar se existe gateway de pagamento ativo (PaymentGateway model se disponível)
- Renderizar: header + financial/detail.php + footer

#### tracking()
- GET &id=X (order_id), auth check
- Buscar pedido + validar pertence ao cliente
- Extrair tracking_code, shipping_carrier, e dados de envio
- Renderizar: header + tracking/index.php + footer

### 2. Model — Métodos

- `getInstallmentsByCustomer(int $customerId, string $filter, int $limit, int $offset): array`
- `countInstallmentsByCustomer(int $customerId, string $filter): int`
- `getInstallmentDetail(int $installmentId, int $customerId): ?array`
- `getTrackingInfo(int $orderId, int $customerId): ?array`

### 3. Views

#### app/views/portal/financial/index.php
- Resumo no topo: total em aberto, total pago (cards resumo)
- Tabs: Em Aberto, Pagas, Todas
- Lista de cards de parcela: pedido #X, parcela N/total, valor, vencimento, badge status
- Badge colorido: verde=pago, amarelo=pendente, vermelho=atrasada
- Link para detalhe: ?page=portal&action=installmentDetail&id=X
- Empty state

#### app/views/portal/financial/detail.php
- Dados da parcela: número, valor, vencimento, status, método de pagamento
- Dados do pedido vinculado: #id, total, link para detalhe do pedido
- Se status pendente/atrasada E gateway ativo: botão "Pagar Online" (link externo ou modal)
- Se pago: data de pagamento, comprovante (se disponível)

#### app/views/portal/tracking/index.php
- Dados do pedido: #id, status, etapa do pipeline
- Se tracking_code existe: exibir código com botão copiar, transportadora, link de rastreio externo
- Timeline visual simples de envio (preparação → enviado → em trânsito → entregue)
- Se tracking_code não existe: mensagem "Código de rastreio ainda não disponível"

### 4. portal.css
- `.portal-financial-summary` — cards resumo (total aberto / total pago)
- `.portal-installment-card` — card de parcela
- `.portal-installment-badge` — badges de status financeiro
- `.portal-tracking-code` — exibição do código com botão copiar
- `.portal-tracking-timeline` — timeline horizontal de envio
- `.portal-copy-btn` — botão de copiar para clipboard

### 5. portal.js
- Função `copyToClipboard(text)` para copiar código de rastreio

### Regras importantes:
- Todas as queries DEVEM filtrar por customer_id da sessão (segurança)
- Tabela order_installments pode não existir — usar try/catch
- Se gateway de pagamento não estiver configurado, ocultar botão "Pagar Online"
- Usar __p() para todas as strings, e() e eAttr() para escape
```

---

## Fase 4 — Novo Pedido / Catálogo

### Prompt

```
Implemente a Fase 4 do Portal do Cliente (Akti) — Novo Pedido / Catálogo.

Consulte os seguintes documentos de referência na pasta docs/portal/:
- ROADMAP_PORTAL.md (seção Fase 4)
- RELATORIO_PORTAL_CLIENTE_V2.md (seção 13 — Fase 3 do roadmap original)

## Contexto do Estado Atual

- A bottom nav tem o botão central "+" linkando para `?page=portal&action=newOrder` — action não existe.
- As rotas newOrder, getProducts, addToCart, removeFromCart, updateCartItem, getCart, submitOrder estão declaradas.
- A config `allow_new_orders` existe no BD com valor '1' mas nunca é lida.
- A config `show_prices_in_catalog` existe no BD com valor '1' mas nunca é lida.
- A coluna `portal_origin` existe em orders (TINYINT DEFAULT 0) mas nunca é escrita.
- A sessão já prevê `$_SESSION['portal_cart']` (listada no logout do middleware).
- O model Product já existe em app/models/Product.php.
- O model Order já existe em app/models/Order.php.
- O model Category já existe em app/models/Category.php.

## O que implementar

### 1. PortalController.php — 7 novos métodos

#### newOrder()
- GET, auth check
- Ler config allow_new_orders — se '0', renderizar mensagem de indisponível
- Carregar categorias via Category model
- Renderizar: header + orders/new.php + footer

#### getProducts()
- GET AJAX, auth check
- Parâmetros: Input::get('category_id'), Input::get('search'), Input::get('page')
- Buscar produtos ativos, filtrados por categoria e/ou busca textual
- Se show_prices_in_catalog = '0', não incluir preço no retorno
- Retornar JSON: { products: [...], total: N, page: X }

#### addToCart()
- POST AJAX, auth check, CSRF
- Input::post('product_id'), Input::post('quantity', 'int'), Input::post('notes')
- Adicionar/incrementar em $_SESSION['portal_cart'] (array de items)
- Cada item: { product_id, name, price, quantity, notes, subtotal }
- Retornar JSON: { success: true, cart_count: N, cart_total: X }

#### removeFromCart()
- POST AJAX, auth check, CSRF
- Input::post('index') — índice do item no array do carrinho
- Remover do $_SESSION['portal_cart']
- Retornar JSON atualizado

#### updateCartItem()
- POST AJAX, auth check, CSRF
- Input::post('index'), Input::post('quantity')
- Atualizar quantidade, recalcular subtotal
- Se quantity = 0, remover item
- Retornar JSON atualizado

#### getCart()
- GET AJAX, auth check
- Retornar JSON de $_SESSION['portal_cart'] com totais

#### submitOrder()
- POST, auth check, CSRF
- Ler carrinho da sessão, validar que não está vazio
- Input::post('notes') — observações do cliente
- Criar pedido via Order model:
  - customer_id da sessão
  - status = 'orcamento' (ou equivalente no sistema)
  - pipeline_stage = 'orcamento'
  - portal_origin = 1
  - total_amount = soma dos itens
  - Criar order_items para cada item do carrinho
- Limpar $_SESSION['portal_cart']
- Disparar evento portal.order.submitted
- Redirect para orderDetail do novo pedido

### 2. Views

#### app/views/portal/orders/new.php
- Barra de busca no topo
- Filtro por categoria (select ou tabs)
- Grid de cards de produtos (imagem, nome, preço se permitido, botão "Adicionar")
- Mini-indicador do carrinho (ícone + contador)
- Botão fixo "Ver Carrinho (N itens)"
- AJAX: ao clicar "Adicionar", chamar Portal.post('addToCart', ...) e atualizar contador

#### app/views/portal/orders/cart.php
- Lista de itens no carrinho com: nome, quantidade editável (input number), preço, subtotal, botão remover
- Total geral
- Campo de observações (textarea)
- Botão "Enviar Pedido" (POST para submitOrder)
- Botão "Continuar Comprando" (link para newOrder)
- Empty state se carrinho vazio

### 3. portal.css
- `.portal-product-grid` — grid de produtos (2 colunas mobile, 3-4 desktop)
- `.portal-product-card` — card de produto com imagem, nome, preço, botão
- `.portal-cart-item` — item do carrinho
- `.portal-cart-summary` — resumo de totais
- `.portal-cart-fab` — botão flutuante "Ver Carrinho"
- `.portal-search-bar` — barra de busca estilizada

### 4. portal.js — Funções de carrinho
- Funções AJAX usando Portal.post/get para manipular carrinho
- Atualização dinâmica do contador e total
- Feedback visual ao adicionar item (toast ou animação)

### 5. portal.php — Novas chaves
- cart_title, cart_empty, cart_remove, cart_quantity, cart_subtotal, cart_total, cart_notes, cart_submit, cart_continue
- catalog_title, catalog_search, catalog_no_results, catalog_add, catalog_added
- new_order_disabled (mensagem quando allow_new_orders='0')

### Regras importantes:
- O carrinho é gerenciado via $_SESSION['portal_cart'] (não persiste em BD)
- Sempre validar que product_id existe e está ativo antes de adicionar ao carrinho
- portal_origin=1 no pedido criado (diferenciá-lo de pedidos criados pelo admin)
- Respeitar show_prices_in_catalog (ocultar preços se '0')
- Respeitar allow_new_orders (bloquear acesso se '0')
```

---

## Fase 5 — Mensagens & Documentos

### Prompt

```
Implemente a Fase 5 do Portal do Cliente (Akti) — Mensagens & Documentos.

Consulte os seguintes documentos de referência na pasta docs/portal/:
- ROADMAP_PORTAL.md (seção Fase 5)
- AUDITORIA_PORTAL_CLIENTE.md (seção 1.2 — PortalMessage.php)
- RELATORIO_PORTAL_CLIENTE_V2.md (seções 3.3, 7)

## Contexto do Estado Atual

- O model `PortalMessage.php` já está COMPLETO (162 linhas) com: create(), getByCustomer(), countUnread(), markAsRead(), findById(), countUnreadFromCustomers()
- O dashboard já chama countUnread() e mostra o número de mensagens não lidas
- A tabela `customer_portal_messages` já existe com: id, customer_id, order_id, sender_type, sender_id, message, is_read, read_at, attachment_path, created_at
- As rotas `messages` e `sendMessage` estão declaradas em routes.php
- A config `allow_messages` existe no BD com valor '1' mas nunca é lida
- As chaves de tradução messages_* já existem em portal.php
- O evento `portal.message.sent` já é disparado no model

## O que implementar

### 1. PortalController.php — 4 novos métodos

#### messages()
- GET, auth check
- Ler config allow_messages — se '0', renderizar indisponível
- Se &order_id=X: mostrar mensagens daquele pedido (validar que pedido pertence ao cliente)
- Se sem order_id: listar últimas mensagens agrupadas por pedido (ou lista geral)
- Chamar markAsRead() para mensagens de admin visíveis
- Renderizar: header + messages/index.php + footer

#### sendMessage()
- POST, auth check, CSRF
- Input::post('order_id'), Input::post('message')
- Validar: message não vazia, order_id pertence ao cliente (se informado)
- Chamar PortalMessage::create() com sender_type='customer'
- Se AJAX: retornar JSON { success, message_data }
- Se não AJAX: redirect para messages com &order_id

#### documents()
- GET, auth check
- Listar documentos vinculados aos pedidos do cliente
- Buscar em NF-e (nfe_documents se existir), comprovantes de pagamento, etc.
- Resiliência: se tabelas não existem, mostrar lista vazia
- Renderizar: header + documents/index.php + footer

#### downloadDocument()
- GET &id=X, auth check
- Validar que documento pertence ao cliente (JOIN com pedido do cliente)
- Servir arquivo com headers corretos (Content-Type, Content-Disposition)
- Se arquivo não existe, retornar 404

### 2. Views

#### app/views/portal/messages/index.php
- Se order_id fornecido: chat view — lista de mensagens (bolhas) + input de texto + botão enviar
  - Bolhas à direita = mensagens do cliente (azul)
  - Bolhas à esquerda = mensagens do admin (cinza), com nome do admin
  - Ordenadas por created_at ASC
  - Input fixo no bottom com textarea + botão enviar
- Se sem order_id: lista de conversas (pedidos com mensagens), com contador de não lidas
- AJAX para enviar mensagem sem recarregar (usar Portal.post)

#### app/views/portal/documents/index.php
- Lista de documentos agrupados por pedido
- Cada documento: tipo (NF-e, Boleto, Comprovante), ícone, data, botão download
- Se sem documentos: empty state

### 3. portal.css
- `.portal-chat` — container do chat
- `.portal-chat-messages` — área scrollável de mensagens
- `.portal-chat-bubble` — bolha de mensagem
- `.portal-chat-bubble.sent` — bolha do cliente (alinhada à direita, fundo azul)
- `.portal-chat-bubble.received` — bolha do admin (alinhada à esquerda, fundo cinza)
- `.portal-chat-input` — input fixo no bottom (textarea + botão)
- `.portal-chat-time` — timestamp da mensagem
- `.portal-document-card` — card de documento com ícone por tipo
- `.portal-conversation-item` — item de lista de conversas

### 4. portal.js
- Função de enviar mensagem via AJAX (Portal.post)
- Append da nova mensagem ao chat sem reload
- Auto-scroll para a última mensagem

### Regras importantes:
- PortalMessage model já está pronto — usar seus métodos, não reescrever
- markAsRead() deve ser chamado ao abrir conversa (marcar mensagens do admin como lidas)
- countUnread() no dashboard já funciona e continuará funcionando
- Validar SEMPRE que order_id pertence ao customer_id da sessão
- attachment_path existe no model mas upload NÃO será implementado nesta fase (campo fica null)
- documents(): ser resiliente a tabelas inexistentes (NF-e pode não estar configurada)
```

---

## Fase 6 — Admin do Portal

### Prompt

```
Implemente a Fase 6 do Portal do Cliente (Akti) — Admin do Portal.

Consulte os seguintes documentos de referência na pasta docs/portal/:
- ROADMAP_PORTAL.md (seção Fase 6)
- RELATORIO_PORTAL_CLIENTE_V2.md (seção 13 — Fase 6)
- AUDITORIA_PORTAL_CLIENTE.md (seção 4 — configs ociosas)

## Contexto do Estado Atual

- O PortalAccess model já tem: readAll() (lista com JOIN customers), setConfig(), getAllConfig()
- A tabela customer_portal_config já tem 8 registros (6 nunca lidos pelo código — serão lidos pelas fases anteriores)
- A tabela customer_portal_sessions está ociosa (criada mas não usada)
- PortalMessage::countUnreadFromCustomers() existe para o painel admin
- O sistema admin tem: menu.php para configuração de menu, groups.php para permissões, header.php para layout

## O que implementar — TUDO no painel admin (NÃO no portal do cliente)

### 1. Novo Controller ou adicionar ao SettingsController
Criar `PortalAdminController.php` ou adicionar métodos ao SettingsController existente:

#### portalAccess() — Listagem de acessos
- GET, admin auth, verificar permissão
- Listar todos os acessos via PortalAccess::readAll()
- Renderizar: admin header + portal-admin/access.php + admin footer

#### portalAccessToggle() — Ativar/desativar acesso
- POST, admin auth, CSRF
- PortalAccess::update($id, ['is_active' => toggle])

#### portalAccessResetPassword() — Resetar senha
- POST, admin auth, CSRF
- Gerar nova senha temporária ou enviar link de reset

#### portalConfig() — Configurações do portal
- GET: exibir formulário com todas as configs
- POST: salvar via PortalAccess::setConfig()

#### portalDashboard() — Métricas
- Totais: acessos ativos, logins últimos 7 dias, mensagens não lidas de clientes
- Últimos logins

### 2. Views admin
- `app/views/portal-admin/access.php` — tabela de acessos (cliente, email, último login, status, ações)
- `app/views/portal-admin/config.php` — formulário de configurações (checkboxes, inputs)
- `app/views/portal-admin/dashboard.php` — cards de métricas

### 3. Rotas
- Adicionar rotas em routes.php: portal_admin com actions access, config, dashboard, etc.

### 4. Menu e Permissões
- Adicionar entrada no menu.php para "Portal do Cliente" (ícone portal)
- Adicionar permissão 'portal_admin' no sistema de grupos

### 5. Ativar customer_portal_sessions (opcional)
- PortalAuthMiddleware::login() — gravar sessão na tabela
- PortalAuthMiddleware::logout() — remover sessão da tabela
- PortalAuthMiddleware::check() — validar também na tabela
- Admin: tela de sessões ativas com opção de forçar logout

### Regras importantes:
- Este é código do ADMIN, usa layout admin (header.php/footer.php do admin)
- Verificar permissões de grupo (checkAdmin ou similar)
- Seguir padrões do sistema admin existente para tabelas, formulários e layout
```

---

## Fase 7 — Polimento & PWA Avançado

### Prompt

```
Implemente a Fase 7 do Portal do Cliente (Akti) — Polimento & PWA Avançado.

Consulte os seguintes documentos de referência na pasta docs/portal/:
- ROADMAP_PORTAL.md (seção Fase 7)
- RELATORIO_PORTAL_CLIENTE_V2.md (seções 12.2, 12.3)

## Contexto
Todas as fases anteriores (1A, 1B, 2, 3, 4, 5, 6) devem estar implementadas.

## O que implementar (selecionar conforme prioridade)

### Segurança
1. **2FA opcional via e-mail** — ao fazer login, enviar código de 6 dígitos por e-mail, tela de verificação
   - Nova tabela ou colunas: portal_2fa_code, portal_2fa_expires_at
   - Tela intermediária de verificação entre login e dashboard
   - Config: portal_2fa_enabled (adicionar à customer_portal_config)

2. **Rate limiting por IP global** — usar tabela ou cache para bloquear IPs com muitas tentativas
   - Similar ao LoginAttempt do admin
   - Bloquear IP após X tentativas falhas independente da conta

3. **Log de ações sensíveis** — via Logger model existente
   - Login, logout, alteração de senha, aprovação/recusa de pedido, envio de mensagem

### UX
4. **Skeleton loading** — placeholders animados enquanto dados carregam
   - CSS: `.portal-skeleton` com animação shimmer
   - Aplicar nos stat cards do dashboard e na listagem de pedidos

5. **Pull-to-refresh** no dashboard (mobile)
   - JS: detectar overscroll touch, recarregar dados via AJAX

6. **Máscara de telefone e CPF/CNPJ** — nos formulários de registro e perfil
   - JS com formatação automática: (XX) XXXXX-XXXX e XXX.XXX.XXX-XX / XX.XXX.XXX/XXXX-XX

### PWA
7. **Página offline customizada** — quando sem internet
   - portal-sw.js: retornar página offline ao invés de erro
   - Criar app/views/portal/offline.php

8. **Indicador de status offline** — banner no topo quando sem conexão
   - JS: window.addEventListener('offline'/'online')

### i18n
9. **Idiomas adicionais** — English (en), Español (es)
   - Criar app/lang/en/portal.php e app/lang/es/portal.php
   - Traduzir as ~204 chaves
   - Atualizar PortalLang::getAvailableLanguages() para incluir novos idiomas

### Migration SQL (se necessário)
- sql/update_YYYYMMDDHHMM_portal_fase7.sql para colunas de 2FA

### Regras importantes:
- Cada item pode ser implementado de forma independente
- Priorizar: masks → skeleton → rate limiting → log → 2FA → PWA → i18n
- Não quebrar nenhuma funcionalidade existente
```

---

## 📋 Notas Gerais para Todos os Prompts

1. **Sempre gerar migration SQL** em `/sql/update_YYYYMMDDHHMM_descricao.sql` quando houver alteração no banco
2. **Seguir padrão MVC** rigoroso: Model (queries), Controller (lógica), View (HTML)
3. **Namespaces obrigatórios**: `Akti\Controllers`, `Akti\Models`, `Akti\Middleware`, `Akti\Services`
4. **Sem require_once** — o autoloader PSR-4 carrega automaticamente
5. **Segurança em todas as queries**: prepared statements, filtro por customer_id, CSRF em POST
6. **Tradução**: usar `__p()` para toda string visível ao usuário
7. **Escape**: usar `e()` para texto, `eAttr()` para atributos HTML
8. **Eventos**: disparar via `EventDispatcher::dispatch()` para ações relevantes
9. **Resiliência**: usar try/catch para tabelas que podem não existir (order_installments, nfe_documents)
10. **Responsividade**: CSS mobile-first, funcionar em mobile e desktop

---

*Prompts gerados com base na auditoria real e no roadmap do Portal do Cliente Akti.*
