# 🔍 Auditoria Geral do Sistema — Akti Gestão em Produção

**Data da Auditoria:** 30 de março de 2026  
**Versão analisada:** Código em produção (branch principal)  
**Auditor:** Auditoria automatizada por análise estática de código  
**Escopo:** Segurança, Arquitetura, Performance, Qualidade de Código, Frontend, Testes, Infraestrutura e Conformidade com boas práticas profissionais.

---

## 📑 Índice

1. [Resumo Executivo](#1-resumo-executivo)
2. [Arquitetura e Estrutura do Projeto](#2-arquitetura-e-estrutura-do-projeto)
3. [Segurança](#3-segurança)
4. [Banco de Dados](#4-banco-de-dados)
5. [Backend PHP — Qualidade de Código](#5-backend-php--qualidade-de-código)
6. [Frontend — HTML/CSS/JS](#6-frontend--htmlcssjs)
7. [API Node.js (Microserviço)](#7-api-nodejs-microserviço)
8. [Testes Automatizados](#8-testes-automatizados)
9. [Multi-Tenancy](#9-multi-tenancy)
10. [Performance](#10-performance)
11. [Infraestrutura e DevOps](#11-infraestrutura-e-devops)
12. [Conformidade com Padrões Profissionais (Benchmark)](#12-conformidade-com-padrões-profissionais-benchmark)
13. [Plano de Ação — Priorização](#13-plano-de-ação--priorização)
14. [Conclusão](#14-conclusão)

---

## 1. Resumo Executivo

### Pontuação Geral: **6.8 / 10**

| Categoria                     | Nota | Status      |
|-------------------------------|------|-------------|
| Arquitetura MVC               | 8/10 | ✅ Bom       |
| Segurança (CSRF/XSS)          | 7/10 | ⚠️ Adequado  |
| Segurança (Infraestrutura)    | 5/10 | 🔴 Atenção   |
| Banco de Dados & Migrations   | 6/10 | ⚠️ Melhorar  |
| Qualidade de Código Backend   | 7/10 | ⚠️ Adequado  |
| Frontend & Responsividade     | 7/10 | ✅ Bom       |
| Testes Automatizados          | 5/10 | 🔴 Atenção   |
| Multi-Tenancy                 | 7/10 | ⚠️ Adequado  |
| Performance                   | 6/10 | ⚠️ Melhorar  |
| Infraestrutura / DevOps       | 4/10 | 🔴 Crítico   |
| Documentação                  | 7/10 | ✅ Bom       |
| API Node.js                   | 7/10 | ✅ Bom       |

### Destaques Positivos
- ✅ Proteção CSRF robusta com grace period e rotação de tokens
- ✅ Autoloader PSR-4 bem implementado (manual + Composer)
- ✅ Sistema de eventos (EventDispatcher) para desacoplamento
- ✅ Router declarativo baseado em mapa de rotas
- ✅ Utilitários de sanitização (Input/Sanitizer) e escape (Escape) bem estruturados
- ✅ Proteção contra brute force (LoginAttempt) com rate limiting
- ✅ IP Guard para proteção contra flood 404
- ✅ ModuleBootloader para controle de módulos por tenant
- ✅ Sessão configurada com flags de segurança (`httponly`, `samesite`, `strict_mode`)

### Pontos Críticos
- 🔴 Credenciais hardcoded no código-fonte (`tenant.php`)
- 🔴 Ausência de headers de segurança HTTP
- 🔴 `Database.php` exibe erro de conexão via `echo`
- 🔴 Arquivos de backup em produção (`.bak`, `.new`)
- 🔴 Cobertura de testes insuficiente
- 🔴 Ausência de CI/CD pipeline
- 🔴 Falta de migrations versionadas automatizadas

---

## 2. Arquitetura e Estrutura do Projeto

### 2.1 Padrão MVC

**Status:** ✅ **Bem implementado com separação clara**

| Camada      | Localização           | Responsabilidade                | Conformidade |
|-------------|-----------------------|---------------------------------|-------------|
| Models      | `app/models/`         | Acesso a dados + Regras de negócio | ✅ Correto   |
| Controllers | `app/controllers/`    | Orquestração, validação, redirect  | ✅ Correto   |
| Views       | `app/views/`          | Renderização HTML                  | ⚠️ Parcial  |
| Core        | `app/core/`           | Infraestrutura (Router, Events)    | ✅ Correto   |
| Services    | `app/services/`       | Lógica de negócio complexa         | ✅ Correto   |
| Middleware  | `app/middleware/`     | Interceptação (CSRF, Auth)         | ✅ Correto   |
| Utils       | `app/utils/`          | Helpers (Input, Sanitizer, Escape) | ✅ Correto   |

**Problemas identificados:**

1. **Views com lógica de banco** — O `header.php` executa queries SQL diretamente para contar pedidos atrasados e buscar permissões de menu. Isso viola o padrão MVC.
   - **Onde:** `app/views/layout/header.php`, linhas 102-170
   - **Severidade:** ⚠️ Média
   - **Correção:** Mover a lógica para um `LayoutService` ou middleware que injete dados no escopo da view.

2. **Controllers criando novas conexões** — Vários controllers instanciam `new Database()` repetidamente.
   - **Onde:** `CustomerController.php`, `OrderController.php`, `ProductController.php`
   - **Severidade:** ⚠️ Média
   - **Correção:** Implementar um Container de Dependências (DI Container) ou Service Locator.

3. **Ausência de camada Repository** — Models misturam queries SQL e regras de negócio.
   - **Severidade:** ⚠️ Baixa (aceitável para o porte do projeto)
   - **Correção (futura):** Separar Repository (SQL) de Model (regras).

### 2.2 Router

**Status:** ✅ **Bem implementado**

- Mapa de rotas declarativo em `app/config/routes.php` (760 linhas, ~30+ páginas mapeadas)
- Suporte a actions mapeadas, allow_unmapped, redirect declarativo
- Tratamento de 404 com IP Guard
- Página pública vs autenticada declarada na configuração

**Melhoria sugerida:**
- Adicionar suporte a HTTP method-based routing (ex: `POST /customers` vs `GET /customers`) em vez de depender do parâmetro `?action=`.

### 2.3 Autoloader PSR-4

**Status:** ✅ **Correto e redundante (boa prática)**

- Autoloader manual em `app/bootstrap/autoload.php`
- Autoloader do Composer em `vendor/autoload.php`
- Mapeamento consistente: `Akti\Controllers\` → `app/controllers/`

### 2.4 Sistema de Eventos

**Status:** ✅ **Bem implementado**

- `EventDispatcher` estático com registro de listeners via `listen()`
- Fire-and-forget: listeners que falham não quebram o fluxo
- Convenção de nomes: `camada.entidade.acao`
- Usado em Models (`model.user.created`, `model.order.created`)
- Usado em Middleware (`middleware.csrf.failed`)

**Melhoria sugerida:**
- Adicionar prioridade aos listeners
- Suporte a listeners assíncronos (queue-based) para operações pesadas

---

## 3. Segurança

### 3.1 Proteção CSRF

**Status:** ✅ **Robusto**

| Aspecto                     | Implementação        | Nota |
|-----------------------------|---------------------|------|
| Geração de token            | `random_bytes(32)`  | ✅    |
| Validação timing-safe       | `hash_equals()`     | ✅    |
| Grace period                | 5 min para token anterior | ✅    |
| Rotação automática          | A cada 30 min       | ✅    |
| Campo em formulários        | `csrf_field()`      | ✅    |
| Meta tag para AJAX          | `csrf_meta()`       | ✅    |
| Log de falhas               | `storage/logs/security.log` | ✅    |
| Rotas isentas (webhooks)    | Configurável        | ✅    |
| Middleware centralizado     | `CsrfMiddleware`    | ✅    |

### 3.2 Proteção contra XSS

**Status:** ⚠️ **Parcialmente implementado**

| Aspecto                     | Status              |
|-----------------------------|---------------------|
| Escape helpers (`e()`, `eAttr()`, `eJs()`) | ✅ Existentes |
| Sanitizer de entrada (`Input::post()`) | ✅ Existente |
| Uso consistente nas views   | ⚠️ **Inconsistente** |

**Problemas identificados:**

1. **Uso inconsistente de escape nas views** — Algumas views usam `e()` para escape, mas outras usam `htmlspecialchars` diretamente ou nenhum escape.
   - **Exemplo no `header.php`:** `$_SESSION['user_name'] ?? 'Visitante'` é exibido sem escape (linha 401).
   - **Severidade:** ⚠️ Média
   - **Correção:** Auditar todas as views e substituir por `e()`.

2. **JavaScript inline com `addslashes` em vez de `eJs()`** — A view de clientes usa `addslashes()` para injetar mensagens flash em JavaScript.
   - **Onde:** `app/views/customers/index.php`, linhas 24-27
   - **Severidade:** ⚠️ Média
   - **Correção:** Usar `eJs()` para contexto JavaScript.

### 3.3 Proteção contra SQL Injection

**Status:** ✅ **Bem protegido**

- Todas as queries observadas usam **prepared statements** via PDO
- `PDO::ATTR_ERRMODE` configurado como `ERRMODE_EXCEPTION`
- Nenhum uso direto de variáveis em queries SQL encontrado
- Alguns usos de `$this->conn->exec()` em operações fixas (sem input do usuário) — aceitável

### 3.4 Autenticação e Sessão

**Status:** ⚠️ **Adequado, com pontos de melhoria**

| Aspecto                           | Status |
|-----------------------------------|--------|
| Cookies `HttpOnly`                | ✅      |
| Cookies `SameSite=Strict`         | ✅      |
| Cookies `Secure` (HTTPS)          | ✅ (condicional) |
| `use_strict_mode`                 | ✅      |
| `use_only_cookies`                | ✅      |
| Session name customizado          | ✅ `AKTI_SID` |
| Timeout por inatividade           | ✅ Configurável |
| Proteção brute force              | ✅ LoginAttempt |
| Regeneração de session ID (login) | ❌ **NÃO ENCONTRADO** |
| Password hashing                  | ✅ `PASSWORD_BCRYPT` |
| Password policy (força)           | ⚠️ Mínimo 6 chars |

**Problemas Críticos:**

1. **🔴 Credenciais hardcoded no código-fonte:**
   ```php
   // app/config/tenant.php, linha 72
   'password' => getenv('AKTI_DB_PASS') ?: 'kP9!vR2@mX6#zL5$',
   ```
   Embora haja fallback para variáveis de ambiente, a senha padrão está no código-fonte que pode estar versionado.
   - **Severidade:** 🔴 Crítica
   - **Correção:** Remover senhas hardcoded. Usar APENAS variáveis de ambiente. Falhar com exceção se não configurado.

2. **🔴 Mesmo padrão em `IpGuard.php`:**
   ```php
   // app/models/IpGuard.php, linha 57
   $pass = getenv('AKTI_DB_PASS') ?: 'kP9!vR2@mX6#zL5$';
   ```
   - **Severidade:** 🔴 Crítica

3. **❌ `session_regenerate_id()` não encontrado no login:**
   O método `User::login()` não regenera o ID da sessão após autenticação bem-sucedida.
   - **Severidade:** 🔴 Alta (Session Fixation attack)
   - **Correção:** Chamar `session_regenerate_id(true)` após login bem-sucedido.

4. **⚠️ Política de senha fraca:**
   Apenas 6 caracteres mínimos. Não há verificação de complexidade.
   - **Severidade:** ⚠️ Média
   - **Correção:** Exigir mínimo 8 chars, pelo menos 1 maiúscula, 1 número e 1 caractere especial.

### 3.5 Headers de Segurança HTTP

**Status:** 🔴 **Ausentes**

Os seguintes headers de segurança **NÃO foram encontrados** em nenhum lugar do código PHP:

| Header                          | Status | Impacto |
|---------------------------------|--------|---------|
| `X-Content-Type-Options: nosniff` | ❌ Ausente | Previne MIME type sniffing |
| `X-Frame-Options: DENY`        | ❌ Ausente | Previne clickjacking |
| `Content-Security-Policy`       | ❌ Ausente | Previne XSS e injeção de recursos |
| `Strict-Transport-Security`     | ❌ Ausente | Força HTTPS |
| `Referrer-Policy`               | ❌ Ausente | Controla vazamento de referrer |
| `Permissions-Policy`            | ❌ Ausente | Controla APIs do navegador |

**Correção:**
Adicionar no `index.php` ou no webserver (Nginx/Apache):
```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 0'); // Obsoleto, mas não prejudica
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
// HSTS apenas em produção com HTTPS:
// header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

### 3.6 Exposição de Informações Sensíveis

**Status:** 🔴 **Problemas encontrados**

1. **`Database.php` expõe erro de conexão via `echo`:**
   ```php
   // app/config/database.php, linha 41
   echo 'Erro na conexão: ' . $exception->getMessage();
   ```
   Mensagens PDO podem conter hostname, porta, nome do banco.
   - **Severidade:** 🔴 Alta
   - **Correção:** Lançar exceção (`throw $exception`) ou logar e retornar null.

2. **Arquivo `.env` quase vazio:**
   As configurações de banco estão hardcoded em vez de no `.env`.
   - **Severidade:** ⚠️ Média

3. **Arquivos de backup no diretório de controllers:**
   - `app/controllers/FinancialController.php.bak`
   - `app/controllers/FinancialController.php.new`
   Podem conter código legado com vulnerabilidades.
   - **Severidade:** ⚠️ Média
   - **Correção:** Remover imediatamente. Usar Git para versionamento.

4. **Credenciais de teste no `phpunit.xml`:**
   ```xml
   <env name="AKTI_TEST_USER_PASSWORD" value="admin123" />
   ```
   O `phpunit.xml` está no repositório com senha real.
   - **Severidade:** ⚠️ Média
   - **Correção:** Usar `phpunit.xml.dist` no repo e `.gitignore` no `phpunit.xml`.

### 3.7 Rate Limiting

**Status:** ✅ **Implementado em duas camadas**

- **Sessão:** `RateLimitMiddleware::check()` — rápido, sem DB
- **Banco de dados:** `RateLimitMiddleware::checkWithDb()` — robusto, cross-session
- **API Node.js:** `express-rate-limit` + `helmet`

**Melhoria sugerida:**
- Aplicar rate limiting no endpoint de login PHP (além do LoginAttempt)
- Adicionar rate limiting para endpoints de exportação (CSV/XLS)

---

## 4. Banco de Dados

### 4.1 Conexão e Configuração

**Status:** ⚠️ **Funcional, mas com problemas**

| Aspecto                           | Status |
|-----------------------------------|--------|
| PDO com prepared statements       | ✅      |
| Charset utf8mb4                   | ✅      |
| ERRMODE_EXCEPTION                 | ✅      |
| Connection pooling                | ❌ Ausente |
| Singleton / DI para conexão       | ❌ Ausente |
| Tratamento de erro no Database    | 🔴 `echo` |

**Problemas:**

1. **Múltiplas instâncias de Database por request:**
   Cada controller chama `new Database()` e `getConnection()`, criando múltiplas conexões PDO.
   ```php
   // OrderController.php - cria 1 conexão no construtor
   // Depois cria OUTRA no método create():
   $database = new Database();
   $db = $database->getConnection();
   ```
   - **Severidade:** ⚠️ Média (desperdício de recursos)
   - **Correção:** Implementar Singleton na classe Database ou usar DI Container.

2. **`Database::getConnection()` retorna null em caso de erro:**
   Se a conexão falhar, o código continua com `$this->conn = null`, causando Fatal Error em queries.
   - **Severidade:** 🔴 Alta
   - **Correção:** Lançar exceção em vez de `echo`.

### 4.2 Migrations / SQL Updates

**Status:** ⚠️ **Processo manual**

- Diretório `sql/` contém apenas 2 arquivos de migration
- Pasta `sql/prontos/` para migrations já aplicadas
- Sem sistema de versionamento automático (tipo Phinx, Doctrine Migrations)
- `Stock.php` executa `ALTER TABLE` diretamente no código PHP — violação grave

**Problemas:**

1. **Schema alterations no código PHP:**
   ```php
   // app/models/Stock.php, linhas 793-804
   $this->conn->exec("ALTER TABLE warehouses ADD COLUMN is_default ...");
   $this->conn->exec("ALTER TABLE orders ADD COLUMN stock_warehouse_id ...");
   ```
   - **Severidade:** 🔴 Alta
   - **Correção:** Mover para arquivos SQL de migration na pasta `/sql`.

2. **Ausência de sistema de migration automatizado:**
   - **Severidade:** ⚠️ Média
   - **Correção:** Implementar sistema de migrations (mesmo que simples) que rastreie quais migrations foram aplicadas.

### 4.3 Transações

**Status:** ⚠️ **Uso inconsistente**

- Algumas operações críticas (importação, criação de pedido com itens) não usam transações explícitas
- Se um item do pedido falhar, o pedido fica em estado inconsistente

**Correção:** Envolver operações multi-tabela em `beginTransaction()` / `commit()` / `rollback()`.

---

## 5. Backend PHP — Qualidade de Código

### 5.1 Namespaces e Autoloading

**Status:** ✅ **Correto**

- Todos os controllers: `namespace Akti\Controllers;`
- Todos os models: `namespace Akti\Models;`
- Classes globais (Database, TenantManager, SessionGuard) via `classmap`
- PSR-4 implementado corretamente

### 5.2 Sanitização de Entrada

**Status:** ✅ **Bem estruturado**

- `Input::post()`, `Input::get()` com sanitização automática via `Sanitizer`
- Suporte a tipos: string, int, float, email, enum, bool, date, intArray
- `Validator` encadeável com mensagens em PT-BR

**Melhoria sugerida:**
- Adicionar validação de upload de arquivo (tipo MIME, tamanho) centralizada

### 5.3 Escape de Saída

**Status:** ⚠️ **Parcialmente consistente**

- Funções globais `e()`, `eAttr()`, `eJs()`, `eNum()`, `eUrl()` disponíveis
- Nem todas as views utilizam consistentemente

### 5.4 Padrões de Código

| Aspecto                    | Status |
|----------------------------|--------|
| Tipo hinting               | ⚠️ Parcial (mistura PHP 7.4 e 8.x) |
| Return types               | ⚠️ Parcial |
| Documentação PHPDoc        | ✅ Bom em Models e Core |
| Código morto               | ⚠️ Arquivos .bak/.new |
| Magic numbers              | ⚠️ Alguns hardcoded |
| Single Responsibility      | ⚠️ Controllers grandes (CustomerController: 2398 linhas) |

**Problemas:**

1. **Controllers monolíticos:**
   - `CustomerController.php`: 2398 linhas
   - `ProductController.php`: 1194 linhas
   - **Correção:** Extrair lógica de importação para `CustomerImportService`, exportação para `CustomerExportService`, etc.

2. **Model com propriedades públicas:**
   ```php
   class User {
       public $id;
       public $name;
       public $email;
       public $password; // ← Senha em texto exposta!
   ```
   - **Severidade:** ⚠️ Média
   - **Correção:** Usar setters/getters ou parâmetros de método.

3. **Sanitização dupla no Model User:**
   ```php
   // User.php, método create()
   $this->name = htmlspecialchars(strip_tags($this->name));
   ```
   A sanitização está sendo feita no Model E no Controller (via Input). Sanitizar no Model com `htmlspecialchars` pode corromper dados no banco.
   - **Severidade:** ⚠️ Média
   - **Correção:** Remover sanitização HTML do Model. A sanitização de entrada deve ser no Controller (Input/Sanitizer), e o escape de saída na View (Escape/e()).

---

## 6. Frontend — HTML/CSS/JS

### 6.1 Tecnologias

| Tecnologia        | Versão    | Status    |
|-------------------|-----------|-----------|
| Bootstrap          | 5.3.0     | ✅ Atual   |
| jQuery            | CDN       | ⚠️ Verificar versão |
| Font Awesome      | 6.4.0     | ✅ Atual   |
| SweetAlert2       | 11.x      | ✅ Atual   |
| Select2           | 4.1.0     | ✅ Atual   |
| Google Fonts (Inter) | —      | ✅ Moderno |

### 6.2 Responsividade

**Status:** ✅ **Bem implementado**

- CSS media queries para mobile (breakpoints Bootstrap)
- Grid system com `col-lg-*` / `col-md-*`
- Views com toggle tabela/cards para diferentes viewports
- Sidebar responsiva com scroll horizontal em mobile

### 6.3 Performance Frontend

| Aspecto                         | Status |
|---------------------------------|--------|
| CSS/JS minificados              | ❌ Ausente |
| Bundling (Webpack/Vite)         | ❌ Ausente |
| Lazy loading de imagens         | ❌ Ausente |
| CDN para bibliotecas            | ✅ Usado |
| Assets versionados (cache bust) | ❌ Ausente |
| Service Worker (PWA)            | ✅ portal-sw.js |

**Correções sugeridas:**
- Adicionar versionamento de assets: `style.css?v=<?= filemtime('assets/css/style.css') ?>`
- Minificar CSS/JS para produção
- Implementar lazy loading para tabelas com muitos dados

### 6.4 CSS Inline

**Status:** ⚠️ **Excessivo**

- Muitas views contêm blocos `<style>` extensos (ex: `customers/index.php` tem ~90 linhas de CSS inline)
- Dificulta manutenção e cache do navegador

**Correção:** Extrair CSS inline para arquivos `.css` na pasta `assets/css/`.

### 6.5 Acessibilidade (A11y)

**Status:** ⚠️ **Parcial**

| Aspecto                    | Status |
|----------------------------|--------|
| `aria-label` em botões     | ⚠️ Parcial |
| `alt` em imagens           | ⚠️ Parcial |
| Contraste de cores         | ⚠️ Não verificado |
| Navegação por teclado      | ⚠️ Parcial |
| `role` attributes          | ✅ Alguns |
| `lang` attribute           | ✅ `pt-br` |

---

## 7. API Node.js (Microserviço)

### 7.1 Estrutura

**Status:** ✅ **Bem organizada**

```
api/src/
  ├── config/        ← Configurações (env, database, cors)
  ├── controllers/   ← Controllers
  ├── middlewares/    ← Rate limiter, error handler, auth
  ├── models/        ← Sequelize models
  ├── routes/        ← Definição de rotas
  ├── services/      ← Lógica de negócio
  └── utils/         ← Utilitários
```

### 7.2 Segurança

| Aspecto                    | Status |
|----------------------------|--------|
| Helmet                     | ✅      |
| CORS configurado           | ✅      |
| Rate limiter               | ✅      |
| JWT auth                   | ✅      |
| Raw body para webhooks     | ✅      |
| Graceful shutdown          | ✅      |

### 7.3 Dependências

| Pacote           | Versão     | Finalidade            | Status   |
|------------------|------------|----------------------|----------|
| express          | ^4.21.2    | Framework web         | ✅ Atual  |
| helmet           | ^8.0.0     | Security headers      | ✅ Atual  |
| cors             | ^2.8.5     | Cross-Origin          | ✅ Atual  |
| jsonwebtoken     | ^9.0.2     | JWT auth              | ✅ Atual  |
| mysql2           | ^3.12.0    | Database driver       | ✅ Atual  |
| sequelize        | ^6.37.5    | ORM                   | ✅ Atual  |
| express-rate-limit | ^7.5.0   | Rate limiting         | ✅ Atual  |

**Melhoria sugerida:**
- Adicionar `npm audit` ao CI/CD
- Considerar migrar para Sequelize v7 quando estável

---

## 8. Testes Automatizados

### 8.1 Cobertura

**Status:** 🔴 **Insuficiente para produção**

| Suíte           | Arquivos  | Tipo              |
|-----------------|-----------|-------------------|
| Pages           | 9 testes  | Testes de integração (HTTP) |
| Unit            | 12 testes | Testes unitários  |

**Problemas:**

1. **Apenas ~21 arquivos de teste** para um sistema com:
   - 28 controllers
   - 43 models
   - 28 services
   - 8 utils
   - Centenas de endpoints

2. **Sem testes para:**
   - Middleware (CSRF, Rate Limit)
   - Sanitização/Validação (Input, Validator — parcial)
   - Router
   - Services (Financial, NF-e, Commission)
   - Gateways de pagamento
   - Multi-tenancy

3. **Testes de integração dependem de servidor rodando:**
   Usam `AKTI_TEST_BASE_URL` e fazem requests HTTP reais.
   - **Correção:** Adicionar testes unitários puros que não dependam de servidor.

### 8.2 Benchmark de Cobertura

| Nível                  | Cobertura Estimada | Alvo Profissional |
|------------------------|-------------------|-------------------|
| Models                 | ~5%               | 80%+              |
| Controllers            | ~10%              | 70%+              |
| Services               | ~0%               | 80%+              |
| Middleware              | ~0%               | 90%+              |
| Utils                  | ~15%              | 90%+              |
| **Total estimado**     | **~8%**           | **70%+**          |

---

## 9. Multi-Tenancy

### 9.1 Implementação

**Status:** ⚠️ **Adequado, com riscos**

| Aspecto                           | Status |
|-----------------------------------|--------|
| Isolamento por banco de dados     | ✅      |
| Resolução por subdomínio          | ✅      |
| Banco master para lookup          | ✅      |
| Session lock por tenant           | ✅      |
| Upload isolado por tenant         | ✅      |
| Limites por tenant (users, products) | ✅   |
| Módulos habilitáveis por tenant   | ✅      |

**Problemas:**

1. **Credenciais do tenant em sessão:**
   Os dados de conexão (incluindo password do banco) são resolvidos e usados pela classe Database a cada request, mas a password está hardcoded como fallback.
   - **Severidade:** 🔴 Alta

2. **Cross-tenant data leakage potential:**
   Se um controller não utilizar a conexão do tenant corretamente, pode acessar dados de outro tenant.
   - **Mitigação:** Cada Database() instância usa o TenantManager para resolver a conexão.

3. **Sem auditoria de acesso cross-tenant:**
   - **Correção:** Adicionar log quando tenant muda durante uma sessão.

---

## 10. Performance

### 10.1 Backend

| Aspecto                            | Status |
|------------------------------------|--------|
| Queries N+1                        | ⚠️ Algumas (header.php) |
| Caching (Redis/Memcached)          | ❌ Ausente |
| Query caching                      | ❌ Ausente |
| Conexões persistentes              | ❌ Ausente |
| Paginação server-side              | ✅ Implementada |
| AJAX para listagens pesadas        | ✅ Implementada |

**Problemas:**

1. **Header.php executa queries pesadas em TODA request:**
   ```php
   // Conta pedidos atrasados
   $stmtActiveH = $dbAlert->query("SELECT o.id, ... FROM orders o LEFT JOIN customers c ...");
   // Busca produtos em produção
   $stmtDelayedProd = $dbAlert->query("SELECT ops.* ... FROM order_production_sectors ops JOIN ...");
   ```
   - **Severidade:** 🔴 Alta (impacto em todas as páginas)
   - **Correção:** Cachear o resultado por 1-5 minutos em sessão ou cache.

2. **Ausência de índices documentados:**
   Sem evidência de criação de índices compostos para queries frequentes.
   - **Correção:** Analisar slow query log e criar índices.

3. **`readAll()` sem paginação em vários models:**
   ```php
   $stmt_products = $productModel->readAll(); // Carrega TODOS os produtos
   $stmt_customers = $customerModel->readAll(); // Carrega TODOS os clientes
   ```
   - **Onde:** `OrderController::create()` carrega todos produtos e clientes para dropdowns
   - **Correção:** Usar autocomplete AJAX com busca paginada (Select2 com source AJAX).

### 10.2 Frontend

| Aspecto                    | Status |
|----------------------------|--------|
| Debounce em busca          | ✅ 350ms |
| Lazy rendering             | ❌ Ausente |
| Virtualização de listas    | ❌ Ausente |
| Gzip/Brotli                | ❓ Depende do webserver |

---

## 11. Infraestrutura e DevOps

### 11.1 Ambiente

**Status:** 🔴 **Sem pipeline de CI/CD**

| Aspecto                        | Status |
|--------------------------------|--------|
| `.gitignore` configurado       | ✅ Parcial |
| `.env` no `.gitignore`         | ✅      |
| CI/CD (GitHub Actions)         | ❌ Ausente |
| Linting automatizado (PHPStan) | ❌ Ausente |
| Code formatting (PHP-CS-Fixer) | ❌ Ausente |
| Docker para desenvolvimento    | ❌ Ausente |
| Monitoramento (Sentry, etc.)   | ❌ Ausente |
| Backup automatizado            | ❌ Ausente |
| Health check endpoint          | ✅ API Node.js |

### 11.2 Logging

**Status:** ⚠️ **Parcial**

| Log                    | Localização                    | Status |
|------------------------|-------------------------------|--------|
| Security (CSRF)        | `storage/logs/security.log`   | ✅      |
| Eventos                | `storage/logs/events.log`     | ✅      |
| Financeiro             | `storage/logs/financial.log`  | ✅      |
| Comissões              | `storage/logs/commission.log` | ✅      |
| Gateways               | `storage/logs/gateways.log`   | ✅      |
| Errors gerais          | PHP error_log                 | ⚠️ Default |
| Requests HTTP          | ❌ Ausente                     | 🔴     |
| Auditoria de dados     | `system_logs` (DB)            | ✅      |

**Melhorias:**
- Implementar log structurado (JSON) para facilitar parsing
- Rotação de logs (logrotate) configurada
- Enviar logs críticos para serviço externo (ELK, CloudWatch, etc.)

### 11.3 Arquivos Desnecessários

| Arquivo                                 | Risco           | Ação         |
|-----------------------------------------|-----------------|-------------|
| `FinancialController.php.bak`           | Exposição de código | ❌ Remover |
| `FinancialController.php.new`           | Exposição de código | ❌ Remover |
| `composer.phar` na raiz               | Desnecessário    | ⚠️ Mover    |
| `scripts/*.php` (debug, diagnostico)   | Ferramentas dev  | ⚠️ Proteger |
| `phpunit.xml` com credenciais          | Exposição       | ⚠️ Gitignore |

---

## 12. Conformidade com Padrões Profissionais (Benchmark)

### 12.1 Comparação com Sistemas Profissionais

| Critério                              | Akti (Atual) | Laravel/Symfony | ERPNext  | SaaS Profissional |
|---------------------------------------|-------------|-----------------|----------|-------------------|
| Injeção de Dependências               | ❌ Manual    | ✅ Container     | ✅        | ✅                 |
| ORM / Query Builder                   | ❌ Raw PDO   | ✅ Eloquent      | ✅        | ✅                 |
| Migrations versionadas                | ⚠️ Manual   | ✅ Automático    | ✅        | ✅                 |
| Caching layer                         | ❌ Ausente   | ✅ Redis         | ✅        | ✅                 |
| Queue / Jobs                          | ❌ Ausente   | ✅ Queue         | ✅        | ✅                 |
| API RESTful completa                  | ⚠️ Parcial  | ✅ Resource      | ✅        | ✅                 |
| Autenticação OAuth2 / SSO             | ❌ Ausente   | ✅ Passport      | ✅        | ✅                 |
| Audit trail completo                  | ⚠️ Parcial  | ✅ Completo      | ✅        | ✅                 |
| Internacionalização (i18n)            | ⚠️ Parcial  | ✅ Completo      | ✅        | ✅                 |
| Rate limiting avançado                | ✅ Bom       | ✅ Middleware     | ✅        | ✅                 |
| Security headers                      | ❌ Ausente   | ✅ Automático    | ✅        | ✅                 |
| HTTPS enforcement                     | ⚠️ Parcial  | ✅ Middleware     | ✅        | ✅                 |
| CI/CD pipeline                        | ❌ Ausente   | ✅ Comum         | ✅        | ✅                 |
| Monitoramento / APM                   | ❌ Ausente   | ✅ Comum         | ✅        | ✅                 |
| Testes (cobertura > 60%)              | ❌ ~8%       | ✅ 80%+          | ✅        | ✅                 |
| Code review / PR process              | ❓ Desconhecido | ✅            | ✅        | ✅                 |
| Backup automatizado                   | ❌ Ausente   | ✅               | ✅        | ✅                 |
| Documentação API (Swagger)            | ❌ Ausente   | ✅               | ✅        | ✅                 |

### 12.2 Pontos onde Akti se destaca

1. **CSRF Protection** — Implementação acima da média com grace period, rotação e logging
2. **Multi-tenancy** — Isolamento por banco de dados é a abordagem mais segura
3. **Event System** — Desacoplamento via eventos é uma boa prática
4. **Brute Force Protection** — LoginAttempt com bloqueio progressivo e reCAPTCHA
5. **ModuleBootloader** — Controle granular de módulos por tenant (flexibilidade comercial)
6. **Sanitização/Escape** — Camadas separadas (Input → Sanitizer → Escape) é a abordagem correta

---

## 13. Plano de Ação — Priorização

### 🔴 Prioridade CRÍTICA (Fazer Imediatamente)

| #  | Item                                          | Esforço | Impacto |
|----|-----------------------------------------------|---------|---------|
| C1 | Remover credenciais hardcoded do código       | Baixo   | Crítico |
| C2 | Corrigir `Database.php` — lançar exceção      | Baixo   | Alto    |
| C3 | Adicionar `session_regenerate_id(true)` no login | Baixo | Alto    |
| C4 | Adicionar headers de segurança HTTP           | Baixo   | Alto    |
| C5 | Remover arquivos .bak / .new                  | Trivial | Médio   |
| C6 | Mover `ALTER TABLE` do Stock.php para SQL migration | Médio | Alto |

### ⚠️ Prioridade ALTA (Próximos 30 dias)

| #  | Item                                          | Esforço | Impacto |
|----|-----------------------------------------------|---------|---------|
| A1 | Cachear queries do header.php                 | Médio   | Alto    |
| A2 | Implementar DI/Singleton para Database        | Médio   | Médio   |
| A3 | Remover sanitização HTML do Model User        | Baixo   | Médio   |
| A4 | Política de senha mais forte                  | Baixo   | Médio   |
| A5 | Substituir `addslashes` por `eJs()` nas views | Baixo   | Médio   |
| A6 | Proteger diretório `scripts/` via .htaccess   | Baixo   | Alto    |
| A7 | Usar `phpunit.xml.dist` e gitignore `phpunit.xml` | Baixo | Médio |

### 📋 Prioridade MÉDIA (Próximos 90 dias)

| #  | Item                                          | Esforço | Impacto |
|----|-----------------------------------------------|---------|---------|
| M1 | Configurar CI/CD (GitHub Actions)             | Médio   | Alto    |
| M2 | Aumentar cobertura de testes (alvo: 40%)      | Alto    | Alto    |
| M3 | Implementar sistema de migrations automático  | Médio   | Alto    |
| M4 | Extrair CSS inline para arquivos .css          | Médio   | Baixo   |
| M5 | Refatorar controllers grandes (< 500 linhas)  | Alto    | Médio   |
| M6 | Implementar caching (session/file/Redis)       | Médio   | Alto    |
| M7 | Documentar API (Swagger/OpenAPI)               | Médio   | Médio   |
| M8 | Versionamento de assets (cache busting)        | Baixo   | Médio   |

### 📌 Prioridade BAIXA (Roadmap Futuro)

| #  | Item                                          | Esforço | Impacto |
|----|-----------------------------------------------|---------|---------|
| B1 | Implementar DI Container completo             | Alto    | Médio   |
| B2 | Adicionar ORM (Doctrine ou Eloquent standalone) | Alto  | Médio   |
| B3 | Implementar Queue/Jobs para processos pesados | Alto    | Alto    |
| B4 | Docker para ambiente de desenvolvimento       | Médio   | Médio   |
| B5 | Monitoramento APM (Sentry, New Relic)         | Médio   | Alto    |
| B6 | Implementar OAuth2/SSO                        | Alto    | Médio   |
| B7 | Content Security Policy (CSP) headers         | Médio   | Médio   |
| B8 | Mover lógica de queries do header.php para Service | Médio | Médio |

---

## 14. Conclusão

O **Akti — Gestão em Produção** demonstra uma base sólida de desenvolvimento com atenção a padrões importantes como MVC, sanitização de dados, proteção CSRF e multi-tenancy. O sistema é funcional e cobre um escopo extenso (ERP completo com CRM, Pipeline, Financeiro, NF-e, Estoque, Portal do Cliente).

### Maturidade do Projeto

| Nível            | Descrição                              | Status |
|------------------|----------------------------------------|--------|
| 1 - Inicial      | Funciona, mas sem processos           | —      |
| 2 - Gerenciado   | Alguns processos definidos            | —      |
| **3 - Definido** | **Processos consistentes, padrões seguidos** | **← Aqui** |
| 4 - Quantificado | Métricas, testes extensivos, CI/CD    | Alvo   |
| 5 - Otimizado    | Melhoria contínua, automação total    | Futuro |

**O sistema está no nível 3 (Definido)** e precisa avançar para o nível 4 (Quantificado) com foco em:
1. Resolver as vulnerabilidades de segurança críticas
2. Aumentar a cobertura de testes
3. Implementar CI/CD
4. Adicionar monitoramento e caching

Com as correções críticas aplicadas e o roadmap de melhorias seguido, o sistema pode alcançar o nível 4 em aproximadamente 3-6 meses.

---

**Documento gerado em:** 30/03/2026  
**Próxima auditoria recomendada:** 30/06/2026
