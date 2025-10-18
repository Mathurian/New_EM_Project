<?php
declare(strict_types=1);

namespace App;

/**
 * Security service for headers, session management, and security enhancements
 */
class SecurityService
{
    /**
     * Set security headers
     */
    public static function setSecurityHeaders(): void
    {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
               "style-src 'self' 'unsafe-inline'; " .
               "img-src 'self' data: blob:; " .
               "font-src 'self' data:; " .
               "connect-src 'self'; " .
               "frame-ancestors 'none';";
        header("Content-Security-Policy: {$csp}");
        
        // HSTS (only in production with HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    /**
     * Enhanced session management
     */
    public static function startSecureSession(): void
    {
        // Set secure session parameters
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_lifetime', '0'); // Session cookie
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    /**
     * Validate and sanitize input with enhanced security
     */
    public static function sanitizeInput(array $data, array $rules = []): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Trim whitespace
                $value = trim($value);
                
                // Remove null bytes
                $value = str_replace("\0", '', $value);
                
                // Apply HTML rules
                if (isset($rules[$key]['html']) && !$rules[$key]['html']) {
                    $value = strip_tags($value);
                }
                
                // Apply length limits
                if (isset($rules[$key]['max_length'])) {
                    $value = substr($value, 0, $rules[$key]['max_length']);
                }
                
                // Apply specific sanitization rules
                if (isset($rules[$key]['type'])) {
                    switch ($rules[$key]['type']) {
                        case 'email':
                            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                            break;
                        case 'int':
                            $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                            break;
                        case 'float':
                            $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                            break;
                        case 'url':
                            $value = filter_var($value, FILTER_SANITIZE_URL);
                            break;
                    }
                }
                
                // SQL injection prevention (additional layer)
                $value = addslashes($value);
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }

    /**
     * Enhanced CSRF token generation
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token with timing attack protection
     */
    public static function verifyCsrfToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        // Use hash_equals for timing attack protection
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Rate limiting implementation
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 10, int $window = 300): bool
    {
        $cacheKey = "rate_limit_{$key}";
        $attempts = Cache::get($cacheKey, []);
        
        $now = time();
        $attempts = array_filter($attempts, function($timestamp) use ($now, $window) {
            return $timestamp > ($now - $window);
        });
        
        if (count($attempts) >= $maxAttempts) {
            return false;
        }
        
        $attempts[] = $now;
        Cache::put($cacheKey, $attempts, $window);
        
        return true;
    }

    /**
     * Log security events
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        Logger::warn('security_event', 'security', null, $event, $context);
    }

    /**
     * Validate file upload with enhanced security
     */
    public static function validateFileUpload(array $file, array $allowedTypes = [], int $maxSize = 5242880): array
    {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . $file['error'];
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File too large. Maximum size: ' . formatBytes($maxSize);
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes);
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Invalid file extension. Allowed: ' . implode(', ', $allowedExtensions);
        }
        
        // Check for malicious content in images
        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                $errors[] = 'Invalid image file';
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }

    /**
     * Generate secure random password
     */
    public static function generateSecurePassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}

/**
 * Helper function for formatting bytes
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}