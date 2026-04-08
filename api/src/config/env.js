import 'dotenv/config';

export const env = Object.freeze({
  NODE_ENV: process.env.NODE_ENV || 'development',
  PORT: parseInt(process.env.PORT, 10) || 3000,

  // ── Master Database (akti_master) ──
  DB_MASTER_HOST: process.env.DB_MASTER_HOST || '127.0.0.1',
  DB_MASTER_PORT: parseInt(process.env.DB_MASTER_PORT, 10) || 3306,
  DB_MASTER_NAME: process.env.DB_MASTER_NAME || 'akti_master',
  DB_MASTER_USER: process.env.DB_MASTER_USER || 'root',
  DB_MASTER_PASS: process.env.DB_MASTER_PASS || '',

  // ── Legacy single-DB variables (kept for backwards compat) ──
  DB_HOST: process.env.DB_HOST || '127.0.0.1',
  DB_PORT: parseInt(process.env.DB_PORT, 10) || 3306,
  DB_NAME: process.env.DB_NAME || 'akti',
  DB_USER: process.env.DB_USER || 'root',
  DB_PASS: process.env.DB_PASS || '',

  // ── Tenant pool tuning ──
  TENANT_POOL_IDLE_MS: parseInt(process.env.TENANT_POOL_IDLE_MS, 10) || 10 * 60 * 1000,

  // JWT
  JWT_SECRET: process.env.JWT_SECRET || (() => {
    if (process.env.NODE_ENV === 'production') {
      throw new Error('JWT_SECRET must be set in production.');
    }
    return 'dev-only-secret';
  })(),

  // CORS
  CORS_ORIGIN_PATTERN: process.env.CORS_ORIGIN_PATTERN || '.useakti.com',

  // Base domain for subdomain resolution (webhooks, checkout)
  BASE_DOMAIN: process.env.BASE_DOMAIN || 'useakti.com',

  // Rate Limiting
  RATE_LIMIT_WINDOW_MS: parseInt(process.env.RATE_LIMIT_WINDOW_MS, 10) || 900_000,
  RATE_LIMIT_MAX_REQUESTS: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS, 10) || 100,
});
