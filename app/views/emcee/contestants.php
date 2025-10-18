<h2>Contestant Bios</h2>

<?php if (!empty($contestants)): ?>
<div class="contestants-section">
	<div class="contestant-grid">
		<?php foreach ($contestants as $contestant): ?>
			<div class="contestant-card">
				<?php if (!empty($contestant['image_path'])): ?>
					<div class="contestant-image">
						<img src="<?= $contestant['image_path'] ?>" alt="<?= htmlspecialchars($contestant['name']) ?>" />
					</div>
				<?php endif; ?>
				<div class="contestant-info">
					<h4><?= htmlspecialchars($contestant['name']) ?></h4>
					<?php if (!empty($contestant['contestant_number'])): ?>
						<p class="contestant-number">Contestant #<?= htmlspecialchars($contestant['contestant_number']) ?></p>
					<?php endif; ?>
					<?php if (!empty($contestant['bio'])): ?>
						<p class="bio-preview"><?= htmlspecialchars(substr($contestant['bio'], 0, 100)) ?><?= strlen($contestant['bio']) > 100 ? '...' : '' ?></p>
					<?php endif; ?>
					<?php if (!empty($contestant['contestant_number'])): ?>
						<a href="/emcee/contestant/<?= $contestant['contestant_number'] ?>" class="view-bio-btn">View Full Bio</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php else: ?>
<div class="no-contestants">
	<p>No contestants are currently available.</p>
</div>
<?php endif; ?>

<style>
.contestants-section {
	margin: 20px 0;
}

.contestant-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.contestant-card {
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 15px;
	background: #f9f9f9;
}

.contestant-image img {
	width: 100%;
	height: 200px;
	object-fit: cover;
	border-radius: 5px;
	margin-bottom: 10px;
}

.contestant-info h4 {
	margin: 0 0 5px 0;
	color: #333;
}

.contestant-number {
	font-weight: bold;
	color: #666;
	margin: 5px 0;
}

.bio-preview {
	color: #555;
	font-style: italic;
	margin: 10px 0;
}

.view-bio-btn {
	display: inline-block;
	background: #007cba;
	color: white;
	padding: 8px 16px;
	text-decoration: none;
	border-radius: 4px;
	margin-top: 10px;
}

.view-bio-btn:hover {
	background: #005a87;
}

.no-contestants {
	text-align: center;
	padding: 40px 20px;
	color: #666;
	font-style: italic;
}

@media (max-width: 768px) {
	.contestant-grid {
		grid-template-columns: 1fr;
	}
}
</style>
