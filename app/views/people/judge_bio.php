<h2>Judge Bio: <?= htmlspecialchars($judge['name']) ?></h2>
<p><a href="/people">Back to People</a></p>

<div class="judge-bio">
	<?php if (!empty($judge['image_path'])): ?>
		<div class="judge-image">
			<img src="<?= $judge['image_path'] ?>" alt="<?= htmlspecialchars($judge['name']) ?>" style="max-width: 300px; max-height: 300px; border-radius: 8px;" />
		</div>
	<?php endif; ?>
	
	<div class="judge-info">
		<h3><?= htmlspecialchars($judge['name']) ?></h3>
		
		<?php if (!empty($judge['email'])): ?>
			<p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($judge['email']) ?>"><?= htmlspecialchars($judge['email']) ?></a></p>
		<?php endif; ?>
		
		<?php if (!empty($judge['gender'])): ?>
			<p><strong>Gender:</strong> <?= htmlspecialchars($judge['gender']) ?></p>
		<?php endif; ?>
		
		<?php if (!empty($judge['bio'])): ?>
			<div class="bio-section">
				<h4>About</h4>
				<div class="bio-text"><?= nl2br(htmlspecialchars($judge['bio'])) ?></div>
			</div>
		<?php endif; ?>
	</div>
</div>

<style>
.judge-bio {
	display: flex;
	gap: 20px;
	margin: 20px 0;
}

.judge-image {
	flex-shrink: 0;
}

.judge-info {
	flex: 1;
}

.bio-section {
	margin-top: 20px;
}

.bio-text {
	background: #f5f5f5;
	padding: 15px;
	border-radius: 5px;
	line-height: 1.6;
}

@media (max-width: 768px) {
	.judge-bio {
		flex-direction: column;
	}
}
</style>
