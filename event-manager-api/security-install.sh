#!/bin/bash
# Security-focused clean install script for Event Manager API
# This script removes old node_modules and reinstalls with security fixes

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

echo "🔒 Event Manager Security-Focused Clean Install Script"
echo "====================================================="

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
print_status "Installing production dependencies with security fixes..."
npm install --omit=dev --no-audit --no-fund

# Apply security resolutions
print_status "Applying security resolutions..."
npx npm-force-resolutions 2>/dev/null || print_warning "npm-force-resolutions not available, continuing..."

# Reinstall to apply resolutions
print_status "Reinstalling with security resolutions..."
npm install --omit=dev --no-audit --no-fund

print_success "Production dependencies installed with security fixes"

# Run security audit
print_status "Running security audit..."
if npm audit --audit-level=moderate; then
    print_success "No moderate or high severity vulnerabilities found!"
else
    print_warning "Some vulnerabilities may remain. Check the audit report above."
fi

print_success "Security-focused clean install completed!"
echo ""
print_status "Next steps:"
echo "1. Run: npm run db:migrate"
echo "2. Start the application: npm start"
echo "3. Test email functionality (nodemailer was updated to v7)"
