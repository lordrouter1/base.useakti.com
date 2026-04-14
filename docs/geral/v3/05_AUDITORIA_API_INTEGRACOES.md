# Auditoria de API e Integrações — Akti v3

> **Data da Auditoria:** 14/04/2025
> **Escopo:** API Node.js (Express), gateways de pagamento, NF-e, webhooks
> **Auditor:** Auditoria Automatizada via Análise Estática de Código
> **Classificação de Severidade:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## 1. Resumo Executivo

| Aspecto | Nota | Tendência vs v2 |
|---------|------|------------------|
| API Node.js (Express) | ✅ B+ | = Mantido |
| JWT Authentication | ✅ A | = Mantido |
| CORS Configuration | ⚠️ C | = Mantido |
| Rate Limiting API | ⚠️ B | = Mantido |
| Payment Gateways | ✅ B+ | = Mantido |
| NF-e Integration | ✅ A | ↑ Melhorado (eventos) |
| Webhooks | ⚠️ B | = Mantido |

**Nota Geral: B** (v2: B-)

A API Node.js está bem estruturada com Express + Sequelize, JWT para autenticação, e multi-tenant pooling. O módulo NF-e evoluiu com 10 event listeners. Principal gap: CORS excessivamente permissivo e rate limiting apenas por IP.

---

## 2. API Node.js — Estrutura

### 2.1 Arquitetura

**Pasta:** `api/`

```
api/
├── server.js           # Entry point
├── package.json        # Dependências
└── src/
    ├── app.js          # Express setup
    ├── config/         # Database, CORS, etc.
    ├── controllers/    # Request handlers
    ├── middlewares/     # Auth, validation
    ├── models/         # Sequelize models
    ├── routes/         # Route definitions
    ├── services/       # Business logic
    └── utils/          # Helpers
```

**Stack:** Express.js + Sequelize ORM

### 2.2 Multi-Tenant Pooling

- ✅ Connection pooling por tenant database
- ✅ Tenant resolution via JWT payload
- ✅ Database switching transparente

---

## 3. JWT Authentication

### Status: ✅ Aprovado

| Aspecto | Status |
|---------|--------|
| Token generation | ✅ Via login endpoint |
| Token expiry | ✅ Configurável |
| Token validation | ✅ Middleware `auth` |
| Refresh tokens | ⚠️ Não implementado |
| Token revocation | ⚠️ Não implementado (sem blacklist) |

**Recomendação:** Implementar refresh tokens e blacklist para tokens comprometidos.

---

## 4. CORS Configuration

### Status: ⚠️ C — Excessivamente permissivo

**Arquivo:** `api/src/config/cors.js`

**Problemas:**
1. **Permite `origin: null`** — requisições de `file://` e redirects inter-origin
2. **Regex de subdomínio frouxa** — pode ser bypassada com subdomínios maliciosos
3. **`credentials: true` com padrão amplo** — risco de CSRF cross-origin

**Correção Recomendada:**
```javascript
const allowedOrigins = [
    'https://app.akti.com.br',
    /^https:\/\/[a-z0-9]+\.akti\.com\.br$/
];

const corsOptions = {
    origin: (origin, callback) => {
        if (!origin) return callback(null, false); // Bloqueia null
        const allowed = allowedOrigins.some(o =>
            o instanceof RegExp ? o.test(origin) : o === origin
        );
        callback(null, allowed);
    },
    credentials: true,
    methods: ['GET', 'POST', 'PUT', 'DELETE'],
    allowedHeaders: ['Content-Type', 'Authorization']
};
```

---

## 5. Rate Limiting

### Status: ⚠️ B

**Implementação:** Rate limiting por IP address no middleware Express.

**Gaps:**
- ⚠️ Sem rate limiting por usuário/token (um usuário pode usar múltiplos IPs)
- ⚠️ Sem rate limiting por endpoint (endpoints sensíveis como login devem ter limites menores)
- ⚠️ Sem sliding window (fixed window pode ter bursts no boundary)

