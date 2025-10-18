<h2>Contest Scripts</h2>
<p><a href="/emcee">← Back to Emcee Dashboard</a></p>

<?php if (!empty($scripts)): ?>
<div class="scripts-section">
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
<?php else: ?>
<div class="no-scripts">
	<p>No active scripts are currently available.</p>
</div>
<?php endif; ?>

<style>
.scripts-section {
	margin: 20px 0;
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

.no-scripts {
	text-align: center;
	padding: 40px 20px;
	color: #666;
	font-style: italic;
}

@media (max-width: 768px) {
	.scripts-grid {
		grid-template-columns: 1fr;
	}
}
</style>
