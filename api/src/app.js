import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import morgan from 'morgan';

import { env } from './config/env.js';
import { corsOptions } from './config/cors.js';
import { rateLimiter } from './middlewares/rateLimiter.js';
import { errorHandler } from './middlewares/errorHandler.js';
import routes from './routes/index.js';
import webhookRoutes from './routes/webhookRoutes.js';

const app = express();

// --------------- Security ---------------
app.use(helmet());
app.use(cors(corsOptions));

// --------------- Logging ----------------
if (env.NODE_ENV !== 'test') {
  app.use(morgan('combined'));
}

// ═══════════════════════════════════════════════════════════════
// WEBHOOKS — Montados ANTES do express.json() global.
// Os webhooks precisam do rawBody para validação HMAC.
// O express.json() global consome o body stream, impossibilitando
// a captura do rawBody pelo verify callback.
// Rate limiter NÃO se aplica a webhooks (gateways fazem retries).
// ═══════════════════════════════════════════════════════════════
app.use('/api/webhooks', webhookRoutes);

// --------------- Rate Limiter -----------
// Aplicado DEPOIS dos webhooks para não bloquear notificações de gateways
app.use(rateLimiter);

// --------------- Parsing ----------------
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// --------------- Routes -----------------
// tenantMiddleware é aplicado DEPOIS do authMiddleware dentro de routes/index.js
// para que req.user.tenant_db esteja disponível.
app.use('/api', routes);

// ----------- Health Check ---------------
app.get('/health', (_req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// ----------- Error Handling -------------
app.use(errorHandler);

export default app;
