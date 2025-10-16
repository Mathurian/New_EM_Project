<h2>Contestant Bio: <?= htmlspecialchars($contestant['name']) ?></h2>
<p><a href="/emcee">Back to Contestant List</a></p>

<div class="contestant-bio">
	<?php if (!empty($contestant['image_path'])): ?>
		<div class="contestant-image">
			<img src="<?= $contestant['image_path'] ?>" alt="<?= htmlspecialchars($contestant['name']) ?>" style="max-width: 400px; max-height: 400px; border-radius: 8px;" />
		</div>
	<?php endif; ?>
	
	<div class="contestant-info">
		<h3><?= htmlspecialchars($contestant['name']) ?></h3>
		
		<?php if (!empty($contestant['contestant_number'])): ?>
			<p><strong>Contestant Number:</strong> <?= htmlspecialchars($contestant['contestant_number']) ?></p>
		<?php endif; ?>
		
		<?php if (!empty($contestant['email'])): ?>
			<p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($contestant['email']) ?>"><?= htmlspecialchars($contestant['email']) ?></a></p>
		<?php endif; ?>
		
		<?php if (!empty($contestant['gender'])): ?>
			<p><strong>Gender:</strong> <?= htmlspecialchars($contestant['gender']) ?></p>
		<?php endif; ?>
		
		<?php if (!empty($contestant['bio'])): ?>
			<div class="bio-section">
				<h4>About</h4>
				<div class="bio-text"><?= nl2br(htmlspecialchars($contestant['bio'])) ?></div>
			</div>
		<?php endif; ?>
	</div>
</div>

<style>
.contestant-bio {
	display: flex;
	gap: 30px;
	margin: 20px 0;
}

.contestant-image {
	flex-shrink: 0;
}

.contestant-info {
	flex: 1;
}

.bio-section {
	margin-top: 20px;
}

.bio-text {
	background: #f5f5f5;
	padding: 20px;
	border-radius: 8px;
	line-height: 1.6;
	font-size: 16px;
}

@media (max-width: 768px) {
	.contestant-bio {
		flex-direction: column;
	}
}
</style>
