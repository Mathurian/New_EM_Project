#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Table Comparison Script
 * 
 * This script compares SQLite tables with the migration order to find missing tables
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\DB;
use App\DatabaseFactory;

echo "🔍 Table Comparison Script\n";
echo "=========================\n\n";

try {
    // Initialize database connection
    $config = require __DIR__ . '/migration_config.php';
    $sourceDb = DatabaseFactory::createFromConfig($config['source']);
    
    echo "1. Getting all tables from SQLite database...\n";
    $sqliteTables = $sourceDb->getTables();
    $sqliteTableNames = array_column($sqliteTables, 'name');
    
    echo "   Found " . count($sqliteTableNames) . " tables:\n";
    foreach ($sqliteTableNames as $table) {
        echo "   - $table\n";
    }
    
    echo "\n2. Getting migration table order...\n";
    $migrationOrder = [
        'users',
        'contests',
        'categories',
        'subcategories',
        'contestants',
        'judges',
        'criteria',
        'emcee_scripts',
        'system_settings',
        'backup_settings',
        'activity_logs',
        'backup_logs',
        'category_contestants',
        'category_judges',
        'subcategory_contestants',
        'subcategory_judges',
        'scores',
        'judge_comments',
        'judge_certifications',
        'tally_master_certifications',
        'auditor_certifications',
        'judge_score_removal_requests',
        'overall_deductions',
        'subcategory_templates',
        'template_criteria',
        'archived_contests',
        'archived_categories',
        'archived_subcategories',
        'archived_contestants',
        'archived_judges',
        'archived_criteria',
        'archived_emcee_scripts',
        'archived_category_contestants',
        'archived_category_judges',
        'archived_subcategory_contestants',
        'archived_subcategory_judges',
        'archived_scores',
        'archived_judge_comments',
        'archived_judge_certifications',
        'archived_overall_deductions',
    ];
    
    echo "   Migration order has " . count($migrationOrder) . " tables:\n";
    foreach ($migrationOrder as $table) {
        echo "   - $table\n";
    }
    
    echo "\n3. Comparing tables...\n";
    $missingFromOrder = array_diff($sqliteTableNames, $migrationOrder);
    $missingFromSqlite = array_diff($migrationOrder, $sqliteTableNames);
    
    if (!empty($missingFromOrder)) {
        echo "   ❌ Tables in SQLite but missing from migration order:\n";
        foreach ($missingFromOrder as $table) {
            echo "   - $table\n";
        }
    } else {
        echo "   ✅ All SQLite tables are in migration order\n";
    }
    
    if (!empty($missingFromSqlite)) {
        echo "   ⚠️  Tables in migration order but missing from SQLite:\n";
        foreach ($missingFromSqlite as $table) {
            echo "   - $table\n";
        }
    } else {
        echo "   ✅ All migration order tables exist in SQLite\n";
    }
    
    echo "\n4. Recommended migration order:\n";
    $recommendedOrder = array_intersect($sqliteTableNames, $migrationOrder);
    $recommendedOrder = array_merge($recommendedOrder, $missingFromOrder);
    
    foreach ($recommendedOrder as $table) {
        echo "   - $table\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Table comparison completed!\n";
