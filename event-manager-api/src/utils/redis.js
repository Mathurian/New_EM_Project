import { createClient } from 'redis'
import { config } from '../config/index.js'
import { logger } from './logger.js'

// Create Redis client
export const redisClient = createClient({
  host: config.redis.host,
  port: config.redis.port,
  password: config.redis.password,
  db: config.redis.db
})

// Redis event handlers
redisClient.on('connect', () => {
  logger.info('Redis client connected')
})

redisClient.on('error', (error) => {
  logger.error('Redis client error:', error)
})

redisClient.on('end', () => {
  logger.info('Redis client disconnected')
})

// Connect to Redis
try {
  await redisClient.connect()
} catch (error) {
  logger.error('Failed to connect to Redis:', error)
}

export default redisClient
