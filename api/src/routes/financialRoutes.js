import { Router } from 'express';
import { FinancialController } from '../controllers/FinancialController.js';
import { validateId, validateBody } from '../middlewares/validateMiddleware.js';

/**
 * Financial Routes — FEAT-012
 */
const router = Router();
const ctrl = new FinancialController();

router.get('/summary', ctrl.summary);
router.get('/', ctrl.index);
router.get('/:id', validateId, ctrl.show);
router.post('/', validateBody, ctrl.store);
router.put('/:id', validateId, validateBody, ctrl.update);
router.delete('/:id', validateId, ctrl.destroy);

export default router;
