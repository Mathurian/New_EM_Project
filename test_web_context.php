<?php
/**
 * Test script to check web application context
 * This simulates the web environment more closely
 */

// Set up web environment variables
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/board/emcee-scripts';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Test Agent';

// Start session
session_start();

// Simulate a Board user session
$_SESSION['user'] = [
    'id' => 'test-board-user-id',
    'name' => 'Test Board User',
    'role' => 'board'
];

// Simulate POST data
$_POST['title'] = 'Test Script';
$_POST['description'] = 'Test Description';
$_POST['csrf_token'] = 'test-token';

// Simulate file upload data
$_FILES['script_file'] = [
    'name' => 'test.pdf',
    'type' => 'application/pdf',
    'tmp_name' => '/tmp/test.pdf',
    'error' => UPLOAD_ERR_OK,
    'size' => 1024
];

echo "=== Web Application Context Test ===\n";

try {
    // Test 1: Load application files
    echo "1. Loading application files...\n";
    require_once __DIR__ . '/app/lib/DB.php';
    require_once __DIR__ . '/app/lib/helpers.php';
    require_once __DIR__ . '/app/lib/SecurityService.php';
    require_once __DIR__ . '/app/routes/BoardController.php';
    echo "   ✅ Application files loaded\n";
    
    // Test 2: Test helper functions
    echo "2. Testing helper functions...\n";
    $user = current_user();
    echo "   ✅ current_user(): {$user['name']} ({$user['role']})\n";
    
    $isBoard = is_board();
    echo "   ✅ is_board(): " . ($isBoard ? 'true' : 'false') . "\n";
    
    $testUuid = uuid();
    echo "   ✅ uuid(): $testUuid\n";
    
    // Test 3: Test database operations
    echo "3. Testing database operations...\n";
    $pdo = App\DB::pdo();
    
    // Test activity_logs insert (this was failing before)
    $logId = uuid();
    $stmt = $pdo->prepare("INSERT INTO activity_logs (id, user_id, user_name, user_role, action, resource_type, resource_id, details, ip_address, user_agent, log_level, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $logId,
        $user['id'],
        $user['name'],
        $user['role'],
        'test_action',
        'test',
        'test_id',
        'Testing web context',
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'],
        'info',
        date('c')
    ]);
    
    // Clean up
    $pdo->exec("DELETE FROM activity_logs WHERE id = '$logId'");
    echo "   ✅ Activity logs insert successful\n";
    
    // Test 4: Test emcee_scripts table
    echo "4. Testing emcee_scripts table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM emcee_scripts");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Emcee_scripts table accessible - $count records\n";
    
    // Test 5: Test error logging
    echo "5. Testing error logging...\n";
    error_log('Test error log from web context');
    echo "   ✅ Error logging test completed\n";
    
    echo "\n=== Web Context Test Complete ===\n";
    echo "All web application components are working correctly.\n";
    echo "The issue might be:\n";
    echo "1. File upload directory permissions\n";
    echo "2. Web server configuration\n";
    echo "3. CSRF token validation\n";
    echo "4. Route not being reached\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
