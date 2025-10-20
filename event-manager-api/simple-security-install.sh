#!/bin/bash
# Simple security install script for Event Manager API
# This script handles dependency conflicts gracefully

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

echo "🔒 Event Manager Simple Security Install Script"
echo "=============================================="

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

# Clear npm cache
print_status "Clearing npm cache..."
npm cache clean --force
print_success "npm cache cleared"

# Install production dependencies with legacy peer deps
print_status "Installing production dependencies..."
if npm install --omit=dev --no-audit --no-fund --legacy-peer-deps; then
    print_success "Production dependencies installed successfully"
else
    print_warning "Installation had issues, trying with force flag..."
    npm install --omit=dev --no-audit --no-fund --legacy-peer-deps --force
    print_success "Production dependencies installed with force flag"
fi

# Run security audit
print_status "Running security audit..."
if npm audit --audit-level=moderate; then
    print_success "No moderate or high severity vulnerabilities found!"
else
    print_warning "Some vulnerabilities may remain. Check the audit report above."
    print_status "Attempting to fix with npm overrides..."
    
    # Check if overrides field exists in package.json
    if grep -q '"overrides"' package.json; then
        print_status "Applying npm overrides to fix remaining vulnerabilities..."
        rm -rf node_modules package-lock.json
        npm install --omit=dev --no-audit --no-fund --legacy-peer-deps
        
        print_status "Re-running security audit after overrides..."
        if npm audit --audit-level=moderate; then
            print_success "✅ All vulnerabilities resolved with overrides!"
        else
            print_warning "Some vulnerabilities may still remain after overrides."
        fi
    else
        print_status "No overrides configured. You may need to manually update packages."
    fi
fi

print_success "Security install completed!"
echo ""
print_status "Next steps:"
echo "1. Run: npm run db:migrate"
echo "2. Start the application: npm start"
echo "3. Test email functionality (nodemailer was updated to v7)"
