import { HTTP_STATUS, PAGINATION } from '../config/constants.js';
import { toPositiveInt } from '../utils/helpers.js';

/**
 * BaseController — thin HTTP adapter for any BaseService.
 *
 * Na arquitetura multi-tenant, os models são resolvidos por request
 * (via req.models injetado pelo tenantMiddleware). Cada subclasse
 * deve implementar `getService(req)` que retorna uma instância do
 * service usando os models do tenant correto.
 *
 * Usage:
 *   class ProductController extends BaseController {
 *     getService(req) {
 *       const { Product, ProductGradeCombination } = req.models;
 *       return new ProductService(Product, ProductGradeCombination);
 *     }
 *   }
 */
export class BaseController {
  /**
   * Deve ser implementado pelas subclasses.
   * Recebe `req` e retorna uma instância do service com os models do tenant.
   *
   * @param {import('express').Request} req
   * @returns {import('../services/BaseService.js').BaseService}
   */
  getService(req) {
    throw new Error('BaseController.getService() must be implemented by subclass.');
  }

  /**
   * GET /  — list all records (paginated).
   */
  index = async (req, res, next) => {
    try {
      const service = this.getService(req);
      const page = toPositiveInt(req.query.page, PAGINATION.DEFAULT_PAGE);
      const limit = toPositiveInt(req.query.limit, PAGINATION.DEFAULT_LIMIT);
      const result = await service.findAll({ page, limit });
      return res.json(result);
    } catch (err) {
      return next(err);
    }
  };

  /**
   * GET /:id  — show a single record.
   */
  show = async (req, res, next) => {
    try {
      const service = this.getService(req);
      const record = await service.findById(req.params.id);
      if (!record) {
        return res.status(HTTP_STATUS.NOT_FOUND).json({ error: 'Resource not found.' });
      }
      return res.json({ data: record });
    } catch (err) {
      return next(err);
    }
  };

  /**
   * POST /  — create a new record.
   */
  store = async (req, res, next) => {
    try {
      const service = this.getService(req);
      const record = await service.create(req.body);
      return res.status(HTTP_STATUS.CREATED).json({ data: record });
    } catch (err) {
      return next(err);
    }
  };

  /**
   * PUT /:id  — update a record.
   */
  update = async (req, res, next) => {
    try {
      const service = this.getService(req);
      const updated = await service.update(req.params.id, req.body);
      if (!updated) {
        return res.status(HTTP_STATUS.NOT_FOUND).json({ error: 'Resource not found.' });
      }
      return res.status(HTTP_STATUS.NO_CONTENT).end();
    } catch (err) {
      return next(err);
    }
  };

  /**
   * DELETE /:id  — remove a record.
   */
  destroy = async (req, res, next) => {
    try {
      const service = this.getService(req);
      const deleted = await service.delete(req.params.id);
      if (!deleted) {
        return res.status(HTTP_STATUS.NOT_FOUND).json({ error: 'Resource not found.' });
      }
      return res.status(HTTP_STATUS.NO_CONTENT).end();
    } catch (err) {
      return next(err);
    }
  };
}
