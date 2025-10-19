# SQLite to PostgreSQL Migration Guide

## Overview

This guide provides comprehensive instructions for migrating the Event Manager application from SQLite to PostgreSQL. The migration system includes automated schema conversion, data migration, validation, and rollback procedures.

## Prerequisites

### System Requirements
- PHP 8.0 or higher
- PostgreSQL 12 or higher
- PHP PostgreSQL extension (`php-pgsql`)
- Sufficient disk space for backups

### Database Setup
1. Install PostgreSQL:
   ```bash
   sudo apt-get install postgresql postgresql-contrib php-pgsql
   ```

2. Create database and user:
   ```sql
   CREATE DATABASE event_manager;
   CREATE USER event_manager WITH PASSWORD 'secure_password';
   GRANT ALL PRIVILEGES ON DATABASE event_manager TO event_manager;
   ```

## Migration Process

### Step 1: Test Migration

Before performing the actual migration, test the process:

```bash
php migrate.php --test
```

This will:
- Test database connections
- Validate schema migration
- Test data type conversions
- Check data integrity
- Verify application compatibility

### Step 2: Configure Migration

Create a configuration file:

```bash
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
        'password' => 'secure_password'
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

Run the migration:

```bash
php migrate.php --migrate
```

The migration process will:
1. Create a backup of the SQLite database
2. Create PostgreSQL schema
3. Migrate all data with type conversions
4. Validate data integrity
5. Create rollback scripts

### Step 4: Update Application Configuration

Update your application configuration to use PostgreSQL:

```php
// config/production.php
return [
    'database' => [
        'type' => 'postgresql',
        'host' => 'localhost',
        'port' => '5432',
        'name' => 'event_manager',
        'user' => 'event_manager',
        'password' => 'secure_password',
    ],
];
```

### Step 5: Test Application

Test your application thoroughly:
- User authentication
- Data entry and retrieval
- Report generation
- Email functionality
- All user workflows

## Data Type Conversions

### SQLite to PostgreSQL Mappings

| SQLite Type | PostgreSQL Type | Conversion Notes |
|-------------|-----------------|------------------|
| TEXT | VARCHAR/TEXT | Direct mapping |
| INTEGER | INTEGER/BIGINT | Direct mapping |
| REAL | DECIMAL/NUMERIC | Precision handling |
| BOOLEAN | BOOLEAN | 0/1 → FALSE/TRUE |
| PRIMARY KEY | UUID | Hex string → UUID format |

### Special Conversions

#### UUID Generation
- **SQLite**: Custom hex strings (32 characters)
- **PostgreSQL**: Native UUID format with hyphens
- **Conversion**: `12345678901234567890123456789012` → `12345678-9012-3456-7890-123456789012`

#### Boolean Handling
- **SQLite**: INTEGER (0/1)
- **PostgreSQL**: BOOLEAN (TRUE/FALSE)
- **Conversion**: Automatic type casting

#### Timestamp Handling
- **SQLite**: TEXT timestamps
- **PostgreSQL**: TIMESTAMP WITH TIME ZONE
- **Conversion**: ISO format with timezone

## Breaking Changes & Mitigation

### Critical Changes

1. **Data Type Enforcement**
   - **Issue**: PostgreSQL enforces strict typing
   - **Mitigation**: Automatic type conversion during migration
   - **Remediation**: Data validation and type casting

2. **Primary Key Generation**
   - **Issue**: Different UUID formats
   - **Mitigation**: Automatic UUID conversion
   - **Remediation**: Update application code to use `DB::generateUUID()`

3. **Query Syntax Differences**
   - **Issue**: SQLite-specific queries
   - **Mitigation**: Automatic query conversion
   - **Remediation**: Use database abstraction layer

### Moderate Changes

4. **String Functions**
   - **Issue**: Different concatenation syntax
   - **Mitigation**: Automatic query conversion
   - **Remediation**: Use `CONCAT()` function

5. **Case Sensitivity**
   - **Issue**: PostgreSQL is case-sensitive by default
   - **Mitigation**: Use `ILIKE` for case-insensitive searches
   - **Remediation**: Update search queries

### Minor Changes

6. **Error Messages**
   - **Issue**: Different error formats
   - **Mitigation**: Consistent error handling
   - **Remediation**: Update error handling code

## Rollback Procedures

### Automatic Rollback

If migration fails, the system will:
1. Stop the migration process
2. Preserve the original SQLite database
3. Clean up any partial PostgreSQL data
4. Provide detailed error logs

### Manual Rollback

To rollback after successful migration:

```bash
php migrate.php --rollback
```

This will:
1. Switch the application back to SQLite
2. Clear PostgreSQL-specific caches
3. Restore original configuration

### Rollback Scripts

Rollback scripts are automatically created during migration:
- Location: `backups/rollback_YYYY-MM-DD_HH-MM-SS.php`
- Contains: Database switching logic
- Usage: `php backups/rollback_*.php`

## Performance Considerations

### Optimization Strategies

1. **Indexing**
   - Automatic index creation during migration
   - Performance indexes for common queries
   - Foreign key indexes

2. **Connection Pooling**
   - PostgreSQL connection pooling
   - Persistent connections
   - Connection limits

3. **Query Optimization**
   - Query plan analysis
   - Slow query identification
   - Performance monitoring

### Performance Monitoring

Monitor these metrics:
- Query response times
- Connection pool usage
- Database size growth
- Concurrent user capacity

## Troubleshooting

### Common Issues

#### Connection Errors
```
Error: Connection refused
Solution: Check PostgreSQL service status and firewall settings
```

#### Permission Errors
```
Error: Permission denied
Solution: Verify database user permissions and ownership
```

#### Data Type Errors
```
Error: Invalid input syntax
Solution: Check data type conversions and constraints
```

#### Performance Issues
```
Error: Query timeout
Solution: Optimize queries and add indexes
```

### Debug Mode

Enable debug mode for troubleshooting:

```php
// config/development.php
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

### Log Files

Check these log files:
- Application logs: `logs/event-manager.log`
- Migration logs: Console output during migration
- PostgreSQL logs: `/var/log/postgresql/`

## Best Practices

### Before Migration

1. **Backup Everything**
   - Database backup
   - Application files backup
   - Configuration backup

2. **Test Environment**
   - Set up staging environment
   - Test migration process
   - Validate all functionality

3. **Plan Downtime**
   - Schedule maintenance window
   - Notify users
   - Prepare rollback plan

### During Migration

1. **Monitor Progress**
   - Watch migration logs
   - Monitor system resources
   - Check for errors

2. **Validate Data**
   - Verify row counts
   - Check data integrity
   - Test critical functions

### After Migration

1. **Comprehensive Testing**
   - All user workflows
   - Performance testing
   - Load testing

2. **Monitor Performance**
   - Query performance
   - Response times
   - Error rates

3. **Keep Rollback Ready**
   - Maintain rollback scripts
   - Test rollback procedures
   - Document rollback process

## Support

### Getting Help

1. **Check Logs**: Review application and migration logs
2. **Run Tests**: Use the test suite to identify issues
3. **Documentation**: Refer to this guide and code comments
4. **Community**: Check project documentation and forums

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

3. **Contact Support**: Provide detailed error logs and system information

## Conclusion

The SQLite to PostgreSQL migration provides significant benefits:
- Better concurrency handling
- Advanced features and data types
- Improved performance and scalability
- Enhanced data integrity

With proper planning, testing, and execution, the migration can be completed successfully with minimal downtime and risk.
