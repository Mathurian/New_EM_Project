<?php
declare(strict_types=1);
namespace App;

function view(string $template, array $data = []): void {
	extract($data);
	// Ensure template variable is available to layout and not overwritten by extract()
	$templateName = $template;
	include __DIR__ . '/../views/partials/layout.php';
}

function render(string $template, array $data = []): void {
	extract($data);
	include __DIR__ . '/../views/' . $template . '.php';
}

function render_to_string(string $template, array $data = []): string {
	// Render a view file (without layout) to an HTML string
	ob_start();
	try {
		render($template, $data);
		return (string)ob_get_clean();
	} catch (\Throwable $e) {
		ob_end_clean();
		throw $e;
	}
}

function url(string $path = ''): string {
	// Handle missing HTTP_HOST (e.g., CLI context)
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
	$baseUrl = $protocol . '://' . $host;
	
	$basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
	if ($basePath === '/') $basePath = '';
	return $baseUrl . $basePath . '/' . ltrim($path, '/');
}

function redirect(string $path): void {
	header('Location: ' . url($path));
	exit;
}

function param(string $key, array $params): string { return $params[$key] ?? ''; }
function post(string $key, $default = null) { return $_POST[$key] ?? $default; }
function request_array(string $key): array {
	$value = $_POST[$key] ?? [];
	return is_array($value) ? $value : ($value !== '' ? [$value] : []);
}

function uuid(): string {
	return bin2hex(random_bytes(16));
}

// CSRF Protection Functions
function csrf_token(): string {
	return SecurityService::generateCsrfToken();
}

function csrf_field(): string {
	return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token(): bool {
	$token = $_POST['csrf_token'] ?? '';
	return SecurityService::verifyCsrfToken($token);
}

function require_csrf(): void {
	if (!verify_csrf_token()) {
		http_response_code(403);
		die('CSRF token verification failed');
	}
}

// Secure File Upload Functions
function validate_uploaded_file(array $file, array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], int $maxSize = 5242880): array {
	$errors = [];
	
	// Check for upload errors
	if ($file['error'] !== UPLOAD_ERR_OK) {
		$errors[] = 'File upload failed with error code: ' . $file['error'];
		return $errors;
	}
	
	// Check file size
	if ($file['size'] > $maxSize) {
		$errors[] = 'File size exceeds maximum allowed size of ' . ($maxSize / 1024 / 1024) . 'MB';
	}
	
	// Check MIME type
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mimeType = finfo_file($finfo, $file['tmp_name']);
	finfo_close($finfo);
	
	if (!in_array($mimeType, $allowedTypes)) {
		$errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
	}
	
	// Check file extension
	$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
	if (!in_array($extension, $allowedExtensions)) {
		$errors[] = 'Invalid file extension. Allowed extensions: ' . implode(', ', $allowedExtensions);
	}
	
	// Additional security: check if file is actually an image
	if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
		$imageInfo = getimagesize($file['tmp_name']);
		if ($imageInfo === false) {
			$errors[] = 'File is not a valid image';
		}
	}
	
	return $errors;
}

function secure_file_upload(array $file, string $uploadDir, string $filenamePrefix = '', array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], int $maxSize = 5242880): array {
	$errors = validate_uploaded_file($file);
	if (!empty($errors)) {
		return ['success' => false, 'errors' => $errors];
	}
	
	// Create upload directory if it doesn't exist
	if (!is_dir($uploadDir)) {
		if (!mkdir($uploadDir, 0755, true)) {
			return ['success' => false, 'errors' => ['Failed to create upload directory']];
		}
	}
	
	// Generate secure filename
	$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	$filename = ($filenamePrefix ? $filenamePrefix . '_' : '') . uuid() . '.' . $extension;
	$filePath = $uploadDir . $filename;
	
	// Move uploaded file
	if (!move_uploaded_file($file['tmp_name'], $filePath)) {
		return ['success' => false, 'errors' => ['Failed to move uploaded file']];
	}
	
	// Set proper permissions
	chmod($filePath, 0644);
	
	return ['success' => true, 'filename' => $filename, 'filePath' => $filePath];
}

