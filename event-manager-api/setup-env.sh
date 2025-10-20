#!/bin/bash
# Environment Setup Script for Event Manager
# This script creates the .env file in the correct location

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

echo "⚙️  Event Manager Environment Setup Script"
echo "=========================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_warning "package.json not found. Please run this script from the event-manager-api directory."
    exit 1
fi

# Check if .env already exists
if [ -f "../.env" ]; then
    print_warning ".env file already exists at /opt/event-manager/.env"
    read -p "Do you want to overwrite it? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_status "Keeping existing .env file"
        exit 0
    fi
fi

# Create .env file
print_status "Creating .env file at /opt/event-manager/.env..."

cat > ../.env << 'EOF'
# Event Manager Environment Configuration

# Application Settings
APP_NAME=Event Manager
APP_VERSION=2.0.0
NODE_ENV=production
PORT=3000
HOST=0.0.0.0
APP_URL=http://localhost:3000
TZ=America/New_York
DEBUG=false

# Database Configuration
DB_HOST=localhost
DB_PORT=5432
DB_NAME=eventmanager
DB_USER=eventmanager
DB_PASSWORD=change_this_password
DB_SSL=false
DB_MAX_CONNECTIONS=20
DB_MIN_CONNECTIONS=2

# Redis Configuration
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
REDIS_KEY_PREFIX=event_manager:
REDIS_TTL=3600

# Session Configuration
SESSION_SECRET=change-this-super-secret-session-key-in-production
SESSION_MAX_AGE=86400000
SESSION_SECURE=false
SESSION_TIMEOUT=1800000

# Security Settings
BCRYPT_ROUNDS=12
CSRF_SECRET=change-this-csrf-secret-key
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW_MS=900000
MAX_FILE_SIZE=5242880
ALLOWED_FILE_TYPES=image/jpeg,image/png,image/gif,application/pdf

# Email Configuration
EMAIL_HOST=localhost
EMAIL_PORT=587
EMAIL_SECURE=false
EMAIL_USER=
EMAIL_PASS=
EMAIL_FROM=noreply@eventmanager.com

# Logging Configuration
LOG_LEVEL=info
LOG_FILE=./logs/app.log
LOG_MAX_SIZE=10m
LOG_MAX_FILES=5
LOG_DATE_PATTERN=YYYY-MM-DD

# Feature Flags
FEATURE_REALTIME_SCORING=true
FEATURE_EMAIL_NOTIFICATIONS=true
FEATURE_FILE_UPLOADS=true
FEATURE_AUDIT_LOGGING=true
FEATURE_BACKUP_AUTOMATION=true
FEATURE_API_DOCS=true

# CORS Configuration
CORS_ORIGIN=http://localhost:3000,http://localhost:5173

# Apache Configuration
APACHE_ENABLED=true
APACHE_DOCUMENT_ROOT=/var/www/html
APACHE_CONFIG_PATH=/etc/apache2/sites-available/event-manager.conf
APACHE_SSL_ENABLED=false
APACHE_SSL_CERT_PATH=/etc/ssl/certs/event-manager.crt
APACHE_SSL_KEY_PATH=/etc/ssl/private/event-manager.key
EOF

print_success ".env file created successfully!"

# Set proper permissions
chmod 600 ../.env
print_success "Set secure permissions on .env file"

print_warning "IMPORTANT: Please update the following values in /opt/event-manager/.env:"
echo "1. DB_PASSWORD - Set a secure database password"
echo "2. SESSION_SECRET - Generate a random secret key"
echo "3. CSRF_SECRET - Generate a random CSRF secret"
echo "4. DB_USER - Update if using different database user"
echo "5. DB_NAME - Update if using different database name"

print_status "You can now run: ./setup-database.sh"
