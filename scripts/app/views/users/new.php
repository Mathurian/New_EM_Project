<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>Create New User</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'invalid_role' => 'Invalid role selected',
		'email_exists' => 'Email already exists',
		'preferred_name_exists' => 'Preferred name already exists',
		'constraint_failed' => 'Database constraint error. Please run the constraint fix script.',
		'database_error' => 'Database error occurred',
		'upload_failed' => 'Failed to upload image'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
	<?php if ($_GET['error'] === 'constraint_failed'): ?>
		<div class="alert alert-warning">
			<strong>Fix:</strong> Run <code>php fix_constraint.php</code> from the project root directory to update the database constraint.
		</div>
	<?php endif; ?>
<?php endif; ?>

<form method="post" action="<?= url('users') ?>" enctype="multipart/form-data" id="userForm">
	<label>Full Name
		<input type="text" name="name" required />
	</label>
	
	<label>Preferred Name (optional)
		<input type="text" name="preferred_name" placeholder="Leave blank to use full name" />
		<small>This can be used for login and signatures</small>
	</label>
	
	<label>Email Address (optional)
		<input type="email" name="email" placeholder="Leave blank if no email needed" />
		<small>Required for organizers, optional for others</small>
	</label>
	
	<label>Password (optional)
		<input type="password" name="password" placeholder="Leave blank if no login needed" />
		<div class="alert alert-info" style="margin-top:6px;">
			Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.
		</div>
		<small>Required for organizers, optional for others</small>
	</label>
	
	<label>Role
		<select name="role" required id="roleSelect">
			<option value="">Select a role</option>
			<option value="organizer">Organizer</option>
			<option value="judge">Judge</option>
			<option value="emcee">Emcee</option>
			<option value="contestant">Contestant</option>
		</select>
	</label>
	
	<label>Gender (optional)
		<input type="text" name="gender" placeholder="Enter custom gender or leave blank" />
	</label>
	
	<!-- Contestant-specific fields -->
	<div id="contestantFields" style="display: none;">
		<fieldset>
			<legend>Contestant Information</legend>
			
			<label>Contestant Number (optional)
				<input type="number" name="contestant_number" min="1" placeholder="Leave blank for auto-assignment" />
				<small>Will be automatically assigned if not provided</small>
			</label>
			
			<label>Bio
				<textarea name="bio" rows="4" cols="50" placeholder="Tell us about yourself, your background, and what makes you unique..."></textarea>
			</label>
			
			<label>Profile Image
				<input type="file" name="image" accept="image/*" />
				<small>Upload a professional photo for your contestant profile</small>
			</label>
			
			<label>Assign to Category (optional)
				<select name="category_id">
					<option value="">No category assignment</option>
					<?php foreach ($categories as $category): ?>
						<option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['contest_name']) ?> - <?= htmlspecialchars($category['name']) ?></option>
					<?php endforeach; ?>
				</select>
				<small>Assign this contestant to a specific category</small>
			</label>
		</fieldset>
	</div>
	
	<!-- Judge-specific fields -->
	<div id="judgeFields" style="display: none;">
		<fieldset>
			<legend>Judge Information</legend>
			
			<label>Bio
				<textarea name="bio" rows="4" cols="50" placeholder="Tell us about your experience, qualifications, and what makes you a great judge..."></textarea>
			</label>
			
			<label>Profile Image
				<input type="file" name="image" accept="image/*" />
				<small>Upload a professional photo for your judge profile</small>
			</label>
			
			<label>Assign to Category (optional)
				<select name="category_id">
					<option value="">No category assignment</option>
					<?php foreach ($categories as $category): ?>
						<option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['contest_name']) ?> - <?= htmlspecialchars($category['name']) ?></option>
					<?php endforeach; ?>
				</select>
				<small>Assign this judge to a specific category</small>
			</label>
			<label><input type="checkbox" name="is_head_judge" value="1" /> Head Judge</label>
		</fieldset>
	</div>
	
	<button type="submit">Create User</button>
</form>

<script>
document.getElementById('roleSelect').addEventListener('change', function() {
	const contestantFields = document.getElementById('contestantFields');
	const judgeFields = document.getElementById('judgeFields');
	
	// Hide all role-specific fields first
	contestantFields.style.display = 'none';
	judgeFields.style.display = 'none';
	
	// Show appropriate fields based on role
	if (this.value === 'contestant') {
		contestantFields.style.display = 'block';
	} else if (this.value === 'judge') {
		judgeFields.style.display = 'block';
	}
});
</script>
