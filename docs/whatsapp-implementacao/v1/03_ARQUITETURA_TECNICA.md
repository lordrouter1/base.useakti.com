# 03 — Arquitetura Técnica — Meta Cloud WhatsApp API

> **Data da Auditoria:** 04/04/2026  
> **Foco:** Design técnico para integração com o sistema Akti existente  

---

## 1. Visão Geral da Arquitetura

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           AKTI SYSTEM                                    │
│                                                                          │
│  ┌──────────────┐    ┌─────────────────┐    ┌────────────────────────┐  │
│  │  Controllers  │───►│  WhatsAppService │───►│  Meta Graph API        │  │
│  │  (triggers)   │    │  (PHP service)   │    │  graph.facebook.com    │  │
│  │               │    │                  │    │  POST /{phone-id}/     │  │
│  │ - Pipeline    │    │ - sendTemplate() │    │       messages         │  │
│  │ - Order       │    │ - sendMessage()  │    │                        │  │
│  │ - Financial   │    │ - getTemplates() │    │  Outbound ──────────►  │  │
│  │ - Portal      │    │ - logMessage()   │    │                        │  │
│  └──────────────┘    └─────────────────┘    └────────────────────────┘  │
│         │                     │                         │                │
│         │              ┌──────┴──────┐                  │                │
│         │              │ WhatsApp    │                  │                │
│  ┌──────▼──────┐       │ Model       │           ┌─────▼──────────┐    │
│  │ EventDispatcher│    │ (logs DB)   │           │ Node.js API     │    │
│  │ (listeners) │       │             │           │ /webhooks/      │    │
│  │             │       │ - messages  │           │   whatsapp      │    │
│  │ 15+ eventos │       │ - templates │           │                 │    │
│  │ existentes  │       │ - configs   │           │ Inbound ◄──────│    │
│  └─────────────┘       └─────────────┘           └────────────────┘    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │       META CLOUD API           │
                    │                                │
                    │  ┌──────────────────────────┐  │
                    │  │ WhatsApp Business Account │  │
                    │  │ (WABA)                    │  │
                    │  │                           │  │
                    │  │ Phone Number: +55-XX-XXXX │  │
                    │  │ Templates: aprovados       │  │
                    │  │ Webhooks: delivery/read    │  │
                    │  └──────────────────────────┘  │
                    │                                │
                    └────────────────────────────────┘
                                    │
                                    ▼
                            ┌──────────────┐
                            │   CLIENTE    │
                            │  WhatsApp    │
                            │  (celular)   │
                            └──────────────┘
```

---

## 2. Componentes a Criar

### 2.1 Estrutura de Arquivos

```
app/
├── config/
│   └── whatsapp.php              # WhatsAppConfig — carrega config do tenant
├── controllers/
│   └── WhatsAppController.php    # CRUD de configurações + visualização de logs
├── models/
│   ├── WhatsAppConfig.php        # Configuração por tenant (tokens, phone ID)
│   ├── WhatsAppMessage.php       # Log de mensagens enviadas/recebidas
│   └── WhatsAppTemplate.php      # Templates sincronizados da Meta
├── services/
│   └── WhatsAppService.php       # Core: envio de mensagens via API
└── views/
    └── whatsapp/
        ├── index.php             # Dashboard de mensagens
        ├── config.php            # Configurações da integração
        ├── templates.php         # Gerenciamento de templates
        └── logs.php              # Histórico de mensagens

api/
└── src/
    ├── controllers/
    │   └── WhatsAppWebhookController.js   # Recebe webhooks da Meta
    ├── routes/
    │   └── whatsappRoutes.js              # Rotas do webhook
    └── services/
        └── WhatsAppWebhookService.js      # Processa webhooks

