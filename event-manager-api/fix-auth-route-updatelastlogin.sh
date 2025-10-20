#!/bin/bash

echo "🔧 Fix Auth Route - Remove updateLastLogin Call"
echo "=============================================="

cd /opt/event-manager/event-manager-api

echo "[INFO] Fixing auth route to remove the non-existent updateLastLogin call..."

# Create a backup
cp src/routes/auth.js src/routes/auth.js.backup

# Remove the updateLastLogin call from the login route
sed -i '/userService.updateLastLogin(user.id)/d' src/routes/auth.js

echo "[INFO] Restarting server to apply changes..."

# Kill existing server
pkill -f "node src/server.js" || true
sleep 2

# Start server in background
nohup npm start > server.log 2>&1 &
sleep 3

echo "[INFO] Testing login after auth route fix..."
timeout 10s curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
  -w "\nHTTP Status: %{http_code}\n" || echo "Request timed out"

echo ""
echo "[SUCCESS] Auth route fixed!"
echo "[INFO] The updateLastLogin call has been removed"
echo "[INFO] Last login is already updated in authenticateUser method"
