<?php
declare(strict_types=1);
namespace App\Routes;
use App\DB;
use function App\{view, render, redirect, param, post, request_array, current_user, is_logged_in, is_organizer, is_judge, is_emcee, require_login, require_organizer, require_emcee};

function uuid(): string { return bin2hex(random_bytes(16)); }

class HomeController {
	public function index(): void { view('home', ['title' => 'Contest Judge']); }
	public function health(): void { header('Content-Type: application/json'); echo json_encode(['ok'=>true]); }
}

class ContestController {
	public function index(): void {
		require_login();
		if (!is_organizer()) { http_response_code(403); echo 'Forbidden'; return; }
		$rows = DB::pdo()->query('SELECT * FROM contests ORDER BY start_date DESC')->fetchAll(\PDO::FETCH_ASSOC);
		view('contests/index', compact('rows'));
	}
	public function new(): void { require_organizer(); view('contests/new'); }
	public function create(): void {
		require_organizer();
		$stmt = DB::pdo()->prepare('INSERT INTO contests (id, name, start_date, end_date) VALUES (?, ?, ?, ?)');
		$stmt->execute([uuid(), post('name'), post('start_date'), post('end_date')]);
		redirect('/contests');
	}
	
	public function archive(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		
		// Get contest information
		$stmt = DB::pdo()->prepare('SELECT * FROM contests WHERE id = ?');
		$stmt->execute([$contestId]);
		$contest = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contest) {
			redirect('/contests?error=contest_not_found');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			$archivedBy = $_SESSION['user']['name'] ?? 'Unknown';
			$archivedContestId = uuid();
			
			// Archive the contest
			$stmt = $pdo->prepare('INSERT INTO archived_contests (id, name, description, start_date, end_date, archived_by) VALUES (?, ?, ?, ?, ?, ?)');
			$stmt->execute([$archivedContestId, $contest['name'], $contest['description'] ?? null, $contest['start_date'], $contest['end_date'], $archivedBy]);
			
			// Get all categories for this contest
			$stmt = $pdo->prepare('SELECT * FROM categories WHERE contest_id = ?');
			$stmt->execute([$contestId]);
			$categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($categories as $category) {
				$archivedCategoryId = uuid();
				
				// Archive the category
				$stmt = $pdo->prepare('INSERT INTO archived_categories (id, archived_contest_id, name, description) VALUES (?, ?, ?, ?)');
				$stmt->execute([$archivedCategoryId, $archivedContestId, $category['name'], $category['description'] ?? null]);
				
				// Get all subcategories for this category
				$stmt = $pdo->prepare('SELECT * FROM subcategories WHERE category_id = ?');
				$stmt->execute([$category['id']]);
				$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				foreach ($subcategories as $subcategory) {
					$archivedSubcategoryId = uuid();
					
					// Archive the subcategory
					$stmt = $pdo->prepare('INSERT INTO archived_subcategories (id, archived_category_id, name, description, score_cap) VALUES (?, ?, ?, ?, ?)');
					$stmt->execute([$archivedSubcategoryId, $archivedCategoryId, $subcategory['name'], $subcategory['description'] ?? null, $subcategory['score_cap']]);
					
					// Archive criteria
					$stmt = $pdo->prepare('SELECT * FROM criteria WHERE subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$criteria = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($criteria as $criterion) {
						$archivedCriterionId = uuid();
						$stmt = $pdo->prepare('INSERT INTO archived_criteria (id, archived_subcategory_id, name, max_score) VALUES (?, ?, ?, ?)');
						$stmt->execute([$archivedCriterionId, $archivedSubcategoryId, $criterion['name'], $criterion['max_score']]);
					}
					
					// Archive scores
					$stmt = $pdo->prepare('SELECT s.*, c.id as criterion_id FROM scores s JOIN criteria c ON s.criterion_id = c.id WHERE s.subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$scores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($scores as $score) {
						$archivedScoreId = uuid();
						$stmt = $pdo->prepare('INSERT INTO archived_scores (id, archived_subcategory_id, archived_contestant_id, archived_judge_id, archived_criterion_id, score) VALUES (?, ?, ?, ?, ?, ?)');
						$stmt->execute([$archivedScoreId, $archivedSubcategoryId, $score['contestant_id'], $score['judge_id'], $score['criterion_id'], $score['score']]);
					}
					
					// Archive judge comments
					$stmt = $pdo->prepare('SELECT * FROM judge_comments WHERE subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$comments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($comments as $comment) {
						$archivedCommentId = uuid();
						$stmt = $pdo->prepare('INSERT INTO archived_judge_comments (id, archived_subcategory_id, archived_contestant_id, archived_judge_id, comment) VALUES (?, ?, ?, ?, ?)');
						$stmt->execute([$archivedCommentId, $archivedSubcategoryId, $comment['contestant_id'], $comment['judge_id'], $comment['comment']]);
					}
					
					// Archive judge certifications
					$stmt = $pdo->prepare('SELECT * FROM judge_certifications WHERE subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$certifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($certifications as $cert) {
						$archivedCertId = uuid();
						$stmt = $pdo->prepare('INSERT INTO archived_judge_certifications (id, archived_subcategory_id, archived_judge_id, signature_name, certified_at) VALUES (?, ?, ?, ?, ?)');
						$stmt->execute([$archivedCertId, $archivedSubcategoryId, $cert['judge_id'], $cert['signature_name'], $cert['certified_at']]);
					}
					
					// Archive subcategory assignments
					$stmt = $pdo->prepare('SELECT * FROM subcategory_contestants WHERE subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$subcategoryContestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($subcategoryContestants as $assignment) {
						$stmt = $pdo->prepare('INSERT INTO archived_subcategory_contestants (archived_subcategory_id, archived_contestant_id) VALUES (?, ?)');
						$stmt->execute([$archivedSubcategoryId, $assignment['contestant_id']]);
					}
					
					$stmt = $pdo->prepare('SELECT * FROM subcategory_judges WHERE subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$subcategoryJudges = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($subcategoryJudges as $assignment) {
						$stmt = $pdo->prepare('INSERT INTO archived_subcategory_judges (archived_subcategory_id, archived_judge_id) VALUES (?, ?)');
						$stmt->execute([$archivedSubcategoryId, $assignment['judge_id']]);
					}
				}
				
				// Archive category assignments
				$stmt = $pdo->prepare('SELECT * FROM category_contestants WHERE category_id = ?');
				$stmt->execute([$category['id']]);
				$categoryContestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				foreach ($categoryContestants as $assignment) {
					$stmt = $pdo->prepare('INSERT INTO archived_category_contestants (archived_category_id, archived_contestant_id) VALUES (?, ?)');
					$stmt->execute([$archivedCategoryId, $assignment['contestant_id']]);
				}
				
				$stmt = $pdo->prepare('SELECT * FROM category_judges WHERE category_id = ?');
				$stmt->execute([$category['id']]);
				$categoryJudges = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				foreach ($categoryJudges as $assignment) {
					$stmt = $pdo->prepare('INSERT INTO archived_category_judges (archived_category_id, archived_judge_id) VALUES (?, ?)');
					$stmt->execute([$archivedCategoryId, $assignment['judge_id']]);
				}
			}
			
			// Archive contestants and judges (only if they're not used in other contests)
			$stmt = $pdo->prepare('SELECT DISTINCT c.* FROM contestants c JOIN subcategory_contestants sc ON c.id = sc.contestant_id JOIN subcategories s ON sc.subcategory_id = s.id JOIN categories cat ON s.category_id = cat.id WHERE cat.contest_id = ?');
			$stmt->execute([$contestId]);
			$contestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($contestants as $contestant) {
				// Check if contestant is used in other contests
				$stmt = $pdo->prepare('SELECT COUNT(*) FROM subcategory_contestants sc JOIN subcategories s ON sc.subcategory_id = s.id JOIN categories cat ON s.category_id = cat.id WHERE sc.contestant_id = ? AND cat.contest_id != ?');
				$stmt->execute([$contestant['id'], $contestId]);
				$otherContests = $stmt->fetchColumn();
				
				if ($otherContests == 0) {
					$stmt = $pdo->prepare('INSERT INTO archived_contestants (id, name, email, gender, contestant_number, bio, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
					$stmt->execute([$contestant['id'], $contestant['name'], $contestant['email'], $contestant['gender'], $contestant['contestant_number'], $contestant['bio'], $contestant['image_path']]);
				}
			}
			
			$stmt = $pdo->prepare('SELECT DISTINCT j.* FROM judges j JOIN subcategory_judges sj ON j.id = sj.judge_id JOIN subcategories s ON sj.subcategory_id = s.id JOIN categories cat ON s.category_id = cat.id WHERE cat.contest_id = ?');
			$stmt->execute([$contestId]);
			$judges = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($judges as $judge) {
				// Check if judge is used in other contests
				$stmt = $pdo->prepare('SELECT COUNT(*) FROM subcategory_judges sj JOIN subcategories s ON sj.subcategory_id = s.id JOIN categories cat ON s.category_id = cat.id WHERE sj.judge_id = ? AND cat.contest_id != ?');
				$stmt->execute([$judge['id'], $contestId]);
				$otherContests = $stmt->fetchColumn();
				
				if ($otherContests == 0) {
					$stmt = $pdo->prepare('INSERT INTO archived_judges (id, name, email, gender, bio, image_path) VALUES (?, ?, ?, ?, ?, ?)');
					$stmt->execute([$judge['id'], $judge['name'], $judge['email'], $judge['gender'], $judge['bio'], $judge['image_path']]);
				}
			}
			
			// Delete the original contest and all associated data
			$pdo->prepare('DELETE FROM contests WHERE id = ?')->execute([$contestId]);
			
			$pdo->commit();
			\App\Logger::logContestArchive($contestId, $contest['name']);
			redirect('/contests?success=contest_archived');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/contests?error=archive_failed');
		}
	}
	
	public function archivedContests(): void {
		require_organizer();
		$rows = DB::pdo()->query('SELECT * FROM archived_contests ORDER BY archived_at DESC')->fetchAll(\PDO::FETCH_ASSOC);
		view('contests/archived', compact('rows'));
	}
}

class CategoryController {
	public function index(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		$contest = DB::pdo()->prepare('SELECT * FROM contests WHERE id = ?');
		$contest->execute([$contestId]);
		$contest = $contest->fetch(\PDO::FETCH_ASSOC);
		$categories = DB::pdo()->prepare('SELECT * FROM categories WHERE contest_id = ?');
		$categories->execute([$contestId]);
		$categories = $categories->fetchAll(\PDO::FETCH_ASSOC);
		view('categories/index', compact('contest','categories'));
	}
	public function new(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		$contest = DB::pdo()->prepare('SELECT * FROM contests WHERE id = ?');
		$contest->execute([$contestId]);
		$contest = $contest->fetch(\PDO::FETCH_ASSOC);
		view('categories/new', compact('contest'));
	}
	public function create(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		$stmt = DB::pdo()->prepare('INSERT INTO categories (id, contest_id, name) VALUES (?, ?, ?)');
		$stmt->execute([uuid(), $contestId, post('name')]);
		redirect('/contests/' . $contestId . '/categories');
	}
}

class ContestSubcategoryController {
	public function index(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		$contest = DB::pdo()->prepare('SELECT * FROM contests WHERE id = ?');
		$contest->execute([$contestId]);
		$contest = $contest->fetch(\PDO::FETCH_ASSOC);
		
		// Get all subcategories for this contest
		$sql = 'SELECT sc.*, c.name as category_name 
				FROM subcategories sc 
				JOIN categories c ON sc.category_id = c.id 
				WHERE c.contest_id = ? 
				ORDER BY c.name, sc.name';
		$stmt = DB::pdo()->prepare($sql);
		$stmt->execute([$contestId]);
		$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('contests/subcategories', compact('contest','subcategories'));
	}
}

