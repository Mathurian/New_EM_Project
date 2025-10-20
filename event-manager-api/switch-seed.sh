#!/bin/bash
# Switch Seed File Script
# This script switches between bcrypt and simple seed files

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

echo "🔄 Event Manager Seed File Switcher"
echo "=================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_warning "package.json not found. Please run this script from the event-manager-api directory."
    exit 1
fi

echo "Choose seed file type:"
echo "1) bcrypt version (requires bcryptjs import)"
echo "2) simple version (pre-hashed password)"
echo ""
read -p "Enter choice (1 or 2): " choice

case $choice in
    1)
        print_status "Switching to bcrypt seed file..."
        mv src/database/seeds/001_initial_data.js src/database/seeds/001_initial_data_backup.js
        mv src/database/seeds/001_initial_data_simple.js src/database/seeds/001_initial_data.js
        mv src/database/seeds/001_initial_data_backup.js src/database/seeds/001_initial_data_simple.js
        print_success "Switched to bcrypt seed file"
        ;;
    2)
        print_status "Switching to simple seed file..."
        mv src/database/seeds/001_initial_data.js src/database/seeds/001_initial_data_backup.js
        mv src/database/seeds/001_initial_data_simple.js src/database/seeds/001_initial_data.js
        mv src/database/seeds/001_initial_data_backup.js src/database/seeds/001_initial_data_simple.js
        print_success "Switched to simple seed file"
        ;;
    *)
        print_warning "Invalid choice. Exiting."
        exit 1
        ;;
esac

print_status "You can now run: npm run db:seed"
