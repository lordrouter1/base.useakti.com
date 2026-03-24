# 📋 RELATÓRIO COMPLETO — Portal do Cliente (Akti)

> **Data:** 24/03/2026  
> **Versão:** Fase 1 — Autenticação, Dashboard, Perfil  
> **Status:** Funcional com correções pendentes e melhorias identificadas

---

## 📐 1. Arquitetura Atual

### 1.1 Estrutura de Arquivos

```
app/
├── controllers/
│   └── PortalController.php        ← Controller principal (467 linhas)
├── middleware/
│   └── PortalAuthMiddleware.php     ← Sessão isolada do portal (156 linhas)
├── models/
│   ├── PortalAccess.php             ← CRUD + auth + stats (569 linhas)
│   └── PortalMessage.php            ← Mensagens cliente↔empresa (162 linhas)
├── services/
│   └── PortalLang.php               ← i18n do portal (116 linhas)
├── utils/
│   └── portal_helper.php            ← Helpers globais (109 linhas)
├── lang/
│   └── pt-br/
│       └── portal.php               ← 204 chaves de tradução
└── views/
    └── portal/
        ├── layout/
        │   ├── header.php            ← Layout autenticado (topbar + nav)
        │   ├── footer.php            ← Footer + bottom nav + PWA banner
        │   ├── header_auth.php       ← Layout login/register (gradient bg)
        │   └── footer_auth.php       ← Footer simples (sem nav)
        ├── auth/
        │   ├── login.php             ← Tela de login (143 linhas)
        │   └── register.php          ← Auto-registro (145 linhas)
        ├── dashboard.php             ← Dashboard principal (126 linhas)
        ├── profile/
        │   └── index.php             ← Perfil do cliente (114 linhas)
        └── disabled.php              ← Portal desabilitado (21 linhas)

assets/
├── css/
│   └── portal.css                   ← CSS mobile-first (904 linhas)
├── js/
│   └── portal.js                    ← JS: AJAX, PWA, toast, forms (231 linhas)

portal-sw.js                         ← Service Worker PWA (raiz, 104 linhas)

sql/
└── update_202603241000_portal_cliente.sql  ← Migration Fase 1 (163 linhas)

app/config/
└── routes.php                       ← Rota 'portal' (public, before_auth)
```

### 1.2 Banco de Dados — Tabelas Criadas

| Tabela | Descrição | Campos-chave |
|--------|-----------|--------------|
| `customer_portal_access` | Autenticação de clientes | `customer_id`, `email`, `password_hash`, `magic_token`, `is_active`, `failed_attempts`, `locked_until`, `lang` |
| `customer_portal_sessions` | Sessões ativas (para multi-device no futuro) | `customer_id`, `session_token`, `device_info`, `ip_address`, `expires_at` |
| `customer_portal_messages` | Chat cliente↔empresa | `customer_id`, `order_id`, `sender_type`, `sender_id`, `message`, `is_read`, `attachment_path` |
| `customer_portal_config` | Configurações globais do portal | `config_key`, `config_value`, `descricao` |

**Colunas adicionais em `orders`** (via ALTER TABLE condicional):
- `customer_approval_status` — ENUM('pendente','aprovado','recusado')
- `customer_approval_at` — DATETIME
- `customer_approval_ip` — VARCHAR(45)
- `customer_approval_notes` — TEXT
- `portal_origin` — TINYINT(1)
- Índice `idx_orders_customer_portal` — (customer_id, status, pipeline_stage)

### 1.3 Configurações do Portal (`customer_portal_config`)

| Chave | Valor Padrão | Descrição |
|-------|-------------|-----------|
| `portal_enabled` | `1` | Liga/desliga o portal inteiro |
| `allow_self_register` | `1` | Permite auto-registro |
| `allow_new_orders` | `1` | Permite criação de pedidos |
| `allow_order_approval` | `1` | Permite aprovar/recusar orçamentos |
| `allow_messages` | `1` | Permite chat |
| `magic_link_expiry_hours` | `24` | Validade do magic link |
| `show_prices_in_catalog` | `1` | Exibe preços no catálogo |
| `require_password` | `0` | Força senha (0=apenas magic link) |

