import { Router } from 'express';
import { OrderController } from '../controllers/OrderController.js';
import { validateId, validateBody } from '../middlewares/validateMiddleware.js';

/**
 * Order Routes — FEAT-012
 */
const router = Router();
const ctrl = new OrderController();

router.get('/search', ctrl.search);
router.get('/', ctrl.index);
router.get('/:id', validateId, ctrl.show);
router.get('/:id/items', validateId, ctrl.items);
router.post('/', validateBody, ctrl.store);
router.put('/:id', validateId, validateBody, ctrl.update);
router.delete('/:id', validateId, ctrl.destroy);

export default router;
