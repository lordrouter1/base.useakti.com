import { Router } from 'express';
import { authMiddleware } from '../middlewares/authMiddleware.js';
import { tenantMiddleware } from '../middlewares/tenantMiddleware.js';
import productRoutes from './productRoutes.js';
import customerRoutes from './customerRoutes.js';
import orderRoutes from './orderRoutes.js';
import financialRoutes from './financialRoutes.js';

const router = Router();
/**
 * Public routes (no authentication required).
 */
router.get('/status', (_req, res) => {
  res.json({ status: 'ok' });
});

/**
 * Webhook routes foram movidas para app.js — montadas ANTES do express.json()
 * global para preservar o rawBody necessário para validação HMAC.
 * @see api/src/app.js
 */

/**
 * Protected routes — require valid JWT + tenant resolution.
 *
 * Fluxo:
 *   1. authMiddleware   → valida JWT, popula req.user
 *   2. tenantMiddleware → lê req.user.tenant_db, adquire pool, popula req.db e req.models
 *   3. resource router  → usa req.models nos controllers/services
 *
 * Para adicionar novas rotas protegidas:
 *   import orderRoutes from './orderRoutes.js';
 *   v1Router.use('/orders', orderRoutes);
 */
const v1Router = Router();
v1Router.use(authMiddleware);
v1Router.use(tenantMiddleware);

// ── Resource routes (v1) ──
v1Router.use('/products', productRoutes);
v1Router.use('/customers', customerRoutes);
v1Router.use('/orders', orderRoutes);
v1Router.use('/financial', financialRoutes);

// Mount versioned routes
router.use('/v1', v1Router);

// ── Backward compatibility: unversioned paths redirect to v1 ──
router.use(authMiddleware);
router.use(tenantMiddleware);
router.use('/products', productRoutes);
router.use('/customers', customerRoutes);
router.use('/orders', orderRoutes);
router.use('/financial', financialRoutes);

export default router;
