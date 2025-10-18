<?php
declare(strict_types=1);

namespace App\Controllers;

use function App\{view, render, redirect, param, post, request_array, current_user, is_logged_in, is_organizer, is_judge, is_emcee, require_login, require_organizer, require_emcee, csrf_field, require_csrf, secure_file_upload, paginate, pagination_links, validate_input, sanitize_input, get_user_validation_rules, handle_error, handle_validation_errors, handle_database_error, uuid};

class UserController {
	public function new(): void {
		require_organizer();
		$categories = DB::pdo()->query('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id ORDER BY co.name, c.name')->fetchAll(\PDO::FETCH_ASSOC);
		view('users/new', compact('categories'));
	}
	
	public function create(): void {
		require_organizer();
		require_csrf();
		
		try {
			// Get and sanitize input data
			$inputData = sanitize_input($_POST);
			$name = $inputData['name'] ?? '';
			$email = $inputData['email'] ?? null;
			$password = $inputData['password'] ?? '';
			$role = $inputData['role'] ?? '';
			$preferredName = $inputData['preferred_name'] ?? $name;
			$gender = $inputData['gender'] ?? null;
			$pronouns = $inputData['pronouns'] ?? null;
			$categoryId = $inputData['category_id'] ?? null;
			$isHeadJudge = isset($inputData['is_head_judge']) ? 1 : 0;
			
			// Debug log user creation attempt
			\App\Logger::debug('user_creation_attempt', 'user', null, 
				"Attempting to create user: name={$name}, email={$email}, role={$role}, category_id={$categoryId}, is_head_judge={$isHeadJudge}");
			
			// Validate input data
			$validationRules = get_user_validation_rules();
			$validationErrors = validate_input($inputData, $validationRules);
			
			if (!empty($validationErrors)) {
				\App\Logger::debug('user_creation_validation_failed', 'user', null, 
					"User creation failed validation: " . json_encode($validationErrors));
				handle_validation_errors($validationErrors);
				return;
			}
			
			// Create user based on role
			$userId = uuid();
			$passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
			
			// Insert user record
			$stmt = DB::pdo()->prepare('INSERT INTO users (id, name, email, password_hash, role, preferred_name, gender, pronouns, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
			$stmt->execute([$userId, $name, $email, $passwordHash, $role, $preferredName, $gender, $pronouns, date('c')]);
			
			// Create role-specific records
			if ($role === 'contestant') {
				$this->createContestant($userId, $name, $email, $categoryId, $inputData);
			} elseif ($role === 'judge') {
				$this->createJudge($userId, $name, $email, $isHeadJudge, $inputData);
			} elseif ($role === 'emcee') {
				$this->createEmcee($userId, $name, $email);
			}
			
			\App\Logger::logAdminAction('user_created', 'user', $userId, "User created: {$name} ({$role})");
			redirect('/admin/users?success=user_created');
			
		} catch (\PDOException $e) {
			handle_database_error($e, 'user_creation');
		} catch (\Exception $e) {
			handle_error('Failed to create user: ' . $e->getMessage(), 500);
		}
	}
	
	private function createContestant(string $userId, string $name, ?string $email, ?string $categoryId, array $data): void {
		$contestantId = uuid();
		$contestantNumber = $data['contestant_number'] ?? null;
		
		// Handle image upload
		$imagePath = null;
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = __DIR__ . '/../../public/uploads/contestants/';
			$result = secure_file_upload($_FILES['image'], $uploadDir, 'contestant');
			
			if (!$result['success']) {
				throw new \Exception('Image upload failed: ' . implode(', ', $result['errors']));
			}
			
			$imagePath = '/uploads/contestants/' . $result['filename'];
		}
		
		// Insert contestant record
		$stmt = DB::pdo()->prepare('INSERT INTO contestants (id, name, email, contestant_number, bio, image_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
		$stmt->execute([$contestantId, $name, $email, $contestantNumber, $data['bio'] ?? null, $imagePath, date('c')]);
		
		// Link user to contestant
		$stmt = DB::pdo()->prepare('UPDATE users SET contestant_id = ? WHERE id = ?');
		$stmt->execute([$contestantId, $userId]);
		
		// Assign to category if specified
		if ($categoryId) {
			$stmt = DB::pdo()->prepare('INSERT INTO subcategory_contestants (subcategory_id, contestant_id) VALUES (?, ?)');
			$stmt->execute([$categoryId, $contestantId]);
		}
	}
	
	private function createJudge(string $userId, string $name, ?string $email, int $isHeadJudge, array $data): void {
		$judgeId = uuid();
		
		// Handle image upload
		$imagePath = null;
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = __DIR__ . '/../../public/uploads/judges/';
			$result = secure_file_upload($_FILES['image'], $uploadDir, 'judge');
			
			if (!$result['success']) {
				throw new \Exception('Image upload failed: ' . implode(', ', $result['errors']));
			}
			
			$imagePath = '/uploads/judges/' . $result['filename'];
		}
		
		// Insert judge record
		$stmt = DB::pdo()->prepare('INSERT INTO judges (id, name, email, is_head_judge, bio, image_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
		$stmt->execute([$judgeId, $name, $email, $isHeadJudge, $data['bio'] ?? null, $imagePath, date('c')]);
		
		// Link user to judge
		$stmt = DB::pdo()->prepare('UPDATE users SET judge_id = ? WHERE id = ?');
		$stmt->execute([$judgeId, $userId]);
	}
	
	private function createEmcee(string $userId, string $name, ?string $email): void {
		$emceeId = uuid();
		
		// Insert emcee record
		$stmt = DB::pdo()->prepare('INSERT INTO emcees (id, name, email, created_at) VALUES (?, ?, ?, ?)');
		$stmt->execute([$emceeId, $name, $email, date('c')]);
		
		// Link user to emcee
		$stmt = DB::pdo()->prepare('UPDATE users SET emcee_id = ? WHERE id = ?');
		$stmt->execute([$emceeId, $userId]);
	}
	
	public function index(): void {
		require_organizer();
		
		$page = (int)($_GET['page'] ?? 1);
		$perPage = 50;
		$role = $_GET['role'] ?? '';
		
		// Debug log data retrieval
		\App\Logger::debug('users_index_data_retrieval', 'users', null, 
			"Retrieving users with their associated contestant/judge data, page: {$page}, role: {$role}");
		
		// Build query with optional role filter
		$whereClause = '';
		$params = [];
		if (!empty($role)) {
			$whereClause = ' WHERE u.role = ?';
			$params[] = $role;
		}
		
		$sql = "
			SELECT u.*, 
			       c.contestant_number,
			       j.is_head_judge
			FROM users u 
			LEFT JOIN contestants c ON u.contestant_id = c.id 
			LEFT JOIN judges j ON u.judge_id = j.id
			{$whereClause}
			ORDER BY u.role, u.name
		";
		
		// Get total count
		$countSql = "SELECT COUNT(*) FROM users u{$whereClause}";
		$stmt = DB::pdo()->prepare($countSql);
		$stmt->execute($params);
		$totalCount = $stmt->fetchColumn();
		
		// Get paginated data
		$offset = ($page - 1) * $perPage;
		$stmt = DB::pdo()->prepare($sql . " LIMIT ? OFFSET ?");
		$stmt->execute(array_merge($params, [$perPage, $offset]));
		$users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Group users by role
		$usersByRole = [];
		foreach ($users as $user) {
			$usersByRole[$user['role']][] = $user;
		}
		
		// Calculate pagination info
		$totalPages = ceil($totalCount / $perPage);
		$pagination = [
			'current_page' => $page,
			'per_page' => $perPage,
			'total_count' => $totalCount,
			'total_pages' => $totalPages,
			'has_next' => $page < $totalPages,
			'has_prev' => $page > 1,
			'next_page' => $page < $totalPages ? $page + 1 : null,
			'prev_page' => $page > 1 ? $page - 1 : null,
		];
		
		\App\Logger::debug('users_index_data_retrieved', 'users', null, 
			"Retrieved " . count($users) . " users (page {$page}/{$totalPages}): " . 
			(count($usersByRole['organizer'] ?? []) . " organizers, " .
			count($usersByRole['judge'] ?? []) . " judges, " .
			count($usersByRole['contestant'] ?? []) . " contestants, " .
			count($usersByRole['emcee'] ?? []) . " emcees"));
		
		view('users/index', compact('usersByRole', 'pagination', 'role'));
	}
	
	public function edit(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT * FROM users WHERE id = ?');
		$stmt->execute([$id]);
		$user = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$user) {
			redirect('/admin/users');
			return;
		}
		$categories = DB::pdo()->query('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id ORDER BY co.name, c.name')->fetchAll(\PDO::FETCH_ASSOC);
		view('users/edit', compact('user', 'categories'));
	}
	
