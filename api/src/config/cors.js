import { env } from './env.js';

/**
 * CORS options — allows requests from any subdomain that matches the
 * configured pattern (e.g. *.useakti.com) plus localhost during development.
 */
export const corsOptions = {
  origin(origin, callback) {
    // Block requests with no origin in production (prevents null origin attacks).
    // Allow in development for curl/Postman.
    if (!origin) {
      if (env.NODE_ENV === 'development') {
        return callback(null, true);
      }
      return callback(new Error('Not allowed by CORS'));
    }

    const pattern = env.CORS_ORIGIN_PATTERN; // e.g. ".useakti.com"

    // Strict origin validation using regex for subdomains
    const isAllowed =
      /^https?:\/\/([a-z0-9-]+\.)*useakti\.com(:\d+)?$/.test(origin) ||
      origin.endsWith(pattern) ||
      (env.NODE_ENV === 'development' && /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/.test(origin));

    if (isAllowed) {
      return callback(null, true);
    }

    return callback(new Error('Not allowed by CORS'));
  },
  credentials: true,
  optionsSuccessStatus: 200,
};
