#!/bin/bash

echo "🔍 Frontend JavaScript Debug Script"
echo "==================================="

cd /opt/event-manager

echo "[INFO] Checking frontend JavaScript for errors..."

echo ""
echo "[INFO] Testing API through Apache proxy..."
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
  -w "\nHTTP Status: %{http_code}\n"

echo ""
echo "[INFO] Checking frontend build for API calls..."
if grep -q "auth/login" event-manager-frontend/dist/assets/*.js; then
    echo "✅ Login API call found in frontend build"
else
    echo "❌ Login API call not found in frontend build"
fi

echo ""
echo "[INFO] Checking for CORS issues..."
echo "Testing preflight request..."
curl -X OPTIONS http://localhost/api/auth/login \
  -H "Origin: http://localhost" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type" \
  -v 2>&1 | grep -E "(HTTP|Access-Control|Origin)"

echo ""
echo "[INFO] Checking frontend login page source..."
echo "Looking for login form and API calls..."
grep -A 10 -B 5 "login" event-manager-frontend/dist/assets/*.js | head -20

echo ""
echo "[INFO] Checking auth store implementation..."
if grep -q "useAuthStore" event-manager-frontend/dist/assets/*.js; then
    echo "✅ Auth store found in build"
else
    echo "❌ Auth store not found in build"
fi

echo ""
echo "[INFO] Manual Browser Debug Steps:"
echo "1. Open browser dev tools (F12)"
echo "2. Go to Console tab"
echo "3. Try to login"
echo "4. Check for JavaScript errors"
echo "5. Go to Network tab"
echo "6. Look for failed requests to /api/auth/login"
echo "7. Check if requests are being made to the right URL"

echo ""
echo "[INFO] Testing different scenarios..."

echo ""
echo "[INFO] Test 1: Direct API call through Apache..."
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
  -w "\nHTTP Status: %{http_code}\n"

echo ""
echo "[INFO] Test 2: Check if frontend is making requests..."
echo "Look in browser Network tab for requests to:"
echo "- /api/auth/login"
echo "- Any 404 or 500 errors"
echo "- CORS errors"

echo ""
echo "[INFO] Test 3: Check browser console for errors..."
echo "Common issues to look for:"
echo "- 'Failed to fetch' errors"
echo "- CORS policy errors"
echo "- 'Network Error' messages"
echo "- JavaScript syntax errors"

echo ""
echo "[INFO] Quick Fix Attempt - Check if it's a session issue..."
echo "Testing with session cookie..."
SESSION_RESPONSE=$(curl -s -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}')

echo "Login response: $SESSION_RESPONSE"

# Extract session cookie if present
SESSION_COOKIE=$(echo "$SESSION_RESPONSE" | grep -i "set-cookie" | cut -d'=' -f2 | cut -d';' -f1)

if [ ! -z "$SESSION_COOKIE" ]; then
    echo "Session cookie found: $SESSION_COOKIE"
    echo "Testing auth status with session..."
    curl -X GET http://localhost/api/auth/status \
      -H "Cookie: event-manager-session=$SESSION_COOKIE" \
      -w "\nHTTP Status: %{http_code}\n"
else
    echo "No session cookie found"
fi

echo ""
echo "[SUCCESS] Debug script completed!"
echo "[INFO] Check the browser console and network tab for specific errors"
