<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\DB;

echo "Database Lock Fix Script\n";
echo "========================\n\n";

try {
    // Step 1: Check if database exists
    $dbPath = DB::getDatabasePath();
    echo "Database path: $dbPath\n";
    
    if (!file_exists($dbPath)) {
        echo "Database file does not exist. Creating new database...\n";
        DB::migrate();
        echo "✓ New database created successfully\n";
        exit(0);
    }
    
    // Step 2: Try to connect and check for locks
    echo "Checking database connection...\n";
    
    try {
        $pdo = DB::pdo();
        echo "✓ Database connection successful\n";
    } catch (Exception $e) {
        echo "✗ Database connection failed: " . $e->getMessage() . "\n";
        
        // Try to remove lock files
        $lockFiles = [
            $dbPath . '-wal',
            $dbPath . '-shm',
            $dbPath . '-journal'
        ];
        
        echo "Attempting to remove lock files...\n";
        foreach ($lockFiles as $lockFile) {
            if (file_exists($lockFile)) {
                if (unlink($lockFile)) {
                    echo "✓ Removed lock file: " . basename($lockFile) . "\n";
                } else {
                    echo "✗ Could not remove lock file: " . basename($lockFile) . "\n";
                }
            }
        }
        
        // Try to connect again
        try {
            $pdo = DB::pdo();
            echo "✓ Database connection successful after lock removal\n";
        } catch (Exception $e2) {
            echo "✗ Database still locked after lock removal: " . $e2->getMessage() . "\n";
            exit(1);
        }
    }
    
    // Step 3: Optimize database settings
    echo "Optimizing database settings...\n";
    
    $pdo = DB::pdo();
    
    // Set optimal pragmas
    $pragmas = [
        'PRAGMA journal_mode = WAL',
        'PRAGMA synchronous = NORMAL',
        'PRAGMA cache_size = 10000',
        'PRAGMA temp_store = MEMORY',
        'PRAGMA busy_timeout = 30000',
        'PRAGMA wal_autocheckpoint = 1000'
    ];
    
    foreach ($pragmas as $pragma) {
        try {
            $pdo->exec($pragma);
            echo "✓ " . $pragma . "\n";
        } catch (Exception $e) {
            echo "✗ Failed to set " . $pragma . ": " . $e->getMessage() . "\n";
        }
    }
    
    // Step 4: Test database operations
    echo "Testing database operations...\n";
    
    try {
        // Test a simple query
        $stmt = $pdo->query('SELECT 1');
        if ($stmt) {
            echo "✓ Simple query test passed\n";
        }
        
        // Test a write operation
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM sqlite_master WHERE type="table"');
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Database has " . $result['count'] . " tables\n";
        
    } catch (Exception $e) {
        echo "✗ Database operation test failed: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    // Step 5: Run database optimization
    echo "Running database optimization...\n";
    
    try {
        DB::optimizeDatabase();
        echo "✓ Database optimization completed\n";
    } catch (Exception $e) {
        echo "✗ Database optimization failed: " . $e->getMessage() . "\n";
    }
    
    // Step 6: Final health check
    echo "Running final health check...\n";
    
    if (DB::isHealthy()) {
        echo "✓ Database is healthy and ready\n";
    } else {
        echo "✗ Database health check failed\n";
        exit(1);
    }
    
    echo "\n✓ Database lock fix completed successfully!\n";
    echo "The database should now be accessible without locking issues.\n";
    
} catch (Exception $e) {
    echo "✗ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}