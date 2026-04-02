import { Router } from 'express';
import { CustomerController } from '../controllers/CustomerController.js';
import { validateId, validateBody } from '../middlewares/validateMiddleware.js';

/**
 * Customer Routes — FEAT-012
 */
const router = Router();
const ctrl = new CustomerController();

router.get('/search', ctrl.search);
router.get('/', ctrl.index);
router.get('/:id', validateId, ctrl.show);
router.post('/', validateBody, ctrl.store);
router.put('/:id', validateId, validateBody, ctrl.update);
router.delete('/:id', validateId, ctrl.destroy);

export default router;
