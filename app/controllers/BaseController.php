<?php
declare(strict_types=1);

namespace App\Controllers;

use function App\{view, render, redirect, param, post, request_array, current_user, is_logged_in, is_organizer, is_judge, is_emcee, require_login, require_organizer, require_emcee, csrf_field, require_csrf, secure_file_upload, paginate, pagination_links, validate_input, sanitize_input, get_user_validation_rules, handle_error, handle_validation_errors, handle_database_error};
use App\{DB, Logger, Cache, DatabaseService, SecurityService, PaginationService};

/**
 * Base controller with common functionality
 */
abstract class BaseController
{
    protected function requireAuth(): void
    {
        require_login();
    }

    protected function requireOrganizer(): void
    {
        require_organizer();
    }

    protected function requireJudge(): void
    {
        require_judge();
    }

    protected function requireEmcee(): void
    {
        require_emcee();
    }

    protected function validateCsrf(): void
    {
        require_csrf();
    }

    protected function sanitizeInput(array $data, array $rules = []): array
    {
        return SecurityService::sanitizeInput($data, $rules);
    }

    protected function validateInput(array $data, array $rules): array
    {
        return validate_input($data, $rules);
    }

    protected function handleError(string $message, int $code = 500, array $context = []): void
    {
        Logger::error('controller_error', 'controller', null, $message, $context);
        handle_error($message, $code);
    }

    protected function handleValidationErrors(array $errors): void
    {
        handle_validation_errors($errors);
    }

    protected function handleDatabaseError(\PDOException $e, string $context = ''): void
    {
        handle_database_error($e, $context);
    }

    protected function logAction(string $action, string $entity, ?string $entityId, string $message): void
    {
        Logger::logAdminAction($action, $entity, $entityId, $message);
    }

    protected function logDebug(string $action, string $entity, ?string $entityId, string $message): void
    {
        Logger::debug($action, $entity, $entityId, $message);
    }

    protected function redirectWithMessage(string $url, string $message, string $type = 'success'): void
    {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        redirect($url);
    }

    protected function getFlashMessage(): ?array
    {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'info';
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return ['message' => $message, 'type' => $type];
        }
        return null;
    }

    protected function generateUuid(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function checkRateLimit(string $key, int $maxAttempts = 10, int $window = 300): bool
    {
        return SecurityService::checkRateLimit($key, $maxAttempts, $window);
    }

    protected function renderView(string $view, array $data = []): void
    {
        $data['flash'] = $this->getFlashMessage();
        view($view, $data);
    }

    protected function renderJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function renderError(string $message, int $code = 400): void
    {
        $this->renderJson(['error' => $message], $code);
    }

    protected function getPaginationParams(): array
    {
        return [
            'page' => max(1, (int)param('page', 1)),
            'per_page' => max(1, min(100, (int)param('per_page', 20)))
        ];
    }

    protected function getSearchParams(): array
    {
        return [
            'search' => trim(param('search', '')),
            'role' => param('role', ''),
            'status' => param('status', ''),
            'sort' => param('sort', ''),
            'order' => param('order', 'asc')
        ];
    }
}