# 01 — Estado Atual do WhatsApp no Sistema Akti

> **Data da Auditoria:** 04/04/2026  
> **Resultado:** Nenhuma integração com API — apenas links manuais  

---

## 1. Inventário Completo de Referências ao WhatsApp

### 1.1 Links Manuais `wa.me/` na Interface (8 pontos)

| # | Local | Arquivo | Linha | Tipo | Função |
|---|-------|---------|-------|------|--------|
| 1 | Pipeline — Botão WhatsApp do cliente | `app/views/pipeline/detail.php` | L299 | Botão | Abre conversa direta com o celular do cliente |
| 2 | Pipeline — Compartilhar catálogo | `app/views/pipeline/detail.php` | L420 / L3492 | Botão + JS | `shareViaWhatsApp()` — monta mensagem com link do catálogo |
| 3 | Pipeline — Enviar rastreio | `app/views/pipeline/detail.php` | L2267 | Link | Abre WhatsApp com código de rastreio pré-formatado |
| 4 | Pipeline — Reenviar link de pagamento | `app/views/pipeline/detail.php` | L5416 | Botão + JS | Envia link do gateway de pagamento via WhatsApp Web |
| 5 | Cliente — Visualização | `app/views/customers/view.php` | L273 | Link | Celular do cliente como link clicável para WhatsApp |
| 6 | Cliente — Edição | `app/views/customers/edit.php` | L201 | Label/Ícone | Campo "Celular / WhatsApp" com ícone |
| 7 | Cliente — Criação | `app/views/customers/create.php` | L169 | Label/Ícone | Campo "Celular / WhatsApp" com ícone |
| 8 | Loja — Ícone social | `loja/templates/snippets/social-icons.html.twig` | L20 | Link | Ícone WhatsApp no rodapé da loja online |

### 1.2 Referências em Documentação e Labels (6 pontos)

| # | Local | Arquivo | Conteúdo |
|---|-------|---------|----------|
| 1 | Financeiro — Pagamentos | `app/views/financial/payments.php` L752 | Placeholder: "Ex: Comprovante recebido via WhatsApp" |
| 2 | Financeiro — Modal | `app/views/financial/partials/_modals.php` L57 | Placeholder em campo de observação |
| 3 | Portal — Campo telefone (pt-br) | `app/lang/pt-br/portal.php` L78 | `'register_phone' => 'Telefone / WhatsApp'` |
| 4 | Portal — Campo telefone (es) | `app/lang/es/portal.php` L77 | `'register_phone' => 'Teléfono / WhatsApp'` |
| 5 | Portal — Campo telefone (en) | `app/lang/en/portal.php` L77 | `'register_phone' => 'Phone / WhatsApp'` |
| 6 | Manual do Sistema | `docs/MANUAL_DO_SISTEMA.md` L144 | "Envie o link ao cliente por WhatsApp" |

### 1.3 O que NÃO existe

| Componente | Status |
|-----------|--------|
| WhatsAppService (PHP) | ❌ Inexistente |
| WhatsApp Model/Tabela | ❌ Inexistente |
| WhatsApp Controller | ❌ Inexistente |
| Webhook endpoint para Meta | ❌ Inexistente |
| Configuração de API (tokens, WABA ID) | ❌ Inexistente |
| Rota para WhatsApp em `routes.php` | ❌ Inexistente |
| Menu item para WhatsApp em `menu.php` | ❌ Inexistente |
| Template messages | ❌ Inexistente |
| Histórico de mensagens enviadas | ❌ Inexistente |
| Pacote/dependência WhatsApp | ❌ Inexistente |
| Testes relacionados | ❌ Inexistente |

---

## 2. Análise dos Links Manuais Existentes

### 2.1 Padrão de Uso Atual

```
OPERADOR                          WHATSAPP WEB                    CLIENTE
   │                                    │                            │
   ├── Clica botão WhatsApp ──────────► │                            │
   │   (wa.me/55{phone}?text=...)       │                            │
   │                                    ├── Abre conversa ──────────►│
   │                                    │   com texto pré-formatado  │
   │                                    │                            │
   ├── Confirma envio manualmente ──►   │                            │
   │   (precisa clicar "Enviar")        ├── Mensagem enviada ───────►│
   │                                    │                            │
   └── Volta ao sistema ◄──────────────┘                            │
       (sem registro, sem tracking)                                  │
```

### 2.2 Problemas do Modelo Manual

| Problema | Impacto | Severidade |
|----------|---------|------------|
| **Sem registro** — sistema não sabe se mensagem foi enviada | Impossível auditar comunicação | 🔴 ALTO |
| **Sem automação** — toda mensagem requer ação humana | Operador gasta 30-60s por mensagem | 🔴 ALTO |
| **Sem tracking** — não sabe se cliente leu | Zero visibilidade de engajamento | 🟡 MÉDIO |
| **Dependência do navegador** — precisa abrir WhatsApp Web | Operador perde contexto do sistema | 🟡 MÉDIO |
| **Sem templates** — mensagem fixa, não personalizável | Comunicação genérica | 🟡 MÉDIO |
| **Sem fallback** — se celular inválido, nenhum erro | Mensagem perdida silenciosamente | 🟡 MÉDIO |
| **Multi-dispositivo** — precisa estar logado no WhatsApp Web | Ponto de falha operacional | 🟡 MÉDIO |
| **Sem horário comercial** — envia a qualquer hora | Pode incomodar cliente fora de hora | 🟢 BAIXO |

