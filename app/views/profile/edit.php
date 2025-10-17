<?php use function App\{url}; ?>
<h2>My Profile</h2>
<p><a href="<?= url() ?>">Back</a></p>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'email_exists' => 'Email already exists',
		'preferred_name_exists' => 'Preferred name already exists'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<p style="color: red; font-weight: bold;"><?= htmlspecialchars($errorMessage) ?></p>
<?php endif; ?>

<?php if (!empty($_GET['success'])): ?>
	<p style="color: green; font-weight: bold;">Profile updated successfully!</p>
<?php endif; ?>

<?php if (!empty($_SESSION['error_message'])): ?>
	<p style="color: red; font-weight: bold;"><?= htmlspecialchars($_SESSION['error_message']) ?></p>
	<?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<form method="post" action="<?= url('profile') ?>" enctype="multipart/form-data">
	<label>Full Name 
		<input name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required />
	</label>
	
	<label>Preferred Name (optional)
		<input type="text" name="preferred_name" value="<?= htmlspecialchars($user['preferred_name'] ?? '') ?>" placeholder="Leave blank to use full name" />
		<small>This can be used for login and signatures</small>
	</label>
	
	<label>Email Address 
		<input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required />
	</label>
	
	<label>Gender (optional)
		<input type="text" name="gender" value="<?= htmlspecialchars($user['gender'] ?? '') ?>" placeholder="Enter custom gender or leave blank" />
	</label>
	
	<label>Pronouns (optional)
		<input type="text" name="pronouns" value="<?= htmlspecialchars($user['pronouns'] ?? '') ?>" placeholder="e.g., they/them, she/her, he/him, ze/zir, or custom" />
		<small>How you would like to be referred to</small>
	</label>
	
	<?php if (($user['role'] ?? '') === 'judge' && !empty($judge)): ?>
		<fieldset>
			<legend>Judge Bio Information</legend>
			
			<label>Bio
				<textarea name="bio" rows="6" cols="60" placeholder="Tell us about yourself, your experience, and what makes you a great judge..."><?= htmlspecialchars($judge['bio'] ?? '') ?></textarea>
			</label>
			
			<label>Profile Image
				<input type="file" name="image" accept="image/*" />
				<?php if (!empty($judge['image_path'])): ?>
					<p>Current image: <img src="<?= url($judge['image_path']) ?>" alt="Current profile" style="max-width: 100px; max-height: 100px;" /></p>
				<?php endif; ?>
			</label>
		</fieldset>
	<?php endif; ?>
	
	<label>New Password (leave blank to keep current)
		<input type="password" name="password" />
	</label>

	<fieldset style="margin-top: 16px;">
		<legend>Display Preferences</legend>
		<label>Theme
			<select id="themeSelect">
				<option value="light">Light</option>
				<option value="dark">Dark</option>
			</select>
		</label>
		<small>Your preference is saved in your browser.</small>
	</fieldset>
	
	<button type="submit">Save</button>
</form>


<script>
document.addEventListener('DOMContentLoaded', function() {
	const select = document.getElementById('themeSelect');
	const saved = localStorage.getItem('theme') || 'light';
	select.value = saved;
	select.addEventListener('change', function() {
		document.body.setAttribute('data-theme', this.value);
		localStorage.setItem('theme', this.value);
	});
});
</script>
