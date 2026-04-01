# Roadmap de Novas Funcionalidades — Akti v2

> ## Por que este Roadmap existe?
>
> O Akti nasceu como um sistema de gestão de produção e evoluiu para um ERP completo com CRM, financeiro, NF-e, portal do cliente e site builder. A cada sprint, novas demandas surgem dos segmentos atendidos — gráficas, confecções, metalurgia, marcenaria, alimentos e serviços. Este roadmap consolida as **funcionalidades mais solicitadas e estratégicas** em um documento versionável.
>
> **Para que ele serve:**
>
> 1. **Priorização:** Define o que implementar primeiro com base no impacto no negócio e na complexidade técnica
> 2. **Planejamento:** Permite estimar esforço e alocar recursos por quarter/sprint
> 3. **Comunicação:** Alinha equipe técnica, produto e stakeholders sobre a direção do sistema
> 4. **Tracking:** Cada funcionalidade tem ID, status e dependências mapeadas
> 5. **Modularidade:** Features são descritas com escopo fechado — cada uma pode ser implementada independentemente
>
> **Como ler este document:**
>
> - **Fase 1 (Foundation):** Features que habilitam outras features — infraestrutura necessária
> - **Fase 2 (Core Business):** Features que agregam valor direto ao operador
> - **Fase 3 (Advanced):** Features diferenciadas que posicionam o Akti no mercado
> - **Fase 4 (Innovation):** Features de longo prazo e exploratórias
>
> Cada feature inclui: ID, descrição, dependências, complexidade estimada e benefício.

---

## Fase 1: Foundation (Infraestrutura)

### FEAT-001: Sistema de Notificações em Tempo Real
- **Descrição:** Substituir polling de 60s por WebSocket ou Server-Sent Events para notificações instantâneas
- **Benefício:** Operadores recebem alertas de pipeline, pagamentos e NF-e em tempo real
- **Complexidade:** Alta
- **Dependências:** API Node.js (já tem Express)
- **Implementação sugerida:**
  - WebSocket server no Node.js (socket.io ou ws)
  - PHP emite evento → Node.js broadcast para clientes do tenant
  - Frontend: `notification-bell.js` migra de polling para WebSocket
- **Escopo:**
  - [ ] WebSocket server em api/
  - [ ] Emissão de eventos PHP → Node.js (via HTTP interno ou Redis pub/sub)
  - [ ] Frontend listener
  - [ ] Fallback para polling se WebSocket indisponível
- **Status:** ⬜ Planejado

### FEAT-002: Sistema de Permissões Granular (RBAC v2)
- **Descrição:** Evoluir de permissões por página para permissões por ação (CRUD granular)
- **Benefício:** "Vendedor vê pedidos mas não deleta", "Financeiro vê parcelas mas não cancela NF-e"
- **Complexidade:** Alta
- **Dependências:** Tabela `permissions`, refatoração de UserGroup
- **Implementação sugerida:**
  ```
  permissions: page, action, can_view, can_create, can_edit, can_delete
  user_groups: id, name
  group_permissions: group_id, permission_id
  ```
- **Escopo:**
  - [ ] Migration: tabela permissions + group_permissions
  - [ ] Model Permission com CRUD
  - [ ] Middleware de verificação por action
  - [ ] UI de configuração de permissões por grupo
  - [ ] Migração de permissões existentes
- **Status:** ⬜ Planejado

### FEAT-003: Sistema de Anexos/Documentos
- **Descrição:** Upload e gestão de documentos vinculados a entidades (pedidos, clientes, NF-e)
- **Benefício:** Armazena contratos, comprovantes, fotos de produção, laudos
- **Complexidade:** Média
- **Dependências:** Upload multi-tenant (já existe), MIME validation (SEC-006)
- **Implementação sugerida:**
  ```
  attachments: id, tenant_id, entity_type, entity_id, filename, path, mime_type, size, uploaded_by, created_at
  ```
- **Escopo:**
  - [ ] Migration: tabela attachments
  - [ ] Model Attachment
  - [ ] Service: upload, download, delete com validação MIME
  - [ ] Componente view reutilizável (lista de anexos + upload)
  - [ ] Integração com Pedidos, Clientes, NF-e
- **Status:** ⬜ Planejado

