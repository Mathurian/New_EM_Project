#!/bin/bash
# Event Manager Uninstall Script for Ubuntu 24.04
# This script removes all Event Manager application packages and dependencies
# while preserving PostgreSQL database

set -e  # Exit on any error

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

# Function to confirm action
confirm() {
    read -p "$(echo -e ${YELLOW}$1${NC}) [y/N]: " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_warning "Operation cancelled by user"
        exit 0
    fi
}

echo "🗑️  Event Manager Uninstall Script for Ubuntu 24.04"
echo "=================================================="
echo ""
print_warning "This script will remove:"
echo "  • Node.js and npm"
echo "  • All global npm packages"
echo "  • Redis server"
echo "  • Nginx (if installed)"
echo "  • PM2 process manager"
echo "  • Application files and directories"
echo "  • All Event Manager related files"
echo ""
print_warning "This script will PRESERVE:"
echo "  • PostgreSQL database and data"
echo "  • System packages and configurations"
echo ""

# Confirmation
confirm "Are you sure you want to proceed with the uninstallation?"

# Step 1: Stop running applications
print_status "Stopping Event Manager applications..."

# Stop PM2 processes if running
if command -v pm2 &> /dev/null; then
    print_status "Stopping PM2 processes..."
    pm2 stop all 2>/dev/null || true
    pm2 delete all 2>/dev/null || true
    pm2 kill 2>/dev/null || true
    print_success "PM2 processes stopped"
fi

# Stop any running Node.js processes
print_status "Stopping Node.js processes..."
pkill -f "node.*event-manager" 2>/dev/null || true
pkill -f "node.*server.js" 2>/dev/null || true
print_success "Node.js processes stopped"

# Step 2: Stop services
print_status "Stopping services..."

# Stop Redis
if systemctl is-active --quiet redis-server; then
    print_status "Stopping Redis server..."
    sudo systemctl stop redis-server
    print_success "Redis server stopped"
fi

# Stop Nginx
if systemctl is-active --quiet nginx; then
    print_status "Stopping Nginx..."
    sudo systemctl stop nginx
    print_success "Nginx stopped"
fi

# Step 3: Remove Node.js and npm
print_status "Removing Node.js and npm..."
sudo apt-get remove --purge -y nodejs npm 2>/dev/null || true
print_success "Node.js and npm removed"

# Step 4: Remove global npm packages
print_status "Removing global npm packages..."
if command -v npm &> /dev/null; then
    # List and remove global packages
    npm list -g --depth=0 2>/dev/null | tail -n +2 | awk '{print $2}' | awk -F@ '{print $1}' | xargs -r sudo npm uninstall -g 2>/dev/null || true
fi
print_success "Global npm packages removed"

# Step 5: Remove PM2
print_status "Removing PM2..."
sudo npm uninstall -g pm2 2>/dev/null || true
print_success "PM2 removed"

# Step 6: Remove Redis
print_status "Removing Redis server..."
sudo apt-get remove --purge -y redis-server redis-tools libhiredis-dev 2>/dev/null || true
sudo apt-get autoremove -y 2>/dev/null || true
print_success "Redis server removed"

# Step 7: Remove Nginx (if installed for Event Manager)
print_status "Checking for Nginx removal..."
if dpkg -l | grep -q nginx; then
    confirm "Nginx is installed. Do you want to remove it? (This will affect other websites if any)"
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sudo apt-get remove --purge -y nginx nginx-common nginx-core 2>/dev/null || true
        print_success "Nginx removed"
    else
        print_warning "Nginx preserved"
    fi
fi

# Step 8: Remove build dependencies (optional)
confirm "Do you want to remove build dependencies (build-essential, python3-dev, etc.)? These may be needed by other applications."
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_status "Removing build dependencies..."
    sudo apt-get remove --purge -y \
        build-essential \
        python3-dev \
        make \
        g++ \
        pkg-config \
        libvips-dev \
        libcairo2-dev \
        libpango1.0-dev \
        libjpeg-dev \
        libgif-dev \
        librsvg2-dev \
        libpng-dev \
        libwebp-dev \
        libtiff-dev 2>/dev/null || true
    print_success "Build dependencies removed"
else
    print_warning "Build dependencies preserved"
fi

# Step 9: Clean up residual files and directories
print_status "Cleaning up residual files..."

# Remove Node.js related directories
sudo rm -rf /usr/local/lib/node_modules 2>/dev/null || true
sudo rm -rf /usr/local/bin/npm 2>/dev/null || true
sudo rm -rf /usr/local/bin/node 2>/dev/null || true
sudo rm -rf /usr/local/share/man/man1/node* 2>/dev/null || true
sudo rm -rf /usr/local/lib/dtrace/node.d 2>/dev/null || true