class SubcategoryController {
	public function index(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$category = DB::pdo()->prepare('SELECT * FROM categories WHERE id = ?');
		$category->execute([$categoryId]);
		$category = $category->fetch(\PDO::FETCH_ASSOC);
		$subcategories = DB::pdo()->prepare('SELECT * FROM subcategories WHERE category_id = ?');
		$subcategories->execute([$categoryId]);
		$subcategories = $subcategories->fetchAll(\PDO::FETCH_ASSOC);
		view('subcategories/index', compact('category','subcategories'));
	}
	public function new(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$category = DB::pdo()->prepare('SELECT * FROM categories WHERE id = ?');
		$category->execute([$categoryId]);
		$category = $category->fetch(\PDO::FETCH_ASSOC);
		view('subcategories/new', compact('category'));
	}
	public function create(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		$subcategoryId = uuid();
		$stmt = $pdo->prepare('INSERT INTO subcategories (id, category_id, name, description, score_cap) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([$subcategoryId, $categoryId, post('name'), post('description') ?: null, post('score_cap') ?: null]);
		
		// Automatically assign category-level contestants and judges to this new subcategory
		$categoryContestants = $pdo->prepare('SELECT contestant_id FROM category_contestants WHERE category_id = ?');
		$categoryContestants->execute([$categoryId]);
		$contestantIds = array_column($categoryContestants->fetchAll(\PDO::FETCH_ASSOC), 'contestant_id');
		
		$categoryJudges = $pdo->prepare('SELECT judge_id FROM category_judges WHERE category_id = ?');
		$categoryJudges->execute([$categoryId]);
		$judgeIds = array_column($categoryJudges->fetchAll(\PDO::FETCH_ASSOC), 'judge_id');
		
		$insC = $pdo->prepare('INSERT INTO subcategory_contestants (subcategory_id, contestant_id) VALUES (?, ?)');
		$insJ = $pdo->prepare('INSERT INTO subcategory_judges (subcategory_id, judge_id) VALUES (?, ?)');
		
		foreach ($contestantIds as $id) {
			$insC->execute([$subcategoryId, $id]);
		}
		foreach ($judgeIds as $id) {
			$insJ->execute([$subcategoryId, $id]);
		}
		
		// Create a default criterion with max score 60 if none exist
		$insC = $pdo->prepare('INSERT INTO criteria (id, subcategory_id, name, max_score) VALUES (?, ?, ?, ?)');
		$insC->execute([uuid(), $subcategoryId, 'Overall Performance', 60]);
		
		$pdo->commit();
		redirect('/categories/' . $categoryId . '/subcategories');
	}
	public function templates(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$category = DB::pdo()->prepare('SELECT * FROM categories WHERE id = ?');
		$category->execute([$categoryId]);
		$category = $category->fetch(\PDO::FETCH_ASSOC);
		$templates = DB::pdo()->query('SELECT * FROM subcategory_templates ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		view('subcategories/templates', compact('category','templates'));
	}
	public function createFromTemplate(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$templateId = post('template_id');
		$scoreCap = post('score_cap') ?: null;
		
		// Get template info
		$template = DB::pdo()->prepare('SELECT * FROM subcategory_templates WHERE id = ?');
		$template->execute([$templateId]);
		$template = $template->fetch(\PDO::FETCH_ASSOC);
		
		if (!$template) {
			redirect('/categories/' . $categoryId . '/subcategories');
			return;
		}
		
		// Get subcategory names from template
		$subcategoryNames = [];
		if (!empty($template['subcategory_names'])) {
			$subcategoryNames = json_decode($template['subcategory_names'], true) ?: [];
		}
		
		if (empty($subcategoryNames)) {
			redirect('/categories/' . $categoryId . '/subcategories');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		// Create each subcategory
		foreach ($subcategoryNames as $subcategoryName) {
			$subcategoryId = uuid();
			$stmt = $pdo->prepare('INSERT INTO subcategories (id, category_id, name, description, score_cap) VALUES (?, ?, ?, ?, ?)');
			$stmt->execute([$subcategoryId, $categoryId, $subcategoryName, $template['description'], $scoreCap]);
			
			// Copy criteria from template
			$templateCriteria = $pdo->prepare('SELECT * FROM template_criteria WHERE template_id = ?');
			$templateCriteria->execute([$templateId]);
			$criteria = $templateCriteria->fetchAll(\PDO::FETCH_ASSOC);
			$insC = $pdo->prepare('INSERT INTO criteria (id, subcategory_id, name, max_score) VALUES (?, ?, ?, ?)');
			foreach ($criteria as $c) {
				$insC->execute([uuid(), $subcategoryId, $c['name'], $c['max_score']]);
			}
			
			// If no criteria exist in template, create a default criterion with template's max score
			if (empty($criteria)) {
				$templateMaxScore = $template['max_score'] ?? 60;
				$insC->execute([uuid(), $subcategoryId, 'Overall Performance', $templateMaxScore]);
			}
			
			// Automatically assign category-level contestants and judges to this new subcategory
			$categoryContestants = $pdo->prepare('SELECT contestant_id FROM category_contestants WHERE category_id = ?');
			$categoryContestants->execute([$categoryId]);
			$contestantIds = array_column($categoryContestants->fetchAll(\PDO::FETCH_ASSOC), 'contestant_id');
			
			$categoryJudges = $pdo->prepare('SELECT judge_id FROM category_judges WHERE category_id = ?');
			$categoryJudges->execute([$categoryId]);
			$judgeIds = array_column($categoryJudges->fetchAll(\PDO::FETCH_ASSOC), 'judge_id');
			
			$insSC = $pdo->prepare('INSERT INTO subcategory_contestants (subcategory_id, contestant_id) VALUES (?, ?)');
			$insSJ = $pdo->prepare('INSERT INTO subcategory_judges (subcategory_id, judge_id) VALUES (?, ?)');
			
			foreach ($contestantIds as $id) {
				$insSC->execute([$subcategoryId, $id]);
			}
			foreach ($judgeIds as $id) {
				$insSJ->execute([$subcategoryId, $id]);
			}
		}
		
		$pdo->commit();
		$_SESSION['success_message'] = 'Created ' . count($subcategoryNames) . ' subcategories from template successfully!';
		redirect('/categories/' . $categoryId . '/subcategories');
	}
	
	public function bulkDelete(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$subcategoryIds = post('subcategory_ids', []);
		
		if (empty($subcategoryIds)) {
			redirect('/categories/' . $categoryId . '/subcategories?error=no_subcategories_selected');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			foreach ($subcategoryIds as $subcategoryId) {
				// Delete all related data
				$pdo->prepare('DELETE FROM judge_certifications WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM judge_comments WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM scores WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM criteria WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM subcategory_contestants WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM subcategory_judges WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM subcategories WHERE id = ?')->execute([$subcategoryId]);
			}
			$pdo->commit();
			redirect('/categories/' . $categoryId . '/subcategories?success=subcategories_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/categories/' . $categoryId . '/subcategories?error=delete_failed');
		}
	}
	
	public function bulkUpdate(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$updates = post('updates', []);
		
		if (empty($updates)) {
			redirect('/categories/' . $categoryId . '/subcategories?error=no_updates');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			foreach ($updates as $subcategoryId => $data) {
				if (isset($data['name']) || isset($data['description']) || isset($data['score_cap'])) {
					$fields = [];
					$values = [];
					
					if (isset($data['name'])) {
						$fields[] = 'name = ?';
						$values[] = $data['name'];
					}
					if (isset($data['description'])) {
						$fields[] = 'description = ?';
						$values[] = $data['description'];
					}
					if (isset($data['score_cap'])) {
						$fields[] = 'score_cap = ?';
						$values[] = (int)$data['score_cap'];
					}
					
					$values[] = $subcategoryId;
					$pdo->prepare('UPDATE subcategories SET ' . implode(', ', $fields) . ' WHERE id = ?')
						->execute($values);
				}
			}
			$pdo->commit();
			redirect('/categories/' . $categoryId . '/subcategories?success=subcategories_updated');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/categories/' . $categoryId . '/subcategories?error=update_failed');
		}
	}
}

class PeopleController {
	public function index(): void {
		require_organizer();
		$contestants = DB::pdo()->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		$judges = DB::pdo()->query('SELECT * FROM judges ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		view('people/index', compact('contestants','judges'));
	}
	public function new(): void { require_organizer(); view('people/new'); }
	public function createContestant(): void {
		require_organizer();
		$imagePath = null;
		
		// Handle image upload
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = __DIR__ . '/../../public/uploads/contestants/';
			// Create directory if it doesn't exist
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0755, true);
			}
			$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
			$filename = uuid() . '.' . $extension;
			$imagePath = '/uploads/contestants/' . $filename;
			
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
				$_SESSION['error_message'] = 'Failed to upload image - check directory permissions';
				redirect('/people/new');
				return;
			}
		}
		
		// Auto-assign contestant number if not provided
		$contestantNumber = post('contestant_number');
		if (!$contestantNumber) {
			$stmt = DB::pdo()->query('SELECT MAX(contestant_number) as max_num FROM contestants WHERE contestant_number IS NOT NULL');
			$result = $stmt->fetch(\PDO::FETCH_ASSOC);
			$contestantNumber = ($result['max_num'] ?? 0) + 1;
		}
		
		$stmt = DB::pdo()->prepare('INSERT INTO contestants (id, name, email, contestant_number, bio, image_path) VALUES (?, ?, ?, ?, ?, ?)');
		$stmt->execute([uuid(), post('name'), post('email') ?: null, $contestantNumber, post('bio') ?: null, $imagePath]);
		redirect('/people');
	}
	public function createJudge(): void {
		require_organizer();
		$imagePath = null;
		
		// Handle image upload
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = __DIR__ . '/../../public/uploads/judges/';
			// Create directory if it doesn't exist
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0755, true);
			}
			$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
			$filename = uuid() . '.' . $extension;
			$imagePath = '/uploads/judges/' . $filename;
			
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
				$_SESSION['error_message'] = 'Failed to upload image - check directory permissions';
				redirect('/people/new');
				return;
			}
		}
		
		$stmt = DB::pdo()->prepare('INSERT INTO judges (id, name, email, bio, image_path) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([uuid(), post('name'), post('email') ?: null, post('bio') ?: null, $imagePath]);
		redirect('/people');
	}
	public function editContestant(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
		$stmt->execute([$id]);
		$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$contestant) {
			redirect('/people');
			return;
		}
		view('people/edit_contestant', compact('contestant'));
	}
	public function updateContestant(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$imagePath = null;
		
		// Handle image upload
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = __DIR__ . '/../../public/uploads/contestants/';
			// Create directory if it doesn't exist
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0755, true);
			}
			$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
			$filename = uuid() . '.' . $extension;
			$imagePath = '/uploads/contestants/' . $filename;
			
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
				$_SESSION['error_message'] = 'Failed to upload image - check directory permissions';
				redirect('/people/contestants/' . $id . '/edit');
				return;
			}
		}
		
		// Get current image path if no new image uploaded
		if (!$imagePath) {
			$stmt = DB::pdo()->prepare('SELECT image_path FROM contestants WHERE id = ?');
			$stmt->execute([$id]);
			$current = $stmt->fetch(\PDO::FETCH_ASSOC);
			$imagePath = $current['image_path'] ?? null;
		}
		
		$stmt = DB::pdo()->prepare('UPDATE contestants SET name = ?, email = ?, gender = ?, contestant_number = ?, bio = ?, image_path = ? WHERE id = ?');
		$stmt->execute([post('name'), post('email') ?: null, post('gender') ?: null, post('contestant_number') ?: null, post('bio') ?: null, $imagePath, $id]);
		$_SESSION['success_message'] = 'Contestant updated successfully!';
		redirect('/people');
	}
	public function deleteContestant(array $params): void {
		require_organizer();
		$id = param('id', $params);
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Delete associated image file
			$stmt = $pdo->prepare('SELECT image_path FROM contestants WHERE id = ?');
			$stmt->execute([$id]);
			$imagePath = $stmt->fetchColumn();
			if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
				unlink(__DIR__ . '/../../public' . $imagePath);
			}
			
			// Delete all related data
			$pdo->prepare('DELETE FROM judge_comments WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM scores WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM subcategory_contestants WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM category_contestants WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM contestants WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			$_SESSION['success_message'] = 'Contestant and all associated data deleted successfully!';
			redirect('/people');
		} catch (\Exception $e) {
			$pdo->rollBack();
			$_SESSION['error_message'] = 'Failed to delete contestant: ' . $e->getMessage();
			redirect('/people');
		}
	}
	public function viewJudgeBio(array $params): void {
		require_login();
		$id = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT * FROM judges WHERE id = ?');
		$stmt->execute([$id]);
		$judge = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$judge) {
			redirect('/people');
			return;
		}
		view('people/judge_bio', compact('judge'));
	}
	public function editJudge(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT * FROM judges WHERE id = ?');
		$stmt->execute([$id]);
		$judge = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$judge) {
			redirect('/people');
			return;
		}
		view('people/edit_judge', compact('judge'));
	}
	public function updateJudge(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$imagePath = null;
		
		// Handle image upload
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = __DIR__ . '/../../public/uploads/judges/';
			// Create directory if it doesn't exist
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0755, true);
			}
			$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
			$filename = uuid() . '.' . $extension;
			$imagePath = '/uploads/judges/' . $filename;
			
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
				$_SESSION['error_message'] = 'Failed to upload image - check directory permissions';
				redirect('/people/judges/' . $id . '/edit');
				return;
			}
		}
		
		// Get current image path if no new image uploaded
		if (!$imagePath) {
			$stmt = DB::pdo()->prepare('SELECT image_path FROM judges WHERE id = ?');
			$stmt->execute([$id]);
			$current = $stmt->fetch(\PDO::FETCH_ASSOC);
			$imagePath = $current['image_path'] ?? null;
		}
		
		$stmt = DB::pdo()->prepare('UPDATE judges SET name = ?, email = ?, gender = ?, bio = ?, image_path = ? WHERE id = ?');
		$stmt->execute([post('name'), post('email') ?: null, post('gender') ?: null, post('bio') ?: null, $imagePath, $id]);
		$_SESSION['success_message'] = 'Judge updated successfully!';
		redirect('/people');
	}
	public function deleteJudge(array $params): void {
		require_organizer();
		$id = param('id', $params);
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Delete associated image file
			$stmt = $pdo->prepare('SELECT image_path FROM judges WHERE id = ?');
			$stmt->execute([$id]);
			$imagePath = $stmt->fetchColumn();
			if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
				unlink(__DIR__ . '/../../public' . $imagePath);
			}
			
			// Delete all related data
			$pdo->prepare('DELETE FROM judge_certifications WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM judge_comments WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM scores WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM subcategory_judges WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM category_judges WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM judges WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			$_SESSION['success_message'] = 'Judge and all associated data deleted successfully!';
			redirect('/people');
		} catch (\Exception $e) {
			$pdo->rollBack();
			$_SESSION['error_message'] = 'Failed to delete judge: ' . $e->getMessage();
			redirect('/people');
		}
	}
}

