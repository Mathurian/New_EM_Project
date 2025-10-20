#!/bin/bash
# Database Setup Script for Event Manager API
# This script runs migrations and seeds the database

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

echo "🗄️  Event Manager Database Setup Script"
echo "======================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_warning "package.json not found. Please run this script from the event-manager-api directory."
    exit 1
fi

# Check if .env file exists
if [ ! -f "../.env" ]; then
    print_warning ".env file not found in parent directory. Please create one with your database configuration."
    print_status "Expected location: /opt/event-manager/.env"
    print_status "Example .env file:"
    echo "DB_HOST=localhost"
    echo "DB_PORT=5432"
    echo "DB_USER=eventmanager"
    echo "DB_PASSWORD=your_password"
    echo "DB_NAME=eventmanager"
    echo "DB_SSL=false"
    exit 1
fi

# Run migrations
print_status "Running database migrations..."
if npm run db:migrate; then
    print_success "Database migrations completed successfully"
else
    print_error "Database migrations failed"
    exit 1
fi

# Ask if user wants to run seeds
echo ""
read -p "Do you want to run database seeds? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
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
else
    print_status "Skipping database seeds"
fi

print_success "Database setup completed!"
echo ""
print_status "Next steps:"
echo "1. Start the application: npm start"
echo "2. Access the application in your browser"
echo "3. Login with the default admin credentials (if seeds were run)"
echo "4. Change the default password"
