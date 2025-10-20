#!/usr/bin/env node
/**
 * Database Migration Script
 * Runs database migrations using Knex.js
 */

import { db, testConnection, closeConnection } from '../src/database/connection.js'
import { logger } from '../src/utils/logger.js'

async function runMigrations() {
  try {
    console.log('🔄 Starting database migrations...')
    
    // Test database connection first
    const connected = await testConnection()
    if (!connected) {
      console.error('❌ Cannot connect to database. Please check your configuration.')
      process.exit(1)
    }

    // Run migrations
    console.log('📦 Running migrations...')
    const [batchNo, log] = await db.migrate.latest()
    
    if (log.length === 0) {
      console.log('✅ Database is up to date - no migrations to run')
    } else {
      console.log(`✅ Successfully ran ${log.length} migration(s)`)
      console.log('📋 Migrations executed:')
      log.forEach(migration => {
        console.log(`   - ${migration}`)
      })
    }

    console.log(`📊 Current migration batch: ${batchNo}`)
    
  } catch (error) {
    console.error('❌ Migration failed:', error.message)
    logger.error('Migration error:', error)
    process.exit(1)
  } finally {
    await closeConnection()
  }
}

// Handle process termination
process.on('SIGINT', async () => {
  console.log('\n🛑 Migration interrupted by user')
  await closeConnection()
  process.exit(0)
})

process.on('SIGTERM', async () => {
  console.log('\n🛑 Migration terminated')
  await closeConnection()
  process.exit(0)
})

// Run migrations
runMigrations()
