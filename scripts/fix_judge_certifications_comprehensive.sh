#!/bin/bash

# Comprehensive Judge Certifications Migration Fix
# This script handles the migration fix with proper error handling and logging

# Set script options
set -e  # Exit on any error
set -u  # Exit on undefined variables

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="$SCRIPT_DIR/migration_fix.log"
BACKUP_DIR="$SCRIPT_DIR/backup_$(date +%Y%m%d_%H%M%S)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

# Function to check prerequisites
check_prerequisites() {
    log "Checking prerequisites..."
    
    # Check if we're in the right directory
    if [ ! -f "app/lib/DB.php" ]; then
        error "This script must be run from the project root directory"
        error "Current directory: $(pwd)"
        error "Expected files: app/lib/DB.php"
        exit 1
    fi
    
    # Check if PHP is available
    if ! command -v php &> /dev/null; then
        error "PHP is not installed or not in PATH"
        exit 1
    fi
    
    # Check PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    log "PHP version: $PHP_VERSION"
    
    # Check if database directory exists and is writable
    if [ ! -d "app/db" ]; then
        warning "Database directory 'app/db' does not exist, will be created"
    else
        if [ ! -w "app/db" ]; then
            error "Database directory 'app/db' is not writable"
            error "Please fix permissions: chmod 755 app/db"
            exit 1
        fi
    fi
    
    success "Prerequisites check passed"
}

# Function to create backup
create_backup() {
    log "Creating backup of database..."
    
    if [ -f "app/db/contest.sqlite" ]; then
        mkdir -p "$BACKUP_DIR"
        cp "app/db/contest.sqlite" "$BACKUP_DIR/"
        success "Database backed up to: $BACKUP_DIR/contest.sqlite"
    else
        warning "No existing database found to backup"
    fi
}

# Function to stop web server (if possible)
stop_web_server() {
    log "Attempting to stop web server to prevent database locks..."
    
    # Try to stop Apache
    if command -v systemctl &> /dev/null; then
        if systemctl is-active --quiet apache2; then
            log "Stopping Apache..."
            sudo systemctl stop apache2
            success "Apache stopped"
        elif systemctl is-active --quiet httpd; then
            log "Stopping HTTPD..."
            sudo systemctl stop httpd
            success "HTTPD stopped"
        else
            warning "No active web server found via systemctl"
        fi
    elif command -v service &> /dev/null; then
        if service apache2 status &> /dev/null; then
            log "Stopping Apache..."
            sudo service apache2 stop
            success "Apache stopped"
        elif service httpd status &> /dev/null; then
            log "Stopping HTTPD..."
            sudo service httpd stop
            success "HTTPD stopped"
        else
            warning "No active web server found via service"
        fi
    else
        warning "Cannot determine web server status - proceeding anyway"
    fi
}

# Function to start web server
start_web_server() {
    log "Starting web server..."
    
    # Try to start Apache
    if command -v systemctl &> /dev/null; then
        if systemctl is-enabled --quiet apache2; then
            log "Starting Apache..."
            sudo systemctl start apache2
            success "Apache started"
        elif systemctl is-enabled --quiet httpd; then
            log "Starting HTTPD..."
            sudo systemctl start httpd
            success "HTTPD started"
        else
            warning "No web server service found to start"
        fi
    elif command -v service &> /dev/null; then
        if service apache2 status &> /dev/null; then
            log "Starting Apache..."
            sudo service apache2 start
            success "Apache started"
        elif service httpd status &> /dev/null; then
            log "Starting HTTPD..."
            sudo service httpd start
            success "HTTPD started"
        else
            warning "No web server service found to start"
        fi
    else
        warning "Cannot start web server - please start manually if needed"
    fi
}

# Function to run the migration
run_migration() {
    log "Running judge certifications migration fix..."
    
    if [ ! -f "fix_judge_certifications.php" ]; then
        error "Migration script 'fix_judge_certifications.php' not found"
        exit 1
    fi
    
    # Run the PHP migration script
    php fix_judge_certifications.php 2>&1 | tee -a "$LOG_FILE"
    
    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        success "Migration completed successfully"
    else
        error "Migration failed"
        exit 1
    fi
}

# Function to verify migration
verify_migration() {
    log "Verifying migration..."
    
    # Test database connection
    php -r "
        require_once 'app/lib/DB.php';
        use App\DB;
        try {
            \$pdo = DB::pdo();
            \$stmt = \$pdo->query('SELECT COUNT(*) FROM judge_certifications');
            \$count = \$stmt->fetchColumn();
            echo \"Database connection successful. Total certifications: \$count\n\";
        } catch (Exception \$e) {
            echo \"Database verification failed: \" . \$e->getMessage() . \"\n\";
            exit(1);
        }
    " 2>&1 | tee -a "$LOG_FILE"
    
    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        success "Database verification passed"
    else
        error "Database verification failed"
        exit 1
    fi
}

# Main execution
main() {
    echo "=============================================="
    echo "Judge Certifications Migration Fix"
    echo "=============================================="
    echo ""
    
    # Initialize log file
    echo "Migration started at $(date)" > "$LOG_FILE"
    
    # Run all steps
    check_prerequisites
    create_backup
    stop_web_server
    
    # Run migration with error handling
    if run_migration; then
        verify_migration
        start_web_server
        
        echo ""
        success "🎉 Migration completed successfully!"
        success "The application should now work normally."
        success "Backup created at: $BACKUP_DIR"
        success "Log file: $LOG_FILE"
    else
        error "Migration failed. Check the log file: $LOG_FILE"
        start_web_server
        exit 1
    fi
}

# Handle script interruption
trap 'error "Script interrupted"; start_web_server; exit 1' INT TERM

# Run main function
main "$@"
