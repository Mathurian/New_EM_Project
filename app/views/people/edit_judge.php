<h2>Edit Judge</h2>
<p><a href="/people">Back</a></p>
<form method="post" action="/people/judges/<?= urlencode($judge['id']) ?>/update" enctype="multipart/form-data">
	<div class="form-section">
		<h4>Basic Information</h4>
		<div class="form-table">
			<div class="form-row">
				<label class="form-label">Name</label>
				<div class="form-input">
					<input name="name" value="<?= htmlspecialchars($judge['name']) ?>" required />
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Email</label>
				<div class="form-input">
					<input type="email" name="email" value="<?= htmlspecialchars($judge['email'] ?? '') ?>" />
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Gender</label>
				<div class="form-input">
					<input type="text" name="gender" value="<?= htmlspecialchars($judge['gender'] ?? '') ?>" placeholder="Enter custom gender or leave blank" />
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Pronouns (optional)</label>
				<div class="form-input">
					<input type="text" name="pronouns" value="<?= htmlspecialchars($judge['pronouns'] ?? '') ?>" placeholder="e.g., they/them, she/her, he/him, ze/zir, or custom" />
				</div>
			</div>
		</div>
	</div>

	<div class="form-section">
		<h4>Profile Information</h4>
		<div class="form-table">
			<div class="form-row">
				<label class="form-label">Bio</label>
				<div class="form-input">
					<textarea name="bio" rows="4" cols="50" placeholder="Tell us about yourself..."><?= htmlspecialchars($judge['bio'] ?? '') ?></textarea>
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Profile Image</label>
				<div class="form-input">
					<input type="file" name="image" accept="image/*" />
					<?php if (!empty($judge['image_path'])): ?>
						<p>Current image: <img src="<?= $judge['image_path'] ?>" alt="Current profile" style="max-width: 100px; max-height: 100px;" /></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="form-section">
		<div class="form-table">
			<div class="form-row">
				<div class="form-label"></div>
				<div class="form-input">
					<button type="submit" class="btn btn-primary">Update Judge</button>
				</div>
			</div>
		</div>
	</div>
</form>
