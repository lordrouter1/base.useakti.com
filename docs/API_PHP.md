# API PHP — Documentação de Endpoints AJAX

**Versão:** 1.0  
**Base URL:** `https://[dominio]/`  
**Formato:** JSON (Content-Type: application/json)

---

## Autenticação

Todos os endpoints requerem sessão ativa (cookie de sessão PHP), exceto os marcados como **públicos**.

### Headers obrigatórios para AJAX:
```
X-Requested-With: XMLHttpRequest
X-CSRF-Token: {token do meta csrf}
```

---

## Endpoints

### 1. Busca Global (Command Palette)

| Campo | Valor |
|-------|-------|
| **URL** | `?page=search&action=query` |
| **Método** | GET |
| **Auth** | Sim |

**Parâmetros:**

| Param | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `q` | string | Sim | Termo de busca (mín. 2 caracteres) |
| `limit` | int | Não | Máx. resultados por tipo (default: 5, max: 10) |

**Resposta (200):**
```json
{
  "success": true,
  "query": "termo",
  "results": [
    {
      "type": "customer",
      "category": "Clientes",
      "icon": "fas fa-user",
      "title": "João Silva",
      "subtitle": "joao@email.com",
      "url": "?page=customers&action=view&id=1"
    }
  ]
}
```

---

### 2. Notificações

#### 2.1 Listar Notificações

| Campo | Valor |
|-------|-------|
| **URL** | `?page=notifications` |
| **Método** | GET |
| **Auth** | Sim |

**Parâmetros:**

| Param | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `unread` | "1" | Não | Filtrar apenas não-lidas |
| `limit` | int | Não | Máx. resultados (default: 20, max: 50) |

**Resposta (200):**
```json
{
  "success": true,
  "notifications": [
    {
      "id": 1,
      "type": "order_delayed",
      "title": "Pedido #0042 atrasado",
      "message": "Pedido está há 48h na etapa Produção",
      "data": { "order_id": 42 },
      "read_at": null,
      "created_at": "2026-03-31 10:00:00"
    }
  ],
  "unread_count": 3
}
```

#### 2.2 Contar Não-lidas

| Campo | Valor |
|-------|-------|
| **URL** | `?page=notifications&action=count` |
| **Método** | GET |
| **Auth** | Sim |

**Resposta:**
```json
{ "success": true, "count": 5 }
```

#### 2.3 Marcar como Lida

| Campo | Valor |
|-------|-------|
| **URL** | `?page=notifications&action=markRead` |
| **Método** | GET/POST |
| **Auth** | Sim |

**Parâmetros:** `id` (int) — ID da notificação

**Resposta:** `{ "success": true }`

#### 2.4 Marcar Todas como Lidas

| Campo | Valor |
|-------|-------|
| **URL** | `?page=notifications&action=markAllRead` |
| **Método** | GET/POST |
| **Auth** | Sim |

**Resposta:** `{ "success": true }`

---

### 3. Dashboard Widgets

#### 3.1 Obter Configuração de Widgets

| Campo | Valor |
|-------|-------|
| **URL** | `?page=dashboard_widgets&action=config` |
| **Método** | GET |
| **Auth** | Sim |

**Resposta:**
```json
{
  "success": true,
  "widgets": [
    { "key": "header", "label": "Saudação e Atalhos", "icon": "fas fa-hand-sparkles" },
    { "key": "cards_summary", "label": "Cards de Resumo", "icon": "fas fa-th-large" },
    { "key": "pipeline", "label": "Pipeline", "icon": "fas fa-stream" }
  ]
}
```

#### 3.2 Carregar Widget Individual

| Campo | Valor |
|-------|-------|
| **URL** | `?page=dashboard_widgets&action=load` |
| **Método** | GET |
| **Auth** | Sim |

**Parâmetros:** `widget` (string) — Chave do widget (ex: `cards_summary`)

**Resposta:**
```json
{
  "success": true,
  "widget": "cards_summary",
  "html": "<div class='...'> ... HTML renderizado ... </div>"
}
```

---

### 4. Health Check

#### 4.1 Ping (Uptime Monitor)

