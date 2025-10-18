<?php use function App\{url, is_organizer}; ?>
<h2>Contests</h2>
<p><a href="<?= url() ?>">Back</a></p>

<?php if (!empty($_GET['success'])): ?>
	<?php 
	$successMessages = [
		'contest_archived' => 'Contest archived successfully!',
		'contest_reactivated' => 'Contest reactivated successfully!'
	];
	$successMessage = $successMessages[$_GET['success']] ?? 'Operation completed successfully!';
	?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'contest_not_found' => 'Contest not found',
		'archive_failed' => 'Failed to archive contest. Please try again.',
		'reactivation_failed' => 'Failed to reactivate contest. Please try again.'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<p style="color: red; font-weight: bold;"><?= htmlspecialchars($errorMessage) ?></p>
<?php endif; ?>

<div style="margin-bottom: 20px;">
	<a href="<?= url('contests/new') ?>">+ New Contest</a>
	<?php if (is_organizer()): ?>
		| <a href="<?= url('admin/archived-contests') ?>">View Archived Contests</a>
	<?php endif; ?>
</div>

<table style="width: 100%; border-collapse: collapse;">
	<tr style="background: #f8f9fa;">
		<th style="border: 1px solid #dee2e6; padding: 8px;">Name</th>
		<th style="border: 1px solid #dee2e6; padding: 8px;">Start Date</th>
		<th style="border: 1px solid #dee2e6; padding: 8px;">End Date</th>
		<th style="border: 1px solid #dee2e6; padding: 8px;">Actions</th>
	</tr>
	<?php foreach ($rows as $c): ?>
		<tr>
			<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars($c['name']) ?></td>
			<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars($c['start_date']) ?></td>
			<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars($c['end_date']) ?></td>
			<td style="border: 1px solid #dee2e6; padding: 8px;">
				<a href="<?= url('contests/' . urlencode($c['id']) . '/categories') ?>">Categories</a> |
				<a href="<?= url('contests/' . urlencode($c['id']) . '/subcategories') ?>">Subcategories</a>
				<?php if (is_organizer()): ?>
					| <form method="post" action="<?= url('contests/' . urlencode($c['id']) . '/archive') ?>" style="display: inline-block;">
						<button type="submit" onclick="return confirm('Are you sure you want to archive this contest? This will move all contest data to the archive and remove it from active contests. This action cannot be undone.')" style="background: #6c757d; color: white; border: none; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
							Archive
						</button>
					</form>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>