// Pagination Helper Functions
function paginate(string $table, array $conditions = [], array $params = [], int $page = 1, int $perPage = 50, string $orderBy = 'id'): array {
	$offset = ($page - 1) * $perPage;
	
	// Validate and sanitize table name
	$allowedTables = ['contests', 'users', 'categories', 'subcategories', 'criteria', 'scores', 'judge_certifications', 'judge_comments', 'deductions', 'system_settings', 'backup_settings', 'emcee_scripts', 'templates'];
	if (!in_array($table, $allowedTables)) {
		throw new \InvalidArgumentException("Invalid table name: {$table}");
	}
	
	// Validate and sanitize orderBy column
	$allowedOrderColumns = ['id', 'name', 'created_at', 'updated_at', 'start_date', 'end_date', 'email', 'role', 'score', 'max_score', 'created_by', 'filename', 'title', 'description'];
	$orderByParts = explode(' ', trim($orderBy));
	$orderColumn = $orderByParts[0];
	$orderDirection = isset($orderByParts[1]) ? strtoupper($orderByParts[1]) : 'ASC';
	
	if (!in_array($orderColumn, $allowedOrderColumns)) {
		throw new \InvalidArgumentException("Invalid order column: {$orderColumn}");
	}
	
	if (!in_array($orderDirection, ['ASC', 'DESC'])) {
		throw new \InvalidArgumentException("Invalid order direction: {$orderDirection}");
	}
	
	$orderBy = $orderColumn . ' ' . $orderDirection;
	
	// Build WHERE clause
	$whereClause = '';
	if (!empty($conditions)) {
		$whereClause = ' WHERE ' . implode(' AND ', $conditions);
	}
	
	// Get total count
	$countSql = "SELECT COUNT(*) FROM `{$table}`{$whereClause}";
	$stmt = DB::pdo()->prepare($countSql);
	$stmt->execute($params);
	$totalCount = $stmt->fetchColumn();
	
	// Get paginated data
	$dataSql = "SELECT * FROM `{$table}`{$whereClause} ORDER BY {$orderBy} LIMIT ? OFFSET ?";
	$stmt = DB::pdo()->prepare($dataSql);
	$stmt->execute(array_merge($params, [$perPage, $offset]));
	$data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
	
	$totalPages = ceil($totalCount / $perPage);
	
	return [
		'data' => $data,
		'pagination' => [
			'current_page' => $page,
			'per_page' => $perPage,
			'total_count' => $totalCount,
			'total_pages' => $totalPages,
			'has_next' => $page < $totalPages,
			'has_prev' => $page > 1,
			'next_page' => $page < $totalPages ? $page + 1 : null,
			'prev_page' => $page > 1 ? $page - 1 : null,
		]
	];
}

function pagination_links(array $pagination, string $baseUrl, array $queryParams = []): string {
	if ($pagination['total_pages'] <= 1) {
		return '';
	}
	
	$html = '<nav aria-label="Pagination"><ul class="pagination">';
	
	// Previous button
	if ($pagination['has_prev']) {
		$prevUrl = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $pagination['prev_page']]));
		$html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($prevUrl) . '">Previous</a></li>';
	} else {
		$html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
	}
	
	// Page numbers
	$start = max(1, $pagination['current_page'] - 2);
	$end = min($pagination['total_pages'], $pagination['current_page'] + 2);
	
	if ($start > 1) {
		$url = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => 1]));
		$html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($url) . '">1</a></li>';
		if ($start > 2) {
			$html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
		}
	}
	
	for ($i = $start; $i <= $end; $i++) {
		$url = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $i]));
		$activeClass = $i === $pagination['current_page'] ? ' active' : '';
		$html .= '<li class="page-item' . $activeClass . '"><a class="page-link" href="' . htmlspecialchars($url) . '">' . $i . '</a></li>';
	}
	
	if ($end < $pagination['total_pages']) {
		if ($end < $pagination['total_pages'] - 1) {
			$html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
		}
		$url = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $pagination['total_pages']]));
		$html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($url) . '">' . $pagination['total_pages'] . '</a></li>';
	}
	
	// Next button
	if ($pagination['has_next']) {
		$nextUrl = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $pagination['next_page']]));
		$html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($nextUrl) . '">Next</a></li>';
	} else {
		$html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
	}
	
	$html .= '</ul></nav>';
	return $html;
}

