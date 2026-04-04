# 04 — Roadmap de Implementação — Meta Cloud WhatsApp API

> **Data da Auditoria:** 04/04/2026  
> **Estratégia:** Implementação progressiva em 4 fases  
> **Pré-requisito:** Conta WhatsApp Business na Meta verificada e aprovada  

---

## Fase 1 — Foundation (Infraestrutura Base)

### WA-001: Criar tabelas no banco de dados

- **Prioridade:** CRÍTICO
- **Skill:** sql-migration
- **Tabelas:** `whatsapp_configs`, `whatsapp_messages`, `whatsapp_templates`, `whatsapp_opt_outs`
- **Inclui:** Índices, FKs, `tenant_id`, `created_at`, `updated_at`
- **Status:** ⬜ Pendente

---

### WA-002: Criar Models

- **Prioridade:** CRÍTICO
- **Arquivos:**
  - `app/models/WhatsAppConfig.php` — CRUD de configuração do tenant
  - `app/models/WhatsAppMessage.php` — CRUD + queries de log de mensagens
  - `app/models/WhatsAppTemplate.php` — CRUD + sincronização de templates
- **Padrão:** `namespace Akti\Models;` + constructor com `PDO $db`
- **Status:** ⬜ Pendente

---

### WA-003: Criar WhatsAppService

- **Prioridade:** CRÍTICO
- **Arquivo:** `app/services/WhatsAppService.php`
- **Métodos core:**
  - `sendTemplate()` — envia template message via Graph API
  - `sendText()` — envia texto livre (janela 24h)
  - `canSendTo()` — verifica opt-out
  - `isWithinSendWindow()` — verifica horário comercial
  - `syncTemplates()` — sincroniza templates da Meta
  - `updateMessageStatus()` — atualiza status via webhook
  - `formatPhone()` — normaliza telefone para formato internacional
- **Segurança:** Token criptografado, sanitização de variáveis, verificação de opt-out
- **Status:** ⬜ Pendente

---

### WA-004: Criar WhatsAppController

- **Prioridade:** ALTO
- **Arquivo:** `app/controllers/WhatsAppController.php`
- **Actions:**
  - `index()` — dashboard de mensagens (últimas 50, status, métricas)
  - `config()` — formulário de configuração (WABA ID, token, limites)
  - `saveConfig()` — salvar configuração
  - `templates()` — listar templates sincronizados
  - `syncTemplates()` — AJAX: sincronizar da Meta
  - `logs()` — histórico paginado com filtros
  - `sendTest()` — enviar mensagem de teste
- **Status:** ⬜ Pendente

---

### WA-005: Criar Rotas e Menu

- **Prioridade:** ALTO
- **Arquivos:**
  - `app/config/routes.php` — adicionar rota `whatsapp`
  - `app/config/menu.php` — adicionar item sob "Ferramentas"
- **ModuleBootloader:** Registrar módulo `whatsapp` (default: desativado)
- **Status:** ⬜ Pendente

---

### WA-006: Criar Views

- **Prioridade:** ALTO
- **Arquivos:**
  - `app/views/whatsapp/index.php` — dashboard com métricas e últimas mensagens
  - `app/views/whatsapp/config.php` — configuração da integração
  - `app/views/whatsapp/templates.php` — gerenciamento de templates
  - `app/views/whatsapp/logs.php` — histórico detalhado
- **Padrão:** Bootstrap 5, responsivo, dark mode, `e()` para escape
- **Status:** ⬜ Pendente

---

### WA-007: Webhook Endpoint (Node.js)

- **Prioridade:** ALTO
- **Arquivos:**
  - `api/src/routes/whatsappRoutes.js`
  - `api/src/controllers/WhatsAppWebhookController.js`
  - `api/src/services/WhatsAppWebhookService.js`
- **Endpoints:**
  - `GET /webhooks/whatsapp` — verificação da Meta (hub.challenge)
  - `POST /webhooks/whatsapp` — receber status updates e mensagens inbound
- **Segurança:** Validar `X-Hub-Signature-256`, idempotência por `wa_message_id`
- **Status:** ⬜ Pendente

---

## Fase 2 — Core Notifications (Eventos Automáticos)

