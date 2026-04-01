# Auditoria API, Integrações e Infraestrutura — Akti v2

> **Data da Auditoria:** 01/04/2026  
> **Escopo:** API Node.js, Payment Gateways, NF-e SEFAZ, Docker, Webhooks, Autenticação JWT  
> **Referência:** OWASP API Security Top 10, Express.js Best Practices, Stripe/MercadoPago/PagSeguro Docs

---

## 1. Resumo Executivo

O Akti possui uma **API Node.js (Express)** para operações assíncronas e integrações externas, com padrão multi-tenant via Sequelize + JWT. O sistema de pagamentos utiliza o **Strategy Pattern** com 3 gateways (Stripe, Mercado Pago, PagSeguro). As integrações fiscais NF-e são as mais completas, com **24 services** especializados utilizando a biblioteca sped-nfe. A infraestrutura Docker cobre PHP, MySQL e Node.js com healthchecks.

| Aspecto | Nota | Observação |
|---|---|---|
| API Node.js | ⭐⭐⭐⭐ | RESTful, multi-tenant, JWT, rate limited |
| Payment Gateways | ⭐⭐⭐⭐⭐ | Strategy pattern, webhook signature validation, idempotency |
| NF-e Integration | ⭐⭐⭐⭐⭐ | 24 services, cobertura completa SEFAZ |
| Docker | ⭐⭐⭐⭐ | 3 services, healthchecks, volumes |
| Webhook Security | ⭐⭐⭐⭐⭐ | HMAC-SHA256, timing-safe, replay protection |

---

## 2. API Node.js

### 2.1 Stack Tecnológico

| Pacote | Versão | Função |
|---|---|---|
| Express | ^4.21.2 | Framework HTTP |
| Helmet | ^8.0.0 | Security headers |
| CORS | ^2.8.5 | Cross-Origin Resource Sharing |
| Morgan | ^1.10.0 | HTTP logging |
| jsonwebtoken | ^9.0.2 | Validação JWT |
| Sequelize | ^6.37.5 | ORM multi-tenant |
| mysql2 | ^3.12.0 | Driver MySQL |
| express-rate-limit | ^7.5.0 | Rate limiting |
| dotenv | ^16.4.7 | Variáveis de ambiente |

**Requisito:** Node.js ≥20.0.0 | **Tipo:** ES Modules (import/export)

### 2.2 Arquitetura

```
api/
├── server.js              # Entry point + graceful shutdown
├── src/
│   ├── app.js             # Express config (helmet, cors, routes)
│   ├── config/
│   │   ├── database.js    # Multi-tenant pool manager (210 linhas)
│   │   └── env.js         # Environment variables
│   ├── controllers/
│   │   ├── BaseController.js    # Abstract RESTful controller
│   │   ├── ProductController.js # Product search + CRUD
│   │   └── WebhookController.js # Gateway webhook handler
│   ├── middlewares/
│   │   ├── authMiddleware.js    # JWT validation
│   │   ├── tenantMiddleware.js  # Tenant DB resolution
│   │   ├── rateLimiter.js       # 100 req/15min
│   │   └── errorHandler.js      # Centralized error handler
│   ├── models/
│   │   ├── index.js             # Model registry + WeakMap cache
│   │   ├── Product.js
│   │   ├── ProductGradeCombination.js
│   │   ├── PaymentGateway.js
│   │   └── PaymentGatewayTransaction.js
│   ├── routes/
│   │   ├── index.js             # Main router
│   │   ├── productRoutes.js
│   │   └── webhookRoutes.js
│   └── services/
│       ├── BaseService.js       # Abstract CRUD service
│       ├── ProductService.js    # Search + associations
│       └── WebhookService.js    # Signature validation + parsing
```

### 2.3 Multi-Tenant Database Pool

**Arquivo:** `api/src/config/database.js` (210 linhas)

| Componente | Função |
|---|---|
| `getMasterSequelize()` | Singleton connection para `akti_master` DB |
| `tenantPool.acquire(dbName)` | Cria/retorna Sequelize por tenant |
| `tenantPool.release(dbName)` | Fecha conexão específica |
| `tenantPool.closeAll()` | Graceful shutdown de todas as pools |
| `resolveTenantCredentials()` | Consulta `akti_master.tenant_clients` com cache 5min |

**Pool Configuration:**
| Parâmetro | Master | Tenant |
|---|---|---|
| Max connections | 3 | 5 |
| Idle timeout | 60s | 10s |
| GC interval | — | 60s |
| Credential cache | — | 5 min |

