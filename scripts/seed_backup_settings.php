<?php
/**
 * Migration script to seed backup_settings table
 * Run this once to add default backup settings to existing installations
 */

require_once __DIR__ . '/app/bootstrap.php';

try {
    $pdo = App\DB::pdo();
    
    echo "Checking backup_settings table...\n";
    
    // Check if backup_settings table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='backup_settings'");
    if (!$stmt->fetch()) {
        echo "ERROR: backup_settings table does not exist. Please run the main migration first.\n";
        exit(1);
    }
    
    // Check if backup settings already exist
    $stmt = $pdo->query('SELECT COUNT(*) FROM backup_settings');
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "Backup settings already exist ($count records). No action needed.\n";
        exit(0);
    }
    
    echo "Seeding default backup settings...\n";
    
    // Insert default backup settings
    $stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, retention_days) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([\App\uuid(), 'schema', 0, 'daily', 30]);
    $stmt->execute([\App\uuid(), 'full', 0, 'weekly', 30]);
    
    echo "Successfully seeded backup settings!\n";
    echo "- Schema backup: disabled, daily frequency, 30-day retention\n";
    echo "- Full backup: disabled, weekly frequency, 30-day retention\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
