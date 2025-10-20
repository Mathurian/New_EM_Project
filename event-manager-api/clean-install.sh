#!/bin/bash
# Clean install script for Event Manager API
# This script removes old node_modules and reinstalls with updated packages

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

echo "🧹 Event Manager Clean Install Script"
echo "====================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_warning "package.json not found. Please run this script from the event-manager-api directory."
    exit 1
fi

# Clean up existing installation
print_status "Cleaning up existing installation..."
rm -rf node_modules
rm -f package-lock.json
print_success "Cleaned up node_modules and package-lock.json"

# Install production dependencies only
print_status "Installing production dependencies..."
npm install --omit=dev --no-audit --no-fund
print_success "Production dependencies installed"

# Run security audit
print_status "Running security audit..."
npm audit --audit-level=moderate || true

print_success "Clean install completed!"
echo ""
print_status "Next steps:"
echo "1. Run: npm run db:migrate"
echo "2. Start the application: npm start"
