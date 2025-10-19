# Event Manager: SQLite to PostgreSQL Migration

## 🚀 Quick Start Guide

This guide will help you migrate your Event Manager application from SQLite to PostgreSQL on a server that currently only has SQLite installed.

## 📋 Prerequisites

- **Server**: Linux (Ubuntu/Debian recommended)
- **PHP**: 8.0 or higher with PostgreSQL extension
- **Current Database**: SQLite (contest.sqlite)
- **Target Database**: PostgreSQL 12 or higher
- **Disk Space**: At least 2x your current database size for backups

## 🛠️ Installation Steps

### Step 1: Install PostgreSQL

#### For Ubuntu/Debian:
```bash
# Update package list
sudo apt update

# Install PostgreSQL and additional utilities
sudo apt install postgresql postgresql-contrib php-pgsql

# Start and enable PostgreSQL service
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

#### For CentOS/RHEL:
```bash
# Install PostgreSQL repository
sudo yum install -y https://download.postgresql.org/pub/repos/yum/reporpms/EL-$(rpm -E %rhel)-x86_64/pgdg-redhat-repo-latest.noarch.rpm

# Install PostgreSQL
sudo yum install -y postgresql14-server

# Initialize database
sudo /usr/pgsql-14/bin/postgresql-14-setup initdb

# Start and enable service
sudo systemctl enable postgresql-14
sudo systemctl start postgresql-14
```

### Step 2: Configure PostgreSQL

```bash
# Switch to postgres user
sudo -i -u postgres

# Access PostgreSQL prompt
psql

# Create database and user
CREATE USER event_manager WITH PASSWORD 'secure_password_here';
CREATE DATABASE event_manager;
GRANT ALL PRIVILEGES ON DATABASE event_manager TO event_manager;

# Exit PostgreSQL
\q
exit
```

### Step 3: Configure Remote Access (Optional)

If you need remote access to PostgreSQL:

```bash
# Edit PostgreSQL configuration
sudo nano /etc/postgresql/14/main/postgresql.conf

# Uncomment and set:
listen_addresses = '*'

# Edit authentication configuration
sudo nano /etc/postgresql/14/main/pg_hba.conf

# Add this line for remote access:
host    all             all             0.0.0.0/0               md5

# Restart PostgreSQL
sudo systemctl restart postgresql
```

### Step 4: Install PHP PostgreSQL Extension

```bash
# Install PHP PostgreSQL extension
sudo apt install php-pgsql

# Restart web server
sudo systemctl restart apache2
# OR
sudo systemctl restart nginx
```

## 🔄 Migration Process

### Step 1: Test Migration (Safe - No Changes)

```bash
# Navigate to your project directory
cd /path/to/your/event-manager

# Test the migration process
php migrate.php --test
```

This will:
- ✅ Test database connections
- ✅ Validate schema migration
- ✅ Test data type conversions
- ✅ Check data integrity
- ✅ Verify application compatibility

### Step 2: Create Migration Configuration

```bash
# Create configuration file
php migrate.php --create-config
```

Edit `migration_config.php`:

```php
<?php
return [
    'source' => [
        'type' => 'sqlite',
        'path' => __DIR__ . '/app/db/contest.sqlite'
    ],
    'target' => [
        'type' => 'postgresql',
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'event_manager',
        'username' => 'event_manager',
        'password' => 'your_secure_password_here'
    ],
    'migration' => [
        'batch_size' => 1000,
        'backup_before_migration' => true,
        'validate_after_migration' => true,
        'create_rollback_script' => true
    ]
];
```

### Step 3: Perform Migration

```bash
# Run the actual migration
php migrate.php --migrate
```

**⚠️ WARNING**: This will migrate your database. A backup will be created automatically.

The migration process will:
1. 🔒 Create backup of SQLite database
2. 🏗️ Create PostgreSQL schema
3. 📊 Migrate all data with type conversions
4. ✅ Validate data integrity
5. 📝 Create rollback scripts

### Step 4: Update Application Configuration

Update your production configuration:

```bash
# Edit production config
nano config/production.php
```

Set database type to PostgreSQL:

```php
return [
    'database' => [
        'type' => 'postgresql',
        'host' => 'localhost',
        'port' => '5432',
        'name' => 'event_manager',
        'user' => 'event_manager',
        'password' => 'your_secure_password_here',
    ],
    // ... rest of config
];
```

### Step 5: Test Application

```bash
# Run comprehensive tests
php test_migration.php
```

Test your application thoroughly:
- ✅ User login/logout
- ✅ Data entry and retrieval
- ✅ Report generation
- ✅ Email functionality
- ✅ All user workflows

## 🔄 Rollback Procedures

### If Migration Fails

The migration system will automatically:
- 🛑 Stop the migration process
- 💾 Preserve your original SQLite database
- 🧹 Clean up any partial PostgreSQL data
- 📋 Provide detailed error logs

### Manual Rollback After Successful Migration

```bash
# Rollback to SQLite
php migrate.php --rollback
```

This will:
- 🔄 Switch application back to SQLite
- 🧹 Clear PostgreSQL-specific caches
- ⚙️ Restore original configuration

## 📊 Monitoring & Status

### Check Migration Status

```bash
# View current status
php migrate.php --status
```

### View Configuration

```bash
# Show current configuration
php migrate.php --config
```

## 🚨 Troubleshooting

### Common Issues

#### 1. Connection Errors
```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Check if PostgreSQL is listening
sudo netstat -tlnp | grep 5432
```

#### 2. Permission Errors
```bash
# Check PostgreSQL user permissions
sudo -u postgres psql -c "\du"

