#!/bin/bash

# Event Manager Complete Installation Script
# This script can install ALL prerequisites and set up the complete Event Manager application
# Compatible with macOS and Ubuntu 24.04+
# 
# Usage:
#   chmod +x setup.sh
#   ./setup.sh
#
# This script will:
#   1. Detect your operating system
#   2. Optionally install all prerequisites (Node.js, PostgreSQL, build tools)
#   3. Set up the backend and frontend applications
#   4. Configure the database
#   5. Clean up old PHP files (optional)

set -e

echo "🚀 Event Manager Complete Installation Script"
echo "=============================================="

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

# Detect web server user
detect_web_server() {
    WEB_SERVER_USER=""
    WEB_SERVER_GROUP=""
    
    if [[ "$OS" == "linux" || "$OS" == "ubuntu" ]]; then
        # Check for common web server processes
        if pgrep -f nginx > /dev/null 2>&1; then
            WEB_SERVER_USER="www-data"
            WEB_SERVER_GROUP="www-data"
            print_status "Detected Nginx web server"
        elif pgrep -f apache2 > /dev/null 2>&1; then
            WEB_SERVER_USER="www-data"
            WEB_SERVER_GROUP="www-data"
            print_status "Detected Apache web server"
        elif pgrep -f httpd > /dev/null 2>&1; then
            WEB_SERVER_USER="apache"
            WEB_SERVER_GROUP="apache"
            print_status "Detected Apache (httpd) web server"
        else
            # Default to www-data for Linux
            WEB_SERVER_USER="www-data"
            WEB_SERVER_GROUP="www-data"
            print_status "No web server detected, using default: www-data"
        fi
        
        # Verify the user exists
        if ! id "$WEB_SERVER_USER" &> /dev/null; then
            print_warning "Web server user '$WEB_SERVER_USER' does not exist"
            WEB_SERVER_USER=""
            WEB_SERVER_GROUP=""
        fi
    fi
}

# Fix npm permission issues
fix_npm_permissions() {
    if [[ "$OS" != "linux" && "$OS" != "ubuntu" ]]; then
        return
    fi
    
    print_status "Checking and fixing npm permissions..."
    
    # Check if npm global directories exist and fix ownership if needed
    if [[ -d "/usr/local/lib/node_modules" ]]; then
        if [[ "$(stat -c %U /usr/local/lib/node_modules)" == "root" ]]; then
            print_status "Fixing npm global directory ownership..."
            sudo chown -R $USER:$(id -gn $USER) /usr/local/lib/node_modules
        else
            print_status "npm global directory already has correct ownership"
        fi
    else
        print_status "npm global directory /usr/local/lib/node_modules does not exist (likely using NVM or user installation)"
    fi
    
    # Fix other npm directories if they exist
    if [[ -d "/usr/local/bin" ]] && [[ "$(stat -c %U /usr/local/bin)" == "root" ]]; then
        print_status "Fixing /usr/local/bin ownership..."
        sudo chown -R $USER:$(id -gn $USER) /usr/local/bin
    fi
    
    if [[ -d "/usr/local/share" ]] && [[ "$(stat -c %U /usr/local/share)" == "root" ]]; then
        print_status "Fixing /usr/local/share ownership..."
        sudo chown -R $USER:$(id -gn $USER) /usr/local/share
    fi
    
    # Check npm cache permissions
    if [[ -d "$HOME/.npm" ]]; then
        print_status "Fixing npm cache permissions..."
        chown -R $USER:$(id -gn $USER) $HOME/.npm
    fi
    
    # Set npm prefix to user directory to avoid permission issues
    print_status "Configuring npm to use user directory..."
    mkdir -p ~/.npm-global
    npm config set prefix ~/.npm-global
    
    # Add to PATH if not already present
    if ! grep -q "~/.npm-global/bin" ~/.bashrc; then
        echo 'export PATH=~/.npm-global/bin:$PATH' >> ~/.bashrc
        print_success "Added npm global bin to PATH in ~/.bashrc"
    fi
    export PATH=~/.npm-global/bin:$PATH
    
    print_success "npm permissions fixed!"
}

