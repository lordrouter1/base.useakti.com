# Akti — Gestão em Produção

> **Sistema ERP/CRM operacional** focado na linha de produção, adaptável para diferentes segmentos industriais (gráfica, confecção, alimentos, metalurgia, marcenaria, serviços sob demanda etc.).

---

## Sumário

1. [Regras Obrigatórias (Leia Primeiro)](#1-regras-obrigatórias-leia-primeiro)
2. [Controle de Versão (Git)](#2-controle-de-versão-git)
3. [Stack Tecnológica](#3-stack-tecnológica)
4. [Arquitetura e Estrutura de Pastas](#4-arquitetura-e-estrutura-de-pastas)
5. [Padrões de Código — PHP](#5-padrões-de-código--php)
6. [Padrões de Código — Frontend](#6-padrões-de-código--frontend)
7. [Banco de Dados e Migrations](#7-banco-de-dados-e-migrations)
8. [Roteamento](#8-roteamento)
9. [Responsabilidades MVC](#9-responsabilidades-mvc)
10. [Workflow: Adicionar Nova Funcionalidade](#10-workflow-adicionar-nova-funcionalidade)
11. [Segurança](#11-segurança)
12. [Testes](#12-testes)
13. [Multi-Tenant](#13-multi-tenant)
14. [Instruções Complementares](#14-instruções-complementares)

---

## 1. Regras Obrigatórias (Leia Primeiro)

> ⚠️ As regras desta seção são **bloqueantes** — devem ser seguidas **antes de qualquer outra ação**.

### 1.1 Workflow Git Obrigatório

**Ao INICIAR qualquer instrução/prompt:**

1. Executar `git status` para verificar arquivos pendentes (unstaged ou staged).
2. Se houver arquivos pendentes:
   - Executar `git add .`
   - Executar `git commit -m "inicio_<descricao_do_prompt>"` onde `<descricao_do_prompt>` é um resumo curto em snake_case do que será feito.
3. Se não houver arquivos pendentes: prosseguir normalmente.

**Ao FINALIZAR qualquer instrução/prompt:**

1. Executar `git add .`
2. Executar `git commit -m "<tipo>: <descricao_do_que_foi_feito>"` registrando todas as alterações realizadas.
   - Tipos de commit: `feat` (nova funcionalidade), `fix` (correção), `refactor` (refatoração), `docs` (documentação), `style` (formatação), `test` (testes), `chore` (manutenção).
   - Exemplo: `feat: adicionar modulo de fornecedores com CRUD completo`
,
> **Nunca** use `--force`, `--no-verify` ou `reset --hard` sem confirmação explícita do usuário.

### 1.2 Banco de Dados — Usar Skill `sql-migration`

**Toda alteração que envolva o banco de dados** (criação, modificação ou remoção de tabelas, colunas, índices, constraints, dados de configuração etc.) **deve obrigatoriamente usar a skill `sql-migration`** (`.github/skills/sql-migration/SKILL.md`) para gerar o arquivo SQL de atualização na pasta `/sql`.

**Padrão de nomenclatura:** `update_YYYYMMDDhhmm_<N>_descricao.sql`
- `YYYYMMDDhhmm` = data e hora **do momento da criação** (nunca usar data sugerida ou copiada)
- `<N>` = sequencial auto-detectado a partir dos arquivos existentes em `/sql` e `/sql/prontos`
- `descricao` = snake_case descritivo, sem acentos

O arquivo deve conter **apenas os comandos necessários** para atualizar o banco de produção. **Nunca altere diretamente o banco de produção sem o arquivo de migração correspondente.**

### 1.3 Proibições em Testes

- **Nenhum teste do PHPUnit deve verificar existência de arquivos `.sql`.**
- Não criar testes que dependam de estado do filesystem para migrations.

### 1.4 Autoload — Nunca Usar `require_once`

O projeto usa autoloader PSR-4 (`app/bootstrap/autoload.php`). **Nunca** adicionar `require_once`, `include` ou `require` para carregar classes do namespace `Akti\`. O autoloader resolve automaticamente.

---

## 2. Controle de Versão (Git)

### Convenção de Commits

```
<tipo>(<escopo>): <descrição curta>
```

| Tipo       | Quando usar                                          |
|------------|------------------------------------------------------|
| `feat`     | Nova funcionalidade                                  |
| `fix`      | Correção de bug                                      |
| `refactor` | Refatoração sem alterar comportamento                |
| `docs`     | Alteração de documentação                            |
| `style`    | Formatação, espaçamento (sem alteração de lógica)    |
| `test`     | Adicionar ou corrigir testes                         |
| `chore`    | Manutenção, configs, dependências                    |
| `migration`| Criação de arquivo SQL de migração                   |
| `perf`     | Melhoria de performance                              |

**Exemplos:**
- `feat(customers): adicionar filtro por cidade na listagem`
- `fix(pipeline): corrigir calculo de prazo na etapa de producao`
- `migration(products): adicionar coluna peso_bruto`

### Regras de Branch

- `main` — produção estável
- `develop` — desenvolvimento ativo
- `feature/<nome>` — novas funcionalidades
- `fix/<nome>` — correções
- Nunca comitar diretamente em `main` sem revisão.

---

## 3. Stack Tecnológica

| Camada       | Tecnologia                              | Versão       |
|--------------|----------------------------------------|--------------|
| Backend      | PHP                                    | ≥ 8.1        |
| Frontend     | HTML5, CSS3, JavaScript                | —            |
| Framework CSS| Bootstrap                              | 5.x          |
| Biblioteca JS| jQuery                                 | 3.7.x        |
| Ícones       | Font Awesome                           | 6.x          |
| Modais       | SweetAlert2                            | Última       |
| Gráficos     | Chart.js                               | Última       |
| Banco de Dados| MySQL / MariaDB                       | 5.7+ / 10.3+ |
| Arquitetura  | MVC (Model-View-Controller)            | —            |
| Autoloader   | PSR-4 customizado (`Akti\`)            | —            |
| API Node.js  | Express + Sequelize                    | —            |

---

## 4. Arquitetura e Estrutura de Pastas

```
/
├── index.php                   # Entry point / Bootstrap
├── composer.json               # Dependências PHP
├── app/
│   ├── bootstrap/
│   │   ├── autoload.php        # PSR-4 autoloader
│   │   └── events.php          # Registro de event listeners
│   ├── config/
│   │   ├── database.php        # Conexão PDO
│   │   ├── menu.php            # Registro centralizado de menus e permissões
│   │   ├── routes.php          # Mapeamento declarativo de rotas
│   │   ├── session.php         # SessionGuard e timeout
│   │   └── tenant.php          # TenantManager (multi-tenant)
│   ├── controllers/            # Controllers (namespace Akti\Controllers)
│   ├── core/                   # Application, Router, classes base
│   ├── middleware/              # Middleware de requisição
│   ├── models/                 # Models (namespace Akti\Models)
│   ├── services/               # Services (namespace Akti\Services)
│   ├── gateways/               # Gateway de pagamento
│   ├── utils/                  # Helpers e utilitários
│   ├── lang/                   # Internacionalização
│   └── views/                  # Templates PHP (HTML)
│       └── layout/             # header.php, footer.php
├── api/                        # API Node.js (Express)
├── assets/                     # Recursos estáticos (css, js, img, uploads)
├── sql/                        # Migrations pendentes
│   └── prontos/                # Migrations já aplicadas em teste
├── tests/                      # Testes PHPUnit
├── docs/                       # Documentação do projeto
├── scripts/                    # Scripts de manutenção (não versionados em prod)
├── storage/logs/               # Logs da aplicação
└── .github/
    ├── copilot-instructions.md # ESTE ARQUIVO
    ├── instructions/           # Instruções complementares por domínio
    └── skills/                 # Skills especializadas do Copilot
```

### Mapeamento de Namespaces (PSR-4)

| Namespace             | Diretório            |
|-----------------------|----------------------|
| `Akti\Controllers\`   | `app/controllers/`   |
| `Akti\Models\`         | `app/models/`        |
| `Akti\Services\`       | `app/services/`      |
| `Akti\Core\`           | `app/core/`          |
| `Akti\Middleware\`     | `app/middleware/`    |
| `Akti\Utils\`          | `app/utils/`         |
| `Akti\Config\`         | `app/config/`        |
| `Akti\Bootstrap\`      | `app/bootstrap/`     |
| `Akti\Gateways\`       | `app/gateways/`      |

---

## 5. Padrões de Código — PHP

### 5.1 Estilo Geral

- **Encoding:** UTF-8 sem BOM.
- **Indentação:** 4 espaços (nunca tabs).
- **Delimitador:** Sempre `<?php` completo, nunca short tags `<?`.
- **Linha final:** Todo arquivo PHP deve terminar com uma linha em branco.
- **Tamanho de linha:** Máximo 120 caracteres (soft limit).

### 5.2 Nomenclatura

| Elemento          | Convenção        | Exemplo                         |
|-------------------|------------------|---------------------------------|
| Classes           | PascalCase       | `CustomerController`            |
| Métodos           | camelCase        | `readAll()`, `processExportCsv()`|
| Variáveis         | camelCase        | `$orderTotal`, `$tenantId`      |
| Constantes        | UPPER_SNAKE_CASE | `MAX_LOGIN_ATTEMPTS`            |
| Tabelas BD        | snake_case       | `order_items`, `pipeline_stages`|
| Colunas BD        | snake_case       | `created_at`, `tenant_id`       |
| Arquivos de classe| PascalCase.php   | `OrderController.php`           |
| Arquivos de view  | snake_case.php   | `index.php`, `edit_form.php`    |

### 5.3 Estrutura de Classes

```php
<?php

namespace Akti\Models;

class Product
{
    private $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function readAll(): array
    {
        // ...
    }
}
```

### 5.4 Funções e Helpers

- Funções utilitárias ficam em `app/utils/helpers.php` (já carregado globalmente).
- Novas funções devem ser documentadas no mínimo com `@param` e `@return`.
- Consultar `.github/instructions/funcoes.md` para regras detalhadas de criação de funções.

### 5.5 Boas Práticas PHP

- **Prepared Statements** obrigatórios para toda query que receba input externo.
- **Type hints** em parâmetros e retorno quando possível.
- **Early return** para reduzir aninhamento.
- **Sem lógica em construtores** — construtores apenas atribuem dependências.
- **Sem variáveis globais** — usar injeção de dependência via construtor.

---

## 6. Padrões de Código — Frontend

### 6.1 HTML

- Semântica válida (HTML5): usar `<main>`, `<section>`, `<article>`, `<nav>` quando apropriado.
- Atributos `id` em camelCase: `id="customerForm"`.
- Atributos `class` em kebab-case: `class="btn-primary"`.
- Usar helpers de escape para todo dado dinâmico: `<?= e($variavel) ?>`.

### 6.2 CSS

- Framework base: **Bootstrap 5** — seguir o grid system (`col-lg-8`, `col-lg-4`).
- CSS customizado em `assets/css/` — organizado por módulo quando necessário.
- Nunca usar `!important` exceto para sobrescrever estilos de terceiros.
- **Responsividade obrigatória** — todo layout deve funcionar em mobile.

### 6.3 JavaScript

- Usar jQuery para manipulação do DOM e AJAX.
- **Nunca** usar `alert()`, `confirm()` ou `prompt()` nativos — usar **SweetAlert2**.
- AJAX: sempre enviar header CSRF: `headers: {'X-CSRF-TOKEN': csrfToken}`.
- Scripts específicos de página no final do `<body>` ou via `footer.php`.
- Variáveis e funções em camelCase: `loadCustomers()`, `handleFormSubmit()`.
- Usar `const` e `let` — nunca `var`.

### 6.4 Componentes Visuais

- **Cards:** layout padrão com `card > card-header > card-body`.
- **Tabelas:** sempre com `table-responsive`, classe `table table-hover`.
- **Formulários:** validação client-side com Bootstrap validation + server-side no controller.
- **Modais:** SweetAlert2 para confirmações, toasts para feedback.
- Consultar `.github/instructions/ui-ux.md` e `.github/instructions/modal-style.md` para detalhes.

---

## 7. Banco de Dados e Migrations

### 7.1 Regra Absoluta

> **Toda alteração de banco de dados** obriga a leitura e execução da skill `sql-migration` (`.github/skills/sql-migration/SKILL.md`).  
> **Nunca** criar arquivos SQL manualmente sem seguir o procedimento da skill.

### 7.2 Padrões de Schema

- **Engine:** InnoDB
- **Charset:** utf8mb4
- **Collation:** utf8mb4_unicode_ci
- **Toda tabela** deve ter `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` e `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`.
- **Toda tabela** de dados de tenant deve ter `tenant_id INT NOT NULL` com FK para `tenants(id)`.
- **Índices** em colunas usadas em WHERE, JOIN e ORDER BY.
- **Preferir idempotência:** `IF NOT EXISTS` / `IF EXISTS` sempre que possível.
- **Soft delete** com coluna `deleted_at DATETIME NULL` quando aplicável.

### 7.3 Fluxo de Vida dos Arquivos SQL

```
/sql/           → Migrations pendentes (não aplicadas)
    ↓ aplicar no DB de teste
/sql/prontos/   → Aplicadas em teste, pendentes para produção
    ↓ deploy em produção
    Arquivar    → Aplicadas em produção
```

### 7.4 Proibições

- **Nunca** DROP TABLE sem confirmação explícita do usuário.
- **Nunca** DELETE/TRUNCATE em massa sem confirmação.
- **Nunca** incluir dados de seed/teste no arquivo de migração.
- **Nunca** executar SQL automaticamente — apenas gerar o arquivo.

---

## 8. Roteamento

### 8.1 Registro de Rotas (`app/config/routes.php`)

Formato declarativo:

```php
'page_name' => [
    'controller'     => 'NomeController',    // Auto-prefixado: Akti\Controllers\
    'default_action' => 'index',
    'public'         => false,               // true = sem autenticação
    'before_auth'    => false,               // true = processada antes do auth check
    'actions'        => [
        'create' => 'create',               // action GET → método do controller
        'store'  => 'store',
        'edit'   => 'edit',
        'update' => 'update',
        'delete' => 'delete',
    ],
],
```

### 8.2 Padrão de Actions (CRUD)

| Action   | Método HTTP | Descrição                        |
|----------|------------|----------------------------------|
| `index`  | GET        | Listagem (padrão)                |
| `create` | GET        | Exibir formulário de criação     |
| `store`  | POST       | Processar formulário de criação  |
| `edit`   | GET        | Exibir formulário de edição      |
| `update` | POST       | Processar formulário de edição   |
| `delete` | GET/POST   | Excluir registro                 |

### 8.3 Permissões e Menu

- Registro centralizado em `app/config/menu.php`.
- Cada página com `'permission' => true` requer verificação de grupo do usuário.
- O `ModuleBootloader` bloqueia páginas de módulos desativados para o tenant.

---

## 9. Responsabilidades MVC

### Models (`app/models/`)

| ✅ Deve conter                                          | ❌ NÃO pode conter                     |
|---------------------------------------------------------|-----------------------------------------|
| Queries SQL (INSERT, SELECT, UPDATE, DELETE)             | HTML, `echo`, `print`                   |
| Validação de dados/regras de negócio                    | Acesso direto a `$_POST` ou `$_GET`     |
| Prepared statements com PDO                             | Redirecionamentos HTTP                  |
| Métodos CRUD: `create`, `readAll`, `readOne`, `update`, `delete` | Lógica de sessão ou autenticação |

### Controllers (`app/controllers/`)

| ✅ Deve conter                                          | ❌ NÃO pode conter                     |
|---------------------------------------------------------|-----------------------------------------|
| Captura de input (`$_POST`, `$_GET`)                    | Queries SQL diretas                     |
| Verificação de login/permissão (`checkAdmin`)           | HTML complexo                           |
| Instanciação de Models                                  | Lógica pesada de negócio                |
| Decisão de qual View renderizar                         | Manipulação direta de arquivos          |
| Redirecionamentos (`header('Location: ...')`)           |                                         |
| Mensagens de erro/sucesso                               |                                         |

### Views (`app/views/`)

| ✅ Deve conter                                          | ❌ NÃO pode conter                     |
|---------------------------------------------------------|-----------------------------------------|
| HTML estruturado com Bootstrap 5                        | Queries SQL / acesso ao banco           |
| Loops `foreach` para exibir dados                       | Alterações de registro                  |
| Escape com `e()` para XSS                               | Lógica complexa de PHP                  |
| Includes de `header.php` e `footer.php`                 | Instanciação de Models                  |
| FQCN quando necessário: `\Akti\Models\Classe::metodo()` | Redirecionamentos                       |

---

## 10. Workflow: Adicionar Nova Funcionalidade

Para adicionar um módulo completo (ex: "Fornecedores"), seguir **esta ordem rigorosa:**

### Passo 1 — Banco de Dados
- Usar skill `sql-migration` para gerar o arquivo SQL em `/sql/`.
- Incluir `tenant_id`, `created_at`, `updated_at`, índices.

### Passo 2 — Model
- Criar `app/models/Supplier.php`.
- `namespace Akti\Models;`.
- Construtor recebe `\PDO $db`.
- Métodos CRUD: `create()`, `readAll()`, `readOne()`, `update()`, `delete()`.

### Passo 3 — Controller
- Criar `app/controllers/SupplierController.php`.
- `namespace Akti\Controllers;` + `use Akti\Models\Supplier;`.
- Estender `BaseController`.
- Métodos: `index()`, `create()`, `store()`, `edit()`, `update()`, `delete()`.
- Checagem de permissão no início de cada método.

### Passo 4 — View
- Criar `app/views/suppliers/index.php` (e demais).
- Include de `header.php` / `footer.php` para layout.
- Usar `e()` para escape de dados.
- Layout responsivo com Bootstrap 5.

### Passo 5 — Rotas
- Adicionar entrada em `app/config/routes.php`.
- Definir `controller`, `default_action`, `public`, `actions`.

### Passo 6 — Menu e Permissões
- Adicionar item em `app/config/menu.php`.
- Configurar permissão no grupo de usuários em `app/views/users/groups.php`.

---

## 11. Segurança

### 11.1 Proteções Obrigatórias

| Ameaça           | Proteção                                                |
|------------------|---------------------------------------------------------|
| SQL Injection    | Prepared statements em **toda** query                   |
| XSS              | `e()`, `eAttr()`, `eJs()`, `eNum()`, `eUrl()` nas views|
| CSRF             | `csrf_field()` em forms, `X-CSRF-TOKEN` header em AJAX  |
| Brute Force      | LoginAttempt (3 fails → reCAPTCHA, 5+ → block 30min)   |
| Path Traversal   | Validar e sanitizar nomes de arquivo em uploads         |
| Session Fixation | Regenerar session ID após login                         |

### 11.2 Checklist ao Criar/Modificar Código

- [ ] Inputs do usuário sanitizados antes de usar
- [ ] Queries usam prepared statements
- [ ] Views escapam dados com `e()`
- [ ] Formulários incluem `csrf_field()`
- [ ] Uploads validam tipo/extensão e usam diretório do tenant
- [ ] Permissões verificadas no controller

Consultar `.github/instructions/security.md` para regras detalhadas.

---

## 12. Testes

### 12.1 Estrutura

```
tests/
├── bootstrap.php       # Setup do ambiente de teste
├── TestCase.php         # Classe base
├── Unit/               # Testes unitários (Models, Services, Utils)
├── Integration/        # Testes de integração (fluxos completos)
├── Pages/              # Testes de páginas/rotas
└── Security/           # Testes de segurança
```

### 12.2 Convenções

- Classes de teste: `NomeClasseTest.php` (sufixo `Test`).
- Métodos de teste: `test_descricao_do_cenario()` (snake_case com prefixo `test_`).
- Executar: `vendor/bin/phpunit`.
- **Nunca** criar testes que verifiquem existência de arquivos `.sql`.

### 12.3 Quando Criar Testes

- Novo model com regra de negócio → teste unitário.
- Novo endpoint/rota → teste de página.
- Correção de bug → teste de regressão.
- Lógica de segurança → teste em `Security/`.

---

## 13. Multi-Tenant

- **Master DB:** `akti_master` (tenant_clients, login_attempts, ip_blacklist).
- **Client DBs:** `akti_<clientname>` (isolado por tenant).
- **Resolução:** Subdomínio → `TenantManager` → Session.
- **Uploads:** `TenantManager::getTenantUploadBase()` → `assets/uploads/{db_name}/`.
- **Módulos:** `ModuleBootloader` verifica `enabled_modules` (JSON) por tenant.

Toda tabela de dados do cliente deve incluir `tenant_id` com FK para `tenants(id)`.

---

## 14. Instruções Complementares

Detalhes específicos por domínio estão em **`.github/instructions/`**:

| Arquivo                                   | Conteúdo                                                     |
|------------------------------------------|--------------------------------------------------------------|
| `architecture.md`                         | Padrões PSR-4, Multi-Tenant, estrutura Application/Router    |
| `security.md`                             | Sanitização, escape, CSRF, IpGuard, rate-limiting            |
| `database.md`                             | Procedimentos com banco e migrations                         |
| `pipeline.md`                             | Pipeline Kanban (8 etapas de produção)                       |
| `events.md`                               | EventDispatcher (listen, dispatch, forget)                   |
| `extras.md`                               | Frontend, componentes visuais e módulos auxiliares            |
| `ui-ux.md`                                | Padrões de UI (cards, grids, CTA, progressive disclosure)    |
| `modal-style.md`                          | Padrões SweetAlert2 (toasts, confirms, loading)              |
| `modulo-grade_categoria_subcategoria.md`  | Grades e herança de categorias/subcategorias de produtos     |
| `upload.md`                               | Upload de arquivos por tenant                                |
| `modulo-financeiro.md`                    | Módulo financeiro (Service Layer, OFX, installments)         |
| `Bootloader.md`                           | ModuleBootloader e feature flags por tenant                  |
| `funcoes.md`                              | Regras para criação e alteração de funções PHP               |
| `nodejs-api.md`                           | API Node.js (Express, Sequelize, multi-tenant pooling)       |
| `modulo-payment-gateways.md`              | Integrações com gateways de pagamento                        |

---

## Fluxo de Desenvolvimento — Resumo Rápido

```
1. git status → commit pendências com "inicio_<descricao>"
2. Analisar requisito → quebrar em tarefas
3. Se altera banco → USAR SKILL sql-migration → gerar /sql/update_*.sql
4. Implementar: Model → Controller → View → Rotas → Menu
5. Garantir: PSR-4, escape XSS, CSRF, prepared statements, responsivo
6. Rodar testes: vendor/bin/phpunit
7. git add . → git commit -m "<tipo>: <descricao>"
```