---

## 🔐 2. Fluxo de Autenticação Atual

### 2.1 Login Separado (Problema Identificado)

```
Acesso Atual:
  ADMIN:   ?page=login           → UserController::login()   → $_SESSION['user_id']
  PORTAL:  ?page=portal          → PortalController::login()  → $_SESSION['portal_customer_id']
  
  ❌ São telas de login DIFERENTES em URLs DIFERENTES
  ❌ O cliente precisa saber acessar ?page=portal
  ❌ Não há detecção automática de tipo de conta
```

### 2.2 Sessão do Portal (Isolamento)

| Variável de Sessão | Tipo | Descrição |
|---------------------|------|-----------|
| `portal_customer_id` | int | ID do cliente na tabela `customers` |
| `portal_access_id` | int | ID do registro em `customer_portal_access` |
| `portal_customer_name` | string | Nome do cliente |
| `portal_email` | string | E-mail do cliente |
| `portal_lang` | string | Idioma preferido |
| `portal_last_activity` | int | Timestamp da última atividade |
| `portal_cart` | array | Carrinho do portal (removido no logout) |

### 2.3 Segurança Implementada

| Recurso | Status | Detalhes |
|---------|--------|----------|
| CSRF em forms | ✅ OK | `csrf_field()` em todos os formulários |
| CSRF meta tag | ✅ OK | Para AJAX via `Portal.csrfToken()` |
| Password hash bcrypt | ✅ OK | `password_hash(PASSWORD_BCRYPT)` |
| Rate limiting | ✅ OK | 5 tentativas → 15min lockout |
| Evento de bloqueio | ✅ OK | `portal.access.locked` disparado |
| Filtro por customer_id | ✅ OK | Todas queries filtram pelo ID do cliente |
| Session timeout | ✅ OK | 60min de inatividade (configurável) |
| Session fixation | ❌ FALTA | `session_regenerate_id()` não é chamado no login do portal |
| IP logging | ✅ OK | Último IP registrado no login |
| Magic link uso único | ✅ OK | Token invalidado após uso |
| Input sanitization | ✅ OK | Usa `Input::post()` e `Input::get()` |
| XSS prevention | ✅ OK | `e()` e `eAttr()` nas views |

---

## 🚨 3. Problemas e Campos Não Funcionais

### 3.1 Críticos (quebram funcionalidade)

| # | Problema | Arquivo | Status |
|---|----------|---------|--------|
| 1 | **`requestMagicLink` sem rota e sem método** — O `login.php` faz POST para `?page=portal&action=requestMagicLink`, mas não existe essa action na rota nem no controller | `login.php` L94, `routes.php`, `PortalController.php` | ❌ Quebrado |
| 2 | **`forgotPassword` sem método** — Rota mapeada em `routes.php` mas método não implementado no controller | `routes.php` L74, `PortalController.php` | ❌ Stub |
| 3 | **`resetPassword` sem método** — Idem | `routes.php` L75, `PortalController.php` | ❌ Stub |
| 4 | **Tabela `customer_portal_sessions` não é usada** — Criada na migração mas nunca consultada/populada. O sistema usa apenas `$_SESSION` | `PortalAuthMiddleware.php`, `PortalAccess.php` | ⚠️ Ociosa |
| 5 | **`session_regenerate_id()` ausente no login** — Vulnerável a session fixation | `PortalController.php` L142-153 | ❌ Falha seg. |

### 3.2 Ações Mapeadas Sem Implementação (Stubs no routes.php)

Estas actions estão declaradas em `routes.php` mas **NÃO TÊM método no controller**:

