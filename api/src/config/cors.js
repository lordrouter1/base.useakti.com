import { env } from './env.js';

/**
 * CORS options — allows requests from any subdomain that matches the
 * configured pattern (e.g. *.useakti.com) plus localhost during development.
 */
export const corsOptions = {
  origin(origin, callback) {
    // Allow requests with no origin (server-to-server, curl, mobile apps)
    if (!origin) {
      return callback(null, true);
    }

    const pattern = env.CORS_ORIGIN_PATTERN; // e.g. ".useakti.com"
    const isAllowed =
      origin.endsWith(pattern) ||
      origin.includes('localhost') ||
      origin.includes('127.0.0.1') ||
      origin.includes('.akti.com') ||
      origin.includes('akti.com');

    if (isAllowed) {
      return callback(null, true);
    }

    return callback(new Error('Not allowed by CORS'));
  },
  credentials: true,
  optionsSuccessStatus: 200,
};
