# Sistema de Eventos

- Use `Akti\Core\EventDispatcher` e `Akti\Core\Event`.
- Ao criar, atualizar ou remover, lance eventos na convenção `camada.entidade.acao` (exemplo: `model.pedido.criado`).
- O sistema intercepta por um repositório central (`bootstrap/events.php`), disparando no método `listen()`.
- O listener de evento funciona fire-and-forget; as intercorrências nele não podem impactar a cadeia da aplicação e são logadas (`logs/events.log`).

## Sistema de Eventos (Event Dispatcher)

### Visão Geral
O Akti possui um **Event Dispatcher nativo** em PHP puro, sem dependências externas, compatível com **PHP 7.4+**. Ele permite que qualquer camada (Models, Controllers, Core, Middleware) emita eventos nomeados e que módulos futuros se inscrevam neles via listeners, sem modificar o código existente.

### Arquitetura

| Componente | Arquivo | Namespace | Responsabilidade |
|------------|---------|-----------|------------------|
| **EventDispatcher** | `app/core/EventDispatcher.php` | `Akti\Core` | Classe estática — `listen()`, `dispatch()`, `forget()`, `getRegistered()` |
| **Event** | `app/core/Event.php` | `Akti\Core` | Value Object imutável com dados do evento |
| **Bootstrap** | `app/bootstrap/events.php` | *(sem namespace)* | Registro central de listeners, carregado pelo `autoload.php` |
| **Log** | `storage/logs/events.log` | — | Log de exceções de listeners (fire-and-forget) |

### Classes

#### `Akti\Core\EventDispatcher`
```php
// Registrar um listener
EventDispatcher::listen('model.order.created', function (Event $event) {
    // $event->name, $event->data, $event->timestamp, $event->userId, $event->tenantDb
});

// Disparar um evento
EventDispatcher::dispatch('model.order.created', new Event('model.order.created', [
    'id' => $newId,
    'customer_id' => $data['customer_id'],
]));

// Remover todos os listeners de um evento
EventDispatcher::forget('model.order.created');

// Listar todos os eventos registrados
$registered = EventDispatcher::getRegistered();
```

#### `Akti\Core\Event` (Value Object)
| Propriedade | Tipo | Descrição |
|-------------|------|-----------|
| `$name` | `string` | Nome do evento (convenção: `camada.entidade.acao`) |
| `$data` | `array` | Dados arbitrários do evento |
| `$timestamp` | `int` | Timestamp Unix de criação (preenchido automaticamente) |
| `$userId` | `int\|null` | ID do usuário da sessão (`$_SESSION['user_id']`) |
| `$tenantDb` | `string\|null` | Nome do banco do tenant (`$_SESSION['db_name']`) |

### Convenção de Nomes de Eventos
```
camada.entidade.acao
```

| Camada | Prefixo | Exemplo |
|--------|---------|---------|
| Models | `model.` | `model.order.created` |
| Controllers | `controller.` | `controller.user.login` |
| Core | `core.` | `core.security.access_denied` |
| Middleware | `middleware.` | `middleware.csrf.failed` |

### Ações Padrão
- `created` → Após INSERT bem-sucedido
- `updated` → Após UPDATE bem-sucedido
- `deleted` → Após DELETE bem-sucedido
- `saved` → Após operação de salvar (batch/bulk)
- `toggled` → Após alternar estado (ativar/desativar)
- `completed` → Após concluir um fluxo
- `skipped` → Após pular um fluxo
- `failed` → Após falha de validação ou segurança

---

### Comportamento de Segurança

#### Fire-and-Forget
- Se um listener lançar exceção, o erro é logado em `storage/logs/events.log` e os demais listeners **continuam executando** normalmente.
- O sistema de eventos **nunca interrompe** o fluxo principal da aplicação.

#### Log de Erros
Formato do log em `storage/logs/events.log`:
```
[2026-03-11 14:30:22] Event listener error | Event: model.order.created | Error: Connection refused | File: /app/modules/webhook.php:45 | User: 5
```

---

### Como Registrar Listeners

