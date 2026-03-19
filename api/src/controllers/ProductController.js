import { BaseController } from './BaseController.js';
import { ProductService } from '../services/ProductService.js';

export class ProductController extends BaseController {
  /**
   * Cria uma instância de ProductService usando os models do tenant
   * resolvido pelo tenantMiddleware (req.models).
   */
  getService(req) {
    const { Product, ProductGradeCombination } = req.models;
    return new ProductService(Product, ProductGradeCombination);
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
