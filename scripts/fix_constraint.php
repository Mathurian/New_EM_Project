<?php
// Manual database constraint fix script
// Run this if you're getting the CHECK constraint error when creating emcee users

require_once __DIR__ . '/app/lib/DB.php';

try {
    $pdo = App\DB::pdo();
    
    echo "Checking current users table constraint...\n";
    
    // Try to create an emcee user to test the constraint
    try {
        $pdo->exec("INSERT INTO users (id, name, email, password_hash, role) VALUES ('test_emcee', 'Test Emcee', 'test@emcee.com', 'test', 'emcee')");
        $pdo->exec("DELETE FROM users WHERE id = 'test_emcee'");
        echo "Constraint already allows emcee role. No update needed.\n";
        exit(0);
    } catch (PDOException $e) {
        echo "Constraint needs updating: " . $e->getMessage() . "\n";
    }
    
    echo "Updating users table constraint to allow emcee and contestant roles...\n";
    
    // Clean up any existing users_new table from previous failed attempts
    $pdo->exec('DROP TABLE IF EXISTS users_new');
    
    // Create new table with updated constraint
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
    
    // Copy existing data with proper handling
    echo "Copying existing user data...\n";
    
    // First, let's check what data we have
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Found {$result['count']} existing users.\n";
    
    // Copy data with explicit column mapping to handle any schema differences
    $pdo->exec('INSERT INTO users_new (id, name, preferred_name, email, password_hash, role, judge_id, gender) 
                SELECT id, name, preferred_name, email, password_hash, role, judge_id, gender FROM users');
    
    echo "Data copied successfully.\n";
    
    // Replace old table
    $pdo->exec('DROP TABLE users');
    $pdo->exec('ALTER TABLE users_new RENAME TO users');
    
    echo "Constraint updated successfully! You can now create emcee and contestant users.\n";
    
} catch (Exception $e) {
    echo "Error updating constraint: " . $e->getMessage() . "\n";
    exit(1);
}
