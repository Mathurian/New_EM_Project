<h2>Category Assignments: <?= htmlspecialchars($category['name']) ?></h2>
<p><a href="/categories/<?= urlencode($category['id']) ?>/subcategories">Back</a></p>

<?php if (!empty($_SESSION['success_message'])): ?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
	<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
<form method="post" action="/categories/<?= urlencode($category['id']) ?>/assign">
	<h3>Contestants</h3>
	<?php foreach ($contestants as $contestant): ?>
		<label>
			<input type="checkbox" name="contestants[]" value="<?= htmlspecialchars($contestant['id']) ?>" 
				<?= in_array($contestant['id'], $assignedContestants) ? 'checked' : '' ?> />
			<?= htmlspecialchars($contestant['name']) ?>
		</label><br>
	<?php endforeach; ?>
	
	<h3>Judges</h3>
	<?php foreach ($judges as $judge): ?>
		<label>
			<input type="checkbox" name="judges[]" value="<?= htmlspecialchars($judge['id']) ?>" 
				<?= in_array($judge['id'], $assignedJudges) ? 'checked' : '' ?> />
			<?= htmlspecialchars($judge['name']) ?>
		</label><br>
	<?php endforeach; ?>
	
	<button type="submit">Update Assignments</button>
</form>
