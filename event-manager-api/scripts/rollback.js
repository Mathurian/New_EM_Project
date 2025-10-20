#!/usr/bin/env node
/**
 * Database Migration Rollback Script
 * Rolls back database migrations using Knex.js
 */

import { db, testConnection, closeConnection } from '../src/database/connection.js'
import { logger } from '../src/utils/logger.js'

async function rollbackMigrations() {
  try {
    console.log('🔄 Starting database migration rollback...')
    
    // Test database connection first
    const connected = await testConnection()
    if (!connected) {
      console.error('❌ Cannot connect to database. Please check your configuration.')
      process.exit(1)
    }

    // Rollback migrations
    console.log('📦 Rolling back migrations...')
    const [batchNo, log] = await db.migrate.rollback()
    
    if (log.length === 0) {
      console.log('✅ No migrations to rollback')
    } else {
      console.log(`✅ Successfully rolled back ${log.length} migration(s)`)
      console.log('📋 Migrations rolled back:')
      log.forEach(migration => {
        console.log(`   - ${migration}`)
      })
    }

    console.log(`📊 Current migration batch: ${batchNo}`)
    
  } catch (error) {
    console.error('❌ Rollback failed:', error.message)
    logger.error('Rollback error:', error)
    process.exit(1)
  } finally {
    await closeConnection()
  }
}

// Handle process termination
process.on('SIGINT', async () => {
  console.log('\n🛑 Rollback interrupted by user')
  await closeConnection()
  process.exit(0)
})

process.on('SIGTERM', async () => {
  console.log('\n🛑 Rollback terminated')
  await closeConnection()
  process.exit(0)
})

// Run rollback
rollbackMigrations()
