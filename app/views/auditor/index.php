<?php use function App\{url, hierarchical_back_url, home_url, is_auditor}; ?>
<h2>Auditor Dashboard</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="dashboard-grid">
	<div class="dashboard-card">
		<h3>📊 Score Audit</h3>
		<p>Review and verify all scores across contests, categories, and subcategories.</p>
		<div class="card-actions">
			<a href="<?= url('auditor/scores') ?>" class="btn btn-primary">View All Scores</a>
		</div>
	</div>
	
	<div class="dashboard-card">
		<h3>✅ Tally Master Verification</h3>
		<p>Verify that all Tally Masters have certified all Judge scores.</p>
		<div class="card-actions">
			<a href="<?= url('auditor/tally-master-status') ?>" class="btn btn-primary">Check Status</a>
		</div>
	</div>
	
	<div class="dashboard-card">
		<h3>🔒 Final Certification</h3>
		<p>Sign and certify final totals after all Tally Masters have completed their verification.</p>
		<div class="card-actions">
			<a href="<?= url('auditor/final-certification') ?>" class="btn btn-primary">Final Certification</a>
		</div>
	</div>
	
	<div class="dashboard-card">
		<h3>📈 Score Summary</h3>
		<p>View comprehensive score summaries and statistics.</p>
		<div class="card-actions">
			<a href="<?= url('auditor/summary') ?>" class="btn btn-primary">View Summary</a>
		</div>
	</div>
</div>

<?php if (!empty($_GET['success'])): ?>
	<?php 
	$successMessages = [
		'certification_completed' => 'Final certification completed successfully!',
		'audit_completed' => 'Score audit completed successfully!'
	];
	$successMessage = $successMessages[$_GET['success']] ?? 'Operation completed successfully!';
	?>
	<div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'tally_masters_not_ready' => 'Tally Masters have not completed all certifications yet.',
		'certification_failed' => 'Final certification failed. Please try again.',
		'audit_failed' => 'Score audit failed. Please try again.'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<style>
.dashboard-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.dashboard-card {
	background: var(--bg-primary);
	border: 1px solid var(--border-color);
	border-radius: 8px;
	padding: 20px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.dashboard-card h3 {
	margin: 0 0 10px 0;
	color: var(--text-primary);
	font-size: 1.2em;
}

.dashboard-card p {
	margin: 0 0 15px 0;
	color: var(--text-secondary);
	line-height: 1.5;
}

.card-actions {
	display: flex;
	gap: 10px;
}

.card-actions .btn {
	flex: 1;
	text-align: center;
}
</style>
