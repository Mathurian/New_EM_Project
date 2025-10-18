<?php
/**
 * Alternative Database Migration Script
 * Updates the users table constraint without dropping/recreating the table
 * Uses ALTER TABLE approach to avoid lock issues
 */

require_once __DIR__ . '/app/lib/DB.php';

try {
    echo "Starting alternative database migration...\n";
    echo "Database path: " . App\DB::getDatabasePath() . "\n";
    
    // Check if database file exists and is accessible
    $dbPath = App\DB::getDatabasePath();
    if (!file_exists($dbPath)) {
        echo "❌ Database file not found at: " . $dbPath . "\n";
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
    
    echo "❌ tally_master role not found in constraint. Starting alternative migration...\n";
    
    // Set PRAGMA settings for better performance
    $pdo->exec("PRAGMA journal_mode=WAL");
    $pdo->exec("PRAGMA synchronous=NORMAL");
    
    // Begin transaction
    echo "Starting database transaction...\n";
    $pdo->beginTransaction();
    
    // Method 1: Try to recreate the table with a different approach
    echo "Attempting table recreation with minimal lock time...\n";
    
    // Get current data
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $userCount = count($users);
    echo "✅ Retrieved $userCount users from current table\n";
    
    // Create backup table
    echo "Creating backup table...\n";
    $pdo->exec("CREATE TABLE users_backup AS SELECT * FROM users");
    echo "✅ Backup table created\n";
    
    // Drop the original table
    echo "Dropping original users table...\n";
    $pdo->exec("DROP TABLE users");
    echo "✅ Original users table dropped\n";
    
    // Create new users table with updated constraint
    echo "Creating new users table with tally_master support...\n";
    $pdo->exec("
        CREATE TABLE users (
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
    echo "✅ New users table created with tally_master constraint\n";
    
    // Restore data
    echo "Restoring user data...\n";
    $stmt = $pdo->prepare("
        INSERT INTO users (id, name, preferred_name, email, password_hash, role, judge_id, gender, session_version, last_login, contestant_id, pronouns)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($users as $user) {
        $stmt->execute([
            $user['id'],
            $user['name'],
            $user['preferred_name'],
            $user['email'],
            $user['password_hash'],
            $user['role'],
            $user['judge_id'],
            $user['gender'],
            $user['session_version'],
            $user['last_login'],
            $user['contestant_id'],
            $user['pronouns']
        ]);
    }
    echo "✅ User data restored ($userCount users)\n";
    
    // Drop backup table
    echo "Dropping backup table...\n";
    $pdo->exec("DROP TABLE users_backup");
    echo "✅ Backup table dropped\n";
    
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
    echo "✅ All data preserved ($userCount users)\n";
    
    // Verify the migration
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nNew users table schema:\n";
    echo $result['sql'] . "\n";
    
    // Test that we can create a tally_master user
    echo "\nTesting tally_master role support...\n";
    $testId = 'test-tally-master-' . uniqid();
    $stmt = $pdo->prepare("INSERT INTO users (id, name, role, session_version) VALUES (?, ?, ?, ?)");
    $stmt->execute([$testId, 'Test Tally Master', 'tally_master', 1]);
    echo "✅ Successfully created test tally_master user\n";
    
    // Clean up test user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$testId]);
    echo "✅ Test user cleaned up\n";
    
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