#### Registro Central (`app/bootstrap/events.php`)
```php
<?php
use Akti\Core\EventDispatcher;
use Akti\Core\Event;

// Exemplo: Log de auditoria para todos os creates
EventDispatcher::listen('model.order.created', function (Event $event) {
    error_log("Pedido #{$event->data['id']} criado por user #{$event->userId}");
});

// Exemplo: Notificação por e-mail ao mover pedido
EventDispatcher::listen('model.order.stage_changed', function (Event $event) {
    $from = $event->data['from_stage'];
    $to = $event->data['to_stage'];
    // Enviar e-mail...
});

// Módulos futuros:
// require_once AKTI_BASE_PATH . 'app/modules/notificacoes/listeners.php';
// require_once AKTI_BASE_PATH . 'app/modules/webhooks/listeners.php';
```

---

### Como Adicionar Eventos em Novo Código

#### Em um Model (operação CRUD)
```php
<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

class NovoModel {
    public function create($data) {
        // ... INSERT SQL ...
        $newId = $this->conn->lastInsertId();
        EventDispatcher::dispatch('model.novo_model.created', new Event('model.novo_model.created', [
            'id' => $newId,
            'name' => $data['name'],
        ]));
        return $newId;
    }

    public function update($data) {
        // ... UPDATE SQL ...
        if ($result) {
            EventDispatcher::dispatch('model.novo_model.updated', new Event('model.novo_model.updated', [
                'id' => $data['id'],
            ]));
        }
        return $result;
    }

    public function delete($id) {
        // ... DELETE SQL ...
        if ($result) {
            EventDispatcher::dispatch('model.novo_model.deleted', new Event('model.novo_model.deleted', [
                'id' => $id,
            ]));
        }
        return $result;
    }
}
```

#### Em um Controller
```php
<?php
namespace Akti\Controllers;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;

class NovoController {
    public function algumaAcao() {
        // ... lógica ...
        EventDispatcher::dispatch('controller.novo.acao_realizada', new Event('controller.novo.acao_realizada', [
            'user_id' => $_SESSION['user_id'] ?? null,
        ]));
    }
}
```

---

### Regras Obrigatórias para Eventos

1. **SEMPRE** usar a convenção `camada.entidade.acao` para nomes de eventos.
2. **SEMPRE** adicionar `use Akti\Core\EventDispatcher;` e `use Akti\Core\Event;` no topo do arquivo.
3. **SEMPRE** disparar eventos **após** a operação bem-sucedida (não antes).
4. **SEMPRE** incluir pelo menos o `id` do recurso afetado nos dados do evento.
5. **NUNCA** fazer o fluxo principal depender do retorno de um listener.
6. **NUNCA** lançar exceções dentro de listeners que devam interromper o fluxo.
7. **NUNCA** fazer queries pesadas ou chamadas HTTP síncronas dentro de listeners sem considerar performance.
8. Listeners devem ser registrados **apenas** em `app/bootstrap/events.php` ou em arquivos de listeners de módulos.
9. Ao criar novo Model com CRUD, **documentar os eventos neste arquivo** (catálogo de eventos).
10. Ao criar novo módulo, registrar listeners via `require_once AKTI_BASE_PATH . 'app/modules/nome/listeners.php';` no `events.php`.

---

### Models SEM Eventos (Infraestrutura)

Os seguintes models são de **infraestrutura/segurança** e **não emitem eventos** intencionalmente para evitar loops ou problemas de recursão:

| Model | Arquivo | Motivo |
|-------|---------|--------|
| `Logger` | `Logger.php` | Classe de logging — emitir evento aqui causaria loop |
| `IpGuard` | `IpGuard.php` | Proteção contra flood — opera no nível de infraestrutura |
| `LoginAttempt` | `LoginAttempt.php` | Rate-limiting de login — opera antes da sessão estar disponível |

---

### Testes Automatizados de Eventos

#### Conceito
Os testes unitários do EventDispatcher verificam:
- Registro e disparo correto de listeners (FIFO)
- Dados do Event (name, data, timestamp, userId, tenantDb)
- Isolamento de falhas — exceção em listener não interrompe outros
- Método `forget()` remove listeners corretamente
- Convenção de nomes (`camada.entidade.acao`)
- Presença de `use EventDispatcher` e `dispatch()` nos Models/Controllers que devem emitir eventos

#### Executar
```bash
php vendor/bin/phpunit tests/Unit/EventDispatcherTest.php --testdox
```

#### Arquivo
`tests/Unit/EventDispatcherTest.php`

---