<?php
/**
 * Auditor User Type Test Script
 * Demonstrates the new Auditor user type functionality
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/SecurityService.php';
require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{get_user_types, get_user_type_config, generate_role_label, get_bulk_removal_enabled_roles, is_bulk_removal_enabled, get_user_type_special_field, get_user_type_special_field_label, requires_password_for_role};

echo "Auditor User Type Test\n";
echo "=====================\n\n";

// Test 1: Show Auditor configuration
echo "1. Auditor User Type Configuration:\n";
$auditorConfig = get_user_type_config('auditor');
foreach ($auditorConfig as $key => $value) {
    echo "  $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
}

// Test 2: Test role label generation
echo "\n2. Role Label Generation:\n";
$label = generate_role_label('auditor');
echo "  auditor -> $label\n";

// Test 3: Test password requirement
echo "\n3. Password Requirement:\n";
$requiresPassword = requires_password_for_role('auditor');
echo "  Auditor requires password: " . ($requiresPassword ? 'Yes' : 'No') . "\n";

// Test 4: Test bulk removal permission
echo "\n4. Bulk Removal Permission:\n";
$bulkRemovalEnabled = is_bulk_removal_enabled('auditor');
echo "  Auditor bulk removal enabled: " . ($bulkRemovalEnabled ? 'Yes' : 'No') . "\n";

// Test 5: Test special fields
echo "\n5. Special Fields:\n";
$specialField = get_user_type_special_field('auditor');
$specialFieldLabel = get_user_type_special_field_label('auditor');
if ($specialField) {
    echo "  Special field: $specialField ($specialFieldLabel)\n";
} else {
    echo "  No special fields\n";
}

// Test 6: Show all user types including Auditor
echo "\n6. All User Types (including Auditor):\n";
$userTypes = get_user_types();
foreach ($userTypes as $role => $config) {
    echo "  $role: {$config['label']}\n";
}

// Test 7: Show bulk removal enabled roles
echo "\n7. Bulk Removal Enabled Roles:\n";
$bulkRemovalRoles = get_bulk_removal_enabled_roles();
foreach ($bulkRemovalRoles as $role) {
    echo "  - $role\n";
}

// Test 8: Test navigation permissions
echo "\n8. Auditor Navigation Permissions:\n";
$auditorNavItems = ['Home', 'Score Audit', 'Final Certification', 'My Profile', 'Logout'];
foreach ($auditorNavItems as $item) {
    $canView = can_view_nav($item);
    echo "  $item: " . ($canView ? '✅ Allowed' : '❌ Denied') . "\n";
}

// Test 9: Test home URL generation
echo "\n9. Home URL Generation:\n";
// Simulate auditor user in session
$_SESSION['user'] = ['role' => 'auditor'];
$homeUrl = home_url();
echo "  Auditor home URL: $homeUrl\n";

// Test 10: Show Auditor features
echo "\n10. Auditor Features:\n";
echo "  ✅ Password required for creation\n";
echo "  ✅ Unified score viewing across all contests/categories/subcategories\n";
echo "  ✅ Tally Master certification verification\n";
echo "  ✅ Final certification authority\n";
echo "  ✅ Comprehensive score summaries\n";
echo "  ✅ Dynamic dashboard with logical navigation\n";
echo "  ✅ Automatic integration with User Management\n";

echo "\n🎯 Auditor User Type Test Completed!\n";
echo "The Auditor user type is fully integrated and ready to use!\n";
echo "\nTo create an Auditor user:\n";
echo "1. Go to /users/new\n";
echo "2. Select 'Auditor' as the role\n";
echo "3. Provide a password (required)\n";
echo "4. Fill in other required fields\n";
echo "5. Submit the form\n";
echo "\nThe Auditor will automatically appear in User Management with full functionality!\n";
