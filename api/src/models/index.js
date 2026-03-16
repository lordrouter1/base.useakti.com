import { getSequelize } from '../config/database.js';

const sequelize = getSequelize();

/**
 * Model registry.
 *
 * Import and register Sequelize models here so they are initialised once
 * and associations can be declared in a single place.
 *
 * Example:
 *   import { defineOrder } from './Order.js';
 *   const Order = defineOrder(sequelize);
 *   export { Order };
 */

export { sequelize };