class AssignmentController {
	public function edit(array $params): void {
		$subcategoryId = param('id', $params);
		if (is_judge()) {
			// ensure judge is assigned to this subcategory
			$allowed = DB::pdo()->prepare('SELECT 1 FROM subcategory_judges WHERE subcategory_id = ? AND judge_id = ?');
			$allowed->execute([$subcategoryId, current_user()['judge_id'] ?? '']);
			if (!$allowed->fetchColumn()) { http_response_code(403); echo 'Forbidden'; return; }
		} else {
			require_organizer();
		}
		$subcategory = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		$contestants = DB::pdo()->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		$judges = DB::pdo()->query('SELECT * FROM judges ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		$assignedContestants = DB::pdo()->prepare('SELECT contestant_id FROM subcategory_contestants WHERE subcategory_id = ?');
		$assignedContestants->execute([$subcategoryId]);
		$assignedContestants = array_column($assignedContestants->fetchAll(\PDO::FETCH_ASSOC), 'contestant_id');
		$assignedJudges = DB::pdo()->prepare('SELECT judge_id FROM subcategory_judges WHERE subcategory_id = ?');
		$assignedJudges->execute([$subcategoryId]);
		$assignedJudges = array_column($assignedJudges->fetchAll(\PDO::FETCH_ASSOC), 'judge_id');
		view('assignments/edit', compact('subcategory','contestants','judges','assignedContestants','assignedJudges'));
	}
	public function update(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$contestants = request_array('contestants');
		$judges = request_array('judges');
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		$pdo->prepare('DELETE FROM subcategory_contestants WHERE subcategory_id = ?')->execute([$subcategoryId]);
		$pdo->prepare('DELETE FROM subcategory_judges WHERE subcategory_id = ?')->execute([$subcategoryId]);
		$insC = $pdo->prepare('INSERT INTO subcategory_contestants (subcategory_id, contestant_id) VALUES (?, ?)');
		$insJ = $pdo->prepare('INSERT INTO subcategory_judges (subcategory_id, judge_id) VALUES (?, ?)');
		foreach ($contestants as $id) { if ($id) $insC->execute([$subcategoryId, $id]); }
		foreach ($judges as $id) { if ($id) $insJ->execute([$subcategoryId, $id]); }
		$pdo->commit();
		$_SESSION['success_message'] = 'Assignments updated successfully!';
		redirect('/subcategories/' . $subcategoryId . '/assign');
	}
}

class CriteriaController {
	public function index(array $params): void {
		require_organizer(); // Only organizers can manage criteria
		$subcategoryId = param('id', $params);
		$subcategory = DB::pdo()->prepare('SELECT * FROM subcategories WHERE id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		$stmt = DB::pdo()->prepare('SELECT * FROM criteria WHERE subcategory_id = ?');
		$stmt->execute([$subcategoryId]);
		$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		view('criteria/index', compact('subcategory','rows'));
	}
	public function new(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$subcategory = DB::pdo()->prepare('SELECT * FROM subcategories WHERE id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		view('criteria/new', compact('subcategory'));
	}
	public function create(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$maxScore = post('max_score') ?: 60; // Default to 60 if not provided
		
		// Auto-generate criterion name
		$stmt = DB::pdo()->prepare('SELECT COUNT(*) as count FROM criteria WHERE subcategory_id = ?');
		$stmt->execute([$subcategoryId]);
		$count = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
		$criterionName = 'Criterion ' . ($count + 1);
		
		$stmt = DB::pdo()->prepare('INSERT INTO criteria (id, subcategory_id, name, max_score) VALUES (?, ?, ?, ?)');
		$stmt->execute([uuid(), $subcategoryId, $criterionName, (int)$maxScore]);
		redirect('/subcategories/' . $subcategoryId . '/criteria');
	}
	
	public function bulkDelete(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$criteriaIds = post('criteria_ids', []);
		
		if (empty($criteriaIds)) {
			redirect('/subcategories/' . $subcategoryId . '/criteria?error=no_criteria_selected');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			foreach ($criteriaIds as $criterionId) {
				$pdo->prepare('DELETE FROM criteria WHERE id = ?')->execute([$criterionId]);
			}
			$pdo->commit();
			redirect('/subcategories/' . $subcategoryId . '/criteria?success=criteria_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/subcategories/' . $subcategoryId . '/criteria?error=delete_failed');
		}
	}
	
	public function bulkUpdate(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$updates = post('updates', []);
		
		if (empty($updates)) {
			redirect('/subcategories/' . $subcategoryId . '/criteria?error=no_updates');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			foreach ($updates as $criterionId => $data) {
				if (isset($data['max_score'])) {
					$pdo->prepare('UPDATE criteria SET max_score = ? WHERE id = ?')
						->execute([(int)$data['max_score'], $criterionId]);
				}
			}
			$pdo->commit();
			redirect('/subcategories/' . $subcategoryId . '/criteria?success=criteria_updated');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/subcategories/' . $subcategoryId . '/criteria?error=update_failed');
		}
	}
}

// Organizer can set score cap per subcategory
class SubcategoryAdminController {
	public function edit(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$stmt->execute([$subcategoryId]);
		$subcategory = $stmt->fetch(\PDO::FETCH_ASSOC);
		view('subcategories/admin_edit', compact('subcategory'));
	}
	public function update(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$cap = (int)post('score_cap');
		DB::pdo()->prepare('UPDATE subcategories SET score_cap=? WHERE id=?')->execute([$cap, $subcategoryId]);
		redirect('/categories/' . (string)post('category_id') . '/subcategories');
	}
}

class ScoringController {
	public function scoreContestant(array $params): void {
		require_login();
		$subcategoryId = param('subcategoryId', $params);
		$contestantId = param('contestantId', $params);
		
		if (is_judge()) {
			$judgeId = current_user()['judge_id'] ?? '';
			if (empty($judgeId)) {
				http_response_code(403); 
				echo 'Judge account not properly linked. Please contact administrator.'; 
				return;
			}
			
			// Verify judge is assigned to this subcategory
			$stmt = DB::pdo()->prepare('SELECT COUNT(*) FROM subcategory_judges WHERE subcategory_id = ? AND judge_id = ?');
			$stmt->execute([$subcategoryId, $judgeId]);
			if ($stmt->fetchColumn() == 0) {
				http_response_code(403);
				echo 'You are not assigned to this subcategory.';
				return;
			}
		}
		
		// Get subcategory info
		$stmt = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$stmt->execute([$subcategoryId]);
		$subcategory = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$subcategory) {
			http_response_code(404);
			echo 'Subcategory not found.';
			return;
		}
		
		// Get contestant info
		$stmt = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
		$stmt->execute([$contestantId]);
		$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contestant) {
			http_response_code(404);
			echo 'Contestant not found.';
			return;
		}
		
		// Get criteria for this subcategory
		$stmt = DB::pdo()->prepare('SELECT * FROM criteria WHERE subcategory_id = ? ORDER BY name');
		$stmt->execute([$subcategoryId]);
		$criteria = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get existing scores for this judge and contestant
		$existingScores = [];
		if (is_judge()) {
			$stmt = DB::pdo()->prepare('SELECT criterion_id, score FROM scores WHERE subcategory_id = ? AND contestant_id = ? AND judge_id = ?');
			$stmt->execute([$subcategoryId, $contestantId, $judgeId]);
			$existingScores = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
		}
		
		// Get existing comment for this judge and contestant
		$existingComment = '';
		if (is_judge()) {
			$stmt = DB::pdo()->prepare('SELECT comment FROM judge_comments WHERE subcategory_id = ? AND contestant_id = ? AND judge_id = ?');
			$stmt->execute([$subcategoryId, $contestantId, $judgeId]);
			$existingComment = $stmt->fetchColumn() ?: '';
		}
		
		// Check if scores are certified for this specific contestant
		$isCertified = false;
		if (is_judge()) {
			$stmt = DB::pdo()->prepare('SELECT COUNT(*) FROM judge_certifications WHERE subcategory_id = ? AND contestant_id = ? AND judge_id = ?');
			$stmt->execute([$subcategoryId, $contestantId, $judgeId]);
			$isCertified = $stmt->fetchColumn() > 0;
		}
		
		view('scoring/contestant', compact('subcategory', 'contestant', 'criteria', 'existingScores', 'existingComment', 'isCertified'));
	}

