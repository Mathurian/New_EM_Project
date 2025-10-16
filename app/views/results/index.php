<?php use function App\{url, is_organizer, hierarchical_back_url, home_url}; ?>
<?php if (!$subcategory): ?>
	<h2>Subcategory Not Found</h2>
	<p>The requested subcategory could not be found.</p>
	<div class="navigation-buttons">
		<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
		<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
	</div>
<?php else: ?>
	<h2>Results: <?= htmlspecialchars($subcategory['category_name']) ?> - <?= htmlspecialchars($subcategory['name']) ?></h2>
	<div class="navigation-buttons">
		<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
		<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
	</div>

	<?php if (!empty($_SESSION['success_message'])): ?>
		<p style="color: green; font-weight: bold;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
		<?php unset($_SESSION['success_message']); ?>
	<?php endif; ?>

	<?php if (is_organizer()): ?>
		<div class="warning-box">
			<h3>Admin Actions</h3>
			<form method="post" action="<?= url('results/' . urlencode($subcategory['id']) . '/unsign-all') ?>" style="display: inline-block;">
				<button type="submit" onclick="return confirm('Are you sure you want to unsign ALL scores for this subcategory? This will unlock all certified scores for editing.')" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px;">
					Unsign All Scores for This Subcategory
				</button>
			</form>
		</div>
	<?php endif; ?>

	<?php if (empty($results)): ?>
		<p>No scores recorded for this subcategory yet.</p>
	<?php else: ?>
		<table>
			<tr><th>Rank</th><th>Contestant</th><th>Total</th></tr>
			<?php $i = 1; foreach ($results as $r): ?>
				<tr>
					<td><?= $i++ ?></td>
					<td><?= htmlspecialchars($r['contestantName']) ?></td>
					<td><?= number_format((float)$r['totalScore'], 2) ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
<?php endif; ?>


