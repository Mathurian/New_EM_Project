#!/usr/bin/env node
/**
 * Database Seed Script
 * Runs database seeds using Knex.js
 */

import { db, testConnection, closeConnection } from '../src/database/connection.js'
import { logger } from '../src/utils/logger.js'

async function runSeeds() {
  try {
    console.log('🌱 Starting database seeding...')
    
    // Test database connection first
    const connected = await testConnection()
    if (!connected) {
      console.error('❌ Cannot connect to database. Please check your configuration.')
      process.exit(1)
    }

    // Run seeds
    console.log('📦 Running seeds...')
    const result = await db.seed.run()
    
    // Handle different return formats from Knex
    let batchNo, log
    if (Array.isArray(result)) {
      [batchNo, log] = result
    } else {
      batchNo = result
      log = []
    }
    
    if (!log || log.length === 0) {
      console.log('✅ No seeds to run')
    } else {
      console.log(`✅ Successfully ran ${log.length} seed(s)`)
      console.log('📋 Seeds executed:')
      log.forEach(seed => {
        console.log(`   - ${seed}`)
      })
    }

    console.log(`📊 Current seed batch: ${batchNo}`)
    
  } catch (error) {
    console.error('❌ Seeding failed:', error.message)
    logger.error('Seeding error:', error)
    process.exit(1)
  } finally {
    await closeConnection()
  }
}

// Handle process termination
process.on('SIGINT', async () => {
  console.log('\n🛑 Seeding interrupted by user')
  await closeConnection()
  process.exit(0)
})

process.on('SIGTERM', async () => {
  console.log('\n🛑 Seeding terminated')
  await closeConnection()
  process.exit(0)
})

// Run seeds
runSeeds()
