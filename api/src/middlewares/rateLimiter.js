import rateLimit from 'express-rate-limit';
import { env } from '../config/env.js';

/**
 * Global rate limiter — protects all endpoints from excessive requests.
 * Defaults: 100 requests per 15-minute window (configurable via env).
 */
export const rateLimiter = rateLimit({
  windowMs: env.RATE_LIMIT_WINDOW_MS,
  max: env.RATE_LIMIT_MAX_REQUESTS,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: 'Too many requests. Please try again later.' },
});
