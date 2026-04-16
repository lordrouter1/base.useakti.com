# Documentação Técnica — Akti ERP/CRM

> Documentação auto-gerada de todos os arquivos PHP do sistema.

**Gerado em:** 16/04/2026 12:41:01

---

## Sumário

- [Core (Núcleo)](core.md) (12 arquivos)
- [Bootstrap (Inicialização)](bootstrap.md) (3 arquivos)
- [Config (Configurações)](config.md) (6 arquivos)
- [Utils (Utilitários)](utils.md) (17 arquivos)
- [Middleware](middleware.md) (5 arquivos)
- [Gateways (Pagamento)](gateways.md) (6 arquivos)
- [Models (Modelos)](models.md) (77 arquivos)
- [Services (Serviços)](services.md) (90 arquivos)
- [Controllers (Controladores)](controllers.md) (75 arquivos)

---

## Visão Geral da Arquitetura

```
Requisição HTTP
    │
    ▼
 index.php → Application::boot() → Application::handle()
    │                                    │
    │         ┌───────────────────────────┘
    │         │
    │    SecurityHeaders → CSRF → Auth → Permissions → ModuleBootloader
    │         │
    │         ▼
    │    Router::dispatch()
    │         │
    │         ▼
    │    Controller::action()
    │         │
    │    ┌────┴────┐
    │    │         │
    │  Model    Service
    │    │         │
    │    └────┬────┘
    │         │
    │         ▼
    │      View (HTML)
    │         │
    ▼         ▼
 Resposta HTTP
```

## Namespaces PSR-4

| Namespace | Diretório |
|---|---|
| `Akti\Core\` | `app/core/` |
| `Akti\Controllers\` | `app/controllers/` |
| `Akti\Models\` | `app/models/` |
| `Akti\Services\` | `app/services/` |
| `Akti\Middleware\` | `app/middleware/` |
| `Akti\Utils\` | `app/utils/` |
| `Akti\Config\` | `app/config/` |
| `Akti\Bootstrap\` | `app/bootstrap/` |
| `Akti\Gateways\` | `app/gateways/` |

## Estatísticas

| Métrica | Valor |
|---|---|
| Arquivos PHP analisados | 291 |
| Classes/Interfaces | 286 |
| Métodos | 1811 |
| Funções standalone | 1227 |
