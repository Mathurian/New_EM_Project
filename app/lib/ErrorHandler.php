<?php
declare(strict_types=1);

namespace App;

/**
 * Centralized error handling service
 */
class ErrorHandler
{
    private static array $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        $errorType = self::$errorTypes[$severity] ?? 'Unknown Error';
        
        // Log the error
        Logger::error('php_error', 'system', null, 
            "{$errorType}: {$message} in {$file} on line {$line}", [
                'severity' => $severity,
                'file' => $file,
                'line' => $line,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
            ]);

        // Don't execute PHP internal error handler for non-fatal errors
        if (!(error_reporting() & $severity)) {
            return false;
        }

        // For fatal errors, show user-friendly page
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            self::renderErrorPage('Internal Server Error', 500, [
                'message' => 'An unexpected error occurred. Please try again later.',
                'error_id' => uniqid('err_', true)
            ]);
        }

        return true;
    }

    public static function handleException(\Throwable $exception): void
    {
        // Log the exception
        Logger::error('uncaught_exception', 'system', null, 
            "Uncaught exception: " . $exception->getMessage(), [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);

        // Show user-friendly error page
        self::renderErrorPage('Internal Server Error', 500, [
            'message' => 'An unexpected error occurred. Please try again later.',
            'error_id' => uniqid('err_', true)
        ]);
    }

    public static function renderErrorPage(string $title, int $code, array $context = []): void
    {
        http_response_code($code);
        
        // Set security headers
        SecurityService::setSecurityHeaders();
        
        // Render error page
        $errorData = [
            'title' => $title,
            'code' => $code,
            'message' => $context['message'] ?? 'An error occurred',
            'context' => $context
        ];
        
        // Try to render custom error page, fallback to generic
        try {
            view('errors/generic', $errorData);
        } catch (\Exception $e) {
            // Ultimate fallback - render basic HTML
            echo "<!DOCTYPE html><html><head><title>{$title}</title></head><body>";
            echo "<h1>{$code}</h1><h2>{$title}</h2><p>{$errorData['message']}</p>";
            echo "<a href='/'>Go Home</a></body></html>";
        }
        
        exit;
    }

    public static function handleValidationErrors(array $errors): void
    {
        $errorMessages = [];
        
        foreach ($errors as $field => $fieldErrors) {
            if (is_array($fieldErrors)) {
                $errorMessages[$field] = implode(', ', $fieldErrors);
            } else {
                $errorMessages[$field] = $fieldErrors;
            }
        }
        
        $_SESSION['validation_errors'] = $errorMessages;
        
        // Log validation errors
        Logger::warn('validation_errors', 'validation', null, 
            'Validation failed', ['errors' => $errorMessages]);
    }

    public static function handleDatabaseError(\PDOException $e, string $context = ''): void
    {
        $message = "Database error in {$context}: " . $e->getMessage();
        
        Logger::error('database_error', 'database', null, $message, [
            'context' => $context,
            'sql_state' => $e->getCode(),
            'error_info' => $e->errorInfo ?? []
        ]);

        // Don't expose database details to users
        self::renderErrorPage('Database Error', 500, [
            'message' => 'A database error occurred. Please try again later.',
            'error_id' => uniqid('db_err_', true)
        ]);
    }

    public static function handleFileUploadError(array $errors): void
    {
        $errorMessage = 'File upload failed: ' . implode(', ', $errors);
        
        Logger::warn('file_upload_error', 'upload', null, $errorMessage, [
            'errors' => $errors
        ]);

        $_SESSION['upload_errors'] = $errors;
    }

    public static function getValidationErrors(): array
    {
        $errors = $_SESSION['validation_errors'] ?? [];
        unset($_SESSION['validation_errors']);
        return $errors;
    }

    public static function getUploadErrors(): array
    {
        $errors = $_SESSION['upload_errors'] ?? [];
        unset($_SESSION['upload_errors']);
        return $errors;
    }

    public static function hasErrors(): bool
    {
        return !empty($_SESSION['validation_errors']) || !empty($_SESSION['upload_errors']);
    }

    public static function clearErrors(): void
    {
        unset($_SESSION['validation_errors'], $_SESSION['upload_errors']);
    }

    public static function renderJsonError(string $message, int $code = 400, array $context = []): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'error' => $message,
            'code' => $code,
            'timestamp' => date('c')
        ];
        
        if (!empty($context)) {
            $response['context'] = $context;
        }
        
        echo json_encode($response);
        exit;
    }
}