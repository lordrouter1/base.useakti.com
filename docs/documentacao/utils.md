# Utils (Utilitários)

> Classes e funções utilitárias: escape XSS, validação, sanitização, formatação, JWT, cache simples.

**Total de arquivos:** 17

---

## Índice

- [CurrencyFormatter](#currencyformatter) — `app/utils/CurrencyFormatter.php`
- [CursorPaginator](#cursorpaginator) — `app/utils/CursorPaginator.php`
- [Escape](#escape) — `app/utils/Escape.php`
- [Input](#input) — `app/utils/Input.php`
- [JwtHelper](#jwthelper) — `app/utils/JwtHelper.php`
- [SafeHtml](#safehtml) — `app/utils/SafeHtml.php`
- [Sanitizer](#sanitizer) — `app/utils/Sanitizer.php`
- [SimpleCache](#simplecache) — `app/utils/SimpleCache.php`
- [Validator](#validator) — `app/utils/Validator.php`
- [ViteAssets](#viteassets) — `app/utils/ViteAssets.php`
- [asset_helper](#asset-helper) — `app/utils/asset_helper.php` (funções)
- [AktiEnvRegistry](#aktienvregistry) — `app/utils/env_loader.php`
- [escape_helper](#escape-helper) — `app/utils/escape_helper.php` (funções)
- [file_helper](#file-helper) — `app/utils/file_helper.php` (funções)
- [form_helper](#form-helper) — `app/utils/form_helper.php` (funções)
- [i18n_helper](#i18n-helper) — `app/utils/i18n_helper.php` (funções)
- [portal_helper](#portal-helper) — `app/utils/portal_helper.php` (funções)

---

## CurrencyFormatter

**Tipo:** Class  
**Arquivo:** `app/utils/CurrencyFormatter.php`  
**Namespace:** `Akti\Utils`  

CurrencyFormatter — Format monetary values for different locales/currencies.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$currencies` | Sim |

### Métodos

#### Métodos Public

##### `static format($value, string $currency = 'BRL', bool $showSymbol = true): string`

Format a value in the given currency.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `float|int|string` | * @param  string           $currency ISO code (BRL, USD, EUR) |
| `$showSymbol` | `bool` | * @return string |

**Retorno:** `string — */`

---

##### `static parse(string $value, string $currency = 'BRL'): float`

Parse a locale-formatted string back to a float.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `string` | * @param  string $currency |

**Retorno:** `float — */`

---

##### `static getAvailable(): array`

Get available currencies list.

**Retorno:** `array — */`

---

## CursorPaginator

**Tipo:** Class  
**Arquivo:** `app/utils/CursorPaginator.php`  
**Namespace:** `Akti\Utils`  

CursorPaginator — Paginação baseada em cursor para large datasets.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe CursorPaginator.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `paginate(string $table,
        string $columns = '*',
        string $joins = '',
        string $where = '',
        array $params = [],
        ?int $cursor = null,
        int $limit = 50,
        string $direction = 'next',
        string $orderCol = 'id'): array`

Executa paginação cursor-based.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$table` | `string` | Tabela principal (com alias se necessário) |
| `$columns` | `string` | Colunas a selecionar |
| `$joins` | `string` | JOINs adicionais (opcional) |
| `$where` | `string` | Condição WHERE sem o cursor (opcional) |
| `$params` | `array` | Parâmetros bind para WHERE |
| `$cursor` | `int|null` | ID do último registro (null = primeira página) |
| `$limit` | `int` | Registros por página |
| `$direction` | `string` | 'next' (> cursor) ou 'prev' (< cursor) |
| `$orderCol` | `string` | Coluna de ordenação (deve ser indexada e única) |

**Retorno:** `array{data: — array, next_cursor: int|null, prev_cursor: int|null, has_more: bool}`

---

## Escape

**Tipo:** Class  
**Arquivo:** `app/utils/Escape.php`  
**Namespace:** `Akti\Utils`  

Utilitários de escape para prevenção de XSS.

### Métodos

#### Métodos Public

##### `static html($value, string $encoding = 'UTF-8'): string`

Escape para contexto HTML (conteúdo de tags).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  string $encoding |

**Retorno:** `string — */`

---

##### `static attr($value, string $encoding = 'UTF-8'): string`

Escape para contexto de atributo HTML.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  string $encoding |

**Retorno:** `string — */`

---

##### `static js($value): string`

Escape para contexto JavaScript (inline scripts).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | String, array, object, number, bool |

**Retorno:** `string — JSON-encoded e seguro para HTML`

---

##### `static url($value): string`

Escape para contexto de URL (query string).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return string |

**Retorno:** `string — */`

---

##### `static css($value): string`

Escape para contexto CSS (valores inline).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return string |

**Retorno:** `string — */`

---

##### `static number($value, int $decimals = 2, string $decSep = ',', string $thousSep = '.'): string`

Formata número para exibição (locale BR).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  int   $decimals |
| `$decSep` | `string` | Separador decimal |
| `$thousSep` | `string` | Separador de milhar |

**Retorno:** `string — */`

---

## Input

**Tipo:** Class  
**Arquivo:** `app/utils/Input.php`  
**Namespace:** `Akti\Utils`  

Classe para captura e sanitização de inputs do usuário.

### Métodos

#### Métodos Public

##### `static post(string $key, string $type = 'string', $default = null, ?array $options = null)`

Obtém valor de $_POST com sanitização.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Nome do campo |
| `$type` | `string` | Tipo de sanitização (string, int, float, email, bool, date, etc.) |
| `$default` | `mixed` | Valor padrão se ausente |
| `$options` | `array|null` | Opções extras (ex: lista de valores para 'enum') |

**Retorno:** `mixed — */`

---

##### `static get(string $key, string $type = 'string', $default = null, ?array $options = null)`

Obtém valor de $_GET com sanitização.

---

##### `static request(string $key, string $type = 'string', $default = null, ?array $options = null)`

Obtém valor de $_REQUEST com sanitização.

---

##### `static hasPost(string $key): bool`

Verifica se um campo existe em $_POST (e não está vazio).

---

##### `static hasGet(string $key): bool`

Verifica se um campo existe em $_GET (e não está vazio).

---

##### `static allPost(array $fields): array`

Obtém múltiplos campos de $_POST com sanitização.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$fields` | `array` | Mapa de campo => tipo ou lista de campos (tipo 'string' padrão) |

**Retorno:** `array<string, — mixed>`

---

##### `static allGet(array $fields): array`

Obtém múltiplos campos de $_GET com sanitização.

---

##### `static postRaw(string $key, $default = null)`

Obtém um valor raw de $_POST sem sanitização (usar com cautela).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | * @param  mixed  $default |

**Retorno:** `mixed — */`

---

##### `static getRaw(string $key, $default = null)`

Obtém um valor raw de $_GET sem sanitização.

---

##### `static postArray(string $key): array`

Obtém um array de $_POST (ex: grades[], items[]).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | * @return array |

**Retorno:** `array — */`

---

##### `static getArray(string $key): array`

Obtém um array de $_GET.

---

#### Métodos Private

##### `static sanitize($value, string $type, $default, ?array $options)`

Aplica sanitização pelo tipo especificado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  string     $type |
| `$default` | `mixed` | * @param  array|null $options |

**Retorno:** `mixed — */`

---

##### `static allFrom(array $source, array $fields): array`

Helper interno para allPost / allGet.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$source` | `array` | $_POST ou $_GET |
| `$fields` | `array` | Definição dos campos |

**Retorno:** `array<string, — mixed>`

---

## JwtHelper

**Tipo:** Class  
**Arquivo:** `app/utils/JwtHelper.php`  
**Namespace:** `Akti\Utils`  

JWT Helper — Gera tokens JWT (HMAC-SHA256) compatíveis com jsonwebtoken do Node.js.

### Métodos

#### Métodos Public

##### `static encode(array $payload, string $secret, int $ttl = 3600): string`

Gera um JWT com payload personalizado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$payload` | `array` | Dados a incluir no token (ex: user_id, tenant_id). |
| `$secret` | `string` | Chave secreta compartilhada com o Node.js. |
| `$ttl` | `int` | Tempo de vida em segundos (padrão: 1 hora). |

**Retorno:** `string — Token JWT codificado.`

---

#### Métodos Private

##### `static base64UrlEncode(string $data): string`

Base64 URL-safe encode (sem padding, + → -, / → _).

---

## SafeHtml

**Tipo:** Class  
**Arquivo:** `app/utils/SafeHtml.php`  
**Namespace:** `Akti\Utils`  

Classe para geração segura de HTML.

### Métodos

#### Métodos Public

##### `static sanitizeFragment(string $html, array $allowedTags, array $allowedAttributes = []): string`

Sanitiza dados de entrada.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$html` | `string` | Html |
| `$allowedTags` | `array` | Allowed tags |
| `$allowedAttributes` | `array` | Allowed attributes |

**Retorno:** `string — */`

---

#### Métodos Private

##### `static sanitizeChildren(\DOMNode $parent, array $allowedTags, array $allowedAttributes): void`

Sanitiza dados de entrada.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$parent` | `\DOMNode` | Parent |
| `$allowedTags` | `array` | Allowed tags |
| `$allowedAttributes` | `array` | Allowed attributes |

**Retorno:** `void — */`

---

##### `static sanitizeNode(\DOMNode $node, array $allowedTags, array $allowedAttributes): void`

Sanitiza dados de entrada.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$node` | `\DOMNode` | Node |
| `$allowedTags` | `array` | Allowed tags |
| `$allowedAttributes` | `array` | Allowed attributes |

**Retorno:** `void — */`

---

##### `static sanitizeAttributes(\DOMElement $element, array $allowedAttributes): void`

Sanitiza dados de entrada.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$element` | `\DOMElement` | Element |
| `$allowedAttributes` | `array` | Allowed attributes |

**Retorno:** `void — */`

---

##### `static unwrapNode(\DOMElement $element): void`

Unwrap node.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$element` | `\DOMElement` | Element |

**Retorno:** `void — */`

---

##### `static buildAllowedTagList(array $allowedTags): string`

Constrói dados ou estrutura.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$allowedTags` | `array` | Allowed tags |

**Retorno:** `string — */`

---

##### `static stripControlChars(string $value): string`

Strip control chars.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `string` | Valor |

**Retorno:** `string — */`

---

##### `static isSafeUrl(string $url): bool`

Verifica uma condição booleana.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$url` | `string` | Url |

**Retorno:** `bool — */`

---

## Sanitizer

**Tipo:** Class  
**Arquivo:** `app/utils/Sanitizer.php`  
**Namespace:** `Akti\Utils`  

Sanitizador de dados de entrada.

### Métodos

#### Métodos Public

##### `static string($value, ?string $default = ''): string`

Sanitiza string genérica: trim + strip_tags.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  string|null $default Valor padrão se vazio/null |

**Retorno:** `string — */`

---

##### `static richText($value, string $allowedTags = '<b><i><br><p><ul><ol><li><strong><em>'): string`

Sanitiza string preservando algumas tags HTML permitidas.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  string $allowedTags Tags permitidas (ex: '<b><i><br><p><ul><li>') |

**Retorno:** `string — */`

---

##### `static int($value, ?int $default = null): ?int`

Sanitiza e converte para inteiro.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  int|null $default |

**Retorno:** `int|null — */`

---

##### `static float($value, ?float $default = null): ?float`

Sanitiza e converte para float, aceitando formato PT-BR ("1.234,56").

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  float|null  $default |

**Retorno:** `float|null — */`

---

##### `static bool($value): bool`

Sanitiza valor booleano.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return bool |

**Retorno:** `bool — */`

---

##### `static email($value, ?string $default = ''): string`

Sanitiza e-mail: trim + lowercase + filter.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  string|null $default |

**Retorno:** `string — */`

---

##### `static phone($value): string`

Sanitiza telefone: remove tudo exceto dígitos, +, ( e ).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return string |

**Retorno:** `string — */`

---

##### `static document($value): string`

Sanitiza CPF/CNPJ: remove tudo exceto dígitos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return string Apenas dígitos |

**Retorno:** `string — Apenas dígitos`

---

##### `static cep($value): string`

Sanitiza CEP: remove tudo exceto dígitos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return string |

**Retorno:** `string — */`

---

##### `static url($value): string`

Sanitiza URL.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return string |

**Retorno:** `string — */`

---

##### `static date($value, ?string $default = null): ?string`

Sanitiza data no formato Y-m-d.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  string|null $default |

**Retorno:** `string|null — */`

---

##### `static datetime($value, ?string $default = null): ?string`

Sanitiza datetime no formato Y-m-d H:i:s.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  string|null $default |

**Retorno:** `string|null — */`

---

##### `static slug($value): string`

Sanitiza slug: lowercase, sem acentos, apenas a-z 0-9 e hífens.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return string |

**Retorno:** `string — */`

---

##### `static intArray($value): array`

Sanitiza um array de inteiros.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return array<int> |

**Retorno:** `array<int> — */`

---

##### `static stringArray($value): array`

Sanitiza um array de strings.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return array<string> |

**Retorno:** `array<string> — */`

---

##### `static enum($value, array $allowed, $default = null)`

Valida se o valor está em uma lista de opções permitidas.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  array       $allowed  Lista de valores permitidos |
| `$default` | `mixed` | Valor padrão se não estiver na lista |

**Retorno:** `mixed — */`

---

##### `static filename($value): string`

Sanitiza um nome de arquivo: remove caracteres perigosos, preserva extensão.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return string |

**Retorno:** `string — */`

---

##### `static json($value, ?string $default = null): ?string`

Sanitiza JSON string: decode + re-encode para garantir formato válido.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  string|null $default |

**Retorno:** `string|null — JSON string válido ou default`

---

## SimpleCache

**Tipo:** Class  
**Arquivo:** `app/utils/SimpleCache.php`  
**Namespace:** `Akti\Utils`  

SimpleCache — Cache em sessão para dados frequentes

### Métodos

#### Métodos Public

##### `static remember(string $key, int $ttlSeconds, callable $loader)`

Busca um valor no cache; se não existir ou expirado, executa o loader

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Chave única do cache |
| `$ttlSeconds` | `int` | Tempo de vida em segundos |
| `$loader` | `callable` | Função que carrega os dados (chamada apenas se cache miss) |

**Retorno:** `mixed — Dados do cache ou retorno do loader`

---

##### `static get(string $key)`

Retorna dados do cache sem executar loader.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Chave do cache |

**Retorno:** `mixed|null — Dados ou null`

---

##### `static set(string $key, $data, int $ttlSeconds = 300): void`

Armazena um valor diretamente no cache.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Chave do cache |
| `$data` | `mixed` | Dados a armazenar |
| `$ttlSeconds` | `int` | TTL em segundos |

**Retorno:** `void — */`

---

##### `static forget(string $key): void`

Invalida uma chave específica do cache.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Chave a invalidar |

**Retorno:** `void — */`

---

##### `static forgetByPrefix(string $prefix): int`

Invalida todas as chaves que começam com um prefixo.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$prefix` | `string` | Prefixo das chaves a invalidar |

**Retorno:** `int — Número de chaves removidas`

---

##### `static flush(): void`

Limpa todo o cache da sessão.

**Retorno:** `void — */`

---

##### `static has(string $key): bool`

Verifica se uma chave existe e não está expirada.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Chave a verificar |

**Retorno:** `bool — */`

---

##### `static stats(): array`

Retorna estatísticas do cache (para debug).

**Retorno:** `array{total_keys: — int, total_size_bytes: int, keys: array}`

---

## Validator

**Tipo:** Class  
**Arquivo:** `app/utils/Validator.php`  
**Namespace:** `Akti\Utils`  

Validador de dados com regras configuráveis.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$errors` | Não |

### Métodos

#### Métodos Public

##### `fails(): bool`

Verifica se existem erros acumulados.

---

##### `passes(): bool`

Retorna verdadeiro se não houver erros.

---

##### `errors(): array`

Retorna todos os erros: ['campo' => 'mensagem', ...]

---

##### `error(string $field): ?string`

Retorna o primeiro erro de um campo específico (ou null).

---

##### `addError(string $field, string $message): self`

Adiciona um erro manualmente.

---

##### `reset(): self`

Limpa todos os erros acumulados.

---

##### `required(string $field, $value, string $label = ''): self`

Campo obrigatório (não vazio).

---

### Funções auxiliares do arquivo

#### `minLength(string $field, $value, int $min, string $label = '')`

---

#### `passwordStrength(string $field, $value, string $label = '')`

---

#### `maxLength(string $field, $value, int $max, string $label = '')`

---

#### `email(string $field, $value, string $label = '')`

---

#### `integer(string $field, $value, string $label = '')`

---

#### `numeric(string $field, $value, string $label = '')`

---

#### `min(string $field, $value, float $min, string $label = '')`

---

#### `max(string $field, $value, float $max, string $label = '')`

---

#### `inList(string $field, $value, array $allowed, string $label = '')`

---

#### `date(string $field, $value, string $label = '')`

---

#### `url(string $field, $value, string $label = '')`

---

#### `regex(string $field, $value, string $pattern, string $label = '', string $message = '')`

---

#### `cpf(string $field, $value, string $label = '')`

---

#### `cnpj(string $field, $value, string $label = '')`

---

#### `document(string $field, $value, string $personType = 'PF', string $label = '')`

---

#### `dateNotFuture(string $field, $value, string $label = '')`

---

#### `decimal(string $field, $value, string $label = '')`

---

#### `between(string $field, $value, float $min, float $max, string $label = '')`

---

#### `cpfOrCnpj(string $field, $value, string $label = '')`

---

#### `uniqueExcept(string $field, $value, \PDO $db, string $table, string $column, ?int $excludeId = null, string $label = '')`

---

#### `isValidCpf(string $cpf)`

---

#### `isValidCnpj(string $cnpj)`

---

## ViteAssets

**Tipo:** Class  
**Arquivo:** `app/utils/ViteAssets.php`  
**Namespace:** `Akti\Utils`  

Vite asset helper — reads the manifest produced by `npm run build`

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$loaded` | Sim |

### Métodos

#### Métodos Public

##### `static isBuilt(): bool`

Verifica uma condição booleana.

**Retorno:** `bool — */`

---

##### `static css(string $name): ?string`

Css.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$name` | `string` | Nome |

**Retorno:** `string|null — */`

---

##### `static js(string $name): ?string`

Js.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$name` | `string` | Nome |

**Retorno:** `string|null — */`

---

##### `static tag(string $type, string $name, string $extra = ''): string`

Tag.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$type` | `string` | Tipo do recurso |
| `$name` | `string` | Nome |
| `$extra` | `string` | Extra |

**Retorno:** `string — */`

---

#### Métodos Private

##### `static load(): void`

Carrega dados.

**Retorno:** `void — */`

---

## asset_helper

**Arquivo:** `app/utils/asset_helper.php`  
**Tipo:** Arquivo de funções  

### Funções

#### `asset(string $path)`

Asset Helper — Cache Busting via file modification time

---

## AktiEnvRegistry

**Tipo:** Class  
**Arquivo:** `app/utils/env_loader.php`  

Lightweight .env file loader for Akti.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$vars` | Sim |
| private | `$loaded` | Sim |

### Métodos

#### Métodos Public

##### `static set(string $name, string $value): void`

Set.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$name` | `string` | Nome |
| `$value` | `string` | Valor |

**Retorno:** `void — */`

---

##### `static get(string $name)`

**Retorno:** `string|false — */`

---

##### `static isLoaded(): bool`

Verifica uma condição booleana.

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `akti_load_env(string $path)`

Akti load env.

---

#### `akti_env(string $name)`

Retrieve an environment variable loaded by akti_load_env().

---

## escape_helper

**Arquivo:** `app/utils/escape_helper.php`  
**Tipo:** Arquivo de funções  

### Funções

#### `e($value)`

Escape Helpers — Akti

---

#### `eAttr($value)`

Escape para contexto de atributo HTML.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return string |

**Retorno:** `string — */`

---

#### `eJs($value)`

Escape para contexto JavaScript (inline scripts).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return string |

**Retorno:** `string — */`

---

#### `eNum($value, int $decimals = 2)`

Formata número para exibição (locale BR).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @param  int   $decimals |

**Retorno:** `string — */`

---

#### `eUrl($value)`

Escape para URL (query string).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `mixed` | * @return string |

**Retorno:** `string — */`

---

#### `cspNonce()`

Retorna o nonce CSP do request atual para uso em tags <script>.

**Retorno:** `string — */`

---

## file_helper

**Arquivo:** `app/utils/file_helper.php`  
**Tipo:** Arquivo de funções  

### Funções

#### `file_url(?string $path, ?string $size = null)`

Obter URL de um arquivo com suporte a thumbnail.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$path` | `string|null` | Caminho relativo do arquivo (salvo no BD) |
| `$size` | `string|null` | Preset de tamanho: 'xs'(40px), 'sm'(80px), 'md'(150px), 'lg'(300px), 'xl'(600px), ou 'WxH' |

**Retorno:** `string — URL do arquivo ou string vazia`

---

#### `thumb_url(?string $path, int $width, ?int $height = null)`

Obter URL de thumbnail de uma imagem.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$path` | `string|null` | Caminho da imagem original |
| `$width` | `int` | Largura desejada em pixels |
| `$height` | `int|null` | Altura (null = proporcional) |

**Retorno:** `string — URL do thumbnail ou imagem original`

---

#### `is_file_image(?string $path)`

Verificar se um path de arquivo é uma imagem.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$path` | `string|null` | Caminho do arquivo |

**Retorno:** `bool — */`

---

#### `file_url_or(?string $path,
    string $placeholder = 'assets/img/default-avatar.png',
    ?string $size = null)`

Obter URL de imagem com fallback para placeholder.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$path` | `string|null` | Caminho da imagem |
| `$placeholder` | `string` | URL do placeholder (default: ícone genérico) |
| `$size` | `string|null` | Preset de tamanho do thumbnail |

**Retorno:** `string — URL da imagem ou placeholder`

---

## form_helper

**Arquivo:** `app/utils/form_helper.php`  
**Tipo:** Arquivo de funções  

### Funções

#### `csrf_field()`

Helpers de formulário — Akti

---

#### `csrf_meta()`

Gera a meta tag do token CSRF para uso no <head>.

**Retorno:** `string — HTML da meta tag com token CSRF`

---

#### `csrf_token()`

Retorna apenas o valor do token CSRF (sem HTML).

**Retorno:** `string — Token CSRF puro`

---

## i18n_helper

**Arquivo:** `app/utils/i18n_helper.php`  
**Tipo:** Arquivo de funções  

### Funções

#### `__($key, array $replace = [], string $group = 'app')`

Internationalization (i18n) Helper — Akti

---

#### `currentLocale()`

Get the current locale.

**Retorno:** `string — */`

---

#### `_setLocale(string $locale)`

Set the current locale for the session.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$locale` | `string` | */ |

---

## portal_helper

**Arquivo:** `app/utils/portal_helper.php`  
**Tipo:** Arquivo de funções  

### Funções

#### `__p(string $key, array $params = [], ?string $default = null)`

Portal Helper — Funções utilitárias globais para o Portal do Cliente.

---

#### `portal_money($value)`

Formata valor monetário no padrão pt-BR.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$value` | `float|string` | * @return string Ex: "R$ 1.500,00" |

**Retorno:** `string — Ex: "R$ 1.500,00"`

---

#### `portal_date(?string $date, string $format = 'd/m/Y')`

Formata data no padrão do portal.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$date` | `string|null` | * @param string $format |

**Retorno:** `string — */`

---

#### `portal_datetime(?string $datetime)`

Formata data e hora no padrão do portal.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$datetime` | `string|null` | * @return string |

**Retorno:** `string — */`

---

#### `portal_stage_class(string $stage)`

Retorna a classe CSS para o status do pipeline.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$stage` | `string` | * @return string |

**Retorno:** `string — */`

---

#### `portal_stage_icon(string $stage)`

Retorna o ícone para o status do pipeline.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$stage` | `string` | * @return string |

**Retorno:** `string — */`

---

