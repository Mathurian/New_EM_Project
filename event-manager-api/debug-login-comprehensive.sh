#!/bin/bash

echo "🔍 Comprehensive Login Debug Script"
echo "=================================="

# Navigate to the API directory
cd /opt/event-manager/event-manager-api

echo "[INFO] Server Status Check..."
if ps aux | grep -q "node src/server.js"; then
    echo "✅ Server is running"
else
    echo "❌ Server is not running"
    exit 1
fi

echo ""
echo "[INFO] Health Check..."
curl -s http://localhost:3000/api/health | jq . 2>/dev/null || curl -s http://localhost:3000/api/health

echo ""
echo "[INFO] Testing Redis Connection..."
redis-cli ping 2>/dev/null || echo "Redis CLI not available"

echo ""
echo "[INFO] Checking Redis Authentication..."
redis-cli -a "$REDIS_PASSWORD" ping 2>/dev/null || echo "Redis auth failed"

echo ""
echo "[INFO] Testing Database Connection..."
psql -h localhost -U event_manager -d event_manager_db -c "SELECT COUNT(*) FROM users WHERE email='admin@eventmanager.com';" 2>/dev/null || echo "Database connection failed"

echo ""
echo "[INFO] Checking User Data..."
psql -h localhost -U event_manager -d event_manager_db -c "SELECT email, role, is_active, LENGTH(password_hash) as hash_length FROM users WHERE email='admin@eventmanager.com';" 2>/dev/null || echo "User query failed"

echo ""
echo "[INFO] Testing Auth Route Availability..."
echo "Testing GET /api/auth/login (should return method not allowed):"
curl -s -w "HTTP Status: %{http_code}\n" http://localhost:3000/api/auth/login

echo ""
echo "Testing POST /api/auth/login with minimal data:"
curl -s -w "HTTP Status: %{http_code}\n" -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{}'

echo ""
echo "Testing POST /api/auth/login with correct credentials:"
timeout 15s curl -v -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
  -w "\nHTTP Status: %{http_code}\nTime: %{time_total}s\n" || echo "Request timed out after 15 seconds"

echo ""
echo "[INFO] Checking Server Logs..."
echo "Last 10 lines of server.log:"
tail -10 server.log 2>/dev/null || echo "No server.log found"

echo ""
echo "[INFO] Checking for Auth Route Registration..."
echo "Looking for auth route in server.js:"
grep -n "auth" src/server.js | head -5

echo ""
echo "[INFO] Checking Auth Route File..."
if [ -f "src/routes/auth.js" ]; then
    echo "✅ Auth route file exists"
    echo "First few lines:"
    head -10 src/routes/auth.js
else
    echo "❌ Auth route file missing"
fi

echo ""
echo "[INFO] Manual Debug Commands:"
echo "1. Check server logs: tail -f server.log"
echo "2. Test Redis: redis-cli -a '$REDIS_PASSWORD' ping"
echo "3. Test DB: psql -h localhost -U event_manager -d event_manager_db"
echo "4. Check routes: curl -v http://localhost:3000/api/auth/login"
