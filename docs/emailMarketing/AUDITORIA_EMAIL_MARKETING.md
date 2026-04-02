# Auditoria — Sistema de E-mail Marketing

> **Data:** 2026-04-02  
> **Módulo:** FEAT-013 — E-mail Marketing  
> **Arquivos auditados:** 5 (1 controller, 1 model, 3 views)

---

## Índice

1. [Resumo Executivo](#1-resumo-executivo)
2. [Arquitetura Atual](#2-arquitetura-atual)
3. [Auditoria de Templates](#3-auditoria-de-templates)
4. [Auditoria de Campanhas](#4-auditoria-de-campanhas)
5. [Auditoria de Banco de Dados](#5-auditoria-de-banco-de-dados)
6. [Problemas Identificados](#6-problemas-identificados)
7. [Plano de Implementação](#7-plano-de-implementação)
8. [Alterações Realizadas](#8-alterações-realizadas)

---

## 1. Resumo Executivo

O módulo de E-mail Marketing (FEAT-013) possui estrutura básica funcional: CRUD de campanhas, CRUD de templates, schema de banco com 3 tabelas (`email_campaigns`, `email_templates`, `email_logs`). Porém, apresenta deficiências significativas de UX e funcionalidade incompleta.

### Status Geral

| Componente | Estado | Nota |
|---|---|---|
| CRUD Campanhas | ✅ Funcional | Criar, editar, listar, excluir |
| CRUD Templates | ⚠️ Incompleto | Criar e listar apenas; sem editar/excluir |
| Editor de Conteúdo | ❌ Ausente | Textarea bruto sem editor rico |
| Variáveis de Template | ❌ Não funcional | Campo JSON manual, sem UI amigável |
| Seleção de Destinatários | ❌ Ausente | Nenhuma UI para selecionar clientes |
| Preenchimento por Template | ❌ Ausente | Select de template não preenche campos |
| Motor de Envio | ❌ Ausente | Nenhum código de envio de e-mail |
| Rastreamento | ❌ Ausente | Colunas existem mas nunca são atualizadas |

---

## 2. Arquitetura Atual

### Arquivos do Módulo

| Arquivo | Tipo | Linhas | Função |
|---|---|---|---|
| `app/controllers/EmailMarketingController.php` | Controller | 142 | 8 métodos: index, create, store, edit, update, delete, templates, storeTemplate |
| `app/models/EmailCampaign.php` | Model | 164 | CRUD campaigns + templates + logs/stats |
| `app/views/email_marketing/index.php` | View | 95 | Listagem de campanhas com tabela |
| `app/views/email_marketing/form.php` | View | 128 | Formulário de campanha (criar/editar) |
| `app/views/email_marketing/templates.php` | View | 85 | Grid de templates + modal de criação |

### Rotas Registradas (`routes.php` L984-998)

```
email_marketing → EmailMarketingController
  - index (GET) → Listar campanhas
  - create (GET) → Form nova campanha
  - store (POST) → Salvar campanha
  - edit (GET) → Form editar campanha
  - update (POST) → Atualizar campanha
  - delete (GET) → Excluir campanha
  - templates (GET) → Listar templates
  - storeTemplate (POST) → Criar template
```

### Tabelas de Banco

- `email_templates` — Templates de e-mail (nome, assunto, body_html, variáveis JSON)
- `email_campaigns` — Campanhas (nome, assunto, body_html, status, agendamento, filtros JSON)
- `email_logs` — Logs de envio (destinatário, status, timestamps de abertura/clique/bounce)

---

## 3. Auditoria de Templates

### Estado Atual

**View:** `templates.php` — Grid de cards (`col-md-4`) mostrando nome, assunto e data de criação.

**Modal de criação:** Form simples com 4 campos:
- Nome (text)
- Assunto (text)
- Conteúdo HTML (textarea bruto, 8 linhas)
- Variáveis (input text, formato JSON manual: `["nome","email"]`)

### Problemas Encontrados

| # | Problema | Severidade | Descrição |
|---|---|---|---|
| T-01 | Sem edição de templates | 🔴 Alto | Não existe action `editTemplate` nem `updateTemplate`. Templates criados não podem ser modificados |
| T-02 | Sem exclusão de templates | 🔴 Alto | Botão de excluir não existe na view. Model tem `deleteTemplate()` mas controller/view não usam |
| T-03 | Criação via modal insuficiente | 🔴 Alto | Modal limita o espaço de edição. Conteúdo HTML de e-mail exige página dedicada |
| T-04 | Sem editor rico | 🔴 Alto | Textarea bruto para HTML. Usuário precisa escrever HTML puro sem pré-visualização |
| T-05 | Variáveis como JSON manual | 🟡 Médio | Campo texto esperando `["nome","email"]` — confuso para usuários não-técnicos |
| T-06 | Sem preview do template | 🟡 Médio | Nenhuma visualização do HTML renderizado |
| T-07 | Cards sem ações | 🟡 Médio | Cards mostram apenas info — sem botões de editar/excluir |

### Variáveis Disponíveis para Templates

Baseado na tabela `customers`, as variáveis que devem estar disponíveis para substituição nos templates:

| Variável | Descrição | Exemplo |
|---|---|---|
| `{{nome}}` | Nome completo do cliente | João Silva |
| `{{email}}` | E-mail do cliente | joao@email.com |
| `{{telefone}}` | Telefone do cliente | (11) 99999-0000 |
| `{{documento}}` | CPF/CNPJ | 123.456.789-00 |
| `{{cidade}}` | Cidade | São Paulo |
| `{{estado}}` | Estado (UF) | SP |
| `{{empresa}}` | Nome da empresa (tenant) | Akti Gráfica |

---

## 4. Auditoria de Campanhas

### Estado Atual

**View:** `form.php` — Formulário com campos:
- Nome da campanha (text)
- Template (select dropdown)
- Agendamento (datetime-local)
- Assunto (text)
- Conteúdo HTML (textarea bruto, 10 linhas)

### Problemas Encontrados

| # | Problema | Severidade | Descrição |
|---|---|---|---|
| C-01 | Sem editor rico | 🔴 Alto | Mesmo problema dos templates — textarea bruto |
| C-02 | Template não preenche campos | 🔴 Alto | Select de template não carrega assunto/body no formulário |
| C-03 | Sem seleção de destinatários | 🔴 Alto | Campo `segment_filters` (JSON) existe no banco mas nenhuma UI permite selecionar clientes |
| C-04 | Sem variáveis clicáveis | 🟡 Médio | Sem indicação de quais variáveis podem ser usadas no corpo |
| C-05 | Sem preview | 🟡 Médio | Nenhuma visualização do e-mail final |
| C-06 | Stats sem dados reais | 🟡 Médio | Cards de stats (Enviados/Abertos/Clicados/Bounced) existem mas sempre mostram zero |

---

## 5. Auditoria de Banco de Dados

### Schema Existente — Adequação

| Tabela | Colunas | Status |
|---|---|---|
| `email_templates` | id, tenant_id, name, subject, body_html, body_text, variables (JSON), created_by, created_at, updated_at | ✅ Suficiente |
| `email_campaigns` | id, tenant_id, template_id, name, subject, body_html, status, scheduled_at, sent_at, total_recipients, total_sent, total_opened, total_clicked, segment_filters (JSON), created_by, created_at, updated_at | ✅ Suficiente |
| `email_logs` | id, tenant_id, campaign_id, recipient_email, recipient_name, customer_id, status, opened_at, clicked_at, bounced_at, error_message, created_at | ✅ Suficiente |

**Nota:** O schema está bem projetado e suporta todas as funcionalidades planejadas. Não necessita alterações.

---

## 6. Problemas Identificados — Prioridade de Correção

### Prioridade 1 — Correções Críticas (implementar agora)

1. **Templates: Adicionar edição e exclusão** — Página dedicada para criar/editar com editor rico
2. **Templates: Editor de texto rico (Summernote)** — Substituir textarea por editor WYSIWYG profissional
3. **Templates: Painel de variáveis clicáveis** — Botões que inserem `{{variavel}}` no cursor do editor
4. **Campanhas: Editor de texto rico (Summernote)** — Mesmo editor nos formulários de campanha
5. **Campanhas: Preenchimento automático por template** — Ao selecionar template, carregar assunto + body via AJAX
6. **Campanhas: Seleção de destinatários** — UI para escolher "Todos" ou selecionar clientes específicos via Select2

### Prioridade 2 — Melhorias Futuras

7. ~~Preview do e-mail renderizado (iframe)~~ ✅ Implementado
8. ~~Motor de envio via PHPMailer/SMTP~~ ✅ Implementado
9. ~~Rastreamento de aberturas e cliques (tracking pixel + redirect)~~ ✅ Implementado
10. ~~Agendamento via cron job~~ ✅ Implementado

---

## 7. Plano de Implementação

### 7.1 — Editor Rico: Summernote

**Escolha:** [Summernote](https://summernote.org/) — Editor WYSIWYG leve, compatível com Bootstrap 5, jQuery-based (já disponível no sistema).

**CDN:**
```html
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-pt-BR.min.js"></script>
```

**Config padrão para e-mail marketing:**
```javascript
$('#bodyHtml').summernote({
    lang: 'pt-BR',
    height: 400,
    toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
        ['fontname', ['fontname']],
        ['fontsize', ['fontsize']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link', 'picture', 'hr']],
        ['view', ['fullscreen', 'codeview', 'help']]
    ],
    callbacks: {
        onImageUpload: function(files) { /* upload handler */ }
    }
});
```

### 7.2 — Templates: Página dedicada de criação/edição

**Novas actions necessárias:**
- `createTemplate` (GET) → Página de criação de template
- `editTemplate` (GET) → Página de edição de template
- `updateTemplate` (POST) → Salvar alterações
- `deleteTemplate` (GET/POST) → Excluir template
- `getTemplateJson` (GET/AJAX) → Retornar template em JSON (para preenchimento na campanha)

**Nova view:** `app/views/email_marketing/template_form.php` — Página completa com:
- Nome, Assunto
- Editor Summernote para body_html
- Painel lateral de variáveis clicáveis
- Preview em tempo real

### 7.3 — Campanhas: Preenchimento por Template

Ao selecionar um template no `<select>`, fazer fetch AJAX para `getTemplateJson` e preencher:
- Campo `subject` com o assunto do template
- Editor Summernote com o `body_html` do template

### 7.4 — Campanhas: Seleção de Destinatários

Adicionar ao formulário:
- Radio: "Todos os clientes" / "Selecionar clientes"
- Select2 múltiplo com busca AJAX (reutilizar pattern do AttachmentController.searchEntities)
- Chips visuais dos clientes selecionados

### 7.5 — Variáveis Disponíveis

Painel de variáveis padrão disponíveis para inserção:

```
{{nome}} — Nome do cliente
{{email}} — E-mail do cliente
{{telefone}} — Telefone
{{documento}} — CPF/CNPJ
{{cidade}} — Cidade
{{estado}} — Estado (UF)
{{empresa}} — Nome da empresa
```

Cada variável é um botão clicável que insere o texto no cursor do Summernote.

---

## 8. Alterações Realizadas

### Arquivos Modificados

| Arquivo | Alteração |
|---|---|
| `app/controllers/EmailMarketingController.php` | Novos métodos: `createTemplate()`, `editTemplate()`, `updateTemplate()`, `deleteTemplate()`, `getTemplateJson()`, `searchCustomers()`, `previewTemplate()`, `previewCampaign()`, `sendCampaign()`, `sendTest()` |
| `app/models/EmailCampaign.php` | Novo método: `updateTemplate()`, `getStats()` retorna array com chaves nomeadas |
| `app/views/email_marketing/templates.php` | Botões editar/excluir/preview nos cards, modal preview com iframe |
| `app/views/email_marketing/template_form.php` | **NOVO** — Página dedicada com Summernote + variáveis + preview |
| `app/views/email_marketing/form.php` | Summernote, preenchimento por template, seleção de destinatários, preview, envio teste, envio campanha |
| `app/views/email_marketing/index.php` | Preview, envio direto, correção `total_sent`/`total_opened`/`total_clicked` |
| `app/config/routes.php` | Novas actions: createTemplate, editTemplate, updateTemplate, deleteTemplate, getTemplateJson, searchCustomers, previewTemplate, previewCampaign, sendCampaign, sendTest + rota pública email_track (open, click) |
| `app/config/mail.php` | **NOVO** — Configuração SMTP via env vars |
| `app/services/EmailService.php` | **NOVO** — Serviço de envio de e-mails (PHPMailer), substituição de variáveis, logs, tracking pixel/link |
| `app/controllers/EmailTrackingController.php` | **NOVO** — Tracking de aberturas (pixel 1x1) e cliques (redirect com HMAC) |
| `scripts/email_cron.php` | **NOVO** — Cron job para envio de campanhas agendadas |
| `composer.json` | Adicionado `phpmailer/phpmailer ^6.9` |
| `.env.example` | Variáveis de e-mail: MAIL_HOST, MAIL_PORT, MAIL_USERNAME, etc. |

### Novas Rotas

```
email_marketing:
  + createTemplate  → GET  → Página de criação de template
  + editTemplate    → GET  → Página de edição de template
  + updateTemplate  → POST → Salvar template editado
  + deleteTemplate  → GET  → Excluir template (com confirmação)
  + getTemplateJson → GET  → AJAX: retorna template em JSON
  + searchCustomers → GET  → AJAX: busca clientes para Select2
  + previewTemplate → GET  → Renderiza preview do template
  + previewCampaign → GET  → Renderiza preview da campanha
  + sendCampaign    → GET  → Dispara envio da campanha
  + sendTest        → GET  → Envia e-mail de teste

email_track (público, sem autenticação):
  + open  → GET  → Tracking pixel (1x1 GIF) — registra abertura
  + click → GET  → Tracking de clique — registra e redireciona
```

### Cron Job

Script: `scripts/email_cron.php`

Processa campanhas com `status = 'scheduled'` e `scheduled_at <= NOW()`.

**Configuração:**
```bash
# Linux (crontab)
* * * * * php /path/to/scripts/email_cron.php >> /path/to/storage/logs/email_cron.log 2>&1

# Windows (Task Scheduler)
# Ação: php.exe
# Argumentos: D:\path\to\scripts\email_cron.php
# Disparar: A cada 1 minuto
```
