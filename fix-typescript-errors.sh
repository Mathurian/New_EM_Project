#!/bin/bash

# Script to fix TypeScript errors on remote server
# Run this on your remote server after copying the corrected api.ts file

echo "🔧 Fixing TypeScript errors on remote server..."

# Navigate to the frontend directory
cd /var/www/event-manager/frontend || {
    echo "❌ Frontend directory not found. Please run setup.sh first."
    exit 1
}

# Backup the current API service file
echo "📦 Backing up current API service file..."
cp src/services/api.ts src/services/api.ts.backup

# The corrected API service file should be copied here
# (You'll need to copy the corrected api.ts file to this location)

echo "✅ API service file backup created at src/services/api.ts.backup"
echo ""
echo "📋 Next steps:"
echo "1. Copy the corrected api.ts file to: /var/www/event-manager/frontend/src/services/api.ts"
echo "2. Run: cd /var/www/event-manager/frontend && npm run build"
echo "3. If successful, restart the frontend service"
echo ""
echo "🚀 Ready for deployment!"
