import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import morgan from 'morgan';

import { env } from './config/env.js';
import { corsOptions } from './config/cors.js';
import { rateLimiter } from './middlewares/rateLimiter.js';
import { tenantMiddleware } from './middlewares/tenantMiddleware.js';
import { errorHandler } from './middlewares/errorHandler.js';
import routes from './routes/index.js';

const app = express();

// --------------- Security ---------------
app.use(helmet());
app.use(cors(corsOptions));
app.use(rateLimiter);

// --------------- Parsing ----------------
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// --------------- Logging ----------------
if (env.NODE_ENV !== 'test') {
  app.use(morgan('combined'));
}

// ----------- Multi-tenancy --------------
app.use(tenantMiddleware);

// --------------- Routes -----------------
app.use('/api', routes);

// ----------- Health Check ---------------
app.get('/health', (_req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// ----------- Error Handling -------------
app.use(errorHandler);

export default app;
