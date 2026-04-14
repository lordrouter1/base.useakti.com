import { Router } from 'express';
import { OrderController } from '../controllers/OrderController.js';
import { validateId, validateBody } from '../middlewares/validateMiddleware.js';

/**
 * Order Routes — FEAT-012
 */
const router = Router();
const ctrl = new OrderController();

/**
 * @openapi
 * /orders/search:
 *   get:
 *     summary: Busca pedidos por termo
 *     tags: [Orders]
 *     parameters:
 *       - in: query
 *         name: q
 *         schema:
 *           type: string
 *         description: Termo de busca (número, cliente)
 *     responses:
 *       200:
 *         description: Resultados da busca
 */
router.get('/search', ctrl.search);

/**
 * @openapi
 * /orders:
 *   get:
 *     summary: Lista todos os pedidos
 *     tags: [Orders]
 *     parameters:
 *       - in: query
 *         name: page
 *         schema:
 *           type: integer
 *       - in: query
 *         name: limit
 *         schema:
 *           type: integer
 *       - in: query
 *         name: status
 *         schema:
 *           type: string
 *         description: Filtrar por status
 *     responses:
 *       200:
 *         description: Lista paginada de pedidos
 */
router.get('/', ctrl.index);

/**
 * @openapi
 * /orders/{id}:
 *   get:
 *     summary: Retorna um pedido pelo ID
 *     tags: [Orders]
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: integer
 *     responses:
 *       200:
 *         description: Pedido encontrado
 *       404:
 *         description: Pedido não encontrado
 */
router.get('/:id', validateId, ctrl.show);

/**
 * @openapi
 * /orders/{id}/items:
 *   get:
 *     summary: Retorna os itens de um pedido
 *     tags: [Orders]
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: integer
 *     responses:
 *       200:
 *         description: Lista de itens do pedido
 *       404:
 *         description: Pedido não encontrado
 */
router.get('/:id/items', validateId, ctrl.items);

/**
 * @openapi
 * /orders:
 *   post:
 *     summary: Cria um novo pedido
 *     tags: [Orders]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             required: [customer_id]
 *             properties:
 *               customer_id:
 *                 type: integer
 *               items:
 *                 type: array
 *                 items:
 *                   type: object
 *                   properties:
 *                     product_id:
 *                       type: integer
 *                     quantity:
 *                       type: number
 *                     unit_price:
 *                       type: number
 *     responses:
 *       201:
 *         description: Pedido criado
 */
router.post('/', validateBody, ctrl.store);

/**
 * @openapi
 * /orders/{id}:
 *   put:
 *     summary: Atualiza um pedido
 *     tags: [Orders]
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: integer
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *     responses:
 *       200:
 *         description: Pedido atualizado
 *       404:
 *         description: Pedido não encontrado
 */
router.put('/:id', validateId, validateBody, ctrl.update);

/**
 * @openapi
 * /orders/{id}:
 *   delete:
 *     summary: Remove um pedido
 *     tags: [Orders]
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: integer
 *     responses:
 *       200:
 *         description: Pedido removido
 *       404:
 *         description: Pedido não encontrado
 */
router.delete('/:id', validateId, ctrl.destroy);

export default router;