**Recomendação:**
```javascript
const rateLimit = require('express-rate-limit');

// Global
app.use(rateLimit({ windowMs: 15 * 60 * 1000, max: 100 }));

// Login endpoint
app.use('/auth/login', rateLimit({ windowMs: 15 * 60 * 1000, max: 5 }));
```

---

## 6. Payment Gateways

### Status: ✅ B+

**Pattern:** Strategy pattern via `app/gateways/`

**Gateways implementados:**
| Gateway | Status | Tokenização | Webhooks |
|---------|--------|-------------|----------|
| MercadoPago | ✅ | ✅ | ✅ |
| Stripe | ✅ | ✅ | ✅ |
| PagSeguro | ✅ | ✅ | ✅ |

**Verificações de segurança:**
- ✅ Credenciais via config (não hardcoded)
- ✅ Webhook signature validation
- ✅ HTTPS obrigatório
- ⚠️ PCI compliance: dados de cartão não transitam pelo servidor (tokenização client-side)

---

## 7. NF-e / NFC-e Integration

### Status: ✅ A (Melhorado vs v2)

**Services:**
| Service | Responsabilidade | Linhas |
|---------|-----------------|--------|
| `NfeService.php` | Core de emissão/cancelamento | 2069 |
| `NfeDocument.php` (model) | Persistência de documentos | ~500 |
| `NfeLog.php` (model) | Log de operações fiscais | ~200 |

**Event System (Novo em v3):**
10 event listeners em `app/bootstrap/events.php`:
- `nfe.authorized` → Log + notificação
- `nfe.cancelled` → Log + reversão estoque
- `nfe.correction_letter` → Log
- `nfe.denied` → Log + alerta admin
- `nfe.returned` → Log + processamento
- `nfe.usage` → Log
- `nfe.status` → Log + atualização estado
- `nfe.disabled` → Log
- `nfe.contingency.on/off` → Log + flag de modo

**Certificado Digital:** Gerenciado por tenant via TenantManager.

**Comunicação SEFAZ:** Via web services SOAP com timeout e retry.

---

## 8. Webhooks

### Status: ⚠️ B

**Implementações:**
- ✅ Payment gateway webhooks (MercadoPago, Stripe, PagSeguro)
- ✅ Signature validation para webhooks de pagamento
- ⚠️ Sem retry automático para falhas
- ⚠️ Sem idempotência (reprocessamento pode duplicar operações)
- ⚠️ Sem logging estruturado de webhooks recebidos

**Recomendação:** Implementar tabela `webhook_events` com:
```sql
CREATE TABLE webhook_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL,
    event_id VARCHAR(255) NOT NULL UNIQUE,
    payload JSON,
    status ENUM('received', 'processing', 'completed', 'failed'),
    attempts INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL
);
```

---

## 9. Evolução vs. v2

### Melhorias desde v2

| Aspecto | v2 | v3 |
|---------|----|----|
| NF-e Event System | Sem eventos | ✅ 10 listeners |
| API Structure | Básica | ✅ Consolidada |
| Payment Gateways | 2 gateways | 3 gateways |

### Issues Mantidas

| Issue | Severidade |
|-------|-----------|
| CORS excessivamente permissivo | 🟠 ALTO |
| Rate limiting apenas por IP | 🟡 MÉDIO |
| Sem refresh tokens JWT | 🟡 MÉDIO |
| Sem token revocation | 🟡 MÉDIO |
| Webhooks sem idempotência | 🟡 MÉDIO |
| Webhooks sem retry | 🟡 MÉDIO |

### Novas Observações

| Observação | Nota |
|-----------|------|
| NfeService.php com 2069 linhas | 🟠 ALTO — god class (ver ARCH-013) |
| Event system apenas para NF-e | 🟢 BAIXO — oportunidade de expansão |
