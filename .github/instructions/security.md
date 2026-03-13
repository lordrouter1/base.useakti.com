# Instruções de Segurança e Tratamento

- Toda alteração de dados de entrada via GET, POST ou REQUEST deve ser acessada via classe `Input` em `Akti\Utils\Input` e não de forma direta.
- Prevenir XSS usando escape helper em views: `e()`, `eAttr()`.
- O banco exige `PDO::prepare` params. Nunca concatenar strings diretas (prevenir Injections).
- Cross-Site Request Forgery protegido mediante middleware (`CsrfMiddleware`) e inclusão da tag no form via `<?= csrf_field() ?>`. Lançamentos AJAX devem usar `X-CSRF-TOKEN`.
- As tentativas de roteamento com log de erro prevê bloqueio progressivo por IP (sistema `IpGuard`).

## Módulo: Segurança — IpGuard (Blacklist Automática por Flood 404)

### Conceito
O sistema detecta automaticamente ataques de varredura (scanners, bots, brute-force de paths) com base na quantidade de requisições 404 por IP dentro de uma janela de tempo. IPs que excedem o threshold são automaticamente adicionados à blacklist.

A proteção opera em **duas camadas**:
1. **PHP (index.php):** No handler `default` do switch de roteamento, o `IpGuard` registra hits 404 e bloqueia IPs que ultrapassem o limite. Se o IP já estiver na blacklist, retorna 403 imediato sem renderizar a view.
2. **Nginx/OpenResty (Lua):** Antes de processar qualquer request, o script Lua consulta a blacklist no banco `akti_master` e retorna 403 para IPs bloqueados. Usa cache em `lua_shared_dict` para minimizar queries.

### Tabelas no Banco de Dados (`akti_master`)
- `ip_404_hits` — Registro de cada hit 404 por IP (path, user-agent, timestamp)
- `ip_blacklist` — IPs bloqueados (com razão, duração, expiração e flag ativo/inativo)

> ⚠️ Estas tabelas ficam no banco **master** (`akti_master`), não nos bancos de tenant.

### Parâmetros de Configuração (constantes em `IpGuard.php`)

| Constante | Valor padrão | Descrição |
|-----------|-------------|-----------|
| `THRESHOLD` | 30 | Número máximo de 404s na janela de tempo |
| `WINDOW_SECONDS` | 60 | Janela de tempo em segundos |
| `BLOCK_HOURS` | 24 | Duração do bloqueio em horas (0 = permanente) |
| `MAX_PATH_LENGTH` | 2048 | Tamanho máximo do path armazenado |
| `MAX_UA_LENGTH` | 512 | Tamanho máximo do user-agent armazenado |

### Fluxo Progressivo de Proteção

```
Tentativa 1–2  → Login normal
Tentativa 3–4  → reCAPTCHA obrigatório (se chaves configuradas)
Tentativa 5+   → Bloqueio de 30 minutos + reCAPTCHA mantido visível
```

### Detalhamento do Fluxo no Controller (`UserController::login()`)

1. **Obter IP real** — `LoginAttempt::getClientIp()` detecta o IP considerando headers de proxy (`CF-Connecting-IP`, `X-Forwarded-For`, `X-Real-IP`, fallback `REMOTE_ADDR`).
2. **Verificar bloqueio** — `checkLockout($ip, $email)` consulta se há ≥ 5 falhas na janela de 10 min. Se bloqueado:
   - Loga evento `LOGIN_BLOCKED` via `Logger`
   - Retorna erro com tempo restante: "Aguarde N minuto(s) e tente novamente."
   - Mantém reCAPTCHA visível para quando o bloqueio expirar
   - O formulário de login é desabilitado (submit bloqueado via `$isBlocked`)
3. **Verificar reCAPTCHA** — `requiresCaptcha($ip, $email)` verifica se há ≥ 3 falhas recentes. Se sim e chaves estão configuradas:
   - Widget reCAPTCHA v2 é renderizado no formulário
   - Resposta é validada server-side via API `siteverify` do Google
   - Falha no captcha retorna erro e loga `LOGIN_CAPTCHA_FAIL`
