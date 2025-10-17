<h2>Edit Contestant</h2>
<p><a href="/people">Back</a></p>
<form method="post" action="/people/contestants/<?= urlencode($contestant['id']) ?>/update" enctype="multipart/form-data">
	<label>Name
		<input name="name" value="<?= htmlspecialchars($contestant['name']) ?>" required />
	</label>
	<label>Email
		<input type="email" name="email" value="<?= htmlspecialchars($contestant['email'] ?? '') ?>" />
	</label>
	<label>Contestant Number
		<input type="number" name="contestant_number" min="1" value="<?= htmlspecialchars($contestant['contestant_number'] ?? '') ?>" />
	</label>
	<label>Gender
		<input type="text" name="gender" value="<?= htmlspecialchars($contestant['gender'] ?? '') ?>" placeholder="Enter custom gender or leave blank" />
	</label>
	<label>Pronouns (optional)
		<input type="text" name="pronouns" value="<?= htmlspecialchars($contestant['pronouns'] ?? '') ?>" placeholder="e.g., they/them, she/her, he/him, ze/zir, or custom" />
	</label>
	<label>Bio
		<textarea name="bio" rows="4" cols="50" placeholder="Tell us about yourself..."><?= htmlspecialchars($contestant['bio'] ?? '') ?></textarea>
	</label>
	<label>Profile Image
		<input type="file" name="image" accept="image/*" />
		<?php if (!empty($contestant['image_path'])): ?>
			<p>Current image: <img src="<?= $contestant['image_path'] ?>" alt="Current profile" style="max-width: 100px; max-height: 100px;" /></p>
		<?php endif; ?>
	</label>
	<button type="submit">Update Contestant</button>
</form>
