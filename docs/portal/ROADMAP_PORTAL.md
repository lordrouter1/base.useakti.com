# 🗺️ ROADMAP — Portal do Cliente (Akti)

> **Data:** 24/03/2026  
> **Base:** Auditoria (`AUDITORIA_PORTAL_CLIENTE.md`) + Relatório Técnico (`RELATORIO_PORTAL_CLIENTE_V2.md`)  
> **Estado atual:** Fase 1 parcial — 8 de 28 actions implementadas (28.5%)  
> **Objetivo:** Levar o Portal do Cliente a 100% de cobertura funcional em 7 fases incrementais

---

## 📊 Visão Geral das Fases

| Fase | Nome | Escopo | Actions Novas | Complexidade | Pré-Requisito |
|------|------|--------|--------------|-------------|---------------|
| **1A** | Correções Críticas & Segurança | Bugs que quebram UX + falhas de segurança | 3 | 🟡 Média | — |
| **1B** | Visual & Navegação | Paleta de cores, navegação desktop, PWA | 0 | 🟡 Média | — |
| **2** | Meus Pedidos | Listagem + detalhe + aprovação/recusa | 4 | 🟡 Média | Fase 1A |
| **3** | Financeiro & Rastreamento | Parcelas + pagamentos + tracking | 3 | 🟡 Média | Fase 2 |
| **4** | Novo Pedido / Catálogo | Catálogo + carrinho + submit | 7 | 🔴 Alta | Fase 2 |
| **5** | Mensagens & Documentos | Chat por pedido + download NF-e/boletos | 4 | 🟡 Média | Fase 2 |
| **6** | Admin do Portal | Gerência de acessos + configs + métricas | 0 (admin) | 🟡 Média | Fase 2 |
| **7** | Polimento & PWA Avançado | 2FA, push, offline, multi-idioma | 0 | 🟢 Baixa | Fase 5 |

**Total de actions a implementar:** 21 (+ 3 actions de rota `requestMagicLink`, `forgotPassword`, `resetPassword` na Fase 1A)

---

## 📅 Fase 1A — Correções Críticas & Segurança

### Objetivo
Corrigir funcionalidades **referenciadas pela UI mas não implementadas** e **falhas de segurança** identificadas na auditoria.

### O que será feito

| # | Entrega | Tipo | Arquivos Afetados |
|---|---------|------|-------------------|
| 1 | `requestMagicLink()` — gerar token + enviar e-mail | Action nova | `PortalController.php`, `PortalAccess.php`, `login.php` |
| 2 | `forgotPassword()` / `resetPassword()` — fluxo completo de recuperação de senha | Action nova (x2) | `PortalController.php`, `PortalAccess.php`, novas views `auth/forgot.php`, `auth/reset.php` |
| 3 | Login unificado — detectar cliente no `UserController::login()` | Modificação | `UserController.php` |
| 4 | `checkInactivity()` — ativar timeout de sessão do portal | Segurança | `index.php` |
| 5 | Exigir senha atual no `updateProfile()` | Segurança | `PortalController.php`, `profile/index.php` |
| 6 | Ler `magic_link_expiry_hours` da config no model | Bugfix | `PortalAccess.php` |
| 7 | Ler `require_password` da config no login | Bugfix | `PortalController.php`, `login.php` |
| 8 | Validação de força de senha server-side | Segurança | `PortalController.php` |
| 9 | Rota `requestMagicLink` na routes.php | Config | `routes.php` |
| 10 | Novas chaves de tradução (forgot, reset, current password) | i18n | `portal.php` |