4. **Tentativa de autenticação** — Se passou nas verificações acima:
   - **Sucesso:** registra tentativa com `success=1`, limpa falhas anteriores do par IP+email, purga registros antigos, regenera session ID
   - **Falha:** registra tentativa com `success=0`, recalcula estado de bloqueio/captcha, exibe mensagem genérica ("Credenciais inválidas") para não vazar existência de e-mails

### Tabela `login_attempts`

```sql
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,       -- Suporta IPv6
    email VARCHAR(191) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) NOT NULL DEFAULT 0 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Índices

| Nome | Colunas | Uso |
|------|---------|-----|
| `idx_la_ip_email_date` | `ip_address, email, attempted_at` | Consultas de rate-limit (countRecentFailures, checkLockout) |
| `idx_la_attempted_at` | `attempted_at` | Limpeza de registros antigos (purgeOld) |

### Integração com reCAPTCHA v2

- As chaves são configuradas via **variáveis de ambiente** (prioridade) ou constantes na classe:
  - `AKTI_RECAPTCHA_SITE_KEY` (env) → `LoginAttempt::RECAPTCHA_SITE_KEY` (fallback)
  - `AKTI_RECAPTCHA_SECRET_KEY` (env) → `LoginAttempt::RECAPTCHA_SECRET_KEY` (fallback)
- Se ambas as chaves estiverem **vazias**, o reCAPTCHA nunca é exibido — o sistema opera apenas com bloqueio temporal
- O script do reCAPTCHA (`recaptcha/api.js`) é carregado condicionalmente apenas quando necessário (`$captchaEnabled` na view)
- Validação server-side usa `file_get_contents` com timeout de 5s. Em caso de falha de rede, a validação é permissiva (não bloqueia o usuário)

### Métodos do Model `LoginAttempt`

| Método | Descrição |
|--------|-----------|
| `record($ip, $email, $success)` | Registra uma tentativa de login (sucesso ou falha) |
| `countRecentFailures($ip, $email)` | Conta falhas na janela de `WINDOW_MINUTES` |
| `checkLockout($ip, $email)` | Retorna `['blocked' => bool, 'remaining_minutes' => int]` |
| `requiresCaptcha($ip, $email)` | `true` se falhas ≥ `CAPTCHA_THRESHOLD` e chaves configuradas |
| `validateCaptcha($response, $ip)` | Valida resposta reCAPTCHA v2 via API Google |
| `clearFailures($ip, $email)` | Remove falhas de um par IP+email (chamado no login bem-sucedido) |
| `purgeOld()` | Remove registros com mais de `CLEANUP_MINUTES` |
| `getClientIp()` | (estático) Detecta IP real considerando proxies |

### Comportamento na View (`login.php`)

- **Estado normal:** formulário padrão de e-mail + senha
- **reCAPTCHA ativo ($showCaptcha):** widget `g-recaptcha` renderizado no formulário
- **Bloqueio ativo ($isBlocked):** mensagem de erro com contagem de minutos restantes, botão de submit desabilitado
- **Mensagens de erro:** sempre genéricas ("Credenciais inválidas") para evitar enumeração de e-mails

### Logging de Eventos

| Evento | Descrição |
|--------|-----------|
| `LOGIN` | Login bem-sucedido |
| `LOGIN_FAIL` | Tentativa de login com credenciais inválidas |
| `LOGIN_BLOCKED` | Tentativa bloqueada por rate-limit (IP+email) |
| `LOGIN_CAPTCHA_FAIL` | reCAPTCHA inválido ou não preenchido |

### Limpeza Automática
- A cada **login bem-sucedido**, `purgeOld()` é chamado automaticamente, removendo registros com mais de 60 minutos
- `clearFailures()` remove apenas as falhas do par IP+email autenticado, mantendo o histórico de outros IPs/emails para auditoria
- Para ambientes de alto tráfego, a purga pode ser movida para um cron job

### Migração
- **Arquivo:** `sql/update_20260309_login_attempts.sql`
- Cria a tabela e os índices de forma idempotente (`IF NOT EXISTS` + verificação em `INFORMATION_SCHEMA`)
- A tabela também está definida em `sql/database.sql` para instalações novas

### Arquivos do Módulo
- `app/models/LoginAttempt.php` — Model com rate-limiting, captcha e limpeza
- `app/controllers/UserController.php` — Integração completa no fluxo de login (POST)
- `app/views/auth/login.php` — Renderização condicional de reCAPTCHA e estados de bloqueio
- `sql/update_20260309_login_attempts.sql` — Migração: tabela + índices
- `sql/database.sql` — Definição da tabela para instalações novas

---

## Módulo: Segurança — Proteção CSRF

### Conceito
O sistema implementa proteção **CSRF (Cross-Site Request Forgery)** em todas as requisições que alteram dados (POST, PUT, PATCH, DELETE). Um token criptograficamente seguro é gerado por sessão, validado automaticamente por middleware, e injetado nos formulários e requisições AJAX.

### Arquitetura

| Componente | Arquivo | Namespace | Responsabilidade |
|------------|---------|-----------|------------------|
| **Security** | `app/core/Security.php` | `Akti\Core` | Geração, validação e log de tokens CSRF |
| **CsrfMiddleware** | `app/middleware/CsrfMiddleware.php` | `Akti\Middleware` | Intercepta requisições e valida o token antes do Router |
| **form_helper** | `app/utils/form_helper.php` | *(sem namespace)* | Funções helper para injeção de token em views |
| **Página 403** | `app/views/errors/403.php` | — | Página de erro personalizada para falhas CSRF |
| **Log de segurança** | `storage/logs/security.log` | — | Registro de todas as falhas de validação |

### Classes

#### `Akti\Core\Security`
- `generateCsrfToken()`: Gera um novo token CSRF e armazena na sessão.
- `validateCsrfToken($token)`: Valida um token CSRF recebido em relação ao armazenado na sessão.
- `getCsrfToken()`: Retorna o token CSRF atual da sessão.

#### `Akti\Middleware\CsrfMiddleware`
- `handle()`: Intercepta a requisição e valida o token CSRF para métodos seguros (POST, PUT, DELETE). Retorna 403 se inválido.
- `addExemptRoute($route)`: Adiciona uma rota à lista de isentas de verificação CSRF.

### Fluxo de Proteção CSRF

1. **Geração do Token**:
   - O token CSRF é gerado na inicialização da sessão pelo método `Security::generateCsrfToken()`.
   - O token é um valor aleatório, único por sessão, e tem um tempo de vida definido (ex: 2 horas).

2. **Injeção do Token**:
   - O token deve ser incluído em todos os formulários que fazem requisições que alteram o estado (POST, PUT, DELETE).
   - Exemplo em um formulário:
     ```php
     <form method="POST" action="/salvar">
         <?= csrf_field() ?>
         <!-- outros campos -->
         <button type="submit">Salvar</button>
     </form>
     ```

3. **Validação do Token**:
   - Ao receber uma requisição, o middleware `CsrfMiddleware` valida o token:
     - Se o método for seguro (POST, PUT, DELETE), o middleware verifica a presença e validade do token CSRF.
     - O token é comparado com o valor armazenado na sessão usando `Security::validateCsrfToken($token)`.
     - Se o token for inválido ou estiver ausente, a requisição é rejeitada com erro 403 (Forbidden).

4. **Exceções**:
   - Algumas rotas podem ser isentas da verificação CSRF (ex: webhooks, APIs públicas).
   - Essas rotas devem ser registradas no middleware usando `addExemptRoute()`.

5. **Logs de Segurança**:
   - Todas as falhas de validação CSRF são registradas em `storage/logs/security.log` para auditoria.

### Como Usar CSRF em Formulários

- Em cada formulário que faz uma requisição POST, inclua o token CSRF usando a função `csrf_field()`:
  ```php
  <form method="POST" action="/atualizar">
      <?= csrf_field() ?>
      <!-- campos do formulário -->
      <button type="submit">Atualizar</button>
  </form>
  ```

- O token será automaticamente incluído como um campo oculto no formulário.

### Como Funciona em Requisições AJAX

- Para requisições AJAX, o token CSRF deve ser incluído no cabeçalho da requisição:
  ```javascript
  $.ajax({
      url: '/api/dados',
      method: 'POST',
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      data: {
          // dados da requisição
      },
      success: function(response) {
          // tratar resposta
      }
  });
  ```

- O token é lido da meta tag `csrf-token` e enviado no cabeçalho `X-CSRF-TOKEN`.

### Exceções e Isenções

- Algumas rotas podem ser isentas da verificação CSRF, como webhooks ou APIs públicas.
- Essas rotas devem ser registradas no middleware CSRF usando o método `addExemptRoute()`.

### Logs de Segurança

- Todas as falhas de validação CSRF são registradas em `storage/logs/security.log` com detalhes da requisição e do erro.

---
### Fluxo de Carregamento

```
index.php
  └── require autoload.php
        ├── spl_autoload_register()  (PSR-4: Akti\Core, Akti\Models, etc.)
        ├── require session.php      (SessionGuard)
        ├── require tenant.php       (TenantManager)
        ├── require database.php     (Database)
        ├── require form_helper.php  (csrf_field, csrf_meta, csrf_token)
        ├── require escape_helper.php (e, eAttr, eJs, eNum, eUrl)
        └── require events.php       (registro de listeners — EventDispatcher já autoloaded)
