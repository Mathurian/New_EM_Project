#!/bin/bash

echo "🔧 Comprehensive Redis and Authentication Fix"
echo "=============================================="

# Navigate to the API directory
cd /opt/event-manager/event-manager-api

echo "[INFO] Fixing Redis configuration and authentication issues..."

# Update redis.js with proper configuration
cat > src/utils/redis.js << 'EOF'
import { createClient } from 'redis'
import { config } from '../config/index.js'
import { logger } from './logger.js'

// Create Redis client with proper configuration
const redisConfig = {
  url: `redis://${config.redis.host}:${config.redis.port}`,
  database: config.redis.db,
  legacyMode: true
}

// Only add password if it's not null/empty
if (config.redis.password && config.redis.password !== 'null' && config.redis.password !== '') {
  redisConfig.password = config.redis.password
}

export const redisClient = createClient(redisConfig)

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
EOF

echo "[SUCCESS] Updated redis.js with proper password handling"

echo "[INFO] Checking Redis configuration..."
echo "Redis Host: ${REDIS_HOST:-localhost}"
echo "Redis Port: ${REDIS_PORT:-6379}"
echo "Redis Password: ${REDIS_PASSWORD:-'not set'}"

echo "[INFO] Updating .env file to ensure Redis config is correct..."
# Update .env file to ensure Redis config is correct
if [ -f ../../.env ]; then
    # Remove any existing Redis config
    sed -i '/^REDIS_/d' ../../.env
    # Add correct Redis config
    echo "REDIS_HOST=localhost" >> ../../.env
    echo "REDIS_PORT=6379" >> ../../.env
    echo "REDIS_PASSWORD=" >> ../../.env
    echo "REDIS_DB=0" >> ../../.env
    echo "[SUCCESS] Updated .env file with Redis configuration"
else
    echo "[WARNING] .env file not found at ../../.env"
fi

echo "[INFO] Verifying user exists in database..."
sudo -u postgres psql event_manager << 'EOF'
-- Check if user exists and get details
SELECT email, is_active, role, created_at FROM users WHERE email = 'admin@eventmanager.com';
\q
EOF

echo "[INFO] Stopping current server..."
pkill -f "node src/server.js" || true
sleep 2

echo "[INFO] Starting server with fixed configuration..."
nohup npm start > server.log 2>&1 &

echo "[INFO] Waiting for server to start..."
sleep 5

echo "[INFO] Checking server status..."
if ps aux | grep -q "node src/server.js"; then
    echo "[SUCCESS] Server is running!"
    
    echo "[INFO] Testing health endpoint first..."
    curl -s http://localhost:3000/api/health | head -5
    
    echo ""
    echo "[INFO] Testing login with debug info..."
    curl -X POST http://localhost:3000/api/auth/login \
      -H "Content-Type: application/json" \
      -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
      -w "\nHTTP Status: %{http_code}\n" \
      -v 2>&1 | grep -E "(HTTP|{|error|message)"
else
    echo "[ERROR] Server failed to start. Checking logs..."
    echo "=== Server Log ==="
    tail -20 server.log
    echo "=================="
fi

echo ""
echo "[INFO] If login still fails, check:"
echo "1. Database connection: sudo -u postgres psql event_manager -c \"SELECT email FROM users;\""
echo "2. Redis connection: redis-cli ping"
echo "3. Server logs: tail -f server.log"