	public function update(array $params): void {
		require_organizer();
		require_csrf();
		
		try {
			$id = param('id', $params);
			$inputData = sanitize_input($_POST);
			
			// Validate input data
			$validationRules = get_user_validation_rules();
			$validationErrors = validate_input($inputData, $validationRules);
			
			if (!empty($validationErrors)) {
				handle_validation_errors($validationErrors);
				return;
			}
			
			$name = $inputData['name'];
			$email = $inputData['email'] ?? null;
			$password = $inputData['password'] ?? '';
			$role = $inputData['role'];
			$preferredName = $inputData['preferred_name'] ?? $name;
			$gender = $inputData['gender'] ?? null;
			$pronouns = $inputData['pronouns'] ?? null;
			
			// Update user record
			$passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
			$stmt = DB::pdo()->prepare('UPDATE users SET name = ?, email = ?, password_hash = COALESCE(?, password_hash), role = ?, preferred_name = ?, gender = ?, pronouns = ? WHERE id = ?');
			$stmt->execute([$name, $email, $passwordHash, $role, $preferredName, $gender, $pronouns, $id]);
			
			\App\Logger::logAdminAction('user_updated', 'user', $id, "User updated: {$name}");
			redirect('/admin/users?success=user_updated');
			
		} catch (\PDOException $e) {
			handle_database_error($e, 'user_update');
		} catch (\Exception $e) {
			handle_error('Failed to update user: ' . $e->getMessage(), 500);
		}
	}
	
	public function delete(array $params): void {
		require_organizer();
		require_csrf();
		
		try {
			$id = param('id', $params);
			
			// Get user info for logging
			$stmt = DB::pdo()->prepare('SELECT name FROM users WHERE id = ?');
			$stmt->execute([$id]);
			$user = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if (!$user) {
				redirect('/admin/users?error=user_not_found');
				return;
			}
			
			// Delete user (cascade will handle related records)
			$stmt = DB::pdo()->prepare('DELETE FROM users WHERE id = ?');
			$stmt->execute([$id]);
			
			\App\Logger::logAdminAction('user_deleted', 'user', $id, "User deleted: {$user['name']}");
			redirect('/admin/users?success=user_deleted');
			
		} catch (\PDOException $e) {
			handle_database_error($e, 'user_deletion');
		} catch (\Exception $e) {
			handle_error('Failed to delete user: ' . $e->getMessage(), 500);
		}
	}
}