# Grant necessary permissions
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE event_manager TO event_manager;"
```

#### 3. PHP Extension Missing
```bash
# Check if PHP PostgreSQL extension is loaded
php -m | grep pgsql

# If not found, install it
sudo apt install php-pgsql
sudo systemctl restart apache2
```

#### 4. Database Path Issues
```bash
# Check if SQLite database exists
ls -la app/db/contest.sqlite

# Check permissions
ls -la app/db/
```

### Debug Mode

Enable debug mode for troubleshooting:

```bash
# Edit development config
nano config/development.php
```

```php
return [
    'app' => [
        'debug' => true,
    ],
    'logging' => [
        'channels' => [
            'file' => [
                'level' => 'debug',
            ],
        ],
    ],
];
```

## 📁 File Structure

After migration, you'll have:

```
your-project/
├── migrate.php                 # Migration CLI tool
├── test_migration.php         # Test suite
├── migration_config.php       # Migration configuration
├── MIGRATION_GUIDE.md         # Detailed migration guide
├── backups/                   # Backup files
│   ├── migration_backup_*.sqlite
│   └── rollback_*.php
├── config/                    # Configuration files
│   ├── app.php
│   ├── development.php
│   └── production.php
└── app/lib/                   # Migration system
    ├── DatabaseInterface.php
    ├── SchemaMigrator.php
    ├── DataMigrator.php
    ├── MigrationController.php
    └── Config.php
```

## 🎯 Migration Benefits

After successful migration, you'll gain:

- 🚀 **Better Performance**: PostgreSQL handles concurrent users better
- 🔒 **Data Integrity**: Stronger data type enforcement and constraints
- 📈 **Scalability**: Better support for large datasets
- 🛠️ **Advanced Features**: JSON support, full-text search, etc.
- 🔄 **Backup & Recovery**: Better backup and recovery options

## 📞 Support

### Getting Help

1. **Check Logs**: Review application and migration logs
2. **Run Tests**: Use `php test_migration.php` to identify issues
3. **Documentation**: Refer to `MIGRATION_GUIDE.md` for detailed information
4. **Rollback**: Use `php migrate.php --rollback` if needed

### Emergency Procedures

If critical issues occur:

1. **Immediate Rollback**:
   ```bash
   php migrate.php --rollback
   ```

2. **Restore from Backup**:
   ```bash
   cp backups/migration_backup_*.sqlite app/db/contest.sqlite
   ```

## ✅ Success Checklist

- [ ] PostgreSQL installed and configured
- [ ] PHP PostgreSQL extension installed
- [ ] Migration test passed (`php migrate.php --test`)
- [ ] Migration configuration created
- [ ] Migration completed successfully
- [ ] Application configuration updated
- [ ] Application tested thoroughly
- [ ] Rollback procedures tested
- [ ] Performance monitoring in place

## 🎉 Next Steps

After successful migration:

1. **Monitor Performance**: Watch query performance and response times
2. **Optimize Queries**: Use PostgreSQL's query analysis tools
3. **Set Up Backups**: Configure automated PostgreSQL backups
4. **Update Documentation**: Update your deployment documentation
5. **Train Team**: Ensure your team understands the new database system

---

**Need help?** Check the detailed `MIGRATION_GUIDE.md` or run `php migrate.php --help` for more information.
