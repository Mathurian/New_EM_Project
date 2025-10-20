#!/bin/bash

echo "🔧 Fixing Redis Import Issues"
echo "=============================="

# Navigate to the API directory
cd /opt/event-manager/event-manager-api

echo "[INFO] Updating server.js imports..."

# Fix the Redis imports in server.js
sed -i "s/import connectRedis from 'connect-redis'/import { createClient } from 'redis'\nimport RedisStore from 'connect-redis'/" src/server.js

# Fix the Redis store initialization
sed -i "s/const RedisStore = connectRedis(session)/const redisStore = new RedisStore({ client: redisClient })/" src/server.js

# Fix the session store reference
sed -i "s/store: new RedisStore({ client: redisClient })/store: redisStore/" src/server.js

echo "[INFO] Updating redis.js configuration..."

# Fix Redis client configuration
sed -i "s/host: config.redis.host,/url: \`redis:\/\/\${config.redis.host}:\${config.redis.port}\`,/" src/utils/redis.js
sed -i "s/port: config.redis.port,//" src/utils/redis.js
sed -i "s/db: config.redis.db/database: config.redis.db/" src/utils/redis.js

echo "[SUCCESS] Redis import fixes applied"
echo "[INFO] Testing server startup..."

# Test the server
timeout 10s node src/server.js 2>&1 | head -20

echo "[INFO] If you see 'Server started' above, the fix worked!"
echo "[INFO] You can now run: npm start"
