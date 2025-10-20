#!/bin/bash

echo "🔧 Fixing Redis Session Store Issues (Improved)"
echo "==============================================="

# Navigate to the API directory
cd /opt/event-manager/event-manager-api

echo "[INFO] Reinstalling redis@4.7.1 with legacy mode..."
npm uninstall redis
npm install redis@4.7.1

echo "[INFO] Updating redis.js with legacy mode for compatibility..."

# Update redis.js with legacy mode
cat > src/utils/redis.js << 'EOF'
import { createClient } from 'redis'
import { config } from '../config/index.js'
import { logger } from './logger.js'

// Create Redis client with legacy mode for connect-redis compatibility
export const redisClient = createClient({
  url: `redis://${config.redis.host}:${config.redis.port}`,
  password: config.redis.password,
  database: config.redis.db,
  legacyMode: true  // This fixes the compatibility issue
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

echo "[SUCCESS] Updated redis.js with legacy mode"

echo "[INFO] Checking server logs for errors..."
if [ -f server.log ]; then
    echo "=== Last 20 lines of server.log ==="
    tail -20 server.log
    echo "=================================="
fi

echo "[INFO] Stopping any running server processes..."
pkill -f "node src/server.js" || true
sleep 2

echo "[INFO] Starting server with fixed Redis configuration..."
nohup npm start > server.log 2>&1 &

echo "[INFO] Waiting for server to start..."
sleep 5

echo "[INFO] Checking if server started successfully..."
if ps aux | grep -q "node src/server.js"; then
    echo "[SUCCESS] Server is running!"
    
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
echo "[INFO] To monitor server logs: tail -f server.log"
echo "[INFO] To stop server: pkill -f 'node src/server.js'"