// Input Validation Functions
function validate_input(array $data, array $rules): array {
	$errors = [];
	
	foreach ($rules as $field => $rule) {
		$value = $data[$field] ?? null;
		$fieldErrors = [];
		
		// Required validation
		if (isset($rule['required']) && $rule['required'] && empty($value)) {
			$fieldErrors[] = ucfirst($field) . ' is required';
		}
		
		// Skip other validations if field is empty and not required
		if (empty($value) && !isset($rule['required'])) {
			continue;
		}
		
		// String length validation
		if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
			$fieldErrors[] = ucfirst($field) . ' must be at least ' . $rule['min_length'] . ' characters';
		}
		
		if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
			$fieldErrors[] = ucfirst($field) . ' must be no more than ' . $rule['max_length'] . ' characters';
		}
		
		// Email validation
		if (isset($rule['email']) && $rule['email'] && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
			$fieldErrors[] = ucfirst($field) . ' must be a valid email address';
		}
		
		// Numeric validation
		if (isset($rule['numeric']) && $rule['numeric'] && !is_numeric($value)) {
			$fieldErrors[] = ucfirst($field) . ' must be a number';
		}
		
		// Integer validation
		if (isset($rule['integer']) && $rule['integer'] && !filter_var($value, FILTER_VALIDATE_INT)) {
			$fieldErrors[] = ucfirst($field) . ' must be an integer';
		}
		
		// Range validation
		if (isset($rule['min']) && is_numeric($value) && $value < $rule['min']) {
			$fieldErrors[] = ucfirst($field) . ' must be at least ' . $rule['min'];
		}
		
		if (isset($rule['max']) && is_numeric($value) && $value > $rule['max']) {
			$fieldErrors[] = ucfirst($field) . ' must be no more than ' . $rule['max'];
		}
		
		// Pattern validation
		if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
			$fieldErrors[] = ucfirst($field) . ' format is invalid';
		}
		
		// In array validation
		if (isset($rule['in']) && !in_array($value, $rule['in'])) {
			$fieldErrors[] = ucfirst($field) . ' must be one of: ' . implode(', ', $rule['in']);
		}
		
		// Custom validation
		if (isset($rule['custom']) && is_callable($rule['custom'])) {
			$customError = $rule['custom']($value, $data);
			if ($customError) {
				$fieldErrors[] = $customError;
			}
		}
		
		if (!empty($fieldErrors)) {
			$errors[$field] = $fieldErrors;
		}
	}
	
	return $errors;
}

function sanitize_input(array $data, array $rules = []): array {
	$sanitized = [];
	
	foreach ($data as $key => $value) {
		if (is_string($value)) {
			// Basic sanitization
			$value = trim($value);
			
			// HTML sanitization
			if (isset($rules[$key]['html']) && !$rules[$key]['html']) {
				$value = strip_tags($value);
			}
			
			// SQL injection prevention
			$value = addslashes($value);
		}
		
		$sanitized[$key] = $value;
	}
	
	return $sanitized;
}

// Common validation rules
function get_user_validation_rules(): array {
	return [
		'name' => [
			'required' => true,
			'min_length' => 2,
			'max_length' => 100
		],
		'email' => [
			'email' => true,
			'max_length' => 255
		],
		'password' => [
			'min_length' => 8,
			'max_length' => 255,
			'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'
		],
		'role' => [
			'required' => true,
			'in' => ['organizer', 'judge', 'contestant', 'emcee', 'tally_master']
		],
		'preferred_name' => [
			'max_length' => 100
		],
		'gender' => [
			'in' => ['male', 'female', 'non-binary', 'other', 'prefer-not-to-say']
		],
	'pronouns' => [
		'max_length' => 50
	],
	'contestant_number' => [
		'integer' => true,
		'min' => 1,
		'max' => 9999
	]
];
}

// Error Handling Functions
function handle_error(string $message, int $code = 500, array $context = []): void {
	// Log the error
	\App\Logger::error('application_error', 'system', null, $message, $context);
	
	// Set appropriate HTTP status code
	http_response_code($code);
	
	// Show user-friendly error message
	$errorMessages = [
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		422 => 'Validation Error',
		500 => 'Internal Server Error'
	];
	
	$title = $errorMessages[$code] ?? 'Error';
	
	// Don't show detailed errors in production
	$showDetails = ($_ENV['APP_ENV'] ?? 'production') === 'development';
	
	view('errors/generic', [
		'title' => $title,
		'message' => $showDetails ? $message : 'An error occurred. Please try again.',
		'code' => $code,
		'context' => $showDetails ? $context : []
	]);
}

