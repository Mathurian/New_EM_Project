#!/bin/bash
# Super Simple Database Fix
# This script replaces the seed file with a working version

set -e

echo "🔧 Super Simple Database Fix"
echo "============================"

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    echo "❌ package.json not found. Please run this script from the event-manager-api directory."
    exit 1
fi

echo "📝 Replacing seed file with ultra-simple version..."

# Replace the seed file with the ultra-simple version
cp src/database/seeds/001_initial_data_ultra_simple.js src/database/seeds/001_initial_data.js

echo "✅ Seed file replaced"

# Run seeds
echo "🌱 Running database seeds..."
if npm run db:seed; then
    echo "✅ Database seeds completed successfully"
    echo "🔐 Default admin user created:"
    echo "   Email: admin@eventmanager.com"
    echo "   Password: admin123"
    echo "⚠️  Please change the default password after first login!"
else
    echo "❌ Database seeds failed"
    exit 1
fi

echo ""
echo "🎉 Database fix completed!"
echo "Next steps:"
echo "1. Start the application: npm start"
echo "2. Access the application in your browser"
echo "3. Login with the default admin credentials"
echo "4. Change the default password"
