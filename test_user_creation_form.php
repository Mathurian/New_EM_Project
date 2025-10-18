<?php
/**
 * User Creation Form Test Script
 * Tests the actual user creation form submission process
 */

require_once __DIR__ . '/app/lib/DB.php';

echo "User Creation Form Test\n";
echo "======================\n\n";

// Simulate form data that would be submitted
$testFormData = [
    'name' => 'Test Tally Master',
    'email' => 'tallymaster@test.com',
    'password' => 'TestPass123!',
    'role' => 'tally_master',
    'preferred_name' => 'Test Master',
    'gender' => 'other',
    'pronouns' => 'they/them'
];

echo "Testing form data:\n";
foreach ($testFormData as $key => $value) {
    echo "  $key: $value\n";
}
echo "\n";

try {
    // Test the validation rules
    echo "1. Testing validation rules...\n";
    
    require_once __DIR__ . '/app/lib/helpers.php';
    
    $validationRules = get_user_validation_rules();
    echo "Validation rules loaded: " . count($validationRules) . " rules\n";
    
    $validationErrors = validate_input($testFormData, $validationRules);
    if (empty($validationErrors)) {
        echo "✅ Validation passed\n";
    } else {
        echo "❌ Validation failed:\n";
        foreach ($validationErrors as $field => $errors) {
            echo "  $field: " . implode(', ', $errors) . "\n";
        }
    }
    
    // Test password validation
    echo "\n2. Testing password validation...\n";
    $password = $testFormData['password'];
    
    $passwordChecks = [
        'length >= 8' => strlen($password) >= 8,
        'has uppercase' => preg_match('/[A-Z]/', $password),
        'has lowercase' => preg_match('/[a-z]/', $password),
        'has number' => preg_match('/[0-9]/', $password),
        'has symbol' => preg_match('/[^A-Za-z0-9]/', $password)
    ];
    
    foreach ($passwordChecks as $check => $result) {
        echo "  $check: " . ($result ? "✅" : "❌") . "\n";
    }
    
    // Test role validation
    echo "\n3. Testing role validation...\n";
    $role = $testFormData['role'];
    $validRoles = ['organizer', 'judge', 'contestant', 'emcee', 'tally_master'];
    
    if (in_array($role, $validRoles)) {
        echo "✅ Role '$role' is valid\n";
    } else {
        echo "❌ Role '$role' is invalid\n";
    }
    
    // Test password requirement
    echo "\n4. Testing password requirement...\n";
    if (in_array($role, ['organizer', 'tally_master']) && empty($password)) {
        echo "❌ Password required for $role\n";
    } else {
        echo "✅ Password requirement check passed\n";
    }
    
    // Test database insertion
    echo "\n5. Testing database insertion...\n";
    
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n";
    
    $testId = 'test-form-' . uniqid();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (id, name, email, password_hash, role, preferred_name, gender, pronouns, session_version) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $testId,
            $testFormData['name'],
            $testFormData['email'],
            $passwordHash,
            $testFormData['role'],
            $testFormData['preferred_name'],
            $testFormData['gender'],
            $testFormData['pronouns'],
            1
        ]);
        
        echo "✅ Database insertion successful\n";
        
        // Verify the user was created
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$testId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✅ User verification successful\n";
            echo "  ID: " . $user['id'] . "\n";
            echo "  Name: " . $user['name'] . "\n";
            echo "  Role: " . $user['role'] . "\n";
            echo "  Email: " . $user['email'] . "\n";
        } else {
            echo "❌ User verification failed\n";
        }
        
        // Clean up
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$testId]);
        echo "✅ Test user cleaned up\n";
        
    } catch (Exception $e) {
        echo "❌ Database insertion failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Form test completed!\n";
    echo "If all tests passed, the issue might be in the web form submission or CSRF handling.\n";
    
} catch (Exception $e) {
    echo "❌ Form test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
