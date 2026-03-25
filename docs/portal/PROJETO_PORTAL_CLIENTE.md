# 📱 Portal do Cliente — Projeto Completo

> **Módulo:** `customer_portal`  
> **Status:** Projeto / Planejamento  
> **Prioridade:** Alta  
> **Estimativa:** ~35-45 arquivos novos + alterações em ~10 existentes  
> **Data:** 24/03/2026  

---

## 1. Visão Geral

Área exclusiva para o **cliente final** acessar pelo celular (mobile-first, PWA-ready), onde ele pode:

| Funcionalidade | Descrição |
|---|---|
| 🔐 Login próprio | Acesso por **e-mail + senha** ou **link mágico** (token por e-mail/WhatsApp) |
| 📦 Meus Pedidos | Listar todos os pedidos com filtros por status |
| 📊 Acompanhamento Pipeline | Timeline visual do progresso do pedido (etapa atual, histórico) |
| ✅ Aprovação de Orçamento | Aprovar ou recusar pedidos em `aguardando_aprovacao` |
| 🛒 Novo Pedido (Orçamento) | Montar pedido a partir do catálogo de produtos → cai como `orcamento` |
| 💰 Parcelas / Financeiro | Ver parcelas em aberto, pagas, atrasadas. Download de boleto/comprovante |
| 🚚 Rastreamento de Envio | Ver status de envio, código de rastreio, previsão de entrega |
| 💬 Mensagens | Comunicação com a empresa (notas do pedido) |
| 📄 Documentos | Acesso a NF-e, boletos, comprovantes gerados |

**Princípio:** O portal NÃO compartilha sessão com o painel admin. É uma área pública autenticada separada, com layout mobile-first próprio.

---

## 2. Arquitetura Técnica

### 2.1 Autenticação do Cliente

O cliente NÃO é um `user` do sistema (tabela `users`). Ele é um `customer` (tabela `customers`).  
Será criada uma estrutura de autenticação **separada**:

```
┌─────────────────────────────────────────────────┐
│           customer_portal_access                 │
├─────────────────────────────────────────────────┤
│ id (PK)                                          │
│ customer_id (FK → customers.id)                  │
│ email (UNIQUE, login)                            │
│ password_hash (bcrypt)                           │
│ magic_token (VARCHAR 128, nullable)              │
│ magic_token_expires_at (DATETIME)                │
│ is_active (TINYINT, default 1)                   │
│ last_login_at (DATETIME)                         │
│ last_login_ip (VARCHAR 45)                       │
│ created_at (TIMESTAMP)                           │
│ updated_at (TIMESTAMP)                           │
└─────────────────────────────────────────────────┘
```

**Dois métodos de login:**

1. **E-mail + Senha** — cadastro gerenciado pelo admin ou auto-registro.
2. **Link Mágico** — o admin ou o sistema envia um link temporário (token 64 chars, validade 24h) por e-mail/WhatsApp. O cliente clica e entra direto.

**Sessão separada:** Usa chave `$_SESSION['portal_customer_id']` (diferente de `$_SESSION['user_id']`), evitando conflito com o painel admin.

### 2.2 Roteamento

Nova página pública no `routes.php`:

