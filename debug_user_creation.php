<?php
/**
 * User Creation Debug Script
 * Tests user creation functionality and identifies issues
 */

require_once __DIR__ . '/app/lib/DB.php';

echo "User Creation Debug Script\n";
echo "========================\n\n";

try {
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n";
    
    // Check if tally_master role is supported
    echo "\n1. Checking tally_master role support...\n";
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (strpos($result['sql'], 'tally_master') !== false) {
        echo "✅ tally_master role is supported in database\n";
    } else {
        echo "❌ tally_master role is NOT supported in database\n";
        exit(1);
    }
    
    // Test creating a user with each role
    echo "\n2. Testing user creation for each role...\n";
    
    $roles = ['organizer', 'judge', 'emcee', 'tally_master', 'contestant'];
    $testUsers = [];
    
    foreach ($roles as $role) {
        $testId = 'test-' . $role . '-' . uniqid();
        $testName = 'Test ' . ucfirst($role);
        
        echo "Testing $role role...\n";
        
        try {
            // Test with password (required for organizer and tally_master)
            if (in_array($role, ['organizer', 'tally_master'])) {
                $passwordHash = password_hash('TestPass123!', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (id, name, email, password_hash, role, session_version) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$testId, $testName, $testId . '@test.com', $passwordHash, $role, 1]);
            } else {
                // Test without password (optional for other roles)
                $stmt = $pdo->prepare("
                    INSERT INTO users (id, name, role, session_version) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$testId, $testName, $role, 1]);
            }
            
            echo "✅ Successfully created $role user\n";
            $testUsers[] = $testId;
            
        } catch (Exception $e) {
            echo "❌ Failed to create $role user: " . $e->getMessage() . "\n";
        }
    }
    
    // Test validation rules
    echo "\n3. Testing validation rules...\n";
    
    // Test invalid role
    try {
        $testId = 'test-invalid-' . uniqid();
        $stmt = $pdo->prepare("
            INSERT INTO users (id, name, role, session_version) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$testId, 'Test Invalid', 'invalid_role', 1]);
        echo "❌ Invalid role was accepted (this should not happen)\n";
    } catch (Exception $e) {
        echo "✅ Invalid role correctly rejected: " . $e->getMessage() . "\n";
    }
    
    // Test duplicate email
    try {
        $testId = 'test-duplicate-' . uniqid();
        $stmt = $pdo->prepare("
            INSERT INTO users (id, name, email, role, session_version) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$testId, 'Test Duplicate', 'test-organizer-' . $testUsers[0] . '@test.com', 'contestant', 1]);
        echo "❌ Duplicate email was accepted (this should not happen)\n";
    } catch (Exception $e) {
        echo "✅ Duplicate email correctly rejected: " . $e->getMessage() . "\n";
    }
    
    // Test missing required fields
    try {
        $testId = 'test-missing-' . uniqid();
        $stmt = $pdo->prepare("
            INSERT INTO users (id, role, session_version) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$testId, 'contestant', 1]);
        echo "❌ Missing name was accepted (this should not happen)\n";
    } catch (Exception $e) {
        echo "✅ Missing name correctly rejected: " . $e->getMessage() . "\n";
    }
    
    // Clean up test users
    echo "\n4. Cleaning up test users...\n";
    foreach ($testUsers as $testId) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$testId]);
    }
    echo "✅ Test users cleaned up\n";
    
    // Test the actual user creation process
    echo "\n5. Testing user creation process...\n";
    
    // Simulate the user creation process
    $testData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'TestPass123!',
        'role' => 'tally_master',
        'preferred_name' => 'Test',
        'gender' => 'other',
        'pronouns' => 'they/them'
    ];
    
    echo "Testing with data: " . json_encode($testData) . "\n";
    
    // Test password validation
    $password = $testData['password'];
    if (strlen($password) < 8) {
        echo "❌ Password too short\n";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        echo "❌ Password missing uppercase\n";
    } elseif (!preg_match('/[a-z]/', $password)) {
        echo "❌ Password missing lowercase\n";
    } elseif (!preg_match('/[0-9]/', $password)) {
        echo "❌ Password missing number\n";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        echo "❌ Password missing symbol\n";
    } else {
        echo "✅ Password validation passed\n";
    }
    
    // Test role validation
    if (in_array($testData['role'], ['organizer', 'judge', 'contestant', 'emcee', 'tally_master'])) {
        echo "✅ Role validation passed\n";
    } else {
        echo "❌ Role validation failed\n";
    }
    
    // Test password requirement for tally_master
    if (in_array($testData['role'], ['organizer', 'tally_master']) && empty($testData['password'])) {
        echo "❌ Password required for " . $testData['role'] . "\n";
    } else {
        echo "✅ Password requirement check passed\n";
    }
    
    // Test actual insertion
    $testId = 'test-process-' . uniqid();
    $passwordHash = password_hash($testData['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (id, name, email, password_hash, role, preferred_name, gender, pronouns, session_version) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $testId,
            $testData['name'],
            $testData['email'],
            $passwordHash,
            $testData['role'],
            $testData['preferred_name'],
            $testData['gender'],
            $testData['pronouns'],
            1
        ]);
        echo "✅ User creation process test successful\n";
        
        // Clean up
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$testId]);
        echo "✅ Test user cleaned up\n";
        
    } catch (Exception $e) {
        echo "❌ User creation process test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Debug test completed!\n";
    echo "If all tests passed, the issue might be in the web form or controller logic.\n";
    
} catch (Exception $e) {
    echo "❌ Debug test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