| Action | Descrição | Fase Planejada |
|--------|-----------|---------------|
| `orders` | Listagem de pedidos | Fase 2 |
| `orderDetail` | Detalhe do pedido | Fase 2 |
| `approveOrder` | Aprovar orçamento | Fase 2 |
| `rejectOrder` | Recusar orçamento | Fase 2 |
| `newOrder` | Tela de novo pedido | Fase 3 |
| `getProducts` | AJAX produtos catálogo | Fase 3 |
| `addToCart` / `removeFromCart` / `updateCartItem` / `getCart` | Carrinho | Fase 3 |
| `submitOrder` | Finalizar pedido | Fase 3 |
| `installments` / `installmentDetail` | Financeiro | Fase 4 |
| `tracking` | Rastreamento | Fase 4 |
| `messages` / `sendMessage` | Chat | Fase 5 |
| `documents` / `downloadDocument` | Documentos | Fase 5 |
| `forgotPassword` / `resetPassword` | Recuperação de senha | Fase 1 (pendente) |
| `requestMagicLink` | Solicitar magic link | Fase 1 (pendente) |

### 3.3 Inconsistências no CSS / Visual

| # | Problema | Detalhe |
|---|----------|---------|
| 1 | **Paleta de cores diverge do sistema admin** | Portal usa `#667eea → #764ba2` (roxo/violeta). Admin usa `#1e293b` (slate) + `#3b82f6` (blue). Não estão alinhados com identidade Akti |
| 2 | **Dark mode automático** | O CSS aplica dark mode via `prefers-color-scheme: dark`, mas não há toggle manual. Pode conflitar com preferência do usuário |
| 3 | **Navegação desktop incompleta** | Em `@media (min-width: 768px)` a bottom nav desaparece, mas **nenhum menu lateral ou top nav com links é mostrado** — o usuário desktop fica sem navegação |
| 4 | **Manifest.json é do sistema admin** | O `manifest.json` na raiz é genérico ("Akti — Gestão em Produção"), não do portal. O portal precisa de seu próprio manifest |
| 5 | **Service Worker na raiz** | `portal-sw.js` está na raiz e pode interceptar requests do admin. Deveria ter scope limitado |

---

## 🏗️ 4. Proposta: Login Unificado (Detecção Automática)

### 4.1 Conceito

Em vez de duas telas de login separadas, o sistema deve ter **uma única tela de login** na URL do tenant (`?page=login`) que:

1. Recebe e-mail + senha
2. Tenta autenticar como **usuário do sistema** (`users` table)
3. Se falhar, tenta autenticar como **cliente do portal** (`customer_portal_access` table)
4. Redireciona automaticamente para o ambiente correto
5. Casos email e senha estejam cadastrados nos dois a preferencia é o sistema.

```
LOGIN UNIFICADO (?page=login)
        │
        ▼
  Email + Senha
        │
        ├─ Encontrou em `users`? → Login admin → $_SESSION['user_id'] → redirect ?page=home
        │
        └─ Encontrou em `customer_portal_access`? → Login portal → $_SESSION['portal_customer_id'] → redirect ?page=portal&action=dashboard
        │
        └─ Nenhum? → "E-mail ou senha inválidos"
```

### 4.2 Vantagens

- ✅ Cliente não precisa saber que existe URL diferente
- ✅ Uma URL só para compartilhar (URL do tenant)
- ✅ Menos complexidade para o cliente final
- ✅ Magic link e "esqueci senha" integrados
- ✅ Mantém sessões isoladas internamente

### 4.3 Implementação Sugerida

**Alteração no `UserController::login()` (método POST):**

