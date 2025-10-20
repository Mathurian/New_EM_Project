#!/bin/bash

echo "🔧 Fix Route Loading in Server.js"
echo "================================="

cd /opt/event-manager/event-manager-api

echo "[INFO] Converting async route imports to sync imports..."

# Create a backup
cp src/server.js src/server.js.backup

# Replace async imports with sync imports
sed -i 's/app\.use('\''\/api\/auth'\'', (await import('\''\.\/routes\/auth\.js'\'')).default)/import authRoutes from '\''.\/routes\/auth.js'\''; app.use('\''\/api\/auth'\'', authRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/events'\'', (await import('\''\.\/routes\/events\.js'\'')).default)/import eventsRoutes from '\''.\/routes\/events.js'\''; app.use('\''\/api\/events'\'', eventsRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/contests'\'', (await import('\''\.\/routes\/contests\.js'\'')).default)/import contestsRoutes from '\''.\/routes\/contests.js'\''; app.use('\''\/api\/contests'\'', contestsRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/categories'\'', (await import('\''\.\/routes\/categories\.js'\'')).default)/import categoriesRoutes from '\''.\/routes\/categories.js'\''; app.use('\''\/api\/categories'\'', categoriesRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/scoring'\'', (await import('\''\.\/routes\/scoring\.js'\'')).default)/import scoringRoutes from '\''.\/routes\/scoring.js'\''; app.use('\''\/api\/scoring'\'', scoringRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/users'\'', (await import('\''\.\/routes\/users\.js'\'')).default)/import usersRoutes from '\''.\/routes\/users.js'\''; app.use('\''\/api\/users'\'', usersRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/results'\'', (await import('\''\.\/routes\/results\.js'\'')).default)/import resultsRoutes from '\''.\/routes\/results.js'\''; app.use('\''\/api\/results'\'', resultsRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/files'\'', (await import('\''\.\/routes\/files\.js'\'')).default)/import filesRoutes from '\''.\/routes\/files.js'\''; app.use('\''\/api\/files'\'', filesRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/settings'\'', (await import('\''\.\/routes\/settings\.js'\'')).default)/import settingsRoutes from '\''.\/routes\/settings.js'\''; app.use('\''\/api\/settings'\'', settingsRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/backup'\'', (await import('\''\.\/routes\/backup\.js'\'')).default)/import backupRoutes from '\''.\/routes\/backup.js'\''; app.use('\''\/api\/backup'\'', backupRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/print'\'', (await import('\''\.\/routes\/print\.js'\'')).default)/import printRoutes from '\''.\/routes\/print.js'\''; app.use('\''\/api\/print'\'', printRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/templates'\'', (await import('\''\.\/routes\/templates\.js'\'')).default)/import templatesRoutes from '\''.\/routes\/templates.js'\''; app.use('\''\/api\/templates'\'', templatesRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/tally-master'\'', (await import('\''\.\/routes\/tally-master\.js'\'')).default)/import tallyMasterRoutes from '\''.\/routes\/tally-master.js'\''; app.use('\''\/api\/tally-master'\'', tallyMasterRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/emcee'\'', (await import('\''\.\/routes\/emcee\.js'\'')).default)/import emceeRoutes from '\''.\/routes\/emcee.js'\''; app.use('\''\/api\/emcee'\'', emceeRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/auditor'\'', (await import('\''\.\/routes\/auditor\.js'\'')).default)/import auditorRoutes from '\''.\/routes\/auditor.js'\''; app.use('\''\/api\/auditor'\'', auditorRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/board'\'', (await import('\''\.\/routes\/board\.js'\'')).default)/import boardRoutes from '\''.\/routes\/board.js'\''; app.use('\''\/api\/board'\'', boardRoutes)/' src/server.js
sed -i 's/app\.use('\''\/api\/database'\'', (await import('\''\.\/routes\/database-browser\.js'\'')).default)/import databaseRoutes from '\''.\/routes\/database-browser.js'\''; app.use('\''\/api\/database'\'', databaseRoutes)/' src/server.js

echo "[INFO] Moving imports to the top of the file..."

# Extract all import statements and move them to the top
grep "import.*Routes from" src/server.js > /tmp/route_imports.txt

# Remove the import lines from the middle of the file
sed -i '/import.*Routes from/d' src/server.js

# Add the imports after the existing imports (after line with redisClient import)
sed -i '/import { redisClient } from '\''\.\/utils\/redis\.js'\''/r /tmp/route_imports.txt' src/server.js

echo "[INFO] Restarting server to apply changes..."

# Kill existing server
pkill -f "node src/server.js" || true
sleep 2

# Start server in background
nohup npm start > server.log 2>&1 &
sleep 3

echo "[INFO] Testing login after fix..."
timeout 10s curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
  -w "\nHTTP Status: %{http_code}\n" || echo "Request timed out"

echo ""
echo "[SUCCESS] Route loading fix applied!"
echo "[INFO] Check server.log for any errors"
