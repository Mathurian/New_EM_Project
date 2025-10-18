<?php
/**
 * Database Backup Script
 * Creates a backup of the contest database before migration
 */

require_once __DIR__ . '/app/lib/DB.php';

try {
    $dbPath = App\DB::getDatabasePath();
    $backupPath = dirname($dbPath) . '/contest_backup_' . date('Y-m-d_H-i-s') . '.sqlite';
    
    echo "Creating database backup...\n";
    echo "Source: " . $dbPath . "\n";
    echo "Backup: " . $backupPath . "\n";
    
    if (!file_exists($dbPath)) {
        echo "❌ Database file not found at: " . $dbPath . "\n";
        exit(1);
    }
    
    if (copy($dbPath, $backupPath)) {
        echo "✅ Database backup created successfully!\n";
        echo "✅ Backup saved to: " . $backupPath . "\n";
    } else {
        echo "❌ Failed to create backup\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}