sql/
└── update_YYYYMMDDHHMM_XX_whatsapp_tables.sql   # Tabelas do módulo
```

### 2.2 Tabelas de Banco de Dados

#### `whatsapp_configs` — Configuração por tenant

```sql
CREATE TABLE IF NOT EXISTS whatsapp_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    waba_id VARCHAR(50) NOT NULL COMMENT 'WhatsApp Business Account ID',
    phone_number_id VARCHAR(50) NOT NULL COMMENT 'Phone Number ID na Meta',
    display_phone VARCHAR(20) COMMENT 'Número exibido (+55 11 9xxxx)',
    access_token TEXT NOT NULL COMMENT 'Token de acesso permanente (criptografado)',
    verify_token VARCHAR(100) NOT NULL COMMENT 'Token de verificação do webhook',
    webhook_secret VARCHAR(100) COMMENT 'App Secret para validação de assinatura',
    business_name VARCHAR(100) COMMENT 'Nome da empresa no WhatsApp',
    is_active TINYINT(1) DEFAULT 1,
    send_window_start TIME DEFAULT '08:00:00' COMMENT 'Início do horário de envio',
    send_window_end TIME DEFAULT '20:00:00' COMMENT 'Fim do horário de envio',
    daily_limit INT DEFAULT 1000 COMMENT 'Limite diário de mensagens',
    messages_sent_today INT DEFAULT 0,
    last_reset_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    UNIQUE KEY uq_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `whatsapp_messages` — Log de mensagens

```sql
CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    wa_message_id VARCHAR(100) COMMENT 'ID da mensagem na Meta (wamid.xxx)',
    direction ENUM('outbound', 'inbound') NOT NULL DEFAULT 'outbound',
    customer_id INT COMMENT 'FK para customers',
    customer_phone VARCHAR(20) NOT NULL,
    template_name VARCHAR(100) COMMENT 'Nome do template usado',
    template_category ENUM('UTILITY', 'AUTHENTICATION', 'MARKETING') COMMENT 'Categoria Meta',
    message_type ENUM('template', 'text', 'image', 'document', 'interactive') DEFAULT 'template',
    content TEXT COMMENT 'Conteúdo ou variáveis do template (JSON)',
    status ENUM('queued', 'sent', 'delivered', 'read', 'failed') DEFAULT 'queued',
    error_code VARCHAR(20) COMMENT 'Código de erro da Meta (se falhou)',
    error_message TEXT COMMENT 'Mensagem de erro',
    trigger_event VARCHAR(100) COMMENT 'Evento que disparou (ex: order.created)',
    trigger_entity_id INT COMMENT 'ID da entidade (ex: order_id)',
    sent_at DATETIME COMMENT 'Quando foi enviada à API',
    delivered_at DATETIME COMMENT 'Webhook: entregue ao dispositivo',
    read_at DATETIME COMMENT 'Webhook: lida pelo destinatário',
    cost_category VARCHAR(20) COMMENT 'Categoria de cobrança da Meta',
    retry_count TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_customer (customer_id),
    INDEX idx_wa_message (wa_message_id),
    INDEX idx_trigger (trigger_event, trigger_entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `whatsapp_templates` — Templates sincronizados

```sql
CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    meta_template_id VARCHAR(50) COMMENT 'ID do template na Meta',
    name VARCHAR(100) NOT NULL COMMENT 'Nome do template (slug)',
    language VARCHAR(10) DEFAULT 'pt_BR',
    category ENUM('UTILITY', 'AUTHENTICATION', 'MARKETING') NOT NULL,
    status ENUM('APPROVED', 'PENDING', 'REJECTED') DEFAULT 'PENDING',
    components JSON COMMENT 'Estrutura do template (header, body, footer, buttons)',
    trigger_event VARCHAR(100) COMMENT 'Evento do sistema vinculado',
    is_active TINYINT(1) DEFAULT 1,
    variables JSON COMMENT 'Mapeamento de variáveis do sistema para template',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    UNIQUE KEY uq_tenant_template (tenant_id, name, language)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `whatsapp_opt_outs` — Opt-out de clientes

```sql
CREATE TABLE IF NOT EXISTS whatsapp_opt_outs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    customer_id INT,
    phone VARCHAR(20) NOT NULL,
    opted_out_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(255) COMMENT 'Motivo (STOP, manual, etc.)',
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    UNIQUE KEY uq_tenant_phone (tenant_id, phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Service Layer — WhatsAppService

### 3.1 Responsabilidades

```php
<?php
namespace Akti\Services;

