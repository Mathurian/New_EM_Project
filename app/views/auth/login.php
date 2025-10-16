<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center;">
	<div style="width: 100%; max-width: 380px; padding: 24px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-primary); box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
		<h2 style="text-align:center; margin-top:0;">Login</h2>
		<?php if (!empty($error)): ?>
			<p style="color:red; text-align:center; margin: 8px 0 16px;"><?= htmlspecialchars($error) ?></p>
		<?php endif; ?>
		<form method="post" action="/login" style="display:block;">
			<label style="display:block; margin-bottom:12px;">Email or Preferred Name
				<input type="text" name="email" required style="width:100%; padding:10px 12px; border:1px solid var(--border-color); border-radius:4px;" />
			</label>
			<label style="display:block; margin-bottom:16px;">Password
				<input type="password" name="password" required style="width:100%; padding:10px 12px; border:1px solid var(--border-color); border-radius:4px;" />
			</label>
			<button type="submit" class="btn btn-primary" style="width:100%;">Login</button>
		</form>
	</div>
</div>