```

---

## Módulo: Sanitização, Validação e Escape de Saída

### Conceito
O sistema implementa uma estratégia de segurança em **três camadas** para prevenir **SQL Injection**, **XSS (Cross-Site Scripting)** e dados inconsistentes:

1. **Sanitização de Entrada** — Limpa e normaliza dados recebidos do usuário (`Sanitizer`, `Input`)
2. **Validação** — Verifica regras de negócio e acumula erros (`Validator`)
3. **Escape de Saída** — Codifica dados ao exibi-los em HTML/JS/URL (`Escape`, helpers globais)

> ⚠️ **REGRA CRÍTICA — Nunca confiar em dados do usuário**
>
> - **Controllers:** SEMPRE usar `Input::post()` / `Input::get()` em vez de `$_POST` / `$_GET` direto
> - **Views:** SEMPRE usar `e()`, `eAttr()`, `eJs()`, `eNum()` para exibir qualquer dado dinâmico
> - **Models:** SEMPRE usar prepared statements com parâmetros (`?` ou `:nome`). Nunca concatenar strings em SQL
> - **Nunca** usar `htmlspecialchars()` diretamente — usar os helpers de escape

### Arquitetura

| Componente | Arquivo | Namespace | Responsabilidade |
|------------|---------|-----------|------------------|
| **Sanitizer** | `app/utils/Sanitizer.php` | `Akti\Utils` | Sanitização estática de valores (string, int, float, email, etc.) |
| **Validator** | `app/utils/Validator.php` | `Akti\Utils` | Validação encadeável com acúmulo de erros (pt-BR) |
| **Input** | `app/utils/Input.php` | `Akti\Utils` | Wrapper seguro para `$_POST`/`$_GET` com sanitização automática |
| **Escape** | `app/utils/Escape.php` | `Akti\Utils` | Escape de saída para HTML, atributos, JS, URL, CSS |
| **escape_helper.php** | `app/utils/escape_helper.php` | *(sem namespace)* | Funções globais `e()`, `eAttr()`, `eJs()`, `eNum()`, `eUrl()` |

### Fluxo de Dados Seguro

```
[Requisição HTTP]
       │
       ▼
  Controller ──── Input::post('name')         ← SANITIZAÇÃO automática
       │         Input::post('price', 'float')
       │         Input::post('role', 'enum', 'user', ['admin','user'])
       │
       ▼
  Validator ──── $v->required('name', $name, 'Nome')  ← VALIDAÇÃO
       │         $v->email('email', $email, 'E-mail')
       │         $v->maxLength('name', $name, 191, 'Nome')
       │
       ▼
  Model ────── $stmt->execute([':name' => $name])  ← PREPARED STATEMENT
       │
       ▼
  View ──────── <?= e($customer['name']) ?>    ← ESCAPE de saída
                <input value="<?= eAttr($v) ?>">
