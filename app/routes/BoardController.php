<?php
declare(strict_types=1);

namespace App\Routes;

use function App\{view, redirect, require_board, require_csrf, url, current_user, is_board, is_organizer, is_auditor, is_tally_master, secure_file_upload, uuid, render_to_string, calculate_contestant_totals_for_category};
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
		
		// Debug: Log the start of upload process
		error_log('Board upload: Starting upload process');
		
		// Validate required fields
		$title = trim($_POST['title'] ?? '');
		if (empty($title)) {
			error_log('Board upload: Title validation failed');
			redirect('/board/emcee-scripts?error=title_required');
			return;
		}
		
		// Check if file was uploaded
		if (!isset($_FILES['script_file']) || $_FILES['script_file']['error'] !== UPLOAD_ERR_OK) {
			error_log('Board upload: File upload failed - error code: ' . ($_FILES['script_file']['error'] ?? 'no file'));
			redirect('/board/emcee-scripts?error=file_upload_failed');
			return;
		}
		
		error_log('Board upload: File validation passed, proceeding with secure_file_upload');
		
		// Use secure file upload with document-specific validation (same as admin)
		$uploadDir = __DIR__ . '/../../public/uploads/emcee-scripts/';
		$allowedTypes = ['application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
		$maxSize = 10 * 1024 * 1024; // 10MB
		
		$result = secure_file_upload($_FILES['script_file'], $uploadDir, 'script', $allowedTypes, $maxSize);
		
		if (!$result['success']) {
			error_log('Board upload: secure_file_upload failed - ' . implode(', ', $result['errors']));
			redirect('/board/emcee-scripts?error=file_validation_failed&details=' . urlencode(implode(', ', $result['errors'])));
			return;
		}
		
		error_log('Board upload: secure_file_upload successful');
		
		$filename = $result['filename'];
		$filepath = $result['filePath'];
		$originalFilename = $_FILES['script_file']['name'];
		
		// File uploaded successfully by secure_file_upload
		$description = trim($_POST['description'] ?? '');
		$fileSize = $_FILES['script_file']['size'];
		$uploadedAt = date('Y-m-d H:i:s');
		
		try {
			// Debug: Log the database insert attempt
			error_log('Board upload: Attempting database insert for file: ' . $filename);
			error_log('Board upload: User ID: ' . \App\current_user()['id']);
			
			// Use the same database insert as admin (with all columns)
			$insertValues = [\App\uuid(), $filename, '/uploads/emcee-scripts/' . $filename, 1, date('Y-m-d H:i:s'), \App\current_user()['id'], $title, $description, $originalFilename, $fileSize, $uploadedAt];
			
			$stmt = DB::pdo()->prepare('INSERT INTO emcee_scripts (id, filename, file_path, is_active, created_at, uploaded_by, title, description, file_name, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
			$stmt->execute($insertValues);
			
			error_log('Board upload: Database insert successful');
			redirect('/board/emcee-scripts?success=script_uploaded');
		} catch (\Exception $e) {
			// Debug: Log the database error
			error_log('Board upload: Database insert failed - ' . $e->getMessage());
			error_log('Board upload: Exception trace - ' . $e->getTraceAsString());
			
			// Clean up uploaded file if database insert fails
			if (file_exists($filepath)) {
				unlink($filepath);
				error_log('Board upload: Cleaned up uploaded file');
			}
			// Redirect with error details for debugging
			redirect('/board/emcee-scripts?error=file_save_failed&details=' . urlencode($e->getMessage()));
		}
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
		$requestId = \App\uuid();
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
	
	public function emailReport(): void {
		require_board();
		require_csrf();
		
		$reportType = $_POST['report_type'] ?? '';
		$entityId = $_POST['entity_id'] ?? '';
		$userId = $_POST['user_id'] ?? '';
		$toEmail = $_POST['to_email'] ?? '';
		
		// Validate required fields based on report type
		$requiresEntityId = in_array($reportType, ['contest', 'contestant', 'judge', 'category']);
		
		if (empty($reportType) || (empty($userId) && empty($toEmail))) {
			redirect('/board/print-reports?error=missing_email_data');
			return;
		}
		
		if ($requiresEntityId && empty($entityId)) {
			redirect('/board/print-reports?error=missing_entity_id');
			return;
		}
		
		// Determine recipient email
		$recipientEmail = '';
		if (!empty($userId)) {
			$user = DB::pdo()->prepare('SELECT email FROM users WHERE id = ?');
			$user->execute([$userId]);
			$userData = $user->fetch(\PDO::FETCH_ASSOC);
			$recipientEmail = $userData['email'] ?? '';
		} else {
			$recipientEmail = $toEmail;
		}
		
		if (empty($recipientEmail)) {
			redirect('/board/print-reports?error=invalid_email');
			return;
		}
		
		// Generate report HTML based on type
		$html = '';
		$subject = '';
		$isEmail = true; // Flag for email templates
		
		if ($reportType === 'contest') {
			// Get contest data
			$contest = DB::pdo()->prepare('SELECT * FROM contests WHERE id = ?');
			$contest->execute([$entityId]);
			$contest = $contest->fetch(\PDO::FETCH_ASSOC);
			
			if (!$contest) {
				redirect('/board/print-reports?error=contest_not_found');
				return;
			}
			
			// Get all categories for this contest
			$categories = DB::pdo()->prepare('SELECT * FROM categories WHERE contest_id = ? ORDER BY name');
			$categories->execute([$entityId]);
			$categories = $categories->fetchAll(\PDO::FETCH_ASSOC);
			
			// Get summary data for each category
			$categoryData = [];
			foreach ($categories as $category) {
				// Get contestants with their total scores for this category
				$contestants = calculate_contestant_totals_for_category($category['id']);
				$categoryData[] = [
					'category' => $category,
					'contestants' => $contestants
				];
			}
			
			$html = \App\render_to_string('board/contest-summary', compact('contest','categories','categoryData','isEmail'));
			$subject = 'Contest Summary: ' . ($contest['name'] ?? '');
			
		} else if ($reportType === 'contestant_summary') {
			// Generate comprehensive contestant summary email
			$pdo = DB::pdo();
			
			// Get all contestants with their scores - simplified query
			$contestants = $pdo->query('
				SELECT c.id, c.name, c.contestant_number,
				       COALESCE(COUNT(DISTINCT s.subcategory_id), 0) as subcategories_count,
				       COALESCE(AVG(s.score), 0) as avg_score,
				       COALESCE(SUM(s.score), 0) as total_score
				FROM contestants c
				LEFT JOIN scores s ON c.id = s.contestant_id
				GROUP BY c.id, c.name, c.contestant_number
				ORDER BY total_score DESC, c.name
			')->fetchAll(\PDO::FETCH_ASSOC);
			
			// Get contest statistics
			$totalContestants = count($contestants);
			$totalSubcategories = $pdo->query('SELECT COUNT(*) FROM subcategories')->fetchColumn();
			
			$html = '<html><body>';
			$html .= '<h1>Contestant Summary Report</h1>';
			$html .= '<p><strong>Total Contestants:</strong> ' . $totalContestants . '</p>';
			$html .= '<p><strong>Total Subcategories:</strong> ' . $totalSubcategories . '</p>';
			$html .= '<p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>';
			
			if (!empty($contestants)) {
				$html .= '<h2>Contestant Rankings</h2>';
				$html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
				$html .= '<tr style="background-color: #f2f2f2;"><th>Rank</th><th>Name</th><th>Number</th><th>Total Score</th><th>Avg Score</th><th>Subcategories</th></tr>';
				
				$rank = 1;
				foreach ($contestants as $contestant) {
					$html .= '<tr>';
					$html .= '<td>' . $rank++ . '</td>';
					$html .= '<td>' . htmlspecialchars($contestant['name'] ?? '') . '</td>';
					$html .= '<td>' . htmlspecialchars((string)($contestant['contestant_number'] ?? '')) . '</td>';
					$html .= '<td>' . number_format($contestant['total_score'], 2) . '</td>';
					$html .= '<td>' . number_format($contestant['avg_score'], 2) . '</td>';
					$html .= '<td>' . $contestant['subcategories_count'] . '</td>';
					$html .= '</tr>';
				}
				$html .= '</table>';
			} else {
				$html .= '<p>No contestants found.</p>';
			}
			
			$html .= '</body></html>';
			$subject = 'Contestant Summary Report';
			
		} else if ($reportType === 'judge_summary') {
			// Generate comprehensive judge summary email grouped by contest/category
			$pdo = DB::pdo();
			
			// Get contests and their categories
			$contests = $pdo->query('
				SELECT c.id as contest_id, c.name as contest_name
				FROM contests c
				ORDER BY c.name
			')->fetchAll(\PDO::FETCH_ASSOC);
			
			$html = '<html><body>';
			$html .= '<h1>Judge Summary Report</h1>';
			$html .= '<p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>';
			
			foreach ($contests as $contest) {
				// Get categories for this contest
				$categories = $pdo->prepare('
					SELECT cat.id as category_id, cat.name as category_name
					FROM categories cat
					WHERE cat.contest_id = ?
					ORDER BY cat.name
				');
				$categories->execute([$contest['contest_id']]);
				$categories = $categories->fetchAll(\PDO::FETCH_ASSOC);
				
				if (!empty($categories)) {
					$html .= '<h2>' . htmlspecialchars($contest['contest_name'] ?? '') . '</h2>';
					
					foreach ($categories as $category) {
						// Get judges for this category with their certification status
						$judges = $pdo->prepare('
							SELECT COALESCE(u.preferred_name, u.name) as judge_name,
							       COUNT(DISTINCT jc.subcategory_id) as certified_categories,
							       COUNT(DISTINCT s.subcategory_id) as total_categories
							FROM judges j
							LEFT JOIN users u ON j.id = u.id
							LEFT JOIN scores s ON j.id = s.judge_id AND s.subcategory_id IN (
								SELECT sc.id FROM subcategories sc WHERE sc.category_id = ?
							)
							LEFT JOIN judge_certifications jc ON j.id = jc.judge_id AND jc.certified_at IS NOT NULL AND jc.subcategory_id IN (
								SELECT sc.id FROM subcategories sc WHERE sc.category_id = ?
							)
							WHERE EXISTS (
								SELECT 1 FROM scores s2 
								JOIN subcategories sc2 ON s2.subcategory_id = sc2.id 
								WHERE s2.judge_id = j.id AND sc2.category_id = ?
							)
							GROUP BY j.id, u.preferred_name, u.name
							ORDER BY judge_name
						');
						$judges->execute([$category['category_id'], $category['category_id'], $category['category_id']]);
						$judges = $judges->fetchAll(\PDO::FETCH_ASSOC);
						
						if (!empty($judges)) {
							$html .= '<h3>' . htmlspecialchars($category['category_name'] ?? '') . '</h3>';
							$html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%; margin-bottom: 20px;">';
							$html .= '<tr style="background-color: #f2f2f2;"><th>Preferred Name</th><th>Certified Categories</th><th>Total Categories</th></tr>';
							
							foreach ($judges as $judge) {
								$html .= '<tr>';
								$html .= '<td>' . htmlspecialchars($judge['judge_name'] ?? '') . '</td>';
								$html .= '<td>' . ($judge['certified_categories'] ?? 0) . '</td>';
								$html .= '<td>' . ($judge['total_categories'] ?? 0) . '</td>';
								$html .= '</tr>';
							}
							$html .= '</table>';
						}
					}
				}
			}
			
			$html .= '</body></html>';
			$subject = 'Judge Summary Report';
			
		} else if ($reportType === 'contestant') {
			// Get contestant data
			$contestant = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
			$contestant->execute([$entityId]);
			$contestant = $contestant->fetch(\PDO::FETCH_ASSOC);
			
			if (!$contestant) {
				redirect('/board/print-reports?error=contestant_not_found');
				return;
			}
			
			// Get subcategories, scores, comments, deductions (simplified for email)
			$subcategories = [];
			$scores = [];
			$comments = [];
			$deductions = [];
			$tabulation = [];
			
			$html = \App\render_to_string('print/contestant', compact('contestant','subcategories','scores','comments','deductions','tabulation','isEmail'));
			$subject = 'Contestant Report: ' . ($contestant['name'] ?? '');
			
		} else if ($reportType === 'judge') {
			// Get judge data
			$judge = DB::pdo()->prepare('SELECT * FROM judges WHERE id = ?');
			$judge->execute([$entityId]);
			$judge = $judge->fetch(\PDO::FETCH_ASSOC);
			
			if (!$judge) {
				redirect('/board/print-reports?error=judge_not_found');
				return;
			}
			
			// Get subcategories, scores, comments (simplified for email)
			$subcategories = [];
			$scores = [];
			$comments = [];
			$tabulation = [];
			
			$html = \App\render_to_string('print/judge', compact('judge','subcategories','scores','comments','tabulation','isEmail'));
			$subject = 'Judge Report: ' . ($judge['name'] ?? '');
			
		} else if ($reportType === 'category') {
			// Get category data with contest information
			$category = DB::pdo()->prepare('
				SELECT c.*, co.name as contest_name 
				FROM categories c 
				JOIN contests co ON c.contest_id = co.id 
				WHERE c.id = ?
			');
			$category->execute([$entityId]);
			$category = $category->fetch(\PDO::FETCH_ASSOC);
			
			if (!$category) {
				redirect('/board/print-reports?error=category_not_found');
				return;
			}
			
			// Get contestants with their total scores for this category
			$contestants = calculate_contestant_totals_for_category($entityId);
			
			// Generate simple HTML email for category results
			$html = '<html><body>';
			$html .= '<h1>Contest Results: ' . htmlspecialchars($category['name']) . '</h1>';
			$html .= '<p><strong>Contest:</strong> ' . htmlspecialchars($category['contest_name']) . '</p>';
			$html .= '<p><strong>Category:</strong> ' . htmlspecialchars($category['name']) . '</p>';
			$html .= '<p><strong>Description:</strong> ' . htmlspecialchars($category['description'] ?? 'N/A') . '</p>';
			$html .= '<p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>';
			
			if (!empty($contestants)) {
				$html .= '<h2>Contestant Rankings</h2>';
				$html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
				$html .= '<tr style="background-color: #f2f2f2;"><th>Rank</th><th>Contestant</th><th>Number</th><th>Total Score</th></tr>';
				
				$rank = 1;
				foreach ($contestants as $contestant) {
					$html .= '<tr>';
					$html .= '<td>' . $rank++ . '</td>';
					$html .= '<td>' . htmlspecialchars($contestant['contestant_name'] ?? '') . '</td>';
					$html .= '<td>' . htmlspecialchars((string)($contestant['contestant_number'] ?? '')) . '</td>';
					$html .= '<td>' . number_format($contestant['total_current'], 2) . '</td>';
					$html .= '</tr>';
				}
				$html .= '</table>';
			} else {
				$html .= '<p>No contestants found for this category.</p>';
			}
			
			$html .= '</body></html>';
			$subject = 'Contest Results: ' . ($category['name'] ?? '');
			
		} else {
			redirect('/board/print-reports?error=invalid_report_type');
			return;
		}
		
		$sent = \App\Mailer::sendHtml($recipientEmail, $subject, $html);
		if ($sent) {
			\App\Logger::logAdminAction('email_report_sent', 'board', current_user()['id'], 
				"type={$reportType}; to={$recipientEmail}");
			redirect('/board/print-reports?success=report_emailed');
		} else {
			redirect('/board/print-reports?error=email_failed');
		}
	}
	
	public function contestSummary(array $params): void {
		require_board();
		
		$contestId = $params['id'] ?? '';
		if (empty($contestId)) {
			redirect('/board/print-reports?error=invalid_contest');
			return;
		}
		
		$pdo = DB::pdo();
		
		// Get contest data
		$contest = $pdo->prepare('SELECT * FROM contests WHERE id = ?');
		$contest->execute([$contestId]);
		$contest = $contest->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contest) {
			redirect('/board/print-reports?error=contest_not_found');
			return;
		}
		
		// Get all categories for this contest
		$categories = $pdo->prepare('SELECT * FROM categories WHERE contest_id = ? ORDER BY name');
		$categories->execute([$contestId]);
		$categories = $categories->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get summary data for each category
		$categoryData = [];
		foreach ($categories as $category) {
			// Get contestants with their total scores for this category
			$contestants = calculate_contestant_totals_for_category($category['id']);
			$categoryData[] = [
				'category' => $category,
				'contestants' => $contestants
			];
		}
		
		view('board/contest-summary', compact('contest', 'categories', 'categoryData'));
	}
}
