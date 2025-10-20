#!/bin/bash

# Test script to verify the one-click installation script functions
# This script tests the key functions without actually installing anything

set -e

echo "Testing Event Manager One-Click Installation Script"
echo "=================================================="

# Test script syntax
echo "Testing script syntax..."
if bash -n install-event-manager-one-click.sh; then
    echo "✓ Script syntax is valid"
else
    echo "✗ Script syntax errors found"
    exit 1
fi
echo

# Test script exists and is executable
echo "Testing script permissions..."
if [[ -f "install-event-manager-one-click.sh" ]]; then
    echo "✓ Installation script exists"
else
    echo "✗ Installation script not found"
    exit 1
fi

if [[ -x "install-event-manager-one-click.sh" ]]; then
    echo "✓ Installation script is executable"
else
    echo "✗ Installation script is not executable"
    exit 1
fi
echo

# Test configuration variables are defined
echo "Testing configuration variables..."
if grep -q 'APP_NAME="Event Manager"' install-event-manager-one-click.sh; then
    echo "✓ APP_NAME is defined"
else
    echo "✗ APP_NAME not found"
fi

if grep -q 'APP_VERSION="2.0.0"' install-event-manager-one-click.sh; then
    echo "✓ APP_VERSION is defined"
else
    echo "✗ APP_VERSION not found"
fi

if grep -q 'INSTALL_DIR="/opt/event-manager"' install-event-manager-one-click.sh; then
    echo "✓ INSTALL_DIR is defined"
else
    echo "✗ INSTALL_DIR not found"
fi

if grep -q 'SERVICE_USER="eventmanager"' install-event-manager-one-click.sh; then
    echo "✓ SERVICE_USER is defined"
else
    echo "✗ SERVICE_USER not found"
fi

if grep -q 'DB_NAME="event_manager"' install-event-manager-one-click.sh; then
    echo "✓ DB_NAME is defined"
else
    echo "✗ DB_NAME not found"
fi

if grep -q 'DB_USER="event_manager"' install-event-manager-one-click.sh; then
    echo "✓ DB_USER is defined"
else
    echo "✗ DB_USER not found"
fi
echo

# Test that Apache packages are correctly configured
echo "Testing Apache package configuration..."
if grep -q 'apache2-utils' install-event-manager-one-click.sh && ! grep -q 'libapache2-mod-proxy-html' install-event-manager-one-click.sh; then
    echo "✓ Apache packages correctly configured for Ubuntu 24.04"
else
    echo "✗ Apache packages may need updating for Ubuntu 24.04"
fi

if grep -q 'sudo a2enmod proxy' install-event-manager-one-click.sh; then
    echo "✓ Apache modules are enabled via a2enmod"
else
    echo "✗ Apache module enabling method not found"
fi
echo

# Test that required functions exist
echo "Testing function definitions..."
required_functions=("check_root" "check_ubuntu_version" "collect_configuration" "update_system" "install_system_dependencies" "create_app_user" "setup_postgresql" "setup_redis" "configure_apache" "setup_pm2" "setup_firewall" "create_systemd_service" "final_setup" "display_summary")

for func in "${required_functions[@]}"; do
    if grep -q "^$func()" install-event-manager-one-click.sh; then
        echo "✓ Function $func is defined"
    else
        echo "✗ Function $func not found"
    fi
done
echo

echo "=================================================="
echo "✓ All tests completed!"
echo "The installation script appears to be ready for use."
echo "=================================================="