function handle_validation_errors(array $errors): void {
	ErrorHandler::handleValidationErrors($errors);
	redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

function handle_database_error(\PDOException $e, string $operation = 'database_operation'): void {
	ErrorHandler::handleDatabaseError($e, $operation);
}

function handle_file_upload_error(string $message, array $file = []): void {
	\App\Logger::error('file_upload_error', 'file_upload', null, $message, $file);
	
	handle_error('File upload failed: ' . $message, 400, ['file' => $file]);
}

// Global error handler
function global_error_handler(int $severity, string $message, string $file, int $line): bool {
	$errorTypes = [
		E_ERROR => 'Fatal Error',
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
	
	$errorType = $errorTypes[$severity] ?? 'Unknown Error';
	
	\App\Logger::error('php_error', 'system', null, 
		"{$errorType}: {$message} in {$file} on line {$line}");
	
	// Don't execute PHP internal error handler
	return true;
}

// Exception handler
function global_exception_handler(\Throwable $e): void {
	\App\Logger::error('uncaught_exception', 'system', null, 
		"Uncaught exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
	
	handle_error('An unexpected error occurred', 500, [
		'exception' => $e->getMessage(),
		'file' => $e->getFile(),
		'line' => $e->getLine()
	]);
}

function get_contest_validation_rules(): array {
	return [
		'name' => [
			'required' => true,
			'min_length' => 2,
			'max_length' => 100
		],
		'description' => [
			'max_length' => 1000
		],
		'start_date' => [
			'required' => true,
			'pattern' => '/^\d{4}-\d{2}-\d{2}$/'
		],
		'end_date' => [
			'pattern' => '/^\d{4}-\d{2}-\d{2}$/'
		]
	];
}

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_logged_in(): bool {
    $user = $_SESSION['user'] ?? null;
    if (!$user) return false;
    try {
        $stmt = DB::pdo()->prepare('SELECT session_version FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) { return false; }
        $dbVersion = (int)($row['session_version'] ?? 1);
        $sessionVersion = (int)($user['session_version'] ?? 1);
        if ($sessionVersion !== $dbVersion) {
            // Session invalidated
            \App\Logger::logSecurityEvent('session_invalidated', 'Session version mismatch; forcing logout');
            session_destroy();
            return false;
        }
    } catch (\Throwable $e) {
        // On DB error, treat as not logged in
        return false;
    }
    return true;
}
function is_organizer(): bool { return is_logged_in() && (current_user()['role'] ?? '') === 'organizer'; }
function is_judge(): bool { return is_logged_in() && (current_user()['role'] ?? '') === 'judge'; }
function is_emcee(): bool { return is_logged_in() && (current_user()['role'] ?? '') === 'emcee'; }
function is_tally_master(): bool { return is_logged_in() && (current_user()['role'] ?? '') === 'tally_master'; }
function require_login(): void { if (!is_logged_in()) { redirect('/'); } }
function require_organizer(): void { if (!is_organizer()) { redirect('/'); } }
function require_emcee(): void { if (!is_emcee()) { redirect('/'); } }
function require_judge(): void { if (!is_judge()) { redirect('/'); } }
function require_tally_master(): void { if (!is_tally_master()) { redirect('/'); } }
function can_view_nav(string $item): bool {
	if (!is_logged_in()) { return in_array($item, ['Home','Login'], true); }
	if (is_organizer()) { return true; }
	if (is_emcee()) { return in_array($item, ['Home','Contestant Bios','My Profile','Logout'], true); }
	if (is_tally_master()) { return in_array($item, ['Home','Score Review','Certification','My Profile','Logout'], true); }
	// judge
	return in_array($item, ['Home','My Assignments','My Profile','Logout','Results'], true);
}

function back_url(string $default = '/'): string {
	// Check if there's a referrer
	$referrer = $_SERVER['HTTP_REFERER'] ?? '';
	
	// If referrer is from the same domain, use it
	if ($referrer && strpos($referrer, $_SERVER['HTTP_HOST']) !== false) {
		return $referrer;
	}
	
	// Otherwise use the default
	return url($default);
}

function hierarchical_back_url(string $currentPath = ''): string {
	$currentPath = $currentPath ?: $_SERVER['REQUEST_URI'] ?? '';
	
	// Define hierarchical navigation patterns
	$hierarchy = [
		// Results hierarchy
		'/results' => '/',
		'/results/categories' => '/results',
		'/results/all' => '/results',
		
		// Contest hierarchy
		'/contests' => '/',
		'/contests/new' => '/contests',
		'/admin/archived-contests' => '/contests',
		
		// Category hierarchy
		'/categories' => '/contests',
		'/categories/{id}' => '/contests',
		'/categories/{id}/subcategories' => '/categories/{id}',
		'/categories/{id}/subcategories/new' => '/categories/{id}/subcategories',
		
		// Subcategory hierarchy
		'/subcategories' => '/categories',
		'/subcategories/{id}' => '/subcategories',
		'/subcategories/{id}/edit' => '/subcategories/{id}',
		
		// People hierarchy
		'/people' => '/',
		'/people/contestants' => '/people',
		'/people/judges' => '/people',
		'/people/contestants/new' => '/people/contestants',
		'/people/judges/new' => '/people/judges',
		
		// Users hierarchy
		'/users' => '/',
		'/users/new' => '/users',
		'/users/{id}/edit' => '/users',
		
		// Admin hierarchy
		'/admin' => '/',
		'/admin/settings' => '/admin',
		'/admin/logs' => '/admin',
		'/admin/templates' => '/admin',
		'/admin/templates/new' => '/admin/templates',
		'/admin/templates/{id}/edit' => '/admin/templates',
		'/admin/emcee-scripts' => '/admin',
		'/admin/emcee-scripts/new' => '/admin/emcee-scripts',
		
		// Scoring hierarchy
		'/score' => '/judge',
		'/score/{id}' => '/judge',
		'/score/{id}/contestant/{contestantId}' => '/score/{id}',
		
		// Judge hierarchy
		'/judge' => '/',
		'/judge/subcategory/{id}' => '/judge',
		
		// Emcee hierarchy
		'/emcee' => '/',
		
		// Profile hierarchy
		'/profile' => '/',
		'/profile/edit' => '/profile',
	];
	
	// Try to find exact match first
	if (isset($hierarchy[$currentPath])) {
		return url($hierarchy[$currentPath]);
	}
	
	// Try to find pattern match (for dynamic routes)
	foreach ($hierarchy as $pattern => $parent) {
		// Convert pattern to regex
		$regex = str_replace(['{id}', '{contestantId}'], ['([^/]+)', '([^/]+)'], preg_quote($pattern, '/'));
		$regex = '/^' . $regex . '$/';
		
		if (preg_match($regex, $currentPath)) {
			// Replace placeholders in parent path
			$parentPath = $parent;
			if (preg_match($regex, $currentPath, $matches)) {
				for ($i = 1; $i < count($matches); $i++) {
					$parentPath = str_replace('{id}', $matches[$i], $parentPath);
					$parentPath = str_replace('{contestantId}', $matches[$i], $parentPath);
				}
			}
			return url($parentPath);
		}
	}
	
	// Fallback to home or default
	return url('/');
}

function home_url(): string {
	$user = $_SESSION['user'] ?? null;
	if (!$user) {
		return url('/');
	}
	
	// Return appropriate home based on user role
	switch ($user['role']) {
		case 'judge':
			return url('/judge');
		case 'emcee':
			return url('/emcee');
		case 'organizer':
			return url('/admin');
		case 'tally_master':
			return url('/tally-master');
		default:
			return url('/');
	}
}

// Score Tabulation Helper Functions
function calculate_score_tabulation(array $scores): array {
	$tabulation = [
		'total_current' => 0,
		'total_possible' => 0,
		'by_contest' => [],
		'by_category' => [],
		'by_subcategory' => []
	];
	
	// Handle empty scores array
	if (empty($scores)) {
		return $tabulation;
	}
	
	foreach ($scores as $score) {
		$current = (float)$score['score'];
		$possible = (float)$score['max_score'];
		
		// Overall totals
		$tabulation['total_current'] += $current;
		$tabulation['total_possible'] += $possible;
		
		// By contest
		$contestName = $score['contest_name'] ?? 'Unknown Contest';
		if (!isset($tabulation['by_contest'][$contestName])) {
			$tabulation['by_contest'][$contestName] = ['current' => 0, 'possible' => 0];
		}
		$tabulation['by_contest'][$contestName]['current'] += $current;
		$tabulation['by_contest'][$contestName]['possible'] += $possible;
		
		// By category
		$categoryName = $score['category_name'] ?? 'Unknown Category';
		if (!isset($tabulation['by_category'][$categoryName])) {
			$tabulation['by_category'][$categoryName] = ['current' => 0, 'possible' => 0];
		}
		$tabulation['by_category'][$categoryName]['current'] += $current;
		$tabulation['by_category'][$categoryName]['possible'] += $possible;
		
		// By subcategory
		$subcategoryName = $score['subcategory_name'] ?? 'Unknown Subcategory';
		if (!isset($tabulation['by_subcategory'][$subcategoryName])) {
			$tabulation['by_subcategory'][$subcategoryName] = ['current' => 0, 'possible' => 0];
		}
		$tabulation['by_subcategory'][$subcategoryName]['current'] += $current;
		$tabulation['by_subcategory'][$subcategoryName]['possible'] += $possible;
	}
	
	return $tabulation;
}

function format_score_tabulation(array $tabulation, string $level = 'overall'): string {
	// Handle empty or invalid tabulation
	if (empty($tabulation) || !is_array($tabulation)) {
		return '0.0 / 0.0 (N/A%)';
	}
	
	if ($level === 'overall') {
		$current = $tabulation['total_current'] ?? 0;
		$possible = $tabulation['total_possible'] ?? 0;
	} else {
		$current = $tabulation['current'] ?? 0;
		$possible = $tabulation['possible'] ?? 0;
	}
	
	if ($possible > 0) {
		$percentage = number_format(($current / $possible) * 100, 1);
		return number_format($current, 1) . ' / ' . number_format($possible, 1) . ' (' . $percentage . '%)';
	}
	
	return number_format($current, 1) . ' / ' . number_format($possible, 1) . ' (N/A%)';
}

function get_contestant_total_score(string $contestantId): array {
	$stmt = DB::pdo()->prepare('
		SELECT 
			COALESCE(SUM(s.score), 0) as total_current,
			COALESCE(SUM(cr.max_score), 0) as total_possible
		FROM contestants con
		JOIN subcategory_contestants sc ON con.id = sc.contestant_id
		JOIN subcategories sub ON sc.subcategory_id = sub.id
		LEFT JOIN scores s ON con.id = s.contestant_id AND sub.id = s.subcategory_id
		LEFT JOIN criteria cr ON s.criterion_id = cr.id
		WHERE con.id = ?
	');
	$stmt->execute([$contestantId]);
	return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total_current' => 0, 'total_possible' => 0];
}

function get_judge_total_score(string $judgeId): array {
	$stmt = DB::pdo()->prepare('
		SELECT 
			COALESCE(SUM(s.score), 0) as total_current,
			COALESCE(SUM(cr.max_score), 0) as total_possible
		FROM judges j
		JOIN subcategory_judges sj ON j.id = sj.judge_id
		JOIN subcategories sub ON sj.subcategory_id = sub.id
		LEFT JOIN scores s ON j.id = s.judge_id AND sub.id = s.subcategory_id
		LEFT JOIN criteria cr ON s.criterion_id = cr.id
		WHERE j.id = ?
	');
	$stmt->execute([$judgeId]);
	return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total_current' => 0, 'total_possible' => 0];
}

function get_category_total_score(string $categoryId): array {
	$stmt = DB::pdo()->prepare('
		SELECT 
			COALESCE(SUM(s.score), 0) as total_current,
			COALESCE(SUM(cr.max_score), 0) as total_possible
		FROM categories c
		JOIN subcategories sub ON c.id = sub.category_id
		LEFT JOIN scores s ON sub.id = s.subcategory_id
		LEFT JOIN criteria cr ON s.criterion_id = cr.id
		WHERE c.id = ?
	');
	$stmt->execute([$categoryId]);
	return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total_current' => 0, 'total_possible' => 0];
}

function calculate_contestant_totals_for_category(string $categoryId): array {
	$stmt = DB::pdo()->prepare('
		SELECT 
			con.id as contestant_id,
			con.name as contestant_name,
			con.contestant_number,
			COALESCE(SUM(s.score), 0) as total_current,
			COALESCE(SUM(cr.max_score), 0) as total_possible
		FROM contestants con
		JOIN subcategory_contestants sc ON con.id = sc.contestant_id
		JOIN subcategories sub ON sc.subcategory_id = sub.id
		LEFT JOIN scores s ON con.id = s.contestant_id AND sub.id = s.subcategory_id
		LEFT JOIN criteria cr ON s.criterion_id = cr.id
		WHERE sub.category_id = ?
		GROUP BY con.id, con.name, con.contestant_number
		ORDER BY total_current DESC, con.name
	');
	$stmt->execute([$categoryId]);
	return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}


