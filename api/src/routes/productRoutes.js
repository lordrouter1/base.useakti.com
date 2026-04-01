import { Router } from 'express';
import { ProductController } from '../controllers/ProductController.js';
import { validateId, validateBody } from '../middlewares/validateMiddleware.js';

const router = Router();
const ctrl = new ProductController();

/**
 * @openapi
 * /products:
 *   get:
 *     summary: Lista todos os produtos
 *     tags: [Products]
 *     responses:
 *       200:
 *         description: Lista de produtos
 */

/**
 * @openapi
 * /products/search:
 *   get:
 *     summary: Busca produtos por termo
 *     tags: [Products]
 *     parameters:
 *       - in: query
 *         name: q
 *         schema:
 *           type: string
 *         description: Termo de busca
 *     responses:
 *       200:
 *         description: Resultados da busca
 */

/**
 * @openapi
 * /products/{id}:
 *   get:
 *     summary: Retorna um produto pelo ID
 *     tags: [Products]
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: integer
 *     responses:
 *       200:
 *         description: Produto encontrado
 *       404:
 *         description: Produto não encontrado
 */

/**
 * @openapi
 * /products:
 *   post:
 *     summary: Cria um novo produto
 *     tags: [Products]
 *     responses:
 *       201:
 *         description: Produto criado
 */

/**
 * @openapi
 * /products/{id}:
 *   put:
 *     summary: Atualiza um produto
 *     tags: [Products]
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: integer
 *     responses:
 *       200:
 *         description: Produto atualizado
 */

/**
 * @openapi
 * /products/{id}:
 *   delete:
 *     summary: Remove um produto
 *     tags: [Products]
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: integer
 *     responses:
 *       200:
 *         description: Produto removido
 */

// Auth + Tenant middleware já são aplicados em routes/index.js
router.get('/search', ctrl.search);
router.get('/', ctrl.index);
router.get('/:id', validateId, ctrl.show);
router.post('/', validateBody, ctrl.store);
router.put('/:id', validateId, validateBody, ctrl.update);
router.delete('/:id', validateId, ctrl.destroy);

export default router;
