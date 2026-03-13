# Sistema de Eventos

---

## Sumário
- [Visão Geral](#visão-geral)
- [Arquitetura](#arquitetura)
- [Classes](#classes)
- [Exemplo de Uso](#exemplo-de-uso)

---

## Visão Geral
Event Dispatcher nativo em PHP, permite emissão e escuta de eventos em qualquer camada.

---

## Arquitetura
| Componente | Arquivo | Namespace | Responsabilidade |
|------------|---------|-----------|------------------|
| EventDispatcher | `app/core/EventDispatcher.php` | `Akti\Core` | listen(), dispatch(), forget(), getRegistered() |
| Event | `app/core/Event.php` | `Akti\Core` | Value Object imutável |
| Bootstrap | `app/bootstrap/events.php` | *(sem namespace)* | Registro central de listeners |
| Log | `storage/logs/events.log` | — | Log de exceções de listeners |

---

## Classes

### EventDispatcher
- Registrar listener: `listen()`
- Disparar evento: `dispatch()`
- Remover listeners: `forget()`
- Listar eventos: `getRegistered()`

### Event
- Propriedades: `$name`, `$data`, `$timestamp`, `$userId`

---

## Exemplo de Uso
```php
EventDispatcher::listen('model.order.created', function (Event $event) {
    // $event->name, $event->data, $event->timestamp, $event->userId
});

EventDispatcher::dispatch('model.order.created', new Event('model.order.created', [
    'id' => $newId,
    'customer_id' => $data['customer_id'],
]));
```

---