<?php
/**
 * Validation Debugging Script
 * Tests the exact validation that's failing in the web form
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/SecurityService.php';
require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{get_user_validation_rules, validate_input, sanitize_input};

echo "Validation Debugging Test\n";
echo "========================\n\n";

// Test 1: Get validation rules
echo "1. Getting validation rules...\n";
$validationRules = get_user_validation_rules();
echo "✅ Validation rules loaded: " . count($validationRules) . " rules\n";

echo "\nValidation rules:\n";
foreach ($validationRules as $field => $rules) {
    echo "  $field: " . json_encode($rules) . "\n";
}

// Test 2: Test with typical form data
echo "\n2. Testing with typical form data...\n";
$testData = [
    'name' => 'Test Tally Master',
    'email' => 'tallymaster@test.com',
    'password' => 'TestPass123!',
    'role' => 'tally_master',
    'preferred_name' => 'Test Master',
    'gender' => 'other',
    'pronouns' => 'they/them'
];

echo "Test data:\n";
foreach ($testData as $key => $value) {
    echo "  $key: $value\n";
}

// Test 3: Sanitize input
echo "\n3. Sanitizing input...\n";
$sanitizedData = sanitize_input($testData);
echo "✅ Input sanitized\n";

// Test 4: Validate input
echo "\n4. Validating input...\n";
$validationErrors = validate_input($sanitizedData, $validationRules);

if (empty($validationErrors)) {
    echo "✅ Validation passed\n";
} else {
    echo "❌ Validation failed:\n";
    foreach ($validationErrors as $field => $errors) {
        echo "  $field: " . implode(', ', $errors) . "\n";
    }
}

// Test 5: Test with minimal data
echo "\n5. Testing with minimal data...\n";
$minimalData = [
    'name' => 'Test User',
    'role' => 'tally_master'
];

echo "Minimal test data:\n";
foreach ($minimalData as $key => $value) {
    echo "  $key: $value\n";
}

$minimalErrors = validate_input($minimalData, $validationRules);
if (empty($minimalErrors)) {
    echo "✅ Minimal validation passed\n";
} else {
    echo "❌ Minimal validation failed:\n";
    foreach ($minimalErrors as $field => $errors) {
        echo "  $field: " . implode(', ', $errors) . "\n";
    }
}

// Test 6: Test each field individually
echo "\n6. Testing each field individually...\n";
foreach ($validationRules as $field => $rules) {
    $singleFieldData = [$field => $testData[$field] ?? ''];
    $singleFieldErrors = validate_input($singleFieldData, [$field => $rules]);
    
    if (empty($singleFieldErrors)) {
        echo "✅ $field: Valid\n";
    } else {
        echo "❌ $field: " . implode(', ', $singleFieldErrors[$field] ?? []) . "\n";
    }
}

echo "\n🎯 Validation debugging completed!\n";
echo "This will show exactly which validation rule is failing.\n";
