# 📖 Documentação Final — Cadastro de Clientes v2

> **Módulo:** Cadastro de Clientes  
> **Sistema:** Akti — Gestão em Produção  
> **Versão:** 2.0 (Pós-refatoração completa — Fases 1 a 4)  
> **Última atualização:** 27/03/2026  

---

## 📋 Índice

1. [Visão Geral](#1-visão-geral)
2. [Campos do Cadastro](#2-campos-do-cadastro)
3. [Fluxo do Wizard (Formulário)](#3-fluxo-do-wizard)
4. [APIs e Integrações Externas](#4-apis-e-integrações-externas)
5. [Validações Aplicadas](#5-validações-aplicadas)
6. [Funcionalidades Avançadas (Fase 4)](#6-funcionalidades-avançadas)
7. [Atalhos de Teclado](#7-atalhos-de-teclado)
8. [Importação em Massa](#8-importação-em-massa)
9. [Permissões e Segurança](#9-permissões-e-segurança)
10. [Estrutura de Arquivos](#10-estrutura-de-arquivos)
11. [FAQ — Perguntas Frequentes](#11-faq)

---

## 1. Visão Geral

O módulo de Cadastro de Clientes do Akti foi completamente refatorado para atender necessidades profissionais de gestão, passando de 9 campos básicos para mais de 40 campos organizados em categorias, com validações em tempo real, integração com APIs externas e interface moderna em formato wizard.

### Evolução

| Métrica                         | Antes (v1) | Depois (v2) |
|---------------------------------|:----------:|:-----------:|
| Campos disponíveis              | 9          | 40+         |
| Score de completude (benchmark) | 24%        | 96%         |
| Validações em tempo real        | 0          | 15+         |
| Auto-preenchimento por API      | Não        | Sim         |
| Detecção de duplicatas          | Não        | Sim         |
| Importação em massa             | Básica     | Completa    |
| Soft delete                     | Não        | Sim         |
| Operações em lote               | Não        | Sim         |

---

## 2. Campos do Cadastro

### 2.1 — Bloco: Identificação (Step 1)

| Campo            | Tipo no BD       | Obrigatório | Descrição                                  |
|------------------|------------------|:-----------:|--------------------------------------------|
| `code`           | VARCHAR(20)      | Auto        | Código sequencial (CLI-00001)              |
| `person_type`    | ENUM('PF','PJ')  | Sim         | Pessoa Física ou Jurídica                  |
| `name`           | VARCHAR(191)     | Sim         | Nome Completo (PF) ou Razão Social (PJ)   |
| `fantasy_name`   | VARCHAR(191)     | Não         | Nome Fantasia (visível para PJ)            |
| `document`       | VARCHAR(20)      | Não*        | CPF (PF) ou CNPJ (PJ) — apenas números    |
| `rg_ie`          | VARCHAR(30)      | Não         | RG (PF) ou Inscrição Estadual (PJ)        |
| `im`             | VARCHAR(30)      | Não         | Inscrição Municipal (apenas PJ)            |
| `birth_date`     | DATE             | Não         | Data de Nascimento (PF) ou Fundação (PJ)  |
| `gender`         | VARCHAR(20)      | Não         | Gênero (apenas PF)                         |
| `status`         | ENUM             | Sim         | active, inactive, blocked                  |
| `photo`          | VARCHAR(255)     | Não         | Caminho da foto do cliente                 |

> *O documento não é obrigatório no formulário, mas é fortemente recomendado para evitar duplicatas.

### 2.2 — Bloco: Contato (Step 2)

| Campo              | Tipo no BD     | Obrigatório | Descrição                            |
|--------------------|----------------|:-----------:|--------------------------------------|
| `email`            | VARCHAR(191)   | Não*        | E-mail principal                     |
| `email_secondary`  | VARCHAR(191)   | Não         | E-mail secundário                    |
| `phone`            | VARCHAR(20)    | Não         | Telefone fixo                        |
| `cellphone`        | VARCHAR(20)    | Não*        | Celular / WhatsApp                   |
| `phone_commercial` | VARCHAR(20)    | Não         | Telefone comercial                   |
| `website`          | VARCHAR(255)   | Não         | Website                              |
| `instagram`        | VARCHAR(100)   | Não         | Instagram (sem @)                    |
| `contact_name`     | VARCHAR(191)   | Não         | Nome do contato principal (PJ)       |
| `contact_role`     | VARCHAR(100)   | Não         | Cargo/função do contato (PJ)         |

> *É recomendado preencher ao menos email ou celular.

### 2.3 — Bloco: Endereço (Step 3)

| Campo                  | Tipo no BD     | Obrigatório | Descrição                         |
|------------------------|----------------|:-----------:|-----------------------------------|
| `zipcode`              | VARCHAR(10)    | Não         | CEP (auto-preenche via ViaCEP)    |
| `address_street`       | VARCHAR(255)   | Não         | Logradouro                        |
| `address_number`       | VARCHAR(20)    | Não         | Número                            |
| `address_complement`   | VARCHAR(255)   | Não         | Complemento                       |
| `address_neighborhood` | VARCHAR(191)   | Não         | Bairro                            |
| `address_city`         | VARCHAR(191)   | Não         | Cidade                            |
| `address_state`        | VARCHAR(2)     | Não         | UF (2 letras)                     |
| `address_country`      | VARCHAR(100)   | Não         | País (default: Brasil)            |
| `address_ibge`         | VARCHAR(10)    | Não         | Código IBGE (auto via ViaCEP)     |

### 2.4 — Bloco: Comercial (Step 4)

| Campo              | Tipo no BD     | Obrigatório | Descrição                           |
|--------------------|----------------|:-----------:|-------------------------------------|
| `price_table_id`   | INT            | Não         | Tabela de preço vinculada           |
| `payment_term`     | VARCHAR(50)    | Não         | Condição de pagamento (ex: 30/60)   |
| `credit_limit`     | DECIMAL(12,2)  | Não         | Limite de crédito                   |
| `discount_default` | DECIMAL(5,2)   | Não         | Desconto padrão (%)                 |
| `seller_id`        | INT            | Não         | Vendedor responsável                |
| `origin`           | VARCHAR(100)   | Não         | Origem (Indicação, Site, etc.)      |
| `tags`             | TEXT           | Não         | Tags separadas por vírgula          |
| `observations`     | TEXT           | Não         | Observações gerais                  |

### 2.5 — Campos de Controle (automáticos)

| Campo         | Tipo         | Descrição                    |
|---------------|-------------|-------------------------------|
| `created_at`  | DATETIME    | Data/hora de criação          |
| `updated_at`  | DATETIME    | Data/hora da última alteração |
| `created_by`  | INT         | ID do usuário que criou       |
| `updated_by`  | INT         | ID do usuário que alterou     |
| `deleted_at`  | DATETIME    | Soft delete (NULL = ativo)    |

---

## 3. Fluxo do Wizard

O formulário de cadastro (criação e edição) utiliza um wizard em 4 passos:

```
┌────────────────────────────────────────────────────────────────┐
│  Step 1            Step 2          Step 3          Step 4      │
│  Identificação  ▶  Contato      ▶  Endereço     ▶  Comercial  │
│  ████████████     ░░░░░░░░       ░░░░░░░░        ░░░░░░░░     │
└────────────────────────────────────────────────────────────────┘
```

### Step 1 — Identificação
- Toggle PF/PJ no topo (altera labels e campos visíveis)
- Upload de foto com drag & drop e preview
- Campos condicionais: Gênero (PF), IM e Contato PJ (PJ)
- Botão "Consultar CNPJ" para auto-preenchimento via BrasilAPI

### Step 2 — Contato
- Campos de telefone com máscaras dinâmicas
- Validação de e-mail em tempo real
- Campos de contato PJ (nome e cargo)

### Step 3 — Endereço
- CEP com auto-preenchimento via ViaCEP
- Campos de endereço preenchidos automaticamente
- Indicador visual de campos preenchidos por API (`cst-api-filled`)

### Step 4 — Comercial
- Campo de tags com autocomplete e chips coloridos
- Seleção de tabela de preço e vendedor
- Campos financeiros com máscara de moeda
- Indicador de completude do cadastro

### Navegação
- **Botão Anterior / Próximo** no footer do wizard
- **Ctrl+→ / Ctrl+←** para avançar/voltar
- Barra de progresso visual (stepper) no topo
- Validação por step antes de avançar

---

## 4. APIs e Integrações Externas

### 4.1 — ViaCEP (Auto-preenchimento de endereço)

| Item        | Detalhe                                     |
|-------------|---------------------------------------------|
| **URL**     | `https://viacep.com.br/ws/{CEP}/json/`      |
| **Método**  | GET                                          |
| **Trigger** | Blur no campo CEP após validação de formato  |
| **Campos preenchidos** | Logradouro, Bairro, Cidade, UF, Código IBGE |
| **Fallback**| Se o servidor PHP proxy falhar, tenta direto do JS |
| **Endpoint interno** | `?page=customers&action=searchCep&cep=XXXXX` |

### 4.2 — BrasilAPI (Consulta CNPJ)

| Item        | Detalhe                                      |
|-------------|----------------------------------------------|
| **URL**     | `https://brasilapi.com.br/api/cnpj/v1/{CNPJ}` |
| **Método**  | GET                                           |
| **Trigger** | Botão "Consultar CNPJ" (id="btnSearchCnpj")  |
| **Campos preenchidos** | Razão Social, Nome Fantasia, E-mail, Telefone, Endereço completo |
| **Endpoint interno** | `?page=customers&action=searchCnpj&cnpj=XXXXX` |

### 4.3 — Verificação de Duplicidade

| Item        | Detalhe                                            |
|-------------|----------------------------------------------------|
| **Trigger** | Blur no campo documento (CPF/CNPJ)                 |
| **Endpoint**| `?page=customers&action=checkDuplicate&document=XX` |
| **Resposta**| JSON com `exists`, `customer` (id, name, code)     |
| **Visual**  | ✓ verde se disponível, ⚠ amarelo se duplicado      |

### 4.4 — Autocomplete de Tags

| Item        | Detalhe                                            |
|-------------|----------------------------------------------------|
| **Trigger** | Focus no campo de tags                              |
| **Endpoint**| `?page=customers&action=getTags`                   |
| **Resposta**| JSON com array `tags` de tags únicas                |
| **Visual**  | Dropdown com sugestões filtráveis                   |

### 4.5 — Histórico de Pedidos

| Item        | Detalhe                                                   |
|-------------|-----------------------------------------------------------|
| **Trigger** | Abertura da aba Histórico na ficha do cliente              |
| **Endpoint**| `?page=customers&action=getOrderHistory&customer_id=X&page=N` |
| **Resposta**| JSON com `orders` (array) e `total` (int)                  |
| **Visual**  | Tabela com paginação AJAX                                  |

---

## 5. Validações Aplicadas

### 5.1 — Client-side (JavaScript — `customer-validation.js`)

| Campo        | Validação                                        | Feedback                    |
|--------------|--------------------------------------------------|-----------------------------|
| `name`       | Mínimo 3 caracteres                              | ✓ Verde / ✗ Vermelho       |
| `document`   | CPF: 11 dígitos + dígito verificador             | ✓ Disponível / ⚠ Duplicado |
| `document`   | CNPJ: 14 dígitos + dígito verificador            | ✓ Disponível / ⚠ Duplicado |
| `email`      | Formato válido (regex)                           | ✓ Verde / ✗ Vermelho       |
| `cellphone`  | Mínimo 10 dígitos                                | ✓ Verde / ✗ Vermelho       |
| `zipcode`    | CEP: 8 dígitos + auto-fill                       | ✓ Verde / ✗ Não encontrado |
| `website`    | URL com protocolo (http/https)                   | ✓ Verde / ✗ Vermelho       |
| Obrigatórios | Nome, Tipo Pessoa, Status                        | Bloqueio de avanço no step  |

### 5.2 — Server-side (PHP — `CustomerController::store()` / `update()`)

| Validação                      | Classe           | Método               |
|--------------------------------|------------------|-----------------------|
| Campos obrigatórios            | `Validator`      | `required()`          |
| CPF válido                     | `Validator`      | `cpf()`               |
| CNPJ válido                    | `Validator`      | `cnpj()`              |
| E-mail válido                  | `Validator`      | `email()`             |
| Valor em lista                 | `Validator`      | `inList()`            |
| URL válida                     | `Validator`      | `url()`               |
| Unicidade (excluindo próprio)  | `Validator`      | `uniqueExcept()`      |
| Sanitização de documento       | Model            | `preg_replace('/\D/', '')` |
| CSRF token                     | Middleware       | `csrf_check()`        |

### 5.3 — Máscaras de Input (IMask.js — `customer-masks.js`)

| Campo        | Máscara                              |
|--------------|--------------------------------------|
| CPF          | `000.000.000-00`                     |
| CNPJ         | `00.000.000/0000-00`                 |
| Telefone     | `(00) 0000-0000`                     |
| Celular      | `(00) 00000-0000`                    |
| CEP          | `00000-000`                          |
| Data         | `00/00/0000`                         |
| Moeda (R$)   | `R$ 0.000.000,00`                    |
| Desconto     | `0,00%`                              |

---

## 6. Funcionalidades Avançadas

### 6.1 — Campo de Tags (Autocomplete + Chips)

**Arquivo:** `assets/js/customer-tags.js`

O campo de tags exibe tags como "pills" (chips) coloridos com botão de remoção. Ao focar no campo, as tags existentes no sistema são carregadas via AJAX e exibidas como sugestões em dropdown.

**Funcionalidades:**
- Autocomplete com sugestões filtráveis do banco de dados
- Pills/chips coloridos com cores consistentes por tag
- Criação de novas tags ao pressionar Enter ou vírgula
- Remoção de tags com Backspace ou clique no ×
- Armazenamento como string separada por vírgula no campo hidden `tags`
- Case-insensitive para evitar duplicatas (ex: "VIP" = "vip")

### 6.2 — Indicador de Completude

**Arquivo:** `assets/js/customer-completeness.js`

Barra de progresso que mostra a porcentagem de preenchimento do cadastro.

**Pesos:**

| Grupo          | Peso | Critério de "Completo" (✅)                    |
|----------------|:----:|------------------------------------------------|
| Identificação  | 30%  | Tipo + Nome (≥3 chars) + Documento             |
| Contato        | 25%  | Email OU Celular preenchido                    |
| Endereço       | 25%  | CEP + Cidade + Estado                          |
| Comercial      | 20%  | Qualquer campo preenchido                      |

**Cores:**
- 🔴 Vermelho: 0-39%
- 🟡 Amarelo: 40-69%
- 🟢 Verde: 70-100%

### 6.3 — Auto-Save (localStorage)

**Arquivo:** `assets/js/customer-autosave.js`

Salva automaticamente o estado do formulário a cada 30 segundos no localStorage do navegador.

**Fluxo:**
1. A cada 30s, coleta todos os valores do formulário
2. Salva com chave `akti_customer_draft_{create|edit_ID}`
3. Ao abrir o formulário, se houver draft:
   - Exibe SweetAlert: "Rascunho encontrado. Deseja restaurar?"
   - Opções: Restaurar / Descartar / Fechar
4. Ao submeter com sucesso, o draft é limpo
5. Ao clicar "Cancelar", pergunta se quer limpar o draft
6. Drafts expiram após 24 horas

### 6.4 — Histórico de Pedidos com Paginação AJAX

Na ficha do cliente (tab "Histórico"), os pedidos são carregados via AJAX com paginação, sem recarregar a página.

**Colunas exibidas:**
- Número do pedido (link para detalhes)
- Data de criação
- Valor total formatado (R$)
- Status (badge colorido)
- Ações (ver detalhes)

---

## 7. Atalhos de Teclado

**Arquivo:** `assets/js/customer-shortcuts.js`

### Formulário (Create / Edit)

| Atalho     | Ação                          |
|------------|-------------------------------|
| `Ctrl+S`   | Salvar formulário             |
| `Ctrl+→`   | Próximo step do wizard        |
| `Ctrl+←`   | Step anterior do wizard       |
| `Esc`       | Voltar à listagem             |
| `?`         | Mostrar painel de atalhos     |

### Listagem

| Atalho     | Ação                          |
|------------|-------------------------------|
| `Ctrl+N`   | Novo cliente (ir para create) |
| `Ctrl+E`   | Exportar clientes             |
| `/`         | Focar na busca                |
| `Esc`       | Limpar campo de busca         |
| `?`         | Mostrar painel de atalhos     |

### Ficha do Cliente

| Atalho     | Ação                          |
|------------|-------------------------------|
| `Esc`       | Voltar à listagem             |

---

## 8. Importação em Massa

O módulo de importação aceita arquivos CSV e XLSX com mapeamento de colunas flexível.

### 8.1 — Template CSV

O template pode ser baixado via botão "Baixar template" na seção de importação.

### 8.2 — Campos Suportados na Importação

| Campo Sistema        | Label na Importação        | Auto-mapeamento aceito               |
|----------------------|----------------------------|---------------------------------------|
| `name`               | Nome / Razão Social        | nome, razao_social, name              |
| `person_type`        | Tipo Pessoa                | tipo, tipo_pessoa, type               |
| `document`           | CPF / CNPJ                 | cpf, cnpj, documento, document        |
| `fantasy_name`       | Nome Fantasia              | fantasia, nome_fantasia, fantasy      |
| `rg_ie`              | RG / Inscrição Estadual    | rg, ie, inscricao_estadual            |
| `im`                 | Inscrição Municipal        | im, inscricao_municipal               |
| `email`              | E-mail                     | email, e-mail, correio                |
| `phone`              | Telefone                   | telefone, fone, tel, phone            |
| `cellphone`          | Celular / WhatsApp         | celular, whatsapp, mobile             |
| `address_city`       | Cidade                     | cidade, city, municipio               |
| `address_state`      | Estado (UF)                | estado, uf, state                     |
| `zipcode`            | CEP                        | cep, zipcode, zip                     |
| `birth_date`         | Data Nascimento/Fundação   | nascimento, fundacao, birth           |
| `observations`       | Observações                | obs, observacao, observacoes, notes   |
| `origin`             | Origem                     | origem, canal, origin                 |
| `tags`               | Tags                       | tags, etiquetas, classificacao        |

### 8.3 — Fluxo de Importação

```
1. Upload do arquivo (CSV/XLSX)
2. Detecção automática de colunas (auto-mapeamento)
3. Revisão e ajuste manual do mapeamento
4. Preview dos primeiros registros
5. Confirmação e importação
6. Relatório de sucesso/erros
```

---

## 9. Permissões e Segurança

### 9.1 — Permissões por Grupo

O módulo de clientes está protegido por permissão de grupo. Para acessar:

1. O usuário deve estar logado
2. O grupo do usuário deve ter permissão na página `customers`
3. Configurado em **Gestão de Grupos** (`?page=users&action=groups`)

### 9.2 — Proteções de Segurança

| Proteção                | Implementação                              |
|-------------------------|--------------------------------------------|
| **CSRF**                | Token `csrf_token` em todos os formulários |
| **SQL Injection**       | Prepared statements (PDO) em todo o Model  |
| **XSS**                 | `htmlspecialchars()` em toda saída de dados |
| **Upload seguro**       | Validação de tipo, tamanho e nome do arquivo |
| **Soft Delete**         | Registros não são removidos fisicamente     |
| **Sanitização**         | Documento: apenas dígitos no banco          |
| **Escape HTML (JS)**    | `escapeHtml()` em todos os módulos JS       |

### 9.3 — Multi-Tenant

O sistema suporta multi-tenant. Cada tenant possui seu próprio banco de dados e a conexão é isolada por sessão.

---

## 10. Estrutura de Arquivos

```
Módulo de Clientes — Arquivos Envolvidos
═══════════════════════════════════════════

📁 app/
├── 📁 controllers/
│   └── CustomerController.php          # Controller principal (~1000 linhas)
├── 📁 models/
│   ├── Customer.php                    # Model CRUD + filtros + importação
│   └── CustomerContact.php            # Model de contatos adicionais
└── 📁 views/customers/
    ├── index.php                       # Listagem com drawer, cards, ações em lote
    ├── create.php                      # Wizard 4 steps (novo cliente)
    ├── edit.php                        # Wizard 4 steps (edição)
    └── view.php                        # Ficha detalhada com tabs

📁 assets/
├── 📁 css/
│   └── customers.css                   # CSS customizado com variáveis cst-*
└── 📁 js/
    ├── customer-masks.js               # Máscaras de input (IMask.js)
    ├── customer-validation.js          # Validações client-side
    ├── customer-wizard.js              # Lógica do wizard + toggle PF/PJ
    ├── customer-tags.js                # Autocomplete + chips de tags
    ├── customer-completeness.js        # Indicador de completude
    ├── customer-autosave.js            # Auto-save em localStorage
    └── customer-shortcuts.js           # Atalhos de teclado

📁 tests/Unit/
├── CustomerModelTest.php               # Testes do Model com PDO mock
├── CustomerFase2Test.php               # Testes Fase 2 (Validator, actions)
├── CustomerFase3Test.php               # Testes Fase 3 (Views, CSS, JS)
├── CustomerFase4Test.php               # Testes Fase 4 (Integrações)
└── ValidatorCpfCnpjTest.php           # Testes de validação CPF/CNPJ

📁 scripts/
└── fix_customer_duplicates.php         # Limpeza de duplicatas

📁 sql/
└── update_*.sql                        # Migrations do banco de dados

📁 docs/cadastro/
├── 01_DIAGNOSTICO_ATUAL.md
├── 02_PROPOSTA_CAMPOS.md
├── 03_PROPOSTA_UX.md
├── 04_PLANO_IMPLEMENTACAO.md
├── 05_COMPARATIVO_MERCADO.md
├── 06_CHECKLIST_VALIDACOES.md
├── 07_DOCUMENTACAO_FINAL.md            # ← Este arquivo
└── ROADMAP_CADASTRO_CLIENTES.md
```

---

## 11. FAQ

### ❓ Como criar um novo cliente?

Acesse **Clientes** no menu lateral e clique em **Novo Cliente** ou use o atalho `Ctrl+N`. Preencha os 4 steps do wizard e clique em **Salvar**.

### ❓ Qual a diferença entre PF e PJ?

- **PF (Pessoa Física):** Exibe campos de CPF, RG, Data de Nascimento e Gênero.
- **PJ (Pessoa Jurídica):** Exibe campos de CNPJ, Razão Social, Nome Fantasia, Inscrição Estadual, Inscrição Municipal e Contato PJ.

### ❓ O que é o indicador de completude?

A barra de progresso no formulário mostra a porcentagem de preenchimento do cadastro. Quanto mais campos preenchidos, maior a completude. O ideal é atingir acima de 70% (barra verde).

### ❓ O que acontece se eu fechar o formulário sem salvar?

Se o auto-save estiver ativo, ao reabrir o formulário será exibida uma mensagem perguntando se deseja restaurar o rascunho. Rascunhos são salvos a cada 30 segundos e expiram em 24 horas.

### ❓ Como funciona a verificação de duplicatas?

Ao sair do campo CPF/CNPJ, o sistema verifica automaticamente se já existe um cliente com o mesmo documento. Se houver duplicata, um alerta amarelo é exibido com o nome e código do cliente existente e um link para visualizá-lo.

### ❓ Posso importar clientes de uma planilha?

Sim. Na listagem de clientes, clique na aba **Importar** no menu lateral. Faça upload de um arquivo CSV ou XLSX. O sistema detecta automaticamente as colunas e permite ajuste manual antes da importação.

### ❓ Como adicionar tags a um cliente?

No Step 4 do wizard, o campo de tags permite digitar e selecionar tags existentes (autocomplete) ou criar novas. Pressione Enter ou vírgula para adicionar. Clique no × para remover.

### ❓ Como excluir um cliente?

Na ficha do cliente, clique no menu **Mais ações** (⋮) e selecione **Excluir**. O registro será marcado como excluído (soft delete) e não aparecerá mais na listagem. Administradores podem restaurar registros excluídos.

### ❓ Como funciona o auto-preenchimento por CEP?

Ao digitar um CEP válido (8 dígitos) e sair do campo, o sistema consulta o ViaCEP automaticamente e preenche Logradouro, Bairro, Cidade, UF e Código IBGE. Campos preenchidos por API ficam com bordas azuis.

### ❓ Como consultar dados de um CNPJ?

No formulário (quando PJ), clique no botão **Consultar CNPJ** ao lado do campo de documento. O sistema consultará a BrasilAPI e preencherá automaticamente Razão Social, Nome Fantasia, Telefone, E-mail e Endereço completo.

### ❓ Como alterar o status de vários clientes?

Na listagem, marque os checkboxes dos clientes desejados. A toolbar de ações em lote aparecerá no topo, permitindo alterar status (Ativar, Inativar, Bloquear) ou excluir em massa.

### ❓ Como exportar a lista de clientes?

Na listagem, clique no botão **Exportar CSV** ou use o atalho `Ctrl+E`. Os filtros ativos serão aplicados à exportação.

### ❓ Quais atalhos de teclado estão disponíveis?

Pressione `?` em qualquer página do módulo de clientes para ver o painel de atalhos de teclado disponíveis no contexto atual.

### ❓ O script de limpeza de duplicatas é seguro?

Sim. O script `scripts/fix_customer_duplicates.php` possui um modo **dry-run** (padrão) que apenas lista as duplicatas sem alterar nada. Execute sem parâmetros para ver o relatório e depois com `--execute` para aplicar.

### ❓ Como os dados são protegidos?

Todas as entradas são sanitizadas e validadas tanto no client-side (JavaScript) quanto no server-side (PHP). O sistema usa prepared statements (PDO) contra SQL Injection, tokens CSRF contra requisições forjadas e `htmlspecialchars()` contra XSS.

---

> **Documento gerado como parte da Fase 4 do Roadmap de Refatoração do Cadastro de Clientes.**  
> **Referências:** `ROADMAP_CADASTRO_CLIENTES.md`, `04_PLANO_IMPLEMENTACAO.md`, `06_CHECKLIST_VALIDACOES.md`
