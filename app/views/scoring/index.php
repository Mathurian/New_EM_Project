<?php use function App\{is_organizer, url}; use App\DB; ?>
<h2>Scoring: <?= htmlspecialchars($subcategory['category_name']) ?> - <?= htmlspecialchars($subcategory['name']) ?></h2>
<p><a href="<?= !empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'judge' ? url('judge') : url('categories/' . $subcategory['category_id'] . '/subcategories') ?>">Back</a></p>

<?php if (!empty($_SESSION['success_message'])): ?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
	<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'no_judge' => 'No judge specified for unsigning',
		'signature_mismatch' => 'Signature does not match your preferred name. Please enter your correct preferred name to certify scores.'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<p style="color: red; font-weight: bold;"><?= htmlspecialchars($errorMessage) ?></p>
<?php endif; ?>

<?php if (empty($contestants)): ?>
	<p style="color: red;">No contestants assigned to this subcategory.</p>
	<p><a href="<?= url('subcategories/' . urlencode($subcategory['id']) . '/assign') ?>">Assign contestants</a></p>
<?php elseif (empty($criteria)): ?>
	<p style="color: red;">No criteria defined for this subcategory.</p>
	<p><a href="<?= url('subcategories/' . urlencode($subcategory['id']) . '/criteria') ?>">Define criteria</a></p>
<?php else: ?>
	<p>Debug: Found <?= count($contestants) ?> contestants and <?= count($criteria) ?> criteria</p>
<?php if (!empty($locked) && $locked): ?>
	<p style="color: red; font-weight: bold;">This subcategory has been certified and locked for editing.</p>
<?php endif; ?>

<!-- Unsign buttons for organizers -->
<?php if (is_organizer() && !empty($judges)): ?>
	<div style="margin-bottom: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px;">
		<h3>Admin Actions</h3>
		<?php foreach ($judges as $judge): ?>
			<?php
			// Check if this judge has certified scores
			$stmt = DB::pdo()->prepare('SELECT 1 FROM judge_certifications WHERE subcategory_id = ? AND judge_id = ?');
			$stmt->execute([$subcategory['id'], $judge['id']]);
			$isCertified = $stmt->fetchColumn();
			?>
			<?php if ($isCertified): ?>
				<form method="post" action="<?= url('score/' . urlencode($subcategory['id']) . '/unsign') ?>" style="display: inline-block; margin-right: 10px;">
					<input type="hidden" name="judge_id" value="<?= htmlspecialchars($judge['id']) ?>" />
					<button type="submit" onclick="return confirm('Are you sure you want to unsign scores for <?= htmlspecialchars($judge['name']) ?>?')" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px;">
						Unsign <?= htmlspecialchars($judge['name']) ?>
					</button>
				</form>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<form method="post" action="<?= url('score/' . urlencode($subcategory['id']) . '/submit') ?>">
<?php if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'judge'): ?>
	<label>Judge
		<select name="judge_id" required>
			<option value="">Select Judge</option>
			<?php foreach ($judges as $j): ?>
				<option value="<?= htmlspecialchars($j['id']) ?>"><?= htmlspecialchars($j['name']) ?></option>
			<?php endforeach; ?>
		</select>
	</label>
<?php endif; ?>
	<table>
		<tr>
			<th>Contestant</th>
			<?php foreach ($criteria as $crit): ?>
				<th><?= htmlspecialchars($crit['name']) ?> (/<?= htmlspecialchars((string)$crit['max_score']) ?>)</th>
			<?php endforeach; ?>
		</tr>
		<?php foreach ($contestants as $con): ?>
			<tr>
				<td><?= htmlspecialchars($con['name']) ?></td>
				<?php foreach ($criteria as $crit): ?>
					<td>
						<?php 
						$cap = isset($subcategory['score_cap']) && $subcategory['score_cap'] !== null ? (float)$subcategory['score_cap'] : (float)$crit['max_score']; 
						$existingValue = '';
						foreach ($existingScores ?? [] as $score) {
							if ($score['contestant_id'] === $con['id'] && $score['criterion_id'] === $crit['id']) {
								$existingValue = $score['score'];
								break;
							}
						}
						?>
						<input type="number" name="scores[<?= htmlspecialchars($con['id']) ?>][<?= htmlspecialchars($crit['id']) ?>]" min="0" max="<?= htmlspecialchars((string)$cap) ?>" step="0.1" value="<?= htmlspecialchars($existingValue) ?>" <?= (!empty($locked) && $locked && !is_organizer()) ? 'disabled' : '' ?> />
					</td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</table>

	<h3>Comments</h3>
	<?php foreach ($contestants as $con): ?>
		<label><?= htmlspecialchars($con['name']) ?>
			<textarea name="comments[<?= htmlspecialchars($con['id']) ?>]" rows="2" cols="60" placeholder="Optional comment for this contestant" <?= (!empty($locked) && $locked && !is_organizer()) ? 'disabled' : '' ?>></textarea>
		</label>
	<?php endforeach; ?>
	<?php if (empty($locked) || !$locked || is_organizer()): ?>
		<button type="submit">Submit Scores</button>
	<?php endif; ?>

	<?php if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'judge' && (empty($locked) || !$locked)): ?>
		<h3>Certification</h3>
		<label>Your full name (signature)
			<input type="text" name="signature_name" required />
		</label>
		<p>By submitting, I certify these scores and comments are complete and accurate.</p>
	<?php endif; ?>
</form>
<?php endif; ?>

