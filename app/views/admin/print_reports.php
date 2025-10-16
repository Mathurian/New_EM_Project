<?php use function App\{url, is_organizer, hierarchical_back_url, home_url}; ?>
<h2>Print Reports</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="print-reports-container">
	<div class="card">
		<h3>📊 Contestant Reports</h3>
		<p>Generate detailed score reports for individual contestants.</p>
		<div class="contestant-list">
			<?php if (empty($contestants)): ?>
				<p class="no-data">No contestants found.</p>
			<?php else: ?>
				<?php foreach ($contestants as $contestant): ?>
					<div class="contestant-item">
						<div class="contestant-info">
							<strong><?= htmlspecialchars($contestant['name']) ?></strong>
							<?php if ($contestant['contestant_number']): ?>
								<span class="contestant-number">#<?= htmlspecialchars($contestant['contestant_number']) ?></span>
							<?php endif; ?>
						</div>
						<a href="<?= url('print/contestant/' . $contestant['id']) ?>" class="btn btn-primary" target="_blank">
							🖨️ Print Report
						</a>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="card">
		<h3>⚖️ Judge Reports</h3>
		<p>Generate score reports for individual judges showing all their scores.</p>
		<div class="judge-list">
			<?php if (empty($judges)): ?>
				<p class="no-data">No judges found.</p>
			<?php else: ?>
				<?php foreach ($judges as $judge): ?>
					<div class="judge-item">
						<div class="judge-info">
							<strong><?= htmlspecialchars($judge['name']) ?></strong>
						</div>
						<a href="<?= url('print/judge/' . $judge['id']) ?>" class="btn btn-primary" target="_blank">
							🖨️ Print Report
						</a>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="card">
		<h3>📈 Category Reports</h3>
		<p>Generate comprehensive reports for entire categories.</p>
		<div class="category-list">
			<?php 
			$groupedStructure = [];
			foreach ($structure as $row) {
				if ($row['category_id']) {
					$groupedStructure[$row['contest_id']][$row['category_id']] = [
						'contest_name' => $row['contest_name'],
						'category_name' => $row['category_name']
					];
				}
			}
			?>
			<?php if (empty($groupedStructure)): ?>
				<p class="no-data">No categories found.</p>
			<?php else: ?>
				<?php foreach ($groupedStructure as $contestId => $categories): ?>
					<div class="contest-group">
						<h4><?= htmlspecialchars($categories[array_key_first($categories)]['contest_name']) ?></h4>
						<?php foreach ($categories as $categoryId => $category): ?>
							<div class="category-item">
								<div class="category-info">
									<strong><?= htmlspecialchars($category['category_name']) ?></strong>
								</div>
								<a href="<?= url('print/category/' . $categoryId) ?>" class="btn btn-primary" target="_blank">
									🖨️ Print Report
								</a>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>

<style>
.print-reports-container {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.card {
	background: var(--bg-primary);
	border: 1px solid var(--border-color);
	border-radius: 8px;
	padding: 20px;
}

.card h3 {
	margin-top: 0;
	color: var(--text-primary);
	border-bottom: 2px solid var(--accent-color);
	padding-bottom: 10px;
}

.card p {
	color: var(--text-secondary);
	margin-bottom: 15px;
}

.contestant-item,
.judge-item,
.category-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 10px;
	margin: 8px 0;
	background: var(--bg-secondary);
	border: 1px solid var(--border-color);
	border-radius: 6px;
}

.contestant-info,
.judge-info,
.category-info {
	flex: 1;
}

.contestant-number {
	background: var(--accent-color);
	color: white;
	padding: 2px 8px;
	border-radius: 12px;
	font-size: 0.8em;
	margin-left: 8px;
}

.contest-group {
	margin-bottom: 20px;
}

.contest-group h4 {
	color: var(--text-primary);
	margin-bottom: 10px;
	padding-bottom: 5px;
	border-bottom: 1px solid var(--border-color);
}

.no-data {
	color: var(--text-secondary);
	font-style: italic;
	text-align: center;
	padding: 20px;
}

.btn {
	text-decoration: none;
	padding: 8px 16px;
	border-radius: 4px;
	font-size: 0.9em;
	white-space: nowrap;
}

.btn-primary {
	background: var(--accent-color);
	color: white;
}

.btn-primary:hover {
	opacity: 0.9;
}

@media (max-width: 768px) {
	.print-reports-container {
		grid-template-columns: 1fr;
	}
	
	.contestant-item,
	.judge-item,
	.category-item {
		flex-direction: column;
		align-items: stretch;
		gap: 10px;
	}
	
	.btn {
		text-align: center;
	}
}
</style>
