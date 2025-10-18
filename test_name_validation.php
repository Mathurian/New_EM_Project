<?php
/**
 * Name Validation Test Script
 * Tests the updated name validation rules
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/SecurityService.php';
require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{get_user_validation_rules, validate_input, sanitize_input};

echo "Name Validation Test\n";
echo "===================\n\n";

// Test 1: Get updated validation rules
echo "1. Getting updated validation rules...\n";
$validationRules = get_user_validation_rules();

echo "Name validation rules:\n";
echo "  " . json_encode($validationRules['name']) . "\n";
echo "Preferred name validation rules:\n";
echo "  " . json_encode($validationRules['preferred_name']) . "\n";

// Test 2: Test various name formats
echo "\n2. Testing various name formats...\n";

$testNames = [
    'John Doe',
    'Mary-Jane Smith',
    'José María',
    'Jean-Pierre',
    'O\'Connor',
    'Dr. Smith',
    'Test User 123',
    'User@Company',
    'Name & Associates',
    'Test (Jr.)',
    'Very Long Name That Should Still Work Because It Is Under 100 Characters',
    'A', // Too short
    '', // Empty
    'X' . str_repeat('a', 100) // Too long
];

foreach ($testNames as $name) {
    $testData = ['name' => $name];
    $errors = validate_input($testData, ['name' => $validationRules['name']]);
    
    if (empty($errors)) {
        echo "✅ '$name': Valid\n";
    } else {
        echo "❌ '$name': " . implode(', ', $errors['name'] ?? []) . "\n";
    }
}

// Test 3: Test preferred names
echo "\n3. Testing preferred names...\n";

$testPreferredNames = [
    'John',
    'Mary-Jane',
    'José',
    'Jean-Pierre',
    'O\'Connor',
    'Dr. Smith',
    'User123',
    'User@Company',
    'Name & Associates',
    'Test (Jr.)',
    '', // Empty (should be valid)
    'X' . str_repeat('a', 100) // Too long
];

foreach ($testPreferredNames as $preferredName) {
    $testData = ['preferred_name' => $preferredName];
    $errors = validate_input($testData, ['preferred_name' => $validationRules['preferred_name']]);
    
    if (empty($errors)) {
        echo "✅ '$preferredName': Valid\n";
    } else {
        echo "❌ '$preferredName': " . implode(', ', $errors['preferred_name'] ?? []) . "\n";
    }
}

// Test 4: Test complete tally master data
echo "\n4. Testing complete tally master data...\n";

$tallyMasterData = [
    'name' => 'Test Tally Master',
    'email' => 'tallymaster@test.com',
    'password' => 'TestPass123!',
    'role' => 'tally_master',
    'preferred_name' => 'Test Master',
    'gender' => 'other',
    'pronouns' => 'they/them'
];

$allErrors = validate_input($tallyMasterData, $validationRules);

if (empty($allErrors)) {
    echo "✅ Complete tally master data: Valid\n";
} else {
    echo "❌ Complete tally master data failed:\n";
    foreach ($allErrors as $field => $errors) {
        echo "  $field: " . implode(', ', $errors) . "\n";
    }
}

echo "\n🎯 Name validation test completed!\n";
echo "Names should now accept most common formats without pattern restrictions.\n";
