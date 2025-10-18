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
	<?= App\csrf_field() ?>
	<div class="form-section">
		<h4>Basic Information</h4>
		<div class="form-table">
			<div class="form-row">
				<label class="form-label">Full Name</label>
				<div class="form-input">
					<input name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required />
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
				<label class="form-label">Email Address</label>
				<div class="form-input">
					<input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required />
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

	<?php if (($user['role'] ?? '') === 'judge' && !empty($judge)): ?>
		<div class="form-section">
			<h4>Judge Bio Information</h4>
			<div class="form-table">
				<div class="form-row">
					<label class="form-label">Bio</label>
					<div class="form-input">
						<textarea name="bio" rows="6" cols="60" placeholder="Tell us about yourself, your experience, and what makes you a great judge..."><?= htmlspecialchars($judge['bio'] ?? '') ?></textarea>
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Profile Image</label>
					<div class="form-input">
						<input type="file" name="image" accept="image/*" />
						<?php if (!empty($judge['image_path'])): ?>
							<p>Current image: <img src="<?= url($judge['image_path']) ?>" alt="Current profile" style="max-width: 100px; max-height: 100px;" /></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="form-section">
		<h4>Security</h4>
		<div class="form-table">
			<div class="form-row">
				<label class="form-label">New Password (leave blank to keep current)</label>
				<div class="form-input">
					<input type="password" name="password" />
				</div>
			</div>
		</div>
	</div>

	<div class="form-section">
		<h4>Display Preferences</h4>
		<div class="form-table">
			<div class="form-row">
				<label class="form-label">Theme</label>
				<div class="form-input">
					<select id="themeSelect">
						<option value="light">Light</option>
						<option value="dark">Dark</option>
					</select>
					<small>Your preference is saved in your browser.</small>
				</div>
			</div>
		</div>
	</div>

	<div class="form-section">
		<div class="form-table">
			<div class="form-row">
				<div class="form-label"></div>
				<div class="form-input">
					<button type="submit" class="btn btn-primary">Save</button>
				</div>
			</div>
		</div>
	</div>
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
