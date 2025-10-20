#!/bin/bash
# Quick Database Fix Script
# This script uses the simple seed file to avoid bcrypt import issues

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

echo "🔧 Event Manager Quick Database Fix"
echo "==================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_warning "package.json not found. Please run this script from the event-manager-api directory."
    exit 1
fi

# Switch to simple seed file
print_status "Switching to simple seed file (no bcrypt import required)..."
if [ -f "src/database/seeds/001_initial_data_simple.js" ]; then
    # Backup current file
    cp src/database/seeds/001_initial_data.js src/database/seeds/001_initial_data_bcrypt.js
    # Replace with simple version
    cp src/database/seeds/001_initial_data_simple.js src/database/seeds/001_initial_data.js
    print_success "Switched to simple seed file"
else
    print_warning "Simple seed file not found, using current file"
fi

# Run seeds
print_status "Running database seeds..."
if npm run db:seed; then
    print_success "Database seeds completed successfully"
    print_status "Default admin user created:"
    print_status "Email: admin@eventmanager.com"
    print_status "Password: admin123"
    print_warning "Please change the default password after first login!"
else
    print_error "Database seeds failed"
    exit 1
fi

print_success "Database fix completed!"
echo ""
print_status "Next steps:"
echo "1. Start the application: npm start"
echo "2. Access the application in your browser"
echo "3. Login with the default admin credentials"
echo "4. Change the default password"
