import { Router } from 'express';
import { authMiddleware } from '../middlewares/authMiddleware.js';

const router = Router();

/**
 * Public routes (no authentication required).
 */
router.get('/status', (_req, res) => {
  res.json({ status: 'ok' });
});

/**
 * Protected routes — require valid JWT.
 *
 * Register resource routers below. Example:
 *
 *   import orderRoutes from './orderRoutes.js';
 *   router.use('/orders', authMiddleware, orderRoutes);
 */
router.use(authMiddleware);

// Future resource routes go here:
// router.use('/orders', orderRoutes);
// router.use('/products', productRoutes);
// router.use('/customers', customerRoutes);

export default router;
