<?php
require_once __DIR__ . '/app/bootstrap.php';

echo "=== Auditor Session Debug ===\n\n";

// Check if user is logged in
if (!is_logged_in()) {
    echo "❌ No user logged in\n";
    exit;
}

$user = current_user();
echo "✅ User logged in:\n";
echo "   ID: " . $user['id'] . "\n";
echo "   Name: " . $user['name'] . "\n";
echo "   Role: " . $user['role'] . "\n";
echo "   Email: " . $user['email'] . "\n\n";

// Test role checking functions
echo "=== Role Check Functions ===\n";
echo "is_auditor(): " . (is_auditor() ? 'true' : 'false') . "\n";
echo "is_organizer(): " . (is_organizer() ? 'true' : 'false') . "\n";
echo "is_tally_master(): " . (is_tally_master() ? 'true' : 'false') . "\n\n";

// Test home_url function
echo "=== Home URL Test ===\n";
echo "home_url(): " . home_url() . "\n";
echo "Expected for auditor: " . url('/auditor') . "\n\n";

// Test session data
echo "=== Session Data ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session data:\n";
foreach ($_SESSION as $key => $value) {
    if (is_array($value)) {
        echo "  $key: " . json_encode($value) . "\n";
    } else {
        echo "  $key: $value\n";
    }
}
