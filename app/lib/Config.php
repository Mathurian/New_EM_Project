<?php
declare(strict_types=1);

namespace App;

/**
 * Application Configuration Manager
 */
class Config {
    private static array $config = [];
    private static bool $initialized = false;

    /**
     * Initialize configuration
     */
    public static function init(): void {
        if (self::$initialized) {
            return;
        }

        // Load configuration from file
        $configFile = __DIR__ . '/../config/app.php';
        if (file_exists($configFile)) {
            self::$config = include $configFile;
        }

        // Load environment-specific configuration
        $envConfigFile = __DIR__ . '/../config/' . (self::get('app.env', 'production')) . '.php';
        if (file_exists($envConfigFile)) {
            $envConfig = include $envConfigFile;
            self::$config = array_merge_recursive(self::$config, $envConfig);
        }

        // Override with environment variables
        self::loadFromEnvironment();

        self::$initialized = true;
    }

    /**
     * Load configuration from environment variables
     */
    private static function loadFromEnvironment(): void {
        $envMappings = [
            'APP_ENV' => 'app.env',
            'APP_DEBUG' => 'app.debug',
            'APP_URL' => 'app.url',
            'DB_TYPE' => 'database.type',
            'DB_HOST' => 'database.host',
            'DB_PORT' => 'database.port',
            'DB_NAME' => 'database.name',
            'DB_USER' => 'database.user',
            'DB_PASSWORD' => 'database.password',
            'DB_PATH' => 'database.path',
            'MAIL_HOST' => 'mail.host',
            'MAIL_PORT' => 'mail.port',
            'MAIL_USERNAME' => 'mail.username',
            'MAIL_PASSWORD' => 'mail.password',
            'MAIL_ENCRYPTION' => 'mail.encryption',
            'MAIL_FROM_ADDRESS' => 'mail.from.address',
            'MAIL_FROM_NAME' => 'mail.from.name',
        ];

        foreach ($envMappings as $envKey => $configKey) {
            if (isset($_ENV[$envKey])) {
                $value = $_ENV[$envKey];
                
                // Convert string booleans
                if ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                }
                
                self::set($configKey, $value);
            }
        }
    }

    /**
     * Get configuration value
     */
    public static function get(string $key, mixed $default = null): mixed {
        self::init();
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    /**
     * Set configuration value
     */
    public static function set(string $key, mixed $value): void {
        self::init();
        
        $keys = explode('.', $key);
        $config = &self::$config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }

    /**
     * Get database configuration
     */
    public static function getDatabaseConfig(): array {
        $type = self::get('database.type', 'sqlite');
        
        if ($type === 'postgresql') {
            return [
                'type' => 'postgresql',
                'host' => self::get('database.host', 'localhost'),
                'port' => self::get('database.port', '5432'),
                'dbname' => self::get('database.name', 'event_manager'),
                'username' => self::get('database.user', 'event_manager'),
                'password' => self::get('database.password', 'password')
            ];
        } else {
            return [
                'type' => 'sqlite',
                'path' => self::get('database.path', __DIR__ . '/../db/contest.sqlite')
            ];
        }
    }

    /**
     * Get mail configuration
     */
    public static function getMailConfig(): array {
        return [
            'host' => self::get('mail.host', 'localhost'),
            'port' => self::get('mail.port', 587),
            'username' => self::get('mail.username', ''),
            'password' => self::get('mail.password', ''),
            'encryption' => self::get('mail.encryption', 'tls'),
            'from' => [
                'address' => self::get('mail.from.address', 'noreply@example.com'),
                'name' => self::get('mail.from.name', 'Event Manager')
            ]
        ];
    }

    /**
     * Check if application is in debug mode
     */
    public static function isDebug(): bool {
        return self::get('app.debug', false);
    }

    /**
     * Get application environment
     */
    public static function getEnvironment(): string {
        return self::get('app.env', 'production');
    }

    /**
     * Check if application is in production
     */
    public static function isProduction(): bool {
        return self::getEnvironment() === 'production';
    }

    /**
     * Check if application is in development
     */
    public static function isDevelopment(): bool {
        return self::getEnvironment() === 'development';
    }

    /**
     * Get all configuration
     */
    public static function all(): array {
        self::init();
        return self::$config;
    }

    /**
     * Check if configuration key exists
     */
    public static function has(string $key): bool {
        self::init();
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }
        
        return true;
    }
}
