import { Sequelize } from 'sequelize';
import { env } from './env.js';

let sequelizeInstance = null;

/**
 * Returns a singleton Sequelize instance connected to the MySQL database.
 * All tenant-scoped queries should apply `WHERE tenant_id = ?` at the
 * service/model layer — the connection itself is shared.
 */
export function getSequelize() {
  if (!sequelizeInstance) {
    sequelizeInstance = new Sequelize(env.DB_NAME, env.DB_USER, env.DB_PASS, {
      host: env.DB_HOST,
      port: env.DB_PORT,
      dialect: 'mysql',
      logging: env.NODE_ENV === 'development' ? console.log : false,
      pool: {
        max: 10,
        min: 0,
        acquire: 30_000,
        idle: 10_000,
      },
    });
  }

  return sequelizeInstance;
}

/**
 * Tests the database connection. Call once at startup.
 */
export async function testConnection() {
  const sequelize = getSequelize();
  await sequelize.authenticate();
  console.log('[DB] MySQL connection established successfully.');
}
