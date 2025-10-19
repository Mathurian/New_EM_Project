#!/bin/bash
# Event Manager Installation Script for Ubuntu 24.04
# This script installs all system dependencies and prepares the environment

set -e  # Exit on any error

echo "🚀 Event Manager Installation Script for Ubuntu 24.04"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
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

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_error "This script should not be run as root. Please run as a regular user with sudo privileges."
   exit 1
fi

# Check if sudo is available
if ! command -v sudo &> /dev/null; then
    print_error "sudo is required but not installed. Please install sudo first."
    exit 1
fi

print_status "Starting installation process..."

# Step 1: Update system packages
print_status "Updating system packages..."
sudo apt update && sudo apt upgrade -y
print_success "System packages updated"

# Step 2: Install essential system dependencies
print_status "Installing essential system dependencies..."
sudo apt install -y \
    curl \
    wget \
    git \
    build-essential \
    python3-dev \
    make \
    g++ \
    pkg-config \
    software-properties-common \
    ca-certificates \
    gnupg \
    lsb-release
print_success "Essential dependencies installed"

# Step 3: Install image processing libraries (required for sharp package)
print_status "Installing image processing libraries..."
sudo apt install -y \
    libvips-dev \
    libcairo2-dev \
    libpango1.0-dev \
    libjpeg-dev \
    libgif-dev \
    librsvg2-dev \
    libpng-dev \
    libwebp-dev \
    libtiff-dev
print_success "Image processing libraries installed"

# Step 4: Install PostgreSQL
print_status "Installing PostgreSQL..."
sudo apt install -y postgresql postgresql-contrib postgresql-server-dev-all
sudo systemctl start postgresql
sudo systemctl enable postgresql
print_success "PostgreSQL installed and started"

# Step 5: Install Redis
print_status "Installing Redis..."
sudo apt install -y redis-server libhiredis-dev
sudo systemctl start redis-server
sudo systemctl enable redis-server
print_success "Redis installed and started"

# Step 6: Remove existing Node.js if present
print_status "Removing existing Node.js installations..."
sudo apt remove nodejs npm -y 2>/dev/null || true
print_success "Existing Node.js installations removed"

# Step 7: Install Node.js 20.x (compatible version)
print_status "Installing Node.js 20.x..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
print_success "Node.js 20.x installed"

# Step 8: Verify installations
print_status "Verifying installations..."
echo "Node.js version: $(node --version)"
echo "npm version: $(npm --version)"
echo "PostgreSQL version: $(sudo -u postgres psql -c 'SELECT version();' 2>/dev/null | head -3 || echo 'PostgreSQL installed')"
echo "Redis version: $(redis-server --version 2>/dev/null | head -1 || echo 'Redis installed')"

# Step 9: Set up database
print_status "Setting up database..."
sudo -u postgres psql << EOF
CREATE DATABASE event_manager;
CREATE USER event_manager WITH PASSWORD 'event_manager_secure_password_2024';
GRANT ALL PRIVILEGES ON DATABASE event_manager TO event_manager;
ALTER USER event_manager CREATEDB;
\q
EOF
print_success "Database setup completed"

# Step 10: Configure PostgreSQL for local connections
print_status "Configuring PostgreSQL..."
sudo -u postgres psql -c "ALTER USER event_manager CREATEDB;"
print_success "PostgreSQL configured"

# Step 11: Test services
print_status "Testing services..."
if systemctl is-active --quiet postgresql; then
    print_success "PostgreSQL is running"
else
    print_error "PostgreSQL is not running"
fi

if systemctl is-active --quiet redis-server; then
    print_success "Redis is running"
else
    print_error "Redis is not running"
fi

# Step 12: Create application directory structure
print_status "Creating application directory structure..."
mkdir -p ~/event-manager/{uploads,logs,backups}
print_success "Application directories created"

# Step 13: Set up npm permissions
print_status "Setting up npm permissions..."
sudo chown -R $(whoami) ~/.npm 2>/dev/null || true
print_success "npm permissions configured"

# Step 14: Install PM2 globally (optional)
print_status "Installing PM2 for process management..."
sudo npm install -g pm2
print_success "PM2 installed"

# Final verification
print_status "Final verification..."
echo ""
echo "✅ Installation Summary:"
echo "========================"
echo "Node.js: $(node --version)"
echo "npm: $(npm --version)"
echo "PostgreSQL: Installed and running"
echo "Redis: Installed and running"
echo "PM2: $(pm2 --version 2>/dev/null || echo 'Installed')"
echo ""

print_success "🎉 All system dependencies installed successfully!"
echo ""
print_status "📝 Next steps:"
echo "1. Clone your Event Manager repository"
echo "2. Navigate to the event-manager-api directory"
echo "3. Run: npm install --omit=dev"
echo "4. Copy and configure your .env file"
echo "5. Run database migrations: npm run db:migrate"
echo "6. Start the application: npm start"
echo ""
print_status "🔧 Database credentials:"
echo "Database: event_manager"
echo "User: event_manager"
echo "Password: event_manager_secure_password_2024"
echo ""
print_warning "⚠️  Remember to change the database password in production!"
echo ""
print_status "📚 For detailed setup instructions, see the README.md file"
echo ""
print_success "Installation completed successfully! 🚀"
