import { BaseController } from './BaseController.js';
import { OrderService } from '../services/OrderService.js';

/**
 * OrderController — CRUD de pedidos via API REST.
 * FEAT-012: Expansão da API REST
 */
export class OrderController extends BaseController {
    getService(req) {
        const { Order, OrderItem } = req.models;
        return new OrderService(Order, OrderItem);
    }

    /**
     * GET /search?q=...&limit=10
     */
    search = async (req, res, next) => {
        try {
            const service = this.getService(req);
            const q = req.query.q || '';
            const limit = Math.min(parseInt(req.query.limit || '10', 10), 50);
            const results = await service.search(q, { limit });
            return res.json({ data: results });
        } catch (err) {
            return next(err);
        }
    };

    /**
     * GET /:id/items — listar itens do pedido
     */
    items = async (req, res, next) => {
        try {
            const service = this.getService(req);
            const items = await service.findItems(req.params.id);
            return res.json({ data: items });
        } catch (err) {
            return next(err);
        }
    };
}
