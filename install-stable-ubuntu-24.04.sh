#!/bin/bash
# Event Manager Stable Installation Script for Ubuntu 24.04
# This script installs all system dependencies and prepares the environment

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

echo "🚀 Event Manager Stable Installation Script for Ubuntu 24.04"
echo "=========================================================="
echo ""
print_warning "This script will install:"
echo "  • Node.js 20.x (LTS)"
echo "  • PostgreSQL 15+"
echo "  • Redis 7+"
echo "  • Apache 2.4+"
echo "  • All required system dependencies"
echo "  • Event Manager application"
echo ""

# Confirmation
confirm "Are you sure you want to proceed with the installation?"

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
    lsb-release \
    unzip \
    zip \
    htop \
    nano \
    vim
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
    libtiff-dev \
    libavif-dev
print_success "Image processing libraries installed"

# Step 4: Install PostgreSQL
print_status "Installing PostgreSQL..."
sudo apt install -y postgresql postgresql-contrib postgresql-server-dev-all
sudo systemctl start postgresql
sudo systemctl enable postgresql
print_success "PostgreSQL installed and started"

# Step 5: Install Redis
print_status "Installing Redis..."
sudo apt install -y redis-server redis-tools libhiredis-dev
sudo systemctl start redis-server
sudo systemctl enable redis-server
print_success "Redis installed and started"

# Step 6: Install Apache
print_status "Installing Apache..."
sudo apt install -y apache2 apache2-utils
sudo systemctl start apache2
sudo systemctl enable apache2
print_success "Apache installed and started"

# Step 7: Install Node.js 20.x (LTS)
print_status "Installing Node.js 20.x (LTS)..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
print_success "Node.js 20.x installed"

# Step 8: Install PM2 globally
print_status "Installing PM2 process manager..."
sudo npm install -g pm2
print_success "PM2 installed"

# Step 9: Verify installations
print_status "Verifying installations..."
echo "Node.js version: $(node --version)"
echo "npm version: $(npm --version)"
echo "PostgreSQL version: $(sudo -u postgres psql -c 'SELECT version();' 2>/dev/null | head -3 || echo 'PostgreSQL installed')"
echo "Redis version: $(redis-server --version 2>/dev/null | head -1 || echo 'Redis installed')"
echo "Apache version: $(apache2 -v 2>/dev/null | head -1 || echo 'Apache installed')"
echo "PM2 version: $(pm2 --version 2>/dev/null || echo 'PM2 installed')"

# Step 10: Configure PostgreSQL
print_status "Configuring PostgreSQL..."
sudo -u postgres psql << EOF
CREATE DATABASE event_manager;
CREATE USER event_manager WITH PASSWORD 'event_manager_secure_password_2024';
GRANT ALL PRIVILEGES ON DATABASE event_manager TO event_manager;
ALTER USER event_manager CREATEDB;
\q
EOF
print_success "PostgreSQL configured"

# Step 11: Configure Redis
print_status "Configuring Redis..."
sudo sed -i 's/# requirepass foobared/requirepass event_manager_redis_password_2024/' /etc/redis/redis.conf
sudo systemctl restart redis-server
print_success "Redis configured"

# Step 12: Configure Apache
print_status "Configuring Apache..."
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod proxy_wstunnel
sudo systemctl restart apache2
print_success "Apache modules enabled and service restarted"

# Step 13: Create application directory
print_status "Creating application directory..."
sudo mkdir -p /opt/event-manager
sudo chown $USER:$USER /opt/event-manager
print_success "Application directory created"

# Step 14: Create Apache virtual host
print_status "Creating Apache virtual host..."
sudo tee /etc/apache2/sites-available/event-manager.conf > /dev/null << 'EOF'
<VirtualHost *:80>
    ServerName event-manager.local
    DocumentRoot /opt/event-manager/event-manager-frontend/dist
    
    # Proxy API requests to Node.js
    ProxyPreserveHost On
    ProxyPass /api/ http://localhost:3000/api/
    ProxyPassReverse /api/ http://localhost:3000/api/
    
    # Proxy WebSocket connections
    ProxyPass /socket.io/ ws://localhost:3000/socket.io/
    ProxyPassReverse /socket.io/ ws://localhost:3000/socket.io/
    
    # Serve static files
    <Directory /opt/event-manager/event-manager-frontend/dist>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Handle SPA routing
        RewriteEngine On
        RewriteBase /
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.html [L]
    </Directory>
    
    # Serve uploads
    Alias /uploads /opt/event-manager/uploads
    <Directory /opt/event-manager/uploads>
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "no-referrer-when-downgrade"
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/event-manager_error.log
    CustomLog ${APACHE_LOG_DIR}/event-manager_access.log combined
</VirtualHost>
EOF

sudo a2ensite event-manager
sudo systemctl reload apache2
print_success "Apache virtual host configured"

# Step 15: Create environment file
print_status "Creating environment configuration..."
cat > /opt/event-manager/.env << EOF
# Application Configuration
NODE_ENV=production
PORT=3000
HOST=0.0.0.0
APP_URL=http://event-manager.local
DEBUG=false

# Database Configuration
DB_HOST=localhost
DB_PORT=5432
DB_NAME=event_manager
DB_USER=event_manager
DB_PASSWORD=event_manager_secure_password_2024
DB_SSL=false

# Redis Configuration
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=event_manager_redis_password_2024
REDIS_DB=0

# Session Configuration
SESSION_SECRET=event_manager_super_secret_session_key_2024_change_in_production
SESSION_MAX_AGE=86400000
SESSION_SECURE=false

# Security Configuration
BCRYPT_ROUNDS=12
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW_MS=900000
MAX_FILE_SIZE=5242880

