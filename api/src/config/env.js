import 'dotenv/config';

export const env = Object.freeze({
  NODE_ENV: process.env.NODE_ENV || 'development',
  PORT: parseInt(process.env.PORT, 10) || 3000,

  // Database
  DB_HOST: process.env.DB_HOST || '127.0.0.1',
  DB_PORT: parseInt(process.env.DB_PORT, 10) || 3306,
  DB_NAME: process.env.DB_NAME || 'akti',
  DB_USER: process.env.DB_USER || 'root',
  DB_PASS: process.env.DB_PASS || '',

  // JWT
  JWT_SECRET: process.env.JWT_SECRET || '',

  // CORS
  CORS_ORIGIN_PATTERN: process.env.CORS_ORIGIN_PATTERN || '.useakti.com',

  // Rate Limiting
  RATE_LIMIT_WINDOW_MS: parseInt(process.env.RATE_LIMIT_WINDOW_MS, 10) || 900_000,
  RATE_LIMIT_MAX_REQUESTS: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS, 10) || 100,
});
