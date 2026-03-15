/**
 * Tenant Identification Middleware
 *
 * Resolves the current tenant from:
 *   1. The `x-tenant-id` request header (priority — useful for testing / mobile).
 *   2. The first segment of the Host subdomain (e.g. cliente1.useakti.com → "cliente1").
 *
 * The resolved value is injected into `req.tenantId` so that downstream
 * controllers, services and models can scope queries accordingly.
 */
export function tenantMiddleware(req, _res, next) {
  // 1. Explicit header takes precedence
  const headerTenant = req.headers['x-tenant-id'];

  if (headerTenant) {
    req.tenantId = String(headerTenant).trim();
    return next();
  }

  // 2. Extract from subdomain
  const host = req.hostname || '';
  const parts = host.split('.');

  // A valid subdomain host has at least 3 parts (sub.domain.tld)
  if (parts.length >= 3) {
    req.tenantId = parts[0];
    return next();
  }

  // Fallback: no tenant resolved (useful for health-check, public routes, etc.)
  req.tenantId = null;
  return next();
}
