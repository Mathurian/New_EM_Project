<?php
/**
 * Add created_at column to users table
 * Migration to fix the missing created_at column issue
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/Logger.php';

echo "Adding created_at column to users table\n";
echo "=======================================\n\n";

try {
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n\n";
    
    // Check if created_at column already exists
    echo "1. Checking if created_at column exists...\n";
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasCreatedAt = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'created_at') {
            $hasCreatedAt = true;
            break;
        }
    }
    
    if ($hasCreatedAt) {
        echo "✅ created_at column already exists\n";
        echo "🎉 Migration not needed!\n";
        exit(0);
    }
    
    echo "❌ created_at column missing - proceeding with migration\n\n";
    
    // Show current table structure
    echo "2. Current users table structure:\n";
    foreach ($columns as $column) {
        $nullInfo = $column['notnull'] ? 'NOT NULL' : 'NULL';
        $keyInfo = $column['pk'] ? ' (PRIMARY KEY)' : '';
        echo "   {$column['name']}: {$column['type']} {$nullInfo}{$keyInfo}\n";
    }
    
    // Add created_at column
    echo "\n3. Adding created_at column...\n";
    $sql = "ALTER TABLE users ADD COLUMN created_at DATETIME";
    $pdo->exec($sql);
    echo "✅ created_at column added successfully\n";
    
    // Update existing records with current timestamp
    echo "\n4. Updating existing records...\n";
    $updateSql = "UPDATE users SET created_at = datetime('now') WHERE created_at IS NULL";
    $result = $pdo->exec($updateSql);
    echo "✅ Updated {$result} existing user records\n";
    
    // Verify the column was added
    echo "\n5. Verifying column addition...\n";
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasCreatedAt = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'created_at') {
            $hasCreatedAt = true;
            echo "✅ created_at column verified: {$column['type']} {$column['notnull'] ? 'NOT NULL' : 'NULL'}\n";
            break;
        }
    }
    
    if (!$hasCreatedAt) {
        echo "❌ created_at column still missing after migration\n";
        exit(1);
    }
    
    // Test the column with a sample query
    echo "\n6. Testing created_at column...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE created_at IS NOT NULL");
    $count = $stmt->fetchColumn();
    echo "✅ {$count} users have created_at timestamps\n";
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "\n📝 Summary:\n";
    echo "   ✅ created_at column added to users table\n";
    echo "   ✅ Existing records updated with timestamps\n";
    echo "   ✅ Column verified and tested\n";
    echo "\n🚀 User creation should now work properly!\n";
    
    // Log the migration
    App\Logger::info('migration', 'users_created_at_column_added', null, "Added created_at column to users table");
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Log the failure
    App\Logger::error('migration', 'users_created_at_column_failed', null, "Failed to add created_at column: " . $e->getMessage());
    exit(1);
}
