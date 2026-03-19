import { DataTypes } from 'sequelize';

/**
 * Sequelize model for `payment_gateways` table.
 * Stores gateway configuration per tenant.
 *
 * @param {import('sequelize').Sequelize} sequelize
 * @returns {import('sequelize').ModelStatic}
 */
export function definePaymentGateway(sequelize) {
  return sequelize.define('PaymentGateway', {
    id: {
      type: DataTypes.INTEGER,
      primaryKey: true,
      autoIncrement: true,
    },
    gateway_slug: {
      type: DataTypes.STRING(50),
      allowNull: false,
      unique: true,
    },
    display_name: {
      type: DataTypes.STRING(100),
      allowNull: false,
    },
    is_active: {
      type: DataTypes.TINYINT,
      defaultValue: 0,
    },
    is_default: {
      type: DataTypes.TINYINT,
      defaultValue: 0,
    },
    environment: {
      type: DataTypes.ENUM('sandbox', 'production'),
      defaultValue: 'sandbox',
    },
    credentials: {
      type: DataTypes.TEXT,
      allowNull: true,
      get() {
        const raw = this.getDataValue('credentials');
        if (!raw) return {};
        try { return JSON.parse(raw); } catch { return {}; }
      },
    },
    settings_json: {
      type: DataTypes.TEXT,
      allowNull: true,
      get() {
        const raw = this.getDataValue('settings_json');
        if (!raw) return {};
        try { return JSON.parse(raw); } catch { return {}; }
      },
    },
    webhook_secret: {
      type: DataTypes.STRING(255),
      allowNull: true,
    },
  }, {
    tableName: 'payment_gateways',
    timestamps: true,
    createdAt: 'created_at',
    updatedAt: 'updated_at',
  });
}
