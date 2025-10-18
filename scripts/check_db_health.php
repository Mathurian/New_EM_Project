<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\DB;

echo "Database Health Check\n";
echo "====================\n\n";

// Check if database is healthy
if (DB::isHealthy()) {
    echo "✓ Database is accessible\n";
} else {
    echo "✗ Database is not accessible\n";
    exit(1);
}

// Check database file permissions
$dbPath = DB::getDatabasePath();
if (file_exists($dbPath)) {
    echo "✓ Database file exists: $dbPath\n";
    
    if (is_readable($dbPath)) {
        echo "✓ Database file is readable\n";
    } else {
        echo "✗ Database file is not readable\n";
    }
    
    if (is_writable($dbPath)) {
        echo "✓ Database file is writable\n";
    } else {
        echo "✗ Database file is not writable\n";
    }
    
    $fileSize = filesize($dbPath);
    echo "✓ Database file size: " . number_format($fileSize) . " bytes\n";
} else {
    echo "✗ Database file does not exist: $dbPath\n";
}

// Test a simple query
try {
    $pdo = DB::pdo();
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM sqlite_master WHERE type="table"');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Database has " . $result['count'] . " tables\n";
} catch (Exception $e) {
    echo "✗ Database query failed: " . $e->getMessage() . "\n";
}

// Check for WAL mode
try {
    $pdo = DB::pdo();
    $stmt = $pdo->query('PRAGMA journal_mode');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Journal mode: " . $result['journal_mode'] . "\n";
} catch (Exception $e) {
    echo "✗ Could not check journal mode: " . $e->getMessage() . "\n";
}

echo "\nDatabase health check completed.\n";