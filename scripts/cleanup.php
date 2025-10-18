<?php
// Cleanup script to remove any leftover users_new table
// Run this if you're getting "table users_new already exists" errors

require_once __DIR__ . '/app/lib/DB.php';

try {
    $pdo = App\DB::pdo();
    
    echo "Cleaning up any leftover users_new table...\n";
    
    // Check if users_new table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users_new'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "Found users_new table. Dropping it...\n";
        $pdo->exec('DROP TABLE users_new');
        echo "Cleanup completed successfully!\n";
    } else {
        echo "No users_new table found. Nothing to clean up.\n";
    }
    
    // Also check if users table exists and is working
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    $usersExists = $stmt->fetch();
    
    if ($usersExists) {
        echo "Users table exists and is accessible.\n";
        
        // Test if we can create emcee/contestant users
        try {
            $pdo->exec("INSERT INTO users (id, name, email, password_hash, role) VALUES ('test_cleanup', 'Test', 'test@cleanup.com', 'test', 'emcee')");
            $pdo->exec("DELETE FROM users WHERE id = 'test_cleanup'");
            echo "Constraint allows emcee role. Database is ready!\n";
        } catch (PDOException $e) {
            echo "Constraint still needs updating: " . $e->getMessage() . "\n";
            echo "Run: php fix_constraint.php\n";
        }
    } else {
        echo "ERROR: Users table does not exist! This is a serious problem.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
    exit(1);
}
