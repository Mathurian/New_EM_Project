<?php
/**
 * Quick Database Path and Permissions Check
 */

echo "=== Database Path and Permissions Check ===\n";

// Check the actual database path used by the application
require_once __DIR__ . '/app/lib/DB.php';

$dbPath = \App\DB::getDatabasePath();
$dbDir = dirname($dbPath);

echo "Database path: $dbPath\n";
echo "Database directory: $dbDir\n\n";

echo "1. File existence check:\n";
if (file_exists($dbPath)) {
    echo "✅ Database file exists\n";
} else {
    echo "❌ Database file does NOT exist\n";
}

if (is_dir($dbDir)) {
    echo "✅ Database directory exists\n";
} else {
    echo "❌ Database directory does NOT exist\n";
}

echo "\n2. Permissions check:\n";
if (file_exists($dbPath)) {
    $perms = fileperms($dbPath);
    echo "Database file permissions: " . decoct($perms & 0777) . "\n";
    echo "Database file is readable: " . (is_readable($dbPath) ? "✅ YES" : "❌ NO") . "\n";
    echo "Database file is writable: " . (is_writable($dbPath) ? "✅ YES" : "❌ NO") . "\n";
}

if (is_dir($dbDir)) {
    $dirPerms = fileperms($dbDir);
    echo "Database directory permissions: " . decoct($dirPerms & 0777) . "\n";
    echo "Database directory is readable: " . (is_readable($dbDir) ? "✅ YES" : "❌ NO") . "\n";
    echo "Database directory is writable: " . (is_writable($dbDir) ? "✅ YES" : "❌ NO") . "\n";
}

echo "\n3. Ownership check:\n";
if (file_exists($dbPath)) {
    $owner = posix_getpwuid(fileowner($dbPath));
    $group = posix_getgrgid(filegroup($dbPath));
    echo "Database file owner: " . ($owner['name'] ?? 'unknown') . "\n";
    echo "Database file group: " . ($group['name'] ?? 'unknown') . "\n";
}

echo "\n4. Database connection test:\n";
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test read access
    $stmt = $pdo->query('SELECT 1 as test');
    $result = $stmt->fetch();
    echo "Database read access: " . ($result && $result['test'] == 1 ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    
    // Test write access
    $pdo->exec('CREATE TEMPORARY TABLE test_write (id INTEGER)');
    $pdo->exec('INSERT INTO test_write (id) VALUES (1)');
    $stmt = $pdo->query('SELECT COUNT(*) FROM test_write');
    $count = $stmt->fetchColumn();
    echo "Database write access: " . ($count == 1 ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