	public function index(array $params): void {
		require_login();
		$subcategoryId = param('id', $params);
		if (is_judge()) {
			$judgeId = current_user()['judge_id'] ?? '';
			if (empty($judgeId)) {
				http_response_code(403); 
				echo 'Judge account not properly linked. Please contact administrator.'; 
				return;
			}
			$allowed = DB::pdo()->prepare('SELECT 1 FROM subcategory_judges WHERE subcategory_id = ? AND judge_id = ?');
			$allowed->execute([$subcategoryId, $judgeId]);
			if (!$allowed->fetchColumn()) { 
				http_response_code(403); 
				echo 'You are not assigned to judge this subcategory.'; 
				return; 
			}
		}
		$subcategory = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		// Lock if certified and not organizer
		$locked = false;
		if (is_judge()) {
			$chk = DB::pdo()->prepare('SELECT 1 FROM judge_certifications WHERE subcategory_id=? AND judge_id=?');
			$chk->execute([$subcategoryId, current_user()['judge_id'] ?? '']);
			$locked = (bool)$chk->fetchColumn();
		}
		$contestants = DB::pdo()->prepare('SELECT con.* FROM subcategory_contestants sc JOIN contestants con ON sc.contestant_id = con.id WHERE sc.subcategory_id = ? ORDER BY con.name');
		$contestants->execute([$subcategoryId]);
		$contestants = $contestants->fetchAll(\PDO::FETCH_ASSOC);
		$criteria = DB::pdo()->prepare('SELECT * FROM criteria WHERE subcategory_id = ? ORDER BY name');
		$criteria->execute([$subcategoryId]);
		$criteria = $criteria->fetchAll(\PDO::FETCH_ASSOC);
		$judges = DB::pdo()->prepare('SELECT j.* FROM subcategory_judges sj JOIN judges j ON sj.judge_id = j.id WHERE sj.subcategory_id = ? ORDER BY j.name');
		$judges->execute([$subcategoryId]);
		$judges = $judges->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get existing scores for this judge if they're a judge
		$existingScores = [];
		if (is_judge()) {
			$judgeId = current_user()['judge_id'] ?? '';
			$stmt = DB::pdo()->prepare('SELECT contestant_id, criterion_id, score FROM scores WHERE subcategory_id = ? AND judge_id = ?');
			$stmt->execute([$subcategoryId, $judgeId]);
			$existingScores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		}
		
		view('scoring/index', compact('subcategory','contestants','criteria','judges','locked','existingScores'));
	}
	public function submit(array $params): void {
		require_login();
		$subcategoryId = param('id', $params);
		$contestantId = post('contestant_id');
		$judgeId = is_judge() ? (current_user()['judge_id'] ?? '') : post('judge_id');
		if (is_judge()) {
			$allowed = DB::pdo()->prepare('SELECT 1 FROM subcategory_judges WHERE subcategory_id = ? AND judge_id = ?');
			$allowed->execute([$subcategoryId, $judgeId]);
			if (!$allowed->fetchColumn()) { http_response_code(403); echo 'Forbidden'; return; }
			// Prevent edits after certification for this specific contestant
			$chk = DB::pdo()->prepare('SELECT 1 FROM judge_certifications WHERE subcategory_id=? AND contestant_id=? AND judge_id=?');
			$chk->execute([$subcategoryId, $contestantId, $judgeId]);
			if ($chk->fetchColumn()) { http_response_code(423); echo 'Locked'; return; }
		}
		$scores = $_POST['scores'] ?? [];
		$comments = $_POST['comments'] ?? [];
		
		$pdo = DB::pdo();
		$now = date('c');
		$pdo->beginTransaction();
		
		// Handle scores - they come as scores[criterion_id] = value
		foreach ($scores as $criterionId => $value) {
			if ($value !== '' && $value !== null) {
				$stmt = $pdo->prepare('INSERT OR REPLACE INTO scores (id, subcategory_id, contestant_id, judge_id, criterion_id, score, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
				$stmt->execute([uuid(), $subcategoryId, $contestantId, $judgeId, $criterionId, (float)$value, $now]);
			}
		}
		
		// Handle comments - they come as comments[contestant_id] = text
		foreach ($comments as $commentContestantId => $text) {
			if ($text !== '' && $text !== null) {
				$stmt = $pdo->prepare('INSERT OR REPLACE INTO judge_comments (id, subcategory_id, contestant_id, judge_id, comment, created_at) VALUES (?, ?, ?, ?, ?, ?)');
				$stmt->execute([uuid(), $subcategoryId, $commentContestantId, $judgeId, (string)$text, $now]);
			}
		}
		if (is_judge()) {
			$signature = trim((string)post('signature_name'));
			if ($signature === '') { 
				// Use preferred name as default signature
				$signature = current_user()['preferred_name'] ?? current_user()['name'];
			}
			
			// Validate signature matches judge's preferred name
			$judgePreferredName = current_user()['preferred_name'] ?? current_user()['name'];
			if (strtolower(trim($signature)) !== strtolower(trim($judgePreferredName))) {
				$pdo->rollback();
				redirect('/score/' . $subcategoryId . '/contestant/' . $contestantId . '?error=signature_mismatch');
				return;
			}
			
			$pdo->prepare('INSERT OR REPLACE INTO judge_certifications (id, subcategory_id, contestant_id, judge_id, signature_name, certified_at) VALUES (?,?,?,?,?,?)')
				->execute([uuid(), $subcategoryId, $contestantId, $judgeId, $signature, $now]);
		}
		$pdo->commit();
		
		// Log the scoring action
		$scoreCount = count($scores);
		\App\Logger::logScoreSubmission($subcategoryId, '', $judgeId, $scoreCount);
		
		// Add success message to session
		$_SESSION['success_message'] = 'Scores submitted successfully!';
		redirect('/score/' . $subcategoryId);
	}
	
	public function unsign(array $params): void {
		require_organizer(); // Only organizers can unsign scores
		$subcategoryId = param('id', $params);
		$judgeId = post('judge_id');
		
		if (empty($judgeId)) {
			redirect('/score/' . $subcategoryId . '?error=no_judge');
			return;
		}
		
		// Remove the certification
		$pdo = DB::pdo();
		$pdo->prepare('DELETE FROM judge_certifications WHERE subcategory_id = ? AND judge_id = ?')
			->execute([$subcategoryId, $judgeId]);
		
		\App\Logger::logAdminAction('unsign_scores', 'subcategory', $subcategoryId, "Judge: $judgeId");
		
		$_SESSION['success_message'] = 'Judge scores have been unsigned successfully!';
		redirect('/score/' . $subcategoryId);
	}
}

class ResultsController {
	public function resultsIndex(): void {
		require_login();
		
		// Get subcategories based on user role
		if (is_organizer()) {
			// Organizers can see all subcategories
			$stmt = DB::pdo()->query('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id ORDER BY c.name, s.name');
			$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		} elseif (is_judge()) {
			// Judges can only see their assigned subcategories
			$judgeId = current_user()['judge_id'] ?? '';
			$stmt = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id JOIN subcategory_judges sj ON s.id = sj.subcategory_id WHERE sj.judge_id = ? ORDER BY c.name, s.name');
			$stmt->execute([$judgeId]);
			$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			// Other users see no results
			$subcategories = [];
		}
		
		// Get results for each subcategory
		$results = [];
		foreach ($subcategories as $subcategory) {
			$stmt = DB::pdo()->prepare('SELECT sc.contestant_id as contestantId, con.name as contestantName, SUM(s.score) as totalScore FROM scores s JOIN contestants con ON con.id = s.contestant_id JOIN subcategory_contestants sc ON sc.contestant_id = s.contestant_id AND sc.subcategory_id = s.subcategory_id WHERE s.subcategory_id = ? GROUP BY sc.contestant_id, con.name ORDER BY totalScore DESC');
			$stmt->execute([$subcategory['id']]);
			$subcategoryResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			if (!empty($subcategoryResults)) {
				$results[$subcategory['id']] = [
					'subcategory' => $subcategory,
					'results' => $subcategoryResults
				];
			}
		}
		
		view('results/all', compact('subcategories', 'results'));
	}

	public function index(array $params): void {
		require_login();
		$subcategoryId = param('id', $params);
		$subcategory = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		$agg = DB::pdo()->prepare('SELECT sc.contestant_id as contestantId, con.name as contestantName, SUM(s.score) as totalScore FROM scores s JOIN contestants con ON con.id = s.contestant_id JOIN subcategory_contestants sc ON sc.contestant_id = s.contestant_id AND sc.subcategory_id = s.subcategory_id WHERE s.subcategory_id = ? GROUP BY sc.contestant_id, con.name ORDER BY totalScore DESC');
		$agg->execute([$subcategoryId]);
		$results = $agg->fetchAll(\PDO::FETCH_ASSOC);
		view('results/index', compact('subcategory','results'));
	}
	public function categoryIndex(): void {
		require_login();
		
		// Get categories based on user role
		if (is_organizer()) {
			// Organizers can see all categories
			$categories = DB::pdo()->query('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id ORDER BY co.name, c.name')->fetchAll(\PDO::FETCH_ASSOC);
			$subcategories = DB::pdo()->query('SELECT sc.*, c.name as category_name FROM subcategories sc JOIN categories c ON sc.category_id = c.id ORDER BY c.name, sc.name')->fetchAll(\PDO::FETCH_ASSOC);
		} elseif (is_judge()) {
			// Judges can only see categories with their assigned subcategories
			$judgeId = current_user()['judge_id'] ?? '';
			$stmt = DB::pdo()->prepare('SELECT DISTINCT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id JOIN subcategories sc ON sc.category_id = c.id JOIN subcategory_judges sj ON sc.id = sj.subcategory_id WHERE sj.judge_id = ? ORDER BY co.name, c.name');
			$stmt->execute([$judgeId]);
			$categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			$stmt = DB::pdo()->prepare('SELECT sc.*, c.name as category_name FROM subcategories sc JOIN categories c ON sc.category_id = c.id JOIN subcategory_judges sj ON sc.id = sj.subcategory_id WHERE sj.judge_id = ? ORDER BY c.name, sc.name');
			$stmt->execute([$judgeId]);
			$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			// Other users see no categories
			$categories = [];
			$subcategories = [];
		}
		
		// Aggregate results by category and contestant
		$sql = "SELECT c.id as categoryId, c.name as categoryName, con.id as contestantId, con.name as contestantName, con.contestant_number, SUM(s.score) as totalScore
		FROM categories c
		JOIN subcategories sc ON sc.category_id = c.id
		JOIN scores s ON s.subcategory_id = sc.id
		JOIN contestants con ON con.id = s.contestant_id
		GROUP BY c.id, c.name, con.id, con.name, con.contestant_number
		ORDER BY c.name, totalScore DESC";
		
		try {
			$rows = DB::pdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			// If no scores exist yet, return empty results
			$rows = [];
		}
		
		// Calculate lead contestants for admins
		$leadContestants = [];
		if (is_organizer()) {
			foreach ($categories as $category) {
				$categoryResults = array_filter($rows, function($row) use ($category) {
					return $row['categoryId'] === $category['id'];
				});
				if (!empty($categoryResults)) {
					$leadContestants[$category['id']] = reset($categoryResults); // First result has highest score
				}
			}
		}
		
		view('results/categories', compact('rows','categories','subcategories','leadContestants'));
	}
	public function detailed(array $params): void {
		require_login();
		$subcategoryId = param('id', $params);
		$subcategory = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		
		// Get all contestants and their scores by judge and criterion
		$contestants = DB::pdo()->prepare('SELECT con.* FROM subcategory_contestants sc JOIN contestants con ON sc.contestant_id = con.id WHERE sc.subcategory_id = ? ORDER BY con.name');
		$contestants->execute([$subcategoryId]);
		$contestants = $contestants->fetchAll(\PDO::FETCH_ASSOC);
		
		$criteria = DB::pdo()->prepare('SELECT * FROM criteria WHERE subcategory_id = ? ORDER BY name');
		$criteria->execute([$subcategoryId]);
		$criteria = $criteria->fetchAll(\PDO::FETCH_ASSOC);
		
		$judges = DB::pdo()->prepare('SELECT j.* FROM subcategory_judges sj JOIN judges j ON sj.judge_id = j.id WHERE sj.subcategory_id = ? ORDER BY j.name');
		$judges->execute([$subcategoryId]);
		$judges = $judges->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all scores
		$scores = DB::pdo()->prepare('SELECT * FROM scores WHERE subcategory_id = ?');
		$scores->execute([$subcategoryId]);
		$scores = $scores->fetchAll(\PDO::FETCH_ASSOC);

		// Get overall deductions for this subcategory keyed by contestant
		$dedStmt = DB::pdo()->prepare('SELECT contestant_id, SUM(amount) as total_deduction FROM overall_deductions WHERE subcategory_id = ? GROUP BY contestant_id');
		$dedStmt->execute([$subcategoryId]);
		$deductions = [];
		foreach ($dedStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) { $deductions[$row['contestant_id']] = (float)$row['total_deduction']; }
		
		// Get comments
		$comments = DB::pdo()->prepare('SELECT * FROM judge_comments WHERE subcategory_id = ?');
		$comments->execute([$subcategoryId]);
		$comments = $comments->fetchAll(\PDO::FETCH_ASSOC);
		
		view('results/detailed', compact('subcategory','contestants','criteria','judges','scores','comments','deductions'));
	}

	public function addDeduction(array $params): void {
		require_login();
		$subcategoryId = param('subcategoryId', $params);
		$contestantId = param('contestantId', $params);
		$amount = (float)($_POST['amount'] ?? 0);
		$comment = trim((string)($_POST['comment'] ?? '')) ?: null;

		$allowed = is_organizer();
		if (!$allowed && is_judge()) {
			$jid = $_SESSION['user']['judge_id'] ?? null;
			if ($jid) {
				$st = DB::pdo()->prepare('SELECT is_head_judge FROM judges WHERE id = ?');
				$st->execute([$jid]);
				$row = $st->fetch(\PDO::FETCH_ASSOC);
				$allowed = !empty($row) && (int)$row['is_head_judge'] === 1;
			}
		}
		if (!$allowed) { http_response_code(403); echo 'Forbidden'; return; }

		if ($amount <= 0) { $_SESSION['success_message'] = 'Deduction must be greater than 0.'; redirect('/results/' . $subcategoryId . '/detailed'); return; }
		$uid = $_SESSION['user']['id'] ?? null;
		DB::pdo()->prepare('INSERT INTO overall_deductions (id, subcategory_id, contestant_id, amount, comment, created_by) VALUES (?,?,?,?,?,?)')
			->execute([uuid(), $subcategoryId, $contestantId, $amount, $comment, $uid]);
		\App\Logger::logAdminAction('add_overall_deduction', 'subcategory', $subcategoryId, 'Contestant ' . $contestantId . ' amount ' . $amount);
		$_SESSION['success_message'] = 'Deduction added.';
		redirect('/results/' . $subcategoryId . '/detailed');
	}

