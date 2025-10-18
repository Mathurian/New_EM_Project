<?php
/**
 * Test script to verify Board upload route is working
 * This simulates the upload process without actual file handling
 */

// Start session to simulate logged-in user
session_start();

// Simulate a Board user session
$_SESSION['user'] = [
    'id' => 'test-board-user-id',
    'name' => 'Test Board User',
    'role' => 'board'
];

echo "=== Board Upload Route Test ===\n";

try {
    // Test 1: Check if we can load the required files
    echo "1. Testing file includes...\n";
    require_once __DIR__ . '/app/lib/DB.php';
    require_once __DIR__ . '/app/lib/helpers.php';
    require_once __DIR__ . '/app/routes/BoardController.php';
    echo "   ✅ All required files loaded successfully\n";
    
    // Test 2: Test database connection
    echo "2. Testing database connection...\n";
    $pdo = App\DB::pdo();
    echo "   ✅ Database connection successful\n";
    
    // Test 3: Test current_user() function
    echo "3. Testing current_user() function...\n";
    $user = current_user();
    if ($user && $user['role'] === 'board') {
        echo "   ✅ current_user() working - User: {$user['name']}, Role: {$user['role']}\n";
    } else {
        echo "   ❌ current_user() not working properly\n";
        exit(1);
    }
    
    // Test 4: Test uuid() function
    echo "4. Testing uuid() function...\n";
    $testUuid = uuid();
    if ($testUuid && strlen($testUuid) > 10) {
        echo "   ✅ uuid() working - Generated: $testUuid\n";
    } else {
        echo "   ❌ uuid() not working properly\n";
        exit(1);
    }
    
    // Test 5: Test emcee_scripts table structure
    echo "5. Testing emcee_scripts table structure...\n";
    $stmt = $pdo->query("PRAGMA table_info(emcee_scripts)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✅ Emcee_scripts table has " . count($columns) . " columns:\n";
    foreach ($columns as $column) {
        echo "   - {$column['name']} ({$column['type']})\n";
    }
    
    // Test 6: Test a simple insert (without file data)
    echo "6. Testing database insert (simulation)...\n";
    $testId = uuid();
    $testFilename = 'test_script.pdf';
    $testTitle = 'Test Script';
    $testDescription = 'Test Description';
    $testOriginalFilename = 'test.pdf';
    $testFileSize = 1024;
    $testUploadedAt = date('Y-m-d H:i:s');
    
    $insertValues = [
        $testId,
        $testFilename,
        '/uploads/emcee-scripts/' . $testFilename,
        1,
        date('Y-m-d H:i:s'),
        $user['id'],
        $testTitle,
        $testDescription,
        $testOriginalFilename,
        $testFileSize,
        $testUploadedAt
    ];
    
    $stmt = $pdo->prepare('INSERT INTO emcee_scripts (id, filename, file_path, is_active, created_at, uploaded_by, title, description, file_name, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute($insertValues);
    
    // Clean up test record
    $pdo->exec("DELETE FROM emcee_scripts WHERE id = '$testId'");
    echo "   ✅ Database insert test successful\n";
    
    echo "\n=== All Tests Passed ===\n";
    echo "The Board upload functionality should be working.\n";
    echo "If you're still getting errors, the issue might be:\n";
    echo "1. Web server configuration\n";
    echo "2. File upload permissions\n";
    echo "3. Session handling in web context\n";
    echo "4. Route not reaching the BoardController\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
