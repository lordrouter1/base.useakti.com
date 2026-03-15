import { HTTP_STATUS } from '../config/constants.js';
import { env } from '../config/env.js';

/**
 * Global error-handling middleware.
 * Must be registered AFTER all routes (4-argument signature required by Express).
 */
// eslint-disable-next-line no-unused-vars
export function errorHandler(err, _req, res, _next) {
  const status = err.status || HTTP_STATUS.INTERNAL_ERROR;
  const message = err.message || 'Internal Server Error';

  if (env.NODE_ENV === 'development') {
    console.error('[Error]', err);
  }

  return res.status(status).json({
    error: message,
    ...(env.NODE_ENV === 'development' && { stack: err.stack }),
  });
}