```php
// Após falhar no login de usuário:
if (!$this->userModel->login($email, $password)) {
    // Tentar login como cliente do portal
    $portalAccess = new PortalAccess($this->db);
    $access = $portalAccess->findByEmail($email);
    
    if ($access && $access['is_active'] && !$portalAccess->isLocked($access)
        && !empty($access['password_hash'])
        && $portalAccess->verifyPassword($password, $access['password_hash'])) {
        
        // Login como cliente — redirecionar para o portal
        $portalAccess->registerSuccessfulLogin($access['id'], $ip);
        $customer = (new Customer($this->db))->readOne($access['customer_id']);
        
        session_regenerate_id(true);
        PortalAuthMiddleware::login(
            $access['customer_id'], $access['id'],
            $customer['name'] ?? 'Cliente', $access['email'],
            $access['lang'] ?? 'pt-br'
        );
        
        header('Location: ?page=portal&action=dashboard');
        exit;
    }
    
    // Nenhum dos dois → erro genérico
    $error = 'E-mail ou senha inválidos.';
}
```

**A tela de login do portal (`?page=portal&action=login`) continuaria existindo** como alternativa para quando o portal tiver uma URL/subdomínio dedicado no futuro, mas o fluxo principal seria pelo login unificado.

---

## 🎨 5. Proposta Visual: Minimalismo Akti

### 5.1 Paleta de Cores Recomendada

O portal deve usar a **mesma identidade visual do Akti**, adaptada para tons claros:

```css
:root {
    /* ── Base Akti (alinhada com theme.css) ── */
    --portal-primary: #3b82f6;       /* Blue 500 — mesmo accent-color do admin */
    --portal-primary-dark: #2563eb;  /* Blue 600 */
    --portal-primary-light: #60a5fa; /* Blue 400 */
    
    /* ── Gradient sutil, não agressivo ── */
    --portal-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    
    /* ── Superfícies claras ── */
    --portal-surface: #f8fafc;       /* Slate 50 — fundo geral */
    --portal-bg: #ffffff;            /* Branco — cards */
    --portal-text: #1e293b;          /* Slate 800 — mesmo texto do admin */
    --portal-muted: #94a3b8;         /* Slate 400 */
    --portal-border: #e2e8f0;        /* Slate 200 */
    
    /* ── Accent Clean ── */
    --portal-success: #22c55e;
    --portal-warning: #f59e0b;
    --portal-danger: #ef4444;
}
```

### 5.2 Princípios de Design

| Princípio | Aplicação |
|-----------|-----------|
| **Minimalismo funcional** | Remover gradients agressivos. Usar cores sólidas + sombras sutis |
| **White space generoso** | Padding 20px+ em cards. Gap 16px+ entre seções |
| **Tipografia limpa** | Inter 400/500/600 apenas. Tamanhos: 0.8rem (caption), 0.9rem (body), 1.1rem (heading) |
| **Ícones monocromáticos** | Font Awesome na cor `--portal-muted`. Destaque apenas em badges |
| **Sem dark mode automático** | Remover `prefers-color-scheme`. Adicionar toggle manual se necessário |
| **Cores de ação únicas** | Azul = ação primária. Verde = sucesso. Vermelho = perigo. Sem roxo/violeta |

### 5.3 Componentes a Melhorar

| Componente | Atual | Proposto |
|------------|-------|----------|
| **Login card** | Fundo gradient roxo agressivo | Fundo `#f8fafc` com card branco. Logo no topo. Simples |
| **Bottom nav** | Botão central com sombra colorida | Barra flat, sem sombra forte. Ícones outline. Ativo = azul sólido |
| **Stat cards** | Números coloridos grandes | Cards brancos uniformes. Badge pequeno com cor. Número em `--portal-text` |
| **Top bar** | Brand com gradient text | Logo simples. Nome clean. Fundo branco flat |
| **Botão primário** | Gradient + sombra 3D | Cor sólida `#3b82f6`. Border-radius: 8px. Sem sombra exagerada |

---

## 💡 6. Sugestões de Melhoria

### 6.1 Funcionalidade

