import { BaseService } from './BaseService.js';
import { Op } from 'sequelize';

export class ProductService extends BaseService {
  /**
   * @param {import('sequelize').ModelStatic} ProductModel
   * @param {import('sequelize').ModelStatic} ProductGradeCombinationModel
   */
  constructor(ProductModel, ProductGradeCombinationModel) {
    super(ProductModel);
    this.ProductGradeCombination = ProductGradeCombinationModel;
  }

  /**
   * Busca produtos pelo parâmetro q usando LIKE (case-insensitive),
   * retorna os campos necessários para o Select2 + variações de grade ativas.
   *
   * Nota: Não há filtro por tenant_id — cada tenant possui banco próprio.
   */
  async search(q, { limit = 10 } = {}) {
    const safeLimit = Math.min(limit, 50);
    const where = {};
    if (q) {
      where[Op.or] = [
        { name: { [Op.like]: `%${q}%` } },
        { sku: { [Op.like]: `%${q}%` } },
      ];
    }

    const rows = await this.model.findAll({
      where,
      limit: safeLimit,
      order: [['name', 'ASC']],
      attributes: ['id', 'name', 'sku', 'description', 'price', 'category_id'],
      include: [
        {
          model: this.ProductGradeCombination,
          as: 'combinations',
          where: { is_active: 1 },
          required: false,
          attributes: ['id', 'combination_label', 'sku', 'price_override', 'stock_quantity'],
        },
      ],
    });

    return rows;
  }
}