```

---

### Classe: `Akti\Utils\Sanitizer`

Classe **estática** com métodos de sanitização por tipo. Não lança exceções — sempre retorna um valor limpo ou o default.

#### Métodos — Tipos Primitivos

| Método | Entrada | Retorno | Descrição |
|--------|---------|---------|-----------|
| `string($value, $default)` | `mixed` | `string` | `trim()` + `strip_tags()` |
| `richText($value, $allowedTags)` | `mixed` | `string` | `strip_tags()` preservando tags permitidas |
| `int($value, $default)` | `mixed` | `int\|null` | `FILTER_VALIDATE_INT` |
| `float($value, $default)` | `mixed` | `float\|null` | Aceita formato PT-BR (`1.234,56`) |
| `bool($value)` | `mixed` | `bool` | Aceita `1`, `'true'`, `'on'`, `'yes'`, `'sim'` |

#### Métodos — Tipos Específicos

| Método | Entrada | Retorno | Descrição |
|--------|---------|---------|-----------|
| `email($value, $default)` | `mixed` | `string` | `trim` + `lowercase` + `FILTER_SANITIZE_EMAIL` |
| `phone($value)` | `mixed` | `string` | Mantém apenas dígitos, `+`, `(`, `)`, `-`, espaço |
| `document($value)` | `mixed` | `string` | Apenas dígitos (CPF/CNPJ) |
| `cep($value)` | `mixed` | `string` | Apenas dígitos |
| `url($value)` | `mixed` | `string` | `FILTER_SANITIZE_URL` |
| `date($value, $default)` | `mixed` | `string\|null` | Valida formato `Y-m-d` |
| `datetime($value, $default)` | `mixed` | `string\|null` | Valida formato `Y-m-d H:i:s` |
| `slug($value)` | `mixed` | `string` | Lowercase, sem acentos, apenas `a-z`, `0-9`, `-` |
| `filename($value)` | `mixed` | `string` | Remove path traversal, caracteres perigosos |
| `json($value, $default)` | `mixed` | `string\|null` | Decode + re-encode para validar formato |

#### Métodos — Arrays e Helpers

| Método | Entrada | Retorno | Descrição |
|--------|---------|---------|-----------|
| `intArray($value)` | `mixed` | `array<int>` | Array de inteiros válidos |
| `stringArray($value)` | `mixed` | `array<string>` | Array de strings sanitizadas |
| `enum($value, $allowed, $default)` | `mixed` | `mixed` | Valor se está na whitelist, senão `$default` |

---

### Classe: `Akti\Utils\Input`

Wrapper **estático** que acessa `$_POST` / `$_GET` / `$_REQUEST` aplicando sanitização automática via `Sanitizer`. **Substitui completamente o acesso direto a superglobais.**

#### Métodos Principais

| Método | Descrição |
|--------|-----------|
| `post($key, $type, $default, $options)` | Obtém valor de `$_POST` com sanitização |
| `get($key, $type, $default, $options)` | Obtém valor de `$_GET` com sanitização |
| `request($key, $type, $default, $options)` | Obtém valor de `$_REQUEST` com sanitização |
| `hasPost($key)` | Verifica se campo existe e não é vazio em `$_POST` |
| `hasGet($key)` | Verifica se campo existe e não é vazio em `$_GET` |
| `allPost($fields)` | Múltiplos campos de `$_POST` de uma vez |
| `allGet($fields)` | Múltiplos campos de `$_GET` de uma vez |
| `postRaw($key, $default)` | Valor sem sanitização (senhas, rich text) |
| `getRaw($key, $default)` | Valor sem sanitização de `$_GET` |
| `postArray($key)` | Array de `$_POST` (ex: `grades[]`, `items[]`) |
| `getArray($key)` | Array de `$_GET` |

#### Tipos de Sanitização (parâmetro `$type`)

| Tipo | Sanitizer chamado | Exemplo |
|------|-------------------|---------|
| `'string'` (default) | `Sanitizer::string()` | `Input::post('name')` |
| `'int'` / `'integer'` | `Sanitizer::int()` | `Input::post('id', 'int')` |
| `'float'` / `'decimal'` / `'number'` | `Sanitizer::float()` | `Input::post('price', 'float')` |
| `'bool'` / `'boolean'` | `Sanitizer::bool()` | `Input::post('active', 'bool')` |
| `'email'` | `Sanitizer::email()` | `Input::post('email', 'email')` |
| `'phone'` | `Sanitizer::phone()` | `Input::post('phone', 'phone')` |
| `'document'` / `'cpf'` / `'cnpj'` | `Sanitizer::document()` | `Input::post('cpf', 'document')` |
| `'cep'` | `Sanitizer::cep()` | `Input::post('cep', 'cep')` |
| `'url'` | `Sanitizer::url()` | `Input::post('website', 'url')` |
| `'date'` | `Sanitizer::date()` | `Input::post('deadline', 'date')` |
| `'datetime'` | `Sanitizer::datetime()` | `Input::post('scheduled', 'datetime')` |
| `'slug'` | `Sanitizer::slug()` | `Input::post('slug', 'slug')` |
| `'filename'` | `Sanitizer::filename()` | `Input::post('file', 'filename')` |
| `'json'` | `Sanitizer::json()` | `Input::post('config', 'json')` |
| `'enum'` | `Sanitizer::enum()` | `Input::post('role', 'enum', 'user', ['admin', 'user'])` |
| `'intArray'` | `Sanitizer::intArray()` | `Input::post('ids', 'intArray')` |
| `'stringArray'` | `Sanitizer::stringArray()` | `Input::post('names', 'stringArray')` |
| `'raw'` | *(nenhum)* | `Input::post('password', 'raw')` |

#### Exemplos de Uso

```php
use Akti\Utils\Input;