```php
'portal' => [
    'controller'     => 'PortalController',
    'default_action' => 'index',
    'public'         => true,   // acesso sem login de admin
    'before_auth'    => true,   // processado antes do auth check do admin
    'actions' => [
        // Auth
        'login'             => 'login',
        'loginMagic'        => 'loginMagic',
        'logout'            => 'logout',
        'register'          => 'register',
        'forgotPassword'    => 'forgotPassword',
        'resetPassword'     => 'resetPassword',

        // Dashboard
        'dashboard'         => 'dashboard',

        // Pedidos
        'orders'            => 'orders',
        'orderDetail'       => 'orderDetail',
        'approveOrder'      => 'approveOrder',
        'rejectOrder'       => 'rejectOrder',

        // Novo Pedido (orçamento)
        'newOrder'          => 'newOrder',
        'getProducts'       => 'getProducts',
        'addToCart'         => 'addToCart',
        'removeFromCart'    => 'removeFromCart',
        'updateCartItem'    => 'updateCartItem',
        'getCart'           => 'getCart',
        'submitOrder'       => 'submitOrder',

        // Financeiro
        'installments'      => 'installments',
        'installmentDetail' => 'installmentDetail',

        // Tracking
        'tracking'          => 'tracking',

        // Mensagens
        'messages'          => 'messages',
        'sendMessage'       => 'sendMessage',

        // Documentos
        'documents'         => 'documents',
        'downloadDocument'  => 'downloadDocument',

        // Perfil
        'profile'           => 'profile',
        'updateProfile'     => 'updateProfile',
    ],
],
```

### 2.3 Estrutura de Arquivos

```
app/
├── controllers/
│   └── PortalController.php          ← Controller principal (namespace Akti\Controllers)
│
├── models/
│   ├── PortalAccess.php              ← Model de autenticação do cliente
│   └── PortalMessage.php             ← Model de mensagens cliente↔empresa
│
├── services/
│   └── PortalService.php             ← Lógica de negócio do portal
│
├── middleware/
│   └── PortalAuthMiddleware.php      ← Verificação de autenticação do portal
│
└── views/
    └── portal/
        ├── layout/
        │   ├── header.php            ← Layout mobile-first (standalone, sem sidebar admin)
        │   ├── footer.php            ← Footer com bottom-nav mobile
        │   └── nav_bottom.php        ← Barra de navegação inferior (mobile)
        ├── auth/
        │   ├── login.php             ← Tela de login
        │   ├── register.php          ← Auto-registro (se habilitado)
        │   ├── forgot_password.php   ← Recuperação de senha
        │   └── reset_password.php    ← Redefinir senha
        ├── dashboard.php             ← Home do cliente (resumo)
        ├── orders/
        │   ├── index.php             ← Lista de pedidos
        │   ├── detail.php            ← Detalhe com timeline do pipeline
        │   └── approve.php           ← Tela de aprovação de orçamento
        ├── new_order/
        │   ├── catalog.php           ← Catálogo de produtos (carrinho)
        │   └── checkout.php          ← Revisão e envio do pedido
        ├── installments/
        │   ├── index.php             ← Parcelas em aberto / pagas
        │   └── detail.php            ← Detalhe da parcela (boleto, comprovante)
        ├── tracking/
        │   └── index.php             ← Rastreamento de envio
        ├── messages/
        │   └── index.php             ← Chat de mensagens
        ├── documents/
        │   └── index.php             ← NF-e, boletos, comprovantes
        └── profile/
            └── index.php             ← Dados cadastrais do cliente

assets/
├── css/
│   └── portal.css                    ← CSS exclusivo do portal (mobile-first)
└── js/
    └── portal.js                     ← JS exclusivo do portal (SPA-like navigation)
```

---

## 3. Banco de Dados — Novas Tabelas

### 3.1 `customer_portal_access` — Autenticação

```sql
CREATE TABLE IF NOT EXISTS customer_portal_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    magic_token VARCHAR(128) DEFAULT NULL,
    magic_token_expires_at DATETIME DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME DEFAULT NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    failed_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_portal_email (email),
    UNIQUE KEY uq_portal_customer (customer_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.2 `customer_portal_sessions` — Sessões ativas (opcional, para multi-dispositivo)

```sql
CREATE TABLE IF NOT EXISTS customer_portal_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    session_token VARCHAR(128) NOT NULL,
    device_info VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_session_token (session_token),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.3 `customer_portal_messages` — Mensagens cliente ↔ empresa