| # | Melhoria | Prioridade | Complexidade |
|---|----------|-----------|-------------|
| 1 | **Login unificado** — Detectar automaticamente se é cliente ou admin (seção 4) | 🔴 Alta | Média |
| 2 | **`requestMagicLink`** — Implementar action que gera e envia o link por e-mail | 🔴 Alta | Baixa |
| 3 | **`forgotPassword` / `resetPassword`** — Recuperação de senha funcional | 🔴 Alta | Média |
| 4 | **`session_regenerate_id(true)`** no login do portal | 🔴 Alta | Baixa |
| 5 | **Usar `customer_portal_sessions`** para permitir revogar sessões ativas | 🟡 Média | Média |
| 6 | **Manifest.json separado** — `/portal-manifest.json` com scope limitado | 🟡 Média | Baixa |
| 7 | **Service Worker com scope** — `navigator.serviceWorker.register('portal-sw.js', { scope: '/?page=portal' })` | 🟡 Média | Baixa |
| 8 | **Navegação desktop** — Sidebar ou top nav com links quando tela ≥ 768px | 🟡 Média | Média |
| 9 | **Notificações push** via Service Worker para parcelas vencendo / status do pedido | 🟢 Baixa | Alta |
| 10 | **Foto/avatar do cliente** no topbar e perfil | 🟢 Baixa | Baixa |

### 6.2 Segurança

| # | Melhoria | Prioridade |
|---|----------|-----------|
| 1 | Regenerar session ID no login do portal (`session_regenerate_id(true)`) | 🔴 Alta |
| 2 | Rate limiting por IP global (além de por conta) | 🟡 Média |
| 3 | Log de ações sensíveis (aprovação, rejeição, alteração de senha) na tabela `activity_log` | 🟡 Média |
| 4 | Exigir senha atual antes de permitir alteração de senha no perfil | 🟡 Média |
| 5 | Validação de força de senha (min 8 chars, letras+números) | 🟢 Baixa |
| 6 | 2FA opcional via e-mail (enviar código de 6 dígitos no login) | 🟢 Baixa |

### 6.3 UX / Mobile

| # | Melhoria | Impacto |
|---|----------|---------|
| 1 | **Pull-to-refresh** no dashboard (JS simples) | Alto |
| 2 | **Skeleton loading** nos cards enquanto carregam dados | Alto |
| 3 | **Haptic feedback** em botões (via `navigator.vibrate`) | Médio |
| 4 | **Swipe para ação** em cards de pedidos (aprovar/ver) | Médio |
| 5 | **Indicador de "sem internet"** usando SW offline detection | Médio |
| 6 | **Splash screen customizado** para PWA installed | Baixo |
| 7 | **Máscara de telefone e CPF/CNPJ** nos formulários | Alto |

---

## 📊 7. Mapa de Dependências

```
PortalController
  ├── PortalAccess (model)
  │   ├── customer_portal_access (table)
  │   ├── customer_portal_config (table)
  │   ├── orders (table) — stats/recent [usa total_amount, customer_approval_status*]
  │   └── order_installments (table) — stats
  ├── PortalMessage (model)
  │   └── customer_portal_messages (table)
  ├── Customer (model)
  │   └── customers (table) — dados do cliente
  ├── CompanySettings (model)
  │   └── company_settings (table) — logo, nome
  ├── PortalAuthMiddleware
  │   └── $_SESSION['portal_*'] — sessão isolada
  ├── PortalLang (service)
  │   └── app/lang/pt-br/portal.php
  └── portal_helper.php (utils)
      └── __p(), portal_money(), portal_date(), portal_stage_class()

* = coluna opcional, verificada via hasApprovalColumn()
```

---

## 📝 8. Checklist Para Próximas Fases

### Fase 1.1 — Correções Imediatas

