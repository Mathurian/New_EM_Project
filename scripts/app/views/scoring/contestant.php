<?php use function App\{url, is_judge, is_organizer, hierarchical_back_url, home_url, current_user}; use App\DB; ?>
<h2>Score Contestant</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="scoring-header">
	<h3><?= htmlspecialchars($subcategory['category_name']) ?> - <?= htmlspecialchars($subcategory['name']) ?></h3>
	<div class="contestant-info">
		<h4><?= htmlspecialchars($contestant['name']) ?></h4>
		<?php if (!empty($contestant['contestant_number'])): ?>
			<p class="contestant-number">Contestant #<?= htmlspecialchars($contestant['contestant_number']) ?></p>
		<?php endif; ?>
		<?php if (!empty($contestant['bio'])): ?>
			<p class="contestant-bio"><?= htmlspecialchars($contestant['bio']) ?></p>
		<?php endif; ?>
	</div>
</div>

<?php if (!empty($_GET['error'])): ?>
	<?php
	$errorMessages = [
		'no_judge' => 'No judge specified for unsigning',
		'signature_mismatch' => 'Signature does not match your preferred name. Please enter your correct preferred name to certify scores.'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<?php if (!empty($_GET['success'])): ?>
	<div class="alert alert-success">Scores submitted successfully!</div>
<?php endif; ?>

<?php if ($isCertified && is_judge()): ?>
	<div class="alert alert-info">
		<p><strong>Scores Certified:</strong> Your scores for this contestant have been certified and cannot be edited.</p>
	</div>
<?php endif; ?>

<?php if (is_organizer()): ?>
	<div class="admin-actions">
		<h4>Admin Actions</h4>
		<form method="post" action="<?= url('score/' . $subcategory['id'] . '/unsign') ?>" style="display: inline-block;">
			<input type="hidden" name="judge_id" value="<?= htmlspecialchars(current_user()['judge_id'] ?? '') ?>">
			<button type="submit" onclick="return confirm('Are you sure you want to unsign scores for this judge?')" class="btn btn-warning">
				Unsign Scores
			</button>
		</form>
	</div>
<?php endif; ?>

<?php if (empty($criteria)): ?>
	<div class="alert alert-warning">
		<p>No criteria have been set up for this subcategory yet.</p>
	</div>
<?php else: ?>
	<form method="post" action="<?= url('score/' . $subcategory['id'] . '/submit') ?>">
		<input type="hidden" name="contestant_id" value="<?= htmlspecialchars($contestant['id']) ?>">
		
		<div class="scoring-form">
			<?php foreach ($criteria as $criterion): ?>
				<div class="criterion-row">
					<label for="score_<?= htmlspecialchars($criterion['id']) ?>">
						<?= htmlspecialchars($criterion['name']) ?>
						<span class="max-score">(Max: <?= number_format($criterion['max_score']) ?>)</span>
					</label>
					<input type="number" 
						   id="score_<?= htmlspecialchars($criterion['id']) ?>" 
						   name="scores[<?= htmlspecialchars($criterion['id']) ?>]" 
						   value="<?= htmlspecialchars($existingScores[$criterion['id']] ?? '') ?>"
						   min="0" 
						   max="<?= htmlspecialchars($criterion['max_score']) ?>" 
						   step="0.1"
						   <?= $isCertified && is_judge() ? 'readonly' : '' ?>
						   required>
				</div>
			<?php endforeach; ?>
			
			<?php if (!empty($subcategory['score_cap'])): ?>
				<div class="score-cap-info">
					<p><strong>Score Cap:</strong> <?= number_format($subcategory['score_cap']) ?> points</p>
				</div>
			<?php endif; ?>
			
			<div class="comment-section">
				<label for="comment">Comments (optional):</label>
				<textarea id="comment" 
						  name="comments[<?= htmlspecialchars($contestant['id']) ?>]" 
						  rows="3" 
						  placeholder="Optional comment for this contestant"
						  <?= $isCertified && is_judge() ? 'readonly' : '' ?>><?= htmlspecialchars($existingComment ?? '') ?></textarea>
			</div>
			
			<?php if (is_judge() && !$isCertified): ?>
				<div class="signature-section">
					<label for="signature_name">Signature (for certification):</label>
					<input type="text" 
						   id="signature_name" 
						   name="signature_name" 
						   value="<?= htmlspecialchars(current_user()['preferred_name'] ?? current_user()['name']) ?>"
						   placeholder="Enter your name to certify scores"
						   required>
					<small>Enter your preferred name to certify these scores</small>
				</div>
			<?php endif; ?>
			
			<div class="form-actions">
				<?php if (!$isCertified || is_organizer()): ?>
					<button type="submit" class="btn btn-primary">
						<?= is_judge() ? 'Submit & Certify Scores' : 'Update Scores' ?>
					</button>
				<?php endif; ?>
				
				<a href="<?= url('judge/subcategory/' . $subcategory['id']) ?>" class="btn btn-secondary">
					Back to Contestants
				</a>
			</div>
		</div>
	</form>
<?php endif; ?>

<style>
.navigation-buttons {
	margin-bottom: 20px;
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
}

.btn-outline {
	background: transparent;
	color: var(--accent-color);
	border: 1px solid var(--accent-color);
}

.btn-outline:hover {
	background: var(--accent-color);
	color: white;
	text-decoration: none;
}

.scoring-header {
	background: #f8f9fa;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	padding: 20px;
	margin: 20px 0;
}

.scoring-header h3 {
	margin: 0 0 15px 0;
	color: #007bff;
	font-size: 1.3em;
}

.contestant-info h4 {
	margin: 0 0 5px 0;
	color: #333;
	font-size: 1.2em;
}

.contestant-number {
	font-weight: bold;
	color: #007bff;
	margin: 5px 0;
}

.contestant-bio {
	color: #666;
	font-style: italic;
	margin: 10px 0;
	line-height: 1.4;
}

.admin-actions {
	background: #fff3cd;
	border: 1px solid #ffeaa7;
	border-radius: 8px;
	padding: 15px;
	margin: 20px 0;
}

.scoring-form {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	padding: 20px;
	margin: 20px 0;
}

.criterion-row {
	margin-bottom: 20px;
	padding-bottom: 15px;
	border-bottom: 1px solid #f8f9fa;
}

.criterion-row:last-child {
	border-bottom: none;
}

.criterion-row label {
	display: block;
	margin-bottom: 5px;
	font-weight: bold;
	color: #333;
}

.max-score {
	font-weight: normal;
	color: #666;
	font-size: 0.9em;
}

.criterion-row input {
	width: 100%;
	max-width: 200px;
	padding: 8px 12px;
	border: 1px solid #ddd;
	border-radius: 4px;
	font-size: 16px;
}

.score-cap-info {
	background: #e9ecef;
	border-radius: 4px;
	padding: 10px;
	margin: 20px 0;
	text-align: center;
}

.comment-section {
	margin-top: 20px;
	padding: 15px;
	background: var(--bg-secondary);
	border: 1px solid var(--border-color);
	border-radius: 8px;
}

.comment-section label {
	display: block;
	margin-bottom: 8px;
	font-weight: bold;
	color: var(--text-primary);
}

.comment-section textarea {
	width: 100%;
	padding: 10px;
	border: 1px solid var(--border-color);
	border-radius: 4px;
	background: var(--bg-primary);
	color: var(--text-primary);
	resize: vertical;
	min-height: 80px;
}

.comment-section textarea:focus {
	border-color: var(--accent-color);
	box-shadow: 0 0 0 1px rgba(0,123,255,0.25);
	outline: none;
}

.signature-section {
	background: #f8f9fa;
	border-radius: 4px;
	padding: 15px;
	margin: 20px 0;
}

.signature-section label {
	display: block;
	margin-bottom: 5px;
	font-weight: bold;
}

.signature-section input {
	width: 100%;
	max-width: 300px;
	padding: 8px 12px;
	border: 1px solid #ddd;
	border-radius: 4px;
	margin-bottom: 5px;
}

.signature-section small {
	color: #666;
	font-size: 0.9em;
}

.form-actions {
	margin-top: 30px;
	padding-top: 20px;
	border-top: 1px solid #dee2e6;
	display: flex;
	gap: 15px;
	flex-wrap: wrap;
}

.btn {
	display: inline-block;
	padding: 10px 20px;
	text-decoration: none;
	border-radius: 4px;
	font-weight: bold;
	text-align: center;
	transition: background-color 0.2s;
	border: none;
	cursor: pointer;
}

.btn-primary {
	background: #007bff;
	color: white;
}

.btn-primary:hover {
	background: #0056b3;
}

.btn-secondary {
	background: #6c757d;
	color: white;
}

.btn-secondary:hover {
	background: #545b62;
}

.btn-warning {
	background: #ffc107;
	color: #212529;
}

.btn-warning:hover {
	background: #d39e00;
}

.alert {
	padding: 15px 20px;
	margin: 20px 0;
	border-radius: 4px;
	border: 1px solid transparent;
}

.alert-danger {
	background: #f8d7da;
	border-color: #f5c6cb;
	color: #721c24;
}

.alert-success {
	background: #d4edda;
	border-color: #c3e6cb;
	color: #155724;
}

.alert-info {
	background: #d1ecf1;
	border-color: #bee5eb;
	color: #0c5460;
}

.alert-warning {
	background: #fff3cd;
	border-color: #ffeaa7;
	color: #856404;
}

@media (max-width: 768px) {
	.form-actions {
		flex-direction: column;
	}
	
	.btn {
		width: 100%;
	}
}
</style>
