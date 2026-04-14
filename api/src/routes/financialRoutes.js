import { Router } from 'express';
import { FinancialController } from '../controllers/FinancialController.js';
import { validateId, validateBody } from '../middlewares/validateMiddleware.js';

/**
 * Financial Routes — FEAT-012
 */
const router = Router();
const ctrl = new FinancialController();

/**
 * @openapi
 * /financial/summary:
 *   get:
 *     summary: Resumo financeiro (entradas, saídas, saldo)
 *     tags: [Financial]
 *     parameters:
 *       - in: query
 *         name: month
 *         schema:
 *           type: integer
 *       - in: query
 *         name: year
 *         schema:
 *           type: integer
 *     responses:
 *       200:
 *         description: Resumo financeiro do período
 */
router.get('/summary', ctrl.summary);

/**
 * @openapi
 * /financial:
 *   get:
 *     summary: Lista lançamentos financeiros
 *     tags: [Financial]
 *     parameters:
 *       - in: query
 *         name: page
 *         schema:
 *           type: integer
 *       - in: query
 *         name: type
 *         schema:
 *           type: string
 *           enum: [receita, despesa]
 *       - in: query
 *         name: status
 *         schema:
 *           type: string
 *           enum: [pendente, pago, cancelado]
 *     responses:
 *       200:
 *         description: Lista paginada de lançamentos
 */
router.get('/', ctrl.index);

/**
 * @openapi
 * /financial/{id}:
 *   get:
 *     summary: Retorna um lançamento financeiro pelo ID
 *     tags: [Financial]
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: integer
 *     responses:
 *       200:
 *         description: Lançamento encontrado
 *       404:
 *         description: Lançamento não encontrado
 */
router.get('/:id', validateId, ctrl.show);

/**
 * @openapi
 * /financial:
 *   post:
 *     summary: Cria um lançamento financeiro
 *     tags: [Financial]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             required: [type, amount, due_date]
 *             properties:
 *               type:
 *                 type: string
 *                 enum: [receita, despesa]
 *               amount:
 *                 type: number
 *               due_date:
 *                 type: string
 *                 format: date
 *               description:
 *                 type: string
 *     responses:
 *       201:
 *         description: Lançamento criado
 */
router.post('/', validateBody, ctrl.store);

/**
 * @openapi
 * /financial/{id}:
 *   put:
 *     summary: Atualiza um lançamento financeiro
 *     tags: [Financial]
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
 *         description: Lançamento atualizado
 *       404:
 *         description: Lançamento não encontrado
 */
router.put('/:id', validateId, validateBody, ctrl.update);

/**
 * @openapi
 * /financial/{id}:
 *   delete:
 *     summary: Remove um lançamento financeiro
 *     tags: [Financial]
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: integer
 *     responses:
 *       200:
 *         description: Lançamento removido
 *       404:
 *         description: Lançamento não encontrado
 */
router.delete('/:id', validateId, ctrl.destroy);

export default router;
