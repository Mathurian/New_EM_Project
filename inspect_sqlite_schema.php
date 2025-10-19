#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * SQLite Schema Inspector
 * 
 * This script inspects the actual SQLite database schema to understand
 * the real table structures before creating PostgreSQL tables.
 */

$sqliteFile = '/var/www/html/app/db/contest.sqlite';

try {
    echo "🔍 Inspecting SQLite Database Schema\n";
    echo "=====================================\n\n";
    
    $sqlite = new PDO("sqlite:$sqliteFile");
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    $tablesQuery = "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name";
    $tablesStmt = $sqlite->query($tablesQuery);
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $tableName) {
        echo "📋 Table: $tableName\n";
        echo str_repeat("-", 50) . "\n";
        
        // Get table schema
        $schemaQuery = "PRAGMA table_info($tableName)";
        $schemaStmt = $sqlite->query($schemaQuery);
        $columns = $schemaStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            $nullable = $column['notnull'] ? 'NOT NULL' : 'NULL';
            $default = $column['dflt_value'] ? " DEFAULT '{$column['dflt_value']}'" : '';
            $pk = $column['pk'] ? ' PRIMARY KEY' : '';
            
            echo "  {$column['name']} {$column['type']} $nullable$default$pk\n";
        }
        
        // Get row count
        $countQuery = "SELECT COUNT(*) FROM $tableName";
        $countStmt = $sqlite->query($countQuery);
        $rowCount = $countStmt->fetchColumn();
        echo "  📊 Row count: $rowCount\n";
        
        echo "\n";
    }
    
    echo "✅ Schema inspection complete!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
