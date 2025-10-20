#!/bin/bash
# Apply npm overrides to fix remaining vulnerabilities
# This script forces a secure version of validator.js

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

echo "🔒 Applying npm overrides to fix validator vulnerabilities"
echo "========================================================"

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_warning "package.json not found. Please run this script from the event-manager-api directory."
    exit 1
fi

# Check if overrides field exists
if ! grep -q '"overrides"' package.json; then
    print_warning "No overrides field found in package.json. Please add it first."
    exit 1
fi

# Clean up existing installation
print_status "Cleaning up existing installation..."
rm -rf node_modules
rm -f package-lock.json
print_success "Cleaned up node_modules and package-lock.json"

# Install with overrides
print_status "Installing dependencies with security overrides..."
npm install --omit=dev --no-audit --no-fund --legacy-peer-deps
print_success "Dependencies installed with overrides applied"

# Run security audit
print_status "Running security audit..."
if npm audit --audit-level=moderate; then
    print_success "✅ All moderate and high severity vulnerabilities resolved!"
else
    print_warning "Some vulnerabilities may still remain. Check the audit report above."
fi

print_success "Override installation completed!"
echo ""
print_status "The validator.js vulnerability should now be fixed."
echo "You can now start your application with: npm start"
