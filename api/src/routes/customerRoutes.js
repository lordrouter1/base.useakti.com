import { Router } from 'express';
import { CustomerController } from '../controllers/CustomerController.js';
import { validateId, validateBody } from '../middlewares/validateMiddleware.js';

/**
 * Customer Routes — FEAT-012
 */
const router = Router();
const ctrl = new CustomerController();

/**
 * @openapi
 * /customers/search:
 *   get:
 *     summary: Busca clientes por termo
 *     tags: [Customers]
 *     parameters:
 *       - in: query
 *         name: q
 *         schema:
 *           type: string
 *         description: Termo de busca (nome, e-mail, documento)
 *     responses:
 *       200:
 *         description: Resultados da busca
 */
router.get('/search', ctrl.search);

/**
 * @openapi
 * /customers:
 *   get:
 *     summary: Lista todos os clientes
 *     tags: [Customers]
 *     parameters:
 *       - in: query
 *         name: page
 *         schema:
 *           type: integer
 *         description: Número da página
 *       - in: query
 *         name: limit
 *         schema:
 *           type: integer
 *         description: Itens por página
 *     responses:
 *       200:
 *         description: Lista paginada de clientes
 */
router.get('/', ctrl.index);

/**
 * @openapi
 * /customers/{id}:
 *   get:
 *     summary: Retorna um cliente pelo ID
 *     tags: [Customers]
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: integer
 *     responses:
 *       200:
 *         description: Cliente encontrado
 *       404:
 *         description: Cliente não encontrado
 */
router.get('/:id', validateId, ctrl.show);

/**
 * @openapi
 * /customers:
 *   post:
 *     summary: Cria um novo cliente
 *     tags: [Customers]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             required: [name]
 *             properties:
 *               name:
 *                 type: string
 *               email:
 *                 type: string
 *               phone:
 *                 type: string
 *               document:
 *                 type: string
 *     responses:
 *       201:
 *         description: Cliente criado
 */
router.post('/', validateBody, ctrl.store);

/**
 * @openapi
 * /customers/{id}:
 *   put:
 *     summary: Atualiza um cliente
 *     tags: [Customers]
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
 *             properties:
 *               name:
 *                 type: string
 *               email:
 *                 type: string
 *               phone:
 *                 type: string
 *     responses:
 *       200:
 *         description: Cliente atualizado
 *       404:
 *         description: Cliente não encontrado
 */
router.put('/:id', validateId, validateBody, ctrl.update);

/**
 * @openapi
 * /customers/{id}:
 *   delete:
 *     summary: Remove um cliente
 *     tags: [Customers]
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: integer
 *     responses:
 *       200:
 *         description: Cliente removido
 *       404:
 *         description: Cliente não encontrado
 */
router.delete('/:id', validateId, ctrl.destroy);

export default router;
