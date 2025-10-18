<?php
/**
 * Simple test script to verify database and logging functionality
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/helpers.php';

echo "=== Database and Logging Test ===\n";

try {
    // Test 1: Database connection
    echo "1. Testing database connection...\n";
    $pdo = App\DB::pdo();
    echo "   ✅ Database connection successful\n";
    
    // Test 2: Check if users table exists and is accessible
    echo "2. Testing users table access...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Users table accessible - $userCount users found\n";
    
    // Test 3: Check if activity_logs table exists and foreign key constraints
    echo "3. Testing activity_logs table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM activity_logs");
    $logCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Activity_logs table accessible - $logCount logs found\n";
    
    // Test 4: Check foreign key constraints
    echo "4. Checking foreign key constraints...\n";
    $stmt = $pdo->query("PRAGMA foreign_key_list(activity_logs)");
    $fkList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($fkList)) {
        echo "   Foreign key constraints found:\n";
        foreach ($fkList as $fk) {
            echo "   - {$fk['table']}.{$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
            if ($fk['table'] === 'old_users') {
                echo "   ❌ ERROR: Still pointing to old_users table!\n";
            }
        }
    } else {
        echo "   ⚠️  No foreign key constraints found\n";
    }
    
    // Test 5: Test logging functionality
    echo "5. Testing logging functionality...\n";
    error_log('Test log message from debug script');
    echo "   ✅ Error logging test completed\n";
    
    // Test 6: Test emcee_scripts table
    echo "6. Testing emcee_scripts table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM emcee_scripts");
    $scriptCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Emcee_scripts table accessible - $scriptCount scripts found\n";
    
    // Test 7: Test a simple insert into activity_logs
    echo "7. Testing activity_logs insert...\n";
    $testId = App\uuid();
    $stmt = $pdo->prepare("INSERT INTO activity_logs (id, user_id, user_name, user_role, action, resource_type, resource_id, details, ip_address, user_agent, log_level, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $testId,
        null,
        'Test User',
        'test',
        'test_action',
        'test',
        'test_id',
        'Testing database functionality',
        '127.0.0.1',
        'Test Agent',
        'info',
        date('c')
    ]);
    
    // Clean up test record
    $pdo->exec("DELETE FROM activity_logs WHERE id = '$testId'");
    echo "   ✅ Activity_logs insert test successful\n";
    
    echo "\n=== All Tests Passed ===\n";
    echo "Database and logging functionality appears to be working correctly.\n";
    echo "The issue might be in the web application routing or session handling.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