**Validação de tenant:** Regex `/^[a-zA-Z0-9_]+$/` (max 64 chars) — previne SQL injection no nome do DB.

### 2.4 Autenticação e Autorização

**Fluxo JWT:**
```
1. PHP backend emite JWT com { user_id, tenant_db, ... }
2. Cliente envia: Authorization: Bearer <token>
3. authMiddleware valida com env.JWT_SECRET
4. tenantMiddleware extrai tenant_db → adquire Sequelize pool
5. Injeta: req.user, req.tenantDb, req.db, req.models
```

| Middleware | Responsabilidade | Resposta de Erro |
|---|---|---|
| `authMiddleware` | Valida JWT, extrai payload | 401 Unauthorized |
| `tenantMiddleware` | Resolve tenant DB, cria pool | 400/404 |
| `rateLimiter` | 100 req/15min por IP | 429 Too Many Requests |
| `errorHandler` | Centraliza erros, sanitiza em produção | 500 |

### 2.5 Endpoints da API

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| `GET` | `/api/status` | ❌ | Health check público |
| `GET` | `/api/webhooks/:gateway` | ❌ | Health check webhook |
| `POST` | `/api/webhooks/:gateway?tenant=X` | ❌* | Recebe webhook |
| `GET` | `/api/products` | ✅ JWT | Lista produtos paginada |
| `GET` | `/api/products/search?q=` | ✅ JWT | Busca por nome/SKU |
| `GET` | `/api/products/:id` | ✅ JWT | Detalhe do produto |
| `POST` | `/api/products` | ✅ JWT | Criar produto |
| `PUT` | `/api/products/:id` | ✅ JWT | Atualizar produto |
| `DELETE` | `/api/products/:id` | ✅ JWT | Excluir produto |

*Webhooks: autenticação via HMAC signature do gateway (não JWT).

### 2.6 Avaliação da API

| Aspecto | Status | Observação |
|---|---|---|
| RESTful design | ✅ | Verbos HTTP corretos, URLs semânticas |
| JWT validation | ✅ | Secret em env, token expirado = 401 |
| Multi-tenant isolation | ✅ | Database-per-tenant, pool manager |
| Rate limiting | ✅ | 100 req/15min, excluído de webhooks |
| Error sanitization | ✅ | Stack trace apenas em dev |
| Graceful shutdown | ✅ | SIGINT/SIGTERM fecham pools |
| Input validation | ⚠️ | Limitada; BaseService não valida schemas |
| HTTPS enforcement | ⚠️ | Não verifica scheme (assume reverse proxy) |
| API versioning | ❌ | Sem prefixo `/v1/` — dificulta breaking changes |
| Swagger/OpenAPI | ❌ | Sem documentação automática |

---

## 3. Payment Gateways (PHP)

### 3.1 Arquitetura — Strategy Pattern

```
PaymentGatewayInterface (Contract)
    │
    ▼
AbstractGateway (Shared: HTTP, config, response formatters)
    │
    ├── StripeGateway
    ├── MercadoPagoGateway
    └── PagSeguroGateway

GatewayManager (Factory/Registry)
    ├── make(slug)           → Instância sem config
    ├── resolve(slug, ...)   → Instância configurada
    └── resolveFromRow(row)  → Instância do banco
```

### 3.2 Interface do Gateway

Todo gateway implementa:

| Método | Função |
|---|---|
| `getSlug()` | Identificador (`'stripe'`, `'mercadopago'`, `'pagseguro'`) |
| `getDisplayName()` | Nome para exibição |
| `supports($method)` | Suporta: pix, credit_card, boleto, debit_card |
| `createCharge($data)` | Cria cobrança → retorna external_id, status, payment_url |
| `getChargeStatus($externalId)` | Consulta status no provedor |
| `refund($externalId, $amount)` | Estorno total/parcial |
| `validateWebhookSignature(...)` | Validação HMAC do webhook |
| `parseWebhookPayload(...)` | Padroniza payload do webhook |

### 3.3 Resposta Padronizada

Todos os gateways retornam:
```php
[
  'success' => bool,
  'external_id' => string,
  'status' => 'pending|approved|rejected|refunded|cancelled',
  'payment_url' => ?string,  // Link de pagamento
  'qr_code' => ?string,      // PIX QR code
  'boleto_url' => ?string,   // URL do boleto
  'expires_at' => ?string,   // ISO 8601
  'raw' => array,             // Resposta original do provedor
]
```