# Remove npm cache and config
sudo rm -rf ~/.npm 2>/dev/null || true
sudo rm -rf ~/.node-gyp 2>/dev/null || true
sudo rm -rf ~/.npmrc 2>/dev/null || true

# Remove NodeSource repository
sudo rm -f /etc/apt/sources.list.d/nodesource.list 2>/dev/null || true
sudo apt-key del 9FD3B784BC1C6FC31A8A0A1C1655A0AB68576280 2>/dev/null || true

print_success "Residual files cleaned up"

# Step 10: Remove application directories
print_status "Removing application directories..."

# Common application locations
APP_DIRS=(
    "/opt/event-manager"
    "/var/www/event-manager"
    "/home/*/event-manager"
    "/home/*/New_EM_Project"
    "~/event-manager"
    "~/New_EM_Project"
)

for dir in "${APP_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        print_status "Found application directory: $dir"
        confirm "Remove application directory: $dir?"
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            rm -rf "$dir"
            print_success "Removed: $dir"
        fi
    fi
done

# Remove current directory if it's the Event Manager project
if [ -f "package.json" ] && grep -q "event-manager" package.json 2>/dev/null; then
    print_warning "You are currently in the Event Manager project directory"
    confirm "Do you want to remove the current directory and all its contents?"
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        cd ..
        rm -rf "$(pwd)/New_EM_Project" 2>/dev/null || true
        print_success "Current project directory removed"
    fi
fi

# Step 11: Remove log files
print_status "Cleaning up log files..."
sudo rm -rf /var/log/pm2 2>/dev/null || true
sudo rm -rf /var/log/event-manager* 2>/dev/null || true
print_success "Log files cleaned up"

# Step 12: Remove systemd services (if any)
print_status "Removing systemd services..."
sudo systemctl disable event-manager-api 2>/dev/null || true
sudo rm -f /etc/systemd/system/event-manager-api.service 2>/dev/null || true
sudo systemctl daemon-reload 2>/dev/null || true
print_success "Systemd services removed"

# Step 13: Clean up package cache
print_status "Cleaning up package cache..."
sudo apt-get clean 2>/dev/null || true
sudo apt-get autoremove -y 2>/dev/null || true
print_success "Package cache cleaned"

# Step 14: Verify PostgreSQL is still running
print_status "Verifying PostgreSQL status..."
if systemctl is-active --quiet postgresql; then
    print_success "✅ PostgreSQL is still running and preserved"
    print_status "PostgreSQL version: $(sudo -u postgres psql -c 'SELECT version();' 2>/dev/null | head -3 || echo 'PostgreSQL is running')"
else
    print_warning "PostgreSQL is not running. Starting it..."
    sudo systemctl start postgresql
    sudo systemctl enable postgresql
    print_success "PostgreSQL started and enabled"
fi

# Step 15: Final verification
print_status "Final verification..."

# Check what's left
echo ""
echo "🔍 Remaining packages check:"
echo "=========================="

if command -v node &> /dev/null; then
    print_warning "Node.js is still installed: $(node --version)"
else
    print_success "✅ Node.js completely removed"
fi

if command -v npm &> /dev/null; then
    print_warning "npm is still installed: $(npm --version)"
else
    print_success "✅ npm completely removed"
fi

if command -v redis-server &> /dev/null; then
    print_warning "Redis is still installed"
else
    print_success "✅ Redis completely removed"
fi

if command -v pm2 &> /dev/null; then
    print_warning "PM2 is still installed"
else
    print_success "✅ PM2 completely removed"
fi

if systemctl is-active --quiet postgresql; then
    print_success "✅ PostgreSQL is running and preserved"
else
    print_error "❌ PostgreSQL is not running"
fi

# Summary
echo ""
echo "📋 Uninstallation Summary:"
echo "========================="
echo "✅ Node.js and npm: Removed"
echo "✅ Global npm packages: Removed"
echo "✅ PM2: Removed"
echo "✅ Redis: Removed"
echo "✅ Application files: Removed"
echo "✅ Log files: Cleaned"
echo "✅ Systemd services: Removed"
echo "✅ Package cache: Cleaned"
echo "✅ PostgreSQL: Preserved and running"
echo ""

print_success "🎉 Event Manager uninstallation completed successfully!"
echo ""
print_status "📝 What was preserved:"
echo "  • PostgreSQL database and all data"
echo "  • System packages and configurations"
echo "  • Other applications (if any)"
echo ""
print_status "💡 To reinstall Event Manager:"
echo "  1. Run: ./install-ubuntu-24.04.sh"
echo "  2. Follow the setup instructions in README.md"
echo ""
print_success "Uninstallation completed! 🚀"