// Campos individuais com tipo
$name     = Input::post('name');                       // string sanitizada
$email    = Input::post('email', 'email');              // email sanitizado
$price    = Input::post('price', 'float');              // float (aceita PT-BR)
$id       = Input::get('id', 'int');                    // inteiro ou null
$role     = Input::post('role', 'enum', 'user', ['admin', 'user']); // whitelist
$password = Input::postRaw('password');                 // raw (sem sanitizar)

// Campos em lote
$data = Input::allPost([
    'name'  => 'string',
    'email' => 'email',
    'price' => 'float',
    'phone' => 'phone',
]);

// Verificação de existência
if (Input::hasPost('discount')) {
    $discount = Input::post('discount', 'float', 0.0);
}

// Arrays
$grades = Input::postArray('grades');     // retorna array ou []
$ids    = Input::post('ids', 'intArray'); // retorna [1, 2, 3]
```

---

### Classe: `Akti\Utils\Validator`

Classe de validação **encadeável** que acumula erros por campo. Mensagens em **português do Brasil**. Apenas o primeiro erro por campo é registrado.

#### Métodos de Estado

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `fails()` | `bool` | `true` se existem erros |
| `passes()` | `bool` | `true` se não há erros |
| `errors()` | `array` | `['campo' => 'mensagem', ...]` |
| `error($field)` | `string\|null` | Primeiro erro de um campo |
| `addError($field, $message)` | `self` | Adiciona erro manualmente |
| `reset()` | `self` | Limpa todos os erros |

#### Regras de Validação (encadeáveis, retornam `$this`)

| Método | Parâmetros | Descrição |
|--------|-----------|-----------|
| `required($field, $value, $label)` | campo, valor, rótulo | Campo obrigatório |
| `minLength($field, $value, $min, $label)` | | Comprimento mínimo |
| `maxLength($field, $value, $max, $label)` | | Comprimento máximo |
| `email($field, $value, $label)` | | E-mail válido |
| `integer($field, $value, $label)` | | Inteiro válido |
| `numeric($field, $value, $label)` | | Valor numérico |
| `min($field, $value, $min, $label)` | | Valor mínimo |
| `max($field, $value, $max, $label)` | | Valor máximo |
| `inList($field, $value, $allowed, $label)` | | Valor na lista permitida |
| `date($field, $value, $label)` | | Data `Y-m-d` válida |
| `url($field, $value, $label)` | | URL válida |
| `regex($field, $value, $pattern, $label, $message)` | | Regex customizado |
| `cpf($field, $value, $label)` | | CPF com dígito verificador |
| `cnpj($field, $value, $label)` | | CNPJ com dígito verificador |
| `cpfOrCnpj($field, $value, $label)` | | CPF ou CNPJ (detecta pelo tamanho) |

#### Helpers Estáticos

| Método | Descrição |
|--------|-----------|
| `Validator::isValidCpf($cpf)` | Valida CPF (dígitos verificadores) |
| `Validator::isValidCnpj($cnpj)` | Valida CNPJ (dígitos verificadores) |

#### Exemplo Completo em Controller

```php
use Akti\Utils\Input;
use Akti\Utils\Validator;

