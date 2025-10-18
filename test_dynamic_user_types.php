<?php
/**
 * Dynamic User Type Test Script
 * Demonstrates how easy it is to add new user types
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/SecurityService.php';
require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{get_user_types, get_user_type_config, generate_role_label, get_bulk_removal_enabled_roles, is_bulk_removal_enabled, get_user_type_special_field, get_user_type_special_field_label};

echo "Dynamic User Type System Test\n";
echo "============================\n\n";

// Test 1: Show current user types
echo "1. Current User Types:\n";
$userTypes = get_user_types();
foreach ($userTypes as $role => $config) {
    echo "  $role: {$config['label']}\n";
    echo "    Description: {$config['description']}\n";
    echo "    Bulk Removal: " . ($config['bulk_removal_enabled'] ? 'Enabled' : 'Disabled') . "\n";
    echo "    Requires Password: " . ($config['requires_password'] ? 'Yes' : 'No') . "\n";
    if ($config['has_special_fields']) {
        echo "    Special Field: {$config['special_field']} ({$config['special_field_label']})\n";
    }
    echo "\n";
}

// Test 2: Show bulk removal enabled roles
echo "2. Bulk Removal Enabled Roles:\n";
$bulkRemovalRoles = get_bulk_removal_enabled_roles();
foreach ($bulkRemovalRoles as $role) {
    echo "  - $role\n";
}

// Test 3: Test role label generation
echo "\n3. Role Label Generation:\n";
$testRoles = ['organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'new_role'];
foreach ($testRoles as $role) {
    $label = generate_role_label($role);
    echo "  $role -> $label\n";
}

// Test 4: Test special field detection
echo "\n4. Special Field Detection:\n";
foreach ($testRoles as $role) {
    $specialField = get_user_type_special_field($role);
    $specialFieldLabel = get_user_type_special_field_label($role);
    if ($specialField) {
        echo "  $role: $specialField ($specialFieldLabel)\n";
    } else {
        echo "  $role: No special fields\n";
    }
}

// Test 5: Demonstrate adding a new user type
echo "\n5. Adding New User Type Example:\n";
echo "To add a new user type (e.g., 'volunteer'), you would:\n";
echo "1. Add to get_user_types() in helpers.php:\n";
echo "   'volunteer' => [\n";
echo "       'label' => 'Volunteers',\n";
echo "       'plural' => 'volunteers',\n";
echo "       'has_special_fields' => true,\n";
echo "       'special_field' => 'department',\n";
echo "       'special_field_label' => 'Department',\n";
echo "       'bulk_removal_enabled' => true,\n";
echo "       'requires_password' => false,\n";
echo "       'description' => 'Event volunteers'\n";
echo "   ]\n";
echo "2. Add to validation rules in get_user_validation_rules()\n";
echo "3. That's it! The UI will automatically:\n";
echo "   - Display volunteers in their own section\n";
echo "   - Show department column in the table\n";
echo "   - Add 'Remove All Volunteers' button\n";
echo "   - Generate proper success messages\n";

// Test 6: Show URL generation for bulk removal
echo "\n6. Bulk Removal URL Generation:\n";
foreach ($bulkRemovalRoles as $role) {
    $urlRole = str_replace('_', '-', $role);
    echo "  $role -> /admin/users/remove-all-$urlRole\n";
}

echo "\n🎯 Dynamic User Type System Test Completed!\n";
echo "The system automatically handles new user types without code changes!\n";