# Email Configuration (configure with your SMTP)
EMAIL_HOST=localhost
EMAIL_PORT=587
EMAIL_SECURE=false
EMAIL_USER=
EMAIL_PASS=
EMAIL_FROM=noreply@eventmanager.local

# Features
FEATURE_REALTIME_SCORING=true
FEATURE_EMAIL_NOTIFICATIONS=false
FEATURE_FILE_UPLOADS=true
FEATURE_AUDIT_LOGGING=true
FEATURE_API_DOCS=true

# CORS
CORS_ORIGIN=http://event-manager.local

# Apache Configuration
APACHE_ENABLED=true
APACHE_DOCUMENT_ROOT=/opt/event-manager/event-manager-frontend/dist
APACHE_CONFIG_PATH=/etc/apache2/sites-available/event-manager.conf
APACHE_SSL_ENABLED=false
EOF
print_success "Environment configuration created"

# Step 16: Create PM2 ecosystem file
print_status "Creating PM2 configuration..."
cat > /opt/event-manager/ecosystem.config.js << EOF
module.exports = {
  apps: [{
    name: 'event-manager-api',
    script: 'src/server.js',
    cwd: '/opt/event-manager/event-manager-api',
    instances: 'max',
    exec_mode: 'cluster',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    error_file: '/var/log/pm2/event-manager-api-error.log',
    out_file: '/var/log/pm2/event-manager-api-out.log',
    log_file: '/var/log/pm2/event-manager-api.log',
    time: true,
    max_memory_restart: '1G',
    node_args: '--max-old-space-size=1024'
  }]
}
EOF
print_success "PM2 configuration created"

# Step 17: Create log directories
print_status "Creating log directories..."
sudo mkdir -p /var/log/pm2
sudo chown $USER:$USER /var/log/pm2
mkdir -p /opt/event-manager/logs
mkdir -p /opt/event-manager/uploads
mkdir -p /opt/event-manager/backups
print_success "Log directories created"

# Step 18: Set up log rotation
print_status "Setting up log rotation..."
sudo tee /etc/logrotate.d/event-manager > /dev/null << EOF
/opt/event-manager/logs/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 644 $USER $USER
}
EOF
print_success "Log rotation configured"

# Step 19: Create backup script
print_status "Creating backup script..."
cat > /opt/event-manager/backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/opt/event-manager/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
pg_dump -h localhost -U event_manager event_manager > $BACKUP_DIR/database_$DATE.sql

# Application backup
tar -czf $BACKUP_DIR/application_$DATE.tar.gz /opt/event-manager --exclude=node_modules --exclude=backups

# Keep only last 7 days of backups
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
EOF
chmod +x /opt/event-manager/backup.sh
print_success "Backup script created"

# Step 20: Set up cron job for backups
print_status "Setting up automated backups..."
(crontab -l 2>/dev/null; echo "0 2 * * * /opt/event-manager/backup.sh") | crontab -
print_success "Automated backups configured"

# Step 21: Configure firewall
print_status "Configuring firewall..."
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw --force enable
print_success "Firewall configured"

# Step 22: Final verification
print_status "Final verification..."

# Check services
if systemctl is-active --quiet postgresql; then
    print_success "✅ PostgreSQL is running"
else
    print_error "❌ PostgreSQL is not running"
fi

if systemctl is-active --quiet redis-server; then
    print_success "✅ Redis is running"
else
    print_error "❌ Redis is not running"
fi

if systemctl is-active --quiet apache2; then
    print_success "✅ Apache is running"
else
    print_error "❌ Apache is not running"
fi

# Summary
echo ""
echo "📋 Installation Summary:"
echo "======================="
echo "✅ Node.js 20.x: Installed"
echo "✅ PostgreSQL: Installed and configured"
echo "✅ Redis: Installed and configured"
echo "✅ Apache: Installed and configured"
echo "✅ PM2: Installed"
echo "✅ System dependencies: Installed"
echo "✅ Application directory: Created"
echo "✅ Environment configuration: Created"
echo "✅ Apache virtual host: Configured"
echo "✅ PM2 configuration: Created"
echo "✅ Log rotation: Configured"
echo "✅ Backup system: Configured"
echo "✅ Firewall: Configured"
echo ""

print_success "🎉 Event Manager installation completed successfully!"
echo ""
print_status "📝 Next steps:"
echo "1. Clone your Event Manager repository to /opt/event-manager/"
echo "2. Run: cd /opt/event-manager/event-manager-api && npm install --omit=dev"
echo "3. Run: npm run db:migrate"
echo "4. Run: cd ../event-manager-frontend && npm install && npm run build"
echo "5. Run: pm2 start /opt/event-manager/ecosystem.config.js"
echo "6. Add '127.0.0.1 event-manager.local' to /etc/hosts"
echo "7. Visit: http://event-manager.local"
echo ""
print_status "🔧 Configuration files:"
echo "  • Environment: /opt/event-manager/.env"
echo "  • Apache config: /etc/apache2/sites-available/event-manager.conf"
echo "  • PM2 config: /opt/event-manager/ecosystem.config.js"
echo "  • Backup script: /opt/event-manager/backup.sh"
echo ""
print_status "🔑 Default credentials:"
echo "  • Database: event_manager / event_manager_secure_password_2024"
echo "  • Redis: event_manager_redis_password_2024"
echo "  • Session secret: event_manager_super_secret_session_key_2024_change_in_production"
echo ""
print_warning "⚠️  Remember to change all default passwords in production!"
echo ""
print_status "📚 For detailed setup instructions, see the README.md file"
echo ""
print_success "Installation completed successfully! 🚀"
