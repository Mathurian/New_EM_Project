<h2>Edit Judge</h2>
<p><a href="/people">Back</a></p>
<form method="post" action="/people/judges/<?= urlencode($judge['id']) ?>/update" enctype="multipart/form-data">
	<label>Name
		<input name="name" value="<?= htmlspecialchars($judge['name']) ?>" required />
	</label>
	<label>Email
		<input type="email" name="email" value="<?= htmlspecialchars($judge['email'] ?? '') ?>" />
	</label>
	<label>Gender
		<input type="text" name="gender" value="<?= htmlspecialchars($judge['gender'] ?? '') ?>" placeholder="Enter custom gender or leave blank" />
	</label>
	<label>Pronouns (optional)
		<input type="text" name="pronouns" value="<?= htmlspecialchars($judge['pronouns'] ?? '') ?>" placeholder="e.g., they/them, she/her, he/him, ze/zir, or custom" />
	</label>
	<label>Bio
		<textarea name="bio" rows="4" cols="50" placeholder="Tell us about yourself..."><?= htmlspecialchars($judge['bio'] ?? '') ?></textarea>
	</label>
	<label>Profile Image
		<input type="file" name="image" accept="image/*" />
		<?php if (!empty($judge['image_path'])): ?>
			<p>Current image: <img src="<?= $judge['image_path'] ?>" alt="Current profile" style="max-width: 100px; max-height: 100px;" /></p>
		<?php endif; ?>
	</label>
	<button type="submit">Update Judge</button>
</form>
