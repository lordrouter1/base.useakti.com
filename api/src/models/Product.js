import { DataTypes } from 'sequelize';

export function defineProduct(sequelize) {
  return sequelize.define('Product', {
    id: { type: DataTypes.INTEGER, primaryKey: true, autoIncrement: true },
    name: { type: DataTypes.STRING(191), allowNull: false },
    sku: { type: DataTypes.STRING(100), allowNull: true },
    description: { type: DataTypes.TEXT, allowNull: true },
    category_id: { type: DataTypes.INTEGER, allowNull: true },
    subcategory_id: { type: DataTypes.INTEGER, allowNull: true },
    price: { type: DataTypes.DECIMAL(10, 2), allowNull: false, defaultValue: 0 },
    stock_quantity: { type: DataTypes.INTEGER, allowNull: false, defaultValue: 0 },
    created_at: { type: DataTypes.DATE, allowNull: false, defaultValue: DataTypes.NOW }
  }, {
    tableName: 'products',
    timestamps: false,
    underscored: true,
  });
}
