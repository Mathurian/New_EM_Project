<?php
/**
 * Simple User Creation Test Script
 * Tests user creation without namespace dependencies
 */

require_once __DIR__ . '/app/lib/DB.php';

echo "Simple User Creation Test\n";
echo "=========================\n\n";

try {
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n";
    
    // Test creating a tally_master user directly
    echo "Testing tally_master user creation...\n";
    
    $testId = 'test-simple-' . uniqid();
    $testName = 'Test Tally Master';
    $testEmail = 'test@example.com';
    $testPassword = 'TestPass123!';
    $passwordHash = password_hash($testPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (id, name, email, password_hash, role, session_version) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$testId, $testName, $testEmail, $passwordHash, 'tally_master', 1]);
    echo "✅ Tally master user created successfully\n";
    
    // Verify the user was created
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$testId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ User verification successful:\n";
        echo "  ID: " . $user['id'] . "\n";
        echo "  Name: " . $user['name'] . "\n";
        echo "  Role: " . $user['role'] . "\n";
        echo "  Email: " . $user['email'] . "\n";
    } else {
        echo "❌ User verification failed\n";
    }
    
    // Test creating other roles
    $roles = ['organizer', 'judge', 'emcee', 'contestant'];
    foreach ($roles as $role) {
        $roleTestId = 'test-' . $role . '-' . uniqid();
        $stmt = $pdo->prepare("
            INSERT INTO users (id, name, role, session_version) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$roleTestId, "Test $role", $role, 1]);
        echo "✅ $role user created successfully\n";
        
        // Clean up
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$roleTestId]);
    }
    
    // Clean up tally master test user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$testId]);
    echo "✅ Test users cleaned up\n";
    
    echo "\n🎉 Simple user creation test successful!\n";
    echo "✅ Database supports tally_master role\n";
    echo "✅ User creation works correctly\n";
    echo "✅ All roles can be created\n";
    
} catch (Exception $e) {
    echo "❌ Simple user creation test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
