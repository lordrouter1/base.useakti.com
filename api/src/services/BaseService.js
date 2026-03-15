import { PAGINATION } from '../config/constants.js';

/**
 * BaseService — shared business-logic helpers.
 *
 * Extend this class in concrete services to inherit common CRUD behaviour
 * that automatically scopes queries to the current tenant.
 *
 * Usage:
 *   class OrderService extends BaseService {
 *     constructor() { super(OrderModel); }
 *   }
 */
export class BaseService {
  /**
   * @param {import('sequelize').ModelStatic} model - Sequelize model to operate on.
   */
  constructor(model) {
    this.model = model;
  }

  /**
   * Find all records scoped to a tenant with pagination.
   */
  async findAll(tenantId, { page = PAGINATION.DEFAULT_PAGE, limit = PAGINATION.DEFAULT_LIMIT } = {}) {
    const safeLimit = Math.min(limit, PAGINATION.MAX_LIMIT) || PAGINATION.DEFAULT_LIMIT;
    const offset = (page - 1) * safeLimit;

    const { count, rows } = await this.model.findAndCountAll({
      where: { tenant_id: tenantId },
      limit: safeLimit,
      offset,
      order: [['created_at', 'DESC']],
    });

    return {
      data: rows,
      meta: { total: count, page, limit: safeLimit, pages: Math.ceil(count / safeLimit) },
    };
  }

  /**
   * Find a single record by primary key, scoped to a tenant.
   */
  async findById(tenantId, id) {
    return this.model.findOne({ where: { id, tenant_id: tenantId } });
  }

  /**
   * Create a new record, attaching the tenant id automatically.
   */
  async create(tenantId, data) {
    return this.model.create({ ...data, tenant_id: tenantId });
  }

  /**
   * Update a record scoped to a tenant.
   */
  async update(tenantId, id, data) {
    const [affectedRows] = await this.model.update(data, {
      where: { id, tenant_id: tenantId },
    });
    return affectedRows > 0;
  }

  /**
   * Delete a record scoped to a tenant.
   */
  async delete(tenantId, id) {
    const deleted = await this.model.destroy({
      where: { id, tenant_id: tenantId },
    });
    return deleted > 0;
  }
}
