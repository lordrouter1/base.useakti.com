import { HTTP_STATUS, PAGINATION } from '../config/constants.js';
import { toPositiveInt } from '../utils/helpers.js';

/**
 * BaseController — thin HTTP adapter for any BaseService.
 *
 * Provides standard CRUD endpoint handlers that delegate to the
 * underlying service. Extend for resource-specific behaviour.
 *
 * Usage:
 *   class OrderController extends BaseController {
 *     constructor() { super(new OrderService()); }
 *   }
 */
export class BaseController {
  /**
   * @param {import('../services/BaseService.js').BaseService} service
   */
  constructor(service) {
    this.service = service;
  }

  /**
   * GET /  — list all records (paginated).
   */
  index = async (req, res, next) => {
    try {
      const page = toPositiveInt(req.query.page, PAGINATION.DEFAULT_PAGE);
      const limit = toPositiveInt(req.query.limit, PAGINATION.DEFAULT_LIMIT);
      const result = await this.service.findAll(req.tenantId, { page, limit });
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
      const record = await this.service.findById(req.tenantId, req.params.id);
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
      const record = await this.service.create(req.tenantId, req.body);
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
      const updated = await this.service.update(req.tenantId, req.params.id, req.body);
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
      const deleted = await this.service.delete(req.tenantId, req.params.id);
      if (!deleted) {
        return res.status(HTTP_STATUS.NOT_FOUND).json({ error: 'Resource not found.' });
      }
      return res.status(HTTP_STATUS.NO_CONTENT).end();
    } catch (err) {
      return next(err);
    }
  };
}
