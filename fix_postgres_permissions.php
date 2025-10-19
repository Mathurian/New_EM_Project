#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * PostgreSQL Permissions Fix Script
 * 
 * This script helps fix PostgreSQL permissions for the migration
 */

echo "🔧 PostgreSQL Permissions Fix Script\n";
echo "====================================\n\n";

echo "Run these commands as the postgres superuser:\n\n";

echo "1. Connect to PostgreSQL as superuser:\n";
echo "   sudo -u postgres psql\n\n";

echo "2. Grant permissions to event_manager user:\n";
echo "   GRANT CREATE ON SCHEMA public TO event_manager;\n";
echo "   GRANT USAGE ON SCHEMA public TO event_manager;\n";
echo "   GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO event_manager;\n";
echo "   GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO event_manager;\n";
echo "   ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO event_manager;\n";
echo "   ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO event_manager;\n\n";

echo "3. Verify permissions:\n";
echo "   \\dp\n";
echo "   \\dn+\n\n";

echo "4. Test the connection:\n";
echo "   \\q\n";
echo "   psql -U event_manager -d event_manager -h localhost\n\n";

echo "5. If connection works, test table creation:\n";
echo "   CREATE TABLE test_permissions (id SERIAL PRIMARY KEY);\n";
echo "   DROP TABLE test_permissions;\n";
echo "   \\q\n\n";

echo "💡 Alternative: Create a new database with proper ownership\n";
echo "   sudo -u postgres psql\n";
echo "   DROP DATABASE IF EXISTS event_manager;\n";
echo "   CREATE DATABASE event_manager OWNER event_manager;\n";
echo "   GRANT ALL PRIVILEGES ON DATABASE event_manager TO event_manager;\n";
echo "   \\q\n\n";

echo "After fixing permissions, run: php migrate.php --test\n";
