<?php
/**
 * Test script to check database connectivity and user creation
 */

require_once __DIR__ . '/app/lib/DB.php';

try {
    echo "Testing database connectivity...\n";
    echo "Database path: " . App\DB::getDatabasePath() . "\n";
    
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n";
    
    // Test if we can query the users table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Users table accessible. Current user count: " . $result['count'] . "\n";
    
    // Test if we can insert a test user
    $testUserId = 'test-' . uniqid();
    $stmt = $pdo->prepare("INSERT INTO users (id, name, role, session_version) VALUES (?, ?, ?, ?)");
    $stmt->execute([$testUserId, 'Test User', 'contestant', 1]);
    echo "✅ Test user created successfully\n";
    
    // Clean up test user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$testUserId]);
    echo "✅ Test user cleaned up\n";
    
    // Check if tally_master role is supported
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (strpos($result['sql'], 'tally_master') !== false) {
        echo "✅ tally_master role is supported in database\n";
    } else {
        echo "❌ tally_master role is NOT supported in database\n";
        echo "Current constraint: " . $result['sql'] . "\n";
    }
    
    echo "\nDatabase test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Database test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
