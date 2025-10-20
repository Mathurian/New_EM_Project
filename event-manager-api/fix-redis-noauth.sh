#!/bin/bash

echo "🔧 Fixing Redis Authentication Issue"
echo "===================================="

# Navigate to the API directory
cd /opt/event-manager/event-manager-api

echo "[INFO] Redis requires authentication. Let's fix this..."

echo "[INFO] Checking Redis configuration..."
sudo cat /etc/redis/redis.conf | grep -E "requirepass|bind|port" | head -5

echo "[INFO] Testing Redis connection with authentication..."
# Try to connect to Redis and see what happens
redis-cli ping 2>&1 || echo "Redis connection failed as expected"

echo "[INFO] Let's check if Redis has a password set..."
REDIS_PASSWORD=$(sudo grep "^requirepass" /etc/redis/redis.conf | cut -d' ' -f2)
if [ -z "$REDIS_PASSWORD" ]; then
    echo "[INFO] No password found in Redis config. Setting up Redis without password..."
    
    # Disable Redis authentication
    sudo sed -i 's/^requirepass/#requirepass/' /etc/redis/redis.conf
    
    echo "[INFO] Restarting Redis server..."
    sudo systemctl restart redis-server
    
    echo "[INFO] Waiting for Redis to restart..."
    sleep 3
    
    echo "[INFO] Testing Redis connection..."
    redis-cli ping
else
    echo "[INFO] Redis password found: $REDIS_PASSWORD"
    echo "[INFO] Updating application to use this password..."
    
    # Update .env file with Redis password
    if [ -f ../../.env ]; then
        sed -i '/^REDIS_PASSWORD/d' ../../.env
        echo "REDIS_PASSWORD=$REDIS_PASSWORD" >> ../../.env
    else
        echo "REDIS_PASSWORD=$REDIS_PASSWORD" > ../../.env
    fi
    
    # Update redis.js to use the password
    cat > src/utils/redis.js << EOF
import { createClient } from 'redis'
import { config } from '../config/index.js'
import { logger } from './logger.js'

// Create Redis client with password
export const redisClient = createClient({
  url: \`redis://:\${config.redis.password}@\${config.redis.host}:\${config.redis.port}\`,
  database: config.redis.db,
  legacyMode: true
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
EOF
fi

echo "[SUCCESS] Updated Redis configuration"

echo "[INFO] Stopping current server..."
pkill -f "node src/server.js" || true
sleep 2

echo "[INFO] Starting server with fixed Redis configuration..."
nohup npm start > server.log 2>&1 &

echo "[INFO] Waiting for server to start..."
sleep 5

echo "[INFO] Testing Redis connection..."
redis-cli ping

echo "[INFO] Testing login..."
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
  -w "\nHTTP Status: %{http_code}\n"

echo ""
echo "[INFO] If login still fails, check server logs: tail -f server.log"