### WA-008: Event Listeners — Pipeline

- **Prioridade:** CRÍTICO
- **Arquivo:** `app/bootstrap/events.php`
- **Eventos:**
  - `model.pipeline.moved` → template `order_status_update`
  - `model.tracking.added` → template `tracking_code`
- **Pré-requisito:** Criar EventDispatcher events no PipelineService/Controller
- **Status:** ⬜ Pendente

---

### WA-009: Event Listeners — Pedidos

- **Prioridade:** ALTO
- **Eventos:**
  - `model.order.created` → template `order_created`
  - `model.catalog_link.created` (existente) → template `catalog_share`
- **Pré-requisito:** Disparar evento em OrderController::store()
- **Status:** ⬜ Pendente

---

### WA-010: Event Listeners — Financeiro

- **Prioridade:** ALTO
- **Eventos:**
  - `model.installment.paid` → template `payment_confirmed`
  - `financial.payment_link.created` → template `payment_link`
- **Pré-requisito:** Disparar eventos no InstallmentService
- **Status:** ⬜ Pendente

---

### WA-011: Event Listeners — NF-e

- **Prioridade:** MÉDIO
- **Eventos:**
  - `model.nfe_document.authorized` (existente) → template `nfe_issued`
- **Ação:** Adicionar listener WhatsApp ao evento já existente em events.php
- **Status:** ⬜ Pendente

---

### WA-012: Event Listeners — Portal

- **Prioridade:** MÉDIO
- **Eventos:**
  - `portal.magic_link.requested` (existente) → template `portal_magic_link`
  - `portal.password_reset.requested` (existente) → template `portal_password_reset`
  - `portal.message.sent` (existente) → template `portal_new_message`
- **Ação:** Adicionar listeners aos eventos que já existem mas têm entrega TODO
- **Status:** ⬜ Pendente

---

## Fase 3 — Automação Avançada

### WA-013: Cron de Lembretes Financeiros

- **Prioridade:** ALTO
- **Arquivo novo:** `scripts/whatsapp_reminders.php`
- **Lógica:**
  - Query parcelas com `due_date = NOW() + 3 days` → template `payment_reminder`
  - Query parcelas com `due_date < NOW() AND status != 'paid'` → template `payment_overdue`
- **Cron:** Executar diariamente às 09:00 (dentro do horário comercial)
- **Segurança:** Verificar opt-out + limite diário + evitar duplicidade (1 msg/dia/parcela)
- **Status:** ⬜ Pendente

---

### WA-014: Substituir Links wa.me/ por Envio API

- **Prioridade:** MÉDIO
- **Escopo:** Converter os 8 pontos manuais existentes:
  1. Pipeline — botão WhatsApp do cliente → botão "Enviar mensagem" via API
  2. Pipeline — compartilhar catálogo → envio automático no evento
  3. Pipeline — enviar rastreio → envio automático no evento
  4. Pipeline — reenviar link de pagamento → botão "Reenviar via WhatsApp"  
- **Manter fallback:** Se módulo desativado, manter links wa.me/ originais
- **Status:** ⬜ Pendente

---

### WA-015: Mensagens Inbound (Recebimento)

- **Prioridade:** BAIXO
- **Descrição:** Quando cliente responde uma mensagem, o webhook captura e:
  - Registra em `whatsapp_messages` (direction: inbound)
  - Cria notificação interna para o operador responsável
  - Exibe no dashboard de WhatsApp (mini-chat)
- **Nota:** Dentro da janela de 24h, permite resposta via texto livre (sem template)
- **Status:** ⬜ Pendente

---

### WA-016: Opt-in no Cadastro de Clientes

- **Prioridade:** MÉDIO
- **Escopo:**
  - Adicionar campo `whatsapp_opt_in` TINYINT(1) DEFAULT 1 na tabela `customers`
  - Exibir checkbox "Aceita notificações via WhatsApp" nos formulários create/edit
  - WhatsAppService verifica este campo antes de enviar
- **Status:** ⬜ Pendente

---

## Fase 4 — Otimização e Marketing

### WA-017: Dashboard de Métricas

