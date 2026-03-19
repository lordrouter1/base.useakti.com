import { Router } from 'express';
import { authMiddleware } from '../middlewares/authMiddleware.js';
import { tenantMiddleware } from '../middlewares/tenantMiddleware.js';
import productRoutes from './productRoutes.js';

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
 *   router.use('/orders', orderRoutes);
 */
router.use(authMiddleware);
router.use(tenantMiddleware);

// ── Resource routes ──
router.use('/products', productRoutes);
// router.use('/orders', orderRoutes);
// router.use('/customers', customerRoutes);

export default router;