### Checklist
```
[x] PortalController::requestMagicLink() — gera token via PortalAccess::generateMagicToken(), lê magic_link_expiry_hours da config, envia e-mail com link
[x] PortalController::forgotPassword() — GET: form de email / POST: gera token de reset, envia e-mail
[x] PortalController::resetPassword() — GET: valida token, form nova senha / POST: salva nova senha, invalida token
[x] PortalAccess — método generateResetToken() e validateResetToken() (colunas reset_token, reset_token_expires_at)
[x] Migration SQL: ALTER customer_portal_access ADD reset_token, reset_token_expires_at
[x] UserController::login() — após falha de login admin, tentar login como cliente do portal (fluxo unificado)
[x] index.php — chamar PortalAuthMiddleware::checkInactivity() quando portal_customer_id existir na sessão
[x] PortalController::updateProfile() — validar senha atual antes de permitir alteração de senha
[x] profile/index.php — campo de "senha atual" adicionado ao form
[x] PortalController — ler config require_password e condicionar lógica de login
[x] PortalAccess::generateMagicToken() — ler expiryHours de getConfig('magic_link_expiry_hours')
[x] Validação server-side de força de senha (min 8 chars, letras + números)
[x] routes.php — action requestMagicLink mapeada
[x] portal.php — novas chaves: forgot_title, forgot_btn, reset_title, reset_btn, profile_password_current, etc.
[x] Arquivo SQL: sql/update_202603241200_portal_fase1a.sql
```

---

## 📅 Fase 1B — Visual & Navegação

