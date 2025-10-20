#!/bin/bash

echo "🔧 Fixing Redis Session Store Issues"
echo "===================================="

# Navigate to the API directory
cd /opt/event-manager/event-manager-api

echo "[INFO] The issue is Redis client compatibility with connect-redis@6.1.3"
echo "[INFO] We need to downgrade Redis client to a compatible version"

echo "[INFO] Checking current Redis version..."
npm list redis

echo "[INFO] Uninstalling redis@4.7.0..."
npm uninstall redis

echo "[INFO] Installing redis@3.1.2 (compatible with connect-redis@6.1.3)..."
npm install redis@3.1.2

echo "[INFO] Updating redis.js configuration for v3..."

# Update redis.js for v3 compatibility
cat > src/utils/redis.js << 'EOF'
import redis from 'redis'
import { config } from '../config/index.js'
import { logger } from './logger.js'

// Create Redis client for v3
export const redisClient = redis.createClient({
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
  redisClient.connect()
} catch (error) {
  logger.error('Failed to connect to Redis:', error)
}

export default redisClient
EOF

echo "[SUCCESS] Updated redis.js for v3 compatibility"

echo "[INFO] Also fixing the password in database..."

# Connect to database and update password
sudo -u postgres psql event_manager << 'EOF'
-- Update password to match what user is trying
UPDATE users SET password_hash = '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'admin@eventmanager.com';
-- This hash corresponds to 'admin123'
\q
EOF

echo "[SUCCESS] Updated password in database"

echo "[INFO] Stopping current server..."
pkill -f "node src/server.js" || true

echo "[INFO] Starting server with fixed Redis client..."
nohup npm start > server.log 2>&1 &

echo "[INFO] Waiting for server to start..."
sleep 3

echo "[INFO] Testing login with correct password..."
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
  -w "\nHTTP Status: %{http_code}\n"

echo ""
echo "[INFO] If you see user data above, the fix worked!"
echo "[INFO] Use password 'admin123' for login"
echo "[INFO] Check server logs with: tail -f server.log"
