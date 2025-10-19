#!/bin/bash

# PostgreSQL Permissions Fix Script
# Run this script to fix PostgreSQL permissions for migration

echo "🔧 PostgreSQL Permissions Fix Script"
echo "===================================="
echo ""

echo "This script will fix PostgreSQL permissions for the event_manager user."
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ This script must be run as root (use sudo)"
    exit 1
fi

echo "1. Stopping any existing PostgreSQL connections..."
sudo systemctl stop postgresql 2>/dev/null || true
sleep 2

echo "2. Starting PostgreSQL service..."
sudo systemctl start postgresql
sleep 2

echo "3. Checking PostgreSQL status..."
sudo systemctl status postgresql --no-pager -l

echo ""
echo "4. Fixing permissions..."

# Create a temporary SQL script
cat > /tmp/fix_permissions.sql << 'EOF'
-- Drop and recreate the database with proper ownership
DROP DATABASE IF EXISTS event_manager;
CREATE DATABASE event_manager OWNER event_manager;

-- Connect to the new database
\c event_manager

-- Grant all necessary permissions
GRANT CREATE ON SCHEMA public TO event_manager;
GRANT USAGE ON SCHEMA public TO event_manager;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO event_manager;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO event_manager;
GRANT ALL PRIVILEGES ON ALL FUNCTIONS IN SCHEMA public TO event_manager;

-- Set default privileges for future objects
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO event_manager;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO event_manager;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON FUNCTIONS TO event_manager;

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Test permissions by creating a test table
CREATE TABLE test_permissions (
    id SERIAL PRIMARY KEY,
    test_col TEXT
);

-- Insert test data
INSERT INTO test_permissions (test_col) VALUES ('test');

-- Query test data
SELECT * FROM test_permissions;

-- Clean up test table
DROP TABLE test_permissions;

-- Show current permissions
\dp
\dn+

-- Exit
\q
EOF

echo "5. Executing permission fixes..."
sudo -u postgres psql < /tmp/fix_permissions.sql

echo ""
echo "6. Testing connection as event_manager user..."
sudo -u postgres psql -c "SELECT 'Connection test successful' as result;" -d event_manager -U event_manager

echo ""
echo "7. Testing table creation..."
sudo -u postgres psql -c "CREATE TABLE test_final (id SERIAL PRIMARY KEY); DROP TABLE test_final;" -d event_manager -U event_manager

echo ""
echo "8. Cleaning up temporary files..."
rm -f /tmp/fix_permissions.sql

echo ""
echo "✅ PostgreSQL permissions fix completed!"
echo ""
echo "💡 Now run: php migrate.php --test"
echo "   This should show successful schema creation."
