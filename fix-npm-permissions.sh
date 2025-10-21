#!/bin/bash
# fix-npm-permissions.sh
# Emergency fix for npm EACCES permission errors
# Note: For new installations, use: ./setup.sh --skip-web-server-permissions

set -e

echo "🔧 Emergency npm permissions fix for Ubuntu 24.04..."
echo "ℹ️  For new installations, use: ./setup.sh --skip-web-server-permissions"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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
    print_error "This script should not be run as root. Please run as a regular user."
    exit 1
fi

# Get current user and group
CURRENT_USER=$(whoami)
CURRENT_GROUP=$(id -gn)

print_status "Current user: $CURRENT_USER"
print_status "Current group: $CURRENT_GROUP"

# Fix npm global directory ownership
print_status "Fixing npm global directory ownership..."
if [[ -d "/usr/local/lib/node_modules" ]]; then
    sudo chown -R $CURRENT_USER:$CURRENT_GROUP /usr/local/lib/node_modules
    print_success "Fixed /usr/local/lib/node_modules ownership"
fi

if [[ -d "/usr/local/bin" ]]; then
    sudo chown -R $CURRENT_USER:$CURRENT_GROUP /usr/local/bin
    print_success "Fixed /usr/local/bin ownership"
fi

if [[ -d "/usr/local/share" ]]; then
    sudo chown -R $CURRENT_USER:$CURRENT_GROUP /usr/local/share
    print_success "Fixed /usr/local/share ownership"
fi

# Fix npm cache permissions
print_status "Fixing npm cache permissions..."
if [[ -d "$HOME/.npm" ]]; then
    chown -R $CURRENT_USER:$CURRENT_GROUP $HOME/.npm
    print_success "Fixed npm cache ownership"
fi

# Configure npm to use user directory
print_status "Configuring npm to use user directory..."
mkdir -p ~/.npm-global
npm config set prefix ~/.npm-global

# Add to PATH if not already present
if ! grep -q "~/.npm-global/bin" ~/.bashrc; then
    echo 'export PATH=~/.npm-global/bin:$PATH' >> ~/.bashrc
    print_success "Added npm global bin to PATH in ~/.bashrc"
fi

# Export PATH for current session
export PATH=~/.npm-global/bin:$PATH

# Fix package-lock.json if it exists
if [[ -f "package-lock.json" ]]; then
    print_status "Fixing package-lock.json ownership..."
    sudo chown $CURRENT_USER:$CURRENT_GROUP package-lock.json
    chmod 644 package-lock.json
    print_success "Fixed package-lock.json ownership and permissions"
fi

# Fix project directory ownership
print_status "Fixing project directory ownership..."
sudo chown -R $CURRENT_USER:$CURRENT_GROUP .
print_success "Fixed project directory ownership"

print_success "npm permissions fix complete!"
print_status "You can now run 'npm install' without permission errors."
print_warning "Note: You may need to run 'source ~/.bashrc' or restart your terminal for PATH changes to take effect."

# Test npm
print_status "Testing npm..."
if npm --version > /dev/null 2>&1; then
    print_success "npm is working correctly"
else
    print_warning "npm test failed. Try running 'source ~/.bashrc' and test again."
fi

echo ""
print_status "📚 For future installations, use the enhanced setup script:"
print_status "   Development: ./setup.sh --skip-web-server-permissions"
print_status "   Production:  ./setup.sh --non-interactive --auto-setup-permissions"
print_status "   Interactive: ./setup.sh"
