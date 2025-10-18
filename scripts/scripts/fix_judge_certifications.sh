#!/bin/bash

# Fix Judge Certifications Migration Script
# This script runs the PHP migration fix for judge certifications

echo "Starting Judge Certifications Migration Fix..."
echo "=============================================="

# Check if we're in the right directory
if [ ! -f "app/lib/DB.php" ]; then
    echo "Error: This script must be run from the project root directory"
    echo "Current directory: $(pwd)"
    echo "Expected files: app/lib/DB.php"
    exit 1
fi

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed or not in PATH"
    exit 1
fi

# Run the PHP migration script
echo "Running PHP migration script..."
echo ""

php fix_judge_certifications.php

# Check the exit code
if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Migration completed successfully!"
    echo "You can now access the application normally."
else
    echo ""
    echo "❌ Migration failed. Please check the error messages above."
    exit 1
fi