```sql
CREATE TABLE IF NOT EXISTS customer_portal_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    sender_type ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    sender_id INT DEFAULT NULL COMMENT 'user_id se admin, NULL se customer',
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME DEFAULT NULL,
    attachment_path VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    KEY idx_portal_msg_customer (customer_id),
    KEY idx_portal_msg_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.4 `customer_portal_config` — Configurações do portal

```sql
CREATE TABLE IF NOT EXISTS customer_portal_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT DEFAULT NULL,
    descricao VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO customer_portal_config (config_key, config_value, descricao) VALUES
('portal_enabled', '1', 'Portal do cliente ativo (0=desativado)'),
('allow_self_register', '0', 'Permitir auto-registro do cliente (0=só admin cria)'),
('allow_new_orders', '1', 'Permitir que o cliente crie novos pedidos/orçamentos'),
('allow_order_approval', '1', 'Permitir que o cliente aprove/recuse orçamentos'),
('allow_messages', '1', 'Permitir mensagens no portal'),
('magic_link_expiry_hours', '24', 'Validade do link mágico em horas'),
('show_prices_in_catalog', '1', 'Exibir preços no catálogo de novo pedido'),
('require_password', '0', 'Exigir senha (0=apenas link mágico)')
ON DUPLICATE KEY UPDATE config_key = config_key;
```

### 3.5 Alteração na tabela `orders` — campos de aprovação do cliente

```sql
ALTER TABLE orders
    ADD COLUMN customer_approval_status ENUM('pendente','aprovado','recusado') DEFAULT NULL
        COMMENT 'Status de aprovação do cliente via portal',
    ADD COLUMN customer_approval_at DATETIME DEFAULT NULL
        COMMENT 'Data/hora da aprovação/recusa pelo cliente',
    ADD COLUMN customer_approval_ip VARCHAR(45) DEFAULT NULL
        COMMENT 'IP do cliente no momento da aprovação',
    ADD COLUMN customer_approval_notes TEXT DEFAULT NULL
        COMMENT 'Observações do cliente na aprovação/recusa',
    ADD COLUMN portal_origin TINYINT(1) DEFAULT 0
        COMMENT 'Se o pedido foi originado pelo portal do cliente';
