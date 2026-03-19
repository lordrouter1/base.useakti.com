import { defineProduct } from './Product.js';
import { defineProductGradeCombination } from './ProductGradeCombination.js';
import { definePaymentGateway } from './PaymentGateway.js';
import { definePaymentGatewayTransaction } from './PaymentGatewayTransaction.js';

/**
 * Model cache — evita redefinir os mesmos models na mesma instância Sequelize.
 * Chave = Sequelize instance, Valor = { Product, ProductGradeCombination, ... }
 * @type {WeakMap<import('sequelize').Sequelize, Record<string, import('sequelize').ModelStatic>>}
 */
const _modelCache = new WeakMap();

/**
 * Retorna os models já definidos (ou define pela primeira vez)
 * para uma instância Sequelize específica de um tenant.
 *
 * Usar dentro de qualquer handler que tenha `req.db`:
 *
 *   const { Product, ProductGradeCombination } = getModels(req.db);
 *
 * @param {import('sequelize').Sequelize} sequelize
 * @returns {{ Product: import('sequelize').ModelStatic, ProductGradeCombination: import('sequelize').ModelStatic, PaymentGateway: import('sequelize').ModelStatic, PaymentGatewayTransaction: import('sequelize').ModelStatic }}
 */
export function getModels(sequelize) {
  if (_modelCache.has(sequelize)) {
    return _modelCache.get(sequelize);
  }

  const Product = defineProduct(sequelize);
  const ProductGradeCombination = defineProductGradeCombination(sequelize);
  const PaymentGateway = definePaymentGateway(sequelize);
  const PaymentGatewayTransaction = definePaymentGatewayTransaction(sequelize);

  // ── Associations ──
  Product.hasMany(ProductGradeCombination, {
    foreignKey: 'product_id',
    as: 'combinations',
  });
  ProductGradeCombination.belongsTo(Product, {
    foreignKey: 'product_id',
    as: 'product',
  });

  const models = { Product, ProductGradeCombination, PaymentGateway, PaymentGatewayTransaction };
  _modelCache.set(sequelize, models);
  return models;
}
