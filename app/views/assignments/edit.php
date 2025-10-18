<h2>Assignments for <?= htmlspecialchars($subcategory['category_name']) ?> - <?= htmlspecialchars($subcategory['name']) ?></h2>
<p><a href="/categories/<?= urlencode($subcategory['category_id'] ?? '') ?>/subcategories">Back</a></p>

<?php if (!empty($_SESSION['success_message'])): ?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
	<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
<form method="post" action="/subcategories/<?= urlencode($subcategory['id']) ?>/assign">
	<?= App\csrf_field() ?>
	<div class="two-col">
		<div>
			<h3>Contestants</h3>
			<?php foreach ($contestants as $c): ?>
				<label>
					<input type="checkbox" name="contestants[]" value="<?= htmlspecialchars($c['id']) ?>" <?= in_array($c['id'], $assignedContestants) ? 'checked' : '' ?> />
					<?= htmlspecialchars($c['name']) ?>
				</label><br/>
			<?php endforeach; ?>
		</div>
		<div>
			<h3>Judges</h3>
			<?php foreach ($judges as $j): ?>
				<label>
					<input type="checkbox" name="judges[]" value="<?= htmlspecialchars($j['id']) ?>" <?= in_array($j['id'], $assignedJudges) ? 'checked' : '' ?> />
					<?= htmlspecialchars($j['name']) ?>
				</label><br/>
			<?php endforeach; ?>
		</div>
	</div>
	<button type="submit">Save</button>
</form>