### FEAT-004: Auditoria Completa (Audit Log)
- **Descrição:** Registrar TODAS as ações do sistema (CRUD, login, export, print) com before/after
- **Benefício:** Compliance, investigação de problemas, LGPD
- **Complexidade:** Média
- **Dependências:** Event system (já funcional)
- **Implementação sugerida:**
  - Listener global para todos os `model.*` events
  - Armazena `old_values` vs `new_values` em JSON
  - UI de consulta com filtros por usuário, entidade, período
- **Escopo:**
  - [ ] Migration: tabela audit_logs
  - [ ] Listener: AuditLogListener
  - [ ] View: app/views/audit/index.php com filtros
  - [ ] Export PDF/CSV da auditoria
- **Status:** ⬜ Planejado

---

## Fase 2: Core Business (Valor Direto)

### FEAT-005: Módulo de Compras / Fornecedores
- **Descrição:** CRUD de fornecedores, ordens de compra, cotações, recebimento
- **Benefício:** Fecha o ciclo compras → estoque → produção → venda
- **Complexidade:** Alta
- **Dependências:** Stock module, Products module
- **Escopo:**
  - [ ] Migration: suppliers, purchase_orders, purchase_items
  - [ ] Model: Supplier, PurchaseOrder, PurchaseItem
  - [ ] Controller: SupplierController, PurchaseOrderController
  - [ ] Views: listagem, form, detalhe de compra
  - [ ] Integração com estoque (entrada automática)
  - [ ] Rota + Menu + Permissões
- **Status:** ⬜ Planejado

### FEAT-006: Módulo de Orçamentos Avançado
- **Descrição:** Orçamentos com validade, versões, aprovação pelo cliente, conversão em pedido
- **Benefício:** Fluxo comercial completo — de cotação à venda
- **Complexidade:** Média
- **Dependências:** Portal do cliente (para aprovação)
- **Escopo:**
  - [ ] Migration: quotes, quote_items, quote_versions
  - [ ] Model com versionamento
  - [ ] PDF de orçamento com branding
  - [ ] Link de aprovação no portal
  - [ ] Conversão automática quote → order
  - [ ] Dashboard de orçamentos (win rate, valor médio)
- **Status:** ⬜ Planejado

### FEAT-007: Agenda/Calendário Integrado
- **Descrição:** Calendário visual com compromissos, follow-ups, entregas e vencimentos
- **Benefício:** Vendedores e gestores visualizam prazos em uma tela
- **Complexidade:** Média
- **Dependências:** FullCalendar.js (CDN)
- **Escopo:**
  - [ ] Migration: calendar_events (tipo, data, entidade_vinculada)
  - [ ] Integração com: pedidos (entrega), parcelas (vencimento), pipeline (SLA)
  - [ ] View com FullCalendar drag-drop
  - [ ] Notificações de eventos próximos
  - [ ] Sincronização com Google Calendar (fase futura)
- **Status:** ⬜ Planejado

### FEAT-008: Relatórios Customizáveis
- **Descrição:** Criador de relatórios onde o usuário seleciona campos, filtros e agrupamentos
- **Benefício:** Elimina pedidos de "relatório específico" — self-service analytics
- **Complexidade:** Muito Alta
- **Dependências:** Queries parametrizáveis, export PDF/Excel (já existe)
- **Escopo:**
  - [ ] Migration: report_templates (configuração salva)
  - [ ] UI: Seletor de entidade → campos → filtros → agrupamento → preview
  - [ ] Motor de query dinâmico (seguro contra SQL injection!)
  - [ ] Salvamento de templates por usuário/grupo
  - [ ] Agendamento de relatórios por email
- **Status:** ⬜ Planejado

### FEAT-009: Multi-Currency e Multi-Language
- **Descrição:** Suporte a múltiplas moedas (USD, EUR) e idiomas (en, es)
- **Benefício:** Abre mercado para tenants internacionais
- **Complexidade:** Muito Alta
- **Dependências:** i18n helper (atualmente inexistente)
- **Escopo:**
  - [ ] Helper `__('key')` para traduções
  - [ ] Arquivos de idioma: pt_BR.php, en.php, es.php
  - [ ] Migration: currency_rates, tenant_settings.default_currency
  - [ ] Formatação de valores: `CurrencyFormatter::format($amount, $currency)`
  - [ ] Preferência de idioma por usuário
- **Status:** ⬜ Planejado

---

