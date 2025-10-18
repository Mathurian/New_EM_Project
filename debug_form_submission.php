<?php
/**
 * Comprehensive Form Debugging Script
 * Helps debug form submission issues by testing all components
 */

// Load all required dependencies
require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/SecurityService.php';
require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{url, csrf_field, verify_csrf_token, sanitize_input, get_user_validation_rules, validate_input};

echo "Comprehensive Form Debugging\n";
echo "============================\n\n";

// Test 1: URL Generation
echo "1. Testing URL generation...\n";
$usersUrl = url('users');
echo "url('users') = $usersUrl\n";

// Test 2: CSRF Token Generation
echo "\n2. Testing CSRF token generation...\n";
$csrfField = csrf_field();
echo "CSRF field generated successfully\n";

// Extract token for testing
preg_match('/value="([^"]+)"/', $csrfField, $matches);
$tokenValue = $matches[1] ?? '';
echo "Token value: " . substr($tokenValue, 0, 20) . "...\n";

// Test 3: Simulate form submission
echo "\n3. Simulating form submission...\n";
$_POST = [
    'name' => 'Debug Tally Master',
    'email' => 'debug@test.com',
    'password' => 'TestPass123!',
    'role' => 'tally_master',
    'preferred_name' => 'Debug Master',
    'gender' => 'other',
    'pronouns' => 'they/them',
    'csrf_token' => $tokenValue
];

echo "POST data:\n";
foreach ($_POST as $key => $value) {
    echo "  $key: $value\n";
}

// Test 4: CSRF Verification
echo "\n4. Testing CSRF verification...\n";
try {
    $isValid = verify_csrf_token();
    echo $isValid ? "✅ CSRF verification passed\n" : "❌ CSRF verification failed\n";
} catch (Exception $e) {
    echo "❌ CSRF verification error: " . $e->getMessage() . "\n";
}

// Test 5: Input Sanitization
echo "\n5. Testing input sanitization...\n";
try {
    $inputData = sanitize_input($_POST);
    echo "✅ Input sanitization passed\n";
    echo "Sanitized data:\n";
    foreach ($inputData as $key => $value) {
        echo "  $key: $value\n";
    }
} catch (Exception $e) {
    echo "❌ Input sanitization error: " . $e->getMessage() . "\n";
}

// Test 6: Validation Rules
echo "\n6. Testing validation rules...\n";
try {
    $validationRules = get_user_validation_rules();
    echo "✅ Validation rules loaded: " . count($validationRules) . " rules\n";
    
    $validationErrors = validate_input($inputData, $validationRules);
    if (empty($validationErrors)) {
        echo "✅ Validation passed\n";
    } else {
        echo "❌ Validation failed:\n";
        foreach ($validationErrors as $field => $errors) {
            echo "  $field: " . implode(', ', $errors) . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Validation error: " . $e->getMessage() . "\n";
}

// Test 7: Database Connection
echo "\n7. Testing database connection...\n";
try {
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current user count: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
}

// Test 8: Check if tally_master role exists in validation rules
echo "\n8. Checking tally_master role in validation rules...\n";
if (isset($validationRules['role']['in'])) {
    $allowedRoles = $validationRules['role']['in'];
    echo "Allowed roles: " . implode(', ', $allowedRoles) . "\n";
    if (in_array('tally_master', $allowedRoles)) {
        echo "✅ tally_master role is allowed\n";
    } else {
        echo "❌ tally_master role is NOT allowed\n";
    }
} else {
    echo "❌ Role validation rules not found\n";
}

// Test 9: Test the actual controller method (simulate)
echo "\n9. Testing controller method simulation...\n";
try {
    // Simulate the exact controller logic
    $name = $inputData['name'] ?? '';
    $email = $inputData['email'] ?? null;
    $password = $inputData['password'] ?? '';
    $role = $inputData['role'] ?? '';
    $preferredName = $inputData['preferred_name'] ?? $name;
    $gender = $inputData['gender'] ?? null;
    $pronouns = $inputData['pronouns'] ?? null;
    
    echo "Extracted variables:\n";
    echo "  name: $name\n";
    echo "  email: $email\n";
    echo "  role: $role\n";
    echo "  preferred_name: $preferredName\n";
    
    // Test password requirement
    if (in_array($role, ['organizer', 'tally_master']) && empty($password)) {
        echo "❌ Password required for role: $role\n";
    } else {
        echo "✅ Password requirement check passed\n";
    }
    
    // Test password complexity
    if (!empty($password)) {
        $complexityChecks = [
            'length' => strlen($password) >= 8,
            'uppercase' => preg_match('/[A-Z]/', $password),
            'lowercase' => preg_match('/[a-z]/', $password),
            'number' => preg_match('/[0-9]/', $password),
            'symbol' => preg_match('/[^A-Za-z0-9]/', $password)
        ];
        
        echo "Password complexity checks:\n";
        foreach ($complexityChecks as $check => $passed) {
            echo "  $check: " . ($passed ? "✅" : "❌") . "\n";
        }
        
        if (array_product($complexityChecks)) {
            echo "✅ Password complexity passed\n";
        } else {
            echo "❌ Password complexity failed\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Controller simulation error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Comprehensive debugging completed!\n";
echo "If all tests passed, the form submission should work correctly.\n";
echo "If any test failed, that's likely the cause of the silent failure.\n";
