import { DataTypes } from 'sequelize';

export function defineProductGradeCombination(sequelize) {
  return sequelize.define('ProductGradeCombination', {
    id: { type: DataTypes.INTEGER, primaryKey: true, autoIncrement: true },
    product_id: { type: DataTypes.INTEGER, allowNull: false },
    combination_key: { type: DataTypes.STRING(255), allowNull: false },
    combination_label: { type: DataTypes.STRING(500), allowNull: true },
    sku: { type: DataTypes.STRING(100), allowNull: true },
    price_override: { type: DataTypes.DECIMAL(10, 2), allowNull: true },
    stock_quantity: { type: DataTypes.INTEGER, allowNull: false, defaultValue: 0 },
    is_active: { type: DataTypes.TINYINT, allowNull: false, defaultValue: 1 },
    created_at: { type: DataTypes.DATE, allowNull: false, defaultValue: DataTypes.NOW },
  }, {
    tableName: 'product_grade_combinations',
    timestamps: false,
    underscored: true,
  });
}
