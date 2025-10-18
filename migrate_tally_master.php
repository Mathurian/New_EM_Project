<?php
/**
 * Database Migration Script
 * Updates the users table to include tally_master role in the CHECK constraint
 */

require_once __DIR__ . '/app/lib/DB.php';

try {
    $pdo = App\DB::pdo();
    
    echo "Starting database migration...\n";
    echo "Database path: " . App\DB::getDatabasePath() . "\n";
    
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
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Create new users table with updated constraint
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
    
    // Copy data from old table to new table
    $pdo->exec("INSERT INTO users_new SELECT * FROM users");
    
    // Drop old table
    $pdo->exec("DROP TABLE users");
    
    // Rename new table to users
    $pdo->exec("ALTER TABLE users_new RENAME TO users");
    
    // Create tally_master_certifications table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tally_master_certifications (
            id TEXT PRIMARY KEY,
            subcategory_id TEXT NOT NULL,
            signature_name TEXT NOT NULL,
            certified_at TEXT NOT NULL,
            FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
        )
    ");
    
    $pdo->commit();
    
    echo "✅ Migration completed successfully!\n";
    echo "✅ Users table updated with tally_master role constraint\n";
    echo "✅ tally_master_certifications table created\n";
    
    // Verify the migration
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nNew users table schema:\n";
    echo $result['sql'] . "\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