## Fase 3: Advanced (Diferencial Competitivo)

### FEAT-010: Automação de Workflow (Regras de Negócio)
- **Descrição:** Editor visual de regras: "Quando X acontecer, faça Y"
- **Exemplos:**
  - "Quando pedido > R$5000, exigir aprovação do gerente"
  - "Quando parcela vencer, enviar email ao cliente"
  - "Quando estoque < 10 unidades, notificar compras"
- **Complexidade:** Muito Alta
- **Dependências:** Event system, email service
- **Escopo:**
  - [ ] Migration: workflow_rules, workflow_actions, workflow_conditions
  - [ ] Motor de regras: avaliar condições → executar ações
  - [ ] UI visual (drag-drop de condições e ações)
  - [ ] Ações: enviar email, criar notificação, alterar status, criar tarefa
  - [ ] Logs de execução de regras
- **Status:** ⬜ Planejado

### FEAT-011: App Mobile (PWA Avançado ou React Native)
- **Descrição:** Aplicativo mobile para operadores de produção e vendedores
- **Funções prioritárias:**
  - Pipeline kanban mobile
  - Leitor de código de barras (câmera)
  - Catálogo offline com fotos
  - Notificações push
- **Complexidade:** Muito Alta
- **Dependências:** API REST expandida (FEAT-012)
- **Status:** ⬜ Futuro

### FEAT-012: Expansão da API REST
- **Descrição:** Cobrir TODAS as entidades (customers, orders, financial, NF-e) na API Node.js
- **Benefício:** Habilita integrações externas, app mobile, automações
- **Complexidade:** Alta
- **Dependências:** API versioning (ARQ-009)
- **Escopo:**
  - [ ] CustomerController + routes
  - [ ] OrderController + routes
  - [ ] FinancialController + routes
  - [ ] NfeController + routes (consulta apenas)
  - [ ] Swagger/OpenAPI documentation
  - [ ] Rate limiting por tenant
- **Status:** ⬜ Planejado

### FEAT-013: Email Marketing / CRM Avançado
- **Descrição:** Envio de emails em massa, templates, tracking de abertura, segmentação
- **Benefício:** Marketing direto integrado ao CRM
- **Complexidade:** Alta
- **Dependências:** SMTP/API de email (SendGrid, SES)
- **Escopo:**
  - [ ] Migration: email_campaigns, email_templates, email_logs
  - [ ] Editor de template (HTML)
  - [ ] Segmentação por tags de cliente
  - [ ] Tracking: abertura, cliques
  - [ ] Unsubscribe link (LGPD)
- **Status:** ⬜ Futuro

### FEAT-014: Integração com Marketplaces
- **Descrição:** Sincronizar produtos, pedidos e estoque com Mercado Livre, Shopee, Amazon
- **Benefício:** Multi-channel selling sem duplicação manual
- **Complexidade:** Muito Alta
- **Dependências:** API REST, Stock module
- **Escopo:**
  - [ ] Conector Mercado Livre (OAuth + Products API + Orders API)
  - [ ] Conector Shopee
  - [ ] Sync de estoque (bidirecional)
  - [ ] Sync de pedidos (marketplace → Akti)
  - [ ] Dashboard de vendas por canal
- **Status:** ⬜ Futuro

---

## Fase 4: Innovation (Longo Prazo)

### FEAT-015: IA para Previsão de Demanda
- **Descrição:** Machine learning para prever vendas futuras e sugerir compras
- **Benefício:** Reduz estoque parado e rupturas
- **Complexidade:** Muito Alta
- **Dependências:** Histórico de vendas (>6 meses), Python ML service
- **Status:** ⬜ Exploratório

### FEAT-016: Dashboard em Tempo Real (Real-time Analytics)
- **Descrição:** Dashboard com gráficos atualizados em tempo real via WebSocket
- **Benefício:** Decisões informed — produção, vendas, financeiro ao vivo
- **Dependências:** FEAT-001 (WebSocket)
- **Status:** ⬜ Exploratório

### FEAT-017: Módulo de Qualidade
- **Descrição:** Checklists de qualidade por etapa do pipeline, non-conformities, ações corretivas
- **Benefício:** Rastreabilidade e melhoria contínua (ISO 9001)
- **Complexidade:** Alta
- **Status:** ⬜ Exploratório

