#!/bin/bash
# Simple Direct Fix Script
# This script installs dependencies and builds the frontend

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

echo "🔧 Simple Direct Fix"
echo "===================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Step 1: Install dependencies
print_status "Step 1: Installing dependencies..."
npm install
print_success "Dependencies installed"

# Step 2: Try building
print_status "Step 2: Attempting to build frontend..."
if npm run build; then
    print_success "Frontend build completed successfully!"
    print_status "Build output is in the 'dist' directory"
else
    print_warning "Frontend build had issues, but dependencies are installed"
    print_status "You can now run: npm run type-check"
    print_status "Or try: npm run build"
fi

print_success "Simple direct fix completed!"
echo ""
print_status "Next steps:"
echo "1. Dependencies are now installed"
echo "2. You can run: npm run type-check"
echo "3. You can run: npm run build"
echo "4. You can serve it with: npm run preview"
