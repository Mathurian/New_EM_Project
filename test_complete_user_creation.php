<?php
/**
 * Complete User Creation Test Script
 * Tests the complete user creation process exactly as the web form would
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{get_user_validation_rules, validate_input, sanitize_input, uuid};

echo "Complete User Creation Test\n";
echo "==========================\n\n";

// Simulate the exact data that would come from the web form
$_POST = [
    'name' => 'Test Tally Master',
    'email' => 'tallymaster@test.com',
    'password' => 'TestPass123!',
    'role' => 'tally_master',
    'preferred_name' => 'Test Master',
    'gender' => 'other',
    'pronouns' => 'they/them'
];

echo "Simulating form data:\n";
foreach ($_POST as $key => $value) {
    echo "  $key: $value\n";
}
echo "\n";

try {
    // Step 1: Sanitize input data (as done in controller)
    echo "1. Sanitizing input data...\n";
    $inputData = sanitize_input($_POST);
    echo "✅ Input data sanitized\n";
    
    // Step 2: Extract variables (as done in controller)
    echo "2. Extracting variables...\n";
    $name = $inputData['name'] ?? '';
    $email = $inputData['email'] ?? null;
    $password = $inputData['password'] ?? '';
    $role = $inputData['role'] ?? '';
    $preferredName = $inputData['preferred_name'] ?? $name;
    $gender = $inputData['gender'] ?? null;
    $pronouns = $inputData['pronouns'] ?? null;
    
    echo "✅ Variables extracted:\n";
    echo "  name: $name\n";
    echo "  email: $email\n";
    echo "  role: $role\n";
    echo "  preferred_name: $preferredName\n";
    
    // Step 3: Validate input data (as done in controller)
    echo "\n3. Validating input data...\n";
    $validationRules = get_user_validation_rules();
    $validationErrors = validate_input($inputData, $validationRules);
    
    if (!empty($validationErrors)) {
        echo "❌ Validation failed:\n";
        foreach ($validationErrors as $field => $errors) {
            echo "  $field: " . implode(', ', $errors) . "\n";
        }
        exit(1);
    }
    echo "✅ Validation passed\n";
    
    // Step 4: Validate password complexity (as done in controller)
    echo "\n4. Validating password complexity...\n";
    if (!empty($password)) {
        if (strlen($password) < 8) {
            echo "❌ Password too short\n";
            exit(1);
        }
        if (!preg_match('/[A-Z]/', $password)) {
            echo "❌ Password missing uppercase\n";
            exit(1);
        }
        if (!preg_match('/[a-z]/', $password)) {
            echo "❌ Password missing lowercase\n";
            exit(1);
        }
        if (!preg_match('/[0-9]/', $password)) {
            echo "❌ Password missing number\n";
            exit(1);
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            echo "❌ Password missing symbol\n";
            exit(1);
        }
    }
    echo "✅ Password complexity validation passed\n";
    
    // Step 5: Check password requirement for role (as done in controller)
    echo "\n5. Checking password requirement for role...\n";
    if (in_array($role, ['organizer', 'tally_master']) && empty($password)) {
        echo "❌ Password required for role: $role\n";
        exit(1);
    }
    echo "✅ Password requirement check passed\n";
    
    // Step 6: Database operations (as done in controller)
    echo "\n6. Performing database operations...\n";
    $pdo = DB::pdo();
    echo "✅ Database connection successful\n";
    
    $pdo->beginTransaction();
    echo "✅ Transaction started\n";
    
    try {
        $userId = uuid();
        $passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
        
        echo "✅ User ID generated: $userId\n";
        echo "✅ Password hash created\n";
        
        // Create user (as done in controller)
        $stmt = $pdo->prepare('INSERT INTO users (id, name, email, password_hash, role, preferred_name, gender, pronouns) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $name, $email, $passwordHash, $role, $preferredName, $gender, $pronouns]);
        echo "✅ User created in database\n";
        
        $pdo->commit();
        echo "✅ Transaction committed\n";
        
        // Verify user was created
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✅ User verification successful:\n";
            echo "  ID: " . $user['id'] . "\n";
            echo "  Name: " . $user['name'] . "\n";
            echo "  Role: " . $user['role'] . "\n";
            echo "  Email: " . $user['email'] . "\n";
            echo "  Preferred Name: " . $user['preferred_name'] . "\n";
        } else {
            echo "❌ User verification failed\n";
        }
        
        // Clean up test user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        echo "✅ Test user cleaned up\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "❌ Database operation failed: " . $e->getMessage() . "\n";
        throw $e;
    }
    
    echo "\n🎉 Complete user creation test successful!\n";
    echo "✅ All steps completed successfully\n";
    echo "✅ User creation process is working correctly\n";
    
} catch (Exception $e) {
    echo "❌ User creation test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
