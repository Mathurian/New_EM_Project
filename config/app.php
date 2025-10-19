<?php
declare(strict_types=1);

/**
 * Application Configuration
 */

return [
    'app' => [
        'name' => 'Event Manager',
        'env' => 'production',
        'debug' => false,
        'url' => 'http://localhost',
        'timezone' => 'UTC',
        'locale' => 'en',
        'fallback_locale' => 'en',
    ],

    'database' => [
        'type' => 'sqlite', // 'sqlite' or 'postgresql'
        'path' => __DIR__ . '/../app/db/contest.sqlite',
        'host' => 'localhost',
        'port' => '5432',
        'name' => 'event_manager',
        'user' => 'event_manager',
        'password' => 'password',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],

    'mail' => [
        'driver' => 'smtp',
        'host' => 'localhost',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from' => [
            'address' => 'noreply@example.com',
            'name' => 'Event Manager',
        ],
    ],

    'session' => [
        'lifetime' => 120, // minutes
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => __DIR__ . '/../storage/sessions',
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => 'event_manager_session',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => true,
        'same_site' => 'lax',
    ],

    'cache' => [
        'default' => 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../storage/cache',
            ],
            'array' => [
                'driver' => 'array',
            ],
        ],
    ],

    'logging' => [
        'default' => 'file',
        'channels' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../logs/event-manager.log',
                'level' => 'debug',
                'days' => 14,
            ],
            'single' => [
                'driver' => 'single',
                'path' => __DIR__ . '/../logs/event-manager.log',
                'level' => 'debug',
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => __DIR__ . '/../logs/event-manager.log',
                'level' => 'debug',
                'days' => 14,
            ],
        ],
    ],

    'security' => [
        'csrf' => [
            'enabled' => true,
            'token_length' => 40,
            'expire_time' => 3600, // seconds
        ],
        'password' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
        ],
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
    ],

    'features' => [
        'user_registration' => true,
        'email_verification' => false,
        'password_reset' => true,
        'two_factor_auth' => false,
        'api_access' => false,
        'backup_automation' => true,
        'audit_logging' => true,
    ],

    'backup' => [
        'enabled' => true,
        'schedule' => 'daily',
        'retention_days' => 30,
        'compress' => true,
        'encrypt' => false,
        'storage' => 'local',
        'path' => __DIR__ . '/../backups',
    ],
];