### 3.4 Gateways Implementados

| Gateway | Métodos Suportados | API Pattern | Idempotência |
|---|---|---|---|
| **Stripe** | auto, credit_card, debit_card, pix, boleto | PaymentIntent / Checkout Session | Via Stripe nativo |
| **Mercado Pago** | auto, pix, credit_card, debit_card, boleto | /v1/payments / /checkout/preferences | X-Idempotency-Key header |
| **PagSeguro** | auto, pix, credit_card, debit_card, boleto | Orders API (/orders) | Por external_reference |

### 3.5 Segurança de Webhooks

| Gateway | Header | Algoritmo | Proteção Anti-Replay |
|---|---|---|---|
| **Stripe** | `stripe-signature` | HMAC-SHA256(timestamp.body, secret) | Timestamp ≤ 300s |
| **Mercado Pago** | `x-signature` | HMAC-SHA256(manifest, secret) | Timestamp no manifest |
| **PagSeguro** | `x-pagseguro-signature` | HMAC-SHA256(body, secret) | — |

**Comparação timing-safe:** Node.js `timingSafeEqual()` — previne timing attacks.

### 3.6 Avaliação dos Gateways

| Aspecto | Status | Observação |
|---|---|---|
| Strategy Pattern | ✅ | Extensível, bem abstraído |
| Interface compliance | ✅ | Todos implementam PaymentGatewayInterface |
| HMAC validation | ✅ | Timing-safe comparison |
| Replay protection | ✅ | Timestamp validation (Stripe: 300s) |
| SSL verification | ✅/⚠️ | Prod: ON, Sandbox: OFF |
| Error handling | ✅ | Fallback com errorResponse() |
| Logging | ⚠️ | Sem logging estruturado de transações |
| Retry logic | ❌ | Sem retry em falha de comunicação |

---

## 4. Integração NF-e (Nota Fiscal Eletrônica)

### 4.1 Visão Geral (24 Services)

A camada NF-e é a maior e mais complexa do sistema, com **24 services PHP** organizados por responsabilidade:

### 4.2 Services Core

| Service | Responsabilidade | Linhas (aprox) |
|---|---|---|
| `NfeService.php` | Comunicação com SEFAZ (emissão, cancelamento, consulta) | 500+ |
| `NfeXmlBuilder.php` | Montagem do XML 4.00 com cálculo de impostos | 400+ |
| `NfeXmlValidator.php` | Validação de XML antes da submissão | 200+ |
| `NfePdfGenerator.php` | Geração de DANFE PDF (sped-da) | 100+ |

### 4.3 Services de Workflow

| Service | Responsabilidade |
|---|---|
| `NfeOrderDataService.php` | Transforma pedido → payload NF-e |
| `NfeContingencyService.php` | Modo offline (SCAN, SVC) |
| `NfeQueueService.php` | Fila de emissão em lote |
| `NfeWebhookService.php` | Recebe webhooks da SEFAZ |
| `NfeManifestationService.php` | Manifestação de Destino (MDF-e) |

### 4.4 Services de Dados

| Service | Responsabilidade |
|---|---|
| `NfeDetailService.php` | Detalhes de NF-e emitida |
| `NfeStorageService.php` | Armazenamento local (XML, PDF, JSON) |
| `NfeDownloadService.php` | Download de NF-e autorizada da SEFAZ |
| `NfeDistDFeService.php` | Distribuição NFC-e |
| `NfeBackupService.php` | Backup local de XMLs e PDFs |
| `NfeBatchDownloadService.php` | Download em lote da SEFAZ |
| `NfeBackupManagementService.php` | Gestão de retenção de backups |

### 4.5 Services de Relatórios e Compliance

| Service | Responsabilidade |
|---|---|
| `NfeDashboardService.php` | Métricas: emitidas, rejeitadas, canceladas |
| `NfeFiscalReportService.php` | Relatórios fiscais |
| `NfeSpedFiscalService.php` | EFD-ICMS/IPI (SPED Fiscal) |
| `NfeSintegraService.php` | SINTEGRA (arquivos estaduais) |
| `NfeExportService.php` | Exportação CSV/XML |
| `NfeAuditService.php` | Trilha de auditoria e compliance |

### 4.6 Fluxo de Emissão NF-e

