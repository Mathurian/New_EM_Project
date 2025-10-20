#!/bin/bash

# Event Manager Setup Script
# This script sets up the complete Event Manager application
# Compatible with macOS and Ubuntu 24.04+

set -e

echo "🚀 Setting up Event Manager Contest System..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Detect operating system
detect_os() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Check if it's Ubuntu
        if command -v lsb_release &> /dev/null; then
            OS_DISTRO=$(lsb_release -si)
            OS_VERSION=$(lsb_release -sr)
            if [[ "$OS_DISTRO" == "Ubuntu" ]]; then
                OS="ubuntu"
                OS_VERSION_NUM=$(echo $OS_VERSION | cut -d'.' -f1)
                print_status "Detected Ubuntu $OS_VERSION"
            else
                OS="linux"
                print_status "Detected Linux distribution: $OS_DISTRO"
            fi
        else
            OS="linux"
            print_status "Detected Linux (distribution unknown)"
        fi
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        OS="macos"
        print_status "Detected macOS"
    else
        OS="unknown"
        print_warning "Unknown operating system: $OSTYPE"
    fi
}

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

# Check if Node.js is installed
check_node() {
    if ! command -v node &> /dev/null; then
        print_error "Node.js is not installed. Please install Node.js 18+ first."
        print_status "Installation instructions:"
        
        if [[ "$OS" == "ubuntu" ]]; then
            print_status "For Ubuntu:"
            print_status "  curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -"
            print_status "  sudo apt-get install -y nodejs"
            print_status ""
            print_status "Or using NVM (recommended):"
            print_status "  curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash"
            print_status "  source ~/.bashrc"
            print_status "  nvm install --lts"
            print_status "  nvm use --lts"
        elif [[ "$OS" == "macos" ]]; then
            print_status "For macOS:"
            print_status "  brew install node"
            print_status ""
            print_status "Or using NVM (recommended):"
            print_status "  curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash"
            print_status "  source ~/.zshrc"
            print_status "  nvm install --lts"
            print_status "  nvm use --lts"
        else
            print_status "Please install Node.js 18+ from https://nodejs.org/"
        fi
        
        exit 1
    fi
    
    NODE_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
    if [ "$NODE_VERSION" -lt 18 ]; then
        print_error "Node.js version 18+ is required. Current version: $(node -v)"
        print_status "Please upgrade Node.js using the instructions above."
        exit 1
    fi
    
    print_success "Node.js $(node -v) is installed"
}

# Check if PostgreSQL is installed
check_postgres() {
    if ! command -v psql &> /dev/null; then
        print_warning "PostgreSQL is not installed. Please install PostgreSQL 12+ first."
        print_status "Installation instructions:"
        
        if [[ "$OS" == "ubuntu" ]]; then
            print_status "For Ubuntu:"
            print_status "  sudo apt update"
            print_status "  sudo apt install -y postgresql postgresql-contrib"
            print_status "  sudo systemctl start postgresql"
            print_status "  sudo systemctl enable postgresql"
            print_status ""
            print_status "Create database and user:"
            print_status "  sudo -u postgres createuser --interactive"
            print_status "  sudo -u postgres createdb event_manager"
        elif [[ "$OS" == "macos" ]]; then
            print_status "For macOS:"
            print_status "  brew install postgresql"
            print_status "  brew services start postgresql"
            print_status ""
            print_status "Create database and user:"
            print_status "  createdb event_manager"
        else
            print_status "Please install PostgreSQL 12+ from https://www.postgresql.org/download/"
        fi
        
        return 1
    fi
    
    print_success "PostgreSQL is installed"
    return 0
}

# Setup backend
setup_backend() {
    print_status "Setting up backend..."
    
    # Check for npm permission issues on Ubuntu
    if [[ "$OS" == "ubuntu" ]]; then
        print_status "Checking npm permissions..."
        if npm config get prefix | grep -q "/usr/local"; then
            print_warning "npm global prefix is set to /usr/local which may cause permission issues."
            print_status "Consider using a Node Version Manager (NVM) or configure npm to use a different directory."
        fi
    fi
    
    # Install dependencies
    print_status "Installing backend dependencies..."
    if ! npm install; then
        print_error "npm install failed. This might be due to permission issues or missing dependencies."
        
        if [[ "$OS" == "ubuntu" ]]; then
            print_status "Troubleshooting steps for Ubuntu:"
            print_status "  1. Try: sudo npm install"
            print_status "  2. Or configure npm to use a different directory:"
            print_status "     mkdir ~/.npm-global"
            print_status "     npm config set prefix '~/.npm-global'"
            print_status "     echo 'export PATH=~/.npm-global/bin:\$PATH' >> ~/.bashrc"
            print_status "     source ~/.bashrc"
            print_status "  3. Or use NVM: curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash"
        fi
        
        exit 1
    fi
    
    # Copy environment file
    if [ ! -f .env ]; then
        print_status "Creating environment file..."
        cp env.example .env
        print_warning "Please edit .env file with your database credentials"
        
        # Provide OS-specific database URL examples
        if [[ "$OS" == "ubuntu" ]]; then
            print_status "Example DATABASE_URL for Ubuntu:"
            print_status "  DATABASE_URL=\"postgresql://postgres:password@localhost:5432/event_manager\""
        elif [[ "$OS" == "macos" ]]; then
            print_status "Example DATABASE_URL for macOS:"
            print_status "  DATABASE_URL=\"postgresql://postgres:password@localhost:5432/event_manager\""
        fi
    else
        print_status "Environment file already exists"
    fi
    
    print_success "Backend setup complete"
}

