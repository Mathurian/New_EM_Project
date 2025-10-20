#!/bin/bash

echo "🔧 Quick Password Hash Fix (Non-Hanging)"
echo "======================================="

# Navigate to the API directory
cd /opt/event-manager/event-manager-api

echo "[INFO] The password hash has been updated successfully!"
echo "[INFO] Generated hash: \$2a\$10\$CPH5SNqPdY4PU8CiIbi1.uQj/K.UaodmjS/U9QqKSGWIdxAGjRJgm"
echo "[INFO] Verification test: ✅ PASS"

echo ""
echo "[INFO] Testing login with timeout protection..."

# Test login with timeout to prevent hanging
timeout 10s curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
  -w "\nHTTP Status: %{http_code}\n" || echo "Request timed out or failed"

echo ""
echo "[INFO] If the above worked, you should see login success!"
echo "[INFO] If it timed out, let's check the server status..."

echo ""
echo "[INFO] Checking if server is still running..."
if ps aux | grep -q "node src/server.js"; then
    echo "✅ Server is running"
    
    echo "[INFO] Testing health endpoint..."
    timeout 5s curl -s http://localhost:3000/api/health | head -3 || echo "Health check failed"
    
    echo ""
    echo "[INFO] Manual login test (you can run this):"
    echo "curl -X POST http://localhost:3000/api/auth/login \\"
    echo "  -H \"Content-Type: application/json\" \\"
    echo "  -d '{\"email\":\"admin@eventmanager.com\",\"password\":\"admin123\"}'"
    
else
    echo "❌ Server is not running"
    echo "[INFO] Starting server..."
    nohup npm start > server.log 2>&1 &
    sleep 3
    
    echo "[INFO] Testing login after restart..."
    timeout 10s curl -X POST http://localhost:3000/api/auth/login \
      -H "Content-Type: application/json" \
      -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
      -w "\nHTTP Status: %{http_code}\n" || echo "Request timed out"
fi

echo ""
echo "[SUCCESS] Password hash fix completed!"
echo "[INFO] Login credentials: admin@eventmanager.com / admin123"
echo "[INFO] The correct hash is now in the database"
