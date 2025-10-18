<?php use function App\{url, hierarchical_back_url, home_url, csrf_field}; ?>
<h2>Final Certification</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<?php if ($alreadyCertified): ?>
	<div class="certification-status">
		<div class="alert alert-success">
			<h3>✅ Final Certification Completed</h3>
			<p>You have already completed the final certification process. All scores have been officially certified.</p>
		</div>
	</div>
<?php elseif ($canCertify): ?>
	<div class="certification-ready">
		<div class="alert alert-success">
			<h3>✅ Ready for Final Certification</h3>
			<p>All Tally Masters have completed their certifications. You may now proceed with the final certification.</p>
		</div>
		
		<div class="certification-warning">
			<div class="alert alert-warning">
				<h4>⚠️ Important Notice</h4>
				<p><strong>This action is irreversible.</strong> By proceeding with final certification, you are officially certifying that:</p>
				<ul>
					<li>All Tally Masters have completed their score verifications</li>
					<li>All Judge scores have been properly certified</li>
					<li>The final totals are accurate and complete</li>
					<li>No further changes to scores will be permitted</li>
				</ul>
			</div>
		</div>
		
		<div class="certification-form">
			<form method="post" action="<?= url('auditor/final-certification') ?>" onsubmit="return confirmFinalCertification()">
				<?= csrf_field() ?>
				
				<div class="form-group">
					<label>
						<input type="checkbox" required>
						I confirm that I have reviewed all score certifications and am ready to proceed with final certification.
					</label>
				</div>
				
				<div class="form-group">
					<label>
						<input type="checkbox" required>
						I understand that this action will lock all scores and prevent further modifications.
					</label>
				</div>
				
				<div class="form-actions">
					<button type="submit" class="btn btn-danger btn-large">
						🔒 Complete Final Certification
					</button>
				</div>
			</form>
		</div>
	</div>
<?php else: ?>
	<div class="certification-not-ready">
		<div class="alert alert-warning">
			<h3>⏳ Not Ready for Final Certification</h3>
			<p>Tally Masters have not completed all required certifications yet. Please check the Tally Master Status page for details.</p>
		</div>
		
		<div class="action-buttons">
			<a href="<?= url('auditor/tally-master-status') ?>" class="btn btn-primary">
				📊 Check Tally Master Status
			</a>
			<a href="<?= url('auditor/scores') ?>" class="btn btn-secondary">
				📋 Review All Scores
			</a>
		</div>
	</div>
<?php endif; ?>

<script>
function confirmFinalCertification() {
	return confirm(
		'Are you absolutely sure you want to complete the final certification?\n\n' +
		'This action will:\n' +
		'• Lock all scores permanently\n' +
		'• Prevent any further modifications\n' +
		'• Complete the official certification process\n\n' +
		'This action cannot be undone. Do you wish to proceed?'
	);
}
</script>

<style>
.certification-status,
.certification-ready,
.certification-not-ready {
	margin: 20px 0;
}

.certification-warning {
	margin: 20px 0;
}

.certification-warning ul {
	margin: 10px 0;
	padding-left: 20px;
}

.certification-warning li {
	margin: 5px 0;
}

.certification-form {
	margin: 30px 0;
	padding: 20px;
	background: var(--bg-primary);
	border: 1px solid var(--border-color);
	border-radius: 8px;
}

.form-group {
	margin: 15px 0;
}

.form-group label {
	display: flex;
	align-items: flex-start;
	gap: 10px;
	cursor: pointer;
	font-weight: normal;
}

.form-group input[type="checkbox"] {
	margin-top: 2px;
	flex-shrink: 0;
}

.form-actions {
	margin-top: 30px;
	text-align: center;
}

.btn-large {
	padding: 15px 30px;
	font-size: 1.2em;
}

.action-buttons {
	margin: 20px 0;
	display: flex;
	gap: 15px;
	justify-content: center;
}

.alert h3,
.alert h4 {
	margin: 0 0 10px 0;
}

.alert p {
	margin: 0 0 10px 0;
}

.alert p:last-child {
	margin-bottom: 0;
}
</style>