# Set up proper permissions for server deployment
setup_permissions() {
    if [[ "$OS" != "linux" && "$OS" != "ubuntu" ]]; then
        print_status "Skipping server permissions setup (not Linux)"
        return
    fi
    
    print_status "Setting up permissions for server deployment..."
    
    # Detect web server if not already done
    if [[ -z "$WEB_SERVER_USER" ]]; then
        detect_web_server
    fi
    
    # Get current user
    CURRENT_USER=$(whoami)
    CURRENT_GROUP=$(id -gn)
    
    print_status "Current user: $CURRENT_USER"
    print_status "Web server user: $WEB_SERVER_USER"
    
    # Check if we're in a development environment
    if [[ "$APP_ENV" == "development" && "$CURRENT_USER" != "root" ]]; then
        print_status "Development environment detected. Keeping ownership as current user for npm compatibility."
        WEB_SERVER_USER="$CURRENT_USER"
        WEB_SERVER_GROUP="$CURRENT_GROUP"
    else
        # Set ownership for production deployment
        if [[ -n "$WEB_SERVER_USER" ]]; then
            print_status "Setting ownership to $WEB_SERVER_USER:$WEB_SERVER_GROUP..."
            
            # Set ownership of application files
            sudo chown -R "$WEB_SERVER_USER:$WEB_SERVER_GROUP" . 2>/dev/null || {
                print_warning "Could not set ownership to web server user. Using current user instead."
                WEB_SERVER_USER="$CURRENT_USER"
                WEB_SERVER_GROUP="$CURRENT_GROUP"
            }
        else
            print_status "Using current user for ownership: $CURRENT_USER:$CURRENT_GROUP"
            WEB_SERVER_USER="$CURRENT_USER"
            WEB_SERVER_GROUP="$CURRENT_GROUP"
        fi
    fi
    
    # Set directory permissions (755)
    print_status "Setting directory permissions to 755..."
    sudo find . -type d -exec chmod 755 {} \; 2>/dev/null || find . -type d -exec chmod 755 {} \;
    
    # Set file permissions (644)
    print_status "Setting file permissions to 644..."
    sudo find . -type f -exec chmod 644 {} \; 2>/dev/null || find . -type f -exec chmod 644 {} \;
    
    # Make scripts executable
    print_status "Making scripts executable..."
    sudo chmod +x setup.sh 2>/dev/null || chmod +x setup.sh
    if [[ -f "install.sh" ]]; then
        sudo chmod +x install.sh 2>/dev/null || chmod +x install.sh
    fi
    
    # Secure sensitive files
    print_status "Securing sensitive configuration files..."
    if [[ -f ".env" ]]; then
        sudo chmod 600 .env 2>/dev/null || chmod 600 .env
    fi
    if [[ -f "frontend/.env" ]]; then
        sudo chmod 600 frontend/.env 2>/dev/null || chmod 600 frontend/.env
    fi
    
    # Create and secure upload directories
    print_status "Setting up upload directories..."
    sudo mkdir -p uploads logs 2>/dev/null || mkdir -p uploads logs
    sudo chown -R "$WEB_SERVER_USER:$WEB_SERVER_GROUP" uploads logs 2>/dev/null || chown -R "$WEB_SERVER_USER:$WEB_SERVER_GROUP" uploads logs
    sudo chmod 755 uploads logs 2>/dev/null || chmod 755 uploads logs
    
    # Secure node_modules (if they exist)
    if [[ -d "node_modules" ]]; then
        print_status "Securing node_modules directory..."
        sudo chown -R "$WEB_SERVER_USER:$WEB_SERVER_GROUP" node_modules 2>/dev/null || chown -R "$WEB_SERVER_USER:$WEB_SERVER_GROUP" node_modules
        sudo find node_modules -type d -exec chmod 755 {} \; 2>/dev/null || find node_modules -type d -exec chmod 755 {} \;
        sudo find node_modules -type f -exec chmod 644 {} \; 2>/dev/null || find node_modules -type f -exec chmod 644 {} \;
    fi
    
    if [[ -d "frontend/node_modules" ]]; then
        print_status "Securing frontend node_modules directory..."
        sudo chown -R "$WEB_SERVER_USER:$WEB_SERVER_GROUP" frontend/node_modules 2>/dev/null || chown -R "$WEB_SERVER_USER:$WEB_SERVER_GROUP" frontend/node_modules
        sudo find frontend/node_modules -type d -exec chmod 755 {} \; 2>/dev/null || find frontend/node_modules -type d -exec chmod 755 {} \;
        sudo find frontend/node_modules -type f -exec chmod 644 {} \; 2>/dev/null || find frontend/node_modules -type f -exec chmod 644 {} \;
    fi
    
    print_success "Permissions setup complete!"
    print_status "Application files owned by: $WEB_SERVER_USER:$WEB_SERVER_GROUP"
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
        
        # Check npm version
        NPM_VERSION=$(npm -v | cut -d'.' -f1)
        if [ "$NPM_VERSION" -lt 9 ]; then
            print_warning "npm version is older than 9. Current version: $(npm -v)"
            print_status "Updating npm to latest version..."
            npm install -g npm@latest
            print_success "npm updated to $(npm -v)"
        else
            print_success "npm $(npm -v) is installed"
        fi
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
    
    # Fix npm permissions before installing dependencies
    fix_npm_permissions
    
    # Install dependencies
    print_status "Installing backend dependencies..."
    if ! npm install; then
        print_error "npm install failed. This might be due to permission issues or missing dependencies."
        
        print_status "Troubleshooting steps:"
        print_status "  1. Check file ownership: ls -la package-lock.json"
        print_status "  2. Fix ownership: sudo chown \$USER:\$USER package-lock.json"
        print_status "  3. Fix npm permissions: sudo chown -R \$USER:\$(id -gn \$USER) /usr/local/lib/node_modules"
        print_status "  4. Try using npm ci instead: npm ci"
        
        if [[ "$OS" == "ubuntu" ]]; then
            print_status "Ubuntu-specific solutions:"
            print_status "  5. Use NVM: curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash"
            print_status "  6. Configure npm prefix: npm config set prefix ~/.npm-global"
            print_status "     source ~/.bashrc"
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
    cp env.example frontend/.env
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
       # cp env.example .env
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
    # Use the global variables set earlier in the script
    # DB_USER, DB_PASSWORD, DB_HOST, DB_PORT, DB_NAME are already set from parse_args
    DB_PASS=$DB_PASSWORD
    
    print_status "Database: $DB_NAME on $DB_HOST:$DB_PORT"
    
    # Test database connection
    print_status "Testing database connection..."
    print_status "Connection details: $DB_USER@$DB_HOST:$DB_PORT/$DB_NAME"
    
    # Check if psql is available
    if ! command -v psql &> /dev/null; then
        print_error "psql command not found. Please install PostgreSQL client tools."
        exit 1
    fi
    
    # Test database connection with better error reporting
    if PGPASSWORD="$DB_PASS" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c '\q' 2>/dev/null; then
        print_success "Database connection successful"
    else
        print_error "Cannot connect to database. Please check your credentials and ensure PostgreSQL is running."
        print_status "Troubleshooting steps:"
        print_status "  1. Check if PostgreSQL is running: sudo systemctl status postgresql"
        print_status "  2. Verify database exists: sudo -u postgres psql -c '\\l'"
        print_status "  3. Check user permissions: sudo -u postgres psql -c '\\du'"
        print_status "  4. Test connection manually: psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME"
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

# Install all prerequisites automatically
install_prerequisites() {
    if [[ "$OS" == "ubuntu" ]]; then
        print_status "Installing all prerequisites for Ubuntu 24.04..."
        
        # Update package index
        print_status "Updating package index..."
        sudo apt update
        
        # Install essential build tools and dependencies
        print_status "Installing essential build tools..."
        sudo apt install -y \
            build-essential \
            curl \
            wget \
            git \
            python3 \
            python3-pip \
            python3-dev \
            libpq-dev \
            pkg-config \
            software-properties-common \
            apt-transport-https \
            ca-certificates \
            gnupg \
            lsb-release
        
    # Install Node.js 20 LTS via NodeSource
    print_status "Installing Node.js 20 LTS..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt install -y nodejs
    
    # Update npm to latest version
    print_status "Updating npm to latest version..."
    sudo npm install -g npm@latest
    
    # Fix npm permissions for Ubuntu 24.04
    print_status "Fixing npm permissions..."
    
    # Check if npm global directories exist and fix ownership if needed
    if [[ -d "/usr/local/lib/node_modules" ]]; then
        if [[ "$(stat -c %U /usr/local/lib/node_modules)" == "root" ]]; then
            print_status "Fixing npm global directory ownership..."
            sudo chown -R $USER:$(id -gn $USER) /usr/local/lib/node_modules
        else
            print_status "npm global directory already has correct ownership"
        fi
    else
        print_status "npm global directory /usr/local/lib/node_modules does not exist (likely using NVM or user installation)"
    fi
    
    # Fix other npm directories if they exist
    if [[ -d "/usr/local/bin" ]] && [[ "$(stat -c %U /usr/local/bin)" == "root" ]]; then
        print_status "Fixing /usr/local/bin ownership..."
        sudo chown -R $USER:$(id -gn $USER) /usr/local/bin
    fi
    
    if [[ -d "/usr/local/share" ]] && [[ "$(stat -c %U /usr/local/share)" == "root" ]]; then
        print_status "Fixing /usr/local/share ownership..."
        sudo chown -R $USER:$(id -gn $USER) /usr/local/share
    fi
    
    # Check npm cache permissions
    if [[ -d "$HOME/.npm" ]]; then
        print_status "Fixing npm cache permissions..."
        chown -R $USER:$(id -gn $USER) $HOME/.npm
    fi
    
    # Set npm prefix to user directory to avoid permission issues
    print_status "Configuring npm to use user directory..."
    mkdir -p ~/.npm-global
    npm config set prefix ~/.npm-global
    
    # Add to PATH if not already present
    if ! grep -q "~/.npm-global/bin" ~/.bashrc; then
        echo 'export PATH=~/.npm-global/bin:$PATH' >> ~/.bashrc
        print_success "Added npm global bin to PATH in ~/.bashrc"
    fi
    export PATH=~/.npm-global/bin:$PATH
    
    print_success "npm permissions fixed!"
        
    # Verify Node.js installation
        NODE_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
        if [ "$NODE_VERSION" -lt 18 ]; then
            print_error "Node.js installation failed or version too old. Current version: $(node -v)"
            exit 1
        fi
        print_success "Node.js $(node -v) installed successfully"
        
        # Install PostgreSQL
        print_status "Installing PostgreSQL..."
        sudo apt install -y postgresql postgresql-contrib
        
        # Start and enable PostgreSQL
        print_status "Starting PostgreSQL service..."
        sudo systemctl start postgresql
        sudo systemctl enable postgresql
        
        # Create database and user
        print_status "Setting up database..."
        sudo -u postgres psql -c "CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';" 2>/dev/null || true
        sudo -u postgres psql -c "CREATE DATABASE $DB_NAME OWNER $DB_USER;" 2>/dev/null || true
        sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;" 2>/dev/null || true
        
        # Install additional useful tools
        print_status "Installing additional tools..."
        sudo apt install -y \
            jq \
            htop \
            tree \
            unzip \
            zip \
            vim \
            nano
        
        print_success "All prerequisites installed successfully!"
        
    elif [[ "$OS" == "macos" ]]; then
        print_status "Installing prerequisites for macOS..."
        
        # Check if Homebrew is installed
        if ! command -v brew &> /dev/null; then
            print_status "Installing Homebrew..."
            /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
        fi
        
            # Install Node.js
            print_status "Installing Node.js..."
            brew install node
            
            # Update npm to latest version
            print_status "Updating npm to latest version..."
            npm install -g npm@latest
        
        # Install PostgreSQL
        print_status "Installing PostgreSQL..."
        brew install postgresql
        brew services start postgresql
        
        # Create database and user
        print_status "Setting up database..."
        createdb $DB_NAME 2>/dev/null || true
        
        print_success "All prerequisites installed successfully!"
        
    else
        print_error "Automatic prerequisite installation not supported for this operating system."
        print_status "Please install the following manually:"
        print_status "  - Node.js 18+"
        print_status "  - PostgreSQL 12+"
        print_status "  - Git"
        print_status "  - Build tools (build-essential on Ubuntu/Debian)"
        exit 1
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

# Parse command line arguments
parse_args() {
    AUTO_INSTALL_PREREQS=false
    AUTO_SETUP_DB=false
    AUTO_CLEANUP_PHP=false
    AUTO_CREATE_INSTALLER=false
    AUTO_START_SERVERS=false
    NON_INTERACTIVE=false
    SKIP_ENV_CONFIG=false
    
    # Default environment values
    DB_HOST="localhost"
    DB_PORT="5432"
    DB_NAME="event_manager"
    DB_USER="event_manager"
    DB_PASSWORD="password"
    JWT_SECRET=""
    SESSION_SECRET=""
    APP_ENV="development"
    APP_URL="http://localhost:3001"
    SMTP_HOST=""
    SMTP_PORT="587"
    SMTP_USER=""
    SMTP_PASS=""
    SMTP_FROM="noreply@eventmanager.com"
    
    # Server deployment options
    AUTO_SETUP_PERMISSIONS=false
    WEB_SERVER_USER=""
    SKIP_WEB_SERVER_PERMISSIONS=false
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --auto-install-prereqs)
                AUTO_INSTALL_PREREQS=true
                shift
                ;;
            --auto-setup-db)
                AUTO_SETUP_DB=true
                shift
                ;;
            --auto-cleanup-php)
                AUTO_CLEANUP_PHP=true
                shift
                ;;
            --auto-create-installer)
                AUTO_CREATE_INSTALLER=true
                shift
                ;;
            --auto-start-servers)
                AUTO_START_SERVERS=true
                shift
                ;;
            --non-interactive)
                NON_INTERACTIVE=true
                AUTO_INSTALL_PREREQS=true
                AUTO_SETUP_DB=true
                AUTO_CLEANUP_PHP=true
                AUTO_CREATE_INSTALLER=false
                AUTO_START_SERVERS=false
                SKIP_ENV_CONFIG=true
                shift
                ;;
            --skip-env-config)
                SKIP_ENV_CONFIG=true
                shift
                ;;
            --auto-setup-permissions)
                AUTO_SETUP_PERMISSIONS=true
                shift
                ;;
            --web-server-user=*)
                WEB_SERVER_USER="${1#*=}"
                shift
                ;;
            --skip-web-server-permissions)
                SKIP_WEB_SERVER_PERMISSIONS=true
                shift
                ;;
            --db-host=*)
                DB_HOST="${1#*=}"
                shift
                ;;
            --db-port=*)
                DB_PORT="${1#*=}"
                shift
                ;;
            --db-name=*)
                DB_NAME="${1#*=}"
                shift
                ;;
            --db-user=*)
                DB_USER="${1#*=}"
                shift
                ;;
            --db-password=*)
                DB_PASSWORD="${1#*=}"
                shift
                ;;
            --jwt-secret=*)
                JWT_SECRET="${1#*=}"
                shift
                ;;
            --session-secret=*)
                SESSION_SECRET="${1#*=}"
                shift
                ;;
            --app-env=*)
                APP_ENV="${1#*=}"
                shift
                ;;
            --app-url=*)
                APP_URL="${1#*=}"
                shift
                ;;
            --smtp-host=*)
                SMTP_HOST="${1#*=}"
                shift
                ;;
            --smtp-port=*)
                SMTP_PORT="${1#*=}"
                shift
                ;;
            --smtp-user=*)
                SMTP_USER="${1#*=}"
                shift
                ;;
            --smtp-pass=*)
                SMTP_PASS="${1#*=}"
                shift
                ;;
            --smtp-from=*)
                SMTP_FROM="${1#*=}"
                shift
                ;;
            --help)
                show_help
                exit 0
                ;;
            *)
                print_error "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

