<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\DB;

echo "Database Unlock Script\n";
echo "======================\n\n";

try {
    $dbPath = DB::getDatabasePath();
    echo "Database path: $dbPath\n";
    
    if (!file_exists($dbPath)) {
        echo "Database file does not exist. Nothing to unlock.\n";
        exit(0);
    }
    
    // Check if database is locked
    $pdo = DB::pdo();
    echo "✓ Database connection successful\n";
    
    // Set WAL mode to reduce locking
    $pdo->exec('PRAGMA journal_mode = WAL');
    echo "✓ Set journal mode to WAL\n";
    
    // Set busy timeout
    $pdo->exec('PRAGMA busy_timeout = 30000');
    echo "✓ Set busy timeout to 30 seconds\n";
    
    // Test a simple query
    $stmt = $pdo->query('SELECT 1');
    if ($stmt) {
        echo "✓ Database is accessible and not locked\n";
    }
    
    // Optimize database
    DB::optimizeDatabase();
    echo "✓ Database optimization completed\n";
    
    echo "\nDatabase unlock completed successfully.\n";
    
} catch (Exception $e) {
    echo "Error unlocking database: " . $e->getMessage() . "\n";
    
    // Try to remove any lock files
    $dbPath = DB::getDatabasePath();
    $lockFiles = [
        $dbPath . '-wal',
        $dbPath . '-shm',
        $dbPath . '-journal'
    ];
    
    foreach ($lockFiles as $lockFile) {
        if (file_exists($lockFile)) {
            if (unlink($lockFile)) {
                echo "Removed lock file: $lockFile\n";
            } else {
                echo "Could not remove lock file: $lockFile\n";
            }
        }
    }
    
    exit(1);
}