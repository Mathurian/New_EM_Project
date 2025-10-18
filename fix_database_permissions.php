<?php
/**
 * Fix Database Permissions Script
 * 
 * This script addresses the recurring "attempt to write a readonly database" error
 * by ensuring proper permissions on the database file and directory.
 */

echo "=== Database Permissions Fix ===\n";

// Database file path
$dbPath = __DIR__ . '/app/db/contest.sqlite';
$dbDir = dirname($dbPath);

echo "1. Checking database file and directory...\n";

// Check if database file exists
if (!file_exists($dbPath)) {
    echo "❌ Database file not found at: $dbPath\n";
    exit(1);
}

echo "✅ Database file exists: $dbPath\n";

// Check current permissions
$currentPerms = fileperms($dbPath);
$currentDirPerms = fileperms($dbDir);

echo "Current database file permissions: " . decoct($currentPerms & 0777) . "\n";
echo "Current directory permissions: " . decoct($currentDirPerms & 0777) . "\n";

echo "\n2. Setting proper permissions...\n";

// Set directory permissions (755 = rwxr-xr-x)
if (chmod($dbDir, 0755)) {
    echo "✅ Directory permissions set to 0755\n";
} else {
    echo "❌ Failed to set directory permissions\n";
}

// Set database file permissions (664 = rw-rw-r--)
if (chmod($dbPath, 0664)) {
    echo "✅ Database file permissions set to 0664\n";
} else {
    echo "❌ Failed to set database file permissions\n";
}

echo "\n3. Checking ownership...\n";

// Get current ownership
$owner = posix_getpwuid(fileowner($dbPath));
$group = posix_getgrgid(filegroup($dbPath));

echo "Database file owner: " . ($owner['name'] ?? 'unknown') . "\n";
echo "Database file group: " . ($group['name'] ?? 'unknown') . "\n";

// Try to set ownership to www-data (common web server user)
$webUser = 'www-data';
$webGroup = 'www-data';

if (function_exists('posix_getpwnam') && posix_getpwnam($webUser)) {
    if (chown($dbPath, $webUser)) {
        echo "✅ Database file ownership set to $webUser\n";
    } else {
        echo "⚠️  Could not change ownership to $webUser (may need sudo)\n";
    }
    
    if (chgrp($dbPath, $webGroup)) {
        echo "✅ Database file group set to $webGroup\n";
    } else {
        echo "⚠️  Could not change group to $webGroup (may need sudo)\n";
    }
} else {
    echo "⚠️  Web user '$webUser' not found, skipping ownership change\n";
}

echo "\n4. Testing database write access...\n";

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test write access
    $stmt = $pdo->prepare('SELECT 1 as test');
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result && $result['test'] == 1) {
        echo "✅ Database read access confirmed\n";
    } else {
        echo "❌ Database read access failed\n";
    }
    
    // Test write access by creating a temporary table
    $pdo->exec('CREATE TEMPORARY TABLE test_write (id INTEGER)');
    $pdo->exec('INSERT INTO test_write (id) VALUES (1)');
    $stmt = $pdo->query('SELECT COUNT(*) FROM test_write');
    $count = $stmt->fetchColumn();
    
    if ($count == 1) {
        echo "✅ Database write access confirmed\n";
    } else {
        echo "❌ Database write access failed\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database test failed: " . $e->getMessage() . "\n";
}

echo "\n5. Checking for lock files...\n";

// Check for SQLite lock files
$lockFiles = [
    $dbPath . '-journal',
    $dbPath . '-wal',
    $dbPath . '-shm'
];

foreach ($lockFiles as $lockFile) {
    if (file_exists($lockFile)) {
        echo "⚠️  Found lock file: " . basename($lockFile) . "\n";
        if (unlink($lockFile)) {
            echo "✅ Removed lock file: " . basename($lockFile) . "\n";
        } else {
            echo "❌ Could not remove lock file: " . basename($lockFile) . "\n";
        }
    }
}

echo "\n6. Final verification...\n";

// Check final permissions
$finalPerms = fileperms($dbPath);
$finalDirPerms = fileperms($dbDir);

echo "Final database file permissions: " . decoct($finalPerms & 0777) . "\n";
echo "Final directory permissions: " . decoct($finalDirPerms & 0777) . "\n";

// Test if file is writable
if (is_writable($dbPath)) {
    echo "✅ Database file is writable\n";
} else {
    echo "❌ Database file is NOT writable\n";
}

if (is_writable($dbDir)) {
    echo "✅ Database directory is writable\n";
} else {
    echo "❌ Database directory is NOT writable\n";
}

echo "\n=== Fix Complete ===\n";
echo "If issues persist, you may need to run this script with sudo:\n";
echo "sudo php fix_database_permissions.php\n";
