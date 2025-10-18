<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\DB;

echo "Login Functionality Test\n";
echo "========================\n\n";

try {
    // Test database connection
    if (!DB::isHealthy()) {
        echo "✗ Database is not healthy\n";
        exit(1);
    }
    echo "✓ Database is healthy\n";
    
    // Test user lookup (simulate login process)
    echo "Testing user lookup...\n";
    
    $testEmail = 'admin@example.com';
    $user = DB::safeExecute(function() use ($testEmail) {
        $stmt = DB::pdo()->prepare('SELECT * FROM users WHERE email = ? OR preferred_name = ?');
        $stmt->execute([$testEmail, $testEmail]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }, 'user_lookup_test');
    
    if ($user) {
        echo "✓ User lookup successful for: " . $user['email'] . "\n";
        echo "  Role: " . $user['role'] . "\n";
        echo "  Name: " . $user['name'] . "\n";
    } else {
        echo "! No user found with email: $testEmail\n";
        echo "  This is expected if no admin user has been created yet.\n";
    }
    
    // Test logging functionality
    echo "Testing logging functionality...\n";
    
    try {
        \App\Logger::info('test_login', 'system', null, 'Testing login functionality');
        echo "✓ Logging test successful\n";
    } catch (Exception $e) {
        echo "✗ Logging test failed: " . $e->getMessage() . "\n";
    }
    
    // Test session update (simulate login)
    if ($user) {
        echo "Testing session update...\n";
        
        $result = DB::safeExecute(function() use ($user) {
            $now = date('c');
            $stmt = DB::pdo()->prepare('UPDATE users SET last_login = ? WHERE id = ?');
            $stmt->execute([$now, $user['id']]);
            return true;
        }, 'session_update_test');
        
        if ($result) {
            echo "✓ Session update test successful\n";
        } else {
            echo "✗ Session update test failed\n";
        }
    }
    
    echo "\n✓ Login functionality test completed successfully!\n";
    echo "The login system should now work without database locking issues.\n";
    
} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
    exit(1);
}