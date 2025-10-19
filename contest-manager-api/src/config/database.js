import knex from 'knex'
import { config } from './index.js'

/**
 * Database configuration with connection pooling and query optimization
 */
const dbConfig = {
  client: 'postgresql',
  connection: {
    host: config.database.host,
    port: config.database.port,
    user: config.database.user,
    password: config.database.password,
    database: config.database.name,
    ssl: config.database.ssl ? { rejectUnauthorized: false } : false
  },
  pool: {
    min: 2,
    max: 20,
    acquireTimeoutMillis: 60000,
    createTimeoutMillis: 30000,
    destroyTimeoutMillis: 5000,
    idleTimeoutMillis: 30000,
    reapIntervalMillis: 1000,
    createRetryIntervalMillis: 200
  },
  migrations: {
    directory: './src/database/migrations',
    tableName: 'knex_migrations'
  },
  seeds: {
    directory: './src/database/seeds'
  },
  // Query optimization
  debug: config.app.env === 'development',
  asyncStackTraces: config.app.env === 'development'
}

// Create database instance
const db = knex(dbConfig)

// Database health check
export const checkDatabaseHealth = async () => {
  try {
    await db.raw('SELECT 1')
    return { status: 'healthy', timestamp: new Date().toISOString() }
  } catch (error) {
    return { status: 'unhealthy', error: error.message, timestamp: new Date().toISOString() }
  }
}

// Graceful shutdown
export const closeDatabase = async () => {
  try {
    await db.destroy()
    console.log('Database connection closed')
  } catch (error) {
    console.error('Error closing database connection:', error)
  }
}

export default db