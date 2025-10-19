#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Schema Validation and Fix Script
 * 
 * This script validates and fixes schema issues before migration
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\DatabaseFactory;

echo "🔍 Schema Validation and Fix Script\n";
echo "===================================\n\n";

// Load configuration
$configFile = __DIR__ . '/migration_config.php';
$config = include $configFile;

echo "1. Testing PostgreSQL permissions...\n";

try {
    $pgDb = DatabaseFactory::createFromConfig($config['target']);
    
    // Test basic connection
    $result = $pgDb->query("SELECT version()");
    echo "   ✅ PostgreSQL connection successful\n";
    echo "   📊 Version: " . $result[0]['version'] . "\n";
    
    // Test schema permissions
    echo "   🔍 Testing schema permissions...\n";
    
    // Test CREATE permission
    try {
        $pgDb->execute("CREATE TABLE test_permissions (id SERIAL PRIMARY KEY, test_col TEXT)");
        echo "   ✅ CREATE permission: OK\n";
        
        // Clean up test table
        $pgDb->execute("DROP TABLE test_permissions");
        echo "   ✅ DROP permission: OK\n";
        
    } catch (\Exception $e) {
        echo "   ❌ CREATE permission failed: " . $e->getMessage() . "\n";
        echo "   💡 Fix with: GRANT CREATE ON SCHEMA public TO event_manager;\n";
    }
    
    // Test UUID extension
    echo "   🔍 Testing UUID extension...\n";
    try {
        $pgDb->execute("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"");
        echo "   ✅ UUID extension: OK\n";
    } catch (\Exception $e) {
        echo "   ❌ UUID extension failed: " . $e->getMessage() . "\n";
        echo "   💡 Fix with: CREATE EXTENSION \"uuid-ossp\";\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ PostgreSQL connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2. Analyzing SQLite schema...\n";

try {
    $sqliteDb = DatabaseFactory::createFromConfig($config['source']);
    $tables = $sqliteDb->getTables();
    
    echo "   📊 Found " . count($tables) . " tables\n";
    
    $schemaIssues = [];
    
    foreach ($tables as $table) {
        echo "   🔍 Analyzing table: {$table}\n";
        
        $tableInfo = $sqliteDb->getTableInfo($table);
        
        foreach ($tableInfo as $column) {
            $columnName = $column['name'];
            $columnType = $column['type'];
            $isNullable = !$column['notnull'];
            $defaultValue = $column['dflt_value'];
            
            // Check for potential issues
            $issues = [];
            
            // Check for case sensitivity issues
            if (preg_match('/[A-Z]/', $columnName)) {
                $issues[] = "Mixed case column name: {$columnName}";
            }
            
            // Check for SQLite-specific types that need conversion
            if (strtoupper($columnType) === 'TEXT' && $defaultValue === 'CURRENT_TIMESTAMP') {
                $issues[] = "SQLite CURRENT_TIMESTAMP default needs conversion";
            }
            
            // Check for boolean handling
            if (strtoupper($columnType) === 'INTEGER' && $defaultValue === '0' || $defaultValue === '1') {
                $issues[] = "SQLite boolean (0/1) needs PostgreSQL boolean conversion";
            }
            
            if (!empty($issues)) {
                $schemaIssues[$table][$columnName] = $issues;
            }
        }
    }
    
    if (!empty($schemaIssues)) {
        echo "\n   ⚠️  Potential schema issues found:\n";
        foreach ($schemaIssues as $table => $columns) {
            echo "   📋 Table: {$table}\n";
            foreach ($columns as $column => $issues) {
                echo "      Column: {$column}\n";
                foreach ($issues as $issue) {
                    echo "         - {$issue}\n";
                }
            }
        }
    } else {
        echo "   ✅ No schema issues detected\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ SQLite analysis failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n3. Testing PostgreSQL schema creation...\n";

try {
    // Test creating a sample table with common data types
    $testTable = "
        CREATE TABLE schema_test (
            id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            age INTEGER,
            score DECIMAL(10,2),
            is_active BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $pgDb->execute($testTable);
    echo "   ✅ Sample table creation: OK\n";
    
    // Test inserting data
    $pgDb->execute("INSERT INTO schema_test (name, email, age, score, is_active) VALUES (?, ?, ?, ?, ?)", 
        ['Test User', 'test@example.com', 25, 85.5, true]);
    echo "   ✅ Sample data insertion: OK\n";
    
    // Test querying data
    $result = $pgDb->query("SELECT * FROM schema_test WHERE name = ?", ['Test User']);
    if (!empty($result)) {
        echo "   ✅ Sample data query: OK\n";
    }
    
    // Clean up
    $pgDb->execute("DROP TABLE schema_test");
    echo "   ✅ Sample table cleanup: OK\n";
    
} catch (\Exception $e) {
    echo "   ❌ PostgreSQL schema test failed: " . $e->getMessage() . "\n";
    echo "   💡 This indicates schema compatibility issues\n";
}

echo "\n4. Checking for common migration issues...\n";

// Check for reserved words
$reservedWords = ['user', 'group', 'order', 'select', 'from', 'where'];
$problematicTables = [];

foreach ($tables as $table) {
    if (in_array(strtolower($table), $reservedWords)) {
        $problematicTables[] = $table;
    }
}

if (!empty($problematicTables)) {
    echo "   ⚠️  Tables with reserved word names:\n";
    foreach ($problematicTables as $table) {
        echo "      - {$table} (may need quotes in PostgreSQL)\n";
    }
} else {
    echo "   ✅ No reserved word conflicts\n";
}

echo "\n🎉 Schema validation completed!\n";

if (!empty($schemaIssues) || !empty($problematicTables)) {
    echo "\n⚠️  Issues found that should be addressed before migration:\n";
    echo "   1. Review the schema issues above\n";
    echo "   2. Fix PostgreSQL permissions if needed\n";
    echo "   3. Consider updating the migration scripts\n";
} else {
    echo "\n✅ Schema validation passed! Ready for migration.\n";
    echo "💡 You can now run: php migrate.php --migrate\n";
}
