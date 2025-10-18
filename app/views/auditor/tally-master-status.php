<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>Tally Master Verification Status</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="status-container">
	<div class="overall-status">
		<h3>Overall Certification Status</h3>
		<div class="status-card">
			<div class="status-metric">
				<span class="metric-label">Total Judges:</span>
				<span class="metric-value"><?= $overallStatus['total_judges'] ?></span>
			</div>
			<div class="status-metric">
				<span class="metric-label">Certifications Completed:</span>
				<span class="metric-value"><?= $overallStatus['total_certifications'] ?></span>
			</div>
			<div class="status-metric">
				<span class="metric-label">Completion Rate:</span>
				<span class="metric-value <?= $overallStatus['is_complete'] ? 'status-complete' : 'status-incomplete' ?>">
					<?= $overallStatus['completion_percentage'] ?>%
				</span>
			</div>
			<div class="status-indicator">
				<?php if ($overallStatus['is_complete']): ?>
					<span class="status-badge complete">✅ All Tally Masters Ready</span>
				<?php else: ?>
					<span class="status-badge incomplete">⏳ Pending Certifications</span>
				<?php endif; ?>
			</div>
		</div>
	</div>
	
	<div class="tally-master-details">
		<h3>Individual Tally Master Status</h3>
		<div class="tally-master-list">
			<?php foreach ($tallyMasterStatus as $tallyMaster): ?>
				<div class="tally-master-card">
					<div class="tally-master-header">
						<h4><?= htmlspecialchars($tallyMaster['tally_master_name']) ?></h4>
						<?php 
						$completionRate = $tallyMaster['total_judges'] > 0 
							? round(($tallyMaster['certified_count'] / $tallyMaster['total_judges']) * 100, 2)
							: 0;
						$isComplete = $tallyMaster['certified_count'] >= $tallyMaster['total_judges'];
						?>
						<span class="completion-rate <?= $isComplete ? 'complete' : 'incomplete' ?>">
							<?= $completionRate ?>%
						</span>
					</div>
					<div class="tally-master-metrics">
						<div class="metric">
							<span class="metric-label">Judges Assigned:</span>
							<span class="metric-value"><?= $tallyMaster['total_judges'] ?></span>
						</div>
						<div class="metric">
							<span class="metric-label">Certifications Completed:</span>
							<span class="metric-value"><?= $tallyMaster['certified_count'] ?></span>
						</div>
						<div class="metric">
							<span class="metric-label">Status:</span>
							<span class="metric-value">
								<?php if ($isComplete): ?>
									<span class="status-complete">✅ Complete</span>
								<?php else: ?>
									<span class="status-incomplete">⏳ In Progress</span>
								<?php endif; ?>
							</span>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<?php if ($overallStatus['is_complete']): ?>
	<div class="action-section">
		<a href="<?= url('auditor/final-certification') ?>" class="btn btn-primary btn-large">
			🔒 Proceed to Final Certification
		</a>
	</div>
<?php else: ?>
	<div class="info-section">
		<div class="alert alert-info">
			<strong>Note:</strong> All Tally Masters must complete their certifications before final certification can proceed.
		</div>
	</div>
<?php endif; ?>

<style>
.status-container {
	margin: 20px 0;
}

.overall-status {
	margin-bottom: 30px;
}

.overall-status h3 {
	margin: 0 0 15px 0;
	color: var(--text-primary);
}

.status-card {
	background: var(--bg-primary);
	border: 1px solid var(--border-color);
	border-radius: 8px;
	padding: 20px;
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
	align-items: center;
}

.status-metric {
	display: flex;
	flex-direction: column;
	gap: 5px;
}

.metric-label {
	font-size: 0.9em;
	color: var(--text-secondary);
}

.metric-value {
	font-size: 1.2em;
	font-weight: bold;
	color: var(--text-primary);
}

.status-complete {
	color: var(--success-color);
}

.status-incomplete {
	color: var(--warning-color);
}

.status-indicator {
	grid-column: 1 / -1;
	text-align: center;
	margin-top: 10px;
}

.status-badge {
	padding: 8px 16px;
	border-radius: 20px;
	font-weight: bold;
	font-size: 1.1em;
}

.status-badge.complete {
	background: var(--success-color);
	color: white;
}

.status-badge.incomplete {
	background: var(--warning-color);
	color: white;
}

.tally-master-details h3 {
	margin: 0 0 20px 0;
	color: var(--text-primary);
}

.tally-master-list {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
}

.tally-master-card {
	background: var(--bg-primary);
	border: 1px solid var(--border-color);
	border-radius: 8px;
	padding: 20px;
}

.tally-master-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 15px;
	padding-bottom: 10px;
	border-bottom: 1px solid var(--border-color);
}

.tally-master-header h4 {
	margin: 0;
	color: var(--text-primary);
}

.completion-rate {
	font-size: 1.2em;
	font-weight: bold;
	padding: 4px 8px;
	border-radius: 4px;
}

.completion-rate.complete {
	background: var(--success-color);
	color: white;
}

.completion-rate.incomplete {
	background: var(--warning-color);
	color: white;
}

.tally-master-metrics {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.metric {
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.action-section {
	margin-top: 30px;
	text-align: center;
}

.btn-large {
	padding: 15px 30px;
	font-size: 1.2em;
}

.info-section {
	margin-top: 20px;
}
</style>
