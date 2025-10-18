<?php use function App\{url}; ?>
<h2>Contestants</h2>
<?php if (empty($rows)): ?>
	<p>No contestants available.</p>
<?php else: ?>
	<table style="width:100%; border-collapse: collapse;">
		<tr>
			<th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Number</th>
			<th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Name</th>
			<th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Actions</th>
		</tr>
		<?php foreach ($rows as $r): ?>
			<tr>
				<td style="padding:8px; border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($r['contestant_number'] ?? '-') ?></td>
				<td style="padding:8px; border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($r['name']) ?></td>
				<td style="padding:8px; border-bottom:1px solid #f0f0f0;"><a class="btn btn-primary" href="<?= url('results/contestants/' . urlencode($r['id'])) ?>">View</a></td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>
