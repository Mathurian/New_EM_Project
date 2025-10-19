import knex from 'knex'
import { config } from '../config/index.js'

const knexConfig = {
  client: 'pg',
  connection: {
    host: config.database.host,
    port: config.database.port,
    user: config.database.user,
    password: config.database.password,
    database: config.database.name,
    ssl: config.database.ssl ? { rejectUnauthorized: false } : false
  },
  pool: {
    min: config.database.minConnections,
    max: config.database.maxConnections,
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
  }
}

export const db = knex(knexConfig)

// Test database connection
export async function testConnection() {
  try {
    await db.raw('SELECT 1')
    console.log('✅ Database connection successful')
    return true
  } catch (error) {
    console.error('❌ Database connection failed:', error.message)
    return false
  }
}

// Graceful shutdown
export async function closeConnection() {
  try {
    await db.destroy()
    console.log('✅ Database connection closed')
  } catch (error) {
    console.error('❌ Error closing database connection:', error.message)
  }
}