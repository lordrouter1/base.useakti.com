# Config (Configurações)

> Arquivos de configuração: conexão com banco, rotas, menu, sessão, tenant multi-tenant.

**Total de arquivos:** 6

---

## Índice

- [Database](#database) — `app/config/database.php`
- [SessionGuard](#sessionguard) — `app/config/session.php`
- [TenantManager](#tenantmanager) — `app/config/tenant.php`
- [](#) — `app/config/tenant.php`

---

## Database

**Tipo:** Class  
**Arquivo:** `app/config/database.php`  

Database — Singleton com cache por tenant (DSN).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$instances` | Sim |
| private | `$host` | Não |
| private | `$port` | Não |
| private | `$db_name` | Não |
| private | `$username` | Não |
| private | `$password` | Não |
| private | `$charset` | Não |
| public | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct()`

Construtor da classe Database.

---

##### `static getInstance(?string $tenantDb = null): PDO`

Retorna uma conexão PDO via singleton (cached por DSN).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantDb` | `string|null` | Nome do banco de dados (null = tenant atual) |

**Retorno:** `PDO — * @throws \RuntimeException Se a conexão falhar`

---

##### `getConnection(): PDO`

Wrapper de compatibilidade — retorna conexão PDO via singleton.

**Retorno:** `PDO — */`

---

##### `static resetInstances(): void`

Remove todas as instâncias em cache. Útil para testes unitários.

---

##### `static resetInstance(?string $tenantDb = null): void`

Remove a instância de um DSN específico.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantDb` | `string|null` | Nome do banco (null = todos) |

---

##### `static getMasterInstance(): PDO`

Retorna uma conexão PDO para o banco master (akti_master).

**Retorno:** `PDO — * @throws \RuntimeException Se a conexão falhar`

---

##### `static getMasterCredentials(): array`

Retorna as credenciais do banco master para uso em operações cross-DB.

**Retorno:** `array{host: — string, port: int, username: string, password: string, charset: string, db_name: string}`

---

##### `static connectTo(string $host, int $port, string $user, string $pass, ?string $dbname = null, string $charset = 'utf8mb4'): PDO`

Cria uma conexão PDO avulsa (não cached) para um banco de dados específico.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$host` | `string` | * @param int         $port |
| `$user` | `string` | * @param string      $pass |
| `$dbname` | `string|null` | * @param string      $charset |

**Retorno:** `PDO — */`

---

## SessionGuard

**Tipo:** Class  
**Arquivo:** `app/config/session.php`  

Classe auxiliar para controle de sessão (timeout por inatividade).

### Métodos

#### Métodos Public

##### `static checkInactivity(?PDO $db = null): void`

Verifica se a sessão expirou por inatividade.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO|null` | Conexão com banco do tenant (para ler company_settings). |

---

##### `static touch(): void`

Atualiza o timestamp de última atividade.

---

##### `static getTimeoutMinutes(?PDO $db = null): int`

Obtém o timeout configurado pelo admin em company_settings.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO|null` | Conexão com o banco do tenant |

**Retorno:** `int — */`

---

##### `static getJsSessionData(?PDO $db = null): array`

Retorna dados necessários para o JavaScript do modal de aviso de expiração.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO|null` | * @return array{timeout_seconds: int, warning_seconds: int, remaining_seconds: int} |

**Retorno:** `array{timeout_seconds: — int, warning_seconds: int, remaining_seconds: int}`

---

## TenantManager

**Tipo:** Class  
**Arquivo:** `app/config/tenant.php`  
**Namespace:** `Akti\Config`  

Class TenantManager.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$tenantConfig` | Sim |

### Métodos

#### Métodos Public

##### `static bootstrap(): void`

Inicializa o componente.

**Retorno:** `void — */`

---

##### `static getTenantConfig(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

##### `static getMasterConfig(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

##### `static getTenantUploadBase(): string`

Returns the base upload directory for the current tenant.

---

##### `static getTenantLimit(string $limitKey): ?int`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$limitKey` | `string` | Limit key |

**Retorno:** `int|null — */`

---

##### `static enforceTenantSession(): void`

Enforce tenant session.

**Retorno:** `void — */`

---

#### Métodos Private

##### `static env(string $name)`

Lê variável de ambiente usando akti_env() que inclui fallback

---

##### `static getDefaultTenantConfig(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

##### `static findTenantBySubdomain(string $subdomain): ?array`

Busca registro(s) com critérios específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subdomain` | `string` | Subdomain |

**Retorno:** `array|null — */`

---

##### `static getRequestHost(): string`

Obtém dados específicos.

**Retorno:** `string — */`

---

##### `static extractSubdomain(string $host): ?string`

Extract subdomain.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$host` | `string` | Host |

**Retorno:** `string|null — */`

---

##### `static isLocalHost(string $host): bool`

Verifica uma condição booleana.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$host` | `string` | Host |

**Retorno:** `bool — */`

---

##### `static storeTenantSession(array $tenantConfig, string $tenantKey, bool $hasError, ?string $errorMessage = null, ?int $tenantId = null): void`

Store tenant session.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantConfig` | `array` | Tenant config |
| `$tenantKey` | `string` | Tenant key |
| `$hasError` | `bool` | Has error |
| `$errorMessage` | `string|null` | Error message |
| `$tenantId` | `int|null` | ID do tenant |

**Retorno:** `void — */`

---

## 

**Tipo:** Class  
**Arquivo:** `app/config/tenant.php`  
**Namespace:** `Akti\Config`  

