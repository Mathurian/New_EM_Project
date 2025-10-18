<?php
declare(strict_types=1);
namespace App;

function view(string $template, array $data = []): void {
	extract($data);
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
	$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
	$basePath = dirname($_SERVER['SCRIPT_NAME']);
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

// CSRF Protection Functions
function csrf_token(): string {
	if (!isset($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['csrf_token'];
}

function csrf_field(): string {
	return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token(): bool {
	$token = $_POST['csrf_token'] ?? '';
	return hash_equals($_SESSION['csrf_token'] ?? '', $token);
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
function require_login(): void { if (!is_logged_in()) { redirect('/'); } }
function require_organizer(): void { if (!is_organizer()) { redirect('/'); } }
function require_emcee(): void { if (!is_emcee()) { redirect('/'); } }
function can_view_nav(string $item): bool {
	if (!is_logged_in()) { return in_array($item, ['Home','Login'], true); }
	if (is_organizer()) { return true; }
	if (is_emcee()) { return in_array($item, ['Home','Contestant Bios','My Profile','Logout'], true); }
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
		default:
			return url('/');
	}
}



