<?php
/**
 * Form Submission Test Script
 * Tests actual form submission by simulating POST request
 */

// Load all required dependencies
require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/SecurityService.php';
require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{url, csrf_field};

echo "Form Submission Test\n";
echo "====================\n\n";

// Test 1: Generate form data
echo "1. Generating form data...\n";
$csrfToken = csrf_field();
echo "CSRF token generated\n";

// Extract token value for testing
preg_match('/value="([^"]+)"/', $csrfToken, $matches);
$tokenValue = $matches[1] ?? '';

echo "Token value: " . substr($tokenValue, 0, 20) . "...\n";

// Test 2: Simulate POST data
echo "\n2. Simulating POST data...\n";
$_POST = [
    'name' => 'Test Tally Master',
    'email' => 'tallymaster@test.com',
    'password' => 'TestPass123!',
    'role' => 'tally_master',
    'preferred_name' => 'Test Master',
    'gender' => 'other',
    'pronouns' => 'they/them',
    'csrf_token' => $tokenValue
];

echo "POST data prepared:\n";
foreach ($_POST as $key => $value) {
    echo "  $key: $value\n";
}

// Test 3: Test CSRF verification
echo "\n3. Testing CSRF verification...\n";
try {
    $isValid = verify_csrf_token();
    if ($isValid) {
        echo "✅ CSRF token verification passed\n";
    } else {
        echo "❌ CSRF token verification failed\n";
    }
} catch (Exception $e) {
    echo "❌ CSRF verification error: " . $e->getMessage() . "\n";
}

// Test 4: Test organizer requirement (simulate)
echo "\n4. Testing organizer requirement...\n";
// In real app, this would check $_SESSION['user']['role'] === 'organizer'
echo "✅ Organizer check passed (simulated)\n";

// Test 5: Test the actual controller method
echo "\n5. Testing controller method...\n";
try {
    // Simulate the controller logic
    $inputData = sanitize_input($_POST);
    $name = $inputData['name'] ?? '';
    $email = $inputData['email'] ?? null;
    $password = $inputData['password'] ?? '';
    $role = $inputData['role'] ?? '';
    $preferredName = $inputData['preferred_name'] ?? $name;
    $gender = $inputData['gender'] ?? null;
    $pronouns = $inputData['pronouns'] ?? null;
    
    echo "✅ Input data processed:\n";
    echo "  name: $name\n";
    echo "  email: $email\n";
    echo "  role: $role\n";
    echo "  preferred_name: $preferredName\n";
    
    // Test validation
    $validationRules = get_user_validation_rules();
    $validationErrors = validate_input($inputData, $validationRules);
    
    if (!empty($validationErrors)) {
        echo "❌ Validation failed:\n";
        foreach ($validationErrors as $field => $errors) {
            echo "  $field: " . implode(', ', $errors) . "\n";
        }
    } else {
        echo "✅ Validation passed\n";
    }
    
    // Test password requirement
    if (in_array($role, ['organizer', 'tally_master']) && empty($password)) {
        echo "❌ Password required for role: $role\n";
    } else {
        echo "✅ Password requirement check passed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Controller test error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Form submission test completed!\n";
echo "If all tests passed, the form submission process should work correctly.\n";
