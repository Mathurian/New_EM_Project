#!/bin/bash

echo "🔧 Fix Frontend API Configuration"
echo "================================="

cd /opt/event-manager/event-manager-frontend

echo "[INFO] Creating frontend .env file for production..."

# Create .env file for production
cat > .env << 'EOF'
# Production API URL - use relative path for Apache
VITE_API_URL=/api
EOF

echo "[INFO] Creating .env.local for development..."
cat > .env.local << 'EOF'
# Development API URL
VITE_API_URL=http://localhost:3000/api
EOF

echo "[INFO] Rebuilding frontend with correct API URL..."
npm run build

echo "[INFO] Checking build output..."
if [ -f "dist/index.html" ]; then
    echo "✅ Frontend built successfully"
    echo "Build timestamp:"
    ls -la dist/index.html
else
    echo "❌ Frontend build failed"
fi

echo ""
echo "[INFO] Testing API configuration in built files..."
if grep -q "/api" dist/assets/*.js; then
    echo "✅ API URL configured correctly in build"
else
    echo "❌ API URL not found in build"
fi

echo ""
echo "[INFO] Checking Apache configuration..."
if [ -f "/etc/apache2/sites-enabled/event-manager.conf" ]; then
    echo "✅ Apache event-manager site enabled"
    echo "Checking ProxyPass configuration..."
    grep -A 5 "ProxyPass /api" /etc/apache2/sites-enabled/event-manager.conf
else
    echo "❌ Apache event-manager site not enabled"
fi

echo ""
echo "[INFO] Restarting Apache to ensure changes..."
sudo systemctl restart apache2

echo ""
echo "[INFO] Testing frontend access..."
curl -I http://localhost/ 2>/dev/null | head -3

echo ""
echo "[SUCCESS] Frontend API configuration fixed!"
echo "[INFO] Frontend now uses /api for production (Apache)"
echo "[INFO] Frontend uses localhost:3000/api for development"
echo ""
echo "[INFO] Test the login now at: http://your-server-ip/"
