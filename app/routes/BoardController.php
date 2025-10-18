<?php
declare(strict_types=1);

namespace App\Routes;

use function App\{view, redirect, require_board, require_csrf, url, current_user, is_board, is_organizer, is_auditor, is_tally_master, secure_file_upload, uuid};
use App\DB;
use App\Logger;

class BoardController {
	public function index(): void {
		require_board();
		
		// Get certification status overview
		$pdo = DB::pdo();
		
		// Get overall certification statistics
		$totalSubcategories = $pdo->query('SELECT COUNT(*) FROM subcategories')->fetchColumn();
		$judgeCertifications = $pdo->query('SELECT COUNT(DISTINCT subcategory_id) FROM judge_certifications WHERE certified_at IS NOT NULL')->fetchColumn();
		$tallyMasterCertifications = $pdo->query('SELECT COUNT(*) FROM tally_master_certifications')->fetchColumn();
		$auditorCertifications = $pdo->query('SELECT COUNT(*) FROM auditor_certifications')->fetchColumn();
		
		// Get emcee scripts count
		$emceeScriptsCount = $pdo->query('SELECT COUNT(*) FROM emcee_scripts WHERE is_active = 1')->fetchColumn();
		
		// Get contests and categories for overview
		$contests = $pdo->query('SELECT id, name FROM contests ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		
		$dashboardData = [
			'total_subcategories' => $totalSubcategories,
			'judge_certifications' => $judgeCertifications,
			'tally_master_certifications' => $tallyMasterCertifications,
			'auditor_certifications' => $auditorCertifications,
			'emcee_scripts_count' => $emceeScriptsCount,
			'contests' => $contests,
			'categories' => $categories
		];
		
		view('board/index', compact('dashboardData'));
	}
	
	public function certificationStatus(): void {
		require_board();
		
		$pdo = DB::pdo();
		
		// Get detailed certification status by subcategory
		$stmt = $pdo->query("
			SELECT 
				sc.id as subcategory_id,
				sc.name as subcategory_name,
				c.name as category_name,
				co.name as contest_name,
				COUNT(DISTINCT jc.judge_id) as judges_certified,
				COUNT(DISTINCT sj.judge_id) as total_judges,
				tmc.signature_name as tally_master_signature,
				tmc.certified_at as tally_master_certified_at,
				ac.certified_at as auditor_certified_at
			FROM subcategories sc
			JOIN categories c ON sc.category_id = c.id
			JOIN contests co ON c.contest_id = co.id
			LEFT JOIN subcategory_judges sj ON sc.id = sj.subcategory_id
			LEFT JOIN judge_certifications jc ON sc.id = jc.subcategory_id AND jc.certified_at IS NOT NULL
			LEFT JOIN tally_master_certifications tmc ON sc.id = tmc.subcategory_id
			LEFT JOIN auditor_certifications ac ON 1=1
			GROUP BY sc.id, sc.name, c.name, co.name, tmc.signature_name, tmc.certified_at, ac.certified_at
			ORDER BY co.name, c.name, sc.name
		");
		
		$certificationData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('board/certification-status', compact('certificationData'));
	}
	
	public function emceeScripts(): void {
		require_board();
		
		$scripts = DB::pdo()->query('SELECT es.*, u.preferred_name as uploaded_by_name FROM emcee_scripts es LEFT JOIN users u ON es.uploaded_by = u.id ORDER BY COALESCE(es.created_at, "1970-01-01 00:00:00") DESC')->fetchAll(\PDO::FETCH_ASSOC);
		
		view('board/emcee-scripts', compact('scripts'));
	}
	
	public function uploadEmceeScript(): void {
		require_board();
		require_csrf();
		
		// Validate required fields
		$title = trim($_POST['title'] ?? '');
		if (empty($title)) {
			redirect('/board/emcee-scripts?error=title_required');
			return;
		}
		
		// Check if file was uploaded
		if (!isset($_FILES['script_file']) || $_FILES['script_file']['error'] !== UPLOAD_ERR_OK) {
			Logger::error('emcee_script_upload', 'board', $_SESSION['user']['id'] ?? null, 
				"File upload failed. Error code: " . ($_FILES['script_file']['error'] ?? 'no file'));
			redirect('/board/emcee-scripts?error=file_upload_failed');
			return;
		}
		
		// Validate file type and size manually
		$file = $_FILES['script_file'];
		$allowedTypes = ['application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
		$allowedExtensions = ['pdf', 'txt', 'doc', 'docx'];
		$maxSize = 10 * 1024 * 1024; // 10MB
		
		// Check file size
		if ($file['size'] > $maxSize) {
			Logger::warn('emcee_script_upload', 'board', $_SESSION['user']['id'] ?? null, 
				"File too large: " . $file['size'] . " bytes");
			redirect('/board/emcee-scripts?error=file_too_large');
			return;
		}
		
		// Check MIME type
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $file['tmp_name']);
		finfo_close($finfo);
		
		if (!in_array($mimeType, $allowedTypes)) {
			Logger::warn('emcee_script_upload', 'board', $_SESSION['user']['id'] ?? null, 
				"Invalid MIME type: " . $mimeType);
			redirect('/board/emcee-scripts?error=invalid_file_type');
			return;
		}
		
		// Check file extension
		$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		if (!in_array($extension, $allowedExtensions)) {
			Logger::warn('emcee_script_upload', 'board', $_SESSION['user']['id'] ?? null, 
				"Invalid file extension: " . $extension);
			redirect('/board/emcee-scripts?error=invalid_file_extension');
			return;
		}
		
		// Create upload directory if it doesn't exist
		$uploadDir = __DIR__ . '/../../public/uploads/emcee-scripts/';
		if (!is_dir($uploadDir)) {
			if (!mkdir($uploadDir, 0755, true)) {
				Logger::error('emcee_script_upload', 'board', $_SESSION['user']['id'] ?? null, 
					"Failed to create upload directory");
				redirect('/board/emcee-scripts?error=upload_directory_failed');
				return;
			}
		}
		
		// Generate secure filename and upload
		$filename = 'script_' . uuid() . '.' . $extension;
		$filepath = $uploadDir . $filename;
		
		if (!move_uploaded_file($file['tmp_name'], $filepath)) {
			Logger::error('emcee_script_upload', 'board', $_SESSION['user']['id'] ?? null, 
				"Failed to move uploaded file");
			redirect('/board/emcee-scripts?error=upload_failed');
			return;
		}
		
		// Set proper permissions
		chmod($filepath, 0644);
		
		$originalFilename = $_FILES['script_file']['name'];
		
		// Save to database
		$scriptId = uuid();
		$stmt = DB::pdo()->prepare('
			INSERT INTO emcee_scripts (id, title, filename, file_path, uploaded_by, created_at, is_active)
			VALUES (?, ?, ?, ?, ?, ?, ?)
		');
		$stmt->execute([
			$scriptId,
			$title,
			$originalFilename,
			'/uploads/emcee-scripts/' . $filename,
			current_user()['id'],
			date('Y-m-d H:i:s'),
			1
		]);
		
		Logger::logAdminAction('emcee_script_uploaded', 'board', current_user()['id'], "Emcee script uploaded: {$title}");
		redirect('/board/emcee-scripts?success=script_uploaded');
	}
	
	public function toggleEmceeScript(array $params): void {
		require_board();
		require_csrf();
		
		$scriptId = $params['id'] ?? '';
		if (empty($scriptId)) {
			redirect('/board/emcee-scripts?error=invalid_script');
			return;
		}
		
		$pdo = DB::pdo();
		$stmt = $pdo->prepare('SELECT is_active FROM emcee_scripts WHERE id = ?');
		$stmt->execute([$scriptId]);
		$currentStatus = $stmt->fetchColumn();
		
		if ($currentStatus === false) {
			redirect('/board/emcee-scripts?error=script_not_found');
			return;
		}
		
		$newStatus = $currentStatus ? 0 : 1;
		$stmt = $pdo->prepare('UPDATE emcee_scripts SET is_active = ? WHERE id = ?');
		$stmt->execute([$newStatus, $scriptId]);
		
		$action = $newStatus ? 'activated' : 'deactivated';
		Logger::logAdminAction('emcee_script_toggled', 'board', current_user()['id'], "Emcee script {$action}: {$scriptId}");
		
		redirect('/board/emcee-scripts?success=script_' . $action);
	}
	
	public function deleteEmceeScript(array $params): void {
		require_board();
		require_csrf();
		
		$scriptId = $params['id'] ?? '';
		if (empty($scriptId)) {
			redirect('/board/emcee-scripts?error=invalid_script');
			return;
		}
		
		$pdo = DB::pdo();
		
		// Get script info for logging
		$stmt = $pdo->prepare('SELECT title, file_path FROM emcee_scripts WHERE id = ?');
		$stmt->execute([$scriptId]);
		$script = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$script) {
			redirect('/board/emcee-scripts?error=script_not_found');
			return;
		}
		
		// Delete file
		$filePath = __DIR__ . '/../../public' . $script['file_path'];
		if (file_exists($filePath)) {
			unlink($filePath);
		}
		
		// Delete database record
		$stmt = $pdo->prepare('DELETE FROM emcee_scripts WHERE id = ?');
		$stmt->execute([$scriptId]);
		
		Logger::logAdminAction('emcee_script_deleted', 'board', current_user()['id'], "Emcee script deleted: {$script['title']}");
		redirect('/board/emcee-scripts?success=script_deleted');
	}
	
	public function printReports(): void {
		require_board();
		
		$pdo = DB::pdo();
		$view = $_GET['view'] ?? 'main';
		
		if ($view === 'contestants') {
			// Get all contestants for individual printing
			$contestants = $pdo->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
			$usersWithEmail = $pdo->query('SELECT id, name, preferred_name, email FROM users WHERE email IS NOT NULL AND email != "" ORDER BY preferred_name, name')->fetchAll(\PDO::FETCH_ASSOC);
			view('board/print-reports-contestants', compact('contestants', 'usersWithEmail'));
			return;
		}
		
		if ($view === 'judges') {
			// Get all judges for individual printing
			$judges = $pdo->query('SELECT * FROM judges ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
			$usersWithEmail = $pdo->query('SELECT id, name, preferred_name, email FROM users WHERE email IS NOT NULL AND email != "" ORDER BY preferred_name, name')->fetchAll(\PDO::FETCH_ASSOC);
			view('board/print-reports-judges', compact('judges', 'usersWithEmail'));
			return;
		}
		
		// Main view - get contests and categories for report selection
		$contests = $pdo->query('SELECT id, name FROM contests ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		$usersWithEmail = $pdo->query('SELECT id, name, preferred_name, email FROM users WHERE email IS NOT NULL AND email != "" ORDER BY preferred_name, name')->fetchAll(\PDO::FETCH_ASSOC);
		
		view('board/print-reports', compact('contests', 'categories', 'usersWithEmail'));
	}
	
	public function removeJudgeScores(): void {
		require_board();
		
		$pdo = DB::pdo();
		
		// Get judges and their scores for removal interface
		$judges = $pdo->query('
			SELECT DISTINCT j.id, j.name, u.email
			FROM judges j
			JOIN users u ON j.id = u.judge_id
			WHERE j.id IN (SELECT DISTINCT judge_id FROM scores)
			ORDER BY j.name
		')->fetchAll(\PDO::FETCH_ASSOC);
		
		view('board/remove-judge-scores', compact('judges'));
	}
	
	public function initiateScoreRemoval(): void {
		require_board();
		require_csrf();
		
		$judgeId = $_POST['judge_id'] ?? '';
		$reason = trim($_POST['reason'] ?? '');
		
		if (empty($judgeId) || empty($reason)) {
			redirect('/board/remove-judge-scores?error=missing_fields');
			return;
		}
		
		$pdo = DB::pdo();
		
		// Check if judge exists and has scores
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM scores WHERE judge_id = ?');
		$stmt->execute([$judgeId]);
		$scoreCount = $stmt->fetchColumn();
		
		if ($scoreCount == 0) {
			redirect('/board/remove-judge-scores?error=no_scores_found');
			return;
		}
		
		// Create removal request
		$requestId = uuid();
		$stmt = $pdo->prepare('
			INSERT INTO judge_score_removal_requests (id, judge_id, requested_by, reason, status, created_at)
			VALUES (?, ?, ?, ?, ?, ?)
		');
		$stmt->execute([
			$requestId,
			$judgeId,
			current_user()['id'],
			$reason,
			'pending',
			date('c')
		]);
		
		Logger::logAdminAction('judge_score_removal_initiated', 'board', current_user()['id'], 
			"Judge score removal initiated for judge {$judgeId}: {$reason}");
		
		redirect('/board/remove-judge-scores?success=removal_initiated');
	}
}