public function store() {
    $name  = Input::post('name');
    $email = Input::post('email', 'email');
    $price = Input::post('price', 'float', 0);

    $v = new Validator();
    $v->required('name', $name, 'Nome')
      ->maxLength('name', $name, 191, 'Nome')
      ->required('email', $email, 'E-mail')
      ->email('email', $email, 'E-mail')
      ->required('price', $price, 'Preço')
      ->min('price', $price, 0.01, 'Preço');

    if ($v->fails()) {
        $_SESSION['errors'] = $v->errors();
        $_SESSION['old'] = $_POST;
        header('Location: ?page=products&action=create');
        exit;
    }
    // Salvar no banco...
}
```

---

### Classe: `Akti\Utils\Escape`

Classe **estática** de escape de saída. Cada método corresponde a um contexto de renderização.

#### Métodos

| Método | Contexto | Uso em View |
|--------|----------|-------------|
| `html($value)` | Conteúdo HTML (entre tags) | `<?= Escape::html($name) ?>` |
| `attr($value)` | Atributos HTML (`value=`, `data-*=`, `alt=`) | `value="<?= Escape::attr($name) ?>"` |
| `js($value)` | JavaScript inline (`<script>`) | `var x = <?= Escape::js($data) ?>;` |
| `url($value)` | Query string / URL | `href="?search=<?= Escape::url($q) ?>"` |
| `css($value)` | CSS inline (`style=`) | `color: <?= Escape::css($color) ?>` |
| `number($value, $decimals, $decSep, $thousSep)` | Números formatados (BR) | `R$ <?= Escape::number($price) ?>` |

---

### Funções Globais de Escape (`escape_helper.php`)

Atalhos para uso direto em views (**sem namespace**, carregados pelo autoload):

| Função | Classe chamada | Contexto |
|--------|---------------|----------|
| `e($value)` | `Escape::html()` | Conteúdo HTML |
| `eAttr($value)` | `Escape::attr()` | Atributos HTML |
| `eJs($value)` | `Escape::js()` | JavaScript inline |
| `eNum($value, $decimals)` | `Escape::number()` | Números formatados |
| `eUrl($value)` | `Escape::url()` | URL / query string |

#### Exemplos de Uso em Views

```php
<!-- Conteúdo HTML -->
<td><?= e($customer['name']) ?></td>
<span class="badge"><?= e($order['status']) ?></span>

