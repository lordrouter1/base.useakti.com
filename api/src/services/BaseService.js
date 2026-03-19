import { PAGINATION } from '../config/constants.js';

/**
 * BaseService — shared business-logic helpers.
 *
 * Na arquitetura multi-tenant do Akti, cada tenant possui seu próprio
 * banco de dados. Por isso, NÃO é necessário filtrar por tenant_id nas
 * queries — o isolamento já é garantido pela conexão Sequelize em req.db.
 *
 * O model é recebido no construtor e pode ser trocado por request
 * (via controller) para garantir que aponte para o banco correto.
 *
 * Usage:
 *   const service = new ProductService(req.models.Product);
 *   const results = await service.findAll({ page: 1 });
 */
export class BaseService {
  /**
   * @param {import('sequelize').ModelStatic} model - Sequelize model to operate on.
   */
  constructor(model) {
    this.model = model;
  }

  /**
   * Permite trocar o model em runtime (usado pelo controller por request).
   * @param {import('sequelize').ModelStatic} model
   */
  setModel(model) {
    this.model = model;
    return this;
  }

  /**
   * Find all records with pagination.
   */
  async findAll({ page = PAGINATION.DEFAULT_PAGE, limit = PAGINATION.DEFAULT_LIMIT } = {}) {
    const safeLimit = Math.min(limit, PAGINATION.MAX_LIMIT) || PAGINATION.DEFAULT_LIMIT;
    const offset = (page - 1) * safeLimit;

    const { count, rows } = await this.model.findAndCountAll({
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
   * Find a single record by primary key.
   */
  async findById(id) {
    return this.model.findOne({ where: { id } });
  }

  /**
   * Create a new record.
   */
  async create(data) {
    return this.model.create(data);
  }

  /**
   * Update a record.
   */
  async update(id, data) {
    const [affectedRows] = await this.model.update(data, {
      where: { id },
    });
    return affectedRows > 0;
  }

  /**
   * Delete a record.
   */
  async delete(id) {
    const deleted = await this.model.destroy({
      where: { id },
    });
    return deleted > 0;
  }
}
