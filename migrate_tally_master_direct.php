<?php
/**
 * Direct Database Modification Script
 * Modifies the database file directly to add tally_master role support
 * This approach avoids transaction locks by working directly with the SQLite file
 */

require_once __DIR__ . '/app/lib/DB.php';

try {
    echo "Starting direct database modification...\n";
    echo "Database path: " . App\DB::getDatabasePath() . "\n";
    
    $dbPath = App\DB::getDatabasePath();
    
    // Check if database file exists
    if (!file_exists($dbPath)) {
        echo "❌ Database file not found at: " . $dbPath . "\n";
        exit(1);
    }
    
    echo "✅ Database file exists\n";
    
    // Create a backup first
    $backupPath = $dbPath . '.backup.' . date('Y-m-d_H-i-s');
    if (!copy($dbPath, $backupPath)) {
        echo "❌ Failed to create backup\n";
        exit(1);
    }
    echo "✅ Backup created: " . $backupPath . "\n";
    
    // Connect to database
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n";
    
    // Check current constraint
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current users table schema:\n";
    echo $result['sql'] . "\n\n";
    
    // Check if tally_master is already in the constraint
    if (strpos($result['sql'], 'tally_master') !== false) {
        echo "✅ tally_master role is already in the constraint. No migration needed.\n";
        unlink($backupPath); // Remove backup since it's not needed
        exit(0);
    }
    
    echo "❌ tally_master role not found in constraint. Starting direct modification...\n";
    
    // Get all users data
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $userCount = count($users);
    echo "✅ Retrieved $userCount users\n";
    
    // Close the connection to release any locks
    $pdo = null;
    echo "✅ Database connection closed\n";
    
    // Wait a moment to ensure locks are released
    sleep(1);
    
    // Reconnect
    $pdo = App\DB::pdo();
    echo "✅ Database reconnected\n";
    
    // Drop and recreate the table
    echo "Dropping users table...\n";
    $pdo->exec("DROP TABLE users");
    echo "✅ Users table dropped\n";
    
    // Create new table with tally_master support
    echo "Creating new users table...\n";
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
    echo "✅ New users table created\n";
    
    // Insert all users back
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
    echo "✅ All $userCount users restored\n";
    
    // Create tally_master_certifications table
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
    
    // Verify the migration
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nNew users table schema:\n";
    echo $result['sql'] . "\n";
    
    // Test tally_master role
    echo "\nTesting tally_master role...\n";
    $testId = 'test-tally-' . uniqid();
    $stmt = $pdo->prepare("INSERT INTO users (id, name, role, session_version) VALUES (?, ?, ?, ?)");
    $stmt->execute([$testId, 'Test Tally Master', 'tally_master', 1]);
    echo "✅ Successfully created test tally_master user\n";
    
    // Clean up test
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$testId]);
    echo "✅ Test user cleaned up\n";
    
    echo "\n🎉 Direct modification completed successfully!\n";
    echo "✅ Users table updated with tally_master role constraint\n";
    echo "✅ tally_master_certifications table created\n";
    echo "✅ All data preserved ($userCount users)\n";
    echo "✅ Backup available at: " . $backupPath . "\n";
    
} catch (Exception $e) {
    echo "❌ Direct modification failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Restore from backup if it exists
    if (isset($backupPath) && file_exists($backupPath)) {
        echo "\nAttempting to restore from backup...\n";
        if (copy($backupPath, $dbPath)) {
            echo "✅ Database restored from backup\n";
        } else {
            echo "❌ Failed to restore from backup\n";
        }
    }
    exit(1);
}
