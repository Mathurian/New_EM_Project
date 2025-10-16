<?php use function App\{url, is_organizer}; ?>
<h2>Archived Contests</h2>
<p><a href="<?= url('contests') ?>">Back to Active Contests</a></p>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'contest_not_found' => 'Archived contest not found',
		'reactivation_failed' => 'Failed to reactivate contest. Please try again.'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<p style="color: red; font-weight: bold;"><?= htmlspecialchars($errorMessage) ?></p>
<?php endif; ?>

<?php if (empty($rows)): ?>
	<p>No archived contests found.</p>
<?php else: ?>
	<div style="margin-bottom: 20px; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px;">
		<p><strong>Archived Contests:</strong> These contests have been archived and are no longer active. All contest data has been preserved for historical reference.</p>
	</div>

	<table style="width: 100%; border-collapse: collapse;">
		<tr style="background: #f8f9fa;">
			<th style="border: 1px solid #dee2e6; padding: 8px;">Contest Name</th>
			<th style="border: 1px solid #dee2e6; padding: 8px;">Start Date</th>
			<th style="border: 1px solid #dee2e6; padding: 8px;">End Date</th>
			<th style="border: 1px solid #dee2e6; padding: 8px;">Archived By</th>
			<th style="border: 1px solid #dee2e6; padding: 8px;">Archived Date</th>
			<th style="border: 1px solid #dee2e6; padding: 8px;">Actions</th>
		</tr>
		<?php foreach ($rows as $contest): ?>
			<tr>
				<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars($contest['name']) ?></td>
				<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars($contest['start_date']) ?></td>
				<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars($contest['end_date']) ?></td>
				<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars($contest['archived_by']) ?></td>
				<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars($contest['archived_at']) ?></td>
				<td style="border: 1px solid #dee2e6; padding: 8px;">
					<a href="<?= url('admin/archived-contest/' . urlencode($contest['id'])) ?>">View Details</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>