# Show help information
show_help() {
    echo "Event Manager Complete Installation Script"
    echo "=========================================="
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Installation Options:"
    echo "  --auto-install-prereqs    Automatically install all prerequisites"
    echo "  --auto-setup-db           Automatically setup database (migrate + seed)"
    echo "  --auto-cleanup-php        Automatically remove old PHP files"
    echo "  --auto-create-installer   Automatically create minimal installer"
    echo "  --auto-start-servers      Automatically start development servers"
    echo "  --non-interactive         Run in non-interactive mode (auto-install everything)"
    echo "  --skip-env-config         Skip environment variable configuration"
    echo ""
    echo "Server Deployment Options:"
    echo "  --auto-setup-permissions      Automatically setup proper permissions for web server"
    echo "  --web-server-user=USER       Specify web server user (www-data, apache, etc.)"
    echo "  --skip-web-server-permissions Skip web server permissions (keep current user ownership)"
    echo ""
    echo "Database Configuration:"
    echo "  --db-host=HOST           Database host (default: localhost)"
    echo "  --db-port=PORT           Database port (default: 5432)"
    echo "  --db-name=NAME           Database name (default: event_manager)"
    echo "  --db-user=USER           Database user (default: event_manager)"
    echo "  --db-password=PASS       Database password (default: password)"
    echo ""
    echo "Application Configuration:"
    echo "  --jwt-secret=SECRET       JWT secret key (required for production)"
    echo "  --session-secret=SECRET  Session secret key (required for production)"
    echo "  --app-env=ENV            Application environment (development/production)"
    echo "  --app-url=URL            Application URL (default: http://localhost:3001)"
    echo ""
    echo "Email Configuration:"
    echo "  --smtp-host=HOST         SMTP server host"
    echo "  --smtp-port=PORT         SMTP server port (default: 587)"
    echo "  --smtp-user=USER         SMTP username"
    echo "  --smtp-pass=PASS         SMTP password"
    echo "  --smtp-from=EMAIL        From email address"
    echo ""
    echo "General Options:"
    echo "  --help                   Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                       # Interactive mode (prompts for each step)"
    echo "  $0 --non-interactive     # Fully automated installation"
    echo "  $0 --auto-install-prereqs --auto-setup-db  # Partial automation"
    echo "  $0 --auto-setup-permissions --web-server-user=www-data  # Server deployment"
    echo "  $0 --non-interactive --auto-setup-permissions  # Full server automation"
    echo "  $0 --skip-web-server-permissions  # Development setup (no web server permissions)"
    echo "  $0 --db-host=db.example.com --db-password=secret123  # Custom database"
    echo "  $0 --jwt-secret=my-secret --app-env=production  # Production setup"
    echo ""
}

