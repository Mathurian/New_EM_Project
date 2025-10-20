#!/bin/bash

# Event Manager - Comprehensive Uninstallation Script for Ubuntu 24.04
# This script removes all components of the Event Manager application

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="Event Manager"
INSTALL_DIR="/opt/event-manager"
SERVICE_USER="eventmanager"
DB_NAME="event_manager"
DB_USER="event_manager"
SERVICE_NAME="event-manager"

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

# Confirm uninstallation
confirm_uninstall() {
    echo "=========================================="
    echo "Event Manager Uninstallation Script"
    echo "=========================================="
    echo
    log_warning "This will completely remove Event Manager and all its data!"
    echo
    echo "The following will be removed:"
    echo "  - Event Manager application files"
    echo "  - Database and all data"
    echo "  - Redis data"
    echo "  - Apache configuration"
    echo "  - System services"
    echo "  - Application user"
    echo
    log_warning "This action cannot be undone!"
    echo
    
    read -p "Are you sure you want to continue? (yes/NO): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        log_info "Uninstallation cancelled."
        exit 0
    fi
    
    echo
    read -p "Do you want to keep the database data? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        KEEP_DATABASE=true
        log_info "Database data will be preserved"
    else
        KEEP_DATABASE=false
        log_info "Database data will be removed"
    fi
    
    echo
    read -p "Do you want to keep Redis data? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        KEEP_REDIS=true
        log_info "Redis data will be preserved"
    else
        KEEP_REDIS=false
        log_info "Redis data will be removed"
    fi
}

# Stop services
stop_services() {
    log_info "Stopping Event Manager services..."
    
    # Stop Event Manager service
    if systemctl is-active --quiet "$SERVICE_NAME"; then
        sudo systemctl stop "$SERVICE_NAME"
        log_success "Event Manager service stopped"
    else
        log_info "Event Manager service was not running"
    fi
    
    # Stop PM2 processes
    if command -v pm2 >/dev/null 2>&1; then
        sudo -u "$SERVICE_USER" pm2 stop all 2>/dev/null || true
        sudo -u "$SERVICE_USER" pm2 delete all 2>/dev/null || true
        log_success "PM2 processes stopped"
    fi
    
    # Stop Apache
    if systemctl is-active --quiet apache2; then
        sudo systemctl stop apache2
        log_success "Apache service stopped"
    fi
}

# Remove systemd service
remove_systemd_service() {
    log_info "Removing systemd service..."
    
    if systemctl is-enabled --quiet "$SERVICE_NAME"; then
        sudo systemctl disable "$SERVICE_NAME"
        log_success "Systemd service disabled"
    fi
    
    if [[ -f "/etc/systemd/system/$SERVICE_NAME.service" ]]; then
        sudo rm -f "/etc/systemd/system/$SERVICE_NAME.service"
        sudo systemctl daemon-reload
        log_success "Systemd service file removed"
    fi
}

# Remove Apache configuration
remove_apache_config() {
    log_info "Removing Apache configuration..."
    
    # Disable site
    if [[ -f "/etc/apache2/sites-enabled/event-manager.conf" ]]; then
        sudo a2dissite event-manager
        log_success "Apache site disabled"
    fi
    
    # Remove site configuration
    if [[ -f "/etc/apache2/sites-available/event-manager.conf" ]]; then
        sudo rm -f "/etc/apache2/sites-available/event-manager.conf"
        log_success "Apache site configuration removed"
    fi
    
    # Re-enable default site
    sudo a2ensite 000-default
    
    # Restart Apache
    sudo systemctl restart apache2
    log_success "Apache configuration cleaned up"
}

# Remove database
remove_database() {
    if [[ "$KEEP_DATABASE" == "true" ]]; then
        log_info "Preserving database data as requested"
        return
    fi
    
    log_info "Removing database..."
    
    # Drop database and user
    sudo -u postgres psql << EOF
-- Drop database
DROP DATABASE IF EXISTS $DB_NAME;

-- Drop user
DROP USER IF EXISTS $DB_USER;

-- Exit
\q
EOF
    
    log_success "Database removed"
}

# Remove Redis data
remove_redis_data() {
    if [[ "$KEEP_REDIS" == "true" ]]; then
        log_info "Preserving Redis data as requested"
        return
    fi
    
    log_info "Clearing Redis data..."
    
    # Stop Redis
    sudo systemctl stop redis-server
    
    # Remove Redis data files
    sudo rm -f /var/lib/redis/dump.rdb
    sudo rm -f /var/lib/redis/appendonly.aof
    
    # Start Redis
    sudo systemctl start redis-server
    
    log_success "Redis data cleared"
}

# Remove application files
remove_application_files() {
    log_info "Removing application files..."
    
    if [[ -d "$INSTALL_DIR" ]]; then
        sudo rm -rf "$INSTALL_DIR"
        log_success "Application files removed"
    else
        log_info "Application directory not found"
    fi
}

# Remove application user
remove_application_user() {
    log_info "Removing application user..."
    
    if id "$SERVICE_USER" &>/dev/null; then
        sudo userdel -r "$SERVICE_USER" 2>/dev/null || sudo userdel "$SERVICE_USER"
        log_success "Application user removed"
    else
        log_info "Application user not found"
    fi
}

