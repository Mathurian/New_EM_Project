<?php
declare(strict_types=1);

/**
 * Production Environment Configuration
 */

return [
    'app' => [
        'debug' => false,
        'url' => 'https://your-domain.com',
    ],

    'database' => [
        'type' => 'postgresql', // Use PostgreSQL in production
        'host' => 'localhost',
        'port' => '5432',
        'name' => 'event_manager',
        'user' => 'event_manager',
        'password' => 'secure_password',
    ],

    'mail' => [
        'host' => 'smtp.your-domain.com',
        'port' => 587,
        'username' => 'noreply@your-domain.com',
        'password' => 'secure_email_password',
        'encryption' => 'tls',
        'from' => [
            'address' => 'noreply@your-domain.com',
            'name' => 'Event Manager',
        ],
    ],

    'session' => [
        'secure' => true,
        'http_only' => true,
        'same_site' => 'strict',
    ],

    'logging' => [
        'channels' => [
            'file' => [
                'level' => 'info',
            ],
        ],
    ],

    'security' => [
        'csrf' => [
            'enabled' => true,
        ],
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => 3,
            'decay_minutes' => 30,
        ],
    ],

    'features' => [
        'debug_toolbar' => false,
        'query_logging' => false,
        'error_reporting' => false,
    ],

    'backup' => [
        'enabled' => true,
        'schedule' => 'daily',
        'retention_days' => 90,
        'compress' => true,
        'encrypt' => true,
    ],
];