# Setup frontend
setup_frontend() {
    print_status "Setting up frontend..."
    
    cd frontend
    
    # Install dependencies
    print_status "Installing frontend dependencies..."
    if ! npm install; then
        print_error "Frontend npm install failed. This might be due to permission issues or missing dependencies."
        
        if [[ "$OS" == "ubuntu" ]]; then
            print_status "Troubleshooting steps for Ubuntu:"
            print_status "  1. Try: sudo npm install"
            print_status "  2. Or configure npm to use a different directory (see backend setup instructions)"
            print_status "  3. Or use NVM: curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash"
        fi
        
        cd ..
        exit 1
    fi
    
    # Copy environment file
    if [ ! -f .env ]; then
        print_status "Creating frontend environment file..."
        cp env.example .env
    else
        print_status "Frontend environment file already exists"
    fi
    
    cd ..
    print_success "Frontend setup complete"
}

# Setup database
setup_database() {
    print_status "Setting up database..."
    
    # Check if .env exists
    if [ ! -f .env ]; then
        print_error "Environment file not found. Please run setup first."
        exit 1
    fi
    
    # Source environment variables
    source .env
    
    # Check if DATABASE_URL is set
    if [ -z "$DATABASE_URL" ]; then
        print_error "DATABASE_URL not set in .env file"
        exit 1
    fi
    
    # Extract database info from DATABASE_URL
    DB_USER=$(echo $DATABASE_URL | sed -n 's/.*:\/\/\([^:]*\):.*/\1/p')
    DB_PASS=$(echo $DATABASE_URL | sed -n 's/.*:\/\/[^:]*:\([^@]*\)@.*/\1/p')
    DB_HOST=$(echo $DATABASE_URL | sed -n 's/.*@\([^:]*\):.*/\1/p')
    DB_PORT=$(echo $DATABASE_URL | sed -n 's/.*:\([0-9]*\)\/.*/\1/p')
    DB_NAME=$(echo $DATABASE_URL | sed -n 's/.*\/\([^?]*\).*/\1/p')
    
    print_status "Database: $DB_NAME on $DB_HOST:$DB_PORT"
    
    # Test database connection
    print_status "Testing database connection..."
    if PGPASSWORD=$DB_PASS psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -c '\q' 2>/dev/null; then
        print_success "Database connection successful"
    else
        print_error "Cannot connect to database. Please check your credentials in .env"
        exit 1
    fi
    
    # Run migrations
    print_status "Running database migrations..."
    npm run migrate
    
    # Seed database
    print_status "Seeding database with sample data..."
    npm run seed
    
    print_success "Database setup complete"
}

# Start development servers
start_dev() {
    print_status "Starting development servers..."
    
    # Start backend in background
    print_status "Starting backend server on port 3000..."
    npm run dev &
    BACKEND_PID=$!
    
    # Wait a moment for backend to start
    sleep 3
    
    # Start frontend
    print_status "Starting frontend server on port 3001..."
    cd frontend
    npm run dev &
    FRONTEND_PID=$!
    cd ..
    
    print_success "Development servers started!"
    print_status "Backend: http://localhost:3000"
    print_status "Frontend: http://localhost:3001"
    print_status "Default login: admin@eventmanager.com / admin123"
    
    # Wait for user to stop servers
    print_status "Press Ctrl+C to stop servers"
    wait $BACKEND_PID $FRONTEND_PID
}

# Clean up PHP files
cleanup_php() {
    print_status "Cleaning up PHP files..."
    
    # List of PHP files to remove
    PHP_FILES=(
        "app/"
        "public/"
        "config/"
        "vendor/"
        "composer.json"
        "composer.lock"
        "index.php"
        "*.php"
    )
    
    for file in "${PHP_FILES[@]}"; do
        if [ -e "$file" ]; then
            print_status "Removing $file..."
            rm -rf "$file"
        fi
    done
    
    print_success "PHP files cleaned up"
}

