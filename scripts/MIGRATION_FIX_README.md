# Judge Certifications Migration Fix

This directory contains scripts to fix the UNIQUE constraint violation error that occurs when migrating from subcategory-level judge certifications to per-contestant certifications.

## Error Being Fixed

```
PHP Fatal error: Uncaught PDOException: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: judge_certifications.subcategory_id, judge_certifications.judge_id
```

## Available Scripts

### 1. Simple Fix Script
**File:** `fix_judge_certifications.sh`
**Usage:** `./fix_judge_certifications.sh`

A simple script that runs the PHP migration fix. Use this if you just want to run the fix quickly.

### 2. Comprehensive Fix Script
**File:** `fix_judge_certifications_comprehensive.sh`
**Usage:** `./fix_judge_certifications_comprehensive.sh`

A comprehensive script that:
- Checks prerequisites
- Creates database backups
- Stops/starts web server to prevent database locks
- Runs the migration with detailed logging
- Verifies the migration was successful

### 3. Direct PHP Script
**File:** `fix_judge_certifications.php`
**Usage:** `php fix_judge_certifications.php`

The core PHP script that performs the actual migration. Can be run directly if needed.

## How to Run

### Option 1: Comprehensive Script (Recommended)
```bash
# Make sure you're in the project root directory
cd /path/to/your/project

# Run the comprehensive fix
./fix_judge_certifications_comprehensive.sh
```

### Option 2: Simple Script
```bash
# Make sure you're in the project root directory
cd /path/to/your/project

# Run the simple fix
./fix_judge_certifications.sh
```

### Option 3: Direct PHP
```bash
# Make sure you're in the project root directory
cd /path/to/your/project

# Run the PHP script directly
php fix_judge_certifications.php
```

## What the Script Does

1. **Checks Database Schema**: Verifies if the `contestant_id` column exists in the `judge_certifications` table
2. **Adds Column**: Adds the `contestant_id` column if it doesn't exist
3. **Migrates Data**: Converts old subcategory-level certifications to per-contestant certifications
4. **Handles Duplicates**: Checks for and avoids creating duplicate certifications
5. **Updates Constraints**: Updates the unique constraint to include `contestant_id`
6. **Verifies Results**: Checks that the migration was successful

## Prerequisites

- PHP must be installed and accessible via command line
- The script must be run from the project root directory
- The database directory (`app/db`) must be writable
- For the comprehensive script: sudo access may be needed to stop/start web server

## Output

The scripts provide detailed output showing:
- What steps are being performed
- How many certifications were migrated
- Any warnings or errors encountered
- Verification that the migration was successful

## Backup

The comprehensive script automatically creates a backup of your database before making changes. The backup is stored in a timestamped directory like `backup_20231201_143022/`.

## Troubleshooting

### Permission Issues
If you get permission errors:
```bash
# Fix database directory permissions
chmod 755 app/db
chown www-data:www-data app/db  # or your web server user
```

### Web Server Issues
If the web server can't be stopped/started automatically, you may need to:
```bash
# Stop web server manually
sudo systemctl stop apache2
# or
sudo service apache2 stop

# Run the migration
php fix_judge_certifications.php

# Start web server manually
sudo systemctl start apache2
# or
sudo service apache2 start
```

### Database Lock Issues
If you get database lock errors, make sure:
1. No web requests are being processed
2. The web server is stopped
3. No other processes are accessing the database

## After Running the Script

Once the script completes successfully:
1. The application should work normally
2. Judges can now certify scores per contestant instead of per subcategory
3. The old subcategory-level certifications will be converted to per-contestant certifications

## Support

If you encounter issues:
1. Check the log file created by the comprehensive script
2. Verify all prerequisites are met
3. Ensure the database directory has proper permissions
4. Try running the direct PHP script for more detailed error output
