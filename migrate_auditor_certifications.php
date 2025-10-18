<?php
/**
 * Create Auditor Certification Table Migration
 * Creates the auditor_certifications table for tracking final certifications
 */

require_once __DIR__ . '/app/lib/DB.php';

echo "Creating Auditor Certification Table\n";
echo "====================================\n\n";

try {
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n";
    
    // Create auditor_certifications table
    $sql = "
        CREATE TABLE IF NOT EXISTS auditor_certifications (
            id VARCHAR(32) PRIMARY KEY,
            auditor_id VARCHAR(32) NOT NULL,
            certified_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (auditor_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE (auditor_id)
        )
    ";
    
    $pdo->exec($sql);
    echo "✅ auditor_certifications table created successfully\n";
    
    // Verify table structure (SQLite compatible)
    $stmt = $pdo->query("PRAGMA table_info(auditor_certifications)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nTable structure:\n";
    foreach ($columns as $column) {
        $nullInfo = $column['notnull'] ? 'NOT NULL' : 'NULL';
        $keyInfo = $column['pk'] ? ' (PRIMARY KEY)' : '';
        echo "  {$column['name']}: {$column['type']} {$nullInfo}{$keyInfo}\n";
    }
    
    echo "\n🎉 Auditor certification table migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
