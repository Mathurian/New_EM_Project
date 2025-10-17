<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>Contestants</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="card">
	<h3>Add Contestant</h3>
	<div style="margin-bottom: 16px;">
		<button type="button" class="btn btn-primary" onclick="openUserCreationModal()">
			👤 Create Full User Account
		</button>
	</div>
	<p class="text-muted">Create a complete user account with login access for contestant management.</p>
</div>

<div class="card">
	<h3>Contestants List</h3>
	<?php if (empty($contestants)): ?>
		<p class="text-muted">No contestants found.</p>
	<?php else: ?>
		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Email</th>
						<th>Gender</th>
						<th>Number</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($contestants as $r): ?>
						<tr>
							<td><?= htmlspecialchars($r['name']) ?></td>
							<td><?= htmlspecialchars($r['email'] ?? '') ?></td>
							<td><?= htmlspecialchars($r['gender'] ?? '') ?></td>
							<td><?= htmlspecialchars($r['contestant_number'] ?? '—') ?></td>
							<td>
								<form method="post" action="/admin/contestants/delete" onsubmit="return confirm('Delete contestant?')" style="display:inline-block;">
									<input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>" />
									<button type="submit" class="btn btn-danger btn-sm">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
