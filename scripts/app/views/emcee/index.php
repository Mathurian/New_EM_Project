<h2>Emcee Dashboard</h2>
<p><a href="/">Back to Home</a> | <a href="/emcee/judges">View Judges by Category</a></p>

<?php if (!empty($scripts)): ?>
<div class="scripts-section">
	<h3>📋 Contest Scripts</h3>
	<div class="scripts-grid">
		<?php foreach ($scripts as $script): ?>
			<div class="script-card">
				<div class="script-info">
					<h4><?= htmlspecialchars($script['title']) ?></h4>
					<?php if (!empty($script['description'])): ?>
						<p class="script-description"><?= htmlspecialchars($script['description']) ?></p>
					<?php endif; ?>
					<p class="script-meta">
						<strong>File:</strong> <?= htmlspecialchars($script['file_name']) ?><br>
						<strong>Size:</strong> <?= number_format($script['file_size'] / 1024, 1) ?> KB<br>
						<strong>Uploaded:</strong> <?= date('M j, Y', strtotime($script['uploaded_at'])) ?>
					</p>
				</div>
				<div class="script-actions">
					<a href="/emcee/scripts/<?= urlencode($script['id']) ?>/view" target="_blank" class="view-script-btn">
						📄 View Script
					</a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

<div class="contestants-section">
	<h3>👥 Contestant Bios</h3>
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

<style>
.scripts-section {
	margin-bottom: 40px;
	padding: 20px;
	background: #f8f9fa;
	border-radius: 8px;
	border: 1px solid #dee2e6;
}

.scripts-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.script-card {
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	background: white;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	display: flex;
	flex-direction: column;
	justify-content: space-between;
}

.script-info h4 {
	margin: 0 0 10px 0;
	color: #333;
	font-size: 1.2em;
}

.script-description {
	color: #666;
	font-style: italic;
	margin: 10px 0;
}

.script-meta {
	font-size: 0.9em;
	color: #777;
	margin: 10px 0;
}

.script-actions {
	margin-top: 15px;
}

.view-script-btn {
	display: inline-block;
	background: #28a745;
	color: white;
	padding: 10px 20px;
	text-decoration: none;
	border-radius: 4px;
	font-weight: bold;
	transition: background-color 0.2s;
}

.view-script-btn:hover {
	background: #218838;
	color: white;
	text-decoration: none;
}

.contestants-section {
	margin-top: 20px;
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

@media (max-width: 768px) {
	.scripts-grid {
		grid-template-columns: 1fr;
	}
	
	.contestant-grid {
		grid-template-columns: 1fr;
	}
}
</style>
