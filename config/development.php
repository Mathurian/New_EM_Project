<?php
declare(strict_types=1);

/**
 * Development Environment Configuration
 */

return [
    'app' => [
        'debug' => true,
        'url' => 'http://localhost:8000',
    ],

    'database' => [
        'type' => 'sqlite', // Start with SQLite for development
        'path' => __DIR__ . '/../app/db/contest.sqlite',
    ],

    'logging' => [
        'channels' => [
            'file' => [
                'level' => 'debug',
            ],
        ],
    ],

    'features' => [
        'debug_toolbar' => true,
        'query_logging' => true,
        'error_reporting' => true,
    ],
];
