<?php use function App\{url, is_organizer, hierarchical_back_url, home_url}; ?>
<h2>Archived Contest Details</h2>
<div class="navigation-buttons">
	<a href="<?= url('admin/archived-contests') ?>" class="btn btn-secondary">← Back to Archived Contests</a>
	<a href="<?= url('admin/archived-contest/' . urlencode($contest['id']) . '/print') ?>" class="btn btn-primary">🖨️ Print All Scores</a>
	<form method="post" action="<?= url('admin/archived-contest/' . urlencode($contest['id']) . '/reactivate') ?>" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to reactivate this contest? This will create a new active contest with all the archived data.');">
		<button type="submit" class="btn btn-success">🔄 Re-activate Contest</button>
	</form>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="card">
	<h3><?= htmlspecialchars($contest['name']) ?></h3>
	<div class="contest-info">
		<p><strong>Start Date:</strong> <?= htmlspecialchars($contest['start_date']) ?></p>
		<p><strong>End Date:</strong> <?= htmlspecialchars($contest['end_date']) ?></p>
		<p><strong>Archived By:</strong> <?= htmlspecialchars($contest['archived_by']) ?></p>
		<p><strong>Archived Date:</strong> <?= htmlspecialchars($contest['archived_at']) ?></p>
		<?php if (!empty($contest['description'])): ?>
			<p><strong>Description:</strong> <?= htmlspecialchars($contest['description']) ?></p>
		<?php endif; ?>
	</div>
</div>

<?php if (!empty($categoryWinners)): ?>
<div class="card">
	<h4>🏆 Category Winners</h4>
	<div class="row">
		<?php foreach ($categoryWinners as $categoryId => $winner): ?>
			<?php 
			$category = array_filter($categories, function($cat) use ($categoryId) {
				return $cat['id'] === $categoryId;
			});
			$category = reset($category);
			?>
			<div class="col-6">
				<div class="winner-card">
					<h5><?= htmlspecialchars($category['name']) ?></h5>
					<div class="winner-info">
						<p><strong>Winner:</strong> 
							<?php if (!empty($winner['contestant_number'])): ?>
								#<?= htmlspecialchars($winner['contestant_number']) ?> - 
							<?php endif; ?>
							<?= htmlspecialchars($winner['contestant_name']) ?>
						</p>
						<p><strong>Total Score:</strong> <?= number_format($winner['total_score'], 1) ?></p>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

<div class="row">
	<div class="col-6">
		<div class="card">
			<h4>Categories (<?= count($categories) ?>)</h4>
			<?php if (empty($categories)): ?>
				<p>No categories found.</p>
			<?php else: ?>
				<ul>
					<?php foreach ($categories as $category): ?>
						<li><?= htmlspecialchars($category['name']) ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
	
	<div class="col-6">
		<div class="card">
			<h4>Subcategories (<?= count($subcategories) ?>)</h4>
			<?php if (empty($subcategories)): ?>
				<p>No subcategories found.</p>
			<?php else: ?>
				<ul>
					<?php foreach ($subcategories as $subcategory): ?>
						<li><?= htmlspecialchars($subcategory['category_name']) ?> - <?= htmlspecialchars($subcategory['name']) ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-6">
		<div class="card">
			<h4>Contestants (<?= count($contestants) ?>)</h4>
			<?php if (empty($contestants)): ?>
				<p>No contestants found.</p>
			<?php else: ?>
				<ul>
					<?php foreach ($contestants as $contestant): ?>
						<li>
							<?php if (!empty($contestant['contestant_number'])): ?>
								#<?= htmlspecialchars($contestant['contestant_number']) ?> - 
							<?php endif; ?>
							<?= htmlspecialchars($contestant['name']) ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
	
	<div class="col-6">
		<div class="card">
			<h4>Judges (<?= count($judges) ?>)</h4>
			<?php if (empty($judges)): ?>
				<p>No judges found.</p>
			<?php else: ?>
				<ul>
					<?php foreach ($judges as $judge): ?>
						<li>
							<?= htmlspecialchars($judge['name']) ?>
							<?php if (!empty($judge['is_head_judge'])): ?>
								<span class="badge badge-primary">Head Judge</span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="card">
	<div class="alert alert-info">
		<p><strong>Note:</strong> This contest has been archived. All data has been preserved for historical reference but is no longer active for scoring or management.</p>
		<p><strong>Print Report:</strong> Use the "Print All Scores" button above to generate a comprehensive report of all scores, comments, and deductions for this archived contest.</p>
	</div>
</div>

<style>
.winner-card {
	background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
	border: 2px solid #28a745;
	border-radius: 8px;
	padding: 15px;
	margin-bottom: 15px;
}
.winner-card h5 {
	color: #28a745;
	margin-top: 0;
	font-weight: bold;
}
.winner-info p {
	margin: 5px 0;
}
</style>
