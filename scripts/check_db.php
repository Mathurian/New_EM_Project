<?php
// Database state checker
// Run this to see the current state of your database

require_once __DIR__ . '/app/lib/DB.php';

try {
    $pdo = App\DB::pdo();
    
    echo "=== Database State Check ===\n\n";
    
    // Check if users table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    $usersExists = $stmt->fetch();
    
    if (!$usersExists) {
        echo "ERROR: Users table does not exist!\n";
        exit(1);
    }
    
    echo "✓ Users table exists\n";
    
    // Check table structure
    echo "\n=== Table Structure ===\n";
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        $nullInfo = $col['notnull'] ? 'NOT NULL' : 'NULL';
        $defaultInfo = $col['dflt_value'] ? " DEFAULT '{$col['dflt_value']}'" : '';
        echo "  {$col['name']}: {$col['type']} {$nullInfo}{$defaultInfo}\n";
    }
    
    // Check constraint
    echo "\n=== Constraint Check ===\n";
    try {
        $pdo->exec("INSERT INTO users (id, name, email, password_hash, role) VALUES ('test_organizer', 'Test Org', 'org@test.com', 'test', 'organizer')");
        $pdo->exec("DELETE FROM users WHERE id = 'test_organizer'");
        echo "✓ Organizer role works\n";
    } catch (PDOException $e) {
        echo "✗ Organizer role failed: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("INSERT INTO users (id, name, email, password_hash, role) VALUES ('test_judge', 'Test Judge', 'judge@test.com', 'test', 'judge')");
        $pdo->exec("DELETE FROM users WHERE id = 'test_judge'");
        echo "✓ Judge role works\n";
    } catch (PDOException $e) {
        echo "✗ Judge role failed: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("INSERT INTO users (id, name, email, password_hash, role) VALUES ('test_emcee', 'Test Emcee', 'emcee@test.com', 'test', 'emcee')");
        $pdo->exec("DELETE FROM users WHERE id = 'test_emcee'");
        echo "✓ Emcee role works\n";
    } catch (PDOException $e) {
        echo "✗ Emcee role failed: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("INSERT INTO users (id, name, email, password_hash, role) VALUES ('test_contestant', 'Test Contestant', 'contestant@test.com', 'test', 'contestant')");
        $pdo->exec("DELETE FROM users WHERE id = 'test_contestant'");
        echo "✓ Contestant role works\n";
    } catch (PDOException $e) {
        echo "✗ Contestant role failed: " . $e->getMessage() . "\n";
    }
    
    // Check existing users
    echo "\n=== Existing Users ===\n";
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total users: {$result['count']}\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query('SELECT name, role, email FROM users ORDER BY role, name');
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            $email = $user['email'] ? $user['email'] : 'No email';
            echo "  {$user['name']} ({$user['role']}) - {$email}\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Database appears to be in working order.\n";
    echo "If any roles failed above, run: php fix_constraint_enhanced.php\n";
    
} catch (Exception $e) {
    echo "Error checking database: " . $e->getMessage() . "\n";
    exit(1);
}
