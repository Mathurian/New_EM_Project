#!/bin/bash

echo "=== File Upload Directory Setup ==="

# The upload directory path
UPLOAD_DIR="/var/www/html/public/uploads/emcee-scripts"

echo "1. Checking upload directory: $UPLOAD_DIR"

# Check if directory exists
if [ ! -d "$UPLOAD_DIR" ]; then
    echo "   ❌ Directory does not exist"
    echo "   🔧 Creating directory..."
    
    # Create parent directories if they don't exist
    mkdir -p "$UPLOAD_DIR"
    
    if [ -d "$UPLOAD_DIR" ]; then
        echo "   ✅ Directory created successfully"
    else
        echo "   ❌ Failed to create directory"
        exit 1
    fi
else
    echo "   ✅ Directory exists"
fi

# Set proper permissions
echo "2. Setting directory permissions..."
chmod 755 "$UPLOAD_DIR"
echo "   ✅ Permissions set to 755"

# Set ownership to web server user (www-data on Ubuntu)
echo "3. Setting directory ownership..."
chown www-data:www-data "$UPLOAD_DIR"
echo "   ✅ Ownership set to www-data:www-data"

# Test file creation
echo "4. Testing file creation..."
TEST_FILE="$UPLOAD_DIR/test_$(date +%s).txt"
echo "Test content" > "$TEST_FILE"

if [ -f "$TEST_FILE" ]; then
    echo "   ✅ File creation test successful"
    rm "$TEST_FILE"
    echo "   ✅ Test file cleaned up"
else
    echo "   ❌ File creation test failed"
fi

# Check parent directories
echo "5. Checking parent directories..."
PARENT_DIRS=("/var/www/html" "/var/www/html/public" "/var/www/html/public/uploads")

for dir in "${PARENT_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        perms=$(stat -c "%a" "$dir")
        echo "   ✅ $dir exists (permissions: $perms)"
    else
        echo "   ❌ $dir does not exist"
    fi
done

echo ""
echo "=== Directory Setup Complete ==="
echo "The upload directory should now be properly configured."
echo "Try uploading a file again."