- **Prioridade:** BAIXO
- **Descrição:** Painel visual em `whatsapp/index.php` com:
  - Total enviadas (dia/semana/mês)
  - Taxa de entrega, leitura, falha
  - Top templates mais usados
  - Gráfico de envio por dia (Chart.js — já disponível)
  - Custo estimado por categoria
- **Status:** ⬜ Pendente

---

### WA-018: Integração com Email Marketing

- **Prioridade:** BAIXO
- **Descrição:** Ao criar uma campanha de email marketing, opção de:
  - Enviar a mesma campanha via WhatsApp (template marketing)
  - Segmentar: "apenas quem não abriu o email"
- **Pré-requisito:** Template marketing aprovado pela Meta
- **Status:** ⬜ Pendente

---

### WA-019: Queue / Retry System

- **Prioridade:** MÉDIO
- **Descrição:** Para envios em massa (cron, campanhas):
  - Enfileirar mensagens em `whatsapp_messages` com status `queued`
  - Worker processa fila respeitando rate-limit da Meta (80 msg/segundo)
  - Retry automático em falhas temporárias (3 tentativas, backoff exponencial)
- **Status:** ⬜ Pendente

---

### WA-020: Testes Automatizados

- **Prioridade:** ALTO
- **Arquivos:**
  - `tests/Unit/WhatsAppServiceTest.php` — mock de cURL, verificação de opt-out, formatação
  - `tests/Unit/WhatsAppMessageTest.php` — CRUD de mensagens
  - `tests/Integration/WhatsAppWebhookTest.php` — validação de assinatura, processamento
- **Cenários:**
  - `test_send_template_returns_message_id`
  - `test_send_blocked_by_opt_out`
  - `test_send_blocked_outside_window`
  - `test_send_blocked_by_daily_limit`
  - `test_webhook_updates_status`
  - `test_webhook_rejects_invalid_signature`
  - `test_format_phone_brazilian`
- **Status:** ⬜ Pendente

---

## Resumo do Roadmap

| Fase | Itens | Foco | Dependências |
|------|-------|------|-------------|
| **1 — Foundation** | WA-001 a WA-007 | Infraestrutura (DB, Service, Controller, Views, Webhook) | Conta WABA verificada |
| **2 — Core Notifications** | WA-008 a WA-012 | Eventos automáticos (Pipeline, Pedidos, Financeiro, NF-e, Portal) | Fase 1 |
| **3 — Automação Avançada** | WA-013 a WA-016 | Cron, substituição de wa.me/, inbound, opt-in | Fase 2 |
| **4 — Otimização** | WA-017 a WA-020 | Dashboard, Email Marketing, Queue, Testes | Fase 3 |

---

## Pré-requisitos Externos

| # | Item | Responsável | Detalhes |
|---|------|-------------|---------|
| 1 | Criar conta Meta Business Manager | Cliente/Tenant | business.facebook.com |
| 2 | Criar WhatsApp Business App | Cliente/Tenant | developers.facebook.com |
| 3 | Verificar conta WABA | Meta | Processo de verificação empresarial |
| 4 | Registrar número de telefone | Cliente/Tenant | Número dedicado para API |
| 5 | Criar templates e submeter para aprovação | Cliente/Tenant + Dev | Meta aprova em 24-48h |
| 6 | Gerar System User Token (permanente) | Dev + Cliente | Token de longa duração |
| 7 | Configurar webhook URL na Meta | Dev | URL HTTPS do servidor Akti |

---

## Métricas de Sucesso

| Métrica | Fase 1 | Fase 2 | Fase 3 | Fase 4 |
|---------|--------|--------|--------|--------|
| Templates configurados | 0 | 8+ | 12+ | 14+ |
| Eventos com notificação WA | 0 | 7 | 10 | 12+ |
| Links wa.me/ manuais restantes | 8 | 8 | 0 | 0 |
| Mensagens com tracking de status | 0% | 100% | 100% | 100% |
| Dashboard de métricas | ❌ | ❌ | ❌ | ✅ |
| Testes automatizados | 0 | 0 | 0 | 7+ |
| Cobertura de comunicação pós-venda | ~10% | ~60% | ~90% | ~100% |
