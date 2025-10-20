#!/bin/bash

echo "🔧 Fixing Redis Authentication with Proper Permissions"
echo "====================================================="

# Navigate to the API directory
cd /opt/event-manager/event-manager-api

echo "[INFO] Redis password found: event_manager_redis_password_2024"
echo "[INFO] Fixing permission issues and Redis configuration..."

# Create .env file in the correct location with proper permissions
echo "[INFO] Creating .env file with proper permissions..."
sudo tee /opt/event-manager/.env > /dev/null << 'EOF'
# Database Configuration
DB_HOST=localhost
DB_PORT=5432
DB_NAME=event_manager
DB_USER=event_manager
DB_PASSWORD=password

# Redis Configuration
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=event_manager_redis_password_2024
REDIS_DB=0

# Session Configuration
SESSION_SECRET=your-super-secret-session-key-change-in-production
SESSION_MAX_AGE=86400000

# Application Configuration
NODE_ENV=development
PORT=3000
HOST=0.0.0.0
APP_URL=http://localhost:3000
EOF

echo "[SUCCESS] Created .env file with Redis password"

# Update redis.js with the correct password
cat > src/utils/redis.js << 'EOF'
import { createClient } from 'redis'
import { config } from '../config/index.js'
import { logger } from './logger.js'

// Create Redis client with password
export const redisClient = createClient({
  url: `redis://:event_manager_redis_password_2024@${config.redis.host}:${config.redis.port}`,
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

echo "[SUCCESS] Updated redis.js with correct password"

echo "[INFO] Testing Redis connection with password..."
redis-cli -a event_manager_redis_password_2024 ping

echo "[INFO] Stopping current server..."
pkill -f "node src/server.js" || true
sleep 2

echo "[INFO] Starting server with fixed Redis configuration..."
nohup npm start > server.log 2>&1 &

echo "[INFO] Waiting for server to start..."
sleep 5

echo "[INFO] Checking server status..."
if ps aux | grep -q "node src/server.js"; then
    echo "[SUCCESS] Server is running!"
    
    echo "[INFO] Testing health endpoint..."
    curl -s http://localhost:3000/api/health | head -3
    
    echo ""
    echo "[INFO] Testing login..."
    curl -X POST http://localhost:3000/api/auth/login \
      -H "Content-Type: application/json" \
      -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
      -w "\nHTTP Status: %{http_code}\n"
else
    echo "[ERROR] Server failed to start. Checking logs..."
    echo "=== Server Log ==="
    tail -20 server.log
    echo "=================="
fi

echo ""
echo "[INFO] Redis password: event_manager_redis_password_2024"
echo "[INFO] Login credentials: admin@eventmanager.com / admin123"
