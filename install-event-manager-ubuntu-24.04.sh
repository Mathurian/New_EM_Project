#!/bin/bash

# Event Manager - Comprehensive Installation Script for Ubuntu 24.04
# This script installs all dependencies and sets up the Event Manager application

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="Event Manager"
APP_VERSION="2.0.0"
INSTALL_DIR="/opt/event-manager"
SERVICE_USER="eventmanager"
DB_NAME="event_manager"
DB_USER="event_manager"
REDIS_PASSWORD="$(openssl rand -base64 32)"
SESSION_SECRET="$(openssl rand -base64 64)"

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        log_error "This script should not be run as root. Please run as a regular user with sudo privileges."
        exit 1
    fi
}

# Check Ubuntu version
check_ubuntu_version() {
    if ! lsb_release -d | grep -q "Ubuntu 24.04"; then
        log_warning "This script is designed for Ubuntu 24.04. Other versions may work but are not tested."
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

# Update system packages
update_system() {
    log_info "Updating system packages..."
    sudo apt update
    sudo apt upgrade -y
    log_success "System packages updated"
}

# Install system dependencies
install_system_dependencies() {
    log_info "Installing system dependencies..."
    
    # Essential build tools
    sudo apt install -y \
        build-essential \
        curl \
        wget \
        git \
        unzip \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        gnupg \
        lsb-release
    
    # Python and development tools
    sudo apt install -y \
        python3 \
        python3-pip \
        python3-dev \
        python3-venv
    
    # Image processing libraries
    sudo apt install -y \
        libjpeg-dev \
        libpng-dev \
        libwebp-dev \
        libtiff-dev \
        libgif-dev \
        libfreetype6-dev \
        libfontconfig1-dev
    
    # PostgreSQL
    sudo apt install -y \
        postgresql \
        postgresql-contrib \
        postgresql-client \
        libpq-dev
    
    # Redis
    sudo apt install -y \
        redis-server \
        redis-tools
    
    # Apache
    sudo apt install -y \
        apache2 \
        apache2-utils \
        libapache2-mod-proxy-html \
        libapache2-mod-proxy-http \
        libapache2-mod-proxy-wstunnel
    
    # Node.js (using NodeSource repository for LTS version)
    curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
    sudo apt install -y nodejs
    
    # No PM2 needed - using systemctl directly
    
    log_success "System dependencies installed"
}

# Create application user
create_app_user() {
    log_info "Creating application user..."
    
    if ! id "$SERVICE_USER" &>/dev/null; then
        sudo useradd -r -s /bin/false -d "$INSTALL_DIR" -m "$SERVICE_USER"
        log_success "Application user created: $SERVICE_USER"
    else
        log_info "Application user already exists: $SERVICE_USER"
    fi
}

# Setup PostgreSQL
setup_postgresql() {
    log_info "Setting up PostgreSQL..."
    
    # Start and enable PostgreSQL
    sudo systemctl start postgresql
    sudo systemctl enable postgresql
    
    # Create database and user
    sudo -u postgres psql << EOF
-- Create database
CREATE DATABASE $DB_NAME;

-- Create user
CREATE USER $DB_USER WITH PASSWORD '$REDIS_PASSWORD';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
GRANT ALL PRIVILEGES ON SCHEMA public TO $DB_USER;

-- Exit
\q
EOF
    
    log_success "PostgreSQL setup completed"
}

# Setup Redis
setup_redis() {
    log_info "Setting up Redis..."
    
    # Configure Redis
    sudo tee /etc/redis/redis.conf > /dev/null << EOF
# Redis configuration for Event Manager
bind 127.0.0.1
port 6379
timeout 0
tcp-keepalive 300
daemonize yes
supervised systemd
pidfile /var/run/redis/redis-server.pid
loglevel notice
logfile /var/log/redis/redis-server.log
databases 16
save 900 1
save 300 10
save 60 10000
stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir /var/lib/redis
requirepass $REDIS_PASSWORD
maxmemory 256mb
maxmemory-policy allkeys-lru
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec
no-appendfsync-on-rewrite no
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb
aof-load-truncated yes
aof-use-rdb-preamble yes
EOF
    
    # Start and enable Redis
    sudo systemctl restart redis-server
    sudo systemctl enable redis-server
    
    log_success "Redis setup completed"
}

# Install application
install_application() {
    log_info "Installing Event Manager application..."
    
    # Create installation directory
    sudo mkdir -p "$INSTALL_DIR"
    sudo chown "$SERVICE_USER:$SERVICE_USER" "$INSTALL_DIR"
    
    # Copy application files (assuming we're in the project directory)
    if [[ -d "event-manager-api" && -d "event-manager-frontend" ]]; then
        sudo cp -r event-manager-api "$INSTALL_DIR/"
        sudo cp -r event-manager-frontend "$INSTALL_DIR/"
        sudo chown -R "$SERVICE_USER:$SERVICE_USER" "$INSTALL_DIR"
    else
        log_error "Application files not found. Please run this script from the project root directory."
        exit 1
    fi
    
    log_success "Application files copied"
}

# Install Node.js dependencies
install_node_dependencies() {
    log_info "Installing Node.js dependencies..."
    
    # Backend dependencies
    cd "$INSTALL_DIR/event-manager-api"
    sudo -u "$SERVICE_USER" npm install --omit=dev
    
    # Frontend dependencies
    cd "$INSTALL_DIR/event-manager-frontend"
    sudo -u "$SERVICE_USER" npm install --omit=dev
    
    log_success "Node.js dependencies installed"
}

# Create environment configuration
create_environment_config() {
    log_info "Creating environment configuration..."
    
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/.env" > /dev/null << EOF
# Event Manager Environment Configuration
NODE_ENV=production
PORT=3000
HOST=0.0.0.0

# Database Configuration
DB_HOST=localhost
DB_PORT=5432
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASSWORD=$REDIS_PASSWORD

# Redis Configuration
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=$REDIS_PASSWORD
REDIS_DB=0

# Session Configuration
SESSION_SECRET=$SESSION_SECRET
SESSION_MAX_AGE=86400000

# Security Configuration
BCRYPT_ROUNDS=12
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW_MS=900000

# Application Configuration
APP_NAME=$APP_NAME
APP_VERSION=$APP_VERSION
APP_URL=http://localhost
TZ=UTC

# Features
FEATURE_REALTIME_SCORING=true
FEATURE_EMAIL_NOTIFICATIONS=false
FEATURE_FILE_UPLOADS=true
FEATURE_AUDIT_LOGGING=true
FEATURE_BACKUP_AUTOMATION=true
FEATURE_API_DOCS=true

# CORS Configuration
CORS_ORIGIN=http://localhost

# Apache Configuration
APACHE_ENABLED=true
APACHE_DOCUMENT_ROOT=$INSTALL_DIR/event-manager-frontend/dist
APACHE_CONFIG_PATH=/etc/apache2/sites-available/event-manager.conf
APACHE_SSL_ENABLED=false

# Logging Configuration
LOG_LEVEL=info
LOG_FILE=$INSTALL_DIR/logs/app.log
LOG_MAX_SIZE=10m
LOG_MAX_FILES=5
EOF
    
    # Create logs directory
    sudo mkdir -p "$INSTALL_DIR/logs"
    sudo chown "$SERVICE_USER:$SERVICE_USER" "$INSTALL_DIR/logs"
    
    log_success "Environment configuration created"
}

# Setup database
setup_database() {
    log_info "Setting up database..."
    
    cd "$INSTALL_DIR/event-manager-api"
    
    # Run migrations
    sudo -u "$SERVICE_USER" npm run db:migrate
    
    # Seed database
    sudo -u "$SERVICE_USER" npm run db:seed
    
    log_success "Database setup completed"
}

# Build frontend
build_frontend() {
    log_info "Building frontend..."
    
    cd "$INSTALL_DIR/event-manager-frontend"
    
    # Create production environment file
    sudo -u "$SERVICE_USER" tee .env > /dev/null << EOF
VITE_API_URL=/api
EOF
    
    # Build frontend
    sudo -u "$SERVICE_USER" npm run build
    
    log_success "Frontend built successfully"
}

# Configure Apache
configure_apache() {
    log_info "Configuring Apache..."
    
    # Enable required modules
    sudo a2enmod proxy
    sudo a2enmod proxy_http
    sudo a2enmod proxy_wstunnel
    sudo a2enmod rewrite
    sudo a2enmod headers
    sudo a2enmod ssl
    
    # Create Apache virtual host
    sudo tee /etc/apache2/sites-available/event-manager.conf > /dev/null << EOF
<VirtualHost *:80>
    ServerName localhost
    ServerAlias *
    DocumentRoot $INSTALL_DIR/event-manager-frontend/dist
    
    # Proxy API requests to Node.js backend
    ProxyPreserveHost On
    ProxyPass /api/ http://localhost:3000/api/
    ProxyPassReverse /api/ http://localhost:3000/api/
    
    # Proxy WebSocket connections
    ProxyPass /socket.io/ ws://localhost:3000/socket.io/
    ProxyPassReverse /socket.io/ ws://localhost:3000/socket.io/
    
    # Serve static files
    <Directory "$INSTALL_DIR/event-manager-frontend/dist">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Handle client-side routing
        RewriteEngine On
        RewriteBase /
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.html [L]
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    
    # CORS headers for API
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-CSRF-Token"
    Header always set Access-Control-Allow-Credentials "true"
    
    # Logging
    ErrorLog \${APACHE_LOG_DIR}/event-manager_error.log
    CustomLog \${APACHE_LOG_DIR}/event-manager_access.log combined
</VirtualHost>
EOF
    
    # Enable site and disable default
    sudo a2ensite event-manager
    sudo a2dissite 000-default
    
    # Test configuration
    sudo apache2ctl configtest
    
    # Restart Apache
    sudo systemctl restart apache2
    sudo systemctl enable apache2
    
    log_success "Apache configured successfully"
}

# Setup systemd service (replaces PM2)
setup_systemd_service() {
    log_info "Setting up systemd service..."
    
    # Create systemd service file
    sudo tee /etc/systemd/system/event-manager.service > /dev/null << EOF
[Unit]
Description=Event Manager API Server
After=network.target postgresql.service redis.service
Wants=postgresql.service redis.service

[Service]
Type=simple
User=$SERVICE_USER
Group=$SERVICE_USER
WorkingDirectory=$INSTALL_DIR/event-manager-api
ExecStart=/usr/bin/node src/server.js
ExecReload=/bin/kill -HUP \$MAINPID
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=event-manager

# Environment variables
Environment=NODE_ENV=production
Environment=PORT=3000
EnvironmentFile=$INSTALL_DIR/.env

# Security settings
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=$INSTALL_DIR
ProtectKernelTunables=true
ProtectKernelModules=true
ProtectControlGroups=true

# Resource limits
LimitNOFILE=65536
LimitNPROC=4096

[Install]
WantedBy=multi-user.target
EOF
    
    # Reload systemd and enable service
    sudo systemctl daemon-reload
    sudo systemctl enable event-manager
    
    log_success "Systemd service configured"
}

# Setup firewall
setup_firewall() {
    log_info "Setting up firewall..."
    
    # Enable UFW if not already enabled
    sudo ufw --force enable
    
    # Allow SSH
    sudo ufw allow ssh
    
    # Allow HTTP and HTTPS
    sudo ufw allow 80/tcp
    sudo ufw allow 443/tcp
    
    # Allow internal communication
    sudo ufw allow from 127.0.0.1 to any port 3000
    sudo ufw allow from 127.0.0.1 to any port 5432
    sudo ufw allow from 127.0.0.1 to any port 6379
    
    log_success "Firewall configured"
}

# Create systemd service (simplified - no PM2)
create_systemd_service() {
    log_info "Creating systemd service..."
    
    # Service file already created in setup_systemd_service
    # Just ensure it's enabled and ready
    sudo systemctl daemon-reload
    sudo systemctl enable event-manager
    
    log_success "Systemd service ready"
}

# Final setup and verification
final_setup() {
    log_info "Performing final setup and verification..."
    
    # Set proper permissions
    sudo chown -R "$SERVICE_USER:$SERVICE_USER" "$INSTALL_DIR"
    sudo chmod -R 755 "$INSTALL_DIR"
    
    # Create uploads directory
    sudo mkdir -p "$INSTALL_DIR/uploads"
    sudo chown "$SERVICE_USER:$SERVICE_USER" "$INSTALL_DIR/uploads"
    
    # Start services
    sudo systemctl start event-manager
    sudo systemctl start apache2
    
    # Wait for services to start
    sleep 10
    
    # Verify services are running
    if systemctl is-active --quiet event-manager; then
        log_success "Event Manager service is running"
    else
        log_error "Event Manager service failed to start"
        sudo systemctl status event-manager
        sudo journalctl -u event-manager --no-pager -l
    fi
    
    if systemctl is-active --quiet apache2; then
        log_success "Apache service is running"
    else
        log_error "Apache service failed to start"
        sudo systemctl status apache2
    fi
    
    # Test API health
    if curl -s http://localhost:3000/api/health > /dev/null; then
        log_success "API health check passed"
    else
        log_warning "API health check failed - service may still be starting"
    fi
    
    # Test frontend
    if curl -s http://localhost/ > /dev/null; then
        log_success "Frontend is accessible"
    else
        log_warning "Frontend may not be accessible yet"
    fi
}

# Display installation summary
display_summary() {
    log_success "Installation completed successfully!"
    echo
    echo "=========================================="
    echo "Event Manager Installation Summary"
    echo "=========================================="
    echo
    echo "Application Details:"
    echo "  Name: $APP_NAME"
    echo "  Version: $APP_VERSION"
    echo "  Installation Directory: $INSTALL_DIR"
    echo "  Service User: $SERVICE_USER"
    echo
    echo "Access Information:"
    echo "  Frontend URL: http://localhost"
    echo "  API URL: http://localhost/api"
    echo "  API Documentation: http://localhost/docs"
    echo "  Health Check: http://localhost/api/health"
    echo
    echo "Default Login Credentials:"
    echo "  Email: admin@eventmanager.com"
    echo "  Password: admin123"
    echo
    echo "Database Information:"
    echo "  Database: $DB_NAME"
    echo "  User: $DB_USER"
    echo "  Password: $REDIS_PASSWORD"
    echo
    echo "Redis Information:"
    echo "  Password: $REDIS_PASSWORD"
    echo
    echo "Service Management:"
    echo "  Start: sudo systemctl start event-manager"
    echo "  Stop: sudo systemctl stop event-manager"
    echo "  Restart: sudo systemctl restart event-manager"
    echo "  Status: sudo systemctl status event-manager"
    echo "  Logs: sudo journalctl -u event-manager -f"
    echo
    echo "Logs:"
    echo "  Application: $INSTALL_DIR/logs/"
    echo "  Apache: /var/log/apache2/"
    echo "  System: sudo journalctl -u event-manager"
    echo
    echo "Configuration Files:"
    echo "  Environment: $INSTALL_DIR/.env"
    echo "  Apache: /etc/apache2/sites-available/event-manager.conf"
    echo "  Systemd: /etc/systemd/system/event-manager.service"
    echo
    echo "Next Steps:"
    echo "  1. Access the application at http://localhost"
    echo "  2. Login with the default credentials"
    echo "  3. Change the default password"
    echo "  4. Configure SSL certificates if needed"
    echo "  5. Review and adjust configuration in $INSTALL_DIR/.env"
    echo
    echo "For support, check the logs or visit the documentation."
    echo "=========================================="
}

# Main installation function
main() {
    echo "=========================================="
    echo "Event Manager Installation Script"
    echo "Version: $APP_VERSION"
    echo "Target: Ubuntu 24.04"
    echo "=========================================="
    echo
    
    check_root
    check_ubuntu_version
    
    log_info "Starting installation process..."
    
    update_system
    install_system_dependencies
    create_app_user
    setup_postgresql
    setup_redis
    install_application
    install_node_dependencies
    create_environment_config
    setup_database
    build_frontend
    configure_apache
    setup_systemd_service
    setup_firewall
    create_systemd_service
    final_setup
    display_summary
    
    log_success "Installation completed successfully!"
}

# Run main function
main "$@"