	public function contestantDetailed(array $params): void {
		require_organizer(); // Only organizers can view detailed contestant scores
		$contestantId = param('contestantId', $params);
		$categoryId = param('categoryId', $params);
		
		// Get contestant info
		$contestant = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
		$contestant->execute([$contestantId]);
		$contestant = $contestant->fetch(\PDO::FETCH_ASSOC);
		
		// Get category info
		$category = DB::pdo()->prepare('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id WHERE c.id = ?');
		$category->execute([$categoryId]);
		$category = $category->fetch(\PDO::FETCH_ASSOC);
		
		// Get subcategories for this category
		$subcategories = DB::pdo()->prepare('SELECT * FROM subcategories WHERE category_id = ? ORDER BY name');
		$subcategories->execute([$categoryId]);
		$subcategories = $subcategories->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all scores for this contestant in this category
		$scores = DB::pdo()->prepare('
			SELECT s.*, sc.name as subcategory_name, c.name as criterion_name, c.max_score, j.name as judge_name
			FROM scores s 
			JOIN subcategories sc ON s.subcategory_id = sc.id 
			JOIN criteria c ON s.criterion_id = c.id 
			JOIN judges j ON s.judge_id = j.id 
			WHERE s.contestant_id = ? AND sc.category_id = ?
			ORDER BY sc.name, c.name, j.name
		');
		$scores->execute([$contestantId, $categoryId]);
		$scores = $scores->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get comments for this contestant in this category
		$comments = DB::pdo()->prepare('
			SELECT jc.*, sc.name as subcategory_name, j.name as judge_name
			FROM judge_comments jc 
			JOIN subcategories sc ON jc.subcategory_id = sc.id 
			JOIN judges j ON jc.judge_id = j.id 
			WHERE jc.contestant_id = ? AND sc.category_id = ?
			ORDER BY sc.name, j.name
		');
		$comments->execute([$contestantId, $categoryId]);
		$comments = $comments->fetchAll(\PDO::FETCH_ASSOC);
		
		view('results/contestant_detailed', compact('contestant','category','subcategories','scores','comments'));
	}
	
	public function unsignAll(array $params): void {
		require_organizer(); // Only organizers can unsign scores
		$subcategoryId = param('id', $params);
		
		// Remove all certifications for this subcategory
		$pdo = DB::pdo();
		$pdo->prepare('DELETE FROM judge_certifications WHERE subcategory_id = ?')
			->execute([$subcategoryId]);
		
		$_SESSION['success_message'] = 'All scores for this subcategory have been unsigned successfully!';
		redirect('/results/' . $subcategoryId);
	}
	
	public function unsignAllByCategory(array $params): void {
		require_organizer(); // Only organizers can unsign scores
		$categoryId = param('categoryId', $params);
		
		// Get all subcategories for this category
		$subcategories = DB::pdo()->prepare('SELECT id FROM subcategories WHERE category_id = ?');
		$subcategories->execute([$categoryId]);
		$subcategoryIds = $subcategories->fetchAll(\PDO::FETCH_COLUMN);
		
		// Remove all certifications for all subcategories in this category
		$pdo = DB::pdo();
		foreach ($subcategoryIds as $subcategoryId) {
			$pdo->prepare('DELETE FROM judge_certifications WHERE subcategory_id = ?')
				->execute([$subcategoryId]);
		}
		
		$_SESSION['success_message'] = 'All scores for this category have been unsigned successfully!';
		redirect('/results');
	}
	
	public function unsignAllByContestant(array $params): void {
		require_organizer(); // Only organizers can unsign scores
		$contestantId = param('contestantId', $params);
		
		// Get all subcategories this contestant is assigned to
		$subcategories = DB::pdo()->prepare('SELECT subcategory_id FROM subcategory_contestants WHERE contestant_id = ?');
		$subcategories->execute([$contestantId]);
		$subcategoryIds = $subcategories->fetchAll(\PDO::FETCH_COLUMN);
		
		// Remove all certifications for all subcategories this contestant is in
		$pdo = DB::pdo();
		foreach ($subcategoryIds as $subcategoryId) {
			$pdo->prepare('DELETE FROM judge_certifications WHERE subcategory_id = ?')
				->execute([$subcategoryId]);
		}
		
		$_SESSION['success_message'] = 'All scores for this contestant have been unsigned successfully!';
		redirect('/results');
	}
	
	public function unsignAllByJudge(array $params): void {
		require_organizer(); // Only organizers can unsign scores
		$judgeId = param('judgeId', $params);
		
		// Remove all certifications for this judge
		$pdo = DB::pdo();
		$pdo->prepare('DELETE FROM judge_certifications WHERE judge_id = ?')
			->execute([$judgeId]);
		
		$_SESSION['success_message'] = 'All scores for this judge have been unsigned successfully!';
		redirect('/results');
	}
}

class UserController {
	public function new(): void {
		require_organizer();
		// Get all categories for assignment
		$categories = DB::pdo()->query('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id ORDER BY co.name, c.name')->fetchAll(\PDO::FETCH_ASSOC);
		view('users/new', compact('categories'));
	}
	public function create(): void {
		require_organizer();
		$name = post('name');
		$preferredName = post('preferred_name') ?: null;
		$email = post('email') ?: null;
		$password = post('password') ?: null;
		$role = post('role');
		$gender = post('gender') ?: null;
		
		// Validate role
		if (!in_array($role, ['organizer', 'judge', 'emcee', 'contestant'])) {
			redirect('/users/new?error=invalid_role');
			return;
		}
		
		// Check if email already exists (only if email is provided)
		if ($email) {
			$stmt = DB::pdo()->prepare('SELECT id FROM users WHERE email = ?');
			$stmt->execute([$email]);
			if ($stmt->fetch()) {
				redirect('/users/new?error=email_exists');
				return;
			}
		}
		
		// Check if preferred name already exists (if provided)
		if ($preferredName) {
			$stmt = DB::pdo()->prepare('SELECT id FROM users WHERE preferred_name = ?');
			$stmt->execute([$preferredName]);
			if ($stmt->fetch()) {
				redirect('/users/new?error=preferred_name_exists');
				return;
			}
		}
		
		try {
        // Enforce password complexity when provided
        if ($password) {
            $pwd = (string)$password;
            $complex = (strlen($pwd) >= 8
                && preg_match('/[A-Z]/', $pwd)
                && preg_match('/[a-z]/', $pwd)
                && preg_match('/[0-9]/', $pwd)
                && preg_match('/[^A-Za-z0-9]/', $pwd));
            if (!$complex) {
                redirect('/users/new?error=weak_password');
                return;
            }
        }
        $passwordHash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
			
			if ($role === 'contestant') {
				// Handle contestant creation with bio and image
				$imagePath = null;
				
				// Handle image upload
				if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
					$uploadDir = __DIR__ . '/../../public/uploads/contestants/';
					// Create directory if it doesn't exist
					if (!is_dir($uploadDir)) {
						mkdir($uploadDir, 0755, true);
					}
					$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
					$filename = uuid() . '.' . $extension;
					$imagePath = '/uploads/contestants/' . $filename;
					
					if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
						redirect('/users/new?error=upload_failed');
						return;
					}
				}
				
				// Auto-assign contestant number if not provided
				$contestantNumber = post('contestant_number');
				if (!$contestantNumber) {
					$stmt = DB::pdo()->query('SELECT MAX(contestant_number) as max_num FROM contestants WHERE contestant_number IS NOT NULL');
					$result = $stmt->fetch(\PDO::FETCH_ASSOC);
					$contestantNumber = ($result['max_num'] ?? 0) + 1;
				}
				
				// Create contestant record
				$contestantId = uuid();
				$stmt = DB::pdo()->prepare('INSERT INTO contestants (id, name, email, contestant_number, bio, image_path) VALUES (?, ?, ?, ?, ?, ?)');
				$stmt->execute([$contestantId, $name, $email, $contestantNumber, post('bio') ?: null, $imagePath]);
				\App\Logger::logUserCreation($contestantId, $role, $name);
				
				// Assign to category if specified
				$categoryId = post('category_id');
				if ($categoryId) {
					$stmt = DB::pdo()->prepare('INSERT INTO category_contestants (category_id, contestant_id) VALUES (?, ?)');
					$stmt->execute([$categoryId, $contestantId]);
				}
			} elseif ($role === 'judge') {
				// Handle judge creation with bio and image
				$imagePath = null;
				
				// Handle image upload
				if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
					$uploadDir = __DIR__ . '/../../public/uploads/judges/';
					// Create directory if it doesn't exist
					if (!is_dir($uploadDir)) {
						mkdir($uploadDir, 0755, true);
					}
					$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
					$filename = uuid() . '.' . $extension;
					$imagePath = '/uploads/judges/' . $filename;
					
					if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
						redirect('/users/new?error=upload_failed');
						return;
					}
				}
				
				// Create judge record
				$judgeId = uuid();
				$stmt = DB::pdo()->prepare('INSERT INTO judges (id, name, email, bio, image_path) VALUES (?, ?, ?, ?, ?)');
				$stmt->execute([$judgeId, $name, $email, post('bio') ?: null, $imagePath]);
				
				// Create user record linked to judge
				$userId = uuid();
				$stmt = DB::pdo()->prepare('INSERT INTO users (id, name, preferred_name, email, password_hash, role, judge_id, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
				$stmt->execute([$userId, $name, $preferredName, $email, $passwordHash, $role, $judgeId, $gender]);
				\App\Logger::logUserCreation($userId, $role, $name);
				
				// Assign to category if specified
				$categoryId = post('category_id');
				if ($categoryId) {
					$stmt = DB::pdo()->prepare('INSERT INTO category_judges (category_id, judge_id) VALUES (?, ?)');
					$stmt->execute([$categoryId, $judgeId]);
				}
			} else {
				// Create regular user (organizer, emcee)
				$userId = uuid();
				$stmt = DB::pdo()->prepare('INSERT INTO users (id, name, preferred_name, email, password_hash, role, gender) VALUES (?, ?, ?, ?, ?, ?, ?)');
				$stmt->execute([$userId, $name, $preferredName, $email, $passwordHash, $role, $gender]);
				\App\Logger::logUserCreation($userId, $role, $name);
			}
			
			redirect('/admin/users?success=user_created');
		} catch (\PDOException $e) {
			if (strpos($e->getMessage(), 'CHECK constraint failed') !== false) {
				redirect('/users/new?error=constraint_failed');
			} else {
				redirect('/users/new?error=database_error');
			}
		}
	}
	public function index(): void {
		require_organizer();
		$users = DB::pdo()->query('SELECT * FROM users ORDER BY role, name')->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get contestants from contestants table
		$contestants = DB::pdo()->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		
		// Group users by role
		$usersByRole = [];
		foreach ($users as $user) {
			$usersByRole[$user['role']][] = $user;
		}
		
		// Add contestants to the contestants role group
		$usersByRole['contestant'] = $contestants;
		
		view('users/index', compact('users', 'usersByRole', 'contestants'));
	}
	public function edit(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT * FROM users WHERE id = ?');
		$stmt->execute([$id]);
		$user = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$user) {
			redirect('/admin/users?error=user_not_found');
			return;
		}
		
		view('users/edit', compact('user'));
	}
	public function update(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$name = post('name');
		$preferredName = post('preferred_name') ?: null;
		$email = post('email') ?: null;
		$password = post('password') ?: null;
		$role = post('role');
		$gender = post('gender') ?: null;
		
		// Validate role
		if (!in_array($role, ['organizer', 'judge', 'emcee', 'contestant'])) {
			redirect('/admin/users/' . $id . '/edit?error=invalid_role');
			return;
		}
		
		// Check if email already exists (excluding current user)
		if ($email) {
			$stmt = DB::pdo()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
			$stmt->execute([$email, $id]);
			if ($stmt->fetch()) {
				redirect('/admin/users/' . $id . '/edit?error=email_exists');
				return;
			}
		}
		
		// Check if preferred name already exists (excluding current user)
		if ($preferredName) {
			$stmt = DB::pdo()->prepare('SELECT id FROM users WHERE preferred_name = ? AND id != ?');
			$stmt->execute([$preferredName, $id]);
			if ($stmt->fetch()) {
				redirect('/admin/users/' . $id . '/edit?error=preferred_name_exists');
				return;
			}
		}
		
		try {
			$updateFields = ['name = ?', 'preferred_name = ?', 'email = ?', 'role = ?', 'gender = ?'];
			$params = [$name, $preferredName, $email, $role, $gender];
			
            if ($password) {
                $pwd = (string)$password;
                $complex = (strlen($pwd) >= 8
                    && preg_match('/[A-Z]/', $pwd)
                    && preg_match('/[a-z]/', $pwd)
                    && preg_match('/[0-9]/', $pwd)
                    && preg_match('/[^A-Za-z0-9]/', $pwd));
                if (!$complex) {
                    redirect('/users/' . $id . '/edit?error=weak_password');
                    return;
                }
				$updateFields[] = 'password_hash = ?';
				$params[] = password_hash($password, PASSWORD_DEFAULT);
			}
			
			$params[] = $id;
			$stmt = DB::pdo()->prepare('UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = ?');
			$stmt->execute($params);
			
			redirect('/admin/users?success=user_updated');
		} catch (\PDOException $e) {
			redirect('/admin/users/' . $id . '/edit?error=database_error');
		}
	}
	public function delete(array $params): void {
		require_organizer();
		$id = param('id', $params);
		
		// Get user information
		$stmt = DB::pdo()->prepare('SELECT * FROM users WHERE id = ?');
		$stmt->execute([$id]);
		$user = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$user) {
			redirect('/admin/users?error=user_not_found');
			return;
		}
		
		// Don't allow deleting the last organizer
		$stmt = DB::pdo()->prepare('SELECT COUNT(*) as count FROM users WHERE role = ?');
		$stmt->execute(['organizer']);
		$organizerCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
		
