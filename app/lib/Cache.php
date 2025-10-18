<?php
declare(strict_types=1);

namespace App;

/**
 * Simple file-based cache implementation
 * Can be easily replaced with Redis/Memcached later
 */
class Cache
{
    private static string $cacheDir;
    private static int $defaultTtl = 3600; // 1 hour

    public static function init(): void
    {
        self::$cacheDir = __DIR__ . '/storage/cache/';
        if (!is_dir(self::$cacheDir)) {
            if (!mkdir(self::$cacheDir, 0755, true)) {
                error_log("Cache: Failed to create cache directory: " . self::$cacheDir);
                throw new \RuntimeException("Failed to create cache directory: " . self::$cacheDir);
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        $file = self::getFilePath($key);
        
        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));
        
        if ($data['expires'] < time()) {
            self::forget($key);
            return $default;
        }

        return $data['value'];
    }

    public static function put(string $key, $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? self::$defaultTtl;
        $file = self::getFilePath($key);
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    public static function remember(string $key, callable $callback, int $ttl = null)
    {
        $value = self::get($key);
        
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::put($key, $value, $ttl);
        
        return $value;
    }

    public static function forget(string $key): bool
    {
        $file = self::getFilePath($key);
        return file_exists($file) ? unlink($file) : true;
    }

    public static function flush(): bool
    {
        $files = glob(self::$cacheDir . '*');
        $success = true;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $success = unlink($file) && $success;
            }
        }
        
        return $success;
    }

    private static function getFilePath(string $key): string
    {
        return self::$cacheDir . md5($key) . '.cache';
    }

    public static function getStats(): array
    {
        $files = glob(self::$cacheDir . '*.cache');
        $totalSize = 0;
        $expiredCount = 0;
        $currentTime = time();

        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $data = unserialize(file_get_contents($file));
            if ($data['expires'] < $currentTime) {
                $expiredCount++;
            }
        }

        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'expired_files' => $expiredCount,
            'cache_dir' => self::$cacheDir
        ];
    }
}