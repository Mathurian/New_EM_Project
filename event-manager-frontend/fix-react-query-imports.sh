#!/bin/bash
# Fix React Query Imports Script
# This script updates all react-query imports to @tanstack/react-query

set -e

echo "🔧 Fixing React Query Imports"
echo "============================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    echo "❌ package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

echo "📝 Updating import statements..."

# Find and replace react-query imports
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/from '\''react-query'\''/from '\''@tanstack\/react-query'\''/g'
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/from "react-query"/from "@tanstack\/react-query"/g'

echo "✅ Import statements updated"

# Install dependencies
echo "📦 Installing dependencies..."
npm install

echo "🎉 React Query imports fixed!"
echo "You can now run: npm run build"
