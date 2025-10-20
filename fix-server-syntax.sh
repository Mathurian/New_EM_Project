#!/bin/bash

# Fix Server Syntax Issues
# This script fixes template literal syntax errors and logger path issues

set -e

INSTALL_DIR="/opt/event-manager"
SERVER_FILE="$INSTALL_DIR/event-manager-api/src/server.js"
LOGGER_FILE="$INSTALL_DIR/event-manager-api/src/utils/logger.js"

echo "🔧 Fixing server syntax issues..."

# Check if files exist
if [[ ! -f "$SERVER_FILE" ]]; then
    echo "❌ Server file not found: $SERVER_FILE"
    exit 1
fi

if [[ ! -f "$LOGGER_FILE" ]]; then
    echo "❌ Logger file not found: $LOGGER_FILE"
    exit 1
fi

# Create logs directory
echo "📁 Creating logs directory..."
sudo mkdir -p "$INSTALL_DIR/logs"
sudo chown -R eventmanager:eventmanager "$INSTALL_DIR/logs"

# Fix template literal syntax in server.js
echo "🔨 Fixing template literal syntax in server.js..."
sed -i 's/logger\.info(`Client \${socket\.id} joined room: \${room}`)/logger.info("Client " + socket.id + " joined room: " + room)/g' "$SERVER_FILE"
sed -i 's/logger\.info(`Client \${socket\.id} left room: \${room}`)/logger.info("Client " + socket.id + " left room: " + room)/g' "$SERVER_FILE"
sed -i 's/logger\.info(`Client connected: \${socket\.id}`)/logger.info("Client connected: " + socket.id)/g' "$SERVER_FILE"
sed -i 's/logger\.info(`Client disconnected: \${socket\.id}`)/logger.info("Client disconnected: " + socket.id)/g' "$SERVER_FILE"
sed -i 's/logger\.info(`Server running on port \${PORT}`)/logger.info("Server running on port " + PORT)/g' "$SERVER_FILE"
sed -i 's/logger\.info(`Event Manager API Server started successfully`)/logger.info("Event Manager API Server started successfully")/g' "$SERVER_FILE"

# Fix logger path issues
echo "🔨 Fixing logger path configuration..."
sed -i "s|filename: '../../logs/|filename: '/opt/event-manager/logs/|g" "$LOGGER_FILE"

# Verify fixes
echo "✅ Verifying fixes..."
if grep -q "logger.info(\`" "$SERVER_FILE"; then
    echo "⚠️  Warning: Some template literals may still exist"
else
    echo "✅ Template literals fixed"
fi

if grep -q "filename: '/opt/event-manager/logs/" "$LOGGER_FILE"; then
    echo "✅ Logger paths fixed"
else
    echo "⚠️  Warning: Logger paths may not be fixed"
fi

# Restart service
echo "🔄 Restarting service..."
sudo systemctl restart event-manager

# Check service status
echo "📊 Checking service status..."
sleep 3
if sudo systemctl is-active --quiet event-manager; then
    echo "✅ Service is running successfully"
else
    echo "❌ Service failed to start"
    echo "📋 Recent logs:"
    sudo journalctl -u event-manager --no-pager -l --since "1 minute ago"
fi

echo "🎉 Fix completed!"