# Remove Node.js packages (optional)
remove_node_packages() {
    log_info "Checking for Node.js packages to remove..."
    
    # Ask user if they want to remove Node.js packages
    echo
    read -p "Do you want to remove Node.js and npm packages? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        log_info "Removing Node.js packages..."
        
        # Remove PM2 globally
        if command -v pm2 >/dev/null 2>&1; then
            sudo npm uninstall -g pm2
            log_success "PM2 removed"
        fi
        
        # Remove Node.js
        sudo apt remove -y nodejs npm
        sudo apt autoremove -y
        log_success "Node.js packages removed"
    else
        log_info "Node.js packages preserved"
    fi
}

# Remove system packages (optional)
remove_system_packages() {
    log_info "Checking for system packages to remove..."
    
    # Ask user if they want to remove system packages
    echo
    read -p "Do you want to remove PostgreSQL, Redis, and Apache? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        log_info "Removing system packages..."
        
        # Stop services
        sudo systemctl stop postgresql redis-server apache2
        
        # Remove packages
        sudo apt remove -y \
            postgresql \
            postgresql-contrib \
            postgresql-client \
            libpq-dev \
            redis-server \
            redis-tools \
            apache2 \
            apache2-utils
        
        # Remove configuration files
        sudo rm -rf /etc/postgresql
        sudo rm -rf /var/lib/postgresql
        sudo rm -rf /etc/redis
        sudo rm -rf /var/lib/redis
        sudo rm -rf /etc/apache2
        sudo rm -rf /var/www
        
        # Remove user
        sudo userdel postgres 2>/dev/null || true
        
        sudo apt autoremove -y
        log_success "System packages removed"
    else
        log_info "System packages preserved"
    fi
}

# Clean up firewall rules
cleanup_firewall() {
    log_info "Cleaning up firewall rules..."
    
    # Remove Event Manager specific rules
    sudo ufw delete allow 80/tcp 2>/dev/null || true
    sudo ufw delete allow 443/tcp 2>/dev/null || true
    sudo ufw delete allow from 127.0.0.1 to any port 3000 2>/dev/null || true
    sudo ufw delete allow from 127.0.0.1 to any port 5432 2>/dev/null || true
    sudo ufw delete allow from 127.0.0.1 to any port 6379 2>/dev/null || true
    
    log_success "Firewall rules cleaned up"
}

# Clean up logs
cleanup_logs() {
    log_info "Cleaning up log files..."
    
    # Remove application logs
    sudo rm -rf /var/log/event-manager* 2>/dev/null || true
    
    # Clean Apache logs
    sudo rm -f /var/log/apache2/event-manager* 2>/dev/null || true
    
    log_success "Log files cleaned up"
}

# Final verification
final_verification() {
    log_info "Performing final verification..."
    
    # Check if services are stopped
    if systemctl is-active --quiet "$SERVICE_NAME"; then
        log_warning "Event Manager service is still running"
    else
        log_success "Event Manager service is stopped"
    fi
    
    if systemctl is-active --quiet apache2; then
        log_success "Apache service is running (preserved)"
    else
        log_success "Apache service is stopped"
    fi
    
    # Check if files are removed
    if [[ -d "$INSTALL_DIR" ]]; then
        log_warning "Installation directory still exists: $INSTALL_DIR"
    else
        log_success "Installation directory removed"
    fi
    
    # Check if user is removed
    if id "$SERVICE_USER" &>/dev/null; then
        log_warning "Application user still exists: $SERVICE_USER"
    else
        log_success "Application user removed"
    fi
}

# Display uninstallation summary
display_summary() {
    log_success "Uninstallation completed!"
    echo
    echo "=========================================="
    echo "Event Manager Uninstallation Summary"
    echo "=========================================="
    echo
    echo "Removed Components:"
    echo "  ✓ Event Manager application files"
    if [[ "$KEEP_DATABASE" == "false" ]]; then
        echo "  ✓ Database and all data"
    else
        echo "  ○ Database data preserved"
    fi
    if [[ "$KEEP_REDIS" == "false" ]]; then
        echo "  ✓ Redis data"
    else
        echo "  ○ Redis data preserved"
    fi
    echo "  ✓ Apache configuration"
    echo "  ✓ System services"
    echo "  ✓ Application user"
    echo "  ✓ Firewall rules"
    echo "  ✓ Log files"
    echo
    echo "Preserved Components:"
    if [[ "$KEEP_DATABASE" == "true" ]]; then
        echo "  ○ Database: $DB_NAME"
        echo "  ○ Database User: $DB_USER"
    fi
    if [[ "$KEEP_REDIS" == "true" ]]; then
        echo "  ○ Redis data"
    fi
    echo "  ○ System packages (PostgreSQL, Redis, Apache)"
    echo "  ○ Node.js and npm"
    echo
    echo "Next Steps:"
    echo "  1. If you preserved database data, you can restore it later"
    echo "  2. If you preserved Redis data, it will be available for other applications"
    echo "  3. System packages are still available for other applications"
    echo "  4. You can reinstall Event Manager at any time"
    echo
    echo "Thank you for using Event Manager!"
    echo "=========================================="
}

# Main uninstallation function
main() {
    check_root
    confirm_uninstall
    
    log_info "Starting uninstallation process..."
    
    stop_services
    remove_systemd_service
    remove_apache_config
    remove_database
    remove_redis_data
    remove_application_files
    remove_application_user
    remove_node_packages
    remove_system_packages
    cleanup_firewall
    cleanup_logs
    final_verification
    display_summary
    
    log_success "Uninstallation completed successfully!"
}

# Run main function
main "$@"
