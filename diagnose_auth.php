<?php
/**
 * Database and Authentication Diagnostic Script
 * Run this to check if the database and authentication system are working
 */

require_once __DIR__ . '/app/bootstrap.php';

echo "=== Database and Authentication Diagnostic ===\n";

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    $pdo = App\DB::pdo();
    echo "   ✓ Database connection successful\n";
    
    // Test if users table exists
    echo "2. Checking users table...\n";
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    if ($stmt->fetch()) {
        echo "   ✓ Users table exists\n";
        
        // Check if there are any users
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
        echo "   ✓ Found {$userCount} users in database\n";
        
        // List users
        $stmt = $pdo->query("SELECT id, email, preferred_name, role FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Users:\n";
        foreach ($users as $user) {
            echo "     - ID: {$user['id']}, Email: {$user['email']}, Name: {$user['preferred_name']}, Role: {$user['role']}\n";
        }
    } else {
        echo "   ✗ Users table does not exist!\n";
    }
    
    // Test if system_settings table exists
    echo "3. Checking system_settings table...\n";
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='system_settings'");
    if ($stmt->fetch()) {
        echo "   ✓ System_settings table exists\n";
        
        // Check session timeout setting
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute(['session_timeout']);
        $timeout = $stmt->fetchColumn();
        if ($timeout) {
            echo "   ✓ Session timeout setting found: {$timeout} seconds\n";
        } else {
            echo "   ⚠ Session timeout setting not found\n";
        }
    } else {
        echo "   ✗ System_settings table does not exist!\n";
    }
    
    // Test if backup_settings table exists
    echo "4. Checking backup_settings table...\n";
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='backup_settings'");
    if ($stmt->fetch()) {
        echo "   ✓ Backup_settings table exists\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM backup_settings");
        $backupCount = $stmt->fetchColumn();
        echo "   ✓ Found {$backupCount} backup settings\n";
    } else {
        echo "   ✗ Backup_settings table does not exist!\n";
    }
    
    // Test authentication query
    echo "5. Testing authentication query...\n";
    $testEmail = 'test@example.com';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR preferred_name = ?');
    $stmt->execute([$testEmail, $testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user === false) {
        echo "   ✓ Authentication query works (no user found, which is expected)\n";
    } else {
        echo "   ✓ Authentication query works (found user)\n";
    }
    
    echo "\n=== Diagnostic Complete ===\n";
    echo "If all checks passed, the database and authentication system should be working.\n";
    echo "If login is still failing, check:\n";
    echo "1. Web server error logs\n";
    echo "2. PHP error logs\n";
    echo "3. Browser developer console for JavaScript errors\n";
    echo "4. Network tab for failed requests\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
