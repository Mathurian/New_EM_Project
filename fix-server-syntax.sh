#!/bin/bash

# Quick fix for server.js syntax error
# This script fixes the template literal syntax issues in the generated server.js

set -e

INSTALL_DIR="/opt/event-manager"
SERVER_FILE="$INSTALL_DIR/event-manager-api/src/server.js"

echo "Fixing server.js syntax errors..."

if [[ ! -f "$SERVER_FILE" ]]; then
    echo "Error: Server file not found at $SERVER_FILE"
    exit 1
fi

# Create a backup
cp "$SERVER_FILE" "$SERVER_FILE.backup"

# Fix the template literal syntax issues
sed -i 's/logger\.info(\\`Client \\${socket\.id} joined room: \\${room}\\`)/logger.info("Client " + socket.id + " joined room: " + room)/g' "$SERVER_FILE"
sed -i 's/logger\.info(\\`Client \\${socket\.id} left room: \\${room}\\`)/logger.info("Client " + socket.id + " left room: " + room)/g' "$SERVER_FILE"
sed -i 's/logger\.info(\\`Received \\${signal}, shutting down gracefully\.\.\.\\`)/logger.info("Received " + signal + ", shutting down gracefully...")/g' "$SERVER_FILE"
sed -i 's/logger\.info(\\`🚀 Server running at http:\/\/\\${config\.app\.host}:\\${config\.app\.port}\\`)/logger.info("🚀 Server running at http:\/\/" + config.app.host + ":" + config.app.port)/g' "$SERVER_FILE"
sed -i 's/logger\.info(\\`📚 API Documentation: http:\/\/\\${config\.app\.host}:\\${config\.app\.port}\/docs\\`)/logger.info("📚 API Documentation: http:\/\/" + config.app.host + ":" + config.app.port + "\/docs")/g' "$SERVER_FILE"
sed -i 's/logger\.info(\\`🏥 Health Check: http:\/\/\\${config\.app\.host}:\\${config\.app\.port}\/api\/health\\`)/logger.info("🏥 Health Check: http:\/\/" + config.app.host + ":" + config.app.port + "\/api\/health")/g' "$SERVER_FILE"
sed -i 's/logger\.info(\\`🔌 WebSocket: ws:\/\/\\${config\.app\.host}:\\${config\.app\.port}\\`)/logger.info("🔌 WebSocket: ws:\/\/" + config.app.host + ":" + config.app.port)/g' "$SERVER_FILE"

echo "Server.js syntax fixed!"

# Restart the service
echo "Restarting Event Manager service..."
sudo systemctl restart event-manager

# Wait a moment and check status
sleep 5
if systemctl is-active --quiet event-manager; then
    echo "✅ Event Manager service is now running successfully!"
    echo "You can check the status with: sudo systemctl status event-manager"
    echo "You can view logs with: sudo journalctl -u event-manager -f"
else
    echo "❌ Service still not running. Check logs:"
    sudo journalctl -u event-manager --no-pager -l
fi