### FEAT-018: Integração com Contabilidade
- **Descrição:** Exportação automática para sistemas contábeis (SPED Contábil, ECD)
- **Benefício:** Elimina retrabalho do contador
- **Complexidade:** Alta
- **Status:** ⬜ Exploratório

---

## Resumo por Fase

### Fase 1 — Foundation
| ID | Feature | Complexidade | Dependências |
|---|---|---|---|
| FEAT-001 | Notificações tempo real | Alta | API Node.js |
| FEAT-002 | RBAC granular | Alta | Migrations |
| FEAT-003 | Anexos/Documentos | Média | Upload |
| FEAT-004 | Audit log completo | Média | Events |

### Fase 2 — Core Business
| ID | Feature | Complexidade | Dependências |
|---|---|---|---|
| FEAT-005 | Compras/Fornecedores | Alta | Stock |
| FEAT-006 | Orçamentos avançados | Média | Portal |
| FEAT-007 | Agenda/Calendário | Média | FullCalendar |
| FEAT-008 | Relatórios customizáveis | Muito Alta | — |
| FEAT-009 | Multi-currency/language | Muito Alta | i18n helper |

### Fase 3 — Advanced
| ID | Feature | Complexidade | Dependências |
|---|---|---|---|
| FEAT-010 | Automação de workflow | Muito Alta | Events, email |
| FEAT-011 | App mobile | Muito Alta | API REST |
| FEAT-012 | Expansão API REST | Alta | API versioning |
| FEAT-013 | Email marketing | Alta | SMTP |
| FEAT-014 | Marketplaces | Muito Alta | API REST, Stock |

### Fase 4 — Innovation
| ID | Feature | Complexidade | Dependências |
|---|---|---|---|
| FEAT-015 | IA demanda | Muito Alta | Histórico |
| FEAT-016 | Dashboard real-time | Alta | WebSocket |
| FEAT-017 | Módulo qualidade | Alta | Pipeline |
| FEAT-018 | Contabilidade | Alta | NF-e, Financeiro |

---

## Diagrama de Dependências

```
FEAT-001 (WebSocket) ─────────────────────┐
                                           ├──→ FEAT-016 (Dashboard RT)
FEAT-002 (RBAC v2) ──────────────────────┐│
                                          ││
FEAT-003 (Anexos) ────────────────────────┤│
                                          ├┤──→ FEAT-005 (Compras)
FEAT-004 (Audit) ─────────────────────────┤│
                                          ││
ARQ-009 (API versioning) ─────────────────┤├──→ FEAT-012 (API REST) ──→ FEAT-011 (Mobile)
                                          ││                          ├──→ FEAT-014 (Marketplaces)
SEC-006 (Upload validation) ──→ FEAT-003  ││
                                          ││
FEAT-006 (Orçamentos) ───────────────────┘│
FEAT-007 (Agenda) ───────────────────────┘│
FEAT-009 (i18n) ─────────────────────────┘
```

---

## Checklist de Progresso

| ID | Fase | Status | Feature |
|---|---|---|---|
| FEAT-001 | 1 | ⬜ | Notificações tempo real |
| FEAT-002 | 1 | ⬜ | RBAC granular |
| FEAT-003 | 1 | ⬜ | Anexos/Documentos |
| FEAT-004 | 1 | ⬜ | Audit log completo |
| FEAT-005 | 2 | ⬜ | Compras/Fornecedores |
| FEAT-006 | 2 | ⬜ | Orçamentos avançados |
| FEAT-007 | 2 | ⬜ | Agenda/Calendário |
| FEAT-008 | 2 | ⬜ | Relatórios customizáveis |
| FEAT-009 | 2 | ⬜ | Multi-currency/language |
| FEAT-010 | 3 | ⬜ | Automação de workflow |
| FEAT-011 | 3 | ⬜ | App mobile |
| FEAT-012 | 3 | ⬜ | Expansão API REST |
| FEAT-013 | 3 | ⬜ | Email marketing |
| FEAT-014 | 3 | ⬜ | Marketplaces |
| FEAT-015 | 4 | ⬜ | IA demanda |
| FEAT-016 | 4 | ⬜ | Dashboard real-time |
| FEAT-017 | 4 | ⬜ | Módulo qualidade |
| FEAT-018 | 4 | ⬜ | Contabilidade |

**Total:** 18 features (4 foundation, 5 core, 5 advanced, 4 innovation)