---

## 3. Infraestrutura Existente Reutilizável

O sistema já possui componentes que facilitam a integração:

### 3.1 Sistema de Eventos (EventDispatcher)

**15 eventos definidos** em `app/bootstrap/events.php` — vários são candidatos para trigger de mensagens WhatsApp:

| Evento | Trigger | Candidato para WhatsApp? |
|--------|---------|-------------------------|
| `model.nfe_document.authorized` | NF-e autorizada na SEFAZ | ✅ Enviar DANFE/link ao cliente |
| `model.nfe_document.cancelled` | NF-e cancelada | ✅ Notificar cancelamento |
| `model.catalog_link.created` | Catálogo gerado | ✅ Enviar link do catálogo |
| `portal.order.approved` | Cliente aprovou orçamento | ✅ Confirmar aprovação |
| `portal.magic_link.requested` | Cliente pediu acesso | ✅ Enviar link de acesso |
| `portal.password_reset.requested` | Esqueceu senha | ✅ Enviar link de reset |
| `portal.message.sent` | Admin enviou mensagem | ✅ Notificar nova mensagem |

### 3.2 Sistema de Notificações

- Model `Notification` com SSE (Server-Sent Events)
- Helper `$sendInternalNotification()` e `$notifyAdmins()`
- Tabela `notifications` com campos: `user_id`, `type`, `title`, `message`, `data`, `read_at`

### 3.3 Email Service

- `EmailService.php` com PHPMailer funcional
- Templates com variáveis (`{{nome}}`, `{{email}}`, etc.)
- Tracking de abertura e cliques
- **Padrão de design reutilizável** para WhatsApp template messages

### 3.4 ModuleBootloader

- Sistema de feature flags por tenant
- Pode controlar ativação do módulo WhatsApp per tenant
- Já suporta `data-akti-module-*` para mostrar/esconder UI

### 3.5 Webhook Infrastructure (Node.js API)

- Express já configurado em `api/src/app.js`
- Rotas de webhook existentes para gateways de pagamento
- Parser JSON, CORS, Helmet, rate-limiting já configurados
- **Pode receber webhooks da Meta sem novo servidor**

### 3.6 Dados de Clientes

- Campo `cellphone` em `customers` — já armazena WhatsApp
- Campo `phone` adicional
- Contatos B2B em `customer_contacts` com telefones
- Import/Export de clientes inclui campos de telefone

---

## 4. Gaps de Comunicação que WhatsApp API Preencheria

### 4.1 Eventos que Hoje NÃO Notificam o Cliente

| # | Evento do Ciclo de Vida | Status Atual | Com WhatsApp API |
|---|------------------------|-------------|-----------------|
| 1 | Pedido criado | ❌ Sem notificação | ✅ Template: confirmação de pedido |
| 2 | Orçamento gerado | ❌ Sem notificação | ✅ Template: link do orçamento |
| 3 | Status do pedido mudou | ❌ Sem notificação | ✅ Template: atualização de status |
| 4 | Pagamento confirmado | ❌ Sem notificação | ✅ Template: confirmação de pgto |
| 5 | Parcela vencendo em 3 dias | ❌ Sem lembrete | ✅ Template: lembrete de vencimento |
| 6 | Parcela em atraso | ❌ Sem cobrança | ✅ Template: lembrete de atraso |
| 7 | Código de rastreio adicionado | ❌ Manual (wa.me) | ✅ Template: rastreio automático |
| 8 | Pedido pronto para retirada | ❌ Sem notificação | ✅ Template: aviso de retirada |
| 9 | NF-e emitida | ❌ Email TODO | ✅ Template: NF-e + DANFE |
| 10 | Catálogo personalizado gerado | ❌ Manual (wa.me) | ✅ Template: link do catálogo |
| 11 | Mensagem do admin no portal | ❌ Sem push | ✅ Template: nova mensagem |
| 12 | Link de pagamento gateway | ❌ Manual (wa.me) | ✅ Template: link de pagamento |

---

## 5. Matriz de Comunicação: Atual vs. Proposta

```
                         EMAIL    WHATSAPP    NOTIF.INTERNA    PORTAL
                        ────────  ─────────   ──────────────   ──────
Pedido criado            ❌         ❌            ❌              ❌
Orçamento pronto         ❌         Manual        ❌              ❌
Status mudou             ❌         ❌            ✅ (interno)    ❌
Pagamento confirmado     ❌         ❌            ❌              ✅
Parcela vencendo         ❌         ❌            ❌              ❌
Parcela em atraso        ❌         ❌            ❌              ❌
Rastreio adicionado      ❌         Manual        ❌              ❌
NF-e emitida             TODO       ❌            ✅ (interno)    ❌
Catálogo gerado          ❌         Manual        ❌              ❌
Link de pagamento        ❌         Manual        ❌              ❌
Mensagem admin→cliente   ❌         ❌            ❌              ✅
Magic link               TODO       ❌            ❌              ❌
Reset de senha           TODO       ❌            ❌              ❌
```

**Com a integração WhatsApp API, a coluna WHATSAPP passaria de 0 automatizados para 13+ automatizados.**
