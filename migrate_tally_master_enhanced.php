<?php
/**
 * Enhanced Database Migration Script
 * Updates the users table to include tally_master role in the CHECK constraint
 * Handles database locks and provides better error handling
 */

require_once __DIR__ . '/app/lib/DB.php';

try {
    echo "Starting enhanced database migration...\n";
    echo "Database path: " . App\DB::getDatabasePath() . "\n";
    
    // Check if database file exists and is accessible
    $dbPath = App\DB::getDatabasePath();
    if (!file_exists($dbPath)) {
        echo "❌ Database file not found at: " . $dbPath . "\n";
        exit(1);
    }
    
    if (!is_readable($dbPath)) {
        echo "❌ Database file is not readable: " . $dbPath . "\n";
        exit(1);
    }
    
    if (!is_writable($dbPath)) {
        echo "❌ Database file is not writable: " . $dbPath . "\n";
        exit(1);
    }
    
    echo "✅ Database file is accessible\n";
    
    // Try to connect with timeout and retry logic
    $maxRetries = 5;
    $retryDelay = 2;
    $pdo = null;
    
    for ($i = 0; $i < $maxRetries; $i++) {
        try {
            echo "Attempting database connection (attempt " . ($i + 1) . "/$maxRetries)...\n";
            $pdo = App\DB::pdo();
            echo "✅ Database connection successful\n";
            break;
        } catch (Exception $e) {
            echo "❌ Connection attempt " . ($i + 1) . " failed: " . $e->getMessage() . "\n";
            if ($i < $maxRetries - 1) {
                echo "Waiting $retryDelay seconds before retry...\n";
                sleep($retryDelay);
            }
        }
    }
    
    if (!$pdo) {
        echo "❌ Failed to connect to database after $maxRetries attempts\n";
        exit(1);
    }
    
    // Check current constraint
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current users table schema:\n";
    echo $result['sql'] . "\n\n";
    
    // Check if tally_master is already in the constraint
    if (strpos($result['sql'], 'tally_master') !== false) {
        echo "✅ tally_master role is already in the constraint. No migration needed.\n";
        exit(0);
    }
    
    echo "❌ tally_master role not found in constraint. Starting migration...\n";
    
    // Set PRAGMA settings for better performance and lock handling
    $pdo->exec("PRAGMA journal_mode=WAL");
    $pdo->exec("PRAGMA synchronous=NORMAL");
    $pdo->exec("PRAGMA temp_store=MEMORY");
    $pdo->exec("PRAGMA cache_size=10000");
    
    // Begin transaction with timeout
    echo "Starting database transaction...\n";
    $pdo->beginTransaction();
    
    // Create new users table with updated constraint
    echo "Creating new users table...\n";
    $pdo->exec("
        CREATE TABLE users_new (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            preferred_name TEXT,
            email TEXT UNIQUE,
            password_hash TEXT,
            role TEXT NOT NULL CHECK (role IN ('organizer','judge','emcee','contestant','tally_master')),
            judge_id TEXT,
            gender TEXT,
            session_version INTEGER NOT NULL DEFAULT 1,
            last_login TEXT,
            contestant_id TEXT,
            pronouns TEXT,
            FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE SET NULL
        )
    ");
    echo "✅ New users table created\n";
    
    // Copy data from old table to new table
    echo "Copying data from old table to new table...\n";
    $pdo->exec("INSERT INTO users_new SELECT * FROM users");
    echo "✅ Data copied successfully\n";
    
    // Verify data was copied correctly
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $oldCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users_new");
    $newCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($oldCount !== $newCount) {
        throw new Exception("Data copy verification failed. Old table: $oldCount rows, New table: $newCount rows");
    }
    echo "✅ Data verification successful ($oldCount rows copied)\n";
    
    // Drop old table
    echo "Dropping old users table...\n";
    $pdo->exec("DROP TABLE users");
    echo "✅ Old users table dropped\n";
    
    // Rename new table to users
    echo "Renaming new table to users...\n";
    $pdo->exec("ALTER TABLE users_new RENAME TO users");
    echo "✅ New table renamed to users\n";
    
    // Create tally_master_certifications table if it doesn't exist
    echo "Creating tally_master_certifications table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tally_master_certifications (
            id TEXT PRIMARY KEY,
            subcategory_id TEXT NOT NULL,
            signature_name TEXT NOT NULL,
            certified_at TEXT NOT NULL,
            FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
        )
    ");
    echo "✅ tally_master_certifications table created\n";
    
    // Commit transaction
    echo "Committing transaction...\n";
    $pdo->commit();
    echo "✅ Transaction committed successfully\n";
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "✅ Users table updated with tally_master role constraint\n";
    echo "✅ tally_master_certifications table created\n";
    echo "✅ All data preserved ($oldCount users)\n";
    
    // Verify the migration
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nNew users table schema:\n";
    echo $result['sql'] . "\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        try {
            $pdo->rollBack();
            echo "✅ Transaction rolled back\n";
        } catch (Exception $rollbackError) {
            echo "❌ Rollback failed: " . $rollbackError->getMessage() . "\n";
        }
    }
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
