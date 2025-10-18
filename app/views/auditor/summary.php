<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>Score Summary</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="summary-container">
	<?php foreach ($groupedSummary as $contestName => $categories): ?>
		<div class="contest-summary">
			<h3><?= htmlspecialchars($contestName) ?></h3>
			
			<?php foreach ($categories as $categoryName => $contestants): ?>
				<div class="category-summary">
					<h4><?= htmlspecialchars($categoryName) ?></h4>
					
					<table class="summary-table">
						<thead>
							<tr>
								<th>Contestant</th>
								<th>Number</th>
								<th>Subcategory</th>
								<th>Avg Score</th>
								<th>Min Score</th>
								<th>Max Score</th>
								<th>Score Count</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($contestants as $contestant): ?>
								<tr>
									<td><?= htmlspecialchars($contestant['contestant_name']) ?></td>
									<td><?= htmlspecialchars($contestant['contestant_number'] ?? '-') ?></td>
									<td><?= htmlspecialchars($contestant['subcategory_name']) ?></td>
									<td class="score-value"><?= number_format($contestant['average_score'], 2) ?></td>
									<td><?= htmlspecialchars($contestant['min_score']) ?></td>
									<td><?= htmlspecialchars($contestant['max_score']) ?></td>
									<td><?= htmlspecialchars($contestant['score_count']) ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					
					<?php 
					// Calculate category statistics
					$categoryStats = [
						'total_contestants' => count($contestants),
						'overall_avg' => array_sum(array_column($contestants, 'average_score')) / count($contestants),
						'highest_score' => max(array_column($contestants, 'max_score')),
						'lowest_score' => min(array_column($contestants, 'min_score')),
						'total_scores' => array_sum(array_column($contestants, 'score_count'))
					];
					?>
					
					<div class="category-stats">
						<div class="stats-grid">
							<div class="stat-item">
								<span class="stat-label">Total Contestants:</span>
								<span class="stat-value"><?= $categoryStats['total_contestants'] ?></span>
							</div>
							<div class="stat-item">
								<span class="stat-label">Category Average:</span>
								<span class="stat-value"><?= number_format($categoryStats['overall_avg'], 2) ?></span>
							</div>
							<div class="stat-item">
								<span class="stat-label">Highest Score:</span>
								<span class="stat-value"><?= $categoryStats['highest_score'] ?></span>
							</div>
							<div class="stat-item">
								<span class="stat-label">Lowest Score:</span>
								<span class="stat-value"><?= $categoryStats['lowest_score'] ?></span>
							</div>
							<div class="stat-item">
								<span class="stat-label">Total Scores:</span>
								<span class="stat-value"><?= $categoryStats['total_scores'] ?></span>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
</div>

<style>
.summary-container {
	margin: 20px 0;
}

.contest-summary {
	margin-bottom: 40px;
	padding: 20px;
	border: 1px solid var(--border-color);
	border-radius: 8px;
	background: var(--bg-primary);
}

.contest-summary h3 {
	margin: 0 0 20px 0;
	color: var(--text-primary);
	font-size: 1.5em;
	border-bottom: 2px solid var(--primary-color);
	padding-bottom: 10px;
}

.category-summary {
	margin-bottom: 30px;
	padding: 15px;
	border: 1px solid var(--border-color);
	border-radius: 6px;
	background: var(--bg-secondary);
}

.category-summary h4 {
	margin: 0 0 15px 0;
	color: var(--text-primary);
	font-size: 1.2em;
}

.summary-table {
	width: 100%;
	border-collapse: collapse;
	margin-bottom: 20px;
}

.summary-table th,
.summary-table td {
	padding: 10px 12px;
	text-align: left;
	border-bottom: 1px solid var(--border-color);
}

.summary-table th {
	background: var(--bg-primary);
	font-weight: bold;
	color: var(--text-primary);
}

.summary-table td {
	color: var(--text-secondary);
}

.score-value {
	font-weight: bold;
	color: var(--primary-color);
}

.category-stats {
	margin-top: 20px;
	padding: 15px;
	background: var(--bg-tertiary);
	border-radius: 4px;
}

.stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
}

.stat-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 8px 0;
	border-bottom: 1px solid var(--border-color);
}

.stat-item:last-child {
	border-bottom: none;
}

.stat-label {
	color: var(--text-secondary);
	font-weight: normal;
}

.stat-value {
	color: var(--text-primary);
	font-weight: bold;
}
</style>
