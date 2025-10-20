#!/bin/bash

echo "🔍 Frontend Login Debug Script"
echo "============================="

cd /opt/event-manager

echo "[INFO] Checking Frontend Build Status..."
if [ -d "event-manager-frontend/dist" ]; then
    echo "✅ Frontend dist directory exists"
    ls -la event-manager-frontend/dist/ | head -5
else
    echo "❌ Frontend dist directory missing - needs build"
fi

echo ""
echo "[INFO] Checking Frontend Dependencies..."
cd event-manager-frontend
if [ -d "node_modules" ]; then
    echo "✅ Frontend node_modules exists"
else
    echo "❌ Frontend node_modules missing"
fi

echo ""
echo "[INFO] Checking Frontend Build..."
if [ -f "dist/index.html" ]; then
    echo "✅ Frontend index.html exists"
    echo "Build timestamp:"
    ls -la dist/index.html
else
    echo "❌ Frontend index.html missing"
fi

echo ""
echo "[INFO] Building Frontend..."
npm run build 2>&1 | tail -10

echo ""
echo "[INFO] Checking API Server Status..."
cd ../event-manager-api
if ps aux | grep -q "node src/server.js"; then
    echo "✅ API server is running"
else
    echo "❌ API server is not running"
    echo "[INFO] Starting API server..."
    nohup npm start > server.log 2>&1 &
    sleep 3
fi

echo ""
echo "[INFO] Testing API Health..."
curl -s http://localhost:3000/api/health | jq . 2>/dev/null || curl -s http://localhost:3000/api/health

echo ""
echo "[INFO] Testing Login API Directly..."
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
  -w "\nHTTP Status: %{http_code}\n"

echo ""
echo "[INFO] Checking Apache Configuration..."
if [ -f "/etc/apache2/sites-enabled/event-manager.conf" ]; then
    echo "✅ Apache event-manager site enabled"
else
    echo "❌ Apache event-manager site not enabled"
fi

echo ""
echo "[INFO] Checking Apache Status..."
systemctl is-active apache2 || echo "Apache not active"

echo ""
echo "[INFO] Testing Frontend Access..."
if curl -s -I http://localhost/ | grep -q "200 OK"; then
    echo "✅ Frontend accessible via Apache"
else
    echo "❌ Frontend not accessible via Apache"
fi

echo ""
echo "[INFO] Checking Frontend Console Errors..."
echo "Open browser dev tools and check for:"
echo "1. Network errors in Console tab"
echo "2. Failed requests in Network tab"
echo "3. JavaScript errors in Console tab"

echo ""
echo "[INFO] Manual Test Commands:"
echo "1. Test API: curl -X POST http://localhost:3000/api/auth/login -H 'Content-Type: application/json' -d '{\"email\":\"admin@eventmanager.com\",\"password\":\"admin123\"}'"
echo "2. Test Frontend: curl -I http://localhost/"
echo "3. Check logs: tail -f event-manager-api/server.log"
echo "4. Check Apache logs: tail -f /var/log/apache2/error.log"
