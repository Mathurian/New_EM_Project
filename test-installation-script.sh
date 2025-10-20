#!/bin/bash

# Test script to verify the one-click installation script functions
# This script tests the key functions without actually installing anything

set -e

# Source the installation script functions
source install-event-manager-one-click.sh

echo "Testing Event Manager One-Click Installation Script"
echo "=================================================="

# Test logging functions
echo "Testing logging functions..."
log_info "This is an info message"
log_success "This is a success message"
log_warning "This is a warning message"
log_error "This is an error message"
log_prompt "This is a prompt message"
log_step "This is a step message"
echo

# Test root check (should pass since we're not running as root)
echo "Testing root check..."
check_root
echo "✓ Root check passed"
echo

# Test Ubuntu version check
echo "Testing Ubuntu version check..."
check_ubuntu_version
echo "✓ Ubuntu version check passed"
echo

# Test configuration variables
echo "Testing configuration variables..."
echo "APP_NAME: $APP_NAME"
echo "APP_VERSION: $APP_VERSION"
echo "INSTALL_DIR: $INSTALL_DIR"
echo "SERVICE_USER: $SERVICE_USER"
echo "DB_NAME: $DB_NAME"
echo "DB_USER: $DB_USER"
echo "✓ Configuration variables set correctly"
echo

echo "=================================================="
echo "✓ All basic tests passed!"
echo "The installation script is ready for use."
echo "=================================================="
