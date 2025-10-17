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
	<label>Full Name
		<input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required />
	</label>
	
	<label>Preferred Name (optional)
		<input type="text" name="preferred_name" value="<?= htmlspecialchars($user['preferred_name'] ?? '') ?>" placeholder="Leave blank to use full name" />
		<small>This can be used for login and signatures</small>
	</label>
	
	<label>Email Address (optional)
		<input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Leave blank if no email needed" />
		<small>Required for organizers, optional for others</small>
	</label>
	
	<label>New Password (optional)
		<input type="password" name="password" placeholder="Leave blank to keep current password" />
		<small>Only enter if you want to change the password</small>
	</label>
	
	<label>Role
		<select name="role" required>
			<option value="organizer" <?= $user['role'] === 'organizer' ? 'selected' : '' ?>>Organizer</option>
			<option value="judge" <?= $user['role'] === 'judge' ? 'selected' : '' ?>>Judge</option>
			<option value="emcee" <?= $user['role'] === 'emcee' ? 'selected' : '' ?>>Emcee</option>
			<option value="contestant" <?= $user['role'] === 'contestant' ? 'selected' : '' ?>>Contestant</option>
		</select>
	</label>
	
	<label>Gender (optional)
		<input type="text" name="gender" value="<?= htmlspecialchars($user['gender'] ?? '') ?>" placeholder="Enter custom gender or leave blank" />
	</label>
	
	<label>Pronouns (optional)
		<input type="text" name="pronouns" value="<?= htmlspecialchars($user['pronouns'] ?? '') ?>" placeholder="e.g., they/them, she/her, he/him, ze/zir, or custom" />
		<small>How you would like to be referred to</small>
	</label>
	
	<div style="margin-top: 20px;">
		<button type="submit">Update User</button>
		<a href="<?= url('admin/users') ?>" style="margin-left: 10px;">Cancel</a>
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