		if ($user['role'] === 'organizer' && $organizerCount <= 1) {
			redirect('/admin/users?error=cannot_delete_last_organizer');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			if ($user['role'] === 'judge' && !empty($user['judge_id'])) {
				// Comprehensive cleanup for judge
				$this->cleanupJudge($user['judge_id']);
			} elseif ($user['role'] === 'contestant') {
				// Find contestant record and cleanup
				$stmt = $pdo->prepare('SELECT id FROM contestants WHERE name = ? AND email = ?');
				$stmt->execute([$user['name'], $user['email']]);
				$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
				if ($contestant) {
					$this->cleanupContestant($contestant['id']);
				}
			}
			
			// Delete user record
			$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			redirect('/admin/users?success=user_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/admin/users?error=delete_failed');
		}
	}
	
	public function removeAllJudges(): void {
		require_organizer();
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get all judge IDs
			$judges = $pdo->query('SELECT id FROM judges')->fetchAll(\PDO::FETCH_COLUMN);
			
			foreach ($judges as $judgeId) {
				// Delete associated files
				$stmt = $pdo->prepare('SELECT image_path FROM judges WHERE id = ?');
				$stmt->execute([$judgeId]);
				$imagePath = $stmt->fetchColumn();
				if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
					unlink(__DIR__ . '/../../public' . $imagePath);
				}
			}
			
			// Delete all related data
			$pdo->exec('DELETE FROM judge_certifications');
			$pdo->exec('DELETE FROM judge_comments');
			$pdo->exec('DELETE FROM scores');
			$pdo->exec('DELETE FROM subcategory_judges');
			$pdo->exec('DELETE FROM category_judges');
			$pdo->exec('DELETE FROM users WHERE role = "judge"');
			$pdo->exec('DELETE FROM judges');
			
			$pdo->commit();
			redirect('/admin/users?success=all_judges_removed');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/admin/users?error=remove_failed');
		}
	}
	
	public function removeAllContestants(): void {
		require_organizer();
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get all contestant IDs
			$contestants = $pdo->query('SELECT id FROM contestants')->fetchAll(\PDO::FETCH_COLUMN);
			
			foreach ($contestants as $contestantId) {
				// Delete associated files
				$stmt = $pdo->prepare('SELECT image_path FROM contestants WHERE id = ?');
				$stmt->execute([$contestantId]);
				$imagePath = $stmt->fetchColumn();
				if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
					unlink(__DIR__ . '/../../public' . $imagePath);
				}
			}
			
			// Delete all related data
			$pdo->exec('DELETE FROM judge_comments');
			$pdo->exec('DELETE FROM scores');
			$pdo->exec('DELETE FROM subcategory_contestants');
			$pdo->exec('DELETE FROM category_contestants');
			$pdo->exec('DELETE FROM contestants');
			
			$pdo->commit();
			redirect('/admin/users?success=all_contestants_removed');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/admin/users?error=remove_failed');
		}
	}
	
	public function removeAllEmcees(): void {
		require_organizer();
		$pdo = DB::pdo();
		
		try {
			$pdo->exec('DELETE FROM users WHERE role = "emcee"');
			redirect('/admin/users?success=all_emcees_removed');
		} catch (\Exception $e) {
			redirect('/admin/users?error=remove_failed');
		}
	}
	
	public function forceRefresh(): void {
		require_organizer();
		// Force refresh by redirecting to the same page
		// This will reload all data from the database
		redirect('/admin/users?success=table_refreshed');
	}
	
	private function cleanupJudge(string $judgeId): void {
		$pdo = DB::pdo();
		
		// Delete associated image file
		$stmt = $pdo->prepare('SELECT image_path FROM judges WHERE id = ?');
		$stmt->execute([$judgeId]);
		$imagePath = $stmt->fetchColumn();
		if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
			unlink(__DIR__ . '/../../public' . $imagePath);
		}
		
		// Delete all related data
		$pdo->prepare('DELETE FROM judge_certifications WHERE judge_id = ?')->execute([$judgeId]);
		$pdo->prepare('DELETE FROM judge_comments WHERE judge_id = ?')->execute([$judgeId]);
		$pdo->prepare('DELETE FROM scores WHERE judge_id = ?')->execute([$judgeId]);
		$pdo->prepare('DELETE FROM subcategory_judges WHERE judge_id = ?')->execute([$judgeId]);
		$pdo->prepare('DELETE FROM category_judges WHERE judge_id = ?')->execute([$judgeId]);
		$pdo->prepare('DELETE FROM judges WHERE id = ?')->execute([$judgeId]);
	}
	
	private function cleanupContestant(string $contestantId): void {
		$pdo = DB::pdo();
		
		// Delete associated image file
		$stmt = $pdo->prepare('SELECT image_path FROM contestants WHERE id = ?');
		$stmt->execute([$contestantId]);
		$imagePath = $stmt->fetchColumn();
		if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
			unlink(__DIR__ . '/../../public' . $imagePath);
		}
		
		// Delete all related data
		$pdo->prepare('DELETE FROM judge_comments WHERE contestant_id = ?')->execute([$contestantId]);
		$pdo->prepare('DELETE FROM scores WHERE contestant_id = ?')->execute([$contestantId]);
		$pdo->prepare('DELETE FROM subcategory_contestants WHERE contestant_id = ?')->execute([$contestantId]);
		$pdo->prepare('DELETE FROM category_contestants WHERE contestant_id = ?')->execute([$contestantId]);
		$pdo->prepare('DELETE FROM contestants WHERE id = ?')->execute([$contestantId]);
	}
}

class AuthController {
	public function loginForm(): void { view('auth/login'); }
	public function login(): void {
		$emailOrName = trim((string)post('email'));
		$password = (string)post('password');
		
		// Try to find user by email first, then by preferred name
		$stmt = DB::pdo()->prepare('SELECT * FROM users WHERE email = ? OR preferred_name = ?');
		$stmt->execute([$emailOrName, $emailOrName]);
		$user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
		
		if ($user && $user['password_hash'] && password_verify($password, $user['password_hash'])) {
			// Fetch session_version
			$verStmt = DB::pdo()->prepare('SELECT session_version FROM users WHERE id = ?');
			$verStmt->execute([$user['id']]);
			$verRow = $verStmt->fetch(\PDO::FETCH_ASSOC) ?: ['session_version' => 1];
			$_SESSION['user'] = [
				'id' => $user['id'],
				'name' => $user['name'],
				'preferred_name' => $user['preferred_name'] ?? null,
				'email' => $user['email'],
				'role' => $user['role'],
				'judge_id' => $user['judge_id'] ?? null,
				'session_version' => (int)($verRow['session_version'] ?? 1),
			];
			\App\Logger::logLogin($emailOrName, true);
			redirect($user['role'] === 'judge' ? '/judge' : ($user['role'] === 'emcee' ? '/emcee' : '/contests'));
		}
		\App\Logger::logLogin($emailOrName, false);
		view('auth/login', ['error' => 'Invalid credentials or user cannot login']);
	}
	public function logout(): void { 
		\App\Logger::logLogout();
		session_destroy(); 
		redirect('/login'); 
	}
	
	public function judgeDashboard(): void {
		require_login();
		if (!is_judge()) { http_response_code(403); echo 'Forbidden'; return; }
		$judgeId = current_user()['judge_id'] ?? '';
		$stmt = DB::pdo()->prepare('SELECT s.id, s.name, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id JOIN subcategory_judges sj ON sj.subcategory_id = s.id WHERE sj.judge_id = ? ORDER BY c.name, s.name');
		$stmt->execute([$judgeId]);
		$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		view('auth/judge', ['subcategories' => $subcategories]);
	}
	
	public function judgeSubcategoryContestants(array $params): void {
		require_login();
		if (!is_judge()) { http_response_code(403); echo 'Forbidden'; return; }
		
		$subcategoryId = param('id', $params);
		$judgeId = current_user()['judge_id'] ?? '';
		
		// Verify judge is assigned to this subcategory
		$stmt = DB::pdo()->prepare('SELECT COUNT(*) FROM subcategory_judges WHERE subcategory_id = ? AND judge_id = ?');
		$stmt->execute([$subcategoryId, $judgeId]);
		if ($stmt->fetchColumn() == 0) {
			http_response_code(403);
			echo 'You are not assigned to this subcategory.';
			return;
		}
		
		// Get subcategory info
		$stmt = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$stmt->execute([$subcategoryId]);
		$subcategory = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		// Get contestants assigned to this subcategory
		$stmt = DB::pdo()->prepare('SELECT con.* FROM subcategory_contestants sc JOIN contestants con ON sc.contestant_id = con.id WHERE sc.subcategory_id = ? ORDER BY con.contestant_number IS NULL, con.contestant_number, con.name');
		$stmt->execute([$subcategoryId]);
		$contestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('auth/judge_contestants', compact('subcategory', 'contestants'));
	}
}