# Main setup function
main() {
    echo "🎯 Event Manager Contest System Setup"
    echo "====================================="
    
    # Parse command line arguments
    parse_args "$@"
    
    # Detect operating system first
    detect_os
    
    # Handle prerequisites installation
    if [[ "$NON_INTERACTIVE" == "true" || "$AUTO_INSTALL_PREREQS" == "true" ]]; then
        print_status "Installing prerequisites automatically..."
        install_prerequisites
    else
        echo ""
        print_status "This script can automatically install all prerequisites."
        read -p "Do you want to install prerequisites automatically? (y/n): " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            install_prerequisites
        else
            print_warning "Skipping automatic prerequisite installation."
            print_status "Make sure you have the following installed:"
            print_status "  - Node.js 18+"
            print_status "  - PostgreSQL 12+"
            print_status "  - Git"
            print_status "  - Build tools"
            echo ""
        fi
    fi
    
    # Check prerequisites (will skip if already installed)
    check_node
    check_postgres
    
    # Configure environment variables
    configure_environment
    
    # Setup applications
    setup_backend
    setup_frontend
    
    # Handle permissions setup for server deployment (AFTER npm install)
    if [[ "$SKIP_WEB_SERVER_PERMISSIONS" == "true" ]]; then
        print_status "Skipping web server permissions setup (development mode)"
    elif [[ "$AUTO_SETUP_PERMISSIONS" == "true" || "$NON_INTERACTIVE" == "true" ]]; then
        print_status "Setting up permissions automatically..."
        setup_permissions
    else
        echo ""
        read -p "Do you want to setup proper permissions for server deployment? (y/n): " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            setup_permissions
        else
            print_warning "Skipping permissions setup. You may need to configure permissions manually."
        fi
    fi
    
    # Handle database setup
    if [[ "$NON_INTERACTIVE" == "true" || "$AUTO_SETUP_DB" == "true" ]]; then
        print_status "Setting up database automatically..."
        setup_database
    else
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
    fi
    
    # Handle PHP cleanup
    if [[ "$NON_INTERACTIVE" == "true" || "$AUTO_CLEANUP_PHP" == "true" ]]; then
        print_status "Cleaning up PHP files automatically..."
        cleanup_php
    else
        echo ""
        read -p "Do you want to remove the old PHP files? (y/n): " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            cleanup_php
        else
            print_warning "PHP files kept. You can remove them manually later."
        fi
    fi
    
    # Handle minimal installer creation
    if [[ "$AUTO_CREATE_INSTALLER" == "true" ]]; then
        print_status "Creating minimal installer automatically..."
        create_minimal_installer
    else
        echo ""
        read -p "Do you want to create a minimal installer script for distribution? (y/n): " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            create_minimal_installer
        fi
    fi
    
    # Handle development servers
    if [[ "$AUTO_START_SERVERS" == "true" ]]; then
        print_status "Starting development servers automatically..."
        start_dev
    else
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
            print_status "Application URLs:"
            print_status "  Frontend: http://localhost:3001"
            print_status "  Backend API: http://localhost:3000"
            print_status "  Default login: admin@eventmanager.com / admin123"
            echo ""
            
            # OS-specific additional instructions
            if [[ "$OS" == "ubuntu" ]]; then
                print_status "Ubuntu-specific notes:"
                print_status "  - PostgreSQL service: sudo systemctl start postgresql"
                print_status "  - Check service status: sudo systemctl status postgresql"
                print_status "  - Connect to database: sudo -u postgres psql"
                print_status "  - Database credentials: event_manager / password"
            elif [[ "$OS" == "macos" ]]; then
                print_status "macOS-specific notes:"
                print_status "  - PostgreSQL service: brew services start postgresql"
                print_status "  - Check service status: brew services list | grep postgres"
                print_status "  - Connect to database: psql postgres"
            fi
            
            print_status ""
            print_status "Default login credentials:"
            print_status "  Email: admin@eventmanager.com"
            print_status "  Password: admin123"
            
            # Show Ubuntu-specific troubleshooting tips
            show_ubuntu_troubleshooting
        fi
    fi
}

