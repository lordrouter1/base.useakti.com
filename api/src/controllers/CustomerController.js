import { BaseController } from './BaseController.js';
import { CustomerService } from '../services/CustomerService.js';

/**
 * CustomerController — CRUD de clientes via API REST.
 * FEAT-012: Expansão da API REST
 */
export class CustomerController extends BaseController {
    getService(req) {
        const { Customer } = req.models;
        return new CustomerService(Customer);
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
}