<!-- Atributos HTML -->
<input type="text" name="name" value="<?= eAttr($product['name']) ?>">
<button data-id="<?= eAttr($item['id']) ?>" data-name="<?= eAttr($item['name']) ?>">
<img src="<?= eAttr($product['image_path']) ?>" alt="<?= eAttr($product['name']) ?>">

<!-- JavaScript inline -->
<script>
var orderId = <?= eJs($order['id']) ?>;
var config = <?= eJs(['stages' => $stages, 'goals' => $goals]) ?>;
</script>

<!-- Números formatados (BR) -->
<td>R$ <?= eNum($order['total_amount']) ?></td>
<td><?= eNum($quantity, 0) ?> un</td>

<!-- URL -->
<a href="?page=products&search=<?= eUrl($searchTerm) ?>">Buscar</a>
```

---

### Regras Obrigatórias — Sanitização e Escape

#### Para Controllers (Entrada)
1. **NUNCA** acessar `$_POST`, `$_GET` ou `$_REQUEST` diretamente. Sempre usar `Input::post()`, `Input::get()` ou `Input::request()`.
2. **SEMPRE** especificar o tipo correto no `Input::post()`: `'int'` para IDs, `'float'` para preços, `'email'` para e-mails, `'enum'` para valores de whitelist.
3. **SEMPRE** usar `Input::postRaw()` apenas para senhas (que não devem ser sanitizadas antes do hash).
4. **SEMPRE** validar com `Validator` antes de salvar dados no banco.
5. **SEMPRE** adicionar `use Akti\Utils\Input;` e `use Akti\Utils\Validator;` no topo do controller.
6. Para arrays (ex: `grades[]`, `items[]`), usar `Input::postArray('key')` e sanitizar cada item individualmente.
7. Para dados em lote, usar `Input::allPost(['field1' => 'type1', 'field2' => 'type2'])`.

#### Para Views (Saída)
1. **NUNCA** exibir dados do banco ou da sessão sem escape.
2. **NUNCA** usar `htmlspecialchars()` diretamente — sempre usar `e()` ou `eAttr()`.
3. **SEMPRE** usar `e()` para conteúdo HTML (texto entre tags).
4. **SEMPRE** usar `eAttr()` para atributos HTML (`value=`, `data-*=`, `src=`, `alt=`, `title=`, `content=`).
5. **SEMPRE** usar `eJs()` para dados embutidos em `<script>`.
6. **SEMPRE** usar `eNum()` para exibição de valores numéricos formatados.
7. **SEMPRE** usar `eUrl()` para valores inseridos em query strings.
8. Em views, as funções `e()`, `eAttr()`, `eJs()`, `eNum()`, `eUrl()` são globais — não precisam de `use`.

#### Para Models (Banco de Dados)
1. **SEMPRE** usar prepared statements com `?` ou `:parametro`.
2. **NUNCA** concatenar variáveis em strings SQL.
3. Para cláusulas `IN (...)`, usar `array_fill(0, count($ids), '?')` para gerar placeholders.
4. Referências a `$this->table_name` ou `$this->table` (propriedades da classe) são seguras.
5. Para `LIMIT`, usar `intval()` ou `PDO::PARAM_INT`.

#### Para Novos Módulos
1. Ao criar novo Controller, importar `Input` e `Validator`:
   ```php
   use Akti\Utils\Input;
   use Akti\Utils\Validator;
   ```
2. Ao criar nova View, usar `e()`, `eAttr()`, `eJs()`, `eNum()` para toda saída dinâmica.
3. Ao criar novo Model, usar exclusivamente prepared statements.

---