# Generate secure secrets
generate_secret() {
    if command -v openssl &> /dev/null; then
        openssl rand -base64 32
    elif command -v head &> /dev/null && command -v /dev/urandom &> /dev/null; then
        head -c 32 /dev/urandom | base64
    else
        # Fallback to a simple random string
        echo "$(date +%s)$RANDOM" | sha256sum | cut -c1-32
    fi
}

# Configure environment variables
configure_environment() {
    if [[ "$SKIP_ENV_CONFIG" == "true" ]]; then
        print_status "Skipping environment configuration..."
        return
    fi
    
    print_status "Configuring environment variables..."
    
    # Generate secrets if not provided
    if [[ -z "$JWT_SECRET" ]]; then
        JWT_SECRET=$(generate_secret)
        print_status "Generated JWT secret: ${JWT_SECRET:0:8}..."
    fi
    
    if [[ -z "$SESSION_SECRET" ]]; then
        SESSION_SECRET=$(generate_secret)
        print_status "Generated session secret: ${SESSION_SECRET:0:8}..."
    fi
    
    # Interactive mode - prompt for configuration
    if [[ "$NON_INTERACTIVE" != "true" ]]; then
        echo ""
        print_status "Environment Configuration"
        print_status "========================="
        
        # Database configuration
        echo ""
        print_status "Database Configuration:"
        read -p "Database host [$DB_HOST]: " input_db_host
        DB_HOST=${input_db_host:-$DB_HOST}
        
        read -p "Database port [$DB_PORT]: " input_db_port
        DB_PORT=${input_db_port:-$DB_PORT}
        
        read -p "Database name [$DB_NAME]: " input_db_name
        DB_NAME=${input_db_name:-$DB_NAME}
        
        read -p "Database user [$DB_USER]: " input_db_user
        DB_USER=${input_db_user:-$DB_USER}
        
        read -p "Database password [$DB_PASSWORD]: " input_db_password
        DB_PASSWORD=${input_db_password:-$DB_PASSWORD}
        
        # Application configuration
        echo ""
        print_status "Application Configuration:"
        read -p "Application environment (development/production) [$APP_ENV]: " input_app_env
        APP_ENV=${input_app_env:-$APP_ENV}
        
        read -p "Application URL [$APP_URL]: " input_app_url
        APP_URL=${input_app_url:-$APP_URL}
        
        # Email configuration (optional)
        echo ""
        print_status "Email Configuration (optional):"
        read -p "SMTP host (leave empty to skip): " input_smtp_host
        SMTP_HOST=${input_smtp_host:-$SMTP_HOST}
        
        if [[ -n "$SMTP_HOST" ]]; then
            read -p "SMTP port [$SMTP_PORT]: " input_smtp_port
            SMTP_PORT=${input_smtp_port:-$SMTP_PORT}
            
            read -p "SMTP username: " input_smtp_user
            SMTP_USER=${input_smtp_user:-$SMTP_USER}
            
            read -p "SMTP password: " input_smtp_pass
            SMTP_PASS=${input_smtp_pass:-$SMTP_PASS}
            
            read -p "From email address [$SMTP_FROM]: " input_smtp_from
            SMTP_FROM=${input_smtp_from:-$SMTP_FROM}
        fi
    fi
    
    # Create .env file
    print_status "Creating .env file..."
    cat > .env << EOF
# Environment Configuration
NODE_ENV=$APP_ENV
PORT=3000

# Database Configuration
DATABASE_URL="postgresql://$DB_USER:$DB_PASSWORD@$DB_HOST:$DB_PORT/$DB_NAME?schema=public"

# JWT Configuration
JWT_SECRET=$JWT_SECRET
JWT_EXPIRES_IN=24h

# Redis Configuration
REDIS_URL=redis://localhost:6379

# File Upload Configuration
UPLOAD_DIR=uploads
MAX_FILE_SIZE=10485760

# Security Configuration
BCRYPT_ROUNDS=12
RATE_LIMIT_WINDOW_MS=900000
RATE_LIMIT_MAX_REQUESTS=100

# Logging Configuration
LOG_LEVEL=info
LOG_FILE=logs/event-manager.log

# Email Configuration
SMTP_HOST=$SMTP_HOST
SMTP_PORT=$SMTP_PORT
SMTP_USER=$SMTP_USER
SMTP_PASS=$SMTP_PASS
SMTP_FROM=$SMTP_FROM

# Session Configuration
SESSION_SECRET=$SESSION_SECRET
SESSION_TIMEOUT=1800000
EOF

    # Create frontend .env file
    print_status "Creating frontend .env file..."
    cat > frontend/.env << EOF
# Environment Configuration for Frontend
VITE_API_URL=http://localhost:3000
VITE_APP_NAME=Event Manager
VITE_APP_VERSION=1.0.0
VITE_APP_URL=$APP_URL
EOF

    print_success "Environment configuration complete!"
    
    # Show summary
    echo ""
    print_status "Configuration Summary:"
    print_status "  Database: $DB_USER@$DB_HOST:$DB_PORT/$DB_NAME"
    print_status "  Environment: $APP_ENV"
    print_status "  Application URL: $APP_URL"
    if [[ -n "$SMTP_HOST" ]]; then
        print_status "  Email: $SMTP_USER@$SMTP_HOST:$SMTP_PORT"
    else
        print_status "  Email: Not configured"
    fi
    echo ""
}

# Create a minimal installation script for distribution
create_minimal_installer() {
    print_status "Creating minimal installer script..."
    
    cat > install.sh << 'EOF'
#!/bin/bash

# Event Manager Minimal Installer
# Downloads and runs the complete setup script

set -e

echo "🚀 Event Manager Minimal Installer"
echo "=================================="

# Check if curl is available
if ! command -v curl &> /dev/null; then
    echo "❌ curl is required but not installed. Please install curl first."
    exit 1
fi

# Download and run the complete setup script
echo "📥 Downloading complete setup script..."
curl -fsSL https://raw.githubusercontent.com/your-repo/event-manager/main/setup.sh -o setup.sh

echo "🔧 Making script executable..."
chmod +x setup.sh

echo "▶️  Running complete setup..."
./setup.sh

echo "✅ Installation complete!"
EOF

    chmod +x install.sh
    print_success "Minimal installer created: install.sh"
    print_status "You can distribute this minimal installer to users."
    print_status "It will download and run the complete setup script."
}

# Run main function
main "$@"
