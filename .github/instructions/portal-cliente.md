# Portal do Cliente — Instruções de Desenvolvimento

> Documento complementar ao projeto: `docs/PROJETO_PORTAL_CLIENTE.md`

## Conceito

O Portal do Cliente é uma área **pública autenticada** (separada do painel admin) onde o cliente final acessa pelo celular para:

- Acompanhar pedidos e progresso no pipeline (timeline visual)
- Aprovar/recusar orçamentos pendentes
- Criar novos pedidos (que caem como orçamento no pipeline)
- Ver parcelas em aberto e acompanhar pagamentos
- Acompanhar envio/rastreamento
- Trocar mensagens com a empresa
- Acessar documentos (NF-e, boletos)

## Autenticação

- Tabela própria: `customer_portal_access` (FK → `customers.id`)
- Sessão separada: `$_SESSION['portal_customer_id']` (não conflita com admin)
- Dois métodos: e-mail+senha OU link mágico (token temporário)
- Middleware: `PortalAuthMiddleware` verifica sessão em toda ação autenticada

## Rota

- Página: `?page=portal`
- Config em `routes.php`: `public => true`, `before_auth => true`
- Controller: `PortalController.php` (namespace `Akti\Controllers`)

## Regras Críticas de Segurança

1. **TODA query filtra por `customer_id`** — nunca expor dados de outro cliente
2. **Sessão isolada** — `portal_customer_id` ≠ `user_id`
3. **CSRF obrigatório** em todo POST
4. **Sanitização** via `Input::post()` / `Input::get()`
5. **Rate limiting** no login

## Pedidos Criados pelo Portal

- Entram com `status = 'orcamento'`, `pipeline_stage = 'orcamento'`, `portal_origin = 1`
- O admin decide se aceita ou não (fluxo normal do pipeline)

## Aprovação de Orçamento

- Registra: `customer_approval_status`, `customer_approval_at`, `customer_approval_ip`, `customer_approval_notes`
- Aprovação move para `pipeline_stage = 'venda'`
- Recusa registra motivo (não necessariamente cancela)

## Layout

- **Mobile-first** com Bootstrap 5
- Layout próprio (sem sidebar admin): `app/views/portal/layout/`
- Bottom navigation bar com 5 ícones
- CSS: `assets/css/portal.css`
- JS: `assets/js/portal.js`

## Tabelas do Banco

- `customer_portal_access` — autenticação
- `customer_portal_sessions` — sessões ativas (opcional)
- `customer_portal_messages` — mensagens cliente↔empresa
- `customer_portal_config` — configurações do portal
- ALTER `orders` — campos de aprovação + `portal_origin`

## Eventos

```
portal.customer.logged_in
portal.order.approved
portal.order.rejected
portal.order.created
portal.message.sent
```

## Fases de Implementação

1. **Fase 1**: Auth + Dashboard + Layout
2. **Fase 2**: Pedidos + Aprovação
3. **Fase 3**: Novo Pedido (Orçamento)
4. **Fase 4**: Financeiro + Tracking
5. **Fase 5**: Mensagens + Documentos
6. **Fase 6**: Gestão do Portal no Admin