/**
 * WhatsAppService — envia mensagens via Meta Cloud API.
 *
 * Responsabilidades:
 * - Enviar template messages via Graph API
 * - Enviar mensagens de texto (dentro da janela de 24h)
 * - Registrar logs de envio
 * - Verificar opt-out antes de enviar
 * - Respeitar limites diários e horário comercial
 * - Sincronizar templates aprovados da Meta
 */
class WhatsAppService
{
    private \PDO $db;
    private WhatsAppConfig $config;    // Model
    private WhatsAppMessage $message;  // Model
    private WhatsAppTemplate $template; // Model

    public function __construct(
        \PDO $db,
        WhatsAppConfig $configModel,
        WhatsAppMessage $messageModel,
        WhatsAppTemplate $templateModel
    ) { /* ... */ }

    /**
     * Envia uma template message para um cliente.
     */
    public function sendTemplate(
        int $customerId,
        string $phone,
        string $templateName,
        array $variables = [],
        ?string $triggerEvent = null,
        ?int $triggerEntityId = null
    ): array { /* ... */ }

    /**
     * Verifica se o número não está em opt-out.
     */
    public function canSendTo(string $phone): bool { /* ... */ }

    /**
     * Verifica se está dentro do horário de envio configurado.
     */
    public function isWithinSendWindow(): bool { /* ... */ }

    /**
     * Sincroniza templates aprovados da Meta.
     */
    public function syncTemplates(): array { /* ... */ }

    /**
     * Atualiza status da mensagem (chamado pelo webhook).
     */
    public function updateMessageStatus(
        string $waMessageId,
        string $status,
        ?string $timestamp = null
    ): void { /* ... */ }
}
```

### 3.2 Fluxo de Envio

```
sendTemplate()
    │
    ├── 1. Verificar is_active da config do tenant
    │
    ├── 2. Verificar canSendTo($phone) — checar opt-out
    │
    ├── 3. Verificar isWithinSendWindow() — horário comercial
    │
    ├── 4. Verificar daily_limit — não exceder cota
    │
    ├── 5. Buscar template em whatsapp_templates (ativo + aprovado)
    │
    ├── 6. Montar payload JSON para Graph API:
    │      POST https://graph.facebook.com/v21.0/{phone_number_id}/messages
    │      {
    │        "messaging_product": "whatsapp",
    │        "to": "5511999999999",
    │        "type": "template",
    │        "template": {
    │          "name": "order_status_update",
    │          "language": { "code": "pt_BR" },
    │          "components": [
    │            {
    │              "type": "body",
    │              "parameters": [
    │                { "type": "text", "text": "Maria" },
    │                { "type": "text", "text": "#0042" },
    │                { "type": "text", "text": "Em Produção" }
    │              ]
    │            }
    │          ]
    │        }
    │      }
    │
    ├── 7. Enviar via cURL (sem dependência externa)
    │
    ├── 8. Registrar em whatsapp_messages (status: sent ou failed)
    │
    ├── 9. Incrementar messages_sent_today
    │
    └── 10. Retornar resultado com wa_message_id
```

---

## 4. Integração com Eventos Existentes

### 4.1 Novos Listeners em `app/bootstrap/events.php`

```php
// ══════════════════════════════════════════════════════════════
// WhatsApp — Listeners para notificações automáticas
// ══════════════════════════════════════════════════════════════

// Pedido criado → WhatsApp
EventDispatcher::listen('model.order.created', function (Event $event) {
    $whatsApp = new WhatsAppService($db, ...);
    $order = $event->getData();
    $whatsApp->sendTemplate(
        $order['customer_id'],
        $order['customer_phone'],
        'order_created',
        ['customer_name' => $order['customer_name'], 'order_number' => $order['number']],
        'model.order.created',
        $order['id']
    );
});

// Pipeline move → WhatsApp
EventDispatcher::listen('model.pipeline.moved', function (Event $event) { ... });

// Pagamento confirmado → WhatsApp
EventDispatcher::listen('model.installment.paid', function (Event $event) { ... });

// NF-e autorizada → WhatsApp (complementa o listener de email existente)
EventDispatcher::listen('model.nfe_document.authorized', function (Event $event) { ... });