```
Pedido Criado
  ↓
NfeOrderDataService → transforma pedido em payload fiscal
  ↓
NfeXmlBuilder.build() → XML 4.00 + cálculo impostos (ICMS, PIS, COFINS, IPI)
  ↓
NfeXmlValidator → valida estrutura XML
  ↓
NfeService → assina digitalmente + submete à SEFAZ (sped-nfe/Tools)
  ↓
SEFAZ → retorna recibo
  ↓
NfeService → consulta status (polling)
  ↓
SEFAZ → autorizada / rejeitada
  ↓
NfePdfGenerator → gera DANFE PDF
  ↓
NfeStorageService → salva XML + PDF localmente
  ↓
NfeWebhookService → recebe webhook SEFAZ (se configurado)
  ↓
NfeAuditService → registra auditoria
```

### 4.7 Avaliação NF-e

| Aspecto | Status | Observação |
|---|---|---|
| Cobertura funcional | ⭐⭐⭐⭐⭐ | Emissão, cancelamento, contingência, backup, compliance |
| Biblioteca sped-nfe | ✅ | Lib madura e amplamente utilizada |
| Cálculo de impostos | ✅ | TaxCalculator + IBPTax |
| Contingency mode | ✅ | SCAN/SVC para indisponibilidade SEFAZ |
| Audit trail | ✅ | NfeAuditService + NfeAuditLog model |
| Queue processing | ✅ | NfeQueueService para emissão em lote |
| Error handling | ⚠️ | Exception messages expostas em JSON responses |
| Unit tests | ❌ | Sem testes unitários para services NF-e |

---

## 5. Docker

### 5.1 Serviços

| Serviço | Imagem | Porta | Healthcheck |
|---|---|---|---|
| **app** | php:8.1-apache (custom) | 8080 | curl every 30s |
| **db** | mysql:8 | 3306 | mysqladmin ping |
| **api** | node:20-alpine | 3000 | wget every 30s |

### 5.2 Extensões PHP Instaladas

`pdo_mysql`, `mbstring`, `gd`, `zip`, `xml`, `curl`, `opcache`, `bcmath`

### 5.3 Configuração PHP (`docker/php.ini`)

| Parâmetro | Valor | Observação |
|---|---|---|
| display_errors | On | ⚠️ Desenvolvimento apenas |
| memory_limit | 256M | Adequado |
| upload_max_filesize | 50M | Alta — verificar necessidade |
| post_max_size | 55M | Coerente com upload |
| max_execution_time | 120 | Alto — NF-e processa longo |
| session.cookie_httponly | 1 | ✅ Seguro |
| session.use_strict_mode | 1 | ✅ Seguro |
| opcache.enable | 0 | ⚠️ Desabilitado (dev only) |

### 5.4 Avaliação Docker

| Aspecto | Status | Observação |
|---|---|---|
| Healthchecks | ✅ | Todos os 3 services |
| Persistent storage | ✅ | Volume nomeado para MySQL |
| Network isolation | ✅ | Bridge network dedicada |
| PHP extensions | ✅ | Todas necessárias instaladas |
| Graceful shutdown | ✅ | Node.js com graceful |
| SSL/TLS | ❌ | Sem configuração HTTPS |
| Secrets management | ⚠️ | Credenciais no docker-compose.yml |
| Production readiness | ⚠️ | display_errors On, opcache Off |

---

## 6. Conclusões e Prioridades

### Forças
1. ✅ API Node.js bem estruturada com BaseController/BaseService
2. ✅ Multi-tenant isolation consistente (PHP + Node.js)
3. ✅ Payment Gateways com Strategy Pattern exemplar
4. ✅ Webhook security com HMAC + timing-safe + replay protection
5. ✅ NF-e com 24 services cobrindo todo o ciclo fiscal
6. ✅ Docker com healthchecks e graceful shutdown

### Prioridades de Melhoria

| Prioridade | Item | Esforço | Impacto |
|---|---|---|---|
| 1 | API versioning (`/v1/`) | Baixo | Alto (manutenibilidade) |
| 2 | Input validation (schemas) na API | Médio | Alto (segurança) |
| 3 | Retry logic em gateways de pagamento | Médio | Alto (resiliência) |
| 4 | Mover credenciais Docker para secrets | Baixo | Alto (segurança) |
| 5 | Testes unitários para NF-e services | Alto | Alto (confiabilidade) |
| 6 | Swagger/OpenAPI documentation | Médio | Médio (manutenibilidade) |
| 7 | Habilitar opcache em produção | Baixo | Médio (performance) |
| 8 | Logging estruturado em gateways | Médio | Médio (observabilidade) |