```

---

## 4. Fluxo de Telas (UX Mobile-First)

### 4.1 Login

```
┌──────────────────────────────┐
│        🔶 AKTI               │
│    Portal do Cliente         │
│                              │
│  ┌──────────────────────┐    │
│  │ seu@email.com        │    │
│  └──────────────────────┘    │
│  ┌──────────────────────┐    │
│  │ ••••••••             │    │
│  └──────────────────────┘    │
│                              │
│  [    🔑 Entrar            ] │
│                              │
│  ── ou ──                    │
│                              │
│  [📧 Receber link de acesso] │
│                              │
│  Esqueci minha senha         │
└──────────────────────────────┘
```

### 4.2 Dashboard (Home)

```
┌──────────────────────────────┐
│ 👋 Olá, João!                │
│ Empresa XYZ Ltda             │
├──────────────────────────────┤
│                              │
│  ┌────────┐ ┌────────┐      │
│  │ 3      │ │ 1      │      │
│  │Pedidos │ │Aguard. │      │
│  │Ativos  │ │Aprov.  │      │
│  └────────┘ └────────┘      │
│  ┌────────┐ ┌────────┐      │
│  │ 2      │ │ R$1.2k │      │
│  │Parcelas│ │Em      │      │
│  │Abertas │ │Aberto  │      │
│  └────────┘ └────────┘      │
│                              │
│  🔔 Notificações Recentes    │
│  ├ Pedido #142 em produção   │
│  ├ Parcela vence em 3 dias   │
│  └ Orçamento #155 pronto     │
│                              │
│  📦 Pedidos Recentes          │
│  ├ #155 Orçamento   R$800    │
│  ├ #142 Em produção R$2.500  │
│  └ #138 Concluído   R$1.200  │
│                              │
├──────────────────────────────┤
│ 🏠  📦  ➕  💰  👤          │
│ Home Ped. Novo Fin. Perfil   │
└──────────────────────────────┘
```

### 4.3 Meus Pedidos

```
┌──────────────────────────────┐
│ ← Meus Pedidos               │
├──────────────────────────────┤
│ [Todos] [Abertos] [Aprovação]│
│                              │
│ ┌──────────────────────────┐ │
│ │ #155 — 15/03/2026        │ │
│ │ 🟡 Aguardando Aprovação   │ │
│ │ 3 itens · R$ 800,00      │ │
│ │ [👁 Ver] [✅ Aprovar]     │ │
│ └──────────────────────────┘ │
│                              │
│ ┌──────────────────────────┐ │
│ │ #142 — 10/03/2026        │ │
│ │ 🟠 Em Produção            │ │
│ │ 5 itens · R$ 2.500,00    │ │
│ │ Previsão: 28/03           │ │
│ │ [👁 Ver] [🚚 Rastrear]   │ │
│ └──────────────────────────┘ │
│                              │
│ ┌──────────────────────────┐ │
│ │ #138 — 01/03/2026        │ │
│ │ 🟢 Concluído              │ │
│ │ 2 itens · R$ 1.200,00    │ │
│ │ [👁 Ver] [📄 NF-e]       │ │
│ └──────────────────────────┘ │
├──────────────────────────────┤
│ 🏠  📦  ➕  💰  👤          │
└──────────────────────────────┘
```

### 4.4 Detalhe do Pedido (Timeline Pipeline)

```
┌──────────────────────────────┐
│ ← Pedido #142                │
├──────────────────────────────┤
│                              │
│  Timeline de Progresso       │
│  ✅ Contato      02/03 14h   │
│  │                           │
│  ✅ Orçamento    03/03 09h   │
│  │                           │
│  ✅ Venda        05/03 11h   │
│  │  Aprovado pelo cliente    │
│  │                           │
│  🔵 Produção  ← ATUAL       │
│  │  Desde 10/03 (5 dias)    │
│  │                           │
│  ⚪ Preparação               │
│  ⚪ Envio/Entrega            │
│  ⚪ Financeiro               │
│  ⚪ Concluído                │
│                              │
│ ─────────────────────────    │
│  Itens do Pedido             │
│  ├ 2x Camiseta P · R$50     │
│  ├ 1x Banner 2m  · R$150    │
│  └ 2x Adesivo    · R$25     │
│                              │
│  Subtotal:    R$ 2.500,00   │
│  Desconto:    - R$ 0,00     │
│  Total:       R$ 2.500,00   │
│                              │
│  🚚 Envio: Entrega          │
│  📍 Rua ABC, 123            │
│  📦 Rastreio: —             │
│                              │
│  💰 Parcelas                 │
│  ├ 1/3 R$833 ✅ Paga 10/03   │
│  ├ 2/3 R$833 ⏳ Vence 10/04  │
│  └ 3/3 R$834 ⏳ Vence 10/05  │
│                              │
│ [💬 Enviar Mensagem]         │
├──────────────────────────────┤
│ 🏠  📦  ➕  💰  👤          │
└──────────────────────────────┘
```

### 4.5 Aprovação de Orçamento

```
┌──────────────────────────────┐
│ ← Aprovar Orçamento #155     │
├──────────────────────────────┤
│                              │
│  📋 Itens do Orçamento       │
│  ├ 2x Camiseta GG · R$100   │
│  ├ 1x Caneca      · R$35    │
│  └ Frete           · R$30   │
│                              │
│  Total: R$ 800,00           │
│                              │
│  📝 Observações da empresa:  │
│  "Prazo de produção: 10 dias │
│   úteis após aprovação."     │
│                              │
│  Condições: 3x sem juros    │
│                              │
│  ┌──────────────────────┐    │
│  │ Suas observações...  │    │
│  │                      │    │
│  └──────────────────────┘    │
│                              │
│  [  ✅ APROVAR ORÇAMENTO   ] │
│  [  ❌ Recusar             ] │
│                              │
│  Ao aprovar, você concorda   │
│  com as condições acima.     │
│  IP e data serão registrados.│
├──────────────────────────────┤
│ 🏠  📦  ➕  💰  👤          │
└──────────────────────────────┘
```

### 4.6 Novo Pedido (Catálogo)

```
┌──────────────────────────────┐
│ ← Novo Pedido                │
├──────────────────────────────┤
│ 🔍 Buscar produto...        │
│                              │
│ [Todas] [Camisetas] [Canecas]│
│                              │
│ ┌─────────┬─────────┐       │
│ │ 📷      │ 📷      │       │
│ │Camiseta │ Caneca  │       │
│ │R$ 50,00 │R$ 35,00 │       │
│ │[+ Add]  │[+ Add]  │       │
│ └─────────┴─────────┘       │
│ ┌─────────┬─────────┐       │
│ │ 📷      │ 📷      │       │
│ │Banner   │ Adesivo │       │
│ │R$150,00 │R$ 25,00 │       │
│ │[+ Add]  │[+ Add]  │       │
│ └─────────┴─────────┘       │
│                              │
│ ┌──────────────────────────┐ │
│ │ 🛒 Carrinho (3 itens)    │ │
│ │ Total: R$ 135,00         │ │
│ │ [Ver Carrinho →]         │ │
│ └──────────────────────────┘ │
├──────────────────────────────┤
│ 🏠  📦  ➕  💰  👤          │
└──────────────────────────────┘
```

### 4.7 Parcelas / Financeiro

```
┌──────────────────────────────┐
│ ← Financeiro                 │
├──────────────────────────────┤
│                              │
│  Resumo                      │
│  ┌────────┐ ┌────────┐      │
│  │R$1.667 │ │R$ 833  │      │
│  │Em      │ │Pago    │      │
│  │Aberto  │ │        │      │
│  └────────┘ └────────┘      │
│                              │
│ [Em aberto] [Pagas] [Todas]  │
│                              │
│ ┌──────────────────────────┐ │
│ │ Pedido #142 · Parc. 2/3  │ │
│ │ R$ 833,33                 │ │
│ │ 📅 Vence: 10/04/2026     │ │
│ │ ⏳ Pendente               │ │
│ │ [💳 Ver Detalhes]        │ │
│ └──────────────────────────┘ │
│                              │
│ ┌──────────────────────────┐ │
│ │ Pedido #142 · Parc. 3/3  │ │
│ │ R$ 834,34                 │ │
│ │ 📅 Vence: 10/05/2026     │ │
│ │ ⏳ Pendente               │ │
│ └──────────────────────────┘ │
│                              │
│ ┌──────────────────────────┐ │
│ │ Pedido #142 · Parc. 1/3  │ │
│ │ R$ 833,33                 │ │
│ │ ✅ Paga em 10/03/2026    │ │
│ └──────────────────────────┘ │
├──────────────────────────────┤
│ 🏠  📦  ➕  💰  👤          │
└──────────────────────────────┘
```

### 4.8 Rastreamento de Envio

```
┌──────────────────────────────┐
│ ← Rastreamento #142          │
├──────────────────────────────┤
│                              │
│  🚚 Status: Em trânsito     │
│                              │
│  Código: AB123456789BR       │
│  Transportadora: Correios    │
│                              │
│  📍 Timeline de Envio        │
│  ✅ 20/03 Objeto postado     │
│  │  Curitiba/PR              │
│  ✅ 21/03 Em trânsito        │
│  │  Centro de distribuição   │
│  🔵 22/03 Saiu para entrega  │
│  │  São Paulo/SP             │
│  ⚪ Entregue                  │
│                              │
│  📍 Destino:                 │
│  Rua ABC, 123 — São Paulo   │
│                              │
│  Previsão: 24/03/2026       │
├──────────────────────────────┤
│ 🏠  📦  ➕  💰  👤          │
└──────────────────────────────┘
```

---

## 5. Regras de Negócio

### 5.1 Autenticação

| Regra | Descrição |
|---|---|
| R1 | O acesso é criado pelo admin OU pelo auto-registro (se `allow_self_register = 1`) |
| R2 | Link mágico expira conforme `magic_link_expiry_hours` (padrão 24h) |
| R3 | Após 5 tentativas de login falhas, bloqueia por 15 minutos |
| R4 | Sessão do portal usa `$_SESSION['portal_customer_id']` — isolada do admin |
| R5 | O admin pode ativar/desativar acesso de qualquer cliente |

### 5.2 Pedidos

| Regra | Descrição |
|---|---|
| R6 | O cliente SÓ vê pedidos do próprio `customer_id` |
| R7 | Pedidos criados pelo portal entram com `status = 'orcamento'` e `pipeline_stage = 'orcamento'` e `portal_origin = 1` |
| R8 | Aprovação de orçamento registra IP, data e observações. Move para `pipeline_stage = 'venda'` |
| R9 | Recusa de orçamento registra motivo e move para `pipeline_stage = 'cancelado'` (ou mantém em orçamento com flag) |
| R10 | Cliente NÃO pode editar pedidos já em produção |

### 5.3 Financeiro

| Regra | Descrição |
|---|---|
| R11 | Cliente vê APENAS parcelas dos seus pedidos |
| R12 | Parcelas exibem status, valor, vencimento e se há link de pagamento (gateway) |
| R13 | Se houver integração com gateway (já existe no sistema), exibir botão "Pagar Online" |
| R14 | NÃO exibir transações internas do caixa — apenas parcelas |

### 5.4 Rastreamento

| Regra | Descrição |
|---|---|
| R15 | O `tracking_code` e `shipping_type` já existem na tabela `orders` |
| R16 | Se o pedido está em `pipeline_stage = 'envio'`, exibir seção de rastreamento |
| R17 | Integração futura com APIs de rastreio (Correios, Melhor Envio) é opcional |

### 5.5 Segurança

| Regra | Descrição |
|---|---|
| R18 | Toda query filtra por `customer_id` — **NUNCA** expor dados de outro cliente |
| R19 | Tokens de link mágico são `bin2hex(random_bytes(64))` — 128 chars |
| R20 | CSRF token obrigatório em todos os forms POST |
| R21 | Rate limiting no login (5 tentativas / 15min lockout) |
| R22 | Sanitização completa via `Input::post()` / `Input::get()` existentes |

---

## 6. API Endpoints (AJAX)

Todos os endpoints são acessados via `?page=portal&action=<action>`:

| Action | Método | Descrição | Retorno |
|---|---|---|---|
| `login` | POST | Autenticar cliente | `{success, redirect}` |
| `loginMagic` | GET | Login via link mágico (token na URL) | Redirect |
| `logout` | GET | Encerrar sessão | Redirect |
| `dashboard` | GET | Dados do dashboard (contadores, recentes) | HTML |
| `orders` | GET | Lista de pedidos (com filtros) | HTML/JSON |
| `orderDetail` | GET | Detalhe do pedido + timeline + itens + parcelas | HTML |
| `approveOrder` | POST | Aprovar orçamento | `{success, message}` |
| `rejectOrder` | POST | Recusar orçamento | `{success, message}` |
| `getProducts` | GET | Catálogo de produtos (paginado, busca) | JSON |
| `addToCart` | POST | Adicionar item ao carrinho (sessão) | JSON |
| `removeFromCart` | POST | Remover item do carrinho | JSON |
| `updateCartItem` | POST | Alterar quantidade no carrinho | JSON |
| `getCart` | GET | Conteúdo do carrinho | JSON |
| `submitOrder` | POST | Submeter novo pedido (orçamento) | `{success, order_id}` |
| `installments` | GET | Parcelas do cliente | HTML/JSON |
| `tracking` | GET | Dados de rastreio | JSON |
| `messages` | GET | Lista de mensagens | JSON |
| `sendMessage` | POST | Enviar mensagem | `{success}` |
| `documents` | GET | Lista de documentos (NF-e, boletos) | JSON |
| `downloadDocument` | GET | Download de documento | File |
| `profile` | GET | Dados do perfil | HTML |
| `updateProfile` | POST | Atualizar dados cadastrais | `{success}` |

---

## 7. Layout e Design (Mobile-First)

### 7.1 Princípios

- **Mobile-first**: CSS começa pelo mobile, `@media (min-width)` para desktop
- **PWA-ready**: Usa o `manifest.json` existente, pode ter service worker futuro
- **Bootstrap 5**: Mesmo framework do sistema, mas com layout próprio (sem sidebar admin)
- **Bottom Navigation**: Barra fixa inferior com 5 ícones (Home, Pedidos, Novo, Financeiro, Perfil)
- **Cores**: Herda a identidade visual do Akti (tema do tenant se disponível)
- **Touch-friendly**: Botões mínimo 44x44px, espaçamento generoso

### 7.2 Layout Base

```html
<!-- portal/layout/header.php -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#2c3e50">
    <link rel="manifest" href="manifest.json">
    <!-- Bootstrap 5 + Font Awesome + portal.css -->
