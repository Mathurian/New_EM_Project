<?php use function App\{url}; ?>
<h2>Edit My Bio</h2>
<p><a href="<?= url('judge') ?>">Back to My Assignments</a></p>

<?php if (!empty($_SESSION['success_message'])): ?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
	<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error_message'])): ?>
	<p style="color: red; font-weight: bold;"><?= htmlspecialchars($_SESSION['error_message']) ?></p>
	<?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<form method="post" action="<?= url('judge/bio') ?>" enctype="multipart/form-data">
	<label>Name
		<input name="name" value="<?= htmlspecialchars($judge['name']) ?>" required />
	</label>
	
	<label>Email
		<input type="email" name="email" value="<?= htmlspecialchars($judge['email'] ?? '') ?>" />
	</label>
	
	<label>Gender
		<input type="text" name="gender" value="<?= htmlspecialchars($judge['gender'] ?? '') ?>" placeholder="Enter custom gender or leave blank" />
	</label>
	
	<label>Bio
		<textarea name="bio" rows="6" cols="60" placeholder="Tell us about yourself, your experience, and what makes you a great judge..."><?= htmlspecialchars($judge['bio'] ?? '') ?></textarea>
	</label>
	
	<label>Profile Image
		<input type="file" name="image" accept="image/*" />
		<?php if (!empty($judge['image_path'])): ?>
			<p>Current image: <img src="<?= url($judge['image_path']) ?>" alt="Current profile" style="max-width: 100px; max-height: 100px;" /></p>
		<?php endif; ?>
	</label>
	
	<button type="submit">Update My Bio</button>
</form>

<div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
	<h4>Bio Information</h4>
	<p><strong>Note:</strong> Your bio will be visible to emcees and organizers. This helps them introduce you properly during the contest.</p>
	<p><strong>Image:</strong> Upload a professional photo that represents you well.</p>
	<p><strong>Bio:</strong> Share your background, experience, and what makes you qualified to judge this contest.</p>
</div>
