#!/bin/bash
set -e

DB_NAME="event_manager"
DB_USER="event_manager"
DB_PASSWORD="dittibop"

echo "🔧 Fixing PostgreSQL Permissions for Migration"

# 1. Grant CREATEDB privilege
echo "1. Granting CREATEDB privilege to user '$DB_USER'..."
sudo -u postgres psql -c "ALTER USER $DB_USER WITH CREATEDB;"
echo "   ✅ CREATEDB privilege granted."

# 2. Grant additional privileges
echo "2. Granting additional privileges..."
sudo -u postgres psql -c "ALTER USER $DB_USER WITH SUPERUSER;" || echo "   ⚠️  SUPERUSER privilege already exists or denied"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;" || echo "   ⚠️  Database privileges already granted"

# 3. Test permissions
echo "3. Testing permissions..."
if PGPASSWORD=$DB_PASSWORD psql -U $DB_USER -d postgres -h localhost -c "SELECT 1;" &> /dev/null; then
    echo "   ✅ User can connect to postgres database"
else
    echo "   ❌ User cannot connect to postgres database"
    exit 1
fi

echo "🎉 PostgreSQL permissions fixed successfully!"
echo "You can now run the migration script."