// Catálogo gerado → WhatsApp (complementa evento existente)
EventDispatcher::listen('model.catalog_link.created', function (Event $event) { ... });

// Portal: magic link → WhatsApp (complementa email TODO)
EventDispatcher::listen('portal.magic_link.requested', function (Event $event) { ... });
```

### 4.2 Eventos a Criar

| Evento Novo | Trigger | Onde Disparar |
|-------------|---------|---------------|
| `model.order.created` | OrderController::store() | `app/controllers/OrderController.php` L146 |
| `model.pipeline.moved` | PipelineService::moveOrder() | `app/services/PipelineService.php` |
| `model.installment.paid` | InstallmentService::payInstallment() | `app/services/InstallmentService.php` L93 |
| `model.tracking.added` | PipelineController (tracking save) | `app/controllers/PipelineController.php` |
| `financial.installment.due_soon` | Cron job (D-3) | Novo: `scripts/whatsapp_reminders.php` |
| `financial.installment.overdue` | Cron job (D+1) | Novo: `scripts/whatsapp_reminders.php` |

---

## 5. Webhook (Recebimento — Node.js)

### 5.1 Rota no Express

```javascript
// api/src/routes/whatsappRoutes.js
router.get('/webhooks/whatsapp', WhatsAppWebhookController.verify);   // Verificação
router.post('/webhooks/whatsapp', WhatsAppWebhookController.receive); // Mensagens
```

### 5.2 Tipos de Webhook Recebidos

| Tipo | Campo | Ação no Sistema |
|------|-------|-----------------|
| `message.status.sent` | `statuses[].status = "sent"` | UPDATE whatsapp_messages SET status = 'sent' |
| `message.status.delivered` | `statuses[].status = "delivered"` | UPDATE SET status = 'delivered', delivered_at |
| `message.status.read` | `statuses[].status = "read"` | UPDATE SET status = 'read', read_at |
| `message.status.failed` | `statuses[].status = "failed"` | UPDATE SET status = 'failed', error_code, error_message |
| `messages` (inbound) | `messages[].text.body` | INSERT mensagem recebida + notificação interna |

### 5.3 Segurança do Webhook

| Requisito | Implementação |
|-----------|---------------|
| Verificação de assinatura | Validar `X-Hub-Signature-256` com App Secret |
| Verify token | GET endpoint retorna `hub.challenge` se token bate |
| Rate limiting | helmet + rate-limit já existentes no Express |
| HTTPS obrigatório | Meta exige HTTPS — já configurado |
| Idempotência | Ignorar webhook com `wa_message_id` já processado |

---

## 6. ModuleBootloader — Integração

### 6.1 Registro do Módulo

```php
// app/core/ModuleBootloader.php — adicionar ao mapa de módulos
'whatsapp' => [
    'label'   => 'WhatsApp Cloud API',
    'default' => false,  // Desativado por padrão — ativar por tenant
    'pages'   => ['whatsapp'],
    'menu'    => ['ferramentas.whatsapp'],
],
```

### 6.2 Menu

```php
// app/config/menu.php — dentro do grupo "Ferramentas"
'whatsapp' => [
    'label'      => 'WhatsApp',
    'icon'       => 'fab fa-whatsapp',
    'page'       => 'whatsapp',
    'permission' => true,
    'module'     => 'whatsapp',
],
```

---

## 7. Segurança

| Aspecto | Medida |
|---------|--------|
| **Token armazenamento** | `access_token` criptografado em banco (AES-256 via app) |
| **Token em tráfego** | HTTPS obrigatório (Meta exige) |
| **Webhook autenticação** | Validação HMAC-SHA256 com App Secret |
| **Rate limiting** | Limite diário configurável por tenant |
| **Opt-out** | Tabela `whatsapp_opt_outs` verificada antes de cada envio |
| **LGPD** | Consentimento via campo `whatsapp_opt_in` no cadastro |
| **Injeção de conteúdo** | Variáveis sanitizadas antes de enviar à API |
| **Permissão** | Acesso ao módulo controlado por grupo de usuário |
| **Multi-tenant** | Cada tenant = WABA + token isolados |
| **Logs** | Toda mensagem enviada/recebida registrada com auditoria |
