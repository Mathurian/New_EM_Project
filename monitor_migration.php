#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Migration Progress Monitor
 * 
 * Run this in a separate terminal to monitor migration progress
 * Usage: php monitor_migration.php
 */

echo "🔍 Migration Progress Monitor\n";
echo "============================\n\n";

$logFile = __DIR__ . '/logs/event-manager.log';
$lastSize = 0;

echo "Monitoring log file: {$logFile}\n";
echo "Press Ctrl+C to stop monitoring\n\n";

if (!file_exists($logFile)) {
    echo "❌ Log file not found. Make sure the migration is running.\n";
    exit(1);
}

echo "📊 Starting to monitor migration progress...\n\n";

while (true) {
    if (file_exists($logFile)) {
        $currentSize = filesize($logFile);
        
        if ($currentSize > $lastSize) {
            // Read new content
            $handle = fopen($logFile, 'r');
            fseek($handle, $lastSize);
            $newContent = fread($handle, $currentSize - $lastSize);
            fclose($handle);
            
            // Display new content
            $lines = explode("\n", trim($newContent));
            foreach ($lines as $line) {
                if (!empty($line)) {
                    $timestamp = date('H:i:s');
                    echo "[{$timestamp}] {$line}\n";
                }
            }
            
            $lastSize = $currentSize;
        }
    }
    
    // Check if migration process is still running
    $processes = shell_exec('ps aux | grep "migrate.php" | grep -v grep');
    if (empty($processes)) {
        echo "\n✅ Migration process completed or stopped.\n";
        break;
    }
    
    sleep(1);
}

echo "\n📋 Final log file size: " . number_format(filesize($logFile)) . " bytes\n";
echo "🎉 Monitoring complete!\n";
