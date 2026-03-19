import { Router } from 'express';
import { ProductController } from '../controllers/ProductController.js';

const router = Router();
const ctrl = new ProductController();

// Auth + Tenant middleware já são aplicados em routes/index.js
router.get('/search', ctrl.search);
router.get('/', ctrl.index);
router.get('/:id', ctrl.show);
router.post('/', ctrl.store);
router.put('/:id', ctrl.update);
router.delete('/:id', ctrl.destroy);

export default router;