# Check Ubuntu-specific dependencies
check_ubuntu_deps() {
    if [[ "$OS" == "ubuntu" ]]; then
        print_status "Checking Ubuntu-specific dependencies..."
        
        # Check for curl (needed for Node.js installation)
        if ! command -v curl &> /dev/null; then
            print_warning "curl is not installed. Installing..."
            sudo apt update && sudo apt install -y curl
        fi
        
        # Check for build-essential (needed for native modules)
        if ! dpkg -l | grep -q build-essential; then
            print_warning "build-essential is not installed. Installing..."
            sudo apt update && sudo apt install -y build-essential
        fi
        
        # Check for Python (needed for some npm packages)
        if ! command -v python3 &> /dev/null; then
            print_warning "Python 3 is not installed. Installing..."
            sudo apt update && sudo apt install -y python3 python3-pip
        fi
        
        # Check for git (needed for some npm packages)
        if ! command -v git &> /dev/null; then
            print_warning "git is not installed. Installing..."
            sudo apt update && sudo apt install -y git
        fi
        
        print_success "Ubuntu dependencies checked"
    fi
}

# Show Ubuntu-specific troubleshooting tips
show_ubuntu_troubleshooting() {
    if [[ "$OS" == "ubuntu" ]]; then
        echo ""
        print_status "🔧 Ubuntu Troubleshooting Tips:"
        print_status "If you encounter issues, try these solutions:"
        print_status ""
        print_status "1. Permission Issues with npm:"
        print_status "   sudo chown -R \$(whoami) ~/.npm"
        print_status "   sudo chown -R \$(whoami) /usr/local/lib/node_modules"
        print_status ""
        print_status "2. PostgreSQL Connection Issues:"
        print_status "   sudo systemctl restart postgresql"
        print_status "   sudo -u postgres psql -c \"ALTER USER postgres PASSWORD 'password';\""
        print_status ""
        print_status "3. Port Already in Use:"
        print_status "   sudo lsof -i :3000  # Check what's using port 3000"
        print_status "   sudo lsof -i :3001  # Check what's using port 3001"
        print_status ""
        print_status "4. Firewall Issues:"
        print_status "   sudo ufw allow 3000"
        print_status "   sudo ufw allow 3001"
        print_status ""
        print_status "5. Node.js Version Issues:"
        print_status "   curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash"
        print_status "   source ~/.bashrc"
        print_status "   nvm install --lts"
        print_status "   nvm use --lts"
    fi
}

# Main setup function
main() {
    echo "🎯 Event Manager Contest System Setup"
    echo "====================================="
    
    # Detect operating system first
    detect_os
    
    # Check Ubuntu-specific dependencies
    check_ubuntu_deps
    
    # Check prerequisites
    check_node
    check_postgres
    
    # Setup applications
    setup_backend
    setup_frontend
    
    # Ask about database setup
    echo ""
    read -p "Do you want to setup the database now? (y/n): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        setup_database
    else
        print_warning "Skipping database setup. Run 'npm run migrate' and 'npm run seed' when ready."
        print_status "Make sure your PostgreSQL database is running and accessible."
        
        if [[ "$OS" == "ubuntu" ]]; then
            print_status "Ubuntu PostgreSQL commands:"
            print_status "  sudo systemctl start postgresql    # Start PostgreSQL"
            print_status "  sudo systemctl status postgresql   # Check status"
            print_status "  sudo -u postgres psql              # Connect as postgres user"
        elif [[ "$OS" == "macos" ]]; then
            print_status "macOS PostgreSQL commands:"
            print_status "  brew services start postgresql     # Start PostgreSQL"
            print_status "  brew services list | grep postgres # Check status"
            print_status "  psql postgres                       # Connect as postgres user"
        fi
    fi
    
    # Ask about cleanup
    echo ""
    read -p "Do you want to remove the old PHP files? (y/n): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        cleanup_php
    else
        print_warning "PHP files kept. You can remove them manually later."
    fi
    
    # Ask about starting dev servers
    echo ""
    read -p "Do you want to start the development servers now? (y/n): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        start_dev
    else
        print_success "Setup complete!"
        echo ""
        print_status "To start development servers:"
        print_status "  Backend: npm run dev"
        print_status "  Frontend: cd frontend && npm run dev"
        echo ""
        print_status "To setup database later:"
        print_status "  npm run migrate"
        print_status "  npm run seed"
        echo ""
        
        # OS-specific additional instructions
        if [[ "$OS" == "ubuntu" ]]; then
            print_status "Ubuntu-specific notes:"
            print_status "  - Make sure PostgreSQL is running: sudo systemctl status postgresql"
            print_status "  - If you encounter permission issues, check your .env DATABASE_URL"
            print_status "  - For firewall issues, ensure ports 3000 and 3001 are accessible"
            print_status "  - If npm install fails, try: sudo npm install -g npm@latest"
        elif [[ "$OS" == "macos" ]]; then
            print_status "macOS-specific notes:"
            print_status "  - Make sure PostgreSQL is running: brew services list | grep postgres"
            print_status "  - If you encounter permission issues, check your .env DATABASE_URL"
        fi
        
        print_status ""
        print_status "Default login credentials:"
        print_status "  Email: admin@eventmanager.com"
        print_status "  Password: admin123"
        
        # Show Ubuntu-specific troubleshooting tips
        show_ubuntu_troubleshooting
    fi
}

# Run main function
main "$@"