### Objetivo
Alinhar a identidade visual do portal com o Akti (azul #3b82f6), implementar navegação desktop e corrigir problemas de PWA.

### O que será feito

| # | Entrega | Tipo | Arquivos Afetados |
|---|---------|------|-------------------|
| 1 | Trocar paleta de roxo (#667eea) para azul Akti (#3b82f6) | Visual | `portal.css` |
| 2 | Remover gradients agressivos (botões, auth background) | Visual | `portal.css` |
| 3 | Implementar navegação desktop (top nav ≥768px) | UX | `header.php`, `portal.css` |
| 4 | Aumentar max-width para desktop (600px → 960px) | UX | `portal.css` |
| 5 | Trocar ícones `fas` por `far` (outline) na nav | Visual | `footer.php`, `header.php` |
| 6 | Criar `portal-manifest.json` separado | PWA | Novo arquivo, `header.php`, `header_auth.php` |
| 7 | Limitar scope do Service Worker ao portal | PWA | `portal-sw.js`, `footer.php` |
| 8 | Atualizar `theme-color` para `#3b82f6` | PWA | `header.php`, `header_auth.php` |
| 9 | Remover dark mode automático (ou adicionar toggle) | Visual | `portal.css` |
| 10 | Toast info color atualizada | JS | `portal.js` |

### Checklist
```
[x] portal.css — variáveis CSS: --portal-primary: #3b82f6, --portal-gradient: linear-gradient(135deg, #3b82f6, #1d4ed8)
[x] portal.css — botões: background sólido, sem gradient 3D, border-radius 8px
[x] portal.css — auth body: background var(--portal-surface) ao invés de gradient roxo
[x] portal.css — sombras sutis (trocar sombra forte por --portal-shadow)
[x] portal.css — .portal-desktop-nav com links horizontais, d-none d-md-flex
[x] portal.css — @media ≥768px: max-width 960px, ajustar stats-grid para 4 colunas
[x] portal.css — remover @media (prefers-color-scheme: dark) inteiro
[x] header.php — <nav class="portal-desktop-nav"> entre topbar-left e topbar-right
[x] header.php — trocar meta theme-color para #3b82f6
[x] header_auth.php — trocar meta theme-color para #3b82f6
[x] footer.php — ícones far (outline) na bottom nav
[x] portal-manifest.json — novo arquivo com start_url: "?page=portal", theme_color: #3b82f6
[x] header.php e header_auth.php — apontar manifest para portal-manifest.json
[x] portal-sw.js — registrar com scope limitado e filtrar fetch por URL do portal
[x] portal.js — toast info color para #3b82f6
```

---

## 📅 Fase 2 — Meus Pedidos

### Objetivo
Implementar a seção de pedidos do portal: listagem com filtros, detalhe completo com timeline, e aprovação/recusa de orçamentos.

### O que será feito

| # | Entrega | Tipo | Arquivos Afetados |
|---|---------|------|-------------------|
| 1 | `orders()` — listagem com tabs (Todos, Abertos, Aprovação, Concluídos) | Action nova | `PortalController.php` |
| 2 | `orderDetail()` — detalhe completo: timeline, itens, parcelas, mensagens | Action nova | `PortalController.php` |
| 3 | `approveOrder()` — aprovar orçamento com registro IP/notas | Action nova | `PortalController.php` |
| 4 | `rejectOrder()` — recusar orçamento com registro IP/notas | Action nova | `PortalController.php` |
| 5 | Views: `orders/index.php`, `orders/detail.php` | Views novas | `app/views/portal/orders/` |
| 6 | Model: métodos de listagem com filtros, detalhe com items | Model | `PortalAccess.php` ou novo `PortalOrder.php` |
| 7 | Ler `allow_order_approval` da config | Config | `PortalController.php` |
| 8 | Gravar `customer_approval_status/at/ip/notes` | BD | `PortalAccess.php` |
| 9 | CSS para timeline visual, cards de pedido, tabs de filtro | CSS | `portal.css` |
| 10 | Novas chaves de tradução para pedidos e aprovação | i18n | `portal.php` |

### Checklist
```
[x] PortalController::orders() — GET, auth check, busca pedidos com filtro (tab: all/open/approval/done), paginação
[x] PortalController::orderDetail() — GET &id=X, auth check, busca pedido + itens + parcelas + timeline
[x] PortalController::approveOrder() — POST, auth check, valida pedido pertence ao cliente, grava approval_status='aprovado', at, ip, notes
[x] PortalController::rejectOrder() — POST, idem com status='recusado'
[x] Model — getOrdersByCustomer($customerId, $filter, $limit, $offset): lista com JOIN order_items count
[x] Model — getOrderDetail($orderId, $customerId): pedido + items + installments
[x] Model — approveOrder($orderId, $customerId, $ip, $notes): UPDATE orders SET customer_approval_*
[x] View orders/index.php — tabs de filtro, lista de cards de pedido, paginação, empty state
[x] View orders/detail.php — timeline visual do pipeline, lista de itens, totais, seção de aprovação condicional
[x] portal.css — .portal-timeline, .portal-order-detail, .portal-tab-filter, .portal-approval-card
[x] portal.php — chaves orders_*, order_detail_*, approval_*
[x] Ler config allow_order_approval para mostrar/ocultar botões de aprovação
```

---

## 📅 Fase 3 — Financeiro & Rastreamento

### Objetivo
Implementar a visualização de parcelas (abertas, pagas, atrasadas) e rastreamento de envios.

### O que será feito

| # | Entrega | Tipo | Arquivos Afetados |
|---|---------|------|-------------------|
| 1 | `installments()` — listagem de parcelas com tabs (abertas, pagas, todas) | Action nova | `PortalController.php` |
| 2 | `installmentDetail()` — detalhe da parcela com link de pagamento | Action nova | `PortalController.php` |
| 3 | `tracking()` — tela de rastreamento por pedido | Action nova | `PortalController.php` |
| 4 | Views: `financial/index.php`, `financial/detail.php`, `tracking/index.php` | Views novas | `app/views/portal/financial/`, `app/views/portal/tracking/` |
| 5 | Model: queries de parcelas por cliente, detalhe de parcela | Model | `PortalAccess.php` ou novo model |
| 6 | CSS para cards de parcelas, badges de status, timeline de rastreamento | CSS | `portal.css` |
| 7 | Novas chaves de tradução para financeiro e rastreamento | i18n | `portal.php` |

### Checklist
```
[x] PortalController::installments() — GET, auth, lista parcelas do cliente com JOIN orders, tabs (open/paid/all)
[x] PortalController::installmentDetail() — GET &id=X, auth, detalhe da parcela + dados do pedido + link de pagamento (se gateway ativo)
[x] PortalController::tracking() — GET &id=X, auth, dados de envio do pedido (tracking_code, carrier, timeline)
[x] Model — getInstallmentsByCustomer($customerId, $filter): parcelas com JOIN orders
[x] Model — getInstallmentDetail($installmentId, $customerId): parcela + pedido
[x] Model — getTrackingInfo($orderId, $customerId): dados de envio
[x] View financial/index.php — tabs open/paid/all, cards de parcela com badge status, valor, vencimento
[x] View financial/detail.php — dados completos da parcela, botão "Pagar Online" (condicional a gateway)
[x] View tracking/index.php — código de rastreio, transportadora, timeline visual de eventos
[x] portal.css — .portal-installment-card, .portal-tracking-timeline, badges de status financeiro
[x] portal.php — chaves financial_*, tracking_*
```

---

## 📅 Fase 4 — Novo Pedido / Catálogo

### Objetivo
Permitir que o cliente monte um novo pedido/orçamento a partir do catálogo de produtos, com carrinho de compras via sessão.

### O que será feito

| # | Entrega | Tipo | Arquivos Afetados |
|---|---------|------|-------------------|
| 1 | `newOrder()` — tela de catálogo com busca e categorias | Action nova | `PortalController.php` |
| 2 | `getProducts()` — endpoint AJAX para busca de produtos | Action nova | `PortalController.php` |
| 3 | `addToCart()` — adicionar item ao carrinho (sessão) | Action nova | `PortalController.php` |
| 4 | `removeFromCart()` — remover item do carrinho | Action nova | `PortalController.php` |
| 5 | `updateCartItem()` — atualizar quantidade | Action nova | `PortalController.php` |
| 6 | `getCart()` — endpoint AJAX para ler carrinho | Action nova | `PortalController.php` |
| 7 | `submitOrder()` — finalizar pedido como status=orcamento | Action nova | `PortalController.php` |
| 8 | Views: `orders/new.php`, `orders/cart.php` | Views novas | `app/views/portal/orders/` |
| 9 | Ler configs `allow_new_orders`, `show_prices_in_catalog` | Config | `PortalController.php` |
| 10 | Setar `portal_origin=1` nos pedidos criados | BD | `PortalController.php` |
| 11 | JS para carrinho interativo (AJAX, atualização de totais) | JS | `portal.js` ou novo arquivo |

### Checklist
```
[x] PortalController::newOrder() — GET, auth, carrega categorias e produtos, respeitando show_prices_in_catalog e allow_new_orders
[x] PortalController::getProducts() — GET AJAX, retorna JSON de produtos filtrados por categoria/busca
[x] PortalController::addToCart() — POST AJAX, adiciona item a $_SESSION['portal_cart']
[x] PortalController::removeFromCart() — POST AJAX, remove item do cart
[x] PortalController::updateCartItem() — POST AJAX, atualiza quantidade no cart
[x] PortalController::getCart() — GET AJAX, retorna JSON do carrinho atual com totais
[x] PortalController::submitOrder() — POST, cria pedido via Order model com status=orcamento, portal_origin=1
[x] View orders/new.php — grid/lista de produtos com busca, filtro por categoria, botão "Adicionar ao carrinho"
[x] View orders/cart.php — lista de itens no carrinho, quantidades editáveis, total, botão "Enviar Pedido"
[x] portal.js — funções de carrinho: addToCart, removeFromCart, updateQty com Portal.post/get
[x] Usar configs allow_new_orders e show_prices_in_catalog para condicionar funcionalidade
[x] portal.php — chaves cart_*, catalog_*, new_order_*
[x] Migration SQL (se necessário): nenhuma nova tabela esperada
```

---

## 📅 Fase 5 — Mensagens & Documentos

### Objetivo
Implementar chat de mensagens por pedido (model já existe) e acesso a documentos (NF-e, boletos, comprovantes).

### O que será feito

| # | Entrega | Tipo | Arquivos Afetados |
|---|---------|------|-------------------|
| 1 | `messages()` — tela de mensagens por pedido ou geral | Action nova | `PortalController.php` |
| 2 | `sendMessage()` — enviar mensagem (POST) | Action nova | `PortalController.php` |
| 3 | `documents()` — listagem de documentos do cliente | Action nova | `PortalController.php` |
| 4 | `downloadDocument()` — download seguro de arquivo | Action nova | `PortalController.php` |
| 5 | Views: `messages/index.php`, `documents/index.php` | Views novas | `app/views/portal/messages/`, `app/views/portal/documents/` |
| 6 | Usar `PortalMessage` model (já pronto) | Model | `PortalMessage.php` |
| 7 | Ler config `allow_messages` | Config | `PortalController.php` |
| 8 | CSS para chat (bolhas de mensagem, input fixo) | CSS | `portal.css` |

### Checklist
```
[x] PortalController::messages() — GET, auth, lista mensagens (últimas conversas ou por pedido se &order_id=X)
[x] PortalController::sendMessage() — POST, auth, PortalMessage::create(), resposta AJAX ou redirect
[x] PortalController::documents() — GET, auth, lista documentos (NF-e, boletos) vinculados aos pedidos do cliente
[x] PortalController::downloadDocument() — GET &id=X, auth, valida permissão, serve arquivo
[x] PortalMessage::markAsRead() — chamado ao abrir conversa
[x] View messages/index.php — lista de conversas ou chat (bolhas de mensagem), input de texto + enviar
[x] View documents/index.php — lista de documentos com ícone por tipo, botão download
[x] portal.css — .portal-chat-bubble, .portal-chat-input, .portal-document-card
[x] portal.php — chaves messages_*, documents_*
[x] Ler config allow_messages para habilitar/desabilitar
[x] PortalMessage::countUnread() já chamado no dashboard — agora com link funcional para messages
```

---

## 📅 Fase 6 — Admin do Portal

### Objetivo
Criar telas administrativas (dentro do painel admin existente) para gerenciar acessos, configurações e métricas do portal.

### O que será feito

| # | Entrega | Tipo | Arquivos Afetados |
|---|---------|------|-------------------|
| 1 | Tela admin de listagem/gestão de acessos do portal | View admin | Novo controller ou novo módulo em `UserController` |
| 2 | Tela admin de configurações do portal (`customer_portal_config`) | View admin | Novo controller ou `SettingsController` |
| 3 | Dashboard admin com métricas do portal | View admin | `DashboardController.php` ou novo |
| 4 | Ativar tabela `customer_portal_sessions` para multi-device | Backend | `PortalAuthMiddleware.php`, `PortalAccess.php` |
| 5 | Gerenciamento de sessões ativas (forçar logout remoto) | Backend | Novo método em `PortalAccess.php` |
| 6 | Permissão de grupo para gerenciar portal | Config | `groups.php`, `menu.php` |

### Checklist
```
[x] Controller admin — listagem de customer_portal_access com JOIN customers (nome, email, último login, status)
[x] Controller admin — ativar/desativar acesso, resetar senha, enviar magic link
[x] Controller admin — tela de edição de customer_portal_config (formulário key-value)
[x] Dashboard admin — widget: total de acessos, logins recentes, clientes ativos, mensagens pendentes
[x] PortalAuthMiddleware — integrar com customer_portal_sessions (gravar/validar sessões na tabela)
[x] Método de forçar logout: DELETE sessão específica ou todas de um cliente
[x] Views admin: portal_admin/index.php, portal_admin/create.php, portal_admin/edit.php, portal_admin/config.php
[x] Permissão 'portal_admin' no sistema de grupos (menu.php)
[x] Menu admin: link para gerenciamento do portal (header.php dropdown)
[x] Rotas: portal_admin registrado em routes.php com todas as actions
[x] Migration SQL: sql/update_202506261200_portal_admin.sql
```

---

## 📅 Fase 7 — Polimento & PWA Avançado

### Objetivo
Melhorias de experiência final, segurança avançada e funcionalidades PWA completas.

### O que será feito

| # | Entrega | Tipo |
|---|---------|------|
| 1 | 2FA opcional via e-mail (código de 6 dígitos) | Segurança |
| 2 | Notificações push via Service Worker | PWA |
| 3 | Página offline customizada | PWA |
| 4 | Splash screen PWA customizada | PWA |
| 5 | Skeleton loading nos cards do dashboard | UX |
| 6 | Pull-to-refresh no dashboard (mobile) | UX |
| 7 | Máscara de telefone e CPF/CNPJ nos formulários | UX |
| 8 | Rate limiting por IP global (spray attack) | Segurança |
| 9 | Log de ações sensíveis via Logger | Segurança |
| 10 | Idiomas adicionais (en, es) | i18n |
| 11 | Foto/avatar do cliente | UX |
| 12 | Indicador de status offline | PWA |

### Checklist
```
[x] PortalAccess::generate2faCode(), validate2faCode(), is2faEnabled(), toggle2fa() — model 2FA completo
[x] PortalController::verify2fa() — GET/POST, tela de código 2FA, validação
[x] PortalController::resend2fa() — POST AJAX, reenvio de código
[x] PortalController::toggle2fa() — POST AJAX, ativar/desativar 2FA no perfil
[x] View auth/verify_2fa.php — tela de inserção de código 2FA com auto-submit
[x] portal-sw.js — Push Notification listeners (push, notificationclick), offline fallback
[x] View offline.html — Página offline customizada com auto-reload
[x] portal-manifest.json — shortcuts (Pedidos, Novo, Financeiro) para PWA
[x] Dashboard — Skeleton loading (portal-skeleton-stat-card, portal-skeleton-order-card)
[x] portal.js — Pull-to-refresh (PortalPullToRefresh) para mobile (touch devices)
[x] portal.js — Input masks automáticas para phone/CPF/CNPJ (PortalMasks)
[x] PortalController::checkRateLimit() — Rate limiting por IP usando ip_hits
[x] Logger integrado em login, logout, password change, approve, reject, 2FA, avatar upload
[x] app/lang/en/portal.php — Tradução completa para inglês
[x] app/lang/es/portal.php — Tradução completa para espanhol
[x] PortalLang::getAvailableLanguages() — habilitado en + es
[x] PortalAccess::updateAvatar(), getAvatar() — model de avatar
[x] PortalController::uploadAvatar() — POST multipart, validação tipo/tamanho, AJAX support
[x] Profile view — avatar section (upload, preview, placeholder com inicial)
[x] Header topbar — avatar do cliente exibido ao lado do nome
[x] portal.js — PortalOffline (indicador de status offline, barra amarela)
[x] portal.js — Avatar upload preview + auto-submit via AJAX
[x] portal.js — 2FA toggle handler via AJAX
[x] portal.css — Skeleton loading, avatar, 2FA card, offline bar, pull-to-refresh, input masks
[x] Profile view — Card de 2FA com toggle switch e badge de status
[x] Profile view — Input masks em telefone e documento
[x] Migration SQL: sql/update_202503251200_portal_fase7.sql
[x] Footer — Push notification permission request no SW registration
```

---

## 📈 Resumo de Entregas por Fase

| Fase | Actions Novas | Views Novas | Migrations SQL | Estimativa de Complexidade |
|------|--------------|-------------|---------------|--------------------------|
| 1A | 3 | 2 | 1 | 🟡 Média |
| 1B | 0 | 0 | 0 | 🟡 Média (CSS/PWA) |
| 2 | 4 | 2 | 0 | 🟡 Média |
| 3 | 3 | 3 | 0 | 🟡 Média |
| 4 | 7 | 2 | 0 | 🔴 Alta |
| 5 | 4 | 2 | 0 | 🟡 Média |
| 6 | 0 (admin) | 2 (admin) | 0 | 🟡 Média |
| 7 | 0 | 0 | 1 | 🟢 Baixa |
| **Total** | **21** | **13** | **2** | — |

---

## 🔗 Dependências entre Fases

```
Fase 1A ──► Fase 2 ──► Fase 3
   │            │
   │            ├──► Fase 4
   │            │
   │            ├──► Fase 5
   │            │
   │            └──► Fase 6
   │
Fase 1B (paralela a 1A)
                           Fase 7 (após Fase 5)
```

- **Fases 1A e 1B** podem ser executadas **em paralelo**
- **Fase 2** depende de **1A** (correções de segurança e login unificado são pré-requisitos)
- **Fases 3, 4, 5 e 6** dependem de **Fase 2** (pedidos são a base para financeiro, carrinho, mensagens)
- **Fases 3, 4, 5** podem ser executadas **em paralelo** entre si
- **Fase 7** depende de **Fase 5** (polimento final quando tudo funcional)

---

*Roadmap gerado com base na auditoria real dos arquivos e no relatório técnico V2 do Portal do Cliente Akti.*
