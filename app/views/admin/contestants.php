<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>Contestants</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="card">
	<h3>Add Contestant</h3>
	<div style="display: flex; gap: 12px; margin-bottom: 16px;">
		<button type="button" class="btn btn-primary" onclick="openUserCreationModal()">
			👤 Create Full User Account
		</button>
		<button type="button" class="btn btn-secondary" onclick="toggleQuickAdd()">
			🏆 Quick Add Contestant
		</button>
	</div>
	<p class="text-muted">Use "Create Full User Account" for complete user management with login access.</p>
</div>

<!-- Quick Add Form -->
<div id="quick-add-form" class="card" style="display: none;">
	<h4>Quick Add Contestant</h4>
	<form method="post" action="/admin/contestants">
		<div class="form-row">
			<label>Name
				<input name="name" required />
			</label>
			<label>Email
				<input type="email" name="email" />
			</label>
		</div>
		<div class="form-row">
			<label>Gender
				<select name="gender">
					<option value="">—</option>
					<option>Female</option>
					<option>Male</option>
					<option>Non-binary</option>
					<option>Prefer not to say</option>
				</select>
			</label>
			<label>Contestant Number
				<input type="number" name="contestant_number" min="1" placeholder="Auto-assigned if blank" />
			</label>
		</div>
		<div style="margin-top: 12px; display: flex; gap: 10px;">
			<button type="submit" class="btn btn-primary">Add Contestant</button>
			<button type="button" class="btn btn-secondary" onclick="toggleQuickAdd()">Cancel</button>
		</div>
	</form>
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

<style>
.form-row {
	display: flex;
	gap: 12px;
	margin-bottom: 12px;
}

.form-row label {
	flex: 1;
}

@media (max-width: 768px) {
	.form-row {
		flex-direction: column;
		gap: 8px;
	}
}
</style>

<script>
function toggleQuickAdd() {
	const form = document.getElementById('quick-add-form');
	if (form) {
		form.style.display = form.style.display === 'none' ? 'block' : 'none';
	}
}
</script>


