<?php
// Simple database unlock script
// Run this to try to unlock the database without stopping Apache

require_once __DIR__ . '/app/lib/DB.php';

echo "=== Simple Database Unlock Attempt ===\n\n";

try {
    $pdo = App\DB::pdo();
    
    echo "Attempting to unlock database...\n";
    
    // Try various unlock methods
    $methods = [
        'PRAGMA busy_timeout = 30000',
        'PRAGMA journal_mode = WAL',
        'PRAGMA synchronous = NORMAL',
        'PRAGMA cache_size = 10000',
        'PRAGMA temp_store = MEMORY'
    ];
    
    foreach ($methods as $method) {
        try {
            $pdo->exec($method);
            echo "✓ Executed: $method\n";
        } catch (PDOException $e) {
            echo "✗ Failed: $method - " . $e->getMessage() . "\n";
        }
    }
    
    // Try to test if database is accessible
    echo "\nTesting database access...\n";
    try {
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Database is accessible. Found {$result['count']} users.\n";
        
        // Try a simple insert/delete test
        $testId = 'unlock_test_' . time();
        $pdo->exec("INSERT INTO users (id, name, email, password_hash, role) VALUES ('$testId', 'Test', 'test@unlock.com', 'test', 'organizer')");
        $pdo->exec("DELETE FROM users WHERE id = '$testId'");
        echo "✓ Database is writable.\n";
        
        echo "\nDatabase appears to be unlocked and working.\n";
        echo "You can now try running: php fix_constraint_enhanced.php\n";
        
    } catch (PDOException $e) {
        echo "✗ Database test failed: " . $e->getMessage() . "\n";
        
        if (strpos($e->getMessage(), 'database is locked') !== false) {
            echo "\nDatabase is still locked. Try one of these options:\n";
            echo "1. Run: sudo ./unlock_and_fix.sh (stops Apache temporarily)\n";
            echo "2. Stop Apache manually: sudo systemctl stop apache2\n";
            echo "3. Wait a few minutes and try again\n";
            echo "4. Check for long-running processes: ps aux | grep php\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nTry running: sudo ./unlock_and_fix.sh\n";
}
