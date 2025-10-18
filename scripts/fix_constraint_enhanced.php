<?php
// Enhanced constraint fix script with better error handling
// Run this if you're getting constraint errors when creating emcee/contestant users

require_once __DIR__ . '/app/lib/DB.php';

try {
    $pdo = App\DB::pdo();
    
    echo "Enhanced constraint fix starting...\n";
    
    // Check current constraint
    try {
        $pdo->exec("INSERT INTO users (id, name, email, password_hash, role) VALUES ('test_constraint', 'test', 'test@test.com', 'test', 'emcee')");
        $pdo->exec("DELETE FROM users WHERE id = 'test_constraint'");
        echo "Constraint already allows emcee role. No update needed.\n";
        exit(0);
    } catch (PDOException $e) {
        echo "Constraint needs updating: " . $e->getMessage() . "\n";
    }
    
    echo "Updating users table constraint...\n";
    
    // Clean up any existing users_new table
    $pdo->exec('DROP TABLE IF EXISTS users_new');
    
    // First, let's examine the current users table structure
    echo "Examining current users table...\n";
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current table structure:\n";
    foreach ($columns as $col) {
        echo "  {$col['name']}: {$col['type']} " . ($col['notnull'] ? 'NOT NULL' : 'NULL') . "\n";
    }
    
    // Check existing data
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Found {$result['count']} existing users.\n";
    
    if ($result['count'] > 0) {
        // Show sample data to understand what we're working with
        $stmt = $pdo->query('SELECT id, name, role FROM users LIMIT 3');
        $sampleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Sample users:\n";
        foreach ($sampleUsers as $user) {
            echo "  ID: {$user['id']}, Name: {$user['name']}, Role: {$user['role']}\n";
        }
    }
    
    // Create new table with updated constraint
    echo "Creating new users table with updated constraint...\n";
    $pdo->exec('CREATE TABLE users_new (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        preferred_name TEXT,
        email TEXT UNIQUE,
        password_hash TEXT,
        role TEXT NOT NULL CHECK (role IN (\'organizer\',\'judge\',\'emcee\',\'contestant\')),
        judge_id TEXT,
        gender TEXT,
        FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE SET NULL
    )');
    
    // Copy data with explicit column mapping
    echo "Copying user data...\n";
    
    if ($result['count'] > 0) {
        // Use explicit column mapping to handle any schema differences
        $pdo->exec('INSERT INTO users_new (id, name, preferred_name, email, password_hash, role, judge_id, gender) 
                    SELECT 
                        id, 
                        name, 
                        COALESCE(preferred_name, \'\') as preferred_name,
                        email, 
                        password_hash, 
                        COALESCE(role, \'organizer\') as role,
                        judge_id, 
                        gender 
                    FROM users');
    }
    
    echo "Data copied successfully.\n";
    
    // Verify the new table
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users_new');
    $newCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "New table has {$newCount['count']} users.\n";
    
    // Test the new constraint
    echo "Testing new constraint...\n";
    try {
        $pdo->exec("INSERT INTO users_new (id, name, email, password_hash, role) VALUES ('test_new_constraint', 'test', 'test@new.com', 'test', 'emcee')");
        $pdo->exec("DELETE FROM users_new WHERE id = 'test_new_constraint'");
        echo "New constraint works correctly!\n";
    } catch (PDOException $e) {
        echo "ERROR: New constraint failed: " . $e->getMessage() . "\n";
        $pdo->exec('DROP TABLE users_new');
        exit(1);
    }
    
    // Replace old table
    echo "Replacing old table...\n";
    $pdo->exec('DROP TABLE users');
    $pdo->exec('ALTER TABLE users_new RENAME TO users');
    
    echo "Constraint updated successfully! You can now create emcee and contestant users.\n";
    
    // Final test
    echo "Running final test...\n";
    try {
        $pdo->exec("INSERT INTO users (id, name, email, password_hash, role) VALUES ('final_test', 'Final Test', 'final@test.com', 'test', 'contestant')");
        $pdo->exec("DELETE FROM users WHERE id = 'final_test'");
        echo "Final test passed! Database is ready.\n";
    } catch (PDOException $e) {
        echo "WARNING: Final test failed: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error updating constraint: " . $e->getMessage() . "\n";
    
    // Try to clean up
    try {
        $pdo->exec('DROP TABLE IF EXISTS users_new');
        echo "Cleanup completed.\n";
    } catch (Exception $cleanupError) {
        echo "Cleanup failed: " . $cleanupError->getMessage() . "\n";
    }
    
    exit(1);
}
