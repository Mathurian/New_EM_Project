#!/bin/bash
# Frontend Build Fix Script
# This script fixes all frontend build issues

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

echo "🔧 Event Manager Frontend Build Fix"
echo "==================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Step 1: Fix React Query imports
print_status "Step 1: Fixing React Query imports..."
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/from '\''react-query'\''/from '\''@tanstack\/react-query'\''/g'
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/from "react-query"/from "@tanstack\/react-query"/g'
print_success "React Query imports fixed"

# Step 2: Install dependencies
print_status "Step 2: Installing dependencies..."
npm install
print_success "Dependencies installed"

# Step 3: Run type check
print_status "Step 3: Running TypeScript type check..."
if npm run type-check; then
    print_success "TypeScript type check passed"
else
    print_warning "TypeScript type check had issues, but continuing..."
fi

# Step 4: Try building
print_status "Step 4: Attempting to build frontend..."
if npm run build; then
    print_success "Frontend build completed successfully!"
    print_status "Build output is in the 'dist' directory"
else
    print_error "Frontend build failed"
    print_status "Check the error messages above for remaining issues"
    exit 1
fi

print_success "Frontend build fix completed!"
echo ""
print_status "Next steps:"
echo "1. The frontend is now built and ready"
echo "2. You can serve it with: npm run preview"
echo "3. Or integrate it with your backend server"
