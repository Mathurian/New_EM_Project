<?php
/**
 * Comprehensive User Creation Test Script
 * Tests all aspects of user creation with proper dependencies
 */

// Load all required dependencies
require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/SecurityService.php';
require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{get_user_validation_rules, validate_input, sanitize_input, csrf_field, url, uuid};
use App\DB;

echo "Comprehensive User Creation Test\n";
echo "================================\n\n";

// Test 1: CSRF Token Generation
echo "1. Testing CSRF token generation...\n";
try {
    $csrfToken = csrf_field();
    if (!empty($csrfToken)) {
        echo "✅ CSRF token generated successfully\n";
        echo "Token preview: " . substr($csrfToken, 0, 50) . "...\n";
    } else {
        echo "❌ CSRF token generation failed\n";
    }
} catch (Exception $e) {
    echo "❌ CSRF token generation error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Validation Rules
echo "2. Testing validation rules...\n";
try {
    $validationRules = get_user_validation_rules();
    echo "✅ Validation rules loaded: " . count($validationRules) . " rules\n";
    
    // Test with valid data
    $validData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'TestPass123!',
        'role' => 'tally_master',
        'preferred_name' => 'Test',
        'gender' => 'other',
        'pronouns' => 'they/them'
    ];
    
    $errors = validate_input($validData, $validationRules);
    if (empty($errors)) {
        echo "✅ Valid data passed validation\n";
    } else {
        echo "❌ Valid data failed validation:\n";
        foreach ($errors as $field => $fieldErrors) {
            echo "  $field: " . implode(', ', $fieldErrors) . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Validation rules error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Database Operations
echo "3. Testing database operations...\n";
try {
    $pdo = DB::pdo();
    echo "✅ Database connection successful\n";
    
    // Test creating a user
    $testId = 'test-comp-' . uniqid();
    $testName = 'Test Comprehensive User';
    $testEmail = 'comp@example.com';
    $testPassword = 'TestPass123!';
    $passwordHash = password_hash($testPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (id, name, email, password_hash, role, preferred_name, gender, pronouns, session_version) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$testId, $testName, $testEmail, $passwordHash, 'tally_master', 'Test', 'other', 'they/them', 1]);
    echo "✅ User created in database\n";
    
    // Verify user
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
    
    // Clean up
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$testId]);
    echo "✅ Test user cleaned up\n";
    
} catch (Exception $e) {
    echo "❌ Database operations error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Complete User Creation Process
echo "4. Testing complete user creation process...\n";
try {
    // Simulate form data
    $_POST = [
        'name' => 'Complete Test User',
        'email' => 'complete@example.com',
        'password' => 'TestPass123!',
        'role' => 'tally_master',
        'preferred_name' => 'Complete',
        'gender' => 'other',
        'pronouns' => 'they/them'
    ];
    
    // Sanitize input
    $inputData = sanitize_input($_POST);
    echo "✅ Input data sanitized\n";
    
    // Validate input
    $validationRules = get_user_validation_rules();
    $validationErrors = validate_input($inputData, $validationRules);
    
    if (!empty($validationErrors)) {
        echo "❌ Validation failed:\n";
        foreach ($validationErrors as $field => $errors) {
            echo "  $field: " . implode(', ', $errors) . "\n";
        }
    } else {
        echo "✅ Validation passed\n";
        
        // Test database insertion
        $pdo = DB::pdo();
        $pdo->beginTransaction();
        
        try {
            $userId = uuid();
            $passwordHash = password_hash($inputData['password'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare('INSERT INTO users (id, name, email, password_hash, role, preferred_name, gender, pronouns, session_version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $inputData['name'], $inputData['email'], $passwordHash, $inputData['role'], $inputData['preferred_name'], $inputData['gender'], $inputData['pronouns'], 1]);
            
            $pdo->commit();
            echo "✅ Complete user creation successful\n";
            
            // Clean up
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            echo "✅ Complete test user cleaned up\n";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "❌ Database transaction failed: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Complete user creation process error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Comprehensive test completed!\n";
echo "If all tests passed, the user creation system is working correctly.\n";
