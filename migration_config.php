<?php
declare(strict_types=1);

/**
 * Migration Configuration
 * 
 * This file contains the configuration for migrating from SQLite to PostgreSQL.
 * Modify the values below to match your environment.
 */

return [
    'source' => [
        'type' => 'sqlite',
        'path' => __DIR__ . '/app/db/contest.sqlite'
    ],
    'target' => [
        'type' => 'postgresql',
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'event_manager',
        'username' => 'event_manager',
        'password' => 'dittibop'
    ],
    'migration' => [
        'batch_size' => 1000,
        'backup_before_migration' => true,
        'validate_after_migration' => true,
        'create_rollback_script' => true
    ]
];
