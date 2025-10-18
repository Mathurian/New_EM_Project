<?php use function App\{url}; ?>
<h2>Edit User: <?= htmlspecialchars($user['name']) ?></h2>
<p><a href="<?= url('admin/users') ?>">Back to User Management</a></p>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'invalid_role' => 'Invalid role selected',
		'email_exists' => 'Email already exists',
		'preferred_name_exists' => 'Preferred name already exists',
		'database_error' => 'Database error occurred'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<p style="color: red; font-weight: bold;"><?= htmlspecialchars($errorMessage) ?></p>
<?php endif; ?>

<form method="post" action="<?= url('admin/users/' . urlencode($user['id']) . '/update') ?>">
	<div class="form-section">
		<h4>Basic Information</h4>
		<div class="form-table">
			<div class="form-row">
				<label class="form-label">Full Name</label>
				<div class="form-input">
					<input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required />
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Preferred Name (optional)</label>
				<div class="form-input">
					<input type="text" name="preferred_name" value="<?= htmlspecialchars($user['preferred_name'] ?? '') ?>" placeholder="Leave blank to use full name" />
					<small>This can be used for login and signatures</small>
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Email Address (optional)</label>
				<div class="form-input">
					<input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Leave blank if no email needed" />
					<small>Required for organizers and tally masters, optional for others</small>
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">New Password (optional)</label>
				<div class="form-input">
					<input type="password" name="password" placeholder="Leave blank to keep current password" />
					<small>Only enter if you want to change the password</small>
				</div>
			</div>
		</div>
	</div>

	<div class="form-section">
		<h4>Role & Details</h4>
		<div class="form-table">
			<div class="form-row">
				<label class="form-label">Role</label>
				<div class="form-input">
					<select name="role" required>
						<option value="organizer" <?= $user['role'] === 'organizer' ? 'selected' : '' ?>>Organizer</option>
						<option value="judge" <?= $user['role'] === 'judge' ? 'selected' : '' ?>>Judge</option>
						<option value="emcee" <?= $user['role'] === 'emcee' ? 'selected' : '' ?>>Emcee</option>
						<option value="tally_master" <?= $user['role'] === 'tally_master' ? 'selected' : '' ?>>Tally Master</option>
						<option value="contestant" <?= $user['role'] === 'contestant' ? 'selected' : '' ?>>Contestant</option>
					</select>
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Gender (optional)</label>
				<div class="form-input">
					<input type="text" name="gender" value="<?= htmlspecialchars($user['gender'] ?? '') ?>" placeholder="Enter custom gender or leave blank" />
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Pronouns (optional)</label>
				<div class="form-input">
					<input type="text" name="pronouns" value="<?= htmlspecialchars($user['pronouns'] ?? '') ?>" placeholder="e.g., they/them, she/her, he/him, ze/zir, or custom" />
					<small>How you would like to be referred to</small>
				</div>
			</div>
		</div>
	</div>

	<div class="form-section">
		<div class="form-table">
			<div class="form-row">
				<div class="form-label"></div>
				<div class="form-input">
					<button type="submit" class="btn btn-primary">Update User</button>
					<a href="<?= url('admin/users') ?>" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
				</div>
			</div>
		</div>
	</div>
</form>

<div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
	<h4>User Information</h4>
	<p><strong>Current Login Status:</strong> <?= !empty($user['password_hash']) ? 'Can login' : 'Cannot login' ?></p>
	<p><strong>Created:</strong> User ID: <?= htmlspecialchars($user['id']) ?></p>
	<?php if (!empty($user['email'])): ?>
		<p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
	<?php endif; ?>
	<?php if (!empty($user['preferred_name'])): ?>
		<p><strong>Preferred Name:</strong> <?= htmlspecialchars($user['preferred_name']) ?></p>
	<?php endif; ?>
</div>