</head>
<body class="portal-body">
    <!-- Top bar simples -->
    <nav class="portal-topbar">...</nav>
    
    <!-- Conteúdo principal -->
    <main class="portal-content">
```

```html
<!-- portal/layout/footer.php -->
    </main>
    
    <!-- Bottom Navigation (mobile) -->
    <nav class="portal-bottom-nav">
        <a href="?page=portal&action=dashboard"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="?page=portal&action=orders"><i class="fas fa-box"></i><span>Pedidos</span></a>
        <a href="?page=portal&action=newOrder" class="portal-nav-center"><i class="fas fa-plus"></i></a>
        <a href="?page=portal&action=installments"><i class="fas fa-wallet"></i><span>Financeiro</span></a>
        <a href="?page=portal&action=profile"><i class="fas fa-user"></i><span>Perfil</span></a>
    </nav>
    
    <!-- Scripts -->
</body>
</html>
```

---

## 8. Integração com o Sistema Existente

### 8.1 O que já existe e será reutilizado

| Componente | Uso no Portal |
|---|---|
| `Order` model | Leitura de pedidos, itens, custos extras |
| `Pipeline` model | Histórico de etapas (timeline) |
| `Financial` model | Parcelas do pedido |
| `Product` model | Catálogo para novo pedido |
| `CatalogLink` model | Referência de design (já tem carrinho público) |
| `Customer` model | Dados do cliente logado |
| `PaymentGateway` model | Links de pagamento nas parcelas |
| `NfeDocument` model | Documentos fiscais para download |
| `CompanySettings` model | Nome/logo da empresa, config do tenant |
| `Security` (CSRF) | Tokens CSRF nos forms |
| `Input` util | Sanitização de dados |
| EventDispatcher | Eventos de aprovação, novo pedido, etc. |

### 8.2 Novos Eventos

```php
// Em events.php
EventDispatcher::listen('portal.customer.logged_in', ...);
EventDispatcher::listen('portal.order.approved', ...);
EventDispatcher::listen('portal.order.rejected', ...);
EventDispatcher::listen('portal.order.created', ...);
EventDispatcher::listen('portal.message.sent', ...);
```

### 8.3 Impacto no Admin

- **Tela de Clientes**: Novo botão "Criar Acesso ao Portal" / "Enviar Link de Acesso"
- **Detalhe do Pedido**: Badge "Aprovado pelo cliente via Portal" com IP/data
- **Pipeline**: Indicação visual de pedidos originados pelo portal
- **Notificações Admin**: Alertas quando cliente aprova/recusa orçamento ou envia mensagem

---

## 9. Plano de Implementação (Fases)

### Fase 1 — Fundação (Prioridade Máxima)
1. ✏️ Migration SQL (tabelas novas + alter orders)
2. ✏️ Model `PortalAccess.php` (autenticação)
3. ✏️ Middleware `PortalAuthMiddleware.php`
4. ✏️ Controller `PortalController.php` (login + dashboard)
5. ✏️ Views: layout, login, dashboard
6. ✏️ CSS `portal.css` + JS `portal.js`
7. ✏️ Rota `portal` no `routes.php`

### Fase 2 — Pedidos + Aprovação
1. ✏️ Service `PortalService.php`
2. ✏️ Views: orders/index, orders/detail (timeline)
3. ✏️ Aprovação/Recusa de orçamento
4. ✏️ Eventos de aprovação

### Fase 3 — Novo Pedido (Orçamento)
1. ✏️ Views: new_order/catalog, new_order/checkout
2. ✏️ Carrinho em sessão
3. ✏️ Submissão do pedido como orçamento

### Fase 4 — Financeiro + Tracking
1. ✏️ Views: installments/index, installments/detail
2. ✏️ Views: tracking/index
3. ✏️ Integração com links de pagamento existentes

### Fase 5 — Mensagens + Documentos
1. ✏️ Model `PortalMessage.php`
2. ✏️ Views: messages, documents
3. ✏️ Integração com NF-e existente

### Fase 6 — Admin (Gestão do Portal)
1. ✏️ Botão "Criar Acesso Portal" na tela de clientes
2. ✏️ Tela de configuração do portal (admin)
3. ✏️ Indicadores visuais de ações do portal no pipeline/pedidos

---

## 10. Considerações de Segurança

1. **Isolamento total** — Toda query no portal filtra por `customer_id` da sessão
2. **Sem acesso admin** — A sessão do portal (`portal_customer_id`) não concede nenhum acesso ao painel admin
3. **Rate limiting** — Login com lockout após falhas
4. **CSRF** — Token em todo form POST
5. **XSS** — Sanitização via `e()` / `eAttr()` existentes
6. **SQL Injection** — Prepared statements (padrão do projeto)
7. **Link mágico** — Token `random_bytes(64)` + expiração + uso único (desativa após login)
8. **HTTPS** — Cookie seguro quando HTTPS ativo (já configurado em `session.php`)

---

## 11. Métricas de Sucesso

| Métrica | Meta |
|---|---|
| Tempo de carregamento mobile | < 2s (3G) |
| Lighthouse Performance Score | > 85 |
| Adoção pelos clientes | > 40% em 3 meses |
| Redução de chamados "qual o status?" | > 60% |
| Orçamentos aprovados via portal | > 30% do total |

---

## 12. Perguntas em Aberto (Para Decisão)

1. **Auto-registro**: O cliente pode criar sua própria conta ou só o admin cria?  
   deve permitir que o cliente possa se auto cadastrar gerando um usuario para o acesso ao "aplicativo" e o cadstro de cliente o sistema, sempre garantindo o vinculo do acesso com o cliente cadastrado no sistema. também deve ter a opção para que o admin envie um link de vinculo do cliente ja cadastrado no sistema, aonde o cliente só faça o cadastro de acesso e vincule automaticamente.

2. **Notificações push**: Implementar via Service Worker / Web Push?  
   gere o service e adicione uma forma simples para que o ususario consiga installar a pagina como app no sistema

3. **Multi-idioma**: O portal precisa suportar outros idiomas?  
   faça o sistema multi linguagem começando com pt-br por padrão.

4. **Upload de arquivos pelo cliente**: Permitir que envie referências/artes?  
   o cliente deve ter a opção para vincular imagens ou descrições a produtos do pedido. igual a forma como é feito no sistemapara que seja visivel na produção, tanto no descrição do pedido, quanto nos setores.

5. **Integração com Melhor Envio / Correios**: API de rastreamento automático?  
   Fase futura. Por ora, tracking manual (código inserido pelo admin).

---

> **Próximo passo:** Confirme as decisões pendentes (seção 12) e eu inicio a **Fase 1** — criação da migration SQL, models, controller, views e rota.
