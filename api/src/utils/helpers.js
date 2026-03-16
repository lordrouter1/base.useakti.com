/**
 * Generic helpers and validators used across the application.
 */

/**
 * Wraps an async Express handler so that rejected promises are forwarded to
 * the error-handling middleware automatically.
 *
 * Usage:
 *   router.get('/items', asyncHandler(controller.index));
 */
export function asyncHandler(fn) {
  return (req, res, next) => Promise.resolve(fn(req, res, next)).catch(next);
}

/**
 * Returns a sanitized positive integer from a raw value, or a default.
 */
export function toPositiveInt(value, fallback = 0) {
  const parsed = parseInt(value, 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}
