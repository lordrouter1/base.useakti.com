import { DataTypes } from 'sequelize';

/**
 * Sequelize model for `payment_gateway_transactions` table.
 * Logs all webhook events and charge operations.
 *
 * @param {import('sequelize').Sequelize} sequelize
 * @returns {import('sequelize').ModelStatic}
 */
export function definePaymentGatewayTransaction(sequelize) {
  return sequelize.define('PaymentGatewayTransaction', {
    id: {
      type: DataTypes.INTEGER,
      primaryKey: true,
      autoIncrement: true,
    },
    gateway_slug: {
      type: DataTypes.STRING(50),
      allowNull: false,
    },
    installment_id: {
      type: DataTypes.INTEGER,
      allowNull: true,
    },
    order_id: {
      type: DataTypes.INTEGER,
      allowNull: true,
    },
    external_id: {
      type: DataTypes.STRING(255),
      allowNull: true,
    },
    external_status: {
      type: DataTypes.STRING(100),
      allowNull: true,
    },
    amount: {
      type: DataTypes.DECIMAL(12, 2),
      defaultValue: 0,
    },
    currency: {
      type: DataTypes.STRING(10),
      defaultValue: 'BRL',
    },
    payment_method_type: {
      type: DataTypes.STRING(50),
      allowNull: true,
    },
    raw_payload: {
      type: DataTypes.JSON,
      allowNull: true,
    },
    event_type: {
      type: DataTypes.STRING(100),
      allowNull: true,
    },
    processed_at: {
      type: DataTypes.DATE,
      allowNull: true,
    },
  }, {
    tableName: 'payment_gateway_transactions',
    timestamps: true,
    createdAt: 'created_at',
    updatedAt: false,
  });
}