| Campo | Valor |
|-------|-------|
| **URL** | `?page=health&action=ping` |
| **Método** | GET |
| **Auth** | Não (público) |

**Resposta (200):**
```json
{ "status": "ok", "timestamp": "2026-03-31T10:00:00-03:00" }
```

#### 4.2 Health Check Detalhado

| Campo | Valor |
|-------|-------|
| **URL** | `?page=health&action=check` |
| **Método** | GET |
| **Auth** | Não (público) |

**Resposta (200 ou 503):**
```json
{
  "status": "healthy",
  "timestamp": "2026-03-31T10:00:00-03:00",
  "checks": {
    "php": { "status": "ok", "version": "8.1.25" },
    "database": { "status": "ok", "latency_ms": 2.5 },
    "filesystem": { "status": "ok", "directories": { "storage/logs": true } },
    "backup": { "status": "ok", "last_backup": "backup_20260331.sql.gz", "hours_ago": 8 },
    "disk": { "status": "ok", "free_gb": 45.2, "used_percent": 54.3 },
    "extensions": { "status": "ok", "missing": [] }
  }
}
```

---

### 5. Produtos — AJAX Endpoints

#### 5.1 Busca Select2

| Campo | Valor |
|-------|-------|
| **URL** | `?page=products&action=searchSelect2` |
| **Método** | GET |
| **Auth** | Sim |

**Parâmetros:** `q` (string), `page` (int)

#### 5.2 Subcategorias

| Campo | Valor |
|-------|-------|
| **URL** | `?page=products&action=getSubcategories` |
| **Método** | GET |
| **Auth** | Sim |

**Parâmetros:** `category_id` (int)

---

### 6. Clientes — AJAX Endpoints

#### 6.1 Busca Select2

| Campo | Valor |
|-------|-------|
| **URL** | `?page=customers&action=searchSelect2` |
| **Método** | GET |
| **Auth** | Sim |

**Parâmetros:** `q` (string), `page` (int)

#### 6.2 Busca CEP

| Campo | Valor |
|-------|-------|
| **URL** | `?page=customers&action=searchCep` |
| **Método** | GET |
| **Auth** | Sim |

**Parâmetros:** `cep` (string)

#### 6.3 Busca CNPJ

| Campo | Valor |
|-------|-------|
| **URL** | `?page=customers&action=searchCnpj` |
| **Método** | GET |
| **Auth** | Sim |

**Parâmetros:** `cnpj` (string)

---

### 7. Pipeline — AJAX Endpoints

#### 7.1 Mover Pedido

| Campo | Valor |
|-------|-------|
| **URL** | `?page=pipeline&action=move` |
| **Método** | GET |
| **Auth** | Sim |

**Parâmetros:** `id` (int), `stage` (string)

#### 7.2 Alertas (pedidos atrasados)

| Campo | Valor |
|-------|-------|
| **URL** | `?page=pipeline&action=alerts` |
| **Método** | GET |
| **Auth** | Sim |

**Resposta:** JSON com lista de pedidos atrasados.

---

### 8. Financeiro — AJAX Endpoints

#### 8.1 Parcelas do Pedido

| Campo | Valor |
|-------|-------|
| **URL** | `?page=financial&action=getInstallments` |
| **Método** | GET |
| **Auth** | Sim |

**Parâmetros:** `order_id` (int)

---

## Tipos de Notificação

| Tipo | Descrição |
|------|-----------|
| `order_delayed` | Pedido ultrapassou meta de tempo |
| `payment_received` | Pagamento confirmado |
| `stock_low` | Estoque abaixo do mínimo |
| `new_order` | Novo pedido criado |
| `system` | Notificação do sistema |
| `custom` | Notificação personalizada |

---

## Códigos de Erro

| Código | Descrição |
|--------|-----------|
| 200 | Sucesso |
| 400 | Requisição inválida (parâmetros ausentes) |
| 401 | Não autenticado |
| 403 | Sem permissão |
| 404 | Recurso não encontrado |
| 500 | Erro interno do servidor |

**Formato de erro:**
```json
{ "success": false, "error": "Mensagem descritiva do erro." }
```
