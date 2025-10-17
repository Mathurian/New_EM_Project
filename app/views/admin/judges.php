<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>Judges</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="card">
	<h3>Add Judge</h3>
	<div style="display: flex; gap: 12px; margin-bottom: 16px;">
		<button type="button" class="btn btn-primary" onclick="openUserCreationModal()">
			👤 Create Full User Account
		</button>
		<button type="button" class="btn btn-secondary" onclick="toggleQuickAdd()">
			⚖️ Quick Add Judge
		</button>
	</div>
	<p class="text-muted">Use "Create Full User Account" for complete user management with login access.</p>
</div>

<!-- Quick Add Form -->
<div id="quick-add-form" class="card" style="display: none;">
	<h4>Quick Add Judge</h4>
	<form method="post" action="/admin/judges">
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
			<label>Head Judge
				<input type="checkbox" name="is_head_judge" value="1" />
			</label>
		</div>
		<div class="form-row">
			<label>Create Login Account
				<input type="checkbox" name="create_user" value="1" />
			</label>
			<label>Password
				<input type="password" name="password" placeholder="Required if creating login" />
			</label>
		</div>
		<div style="margin-top: 12px; display: flex; gap: 10px;">
			<button type="submit" class="btn btn-primary">Add Judge</button>
			<button type="button" class="btn btn-secondary" onclick="toggleQuickAdd()">Cancel</button>
		</div>
	</form>
</div>

<div class="card">
	<h3>Judges List</h3>
	<?php if (empty($rows)): ?>
		<p class="text-muted">No judges found.</p>
	<?php else: ?>
		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Email</th>
						<th>Gender</th>
						<th>Head Judge</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($rows as $r): ?>
						<tr>
							<td><?= htmlspecialchars($r['name']) ?></td>
							<td><?= htmlspecialchars($r['email'] ?? '') ?></td>
							<td><?= htmlspecialchars($r['gender'] ?? '') ?></td>
							<td><?= !empty($r['is_head_judge']) ? 'Yes' : 'No' ?></td>
							<td>
								<form method="post" action="/admin/judges/<?= htmlspecialchars($r['id']) ?>/update" style="display:inline-block; margin-right:8px;">
									<input type="hidden" name="name" value="<?= htmlspecialchars($r['name']) ?>" />
									<input type="hidden" name="email" value="<?= htmlspecialchars($r['email'] ?? '') ?>" />
									<input type="hidden" name="gender" value="<?= htmlspecialchars($r['gender'] ?? '') ?>" />
									<input type="hidden" name="is_head_judge" value="<?= !empty($r['is_head_judge']) ? '0' : '1' ?>" />
									<button type="submit" class="btn btn-warning btn-sm"><?= !empty($r['is_head_judge']) ? 'Unset Head' : 'Set Head' ?></button>
								</form>
								<form method="post" action="/admin/judges/delete" onsubmit="return confirm('Delete judge?')" style="display:inline-block;">
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