- [ ] Implementar `requestMagicLink` (action + método + envio de e-mail)
- [ ] Implementar `forgotPassword` / `resetPassword`
- [ ] Adicionar `session_regenerate_id(true)` no `PortalController::login()` e `loginMagic()`
- [ ] Implementar login unificado no `UserController::login()`
- [ ] Exigir senha atual no `updateProfile`
- [ ] Criar `/portal-manifest.json` separado
- [ ] Limitar scope do service worker
- [ ] Alinhar paleta de cores com Akti (trocar roxo por azul)
- [ ] Implementar navegação desktop (sidebar ou top nav ≥768px)

### Fase 2 — Pedidos

- [ ] `orders()` — listagem com filtros (abertos, aprovação, concluídos)
- [ ] `orderDetail()` — timeline visual + itens + parcelas
- [ ] `approveOrder()` / `rejectOrder()` — com registro de IP e notas
- [ ] Views: `orders/index.php`, `orders/detail.php`

### Fase 3 — Novo Pedido / Catálogo

- [ ] `newOrder()` — catálogo de produtos com busca
- [ ] Carrinho (add/remove/update) via sessão `portal_cart`
- [ ] `submitOrder()` — cria pedido como `status=orcamento`
- [ ] Views: `orders/new.php`, `orders/cart.php`

### Fase 4 — Financeiro + Rastreamento

- [ ] `installments()` — parcelas abertas e pagas
- [ ] `installmentDetail()` — boleto/pix link
- [ ] `tracking()` — código de rastreio + timeline
- [ ] Views: `financial/index.php`, `tracking/index.php`

### Fase 5 — Mensagens + Documentos

- [ ] `messages()` / `sendMessage()` — chat por pedido
- [ ] `documents()` / `downloadDocument()` — NF-e, boletos, comprovantes
- [ ] Views: `messages/index.php`, `documents/index.php`

---

## 📎 9. Referência Rápida de Actions

| Action | Método HTTP | Auth? | Status |
|--------|------------|-------|--------|
| `index` | GET | Não | ✅ Funcional |
| `login` | GET/POST | Não | ✅ Funcional |
| `loginMagic` | GET | Não | ✅ Funcional (precisa de token) |
| `requestMagicLink` | POST | Não | ❌ Não existe |
| `logout` | GET | Sim | ✅ Funcional |
| `register` | GET/POST | Não | ✅ Funcional (se habilitado) |
| `forgotPassword` | GET/POST | Não | ❌ Não implementado |
| `resetPassword` | GET/POST | Não | ❌ Não implementado |
| `dashboard` | GET | Sim | ✅ Funcional |
| `profile` | GET | Sim | ✅ Funcional |
| `updateProfile` | POST | Sim | ✅ Funcional (falta validar senha atual) |
| `orders` | GET | Sim | ❌ Não implementado |
| `orderDetail` | GET | Sim | ❌ Não implementado |
| `approveOrder` | POST | Sim | ❌ Não implementado |
| `rejectOrder` | POST | Sim | ❌ Não implementado |
| `newOrder` | GET | Sim | ❌ Não implementado |
| `getProducts` | GET/AJAX | Sim | ❌ Não implementado |
| `addToCart` | POST/AJAX | Sim | ❌ Não implementado |
| `removeFromCart` | POST/AJAX | Sim | ❌ Não implementado |
| `updateCartItem` | POST/AJAX | Sim | ❌ Não implementado |
| `getCart` | GET/AJAX | Sim | ❌ Não implementado |
| `submitOrder` | POST | Sim | ❌ Não implementado |
| `installments` | GET | Sim | ❌ Não implementado |
| `installmentDetail` | GET | Sim | ❌ Não implementado |
| `tracking` | GET | Sim | ❌ Não implementado |
| `messages` | GET | Sim | ❌ Não implementado |
| `sendMessage` | POST | Sim | ❌ Não implementado |
| `documents` | GET | Sim | ❌ Não implementado |
| `downloadDocument` | GET | Sim | ❌ Não implementado |

---

*Relatório gerado automaticamente. Para continuar a implementação, siga o checklist da Seção 8.*
