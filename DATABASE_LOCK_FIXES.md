# Database Lock Fixes

This document describes the fixes implemented to resolve the "database is locked" errors in the Apache logs.

## Problem Analysis

The database locking issues were caused by several factors:

1. **Multiple Database Operations in Login Process**: The login process performed multiple database operations without proper error handling
2. **Logger Database Operations**: Every log entry wrote to the database, causing additional locking during login
3. **Database Migration on Every Request**: The bootstrap.php called `App\DB::migrate()` on every request
4. **No Database Connection Pooling**: Each request created a new PDO connection without proper connection management
5. **Inconsistent WAL Mode**: WAL mode was not consistently applied across all database operations
6. **Role Constraint Updates**: The role constraint update process could cause locking issues

## Fixes Implemented

### 1. Database Connection Optimization (`app/lib/DB.php`)

- **Added WAL Mode**: Set `PRAGMA journal_mode = WAL` for better concurrency
- **Added Busy Timeout**: Set `PRAGMA busy_timeout = 30000` (30 seconds)
- **Added Performance Pragmas**: 
  - `PRAGMA synchronous = NORMAL`
  - `PRAGMA cache_size = 10000`
  - `PRAGMA temp_store = MEMORY`
  - `PRAGMA wal_autocheckpoint = 1000`

### 2. Retry Mechanism (`app/lib/DB.php`)

- **Added `executeWithRetry()`**: Automatically retries database operations with exponential backoff
- **Added `safeExecute()`**: Wraps database operations with error handling and retry logic
- **Added Database Health Check**: `isHealthy()` method to check database accessibility

### 3. Logger Optimization (`app/lib/Logger.php`)

- **File Logging First**: Write to file before database to ensure logging doesn't fail
- **Safe Database Logging**: Use `safeExecute()` for database logging operations
- **Reduced Database Writes**: Minimize database operations during critical processes like login

### 4. Login Process Optimization (`app/routes/controllers.php`)

- **Simplified Database Operations**: Reduced the number of database operations during login
- **Safe Execution**: Use `safeExecute()` for all database operations
- **Non-blocking Logging**: Log operations don't block the login process

### 5. Bootstrap Optimization (`app/bootstrap.php`)

- **Conditional Migration**: Only run migration if database is empty or doesn't exist
- **Health Check**: Check database state before running migration

### 6. Role Constraint Update Fix (`app/lib/DB.php`)

- **Safe Execution**: Use `safeExecute()` for role constraint updates
- **Better Error Handling**: Don't fail the entire application if constraint update fails
- **Complete Column Mapping**: Include all columns when recreating the users table

## Scripts Created

### 1. `fix_database_locks.php`
Comprehensive script to fix existing database lock issues:
- Removes lock files
- Optimizes database settings
- Tests database operations
- Runs health checks

### 2. `test_login.php`
Test script to verify login functionality:
- Tests database connection
- Tests user lookup
- Tests logging functionality
- Tests session updates

### 3. `scripts/init_database.php`
Database initialization script:
- Creates database directory
- Runs migration
- Optimizes database
- Checks health

### 4. `scripts/unlock_database.php`
Database unlock script:
- Removes lock files
- Sets optimal pragmas
- Tests database accessibility

## Usage

### Fix Existing Database Issues
```bash
php fix_database_locks.php
```

### Test Login Functionality
```bash
php test_login.php
```

### Initialize New Database
```bash
php scripts/init_database.php
```

### Unlock Database
```bash
php scripts/unlock_database.php
```

## Configuration Changes

### Database Settings
The following pragmas are now set automatically:
- `journal_mode = WAL` - Better concurrency
- `synchronous = NORMAL` - Balance between safety and performance
- `cache_size = 10000` - Larger cache for better performance
- `temp_store = MEMORY` - Use memory for temporary data
- `busy_timeout = 30000` - Wait up to 30 seconds for locks
- `wal_autocheckpoint = 1000` - Automatic WAL checkpointing

### Error Handling
- All database operations now use retry mechanisms
- Non-critical operations (like logging) don't block critical operations
- Database errors are logged but don't crash the application

## Expected Results

After applying these fixes:
1. **No More Database Lock Errors**: The "database is locked" errors should be eliminated
2. **Improved Login Performance**: Login process should be faster and more reliable
3. **Better Concurrency**: Multiple users can access the system simultaneously
4. **Graceful Error Handling**: Database errors won't crash the application
5. **Consistent Database State**: Database will be properly initialized and optimized

## Monitoring

To monitor the database health:
1. Check Apache error logs for database-related errors
2. Run `php test_login.php` periodically to verify functionality
3. Monitor database file size and WAL file presence
4. Check application logs for any database operation failures

## Troubleshooting

If database lock issues persist:
1. Run `php fix_database_locks.php` to fix existing issues
2. Check file permissions on the database directory
3. Ensure no other processes are accessing the database
4. Check system resources (disk space, memory)
5. Review application logs for specific error patterns

## Files Modified

- `app/lib/DB.php` - Database connection and retry mechanisms
- `app/lib/Logger.php` - Optimized logging with safe database operations
- `app/routes/controllers.php` - Optimized login process
- `app/bootstrap.php` - Conditional database migration
- `check_db_health.php` - Database health check script (new)
- `fix_database_locks.php` - Comprehensive fix script (new)
- `test_login.php` - Login functionality test script (new)
- `scripts/init_database.php` - Database initialization script (new)
- `scripts/unlock_database.php` - Database unlock script (new)