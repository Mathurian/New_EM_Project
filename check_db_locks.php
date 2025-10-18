<?php
/**
 * Database Lock Checker and Cleaner
 * Checks for processes that might be locking the database and provides cleanup options
 */

require_once __DIR__ . '/app/lib/DB.php';

echo "Database Lock Checker\n";
echo "====================\n\n";

$dbPath = App\DB::getDatabasePath();
echo "Database path: " . $dbPath . "\n\n";

// Check if database file exists
if (!file_exists($dbPath)) {
    echo "❌ Database file not found at: " . $dbPath . "\n";
    exit(1);
}

echo "✅ Database file exists\n";

// Check file permissions
$perms = fileperms($dbPath);
echo "File permissions: " . substr(sprintf('%o', $perms), -4) . "\n";

if (!is_readable($dbPath)) {
    echo "❌ Database file is not readable\n";
} else {
    echo "✅ Database file is readable\n";
}

if (!is_writable($dbPath)) {
    echo "❌ Database file is not writable\n";
} else {
    echo "✅ Database file is writable\n";
}

// Check for lock files
$lockFile = $dbPath . '-wal';
$shmFile = $dbPath . '-shm';

echo "\nChecking for lock files:\n";
if (file_exists($lockFile)) {
    echo "❌ WAL file exists: " . $lockFile . "\n";
    echo "   Size: " . filesize($lockFile) . " bytes\n";
    echo "   Modified: " . date('Y-m-d H:i:s', filemtime($lockFile)) . "\n";
} else {
    echo "✅ No WAL file found\n";
}

if (file_exists($shmFile)) {
    echo "❌ SHM file exists: " . $shmFile . "\n";
    echo "   Size: " . filesize($shmFile) . " bytes\n";
    echo "   Modified: " . date('Y-m-d H:i:s', filemtime($shmFile)) . "\n";
} else {
    echo "✅ No SHM file found\n";
}

// Try to connect to database
echo "\nTesting database connection:\n";
try {
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Database query successful (users count: " . $result['count'] . ")\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

// Check for processes using the database
echo "\nChecking for processes using the database:\n";
$processes = shell_exec("lsof " . escapeshellarg($dbPath) . " 2>/dev/null");
if ($processes) {
    echo "❌ Processes found using the database:\n";
    echo $processes . "\n";
} else {
    echo "✅ No processes found using the database\n";
}

// Check for Apache/PHP processes
echo "\nChecking for Apache/PHP processes:\n";
$apacheProcesses = shell_exec("ps aux | grep -E '(apache|httpd|php)' | grep -v grep");
if ($apacheProcesses) {
    echo "❌ Apache/PHP processes found:\n";
    echo $apacheProcesses . "\n";
} else {
    echo "✅ No Apache/PHP processes found\n";
}

echo "\nRecommendations:\n";
echo "===============\n";

if (file_exists($lockFile) || file_exists($shmFile)) {
    echo "1. Remove lock files:\n";
    echo "   rm -f " . $lockFile . " " . $shmFile . "\n";
}

if ($processes) {
    echo "2. Kill processes using the database:\n";
    echo "   kill -9 <PID> (for each process listed above)\n";
}

if ($apacheProcesses) {
    echo "3. Stop Apache/PHP services:\n";
    echo "   systemctl stop apache2\n";
    echo "   systemctl stop php8.x-fpm (if using FPM)\n";
}

echo "4. After cleanup, try the migration again:\n";
echo "   php migrate_tally_master_enhanced.php\n";
