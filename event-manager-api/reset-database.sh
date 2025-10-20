#!/bin/bash
# Database Reset and Reseed Script
# This script resets the database and runs seeds with corrected schema

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

echo "🔄 Event Manager Database Reset and Reseed Script"
echo "==============================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_warning "package.json not found. Please run this script from the event-manager-api directory."
    exit 1
fi

# Check if .env file exists
if [ ! -f "../.env" ]; then
    print_warning ".env file not found in parent directory."
    print_status "Please run ./setup-env.sh first"
    exit 1
fi

print_warning "This will reset the database and re-run all migrations and seeds."
read -p "Are you sure you want to continue? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_status "Operation cancelled"
    exit 0
fi

# Reset database (rollback all migrations)
print_status "Rolling back all migrations..."
if npm run db:migrate:rollback 2>/dev/null || true; then
    print_success "Migrations rolled back"
else
    print_warning "No migrations to rollback or rollback failed"
fi

# Run migrations
print_status "Running database migrations..."
if npm run db:migrate; then
    print_success "Database migrations completed successfully"
else
    print_error "Database migrations failed"
    exit 1
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

print_success "Database reset and reseed completed!"
echo ""
print_status "Next steps:"
echo "1. Start the application: npm start"
echo "2. Access the application in your browser"
echo "3. Login with the default admin credentials"
echo "4. Change the default password"
