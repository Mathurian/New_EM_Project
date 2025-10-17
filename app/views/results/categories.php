<?php use function App\{is_organizer, is_judge, url, hierarchical_back_url, home_url}; use App\DB; ?>
<h2>Results by Category</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
	<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (is_organizer()): ?>
	<div class="warning-box">
		<h3>Admin Actions</h3>
		<p><strong>Bulk Unsign Operations:</strong> Use the buttons below to unsign scores by category or contestant.</p>
	</div>
<?php endif; ?>

<?php if (is_judge()): ?>
	<div class="info-box">
		<p><strong>Judge View:</strong> You can only see your own scores and certification status. Rankings and total scores are not visible to judges.</p>
	</div>
<?php endif; ?>

<?php
// Group categories by contest
$byContest = [];
foreach ($categories as $cat) {
	$byContest[$cat['contest_name']][] = $cat;
}
?>

<?php if (empty($byContest)): ?>
	<div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0; border-radius: 5px;">
		<p><strong>No contests found.</strong> Please create contests and categories first.</p>
	</div>
<?php else: ?>
	<?php foreach ($byContest as $contestName => $contestCategories): ?>
		<h3><?= htmlspecialchars($contestName) ?></h3>
	
	<?php foreach ($contestCategories as $category): ?>
		<h4><?= htmlspecialchars($category['name']) ?>
			<?php if (is_organizer()): ?>
				<a href="<?= url('print/category/' . urlencode($category['id'])) ?>" style="margin-left: 10px; background: #28a745; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; text-decoration: none;">Print Category</a>
				<form method="post" action="<?= url('results/category/' . urlencode($category['id']) . '/unsign-all') ?>" style="display: inline-block; margin-left: 10px;">
					<button type="submit" onclick="return confirm('Are you sure you want to unsign ALL scores for the <?= htmlspecialchars($category['name']) ?> category? This will unlock all certified scores for editing.')" style="background: #dc3545; color: white; border: none; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
						Unsign All Scores
					</button>
				</form>
			<?php endif; ?>
		</h4>
		
		<?php if (is_organizer() && !empty($leadContestants[$category['id']])): ?>
			<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 5px;">
				<strong>🏆 Current Leader:</strong> 
				<?php 
				$leader = $leadContestants[$category['id']];
				echo htmlspecialchars($leader['contestantName']);
				if (!empty($leader['contestant_number'])) {
					echo ' (#' . htmlspecialchars($leader['contestant_number']) . ')';
				}
				echo ' - ' . htmlspecialchars($leader['totalScore']) . ' points';
				?>
			</div>
		<?php endif; ?>
		
		<!-- Categories for this contest -->
		<?php 
		$categorySubcategories = array_filter($subcategories, function($sc) use ($category) {
			return $sc['category_id'] === $category['id'];
		});
		?>
		<?php if (!empty($categorySubcategories)): ?>
			<p><strong>Categories:</strong>
			<?php foreach ($categorySubcategories as $sc): ?>
				<a href="<?= url('results/' . urlencode($sc['id']) . '/detailed') ?>" style="margin-right: 10px;"><?= htmlspecialchars($sc['name']) ?></a>
			<?php endforeach; ?>
			</p>
		<?php endif; ?>
		
		<?php if (is_organizer()): ?>
			<!-- Admin view: Show all results with rankings -->
			<?php 
			$categoryResults = array_filter($rows, function($row) use ($category) {
				return $row['categoryId'] === $category['id'];
			});
			?>
			<?php if (!empty($categoryResults)): ?>
				<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
					<tr style="background: #f8f9fa;">
						<th style="border: 1px solid #dee2e6; padding: 8px;">Rank</th>
						<th style="border: 1px solid #dee2e6; padding: 8px;">Contestant</th>
						<th style="border: 1px solid #dee2e6; padding: 8px;">Number</th>
						<th style="border: 1px solid #dee2e6; padding: 8px;">Total Score</th>
						<th style="border: 1px solid #dee2e6; padding: 8px;">Actions</th>
					</tr>
					<?php 
					$rank = 1;
					foreach ($categoryResults as $result): 
					?>
						<tr>
							<td style="border: 1px solid #dee2e6; padding: 8px;"><?= $rank++ ?></td>
							<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars($result['contestantName']) ?></td>
							<td style="border: 1px solid #dee2e6; padding: 8px;"><?= !empty($result['contestant_number']) ? '#' . htmlspecialchars($result['contestant_number']) : '-' ?></td>
							<td style="border: 1px solid #dee2e6; padding: 8px; font-weight: bold;"><?= htmlspecialchars($result['totalScore']) ?></td>
							<td style="border: 1px solid #dee2e6; padding: 8px;">
								<a href="<?= url('results/contestant/' . urlencode($result['contestantId']) . '/category/' . urlencode($category['id'])) ?>">View Details</a> |
								<form method="post" action="<?= url('results/contestant/' . urlencode($result['contestantId']) . '/unsign-all') ?>" style="display: inline;">
									<button type="submit" onclick="return confirm('Are you sure you want to unsign ALL scores for <?= htmlspecialchars($result['contestantName']) ?>?')" style="background: #dc3545; color: white; border: none; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
										Unsign All
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php else: ?>
				<p>No scores recorded for this category yet.</p>
			<?php endif; ?>
		<?php elseif (is_judge()): ?>
			<!-- Judge view: Show only their own scores and certification status -->
			<?php 
			$judgeId = $_SESSION['user']['judge_id'] ?? '';
			$judgeResults = DB::pdo()->prepare('
				SELECT DISTINCT con.id, con.name, con.contestant_number,
					CASE WHEN jc.judge_id IS NOT NULL THEN "Signed" ELSE "Not Signed" END as certification_status
				FROM contestants con
				JOIN subcategory_contestants sc ON con.id = sc.contestant_id
				JOIN subcategories sub ON sc.subcategory_id = sub.id AND sub.category_id = ?
				LEFT JOIN judge_certifications jc ON sc.subcategory_id = jc.subcategory_id AND jc.judge_id = ?
				ORDER BY con.contestant_number IS NULL, con.contestant_number, con.name
			');
			$judgeResults->execute([$category['id'], $judgeId]);
			$judgeCategoryResults = $judgeResults->fetchAll(\PDO::FETCH_ASSOC);
			?>
			<?php if (!empty($judgeCategoryResults)): ?>
				<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
					<tr style="background: #f8f9fa;">
						<th style="border: 1px solid #dee2e6; padding: 8px;">Contestant</th>
						<th style="border: 1px solid #dee2e6; padding: 8px;">Number</th>
						<th style="border: 1px solid #dee2e6; padding: 8px;">Certification Status</th>
						<th style="border: 1px solid #dee2e6; padding: 8px;">Actions</th>
					</tr>
					<?php foreach ($judgeCategoryResults as $result): ?>
						<tr>
							<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars($result['name']) ?></td>
							<td style="border: 1px solid #dee2e6; padding: 8px;"><?= !empty($result['contestant_number']) ? '#' . htmlspecialchars($result['contestant_number']) : '-' ?></td>
							<td style="border: 1px solid #dee2e6; padding: 8px;">
								<span style="color: <?= $result['certification_status'] === 'Signed' ? 'green' : 'orange' ?>; font-weight: bold;">
									<?= htmlspecialchars($result['certification_status']) ?>
								</span>
							</td>
							<td style="border: 1px solid #dee2e6; padding: 8px;">
								<a href="<?= url('score/' . urlencode($result['id'])) ?>">Score</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php else: ?>
				<p>No contestants assigned to this category.</p>
			<?php endif; ?>
		<?php endif; ?>
		
	<?php endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>