class AdminController {
	public function index(): void { require_organizer(); view('admin/index'); }
	public function judges(): void {
		require_organizer();
		$rows = DB::pdo()->query('SELECT * FROM judges ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		view('admin/judges', compact('rows'));
	}
	public function createJudge(): void {
		require_organizer();
		$name = post('name'); $email = post('email'); $gender = post('gender'); $isHead = post('is_head_judge') ? 1 : 0;
		$id = uuid();
		DB::pdo()->prepare('INSERT INTO judges (id,name,email,gender,is_head_judge) VALUES (?,?,?,?,?)')->execute([$id,$name,$email ?: null,$gender ?: null,$isHead]);
		// Optional: create a judge user account
        if ($email && post('create_user') === '1' && post('password')) {
            $pwd = (string)post('password');
            $complex = (strlen($pwd) >= 8 && preg_match('/[A-Z]/',$pwd) && preg_match('/[a-z]/',$pwd) && preg_match('/[0-9]/',$pwd) && preg_match('/[^A-Za-z0-9]/',$pwd));
            if (!$complex) { redirect('/admin/judges?error=weak_password'); return; }
            $hash = password_hash($pwd, PASSWORD_BCRYPT);
			DB::pdo()->prepare('INSERT INTO users (id,name,email,password_hash,role,judge_id,gender) VALUES (?,?,?,?,?,?,?)')
				->execute([uuid(), $name, $email, $hash, 'judge', $id, $gender ?: null]);
		}
		redirect('/admin/judges');
	}

	public function updateJudge(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$name = post('name'); $email = post('email'); $gender = post('gender'); $isHead = post('is_head_judge') ? 1 : 0;
		$stmt = DB::pdo()->prepare('UPDATE judges SET name = ?, email = ?, gender = ?, is_head_judge = ? WHERE id = ?');
		$stmt->execute([$name, $email ?: null, $gender ?: null, $isHead, $id]);
		redirect('/admin/judges');
	}
	public function deleteJudge(): void {
		require_organizer();
		$id = post('id');
		DB::pdo()->prepare('DELETE FROM users WHERE judge_id = ?')->execute([$id]);
		DB::pdo()->prepare('DELETE FROM judges WHERE id = ?')->execute([$id]);
		redirect('/admin/judges');
	}
	public function contestants(): void {
		require_organizer();
		$rows = DB::pdo()->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		view('admin/contestants', compact('rows'));
	}
	public function createContestant(): void {
		require_organizer();
		DB::pdo()->prepare('INSERT INTO contestants (id,name,email,gender) VALUES (?,?,?,?)')
			->execute([uuid(), post('name'), post('email') ?: null, post('gender') ?: null]);
		redirect('/admin/contestants');
	}
	public function deleteContestant(): void {
		require_organizer();
		DB::pdo()->prepare('DELETE FROM contestants WHERE id = ?')->execute([post('id')]);
		redirect('/admin/contestants');
	}
	public function organizers(): void {
		require_organizer();
		$rows = DB::pdo()->query("SELECT id,name,email,gender FROM users WHERE role = 'organizer' ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC);
		view('admin/organizers', compact('rows'));
	}
	public function createOrganizer(): void {
		require_organizer();
		$name = post('name'); $email = post('email'); $gender = post('gender');
		$pwd = (string)post('password');
		$hash = password_hash($pwd, PASSWORD_BCRYPT);
		DB::pdo()->prepare('INSERT INTO users (id,name,email,password_hash,role,gender) VALUES (?,?,?,?,?,?)')
			->execute([uuid(), $name, $email, $hash, 'organizer', $gender ?: null]);
		redirect('/admin/organizers');
	}
	public function deleteOrganizer(): void {
		require_organizer();
		DB::pdo()->prepare("DELETE FROM users WHERE id = ? AND role = 'organizer'")->execute([post('id')]);
		redirect('/admin/organizers');
	}
	
	public function contestantScores(array $params): void {
		require_organizer();
		$contestantId = param('contestantId', $params);
		
		// Get contestant information
		$stmt = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
		$stmt->execute([$contestantId]);
		$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contestant) {
			redirect('/admin/users?error=contestant_not_found');
			return;
		}
		
		// Get all subcategories this contestant is assigned to
		$stmt = DB::pdo()->prepare('
			SELECT sc.*, c.name as category_name, co.name as contest_name
			FROM subcategory_contestants scc
			JOIN subcategories sc ON scc.subcategory_id = sc.id
			JOIN categories c ON sc.category_id = c.id
			JOIN contests co ON c.contest_id = co.id
			WHERE scc.contestant_id = ?
			ORDER BY co.name, c.name, sc.name
		');
		$stmt->execute([$contestantId]);
		$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all scores for this contestant
		$stmt = DB::pdo()->prepare('
			SELECT s.*, cr.name as criterion_name, cr.max_score, j.name as judge_name, sc.name as subcategory_name, c.name as category_name
			FROM scores s
			JOIN criteria cr ON s.criterion_id = cr.id
			JOIN judges j ON s.judge_id = j.id
			JOIN subcategories sc ON s.subcategory_id = sc.id
			JOIN categories c ON sc.category_id = c.id
			WHERE s.contestant_id = ?
			ORDER BY c.name, sc.name, cr.name, j.name
		');
		$stmt->execute([$contestantId]);
		$scores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all comments for this contestant
		$stmt = DB::pdo()->prepare('
			SELECT jc.*, j.name as judge_name, sc.name as subcategory_name, c.name as category_name
			FROM judge_comments jc
			JOIN judges j ON jc.judge_id = j.id
			JOIN subcategories sc ON jc.subcategory_id = sc.id
			JOIN categories c ON sc.category_id = c.id
			WHERE jc.contestant_id = ?
			ORDER BY c.name, sc.name, j.name
		');
		$stmt->execute([$contestantId]);
		$comments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('admin/contestant_scores', compact('contestant', 'subcategories', 'scores', 'comments'));
	}
	
	public function settings(): void {
		require_organizer();
		
		// Get current settings
		$stmt = DB::pdo()->query('SELECT * FROM system_settings ORDER BY setting_key');
		$settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Convert to key-value array
		$settingsArray = [];
		foreach ($settings as $setting) {
			$settingsArray[$setting['setting_key']] = $setting;
		}
		
		view('admin/settings', compact('settingsArray'));
	}
	
	public function updateSettings(): void {
		require_organizer();
		
		$sessionTimeout = post('session_timeout');
		$logLevel = post('log_level');
		$updatedBy = $_SESSION['user']['id'] ?? null;
		$updated = false;
		
		if ($sessionTimeout && is_numeric($sessionTimeout) && $sessionTimeout > 0) {
			$stmt = DB::pdo()->prepare('UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?');
			$stmt->execute([$sessionTimeout, $updatedBy, 'session_timeout']);
			$updated = true;
		}
		
		if ($logLevel && in_array($logLevel, ['debug', 'info', 'warn', 'error'])) {
			$stmt = DB::pdo()->prepare('UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?');
			$stmt->execute([$logLevel, $updatedBy, 'log_level']);
			
			// Update the Logger's current level
			\App\Logger::setLevel($logLevel);
			$updated = true;
		}
		
		if ($updated) {
			\App\Logger::logAdminAction('update_settings', 'system', null, "Settings updated");
			redirect('/admin/settings?success=settings_updated');
		} else {
			redirect('/admin/settings?error=invalid_settings');
		}
	}

	public function forceLogoutAll(): void {
		require_organizer();
		DB::pdo()->exec('UPDATE users SET session_version = session_version + 1');
		\App\Logger::logAdminAction('force_logout_all', 'user', null, 'All users forced to logout');
		redirect('/admin/users?success=forced_logout_all');
	}

	public function forceLogoutUser(): void {
		require_organizer();
		$id = post('id');
		if ($id) {
			$stmt = DB::pdo()->prepare('UPDATE users SET session_version = session_version + 1 WHERE id = ?');
			$stmt->execute([$id]);
			\App\Logger::logAdminAction('force_logout_user', 'user', $id, 'User forced to logout');
		}
		redirect('/admin/users?success=forced_logout_user');
	}
	
	public function logs(): void {
		require_organizer();
		
		$page = (int)($_GET['page'] ?? 1);
		$limit = 50;
		$offset = ($page - 1) * $limit;
		$logLevel = $_GET['level'] ?? 'all';
		$userRole = $_GET['role'] ?? 'all';
		$action = $_GET['action'] ?? '';
		$dateFrom = $_GET['date_from'] ?? '';
		$dateTo = $_GET['date_to'] ?? '';
		
		// Build query with filters
		$whereConditions = [];
		$params = [];
		
		if ($logLevel !== 'all') {
			$whereConditions[] = 'log_level = ?';
			$params[] = $logLevel;
		}
		
		if ($userRole !== 'all') {
			$whereConditions[] = 'user_role = ?';
			$params[] = $userRole;
		}
		
		if (!empty($action)) {
			$whereConditions[] = 'action LIKE ?';
			$params[] = '%' . $action . '%';
		}
		
		if (!empty($dateFrom)) {
			$whereConditions[] = 'created_at >= ?';
			$params[] = $dateFrom . ' 00:00:00';
		}
		
		if (!empty($dateTo)) {
			$whereConditions[] = 'created_at <= ?';
			$params[] = $dateTo . ' 23:59:59';
		}
		
		$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
		
		// Get total count
		$countQuery = 'SELECT COUNT(*) FROM activity_logs ' . $whereClause;
		$stmt = DB::pdo()->prepare($countQuery);
		$stmt->execute($params);
		$totalLogs = $stmt->fetchColumn();
		$totalPages = ceil($totalLogs / $limit);
		
		// Get logs with pagination
		$query = 'SELECT * FROM activity_logs ' . $whereClause . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
		$params[] = $limit;
		$params[] = $offset;
		$stmt = DB::pdo()->prepare($query);
		$stmt->execute($params);
		$logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get current log level setting
		$stmt = DB::pdo()->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
		$stmt->execute(['log_level']);
		$currentLogLevel = $stmt->fetchColumn() ?: 'info';
		
		// Get unique roles for filter
		$stmt = DB::pdo()->query('SELECT DISTINCT user_role FROM activity_logs ORDER BY user_role');
		$availableRoles = $stmt->fetchAll(\PDO::FETCH_COLUMN);
		
		// Get unique actions for filter
		$stmt = DB::pdo()->query('SELECT DISTINCT action FROM activity_logs ORDER BY action');
		$availableActions = $stmt->fetchAll(\PDO::FETCH_COLUMN);
		
		view('admin/logs', compact('logs', 'page', 'totalPages', 'totalLogs', 'logLevel', 'currentLogLevel', 'userRole', 'action', 'dateFrom', 'dateTo', 'availableRoles', 'availableActions'));
	}

	public function emceeScripts(): void {
		require_organizer();
		
		$stmt = DB::pdo()->query('SELECT es.*, u.preferred_name as uploaded_by_name FROM emcee_scripts es JOIN users u ON es.uploaded_by = u.id ORDER BY es.uploaded_at DESC');
		$scripts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('admin/emcee_scripts', compact('scripts'));
	}

	public function uploadEmceeScript(): void {
		require_organizer();
		
		$title = trim(post('title'));
		$description = trim(post('description'));
		
		if (empty($title)) {
			redirect('/admin/emcee-scripts?error=title_required');
			return;
		}
		
		if (!isset($_FILES['script_file']) || $_FILES['script_file']['error'] !== UPLOAD_ERR_OK) {
			redirect('/admin/emcee-scripts?error=file_upload_failed');
			return;
		}
		
		$file = $_FILES['script_file'];
		$allowedTypes = ['application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
		
		if (!in_array($file['type'], $allowedTypes)) {
			redirect('/admin/emcee-scripts?error=invalid_file_type');
			return;
		}
		
		$uploadDir = __DIR__ . '/../../public/uploads/emcee-scripts/';
		if (!is_dir($uploadDir)) {
			mkdir($uploadDir, 0755, true);
		}
		
		$fileName = uniqid() . '_' . basename($file['name']);
		$filePath = $uploadDir . $fileName;
		
		if (move_uploaded_file($file['tmp_name'], $filePath)) {
			$scriptId = uuid();
			$userId = $_SESSION['user']['id'];
			
			$stmt = DB::pdo()->prepare('INSERT INTO emcee_scripts (id, title, description, file_path, file_name, file_size, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
			$stmt->execute([
				$scriptId,
				$title,
				$description ?: null,
				$filePath,
				$file['name'],
				$file['size'],
				$file['type'],
				$userId
			]);
			
			\App\Logger::logAdminAction('upload_emcee_script', 'emcee_script', $scriptId, "Uploaded script: {$title}");
			redirect('/admin/emcee-scripts?success=script_uploaded');
		} else {
			redirect('/admin/emcee-scripts?error=file_save_failed');
		}
	}

	public function deleteEmceeScript(array $params): void {
		require_organizer();
		
		$scriptId = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT * FROM emcee_scripts WHERE id = ?');
		$stmt->execute([$scriptId]);
		$script = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if ($script) {
			// Delete file
			if (file_exists($script['file_path'])) {
				unlink($script['file_path']);
			}
			
			// Delete database record
			$stmt = DB::pdo()->prepare('DELETE FROM emcee_scripts WHERE id = ?');
			$stmt->execute([$scriptId]);
			
			\App\Logger::logAdminAction('delete_emcee_script', 'emcee_script', $scriptId, "Deleted script: {$script['title']}");
			redirect('/admin/emcee-scripts?success=script_deleted');
		} else {
			redirect('/admin/emcee-scripts?error=script_not_found');
		}
	}

	public function toggleEmceeScript(array $params): void {
		require_organizer();
		
		$scriptId = param('id', $params);
		$stmt = DB::pdo()->prepare('UPDATE emcee_scripts SET is_active = NOT is_active WHERE id = ?');
		$stmt->execute([$scriptId]);
		
		$stmt = DB::pdo()->prepare('SELECT title FROM emcee_scripts WHERE id = ?');
		$stmt->execute([$scriptId]);
		$script = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if ($script) {
			\App\Logger::logAdminAction('toggle_emcee_script', 'emcee_script', $scriptId, "Toggled script: {$script['title']}");
			redirect('/admin/emcee-scripts?success=script_toggled');
		} else {
			redirect('/admin/emcee-scripts?error=script_not_found');
		}
	}
	
	public function printReports(): void {
		require_login();
		require_organizer();
		
		// Get all contests with their categories and subcategories
		$stmt = DB::pdo()->prepare('
			SELECT c.id as contest_id, c.name as contest_name,
				   cat.id as category_id, cat.name as category_name,
				   sc.id as subcategory_id, sc.name as subcategory_name
			FROM contests c
			LEFT JOIN categories cat ON c.id = cat.contest_id
			LEFT JOIN subcategories sc ON cat.id = sc.category_id
			ORDER BY c.name, cat.name, sc.name
		');
		$stmt->execute();
		$structure = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all contestants
		$stmt = DB::pdo()->prepare('SELECT * FROM contestants ORDER BY name');
		$stmt->execute();
		$contestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all judges
		$stmt = DB::pdo()->prepare('SELECT * FROM judges ORDER BY name');
		$stmt->execute();
		$judges = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('admin/print_reports', compact('structure', 'contestants', 'judges'));
	}
}

class EmceeController {
	public function index(): void {
		require_emcee();
		$contestants = DB::pdo()->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get active emcee scripts (include file_type for response headers)
		$scripts = DB::pdo()->query('SELECT id, title, description, file_path, file_name, file_size, file_type, uploaded_at FROM emcee_scripts WHERE is_active = 1 ORDER BY uploaded_at DESC')->fetchAll(\PDO::FETCH_ASSOC);
		
		view('emcee/index', compact('contestants', 'scripts'));
	}

	// Stream a script inline to the browser so supported types open in a new tab
	public function streamScript(array $params): void {
		require_emcee();
		$scriptId = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT file_path, file_name, file_type FROM emcee_scripts WHERE id = ? AND is_active = 1');
		$stmt->execute([$scriptId]);
		$script = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$script) { http_response_code(404); echo 'Not found'; return; }
		$absPath = $script['file_path'];
		if (!is_file($absPath)) { http_response_code(404); echo 'File missing'; return; }
		$mime = $script['file_type'] ?: 'application/octet-stream';
		header('Content-Type: ' . $mime);
		header('Content-Disposition: inline; filename="' . basename($script['file_name']) . '"');
		header('Content-Length: ' . filesize($absPath));
		readfile($absPath);
	}
	public function contestantBio(array $params): void {
		require_emcee();
		$contestantNumber = param('number', $params);
		$stmt = DB::pdo()->prepare('SELECT * FROM contestants WHERE contestant_number = ?');
		$stmt->execute([$contestantNumber]);
		$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$contestant) {
			redirect('/emcee');
			return;
		}
		view('emcee/contestant_bio', compact('contestant'));
	}
	public function judgesByCategory(): void {
		require_emcee();
        $stmt = DB::pdo()->query('
            SELECT c.name as category_name, j.name as judge_name, j.bio, j.image_path, j.email, j.is_head_judge
            FROM categories c
            JOIN category_judges cj ON c.id = cj.category_id
            JOIN judges j ON cj.judge_id = j.id
            ORDER BY c.name, j.name
        ');
		$judges = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Group judges by category
		$groupedJudges = [];
		foreach ($judges as $judge) {
			$groupedJudges[$judge['category_name']][] = $judge;
		}
		
		view('emcee/judges', compact('groupedJudges'));
	}
}

class TemplateController {
	public function index(): void {
		require_organizer();
		$rows = DB::pdo()->query('SELECT * FROM subcategory_templates ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		view('templates/index', compact('rows'));
	}
	public function new(): void {
		require_organizer();
		view('templates/new');
	}
	public function create(): void {
		require_organizer();
		$subcategoryNames = post('subcategory_names');
		$subcategoryNamesArray = $subcategoryNames ? explode("\n", trim($subcategoryNames)) : [];
		$subcategoryNamesJson = json_encode(array_filter(array_map('trim', $subcategoryNamesArray)));
		$maxScore = post('max_score') ?: 60;
		
		$stmt = DB::pdo()->prepare('INSERT INTO subcategory_templates (id, name, description, subcategory_names, max_score) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([uuid(), post('name'), post('description') ?: null, $subcategoryNamesJson, (int)$maxScore]);
		redirect('/admin/templates');
	}
	public function delete(array $params): void {
		require_organizer();
		$templateId = param('id', $params);
		DB::pdo()->prepare('DELETE FROM subcategory_templates WHERE id = ?')->execute([$templateId]);
		redirect('/admin/templates');
	}
}

class CategoryAssignmentController {
	public function edit(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$category = DB::pdo()->prepare('SELECT * FROM categories WHERE id = ?');
		$category->execute([$categoryId]);
		$category = $category->fetch(\PDO::FETCH_ASSOC);
		$contestants = DB::pdo()->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		$judges = DB::pdo()->query('SELECT * FROM judges ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		$assignedContestants = DB::pdo()->prepare('SELECT contestant_id FROM category_contestants WHERE category_id = ?');
		$assignedContestants->execute([$categoryId]);
		$assignedContestants = array_column($assignedContestants->fetchAll(\PDO::FETCH_ASSOC), 'contestant_id');
		$assignedJudges = DB::pdo()->prepare('SELECT judge_id FROM category_judges WHERE category_id = ?');
		$assignedJudges->execute([$categoryId]);
		$assignedJudges = array_column($assignedJudges->fetchAll(\PDO::FETCH_ASSOC), 'judge_id');
		view('category_assignments/edit', compact('category','contestants','judges','assignedContestants','assignedJudges'));
	}
	public function update(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$contestants = request_array('contestants');
		$judges = request_array('judges');
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		// Get all subcategories for this category
		$subcategories = $pdo->prepare('SELECT id FROM subcategories WHERE category_id = ?');
		$subcategories->execute([$categoryId]);
		$subcategoryIds = array_column($subcategories->fetchAll(\PDO::FETCH_ASSOC), 'id');
		
		// Update category assignments
		$pdo->prepare('DELETE FROM category_contestants WHERE category_id = ?')->execute([$categoryId]);
		$pdo->prepare('DELETE FROM category_judges WHERE category_id = ?')->execute([$categoryId]);
		$insC = $pdo->prepare('INSERT INTO category_contestants (category_id, contestant_id) VALUES (?, ?)');
		$insJ = $pdo->prepare('INSERT INTO category_judges (category_id, judge_id) VALUES (?, ?)');
		foreach ($contestants as $id) { if ($id) $insC->execute([$categoryId, $id]); }
		foreach ($judges as $id) { if ($id) $insJ->execute([$categoryId, $id]); }
		
		// Automatically assign to all subcategories
		$insSC = $pdo->prepare('INSERT OR IGNORE INTO subcategory_contestants (subcategory_id, contestant_id) VALUES (?, ?)');
		$insSJ = $pdo->prepare('INSERT OR IGNORE INTO subcategory_judges (subcategory_id, judge_id) VALUES (?, ?)');
		
		foreach ($subcategoryIds as $subcategoryId) {
			foreach ($contestants as $id) { 
				if ($id) $insSC->execute([$subcategoryId, $id]); 
			}
			foreach ($judges as $id) { 
				if ($id) $insSJ->execute([$subcategoryId, $id]); 
			}
		}
		
		$pdo->commit();
		$_SESSION['success_message'] = 'Category assignments updated successfully! All assigned judges and contestants have been automatically assigned to subcategories.';
		redirect('/categories/' . $categoryId . '/assign');
	}
}

class PrintController {
	public function contestant(array $params): void {
		require_login();
		$contestantId = param('id', $params);
		
		// Get contestant information
		$stmt = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
		$stmt->execute([$contestantId]);
		$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contestant) {
			http_response_code(404);
			echo 'Contestant not found';
			return;
		}
		
		// Get all scores for this contestant
		$stmt = DB::pdo()->prepare('
			SELECT s.*, cr.name as criterion_name, cr.max_score, j.name as judge_name, 
				   sc.name as subcategory_name, c.name as category_name, co.name as contest_name
			FROM scores s
			JOIN criteria cr ON s.criterion_id = cr.id
			JOIN judges j ON s.judge_id = j.id
			JOIN subcategories sc ON s.subcategory_id = sc.id
			JOIN categories c ON sc.category_id = c.id
			JOIN contests co ON c.contest_id = co.id
			WHERE s.contestant_id = ?
			ORDER BY co.name, c.name, sc.name, cr.name, j.name
		');
		$stmt->execute([$contestantId]);
		$scores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all comments for this contestant
		$stmt = DB::pdo()->prepare('
			SELECT jc.*, j.name as judge_name, sc.name as subcategory_name, c.name as category_name, co.name as contest_name
			FROM judge_comments jc
			JOIN judges j ON jc.judge_id = j.id
			JOIN subcategories sc ON jc.subcategory_id = sc.id
			JOIN categories c ON sc.category_id = c.id
			JOIN contests co ON c.contest_id = co.id
			WHERE jc.contestant_id = ?
			ORDER BY co.name, c.name, sc.name, j.name
		');
		$stmt->execute([$contestantId]);
		$comments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('print/contestant', compact('contestant', 'scores', 'comments'));
	}
	
	public function judge(array $params): void {
		require_login();
		$judgeId = param('id', $params);
		
		// Get judge information
		$stmt = DB::pdo()->prepare('SELECT * FROM judges WHERE id = ?');
		$stmt->execute([$judgeId]);
		$judge = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$judge) {
			http_response_code(404);
			echo 'Judge not found';
			return;
		}
		
		// Get all scores given by this judge
		$stmt = DB::pdo()->prepare('
			SELECT s.*, cr.name as criterion_name, cr.max_score, con.name as contestant_name, 
				   sc.name as subcategory_name, c.name as category_name, co.name as contest_name
			FROM scores s
			JOIN criteria cr ON s.criterion_id = cr.id
			JOIN contestants con ON s.contestant_id = con.id
			JOIN subcategories sc ON s.subcategory_id = sc.id
			JOIN categories c ON sc.category_id = c.id
			JOIN contests co ON c.contest_id = co.id
			WHERE s.judge_id = ?
			ORDER BY co.name, c.name, sc.name, con.name, cr.name
		');
		$stmt->execute([$judgeId]);
		$scores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all comments given by this judge
		$stmt = DB::pdo()->prepare('
			SELECT jc.*, con.name as contestant_name, sc.name as subcategory_name, c.name as category_name, co.name as contest_name
			FROM judge_comments jc
			JOIN contestants con ON jc.contestant_id = con.id
			JOIN subcategories sc ON jc.subcategory_id = sc.id
			JOIN categories c ON sc.category_id = c.id
			JOIN contests co ON c.contest_id = co.id
			WHERE jc.judge_id = ?
			ORDER BY co.name, c.name, sc.name, con.name
		');
		$stmt->execute([$judgeId]);
		$comments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('print/judge', compact('judge', 'scores', 'comments'));
	}
	
	public function category(array $params): void {
		require_login();
		$categoryId = param('id', $params);
		
		// Get category information
		$stmt = DB::pdo()->prepare('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id WHERE c.id = ?');
		$stmt->execute([$categoryId]);
		$category = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$category) {
			http_response_code(404);
			echo 'Category not found';
			return;
		}
		
		// Get all contestants in this category
		$stmt = DB::pdo()->prepare('
			SELECT DISTINCT con.*, SUM(s.score) as total_score
			FROM contestants con
			JOIN subcategory_contestants sc ON con.id = sc.contestant_id
			JOIN subcategories sub ON sc.subcategory_id = sub.id AND sub.category_id = ?
			LEFT JOIN scores s ON s.contestant_id = con.id AND s.subcategory_id = sub.id
			GROUP BY con.id, con.name, con.email, con.gender, con.contestant_number, con.bio, con.image_path
			ORDER BY total_score DESC, con.contestant_number IS NULL, con.contestant_number, con.name
		');
		$stmt->execute([$categoryId]);
		$contestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all subcategories in this category
		$stmt = DB::pdo()->prepare('SELECT * FROM subcategories WHERE category_id = ? ORDER BY name');
		$stmt->execute([$categoryId]);
		$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('print/category', compact('category', 'contestants', 'subcategories'));
	}
}

class ProfileController {
	public function edit(): void { 
		require_login(); 
		$user = current_user();
		$judge = null;
		
		// Get judge data if user is a judge
		if (($user['role'] ?? '') === 'judge' && !empty($user['judge_id'])) {
			$stmt = DB::pdo()->prepare('SELECT * FROM judges WHERE id = ?');
			$stmt->execute([$user['judge_id']]);
			$judge = $stmt->fetch(\PDO::FETCH_ASSOC);
		}
		
		view('profile/edit', compact('user', 'judge')); 
	}
	public function update(): void {
		require_login();
		$user = current_user();
		$name = post('name'); 
		$preferredName = post('preferred_name') ?: null;
		$email = post('email'); 
		$gender = post('gender');
		$pwd = (string)(post('password') ?? '');
		
		// Check if email already exists (excluding current user)
		$stmt = DB::pdo()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
		$stmt->execute([$email, $user['id']]);
		if ($stmt->fetch()) {
			redirect('/profile?error=email_exists');
			return;
		}
		
		// Check if preferred name already exists (excluding current user)
		if ($preferredName) {
			$stmt = DB::pdo()->prepare('SELECT id FROM users WHERE preferred_name = ? AND id != ?');
			$stmt->execute([$preferredName, $user['id']]);
			if ($stmt->fetch()) {
				redirect('/profile?error=preferred_name_exists');
				return;
			}
		}
		
		// Handle judge bio and image upload
		if (($user['role'] ?? '') === 'judge' && !empty($user['judge_id'])) {
			$imagePath = null;
			
			// Handle image upload
			if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
				$uploadDir = __DIR__ . '/../../public/uploads/judges/';
				// Create directory if it doesn't exist
				if (!is_dir($uploadDir)) {
					mkdir($uploadDir, 0755, true);
				}
				$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
				$filename = uuid() . '.' . $extension;
				$imagePath = '/uploads/judges/' . $filename;
				
				if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
					$_SESSION['error_message'] = 'Failed to upload image - check directory permissions';
					redirect('/profile');
					return;
				}
			}
			
			// Get current image path if no new image uploaded
			if (!$imagePath) {
				$stmt = DB::pdo()->prepare('SELECT image_path FROM judges WHERE id = ?');
				$stmt->execute([$user['judge_id']]);
				$current = $stmt->fetch(\PDO::FETCH_ASSOC);
				$imagePath = $current['image_path'] ?? null;
			}
			
			// Update judge record with bio and image
			DB::pdo()->prepare('UPDATE judges SET name=?, email=?, gender=?, bio=?, image_path=? WHERE id=?')
				->execute([$name, $email ?: null, $gender ?: null, post('bio') ?: null, $imagePath, $user['judge_id']]);
		}
		
		if (!empty($pwd)) {
			$hash = password_hash($pwd, PASSWORD_BCRYPT);
			DB::pdo()->prepare('UPDATE users SET name=?, preferred_name=?, email=?, gender=?, password_hash=? WHERE id=?')
				->execute([$name,$preferredName,$email,$gender ?: null,$hash,$user['id']]);
		} else {
			DB::pdo()->prepare('UPDATE users SET name=?, preferred_name=?, email=?, gender=? WHERE id=?')
				->execute([$name,$preferredName,$email,$gender ?: null,$user['id']]);
		}
		$_SESSION['user']['name'] = $name; 
		$_SESSION['user']['preferred_name'] = $preferredName;
		$_SESSION['user']['email'] = $email; 
		$_SESSION['user']['gender'] = $gender;
		redirect('/profile?success=updated');
	}
}


