<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\DB;

echo "Initializing Database...\n";

try {
    // Create database directory if it doesn't exist
    $dbDir = dirname(__DIR__) . '/app/db';
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
        echo "Created database directory: $dbDir\n";
    }
    
    // Initialize database with migration
    DB::migrate();
    echo "Database migration completed\n";
    
    // Optimize database
    DB::optimizeDatabase();
    echo "Database optimization completed\n";
    
    // Check health
    if (DB::isHealthy()) {
        echo "✓ Database is healthy and ready\n";
    } else {
        echo "✗ Database health check failed\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}